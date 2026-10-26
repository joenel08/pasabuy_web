<?php
namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\View;
use App\Controllers\TransactionController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class AnalyticsController
{
    public function index(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $range = (string) ($request->query['range'] ?? '30d');

        // Reuse the transaction controller's trend builder (system-wide scope)
        $builder = new TransactionController();
        $trend   = $builder->buildTrend($range, []);

        // Top pasabuyers
        $topPasabuyers = $this->topUsers('pasabuyer_id');
        $topBuyers     = $this->topUsers('buyer_id');

        // Recent disputes
        $disputes = Database::pdo()->query(
            "SELECT t.id, t.item_description, t.dispute_reason, t.updated_at,
                    u.email AS disputed_by_email
             FROM transactions t
             LEFT JOIN users u ON u.id = t.disputed_by
             WHERE t.status = 'disputed'
             ORDER BY t.updated_at DESC
             LIMIT 10"
        )->fetchAll();

        // Overall counts
        $stats = Database::pdo()->query(
            "SELECT
                (SELECT COUNT(*) FROM users WHERE role='user') AS users,
                (SELECT COUNT(*) FROM posts)                   AS posts,
                (SELECT COUNT(*) FROM posts WHERE status='open') AS open_posts,
                (SELECT COUNT(*) FROM transactions)             AS total_deals,
                (SELECT COUNT(*) FROM transactions WHERE status='completed') AS completed_deals,
                (SELECT COUNT(*) FROM transactions WHERE status='disputed')  AS disputed_deals,
                (SELECT COALESCE(SUM(agreed_price),0) FROM transactions WHERE status='completed') AS total_value
            "
        )->fetch();

        View::render('analytics.index', [
            'title'          => 'Analytics',
            'range'          => $range,
            'trend'          => $trend,
            'stats'          => $stats,
            'topPasabuyers'  => $topPasabuyers,
            'topBuyers'      => $topBuyers,
            'disputes'       => $disputes,
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
        if (!$u) { http_response_code(404); echo 'User not found'; return; }

        $profile = Database::pdo()->prepare("SELECT * FROM profiles WHERE user_id = ?");
        $profile->execute([$userId]);
        $p = $profile->fetch() ?: [];

        $builder = new TransactionController();
        $trend   = $builder->buildTrend($range, ['user_id' => $userId]);

        // Recent deals for this user
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
        $builder = new TransactionController();
        Response::ok($builder->buildTrend($range, $scope));
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
}