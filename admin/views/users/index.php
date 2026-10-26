<form method="get" class="flex flex-wrap items-center gap-3 mb-5">
    <div class="relative flex-1 min-w-[240px]">
        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </span>
        <input name="search" value="<?= \Admin\Core\View::e($search) ?>"
               placeholder="Search by email or name…"
               class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-slate-200 rounded-lg
                      placeholder:text-slate-400
                      focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10
                      transition-all">
    </div>

    <select name="status"
            class="px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg
                   focus:outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
        <?php foreach (['all','pending','accepted','declined'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
                <?= $s === 'all' ? 'All statuses' : ucfirst($s) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-colors">
        Filter
    </button>

    <?php if ($search !== '' || $status !== 'all'): ?>
        <a href="/pasabuy/users" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">Reset</a>
    <?php endif; ?>
</form>

<div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50/50">
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">User</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Role</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">KYC</th>
                <th class="text-right px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5" class="px-5 py-16 text-center">
                        <div class="inline-flex w-12 h-12 rounded-full bg-slate-100 items-center justify-center mb-3 text-slate-400">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-900">No users found</p>
                        <p class="text-xs text-slate-500 mt-1">Try adjusting your filters</p>
                    </td>
                </tr>
            <?php else: foreach ($users as $u):
                $name = trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''));
                $name = $name !== '' ? $name : '—';
            ?>
                <tr class="hover:bg-slate-50/70 transition-colors">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center
                                        text-slate-600 text-xs font-semibold shrink-0">
                                <?= strtoupper(substr($name !== '—' ? $name : $u['email'], 0, 1)) ?>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 truncate"><?= \Admin\Core\View::e($name) ?></p>
                                <p class="text-xs text-slate-500 truncate"><?= \Admin\Core\View::e($u['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium
                            <?= $u['role'] === 'pasabuyer'
                                ? 'bg-indigo-50 text-indigo-700'
                                : 'bg-slate-100 text-slate-700' ?>">
                            <?= \Admin\Core\View::e($u['role']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <?php
                        $stColors = [
                            'accepted' => 'bg-brand-50 text-brand-700',
                            'declined' => 'bg-red-50 text-red-700',
                            'pending'  => 'bg-amber-50 text-amber-700',
                        ];
                        $dotColors = [
                            'accepted' => 'bg-brand-500',
                            'declined' => 'bg-red-500',
                            'pending'  => 'bg-amber-500',
                        ];
                        $st = $u['status'];
                        ?>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium <?= $stColors[$st] ?? 'bg-slate-100 text-slate-700' ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?= $dotColors[$st] ?? 'bg-slate-400' ?>"></span>
                            <?= \Admin\Core\View::e($st) ?>
                        </span>
                    </td>
                    <td class="px-5 py-4 text-xs text-slate-600">
                        <?= \Admin\Core\View::e($u['kyc_status'] ?? 'none') ?>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <a href="/pasabuy/users/<?= (int) $u['id'] ?>"
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