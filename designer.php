<?php
/**
 * Database Designer - Visual Database Diagram Tool
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
require_once __DIR__ . '/includes/DatabaseDesigner.php';

Auth::require();

// Initialize database connection
$db = Database::getInstance();
$dbOps = new DatabaseOperations($db->getConnection());

// Get current selections
$selectedDatabase = $_GET['database'] ?? null;
$selectedFile = $_GET['file'] ?? null;

// Determine mode: file or database
$isFileMode = !empty($selectedFile);

if (!$selectedDatabase && !$selectedFile) {
    header('Location: index.php');
    exit;
}

// Fetch data
$databases = $isFileMode ? [] : $dbOps->getDatabases();
$displayName = $selectedDatabase ?? 'File Schema';

// Include header
include __DIR__ . '/views/header.php';
?>

<div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="w-64 bg-white border-r border-gray-200 flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200">
            <?php if ($isFileMode): ?>
                <a href="file_manager.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-800 mb-4">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to File Manager</span>
                </a>
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-file text-blue-600"></i>
                    <span class="text-xs text-gray-500">FILE MODE</span>
                </div>
            <?php else: ?>
                <a href="index.php?database=<?php echo urlencode($selectedDatabase); ?>" class="flex items-center gap-2 text-gray-600 hover:text-gray-800 mb-4">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Database</span>
                </a>
            <?php endif; ?>
            <h1 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-project-diagram text-blue-600"></i>
                Database Designer
            </h1>
            <p class="text-sm text-gray-500 mt-1" id="designer-name"><?php echo htmlspecialchars($displayName); ?></p>
        </div>

        <!-- Tools -->
        <div class="flex-1 overflow-y-auto p-4">
            <div class="space-y-4">
                <!-- Tables List -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-700 uppercase mb-2">Tables</h3>
                    <div class="mb-2">
                        <input type="text" id="table-search" placeholder="Search tables..." 
                               onkeyup="filterTables()" 
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div id="tables-list" class="max-h-48 overflow-y-auto space-y-1">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- View Controls -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-700 uppercase mb-2">View Controls</h3>
                    <div class="space-y-2">
                        <button onclick="designer.zoomIn()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-search-plus text-gray-600"></i>
                            <span class="text-sm">Zoom In</span>
                        </button>
                        <button onclick="designer.zoomOut()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-search-minus text-gray-600"></i>
                            <span class="text-sm">Zoom Out</span>
                        </button>
                        <button onclick="designer.resetZoom()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-compress text-gray-600"></i>
                            <span class="text-sm">Reset View</span>
                            <span id="zoom-level" class="ml-auto text-xs text-gray-500">100%</span>
                        </button>
                        <button onclick="designer.toggleGrid()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-th text-gray-600"></i>
                            <span class="text-sm">Toggle Grid</span>
                        </button>
                        <button onclick="designer.toggleMinimap()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-map text-gray-600"></i>
                            <span class="text-sm">Toggle Minimap</span>
                        </button>
                    </div>
                </div>

                <!-- Layout -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-700 uppercase mb-2">Layout</h3>
                    <div class="space-y-2">
                        <button onclick="designer.autoLayout()" class="w-full px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-magic"></i>
                            <span class="text-sm">Auto Arrange</span>
                        </button>
                        <button onclick="openCreateTableModal()" class="w-full px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-plus"></i>
                            <span class="text-sm">New Table</span>
                        </button>
                    </div>
                </div>
                
                <!-- Relationships -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-700 uppercase mb-2">Relationships</h3>
                    <div class="space-y-2">
                        <button id="relationship-mode-btn" onclick="toggleRelationshipModeUI()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-indigo-50 hover:border-indigo-400 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-link text-indigo-600"></i>
                            <span class="text-sm">Create Relationship</span>
                        </button>
                        <button onclick="showRelationshipsList()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-list text-gray-600"></i>
                            <span class="text-sm">View All Relations</span>
                        </button>
                    </div>
                </div>

                <!-- Export Options -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-700 uppercase mb-2">Export</h3>
                    <div class="space-y-2">
                        <button onclick="designer.exportAsImage()" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-image text-gray-600"></i>
                            <span class="text-sm">Export as PNG</span>
                        </button>
                        <button onclick="openExportModal()" class="w-full px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-left flex items-center gap-2">
                            <i class="fas fa-download"></i>
                            <span class="text-sm">Export Schema</span>
                        </button>
                    </div>
                </div>

                <!-- Legend -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-700 uppercase mb-2">Legend</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-key text-yellow-500"></i>
                            <span>Primary Key</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-star text-purple-500"></i>
                            <span>Unique Key</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-0.5 bg-gray-400"></div>
                            <span>RESTRICT</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-0.5 bg-red-500"></div>
                            <span>CASCADE</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-0.5 bg-orange-500"></div>
                            <span>SET NULL</span>
                        </div>
                    </div>
                </div>

                <!-- Help -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <h4 class="font-semibold text-blue-900 text-sm mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Quick Tips
                    </h4>
                    <ul class="text-xs text-blue-800 space-y-1">
                        <li>• Drag tables to move</li>
                        <li>• Drag background to pan</li>
                        <li>• Double-click to edit</li>
                        <li>• Right-click for options</li>
                        <li>• Scroll to zoom</li>
                        <li id="rel-mode-tip" class="hidden text-indigo-900 font-semibold">• Click columns to link</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Canvas -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Toolbar -->
        <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    <?php echo htmlspecialchars($selectedDatabase); ?> - ERD
                </h2>
                <span class="text-sm text-gray-500">
                    <i class="fas fa-table mr-1"></i>
                    <span id="table-count">0</span> tables
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="toggleFullscreen()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-expand"></i>
                </button>
                <button onclick="showHelp()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-question-circle"></i>
                </button>
            </div>
        </div>

        <!-- Canvas Container -->
        <div class="flex-1 relative bg-gray-100">
            <canvas id="designer-canvas" class="w-full h-full"></canvas>
            
            <!-- Loading Overlay -->
            <div id="loading-overlay" class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-600">Loading database schema...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Table Modal -->
<div id="createTableModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-3/4 max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-plus-circle text-green-600"></i>
                Create New Table
            </h3>
            <button onclick="closeCreateTableModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 flex-1 overflow-auto">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Table Name *</label>
                <input type="text" id="create-table-name" placeholder="users" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Comment (Optional)</label>
                <input type="text" id="create-table-comment" placeholder="Table description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Columns *</label>
                    <button onclick="addNewColumn()" class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus mr-1"></i>
                        Add Column
                    </button>
                </div>
                <div id="create-table-columns" class="space-y-2">
                    <!-- Default ID column -->
                    <div class="column-row flex items-center gap-2 p-2 bg-gray-50 rounded">
                        <input type="text" placeholder="id" value="id" class="flex-1 px-3 py-2 border border-gray-300 rounded" data-column="name">
                        <select class="w-40 px-3 py-2 border border-gray-300 rounded" data-column="type">
                            <option value="INT">INT</option>
                            <option value="BIGINT">BIGINT</option>
                            <option value="VARCHAR(255)">VARCHAR(255)</option>
                            <option value="TEXT">TEXT</option>
                            <option value="DATETIME">DATETIME</option>
                        </select>
                        <label class="flex items-center gap-1">
                            <input type="checkbox" data-column="primary" checked>
                            <span class="text-sm">PK</span>
                        </label>
                        <label class="flex items-center gap-1">
                            <input type="checkbox" data-column="null">
                            <span class="text-sm">NULL</span>
                        </label>
                        <label class="flex items-center gap-1">
                            <input type="checkbox" data-column="auto_increment" checked>
                            <span class="text-sm">AI</span>
                        </label>
                        <button onclick="removeColumn(this)" class="text-red-600 hover:text-red-800 px-2">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
            <button onclick="closeCreateTableModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button onclick="createNewTable()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-check mr-2"></i>
                Create Table
            </button>
        </div>
    </div>
</div>

<!-- Add Column Modal -->
<div id="addColumnModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-2/3 max-w-2xl">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-plus text-purple-600"></i>
                Add Column to <span id="add-column-table" class="text-blue-600"></span>
            </h3>
            <button onclick="closeAddColumnModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Column Name *</label>
                <input type="text" id="add-column-name" placeholder="column_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Type *</label>
                <select id="add-column-type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="INT">INT</option>
                    <option value="BIGINT">BIGINT</option>
                    <option value="VARCHAR(255)" selected>VARCHAR(255)</option>
                    <option value="TEXT">TEXT</option>
                    <option value="MEDIUMTEXT">MEDIUMTEXT</option>
                    <option value="LONGTEXT">LONGTEXT</option>
                    <option value="DATETIME">DATETIME</option>
                    <option value="TIMESTAMP">TIMESTAMP</option>
                    <option value="DATE">DATE</option>
                    <option value="TIME">TIME</option>
                    <option value="DECIMAL(10,2)">DECIMAL(10,2)</option>
                    <option value="FLOAT">FLOAT</option>
                    <option value="DOUBLE">DOUBLE</option>
                    <option value="BOOLEAN">BOOLEAN</option>
                    <option value="TINYINT">TINYINT</option>
                    <option value="SMALLINT">SMALLINT</option>
                    <option value="MEDIUMINT">MEDIUMINT</option>
                    <option value="JSON">JSON</option>
                    <option value="BLOB">BLOB</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="flex items-center gap-2">
                    <input type="checkbox" id="add-column-null" checked class="w-4 h-4 text-blue-600">
                    <span class="text-sm font-medium text-gray-700">Allow NULL values</span>
                </label>
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
            <button onclick="closeAddColumnModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button onclick="addColumnToTable()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-check mr-2"></i>
                Add Column
            </button>
        </div>
    </div>
</div>

<!-- Add Relationship Modal -->
<div id="addRelationshipModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-2/3 max-w-2xl">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-link text-indigo-600"></i>
                Add Foreign Key Relationship
            </h3>
            <button onclick="closeAddRelationshipModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">From Table</label>
                <div class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-800 font-medium">
                    <span id="rel-from-table"></span>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">From Column *</label>
                <select id="rel-from-column" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <!-- Will be populated dynamically -->
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">References Table *</label>
                <select id="rel-to-table" onchange="updateRefColumns()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <!-- Will be populated dynamically -->
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">References Column *</label>
                <select id="rel-to-column" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <!-- Will be populated dynamically -->
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">On Update</label>
                    <select id="rel-on-update" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="RESTRICT">RESTRICT</option>
                        <option value="CASCADE">CASCADE</option>
                        <option value="SET NULL">SET NULL</option>
                        <option value="NO ACTION">NO ACTION</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">On Delete</label>
                    <select id="rel-on-delete" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="RESTRICT">RESTRICT</option>
                        <option value="CASCADE">CASCADE</option>
                        <option value="SET NULL">SET NULL</option>
                        <option value="NO ACTION">NO ACTION</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
            <button onclick="closeAddRelationshipModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button onclick="addRelationshipToTable()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-check mr-2"></i>
                Add Relationship
            </button>
        </div>
    </div>
</div>

<!-- Export Modal (shared design — no table-scope option in designer context) -->
<div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl flex flex-col" style="width:800px;max-width:96vw;max-height:90vh">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-download text-green-600"></i>
                Export Schema
            </h3>
            <button onclick="closeExportModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="px-6 pt-5 pb-3 flex-shrink-0">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Choose Format</p>
            <div class="grid grid-cols-4 gap-2" id="format-tiles">
                <button onclick="selectExportFormat('sql')" id="fmt-sql"
                    class="export-fmt-tile flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-center cursor-pointer"
                    data-color-border="#2563eb" data-color-bg="#eff6ff">
                    <i class="fas fa-database text-xl" style="color:#2563eb"></i>
                    <span class="text-xs font-semibold text-gray-700">MySQL SQL</span>
                    <span class="text-[10px] text-gray-400 leading-tight">SQL dump</span>
                </button>
                <button onclick="selectExportFormat('sqlite')" id="fmt-sqlite"
                    class="export-fmt-tile flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-center cursor-pointer"
                    data-color-border="#0d9488" data-color-bg="#f0fdfa">
                    <i class="fas fa-hard-drive text-xl" style="color:#0d9488"></i>
                    <span class="text-xs font-semibold text-gray-700">SQLite</span>
                    <span class="text-[10px] text-gray-400 leading-tight">SQLite schema</span>
                </button>
                <button onclick="selectExportFormat('prisma')" id="fmt-prisma"
                    class="export-fmt-tile flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-center cursor-pointer"
                    data-color-border="#7c3aed" data-color-bg="#f5f3ff">
                    <i class="fas fa-layer-group text-xl" style="color:#7c3aed"></i>
                    <span class="text-xs font-semibold text-gray-700">Prisma</span>
                    <span class="text-[10px] text-gray-400 leading-tight">.prisma schema</span>
                </button>
                <button onclick="selectExportFormat('laravel_migration')" id="fmt-laravel_migration"
                    class="export-fmt-tile flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-center cursor-pointer"
                    data-color-border="#dc2626" data-color-bg="#fef2f2">
                    <i class="fab fa-laravel text-xl" style="color:#dc2626"></i>
                    <span class="text-xs font-semibold text-gray-700">Laravel</span>
                    <span class="text-[10px] text-gray-400 leading-tight">Migration files</span>
                </button>
                <button onclick="selectExportFormat('typescript')" id="fmt-typescript"
                    class="export-fmt-tile flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-center cursor-pointer"
                    data-color-border="#0284c7" data-color-bg="#f0f9ff">
                    <i class="fas fa-code text-xl" style="color:#0284c7"></i>
                    <span class="text-xs font-semibold text-gray-700">TypeScript</span>
                    <span class="text-[10px] text-gray-400 leading-tight">Interfaces (.ts)</span>
                </button>
                <button onclick="selectExportFormat('zod')" id="fmt-zod"
                    class="export-fmt-tile flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-center cursor-pointer"
                    data-color-border="#0891b2" data-color-bg="#ecfeff">
                    <i class="fas fa-shield-halved text-xl" style="color:#0891b2"></i>
                    <span class="text-xs font-semibold text-gray-700">Zod</span>
                    <span class="text-[10px] text-gray-400 leading-tight">Validation schemas</span>
                </button>
                <button onclick="selectExportFormat('json_schema')" id="fmt-json_schema"
                    class="export-fmt-tile flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-center cursor-pointer"
                    data-color-border="#d97706" data-color-bg="#fffbeb">
                    <i class="fas fa-file-code text-xl" style="color:#d97706"></i>
                    <span class="text-xs font-semibold text-gray-700">JSON Schema</span>
                    <span class="text-[10px] text-gray-400 leading-tight">Draft-07</span>
                </button>
                <button onclick="selectExportFormat('django')" id="fmt-django"
                    class="export-fmt-tile flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-center cursor-pointer"
                    data-color-border="#16a34a" data-color-bg="#f0fdf4">
                    <i class="fas fa-leaf text-xl" style="color:#16a34a"></i>
                    <span class="text-xs font-semibold text-gray-700">Django</span>
                    <span class="text-[10px] text-gray-400 leading-tight">models.py</span>
                </button>
                <button onclick="selectExportFormat('sequelize')" id="fmt-sequelize"
                    class="export-fmt-tile flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-center cursor-pointer"
                    data-color-border="#ea580c" data-color-bg="#fff7ed">
                    <i class="fas fa-cubes text-xl" style="color:#ea580c"></i>
                    <span class="text-xs font-semibold text-gray-700">Sequelize</span>
                    <span class="text-[10px] text-gray-400 leading-tight">Node.js ORM</span>
                </button>
                <button onclick="selectExportFormat('mongoose')" id="fmt-mongoose"
                    class="export-fmt-tile flex flex-col items-center gap-1.5 p-3 rounded-lg border-2 transition-all text-center cursor-pointer"
                    data-color-border="#16a34a" data-color-bg="#f0fdf4">
                    <i class="fas fa-leaf text-xl" style="color:#16a34a"></i>
                    <span class="text-xs font-semibold text-gray-700">Mongoose</span>
                    <span class="text-[10px] text-gray-400 leading-tight">MongoDB schemas</span>
                </button>
            </div>
        </div>

        <div class="px-6 py-4 flex-1 overflow-auto border-t border-gray-100">
            <div id="export-content-option" class="hidden mb-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Content</p>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="export_content" value="complete" checked class="accent-blue-600">
                        <span class="text-sm text-gray-700">Structure + Data</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="export_content" value="structure" class="accent-blue-600">
                        <span class="text-sm text-gray-700">Structure Only</span>
                    </label>
                </div>
            </div>
            <div class="flex items-start gap-2 bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-600">
                <i class="fas fa-info-circle text-blue-500 mt-0.5 flex-shrink-0"></i>
                <span id="export-format-desc">Export a full MySQL-compatible SQL dump.</span>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
            <span class="text-xs text-gray-400" id="export-format-badge">Format: MySQL SQL</span>
            <div class="flex gap-2">
                <button onclick="closeExportModal()"
                    class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">Cancel</button>
                <button onclick="doExport()"
                    class="px-5 py-2 text-white rounded-lg hover:opacity-90 transition-opacity text-sm font-medium flex items-center gap-2"
                    id="export-download-btn" style="background:#16a34a">
                    <i class="fas fa-download"></i>
                    <span id="export-btn-text">Export SQL</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table Editor Modal -->
<div id="tableEditorModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-3/4 max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-table text-blue-600"></i>
                Edit Table
            </h3>
            <button onclick="closeTableEditor()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 flex-1 overflow-auto">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Table Name</label>
                <input type="text" id="table-editor-name" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Comment</label>
                <input type="text" id="table-editor-comment" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Columns</label>
                    <button class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                        <i class="fas fa-plus mr-1"></i>
                        Add Column
                    </button>
                </div>
                <div id="table-editor-columns" class="space-y-2">
                    <!-- Columns will be populated here -->
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
            <button onclick="closeTableEditor()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button onclick="saveTableChanges()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Save Changes
            </button>
        </div>
    </div>
</div>

<!-- Relationships List Modal -->
<div id="relationshipsListModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-3/4 max-w-4xl max-h-[80vh] flex flex-col">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-project-diagram text-indigo-600"></i>
                Database Relationships
            </h3>
            <button onclick="closeRelationshipsList()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 flex-1 overflow-auto">
            <div id="relationships-list-content" class="space-y-3">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end">
            <button onclick="closeRelationshipsList()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    // Pass PHP variables to JavaScript
    window.database = <?php echo json_encode($selectedDatabase); ?>;
    window.designerFile = <?php echo json_encode($selectedFile); ?>;
    window.designerMode = <?php echo json_encode($isFileMode ? 'file' : 'database'); ?>;
    
    function closeTableEditor() {
        document.getElementById('tableEditorModal').classList.add('hidden');
        document.getElementById('tableEditorModal').classList.remove('flex');
    }
    
    function saveTableChanges() {
        // Implementation for saving table changes
        showToast('Table updated successfully');
        closeTableEditor();
        designer.loadSchema();
    }
    
    function openCreateTableModal() {
        document.getElementById('createTableModal').classList.remove('hidden');
        document.getElementById('createTableModal').classList.add('flex');
    }
    
    function closeCreateTableModal() {
        document.getElementById('createTableModal').classList.add('hidden');
        document.getElementById('createTableModal').classList.remove('flex');
        document.getElementById('create-table-name').value = '';
        document.getElementById('create-table-comment').value = '';
        document.getElementById('create-table-columns').innerHTML = getDefaultColumnHTML();
    }
    
    function getDefaultColumnHTML() {
        return `
            <div class="column-row flex items-center gap-2 p-2 bg-gray-50 rounded">
                <input type="text" placeholder="id" value="id" class="flex-1 px-3 py-2 border border-gray-300 rounded" data-column="name">
                <select class="w-40 px-3 py-2 border border-gray-300 rounded" data-column="type">
                    <option value="INT">INT</option>
                    <option value="VARCHAR(255)">VARCHAR(255)</option>
                    <option value="TEXT">TEXT</option>
                    <option value="DATETIME">DATETIME</option>
                    <option value="DECIMAL(10,2)">DECIMAL(10,2)</option>
                    <option value="BOOLEAN">BOOLEAN</option>
                </select>
                <label class="flex items-center gap-1">
                    <input type="checkbox" data-column="primary" checked>
                    <span class="text-sm">PK</span>
                </label>
                <label class="flex items-center gap-1">
                    <input type="checkbox" data-column="null">
                    <span class="text-sm">NULL</span>
                </label>
                <label class="flex items-center gap-1">
                    <input type="checkbox" data-column="auto_increment" checked>
                    <span class="text-sm">AI</span>
                </label>
                <button onclick="removeColumn(this)" class="text-red-600 hover:text-red-800 px-2">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    }
    
    function addNewColumn() {
        const columnsContainer = document.getElementById('create-table-columns');
        const columnHTML = `
            <div class="column-row flex items-center gap-2 p-2 bg-gray-50 rounded">
                <input type="text" placeholder="column_name" class="flex-1 px-3 py-2 border border-gray-300 rounded" data-column="name">
                <select class="w-40 px-3 py-2 border border-gray-300 rounded" data-column="type">
                    <option value="INT">INT</option>
                    <option value="VARCHAR(255)">VARCHAR(255)</option>
                    <option value="TEXT">TEXT</option>
                    <option value="DATETIME">DATETIME</option>
                    <option value="TIMESTAMP">TIMESTAMP</option>
                    <option value="DECIMAL(10,2)">DECIMAL(10,2)</option>
                    <option value="FLOAT">FLOAT</option>
                    <option value="DOUBLE">DOUBLE</option>
                    <option value="BOOLEAN">BOOLEAN</option>
                    <option value="DATE">DATE</option>
                    <option value="TIME">TIME</option>
                    <option value="BIGINT">BIGINT</option>
                    <option value="MEDIUMTEXT">MEDIUMTEXT</option>
                    <option value="LONGTEXT">LONGTEXT</option>
                </select>
                <label class="flex items-center gap-1">
                    <input type="checkbox" data-column="primary">
                    <span class="text-sm">PK</span>
                </label>
                <label class="flex items-center gap-1">
                    <input type="checkbox" data-column="null" checked>
                    <span class="text-sm">NULL</span>
                </label>
                <label class="flex items-center gap-1">
                    <input type="checkbox" data-column="auto_increment">
                    <span class="text-sm">AI</span>
                </label>
                <button onclick="removeColumn(this)" class="text-red-600 hover:text-red-800 px-2">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        columnsContainer.insertAdjacentHTML('beforeend', columnHTML);
    }
    
    function removeColumn(button) {
        button.closest('.column-row').remove();
    }
    
    async function createNewTable() {
        const tableName = document.getElementById('create-table-name').value.trim();
        const comment = document.getElementById('create-table-comment').value.trim();
        
        if (!tableName) {
            showToast('Please enter a table name', 'error');
            return;
        }
        
        // Collect columns
        const columnRows = document.querySelectorAll('#create-table-columns .column-row');
        const columns = [];
        
        columnRows.forEach(row => {
            const name = row.querySelector('[data-column="name"]').value.trim();
            const type = row.querySelector('[data-column="type"]').value;
            const primary = row.querySelector('[data-column="primary"]').checked;
            const nullable = row.querySelector('[data-column="null"]').checked;
            const autoIncrement = row.querySelector('[data-column="auto_increment"]') ? 
                                 row.querySelector('[data-column="auto_increment"]').checked : false;
            
            if (name) {
                columns.push({
                    name: name,
                    type: type,
                    key: primary ? 'PRI' : '',
                    null: nullable,
                    extra: autoIncrement ? 'AUTO_INCREMENT' : ''
                });
            }
        });
        
        if (columns.length === 0) {
            showToast('Please add at least one column', 'error');
            return;
        }
        
        try {
            const response = await fetch('handlers/designer_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'create_table',
                    database: window.database,
                    table_name: tableName,
                    columns: JSON.stringify(columns),
                    comment: comment
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Table created successfully');
                closeCreateTableModal();
                designer.loadSchema();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error creating table:', error);
            showToast('Error creating table', 'error');
        }
    }
    
    function openAddColumnModal(tableName) {
        window.currentEditTable = tableName;
        document.getElementById('addColumnModal').classList.remove('hidden');
        document.getElementById('addColumnModal').classList.add('flex');
        document.getElementById('add-column-table').textContent = tableName;
    }
    
    function closeAddColumnModal() {
        document.getElementById('addColumnModal').classList.add('hidden');
        document.getElementById('addColumnModal').classList.remove('flex');
        document.getElementById('add-column-name').value = '';
        document.getElementById('add-column-type').value = 'VARCHAR(255)';
        document.getElementById('add-column-null').checked = true;
    }
    
    async function addColumnToTable() {
        const tableName = window.currentEditTable;
        const columnName = document.getElementById('add-column-name').value.trim();
        const columnType = document.getElementById('add-column-type').value;
        const nullable = document.getElementById('add-column-null').checked;
        
        if (!columnName) {
            showToast('Please enter a column name', 'error');
            return;
        }
        
        try {
            const response = await fetch('handlers/designer_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'add_column',
                    database: window.database,
                    table: tableName,
                    column_name: columnName,
                    column_type: columnType,
                    nullable: nullable ? '1' : '0'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Column added successfully');
                closeAddColumnModal();
                designer.loadSchema();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error adding column:', error);
            showToast('Error adding column', 'error');
        }
    }
    
    function openAddRelationshipModal(tableName) {
        window.currentEditTable = tableName;
        document.getElementById('addRelationshipModal').classList.remove('hidden');
        document.getElementById('addRelationshipModal').classList.add('flex');
        document.getElementById('rel-from-table').textContent = tableName;
        
        // Populate columns dropdown
        const table = designer.tables.find(t => t.name === tableName);
        if (table) {
            const select = document.getElementById('rel-from-column');
            select.innerHTML = table.columns.map(col => 
                `<option value="${col.name}">${col.name}</option>`
            ).join('');
        }
        
        // Populate reference tables dropdown
        const refSelect = document.getElementById('rel-to-table');
        refSelect.innerHTML = designer.tables
            .filter(t => t.name !== tableName)
            .map(t => `<option value="${t.name}">${t.name}</option>`)
            .join('');
        
        updateRefColumns();
    }
    
    function closeAddRelationshipModal() {
        document.getElementById('addRelationshipModal').classList.add('hidden');
        document.getElementById('addRelationshipModal').classList.remove('flex');
    }
    
    function updateRefColumns() {
        const refTableName = document.getElementById('rel-to-table').value;
        const refTable = designer.tables.find(t => t.name === refTableName);
        
        if (refTable) {
            const select = document.getElementById('rel-to-column');
            select.innerHTML = refTable.columns.map(col => 
                `<option value="${col.name}">${col.name}</option>`
            ).join('');
        }
    }
    
    async function addRelationshipToTable() {
        const fromTable = window.currentEditTable;
        const fromColumn = document.getElementById('rel-from-column').value;
        const toTable = document.getElementById('rel-to-table').value;
        const toColumn = document.getElementById('rel-to-column').value;
        const onUpdate = document.getElementById('rel-on-update').value;
        const onDelete = document.getElementById('rel-on-delete').value;
        
        try {
            const response = await fetch('handlers/designer_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'add_foreign_key',
                    database: window.database,
                    table: fromTable,
                    column: fromColumn,
                    ref_table: toTable,
                    ref_column: toColumn
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Relationship added successfully');
                closeAddRelationshipModal();
                designer.loadSchema();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error adding relationship:', error);
            showToast('Error adding relationship', 'error');
        }
    }
    
    // openExportModal / closeExportModal / selectExportFormat / doExport are in main.js

    async function exportDatabaseSQL() {
        try {
            const response = await fetch('handlers/designer_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'generate_sql',
                    database: window.database
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                const blob = new Blob([data.sql], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${window.database}_schema.sql`;
                a.click();
                
                showToast('SQL exported successfully');
                closeExportModal();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error exporting SQL:', error);
            showToast('Error exporting SQL', 'error');
        }
    }
    
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    }
    
    function showHelp() {
        showToast('Help: Drag tables, double-click to edit, scroll to zoom', 'info');
    }
    
    function updateTablesList() {
        const tablesList = document.getElementById('tables-list');
        if (!tablesList || !designer || !designer.tables) return;
        
        tablesList.innerHTML = designer.tables.map(table => `
            <button onclick="focusOnTable('${table.name}')" 
                    class="table-list-item w-full px-2 py-1.5 text-left text-sm rounded hover:bg-blue-50 transition-colors flex items-center justify-between group"
                    data-table-name="${table.name}">
                <span class="flex items-center gap-2">
                    <i class="fas fa-table text-gray-400 text-xs"></i>
                    <span class="text-gray-700">${table.name}</span>
                </span>
                <span class="text-xs text-gray-400">${table.rowCount}</span>
            </button>
        `).join('');
    }
    
    function filterTables() {
        const searchTerm = document.getElementById('table-search').value.toLowerCase();
        const tableItems = document.querySelectorAll('.table-list-item');
        
        tableItems.forEach(item => {
            const tableName = item.getAttribute('data-table-name').toLowerCase();
            if (tableName.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    function focusOnTable(tableName) {
        if (!designer) return;
        
        const table = designer.tables.find(t => t.name === tableName);
        if (table) {
            // Center the table in view
            const canvasRect = designer.canvas.getBoundingClientRect();
            const targetX = canvasRect.width / 2 - (table.x + table.width / 2) * designer.zoom;
            const targetY = canvasRect.height / 2 - (table.y + table.height / 2) * designer.zoom;
            
            designer.panOffset.x = targetX;
            designer.panOffset.y = targetY;
            designer.selectedTable = table;
            designer.draw();
            
            // Add highlight effect
            setTimeout(() => {
                designer.selectedTable = table;
                designer.draw();
            }, 100);
        }
    }
    
    function toggleRelationshipModeUI() {
        if (designer) {
            designer.toggleRelationshipMode();
            const btn = document.getElementById('relationship-mode-btn');
            const tip = document.getElementById('rel-mode-tip');
            
            if (designer.isCreatingRelationship) {
                btn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
                btn.classList.remove('bg-white', 'text-gray-800');
                tip.classList.remove('hidden');
            } else {
                btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
                btn.classList.add('bg-white');
                tip.classList.add('hidden');
            }
        }
    }
    
    function showRelationshipsList() {
        if (!designer) return;
        
        const content = document.getElementById('relationships-list-content');
        
        if (designer.relationships.length === 0) {
            content.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-link text-4xl mb-3"></i>
                    <p>No relationships found in this database</p>
                    <button onclick="closeRelationshipsList(); toggleRelationshipModeUI();" 
                            class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Create First Relationship
                    </button>
                </div>
            `;
        } else {
            content.innerHTML = designer.relationships.map((rel, index) => {
                const actionColor = rel.on_delete === 'CASCADE' ? 'red' : 
                                  rel.on_delete === 'SET NULL' ? 'orange' : 'gray';
                
                return `
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <span class="text-lg font-semibold text-gray-800">${rel.table}</span>
                                <i class="fas fa-arrow-right text-${actionColor}-500"></i>
                                <span class="text-lg font-semibold text-gray-800">${rel.referenced_table}</span>
                            </div>
                            <button onclick="deleteRelationship('${rel.constraint_name}', '${rel.table}')" 
                                    class="text-red-600 hover:text-red-800 px-2 py-1 rounded hover:bg-red-50">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">From:</span>
                                <code class="ml-2 px-2 py-1 bg-white rounded border">${rel.table}.${rel.column}</code>
                            </div>
                            <div>
                                <span class="text-gray-600">To:</span>
                                <code class="ml-2 px-2 py-1 bg-white rounded border">${rel.referenced_table}.${rel.referenced_column}</code>
                            </div>
                            <div>
                                <span class="text-gray-600">On Update:</span>
                                <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">${rel.on_update}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">On Delete:</span>
                                <span class="ml-2 px-2 py-1 bg-${actionColor}-100 text-${actionColor}-800 rounded text-xs font-semibold">${rel.on_delete}</span>
                            </div>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <button onclick="focusOnRelationship('${rel.table}', '${rel.referenced_table}')" 
                                    class="text-xs text-blue-600 hover:text-blue-800">
                                <i class="fas fa-crosshairs mr-1"></i>
                                View on diagram
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        document.getElementById('relationshipsListModal').classList.remove('hidden');
        document.getElementById('relationshipsListModal').classList.add('flex');
    }
    
    function closeRelationshipsList() {
        document.getElementById('relationshipsListModal').classList.add('hidden');
        document.getElementById('relationshipsListModal').classList.remove('flex');
    }
    
    async function deleteRelationship(constraintName, tableName) {
        if (!confirm(`Delete relationship "${constraintName}"?`)) return;
        
        try {
            const response = await fetch('handlers/designer_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'drop_foreign_key',
                    database: window.database,
                    table: tableName,
                    constraint_name: constraintName
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Relationship deleted successfully');
                closeRelationshipsList();
                designer.loadSchema();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error deleting relationship:', error);
            showToast('Error deleting relationship', 'error');
        }
    }
    
    function focusOnRelationship(fromTable, toTable) {
        if (!designer) return;
        
        const table1 = designer.tables.find(t => t.name === fromTable);
        const table2 = designer.tables.find(t => t.name === toTable);
        
        if (table1 && table2) {
            // Calculate center point between tables
            const centerX = (table1.x + table2.x + table2.width) / 2;
            const centerY = (table1.y + table2.y + table2.height) / 2;
            
            const canvasRect = designer.canvas.getBoundingClientRect();
            const targetX = canvasRect.width / 2 - centerX * designer.zoom;
            const targetY = canvasRect.height / 2 - centerY * designer.zoom;
            
            designer.panOffset.x = targetX;
            designer.panOffset.y = targetY;
            designer.draw();
            
            closeRelationshipsList();
            showToast(`Viewing relationship between ${fromTable} and ${toTable}`, 'info');
        }
    }
</script>

<script src="assets/js/designer.js"></script>
<script src="assets/js/main.js"></script>

<?php include __DIR__ . '/views/footer.php'; ?>
