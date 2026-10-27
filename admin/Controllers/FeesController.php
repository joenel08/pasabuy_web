<?php
namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\Flash;
use Admin\Core\View;
use App\Core\Database;
use App\Core\Notifier;
use App\Core\Request;

class FeesController
{
    // =================================================================
    // GET /pasabuy/fees?tab=outstanding|collected|summary
    // =================================================================
    public function index(Request $request, array $params = []): void
    {
        AdminAuth::require();

        $tab = (string) ($request->query['tab'] ?? 'outstanding');
        if (!in_array($tab, ['outstanding', 'collected', 'summary'], true)) {
            $tab = 'outstanding';
        }

        $stats = $this->buildStats();

        $data = [
            'title' => 'Fees',
            'tab'   => $tab,
            'stats' => $stats,
        ];

        if ($tab === 'outstanding') {
            $config = require __DIR__ . '/../../api/config/config.php';
            $data['rows']            = $this->queryOutstanding();
            $data['block_threshold'] = (float) ($config['fees']['debt_block_threshold'] ?? 500);
            $data['block_days']      = (int)   ($config['fees']['debt_block_days']      ?? 30);
        } elseif ($tab === 'collected') {
            $from   = (string) ($request->query['from']   ?? date('Y-m-01'));
            $to     = (string) ($request->query['to']     ?? date('Y-m-d'));
            $search = trim((string) ($request->query['search'] ?? ''));

            $rows = $this->queryCollected($from, $to, $search);

            $data['from']           = $from;
            $data['to']             = $to;
            $data['search']         = $search;
            $data['collected']      = $rows;
            $data['filtered_total'] = array_sum(array_map(fn($r) => (float) $r['admin_fee'], $rows));
            $data['filtered_count'] = count($rows);
        } elseif ($tab === 'summary') {
            $data['monthly']    = $this->queryMonthlyTrend();
            $data['top_payers'] = $this->queryTopPayers();
        }

        View::render('fees.index', $data);
    }

