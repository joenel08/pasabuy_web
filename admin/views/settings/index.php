<div class="max-w-4xl space-y-6">

    <!-- Change own password -->
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
        <h2 class="text-sm font-semibold tracking-tight text-slate-900 mb-1">Change your password</h2>
        <p class="text-xs text-slate-500 mb-5">Use at least 8 characters. You'll stay signed in after saving.</p>

        <form method="post" action="/pasabuy/settings/password" class="space-y-4 max-w-md">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1.5">Current password</label>
                <input type="password" name="current_password" required
                       class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg
                              focus:outline-none focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1.5">New password</label>
                <input type="password" name="new_password" required minlength="8"
                       class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg
                              focus:outline-none focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1.5">Confirm new password</label>
                <input type="password" name="confirm_password" required minlength="8"
                       class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg
                              focus:outline-none focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-800
                           text-white text-sm font-medium rounded-lg transition-colors">
                Update password
            </button>
        </form>
    </div>

    <!-- Add admin -->
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
        <h2 class="text-sm font-semibold tracking-tight text-slate-900 mb-1">Add new admin</h2>
        <p class="text-xs text-slate-500 mb-5">
            Choose what they can see in the console. Only super admins can manage other admins.
        </p>

        <form method="post" action="/pasabuy/settings/admins"
              data-confirm-title="Create this admin?"
              data-confirm-body="They'll be able to sign in immediately with the password you set."
              data-confirm-variant="success"
              data-confirm-label="Create admin"
              class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-1">
                <label class="block text-xs font-medium text-slate-700 mb-1.5">Email</label>
                <input type="email" name="email" required placeholder="name@example.com"
                       class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg
                              focus:outline-none focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1.5">Password</label>
                <input type="password" name="password" required minlength="8" placeholder="At least 8 characters"
                       class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg
                              focus:outline-none focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-slate-700 mb-1.5">Role</label>
                <select name="admin_role" required
                        class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg
                               focus:outline-none focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10">
                    <option value="">— Choose a role —</option>
                    <option value="super">Super admin (all access)</option>
                    <option value="kyc">KYC reviewer (Dashboard, Users, KYC)</option>
                    <option value="moderator">Moderator (Dashboard, Users, Reports)</option>
                    <option value="analytics">Analyst (Dashboard, Analytics)</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-600 hover:bg-brand-700
                               text-white text-sm font-medium rounded-lg transition-colors">
                    Create admin
                </button>
            </div>
        </form>
    </div>

    <!-- Existing admins -->
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold tracking-tight text-slate-900">All admins</h2>
            <p class="text-xs text-slate-500 mt-0.5"><?= count($admins) ?> total</p>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-slate-50/50">
                <tr>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Email</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Role</th>
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Created</th>
                    <th class="text-right px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($admins as $a):
                    $isMe = (int) $a['id'] === (int) $_SESSION['admin_id'];
                    $roleColors = [
                        'super'     => 'bg-brand-50 text-brand-700',
                        'kyc'       => 'bg-indigo-50 text-indigo-700',
                        'moderator' => 'bg-amber-50 text-amber-700',
                        'analytics' => 'bg-blue-50 text-blue-700',
                    ];
                ?>
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-semibold text-slate-600">
                                    <?= strtoupper(substr($a['email'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900"><?= \Admin\Core\View::e($a['email']) ?></p>
                                    <?php if ($isMe): ?>
                                        <span class="text-[10px] font-medium text-brand-600">you</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <?php if ($isMe): ?>
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium <?= $roleColors[$a['admin_role']] ?? 'bg-slate-100 text-slate-700' ?>">
                                    <?= htmlspecialchars($a['admin_role']) ?>
                                </span>
                            <?php else: ?>
                                <form method="post" action="/pasabuy/settings/admins/<?= (int) $a['id'] ?>/role"
                                      class="inline-block">
                                    <select name="admin_role" onchange="this.form.submit()"
                                            class="text-xs px-2 py-1 rounded-md border border-slate-200 bg-white cursor-pointer
                                                   focus:outline-none focus:border-brand-500">
                                        <?php foreach (['super','kyc','moderator','analytics'] as $r): ?>
                                            <option value="<?= $r ?>" <?= $a['admin_role'] === $r ? 'selected' : '' ?>>
                                                <?= $r ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-xs text-slate-500">
                            <?= htmlspecialchars(substr($a['created_at'], 0, 10)) ?>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <?php if (!$isMe): ?>
                                <form method="post" action="/pasabuy/settings/admins/<?= (int) $a['id'] ?>/delete"
                                      data-confirm-title="Remove this admin?"
                                      data-confirm-body="<?= htmlspecialchars($a['email']) ?> will lose console access immediately."
                                      data-confirm-variant="danger"
                                      data-confirm-label="Remove">
                                    <button type="submit"
                                            class="text-xs font-medium text-red-600 hover:text-red-700">
                                        Remove
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>