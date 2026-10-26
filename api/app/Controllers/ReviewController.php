<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Models\Transaction;

class ReviewController
{
    /** GET /users/{id}/reviews */
    /** GET /users/{id}/reviews */
    public function forUser(Request $request, array $params = []): void
    {
        Auth::require($request);
        $userId = (int) ($params['id'] ?? 0);

        $stmt = Database::pdo()->prepare(
            "SELECT r.*, u.email AS reviewer_email
         FROM reviews r
         JOIN users u ON u.id = r.reviewer_id
         WHERE r.reviewee_id = ?
         ORDER BY r.created_at DESC
         LIMIT 100"
        );
        $stmt->execute([$userId]);

        $rows = array_map(fn($r) => [
            'id'             => (int) $r['id'],
            'rating'         => (int) $r['rating'],
            'description'    => $r['description'],
            'reviewer_email' => $r['reviewer_email'],
            'created_at'     => $r['created_at'],
        ], $stmt->fetchAll());

        // Average + count
        $avg = Database::pdo()->prepare(
            "SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM reviews WHERE reviewee_id = ?"
        );
        $avg->execute([$userId]);
        $a = $avg->fetch();

        // Distribution per star level
        $dist = Database::pdo()->prepare(
            "SELECT rating, COUNT(*) AS cnt
         FROM reviews WHERE reviewee_id = ?
         GROUP BY rating"
        );
        $dist->execute([$userId]);

        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($dist->fetchAll() as $d) {
            $distribution[(int) $d['rating']] = (int) $d['cnt'];
        }

        Response::ok([
            'data'          => $rows,
            'average'       => $a['avg'] !== null ? round((float) $a['avg'], 2) : null,
            'total'         => (int) $a['cnt'],
            'distribution'  => $distribution,
        ]);
    }

    /** POST /transactions/{id}/review */
    public function store(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $txId = (int) ($params['id'] ?? 0);
        $tx   = Transaction::find($txId);
        if (!$tx) Response::error('Transaction not found', 404);
        if ($tx['status'] !== 'completed') {
            Response::error('You can only review completed deals', 422);
        }

        $me = (int) $auth['id'];
        if ((int) $tx['buyer_id'] !== $me && (int) $tx['pasabuyer_id'] !== $me) {
            Response::error('Forbidden', 403);
        }

        // Already reviewed?
        $check = Database::pdo()->prepare(
            "SELECT id FROM reviews WHERE transaction_id=? AND reviewer_id=? LIMIT 1"
        );
        $check->execute([$txId, $me]);
        if ($check->fetch()) {
            Response::error('You already reviewed this deal', 422);
        }

        $rating = (int) $request->input('rating', 0);
        $desc   = trim((string) $request->input('description', ''));

        if ($rating < 1 || $rating > 5) {
            Response::error('Rating must be 1–5', 422);
        }

        $reviewee = (int) $tx['buyer_id'] === $me
            ? (int) $tx['pasabuyer_id']
            : (int) $tx['buyer_id'];

        Database::pdo()->prepare(
            "INSERT INTO reviews (transaction_id, reviewer_id, reviewee_id, rating, description, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        )->execute([$txId, $me, $reviewee, $rating, $desc ?: null]);

        Notifier::send(
            $reviewee,
            'new_review',
            'You received a new review',
            "$rating ★ from a recent deal",
            ['transaction_id' => $txId]
        );

        Response::ok(null, 'Review submitted');
    }

    /** GET /me/reviews/pending  — completed deals I haven't reviewed */
    public function pending(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $me   = (int) $auth['id'];

        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.item_description, t.completed_at,
                    buyer.email AS buyer_email,
                    seller.email AS pasabuyer_email,
                    t.buyer_id, t.pasabuyer_id
             FROM transactions t
             JOIN users buyer ON buyer.id = t.buyer_id
             JOIN users seller ON seller.id = t.pasabuyer_id
             LEFT JOIN reviews r ON r.transaction_id = t.id AND r.reviewer_id = ?
             WHERE t.status='completed'
               AND (t.buyer_id = ? OR t.pasabuyer_id = ?)
               AND r.id IS NULL
             ORDER BY t.completed_at DESC
             LIMIT 20"
        );
        $stmt->execute([$me, $me, $me]);

        $rows = array_map(function ($r) use ($me) {
            $iAmBuyer = (int) $r['buyer_id'] === $me;
            return [
                'transaction_id'   => (int) $r['id'],
                'item_description' => $r['item_description'],
                'completed_at'     => $r['completed_at'],
                'other_party'      => $iAmBuyer ? $r['pasabuyer_email'] : $r['buyer_email'],
            ];
        }, $stmt->fetchAll());

        Response::ok(['data' => $rows]);
    }

    /** GET /users/{id}/review-eligibility */
    public function eligibility(Request $request, array $params = []): void
    {
        $auth   = Auth::require($request);
        $me     = (int) $auth['id'];
        $target = (int) ($params['id'] ?? 0);
        if ($target === $me) {
            Response::ok(['can_review' => false, 'reason' => 'self']);
        }

        $stmt = Database::pdo()->prepare(
            "SELECT t.id FROM transactions t
         LEFT JOIN reviews r ON r.transaction_id = t.id AND r.reviewer_id = ?
         WHERE t.status = 'completed'
           AND ((t.buyer_id = ? AND t.pasabuyer_id = ?)
             OR (t.buyer_id = ? AND t.pasabuyer_id = ?))
           AND r.id IS NULL
         ORDER BY t.completed_at DESC LIMIT 1"
        );
        $stmt->execute([$me, $me, $target, $target, $me]);
        $row = $stmt->fetch();

        if ($row) {
            Response::ok(['can_review' => true, 'transaction_id' => (int) $row['id']]);
        } else {
            Response::ok(['can_review' => false, 'reason' => 'no_deal']);
        }
    }
}
