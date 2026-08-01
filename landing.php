<?php
/**
 * Landing Page — Public showcase page (no authentication required)
 * A beautiful, single-file landing page for the Database Servers Manager project.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Servers Manager — Modern MySQL Management Tool</title>
    <meta name="description" content="A modern, self-hosted MySQL database management tool. Browse tables, design schemas visually, export to 10+ formats, backup and restore — all from a beautiful web UI.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; margin: 0; overflow-x: hidden; }

        /* ── Gradient Background ── */
        .bg-hero {
            background: linear-gradient(135deg, #0a0f1e 0%, #111b33 30%, #0d1f3c 60%, #0a1628 100%);
            position: relative;
        }
        .bg-hero::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(600px circle at 20% 30%, rgba(59,130,246,0.12), transparent 50%),
                radial-gradient(500px circle at 80% 70%, rgba(99,102,241,0.10), transparent 50%),
                radial-gradient(400px circle at 50% 50%, rgba(6,182,212,0.06), transparent 50%);
            pointer-events: none;
        }

        /* ── Glass Cards ── */
        .glass {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .glass-white {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        /* ── Floating Animation ── */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-14px) rotate(2deg); }
        }
        @keyframes float-delay {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(-1.5deg); }
        }
        .float { animation: float 5s ease-in-out infinite; }
        .float-delay { animation: float-delay 6s ease-in-out infinite 1s; }

        /* ── Pulse Ring ── */
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 0.6; }
            50% { transform: scale(1.15); opacity: 0; }
            100% { transform: scale(0.8); opacity: 0; }
        }
        .pulse-ring::after {
            content: '';
            position: absolute; inset: -8px;
            border: 2px solid rgba(59,130,246,0.4);
            border-radius: inherit;
            animation: pulse-ring 3s ease-in-out infinite;
        }

        /* ── Glow Effects ── */
        .glow-blue { box-shadow: 0 0 40px rgba(59,130,246,0.15), 0 0 80px rgba(59,130,246,0.05); }
        .glow-purple { box-shadow: 0 0 40px rgba(139,92,246,0.15), 0 0 80px rgba(139,92,246,0.05); }

        /* ── Feature Icon Containers ── */
        .feature-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover .feature-icon {
            transform: translateY(-3px) scale(1.08);
        }

        /* ── Feature Cards ── */
        .feature-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 32px 28px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        .feature-card:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.12);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .feature-card:hover::before { opacity: 1; }

        /* ── Step Numbers ── */
        .step-number {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1.2rem;
            flex-shrink: 0;
        }

        /* ── Export Format Pill ── */
        .format-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.8rem; font-weight: 500;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            transition: all 0.3s ease;
        }
        .format-pill:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        /* ── CTA Button ── */
        .btn-cta {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            color: #fff;
            padding: 14px 36px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex; align-items: center; gap: 10px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(99,102,241,0.3);
        }
        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(99,102,241,0.45);
            background: linear-gradient(135deg, #2563eb, #4f46e5);
        }

        .btn-outline {
            border: 1px solid rgba(255,255,255,0.2);
            color: #cbd5e1;
            padding: 14px 36px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex; align-items: center; gap: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            background: transparent;
        }
        .btn-outline:hover {
            border-color: rgba(255,255,255,0.4);
            background: rgba(255,255,255,0.06);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ── Scroll Animation ── */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ── Smooth Scroll ── */
        html { scroll-behavior: smooth; }

        /* ── Code Block ── */
        .code-block {
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 20px 24px;
            font-family: 'Menlo', 'Consolas', 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.7;
            color: #e2e8f0;
            overflow-x: auto;
        }
        .code-block .comment { color: #64748b; }
        .code-block .keyword { color: #c084fc; }
        .code-block .string { color: #34d399; }
        .code-block .var { color: #60a5fa; }
    </style>
</head>
<body class="bg-hero text-white">

    <!-- ═══════════════════════════════════════════════════
         NAVIGATION
         ═══════════════════════════════════════════════════ -->
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="#" class="flex items-center gap-3 text-white no-underline">
                <div class="w-10 h-10 rounded-xl bg-blue-600 bg-opacity-20 border border-blue-500 border-opacity-30 flex items-center justify-center">
                    <i class="fas fa-database text-blue-400 text-lg"></i>
                </div>
                <span class="font-bold text-lg tracking-tight">DB Servers Manager</span>
            </a>
            <div class="hidden md:flex items-center gap-8">
                <a href="#features" class="text-gray-400 hover:text-white transition text-sm font-medium">Features</a>
                <a href="#quickstart" class="text-gray-400 hover:text-white transition text-sm font-medium">Quick Start</a>
                <a href="#formats" class="text-gray-400 hover:text-white transition text-sm font-medium">Export Formats</a>
                <a href="login.php" class="btn-cta text-sm" style="padding: 10px 24px; font-size: 0.85rem;">
                    <i class="fas fa-sign-in-alt"></i> Launch App
                </a>
            </div>
            <!-- Mobile menu button -->
            <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="md:hidden text-gray-400 hover:text-white">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden glass mx-4 mb-2 rounded-xl p-4">
            <div class="flex flex-col gap-3">
                <a href="#features" class="text-gray-300 hover:text-white transition text-sm font-medium py-2">Features</a>
                <a href="#quickstart" class="text-gray-300 hover:text-white transition text-sm font-medium py-2">Quick Start</a>
                <a href="#formats" class="text-gray-300 hover:text-white transition text-sm font-medium py-2">Export Formats</a>
                <a href="login.php" class="btn-cta text-sm text-center" style="padding: 10px 24px;">
                    <i class="fas fa-sign-in-alt"></i> Launch App
                </a>
            </div>
        </div>
    </nav>


    <!-- ═══════════════════════════════════════════════════
         HERO SECTION
         ═══════════════════════════════════════════════════ -->
    <section class="relative min-h-screen flex items-center justify-center px-6 pt-20">
        <div class="max-w-5xl mx-auto text-center">

            <!-- Floating Icon -->
            <div class="inline-block mb-8 relative">
                <div class="w-24 h-24 rounded-3xl bg-blue-600 bg-opacity-15 border border-blue-500 border-opacity-25 flex items-center justify-center float pulse-ring relative glow-blue">
                    <i class="fas fa-database text-5xl text-blue-400"></i>
                </div>
            </div>

            <!-- Heading -->
            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight leading-tight mb-6">
                <span class="block">Database Servers</span>
                <span class="bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent">Manager</span>
            </h1>

            <!-- Subheading -->
            <p class="text-lg md:text-xl text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                A modern, self-hosted MySQL management tool. Browse tables, design schemas visually,
                export to <strong class="text-gray-200">10+ formats</strong>, backup & restore —
                all from a beautiful web UI.
            </p>

            <!-- CTAs -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
                <a href="#quickstart" class="btn-outline">
                    <i class="fas fa-book-open"></i>
                    Quick Start Guide
                </a>
            </div>

            <!-- Tech Badges -->
            <div class="flex flex-wrap items-center justify-center gap-3">
                <span class="glass px-4 py-2 rounded-full text-xs font-medium text-gray-300 flex items-center gap-2">
                    <i class="fab fa-php text-indigo-400"></i> PHP 8.0+
                </span>
                <span class="glass px-4 py-2 rounded-full text-xs font-medium text-gray-300 flex items-center gap-2">
                    <i class="fas fa-database text-blue-400"></i> MySQL 5.7+
                </span>
                <span class="glass px-4 py-2 rounded-full text-xs font-medium text-gray-300 flex items-center gap-2">
                    <i class="fas fa-wind text-cyan-400"></i> Tailwind CSS
                </span>
                <span class="glass px-4 py-2 rounded-full text-xs font-medium text-gray-300 flex items-center gap-2">
                    <i class="fas fa-lock text-green-400"></i> No Composer Required
                </span>
                <span class="glass px-4 py-2 rounded-full text-xs font-medium text-gray-300 flex items-center gap-2">
                    <i class="fas fa-feather-alt text-amber-400"></i> MIT License
                </span>
            </div>
        </div>

        <!-- Scroll indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-gray-500 text-xs">
            <span>Scroll to explore</span>
            <i class="fas fa-chevron-down animate-bounce"></i>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════════════
         FEATURES SECTION
         ═══════════════════════════════════════════════════ -->
    <section id="features" class="py-24 px-6 relative">
        <div class="max-w-6xl mx-auto">

            <!-- Section Header -->
            <div class="text-center mb-16 reveal">
                <span class="glass inline-block px-4 py-1.5 rounded-full text-xs font-semibold text-blue-300 tracking-widest uppercase mb-4">Features</span>
                <h2 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Everything you need</h2>
                <p class="text-gray-400 text-lg max-w-xl mx-auto">Six powerful modules in one lightweight, zero-dependency tool.</p>
            </div>

            <!-- Feature Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- 1 — Multi-Server -->
                <div class="feature-card reveal" style="--accent: #3b82f6;">
                    <div class="feature-icon mb-5" style="background: rgba(59,130,246,0.12); color: #60a5fa;">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Multi-Server Management</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Configure unlimited MySQL servers and switch between them instantly from any page. Connection status indicators with graceful error handling.
                    </p>
                </div>

                <!-- 2 — Table Browser -->
                <div class="feature-card reveal" style="--accent: #10b981;">
                    <div class="feature-icon mb-5" style="background: rgba(16,185,129,0.12); color: #34d399;">
                        <i class="fas fa-table"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Table Browser & Editor</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Browse databases with paginated data views. Click any cell to inline-edit. Insert, duplicate, delete rows, and run raw SQL queries.
                    </p>
                </div>

                <!-- 3 — Visual Designer -->
                <div class="feature-card reveal" style="--accent: #8b5cf6;">
                    <div class="feature-icon mb-5" style="background: rgba(139,92,246,0.12); color: #a78bfa;">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Visual Database Designer</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Interactive ERD canvas with drag-and-drop. Create tables, draw relationships, auto-layout, minimap navigation, and export as PNG.
                    </p>
                </div>

                <!-- 4 — Schema Export -->
                <div class="feature-card reveal" style="--accent: #f59e0b;">
                    <div class="feature-icon mb-5" style="background: rgba(245,158,11,0.12); color: #fbbf24;">
                        <i class="fas fa-download"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Export to 10+ Formats</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        One-click export to MySQL, SQLite, Prisma, Laravel Migrations, TypeScript, Zod, JSON Schema, Django, Sequelize, and Mongoose.
                    </p>
                </div>

                <!-- 5 — Backup -->
                <div class="feature-card reveal" style="--accent: #ef4444;">
                    <div class="feature-icon mb-5" style="background: rgba(239,68,68,0.12); color: #f87171;">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Backup & Restore</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Create full or partial backups, download as SQL files, and restore from uploads or saved backups. Admin-only access control.
                    </p>
                </div>

                <!-- 6 — File Manager -->
                <div class="feature-card reveal" style="--accent: #06b6d4;">
                    <div class="feature-icon mb-5" style="background: rgba(6,182,212,0.12); color: #22d3ee;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Offline File Manager</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Design database schemas as standalone files — no server connection needed. Import, export, tag, and version your schemas.
                    </p>
                </div>

            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════════════
         QUICK START SECTION
         ═══════════════════════════════════════════════════ -->
    <section id="quickstart" class="py-24 px-6 relative">
        <div class="max-w-4xl mx-auto">

            <!-- Section Header -->
            <div class="text-center mb-16 reveal">
                <span class="glass inline-block px-4 py-1.5 rounded-full text-xs font-semibold text-green-300 tracking-widest uppercase mb-4">Setup</span>
                <h2 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Up and running in 3 steps</h2>
                <p class="text-gray-400 text-lg">No Composer. No npm. No database migrations. Just PHP + MySQL.</p>
            </div>

            <!-- Steps -->
            <div class="space-y-8">

                <!-- Step 1 -->
                <div class="glass rounded-2xl p-6 md:p-8 reveal">
                    <div class="flex items-start gap-5">
                        <div class="step-number" style="background: rgba(59,130,246,0.15); color: #60a5fa;">1</div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-white mb-3">Clone the repository</h3>
                            <div class="code-block">
                                <span class="comment">$ </span>git clone https://github.com/mediansai/database-servers-manager.git<br>
                                <span class="comment">$ </span>cd database-servers-manager
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="glass rounded-2xl p-6 md:p-8 reveal">
                    <div class="flex items-start gap-5">
                        <div class="step-number" style="background: rgba(16,185,129,0.15); color: #34d399;">2</div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-white mb-3">Configure your database</h3>
                            <p class="text-gray-400 text-sm mb-4">Edit <code class="bg-white bg-opacity-10 px-1.5 py-0.5 rounded text-blue-300 text-xs">config.php</code> with your MySQL credentials:</p>
                            <div class="code-block">
                                <span class="var">$GLOBALS</span>[<span class="string">'DB_SERVERS'</span>] = [<br>
                                &nbsp;&nbsp;<span class="string">'local'</span> => [<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;<span class="string">'host'</span> &nbsp;&nbsp;&nbsp;&nbsp;=> <span class="string">'localhost'</span>,<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;<span class="string">'user'</span> &nbsp;&nbsp;&nbsp;&nbsp;=> <span class="string">'root'</span>,<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;<span class="string">'password'</span> => <span class="string">''</span>,<br>
                                &nbsp;&nbsp;],<br>
                                ];
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="glass rounded-2xl p-6 md:p-8 reveal">
                    <div class="flex items-start gap-5">
                        <div class="step-number" style="background: rgba(139,92,246,0.15); color: #a78bfa;">3</div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-white mb-3">Open in your browser</h3>
                            <div class="code-block mb-4">
                                http://localhost/database-servers-manager/
                            </div>
                            <div class="flex flex-wrap gap-4 text-sm">
                                <div class="glass rounded-lg px-4 py-3 flex items-center gap-3">
                                    <i class="fas fa-user text-blue-400"></i>
                                    <div>
                                        <div class="text-gray-300 font-medium">Admin</div>
                                        <div class="text-gray-500 text-xs font-mono">admin / admin123</div>
                                    </div>
                                </div>
                                <div class="glass rounded-lg px-4 py-3 flex items-center gap-3">
                                    <i class="fas fa-eye text-purple-400"></i>
                                    <div>
                                        <div class="text-gray-300 font-medium">Viewer</div>
                                        <div class="text-gray-500 text-xs font-mono">viewer / manager</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════════════
         EXPORT FORMATS SECTION
         ═══════════════════════════════════════════════════ -->
    <section id="formats" class="py-24 px-6 relative">
        <div class="max-w-5xl mx-auto">

            <!-- Section Header -->
            <div class="text-center mb-16 reveal">
                <span class="glass inline-block px-4 py-1.5 rounded-full text-xs font-semibold text-amber-300 tracking-widest uppercase mb-4">Export</span>
                <h2 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">10+ export formats</h2>
                <p class="text-gray-400 text-lg max-w-xl mx-auto">Export your database schema to any framework or language with one click.</p>
            </div>

            <!-- Format Pills -->
            <div class="flex flex-wrap items-center justify-center gap-3 reveal">
                <span class="format-pill" style="--accent: #3b82f6;">
                    <i class="fas fa-database text-blue-400"></i>
                    <span class="text-gray-200">MySQL SQL</span>
                </span>
                <span class="format-pill">
                    <i class="fas fa-hard-drive text-teal-400"></i>
                    <span class="text-gray-200">SQLite</span>
                </span>
                <span class="format-pill">
                    <i class="fas fa-layer-group text-purple-400"></i>
                    <span class="text-gray-200">Prisma</span>
                </span>
                <span class="format-pill">
                    <i class="fab fa-laravel text-red-400"></i>
                    <span class="text-gray-200">Laravel</span>
                </span>
                <span class="format-pill">
                    <i class="fas fa-code text-sky-400"></i>
                    <span class="text-gray-200">TypeScript</span>
                </span>
                <span class="format-pill">
                    <i class="fas fa-shield-halved text-cyan-400"></i>
                    <span class="text-gray-200">Zod</span>
                </span>
                <span class="format-pill">
                    <i class="fas fa-file-code text-amber-400"></i>
                    <span class="text-gray-200">JSON Schema</span>
                </span>
                <span class="format-pill">
                    <i class="fas fa-leaf text-green-400"></i>
                    <span class="text-gray-200">Django</span>
                </span>
                <span class="format-pill">
                    <i class="fas fa-cubes text-orange-400"></i>
                    <span class="text-gray-200">Sequelize</span>
                </span>
                <span class="format-pill">
                    <i class="fas fa-leaf text-emerald-400"></i>
                    <span class="text-gray-200">Mongoose</span>
                </span>
            </div>

            <!-- Highlight Box -->
            <div class="mt-12 glass rounded-2xl p-8 text-center reveal glow-purple">
                <div class="text-3xl mb-3">🎨</div>
                <h3 class="text-xl font-bold text-white mb-2">Visual Designer Included</h3>
                <p class="text-gray-400 text-sm max-w-lg mx-auto leading-relaxed">
                    Design your database visually with our drag-and-drop ERD tool, then export the entire schema
                    to your preferred format — or download a PNG image of your diagram.
                </p>
            </div>

        </div>
    </section>


    <!-- ═══════════════════════════════════════════════════
         SECURITY SECTION
         ═══════════════════════════════════════════════════ -->
    <section class="py-24 px-6 relative">
        <div class="max-w-5xl mx-auto">
            <div class="glass rounded-2xl p-8 md:p-12 reveal">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div>
                        <span class="glass inline-block px-4 py-1.5 rounded-full text-xs font-semibold text-green-300 tracking-widest uppercase mb-4">Security</span>
                        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight mb-4">Built secure by default</h2>
                        <p class="text-gray-400 leading-relaxed mb-6">
                            No external dependencies for authentication. Users are configured in a single PHP file with bcrypt-hashed passwords and role-based access control.
                        </p>
                        <a href="login.php" class="btn-cta text-sm" style="padding: 12px 28px;">
                            <i class="fas fa-lock"></i> Try It Now
                        </a>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(16,185,129,0.12);">
                                <i class="fas fa-check text-green-400 text-xs"></i>
                            </div>
                            <div>
                                <div class="text-white font-medium text-sm">bcrypt Password Hashing</div>
                                <div class="text-gray-500 text-xs mt-0.5">Industry-standard one-way hashing</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(16,185,129,0.12);">
                                <i class="fas fa-check text-green-400 text-xs"></i>
                            </div>
                            <div>
                                <div class="text-white font-medium text-sm">Session Fixation Protection</div>
                                <div class="text-gray-500 text-xs mt-0.5">Session ID regenerated on login</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(16,185,129,0.12);">
                                <i class="fas fa-check text-green-400 text-xs"></i>
                            </div>
                            <div>
                                <div class="text-white font-medium text-sm">Role-Based Access Control</div>
                                <div class="text-gray-500 text-xs mt-0.5">Admin and Viewer roles with granular permissions</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(16,185,129,0.12);">
                                <i class="fas fa-check text-green-400 text-xs"></i>
                            </div>
                            <div>
                                <div class="text-white font-medium text-sm">XSS & SQL Injection Protection</div>
                                <div class="text-gray-500 text-xs mt-0.5">PDO prepared statements + htmlspecialchars</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(16,185,129,0.12);">
                                <i class="fas fa-check text-green-400 text-xs"></i>
                            </div>
                            <div>
                                <div class="text-white font-medium text-sm">Auto Session Timeout</div>
                                <div class="text-gray-500 text-xs mt-0.5">Configurable idle timeout with auto-logout</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════════════
         CTA SECTION
         ═══════════════════════════════════════════════════ -->
    <section class="py-24 px-6 relative">
        <div class="max-w-3xl mx-auto text-center reveal">
            <div class="w-16 h-16 rounded-2xl bg-indigo-600 bg-opacity-15 border border-indigo-500 border-opacity-25 flex items-center justify-center mx-auto mb-6 float-delay">
                <i class="fas fa-rocket text-3xl text-indigo-400"></i>
            </div>
            <h2 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4">Ready to get started?</h2>
            <p class="text-gray-400 text-lg mb-8 max-w-lg mx-auto">
                Clone the repo, edit one config file, and you're managing databases in under a minute.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="https://github.com/mediansai/database-servers-manager" class="btn-outline" target="_blank">
                    <i class="fab fa-github"></i>
                    View on GitHub
                </a>
            </div>
        </div>
    </section>


    <!-- ═══════════════════════════════════════════════════
         FOOTER
         ═══════════════════════════════════════════════════ -->
    <footer class="py-12 px-6 border-t border-white border-opacity-5">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 bg-opacity-20 border border-blue-500 border-opacity-30 flex items-center justify-center">
                        <i class="fas fa-database text-blue-400 text-sm"></i>
                    </div>
                    <span class="font-semibold text-sm text-gray-300">Database Servers Manager</span>
                </div>
                <div class="flex items-center gap-6 text-sm text-gray-500">
                    <span>&copy; <?php echo date('Y'); ?> — MIT License</span>
                    <a href="https://github.com/mediansai/database-servers-manager" class="hover:text-gray-300 transition" target="_blank">
                        <i class="fab fa-github text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>


    <!-- ═══════════════════════════════════════════════════
         SCRIPTS
         ═══════════════════════════════════════════════════ -->
    <script>
    // ── Navbar background on scroll ──
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(10, 15, 30, 0.85)';
            navbar.style.backdropFilter = 'blur(20px)';
            navbar.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
        } else {
            navbar.style.background = 'transparent';
            navbar.style.backdropFilter = 'none';
            navbar.style.borderBottom = 'none';
        }
    });

    // ── Scroll reveal animation ──
    const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -40px 0px' };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Stagger animation for feature cards
                const delay = entry.target.closest('.grid') 
                    ? Array.from(entry.target.parentNode.children).indexOf(entry.target) * 100 
                    : 0;
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, delay);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

    // ── Mobile menu auto-close on link click ──
    document.querySelectorAll('#mobile-menu a').forEach(link => {
        link.addEventListener('click', () => {
            document.getElementById('mobile-menu').classList.add('hidden');
        });
    });
    </script>

</body>
</html>
