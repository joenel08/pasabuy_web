<!-- ============ Tabs (moved to top, before summary strip) ============ -->
<div class="inline-flex items-center gap-1 p-1 bg-white ring-1 ring-slate-200 rounded-lg mb-5">
    <?php foreach (['outstanding'=>'Outstanding', 'collected'=>'Collected', 'summary'=>'Summary'] as $key => $label): ?>
        <a href="?tab=<?= $key ?>"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors
               <?= $tab === $key
                   ? 'bg-slate-900 text-white'
                   : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- ============ Top summary strip (always visible) ============ -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
    <?php
    $cards = [
        ['label'=>'Today',        'value'=>'₱'.number_format($stats['today'], 0),        'color'=>'text-brand-600'],
        ['label'=>'This week',    'value'=>'₱'.number_format($stats['this_week'], 0),    'color'=>'text-brand-600'],
        ['label'=>'This month',   'value'=>'₱'.number_format($stats['this_month'], 0),   'color'=>'text-brand-600'],
        ['label'=>'Lifetime',     'value'=>'₱'.number_format($stats['lifetime'], 0),     'color'=>'text-slate-900'],
        ['label'=>'Outstanding',  'value'=>'₱'.number_format($stats['outstanding'], 0),  'color'=>'text-amber-600'],
        ['label'=>'Users in debt','value'=>(string) $stats['unpaid_users'],              'color'=>'text-red-600'],
    ];
    foreach ($cards as $c): ?>
        <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4 shadow-soft">
            <p class="text-[11px] font-medium text-slate-500"><?= $c['label'] ?></p>
            <p class="text-xl font-semibold tracking-tight <?= $c['color'] ?> mt-1">
                <?= $c['value'] ?>
            </p>
        </div>
    <?php endforeach; ?>
</div>

