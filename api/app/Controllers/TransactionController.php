<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Models\Conversation;
use App\Models\Transaction;
use App\Services\TrendBuilder;
use App\Core\AdminNotifier;

/** GET /me/stats/trend?range=30d */

class TransactionController
{
    /** POST /conversations/{id}/deals  */
    public function create(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $convId = (int) ($params['id'] ?? 0);
        $conv = Conversation::find($convId);
        if (!$conv) Response::error('Conversation not found', 404);

        $me = (int) $auth['id'];
        if ((int) $conv['buyer_id'] !== $me && (int) $conv['pasabuyer_id'] !== $me) {
            Response::error('Forbidden', 403);
        }

        $existing = Database::pdo()->prepare(
            "SELECT id FROM transactions
             WHERE conversation_id = ? AND status NOT IN ('completed','cancelled')
             LIMIT 1"
        );
        $existing->execute([$convId]);
        if ($existing->fetch()) {
            Response::error('A deal is already in progress for this chat', 422);
        }

        $desc  = trim((string) $request->input('item_description', ''));
        $price = $request->input('agreed_price');
        $notes = trim((string) $request->input('notes', ''));

        // Handle proof image — accept HEIC/HEIF too
        $proofImage = null;
        if (!empty($request->files['proof_image'])) {
            $file = $request->files['proof_image'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'], true)) {
                $dir = __DIR__ . '/../../storage/deals';
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                $fn = 'd_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dir . '/' . $fn)) {
                    $proofImage = 'storage/deals/' . $fn;
                }
            }
        }

        if ($desc === '') Response::error('Item description is required', 422);

