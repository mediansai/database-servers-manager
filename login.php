<?php
/**
 * Login Page
 */
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';

// Already logged in → redirect
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$redirect = htmlspecialchars($_GET['redirect'] ?? 'index.php');
$timeout  = isset($_GET['timeout']);
$error    = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Database Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #1e3a5f 0%, #0f2139 50%, #1a1a2e 100%); min-height: 100vh; }
        .card { backdrop-filter: blur(16px); background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); }
        .input-field { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #fff; transition: all .2s; }
        .input-field::placeholder { color: rgba(255,255,255,0.4); }
        .input-field:focus { outline: none; border-color: #3b82f6; background: rgba(255,255,255,0.12); box-shadow: 0 0 0 3px rgba(59,130,246,0.25); }
        .btn-login { background: linear-gradient(135deg, #3b82f6, #6366f1); transition: all .2s; }
        .btn-login:hover { background: linear-gradient(135deg, #2563eb, #4f46e5); transform: translateY(-1px); box-shadow: 0 8px 25px rgba(99,102,241,0.4); }
        .server-badge { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
        .float-icon { animation: float 3s ease-in-out infinite; }
    </style>
</head>
<body class="flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-blue-600 bg-opacity-20 border border-blue-500 border-opacity-30 mb-4 float-icon">
                <i class="fas fa-database text-4xl text-blue-400"></i>
            </div>
            <h1 class="text-3xl font-bold text-white">Database Manager</h1>
            <p class="text-blue-300 mt-1 text-sm">Secure access to your databases</p>
        </div>

        <!-- Server badges -->
        <div class="flex flex-wrap gap-2 justify-center mb-6">
            <?php foreach ($GLOBALS['DB_SERVERS'] as $id => $cfg): ?>
                <span class="server-badge text-blue-300 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                    <i class="fas fa-server text-xs"></i>
                    <?php echo htmlspecialchars($cfg['label']); ?> — <?php echo htmlspecialchars($cfg['host']); ?>
                </span>
            <?php endforeach; ?>
        </div>

        <!-- Card -->
        <div class="card rounded-2xl p-8 shadow-2xl">
            <?php if ($timeout): ?>
                <div class="mb-4 p-3 bg-yellow-500 bg-opacity-20 border border-yellow-500 border-opacity-30 rounded-lg text-yellow-300 text-sm flex items-center gap-2">
                    <i class="fas fa-clock"></i> Session expired. Please log in again.
                </div>
            <?php endif; ?>

            <div id="error-box" class="hidden mb-4 p-3 bg-red-500 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg text-red-300 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                <span id="error-msg">Invalid credentials</span>
            </div>

            <form id="login-form" novalidate>
                <input type="hidden" name="redirect" value="<?php echo $redirect; ?>">

                <!-- Username -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-blue-200 mb-2">
                        <i class="fas fa-user mr-1"></i> Username
                    </label>
                    <input type="text" id="username" name="username" required autocomplete="username"
                           placeholder="Enter your username"
                           class="input-field w-full rounded-lg px-4 py-3 text-sm">
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-blue-200 mb-2">
                        <i class="fas fa-lock mr-1"></i> Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                               placeholder="Enter your password"
                               class="input-field w-full rounded-lg px-4 py-3 pr-12 text-sm">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-blue-300 hover:text-white">
                            <i id="eye-icon" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" id="btn-login"
                        class="btn-login w-full py-3 rounded-lg text-white font-semibold text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <!-- Hint -->
            <p class="text-center text-xs text-blue-400 mt-6">
                Default: <code class="bg-white bg-opacity-10 px-1 rounded">admin</code> /
                <code class="bg-white bg-opacity-10 px-1 rounded">admin123</code>
            </p>
        </div>

        <p class="text-center text-xs text-blue-400 mt-6 opacity-60">
            &copy; <?php echo date('Y'); ?> Database Manager &mdash; All rights reserved
        </p>
    </div>

    <script>
    function togglePassword() {
        const p = document.getElementById('password');
        const i = document.getElementById('eye-icon');
        if (p.type === 'password') { p.type = 'text'; i.classList.replace('fa-eye','fa-eye-slash'); }
        else { p.type = 'password'; i.classList.replace('fa-eye-slash','fa-eye'); }
    }

    document.getElementById('login-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-login');
        const errBox = document.getElementById('error-box');
        const errMsg = document.getElementById('error-msg');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in…';
        errBox.classList.add('hidden');

        const formData = new FormData(this);
        formData.append('action', 'login');

        try {
            const res  = await fetch('handlers/auth_handler.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                window.location.href = document.querySelector('[name=redirect]').value || 'index.php';
            } else {
                errMsg.textContent = data.message || 'Invalid credentials';
                errBox.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            }
        } catch {
            errMsg.textContent = 'Server error — please try again';
            errBox.classList.remove('hidden');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
        }
    });

    // Focus username on load
    document.getElementById('username').focus();
    </script>
</body>
</html>
