<?php

namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\Flash;
use Admin\Core\View;
use App\Core\Database;
use App\Core\Request;

class KycController
{
    public function index(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $status = (string) ($request->query['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'accepted', 'declined', 'all'], true)) $status = 'pending';

        $where = $status === 'all' ? '1=1' : 'k.status = ?';
        $bind  = $status === 'all' ? [] : [$status];

        $stmt = Database::pdo()->prepare(
            "SELECT k.*, u.email, p.firstname, p.lastname
             FROM id_verifications k
             JOIN users u ON u.id = k.user_id
             LEFT JOIN profiles p ON p.user_id = u.id
             WHERE $where
             ORDER BY k.created_at DESC
             LIMIT 200"
        );
        $stmt->execute($bind);

        View::render('kyc.index', [
            'title'  => 'KYC Review',
            'items'  => $stmt->fetchAll(),
            'status' => $status,
        ]);
    }

    public function approve(Request $request, array $params = []): void
    {
        $admin  = AdminAuth::require();
        $userId = (int) ($params['id'] ?? 0);
        $db = Database::pdo();

        $stmt = $db->prepare("SELECT id FROM id_verifications WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            Flash::set('error', 'No KYC record for this user');
            header('Location: /pasabuy/users/' . $userId);
            exit;
        }

        $db->prepare("UPDATE id_verifications SET status='accepted', reviewed_by=?, reviewed_at=NOW(), remarks=NULL WHERE user_id=?")
            ->execute([(int) $admin['id'], $userId]);
        $db->prepare("UPDATE users SET status='accepted', updated_at=NOW() WHERE id=?")
            ->execute([$userId]);

        Flash::set('success', "User #$userId approved.");
        header('Location: /pasabuy/kyc?status=pending');
        exit;
    }

    public function decline(Request $request, array $params = []): void
    {
        $admin   = AdminAuth::require();
        $userId  = (int) ($params['id'] ?? 0);
        $remarks = trim((string) $request->input('remarks', ''));

        if ($remarks === '') {
            Flash::set('error', 'Please provide a reason.');
            header('Location: /pasabuy/users/' . $userId);
            exit;
        }

        $db = Database::pdo();
        $db->prepare("UPDATE id_verifications SET status='declined', reviewed_by=?, reviewed_at=NOW(), remarks=? WHERE user_id=?")
            ->execute([(int) $admin['id'], $remarks, $userId]);
        $db->prepare("UPDATE users SET status='declined', updated_at=NOW() WHERE id=?")
            ->execute([$userId]);

        Flash::set('success', "User #$userId declined.");
        header('Location: /pasabuy/kyc?status=pending');
        exit;
    }

    // Serve the ID image through the admin session (never exposed publicly)
    public function image(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $userId = (int) ($params['id'] ?? 0);
        $side   = (string) ($params['side'] ?? 'front');

        $stmt = Database::pdo()->prepare("SELECT front_path, back_path FROM id_verifications WHERE user_id=? LIMIT 1");
        $stmt->execute([$userId]);
        $kyc = $stmt->fetch();
        if (!$kyc) {
            http_response_code(404);
            exit;
        }

        $rel = $side === 'back' ? $kyc['back_path'] : $kyc['front_path'];
        if (!$rel) {
            http_response_code(404);
            exit;
        }

        // DB stores "storage/ids/xxx.jpg" — file lives in api/storage/ids/
        $abs = __DIR__ . '/../../api/' . ltrim($rel, '/');
        if (!is_file($abs)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . (mime_content_type($abs) ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($abs));
        header('Cache-Control: private, max-age=60');
        readfile($abs);
        exit;
    }

    public function reset(Request $request, array $params = []): void
    {
        $admin  = AdminAuth::require();
        $userId = (int) ($params['id'] ?? 0);
        $db = Database::pdo();

        $db->prepare("UPDATE id_verifications
                  SET status='pending', reviewed_by=NULL, reviewed_at=NULL, remarks=NULL
                  WHERE user_id=?")
            ->execute([$userId]);

        $db->prepare("UPDATE users SET status='pending', updated_at=NOW() WHERE id=?")
            ->execute([$userId]);

        Flash::set('success', "KYC reset for user #$userId.");
        header('Location: /pasabuy/users/' . $userId);
        exit;
    }
}
