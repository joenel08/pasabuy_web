<?php
namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class NotificationController
{
    public function unread(Request $request, array $params = []): void
    {
        $admin = AdminAuth::require();
        $uid   = (int) $admin['id'];

        $countStmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL"
        );
        $countStmt->execute([$uid]);
        $count = (int) $countStmt->fetchColumn();

        $listStmt = Database::pdo()->prepare(
            "SELECT id, type, title, body, data, read_at, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY id DESC
             LIMIT 10"
        );
        $listStmt->execute([$uid]);
        $rows = $listStmt->fetchAll();

        Response::ok([
            'count' => $count,
            'items' => array_map(fn($r) => $this->rowPayload($r), $rows),
        ]);
    }

    public function list(Request $request, array $params = []): void
    {
        $admin = AdminAuth::require();
        $uid   = (int) $admin['id'];

        $stmt = Database::pdo()->prepare(
            "SELECT id, type, title, body, data, read_at, created_at
             FROM notifications
             WHERE user_id = ?
             ORDER BY id DESC
             LIMIT 200"
        );
        $stmt->execute([$uid]);

        Response::ok([
            'items' => array_map(fn($r) => $this->rowPayload($r), $stmt->fetchAll()),
        ]);
    }

    public function markRead(Request $request, array $params = []): void
    {
        $admin = AdminAuth::require();
        $id    = (int) ($params['id'] ?? 0);

        Database::pdo()->prepare(
            "UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?"
        )->execute([$id, (int) $admin['id']]);

        Response::ok(null, 'Marked read');
    }

    public function markAllRead(Request $request, array $params = []): void
    {
        $admin = AdminAuth::require();

        Database::pdo()->prepare(
            "UPDATE notifications SET read_at = NOW()
             WHERE user_id = ? AND read_at IS NULL"
        )->execute([(int) $admin['id']]);

        Response::ok(null, 'All marked read');
    }

    public function badges(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $db = Database::pdo();

        Response::ok([
            'users_pending'   => (int) $db->query(
                "SELECT COUNT(*) FROM users WHERE status = 'pending' AND role != 'admin'"
            )->fetchColumn(),
            'kyc_pending'     => (int) $db->query(
                "SELECT COUNT(*) FROM id_verifications WHERE status = 'pending'"
            )->fetchColumn(),
            'reports_pending' => (int) $db->query(
                "SELECT COUNT(*) FROM reports WHERE status = 'pending'"
            )->fetchColumn(),
        ]);
    }

    // --------------------------------------------------------------

    private function rowPayload(array $r): array
    {
        $data = json_decode($r['data'] ?? '{}', true);
        if (!is_array($data)) $data = [];

        return [
            'id'         => (int) $r['id'],
            'type'       => $r['type'],
            'title'      => $r['title'],
            'body'       => $r['body'],
            'data'       => $data,
            'read'       => $r['read_at'] !== null,
            'created_at' => $r['created_at'],
            'url'        => $this->linkFor($r['type'], $data),
        ];
    }

    private function linkFor(string $type, array $data): string
    {
        $uid = $data['user_id']        ?? null;
        $rid = $data['report_id']      ?? null;

        switch ($type) {
            case 'new_user':
                return $uid ? '/pasabuy/users/' . (int) $uid : '/pasabuy/users';
            case 'kyc_submitted':
                return $uid ? '/pasabuy/users/' . (int) $uid : '/pasabuy/kyc';
            case 'new_post':
                return '/pasabuy/posts';
            case 'report_filed':
                return '/pasabuy/reports';
            case 'dispute_opened':
                return '/pasabuy/transactions';
            default:
                return '/pasabuy/dashboard';
        }
    }
}