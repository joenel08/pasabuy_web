<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Models\Conversation;
use App\Models\Message;

class ConversationController
{
    public function index(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $uid  = (int) $auth['id'];

        $stmt = Database::pdo()->prepare(
            "SELECT c.*, p.title AS post_title, p.type AS post_type,
                    pi.title AS item_title,
                    buyer.email AS buyer_email,
                    buyer_pr.firstname AS buyer_firstname,
                    buyer_pr.lastname  AS buyer_lastname,
                    seller.email AS pasabuyer_email,
                    seller_pr.firstname AS seller_firstname,
                    seller_pr.lastname  AS seller_lastname,
                    (SELECT body       FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) AS last_message,
                    (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) AS last_message_at,
                    (SELECT COUNT(*)   FROM messages WHERE conversation_id = c.id AND sender_id != ? AND read_at IS NULL) AS unread,
                    cus.pinned_at,
                    cus.deleted_at
             FROM conversations c
             JOIN posts p          ON p.id = c.post_id
             LEFT JOIN post_items pi ON pi.id = c.item_id
             JOIN users buyer      ON buyer.id = c.buyer_id
             JOIN users seller     ON seller.id = c.pasabuyer_id
             LEFT JOIN profiles buyer_pr  ON buyer_pr.user_id = buyer.id
             LEFT JOIN profiles seller_pr ON seller_pr.user_id = seller.id
             LEFT JOIN conversation_user_state cus
                ON cus.conversation_id = c.id AND cus.user_id = ?
             WHERE (c.buyer_id = ? OR c.pasabuyer_id = ?)
               AND cus.deleted_at IS NULL
             ORDER BY
                CASE WHEN cus.pinned_at IS NOT NULL THEN 0 ELSE 1 END,
                cus.pinned_at DESC,
                COALESCE(c.last_message_at, c.created_at) DESC
             LIMIT 100"
        );
        $stmt->execute([$uid, $uid, $uid, $uid]);

        $rows = array_map(function ($r) use ($uid) {
            $imBuyer = (int) $r['buyer_id'] === $uid;
            $otherEmail = $imBuyer ? $r['pasabuyer_email'] : $r['buyer_email'];
            $otherName  = $imBuyer
                ? trim(($r['seller_firstname'] ?? '') . ' ' . ($r['seller_lastname'] ?? ''))
                : trim(($r['buyer_firstname'] ?? '') . ' ' . ($r['buyer_lastname'] ?? ''));
            return [
                'id'                => (int) $r['id'],
                'post_id'           => (int) $r['post_id'],
                'item_id'           => $r['item_id'] ? (int) $r['item_id'] : null,
                'post_title'        => $r['post_title'],
                'item_title'        => $r['item_title'] ?? '',
                'post_type'         => $r['post_type'],
                'other_party_email' => $otherEmail,
                'other_party_name'  => $otherName ?: $otherEmail,
                'last_message'      => $r['last_message'],
                'last_message_at'   => $r['last_message_at'],
                'unread'            => (int) $r['unread'],
                'pinned'            => $r['pinned_at'] !== null,
                'pinned_at'         => $r['pinned_at'],
            ];
        }, $stmt->fetchAll());

        Response::ok(['data' => $rows]);
    }

