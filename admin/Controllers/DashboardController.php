<?php
namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\View;
use App\Core\Database;
use App\Core\Request;

class DashboardController
{
   public function root(Request $request, array $params = []): void
{
    header('Location: ' . (AdminAuth::check() ? '/pasabuy/dashboard' : '/pasabuy/login'));
    exit;
}
  public function index(Request $request, array $params = []): void
{
    AdminAuth::require();
    $db = Database::pdo();

    $stats = [
        'total_users'     => (int) $db->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn(),
        'accepted_users'  => (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'accepted' AND role != 'admin'")->fetchColumn(),
        'pending_kyc'     => (int) $db->query("SELECT COUNT(*) FROM id_verifications WHERE status = 'pending'")->fetchColumn(),
        'declined_kyc'    => (int) $db->query("SELECT COUNT(*) FROM id_verifications WHERE status = 'declined'")->fetchColumn(),
        'total_posts'     => (int) $db->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
        'open_posts'      => (int) $db->query("SELECT COUNT(*) FROM posts WHERE status = 'open'")->fetchColumn(),
        'total_items'     => (int) $db->query("SELECT COUNT(*) FROM post_items")->fetchColumn(),   // ← NEW
        'open_items'      => (int) $db->query(                                                     // ← NEW
            "SELECT COUNT(*) FROM post_items pi
             JOIN posts p ON p.id = pi.post_id
             WHERE p.status IN ('open','matched')
               AND (pi.slots IS NULL OR pi.slots_taken < pi.slots)"
        )->fetchColumn(),
    ];

    View::render('dashboard.index', ['title' => 'Dashboard', 'stats' => $stats]);
}
}