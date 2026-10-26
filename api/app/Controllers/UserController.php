<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use Admin\Core\Flash;

class UserController
{
    public function saveFcmToken(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $token = trim((string) $request->input('token', ''));
        $platform = (string) $request->input('platform', 'android');

        if ($token === '') {
            Response::error('Token is required', 422);
        }
        if (!in_array($platform, ['android', 'ios'], true)) {
            $platform = 'android';
        }

        $db = Database::pdo();
        // Remove the same token if it's attached to a different user
        $db->prepare("DELETE FROM fcm_tokens WHERE token = ? AND user_id != ?")
            ->execute([$token, (int) $auth['id']]);

        // Upsert
        $db->prepare(
            "INSERT INTO fcm_tokens (user_id, token, platform, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                platform = VALUES(platform),
                updated_at = NOW()"
        )->execute([(int) $auth['id'], $token, $platform]);

        Response::ok(null, 'Token saved');
    }

    public function removeFcmToken(Request $request, array $params = []): void
    {
        $auth = Auth::require($request);
        $token = trim((string) $request->input('token', ''));
        if ($token === '') Response::error('Token required', 422);

        Database::pdo()->prepare(
            "DELETE FROM fcm_tokens WHERE user_id = ? AND token = ?"
        )->execute([(int) $auth['id'], $token]);

        Response::ok(null, 'Token removed');
    }

    /** GET /me/stats/trend?range=30d  */
    public function myTrend(Request $request, array $params = []): void
    {
        $auth  = Auth::require($request);
        $me    = (int) $auth['id'];
        $range = (string) ($request->query['range'] ?? '30d');

        Response::ok($this->buildTrend($range, ['buyer_id' => $me, 'pasabuyer_id' => $me]));
    }

    /**
     * Shared trend builder. Pass either:
     *   ['buyer_id' => N, 'pasabuyer_id' => N]   for a single user (both roles)
     *   ['user_id' => N]                          for either side, one column
     *   []                                        for the entire system
     */
    public function buildTrend(string $range, array $scope): array
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
        $now    = new \DateTime('today');
        $start  = $bucket === 'day'
            ? (clone $now)->modify('-' . ($count - 1) . ' days')
            : (clone $now)->modify('-' . ($count - 1) . ' months')->modify('first day of this month');

        // ----- Where clause -----
        $where  = [];
        $bind   = [];

        if (isset($scope['buyer_id']) && isset($scope['pasabuyer_id'])) {
            $where[] = '(buyer_id = ? OR pasabuyer_id = ?)';
            $bind[]  = $scope['buyer_id'];
            $bind[]  = $scope['pasabuyer_id'];
        } elseif (isset($scope['user_id'])) {
            $where[] = '(buyer_id = ? OR pasabuyer_id = ?)';
            $bind[]  = $scope['user_id'];
            $bind[]  = $scope['user_id'];
        }

        $where[] = 'COALESCE(completed_at, cancelled_at, updated_at) >= ?';
        $bind[]  = $start->format('Y-m-d 00:00:00');

        $whereSql = $where ? implode(' AND ', $where) : '1=1';

        $sql = "SELECT id, status, agreed_price,
                   completed_at, cancelled_at, updated_at
            FROM transactions
            WHERE $whereSql";

        $stmt = \App\Core\Database::pdo()->prepare($sql);
        $stmt->execute($bind);

        // ----- Pre-fill empty buckets -----
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
                ];
            }
        }

        // ----- Bucket rows -----
        foreach ($stmt->fetchAll() as $r) {
            $status = $r['status'];
            $ts     = $r['completed_at'] ?? $r['cancelled_at'] ?? $r['updated_at'];
            if (!$ts) continue;

            $key = $bucket === 'day'
                ? substr($ts, 0, 10)
                : substr($ts, 0, 7);

            if (!isset($points[$key])) continue;

            if ($status === 'completed') {
                $points[$key]['completed']++;
                $points[$key]['value'] += (float) ($r['agreed_price'] ?? 0);
            } elseif ($status === 'cancelled') {
                $points[$key]['cancelled']++;
            } elseif ($status === 'disputed') {
                $points[$key]['disputed']++;
            }
        }

        // ----- Summary -----
        $summary = $this->summarize($scope);

        return [
            'range'   => $range,
            'label'   => $cfg['label'],
            'bucket'  => $bucket,
            'points'  => array_values($points),
            'summary' => $summary,
        ];
    }

    private function summarize(array $scope): array
    {
        $where = ['1=1'];
        $bind  = [];

        if (isset($scope['buyer_id']) && isset($scope['pasabuyer_id'])) {
            $where[] = '(buyer_id = ? OR pasabuyer_id = ?)';
            $bind[]  = $scope['buyer_id'];
            $bind[]  = $scope['pasabuyer_id'];
        } elseif (isset($scope['user_id'])) {
            $where[] = '(buyer_id = ? OR pasabuyer_id = ?)';
            $bind[]  = $scope['user_id'];
            $bind[]  = $scope['user_id'];
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT
                COUNT(*) AS total,
                SUM(status = 'completed')  AS completed,
                SUM(status = 'cancelled')  AS cancelled,
                SUM(status = 'disputed')   AS disputed,
                SUM(status IN ('pending','in_progress','buyer_done','pasabuyer_done')) AS active,
                COALESCE(SUM(CASE WHEN status='completed' THEN agreed_price END), 0) AS value
            FROM transactions
            WHERE $whereSql";

        $stmt = \App\Core\Database::pdo()->prepare($sql);
        $stmt->execute($bind);
        $r = $stmt->fetch();

        $total     = (int) $r['total'];
        $completed = (int) $r['completed'];
        $cancel    = (int) $r['cancelled'];

        // Average completion time (in hours) — only completed deals
        $avgSql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) AS hrs
               FROM transactions
               WHERE $whereSql AND status = 'completed' AND completed_at IS NOT NULL";
        $avgStmt = \App\Core\Database::pdo()->prepare($avgSql);
        $avgStmt->execute($bind);
        $avgHrs = (float) ($avgStmt->fetch()['hrs'] ?? 0);

        $successRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

        return [
            'total'            => $total,
            'completed'        => $completed,
            'cancelled'        => $cancel,
            'disputed'         => (int) $r['disputed'],
            'active'           => (int) $r['active'],
            'value'            => (float) $r['value'],
            'success_rate'     => $successRate,
            'avg_completion_h' => round($avgHrs, 1),
        ];
    }
}
