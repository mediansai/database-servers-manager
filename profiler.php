<?php
/**
 * Query Profiler
 * Execution time, rows scanned, indexes used, slow queries, EXPLAIN visualization.
 */

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/ServerManager.php';
require_once __DIR__ . '/includes/DatabaseOperations.php';

Auth::require();

$connectionError = null;
$databases = [];
$selectedDatabase = $_GET['database'] ?? null;

try {
    $db    = Database::getInstance();
    $dbOps = new DatabaseOperations($db->getConnection());
    $databases = $dbOps->getDatabases();

    if (!$selectedDatabase && $databases) {
        $selectedDatabase = $databases[0];
    }
} catch (\Exception $e) {
    $connectionError = $e->getMessage();
}

include __DIR__ . '/views/header.php';
?>

<?php if ($connectionError): ?>
<div class="flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-2xl shadow-xl p-10 max-w-lg w-full text-center">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-plug text-red-500 text-2xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Connection Failed</h2>
        <p class="text-red-400 text-xs font-mono bg-red-50 rounded p-3 mt-3 mb-6 text-left break-all"><?php echo htmlspecialchars($connectionError); ?></p>
        <a href="index.php" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">Back to Database Manager</a>
    </div>
</div>
<?php else: ?>
<div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="w-64 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-4 border-b border-gray-200">
            <a href="index.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-800 mb-4">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Database Manager</span>
            </a>
            <h1 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-tachometer-alt text-indigo-600"></i>
                Query Profiler
            </h1>
            <p class="text-sm text-gray-500 mt-1">Execution time, scans & EXPLAIN</p>
        </div>

        <div class="p-4 border-b border-gray-200">
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Database</label>
            <select id="profiler-database" onchange="onProfilerDatabaseChange()"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <?php foreach ($databases as $db_name): ?>
                    <option value="<?php echo htmlspecialchars($db_name); ?>" <?php echo $db_name === $selectedDatabase ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($db_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <nav class="flex-1 overflow-y-auto p-3 space-y-1">
            <button onclick="showProfilerTab('run')" data-tab="run" class="profiler-tab-btn w-full text-left px-3 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 bg-indigo-50 text-indigo-700">
                <i class="fas fa-play-circle"></i> Profile a Query
            </button>
            <button onclick="showProfilerTab('slow')" data-tab="slow" class="profiler-tab-btn w-full text-left px-3 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 text-gray-600 hover:bg-gray-50">
                <i class="fas fa-hourglass-half"></i> Slow Queries
            </button>
            <button onclick="showProfilerTab('history')" data-tab="history" class="profiler-tab-btn w-full text-left px-3 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 text-gray-600 hover:bg-gray-50">
                <i class="fas fa-history"></i> History
            </button>
        </nav>
    </div>

    <!-- Main content -->
    <div class="flex-1 overflow-y-auto">

        <!-- Tab: Profile a Query -->
        <section id="profiler-tab-run" class="profiler-tab p-6 max-w-6xl mx-auto">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">SQL query (SELECT only)</label>
                <textarea id="profiler-query" rows="5" spellcheck="false" placeholder="SELECT * FROM orders WHERE customer_id = 42"
                    class="w-full font-mono text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                <div class="flex items-center justify-between mt-3">
                    <p class="text-xs text-gray-400">EXPLAIN and a live execution both run against the selected database.</p>
                    <div class="flex gap-2">
                        <button onclick="document.getElementById('profiler-query').value=''" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Clear</button>
                        <button onclick="runProfiledQuery()" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium flex items-center gap-2">
                            <i class="fas fa-play"></i> Run & Profile
                        </button>
                    </div>
                </div>
            </div>

            <div id="profiler-run-results"></div>
        </section>

        <!-- Tab: Slow Queries -->
        <section id="profiler-tab-slow" class="profiler-tab p-6 max-w-6xl mx-auto hidden">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Slow Queries</h2>
                <button onclick="loadSlowQueries()" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm text-gray-700 flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div id="profiler-slow-results">
                <div class="flex items-center justify-center py-12"><div class="loader"></div></div>
            </div>
        </section>

        <!-- Tab: History -->
        <section id="profiler-tab-history" class="profiler-tab p-6 max-w-6xl mx-auto hidden">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Recent Profiled Queries</h2>
                <button onclick="clearProfilerHistory()" class="px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-trash-alt"></i> Clear History
                </button>
            </div>
            <div id="profiler-history-results">
                <div class="flex items-center justify-center py-12"><div class="loader"></div></div>
            </div>
        </section>
    </div>
</div>
<?php endif; ?>

<script src="assets/js/profiler.js"></script>
<?php include __DIR__ . '/views/footer.php'; ?>
