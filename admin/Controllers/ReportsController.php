<?php

namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\Flash;
use Admin\Core\View;
use App\Core\Database;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\AdminNotifier;

class ReportsController
{
    public function index(Request $request, array $params = []): void
    {
        AdminAuth::require();

        $status = (string) ($request->query['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'reviewing', 'resolved', 'dismissed', 'all'], true)) {
            $status = 'pending';
        }
        $type = (string) ($request->query['type'] ?? 'all');
        if (!in_array($type, ['user', 'post', 'message', 'transaction', 'all'], true)) {
            $type = 'all';
        }

        $where = [];
        $bind  = [];
        if ($status !== 'all') {
            $where[] = 'r.status = ?';
            $bind[] = $status;
        }
        if ($type !== 'all') {
            $where[] = 'r.target_type = ?';
            $bind[] = $type;
        }
        $whereSql = $where ? implode(' AND ', $where) : '1=1';

        $sql = "SELECT r.*,
                       u.email AS reporter_email,
                       a.email AS reviewer_email,
                       (SELECT COUNT(*) FROM reports r2
                          WHERE r2.target_type = r.target_type
                            AND r2.target_id = r.target_id) AS target_report_count
                FROM reports r
                LEFT JOIN users u ON u.id = r.reporter_id
                LEFT JOIN users a ON a.id = r.reviewed_by
                WHERE $whereSql
                ORDER BY r.created_at DESC
                LIMIT 200";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($bind);
        $items = $stmt->fetchAll();

        foreach ($items as &$it) {
            $it['target_label'] = $this->labelFor($it);
        }
        unset($it);

        $counts = [
            'pending'   => (int) Database::pdo()->query("SELECT COUNT(*) FROM reports WHERE status='pending'")->fetchColumn(),
            'reviewing' => (int) Database::pdo()->query("SELECT COUNT(*) FROM reports WHERE status='reviewing'")->fetchColumn(),
            'resolved'  => (int) Database::pdo()->query("SELECT COUNT(*) FROM reports WHERE status='resolved'")->fetchColumn(),
            'dismissed' => (int) Database::pdo()->query("SELECT COUNT(*) FROM reports WHERE status='dismissed'")->fetchColumn(),
        ];

        View::render('reports.index', [
            'title'  => 'Reports',
            'items'  => $items,
            'status' => $status,
            'type'   => $type,
            'counts' => $counts,
        ]);
    }

    public function show(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $id = (int) ($params['id'] ?? 0);

        $stmt = Database::pdo()->prepare(
            "SELECT r.*, u.email AS reporter_email
             FROM reports r
             LEFT JOIN users u ON u.id = r.reporter_id
             WHERE r.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $report = $stmt->fetch();
        if (!$report) {
            http_response_code(404);
            echo 'Report not found';
            return;
        }

        $target = $this->loadTarget($report['target_type'], (int) $report['target_id']);
        $report['target_label'] = $this->labelFor($report);

        $others = Database::pdo()->prepare(
            "SELECT id, reporter_id, reason, status, created_at
             FROM reports
             WHERE target_type = ? AND target_id = ? AND id != ?
             ORDER BY created_at DESC LIMIT 20"
        );
        $others->execute([$report['target_type'], $report['target_id'], $id]);

        View::render('reports.show', [
            'title'      => 'Report #' . $id,
            'report'     => $report,
            'target'     => $target,
            'otherReports' => $others->fetchAll(),
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        $admin = AdminAuth::require();
        $id    = (int) ($params['id'] ?? 0);

        $status = (string) $request->input('status', '');
        $notes  = trim((string) $request->input('admin_notes', ''));

        if (!in_array($status, ['reviewing', 'resolved', 'dismissed', 'pending'], true)) {
            Flash::set('error', 'Invalid status');
            header('Location: /pasabuy/reports/' . $id);
            exit;
        }

        // Fetch the report BEFORE updating so we know the previous status
        // and the reporter (needed for the notification).
        $beforeStmt = Database::pdo()->prepare("SELECT * FROM reports WHERE id = ?");
        $beforeStmt->execute([$id]);
        $prev = $beforeStmt->fetch();
        if (!$prev) {
            Flash::set('error', 'Report not found');
            header('Location: /pasabuy/reports');
            exit;
        }

        Database::pdo()->prepare(
            "UPDATE reports
             SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
             WHERE id = ?"
        )->execute([$status, $notes ?: null, (int) $admin['id'], $id]);

        // Collect the sibling reports (if resolving all) BEFORE updating so
        // we know exactly which reporter IDs to notify.
        $siblingReports = [];
        if ($request->input('resolve_all') === '1') {
            $sibStmt = Database::pdo()->prepare(
                "SELECT id, reporter_id FROM reports
                 WHERE target_type = ? AND target_id = ? AND id != ?
                   AND status IN ('pending','reviewing')"
            );
            $sibStmt->execute([$prev['target_type'], $prev['target_id'], $id]);
            $siblingReports = $sibStmt->fetchAll();

            Database::pdo()->prepare(
                "UPDATE reports SET status = ?, reviewed_by = ?, reviewed_at = NOW()
                 WHERE target_type = ? AND target_id = ? AND id != ?
                   AND status IN ('pending','reviewing')"
            )->execute([$status, (int) $admin['id'], $prev['target_type'], $prev['target_id'], $id]);
        }

        // Notify the primary reporter — only on terminal states, and only
        // if the report actually just transitioned into that state.
        if (in_array($status, ['resolved', 'dismissed'], true) && $prev['status'] !== $status) {
            $this->notifyReporter((int) $prev['reporter_id'], $status, $id);
        }

        // Notify each sibling reporter
        if (in_array($status, ['resolved', 'dismissed'], true)) {
            foreach ($siblingReports as $sib) {
                $this->notifyReporter((int) $sib['reporter_id'], $status, (int) $sib['id']);
            }
        }

        Flash::set('success', "Report #$id updated to $status.");
        header('Location: /pasabuy/reports');
        exit;
    }

    // ---------------- notification helper ----------------

    private function notifyReporter(int $reporterId, string $status, int $reportId): void
    {
        if ($reporterId <= 0) return;

        $title = $status === 'resolved'
            ? 'Your report was reviewed'
            : 'Your report was reviewed';

        $body = $status === 'resolved'
            ? 'Thank you for helping keep PasaBuy safe. Appropriate action has been taken.'
            : 'After review, no further action was necessary. Thank you for your report.';

        try {
            Notifier::send(
                $reporterId,
                $status === 'resolved' ? 'report_resolved' : 'report_dismissed',
                $title,
                $body,
                [
                    'report_id' => $reportId,
                    'status'    => $status,
                ]
            );
        } catch (\Throwable $e) {
            // Never let a notification failure block the admin action
            error_log('[REPORT] notify failed for reporter ' . $reporterId . ': ' . $e->getMessage());
        }
    }

    // ---------------- helpers ----------------

    private function labelFor(array $r): string
    {
        $type = $r['target_type'];
        $id   = (int) $r['target_id'];
        $db   = Database::pdo();

        try {
            switch ($type) {
                case 'user':
                    $s = $db->prepare("SELECT email FROM users WHERE id = ?");
                    $s->execute([$id]);
                    return 'User: ' . ($s->fetchColumn() ?: '#' . $id);
                case 'post':
                    $s = $db->prepare("SELECT title FROM posts WHERE id = ?");
                    $s->execute([$id]);
                    return 'Post: ' . ($s->fetchColumn() ?: '#' . $id);
                case 'message':
                    $s = $db->prepare("SELECT body FROM messages WHERE id = ?");
                    $s->execute([$id]);
                    $b = $s->fetchColumn();
                    return 'Message: ' . ($b ? mb_strimwidth($b, 0, 60, '…') : '#' . $id);
                case 'transaction':
                    $s = $db->prepare("SELECT item_description FROM transactions WHERE id = ?");
                    $s->execute([$id]);
                    return 'Deal: ' . ($s->fetchColumn() ?: '#' . $id);
            }
        } catch (\Throwable $e) {
            return $type . ' #' . $id;
        }
        return $type . ' #' . $id;
    }

    private function loadTarget(string $type, int $id): ?array
    {
        $db = Database::pdo();
        switch ($type) {
            case 'user':
                $s = $db->prepare(
                    "SELECT u.id, u.email, u.status, u.created_at, p.firstname, p.lastname, p.avatar_path
                     FROM users u LEFT JOIN profiles p ON p.user_id = u.id
                     WHERE u.id = ? LIMIT 1"
                );
                $s->execute([$id]);
                return $s->fetch() ?: null;
            case 'post':
                $s = $db->prepare(
                    "SELECT p.*, u.email AS owner_email
                     FROM posts p JOIN users u ON u.id = p.user_id
                     WHERE p.id = ? LIMIT 1"
                );
                $s->execute([$id]);
                return $s->fetch() ?: null;
            case 'message':
                $s = $db->prepare(
                    "SELECT m.*, u.email AS sender_email
                     FROM messages m JOIN users u ON u.id = m.sender_id
                     WHERE m.id = ? LIMIT 1"
                );
                $s->execute([$id]);
                return $s->fetch() ?: null;
            case 'transaction':
                $s = $db->prepare("SELECT * FROM transactions WHERE id = ? LIMIT 1");
                $s->execute([$id]);
                return $s->fetch() ?: null;
        }
        return null;
    }


    // -----------------------------------------------------------------
    // POST /pasabuy/reports/{id}/suspend-user
    // Determines the offending user from the report target and suspends them.
    // Auto-resolves the report and every sibling pending report on the same target.
    // -----------------------------------------------------------------
    public function suspendUser(Request $request, array $params = []): void
    {
        $admin = AdminAuth::require();
        $id    = (int) ($params['id'] ?? 0);

        $stmt = Database::pdo()->prepare("SELECT * FROM reports WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $report = $stmt->fetch();
        if (!$report) {
            Flash::set('error', 'Report not found');
            header('Location: /pasabuy/reports');
            exit;
        }

        // Determine who to suspend based on the target type
        $userId = null;
        if ($report['target_type'] === 'user') {
            $userId = (int) $report['target_id'];
        } elseif ($report['target_type'] === 'message') {
            $s = Database::pdo()->prepare("SELECT sender_id FROM messages WHERE id = ?");
            $s->execute([(int) $report['target_id']]);
            $userId = (int) $s->fetchColumn();
        } elseif ($report['target_type'] === 'post') {
            $s = Database::pdo()->prepare("SELECT user_id FROM posts WHERE id = ?");
            $s->execute([(int) $report['target_id']]);
            $userId = (int) $s->fetchColumn();
        }

        if (!$userId) {
            Flash::set('error', 'Cannot determine which user to suspend from this report.');
            header('Location: /pasabuy/reports/' . $id);
            exit;
        }

        // Safety checks
        if ($userId === (int) $admin['id']) {
            Flash::set('error', 'You cannot suspend your own admin account.');
            header('Location: /pasabuy/reports/' . $id);
            exit;
        }

        $check = Database::pdo()->prepare("SELECT role, email FROM users WHERE id = ? LIMIT 1");
        $check->execute([$userId]);
        $targetUser = $check->fetch();
        if (!$targetUser) {
            Flash::set('error', 'User not found.');
            header('Location: /pasabuy/reports/' . $id);
            exit;
        }
        if ($targetUser['role'] === 'admin') {
            Flash::set('error', 'Cannot suspend an admin account.');
            header('Location: /pasabuy/reports/' . $id);
            exit;
        }

        // Suspend the account
        Database::pdo()->prepare(
            "UPDATE users SET status = 'declined', updated_at = NOW() WHERE id = ?"
        )->execute([$userId]);

        // Resolve ALL reports on this target in one go
        $reporterStmt = Database::pdo()->prepare(
            "SELECT id, reporter_id FROM reports
         WHERE target_type = ? AND target_id = ? AND status IN ('pending','reviewing')"
        );
        $reporterStmt->execute([$report['target_type'], $report['target_id']]);
        $affectedReports = $reporterStmt->fetchAll();

        Database::pdo()->prepare(
            "UPDATE reports
         SET status = 'resolved',
             admin_notes = CONCAT(COALESCE(admin_notes,''), IF(COALESCE(admin_notes,'')='','',' · '), 'User suspended'),
             reviewed_by = ?, reviewed_at = NOW()
         WHERE target_type = ? AND target_id = ? AND status IN ('pending','reviewing')"
        )->execute([(int) $admin['id'], $report['target_type'], $report['target_id']]);

        // Notify every reporter whose report got resolved
        foreach ($affectedReports as $row) {
            Notifier::send(
                (int) $row['reporter_id'],
                'report_resolved',
                'Your report was reviewed',
                'Thank you for helping keep PasaBuy safe. Appropriate action has been taken.',
                ['report_id' => (int) $row['id'], 'status' => 'resolved']
            );
        }

        // Notify the suspended user
        Notifier::send(
            $userId,
            'account_suspended',
            'Your account has been suspended',
            'Your account was suspended after review of a report. If you believe this is a mistake, contact support.',
            ['report_id' => $id]
        );

        // Admin notification for the audit trail
        AdminNotifier::push([
            'type'        => 'user_suspended',
            'category'    => 'users',
            'title'       => 'User suspended',
            'body'        => $targetUser['email'] . ' · from report #' . $id,
            'link'        => '/pasabuy/users/' . $userId,
            'target_type' => 'user',
            'target_id'   => $userId,
        ]);

        Flash::set('success', "User {$targetUser['email']} suspended. " . count($affectedReports) . " report(s) resolved.");
        header('Location: /pasabuy/reports');
        exit;
    }

    // -----------------------------------------------------------------
    // POST /pasabuy/reports/{id}/delete-target
    // Removes the reported post or message. Auto-resolves all sibling reports.
    // -----------------------------------------------------------------
    public function deleteTarget(Request $request, array $params = []): void
    {
        $admin = AdminAuth::require();
        $id    = (int) ($params['id'] ?? 0);

        $stmt = Database::pdo()->prepare("SELECT * FROM reports WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $report = $stmt->fetch();
        if (!$report) {
            Flash::set('error', 'Report not found');
            header('Location: /pasabuy/reports');
            exit;
        }

        $deleted = false;
        $message = '';

        if ($report['target_type'] === 'post') {
            $postId = (int) $report['target_id'];
            $s = Database::pdo()->prepare("SELECT user_id, title FROM posts WHERE id = ? LIMIT 1");
            $s->execute([$postId]);
            $post = $s->fetch();
            if ($post) {
                Database::pdo()->prepare(
                    "UPDATE posts SET status = 'cancelled', updated_at = NOW() WHERE id = ?"
                )->execute([$postId]);

                Notifier::send(
                    (int) $post['user_id'],
                    'post_removed',
                    'Your post was removed',
                    'Your post "' . $post['title'] . '" was removed for violating our community guidelines.',
                    ['post_id' => $postId]
                );

                $deleted = true;
                $message = "Post #$postId removed.";
            }
        } elseif ($report['target_type'] === 'message') {
            $messageId = (int) $report['target_id'];
            $s = Database::pdo()->prepare("SELECT sender_id FROM messages WHERE id = ? LIMIT 1");
            $s->execute([$messageId]);
            $msg = $s->fetch();
            if ($msg) {
                Database::pdo()->prepare(
                    "UPDATE messages
                 SET body = '[Message removed by admin]', attachment_path = NULL
                 WHERE id = ?"
                )->execute([$messageId]);

                Notifier::send(
                    (int) $msg['sender_id'],
                    'message_removed',
                    'A message you sent was removed',
                    'Your message was removed for violating our community guidelines.',
                    ['report_id' => $id]
                );

                $deleted = true;
                $message = "Message #$messageId removed.";
            }
        }

        if (!$deleted) {
            Flash::set('error', 'This report type has no removable content.');
            header('Location: /pasabuy/reports/' . $id);
            exit;
        }

        // Resolve all reports on this target
        $reporterStmt = Database::pdo()->prepare(
            "SELECT id, reporter_id FROM reports
         WHERE target_type = ? AND target_id = ? AND status IN ('pending','reviewing')"
        );
        $reporterStmt->execute([$report['target_type'], $report['target_id']]);
        $affectedReports = $reporterStmt->fetchAll();

        Database::pdo()->prepare(
            "UPDATE reports
         SET status = 'resolved',
             admin_notes = CONCAT(COALESCE(admin_notes,''), IF(COALESCE(admin_notes,'')='','',' · '), 'Content removed'),
             reviewed_by = ?, reviewed_at = NOW()
         WHERE target_type = ? AND target_id = ? AND status IN ('pending','reviewing')"
        )->execute([(int) $admin['id'], $report['target_type'], $report['target_id']]);

        foreach ($affectedReports as $row) {
            Notifier::send(
                (int) $row['reporter_id'],
                'report_resolved',
                'Your report was reviewed',
                'Thank you for helping keep PasaBuy safe. Appropriate action has been taken.',
                ['report_id' => (int) $row['id'], 'status' => 'resolved']
            );
        }

        Flash::set('success', $message . ' ' . count($affectedReports) . ' report(s) resolved.');
        header('Location: /pasabuy/reports');
        exit;
    }
}
