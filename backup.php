<?php
/**
 * Backup & Restore Management Page
 */
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/ServerManager.php';
require_once __DIR__ . '/includes/DatabaseBackup.php';
require_once __DIR__ . '/includes/DatabaseOperations.php';

Auth::require();

$db      = Database::getInstance();
$dbOps   = new DatabaseOperations($db->getConnection());
$backup  = new DatabaseBackup($db->getConnection());

$databases = $dbOps->getDatabases();
$backups   = $backup->listBackups();

$currentServer  = ServerManager::getCurrentId();
$servers        = ServerManager::getServers();

include __DIR__ . '/views/header.php';
?>

<div class="flex h-screen">
    <?php
    // Sidebar needs these variables
    $selectedDatabase = null;
    $selectedTable    = null;
    $tables = [];
    include __DIR__ . '/views/sidebar.php';
    ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Top Bar -->
        <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-archive text-orange-600"></i> Backup Manager
                </h2>
                <p class="text-sm text-gray-500 mt-1">Create and restore database backups</p>
            </div>
            <a href="index.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm transition">
                <i class="fas fa-arrow-left"></i> Back to Manager
            </a>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">

            <!-- ========== CREATE BACKUP ========== -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-orange-50 to-amber-50">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-orange-600"></i> Create New Backup
                    </h3>
                </div>
                <div class="p-6">
                    <?php if (!Auth::isAdmin()): ?>
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-sm flex items-center gap-2">
                            <i class="fas fa-lock"></i> Creating backups requires admin privileges.
                        </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Database selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-database text-blue-600 mr-1"></i> Database
                            </label>
                            <select id="backup-db" onchange="loadBackupTables()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                                <option value="">-- Select database --</option>
                                <?php foreach ($databases as $dbName): ?>
                                    <option value="<?php echo htmlspecialchars($dbName); ?>">
                                        <?php echo htmlspecialchars($dbName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Scope -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-layer-group text-purple-600 mr-1"></i> Scope
                            </label>
                            <div class="flex gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="backup-scope" value="full" checked onchange="toggleTablePicker()" class="accent-orange-500">
                                    <span class="text-sm text-gray-700">Full database</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="backup-scope" value="partial" onchange="toggleTablePicker()" class="accent-orange-500">
                                    <span class="text-sm text-gray-700">Selected tables</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Table picker (hidden by default) -->
                    <div id="table-picker" class="hidden mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-table text-green-600 mr-1"></i> Tables to backup
                        </label>
                        <div id="table-list" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50 min-h-[60px]">
                            <p class="text-gray-400 text-sm col-span-full">Select a database first</p>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <button onclick="selectAllTables(true)" class="text-xs text-blue-600 hover:underline">Select all</button>
                            <span class="text-gray-300">|</span>
                            <button onclick="selectAllTables(false)" class="text-xs text-red-600 hover:underline">Deselect all</button>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-3">
                        <button onclick="createBackup()" id="btn-create-backup"
                                class="flex items-center gap-2 px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-medium transition">
                            <i class="fas fa-download"></i> Create Backup
                        </button>
                        <span id="backup-status" class="text-sm text-gray-500"></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========== RESTORE BACKUP ========== -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-green-50 to-emerald-50">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-undo text-green-600"></i> Restore Backup
                    </h3>
                </div>
                <div class="p-6">
                    <?php if (!Auth::isAdmin()): ?>
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-sm flex items-center gap-2">
                            <i class="fas fa-lock"></i> Restoring backups requires admin privileges.
                        </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-database text-blue-600 mr-1"></i> Target Database
                            </label>
                            <select id="restore-db" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                                <option value="">-- Select or type database --</option>
                                <?php foreach ($databases as $dbName): ?>
                                    <option value="<?php echo htmlspecialchars($dbName); ?>">
                                        <?php echo htmlspecialchars($dbName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" id="restore-db-custom" placeholder="Or type new database name"
                                   class="mt-2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-file-import text-purple-600 mr-1"></i> Source
                            </label>
                            <div class="flex gap-3 mb-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="restore-source" value="upload" checked onchange="toggleRestoreSource()" class="accent-green-500">
                                    <span class="text-sm text-gray-700">Upload SQL file</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="restore-source" value="stored" onchange="toggleRestoreSource()" class="accent-green-500">
                                    <span class="text-sm text-gray-700">Existing backup</span>
                                </label>
                            </div>

                            <!-- Upload -->
                            <div id="restore-upload">
                                <label class="flex items-center justify-center w-full h-24 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-green-400 hover:bg-green-50 transition">
                                    <div class="text-center">
                                        <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-1"></i>
                                        <p class="text-sm text-gray-500">Click to select .sql file</p>
                                    </div>
                                    <input type="file" id="sql-file" accept=".sql" class="hidden" onchange="onFileSelected(this)">
                                </label>
                                <p id="selected-file" class="text-xs text-gray-500 mt-1"></p>
                            </div>

                            <!-- Stored backup selection -->
                            <div id="restore-stored" class="hidden">
                                <select id="stored-backup" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                                    <option value="">-- Select a backup --</option>
                                    <?php foreach ($backups as $b): ?>
                                        <option value="<?php echo htmlspecialchars($b['filename']); ?>">
                                            <?php echo htmlspecialchars($b['filename']); ?> (<?php echo $b['size_str']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-xs flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <span>Restoring will execute all SQL statements in the backup. Existing tables may be dropped and recreated. This action cannot be undone.</span>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <button onclick="restoreBackup()" id="btn-restore"
                                class="flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                            <i class="fas fa-undo"></i> Restore
                        </button>
                        <span id="restore-status" class="text-sm text-gray-500"></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========== BACKUP LIST ========== -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-list text-blue-600"></i> Saved Backups
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full"><?php echo count($backups); ?></span>
                    </h3>
                    <button onclick="refreshBackupList()" class="text-sm text-gray-500 hover:text-blue-600 flex items-center gap-1">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>

                <div id="backup-table-wrap">
                    <?php if (empty($backups)): ?>
                        <div class="p-8 text-center text-gray-400">
                            <i class="fas fa-archive text-4xl mb-3 block"></i>
                            <p>No backups yet. Create your first backup above.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">File</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Database</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Scope</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Size</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Created</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="backup-rows" class="divide-y divide-gray-100">
                                    <?php foreach ($backups as $b): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-3 font-mono text-xs text-gray-700 max-w-xs truncate" title="<?php echo htmlspecialchars($b['filename']); ?>">
                                                <i class="fas fa-file-code text-orange-400 mr-1"></i>
                                                <?php echo htmlspecialchars($b['filename']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700">
                                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-medium">
                                                    <?php echo htmlspecialchars($b['database']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="<?php echo $b['scope'] === 'full' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800'; ?> px-2 py-0.5 rounded text-xs font-medium">
                                                    <?php echo htmlspecialchars($b['scope']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 text-xs"><?php echo $b['size_str']; ?></td>
                                            <td class="px-4 py-3 text-gray-500 text-xs"><?php echo $b['created']; ?></td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="handlers/backup_handler.php?action=download&file=<?php echo urlencode($b['filename']); ?>"
                                                       class="p-1.5 rounded text-blue-600 hover:bg-blue-50 transition" title="Download">
                                                        <i class="fas fa-download text-xs"></i>
                                                    </a>
                                                    <?php if (Auth::isAdmin()): ?>
                                                    <button onclick="deleteBackup('<?php echo htmlspecialchars($b['filename']); ?>')"
                                                            class="p-1.5 rounded text-red-500 hover:bg-red-50 transition" title="Delete">
                                                        <i class="fas fa-trash text-xs"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /overflow-y-auto -->
    </div><!-- /main content -->
</div><!-- /flex -->

<?php include __DIR__ . '/views/modals.php'; ?>

<script>
// -----------------------------------------------------------------------
// TABLE PICKER
// -----------------------------------------------------------------------
function toggleTablePicker() {
    const scope = document.querySelector('[name=backup-scope]:checked').value;
    document.getElementById('table-picker').classList.toggle('hidden', scope !== 'partial');
}

async function loadBackupTables() {
    const db = document.getElementById('backup-db').value;
    const list = document.getElementById('table-list');
    if (!db) { list.innerHTML = '<p class="text-gray-400 text-sm col-span-full">Select a database first</p>'; return; }

    list.innerHTML = '<p class="text-gray-400 text-sm col-span-full animate-pulse">Loading tables…</p>';
    const res  = await fetch(`handlers/backup_handler.php?action=get_tables&database=${encodeURIComponent(db)}`);
    const data = await res.json();

    if (!data.success || !data.tables.length) {
        list.innerHTML = '<p class="text-gray-400 text-sm col-span-full">No tables found</p>';
        return;
    }
    list.innerHTML = data.tables.map(t => `
        <label class="flex items-center gap-2 cursor-pointer px-2 py-1 rounded hover:bg-white transition">
            <input type="checkbox" name="backup-table" value="${escHtml(t)}" class="accent-orange-500" checked>
            <span class="text-xs text-gray-700 font-mono">${escHtml(t)}</span>
        </label>`).join('');
}

function selectAllTables(val) {
    document.querySelectorAll('[name=backup-table]').forEach(cb => cb.checked = val);
}

// -----------------------------------------------------------------------
// CREATE BACKUP
// -----------------------------------------------------------------------
async function createBackup() {
    const db    = document.getElementById('backup-db').value;
    const scope = document.querySelector('[name=backup-scope]:checked').value;
    const btn   = document.getElementById('btn-create-backup');
    const status = document.getElementById('backup-status');

    if (!db) { showToast('Select a database first', 'error'); return; }

    const tables = scope === 'partial'
        ? [...document.querySelectorAll('[name=backup-table]:checked')].map(c => c.value)
        : [];

    if (scope === 'partial' && tables.length === 0) {
        showToast('Select at least one table', 'error'); return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating…';
    status.textContent = '';

    const fd = new FormData();
    fd.append('action', 'create');
    fd.append('database', db);
    tables.forEach(t => fd.append('tables[]', t));

    const res  = await fetch('handlers/backup_handler.php', { method: 'POST', body: fd });
    const data = await res.json();

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-download"></i> Create Backup';

    if (data.success) {
        showToast(`Backup created: ${data.filename}`, 'success');
        status.textContent = `Saved (${formatSize(data.size)})`;
        refreshBackupList();
    } else {
        showToast(data.message || 'Backup failed', 'error');
    }
}

// -----------------------------------------------------------------------
// RESTORE
// -----------------------------------------------------------------------
function toggleRestoreSource() {
    const src = document.querySelector('[name=restore-source]:checked').value;
    document.getElementById('restore-upload').classList.toggle('hidden', src !== 'upload');
    document.getElementById('restore-stored').classList.toggle('hidden', src !== 'stored');
}

function onFileSelected(input) {
    const p = document.getElementById('selected-file');
    p.textContent = input.files[0] ? `Selected: ${input.files[0].name}` : '';
}

async function restoreBackup() {
    const targetDb   = document.getElementById('restore-db-custom').value.trim()
                    || document.getElementById('restore-db').value;
    const source     = document.querySelector('[name=restore-source]:checked').value;
    const btn        = document.getElementById('btn-restore');
    const status     = document.getElementById('restore-status');

    if (!targetDb) { showToast('Enter or select a target database', 'error'); return; }

    if (!confirm(`⚠️ Restore into "${targetDb}"?\nThis may DROP existing tables. Continue?`)) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring…';
    status.textContent = '';

    const fd = new FormData();
    fd.append('action', 'restore');
    fd.append('database', targetDb);
    fd.append('source', source);

    if (source === 'stored') {
        fd.append('filename', document.getElementById('stored-backup').value);
    } else {
        const file = document.getElementById('sql-file').files[0];
        if (!file) { showToast('Select a .sql file', 'error'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-undo"></i> Restore'; return; }
        fd.append('sql_file', file);
    }

    const res  = await fetch('handlers/backup_handler.php', { method: 'POST', body: fd });
    const data = await res.json();

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-undo"></i> Restore';

    if (data.success) {
        showToast(data.message, 'success');
        status.textContent = 'Done!';
    } else {
        showToast(data.message || 'Restore failed', 'error');
        if (data.errors) console.error('Restore errors:', data.errors);
    }
}

// -----------------------------------------------------------------------
// BACKUP LIST
// -----------------------------------------------------------------------
async function refreshBackupList() {
    const wrap = document.getElementById('backup-table-wrap');
    wrap.innerHTML = '<div class="p-8 text-center text-gray-400 animate-pulse"><i class="fas fa-spinner fa-spin text-3xl"></i></div>';

    const res  = await fetch('handlers/backup_handler.php?action=list');
    const data = await res.json();

    if (!data.success || !data.backups.length) {
        wrap.innerHTML = '<div class="p-8 text-center text-gray-400"><i class="fas fa-archive text-4xl mb-3 block"></i><p>No backups yet.</p></div>';
        return;
    }

    const rows = data.backups.map(b => `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-4 py-3 font-mono text-xs text-gray-700 max-w-xs truncate">
                <i class="fas fa-file-code text-orange-400 mr-1"></i>${escHtml(b.filename)}
            </td>
            <td class="px-4 py-3">
                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-medium">${escHtml(b.database)}</span>
            </td>
            <td class="px-4 py-3">
                <span class="${b.scope === 'full' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800'} px-2 py-0.5 rounded text-xs font-medium">${escHtml(b.scope)}</span>
            </td>
            <td class="px-4 py-3 text-gray-600 text-xs">${escHtml(b.size_str)}</td>
            <td class="px-4 py-3 text-gray-500 text-xs">${escHtml(b.created)}</td>
            <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                    <a href="handlers/backup_handler.php?action=download&file=${encodeURIComponent(b.filename)}"
                       class="p-1.5 rounded text-blue-600 hover:bg-blue-50" title="Download">
                        <i class="fas fa-download text-xs"></i>
                    </a>
                    <button onclick="deleteBackup('${escHtml(b.filename)}')"
                            class="p-1.5 rounded text-red-500 hover:bg-red-50" title="Delete">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
            </td>
        </tr>`).join('');

    wrap.innerHTML = `<div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">File</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Database</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Scope</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Size</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Created</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">${rows}</tbody>
        </table>
    </div>`;
}

async function deleteBackup(filename) {
    if (!confirm(`Delete "${filename}"?`)) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('filename', filename);
    const res  = await fetch('handlers/backup_handler.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { showToast('Backup deleted', 'success'); refreshBackupList(); }
    else showToast(data.message, 'error');
}

// -----------------------------------------------------------------------
// UTILS
// -----------------------------------------------------------------------
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatSize(bytes) {
    if (bytes >= 1048576) return (bytes/1048576).toFixed(2)+' MB';
    if (bytes >= 1024)    return (bytes/1024).toFixed(2)+' KB';
    return bytes+' B';
}
function showToast(msg, type='success') {
    const c = document.getElementById('toast-container');
    const t = document.createElement('div');
    const bg = type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600';
    t.className = `toast ${bg} text-white px-4 py-3 rounded-lg shadow-lg text-sm flex items-center gap-2 max-w-sm`;
    t.innerHTML = `<i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i><span>${escHtml(msg)}</span>`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 5000);
}
</script>

<?php include __DIR__ . '/views/footer.php'; ?>