<!-- ============ TAB: OUTSTANDING ============ -->
<?php if ($tab === 'outstanding'): ?>

    <!-- Block rules banner (contextual to this tab only) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-5 shadow-soft">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 mb-1">Auto-block threshold</p>
                    <p class="text-2xl font-semibold tracking-tight text-amber-600">
                        ₱<?= number_format($block_threshold, 0) ?>
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Posting disabled at or above this amount</p>
                </div>
                <span class="inline-flex w-9 h-9 rounded-xl bg-amber-50 text-amber-600 items-center justify-center shrink-0">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </span>
            </div>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-5 shadow-soft">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 mb-1">Age limit</p>
                    <p class="text-2xl font-semibold tracking-tight text-red-600">
                        <?= (int) $block_days ?> days
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Oldest unpaid fee triggers block</p>
                </div>
                <span class="inline-flex w-9 h-9 rounded-xl bg-red-50 text-red-600 items-center justify-center shrink-0">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50/50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">User</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Unpaid</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Fees</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Oldest</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                    <th class="text-right px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="px-5 py-16 text-center">
                        <div class="inline-flex w-12 h-12 rounded-full bg-brand-50 text-brand-600 items-center justify-center mb-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-900">All fees collected</p>
                        <p class="text-xs text-slate-500 mt-1">No outstanding balance from any user</p>
                    </td></tr>
                <?php else: foreach ($rows as $r):
                    $daysOld = $r['oldest_unpaid_at']
                        ? (int) floor((time() - strtotime($r['oldest_unpaid_at'])) / 86400)
                        : 0;
                    $isBlocked = (float) $r['unpaid_total'] >= $block_threshold || $daysOld >= $block_days;
                    $name = trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? '')) ?: $r['email'];
                ?>
                    <tr class="hover:bg-slate-50/70 cursor-pointer"
                        onclick="window.location='/pasabuy/fees/<?= (int) $r['user_id'] ?>'">
                        <td class="px-5 py-3">
                            <p class="font-medium text-slate-900 truncate max-w-[260px]"><?= \Admin\Core\View::e($name) ?></p>
                            <p class="text-xs text-slate-500 truncate max-w-[260px]"><?= \Admin\Core\View::e($r['email']) ?></p>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <span class="text-base font-semibold text-slate-900">
                                ₱<?= number_format((float) $r['unpaid_total'], 2) ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-slate-600"><?= (int) $r['unpaid_count'] ?></td>
                        <td class="px-5 py-3">
                            <p class="text-xs text-slate-500"><?= \Admin\Core\View::e($r['oldest_unpaid_at'] ?? '—') ?></p>
                            <p class="text-[11px] <?= $daysOld >= $block_days ? 'text-red-600 font-semibold' : 'text-slate-400' ?>">
                                <?= $daysOld ?> days old
                            </p>
                        </td>
                        <td class="px-5 py-3">
                            <?php if ($isBlocked): ?>
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium bg-red-50 text-red-700">BLOCKED</span>
                            <?php elseif ((float) $r['unpaid_total'] >= 200): ?>
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium bg-amber-50 text-amber-700">WARNED</span>
                            <?php else: ?>
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-600">NORMAL</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="/pasabuy/fees/<?= (int) $r['user_id'] ?>"
                               class="text-xs font-medium text-brand-600 hover:text-brand-700">
                                Manage →
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- ============ TAB: COLLECTED ============ -->
<?php if ($tab === 'collected'): ?>
    <form method="get" class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-4 mb-5">
        <input type="hidden" name="tab" value="collected">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">From</label>
                <input type="date" name="from" value="<?= \Admin\Core\View::e($from) ?>"
                       class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
            </div>
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">To</label>
                <input type="date" name="to" value="<?= \Admin\Core\View::e($to) ?>"
                       class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
            </div>
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Search user</label>
                <input type="text" name="search" value="<?= \Admin\Core\View::e($search) ?>"
                       placeholder="Email, name…"
                       class="w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg placeholder:text-slate-400 focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg">
                    Filter
                </button>
                <a href="/pasabuy/fees?tab=collected"
                   class="px-3 py-2 text-sm text-slate-500 hover:text-slate-900">Reset</a>
            </div>
        </div>
    </form>

    <div class="bg-gradient-to-r from-brand-50 to-brand-100/50 rounded-2xl ring-1 ring-brand-200 p-4 mb-5 flex items-center justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-brand-700 mb-1">Filtered total</p>
            <p class="text-2xl font-bold text-brand-800">₱<?= number_format($filtered_total, 2) ?></p>
            <p class="text-xs text-brand-700/70 mt-0.5">
                <?= (int) $filtered_count ?> fee<?= $filtered_count === 1 ? '' : 's' ?>
                · <?= \Admin\Core\View::e($from) ?> to <?= \Admin\Core\View::e($to) ?>
            </p>
        </div>
        <a href="/pasabuy/fees/export.csv?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&search=<?= urlencode($search) ?>"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-brand-50 text-brand-700 text-sm font-medium rounded-lg ring-1 ring-brand-300">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </a>
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50/50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Paid at</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Deal</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Pasabuyer</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Agreed</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Fee</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Confirmed by</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($collected)): ?>
                    <tr><td colspan="7" class="px-5 py-16 text-center">
                        <p class="text-sm font-medium text-slate-900">No fees collected in this range</p>
                        <p class="text-xs text-slate-500 mt-1">Try widening the date range or clearing the search</p>
                    </td></tr>
                <?php else: foreach ($collected as $c):
                    $name = trim(($c['firstname'] ?? '') . ' ' . ($c['lastname'] ?? '')) ?: $c['user_email'];
                ?>
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-3 text-xs text-slate-600 whitespace-nowrap">
                            <?= \Admin\Core\View::e($c['fee_paid_at']) ?>
                        </td>
                        <td class="px-5 py-3">
                            <p class="text-xs font-mono text-slate-500">#<?= (int) $c['id'] ?></p>
                            <p class="text-slate-900 font-medium truncate max-w-[220px]">
                                <?= \Admin\Core\View::e($c['item_title'] ?: $c['item_description']) ?>
                            </p>
                        </td>
                        <td class="px-5 py-3">
                            <!-- Plain text, not a fake link -->
                            <p class="text-sm text-slate-900 truncate max-w-[200px]"><?= \Admin\Core\View::e($name) ?></p>
                            <p class="text-[11px] text-slate-500 truncate max-w-[200px]"><?= \Admin\Core\View::e($c['user_email']) ?></p>
                        </td>
                        <td class="px-5 py-3 text-right text-slate-600">
                            ₱<?= number_format((float) $c['agreed_price'], 2) ?>
                        </td>
                        <td class="px-5 py-3 text-right font-semibold text-brand-600">
                            +₱<?= number_format((float) $c['admin_fee'], 2) ?>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500 truncate max-w-[160px]">
                            <?= \Admin\Core\View::e($c['paid_by_email'] ?? '—') ?>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500 truncate max-w-[180px]">
                            <?= \Admin\Core\View::e($c['fee_payment_notes'] ?? '—') ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- ============ TAB: SUMMARY ============ -->
