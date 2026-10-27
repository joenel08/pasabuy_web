<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Models\IdVerification;
use App\Models\Post;
use App\Core\AdminNotifier;

class PostController
{
    // ---------------------------------------------------------------
    // GET /posts
    // ---------------------------------------------------------------
    public function index(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);

        $search   = trim((string) ($request->query['search']    ?? ''));
        $type     = trim((string) ($request->query['type']      ?? ''));
        $location = trim((string) ($request->query['location']  ?? ''));
        $dateFrom = trim((string) ($request->query['date_from'] ?? ''));
        $dateTo   = trim((string) ($request->query['date_to']   ?? ''));
        $status   = (string) ($request->query['status'] ?? '');
        $mine     = (string) ($request->query['mine'] ?? '');
        $page     = max(1, (int) ($request->query['page'] ?? 1));
        $perPage  = 20;
        $offset   = ($page - 1) * $perPage;

        $where = [];
        $bind  = [];

        if ($mine === '1') {
            $where[] = "p.user_id = ?";
            $bind[]  = (int) $auth['id'];
            if (in_array($status, ['open', 'matched', 'completed', 'closed', 'cancelled'], true)) {
                $where[] = "p.status = ?";
                $bind[]  = $status;
            }
        } else {
            if ($status === 'open') {
                $where[] = "p.status = 'open'";
            } elseif ($status === 'matched') {
                $where[] = "p.status = 'matched'";
            } else {
                $where[] = "p.status IN ('open','matched')";
            }
        }

        if ($search !== '') {
            $where[] = "(p.title LIKE ? OR p.description LIKE ?)";
            $bind[]  = "%$search%";
            $bind[]  = "%$search%";
        }
        if (in_array($type, ['offer', 'request'], true)) {
            $where[] = "p.type = ?";
            $bind[]  = $type;
        }
        if ($location !== '') {
            $where[] = "(p.origin_location LIKE ? OR p.destination_location LIKE ?)";
            $bind[]  = "%$location%";
            $bind[]  = "%$location%";
        }
        if ($dateFrom !== '') {
            $where[] = "p.travel_date >= ?";
            $bind[]  = $dateFrom;
        }
        if ($dateTo   !== '') {
            $where[] = "p.travel_date <= ?";
            $bind[]  = $dateTo;
        }

        $whereSql = $where ? implode(' AND ', $where) : '1=1';

        $sql = "SELECT p.*,
                       u.email AS owner_email,
                       pr.firstname, pr.lastname, pr.avatar_path
                FROM posts p
                JOIN users u ON u.id = p.user_id
                LEFT JOIN profiles pr ON pr.user_id = u.id
                WHERE $whereSql
                ORDER BY p.created_at DESC
                LIMIT $perPage OFFSET $offset";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($bind);
        $rows = $stmt->fetchAll();

        $favIds = [];
        $favStmt = Database::pdo()->prepare("SELECT post_id FROM favorites WHERE user_id=?");
        $favStmt->execute([(int) $auth['id']]);
        foreach ($favStmt->fetchAll() as $f) {
            $favIds[(int) $f['post_id']] = true;
        }

        $config = require __DIR__ . '/../../config/config.php';
        $base   = rtrim($config['app']['url'], '/');

        // Batch-load items + images
        $postIds     = array_column($rows, 'id');
        $itemsByPost = [];

        if ($postIds) {
            $in = implode(',', array_fill(0, count($postIds), '?'));
            $itemStmt = Database::pdo()->prepare(
                "SELECT id, post_id, title, description, slots, slots_taken, sort_order
                 FROM post_items
                 WHERE post_id IN ($in)
                 ORDER BY sort_order"
            );
            $itemStmt->execute($postIds);
            $itemRows = $itemStmt->fetchAll();

            $itemIds = array_column($itemRows, 'id');
            $imagesByItem = [];
            if ($itemIds) {
                $in2 = implode(',', array_fill(0, count($itemIds), '?'));
                $imgStmt = Database::pdo()->prepare(
                    "SELECT item_id, path FROM post_images WHERE item_id IN ($in2) ORDER BY sort_order"
                );
                $imgStmt->execute($itemIds);
                foreach ($imgStmt->fetchAll() as $r) {
                    $imagesByItem[(int) $r['item_id']][] = $r['path'];
                }
            }

            foreach ($itemRows as $ir) {
                $itemsByPost[(int) $ir['post_id']][] = [
                    'id'          => (int) $ir['id'],
                    'title'       => $ir['title'],
                    'description' => $ir['description'],
                    'slots'       => $ir['slots'] !== null ? (int) $ir['slots'] : null,
                    'slots_taken' => (int) $ir['slots_taken'],
                    'images'      => array_map(
                        fn($p) => $base . '/' . ltrim($p, '/'),
                        $imagesByItem[(int) $ir['id']] ?? []
                    ),
                ];
            }
        }

