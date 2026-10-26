<!-- Totals strip -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    <?php
    $cards = [
        ['label'=>'Gross volume', 'value'=>'₱'.number_format((float)$totals['gross'],0), 'sub'=>$totals['completed'].' completed deals'],
        ['label'=>'Admin fees',   'value'=>'₱'.number_format((float)$totals['fees'],0),  'sub'=>'2% of gross'],
        ['label'=>'Net to sellers','value'=>'₱'.number_format((float)$totals['gross'] - (float)$totals['fees'],0), 'sub'=>'paid to pasabuyers'],
    ];
    foreach ($cards as $c): ?>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-5 shadow-soft">
            <p class="text-xs font-medium text-slate-500 mb-1"><?= $c['label'] ?></p>
            <p class="text-2xl font-semibold tracking-tight text-slate-900"><?= $c['value'] ?></p>
            <p class="text-xs text-slate-400 mt-1"><?= $c['sub'] ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Filter tabs -->
<div class="inline-flex items-center gap-1 p-1 bg-white ring-1 ring-slate-200 rounded-lg mb-5">
    <?php foreach (['all','pending','in_progress','completed','disputed','cancelled'] as $s): ?>
        <a href="/pasabuy/transactions?status=<?= $s ?>"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors
               <?= $status === $s
                   ? 'bg-slate-900 text-white'
                   : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
            <?= ucfirst(str_replace('_',' ', $s)) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50/50 border-b border-slate-200">
            <tr>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Item</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Buyer</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Pasabuyer</th>
                <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Agreed</th>
                <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Fee</th>
                <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Net</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Updated</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($items)): ?>
                <tr><td colspan="8" class="px-5 py-16 text-center text-slate-400 text-sm">No transactions</td></tr>
            <?php else: foreach ($items as $t):
                $sc = [
                    'pending'       => 'bg-amber-50 text-amber-700',
                    'in_progress'   => 'bg-blue-50 text-blue-700',
                    'buyer_done'    => 'bg-blue-50 text-blue-700',
                    'pasabuyer_done'=> 'bg-blue-50 text-blue-700',
                    'completed'     => 'bg-brand-50 text-brand-700',
                    'disputed'      => 'bg-red-50 text-red-700',
                    'cancelled'     => 'bg-slate-100 text-slate-600',
                ][$t['status']] ?? 'bg-slate-100 text-slate-600';
            ?>
                <tr class="hover:bg-slate-50/70">
                    <td class="px-5 py-3 font-medium text-slate-900 max-w-[200px] truncate">
                        <?= \Admin\Core\View::e($t['item_description']) ?>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-500 truncate max-w-[160px]">
                        <?= \Admin\Core\View::e($t['buyer_email']) ?>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-500 truncate max-w-[160px]">
                        <?= \Admin\Core\View::e($t['pasabuyer_email']) ?>
                    </td>
                    <td class="px-5 py-3 text-right text-slate-700">
                        <?= $t['agreed_price'] !== null ? '₱'.number_format((float)$t['agreed_price'],2) : '—' ?>
                    </td>
                    <td class="px-5 py-3 text-right font-medium text-amber-700">
                        <?= $t['admin_fee'] !== null ? '₱'.number_format((float)$t['admin_fee'],2) : '—' ?>
                    </td>
                    <td class="px-5 py-3 text-right font-medium text-brand-600">
                        <?= $t['net_payout'] !== null ? '₱'.number_format((float)$t['net_payout'],2) : '—' ?>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium <?= $sc ?>">
                            <?= \Admin\Core\View::e($t['status']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-500">
                        <?= \Admin\Core\View::e($t['updated_at']) ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>