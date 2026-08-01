// Global variables
const database = typeof window.database !== 'undefined' ? window.database : null;
const table = typeof window.table !== 'undefined' ? window.table : null;
const primaryKey = typeof window.primaryKey !== 'undefined' ? window.primaryKey : null;

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast px-6 py-4 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-600' : 'bg-red-600'} text-white flex items-center gap-3`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    document.getElementById('toast-container').appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

/**
 * Delete a row
 */
function deleteRow(primaryValue) {
    if (!confirm('Are you sure you want to delete this row?')) return;
    
    fetch('handlers/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'delete_row',
            database: database,
            table: table,
            primary_key: primaryKey,
            primary_value: primaryValue
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`tr[data-row-id="${primaryValue}"]`).remove();
            showToast('Row deleted successfully');
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(err => showToast('Error deleting row', 'error'));
}

/**
 * Refresh table
 */
function refreshTable() {
    location.reload();
}

/**
 * Export Modal
 */
const EXPORT_FORMATS = {
    sql:               { label: 'MySQL SQL',       ext: '.sql',        desc: 'MySQL-compatible SQL dump with CREATE TABLE and INSERT statements.',           hasContent: true  },
    sqlite:            { label: 'SQLite',           ext: '.sql',        desc: 'SQLite-compatible schema. Types are converted to TEXT / INTEGER / REAL / BLOB.', hasContent: true  },
    prisma:            { label: 'Prisma Schema',    ext: '.prisma',     desc: 'Prisma ORM schema ready for prisma-client-js. Includes @id, @unique, @relation.', hasContent: false },
    laravel_migration: { label: 'Laravel Migration',ext: '.php/.zip',   desc: 'Laravel 10+ migration files. Full database is packaged as a ZIP archive.',     hasContent: false },
    typescript:        { label: 'TypeScript',       ext: '.types.ts',   desc: 'TypeScript interface definitions for every table in the schema.',               hasContent: false },
    zod:               { label: 'Zod Schemas',      ext: '.schemas.ts', desc: 'Zod validation schemas with inferred TypeScript types (requires zod package).', hasContent: false },
    json_schema:       { label: 'JSON Schema',      ext: '.schema.json',desc: 'JSON Schema Draft-07 definitions for all tables — useful for API validation.',  hasContent: false },
    django:            { label: 'Django Models',    ext: '_models.py',  desc: 'Django ORM model classes ready to paste into models.py.',                      hasContent: false },
    sequelize:         { label: 'Sequelize',        ext: '_models.js',  desc: 'Sequelize model definitions for Node.js — one module.exports per table.',      hasContent: false },
    mongoose:          { label: 'Mongoose',         ext: '_schemas.js', desc: 'Mongoose schema + model exports for MongoDB (structure mapping only).',         hasContent: false },
};

let _exportFormat = 'sql';

function selectExportFormat(format) {
    _exportFormat = format;
    const cfg = EXPORT_FORMATS[format];

    // Reset all tiles to default state
    document.querySelectorAll('.export-fmt-tile').forEach(el => {
        el.style.borderColor = '#e5e7eb';
        el.style.backgroundColor = '#ffffff';
    });

    // Highlight selected tile using its own data-color-* attributes
    const tile = document.getElementById('fmt-' + format);
    if (tile) {
        tile.style.borderColor  = tile.dataset.colorBorder || '#2563eb';
        tile.style.backgroundColor = tile.dataset.colorBg || '#eff6ff';
    }

    // Toggle content option (SQL/SQLite only)
    const contentOpt = document.getElementById('export-content-option');
    if (contentOpt) contentOpt.classList.toggle('hidden', !cfg.hasContent);

    // Update description + badge + button
    const desc = document.getElementById('export-format-desc');
    if (desc) desc.textContent = cfg.desc;
    const badge = document.getElementById('export-format-badge');
    if (badge) badge.textContent = 'Format: ' + cfg.label;
    const btnText = document.getElementById('export-btn-text');
    if (btnText) btnText.textContent = 'Export ' + cfg.label;
    const btn = document.getElementById('export-download-btn');
    if (btn && tile) btn.style.background = tile.dataset.colorBorder || '#16a34a';
}

