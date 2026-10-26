<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Profile;

class ProfileController
{
    public function show(Request $request, array $params = []): void
    {
        $auth    = Auth::require($request);
        $profile = Profile::findBy('user_id', (int) $auth['id']);

        if (!$profile) {
            Response::ok(null, 'No profile yet');
        }
        Response::ok($this->payload($profile));
    }

    public function save(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);

        $required = ['lastname', 'firstname', 'street_purok', 'barangay', 'city_municipality', 'province', 'country', 'zip_code'];
        $errors = [];
        $data = [];

        foreach ($required as $f) {
            $v = trim((string) $request->input($f, ''));
            if ($v === '') $errors[$f] = ucwords(str_replace('_', ' ', $f)) . ' is required';
            $data[$f] = $v;
        }
        foreach (['middlename', 'extname'] as $f) {
            $data[$f] = trim((string) $request->input($f, ''));
        }
        if ($data['zip_code'] !== '' && !preg_match('/^\d{3,10}$/', $data['zip_code'])) {
            $errors['zip_code'] = 'Invalid zip code';
        }
        if ($errors) Response::error('Validation failed', 422, $errors);

        $existing = Profile::findBy('user_id', (int) $auth['id']);
        $data['user_id']    = (int) $auth['id'];
        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            Profile::update((int) $existing['id'], $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            Profile::insert($data);
        }

