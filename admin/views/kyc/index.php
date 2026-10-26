<div class="inline-flex items-center gap-1 p-1 bg-white ring-1 ring-slate-200 rounded-lg mb-5">
    <?php foreach (['pending','accepted','declined','all'] as $s): ?>
        <a href="/pasabuy/kyc?status=<?= $s ?>"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors
               <?= $status === $s
                   ? 'bg-slate-900 text-white'
                   : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
            <?= ucfirst($s) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50/50">
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">User</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">ID Type</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">ID Number</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Submitted</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="text-right px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="px-5 py-16 text-center">
                        <div class="inline-flex w-12 h-12 rounded-full bg-brand-50 text-brand-600 items-center justify-center mb-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-900">All caught up</p>
                        <p class="text-xs text-slate-500 mt-1">No submissions in this category</p>
                    </td>
                </tr>
            <?php else: foreach ($items as $it):
                $name = trim(($it['firstname'] ?? '') . ' ' . ($it['lastname'] ?? ''));
                $name = $name !== '' ? $name : $it['email'];
                $stColors = ['accepted' => 'bg-brand-50 text-brand-700', 'declined' => 'bg-red-50 text-red-700', 'pending' => 'bg-amber-50 text-amber-700'];
                $dotColors = ['accepted' => 'bg-brand-500', 'declined' => 'bg-red-500', 'pending' => 'bg-amber-500'];
                $s = $it['status'];
            ?>
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center
                                        text-slate-600 text-xs font-semibold shrink-0">
                                <?= strtoupper(substr($name, 0, 1)) ?>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 truncate"><?= \Admin\Core\View::e($name) ?></p>
                                <p class="text-xs text-slate-500 truncate"><?= \Admin\Core\View::e($it['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-slate-700"><?= \Admin\Core\View::e($it['id_type']) ?></td>
                    <td class="px-5 py-4 text-slate-700 font-mono text-xs"><?= \Admin\Core\View::e($it['id_number']) ?></td>
                    <td class="px-5 py-4 text-slate-500 text-xs"><?= \Admin\Core\View::e($it['created_at']) ?></td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium <?= $stColors[$s] ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?= $dotColors[$s] ?>"></span>
                            <?= \Admin\Core\View::e($s) ?>
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <a href="/pasabuy/users/<?= (int) $it['user_id'] ?>"
                           class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            Review
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