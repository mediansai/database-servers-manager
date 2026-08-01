<!-- Sidebar -->
<div class="sidebar w-80 bg-white border-r border-gray-200 flex flex-col">
    <!-- Header -->
    <div class="p-6 border-b border-gray-200">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-database text-blue-600"></i>
            Database Manager
        </h1>
        <p class="text-sm text-gray-500 mt-1">Modern & Professional</p>
        <div class="mt-3 flex flex-col gap-2">
            <a href="file_manager.php" 
               class="flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-sm text-sm">
                <i class="fas fa-folder-open"></i>
                <span class="font-medium">File Manager</span>
            </a>
            <a href="backup.php"
               class="flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-lg hover:from-orange-600 hover:to-amber-600 transition-all shadow-sm text-sm">
                <i class="fas fa-archive"></i>
                <span class="font-medium">Backup Manager</span>
            </a>
            <a href="profiler.php<?php echo $selectedDatabase ? '?database=' . urlencode($selectedDatabase) : ''; ?>"
               class="flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-teal-500 to-cyan-600 text-white rounded-lg hover:from-teal-600 hover:to-cyan-700 transition-all shadow-sm text-sm">
                <i class="fas fa-tachometer-alt"></i>
                <span class="font-medium">Query Profiler</span>
            </a>
        </div>
    </div>

    <!-- Database List -->
    <div class="flex-1 overflow-y-auto p-4">
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-semibold text-gray-700 uppercase">Databases</h2>
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full"><?php echo count($databases); ?></span>
            </div>
            <div class="space-y-1">
                <?php foreach ($databases as $db): ?>
                    <?php 
                    $isActive = $db === $selectedDatabase;
                    $bgClass = $isActive ? 'bg-blue-50 border-blue-500 text-blue-700' : 'hover:bg-gray-50 border-transparent text-gray-700';
                    ?>
                    <a href="?database=<?php echo urlencode($db); ?>" 
                       class="block px-3 py-2 rounded-lg border-l-4 <?php echo $bgClass; ?> transition-all">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">
                                <i class="fas fa-database text-sm mr-2"></i>
                                <?php echo htmlspecialchars($db); ?>
                            </span>
                            <?php if ($isActive): ?>
                                <i class="fas fa-check text-blue-600"></i>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($selectedDatabase && $tables): ?>
            <div class="mt-6">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase">Tables</h2>
                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full"><?php echo count($tables); ?></span>
                </div>
                <div class="space-y-1">
                    <?php foreach ($tables as $tbl): ?>
                        <?php 
                        $isActive = $tbl === $selectedTable;
                        $bgClass = $isActive ? 'bg-green-50 border-green-500 text-green-700' : 'hover:bg-gray-50 border-transparent text-gray-700';
                        ?>
                        <a href="?database=<?php echo urlencode($selectedDatabase); ?>&table=<?php echo urlencode($tbl); ?>" 
                           class="block px-3 py-2 rounded-lg border-l-4 <?php echo $bgClass; ?> transition-all">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-sm">
                                    <i class="fas fa-table text-xs mr-2"></i>
                                    <?php echo htmlspecialchars($tbl); ?>
                                </span>
                                <?php if ($isActive): ?>
                                    <i class="fas fa-check text-green-600"></i>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="p-4 border-t border-gray-200 bg-gray-50">
        <div class="text-xs text-gray-500 space-y-1">
            <?php $srvCfg = ServerManager::getCurrent(); ?>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-400 flex-shrink-0"></span>
                <span class="font-medium text-gray-700"><?php echo htmlspecialchars($srvCfg['label']); ?></span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-server text-gray-400"></i>
                <span><?php echo htmlspecialchars($srvCfg['host']); ?></span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-user text-gray-400"></i>
                <span><?php echo htmlspecialchars($srvCfg['user']); ?></span>
            </div>
        </div>
    </div>
</div>
