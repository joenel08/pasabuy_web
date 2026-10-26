<?php
namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\View;
use App\Core\Database;
use App\Core\Request;

class PostsController
{
    // ---------------------------------------------------------------
    // GET /pasabuy/posts — list with item counts
    // ---------------------------------------------------------------
    public function index(Request $request, array $params = []): void
    {
        AdminAuth::require();

        $status = (string) ($request->query['status'] ?? 'all');
        $search = trim((string) ($request->query['search'] ?? ''));

        $where = ["1=1"];
        $bind  = [];

        if (in_array($status, ['open','matched','completed','closed','cancelled'], true)) {
            $where[] = "p.status = ?";
            $bind[]  = $status;
        }
        if ($search !== '') {
            $where[] = "(p.title LIKE ? OR p.description LIKE ?)";
            $bind[]  = "%$search%";
            $bind[]  = "%$search%";
        }

        $whereSql = implode(' AND ', $where);

        $stmt = Database::pdo()->prepare(
            "SELECT p.*, u.email AS owner_email,
                    pr.firstname, pr.lastname,
                    (SELECT COUNT(*) FROM post_items pi WHERE pi.post_id = p.id) AS item_count,
                    (SELECT COUNT(*) FROM post_items pi
                     WHERE pi.post_id = p.id AND pi.slots IS NOT NULL
                       AND pi.slots_taken >= pi.slots) AS full_item_count,
                    (SELECT COUNT(*) FROM post_items pi
                     WHERE pi.post_id = p.id AND (pi.slots IS NULL OR pi.slots_taken < pi.slots)) AS open_item_count,
                    (SELECT COUNT(*) FROM post_images pi2
                     JOIN post_items pi ON pi.id = pi2.item_id
                     WHERE pi.post_id = p.id) AS image_count,
                    (SELECT pi2.path FROM post_images pi2
                     JOIN post_items pi ON pi.id = pi2.item_id
                     WHERE pi.post_id = p.id
                     ORDER BY pi.sort_order, pi2.sort_order LIMIT 1) AS thumbnail
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE $whereSql
             ORDER BY p.created_at DESC
             LIMIT 200"
        );
        $stmt->execute($bind);

        $config = require __DIR__ . '/../../api/config/config.php';
        $base   = rtrim($config['app']['url'], '/');

        View::render('posts.index', [
            'title'  => 'Posts',
            'items'  => $stmt->fetchAll(),
            'status' => $status,
            'search' => $search,
            'base'   => $base,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /pasabuy/posts/{id} — detail with all items
    // ---------------------------------------------------------------
    public function show(Request $request, array $params = []): void
    {
        AdminAuth::require();
        $id = (int) ($params['id'] ?? 0);

        $stmt = Database::pdo()->prepare(
            "SELECT p.*, u.email AS owner_email,
                    pr.firstname, pr.lastname, pr.city_municipality, pr.province
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN profiles pr ON pr.user_id = u.id
             WHERE p.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $post = $stmt->fetch();
        if (!$post) {
            http_response_code(404);
            echo 'Post not found';
            return;
        }

        $config = require __DIR__ . '/../../api/config/config.php';
        $base   = rtrim($config['app']['url'], '/');

        // Load items
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM post_items WHERE post_id = ? ORDER BY sort_order"
        );
        $stmt->execute([$id]);
        $items = $stmt->fetchAll();

        // Load images per item
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

        // Active conversations per item
        $convByItem = [];
        if ($itemIds) {
            $in = implode(',', array_fill(0, count($itemIds), '?'));
            $cStmt = Database::pdo()->prepare(
                "SELECT item_id, COUNT(*) AS c FROM conversations
                 WHERE item_id IN ($in) GROUP BY item_id"
            );
            $cStmt->execute($itemIds);
            foreach ($cStmt->fetchAll() as $r) {
                $convByItem[(int) $r['item_id']] = (int) $r['c'];
            }
        }

        View::render('posts.show', [
            'title'        => 'Post #' . $id,
            'post'         => $post,
            'items'        => $items,
            'imagesByItem' => $imagesByItem,
            'convByItem'   => $convByItem,
        ]);
    }
}