        $data = array_map(fn($r) => [
            'id'                   => (int) $r['id'],
            'owner_id'             => (int) $r['user_id'],
            'type'                 => $r['type'],
            'title'                => $r['title'],
            'description'          => $r['description'],
            'origin_location'      => $r['origin_location'],
            'destination_location' => $r['destination_location'],
            'travel_date'          => $r['travel_date'],
            'status'               => $r['status'],
            'is_favorite'          => isset($favIds[(int) $r['id']]),
            'owner_name'           => trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? '')) ?: $r['owner_email'],
            'owner_avatar'         => !empty($r['avatar_path'])
                ? $base . '/' . ltrim($r['avatar_path'], '/')
                : null,
            'created_at'           => $r['created_at'],
            'items'                => $itemsByPost[(int) $r['id']] ?? [],
        ], $rows);

        Response::ok(['data' => $data, 'page' => $page, 'per_page' => $perPage]);
    }

    // ---------------------------------------------------------------
    // GET /posts/{id}
    // ---------------------------------------------------------------
    public function show(Request $request, array $params = []): void
    {
        Auth::require($request);
        $post = Post::find((int) ($params['id'] ?? 0));
        if (!$post) Response::error('Post not found', 404);

        $post['owner_id'] = (int) $post['user_id'];

        $config = require __DIR__ . '/../../config/config.php';
        $base   = rtrim($config['app']['url'], '/');

        $stmt = Database::pdo()->prepare(
            "SELECT * FROM post_items WHERE post_id = ? ORDER BY sort_order"
        );
        $stmt->execute([$post['id']]);
        $items = $stmt->fetchAll();

        $itemIds = array_column($items, 'id');
        $imagesByItem = [];
        if ($itemIds) {
            $in = implode(',', array_fill(0, count($itemIds), '?'));
            $imgStmt = Database::pdo()->prepare(
                "SELECT item_id, path FROM post_images WHERE item_id IN ($in) ORDER BY sort_order"
            );
            $imgStmt->execute($itemIds);
            foreach ($imgStmt->fetchAll() as $r) {
                $imagesByItem[(int) $r['item_id']][] = $base . '/' . ltrim($r['path'], '/');
            }
        }

        $post['items'] = array_map(fn($ir) => [
            'id'          => (int) $ir['id'],
            'title'       => $ir['title'],
            'description' => $ir['description'],
            'slots'       => $ir['slots'] !== null ? (int) $ir['slots'] : null,
            'slots_taken' => (int) $ir['slots_taken'],
            'images'      => $imagesByItem[(int) $ir['id']] ?? [],
        ], $items);

        Response::ok($post);
    }

    // ---------------------------------------------------------------
    // POST /posts — multipart with items[i][...] and item_images[i][]
    // ---------------------------------------------------------------
    public function store(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);

        $kyc = IdVerification::findBy('user_id', (int) $auth['id']);
        if (!$kyc || $kyc['status'] !== 'accepted') {
            Response::error('Your account is not verified yet', 403);
        }

        // ---- Debt block check ----
        $debt = \App\Services\UserDebt::summary((int) $auth['id']);
        if ($debt['is_blocked']) {
            Response::error(
                'Your account is on hold. ' . $debt['block_reason'] . '. Please contact support to settle your outstanding fees.',
                402,  // Payment Required
                [
                    'unpaid_total' => $debt['unpaid_total'],
                    'unpaid_count' => $debt['unpaid_count'],
                    'oldest_at'    => $debt['oldest_unpaid_at'],
                ]
            );
        }

        $title       = trim((string) $request->input('title', ''));
        $description = trim((string) $request->input('description', ''));
        $type        = (string) $request->input('type', 'offer');
        $origin      = trim((string) $request->input('origin_location', ''));
        $destination = trim((string) $request->input('destination_location', ''));
        $travelDate  = $request->input('travel_date');

        $errors = [];
        if ($title === '')       $errors['title']       = 'Title is required';
        if ($description === '') $errors['description'] = 'Description is required';
        if (!in_array($type, ['offer', 'request'], true)) {
            $errors['type'] = 'Invalid type';
        }

        $items = $request->input('items', []);
        if (!is_array($items) || count($items) === 0) {
            $errors['items'] = 'At least one item is required';
        } else {
            foreach ($items as $i => $it) {
                if (empty($it['title']))       $errors["items.$i.title"]       = 'Item title is required';
                if (empty($it['description'])) $errors["items.$i.description"] = 'Item description is required';
            }
        }
        if ($errors) Response::error('Validation failed', 422, $errors);

        $postId = Post::insert([
            'user_id'              => (int) $auth['id'],
            'type'                 => $type,
            'title'                => $title,
            'description'          => $description,
            'origin_location'      => $origin ?: null,
            'destination_location' => $destination ?: null,
            'travel_date'          => $travelDate ?: null,
            'status'               => 'open',
            'created_at'           => date('Y-m-d H:i:s'),
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        $dir = __DIR__ . '/../../storage/posts';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $itemIds = [];
        foreach (array_values($items) as $i => $it) {
            $slots = isset($it['slots']) && is_numeric($it['slots']) && (int) $it['slots'] > 0
                ? (int) $it['slots']
                : null;

            Database::pdo()->prepare(
                "INSERT INTO post_items
                    (post_id, title, description, slots, slots_taken, sort_order, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 0, ?, NOW(), NOW())"
            )->execute([
                $postId,
                trim((string) $it['title']),
                trim((string) $it['description']),
                $slots,
                $i,
            ]);
            $itemId = (int) Database::pdo()->lastInsertId();
            $itemIds[] = $itemId;

            $itemFiles = $this->collectItemFiles($request->files, $i);

            foreach ($itemFiles as $f) {
                // 1. Did PHP itself reject the upload?
                if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    error_log("[POST IMG] PHP rejected {$f['name']}: code {$f['error']}");
                    continue;
                }

                // 2. Sanity check
                if (!is_uploaded_file($f['tmp_name'])) {
                    error_log("[POST IMG] is_uploaded_file=false for {$f['tmp_name']}");
                    continue;
                }

                // 3. Extension — HEIC/HEIF ACCEPTED
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'], true)) {
                    error_log("[POST IMG] bad ext: {$f['name']} (ext=$ext)");
                    continue;
                }

                // 4. Move
                $fn  = 'i' . $itemId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $abs = $dir . '/' . $fn;
                if (!move_uploaded_file($f['tmp_name'], $abs)) {
                    error_log("[POST IMG] move failed: {$f['tmp_name']} → {$abs}");
                    continue;
                }

                // 5. DB insert
                Database::pdo()->prepare(
                    "INSERT INTO post_images (post_id, item_id, path, sort_order) VALUES (?, ?, ?, ?)"
                )->execute([$postId, $itemId, 'storage/posts/' . $fn, 0]);
            }
        }

        AdminNotifier::push([
            'type'        => 'post_created',
            'category'    => 'posts',
            'title'       => 'New post: ' . $title,
            'body'        => count($items) . ' item(s) · ' . ($origin ?: '—') . ' → ' . ($destination ?: '—'),
            'link'        => '/pasabuy/posts',
            'target_type' => 'post',
            'target_id'   => $postId,
        ]);

        Response::ok(['id' => $postId, 'item_ids' => $itemIds], 'Post created');
    }

    // ---------------------------------------------------------------
    // POST|PUT /posts/{id}/full — merges by item id
    // ---------------------------------------------------------------
    public function updateFull(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $id   = (int) ($params['id'] ?? 0);
        $post = Post::find($id);
        if (!$post) Response::error('Post not found', 404);
        if ((int) $post['user_id'] !== (int) $auth['id']) Response::error('Forbidden', 403);
        if (in_array($post['status'], ['completed', 'cancelled'], true)) {
            Response::error('This post can no longer be edited', 422);
        }

        // Header fields
        $data = [];
        foreach (['title', 'description', 'origin_location', 'destination_location', 'travel_date', 'type'] as $f) {
            $v = $request->input($f);
            if ($v !== null) $data[$f] = $v;
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        Post::update($id, $data);

        $items = $request->input('items', null);
        if (!is_array($items)) {
            Response::ok(null, 'Post updated');
            return;
        }

        // ---- Keep set ----
        $keepIds = [];
        foreach ($items as $it) {
            if (!empty($it['id']) && is_numeric($it['id'])) {
                $keepIds[] = (int) $it['id'];
            }
        }

        // ---- Delete removed items + their images ----
        if (count($keepIds) > 0) {
            $in   = implode(',', array_fill(0, count($keepIds), '?'));
            $bind = array_merge([$id], $keepIds);

            $oldImgs = Database::pdo()->prepare(
                "SELECT pi.path
                 FROM post_images pi
                 JOIN post_items pin ON pin.id = pi.item_id
                 WHERE pin.post_id = ? AND pin.id NOT IN ($in)"
            );
            $oldImgs->execute($bind);
            foreach ($oldImgs->fetchAll() as $o) {
                $abs = __DIR__ . '/../../' . ltrim($o['path'], '/');
                if (is_file($abs)) @unlink($abs);
            }
            Database::pdo()->prepare(
                "DELETE pi FROM post_images pi
                 JOIN post_items pin ON pin.id = pi.item_id
                 WHERE pin.post_id = ? AND pin.id NOT IN ($in)"
            )->execute($bind);
            Database::pdo()->prepare(
                "DELETE FROM post_items WHERE post_id = ? AND id NOT IN ($in)"
            )->execute($bind);
        } else {
            $allImgs = Database::pdo()->prepare(
                "SELECT pi.path
                 FROM post_images pi
                 JOIN post_items pin ON pin.id = pi.item_id
                 WHERE pin.post_id = ?"
            );
            $allImgs->execute([$id]);
            foreach ($allImgs->fetchAll() as $o) {
                $abs = __DIR__ . '/../../' . ltrim($o['path'], '/');
                if (is_file($abs)) @unlink($abs);
            }
            Database::pdo()->prepare(
                "DELETE pi FROM post_images pi
                 JOIN post_items pin ON pin.id = pi.item_id
                 WHERE pin.post_id = ?"
            )->execute([$id]);
            Database::pdo()->prepare("DELETE FROM post_items WHERE post_id = ?")->execute([$id]);
        }

        // ---- Insert/update items + handle images ----
        $dir = __DIR__ . '/../../storage/posts';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        foreach (array_values($items) as $i => $it) {
            $slots = isset($it['slots']) && is_numeric($it['slots']) && (int) $it['slots'] > 0
                ? (int) $it['slots']
                : null;

            $itemId = (!empty($it['id']) && is_numeric($it['id'])) ? (int) $it['id'] : null;

            if ($itemId !== null) {
                Database::pdo()->prepare(
                    "UPDATE post_items
                     SET title = ?, description = ?, slots = ?, sort_order = ?, updated_at = NOW()
                     WHERE id = ? AND post_id = ?"
                )->execute([
                    trim((string) $it['title']),
                    trim((string) $it['description']),
                    $slots,
                    $i,
                    $itemId,
                    $id,
                ]);
            } else {
                Database::pdo()->prepare(
                    "INSERT INTO post_items
                        (post_id, title, description, slots, slots_taken, sort_order, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 0, ?, NOW(), NOW())"
                )->execute([
                    $id,
                    trim((string) $it['title']),
                    trim((string) $it['description']),
                    $slots,
                    $i,
                ]);
                $itemId = (int) Database::pdo()->lastInsertId();
            }

            // Only touch images if the client sent NEW ones for this item
            $itemFiles = $this->collectItemFiles($request->files, $i);
            if (!empty($itemFiles)) {
                // Delete existing images for this item
                $oldImgs = Database::pdo()->prepare(
                    "SELECT path FROM post_images WHERE item_id = ?"
                );
                $oldImgs->execute([$itemId]);
                foreach ($oldImgs->fetchAll() as $o) {
                    $abs = __DIR__ . '/../../' . ltrim($o['path'], '/');
                    if (is_file($abs)) @unlink($abs);
                }
                Database::pdo()->prepare("DELETE FROM post_images WHERE item_id = ?")
                    ->execute([$itemId]);

                // Save new ones
                foreach ($itemFiles as $f) {
                    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        error_log("[POST IMG] PHP rejected {$f['name']}: code {$f['error']}");
                        continue;
                    }
                    if (!is_uploaded_file($f['tmp_name'])) {
                        error_log("[POST IMG] is_uploaded_file=false for {$f['tmp_name']}");
                        continue;
                    }

                    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'], true)) {
                        error_log("[POST IMG] bad ext: {$f['name']} (ext=$ext)");
                        continue;
                    }

                    $fn  = 'i' . $itemId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $abs = $dir . '/' . $fn;
                    if (!move_uploaded_file($f['tmp_name'], $abs)) {
                        error_log("[POST IMG] move failed: {$f['tmp_name']} → {$abs}");
                        continue;
                    }

                    Database::pdo()->prepare(
                        "INSERT INTO post_images (post_id, item_id, path, sort_order) VALUES (?, ?, ?, ?)"
                    )->execute([$id, $itemId, 'storage/posts/' . $fn, 0]);
                }
            }
        }

        Response::ok(null, 'Post updated');
    }

    // ---------------------------------------------------------------
    // POST /posts/{id}/close
    // ---------------------------------------------------------------
    public function close(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $id   = (int) ($params['id'] ?? 0);
        $post = Post::find($id);
        if (!$post) Response::error('Post not found', 404);
        if ((int) $post['user_id'] !== (int) $auth['id']) Response::error('Forbidden', 403);
        if (!in_array($post['status'], ['open', 'matched'], true)) {
            Response::error('Post is already closed', 422);
        }

        Post::update($id, [
            'status'     => 'closed',
            'closed_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $stmt = Database::pdo()->prepare(
            "SELECT buyer_id, pasabuyer_id FROM conversations WHERE post_id = ?"
        );
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll() as $c) {
            $other = (int) $c['buyer_id'] === (int) $auth['id']
                ? (int) $c['pasabuyer_id']
                : (int) $c['buyer_id'];
            Notifier::send(
                $other,
                'post_closed',
                'A pasabuy was marked as done',
                $post['title'],
                ['post_id' => $id]
            );
        }

        Response::ok(null, 'Post closed');
    }

    // ---------------------------------------------------------------
    // DELETE /posts/{id}
    // ---------------------------------------------------------------
    public function destroy(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $id   = (int) ($params['id'] ?? 0);
        $post = Post::find($id);
        if (!$post) Response::error('Post not found', 404);
        if ((int) $post['user_id'] !== (int) $auth['id']) Response::error('Forbidden', 403);

        Post::update($id, ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')]);
        Response::ok(null, 'Post cancelled');
    }

    // ---------------------------------------------------------------
    // Helper — extract per-item uploaded files from $_FILES
    // ---------------------------------------------------------------
    private function collectItemFiles(array $requestFiles, int $itemIndex): array
    {
        if (empty($requestFiles['item_images'])) return [];

        $all    = $requestFiles['item_images'];
        $names  = $all['name']     ?? [];
        $tmps   = $all['tmp_name'] ?? [];
        $errors = $all['error']    ?? [];
        $sizes  = $all['size']     ?? [];
        $types  = $all['type']     ?? [];

        $itemNames  = is_array($names)  ? ($names[$itemIndex]  ?? null) : ($itemIndex === 0 ? $names  : null);
        $itemTmps   = is_array($tmps)   ? ($tmps[$itemIndex]   ?? null) : ($itemIndex === 0 ? $tmps   : null);
        $itemErrors = is_array($errors) ? ($errors[$itemIndex] ?? null) : ($itemIndex === 0 ? $errors : null);
        $itemSizes  = is_array($sizes)  ? ($sizes[$itemIndex]  ?? null) : ($itemIndex === 0 ? $sizes  : null);
        $itemTypes  = is_array($types)  ? ($types[$itemIndex]  ?? null) : ($itemIndex === 0 ? $types  : null);

        if ($itemNames === null) return [];

        // Normalize single-file (scalar) to array
        if (!is_array($itemNames)) {
            $itemNames  = [$itemNames];
            $itemTmps   = [$itemTmps];
            $itemErrors = [$itemErrors];
            $itemSizes  = [$itemSizes];
            $itemTypes  = [$itemTypes];
        }

        $out = [];
        foreach ($itemNames as $k => $name) {
            if ($name === '' || $name === null) continue;
            $out[] = [
                'name'     => $name,
                'tmp_name' => $itemTmps[$k]   ?? null,
                'error'    => $itemErrors[$k] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $itemSizes[$k]  ?? 0,
                'type'     => $itemTypes[$k]  ?? '',
            ];
        }
        return $out;
    }
}
