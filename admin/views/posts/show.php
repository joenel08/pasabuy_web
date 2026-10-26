<?php
$ownerName = trim(($post['firstname'] ?? '') . ' ' . ($post['lastname'] ?? ''));
$ownerName = $ownerName ?: $post['owner_email'];

$statusColor = [
    'open'      => 'bg-brand-50 text-brand-700 ring-brand-200',
    'matched'   => 'bg-blue-50 text-blue-700 ring-blue-200',
    'completed' => 'bg-slate-100 text-slate-600 ring-slate-200',
    'closed'    => 'bg-slate-100 text-slate-600 ring-slate-200',
    'cancelled' => 'bg-red-50 text-red-700 ring-red-200',
][$post['status']] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
?>

<div class="mb-5">
    <a href="/pasabuy/posts"
       class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 transition-colors">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"/>
        </svg>
        Back to posts
    </a>
</div>

<!-- Header card -->
<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6 mb-5">
    <div class="flex items-start gap-5">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 mb-2">
                <span class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-semibold
                    <?= $post['type'] === 'offer' ? 'bg-brand-50 text-brand-700' : 'bg-indigo-50 text-indigo-700' ?>">
                    <?= strtoupper($post['type']) ?>
                </span>
                <span class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-semibold ring-1 <?= $statusColor ?>">
                    <?= strtoupper($post['status']) ?>
                </span>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900 mb-1">
                <?= \Admin\Core\View::e($post['title']) ?>
            </h2>
            <p class="text-sm text-slate-600 mb-4"><?= \Admin\Core\View::e($post['description']) ?></p>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-slate-500">
                <?php if (!empty($post['origin_location']) || !empty($post['destination_location'])): ?>
                    <div class="flex items-center gap-1.5">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?= \Admin\Core\View::e($post['origin_location'] ?? '—') ?>
                        <span class="text-slate-300">→</span>
                        <?= \Admin\Core\View::e($post['destination_location'] ?? '—') ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($post['travel_date'])): ?>
                    <div class="flex items-center gap-1.5">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        <?= \Admin\Core\View::e($post['travel_date']) ?>
                    </div>
                <?php endif; ?>
                <div class="flex items-center gap-1.5">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <a href="/pasabuy/users/<?= (int) $post['user_id'] ?>"
                       class="hover:text-slate-900 transition-colors">
                        <?= \Admin\Core\View::e($ownerName) ?>
                    </a>
                </div>
                <div class="flex items-center gap-1.5 text-slate-400">
                    Posted <?= \Admin\Core\View::e($post['created_at']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Items section -->
<div class="flex items-center justify-between mb-3">
    <h3 class="text-sm font-semibold text-slate-900">
        Items <span class="text-slate-400 font-normal">(<?= count($items) ?>)</span>
    </h3>
</div>

<?php if (empty($items)): ?>
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-12 text-center">
        <p class="text-sm text-slate-500">This post has no items.</p>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($items as $idx => $it):
            $slots      = $it['slots'];
            $slotsTaken = (int) $it['slots_taken'];
            $isFull     = $slots !== null && $slotsTaken >= (int) $slots;
            $slotsLeft  = $slots !== null ? ((int) $slots - $slotsTaken) : null;
            $convCount  = $convByItem[(int) $it['id']] ?? 0;
            $images     = $imagesByItem[(int) $it['id']] ?? [];
        ?>
            <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
                <div class="flex items-stretch">

                    <!-- Image panel -->
                    <div class="w-40 shrink-0 bg-slate-50 flex items-center justify-center border-r border-slate-100 relative group">
                        <?php if (!empty($images)): ?>
                            <img src="<?= \Admin\Core\View::e($images[0]) ?>"
                                 class="w-full h-full object-cover min-h-[140px]"
                                 onerror="this.replaceWith(Object.assign(document.createElement('div'),{innerHTML:'<div style=\'padding:2rem;color:#94a3b8\'>Image failed</div>'}))">
                            <?php if (count($images) > 1): ?>
                                <div class="absolute bottom-2 right-2 bg-black/70 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded">
                                    +<?= count($images) - 1 ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-slate-400 p-6 text-center">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.5" class="mx-auto mb-1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                <span class="text-[10px]">No photos</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content panel -->
                    <div class="flex-1 p-5 min-w-0">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                        Item <?= $idx + 1 ?>
                                    </span>
                                    <?php if ($slots !== null): ?>
                                        <?php if ($isFull): ?>
                                            <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-50 text-red-700">
                                                FULL
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold bg-brand-50 text-brand-700">
                                                <?= $slotsLeft ?> left
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600">
                                            Unlimited
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="text-base font-bold text-slate-900">
                                    <?= \Admin\Core\View::e($it['title']) ?>
                                </h4>
                            </div>
                        </div>
                        <p class="text-sm text-slate-600 mb-3"><?= \Admin\Core\View::e($it['description']) ?></p>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                            <?php if ($slots !== null): ?>
                                <div class="flex items-center gap-1.5">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    <span class="font-semibold text-slate-700"><?= $slotsTaken ?></span>
                                    <span>/ <?= (int) $slots ?> taken</span>
                                </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-1.5">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <span class="font-semibold text-slate-700"><?= $convCount ?></span>
                                <span>conversation<?= $convCount === 1 ? '' : 's' ?></span>
                            </div>
                            <?php if (count($images) > 1): ?>
                                <div class="flex items-center gap-1.5">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    <?= count($images) ?> photos
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Additional images (small thumbnails) -->
                        <?php if (count($images) > 1): ?>
                            <div class="flex gap-2 mt-3 overflow-x-auto pb-1">
                                <?php foreach (array_slice($images, 1) as $imgUrl): ?>
                                    <a href="<?= \Admin\Core\View::e($imgUrl) ?>" target="_blank"
                                       class="w-12 h-12 rounded-lg overflow-hidden border border-slate-200 shrink-0 hover:border-brand-400 transition-colors">
                                        <img src="<?= \Admin\Core\View::e($imgUrl) ?>"
                                             class="w-full h-full object-cover">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>