<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php
$points = $trend['points'];
$labels = array_map(fn($p) => $p['label'], $points);
$completed = array_map(fn($p) => $p['completed'], $points);
$cancelled = array_map(fn($p) => $p['cancelled'], $points);
$disputed  = array_map(fn($p) => $p['disputed'],  $points);
$values    = array_map(fn($p) => $p['value'],     $points);
?>

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
    <span class="ml-2 pl-3 pr-1 text-xs text-slate-400 border-l border-slate-200">
        <?= \Admin\Core\View::e($trend['label']) ?>
    </span>
</div>

<!-- Top KPI cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
    <?php
  $cards = [
    ['label'=>'Users',            'value'=>number_format((int)$stats['users']),            'sub'=>'registered',           'color'=>'slate'],
    ['label'=>'Total deals',      'value'=>number_format((int)$stats['total_deals']),      'sub'=>((int)$stats['completed_deals']).' completed', 'color'=>'emerald'],
    ['label'=>'Transacted value', 'value'=>'₱'.number_format((float)$stats['total_value'],0), 'sub'=>'completed only',    'color'=>'brand'],
    ['label'=>'Admin fees earned','value'=>'₱'.number_format((float)$stats['total_fees'],0),  'sub'=>'2% of completed',   'color'=>'amber'],  // NEW
    ['label'=>'Disputes',         'value'=>number_format((int)$stats['disputed_deals']),   'sub'=>'needs attention',      'color'=>'red'],
];
   $accents = [
    'slate'   => ['bg-slate-100','text-slate-700'],
    'emerald' => ['bg-brand-50','text-brand-600'],
    'brand'   => ['bg-brand-50','text-brand-700'],
    'amber'   => ['bg-amber-50','text-amber-700'],
    'red'     => ['bg-red-50','text-red-600'],
];
    foreach ($cards as $c): [$bg,$fg] = $accents[$c['color']]; ?>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-5 shadow-soft">
            <p class="text-xs font-medium text-slate-500 mb-1"><?= $c['label'] ?></p>
            <p class="text-2xl font-semibold tracking-tight text-slate-900"><?= $c['value'] ?></p>
            <p class="text-xs text-slate-400 mt-1"><?= $c['sub'] ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Charts row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Deals trend</h3>
        <p class="text-xs text-slate-500 mb-4">Completed vs cancelled vs disputed</p>
        <div style="height: 260px;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-1">Transacted value</h3>
        <p class="text-xs text-slate-500 mb-4">Value of completed deals</p>
        <div style="height: 260px;">
            <canvas id="valueChart"></canvas>
        </div>
    </div>
</div>

