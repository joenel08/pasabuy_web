<div class="mb-5">
    <a href="/pasabuy/fees"
       class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 transition-colors">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to fees
    </a>
</div>

<!-- User header -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 mb-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">
                <?= \Admin\Core\View::e(trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')) ?: $user['email']) ?>
            </h2>
            <p class="text-sm text-slate-500"><?= \Admin\Core\View::e($user['email']) ?></p>
        </div>
        <div class="text-right">
            <p class="text-xs text-slate-500 mb-1">Total outstanding</p>
            <p class="text-3xl font-bold text-slate-900">
                ₱<?= number_format($total_unpaid, 2) ?>
            </p>
        </div>
    </div>
</div>

<!-- Unpaid fees -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden mb-5">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-900">
            Unpaid fees (<?= count($unpaid) ?>)
        </h3>
        <?php if (!empty($unpaid)): ?>
            <form method="post"
                  action="/pasabuy/fees/<?= (int) $user['id'] ?>/mark-all-paid"
                  data-confirm-title="Mark ALL as paid?"
                  data-confirm-body="All <?= count($unpaid) ?> unpaid fees will be recorded as received."
                  data-confirm-variant="success"
                  data-confirm-label="Mark all paid">
                <input type="hidden" name="notes" value="Bulk cleared">
                <button type="submit"
                        class="text-xs font-medium text-brand-600 hover:text-brand-700">
                    Mark all as paid
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($unpaid)): ?>
        <div class="px-6 py-12 text-center">
            <p class="text-sm text-slate-500">No unpaid fees.</p>
        </div>
    <?php else: ?>
        <form method="post" action="/pasabuy/fees/<?= (int) $user['id'] ?>/mark-paid">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/50 border-b border-slate-200">
                    <tr>
                        <th class="w-10 px-5 py-3">
                            <input type="checkbox" id="selectAll"
                                   onchange="document.querySelectorAll('.fee-check').forEach(c => c.checked = this.checked)">
                        </th>
                        <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Deal</th>
                        <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Item</th>
                        <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Price</th>
                        <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Fee</th>
                        <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Completed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($unpaid as $u): ?>
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-3">
                                <input type="checkbox" class="fee-check rounded"
                                       name="transaction_ids[]" value="<?= (int) $u['id'] ?>">
                            </td>
                            <td class="px-5 py-3 text-slate-700 font-mono text-xs">#<?= (int) $u['id'] ?></td>
                            <td class="px-5 py-3">
                                <p class="text-slate-900 font-medium truncate max-w-[240px]">
                                    <?= \Admin\Core\View::e($u['item_title'] ?: $u['item_description']) ?>
                                </p>
                                <p class="text-xs text-slate-500 truncate max-w-[240px]">
                                    <?= \Admin\Core\View::e($u['post_title'] ?? '') ?>
                                </p>
                            </td>
                            <td class="px-5 py-3 text-right text-slate-600">
                                ₱<?= number_format((float) $u['agreed_price'], 2) ?>
                            </td>
                            <td class="px-5 py-3 text-right text-amber-700 font-semibold">
                                ₱<?= number_format((float) $u['admin_fee'], 2) ?>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                <?= \Admin\Core\View::e($u['completed_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <input name="notes" placeholder="Optional: payment reference / notes"
                       class="px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg w-full max-w-md
                              focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
                <button type="submit"
                        class="ml-3 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg">
                    Mark selected as paid
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Payment history -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="text-sm font-semibold text-slate-900">
            Payment history (<?= count($paid) ?>)
        </h3>
    </div>
    <?php if (empty($paid)): ?>
        <div class="px-6 py-8 text-center text-sm text-slate-400">No payments yet.</div>
    <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-slate-50/50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Deal</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Fee</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Paid at</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Confirmed by</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($paid as $p): ?>
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-3 text-slate-700 font-mono text-xs">#<?= (int) $p['id'] ?></td>
                        <td class="px-5 py-3 text-right text-brand-600 font-semibold">
                            ₱<?= number_format((float) $p['admin_fee'], 2) ?>
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500"><?= \Admin\Core\View::e($p['fee_paid_at']) ?></td>
                        <td class="px-5 py-3 text-xs text-slate-500"><?= \Admin\Core\View::e($p['paid_by_email'] ?? '—') ?></td>
                        <td class="px-5 py-3 text-xs text-slate-500 max-w-[220px] truncate">
                            <?= \Admin\Core\View::e($p['fee_payment_notes'] ?? '—') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>