        $id = Transaction::insert([
            'conversation_id'     => $convId,
            'post_id'             => (int) $conv['post_id'],
            'item_id'             => $conv['item_id'] ? (int) $conv['item_id'] : null,
            'buyer_id'            => (int) $conv['buyer_id'],
            'pasabuyer_id'        => (int) $conv['pasabuyer_id'],
            'proposed_by_user_id' => $me,
            'item_description'    => $desc,
            'agreed_price'        => is_numeric($price) ? (float) $price : null,
            'notes'               => $notes ?: null,
            'status'              => 'pending',
            'proof_image_path'    => $proofImage,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $other = (int) $conv['buyer_id'] === $me
            ? (int) $conv['pasabuyer_id']
            : (int) $conv['buyer_id'];
        Notifier::send(
            $other,
            'deal_proposed',
            'New deal proposed',
            $desc,
            ['transaction_id' => $id, 'conversation_id' => $convId]
        );

        Response::ok(['id' => $id], 'Deal proposed');
    }

    public function myTrend(Request $request, array $params = []): void
    {
        $auth  = Auth::require($request);
        $me    = (int) $auth['id'];
        $range = (string) ($request->query['range'] ?? '30d');

        $trend = (new TrendBuilder())->build($range, [
            'buyer_id'     => $me,
            'pasabuyer_id' => $me,
        ]);

        Response::ok($trend);
    }

    /** POST /deals/{id}/accept  */
    public function accept(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $tx   = $this->authorize($auth, (int) ($params['id'] ?? 0));

        if ($tx['status'] !== 'pending') {
            Response::error('This deal is not pending', 422);
        }
        if ((int) $tx['proposed_by_user_id'] === (int) $auth['id']) {
            Response::error('You proposed this deal — wait for the other party', 422);
        }

        Transaction::update((int) $tx['id'], [
            'status'     => 'in_progress',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->notifyOther(
            $tx,
            (int) $auth['id'],
            'deal_accepted',
            'Deal accepted!',
            'You can now proceed with the exchange.'
        );

        Response::ok(['status' => 'in_progress'], 'Deal accepted');
    }

    /** POST /deals/{id}/decline  */
    public function decline(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $tx   = $this->authorize($auth, (int) ($params['id'] ?? 0));
        if (!in_array($tx['status'], ['pending', 'in_progress'], true)) {
            Response::error('Deal cannot be declined now', 422);
        }

        Transaction::update((int) $tx['id'], [
            'status'        => 'cancelled',
            'cancelled_at'  => date('Y-m-d H:i:s'),
            'cancel_reason' => trim((string) $request->input('reason', '')) ?: null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->notifyOther(
            $tx,
            (int) $auth['id'],
            'deal_declined',
            'Deal was cancelled',
            'The other party cancelled the deal.'
        );

        Response::ok(null, 'Deal cancelled');
    }

    /** POST /deals/{id}/done  */
    public function done(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $tx   = $this->authorize($auth, (int) ($params['id'] ?? 0));

        if (!in_array($tx['status'], ['in_progress', 'buyer_done', 'pasabuyer_done'], true)) {
            Response::error('Deal is not in progress yet', 422);
        }

        $me = (int) $auth['id'];
        $update = ['updated_at' => date('Y-m-d H:i:s')];

        if ($me === (int) $tx['buyer_id']) {
            if ($tx['buyer_done_at']) Response::error('You already confirmed', 422);
            $update['buyer_done_at'] = date('Y-m-d H:i:s');
        } elseif ($me === (int) $tx['pasabuyer_id']) {
            if ($tx['pasabuyer_done_at']) Response::error('You already confirmed', 422);
            $update['pasabuyer_done_at'] = date('Y-m-d H:i:s');
        } else {
            Response::error('Forbidden', 403);
        }

        $buyerDone     = $update['buyer_done_at']     ?? $tx['buyer_done_at'];
        $pasabuyerDone = $update['pasabuyer_done_at'] ?? $tx['pasabuyer_done_at'];

        if ($buyerDone && $pasabuyerDone) {
            $update['status']       = 'completed';
            $update['completed_at'] = date('Y-m-d H:i:s');

            // ---- Compute 2% admin fee ----
            $config = require __DIR__ . '/../../config/config.php';
            $rate   = (float) ($config['fees']['admin_rate'] ?? 0.02);

            $price = $tx['agreed_price'];
            if ($price !== null && (float) $price > 0) {
                $gross = (float) $price;
                $fee   = round($gross * $rate, 2);
                $net   = round($gross - $fee, 2);

                $update['fee_rate']   = $rate;
                $update['admin_fee']  = $fee;
                $update['net_payout'] = $net;

                // Log to admin notification ledger
                Database::pdo()->prepare(
                    "INSERT INTO admin_notifications
                        (type, category, title, body, link, target_type, target_id, created_at)
                     VALUES ('fee_collected', 'transactions',
                             'Fee collected', ?, '/pasabuy/transactions',
                             'transaction', ?, NOW())"
                )->execute([
                    'Deal #' . $tx['id'] . ' · ₱' . number_format($fee, 2),
                    (int) $tx['id'],
                ]);
            } else {
                // No agreed price → no fee, but still mark complete
                $update['fee_rate']   = $rate;
                $update['admin_fee']  = 0;
                $update['net_payout'] = 0;
            }
        } elseif ($buyerDone) {
            $update['status'] = 'buyer_done';
        } elseif ($pasabuyerDone) {
            $update['status'] = 'pasabuyer_done';
        }

        Transaction::update((int) $tx['id'], $update);

        $this->notifyOther(
            $tx,
            $me,
            'deal_update',
            $update['status'] === 'completed' ? 'Deal completed!' : 'Deal updated',
            $update['status'] === 'completed'
                ? 'Both sides confirmed. Thanks!'
                : 'One side confirmed — waiting for you.'
        );

        Response::ok([
            'status'      => $update['status'],
            'admin_fee'   => $update['admin_fee']   ?? null,
            'net_payout'  => $update['net_payout']  ?? null,
        ], 'Updated');
    }

    /** POST /deals/{id}/dispute  */
    public function dispute(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $tx   = $this->authorize($auth, (int) ($params['id'] ?? 0));

        $reason = trim((string) $request->input('reason', ''));
        if ($reason === '') Response::error('Reason required', 422);

        Transaction::update((int) $tx['id'], [
            'status'         => 'disputed',
            'disputed_by'    => (int) $auth['id'],
            'dispute_reason' => $reason,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        // Notify admin users
        $admins = Database::pdo()->query(
            "SELECT id FROM users WHERE role = 'admin'"
        )->fetchAll();
        foreach ($admins as $a) {
            Notifier::send(
                (int) $a['id'],
                'dispute_opened',
                'Dispute opened on deal #' . $tx['id'],
                $reason,
                ['transaction_id' => (int) $tx['id']]
            );
        }

        AdminNotifier::push([
            'type'        => 'dispute_opened',
            'category'    => 'transactions',
            'title'       => 'Dispute opened on deal #' . $tx['id'],
            'body'        => $reason,
            'link'        => '/pasabuy/transactions',
            'target_type' => 'transaction',
            'target_id'   => (int) $tx['id'],
        ]);

        Response::ok(null, 'Dispute filed — admin will review.');
    }

    /** GET /me/deals  */
    public function myDeals(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $me   = (int) $auth['id'];

        $stmt = Database::pdo()->prepare(
            "SELECT t.*,
                    COALESCE(p.title, 'Deleted post') AS post_title,
                    COALESCE(p.type, 'offer')         AS post_type,
                    pi.title                          AS item_title,
                    buyer.email  AS buyer_email,
                    seller.email AS pasabuyer_email
             FROM transactions t
             LEFT JOIN posts p      ON p.id = t.post_id
             LEFT JOIN post_items pi ON pi.id = t.item_id
             LEFT JOIN users buyer  ON buyer.id = t.buyer_id
             LEFT JOIN users seller ON seller.id = t.pasabuyer_id
             WHERE t.buyer_id = ? OR t.pasabuyer_id = ?
             ORDER BY t.updated_at DESC
             LIMIT 200"
        );
        $stmt->execute([$me, $me]);

        $rows = array_map(function ($r) use ($me) {
            $roleAs = (int) $r['buyer_id'] === $me ? 'buyer' : 'pasabuyer';
            $otherEmail = $roleAs === 'buyer' ? $r['pasabuyer_email'] : $r['buyer_email'];
            return [
                'id'                => (int) $r['id'],
                'conversation_id'   => (int) $r['conversation_id'],
                'post_id'           => $r['post_id'] ? (int) $r['post_id'] : null,
                'item_id'           => $r['item_id'] ? (int) $r['item_id'] : null,   // ← ADDED
                'post_title'        => $r['post_title'],
                'item_title'        => $r['item_title'] ?? '',                       // ← ADDED
                'post_type'         => $r['post_type'],
                'role'              => $roleAs,
                'other_party_email' => $otherEmail,
                'item_description'  => $r['item_description'],
                'agreed_price'      => $r['agreed_price'],
                'admin_fee'         => $r['admin_fee'],
                'net_payout'        => $r['net_payout'],
                'fee_rate'          => $r['fee_rate'],
                'currency'          => $r['currency'],
                'status'            => $r['status'],
                'buyer_done'        => $r['buyer_done_at'] !== null,
                'pasabuyer_done'    => $r['pasabuyer_done_at'] !== null,
                'proof_image_url'   => !empty($r['proof_image_path'])
                    ? rtrim((require __DIR__ . '/../../config/config.php')['app']['url'], '/')
                        . '/' . ltrim($r['proof_image_path'], '/')
                    : null,
                'created_at'        => $r['created_at'],
                'completed_at'      => $r['completed_at'],
            ];
        }, $stmt->fetchAll());

        Response::ok(['data' => $rows]);
    }

    /** GET /me/stats  */
    public function myStats(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $me   = (int) $auth['id'];
        $db   = Database::pdo();

        $stats = [];
        foreach (['buyer_id' => 'as_buyer', 'pasabuyer_id' => 'as_pasabuyer'] as $col => $key) {
            $stmt = $db->prepare(
                "SELECT
                    COUNT(*) AS total,
                    SUM(status IN ('pending','in_progress','buyer_done','pasabuyer_done')) AS active,
                    SUM(status = 'completed') AS completed,
                    SUM(status = 'disputed')  AS disputed,
                    SUM(status = 'cancelled') AS cancelled,
                    COALESCE(SUM(CASE WHEN status='completed' THEN agreed_price END), 0) AS value,
                    COALESCE(SUM(CASE WHEN status='completed' THEN admin_fee END),   0) AS fees_paid,
                    COALESCE(SUM(CASE WHEN status='completed' THEN net_payout END),  0) AS net_earned
                 FROM transactions WHERE $col = ?"
            );
            $stmt->execute([$me]);
            $r = $stmt->fetch();
            $stats[$key] = [
                'total'       => (int) $r['total'],
                'active'      => (int) $r['active'],
                'completed'   => (int) $r['completed'],
                'disputed'    => (int) $r['disputed'],
                'cancelled'   => (int) $r['cancelled'],
                'value'       => (float) $r['value'],
                'fees_paid'   => (float) $r['fees_paid'],
                'net_earned'  => (float) $r['net_earned'],
            ];
        }

        Response::ok($stats);
    }

    /** GET /me/stats/by-item?range=30d */
    public function myItemsBreakdown(Request $request, array $params = []): void
    {
        $auth  = Auth::require($request);
        $me    = (int) $auth['id'];
        $range = (string) ($request->query['range'] ?? 'all');

        $dateFilter = '';
        if (in_array($range, ['7d', '30d', '90d'], true)) {
            $days = (int) substr($range, 0, -1);
            $dateFilter = " AND t.completed_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        } elseif ($range === '12m') {
            $dateFilter = " AND t.completed_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }

        $stmt = Database::pdo()->prepare(
            "SELECT
                COALESCE(NULLIF(pi.title, ''), t.item_description) AS item_name,
                t.post_id,
                COUNT(*) AS deal_count,
                COALESCE(SUM(t.agreed_price), 0) AS gmv,
                COALESCE(SUM(t.admin_fee), 0)    AS total_fee,
                COALESCE(SUM(t.net_payout), 0)   AS total_net
             FROM transactions t
             LEFT JOIN post_items pi ON pi.id = t.item_id
             WHERE (t.buyer_id = ? OR t.pasabuyer_id = ?)
               AND t.status = 'completed'
               $dateFilter
             GROUP BY COALESCE(NULLIF(pi.title, ''), t.item_description), t.post_id
             ORDER BY gmv DESC
             LIMIT 25"
        );
        $stmt->execute([$me, $me]);

        $rows = array_map(fn($r) => [
            'item_name'  => $r['item_name'],
            'post_id'    => (int) $r['post_id'],
            'deal_count' => (int) $r['deal_count'],
            'gmv'        => (float) $r['gmv'],
            'total_fee'  => (float) $r['total_fee'],
            'total_net'  => (float) $r['total_net'],
        ], $stmt->fetchAll());

        Response::ok(['data' => $rows, 'range' => $range]);
    }

    // ---------------- helpers ----------------

    private function authorize(array $auth, int $txId): array
    {
        $tx = Transaction::find($txId);
        if (!$tx) Response::error('Transaction not found', 404);
        $me = (int) $auth['id'];
        if ((int) $tx['buyer_id'] !== $me && (int) $tx['pasabuyer_id'] !== $me) {
            Response::error('Forbidden', 403);
        }
        return $tx;
    }

    private function notifyOther(array $tx, int $actorId, string $type, string $title, string $body): void
    {
        $other = (int) $tx['buyer_id'] === $actorId
            ? (int) $tx['pasabuyer_id']
            : (int) $tx['buyer_id'];
        Notifier::send($other, $type, $title, $body, [
            'transaction_id'  => (int) $tx['id'],
            'conversation_id' => (int) $tx['conversation_id'],
        ]);
    }
}