<!-- Top performers + disputes -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
    <!-- Top pasabuyers -->
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Top pasabuyers</h3>
        <?php if (empty($topPasabuyers)): ?>
            <p class="text-sm text-slate-400">No completed deals yet.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($topPasabuyers as $u): ?>
                    <li class="py-2.5 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center
                                    text-slate-600 text-xs font-semibold">
                            <?= strtoupper(substr($u['email'], 0, 1)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="/pasabuy/analytics/user/<?= (int) $u['id'] ?>"
                               class="text-sm font-medium text-slate-900 hover:text-brand-600 truncate block">
                                <?= \Admin\Core\View::e($u['email']) ?>
                            </a>
                            <p class="text-xs text-slate-500">
                                <?= (int) $u['completed'] ?> completed · ₱<?= number_format((float) $u['value'], 0) ?>
                            </p>
                        </div>
                        <span class="text-xs font-semibold text-brand-600"><?= (int) $u['completed'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Recent disputes -->
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Open disputes</h3>
        <?php if (empty($disputes)): ?>
            <p class="text-sm text-slate-400">No open disputes. 🎉</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($disputes as $d): ?>
                    <li class="py-2.5">
                        <p class="text-sm font-medium text-slate-900 truncate">
                            <?= \Admin\Core\View::e($d['item_description']) ?>
                        </p>
                        <p class="text-xs text-slate-500">
                            <?= \Admin\Core\View::e($d['disputed_by_email']) ?>
                            · <?= \Admin\Core\View::e($d['updated_at']) ?>
                        </p>
                        <?php if (!empty($d['dispute_reason'])): ?>
                            <p class="text-xs text-red-600 mt-1 line-clamp-2">
                                <?= \Admin\Core\View::e($d['dispute_reason']) ?>
                            </p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <!-- Top items by GMV -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 mt-5">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Top items by GMV</h3>
            <p class="text-xs text-slate-500 mt-1"><?= \Admin\Core\View::e($trend['label'] ?? '') ?></p>
        </div>
        <a href="/pasabuy/analytics/by-item"
           class="text-xs font-medium text-brand-600 hover:text-brand-700">
            View all →
        </a>
    </div>
    <?php if (empty($topItems)): ?>
        <p class="text-sm text-slate-400">No completed deals yet.</p>
    <?php else: ?>
        <table class="w-full text-sm">
            <thead class="text-left text-slate-500 text-[11px] uppercase tracking-wider border-b border-slate-100">
                <tr>
                    <th class="py-2">Item</th>
                    <th class="py-2 text-center">Deals</th>
                    <th class="py-2 text-right">GMV</th>
                    <th class="py-2 text-right">Admin fee</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($topItems as $ti): ?>
                    <tr>
                        <td class="py-3 text-slate-900 truncate max-w-[240px]">
                            <?= \Admin\Core\View::e($ti['item_name']) ?>
                        </td>
                        <td class="py-3 text-center text-slate-700 font-medium"><?= (int) $ti['deal_count'] ?></td>
                        <td class="py-3 text-right text-slate-900 font-semibold">
                            ₱<?= number_format((float) $ti['gmv'], 0) ?>
                        </td>
                        <td class="py-3 text-right text-amber-700">
                            ₱<?= number_format((float) $ti['total_fee'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
    <!-- Top rated users -->
<!-- Top rated users -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 lg:col-span-2">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Top rated users</h3>
            <p class="text-xs text-slate-500 mt-0.5">Highest average star rating (min. 1 review)</p>
        </div>
    </div>

    <?php if (empty($topRated)): ?>
        <p class="text-sm text-slate-400 text-center py-6">No reviews yet.</p>
    <?php else: ?>
        <?php require __DIR__ . '/../partials/stars.php'; ?>
        <div class="overflow-hidden rounded-xl ring-1 ring-slate-100">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 w-10">#</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">User</th>
                        <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Rating</th>
                        <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Reviews</th>
                        <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">5★</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($topRated as $i => $u): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <span class="inline-flex w-6 h-6 items-center justify-center rounded-full
                                    <?= $i === 0 ? 'bg-amber-100 text-amber-700'
                                        : ($i === 1 ? 'bg-slate-200 text-slate-700'
                                        : ($i === 2 ? 'bg-orange-100 text-orange-700'
                                        : 'bg-slate-100 text-slate-500')) ?>
                                    text-[11px] font-bold">
                                    <?= $i + 1 ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="/pasabuy/users/<?= (int) $u['id'] ?>"
                                   class="text-sm font-medium text-slate-900 hover:text-brand-600">
                                    <?= \Admin\Core\View::e($u['email']) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-900">
                                        <?= number_format((float) $u['avg_rating'], 1) ?>
                                    </span>
                                    <?= render_stars((float) $u['avg_rating'], 13) ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">
                                <?= (int) $u['reviews'] ?>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">
                                <?= (int) $u['fives'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>

<script>
(function() {
    const labels    = <?= json_encode($labels) ?>;
    const completed = <?= json_encode($completed) ?>;
    const cancelled = <?= json_encode($cancelled) ?>;
    const disputed  = <?= json_encode($disputed) ?>;
    const values    = <?= json_encode($values) ?>;

    const common = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
            y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } }, beginAtZero: true }
        }
    };

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Completed', data: completed, borderColor: '#10b981',
                  backgroundColor: 'rgba(16,185,129,.08)', fill: true, tension: .3, borderWidth: 2.5 },
                { label: 'Cancelled', data: cancelled, borderColor: '#94a3b8',
                  backgroundColor: 'transparent', tension: .3, borderWidth: 2 },
                { label: 'Disputed',  data: disputed,  borderColor: '#ef4444',
                  backgroundColor: 'transparent', tension: .3, borderWidth: 2 },
            ]
        },
        options: common
    });

    new Chart(document.getElementById('valueChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Value (₱)',
                data: values,
                backgroundColor: 'rgba(5,150,105,.85)',
                borderRadius: 4,
            }]
        },
        options: {
            ...common,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: {
                    label: (ctx) => '₱' + Number(ctx.parsed.y).toLocaleString()
                }}
            },
            scales: {
                ...common.scales,
                y: {
                    ...common.scales.y,
                    ticks: {
                        font: { size: 10 },
                        callback: (v) => '₱' + Number(v).toLocaleString()
                    }
                }
            }
        }
    });
})();
</script>