    public function start(Request $request, array $params = []): void
    {
        $auth   = Auth::require($request);
        $itemId = (int) $request->input('item_id', 0);
        if ($itemId <= 0) Response::error('item_id is required', 422);

        $stmt = Database::pdo()->prepare(
            "SELECT pi.*, p.status AS post_status, p.user_id AS owner_id,
                    p.type AS post_type, p.title AS post_title
             FROM post_items pi
             JOIN posts p ON p.id = pi.post_id
             WHERE pi.id = ? LIMIT 1"
        );
        $stmt->execute([$itemId]);
        $it = $stmt->fetch();
        if (!$it) Response::error('Item not found', 404);

        $me    = (int) $auth['id'];
        $owner = (int) $it['owner_id'];
        if ($me === $owner) Response::error('You cannot message your own post', 422);
        if (!in_array($it['post_status'], ['open','matched'], true)) {
            Response::error('This pasabuy is no longer active', 422);
        }
        if ($it['slots'] !== null && (int) $it['slots_taken'] >= (int) $it['slots']) {
            Response::error('This item is already full', 422);
        }

        if ($it['post_type'] === 'offer') {
            $pasabuyerId = $owner;
            $buyerId     = $me;
        } else {
            $buyerId     = $owner;
            $pasabuyerId = $me;
        }

        $existing = Database::pdo()->prepare(
            "SELECT * FROM conversations WHERE item_id = ? AND buyer_id = ? AND pasabuyer_id = ? LIMIT 1"
        );
        $existing->execute([$itemId, $buyerId, $pasabuyerId]);
        $conv = $existing->fetch();

        if (!$conv) {
            $convId = Conversation::insert([
                'post_id'      => (int) $it['post_id'],
                'item_id'      => $itemId,
                'buyer_id'     => $buyerId,
                'pasabuyer_id' => $pasabuyerId,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            Database::pdo()->prepare(
                "UPDATE post_items SET slots_taken = slots_taken + 1 WHERE id = ?"
            )->execute([$itemId]);

            Notifier::send($owner, 'new_inquiry',
                'Someone is interested in your item',
                $it['title'],
                ['item_id' => $itemId, 'conversation_id' => $convId]);

            $conv = Conversation::find($convId);
        } else {
            // If the requesting user had "deleted" this conversation, restore it
            Database::pdo()->prepare(
                "UPDATE conversation_user_state SET deleted_at = NULL
                 WHERE conversation_id = ? AND user_id = ?"
            )->execute([(int) $conv['id'], $me]);
        }

        Response::ok(['conversation_id' => (int) $conv['id']]);
    }

    public function messages(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $id   = (int) ($params['id'] ?? 0);
        $conv = Conversation::find($id);
        if (!$conv) Response::error('Conversation not found', 404);
        if ((int) $conv['buyer_id'] !== (int) $auth['id'] &&
            (int) $conv['pasabuyer_id'] !== (int) $auth['id']) {
            Response::error('Forbidden', 403);
        }

        $metaStmt = Database::pdo()->prepare(
            "SELECT p.title AS post_title, p.type AS post_type,
                    pi.title AS item_title, pi.description AS item_description,
                    (SELECT path FROM post_images
                     WHERE item_id = pi.id
                     ORDER BY sort_order LIMIT 1) AS item_image,
                    buyer.email AS buyer_email,
                    buyer_pr.firstname AS buyer_firstname,
                    buyer_pr.lastname  AS buyer_lastname,
                    buyer_pr.avatar_path AS buyer_avatar,
                    seller.email AS pasabuyer_email,
                    seller_pr.firstname AS seller_firstname,
                    seller_pr.lastname  AS seller_lastname,
                    seller_pr.avatar_path AS seller_avatar
             FROM conversations c
             JOIN posts p            ON p.id = c.post_id
             LEFT JOIN post_items pi ON pi.id = c.item_id
             JOIN users buyer        ON buyer.id = c.buyer_id
             JOIN users seller       ON seller.id = c.pasabuyer_id
             LEFT JOIN profiles buyer_pr  ON buyer_pr.user_id = buyer.id
             LEFT JOIN profiles seller_pr ON seller_pr.user_id = seller.id
             WHERE c.id = ? LIMIT 1"
        );
        $metaStmt->execute([$id]);
        $meta = $metaStmt->fetch() ?: [];

        $stmt = Database::pdo()->prepare(
            "SELECT m.*, u.email AS sender_email
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ?
             ORDER BY m.id ASC
             LIMIT 500"
        );
        $stmt->execute([$id]);

        Database::pdo()->prepare(
            "UPDATE messages SET read_at = NOW()
             WHERE conversation_id = ? AND sender_id != ? AND read_at IS NULL"
        )->execute([$id, (int) $auth['id']]);

        // Restore conversation for user if they had deleted it
        Database::pdo()->prepare(
            "UPDATE conversation_user_state SET deleted_at = NULL
             WHERE conversation_id = ? AND user_id = ?"
        )->execute([$id, (int) $auth['id']]);

        $imBuyer = (int) $conv['buyer_id'] === (int) $auth['id'];
        $otherEmail  = $imBuyer ? ($meta['pasabuyer_email'] ?? '') : ($meta['buyer_email'] ?? '');
        $otherName   = $imBuyer
            ? trim(($meta['seller_firstname'] ?? '') . ' ' . ($meta['seller_lastname'] ?? ''))
            : trim(($meta['buyer_firstname'] ?? '') . ' ' . ($meta['buyer_lastname'] ?? ''));
        $otherAvatar = $imBuyer ? ($meta['seller_avatar'] ?? null) : ($meta['buyer_avatar'] ?? null);
        $otherId     = $imBuyer ? (int) $conv['pasabuyer_id'] : (int) $conv['buyer_id'];

        $config = require __DIR__ . '/../../config/config.php';
        $base   = rtrim($config['app']['url'], '/');

        $rows = array_map(fn($m) => [
            'id'             => (int) $m['id'],
            'sender_id'      => (int) $m['sender_id'],
            'sender_email'   => $m['sender_email'],
            'body'           => $m['body'],
            'attachment_url' => !empty($m['attachment_path'])
                ? $base . '/' . ltrim($m['attachment_path'], '/')
                : null,
            'mine'           => (int) $m['sender_id'] === (int) $auth['id'],
            'created_at'     => $m['created_at'],
        ], $stmt->fetchAll());

        Response::ok([
            'data'               => $rows,
            'post_title'         => $meta['post_title'] ?? '',
            'post_type'          => $meta['post_type'] ?? 'offer',
            'item_title'         => $meta['item_title'] ?? '',
            'item_description'   => $meta['item_description'] ?? '',
            'item_image_url'     => !empty($meta['item_image'])
                ? $base . '/' . ltrim($meta['item_image'], '/')
                : null,
            'other_party_email'  => $otherEmail,
            'other_party_name'   => $otherName ?: $otherEmail,
            'other_party_avatar' => $otherAvatar
                ? $base . '/' . ltrim($otherAvatar, '/')
                : null,
            'other_party_id'     => $otherId,
        ]);
    }

    public function send(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $id   = (int) ($params['id'] ?? 0);
        $conv = Conversation::find($id);
        if (!$conv) Response::error('Conversation not found', 404);
        if ((int) $conv['buyer_id'] !== (int) $auth['id'] &&
            (int) $conv['pasabuyer_id'] !== (int) $auth['id']) {
            Response::error('Forbidden', 403);
        }

        $body = trim((string) $request->input('body', ''));
        $attachment = null;

        if (!empty($request->files['attachment'])) {
            $file = $request->files['attachment'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','heic','heif'], true)) {
                $dir = __DIR__ . '/../../storage/messages';
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                $fn = 'm_' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dir . '/' . $fn)) {
                    $attachment = 'storage/messages/' . $fn;
                }
            }
        }

        if ($body === '' && $attachment === null) {
            Response::error('Message is empty', 422);
        }

        $mid = Message::insert([
            'conversation_id' => $id,
            'sender_id'       => (int) $auth['id'],
            'body'            => $body ?: null,
            'attachment_path' => $attachment,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        Conversation::update($id, ['last_message_at' => date('Y-m-d H:i:s')]);

        // Restore the conversation for anyone who had "deleted" it
        Database::pdo()->prepare(
            "UPDATE conversation_user_state SET deleted_at = NULL WHERE conversation_id = ?"
        )->execute([$id]);

        $other = (int) $conv['buyer_id'] === (int) $auth['id']
            ? (int) $conv['pasabuyer_id']
            : (int) $conv['buyer_id'];

        Notifier::send($other, 'new_message', 'New message',
            $body !== '' ? mb_strimwidth($body, 0, 80, '…') : '📷 Image',
            ['conversation_id' => $id]);

        Response::ok(['id' => $mid], 'Sent');
    }

    // -----------------------------------------------------------------
    // POST /conversations/{id}/pin
    // -----------------------------------------------------------------
    public function pin(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $id   = (int) ($params['id'] ?? 0);
        $conv = Conversation::find($id);
        if (!$conv) Response::error('Conversation not found', 404);
        if ((int) $conv['buyer_id'] !== (int) $auth['id'] &&
            (int) $conv['pasabuyer_id'] !== (int) $auth['id']) {
            Response::error('Forbidden', 403);
        }

        Database::pdo()->prepare(
            "INSERT INTO conversation_user_state
                (conversation_id, user_id, pinned_at, created_at)
             VALUES (?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE pinned_at = NOW()"
        )->execute([$id, (int) $auth['id']]);

        Response::ok(['pinned' => true], 'Pinned');
    }

    // -----------------------------------------------------------------
    // POST /conversations/{id}/unpin
    // -----------------------------------------------------------------
    public function unpin(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $id   = (int) ($params['id'] ?? 0);
        $conv = Conversation::find($id);
        if (!$conv) Response::error('Conversation not found', 404);
        if ((int) $conv['buyer_id'] !== (int) $auth['id'] &&
            (int) $conv['pasabuyer_id'] !== (int) $auth['id']) {
            Response::error('Forbidden', 403);
        }

        Database::pdo()->prepare(
            "UPDATE conversation_user_state SET pinned_at = NULL
             WHERE conversation_id = ? AND user_id = ?"
        )->execute([$id, (int) $auth['id']]);

        Response::ok(['pinned' => false], 'Unpinned');
    }

    // -----------------------------------------------------------------
    // DELETE /conversations/{id}  — soft-delete for current user only
    // -----------------------------------------------------------------
    public function destroy(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $id   = (int) ($params['id'] ?? 0);
        $conv = Conversation::find($id);
        if (!$conv) Response::error('Conversation not found', 404);
        if ((int) $conv['buyer_id'] !== (int) $auth['id'] &&
            (int) $conv['pasabuyer_id'] !== (int) $auth['id']) {
            Response::error('Forbidden', 403);
        }

        Database::pdo()->prepare(
            "INSERT INTO conversation_user_state
                (conversation_id, user_id, deleted_at, created_at)
             VALUES (?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE deleted_at = NOW()"
        )->execute([$id, (int) $auth['id']]);

        Response::ok(null, 'Deleted from your list');
    }
}