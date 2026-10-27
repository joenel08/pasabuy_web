<?php

namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class NotificationsController
{
    public function summary(Request $request, array $params = []): void
    {
        if (!AdminAuth::check()) {
            Response::error('Unauthenticated', 401);
            return;
        }

        $db = Database::pdo();

        // Per-category unread counts
        $stmt = $db->query(
            "SELECT category, COUNT(*) AS c
             FROM admin_notifications
             WHERE read_at IS NULL
             GROUP BY category"
        );
        $byCat = ['users' => 0, 'kyc' => 0, 'posts' => 0, 'reports' => 0, 'transactions' => 0, 'fees' => 0];
        $total = 0;
        foreach ($stmt->fetchAll() as $row) {
            $cat = (string) $row['category'];
            $c   = (int) $row['c'];
            if (isset($byCat[$cat])) $byCat[$cat] = $c;
            $total += $c;
        }

        // Latest 20 items
        $latest = $db->query(
            "SELECT id, type, category, title, body, link, read_at, created_at
             FROM admin_notifications
             ORDER BY id DESC
             LIMIT 20"
        )->fetchAll();

        $latest = array_map(fn($r) => [
            'id'         => (int) $r['id'],
            'type'       => $r['type'],
            'category'   => $r['category'],
            'title'      => $r['title'],
            'body'       => $r['body'],
            'link'       => $r['link'],
            'read'       => $r['read_at'] !== null,
            'created_at' => $r['created_at'],
        ], $latest);

        Response::ok([
            'total'       => $total,
            'by_category' => $byCat,
            'latest'      => $latest,
        ]);
    }

    public function markRead(Request $request, array $params = []): void
    {
        if (!AdminAuth::check()) {
            Response::error('Unauthenticated', 401);
            return;
        }

        $id = (int) ($params['id'] ?? 0);

        // Look up the notification's target so we can mark sibling
        // notifications about the same target as read too.
        $stmt = Database::pdo()->prepare(
            "SELECT target_type, target_id
         FROM admin_notifications
         WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $notif = $stmt->fetch();

        if ($notif && !empty($notif['target_type']) && !empty($notif['target_id'])) {
            // Mark every unread notification about the same target as read
            Database::pdo()->prepare(
                "UPDATE admin_notifications
             SET read_at = NOW()
             WHERE target_type = ? AND target_id = ? AND read_at IS NULL"
            )->execute([$notif['target_type'], $notif['target_id']]);
        } else {
            // No target info — just mark this one
            Database::pdo()->prepare(
                "UPDATE admin_notifications SET read_at = NOW()
             WHERE id = ? AND read_at IS NULL"
            )->execute([$id]);
        }

        Response::ok(null, 'Marked read');
    }

    public function markAllRead(Request $request, array $params = []): void
    {
        if (!AdminAuth::check()) {
            Response::error('Unauthenticated', 401);
            return;
        }
        Database::pdo()->prepare(
            "UPDATE admin_notifications SET read_at = NOW() WHERE read_at IS NULL"
        )->execute();
        Response::ok(null, 'All read');
    }
}
