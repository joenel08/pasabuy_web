<?php
namespace App\Core;

class AdminNotifier
{
    /**
     * Push the same notification to every admin.
     */
    public static function push(
        string $type,
        string $title,
        string $body = '',
        array $data = []
    ): void {
        $admins = Database::pdo()
            ->query("SELECT id FROM users WHERE role = 'admin'")
            ->fetchAll();

        if (!$admins) return;

        $stmt = Database::pdo()->prepare(
            "INSERT INTO notifications (user_id, type, title, body, data, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );

        $payload = json_encode($data);

        foreach ($admins as $a) {
            $stmt->execute([(int) $a['id'], $type, $title, $body, $payload]);
        }
    }
}