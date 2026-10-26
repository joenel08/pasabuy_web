<?php

namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\View;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\TrendBuilder;

class AnalyticsController
{
    public function index(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $range = (string) ($request->query['range'] ?? '30d');

        $trend = (new TrendBuilder())->build($range, []);

        $topPasabuyers = $this->topUsers('pasabuyer_id');
        $topBuyers     = $this->topUsers('buyer_id');

        $disputes = Database::pdo()->query(
            "SELECT t.id, t.item_description, t.dispute_reason, t.updated_at,
                    u.email AS disputed_by_email
             FROM transactions t
             LEFT JOIN users u ON u.id = t.disputed_by
             WHERE t.status = 'disputed'
             ORDER BY t.updated_at DESC
             LIMIT 10"
        )->fetchAll();

        $topRated = Database::pdo()->query(
            "SELECT u.id, u.email,
            COUNT(*) AS reviews,
            ROUND(AVG(r.rating), 2) AS avg_rating,
            SUM(r.rating = 5) AS fives
     FROM reviews r
     JOIN users u ON u.id = r.reviewee_id
     GROUP BY u.id, u.email
     HAVING reviews >= 1
     ORDER BY avg_rating DESC, reviews DESC
     LIMIT 10"
        )->fetchAll();

        $stats = Database::pdo()->query(
            "SELECT
        (SELECT COUNT(*) FROM users WHERE role='user') AS users,
        (SELECT COUNT(*) FROM posts)                   AS posts,
        (SELECT COUNT(*) FROM posts WHERE status='open') AS open_posts,
        (SELECT COUNT(*) FROM transactions)             AS total_deals,
        (SELECT COUNT(*) FROM transactions WHERE status='completed') AS completed_deals,
        (SELECT COUNT(*) FROM transactions WHERE status='disputed')  AS disputed_deals,
        (SELECT COALESCE(SUM(agreed_price),0) FROM transactions WHERE status='completed') AS total_value,
        (SELECT COALESCE(SUM(admin_fee),0) FROM transactions WHERE status='completed')    AS total_fees
    "
        )->fetch();
        $topItems = Database::pdo()->query(
    "SELECT
        COALESCE(NULLIF(pi.title, ''), t.item_description) AS item_name,
        COUNT(*) AS deal_count,
        COALESCE(SUM(t.agreed_price), 0) AS gmv,
        COALESCE(SUM(t.admin_fee), 0) AS total_fee
     FROM transactions t
     LEFT JOIN post_items pi ON pi.id = t.item_id
     WHERE t.status = 'completed'
       AND t.completed_at >= DATE_SUB(NOW(), INTERVAL " .
         ($range === '7d' ? '7 DAY' :
          ($range === '90d' ? '90 DAY' :
          ($range === '12m' ? '12 MONTH' : '30 DAY'))) . ")
     GROUP BY COALESCE(NULLIF(pi.title, ''), t.item_description)
     ORDER BY gmv DESC
     LIMIT 5"
)->fetchAll();



        
        View::render('analytics.index', [
            'title'          => 'Analytics',
            'range'          => $range,
            'trend'          => $trend,
            'stats'          => $stats,
            'topPasabuyers'  => $topPasabuyers,
            'topBuyers'      => $topBuyers,
            'disputes'       => $disputes,
            'topRated' => $topRated,
            'topItems' => $topItems,
        ]);
    }

    public function user(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $userId = (int) ($params['id'] ?? 0);
        $range  = (string) ($request->query['range'] ?? '30d');

        $user = Database::pdo()->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $user->execute([$userId]);
        $u = $user->fetch();
        if (!$u) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        $profile = Database::pdo()->prepare("SELECT * FROM profiles WHERE user_id = ?");
        $profile->execute([$userId]);
        $p = $profile->fetch() ?: [];

        $trend = (new TrendBuilder())->build($range, ['user_id' => $userId]);

        $recent = Database::pdo()->prepare(
            "SELECT t.*, p.title AS post_title,
                    buyer.email AS buyer_email,
                    seller.email AS pasabuyer_email
             FROM transactions t
             LEFT JOIN posts p ON p.id = t.post_id
             LEFT JOIN users buyer ON buyer.id = t.buyer_id
             LEFT JOIN users seller ON seller.id = t.pasabuyer_id
             WHERE t.buyer_id = ? OR t.pasabuyer_id = ?
             ORDER BY t.updated_at DESC LIMIT 30"
        );
        $recent->execute([$userId, $userId]);

        View::render('analytics.user', [
            'title'   => 'Analytics · ' . $u['email'],
            'user'    => $u,
            'profile' => $p,
            'range'   => $range,
            'trend'   => $trend,
            'recent'  => $recent->fetchAll(),
        ]);
    }

    public function dataJson(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $range  = (string) ($request->query['range'] ?? '30d');
        $userId = $request->query['user_id'] ?? null;

        $scope = $userId ? ['user_id' => (int) $userId] : [];
        Response::ok((new TrendBuilder())->build($range, $scope));
    }

    private function topUsers(string $column): array
    {
        $sql = "SELECT u.id, u.email,
                       COUNT(*) AS total,
                       SUM(t.status = 'completed') AS completed,
                       COALESCE(SUM(CASE WHEN t.status='completed' THEN t.agreed_price END), 0) AS value
                FROM transactions t
                JOIN users u ON u.id = t.$column
                WHERE t.status = 'completed'
                GROUP BY u.id, u.email
                ORDER BY completed DESC
                LIMIT 10";
        return Database::pdo()->query($sql)->fetchAll();
    }

    /** GET /pasabuy/analytics/by-item?range=30d — full system GMV per item */
public function itemsBreakdown(Request $request, array $params = []): void
{
    AdminAuth::require();

    $range = (string) ($request->query['range'] ?? '30d');

    $dateFilter = '';
    if (in_array($range, ['7d', '30d', '90d'], true)) {
        $days = (int) substr($range, 0, -1);
        $dateFilter = " AND t.completed_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";
    } elseif ($range === '12m') {
        $dateFilter = " AND t.completed_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
    }

    $rows = Database::pdo()->query(
    "SELECT
        COALESCE(NULLIF(pi.title, ''), t.item_description) AS item_name,
        t.post_id,
        COUNT(*) AS deal_count,
        COALESCE(SUM(t.agreed_price), 0) AS gmv,
        COALESCE(SUM(t.admin_fee), 0)    AS total_fee,
        COALESCE(SUM(t.net_payout), 0)   AS total_net
     FROM transactions t
     LEFT JOIN post_items pi ON pi.id = t.item_id
     WHERE t.status = 'completed' {$dateFilter}
     GROUP BY COALESCE(NULLIF(pi.title, ''), t.item_description), t.post_id
     ORDER BY gmv DESC
     LIMIT 100"
)->fetchAll();

    View::render('analytics.items', [
        'title' => 'GMV by Item',
        'items' => $rows,
        'range' => $range,
    ]);
}
}
