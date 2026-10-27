<?php
namespace App\Services;

use App\Core\Database;

class UserDebt
{
    /**
     * Returns the user's debt summary.
     *
     * [
     *   'unpaid_count'      => int,
     *   'unpaid_total'      => float,
     *   'oldest_unpaid_at'  => ?string,
     *   'days_oldest'       => int,
     *   'is_blocked'        => bool,
     *   'block_reason'      => ?string,
     *   'is_warning'        => bool,
     * ]
     */
    public static function summary(int $userId): array
    {
        $config = require __DIR__ . '/../../config/config.php';
        $warn   = (float) ($config['fees']['debt_warn_threshold']  ?? 200);
        $block  = (float) ($config['fees']['debt_block_threshold'] ?? 500);
        $maxDays = (int)  ($config['fees']['debt_block_days']      ?? 30);

        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) AS cnt,
                    COALESCE(SUM(admin_fee), 0) AS total,
                    MIN(completed_at) AS oldest
             FROM transactions
             WHERE pasabuyer_id = ?
               AND status = 'completed'
               AND fee_paid_at IS NULL
               AND admin_fee > 0"
        );
        $stmt->execute([$userId]);
        $r = $stmt->fetch();

        $count      = (int) $r['cnt'];
        $total      = (float) $r['total'];
        $oldest     = $r['oldest'];
        $daysOldest = $oldest ? (int) floor((time() - strtotime($oldest)) / 86400) : 0;

        $isBlocked = false;
        $reason    = null;
        if ($total >= $block) {
            $isBlocked = true;
            $reason = "Unpaid fees exceed ₱" . number_format($block, 0);
        } elseif ($daysOldest >= $maxDays && $count > 0) {
            $isBlocked = true;
            $reason = "Oldest unpaid fee is {$daysOldest} days old (max {$maxDays})";
        }

        return [
            'unpaid_count'     => $count,
            'unpaid_total'     => round($total, 2),
            'oldest_unpaid_at' => $oldest,
            'days_oldest'      => $daysOldest,
            'is_blocked'       => $isBlocked,
            'block_reason'     => $reason,
            'is_warning'       => !$isBlocked && $total >= $warn,
        ];
    }
}