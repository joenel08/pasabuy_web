<?php
namespace App\Core;

class AdminNotifier
{
    public static function push(array $data): void
    {
        try {
            Database::pdo()->prepare(
                "INSERT INTO admin_notifications
                    (type, category, title, body, link, target_type, target_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            )->execute([
                $data['type'],
                $data['category'],
                $data['title'],
                $data['body'] ?? null,
                $data['link'] ?? null,
                $data['target_type'] ?? null,
                $data['target_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // Never let admin notification failures break the main flow
            error_log('[AdminNotifier] ' . $e->getMessage());
        }
    }
}