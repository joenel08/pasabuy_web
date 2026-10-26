<?php

namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\View;
use App\Core\Database;
use App\Core\Request;
use Admin\Core\Flash;

class UsersController
{
    public function index(Request $request, array $params = []): void
    {
        AdminAuth::require();

        $status = (string) ($request->query['status'] ?? 'all');
        $search = trim((string) ($request->query['search'] ?? ''));

        $where = ["u.role != 'admin'"];
        $bind  = [];

        if (in_array($status, ['pending', 'accepted', 'declined'], true)) {
            $where[] = 'u.status = ?';
            $bind[]  = $status;
        }
        if ($search !== '') {
            $where[] = '(u.email LIKE ? OR p.firstname LIKE ? OR p.lastname LIKE ?)';
            $bind[] = "%$search%";
            $bind[] = "%$search%";
            $bind[] = "%$search%";
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT u.id, u.email, u.role, u.status, u.email_verified_at, u.created_at,
                       p.firstname, p.lastname, k.status AS kyc_status
                FROM users u
                LEFT JOIN profiles p ON p.user_id = u.id
                LEFT JOIN id_verifications k ON k.user_id = u.id
                WHERE $whereSql
                ORDER BY u.created_at DESC
                LIMIT 100";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($bind);

        View::render('users.index', [
            'title'  => 'Users',
            'users'  => $stmt->fetchAll(),
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $id = (int) ($params['id'] ?? 0);
        $db = Database::pdo();

        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        $stmt = $db->prepare("SELECT * FROM profiles WHERE user_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $profile = $stmt->fetch() ?: null;

        $stmt = $db->prepare("SELECT * FROM id_verifications WHERE user_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $kyc = $stmt->fetch() ?: null;

        $stmt = $db->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$id]);
        $posts = $stmt->fetchAll();


        // --- Reviews summary ---
        $ratingSummary = $db->prepare(
            "SELECT
        COUNT(*) AS total,
        COALESCE(AVG(rating), 0) AS average,
        SUM(rating = 5) AS s5,
        SUM(rating = 4) AS s4,
        SUM(rating = 3) AS s3,
        SUM(rating = 2) AS s2,
        SUM(rating = 1) AS s1
     FROM reviews WHERE reviewee_id = ?"
        );
        $ratingSummary->execute([$id]);
        $rating = $ratingSummary->fetch() ?: [
            'total' => 0,
            'average' => 0,
            's5' => 0,
            's4' => 0,
            's3' => 0,
            's2' => 0,
            's1' => 0,
        ];

        // --- All reviews received ---
        $reviewsStmt = $db->prepare(
            "SELECT r.id, r.rating, r.description, r.created_at,
            r.transaction_id,
            u.email AS reviewer_email,
            u.id    AS reviewer_id,
            t.item_description
     FROM reviews r
     JOIN users u ON u.id = r.reviewer_id
     LEFT JOIN transactions t ON t.id = r.transaction_id
     WHERE r.reviewee_id = ?
     ORDER BY r.created_at DESC
     LIMIT 50"
        );
        $reviewsStmt->execute([$id]);
        $reviews = $reviewsStmt->fetchAll();

        // --- Reviews given (by this user) ---
        $reviewsGivenStmt = $db->prepare(
            "SELECT r.id, r.rating, r.created_at, r.reviewee_id,
            u.email AS reviewee_email
     FROM reviews r
     JOIN users u ON u.id = r.reviewee_id
     WHERE r.reviewer_id = ?
     ORDER BY r.created_at DESC
     LIMIT 20"
        );
        $reviewsGivenStmt->execute([$id]);
        $reviewsGiven = $reviewsGivenStmt->fetchAll();

        View::render('users.show', [
            'title'   => 'User #' . $id,
            'user'    => $user,
            'profile' => $profile,
            'kyc'     => $kyc,
            'posts'   => $posts,
            'reviews'      => $reviews,
            'reviewsGiven' => $reviewsGiven,
            'rating'       => $rating,
        ]);
    }

    public function forceLogout(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $userId = (int) ($params['id'] ?? 0);

        Database::pdo()
            ->prepare("DELETE FROM personal_access_tokens WHERE user_id=?")
            ->execute([$userId]);

        Flash::set('success', "User #$userId signed out everywhere.");
        header('Location: /pasabuy/users/' . $userId);
        exit;
    }

    public function destroy(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $userId = (int) ($params['id'] ?? 0);

        if ($userId === (int) $_SESSION['admin_id']) {
            Flash::set('error', 'You cannot delete your own admin account.');
            header('Location: /pasabuy/users/' . $userId);
            exit;
        }

        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM personal_access_tokens WHERE user_id=?")->execute([$userId]);
            $db->prepare("DELETE FROM post_images WHERE post_id IN (SELECT id FROM posts WHERE user_id=?)")->execute([$userId]);
            $db->prepare("DELETE FROM posts WHERE user_id=?")->execute([$userId]);
            $db->prepare("DELETE FROM id_verifications WHERE user_id=?")->execute([$userId]);
            $db->prepare("DELETE FROM profiles WHERE user_id=?")->execute([$userId]);
            $db->prepare("DELETE FROM email_verifications WHERE user_id=?")->execute([$userId]);
            $db->prepare("DELETE FROM users WHERE id=? AND role != 'admin'")->execute([$userId]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Flash::set('error', 'Delete failed: ' . $e->getMessage());
            header('Location: /pasabuy/users/' . $userId);
            exit;
        }

        Flash::set('success', "User #$userId deleted.");
        header('Location: /pasabuy/users');
        exit;
    }
}
