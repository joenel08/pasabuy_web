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
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title><?= \Admin\Core\View::e($title ?? 'Admin') ?> · PasaBuy</title>

           <link rel="preconnect" href="https://fonts.googleapis.com">
           <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
           <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                               'soft': '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
                               'lift': '0 4px 12px -2px rgb(15 23 42 / 0.08), 0 2px 6px -2px rgb(15 23 42 / 0.04)'
                           }
                       }
                   }
               }
           </script>

           <style>
               #sidebar {
                   position: fixed;
                   top: 0;
                   bottom: 0;
                   left: 0;
                   width: 16rem;
                   /* w-64 */
                   z-index: 40;
                   transform: translateX(-100%);
                   transition: transform 200ms ease-out;
                   will-change: transform;
               }

               body.sidebar-open #sidebar {
                   transform: translateX(0);
               }

               @media (min-width: 1024px) {
                   #sidebar {
                       position: static;
                       transform: none;
                       z-index: auto;
                       will-change: auto;
                   }
               }

               /* ---- Backdrop ---- */
               #sidebar-backdrop {
                   position: fixed;
                   inset: 0;
                   z-index: 30;
                   background: rgb(15 23 42 / 0.5);
                   backdrop-filter: blur(4px);
                   opacity: 0;
                   pointer-events: none;
                   transition: opacity 200ms ease-out;
               }

               body.sidebar-open #sidebar-backdrop {
                   opacity: 1;
                   pointer-events: auto;
               }

               @media (min-width: 1024px) {
                   #sidebar-backdrop {
                       display: none;
                   }
               }

               /* ---- Scroll lock ---- */
               body.sidebar-open {
                   overflow: hidden;
               }

               @media (min-width: 1024px) {
                   body.sidebar-open {
                       overflow: auto;
                   }
               }


               [x-cloak] {
                   display: none !important
               }

               ::-webkit-scrollbar {
                   width: 10px;
                   height: 10px
               }

               ::-webkit-scrollbar-track {
                   background: transparent
               }

               ::-webkit-scrollbar-thumb {
                   background: #cbd5e1;
                   border-radius: 9999px;
                   border: 2px solid #f8fafc
               }

               ::-webkit-scrollbar-thumb:hover {
                   background: #94a3b8
               }

               /* Confirm modal animations (native <dialog>) */
               dialog#confirm-modal {
                   padding: 0;
                   border: 0;
                   background: transparent;
                   /* No max-width here — Tailwind's max-w-* class controls the size.
       Native showModal() already centers the element in the viewport. */
               }

               dialog#confirm-modal::backdrop {
                   background: rgb(15 23 42 / 0.45);
                   backdrop-filter: blur(4px);
                   transition: opacity .2s ease, display .2s ease allow-discrete;
               }

               dialog#confirm-modal[open] {
                   opacity: 1;
                   transform: scale(1);
                   transition: opacity .2s ease, transform .2s ease,
                       overlay .2s ease allow-discrete, display .2s ease allow-discrete;
               }



               @starting-style {
                   dialog#confirm-modal[open] {
                       opacity: 0;
                       transform: scale(.96);
                   }

                   dialog#confirm-modal[open]::backdrop {
                       opacity: 0;
                   }
               }

               @media (prefers-reduced-motion: reduce) {

                   dialog#confirm-modal,
                   dialog#confirm-modal::backdrop {
                       transition: none;
                   }
               }
           </style>
       </head>

       <body class="font-sans bg-slate-50 text-slate-900 min-h-screen">
           <div class="flex min-h-screen">

               <?php require __DIR__ . '/partials/sidebar.php'; ?>
               <div id="sidebar-backdrop"
                   class="fixed inset-0 z-30 bg-slate-900/50 backdrop-blur-sm
            opacity-0 pointer-events-none transition-opacity duration-200
            lg:hidden"
                   aria-hidden="true"></div>

               <div class="flex-1 flex flex-col min-w-0">
                   <?php require __DIR__ . '/partials/header.php'; ?>

                   <main class="flex-1 px-6 lg:px-8 py-6 max-w-[1600px] w-full mx-auto">

                       <?php foreach (\Admin\Core\Flash::pull() as $f): ?>
                           <?php $ok = $f['type'] === 'success'; ?>
                           <div class="mb-5 flex items-start gap-3 px-4 py-3 rounded-xl ring-1
                    <?= $ok ? 'bg-brand-50 ring-brand-200 text-brand-800'
                                : 'bg-red-50 ring-red-200 text-red-800' ?>">
                               <span class="mt-0.5 inline-flex w-5 h-5 items-center justify-center rounded-full
                        <?= $ok ? 'bg-brand-500' : 'bg-red-500' ?> text-white text-xs font-bold">
                                   <?= $ok ? '✓' : '!' ?>
                               </span>
                               <p class="text-sm font-medium"><?= \Admin\Core\View::e($f['message']) ?></p>
                           </div>
                       <?php endforeach; ?>

                       <?= $content ?>
                   </main>

                   <footer class="px-6 lg:px-8 py-5 text-xs text-slate-400 border-t border-slate-200">
                       PasaBuy Mo, Pasaya Ko · Admin Console
                   </footer>
               </div>
           </div>

           <!-- ============================================================
     Global Confirm Modal — one instance, reused everywhere.
     Open it via:
       - any <form data-confirm="...">  (auto-intercepted)
       - any <a data-confirm="...">     (auto-intercepted)
       - window.Confirm.open({...})     (programmatic)
     ============================================================ -->
           <dialog id="confirm-modal"
               aria-labelledby="confirm-title"
               class="fixed inset-0 m-auto w-full max-w-sm">

               <div data-confirm-card
                   class="bg-white rounded-2xl ring-1 ring-slate-200 shadow-lift p-6">
                   <div class="text-center">
                       <div data-confirm-icon
                           class="mx-auto flex w-11 h-11 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                           <svg width="20" height="20" viewBox="0 0 24 24"
                               fill="none" stroke="currentColor" stroke-width="2"
                               stroke-linecap="round" stroke-linejoin="round">
                               <circle cx="12" cy="12" r="10" />
                               <path d="M12 9v4" />
                               <path d="M12 17h.01" />
                           </svg>
                       </div>

                       <h2 id="confirm-title"
                           data-confirm-title
                           class="mt-4 text-base font-semibold tracking-tight text-slate-900">
                           Are you sure?
                       </h2>

                       <p data-confirm-body
                           class="mt-2 text-sm text-slate-500 leading-relaxed">
                       </p>
                   </div>

                   <div class="mt-6 flex items-center gap-2">
                       <button type="button"
                           data-confirm-cancel
                           class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg
                           text-sm font-medium text-slate-700 bg-white ring-1 ring-slate-200
                           hover:bg-slate-50 transition-colors
                           focus:outline-none focus:ring-4 focus:ring-slate-900/10
                           disabled:opacity-60 disabled:cursor-not-allowed">
                           Cancel
                       </button>

                       <button type="button"
                           data-confirm-ok
                           class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg
                           text-sm font-medium text-white bg-slate-900 hover:bg-slate-800
                           transition-colors focus:outline-none focus:ring-4 focus:ring-slate-900/20
                           disabled:opacity-60 disabled:cursor-not-allowed">
                           <svg data-confirm-spinner
                               class="hidden animate-spin"
                               width="14" height="14" viewBox="0 0 24 24" fill="none">
                               <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity=".25" stroke-width="4" />
                               <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                           </svg>
                           <span data-confirm-ok-label>Confirm</span>
                       </button>
                   </div>
               </div>
           </dialog>

        <script src="<?= htmlspecialchars($base . '/admin/assets/admin.js', ENT_QUOTES) ?>" defer></script>
       </body>

       </html>