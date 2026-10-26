       <?php

        // Portable base detection — works at /pasabuy/ AND at domain root
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim($scriptDir, '/\\');
        if ($base === '/' || $base === '\\' || $base === '.') $base = '';

        $logoUrl = $base . '/admin/assets/images/logo.png';
        $logoAbs = __DIR__ . '/../../assets/images/logo.png';

        $apkUrl   = $base . '/app/pasabuy-mo.apk';
        $loginUrl = $base . '/login';
        ?>
       <!DOCTYPE html>
       <html lang="en">

       <head>
           <meta charset="UTF-8">
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title>PasaBuy Mo, Pasaya Ko</title>
           <meta name="description" content="Community-based local and international pasabuy, made safe and simple.">

           <link rel="preconnect" href="https://fonts.googleapis.com">
           <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
           <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
           <link rel="icon" type="image/png" href="<?= htmlspecialchars($base . '/admin/assets/images/logo.png', ENT_QUOTES) ?>" />
           <link rel="apple-touch-icon" href="<?= htmlspecialchars($base . '/admin/assets/images/logo.png', ENT_QUOTES) ?>" />
           <script src="https://cdn.tailwindcss.com"></script>
           <script>
               tailwind.config = {
                   theme: {
                       extend: {
                           fontFamily: {
                               sans: ['Inter', 'ui-sans-serif', 'system-ui']
                           },
                           colors: {
                               brand: {
                                   50: '#ecfdf5',
                                   100: '#d1fae5',
                                   200: '#a7f3d0',
                                   300: '#6ee7b7',
                                   400: '#34d399',
                                   500: '#10b981',
                                   600: '#059669',
                                   700: '#047857',
                                   800: '#065f46',
                                   900: '#064e3b'
                               }
                           },
                           boxShadow: {
                               soft: '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
                               lift: '0 20px 40px -12px rgb(15 23 42 / 0.15), 0 4px 12px -4px rgb(15 23 42 / 0.08)'
                           }
                       }
                   }
               }
           </script>
           <style>
               .hero-bg {
                   background:
                       radial-gradient(ellipse at top right, rgba(16, 185, 129, .18), transparent 55%),
                       radial-gradient(ellipse at bottom left, rgba(5, 150, 105, .10), transparent 55%),
                       #f8fafc;
               }
           </style>
       </head>

       <body class="font-sans antialiased text-slate-900 bg-slate-50">

           <!-- ================= NAV ================= -->
           <header class="absolute top-0 inset-x-0 z-20">
               <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">


                   <div class="flex items-center gap-2.5">
                       <?php if (is_file($logoAbs)): ?>
                           <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES) ?>"
                               alt="PasaBuy"
                               class="w-9 h-9 rounded-lg object-contain shadow-lift" />
                       <?php else: ?>
                           <div class="w-9 h-9 rounded-lg bg-brand-600 flex items-center justify-center shadow-lift">
                               <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white"
                                   stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                   <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                                   <path d="M3 6h18M16 10a4 4 0 0 1-8 0" />
                               </svg>
                           </div>
                       <?php endif; ?>
                       <span class="text-base font-bold tracking-tight text-slate-900">PasaBuy</span>
                   </div>
                   <a href="/pasabuy/login"
                       class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-700 hover:text-brand-700 transition-colors">
                       Admin sign in
                       <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                           stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                           <polyline points="9 18 15 12 9 6" />
                       </svg>
                   </a>
               </div>
           </header>

           <!-- ================= HERO ================= -->
           <section class="hero-bg pt-32 pb-20 md:pt-40 md:pb-28">
               <div class="max-w-6xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">

                   <div>
                       <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/70 backdrop-blur ring-1 ring-brand-200 text-brand-700 text-xs font-medium mb-6">
                           <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></span>
                           Now available for Android
                       </div>

                       <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight leading-[1.05] text-slate-900">
                           PasaBuy Mo,<br>
                           <span class="text-brand-600">Pasaya Ko.</span>
                       </h1>

                       <p class="mt-6 text-lg text-slate-600 leading-relaxed max-w-lg">
                           The community platform for local and international pasabuy.
                           Buy anything from anywhere, have trusted travelers bring it home —
                           safely, transparently, and with real reviews.
                       </p>

                       <div class="mt-8 flex flex-wrap gap-3">
                           <a href="<?= htmlspecialchars($apkUrl, ENT_QUOTES) ?>"
                               download
                               class="inline-flex items-center gap-2.5 px-6 py-3.5 bg-slate-900 hover:bg-slate-800
                          text-white text-sm font-semibold rounded-xl transition-colors shadow-lift">
                               <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                   <path d="M3 20.5V3.5c0-.6.4-1.1.9-1.3l10.7 8-10.7 8c-.5-.2-.9-.7-.9-1.2zM15.4 13.3l2.8 2.1-2.8-2.1zM14.3 12l3.4-3.4L7.6 2.8 14.3 12zM14.3 12L7.6 21.2l10.1-5.8L14.3 12zm3.9-1.4l2.8-2.1-2.8 2.1z" />
                               </svg>
                               Download for Android
                           </a>

                           <a href="#how"
                               class="inline-flex items-center gap-2 px-6 py-3.5 bg-white hover:bg-slate-50
                          text-slate-700 text-sm font-semibold rounded-xl ring-1 ring-slate-200 transition-colors">
                               How it works
                           </a>
                       </div>

                       <p class="mt-4 text-xs text-slate-500">
                           Android 5.0+ · Requires internet · Free to use
                       </p>
                   </div>

                   <!-- Phone mockup -->
                   <div class="flex justify-center md:justify-end">
                       <div class="relative">
                           <div class="absolute -inset-8 bg-brand-500/10 blur-3xl rounded-full"></div>
                           <div class="relative w-64 md:w-72 bg-slate-900 rounded-[2.5rem] p-3 shadow-lift">
                               <div class="w-full aspect-[9/19] bg-gradient-to-b from-brand-50 to-white rounded-[2rem] overflow-hidden flex flex-col">
                                   <!-- Fake app bar -->
                                   <div class="flex items-center justify-between px-4 pt-4 pb-2">
                                       <div class="flex items-center gap-1.5">
                                           <?php if (is_file($logoAbs)): ?>
                                               <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES) ?>"
                                                   alt="PasaBuy"
                                                   class="w-5 h-5 rounded-md object-contain" />
                                           <?php else: ?>
                                               <div class="w-5 h-5 rounded-md bg-brand-600 flex items-center justify-center">
                                                   <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white"
                                                       stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                       <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                                                       <path d="M3 6h18M16 10a4 4 0 0 1-8 0" />
                                                   </svg>
                                               </div>
                                           <?php endif; ?>
                                           <span class="text-xs font-bold text-brand-600">PasaBuy</span>
                                       </div>
                                       <div class="w-6 h-6 rounded-full bg-brand-600"></div>
                                   </div>

                                   <!-- Fake feed -->
                                   <div class="flex-1 px-3 space-y-2 pt-2">
                                       <div class="bg-white rounded-xl p-2.5 ring-1 ring-slate-100">
                                           <div class="flex items-center gap-2 mb-1.5">
                                               <div class="w-5 h-5 rounded-full bg-brand-500"></div>
                                               <div class="flex-1">
                                                   <div class="h-1.5 w-16 bg-slate-200 rounded-full mb-1"></div>
                                                   <div class="h-1 w-10 bg-slate-100 rounded-full"></div>
                                               </div>
                                           </div>
                                           <div class="h-16 bg-brand-100 rounded-lg mb-1.5"></div>
                                           <div class="h-1.5 w-3/4 bg-slate-200 rounded-full mb-1"></div>
                                           <div class="h-1.5 w-1/2 bg-slate-100 rounded-full"></div>
                                       </div>
                                       <div class="bg-white rounded-xl p-2.5 ring-1 ring-slate-100">
                                           <div class="flex items-center gap-2 mb-1.5">
                                               <div class="w-5 h-5 rounded-full bg-indigo-500"></div>
                                               <div class="flex-1">
                                                   <div class="h-1.5 w-20 bg-slate-200 rounded-full mb-1"></div>
                                                   <div class="h-1 w-12 bg-slate-100 rounded-full"></div>
                                               </div>
                                           </div>
                                           <div class="h-1.5 w-5/6 bg-slate-200 rounded-full mb-1"></div>
                                           <div class="h-1.5 w-2/3 bg-slate-100 rounded-full"></div>
                                       </div>
                                   </div>

                                   <!-- Fake bottom nav -->
                                   <div class="h-10 bg-white border-t border-slate-100 flex items-center justify-around px-2">
                                       <div class="w-4 h-4 rounded bg-brand-500"></div>
                                       <div class="w-4 h-4 rounded bg-slate-200"></div>
                                       <div class="w-4 h-4 rounded bg-slate-200"></div>
                                       <div class="w-4 h-4 rounded bg-slate-200"></div>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
           </section>

           <!-- ================= FEATURES ================= -->
           <section class="py-20 bg-white">
               <div class="max-w-6xl mx-auto px-6">
                   <div class="text-center mb-14">
                       <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">
                           Built for trust
                       </h2>
                       <p class="mt-3 text-slate-600 max-w-xl mx-auto">
                           Every transaction runs on verified identities, honest feedback, and clear deal tracking.
                       </p>
                   </div>

                   <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                       <div class="p-6 rounded-2xl bg-slate-50 ring-1 ring-slate-200 hover:ring-brand-300 transition-colors">
                           <div class="w-11 h-11 rounded-xl bg-brand-100 text-brand-600 flex items-center justify-center mb-4">
                               <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                   <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                   <path d="m9 12 2 2 4-4" />
                               </svg>
                           </div>
                           <h3 class="text-base font-bold text-slate-900 mb-1.5">Verified users</h3>
                           <p class="text-sm text-slate-600 leading-relaxed">
                               Every user submits a government ID. Our team verifies it manually before they can trade.
                           </p>
                       </div>

                       <div class="p-6 rounded-2xl bg-slate-50 ring-1 ring-slate-200 hover:ring-brand-300 transition-colors">
                           <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-4">
                               <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                                   <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                               </svg>
                           </div>
                           <h3 class="text-base font-bold text-slate-900 mb-1.5">Real reviews</h3>
                           <p class="text-sm text-slate-600 leading-relaxed">
                               Both sides rate each other after every completed deal. No fake accounts, no spam.
                           </p>
                       </div>

                       <div class="p-6 rounded-2xl bg-slate-50 ring-1 ring-slate-200 hover:ring-brand-300 transition-colors">
                           <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-4">
                               <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                   <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                               </svg>
                           </div>
                           <h3 class="text-base font-bold text-slate-900 mb-1.5">Chat built-in</h3>
                           <p class="text-sm text-slate-600 leading-relaxed">
                               Talk directly to the other party. Propose deals, share photos, confirm on both sides.
                           </p>
                       </div>

                       <div class="p-6 rounded-2xl bg-slate-50 ring-1 ring-slate-200 hover:ring-brand-300 transition-colors">
                           <div class="w-11 h-11 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center mb-4">
                               <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                   <rect x="3" y="4" width="18" height="18" rx="2" />
                                   <path d="M16 2v4M8 2v4M3 10h18" />
                               </svg>
                           </div>
                           <h3 class="text-base font-bold text-slate-900 mb-1.5">Deal tracking</h3>
                           <p class="text-sm text-slate-600 leading-relaxed">
                               Every deal has a clear status — pending, in progress, confirmed — until both sides are done.
                           </p>
                       </div>

                       <div class="p-6 rounded-2xl bg-slate-50 ring-1 ring-slate-200 hover:ring-brand-300 transition-colors">
                           <div class="w-11 h-11 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center mb-4">
                               <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                   <path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z" />
                               </svg>
                           </div>
                           <h3 class="text-base font-bold text-slate-900 mb-1.5">Local & international</h3>
                           <p class="text-sm text-slate-600 leading-relaxed">
                               Pasabuy from anywhere — within or outside your area.
                           </p>
                       </div>

                       <div class="p-6 rounded-2xl bg-slate-50 ring-1 ring-slate-200 hover:ring-brand-300 transition-colors">
                           <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
                               <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                   <path d="M3 3v18h18" />
                                   <path d="m19 9-5 5-4-4-3 3" />
                               </svg>
                           </div>
                           <h3 class="text-base font-bold text-slate-900 mb-1.5">Your stats</h3>
                           <p class="text-sm text-slate-600 leading-relaxed">
                               Track your completed deals, success rate, and total value over time.
                           </p>
                       </div>
                   </div>
               </div>
           </section>

           <!-- ================= HOW IT WORKS ================= -->
           <section id="how" class="py-20 bg-slate-50">
               <div class="max-w-6xl mx-auto px-6">
                   <div class="text-center mb-14">
                       <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900">
                           How it works
                       </h2>
                       <p class="mt-3 text-slate-600 max-w-xl mx-auto">
                           Four simple steps from download to done deal.
                       </p>
                   </div>

                   <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                       <div class="text-center">
                           <div class="w-14 h-14 mx-auto rounded-2xl bg-brand-600 text-white flex items-center justify-center text-xl font-bold mb-4 shadow-lift">
                               1
                           </div>
                           <h3 class="font-bold text-slate-900 mb-1.5">Get verified</h3>
                           <p class="text-sm text-slate-600">Register, submit a government ID, wait for admin approval.</p>
                       </div>

                       <div class="text-center">
                           <div class="w-14 h-14 mx-auto rounded-2xl bg-brand-600 text-white flex items-center justify-center text-xl font-bold mb-4 shadow-lift">
                               2
                           </div>
                           <h3 class="font-bold text-slate-900 mb-1.5">Post or find</h3>
                           <p class="text-sm text-slate-600">Offer a pasabuy trip or request an item from someone traveling.</p>
                       </div>

                       <div class="text-center">
                           <div class="w-14 h-14 mx-auto rounded-2xl bg-brand-600 text-white flex items-center justify-center text-xl font-bold mb-4 shadow-lift">
                               3
                           </div>
                           <h3 class="font-bold text-slate-900 mb-1.5">Chat & deal</h3>
                           <p class="text-sm text-slate-600">Message, agree on price, propose a deal, mark it done together.</p>
                       </div>

                       <div class="text-center">
                           <div class="w-14 h-14 mx-auto rounded-2xl bg-brand-600 text-white flex items-center justify-center text-xl font-bold mb-4 shadow-lift">
                               4
                           </div>
                           <h3 class="font-bold text-slate-900 mb-1.5">Rate each other</h3>
                           <p class="text-sm text-slate-600">Leave a star rating. Help the community know who's trustworthy.</p>
                       </div>
                   </div>
               </div>
           </section>

           <!-- ================= CTA ================= -->
           <section class="py-20 bg-slate-900 relative overflow-hidden">
               <div class="absolute inset-0 opacity-30"
                   style="background: radial-gradient(ellipse at center, rgba(16,185,129,.4), transparent 60%);"></div>
               <div class="relative max-w-3xl mx-auto px-6 text-center">
                   <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white">
                       Ready to start your first pasabuy?
                   </h2>
                   <p class="mt-4 text-slate-300 text-lg">
                       Free to download. No fees. Just community, trust, and safe trading.
                   </p>
                   <div class="mt-8">
                       <a href="<?= htmlspecialchars($apkUrl, ENT_QUOTES) ?>"
                           download
                           class="inline-flex items-center gap-2.5 px-8 py-4 bg-brand-500 hover:bg-brand-400
                      text-slate-900 text-base font-bold rounded-xl transition-colors shadow-lift">
                           <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                               <path d="M3 20.5V3.5c0-.6.4-1.1.9-1.3l10.7 8-10.7 8c-.5-.2-.9-.7-.9-1.2zM15.4 13.3l2.8 2.1-2.8-2.1zM14.3 12l3.4-3.4L7.6 2.8 14.3 12zM14.3 12L7.6 21.2l10.1-5.8L14.3 12zm3.9-1.4l2.8-2.1-2.8 2.1z" />
                           </svg>
                           Download PasaBuy
                       </a>
                   </div>
               </div>
           </section>

           <!-- ================= FOOTER ================= -->
           <footer class="py-10 bg-white border-t border-slate-200">
               <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
                   <div class="flex items-center gap-2.5">
                       <?php if (is_file($logoAbs)): ?>
                           <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES) ?>"
                               alt="PasaBuy"
                               class="w-7 h-7 rounded-lg object-contain" />
                       <?php else: ?>
                           <div class="w-7 h-7 rounded-lg bg-brand-600 flex items-center justify-center">
                               <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white"
                                   stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                   <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                                   <path d="M3 6h18M16 10a4 4 0 0 1-8 0" />
                               </svg>
                           </div>
                       <?php endif; ?>
                       <span class="text-sm font-bold text-slate-900">PasaBuy Mo, Pasaya Mo</span>
                   </div>
                   <p class="text-xs text-slate-500">
                       © <?= date('Y') ?> PasaBuy. All rights reserved.
                   </p>
                   <a href="/pasabuy/login"
                       class="text-xs font-medium text-slate-500 hover:text-brand-700 transition-colors">
                       Admin sign in →
                   </a>
               </div>
           </footer>

       </body>

       </html>