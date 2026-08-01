/**
 * File Manager JavaScript
 * Handles file-based database schema management
 */

let currentSchemas = [];
let selectedSchema = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSchemasList();
    loadDatabasesForExport();
});

/**
 * Load all schemas
 */
async function loadSchemasList() {
    try {
        const response = await fetch('handlers/file_handler.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            currentSchemas = data.schemas;
            renderSchemasList(data.schemas);
            updateStats(data.schemas);
        } else {
            showToast('Failed to load schemas', 'error');
        }
    } catch (error) {
        console.error('Error loading schemas:', error);
        showToast('Error loading schemas', 'error');
    }
}

/**
 * Render schemas list
 */
function renderSchemasList(schemas) {
    const listContainer = document.getElementById('schemas-list');
    const emptyContainer = document.getElementById('schemas-empty');
    
    if (schemas.length === 0) {
        listContainer.innerHTML = '';
        emptyContainer.classList.remove('hidden');
        return;
    }
    
    emptyContainer.classList.add('hidden');
    
    listContainer.innerHTML = schemas.map(schema => `
        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
             onclick="viewSchema('${schema.filename}')">
            <div class="flex items-start justify-between mb-2">
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-800 mb-1">${escapeHtml(schema.name)}</h4>
                    <p class="text-xs text-gray-500 line-clamp-2">${escapeHtml(schema.description || 'No description')}</p>
                </div>
                <div class="relative">
                    <button onclick="event.stopPropagation(); toggleSchemaMenu('${schema.filename}')" 
                            class="text-gray-400 hover:text-gray-600 p-1">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div id="menu-${schema.filename}" 
                         class="hidden absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10">
                        <button onclick="event.stopPropagation(); viewSchema('${schema.filename}')" 
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">
                            <i class="fas fa-eye text-blue-600 mr-2"></i>View
                        </button>
                        <button onclick="event.stopPropagation(); openInDesigner('${schema.filename}')" 
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">
                            <i class="fas fa-project-diagram text-indigo-600 mr-2"></i>Open in Designer
                        </button>
                        <button onclick="event.stopPropagation(); downloadSQL('${schema.filename}')" 
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">
                            <i class="fas fa-file-code text-green-600 mr-2"></i>Export SQL
                        </button>
                        <button onclick="event.stopPropagation(); openImportToDBModal('${schema.filename}')" 
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">
                            <i class="fas fa-upload text-purple-600 mr-2"></i>Import to DB
                        </button>
                        <button onclick="event.stopPropagation(); duplicateSchema('${schema.filename}')" 
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm">
                            <i class="fas fa-copy text-orange-600 mr-2"></i>Duplicate
                        </button>
                        <button onclick="event.stopPropagation(); deleteSchema('${schema.filename}')" 
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm text-red-600">
                            <i class="fas fa-trash mr-2"></i>Delete
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-500 mt-3">
                <span><i class="fas fa-table mr-1"></i>${schema.table_count}</span>
                <span><i class="fas fa-link mr-1"></i>${schema.relationship_count}</span>
                <span><i class="fas fa-clock mr-1"></i>${formatDate(schema.updated_at)}</span>
            </div>
            ${schema.tags.length > 0 ? `
                <div class="flex flex-wrap gap-1 mt-2">
                    ${schema.tags.map(tag => `
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded">${escapeHtml(tag)}</span>
                    `).join('')}
                </div>
            ` : ''}
        </div>
    `).join('');
}

/**
 * Toggle schema context menu
 */
function toggleSchemaMenu(filename) {
    const menu = document.getElementById(`menu-${filename}`);
    
    // Close all other menus
    document.querySelectorAll('[id^="menu-"]').forEach(m => {
        if (m.id !== `menu-${filename}`) {
            m.classList.add('hidden');
        }
    });
    
    menu.classList.toggle('hidden');
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="menu-"]') && !e.target.closest('button')) {
        document.querySelectorAll('[id^="menu-"]').forEach(m => {
            m.classList.add('hidden');
        });
    }
});

/**
 * View schema details
 */
async function viewSchema(filename) {
    try {
        const response = await fetch(`handlers/file_handler.php?action=load&filename=${encodeURIComponent(filename)}`);
        const result = await response.json();
        
        if (result.success) {
            selectedSchema = result.data;
            renderSchemaDetails(result.data, filename);
            
            // Update title
            document.getElementById('content-title').innerHTML = 
                `<i class="fas fa-database mr-2"></i>${escapeHtml(result.data.name)}`;
            document.getElementById('content-subtitle').textContent = 
                result.data.description || 'No description';
        } else {
            showToast('Failed to load schema', 'error');
        }
    } catch (error) {
        console.error('Error loading schema:', error);
        showToast('Error loading schema', 'error');
    }
}

