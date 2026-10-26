<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class NotificationController
{
    public function index(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);

        $stmt = Database::pdo()->prepare(
            "SELECT * FROM notifications WHERE user_id = ?
             ORDER BY created_at DESC LIMIT 100"
        );
        $stmt->execute([(int) $auth['id']]);

        $rows = array_map(fn($n) => [
            'id'         => (int) $n['id'],
            'type'       => $n['type'],
            'title'      => $n['title'],
            'body'       => $n['body'],
            'data'       => json_decode($n['data'] ?? '[]', true) ?: [],
            'read'       => $n['read_at'] !== null,
            'created_at' => $n['created_at'],
        ], $stmt->fetchAll());

        $unread = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL"
        );
        $unread->execute([(int) $auth['id']]);

        Response::ok([
            'data'         => $rows,
            'unread_count' => (int) $unread->fetchColumn(),
        ]);
    }

    public function unreadCount(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL"
        );
        $stmt->execute([(int) $auth['id']]);
        Response::ok(['count' => (int) $stmt->fetchColumn()]);
    }

    public function markRead(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $id   = (int) ($params['id'] ?? 0);
        Database::pdo()->prepare(
            "UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?"
        )->execute([$id, (int) $auth['id']]);
        Response::ok(null, 'Marked read');
    }

    public function markAllRead(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        Database::pdo()->prepare(
            "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL"
        )->execute([(int) $auth['id']]);
        Response::ok(null, 'All read');
    }
}