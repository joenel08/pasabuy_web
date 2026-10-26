<form method="get" class="flex flex-wrap items-center gap-3 mb-5">
    <div class="relative flex-1 min-w-[240px]">
        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </span>
        <input name="search" value="<?= \Admin\Core\View::e($search) ?>"
               placeholder="Search title or description…"
               class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-slate-200 rounded-lg
                      placeholder:text-slate-400
                      focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
    </div>

    <select name="status"
            class="px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg
                   focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All statuses</option>
        <?php foreach (['open','matched','completed','closed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
                <?= ucfirst($s) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-colors">
        Filter
    </button>

    <?php if ($search !== '' || $status !== 'all'): ?>
        <a href="/pasabuy/posts" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">Reset</a>
    <?php endif; ?>
</form>

<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50/50 border-b border-slate-200">
            <tr>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 w-16"></th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Title</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Type</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Items</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Route</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Owner</th>
                <th class="text-right px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8" class="px-5 py-16 text-center">
                        <p class="text-sm font-medium text-slate-900">No posts found</p>
                        <p class="text-xs text-slate-500 mt-1">Try adjusting your filters</p>
                    </td>
                </tr>
            <?php else: foreach ($items as $p):
                $sc = [
                    'open'      => 'bg-brand-50 text-brand-700',
                    'matched'   => 'bg-blue-50 text-blue-700',
                    'completed' => 'bg-slate-100 text-slate-600',
                    'closed'    => 'bg-slate-100 text-slate-600',
                    'cancelled' => 'bg-red-50 text-red-700',
                ][$p['status']] ?? 'bg-slate-100 text-slate-600';

                $itemCount = (int) $p['item_count'];
                $openCount = (int) $p['open_item_count'];
                $fullCount = (int) $p['full_item_count'];
                $imgCount  = (int) $p['image_count'];
            ?>
                <tr class="hover:bg-slate-50/70 cursor-pointer"
                    onclick="window.location='/pasabuy/posts/<?= (int) $p['id'] ?>'">
                    <td class="px-5 py-3">
                        <?php if (!empty($p['thumbnail'])): ?>
                            <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden">
                                <img src="<?= \Admin\Core\View::e($base . '/' . ltrim($p['thumbnail'], '/')) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.parentNode.innerHTML='<svg width=\'20\' height=\'20\' class=\'m-auto text-slate-400 mt-2.5\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' viewBox=\'0 0 24 24\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\'/><circle cx=\'9\' cy=\'9\' r=\'2\'/><path d=\'m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21\'/></svg>';">
                            </div>
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="9" cy="9" r="2"/>
                                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3">
                        <p class="font-medium text-slate-900 max-w-[240px] truncate">
                            <?= \Admin\Core\View::e($p['title']) ?>
                        </p>
                        <p class="text-xs text-slate-500 max-w-[240px] truncate mt-0.5">
                            <?= \Admin\Core\View::e($p['description']) ?>
                        </p>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium
                            <?= $p['type'] === 'offer' ? 'bg-brand-50 text-brand-700' : 'bg-indigo-50 text-indigo-700' ?>">
                            <?= \Admin\Core\View::e($p['type']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-semibold text-slate-900"><?= $itemCount ?></span>
                            <span class="text-[11px] text-slate-400">item<?= $itemCount === 1 ? '' : 's' ?></span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            <?php if ($fullCount > 0): ?>
                                <span class="text-red-600 font-medium"><?= $fullCount ?> full</span>
                                <?php if ($openCount > 0): ?><span class="text-slate-400">·</span><?php endif; ?>
                            <?php endif; ?>
                            <?php if ($openCount > 0): ?>
                                <span class="text-brand-600 font-medium"><?= $openCount ?> open</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($imgCount > 0): ?>
                            <div class="flex items-center gap-1 text-[10px] text-slate-400 mt-1">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                <?= $imgCount ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-600">
                        <?= \Admin\Core\View::e($p['origin_location'] ?? '?') ?>
                        <span class="text-slate-400 mx-1">→</span>
                        <?= \Admin\Core\View::e($p['destination_location'] ?? '?') ?>
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium <?= $sc ?>">
                            <?= \Admin\Core\View::e($p['status']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-xs text-slate-500 max-w-[160px] truncate">
                        <?= \Admin\Core\View::e($p['owner_email']) ?>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <a href="/pasabuy/posts/<?= (int) $p['id'] ?>"
                           class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            View
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>