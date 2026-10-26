<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php
$display = trim(($profile['firstname'] ?? '') . ' ' . ($profile['lastname'] ?? ''));
if ($display === '') $display = $user['email'];

$points = $trend['points'];
$labels = array_map(fn($p) => $p['label'], $points);
$completed = array_map(fn($p) => $p['completed'], $points);
$cancelled = array_map(fn($p) => $p['cancelled'], $points);
$disputed  = array_map(fn($p) => $p['disputed'],  $points);
$values    = array_map(fn($p) => $p['value'],     $points);
$summary   = $trend['summary'];
?>

<!-- Header card -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 mb-5">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700
                    flex items-center justify-center text-white text-lg font-semibold">
            <?= strtoupper(substr($display, 0, 1)) ?>
        </div>
        <div class="flex-1 min-w-0">
            <h2 class="text-lg font-semibold tracking-tight text-slate-900 truncate">
                <?= \Admin\Core\View::e($display) ?>
            </h2>
            <p class="text-sm text-slate-500 truncate"><?= \Admin\Core\View::e($user['email']) ?></p>
        </div>
        <a href="/pasabuy/users/<?= (int) $user['id'] ?>"
           class="text-xs font-medium text-slate-600 hover:text-slate-900">
            View full profile →
        </a>
    </div>
</div>

<!-- Range picker -->
<div class="inline-flex items-center gap-1 p-1 bg-white ring-1 ring-slate-200 rounded-lg mb-5">
    <?php foreach (['7d'=>'7D','30d'=>'30D','90d'=>'90D','12m'=>'12M'] as $k=>$lbl): ?>
        <a href="?range=<?= $k ?>"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors
               <?= $range === $k
                   ? 'bg-slate-900 text-white'
                   : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
            <?= $lbl ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- KPI row -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <?php
    $kpis = [
        ['Total deals',      (int) $summary['total'],                         'across all time', 'slate'],
        ['Success rate',     number_format($summary['success_rate'],1).'%',   $summary['completed'].' completed', 'emerald'],
        ['Transacted value', '₱'.number_format($summary['value'],0),          'completed only', 'brand'],
        ['Disputes',         (int) $summary['disputed'],                      'under review', 'red'],
    ];
    $accents = [
        'slate'   => 'text-slate-700',
        'emerald' => 'text-brand-600',
        'brand'   => 'text-brand-700',
        'red'     => 'text-red-600',
    ];
    foreach ($kpis as [$l,$v,$s,$c]): ?>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-5 shadow-soft">
            <p class="text-xs font-medium text-slate-500 mb-1"><?= $l ?></p>
            <p class="text-2xl font-semibold tracking-tight <?= $accents[$c] ?>"><?= $v ?></p>
            <p class="text-xs text-slate-400 mt-1"><?= $s ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Chart -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 mb-5">
    <h3 class="text-sm font-semibold text-slate-900 mb-1">Deals trend</h3>
    <p class="text-xs text-slate-500 mb-4"><?= \Admin\Core\View::e($trend['label']) ?></p>
    <div style="height: 260px;"><canvas id="userTrend"></canvas></div>
</div>

<!-- Recent transactions -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="text-sm font-semibold text-slate-900">Recent deals</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50/50">
            <tr>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Item</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Role</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Value</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($recent)): ?>
                <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400 text-sm">No deals yet.</td></tr>
            <?php else: foreach ($recent as $t):
                $role = (int) $t['buyer_id'] === (int) $user['id'] ? 'buyer' : 'pasabuyer';
                $statusColors = [
                    'pending'       => 'bg-amber-50 text-amber-700',
                    'in_progress'   => 'bg-blue-50 text-blue-700',
                    'buyer_done'    => 'bg-blue-50 text-blue-700',
                    'pasabuyer_done'=> 'bg-blue-50 text-blue-700',
                    'completed'     => 'bg-brand-50 text-brand-700',
                    'disputed'      => 'bg-red-50 text-red-700',
                    'cancelled'     => 'bg-slate-100 text-slate-600',
                ];
            ?>
                <tr class="hover:bg-slate-50/70">
                    <td class="px-5 py-3">
                        <p class="font-medium text-slate-900"><?= \Admin\Core\View::e($t['item_description']) ?></p>
                        <p class="text-xs text-slate-500 truncate"><?= \Admin\Core\View::e($t['post_title'] ?? '—') ?></p>
                    </td>
                    <td class="px-5 py-3 text-slate-600 text-xs">
                        <span class="inline-flex px-2 py-0.5 rounded-md <?= $role === 'buyer' ? 'bg-slate-100 text-slate-700' : 'bg-indigo-50 text-indigo-700' ?>">
                            <?= $role ?>
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium <?= $statusColors[$t['status']] ?? 'bg-slate-100 text-slate-600' ?>">
                            <?= \Admin\Core\View::e($t['status']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right text-slate-900">
                        <?= $t['agreed_price'] !== null ? '₱'.number_format((float) $t['agreed_price'],0) : '—' ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script>
(function() {
    const labels    = <?= json_encode($labels) ?>;
    const completed = <?= json_encode($completed) ?>;
    const cancelled = <?= json_encode($cancelled) ?>;
    const disputed  = <?= json_encode($disputed) ?>;
    const values    = <?= json_encode($values) ?>;

    new Chart(document.getElementById('userTrend'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Completed', data: completed, borderColor: '#10b981',
                  backgroundColor: 'rgba(16,185,129,.08)', fill: true, tension: .3, borderWidth: 2.5 },
                { label: 'Cancelled', data: cancelled, borderColor: '#94a3b8',
                  tension: .3, borderWidth: 2, backgroundColor: 'transparent' },
                { label: 'Disputed',  data: disputed,  borderColor: '#ef4444',
                  tension: .3, borderWidth: 2, backgroundColor: 'transparent' },
                { label: 'Value (₱)', data: values, yAxisID: 'y1', borderColor: '#0f766e',
                  borderDash: [4,4], tension: .3, borderWidth: 2, backgroundColor: 'transparent' },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
                y1: {
                    position: 'right', beginAtZero: true,
                    grid: { display: false },
                    ticks: { font: { size: 10 }, callback: (v) => '₱' + Number(v).toLocaleString() }
                }
            }
        }
    });
})();
</script>