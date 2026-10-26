<?php

namespace App\Services;

use App\Core\Database;

class TrendBuilder
{
    public function build(string $range, array $scope = []): array
    {
        $rangeMap = [
            '7d'  => ['bucket' => 'day',   'count' => 7,   'label' => 'Last 7 days'],
            '30d' => ['bucket' => 'day',   'count' => 30,  'label' => 'Last 30 days'],
            '90d' => ['bucket' => 'day',   'count' => 90,  'label' => 'Last 90 days'],
            '12m' => ['bucket' => 'month', 'count' => 12,  'label' => 'Last 12 months'],
        ];
        $cfg    = $rangeMap[$range] ?? $rangeMap['30d'];
        $bucket = $cfg['bucket'];
        $count  = $cfg['count'];

        $now   = new \DateTime('today');
        $start = $bucket === 'day'
            ? (clone $now)->modify('-' . ($count - 1) . ' days')
            : (clone $now)->modify('-' . ($count - 1) . ' months')
                ->modify('first day of this month');

        [$whereSql, $bind] = $this->whereClause($scope, $start);

        // admin_fee added to SELECT
        $stmt = Database::pdo()->prepare(
            "SELECT id, status, agreed_price, admin_fee,
                    completed_at, cancelled_at, updated_at
             FROM transactions
             WHERE $whereSql"
        );
        $stmt->execute($bind);

        // Pre-fill buckets — 'fees' added
        $points = [];
        if ($bucket === 'day') {
            for ($i = 0; $i < $count; $i++) {
                $d = (clone $start)->modify("+$i days")->format('Y-m-d');
                $points[$d] = [
                    'key'       => $d,
                    'label'     => (new \DateTime($d))->format('M j'),
                    'completed' => 0,
                    'cancelled' => 0,
                    'disputed'  => 0,
                    'value'     => 0.0,
                    'fees'      => 0.0,
                ];
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                $k = (clone $start)->modify("+$i months")->format('Y-m');
                $points[$k] = [
                    'key'       => $k,
                    'label'     => (new \DateTime($k . '-01'))->format('M Y'),
                    'completed' => 0,
                    'cancelled' => 0,
                    'disputed'  => 0,
                    'value'     => 0.0,
                    'fees'      => 0.0,
                ];
            }
        }

        foreach ($stmt->fetchAll() as $r) {
            $status = $r['status'];
            $ts     = $r['completed_at'] ?? $r['cancelled_at'] ?? $r['updated_at'];
            if (!$ts) continue;

            $key = $bucket === 'day' ? substr($ts, 0, 10) : substr($ts, 0, 7);
            if (!isset($points[$key])) continue;

            if ($status === 'completed') {
                $points[$key]['completed']++;
                $points[$key]['value'] += (float) ($r['agreed_price'] ?? 0);
                $points[$key]['fees']  += (float) ($r['admin_fee']    ?? 0);
            } elseif ($status === 'cancelled') {
                $points[$key]['cancelled']++;
            } elseif ($status === 'disputed') {
                $points[$key]['disputed']++;
            }
        }

        return [
            'range'   => $range,
            'label'   => $cfg['label'],
            'bucket'  => $bucket,
            'points'  => array_values($points),
            'summary' => $this->summarize($scope),
        ];
    }

    private function summarize(array $scope): array
    {
        [$whereSql, $bind] = $this->whereClause($scope, null);

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(status = 'completed')  AS completed,
                    SUM(status = 'cancelled')  AS cancelled,
                    SUM(status = 'disputed')   AS disputed,
                    SUM(status IN ('pending','in_progress','buyer_done','pasabuyer_done')) AS active,
                    COALESCE(SUM(CASE WHEN status='completed' THEN agreed_price END), 0) AS value,
                    COALESCE(SUM(CASE WHEN status='completed' THEN admin_fee END),   0) AS fees_collected
                FROM transactions
                WHERE $whereSql";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($bind);
        $r = $stmt->fetch();

        $total     = (int) $r['total'];
        $completed = (int) $r['completed'];

        $avgSql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) AS hrs
                   FROM transactions
                   WHERE $whereSql AND status = 'completed' AND completed_at IS NOT NULL";
        $avgStmt = Database::pdo()->prepare($avgSql);
        $avgStmt->execute($bind);
        $avgHrs = (float) ($avgStmt->fetch()['hrs'] ?? 0);

        $successRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

        return [
            'total'            => $total,
            'completed'        => $completed,
            'cancelled'        => (int) $r['cancelled'],
            'disputed'         => (int) $r['disputed'],
            'active'           => (int) $r['active'],
            'value'            => (float) $r['value'],
            'fees_collected'   => (float) $r['fees_collected'],
            'success_rate'     => $successRate,
            'avg_completion_h' => round($avgHrs, 1),
        ];
    }

    private function whereClause(array $scope, ?\DateTime $start): array
    {
        $where = [];
        $bind  = [];

        if (isset($scope['buyer_id'], $scope['pasabuyer_id'])) {
            $where[] = '(buyer_id = ? OR pasabuyer_id = ?)';
            $bind[]  = (int) $scope['buyer_id'];
            $bind[]  = (int) $scope['pasabuyer_id'];
        } elseif (isset($scope['user_id'])) {
            $where[] = '(buyer_id = ? OR pasabuyer_id = ?)';
            $bind[]  = (int) $scope['user_id'];
            $bind[]  = (int) $scope['user_id'];
        }

        if ($start !== null) {
            $where[] = 'COALESCE(completed_at, cancelled_at, updated_at) >= ?';
            $bind[]  = $start->format('Y-m-d 00:00:00');
        }

        return [$where ? implode(' AND ', $where) : '1=1', $bind];
    }
}