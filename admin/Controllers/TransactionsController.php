<?php
namespace Admin\Controllers;

use Admin\Core\AdminAuth;
use Admin\Core\View;
use App\Core\Database;
use App\Core\Request;

class TransactionsController
{
    public function index(Request $request, array $params = []): void
    {
        AdminAuth::require();

        $status = (string) ($request->query['status'] ?? 'all');
        $where  = "1=1";
        $bind   = [];

        if (in_array($status, ['pending','in_progress','completed','disputed','cancelled'], true)) {
            $where = "t.status = ?";
            $bind[] = $status;
        }

        $stmt = Database::pdo()->prepare(
            "SELECT t.*,
                    buyer.email  AS buyer_email,
                    seller.email AS pasabuyer_email
             FROM transactions t
             LEFT JOIN users buyer  ON buyer.id  = t.buyer_id
             LEFT JOIN users seller ON seller.id = t.pasabuyer_id
             WHERE $where
             ORDER BY t.updated_at DESC
             LIMIT 200"
        );
        $stmt->execute($bind);

        $totals = Database::pdo()->query(
            "SELECT
                COALESCE(SUM(CASE WHEN status='completed' THEN agreed_price END),0) AS gross,
                COALESCE(SUM(CASE WHEN status='completed' THEN admin_fee END),0)    AS fees,
                COUNT(CASE WHEN status='completed' THEN 1 END)                       AS completed
             FROM transactions"
        )->fetch();

        View::render('transactions.index', [
            'title'  => 'Transactions',
            'items'  => $stmt->fetchAll(),
            'status' => $status,
            'totals' => $totals,
        ]);
    }
}