function openExportModal() {
    const modal = document.getElementById('exportModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // Show table scope option only when a table is active
    const tableLbl  = document.getElementById('export-table-scope-label');
    const tableCode = document.getElementById('export-table-scope-code');
    if (tableLbl) {
        const hasTable = window.table && window.table !== 'null' && window.table !== '';
        tableLbl.classList.toggle('hidden', !hasTable);
        if (tableCode && hasTable) tableCode.textContent = window.table;
    }

    selectExportFormat(_exportFormat);
}

function closeExportModal() {
    const modal = document.getElementById('exportModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function doExport() {
    const scope   = document.querySelector('input[name="export_scope"]:checked')?.value   || 'database';
    const content = document.querySelector('input[name="export_content"]:checked')?.value || 'complete';
    const fmt     = _exportFormat;
    const db      = window.database;
    const tbl     = window.table;

    let url = `handlers/export_handler.php?action=${encodeURIComponent(fmt)}&database=${encodeURIComponent(db)}`;
    if (scope === 'table' && tbl) url += `&table=${encodeURIComponent(tbl)}`;
    if (fmt === 'sql' || fmt === 'sqlite') url += `&type=${content}`;

    window.location.href = url;
    closeExportModal();
    showToast('Preparing ' + (EXPORT_FORMATS[fmt]?.label || fmt) + ' export…');
}

/**
 * Import Modal Functions
 */
function openImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
    document.getElementById('importModal').classList.add('flex');
}

function closeImportModal() {
    document.getElementById('importModal').classList.add('hidden');
    document.getElementById('importModal').classList.remove('flex');
    document.getElementById('importFile').value = '';
    document.getElementById('importSQLContent').value = '';
    document.getElementById('selectedFileName').classList.add('hidden');
}

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileSize = file.size / 1024 / 1024; // Convert to MB
        
        if (fileSize > 50) {
            showToast('File size exceeds 50MB limit', 'error');
            input.value = '';
            return;
        }
        
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('selectedFileName').classList.remove('hidden');
        
        // Read file content
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('importSQLContent').value = e.target.result;
        };
        reader.readAsText(file);
    }
}

function clearFileSelect() {
    document.getElementById('importFile').value = '';
    document.getElementById('importSQLContent').value = '';
    document.getElementById('selectedFileName').classList.add('hidden');
}

function importSQL() {
    const sqlContent = document.getElementById('importSQLContent').value.trim();
    
    if (!sqlContent) {
        showToast('Please select a file or paste SQL content', 'error');
        return;
    }
    
    if (!database) {
        showToast('Please select a database first', 'error');
        return;
    }
    
    if (!confirm('Are you sure you want to import this SQL file? This may modify or delete existing data.')) {
        return;
    }
    
    // Show progress
    document.getElementById('importProgress').classList.remove('hidden');
    
    fetch('handlers/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'import_sql',
            database: database,
            sql_content: sqlContent
        })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('importProgress').classList.add('hidden');
        
        if (data.success) {
            showToast(data.message);
            closeImportModal();
            
            // Reload page to show updated data
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message, 'error');
            
            // Show detailed errors if any
            if (data.data && data.data.errors && data.data.errors.length > 0) {
                console.error('Import Errors:', data.data.errors);
                showToast(`${data.data.errors.length} errors occurred. Check console for details.`, 'error');
            }
        }
    })
    .catch(err => {
        document.getElementById('importProgress').classList.add('hidden');
        showToast('Error importing database', 'error');
        console.error(err);
    });
}

/**
 * Keyboard shortcuts
 */
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey && e.key === 'q') {
        e.preventDefault();
        openQueryModal();
    }
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        openExportModal();
    }
    if (e.ctrlKey && e.key === 'i') {
        e.preventDefault();
        openImportModal();
    }
    if (e.key === 'Escape') {
        closeQueryModal();
        closeAlterColumnModal();
        closeExportModal();
        closeImportModal();
    }
});
