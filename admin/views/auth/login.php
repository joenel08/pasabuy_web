 <?php
            // Portable base detection — works at /pasabuy/ AND at domain root
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
            $base = rtrim($scriptDir, '/\\');
            if ($base === '/' || $base === '\\' || $base === '.') $base = '';

            $logoUrl = $base . '/admin/assets/images/logo.png';
            $logoAbs = __DIR__ . '/../../assets/images/logo.png';
            ?>

<!DOCTYPE html>
<html lang="en" class="antialiased">

<head>
    <meta charset="UTF-8">
    <title>Sign in · PasaBuy Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="icon" type="image/png" href="<?= htmlspecialchars($base . '/admin/assets/images/logo.png', ENT_QUOTES) ?>" />
<link rel="apple-touch-icon" href="<?= htmlspecialchars($base . '/admin/assets/images/logo.png', ENT_QUOTES) ?>" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui']
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46'
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="font-sans bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
           
            <?php if (is_file($logoAbs)): ?>
                <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES) ?>"
                    alt="PasaBuy"
                    class="inline-flex w-12 h-12 rounded-2xl object-contain shadow-lift mb-4" />
            <?php else: ?>
                <div class="inline-flex w-12 h-12 rounded-2xl bg-brand-600 items-center justify-center text-white shadow-lift mb-4">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                        <path d="M3 6h18M16 10a4 4 0 0 1-8 0" />
                    </svg>
                </div>
            <?php endif; ?>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Welcome back</h1>
            <p class="text-sm text-slate-500 mt-1">Sign in to the admin console</p>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-6 shadow-soft">
            <?php foreach (\Admin\Core\Flash::pull() as $f): ?>
                <div class="mb-4 px-3 py-2.5 rounded-lg text-xs font-medium ring-1 <?= $f['type'] === 'success'
                                                                                        ? 'bg-brand-50 text-brand-700 ring-brand-200'
                                                                                        : 'bg-red-50 text-red-700 ring-red-200' ?>">
                    <?= \Admin\Core\View::e($f['message']) ?>
                </div>
            <?php endforeach; ?>

            <form method="post" action="/pasabuy/login" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Email</label>
                    <input name="email" type="email" required autofocus
                        placeholder="admin@pasabuy.test"
                        class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg
                                  placeholder:text-slate-400
                                  focus:outline-none focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10
                                  transition-all">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Password</label>
                    <input name="password" type="password" required
                        placeholder="••••••••"
                        class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg
                                  placeholder:text-slate-400
                                  focus:outline-none focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10
                                  transition-all">
                </div>
                <button class="w-full bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium
                               py-2.5 rounded-lg transition-colors
                               focus:outline-none focus:ring-4 focus:ring-slate-900/20">
                    Sign in
                </button>
            </form>
        </div>

        <div class="mt-6 text-center space-y-2">
            <p class="text-xs text-slate-400">PasaBuy Mo, Pasaya Ko · Admin Console</p>
            <a href="/pasabuy/" class="inline-block text-xs font-medium text-slate-500 hover:text-brand-700 transition-colors">
                ← Back to homepage
            </a>
        </div>
    </div>
</body>

</html>