<?php
// Portable base detection
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
$base = rtrim($scriptDir, '/\\');
if ($base === '/' || $base === '\\' || $base === '.') $base = '';

$logoUrl = $base . '/admin/assets/images/logo.png';
$logoAbs = __DIR__ . '/../../assets/images/logo.png';

$current = $_SERVER['REQUEST_URI'] ?? '/';
$admin   = \Admin\Core\AdminAuth::user();

$allGroups = [
    [
        'section' => 'Overview',
        'items' => [
            ['key' => 'dashboard', 'href' => '/pasabuy/dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'category' => 'dashboard'],
            ['key' => 'analytics', 'href' => '/pasabuy/analytics', 'label' => 'Analytics', 'icon' => 'chart'],
        ],
    ],
    [
        'section' => 'Manage',
        'items' => [
            ['key' => 'users',   'href' => '/pasabuy/users',   'label' => 'Users',      'icon' => 'users',    'category' => 'users'],
            ['key' => 'kyc',     'href' => '/pasabuy/kyc',     'label' => 'KYC Review', 'icon' => 'shield',   'category' => 'kyc'],
            ['key' => 'posts',   'href' => '/pasabuy/posts',   'label' => 'Posts',      'icon' => 'document', 'category' => 'posts'],
            ['key' => 'reports', 'href' => '/pasabuy/reports', 'label' => 'Reports',    'icon' => 'flag',     'category' => 'reports'],
            ['key' => 'transactions', 'href' => '/pasabuy/transactions', 'label' => 'Transactions', 'icon' => 'receipt', 'category' => 'transactions'],
            ['key' => 'fees', 'href' => '/pasabuy/fees', 'label' => 'Fees', 'icon' => 'wallet', 'category' => 'fees']
            
            ],
    ],
    [
        'section' => 'System',
        'items' => [
            ['key' => 'settings', 'href' => '/pasabuy/settings', 'label' => 'System Settings', 'icon' => 'settings'],
        ],
    ],
];

// Filter by role
$nav = [];
foreach ($allGroups as $group) {
    $visible = array_values(array_filter(
        $group['items'],
        fn($i) => \Admin\Core\AdminAuth::can($i['key'])
    ));
    if (!empty($visible)) {
        $nav[] = ['section' => $group['section'], 'items' => $visible];
    }
}

function nav_icon(string $name): string {
    $icons = [
        'grid'     => '<path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>',
        'chart'    => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
        'users'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
        'flag'     => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
        'receipt' => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
        ];
    $path = $icons[$name] ?? $icons['grid'];
    return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" '
         . 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
         . $path . '</svg>';
}
?>
<aside id="sidebar" class="flex flex-col bg-white border-r border-slate-200">

    <a href="/pasabuy/dashboard"
       class="h-16 flex items-center gap-2.5 px-5 border-b border-slate-200 hover:bg-slate-50 transition-colors">
        <?php if (is_file($logoAbs)): ?>
            <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES) ?>"
                 alt="PasaBuy"
                 class="w-8 h-8 rounded-lg object-contain shadow-soft" />
        <?php else: ?>
            <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white shadow-soft">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                    <path d="M3 6h18M16 10a4 4 0 0 1-8 0" />
                </svg>
            </div>
        <?php endif; ?>
        <div class="leading-tight">
            <p class="text-sm font-semibold tracking-tight text-slate-900">PasaBuy</p>
            <p class="text-[11px] text-slate-500">Admin Console</p>
        </div>
    </a>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        <?php foreach ($nav as $group): ?>
            <div>
                <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                    <?= $group['section'] ?>
                </p>
                <ul class="space-y-0.5">
                    <?php foreach ($group['items'] as $item):
                        $isActive = str_starts_with($current, $item['href']);
                        $category = $item['category'] ?? null;   /* ← FIX */
                    ?>
                        <li>
                            <a href="<?= $item['href'] ?>"
                               class="group flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                                   <?= $isActive
                                       ? 'bg-slate-100 text-slate-900'
                                       : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                                <span class="<?= $isActive ? 'text-brand-600' : 'text-slate-400 group-hover:text-slate-600' ?>">
                                    <?= nav_icon($item['icon']) ?>
                                </span>
                                <span class="flex-1"><?= $item['label'] ?></span>
                                <?php if ($category): ?>
                                    <span class="notif-sidebar-badge hidden
                                                 min-w-[20px] h-5 px-1.5
                                                 items-center justify-center
                                                 rounded-full bg-red-500 text-white
                                                 text-[10px] font-bold"
                                          data-category="<?= $category ?>">0</span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>

    <?php if ($admin): ?>
    <div class="p-3 border-t border-slate-200">
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-slate-50">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-brand-700
                        flex items-center justify-center text-white text-xs font-semibold shrink-0">
                <?= strtoupper(substr($admin['email'], 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-slate-900 truncate">
                    <?= \Admin\Core\View::e($admin['email']) ?>
                </p>
                <p class="text-[11px] text-slate-500 capitalize">
                    <?= \Admin\Core\View::e(\Admin\Core\AdminAuth::role()) ?>
                </p>
            </div>
            <form method="post" action="/pasabuy/logout"
                  data-confirm-title="Sign out?"
                  data-confirm-body="You'll need to sign in again to access the admin console."
                  data-confirm-variant="warning"
                  data-confirm-label="Sign out"
                  data-confirm-cancel="Stay">
                <button type="submit" title="Sign out"
                        class="text-slate-400 hover:text-red-600 transition-colors rounded-md p-1">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</aside>