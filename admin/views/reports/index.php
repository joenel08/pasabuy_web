<?php
$typeLabels = [
    'user'        => 'User',
    'post'        => 'Post',
    'message'     => 'Message',
    'transaction' => 'Transaction',
    'all'         => 'All types',
];
?>

<!-- Status filter pills -->
<div class="inline-flex items-center gap-1 p-1 bg-white ring-1 ring-slate-200 rounded-lg mb-3">
    <?php foreach (['pending','reviewing','resolved','dismissed','all'] as $s): ?>
        <a href="/pasabuy/reports?status=<?= $s ?>&type=<?= \Admin\Core\View::e($type) ?>"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors
               <?= $status === $s
                   ? 'bg-slate-900 text-white'
                   : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
            <?= ucfirst($s) ?>
            <span class="ml-1.5 text-[10px] font-bold
                <?= $status === $s ? 'text-white/70' : 'text-slate-400' ?>">
                <?= (int) ($counts[$s] ?? 0) ?>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Type filter pills -->
<div class="inline-flex items-center gap-1 p-1 bg-white ring-1 ring-slate-200 rounded-lg mb-5 ml-2">
    <?php foreach (['all','user','post','message','transaction'] as $t): ?>
        <a href="/pasabuy/reports?status=<?= \Admin\Core\View::e($status) ?>&type=<?= $t ?>"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors
               <?= $type === $t
                   ? 'bg-slate-900 text-white'
                   : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
            <?= $typeLabels[$t] ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Reports table -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50/50 border-b border-slate-200">
            <tr>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Target</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Reason</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Reporter</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Submitted</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="px-5 py-16 text-center">
                        <p class="text-sm font-medium text-slate-900">All clear</p>
                        <p class="text-xs text-slate-500 mt-1">No reports in this category</p>
                    </td>
                </tr>
            <?php else: foreach ($items as $r):
                $sc = [
                    'pending'   => 'bg-amber-50 text-amber-700',
                    'reviewing' => 'bg-blue-50 text-blue-700',
                    'resolved'  => 'bg-brand-50 text-brand-700',
                    'dismissed' => 'bg-slate-100 text-slate-600',
                ][$r['status']] ?? 'bg-slate-100 text-slate-600';

                $typeColor = [
                    'user'        => 'bg-indigo-50 text-indigo-700',
                    'post'        => 'bg-brand-50 text-brand-700',
                    'message'     => 'bg-blue-50 text-blue-700',
                    'transaction' => 'bg-slate-100 text-slate-700',
                ][$r['target_type']] ?? 'bg-slate-100 text-slate-700';
            ?>
                <tr class="hover:bg-slate-50/70">
                    <!-- Target -->
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold uppercase <?= $typeColor ?>">
                                <?= \Admin\Core\View::e($r['target_type']) ?>
                            </span>
                            <?php if ((int) $r['target_report_count'] > 1): ?>
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-50 text-red-700">
                                    <?= (int) $r['target_report_count'] ?> reports
                                </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm font-medium text-slate-900 max-w-[280px] truncate">
                            <?= \Admin\Core\View::e($r['target_label']) ?>
                        </p>
                        <?php if (!empty($r['description'])): ?>
                            <p class="text-xs text-slate-500 mt-1 max-w-[280px] truncate">
                                <?= \Admin\Core\View::e($r['description']) ?>
                            </p>
                        <?php endif; ?>
                    </td>

                    <!-- Reason -->
                    <td class="px-5 py-4">
                        <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700">
                            <?= \Admin\Core\View::e(str_replace('_', ' ', $r['reason'])) ?>
                        </span>
                    </td>

                    <!-- Reporter -->
                    <td class="px-5 py-4 text-xs text-slate-500 max-w-[160px] truncate">
                        <?= \Admin\Core\View::e($r['reporter_email']) ?>
                    </td>

                    <!-- Submitted -->
                    <td class="px-5 py-4 text-xs text-slate-500">
                        <?= \Admin\Core\View::e($r['created_at']) ?>
                    </td>

                    <!-- Status -->
                    <td class="px-5 py-4">
                        <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium <?= $sc ?>">
                            <?= \Admin\Core\View::e($r['status']) ?>
                        </span>
                    </td>

                    <!-- Actions -->
                    <td class="px-5 py-4 text-right whitespace-nowrap">
                        <?php if (in_array($r['status'], ['pending', 'reviewing'], true)): ?>

                            <a href="/pasabuy/reports/<?= (int) $r['id'] ?>"
                               class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-slate-900 mr-2">
                                View
                            </a>

                            <?php if (in_array($r['target_type'], ['user','message','post'], true)): ?>
                                <form method="post"
                                      action="/pasabuy/reports/<?= (int) $r['id'] ?>/suspend-user"
                                      data-confirm-title="Suspend the offending user?"
                                      data-confirm-body="The user's account will be set to declined. All pending reports on this target will be resolved."
                                      data-confirm-variant="danger"
                                      data-confirm-label="Suspend user"
                                      class="inline-block mr-1">
                                    <button type="submit"
                                            class="text-xs font-medium text-amber-700 hover:text-amber-800">
                                        Suspend
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array($r['target_type'], ['post','message'], true)): ?>
                                <form method="post"
                                      action="/pasabuy/reports/<?= (int) $r['id'] ?>/delete-target"
                                      data-confirm-title="Delete the reported content?"
                                      data-confirm-body="<?= $r['target_type'] === 'post' ? 'The post will be cancelled and no longer visible. All pending reports on it will be resolved.' : 'The message text will be replaced with [Message removed by admin]. All pending reports on it will be resolved.' ?>"
                                      data-confirm-variant="danger"
                                      data-confirm-label="Delete"
                                      class="inline-block mr-2">
                                    <button type="submit"
                                            class="text-xs font-medium text-red-700 hover:text-red-800">
                                        Delete
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form method="post"
                                  action="/pasabuy/reports/<?= (int) $r['id'] ?>/update"
                                  data-confirm-title="Mark as resolved?"
                                  data-confirm-body="No action will be taken. Use this when the report was reviewed and no violation was found."
                                  data-confirm-variant="success"
                                  data-confirm-label="Resolve"
                                  class="inline-block">
                                <input type="hidden" name="status" value="resolved">
                                <button type="submit"
                                        class="text-xs font-medium text-brand-600 hover:text-brand-700">
                                    Resolve
                                </button>
                            </form>

                        <?php else: ?>
                            <a href="/pasabuy/reports/<?= (int) $r['id'] ?>"
                               class="text-xs font-medium text-slate-500 hover:text-slate-900">
                                View
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>