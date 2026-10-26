<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class FavoriteController
{
    public function index(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);

        $stmt = Database::pdo()->prepare(
            "SELECT p.id, p.type, p.title, p.description,
                    p.origin_location, p.destination_location, p.travel_date,
                    p.status, p.user_id AS owner_id, p.slots, p.slots_taken,
                    u.email AS owner_name,
                    f.created_at AS saved_at,
                    (SELECT path FROM post_images WHERE post_id=p.id ORDER BY sort_order LIMIT 1) AS first_image
             FROM favorites f
             JOIN posts p ON p.id = f.post_id
             JOIN users u ON u.id = p.user_id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC"
        );
        $stmt->execute([(int) $auth['id']]);

        $data = array_map(fn($r) => [
            'id'                   => (int) $r['id'],
            'owner_id'             => (int) $r['owner_id'],
            'type'                 => $r['type'],
            'title'                => $r['title'],
            'description'          => $r['description'],
            'origin_location'      => $r['origin_location'],
            'destination_location' => $r['destination_location'],
            'travel_date'          => $r['travel_date'],
            'status'               => $r['status'],
            'owner_name'           => $r['owner_name'],
            'slots'                => $r['slots'] !== null ? (int) $r['slots'] : null,
            'slots_taken'          => (int) $r['slots_taken'],
            'images'               => $r['first_image'] ? [$r['first_image']] : [],
            'created_at'           => $r['saved_at'],
        ], $stmt->fetchAll());

        Response::ok(['data' => $data]);
    }

    public function toggle(Request $request, array $params = []): void
    {
        $auth   = Auth::require($request);
        $postId = (int) $request->input('post_id', 0);
        if ($postId <= 0) Response::error('post_id required', 422);

        $exists = Database::pdo()->prepare("SELECT id FROM posts WHERE id=? LIMIT 1");
        $exists->execute([$postId]);
        if (!$exists->fetch()) Response::error('Post not found', 404);

        $check = Database::pdo()->prepare(
            "SELECT id FROM favorites WHERE user_id=? AND post_id=? LIMIT 1"
        );
        $check->execute([(int) $auth['id'], $postId]);
        $row = $check->fetch();

        if ($row) {
            Database::pdo()->prepare("DELETE FROM favorites WHERE id=?")->execute([(int) $row['id']]);
            Response::ok(['saved' => false], 'Removed from saved');
        }

        Database::pdo()->prepare(
            "INSERT INTO favorites (user_id, post_id, created_at) VALUES (?, ?, NOW())"
        )->execute([(int) $auth['id'], $postId]);

        Response::ok(['saved' => true], 'Saved');
    }
}