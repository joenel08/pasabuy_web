<div class="inline-flex items-center gap-1 p-1 bg-white ring-1 ring-slate-200 rounded-lg mb-5">
    <?php foreach (['7d'=>'7D','30d'=>'30D','90d'=>'90D','12m'=>'12M'] as $k=>$lbl): ?>
        <a href="?range=<?= $k ?>"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors
               <?= $range === $k ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
            <?= $lbl ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50/50 border-b border-slate-200">
            <tr>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">#</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Item</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Deals</th>
                <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">GMV</th>
                <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Admin fee</th>
                <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Net to sellers</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($items)): ?>
                <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">No completed deals in this range.</td></tr>
            <?php else: foreach ($items as $i => $it): ?>
                <tr class="hover:bg-slate-50/70">
                    <td class="px-5 py-3 text-slate-400 font-mono text-xs"><?= $i + 1 ?></td>
                    <td class="px-5 py-3">
                        <p class="font-medium text-slate-900 max-w-[280px] truncate">
                            <?= \Admin\Core\View::e($it['item_name']) ?>
                        </p>
                        <a href="/pasabuy/posts/<?= (int) $it['post_id'] ?>"
                           class="text-[11px] text-brand-600 hover:text-brand-700">
                            View post #<?= (int) $it['post_id'] ?> →
                        </a>
                    </td>
                    <td class="px-5 py-3 text-slate-700 font-semibold"><?= (int) $it['deal_count'] ?></td>
                    <td class="px-5 py-3 text-right text-slate-900 font-semibold">
                        ₱<?= number_format((float) $it['gmv'], 0) ?>
                    </td>
                    <td class="px-5 py-3 text-right text-amber-700 font-medium">
                        ₱<?= number_format((float) $it['total_fee'], 2) ?>
                    </td>
                    <td class="px-5 py-3 text-right text-brand-600 font-medium">
                        ₱<?= number_format((float) $it['total_net'], 2) ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>