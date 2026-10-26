<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function issueToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);
        $stmt  = Database::pdo()->prepare(
            "INSERT INTO personal_access_tokens (user_id, token_hash, created_at) VALUES (?, ?, NOW())"
        );
        $stmt->execute([$userId, $hash]);
        return $token;
    }

    public static function userFromRequest(Request $request): ?array
    {
        $header = (string) $request->header('Authorization', '');
        if (!preg_match('/Bearer\s+(.+)/i', $header, $m)) return null;
        $hash = hash('sha256', trim($m[1]));

        $stmt = Database::pdo()->prepare(
            "SELECT u.* FROM users u
             JOIN personal_access_tokens t ON t.user_id = u.id
             WHERE t.token_hash = ? LIMIT 1"
        );
        $stmt->execute([$hash]);
        return $stmt->fetch() ?: null;
    }

    public static function require(Request $request): array
    {
        $user = self::userFromRequest($request);
        if (!$user) Response::error('Unauthenticated', 401);
        return $user;
    }

    public static function revokeCurrent(Request $request): void
    {
        $header = (string) $request->header('Authorization', '');
        if (!preg_match('/Bearer\s+(.+)/i', $header, $m)) return;
        $hash = hash('sha256', trim($m[1]));
        Database::pdo()->prepare("DELETE FROM personal_access_tokens WHERE token_hash = ?")
            ->execute([$hash]);
    }
}