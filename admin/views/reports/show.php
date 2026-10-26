<?php
$typeBadge = [
    'user'        => 'bg-indigo-50 text-indigo-700',
    'post'        => 'bg-brand-50 text-brand-700',
    'message'     => 'bg-blue-50 text-blue-700',
    'transaction' => 'bg-slate-100 text-slate-600',
][$report['target_type']] ?? 'bg-slate-100 text-slate-600';

$statusBadge = [
    'pending'   => 'bg-amber-50 text-amber-700',
    'reviewing' => 'bg-blue-50 text-blue-700',
    'resolved'  => 'bg-brand-50 text-brand-700',
    'dismissed' => 'bg-slate-100 text-slate-600',
][$report['status']] ?? 'bg-slate-100 text-slate-600';
?>

<div class="mb-5">
    <a href="/pasabuy/reports"
       class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 transition-colors">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to reports
    </a>
</div>

<!-- Report summary -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 mb-5">
    <div class="flex items-center gap-2 mb-3">
        <span class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-semibold <?= $typeBadge ?>">
            <?= strtoupper($report['target_type']) ?>
        </span>
        <span class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-semibold <?= $statusBadge ?>">
            <?= strtoupper($report['status']) ?>
        </span>
        <span class="text-xs text-slate-400 ml-2">Report #<?= (int) $report['id'] ?></span>
    </div>
    <h2 class="text-xl font-bold text-slate-900 mb-1"><?= \Admin\Core\View::e($report['target_label']) ?></h2>
    <p class="text-sm text-slate-600 mb-4"><?= \Admin\Core\View::e(str_replace('_',' ', $report['reason'])) ?></p>
    <?php if (!empty($report['description'])): ?>
        <div class="p-3 bg-slate-50 rounded-lg mb-4 text-sm text-slate-700">
            <?= nl2br(\Admin\Core\View::e($report['description'])) ?>
        </div>
    <?php endif; ?>
    <dl class="grid grid-cols-2 gap-3 text-xs text-slate-500">
        <div><dt class="text-slate-400 mb-0.5">Reporter</dt><dd class="text-slate-900"><?= \Admin\Core\View::e($report['reporter_email']) ?></dd></div>
        <div><dt class="text-slate-400 mb-0.5">Submitted</dt><dd class="text-slate-900"><?= \Admin\Core\View::e($report['created_at']) ?></dd></div>
    </dl>
</div>

<?php if (in_array($report['status'], ['pending','reviewing'], true)): ?>
<!-- Quick actions -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 mb-5">
    <h3 class="text-sm font-semibold text-slate-900 mb-4">Take action</h3>
    <div class="flex flex-wrap gap-2">

        <?php if (in_array($report['target_type'], ['user','message','post'], true)): ?>
            <form method="post"
                  action="/pasabuy/reports/<?= (int) $report['id'] ?>/suspend-user"
                  data-confirm-title="Suspend the offending user?"
                  data-confirm-body="Their account will be declined. All pending reports on this target will be resolved."
                  data-confirm-variant="danger"
                  data-confirm-label="Suspend user">
                <button class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg">
                    Suspend user
                </button>
            </form>
        <?php endif; ?>

        <?php if (in_array($report['target_type'], ['post','message'], true)): ?>
            <form method="post"
                  action="/pasabuy/reports/<?= (int) $report['id'] ?>/delete-target"
                  data-confirm-title="Delete the reported content?"
                  data-confirm-body="This will remove the content and resolve all pending reports on it."
                  data-confirm-variant="danger"
                  data-confirm-label="Delete content">
                <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg">
                    Delete content
                </button>
            </form>
        <?php endif; ?>

        <form method="post" action="/pasabuy/reports/<?= (int) $report['id'] ?>/update">
            <input type="hidden" name="status" value="resolved">
            <button class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-lg">
                Resolve (no action)
            </button>
        </form>

        <form method="post" action="/pasabuy/reports/<?= (int) $report['id'] ?>/update">
            <input type="hidden" name="status" value="dismissed">
            <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg">
                Dismiss
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Existing update form (status + notes) -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 mb-5">
    <h3 class="text-sm font-semibold text-slate-900 mb-4">Update status & notes</h3>
    <form method="post" action="/pasabuy/reports/<?= (int) $report['id'] ?>/update" class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg">
                <?php foreach (['pending','reviewing','resolved','dismissed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $report['status'] === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Admin notes (internal)</label>
            <textarea name="admin_notes" rows="3"
                      class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg"
                      placeholder="What did you find? What action did you take?"><?= \Admin\Core\View::e($report['admin_notes'] ?? '') ?></textarea>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" id="resolve_all" name="resolve_all" value="1" class="rounded">
            <label for="resolve_all" class="text-xs text-slate-600">
                Also resolve all pending reports on this same target
            </label>
        </div>
        <button class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg">
            Save
        </button>
    </form>
</div>

<!-- Target preview -->
<?php if ($target): ?>
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 mb-5">
    <h3 class="text-sm font-semibold text-slate-900 mb-3">Reported content</h3>
    <pre class="text-xs text-slate-600 whitespace-pre-wrap bg-slate-50 p-3 rounded-lg"><?= \Admin\Core\View::e(json_encode($target, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
</div>
<?php endif; ?>

<!-- Other reports on same target -->
<?php if (!empty($otherReports)): ?>
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
    <h3 class="text-sm font-semibold text-slate-900 mb-3">
        Other reports on this target (<?= count($otherReports) ?>)
    </h3>
    <ul class="divide-y divide-slate-100">
        <?php foreach ($otherReports as $o): ?>
            <li class="py-2.5 flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-900"><?= \Admin\Core\View::e(str_replace('_',' ', $o['reason'])) ?></p>
                    <p class="text-xs text-slate-500"><?= \Admin\Core\View::e($o['created_at']) ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-500"><?= \Admin\Core\View::e($o['status']) ?></span>
                    <a href="/pasabuy/reports/<?= (int) $o['id'] ?>"
                       class="text-xs font-medium text-brand-600 hover:text-brand-700">View</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>