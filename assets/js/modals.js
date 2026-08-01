/**
 * Modal Management
 */

// SQL Query Modal
function openQueryModal() {
    document.getElementById('queryModal').classList.remove('hidden');
    document.getElementById('queryModal').classList.add('flex');
}

function closeQueryModal() {
    document.getElementById('queryModal').classList.add('hidden');
    document.getElementById('queryModal').classList.remove('flex');
}

function clearQuery() {
    document.getElementById('sqlQuery').value = '';
    document.getElementById('queryResults').innerHTML = '';
}

function executeQuery() {
    const query = document.getElementById('sqlQuery').value;
    const resultsDiv = document.getElementById('queryResults');
    
    if (!query.trim()) {
        showToast('Please enter a query', 'error');
        return;
    }
    
    resultsDiv.innerHTML = '<div class="flex items-center justify-center py-8"><div class="loader"></div></div>';
    
    fetch('handlers/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'execute_query',
            query: query,
            database: database
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.data && data.data.length > 0) {
                let html = '<div class="bg-white rounded-lg border border-gray-200 overflow-hidden"><table class="w-full"><thead class="bg-gray-50"><tr>';
                Object.keys(data.data[0]).forEach(col => {
                    html += `<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">${escapeHtml(col)}</th>`;
                });
                html += '</tr></thead><tbody class="divide-y divide-gray-200">';
                data.data.forEach(row => {
                    html += '<tr class="hover:bg-gray-50">';
                    Object.values(row).forEach(val => {
                        html += `<td class="px-4 py-3">${val !== null ? escapeHtml(String(val)) : '<span class="text-gray-400 italic">NULL</span>'}</td>`;
                    });
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                resultsDiv.innerHTML = html;
                showToast('Query executed successfully');
            } else {
                resultsDiv.innerHTML = '<div class="text-center py-8 text-gray-500">Query executed successfully. No results to display.</div>';
                showToast('Query executed successfully');
            }
        } else {
            resultsDiv.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">${escapeHtml(data.message)}</div>`;
            showToast(data.message, 'error');
        }
    })
    .catch(err => {
        resultsDiv.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">Error executing query</div>';
        showToast('Error executing query', 'error');
    });
}

// Alter Column Modal
let currentAlterColumn = null;

function openAlterColumnModal(columnName, currentType) {
    currentAlterColumn = columnName;
    document.getElementById('alterColumnName').value = columnName;
    document.getElementById('alterColumnCurrentType').value = currentType;
    
    // Fetch column info and data types
    fetch('handlers/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'get_column_info',
            database: database,
            table: table,
            column: columnName
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('alterColumnNewType');
            select.innerHTML = '<option value="">Select new type...</option>';
            
            // Populate data types
            Object.entries(data.dataTypes).forEach(([category, types]) => {
                const optgroup = document.createElement('optgroup');
                optgroup.label = category;
                
                Object.entries(types).forEach(([key, value]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = key;
                    optgroup.appendChild(option);
                });
                
                select.appendChild(optgroup);
            });
        }
    })
    .catch(err => {
        showToast('Error loading column info', 'error');
    });
    
    document.getElementById('alterColumnModal').classList.remove('hidden');
    document.getElementById('alterColumnModal').classList.add('flex');
}

function closeAlterColumnModal() {
    document.getElementById('alterColumnModal').classList.add('hidden');
    document.getElementById('alterColumnModal').classList.remove('flex');
    currentAlterColumn = null;
}

function alterColumn() {
    const newType = document.getElementById('alterColumnNewType').value;
    
    if (!newType) {
        showToast('Please select a new type', 'error');
        return;
    }
    
    if (!confirm(`Are you sure you want to change the column type?\n\nColumn: ${currentAlterColumn}\nNew Type: ${newType}\n\nThis may result in data loss if the types are incompatible.`)) {
        return;
    }
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<div class="loader mx-auto"></div>';
    button.disabled = true;
    
    fetch('handlers/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'alter_column',
            database: database,
            table: table,
            column: currentAlterColumn,
            new_type: newType
        })
    })
    .then(res => res.json())
    .then(data => {
        button.innerHTML = originalText;
        button.disabled = false;
        
        if (data.success) {
            showToast('Column type changed successfully');
            closeAlterColumnModal();
            // Reload page to reflect changes
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(err => {
        button.innerHTML = originalText;
        button.disabled = false;
        showToast('Error changing column type', 'error');
    });
}