    // =================================================================
    // GET /pasabuy/fees/{userId} — per-user detail (unchanged)
    // =================================================================
    public function show(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $userId = (int) ($params['id'] ?? 0);

        $user = Database::pdo()->prepare(
            "SELECT u.id, u.email, pr.firstname, pr.lastname
             FROM users u LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE u.id = ? LIMIT 1"
        );
        $user->execute([$userId]);
        $u = $user->fetch();
        if (!$u) { http_response_code(404); echo 'User not found'; return; }

        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.item_description, t.agreed_price, t.admin_fee,
                    t.completed_at,
                    p.title AS post_title,
                    pi.title AS item_title
             FROM transactions t
             LEFT JOIN posts p       ON p.id  = t.post_id
             LEFT JOIN post_items pi ON pi.id = t.item_id
             WHERE t.pasabuyer_id = ?
               AND t.status = 'completed'
               AND t.fee_paid_at IS NULL
               AND t.admin_fee > 0
             ORDER BY t.completed_at ASC"
        );
        $stmt->execute([$userId]);
        $unpaid = $stmt->fetchAll();

        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.admin_fee, t.fee_paid_at, t.fee_payment_notes,
                    a.email AS paid_by_email
             FROM transactions t
             LEFT JOIN users a ON a.id = t.fee_paid_by
             WHERE t.pasabuyer_id = ?
               AND t.fee_paid_at IS NOT NULL
             ORDER BY t.fee_paid_at DESC
             LIMIT 50"
        );
        $stmt->execute([$userId]);
        $paid = $stmt->fetchAll();

        $totalUnpaid = array_sum(array_map(fn($r) => (float) $r['admin_fee'], $unpaid));

        View::render('fees.show', [
            'title'        => 'Fees · ' . $u['email'],
            'user'         => $u,
            'unpaid'       => $unpaid,
            'paid'         => $paid,
            'total_unpaid' => $totalUnpaid,
        ]);
    }

    // =================================================================
    // POST /pasabuy/fees/{userId}/mark-paid (unchanged)
    // =================================================================
    public function markPaid(Request $request, array $params = []): void
    {
        $admin  = AdminAuth::require();
        $userId = (int) ($params['id'] ?? 0);

        $ids = $request->input('transaction_ids', []);
        if (!is_array($ids) || empty($ids)) {
            Flash::set('error', 'No transactions selected.');
            header('Location: /pasabuy/fees/' . $userId); exit;
        }

        $ids = array_map('intval', $ids);
        $in  = implode(',', array_fill(0, count($ids), '?'));

        $check = Database::pdo()->prepare(
            "SELECT id FROM transactions
             WHERE id IN ($in) AND pasabuyer_id = ?
               AND status = 'completed' AND fee_paid_at IS NULL"
        );
        $check->execute(array_merge($ids, [$userId]));
        $validIds = array_column($check->fetchAll(), 'id');

        if (empty($validIds)) {
            Flash::set('error', 'None of the selected fees are eligible.');
            header('Location: /pasabuy/fees/' . $userId); exit;
        }

        $notes = trim((string) $request->input('notes', ''));
        $in2   = implode(',', array_fill(0, count($validIds), '?'));

        Database::pdo()->prepare(
            "UPDATE transactions
             SET fee_paid_at = NOW(), fee_paid_by = ?, fee_payment_notes = ?
             WHERE id IN ($in2)"
        )->execute(array_merge([(int) $admin['id'], $notes ?: null], $validIds));

        try {
            Notifier::send(
                $userId, 'fee_paid', 'Payment received',
                'We received your payment for ' . count($validIds) . ' fee(s). Thank you!',
                ['count' => count($validIds)]
            );
        } catch (\Throwable $e) {
            error_log('[FEE] notify failed: ' . $e->getMessage());
        }

        Flash::set('success', count($validIds) . ' fee(s) marked as paid.');
        header('Location: /pasabuy/fees/' . $userId); exit;
    }

    // =================================================================
    // POST /pasabuy/fees/{userId}/mark-all-paid (unchanged)
    // =================================================================
    public function markAllPaid(Request $request, array $params = []): void
    {
        $admin  = AdminAuth::require();
        $userId = (int) ($params['id'] ?? 0);
        $notes  = trim((string) $request->input('notes', ''));

        $stmt = Database::pdo()->prepare(
            "UPDATE transactions
             SET fee_paid_at = NOW(), fee_paid_by = ?, fee_payment_notes = ?
             WHERE pasabuyer_id = ?
               AND status = 'completed'
               AND fee_paid_at IS NULL
               AND admin_fee > 0"
        );
        $stmt->execute([(int) $admin['id'], $notes ?: null, $userId]);
        $count = $stmt->rowCount();

        if ($count > 0) {
            try {
                Notifier::send(
                    $userId, 'fee_paid', 'Payment received',
                    'All your outstanding fees have been settled. Thank you!',
                    []
                );
            } catch (\Throwable $e) {
                error_log('[FEE] notify failed: ' . $e->getMessage());
            }
        }

        Flash::set('success', $count . ' fee(s) marked as paid.');
        header('Location: /pasabuy/fees/' . $userId); exit;
    }

    // =================================================================
    // GET /pasabuy/fees/export.csv — download filtered collected ledger
    // =================================================================
    public function exportCsv(Request $request, array $params = []): void
    {
        AdminAuth::require();

        $from   = (string) ($request->query['from']   ?? date('Y-m-01'));
        $to     = (string) ($request->query['to']     ?? date('Y-m-d'));
        $search = trim((string) ($request->query['search'] ?? ''));

        $rows = $this->queryCollected($from, $to, $search);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="fees_collected_' . $from . '_to_' . $to . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Transaction ID', 'Paid At', 'Pasabuyer', 'Item', 'Agreed Price', 'Fee', 'Confirmed By', 'Notes']);

        foreach ($rows as $r) {
            $name = trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? '')) ?: $r['user_email'];
            fputcsv($out, [
                '#' . $r['id'],
                $r['fee_paid_at'],
                $name,
                $r['item_title'] ?: $r['item_description'],
                number_format((float) $r['agreed_price'], 2, '.', ''),
                number_format((float) $r['admin_fee'],    2, '.', ''),
                $r['paid_by_email'] ?? '—',
                $r['fee_payment_notes'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    // =================================================================
    // Helpers
    // =================================================================
    private function buildStats(): array
    {
        $db = Database::pdo();

        $today = (float) $db->query(
            "SELECT COALESCE(SUM(admin_fee),0) FROM transactions
             WHERE fee_paid_at IS NOT NULL AND DATE(fee_paid_at) = CURDATE()"
        )->fetchColumn();

        $thisWeek = (float) $db->query(
            "SELECT COALESCE(SUM(admin_fee),0) FROM transactions
             WHERE fee_paid_at IS NOT NULL AND fee_paid_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )->fetchColumn();

        $thisMonth = (float) $db->query(
            "SELECT COALESCE(SUM(admin_fee),0) FROM transactions
             WHERE fee_paid_at IS NOT NULL
               AND fee_paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        )->fetchColumn();

        $lifetime = (float) $db->query(
            "SELECT COALESCE(SUM(admin_fee),0) FROM transactions WHERE fee_paid_at IS NOT NULL"
        )->fetchColumn();

        $outstanding = (float) $db->query(
            "SELECT COALESCE(SUM(admin_fee),0) FROM transactions
             WHERE fee_paid_at IS NULL AND status = 'completed' AND admin_fee > 0"
        )->fetchColumn();

        $unpaidUsers = (int) $db->query(
            "SELECT COUNT(DISTINCT pasabuyer_id) FROM transactions
             WHERE fee_paid_at IS NULL AND status = 'completed' AND admin_fee > 0"
        )->fetchColumn();

        $lifetimeCount = (int) $db->query(
            "SELECT COUNT(*) FROM transactions
             WHERE fee_paid_at IS NOT NULL AND admin_fee > 0"
        )->fetchColumn();

        return [
            'today'          => $today,
            'this_week'      => $thisWeek,
            'this_month'     => $thisMonth,
            'lifetime'       => $lifetime,
            'outstanding'    => $outstanding,
            'unpaid_users'   => $unpaidUsers,
            'lifetime_count' => $lifetimeCount,
        ];
    }

    private function queryOutstanding(): array
    {
        return Database::pdo()->query(
            "SELECT
                u.id AS user_id,
                u.email,
                pr.firstname, pr.lastname,
                COUNT(t.id)            AS unpaid_count,
                SUM(t.admin_fee)       AS unpaid_total,
                MIN(t.completed_at)    AS oldest_unpaid_at,
                MAX(t.completed_at)    AS newest_unpaid_at
             FROM users u
             JOIN transactions t ON t.pasabuyer_id = u.id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE t.status = 'completed'
               AND t.fee_paid_at IS NULL
               AND t.admin_fee > 0
             GROUP BY u.id, u.email, pr.firstname, pr.lastname
             ORDER BY unpaid_total DESC"
        )->fetchAll();
    }

    private function queryCollected(string $from, string $to, string $search): array
    {
        $where = ["t.fee_paid_at IS NOT NULL", "t.admin_fee > 0"];
        $bind  = [];

        if ($from !== '') {
            $where[] = "t.fee_paid_at >= ?";
            $bind[]  = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = "t.fee_paid_at <= ?";
            $bind[]  = $to . ' 23:59:59';
        }
        if ($search !== '') {
            $where[] = "(u.email LIKE ? OR pr.firstname LIKE ? OR pr.lastname LIKE ?)";
            $bind[]  = "%$search%";
            $bind[]  = "%$search%";
            $bind[]  = "%$search%";
        }

        $whereSql = implode(' AND ', $where);

        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.admin_fee, t.agreed_price,
                    t.fee_paid_at, t.fee_payment_notes, t.item_description,
                    u.email AS user_email,
                    pr.firstname, pr.lastname,
                    a.email AS paid_by_email,
                    pi.title AS item_title,
                    p.title AS post_title
             FROM transactions t
             JOIN users u           ON u.id  = t.pasabuyer_id
             LEFT JOIN profiles pr  ON pr.user_id = u.id
             LEFT JOIN users a      ON a.id  = t.fee_paid_by
             LEFT JOIN post_items pi ON pi.id = t.item_id
             LEFT JOIN posts p      ON p.id  = t.post_id
             WHERE $whereSql
             ORDER BY t.fee_paid_at DESC
             LIMIT 500"
        );
        $stmt->execute($bind);
        return $stmt->fetchAll();
    }

    private function queryMonthlyTrend(): array
    {
        $rows = Database::pdo()->query(
            "SELECT DATE_FORMAT(fee_paid_at, '%Y-%m') AS month,
                    SUM(admin_fee) AS total,
                    COUNT(*)       AS cnt
             FROM transactions
             WHERE fee_paid_at IS NOT NULL AND admin_fee > 0
               AND fee_paid_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY month
             ORDER BY month"
        )->fetchAll();

        $byMonth = [];
        foreach ($rows as $r) {
            $byMonth[$r['month']] = [
                'total' => (float) $r['total'],
                'count' => (int) $r['cnt'],
            ];
        }

        $points = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-$i months"));
            $points[] = [
                'month' => $m,
                'label' => date('M Y', strtotime("-$i months")),
                'total' => $byMonth[$m]['total'] ?? 0,
                'count' => $byMonth[$m]['count'] ?? 0,
            ];
        }
        return $points;
    }

    private function queryTopPayers(): array
    {
        return Database::pdo()->query(
            "SELECT u.id, u.email, pr.firstname, pr.lastname,
                    SUM(t.admin_fee) AS total_paid,
                    COUNT(*)         AS fee_count
             FROM transactions t
             JOIN users u          ON u.id = t.pasabuyer_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE t.fee_paid_at IS NOT NULL AND t.admin_fee > 0
             GROUP BY u.id, u.email, pr.firstname, pr.lastname
             ORDER BY total_paid DESC
             LIMIT 10"
        )->fetchAll();
    }
}