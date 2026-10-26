<?php
$name = trim(($profile['firstname'] ?? '') . ' ' . ($profile['lastname'] ?? ''));
$name = $name !== '' ? $name : $user['email'];
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <!-- Left -->
    <div class="lg:col-span-2 space-y-5">

        <!-- Profile card -->
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
            <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700
                            flex items-center justify-center text-white text-lg font-semibold shrink-0">
                    <?= strtoupper(substr($name, 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold tracking-tight text-slate-900 truncate"><?= \Admin\Core\View::e($name) ?></h2>
                    <p class="text-sm text-slate-500 truncate"><?= \Admin\Core\View::e($user['email']) ?></p>
                </div>
            </div>

            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Profile Details</h3>

            <?php if ($profile): ?>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <?php
                    $rows = [
                        'Last name' => $profile['lastname'] ?? '',
                        'First name' => $profile['firstname'] ?? '',
                        'Middle name' => $profile['middlename'] ?? '',
                        'Ext. name' => $profile['extname'] ?? '',
                        'Street / Purok' => $profile['street_purok'] ?? '',
                        'Barangay' => $profile['barangay'] ?? '',
                        'City / Municipality' => $profile['city_municipality'] ?? '',
                        'Province' => $profile['province'] ?? '',
                        'Country' => $profile['country'] ?? '',
                        'Zip code' => $profile['zip_code'] ?? '',
                    ];
                    foreach ($rows as $label => $value): ?>
                        <div>
                            <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-400 mb-0.5"><?= $label ?></dt>
                            <dd class="text-slate-900"><?= \Admin\Core\View::e($value) ?: '<span class="text-slate-300">—</span>' ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php else: ?>
                <p class="text-sm text-slate-400">No profile submitted yet.</p>
            <?php endif; ?>
        </div>

        <!-- KYC card -->
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">ID Verification</h3>
                <?php if ($kyc): $ks = $kyc['status'];
                    $stColors = ['accepted' => 'bg-brand-50 text-brand-700', 'declined' => 'bg-red-50 text-red-700', 'pending' => 'bg-amber-50 text-amber-700'];
                    $dotColors = ['accepted' => 'bg-brand-500', 'declined' => 'bg-red-500', 'pending' => 'bg-amber-500'];
                ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium <?= $stColors[$ks] ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?= $dotColors[$ks] ?>"></span>
                        <?= \Admin\Core\View::e($ks) ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!$kyc): ?>
                <p class="text-sm text-slate-400">No KYC submitted yet.</p>
            <?php else: ?>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm mb-6">
                    <div>
                        <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-400 mb-0.5">ID type</dt>
                        <dd class="text-slate-900"><?= \Admin\Core\View::e($kyc['id_type']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-400 mb-0.5">ID number</dt>
                        <dd class="text-slate-900 font-mono text-xs"><?= \Admin\Core\View::e($kyc['id_number']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-400 mb-0.5">Date issued</dt>
                        <dd class="text-slate-900"><?= \Admin\Core\View::e($kyc['date_issued']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-400 mb-0.5">Submitted</dt>
                        <dd class="text-slate-900"><?= \Admin\Core\View::e($kyc['created_at']) ?></dd>
                    </div>
                </dl>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400 mb-2">Front</p>
                        <img src="/pasabuy/kyc/<?= (int) $user['id'] ?>/image/front"
                            class="w-full rounded-xl ring-1 ring-slate-200 object-cover" alt="ID front">
                    </div>
                    <?php if (!empty($kyc['back_path'])): ?>
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400 mb-2">Back</p>
                            <img src="/pasabuy/kyc/<?= (int) $user['id'] ?>/image/back"
                                class="w-full rounded-xl ring-1 ring-slate-200 object-cover" alt="ID back">
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($kyc['remarks'])): ?>
                    <div class="mt-5 p-3.5 bg-red-50 ring-1 ring-red-100 rounded-xl">
                        <p class="text-xs font-semibold text-red-800 mb-1">Decline reason</p>
                        <p class="text-sm text-red-700"><?= \Admin\Core\View::e($kyc['remarks']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($kyc['status'] === 'pending'): ?>
                    <div class="mt-6 pt-6 border-t border-slate-100 flex flex-wrap gap-2">

                        <form method="post"
                            action="/pasabuy/users/<?= (int) $user['id'] ?>/kyc/approve"
                            data-confirm-title="Approve this user?"
                            data-confirm-body="They'll be able to post pasabuys immediately."
                            data-confirm-variant="success"
                            data-confirm-label="Approve">
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-600 hover:bg-brand-700
                           text-white text-sm font-medium rounded-lg transition-colors
                           focus:outline-none focus:ring-4 focus:ring-brand-500/20">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                Approve
                            </button>
                        </form>

                        <button type="button"
                            onclick="document.getElementById('declineBox').classList.toggle('hidden')"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-red-50
                       text-red-700 text-sm font-medium rounded-lg ring-1 ring-red-200 transition-colors">
                            Decline
                        </button>
                    </div>

                    <form id="declineBox" method="post"
                        action="/pasabuy/users/<?= (int) $user['id'] ?>/kyc/decline"
                        data-confirm-title="Decline this submission?"
                        data-confirm-body="The user will be notified and cannot post until they resubmit."
                        data-confirm-variant="danger"
                        data-confirm-label="Decline"
                        class="hidden mt-4 p-4 bg-red-50 ring-1 ring-red-100 rounded-xl space-y-3">
                        <label class="block text-xs font-semibold text-red-900">Reason for declining</label>
                        <textarea name="remarks" rows="3" required
                            class="w-full px-3 py-2 text-sm bg-white border border-red-200 rounded-lg
                         placeholder:text-slate-400
                         focus:outline-none focus:border-red-400 focus:ring-4 focus:ring-red-500/10"
                            placeholder="e.g. ID image is blurry, ID number does not match"></textarea>
                        <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                            Submit decline
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Reviews -->
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Reviews</h3>
                <?php if ((int) $rating['total'] > 0): ?>
                    <div class="flex items-center gap-2">
                       <?php require_once __DIR__ . '/../partials/stars.php'; ?>
                        <span class="text-lg font-bold text-slate-900">
                            <?= number_format((float) $rating['average'], 1) ?>
                        </span>
                        <?= render_stars((float) $rating['average'], 16) ?>
                        <span class="text-xs text-slate-500">
                            (<?= (int) $rating['total'] ?>)
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ((int) $rating['total'] === 0): ?>
                <p class="text-sm text-slate-400">No reviews yet.</p>
            <?php else: ?>

                <!-- Distribution -->
                <div class="mb-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5">
                    <?php foreach ([5, 4, 3, 2, 1] as $star):
                        $count = (int) ($rating['s' . $star] ?? 0);
                        $total = max(1, (int) $rating['total']);
                        $pct   = round(($count / $total) * 100, 1);
                    ?>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-600 w-4"><?= $star ?></span>
                            <?= render_stars($star, 12) ?>
                            <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                <div class="h-full rounded-full"
                                    style="width: <?= $pct ?>%; background: <?= $star >= 4 ? '#10b981' : ($star == 3 ? '#f59e0b' : '#ef4444') ?>;">
                                </div>
                            </div>
                            <span class="text-xs text-slate-500 w-6 text-right"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Reviews list -->
                <div class="border-t border-slate-100 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">
                        Received (<?= count($reviews) ?>)
                    </p>
                    <ul class="space-y-4">
                        <?php foreach ($reviews as $rv): ?>
                            <li class="pb-4 border-b border-slate-100 last:border-0 last:pb-0">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center
                                        text-slate-600 text-xs font-semibold shrink-0">
                                        <?= strtoupper(substr($rv['reviewer_email'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <a href="/pasabuy/users/<?= (int) $rv['reviewer_id'] ?>"
                                                class="text-sm font-semibold text-slate-900 hover:text-brand-600 truncate">
                                                <?= \Admin\Core\View::e($rv['reviewer_email']) ?>
                                            </a>
                                            <span class="text-[11px] text-slate-400">
                                                <?= \Admin\Core\View::e($rv['created_at']) ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <?= render_stars((float) $rv['rating'], 12) ?>
                                            <?php if (!empty($rv['item_description'])): ?>
                                                <span class="text-[11px] text-slate-500 truncate">
                                                    on "<?= \Admin\Core\View::e($rv['item_description']) ?>"
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($rv['description'])): ?>
                                            <p class="text-sm text-slate-700 leading-relaxed">
                                                <?= \Admin\Core\View::e($rv['description']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php if (!empty($reviewsGiven)): ?>
                    <div class="border-t border-slate-100 pt-5 mt-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">
                            Given by this user (<?= count($reviewsGiven) ?>)
                        </p>
                        <ul class="space-y-2">
                            <?php foreach ($reviewsGiven as $rg): ?>
                                <li class="flex items-center gap-3 text-sm">
                                    <a href="/pasabuy/users/<?= (int) $rg['reviewee_id'] ?>"
                                        class="text-slate-700 hover:text-brand-600 truncate flex-1">
                                        <?= \Admin\Core\View::e($rg['reviewee_email']) ?>
                                    </a>
                                    <?= render_stars((float) $rg['rating'], 12) ?>
                                    <span class="text-[11px] text-slate-400">
                                        <?= \Admin\Core\View::e($rg['created_at']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <!-- Right sidebar -->
    <div class="space-y-5">
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Account</h3>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wider mb-0.5">Role</dt>
                    <dd><span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-medium
                        <?= $user['role'] === 'pasabuyer' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700' ?>">
                            <?= \Admin\Core\View::e($user['role']) ?></span></dd>
                </div>
                <div>
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wider mb-0.5">Status</dt>
                    <dd class="text-slate-900"><?= \Admin\Core\View::e($user['status']) ?></dd>
                </div>
                <div>
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wider mb-0.5">Email verified</dt>
                    <dd class="text-slate-900"><?= $user['email_verified_at'] ? 'Yes' : 'No' ?></dd>
                </div>
                <div>
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wider mb-0.5">Registered</dt>
                    <dd class="text-slate-900"><?= \Admin\Core\View::e($user['created_at']) ?></dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">
                Posts (<?= count($posts) ?>)
            </h3>
            <?php if (empty($posts)): ?>
                <p class="text-sm text-slate-400">No posts yet.</p>
            <?php else: ?>
                <ul class="space-y-3 text-sm">
                    <?php foreach ($posts as $p): ?>
                        <li class="pb-3 border-b border-slate-100 last:border-0 last:pb-0">
                            <p class="font-medium text-slate-900 truncate"><?= \Admin\Core\View::e($p['title']) ?></p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                <?= \Admin\Core\View::e($p['type']) ?> · <?= \Admin\Core\View::e($p['status']) ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php if ((int) $rating['total'] > 0): ?>
          <?php require_once __DIR__ . '/../partials/stars.php'; ?>
            <div class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-soft p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4">Rating</h3>
                <div class="text-center">
                    <p class="text-4xl font-bold text-slate-900 tracking-tight mb-2">
                        <?= number_format((float) $rating['average'], 1) ?>
                    </p>
                    <div class="flex justify-center mb-2">
                        <?= render_stars((float) $rating['average'], 20) ?>
                    </div>
                    <p class="text-xs text-slate-500">
                        from <?= (int) $rating['total'] ?> review<?= (int) $rating['total'] === 1 ? '' : 's' ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
        <div class="bg-white rounded-2xl ring-1 ring-red-200 shadow-soft p-6">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-red-500 mb-1">Danger zone</h3>
            <p class="text-xs text-slate-500 mb-4">These actions affect the user's account state.</p>

            <div class="space-y-2">
                <!-- Reset KYC to pending so user can resubmit -->
                <form method="post"
                    action="/pasabuy/users/<?= (int) $user['id'] ?>/kyc/reset"
                    data-confirm-title="Reset KYC submission?"
                    data-confirm-body="The user will be able to upload a new ID. Their account status becomes pending."
                    data-confirm-variant="warning"
                    data-confirm-label="Reset KYC">
                    <button type="submit"
                        class="w-full inline-flex items-center justify-between px-3 py-2 text-sm
                           text-slate-700 bg-white hover:bg-slate-50 ring-1 ring-slate-200
                           rounded-lg transition-colors">
                        Reset KYC submission
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </button>
                </form>

                <!-- Force logout everywhere -->
                <form method="post"
                    action="/pasabuy/users/<?= (int) $user['id'] ?>/force-logout"
                    data-confirm-title="Sign this user out everywhere?"
                    data-confirm-body="All their active sessions and mobile tokens will be revoked."
                    data-confirm-variant="warning"
                    data-confirm-label="Force logout">
                    <button type="submit"
                        class="w-full inline-flex items-center justify-between px-3 py-2 text-sm
                           text-slate-700 bg-white hover:bg-slate-50 ring-1 ring-slate-200
                           rounded-lg transition-colors">
                        Force logout everywhere
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </button>
                </form>

                <!-- Delete account -->
                <form method="post"
                    action="/pasabuy/users/<?= (int) $user['id'] ?>/delete"
                    data-confirm-title="Delete this account?"
                    data-confirm-body="The user, their profile, KYC data, and all posts will be permanently removed. This cannot be undone."
                    data-confirm-variant="danger"
                    data-confirm-label="Delete forever">
                    <button type="submit"
                        class="w-full inline-flex items-center justify-between px-3 py-2 text-sm
                           text-red-700 bg-white hover:bg-red-50 ring-1 ring-red-200
                           rounded-lg transition-colors">
                        Delete account
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>