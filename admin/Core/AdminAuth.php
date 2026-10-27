<?php
namespace Admin\Core;

use App\Models\User;

class AdminAuth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findBy('email', $email);
        if (!$user) return false;
        if ($user['role'] !== 'admin') return false;
        if (empty($user['admin_role'])) return false;
        if (!$user['email_verified_at']) return false;
        if (!password_verify($password, $user['password_hash'])) return false;

        session_regenerate_id(true);
        $_SESSION['admin_id']    = (int) $user['id'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_role']  = $user['admin_role'];
        return true;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function user(): ?array
    {
        return self::check() ? User::find((int) $_SESSION['admin_id']) : null;
    }

    public static function role(): string
    {
        return (string) ($_SESSION['admin_role'] ?? '');
    }

    public static function isSuper(): bool
    {
        return self::role() === 'super';
    }

    /** Can the current admin see a given nav key? */
    public static function can(string $key): bool
    {
        $role = self::role();
        $matrix = [
            'super'     => ['dashboard','analytics','users','kyc','posts','reports','transactions','settings','fees'],
            'kyc'       => ['dashboard','users','kyc'],
            'moderator' => ['dashboard','users','reports'],
            'analytics' => ['dashboard','analytics'],
        ];
        return in_array($key, $matrix[$role] ?? [], true);
    }

    public static function require(): array
    {
        $u = self::user();
        if (!$u) { header('Location: /pasabuy/login'); exit; }
        return $u;
    }

    public static function requireSuper(): void
    {
        self::require();
        if (!self::isSuper()) {
            http_response_code(403);
            echo '<div style="font-family:sans-serif;padding:40px;text-align:center">'
               . '<h1>403 — Access denied</h1>'
               . '<p>Only super admins can access this.</p>'
               . '<a href="/pasabuy/dashboard" style="color:#059669">Back to dashboard</a></div>';
            exit;
        }
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_email'], $_SESSION['admin_role']);
    }
}