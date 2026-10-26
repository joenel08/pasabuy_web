<?php
$current = $_SERVER['REQUEST_URI'] ?? '/';
$crumbs = [['label' => 'Admin', 'href' => '/pasabuy/dashboard']];
if (str_starts_with($current, '/pasabuy/dashboard')) $crumbs[] = ['label' => 'Dashboard'];
elseif (str_starts_with($current, '/pasabuy/analytics')) {
    $crumbs[] = ['label' => 'Analytics', 'href' => '/pasabuy/analytics'];
    if (preg_match('#/analytics/user/(\d+)#', $current, $m)) {
        $crumbs[] = ['label' => 'User #' . $m[1]];
    }
} elseif (str_starts_with($current, '/pasabuy/users')) {
    $crumbs[] = ['label' => 'Users', 'href' => '/pasabuy/users'];
    if (preg_match('#/users/(\d+)#', $current, $m)) $crumbs[] = ['label' => '#' . $m[1]];
} elseif (str_starts_with($current, '/pasabuy/kyc'))     $crumbs[] = ['label' => 'KYC Review'];
elseif (str_starts_with($current, '/pasabuy/posts'))   $crumbs[] = ['label' => 'Posts'];
elseif (str_starts_with($current, '/pasabuy/reports')) $crumbs[] = ['label' => 'Reports'];
elseif (str_starts_with($current, '/pasabuy/settings')) $crumbs[] = ['label' => 'System Settings'];
?>
<header class="h-16 bg-white/80 backdrop-blur-md border-b border-slate-200
              sticky top-0 z-20 flex items-center justify-between px-4 sm:px-6 lg:px-8 gap-3">

    <!-- Left: hamburger + title -->
    <div class="flex items-center gap-3 min-w-0 flex-1">
        <button type="button"
                data-sidebar-toggle
                aria-label="Open menu"
                aria-controls="sidebar"
                aria-expanded="false"
                class="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg
                       text-slate-600 hover:bg-slate-100 hover:text-slate-900
                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-colors shrink-0">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>

        <h1 class="text-base font-semibold tracking-tight text-slate-900 truncate">
            <?= \Admin\Core\View::e($title ?? '') ?>
        </h1>
    </div>

    <!-- Center: breadcrumbs -->
    <nav class="hidden md:flex items-center gap-1.5 text-xs text-slate-500">
        <?php foreach ($crumbs as $i => $c): ?>
            <?php if ($i > 0): ?>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-300">
                    <polyline points="9 18 15 12 9 6" />
                </svg>
            <?php endif; ?>
            <?php if (!empty($c['href']) && $i < count($crumbs) - 1): ?>
                <a href="<?= $c['href'] ?>" class="hover:text-slate-900 transition-colors"><?= $c['label'] ?></a>
            <?php else: ?>
                <span class="text-slate-900 font-medium"><?= $c['label'] ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Right: date + bell -->
    <div class="flex items-center gap-3 text-xs text-slate-500 relative shrink-0">
        <span class="hidden sm:inline"><?= date('M j, Y') ?></span>

        <button type="button"
                id="notif-bell"
                aria-label="Notifications"
                class="relative inline-flex w-9 h-9 items-center justify-center rounded-full
                       text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors
                       focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
            </svg>
            <span id="notif-bell-badge"
                  class="hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1
                         items-center justify-center rounded-full bg-red-500 text-white
                         text-[10px] font-bold leading-none border-2 border-white">
                0
            </span>
        </button>

        <div id="notif-panel"
             class="hidden absolute right-0 top-full mt-2 w-[360px]
                    bg-white rounded-2xl ring-1 ring-slate-200 shadow-lift
                    overflow-hidden z-30">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                <p class="text-sm font-semibold text-slate-900">Notifications</p>
                <button type="button"
                        id="notif-read-all"
                        class="text-xs font-medium text-brand-600 hover:text-brand-700">
                    Mark all as read
                </button>
            </div>
            <div id="notif-list"
                 class="max-h-[420px] overflow-y-auto divide-y divide-slate-100">
                <div class="px-4 py-8 text-center text-sm text-slate-400">Loading…</div>
            </div>
            <div class="px-4 py-3 border-t border-slate-100 text-center">
                <span class="text-[11px] text-slate-400">Auto-refresh every 15s</span>
            </div>
        </div>
    </div>
</header>