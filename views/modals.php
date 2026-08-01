<!-- SQL Query Modal -->
<div id="queryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-3/4 max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-code text-purple-600"></i>
                Execute SQL Query
            </h3>
            <button onclick="closeQueryModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 flex-1 overflow-auto">
            <textarea id="sqlQuery" 
                      class="w-full h-32 p-4 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      placeholder="Enter your SQL query here...">SELECT * FROM `<?php echo htmlspecialchars($selectedTable ?? ''); ?>` LIMIT 10</textarea>
            <div class="mt-4 flex gap-2">
                <button onclick="executeQuery()" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-play"></i>
                    <span>Execute</span>
                </button>
                <button onclick="clearQuery()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Clear
                </button>
            </div>
            <div id="queryResults" class="mt-6"></div>
        </div>
    </div>
</div>

<!-- Alter Column Modal -->
<div id="alterColumnModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-1/2 max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-cog text-orange-600"></i>
                Change Column Type
            </h3>
            <button onclick="closeAlterColumnModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 flex-1 overflow-auto">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Column Name</label>
                <input type="text" id="alterColumnName" readonly 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Type</label>
                <input type="text" id="alterColumnCurrentType" readonly 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">New Type</label>
                <select id="alterColumnNewType" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <option value="">Select new type...</option>
                </select>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex items-start gap-2">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-1"></i>
                    <div class="text-sm text-yellow-800">
                        <p class="font-semibold mb-1">Warning:</p>
                        <p>Changing column type may result in data loss if the new type is incompatible with existing data. Make sure to backup your data before proceeding.</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="alterColumn()" 
                        class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-check"></i>
                    <span>Change Type</span>
                </button>
                <button onclick="closeAlterColumnModal()" 
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl flex flex-col" style="width:800px;max-width:96vw;max-height:90vh">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-download text-green-600"></i>
                Export Schema
            </h3>
            <button onclick="closeExportModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <!-- Format Grid -->
        <div class="px-6 pt-5 pb-3 flex-shrink-0">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Choose Format</p>
            <div class="grid grid-cols-4 gap-2">
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

        <!-- Options -->
        <div class="px-6 py-4 flex-1 overflow-auto border-t border-gray-100">
            <!-- Scope -->
            <div class="mb-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Scope</p>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="export_scope" value="database" checked class="accent-blue-600">
                        <span class="text-sm text-gray-700">Full Database</span>
                    </label>
                    <label id="export-table-scope-label" class="hidden flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="export_scope" value="table" class="accent-blue-600">
                        <span class="text-sm text-gray-700">Current Table:
                            <code id="export-table-scope-code" class="ml-1 text-xs bg-gray-100 px-1.5 py-0.5 rounded font-mono"></code>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Content (SQL + SQLite only) -->
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

            <!-- Description -->
            <div id="export-format-info" class="flex items-start gap-2 bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-600">
                <i class="fas fa-info-circle text-blue-500 mt-0.5 flex-shrink-0"></i>
                <span id="export-format-desc">Export a full MySQL-compatible SQL dump with CREATE TABLE and INSERT statements.</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
            <span class="text-xs text-gray-400" id="export-format-badge">Format: MySQL SQL</span>
            <div class="flex gap-2">
                <button onclick="closeExportModal()"
                    class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                    Cancel
                </button>
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

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-2/3 max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-upload text-purple-600"></i>
                Import Database
            </h3>
            <button onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 flex-1 overflow-auto">
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Upload SQL File
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-purple-500 transition-colors">
                    <input type="file" id="importFile" accept=".sql" class="hidden" onchange="handleFileSelect(this)">
                    <label for="importFile" class="cursor-pointer">
                        <div class="text-gray-400 mb-2">
                            <i class="fas fa-cloud-upload-alt text-5xl"></i>
                        </div>
                        <p class="text-gray-700 font-medium mb-1">Click to select SQL file</p>
                        <p class="text-sm text-gray-500">or drag and drop</p>
                        <p class="text-xs text-gray-400 mt-2">Maximum file size: 50MB</p>
                    </label>
                </div>
                <div id="selectedFileName" class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-alt text-green-600"></i>
                            <span id="fileName" class="text-sm text-gray-700"></span>
                        </div>
                        <button onclick="clearFileSelect()" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Or Paste SQL Content
                </label>
                <textarea id="importSQLContent" 
                          class="w-full h-48 p-4 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                          placeholder="Paste your SQL statements here..."></textarea>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex items-start gap-2">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-1"></i>
                    <div class="text-sm text-yellow-800">
                        <p class="font-semibold mb-1">Warning:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Make sure to backup your database before importing</li>
                            <li>Importing will execute all SQL statements in the file</li>
                            <li>Existing tables with same name may be dropped if DROP statements are present</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-2">
                <button onclick="importSQL()" 
                        class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-upload"></i>
                    <span>Import Database</span>
                </button>
                <button onclick="closeImportModal()" 
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
            </div>
            
            <div id="importProgress" class="hidden mt-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-spinner fa-spin text-blue-600"></i>
                        <span class="text-blue-800">Importing database...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
