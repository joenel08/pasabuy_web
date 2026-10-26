<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class EmailVerification extends Model
{
    protected static string $table = 'email_verifications';

    public static function create(int $userId, string $code): void
    {
        // Invalidate any previous codes for this user
        Database::pdo()->prepare("DELETE FROM email_verifications WHERE user_id = ?")
            ->execute([$userId]);

        // Use MySQL's NOW() so PHP/MySQL timezone mismatch doesn't break expiry
        $stmt = Database::pdo()->prepare(
            "INSERT INTO email_verifications (user_id, code, expires_at, created_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())"
        );
        $stmt->execute([$userId, $code]);
    }

    public static function verify(int $userId, string $code): bool
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM email_verifications
             WHERE user_id = ?
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        // Debug — see what's stored vs what was submitted
        error_log('[VERIFY] user=' . $userId
                . ' submitted=' . $code
                . ' stored=' . ($row['code'] ?? 'NULL')
                . ' expires_at=' . ($row['expires_at'] ?? 'NULL')
                . ' db_now=' . self::now());

        if (!$row) return false;
        if ((string) $row['code'] !== (string) $code) return false;
        if (strtotime($row['expires_at']) <= strtotime(self::now())) return false;

        Database::pdo()->prepare("DELETE FROM email_verifications WHERE user_id = ?")
            ->execute([$userId]);
        return true;
    }

    private static function now(): string
    {
        $stmt = Database::pdo()->query("SELECT NOW() AS n");
        return (string) $stmt->fetch()['n'];
    }
}