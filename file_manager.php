<?php
/**
 * File Manager - Database Schema File Management
 * Manage database schemas as standalone files without server connection
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/ServerManager.php';
require_once __DIR__ . '/includes/DatabaseFileManager.php';

Auth::require();

$fileManager = new DatabaseFileManager();

// Get current view
$view = $_GET['view'] ?? 'list';
$selectedFile = $_GET['file'] ?? null;

// Load file data if viewing specific file
$fileData = null;
if ($selectedFile && $view === 'view') {
    try {
        $fileData = $fileManager->loadSchema($selectedFile);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/views/header.php';
?>

<div class="flex h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-6 border-b border-gray-200">
            <a href="index.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-800 mb-4">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Database Manager</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-folder-open text-blue-600"></i>
                File Manager
            </h1>
            <p class="text-sm text-gray-500 mt-2">Manage database schemas as files</p>
        </div>
        
        <div class="p-4 border-b border-gray-200">
            <div class="flex gap-2">
                <button onclick="openCreateSchemaModal()" 
                        class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span>New Schema</span>
                </button>
                <button onclick="openExportFromDBModal()" 
                        class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-download"></i>
                    <span>Export DB</span>
                </button>
            </div>
        </div>
        
        <div class="p-4 border-b border-gray-200">
            <div class="relative">
                <input type="text" 
                       id="search-input" 
                       placeholder="Search schemas..." 
                       onkeyup="searchSchemas()"
                       class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4">
            <div id="schemas-list" class="space-y-2">
                <!-- Will be populated by JavaScript -->
            </div>
            
            <div id="schemas-empty" class="hidden text-center py-12 text-gray-500">
                <i class="fas fa-folder-open text-6xl mb-4 text-gray-300"></i>
                <p class="text-lg font-medium">No schemas found</p>
                <p class="text-sm mt-2">Create a new schema or export from a database</p>
            </div>
        </div>
        
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            <div class="text-xs text-gray-600">
                <div class="flex items-center justify-between mb-1">
                    <span>Total Schemas:</span>
                    <span id="total-count" class="font-semibold">0</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Storage Used:</span>
                    <span id="storage-used" class="font-semibold">0 KB</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <div>
                <h2 id="content-title" class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-list mr-2"></i>
                    All Schemas
                </h2>
                <p id="content-subtitle" class="text-sm text-gray-500 mt-1">
                    Select a schema to view details
                </p>
            </div>
            <div class="flex gap-2">
                <button onclick="refreshList()" 
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-6">
            <div id="main-content">
                <!-- Welcome Screen -->
                <div id="welcome-screen" class="max-w-4xl mx-auto">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-white">
                            <h3 class="text-3xl font-bold mb-3">
                                <i class="fas fa-database mr-3"></i>
                                Database File Manager
                            </h3>
                            <p class="text-blue-100 text-lg">
                                Work with database schemas offline as standalone files
                            </p>
                        </div>
                        
                        <div class="p-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-4">
                                        <div class="bg-blue-600 text-white rounded-lg p-3">
                                            <i class="fas fa-plus text-2xl"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-800 mb-2">Create New Schema</h4>
                                            <p class="text-sm text-gray-600 mb-3">Start from scratch with an empty database schema</p>
                                            <button onclick="openCreateSchemaModal()" 
                                                    class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                                Create Now →
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 border-2 border-green-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-start gap-4">
                                        <div class="bg-green-600 text-white rounded-lg p-3">
                                            <i class="fas fa-download text-2xl"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-800 mb-2">Export from Database</h4>
                                            <p class="text-sm text-gray-600 mb-3">Save an existing database schema as a file</p>
                                            <button onclick="openExportFromDBModal()" 
                                                    class="text-green-600 hover:text-green-700 font-medium text-sm">
                                                Export Now →
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6">
                                <h4 class="font-semibold text-gray-800 mb-4">
                                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                    What You Can Do
                                </h4>
                                <ul class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-700">
                                    <li class="flex items-start gap-2">
                                        <i class="fas fa-check text-green-600 mt-1"></i>
                                        <span>Create and edit schemas without a database server</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fas fa-check text-green-600 mt-1"></i>
                                        <span>Use schemas in the visual designer tool</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fas fa-check text-green-600 mt-1"></i>
                                        <span>Export schemas to SQL or Laravel migrations</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fas fa-check text-green-600 mt-1"></i>
                                        <span>Import schemas to live databases</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fas fa-check text-green-600 mt-1"></i>
                                        <span>Share schemas as portable files</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <i class="fas fa-check text-green-600 mt-1"></i>
                                        <span>Version control with tags and descriptions</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Schema Details View (Hidden by default) -->
                <div id="schema-details" class="hidden max-w-6xl mx-auto">
                    <!-- Will be populated when schema is selected -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Schema Modal -->
<div id="createSchemaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                Create New Schema
            </h3>
            <button onclick="closeCreateSchemaModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Schema Name *</label>
                    <input type="text" 
                           id="new-schema-name" 
                           placeholder="my_database" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="new-schema-description" 
                              rows="3" 
                              placeholder="Brief description of this database schema..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Author</label>
                    <input type="text" 
                           id="new-schema-author" 
                           placeholder="Your name" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags (comma-separated)</label>
                    <input type="text" 
                           id="new-schema-tags" 
                           placeholder="e-commerce, customer, orders" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
            <button onclick="closeCreateSchemaModal()" 
                    class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button onclick="createNewSchema()" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-check mr-2"></i>
                Create Schema
            </button>
        </div>
    </div>
</div>

<!-- Export from DB Modal -->
<div id="exportFromDBModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-download text-green-600 mr-2"></i>
                Export Database to File
            </h3>
            <button onclick="closeExportFromDBModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Database *</label>
                    <select id="export-database-select" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Select Database --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="export-description" 
                              rows="2" 
                              placeholder="Optional description..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Author</label>
                    <input type="text" 
                           id="export-author" 
                           placeholder="Your name" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
            <button onclick="closeExportFromDBModal()" 
                    class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button onclick="exportFromDatabase()" 
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-download mr-2"></i>
                Export Database
            </button>
        </div>
    </div>
</div>

<!-- Import to DB Modal -->
<div id="importToDBModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-upload text-purple-600 mr-2"></i>
                Import Schema to Database
            </h3>
            <button onclick="closeImportToDBModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Schema File</label>
                    <input type="text" 
                           id="import-filename" 
                           readonly 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Database Name *</label>
                    <input type="text" 
                           id="import-target-database" 
                           placeholder="database_name" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Database will be created if it doesn't exist</p>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-1"></i>
                        <div class="text-sm text-yellow-800">
                            <strong>Warning:</strong> This will create tables and relationships in the target database. 
                            Make sure the database name doesn't conflict with existing databases.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end gap-2">
            <button onclick="closeImportToDBModal()" 
                    class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                Cancel
            </button>
            <button onclick="importToDatabase()" 
                    class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                <i class="fas fa-upload mr-2"></i>
                Import to Database
            </button>
        </div>
    </div>
</div>

<script src="assets/js/file_manager.js"></script>
<script src="assets/js/main.js"></script>

<?php include __DIR__ . '/views/footer.php'; ?>