/**
 * Render schema details
 */
function renderSchemaDetails(data, filename) {
    const detailsContainer = document.getElementById('schema-details');
    const welcomeScreen = document.getElementById('welcome-screen');
    
    welcomeScreen.classList.add('hidden');
    detailsContainer.classList.remove('hidden');
    
    const schema = data.schema;
    
    detailsContainer.innerHTML = `
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-white">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-3xl font-bold mb-2">${escapeHtml(data.name)}</h3>
                        <p class="text-blue-100 mb-4">${escapeHtml(data.description || 'No description')}</p>
                        <div class="flex items-center gap-4 text-sm">
                            ${data.author ? `<span><i class="fas fa-user mr-1"></i>${escapeHtml(data.author)}</span>` : ''}
                            <span><i class="fas fa-calendar mr-1"></i>Updated: ${formatDate(data.updated_at)}</span>
                            <span><i class="fas fa-code-branch mr-1"></i>v${data.version}</span>
                        </div>
                    </div>
                    <button onclick="showWelcomeScreen()" 
                            class="text-white hover:text-blue-200">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Actions Bar -->
            <div class="border-b border-gray-200 p-4 bg-gray-50">
                <div class="flex items-center gap-2">
                    <button onclick="openInDesigner('${filename}')" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-project-diagram mr-2"></i>
                        Open in Designer
                    </button>
                    <button onclick="downloadSQL('${filename}')" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-file-code mr-2"></i>
                        Export SQL
                    </button>
                    <button onclick="openImportToDBModal('${filename}')" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-upload mr-2"></i>
                        Import to DB
                    </button>
                    <button onclick="duplicateSchema('${filename}')" 
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                        <i class="fas fa-copy mr-2"></i>
                        Duplicate
                    </button>
                    <button onclick="deleteSchema('${filename}')" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Delete
                    </button>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="p-6 border-b border-gray-200 bg-white">
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">${schema.tables.length}</div>
                        <div class="text-sm text-gray-600 mt-1">Tables</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">${schema.relationships.length}</div>
                        <div class="text-sm text-gray-600 mt-1">Relationships</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600">
                            ${schema.tables.reduce((sum, t) => sum + t.columns.length, 0)}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Columns</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-orange-600">
                            ${schema.tables.reduce((sum, t) => sum + (t.indexes?.length || 0), 0)}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Indexes</div>
                    </div>
                </div>
            </div>
            
            <!-- Tables List -->
            <div class="p-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-table text-blue-600 mr-2"></i>
                    Tables (${schema.tables.length})
                </h4>
                <div class="space-y-4">
                    ${schema.tables.map(table => `
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h5 class="font-semibold text-gray-800">${escapeHtml(table.name)}</h5>
                                    <span class="text-sm text-gray-500">
                                        ${table.columns.length} columns
                                    </span>
                                </div>
                                ${table.comment ? `
                                    <p class="text-sm text-gray-600 mt-1">${escapeHtml(table.comment)}</p>
                                ` : ''}
                            </div>
                            <div class="p-4">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-medium text-gray-700">Column</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-700">Type</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-700">Null</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-700">Key</th>
                                                <th class="px-3 py-2 text-left font-medium text-gray-700">Extra</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            ${table.columns.map(col => `
                                                <tr>
                                                    <td class="px-3 py-2 font-mono text-xs">${escapeHtml(col.name)}</td>
                                                    <td class="px-3 py-2 text-gray-600">${escapeHtml(col.type)}</td>
                                                    <td class="px-3 py-2">
                                                        ${col.null ? 
                                                            '<span class="text-green-600">YES</span>' : 
                                                            '<span class="text-red-600">NO</span>'}
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        ${col.key === 'PRI' ? 
                                                            '<i class="fas fa-key text-yellow-500" title="Primary Key"></i>' : 
                                                            col.key === 'UNI' ? 
                                                            '<i class="fas fa-star text-purple-500" title="Unique"></i>' : ''}
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-600 text-xs">${escapeHtml(col.extra || '')}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                <!-- Relationships -->
                ${schema.relationships.length > 0 ? `
                    <div class="mt-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-link text-indigo-600 mr-2"></i>
                            Relationships (${schema.relationships.length})
                        </h4>
                        <div class="space-y-2">
                            ${schema.relationships.map(rel => `
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center gap-3">
                                        <code class="px-2 py-1 bg-white border rounded">${escapeHtml(rel.table)}.${escapeHtml(rel.column)}</code>
                                        <i class="fas fa-arrow-right text-gray-400"></i>
                                        <code class="px-2 py-1 bg-white border rounded">${escapeHtml(rel.referenced_table)}.${escapeHtml(rel.referenced_column)}</code>
                                        <span class="ml-auto text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded">
                                            ON DELETE ${rel.on_delete || 'RESTRICT'}
                                        </span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

/**
 * Show welcome screen
 */
function showWelcomeScreen() {
    document.getElementById('schema-details').classList.add('hidden');
    document.getElementById('welcome-screen').classList.remove('hidden');
    document.getElementById('content-title').innerHTML = '<i class="fas fa-list mr-2"></i>All Schemas';
    document.getElementById('content-subtitle').textContent = 'Select a schema to view details';
    selectedSchema = null;
}

/**
 * Open schema in designer
 */
function openInDesigner(filename) {
    window.location.href = `designer.php?file=${encodeURIComponent(filename)}`;
}

/**
 * Download schema as SQL
 */
async function downloadSQL(filename) {
    try {
        const response = await fetch(`handlers/file_handler.php?action=export_to_sql&filename=${encodeURIComponent(filename)}`);
        const data = await response.json();
        
        if (data.success) {
            const blob = new Blob([data.sql], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = data.filename;
            a.click();
            URL.revokeObjectURL(url);
            
            showToast('SQL exported successfully');
        } else {
            showToast('Failed to export SQL', 'error');
        }
    } catch (error) {
        console.error('Error exporting SQL:', error);
        showToast('Error exporting SQL', 'error');
    }
}

/**
 * Duplicate schema
 */
async function duplicateSchema(filename) {
    const newName = prompt('Enter name for the duplicated schema:');
    if (!newName) return;
    
    try {
        const response = await fetch('handlers/file_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'duplicate',
                filename: filename,
                new_name: newName
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Schema duplicated successfully');
            loadSchemasList();
        } else {
            showToast(data.message || 'Failed to duplicate schema', 'error');
        }
    } catch (error) {
        console.error('Error duplicating schema:', error);
        showToast('Error duplicating schema', 'error');
    }
}

/**
 * Delete schema
 */
async function deleteSchema(filename) {
    if (!confirm('Are you sure you want to delete this schema? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch('handlers/file_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'delete',
                filename: filename
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Schema deleted successfully');
            showWelcomeScreen();
            loadSchemasList();
        } else {
            showToast(data.message || 'Failed to delete schema', 'error');
        }
    } catch (error) {
        console.error('Error deleting schema:', error);
        showToast('Error deleting schema', 'error');
    }
}

/**
 * Search schemas
 */
async function searchSchemas() {
    const query = document.getElementById('search-input').value.trim();
    
    if (query === '') {
        renderSchemasList(currentSchemas);
        return;
    }
    
    try {
        const response = await fetch(`handlers/file_handler.php?action=search&query=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success) {
            renderSchemasList(data.schemas);
        }
    } catch (error) {
        console.error('Error searching schemas:', error);
    }
}

/**
 * Update stats
 */
function updateStats(schemas) {
    const totalSize = schemas.reduce((sum, s) => sum + s.size, 0);
    
    document.getElementById('total-count').textContent = schemas.length;
    document.getElementById('storage-used').textContent = formatBytes(totalSize);
}

/**
 * Create Schema Modal
 */
function openCreateSchemaModal() {
    document.getElementById('createSchemaModal').classList.remove('hidden');
    document.getElementById('createSchemaModal').classList.add('flex');
}

function closeCreateSchemaModal() {
    document.getElementById('createSchemaModal').classList.add('hidden');
    document.getElementById('createSchemaModal').classList.remove('flex');
    document.getElementById('new-schema-name').value = '';
    document.getElementById('new-schema-description').value = '';
    document.getElementById('new-schema-author').value = '';
    document.getElementById('new-schema-tags').value = '';
}

async function createNewSchema() {
    const name = document.getElementById('new-schema-name').value.trim();
    const description = document.getElementById('new-schema-description').value.trim();
    const author = document.getElementById('new-schema-author').value.trim();
    const tags = document.getElementById('new-schema-tags').value.trim();
    
    if (!name) {
        showToast('Please enter a schema name', 'error');
        return;
    }
    
    try {
        const response = await fetch('handlers/file_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'create_empty',
                schema_name: name,
                description: description,
                author: author,
                tags: tags
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Schema created successfully');
            closeCreateSchemaModal();
            loadSchemasList();
            
            // Open in designer
            setTimeout(() => {
                openInDesigner(data.filename);
            }, 500);
        } else {
            showToast(data.message || 'Failed to create schema', 'error');
        }
    } catch (error) {
        console.error('Error creating schema:', error);
        showToast('Error creating schema', 'error');
    }
}

/**
 * Export from DB Modal
 */
async function loadDatabasesForExport() {
    try {
        const response = await fetch('handlers/ajax_handler.php?action=get_databases');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('export-database-select');
            select.innerHTML = '<option value="">-- Select Database --</option>' +
                data.databases.map(db => `<option value="${escapeHtml(db)}">${escapeHtml(db)}</option>`).join('');
        }
    } catch (error) {
        console.error('Error loading databases:', error);
    }
}

function openExportFromDBModal() {
    document.getElementById('exportFromDBModal').classList.remove('hidden');
    document.getElementById('exportFromDBModal').classList.add('flex');
}

function closeExportFromDBModal() {
    document.getElementById('exportFromDBModal').classList.add('hidden');
    document.getElementById('exportFromDBModal').classList.remove('flex');
}

async function exportFromDatabase() {
    const database = document.getElementById('export-database-select').value;
    const description = document.getElementById('export-description').value.trim();
    const author = document.getElementById('export-author').value.trim();
    
    if (!database) {
        showToast('Please select a database', 'error');
        return;
    }
    
    try {
        const response = await fetch('handlers/file_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'export_from_db',
                database: database,
                description: description,
                author: author
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Database exported successfully');
            closeExportFromDBModal();
            loadSchemasList();
        } else {
            showToast(data.message || 'Failed to export database', 'error');
        }
    } catch (error) {
        console.error('Error exporting database:', error);
        showToast('Error exporting database', 'error');
    }
}

/**
 * Import to DB Modal
 */
function openImportToDBModal(filename) {
    document.getElementById('importToDBModal').classList.remove('hidden');
    document.getElementById('importToDBModal').classList.add('flex');
    document.getElementById('import-filename').value = filename;
    
    // Suggest database name from filename
    const suggestedName = filename.replace('.dbschema', '');
    document.getElementById('import-target-database').value = suggestedName;
}

function closeImportToDBModal() {
    document.getElementById('importToDBModal').classList.add('hidden');
    document.getElementById('importToDBModal').classList.remove('flex');
}

async function importToDatabase() {
    const filename = document.getElementById('import-filename').value;
    const targetDatabase = document.getElementById('import-target-database').value.trim();
    
    if (!targetDatabase) {
        showToast('Please enter a target database name', 'error');
        return;
    }
    
    if (!confirm(`This will create/modify database "${targetDatabase}". Continue?`)) {
        return;
    }
    
    try {
        const response = await fetch('handlers/file_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'import_to_db',
                filename: filename,
                target_database: targetDatabase
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(`Database imported: ${data.tables_created} tables, ${data.relationships_created} relationships`);
            closeImportToDBModal();
            
            // Offer to view in manager
            if (confirm('Import successful! Would you like to view the database now?')) {
                window.location.href = `index.php?database=${encodeURIComponent(targetDatabase)}`;
            }
        } else {
            let errorMsg = 'Failed to import database';
            if (data.errors && data.errors.length > 0) {
                errorMsg += ':\n' + data.errors.map(e => e.error).join('\n');
            }
            showToast(errorMsg, 'error');
        }
    } catch (error) {
        console.error('Error importing database:', error);
        showToast('Error importing database', 'error');
    }
}

/**
 * Refresh list
 */
function refreshList() {
    loadSchemasList();
    showToast('List refreshed', 'success');
}

/**
 * Utility functions
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 7) {
        return date.toLocaleDateString();
    } else if (days > 0) {
        return `${days}d ago`;
    } else if (hours > 0) {
        return `${hours}h ago`;
    } else if (minutes > 0) {
        return `${minutes}m ago`;
    } else {
        return 'Just now';
    }
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}