<?php if ($tab === 'summary'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
        <div class="lg:col-span-2 bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-1">Fees collected · Last 12 months</h3>
            <p class="text-xs text-slate-500 mb-4">Each bar is the total admin fee collected that month</p>
            <div style="height: 280px;">
                <canvas id="feesChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 flex flex-col">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">Lifetime totals</h3>
            <div class="flex-1 space-y-4">
                <div>
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 mb-1">Total collected</p>
                    <p class="text-3xl font-bold text-brand-600">₱<?= number_format($stats['lifetime'], 0) ?></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 mb-1">Fees confirmed</p>
                    <p class="text-2xl font-semibold text-slate-900"><?= (int) $stats['lifetime_count'] ?></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 mb-1">Avg fee</p>
                    <p class="text-xl font-semibold text-slate-700">
                        ₱<?= $stats['lifetime_count'] > 0 ? number_format($stats['lifetime'] / $stats['lifetime_count'], 2) : '0.00' ?>
                    </p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 mb-1">Still outstanding</p>
                    <p class="text-xl font-semibold text-amber-600">₱<?= number_format($stats['outstanding'], 0) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-900">Top 10 users by fees paid</h3>
            <p class="text-xs text-slate-500 mt-1">Sorted by lifetime contribution</p>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50/50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">#</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">User</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Fees paid</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total amount</th>
                    <th class="text-right px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($top_payers)): ?>
                    <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No fees paid yet.</td></tr>
                <?php else: foreach ($top_payers as $i => $p):
                    $name = trim(($p['firstname'] ?? '') . ' ' . ($p['lastname'] ?? '')) ?: $p['email'];
                ?>
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-md
                                <?= $i < 3 ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600' ?>
                                text-[11px] font-bold">
                                <?= $i + 1 ?>
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <p class="text-sm font-medium text-slate-900 truncate max-w-[260px]"><?= \Admin\Core\View::e($name) ?></p>
                            <p class="text-[11px] text-slate-500 truncate max-w-[260px]"><?= \Admin\Core\View::e($p['email']) ?></p>
                        </td>
                        <td class="px-5 py-3 text-right text-slate-600"><?= (int) $p['fee_count'] ?></td>
                        <td class="px-5 py-3 text-right font-semibold text-brand-600">
                            ₱<?= number_format((float) $p['total_paid'], 2) ?>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="/pasabuy/fees/<?= (int) $p['id'] ?>"
                               class="text-xs font-medium text-slate-600 hover:text-slate-900">
                                View →
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    (function() {
        const labels = <?= json_encode(array_map(fn($m) => $m['label'], $monthly)) ?>;
        const totals = <?= json_encode(array_map(fn($m) => $m['total'], $monthly)) ?>;

        new Chart(document.getElementById('feesChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Fees collected (₱)',
                    data: totals,
                    backgroundColor: 'rgba(5, 150, 105, 0.85)',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => '₱' + Number(ctx.parsed.y).toLocaleString() } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 10 }, callback: (v) => '₱' + Number(v).toLocaleString() }
                    }
                }
            }
        });
    })();
    </script>
<?php endif; ?>