        Response::ok(null, 'Profile saved');
    }

    public function uploadAvatar(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);

        if (empty($request->files['avatar'])) {
            Response::error('Avatar image is required', 422);
        }

        $file = $request->files['avatar'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            Response::error('Only jpg, png, webp allowed', 422);
        }

        $dir = __DIR__ . '/../../storage/avatars';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $fn = 'a_' . (int) $auth['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $abs = $dir . '/' . $fn;
        if (!move_uploaded_file($file['tmp_name'], $abs)) {
            Response::error('Failed to save avatar', 500);
        }

        $rel = 'storage/avatars/' . $fn;
        $existing = Profile::findBy('user_id', (int) $auth['id']);
        if ($existing) {
            Profile::update((int) $existing['id'], ['avatar_path' => $rel, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        Response::ok(['avatar_path' => $rel], 'Avatar updated');
    }

    private function payload(array $p): array
    {
        return [
            'lastname'           => $p['lastname'] ?? '',
            'firstname'          => $p['firstname'] ?? '',
            'middlename'         => $p['middlename'] ?? '',
            'extname'            => $p['extname'] ?? '',
            'street_purok'       => $p['street_purok'] ?? '',
            'barangay'           => $p['barangay'] ?? '',
            'city_municipality'  => $p['city_municipality'] ?? '',
            'province'           => $p['province'] ?? '',
            'country'            => $p['country'] ?? '',
            'zip_code'           => $p['zip_code'] ?? '',
            'avatar_url'         => !empty($p['avatar_path'])
                ? rtrim((require __DIR__ . '/../../config/config.php')['app']['url'], '/')
                . '/' . ltrim($p['avatar_path'], '/')
                : null,
        ];
    }

    public function publicProfile(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $id   = (int) ($params['id'] ?? 0);
        if ($id <= 0) Response::error('Invalid user', 422);

        $user = \App\Models\User::find($id);
        if (!$user) Response::error('User not found', 404);

        $profile = Profile::findBy('user_id', $id);
        $kyc     = \App\Models\IdVerification::findBy('user_id', $id);

        $db = \App\Core\Database::pdo();

        $stats = $db->prepare(
            "SELECT
            COUNT(*) AS total,
            SUM(status='completed') AS completed,
            SUM(status='cancelled') AS cancelled
         FROM transactions
         WHERE buyer_id=? OR pasabuyer_id=?"
        );
        $stats->execute([$id, $id]);
        $s = $stats->fetch();

        $review = $db->prepare(
            "SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM reviews WHERE reviewee_id=?"
        );
        $review->execute([$id]);
        $r = $review->fetch();

        $recentPosts = $db->prepare(
            "SELECT id, title, type, status, created_at,
                (SELECT path FROM post_images WHERE post_id=posts.id ORDER BY sort_order LIMIT 1) AS first_image
         FROM posts WHERE user_id=? ORDER BY created_at DESC LIMIT 6"
        );
        $recentPosts->execute([$id]);
        $postsRaw = $recentPosts->fetchAll();

        $config = require __DIR__ . '/../../config/config.php';
        $base   = rtrim($config['app']['url'], '/');

        $posts = array_map(fn($p) => [
            'id'          => (int) $p['id'],
            'title'       => $p['title'],
            'type'        => $p['type'],
            'status'      => $p['status'],
            'created_at'  => $p['created_at'],
            'image'       => $p['first_image'] ? $base . '/' . ltrim($p['first_image'], '/') : null,
        ], $postsRaw);

        $kycStatus = $kyc['status'] ?? 'none';
        $kycType   = $kycStatus === 'accepted' ? ($kyc['id_type'] ?? null) : null;

        Response::ok([
            'id'              => $id,
            'email'           => $user['email'],
            'member_since'    => $user['created_at'],
            'full_name'       => $profile
                ? trim(($profile['firstname'] ?? '') . ' ' . ($profile['lastname'] ?? ''))
                : null,
            'avatar_url'      => !empty($profile['avatar_path'])
                ? $base . '/' . ltrim($profile['avatar_path'], '/')
                : null,
            'location'        => $profile
                ? trim(($profile['city_municipality'] ?? '') . ', ' . ($profile['province'] ?? ''), ', ')
                : null,
            'kyc_status'      => $kycStatus,
            'kyc_type'        => $kycType,
            'kyc_reviewed_at' => $kycStatus === 'accepted' ? ($kyc['reviewed_at'] ?? null) : null,
            'completed_deals' => (int) $s['completed'],
            'cancelled_deals' => (int) $s['cancelled'],
            'total_deals'     => (int) $s['total'],
            'rating'          => $r['avg'] !== null ? round((float) $r['avg'], 2) : null,
            'review_count'    => (int) $r['cnt'],
            'recent_posts'    => $posts,
        ]);
    }

    public function requestDeletion(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $reason = trim((string) $request->input('reason', ''));

        \App\Core\Database::pdo()->prepare(
            "INSERT INTO reports (reporter_id, target_type, target_id, reason, description, status, created_at)
         VALUES (?, 'user', ?, 'other', ?, 'pending', NOW())"
        )->execute([
            (int) $auth['id'],
            (int) $auth['id'],
            'Account deletion request: ' . ($reason ?: 'no reason provided')
        ]);

        Response::ok(null, 'Deletion request received. Our team will contact you.');
    }
    // public function publicProfile(Request $request, array $params = []): void
    // {
    //     $auth = Auth::require($request);
    //     $id   = (int) ($params['id'] ?? 0);
    //     if ($id <= 0) Response::error('Invalid user', 422);

    //     $user = \App\Models\User::find($id);
    //     if (!$user) Response::error('User not found', 404);

    //     $profile = Profile::findBy('user_id', $id);
    //     $kyc     = \App\Models\IdVerification::findBy('user_id', $id);

    //     $db = \App\Core\Database::pdo();
    //     $stmt = $db->prepare(
    //         "SELECT COUNT(*) FROM transactions
    //      WHERE (buyer_id = ? OR pasabuyer_id = ?) AND status = 'completed'"
    //     );
    //     $stmt->execute([$id, $id]);
    //     $completed = (int) $stmt->fetchColumn();

    //     $rating = null; // placeholder — add reviews table later

    //     // Only expose ID *type* if verified — never the ID number or image.
    //     $kycStatus = $kyc['status'] ?? 'none';
    //     $kycType   = $kycStatus === 'accepted' ? ($kyc['id_type'] ?? null) : null;

    //     $config = require __DIR__ . '/../../config/config.php';

    //     Response::ok([
    //         'id'             => $id,
    //         'email'          => $user['email'],
    //         'member_since'   => $user['created_at'],
    //         'full_name'      => $profile
    //             ? trim(($profile['firstname'] ?? '') . ' ' . ($profile['lastname'] ?? ''))
    //             : null,
    //         'avatar_url'     => !empty($profile['avatar_path'])
    //             ? rtrim($config['app']['url'], '/') . '/' . ltrim($profile['avatar_path'], '/')
    //             : null,
    //         'location'       => $profile
    //             ? trim(($profile['city_municipality'] ?? '') . ', ' . ($profile['province'] ?? ''), ', ')
    //             : null,
    //         'kyc_status'     => $kycStatus,
    //         'kyc_type'       => $kycType,
    //         'kyc_reviewed_at' => $kycStatus === 'accepted' ? ($kyc['reviewed_at'] ?? null) : null,
    //         'completed_deals' => $completed,
    //         'rating'         => $rating,
    //     ]);
    // }
}
