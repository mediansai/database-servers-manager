<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Database Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">

    <script src="assets/js/main.js"></script>

</head>
<body class="bg-gray-50">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Auth/Server Top Bar -->
    <?php
    $currentServer  = ServerManager::getCurrentId();
    $currentCfg     = ServerManager::getCurrent();
    $allServers     = ServerManager::getServers();
    ?>
    <div class="fixed top-0 left-0 right-0 z-40 bg-gray-900 text-white text-xs flex items-center justify-between px-4 py-1.5 shadow" style="height:36px">
        <!-- Left: Server switcher -->
        <div class="flex items-center gap-3">
            <i class="fas fa-server text-blue-400"></i>
            <div class="relative" id="server-dropdown-wrap">
                <button onclick="toggleServerMenu()" class="flex items-center gap-1.5 hover:text-blue-300 transition font-medium">
                    <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>
                    <span id="server-label"><?php echo htmlspecialchars($currentCfg['label']); ?></span>
                    <span class="text-gray-400">(<?php echo htmlspecialchars($currentCfg['host']); ?>)</span>
                    <i class="fas fa-chevron-down text-xs ml-1"></i>
                </button>
                <div id="server-menu" class="hidden absolute left-0 top-6 bg-white text-gray-800 rounded-lg shadow-xl border border-gray-200 min-w-max z-50 py-1">
                    <?php foreach ($allServers as $id => $cfg): ?>
                        <button onclick="switchServer('<?php echo htmlspecialchars($id); ?>')"
                                class="flex items-center gap-2 w-full px-4 py-2 hover:bg-blue-50 text-left text-sm <?php echo $id === $currentServer ? 'text-blue-700 font-semibold' : 'text-gray-700'; ?>">
                            <span class="w-2 h-2 rounded-full <?php echo $id === $currentServer ? 'bg-green-500' : 'bg-gray-300'; ?>"></span>
                            <span><?php echo htmlspecialchars($cfg['label']); ?></span>
                            <span class="text-xs text-gray-400"><?php echo htmlspecialchars($cfg['host']); ?></span>
                            <?php if ($id === $currentServer): ?><i class="fas fa-check text-blue-500 ml-auto"></i><?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right: User info + logout -->
        <div class="flex items-center gap-4">
            <span class="text-gray-400 flex items-center gap-1.5">
                <i class="fas fa-user-circle text-blue-400"></i>
                <span><?php echo htmlspecialchars(Auth::name() ?? ''); ?></span>
                <span class="bg-blue-700 text-blue-200 px-1.5 py-0.5 rounded text-xs capitalize"><?php echo htmlspecialchars(Auth::role() ?? ''); ?></span>
            </span>
            <button onclick="logoutUser()" class="flex items-center gap-1 text-gray-400 hover:text-red-400 transition">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <!-- Spacer for fixed top bar -->
    <div style="height:36px"></div>

    <script>
    function toggleServerMenu() {
        document.getElementById('server-menu').classList.toggle('hidden');
    }
    document.addEventListener('click', function(e) {
        const wrap = document.getElementById('server-dropdown-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('server-menu').classList.add('hidden');
        }
    });
    async function switchServer(id) {
        document.getElementById('server-menu').classList.add('hidden');
        const fd = new FormData();
        fd.append('action', 'switch_server');
        fd.append('server_id', id);
        const res  = await fetch('handlers/auth_handler.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) { window.location.reload(); }
        else alert('Failed: ' + (data.message || 'Unknown error'));
    }
    async function logoutUser() {
        const fd = new FormData();
        fd.append('action', 'logout');
        await fetch('handlers/auth_handler.php', { method: 'POST', body: fd });
        window.location.href = 'login.php';
    }
    </script>

