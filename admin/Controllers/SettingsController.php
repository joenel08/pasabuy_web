<?php
namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\Flash;
use Admin\Core\View;
use App\Core\Database;
use App\Core\Request;

class SettingsController
{
    public function index(Request $request, array $params = []): void
    {
        AdminAuth::requireSuper();

        $admins = Database::pdo()->query(
            "SELECT id, email, admin_role, email_verified_at, created_at
             FROM users
             WHERE role = 'admin'
             ORDER BY admin_role, email"
        )->fetchAll();

        View::render('settings.index', [
            'title'  => 'System Settings',
            'admins' => $admins,
        ]);
    }

    public function changePassword(Request $request, array $params = []): void
    {
        $me = AdminAuth::require();

        $current = (string) $request->input('current_password', '');
        $new     = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('confirm_password', '');

        if (!password_verify($current, $me['password_hash'])) {
            Flash::set('error', 'Current password is incorrect.');
            header('Location: /pasabuy/settings'); exit;
        }
        if (strlen($new) < 8) {
            Flash::set('error', 'New password must be at least 8 characters.');
            header('Location: /pasabuy/settings'); exit;
        }
        if ($new !== $confirm) {
            Flash::set('error', 'New passwords do not match.');
            header('Location: /pasabuy/settings'); exit;
        }

        Database::pdo()->prepare(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?"
        )->execute([password_hash($new, PASSWORD_BCRYPT), (int) $me['id']]);

        Flash::set('success', 'Password updated.');
        header('Location: /pasabuy/settings'); exit;
    }

    public function createAdmin(Request $request, array $params = []): void
    {
        AdminAuth::requireSuper();

        $email = trim((string) $request->input('email', ''));
        $pass  = (string) $request->input('password', '');
        $role  = (string) $request->input('admin_role', '');

        $valid = ['super','kyc','moderator','analytics'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('error', 'Enter a valid email.');
            header('Location: /pasabuy/settings'); exit;
        }
        if (strlen($pass) < 8) {
            Flash::set('error', 'Password must be at least 8 characters.');
            header('Location: /pasabuy/settings'); exit;
        }
        if (!in_array($role, $valid, true)) {
            Flash::set('error', 'Pick a valid admin role.');
            header('Location: /pasabuy/settings'); exit;
        }

        $existing = Database::pdo()->prepare("SELECT id FROM users WHERE email = ?");
        $existing->execute([$email]);
        if ($existing->fetch()) {
            Flash::set('error', 'That email is already registered.');
            header('Location: /pasabuy/settings'); exit;
        }

        Database::pdo()->prepare(
            "INSERT INTO users (email, password_hash, role, admin_role, status, email_verified_at, created_at, updated_at)
             VALUES (?, ?, 'admin', ?, 'accepted', NOW(), NOW(), NOW())"
        )->execute([$email, password_hash($pass, PASSWORD_BCRYPT), $role]);

        Flash::set('success', "Admin created: {$email}");
        header('Location: /pasabuy/settings'); exit;
    }

    public function updateRole(Request $request, array $params = []): void
    {
        AdminAuth::requireSuper();

        $id   = (int) ($params['id'] ?? 0);
        $role = (string) $request->input('admin_role', '');
        $valid = ['super','kyc','moderator','analytics'];

        if ($id === (int) $_SESSION['admin_id']) {
            Flash::set('error', 'You cannot change your own role.');
            header('Location: /pasabuy/settings'); exit;
        }
        if (!in_array($role, $valid, true)) {
            Flash::set('error', 'Invalid role.');
            header('Location: /pasabuy/settings'); exit;
        }

        Database::pdo()->prepare(
            "UPDATE users SET admin_role = ?, updated_at = NOW() WHERE id = ? AND role = 'admin'"
        )->execute([$role, $id]);

        Flash::set('success', 'Role updated.');
        header('Location: /pasabuy/settings'); exit;
    }

    public function deleteAdmin(Request $request, array $params = []): void
    {
        AdminAuth::requireSuper();

        $id = (int) ($params['id'] ?? 0);
        if ($id === (int) $_SESSION['admin_id']) {
            Flash::set('error', 'You cannot delete your own account.');
            header('Location: /pasabuy/settings'); exit;
        }

        // Prevent deleting the last super admin
        $remaining = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM users WHERE role='admin' AND admin_role='super' AND id != ?"
        );
        $remaining->execute([$id]);
        if ((int) $remaining->fetchColumn() === 0) {
            Flash::set('error', 'Cannot remove the last super admin.');
            header('Location: /pasabuy/settings'); exit;
        }

        Database::pdo()->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'")
            ->execute([$id]);

        Flash::set('success', 'Admin removed.');
        header('Location: /pasabuy/settings'); exit;
    }
}