<?php
namespace App\Core;

class Notifier
{
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $body = '',
        array $data = []
    ): void {
        // 1. Save to notifications table (unchanged behavior)
        Database::pdo()->prepare(
            "INSERT INTO notifications (user_id, type, title, body, data, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        )->execute([$userId, $type, $title, $body, json_encode($data)]);

        // 2. Fire FCM push (best-effort; never let a push failure
        //    break the request that triggered it)
        try {
            Fcm::toUser($userId, $title, $body, $data);
        } catch (\Throwable $e) {
            error_log('[Notifier] FCM push failed for user ' . $userId . ': ' . $e->getMessage());
        }
    }
}