<?php
// -------------------------------------------------------------------
// KPI CARDS
// -------------------------------------------------------------------
$cards = [
    ['label' => 'Total Users',    'value' => (string) $stats['total_users'],    'icon' => 'users', 'accent' => 'slate'],
    ['label' => 'Verified Users', 'value' => (string) $stats['accepted_users'], 'icon' => 'check', 'accent' => 'emerald'],
    ['label' => 'Pending KYC',    'value' => (string) $stats['pending_kyc'],    'icon' => 'clock', 'accent' => 'amber'],
    ['label' => 'Total Posts',    'value' => (string) $stats['total_posts'],    'icon' => 'doc',   'accent' => 'slate'],
];

// Fetch fee figures once — wrapped in try/catch so the page still loads
// if the migration hasn't been run yet
$feesToday       = 0.0;
$feesOutstanding = 0.0;
$feesThisMonth   = 0.0;
try {
    $db = \App\Core\Database::pdo();
    $feesToday = (float) $db->query(
        "SELECT COALESCE(SUM(admin_fee),0) FROM transactions
         WHERE fee_paid_at IS NOT NULL AND DATE(fee_paid_at) = CURDATE()"
    )->fetchColumn();
    $feesOutstanding = (float) $db->query(
        "SELECT COALESCE(SUM(admin_fee),0) FROM transactions
         WHERE fee_paid_at IS NULL AND status='completed' AND admin_fee > 0"
    )->fetchColumn();
    $feesThisMonth = (float) $db->query(
        "SELECT COALESCE(SUM(admin_fee),0) FROM transactions
         WHERE fee_paid_at IS NOT NULL
           AND fee_paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    )->fetchColumn();
} catch (\Throwable $e) {
    // Column missing? Show zeros and surface in the error log.
    error_log('[DASHBOARD] fee stats failed: ' . $e->getMessage());
}

$cards[] = ['label' => 'Fees Today',       'value' => '₱' . number_format($feesToday, 0),       'icon' => 'wallet', 'accent' => 'emerald'];
$cards[] = ['label' => 'Fees Outstanding', 'value' => '₱' . number_format($feesOutstanding, 0), 'icon' => 'wallet', 'accent' => 'amber'];

// -------------------------------------------------------------------
// ICONS
// -------------------------------------------------------------------
function dash_icon(string $n): string
{
    $icons = [
        'users'  => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'check'  => '<polyline points="20 6 9 17 4 12"/>',
        'clock'  => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'doc'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
    ];
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        . ($icons[$n] ?? '') . '</svg>';
}

$accentMap = [
    'slate'   => ['bg-slate-100', 'text-slate-700'],
    'emerald' => ['bg-brand-50',  'text-brand-600'],
    'amber'   => ['bg-amber-50',  'text-amber-600'],
    'red'     => ['bg-red-50',    'text-red-600'],
];

// Format helper — outputs the raw string if the value has non-digits (like ₱1,254)
// and casts to int only for numeric counts
function dash_value(string $v): string
{
    return ctype_digit($v) ? $v : $v;
}
?>

<!-- ============ KPI cards (6 across on wide screens) ============ -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
    <?php foreach ($cards as $c): [$bg, $fg] = $accentMap[$c['accent']]; ?>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-5 shadow-soft hover:shadow-lift transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex w-9 h-9 rounded-xl items-center justify-center <?= $bg ?> <?= $fg ?>">
                    <?= dash_icon($c['icon']) ?>
                </span>
            </div>
            <p class="text-xs font-medium text-slate-500 mb-1"><?= $c['label'] ?></p>
            <p class="text-2xl font-semibold tracking-tight text-slate-900">
                <?= htmlspecialchars($c['value'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
    <?php endforeach; ?>
</div>

<!-- ============ Fees this month banner (full width, standalone) ============ -->
<div class="bg-gradient-to-r from-brand-600 to-brand-700 rounded-2xl p-6 mt-6 text-white shadow-lift">
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-wider text-white/70 mb-1">Fees collected this month</p>
            <p class="text-3xl font-bold">
                ₱<?= number_format($feesThisMonth, 0) ?>
            </p>
            <p class="text-xs text-white/60 mt-1">
                <?= number_format($feesToday, 0) === '0'
                    ? 'No fees collected yet today'
                    : '₱' . number_format($feesToday, 0) . ' collected today' ?>
            </p>
        </div>
        <a href="/pasabuy/fees?tab=collected"
           class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium ring-1 ring-white/30 whitespace-nowrap">
            View ledger →
        </a>
    </div>
</div>

<!-- ============ Bottom row: pending KYC + declined ============ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">
    <div class="lg:col-span-2 bg-white rounded-2xl ring-1 ring-slate-200 p-6 shadow-soft">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-900">Pending KYC reviews</h3>
            <a href="/pasabuy/kyc?status=pending"
               class="text-xs font-medium text-brand-600 hover:text-brand-700">
                View all →
            </a>
        </div>

        <?php if ((int) $stats['pending_kyc'] === 0): ?>
            <div class="text-center py-8">
                <div class="inline-flex w-10 h-10 rounded-full bg-brand-50 text-brand-600 items-center justify-center mb-3">
                    <?= dash_icon('check') ?>
                </div>
                <p class="text-sm text-slate-600">All caught up — no pending reviews.</p>
            </div>
        <?php else: ?>
            <p class="text-sm text-slate-600">
                You have <strong class="text-slate-900"><?= (int) $stats['pending_kyc'] ?></strong>
                submission<?= (int) $stats['pending_kyc'] === 1 ? '' : 's' ?> awaiting review.
            </p>
            <a href="/pasabuy/kyc?status=pending"
               class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-slate-900 hover:bg-slate-800
                      text-white text-xs font-medium rounded-lg transition-colors">
                Review now
            </a>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-6 shadow-soft">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Declined</h3>
        <p class="text-3xl font-semibold tracking-tight text-red-600">
            <?= (int) $stats['declined_kyc'] ?>
        </p>
        <p class="text-xs text-slate-500 mt-1">Total declined submissions</p>
    </div>
</div>