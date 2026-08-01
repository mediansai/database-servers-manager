<?php
/**
 * Modern Database Manager - Main Entry Point
 */

// Initialize session
session_start();

// Include configuration
require_once __DIR__ . '/config.php';

// Include core classes
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/ServerManager.php';
require_once __DIR__ . '/includes/DatabaseOperations.php';
require_once __DIR__ . '/includes/ColumnTypeHelper.php';

// Require authentication
Auth::require();

// Initialize database connection
$connectionError = null;
$db = null;
$dbOps = null;
$databases = [];
$tables = [];
$tablesMetadata = [];
$tableData = null;
$tableStructure = null;
$primaryKey = null;

$selectedDatabase = $_GET['database'] ?? null;
$selectedTable = $_GET['table'] ?? null;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = ROWS_PER_PAGE;

try {
    $db = Database::getInstance();
    $dbOps = new DatabaseOperations($db->getConnection());

    $databases = $dbOps->getDatabases();
    $tables = $selectedDatabase ? $dbOps->getTables($selectedDatabase) : [];
    $tablesMetadata = $selectedDatabase && !$selectedTable ? $dbOps->getTablesWithMetadata($selectedDatabase) : [];

    if ($selectedDatabase && $selectedTable) {
        $tableData = $dbOps->getTableData($selectedDatabase, $selectedTable, $currentPage, $perPage);
        $tableStructure = $dbOps->getTableStructure($selectedDatabase, $selectedTable);
        $primaryKey = $dbOps->getPrimaryKey($selectedDatabase, $selectedTable);
    }
} catch (\Exception $e) {
    $connectionError = $e->getMessage();
}

// Include header (never needs a DB connection)
include __DIR__ . '/views/header.php';
?>

<?php if ($connectionError): ?>
<div class="flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-2xl shadow-xl p-10 max-w-lg w-full text-center">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-plug text-red-500 text-2xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Connection Failed</h2>
        <p class="text-gray-500 text-sm mb-1">
            Could not connect to <strong><?php echo htmlspecialchars(ServerManager::getCurrent()['label']); ?></strong>
            (<?php echo htmlspecialchars(ServerManager::getCurrent()['host']); ?>)
        </p>
        <p class="text-red-400 text-xs font-mono bg-red-50 rounded p-3 mt-3 mb-6 text-left break-all"><?php echo htmlspecialchars($connectionError); ?></p>
        <div class="flex gap-3 justify-center">
            <?php foreach (ServerManager::getServers() as $sid => $scfg): if ($sid === ServerManager::getCurrentId()) continue; ?>
            <button onclick="switchServer('<?php echo htmlspecialchars($sid); ?>')" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium flex items-center gap-2">
                <i class="fas fa-server"></i> Switch to <?php echo htmlspecialchars($scfg['label']); ?>
            </button>
            <?php endforeach; ?>
            <button onclick="logoutUser()" class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm font-medium flex items-center gap-2">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>
</div>
<?php else: ?>
<div class="flex h-screen">
    <?php include __DIR__ . '/views/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <?php if ($selectedDatabase && $selectedTable): ?>
                        <h2 class="text-xl font-semibold text-gray-800">
                            <a href="index.php?database=<?php echo urlencode($selectedDatabase); ?>">
                                <?php echo htmlspecialchars($selectedDatabase); ?>
                            </a>
                            <span class="text-gray-400">/</span>
                            <a href="index.php?database=<?php echo urlencode($selectedDatabase); ?>&table=<?php echo urlencode($selectedTable); ?>">
                                <?php echo htmlspecialchars($selectedTable); ?>
                            </a>
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-info-circle"></i>
                            <?php echo $tableData['total']; ?> rows total | Page <?php echo $currentPage; ?> of <?php echo $tableData['pages']; ?>
                        </p>
                    <?php else: ?>
                        <h2 class="text-xl font-semibold text-gray-800">Welcome to 
                            <a href="index.php">
                                Database Manager
                            </a>
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">Select a database and table to get started</p>
                    <?php endif; ?>
                </div>
                <div class="flex gap-2">
                    <?php if ($selectedDatabase): ?>
                        <a href="designer.php?database=<?php echo urlencode($selectedDatabase); ?>" class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2" title="Database Designer">
                            <i class="fas fa-project-diagram"></i>
                        </a>
                        <button onclick="openExportModal()" class="px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2" title="Export Database">
                            <i class="fas fa-download"></i>
                            <!-- <span>Export</span> -->
                        </button>
                        <button onclick="openImportModal()" class="px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2" title="Import SQL">
                            <i class="fas fa-upload"></i>
                            <!-- <span>Import</span> -->
                        </button>
                    <?php endif; ?>
                    <button onclick="openQueryModal()" class="px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2" title="Run SQL Query">
                        <i class="fas fa-code"></i>
                        <!-- <span>SQL Query</span> -->
                    </button>
                    <?php if ($selectedDatabase && $selectedTable): ?>
                        <button onclick="refreshTable()" class="px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center gap-2" title="Refresh Table">
                            <i class="fas fa-sync-alt"></i>
                            <!-- <span>Refresh</span> -->
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/views/table_view.php'; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/views/modals.php'; ?>

<script>
    // Pass PHP variables to JavaScript
    window.database = <?php echo json_encode($selectedDatabase); ?>;
    window.table = <?php echo json_encode($selectedTable); ?>;
    window.primaryKey = <?php echo json_encode($primaryKey); ?>;
</script>

<?php include __DIR__ . '/views/footer.php'; ?>
