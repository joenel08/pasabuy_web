<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Core\AdminNotifier;

class ReportController
{
   private const REASONS = [
    'spam',
    'harassment',
    'fake_id',
    'scam',
    'inappropriate',
    'off_platform',
    'prohibited_item',   // ← add this
    'other'
];

    public function store(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);

        $type   = (string) $request->input('target_type', '');
        $target = (int) $request->input('target_id', 0);
        $reason = (string) $request->input('reason', '');
        $desc   = trim((string) $request->input('description', ''));

        $errors = [];
        if (!in_array($type, ['user', 'post', 'message', 'transaction'], true)) {
            $errors['target_type'] = 'Invalid target';
        }
        if ($target <= 0) $errors['target_id'] = 'Invalid target';
        if (!in_array($reason, self::REASONS, true)) {
            $errors['reason'] = 'Invalid reason';
        }
        if ($type === 'user' && $target === (int) $auth['id']) {
            $errors['target_id'] = 'You cannot report yourself';
        }
        if ($errors) Response::error('Validation failed', 422, $errors);

        // Prevent duplicate pending reports from same user
        $dup = Database::pdo()->prepare(
            "SELECT id FROM reports WHERE reporter_id=? AND target_type=? AND target_id=? AND status='pending' LIMIT 1"
        );
        $dup->execute([(int) $auth['id'], $type, $target]);
        if ($dup->fetch()) {
            Response::error('You already have a pending report for this', 422);
        }

        Database::pdo()->prepare(
            "INSERT INTO reports (reporter_id, target_type, target_id, reason, description, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
        )->execute([(int) $auth['id'], $type, $target, $reason, $desc ?: null]);
        $reportId = (int) Database::pdo()->lastInsertId();

        \App\Core\AdminNotifier::push([
            'type'        => 'report_filed',
            'category'    => 'reports',
            'title'       => 'New ' . $type . ' report',
            'body'        => ucfirst($reason) . ($desc !== '' ? ' — ' . $desc : ''),
            'link'        => '/pasabuy/reports',
            'target_type' => 'report',
            'target_id'   => $reportId,
        ]);
        // Notify admins
        $admins = Database::pdo()->query("SELECT id FROM users WHERE role='admin'")->fetchAll();
        foreach ($admins as $a) {
            Notifier::send(
                (int) $a['id'],
                'report_filed',
                'New report filed',
                ucfirst($type) . ' #' . $target,
                ['report_id' => (int) Database::pdo()->lastInsertId()]
            );
        }



        Response::ok(null, 'Report submitted. Our team will review it.');
    }

    public function changePassword(Request $request, array $params = []): void
    {
        $auth    = Auth::require($request);
        $current = (string) $request->input('current_password', '');
        $new     = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('new_password_confirmation', '');

        if ($current === '' || $new === '' || $confirm === '') {
            Response::error('All fields are required', 422);
        }
        if (strlen($new) < 8) {
            Response::error('New password must be at least 8 characters', 422);
        }
        if ($new !== $confirm) {
            Response::error('Passwords do not match', 422);
        }

        $user = \App\Models\User::find((int) $auth['id']);
        if (!password_verify($current, $user['password_hash'])) {
            Response::error('Current password is incorrect', 422);
        }

        \App\Models\User::update((int) $auth['id'], [
            'password_hash' => password_hash($new, PASSWORD_BCRYPT),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        Response::ok(null, 'Password changed');
    }
}
