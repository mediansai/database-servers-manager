/**
 * Cell Editing functionality with type-specific inputs
 */

function editCell(cell) {
    if (cell.classList.contains('editing')) return;
    
    const currentValue = cell.dataset.value;
    const column = cell.dataset.column;
    const columnType = cell.dataset.type;
    const inputType = cell.dataset.inputType;
    const row = cell.closest('tr');
    const primaryValue = row.dataset.rowId;
    
    cell.classList.add('editing');
    
    let inputElement;
    
    // Create input based on column type
    switch (inputType) {
        case 'textarea':
            inputElement = document.createElement('textarea');
            inputElement.className = 'w-full px-2 py-1 border-none focus:outline-none bg-transparent resize-y';
            inputElement.rows = 3;
            inputElement.value = currentValue;
            break;
            
        case 'checkbox':
            inputElement = document.createElement('input');
            inputElement.type = 'checkbox';
            inputElement.checked = currentValue == '1' || currentValue == 'true';
            inputElement.className = 'w-5 h-5 text-blue-600 focus:ring-2 focus:ring-blue-500';
            break;
            
        case 'select':
            inputElement = document.createElement('select');
            inputElement.className = 'w-full px-2 py-1 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500';
            // Parse ENUM values from column type
            const enumMatch = columnType.match(/enum\('(.+)'\)/i);
            if (enumMatch) {
                const values = enumMatch[1].split("','");
                values.forEach(val => {
                    const option = document.createElement('option');
                    option.value = val;
                    option.textContent = val;
                    if (val === currentValue) option.selected = true;
                    inputElement.appendChild(option);
                });
            }
            break;
            
        case 'date':
        case 'datetime-local':
        case 'time':
        case 'number':
            inputElement = document.createElement('input');
            inputElement.type = inputType;
            inputElement.className = 'w-full px-2 py-1 border-none focus:outline-none bg-transparent';
            
            if (inputType === 'datetime-local' && currentValue) {
                // Convert MySQL datetime to HTML5 datetime-local format
                inputElement.value = currentValue.replace(' ', 'T').substring(0, 16);
            } else if (inputType === 'number') {
                inputElement.value = currentValue;
                inputElement.step = columnType.includes('decimal') || columnType.includes('float') || columnType.includes('double') ? '0.01' : '1';
            } else {
                inputElement.value = currentValue;
            }
            break;
            
        default:
            inputElement = document.createElement('input');
            inputElement.type = 'text';
            inputElement.value = currentValue;
            inputElement.className = 'w-full px-2 py-1 border-none focus:outline-none bg-transparent';
    }
    
    cell.innerHTML = '';
    cell.appendChild(inputElement);
    inputElement.focus();
    
    if (inputElement.select) {
        inputElement.select();
    }
    
    function saveEdit() {
        let newValue;
        
        if (inputType === 'checkbox') {
            newValue = inputElement.checked ? '1' : '0';
        } else if (inputType === 'datetime-local') {
            // Convert HTML5 datetime-local to MySQL datetime format
            newValue = inputElement.value ? inputElement.value.replace('T', ' ') + ':00' : '';
        } else {
            newValue = inputElement.value;
        }
        
        if (newValue === currentValue) {
            cell.classList.remove('editing');
            cell.innerHTML = formatCellValue(currentValue, columnType);
            return;
        }
        
        // Show loader
        cell.innerHTML = '<div class="loader mx-auto"></div>';
        
        fetch('handlers/ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'update_cell',
                database: database,
                table: table,
                primary_key: primaryKey,
                primary_value: primaryValue,
                column: column,
                value: newValue
            })
        })
        .then(res => res.json())
        .then(data => {
            cell.classList.remove('editing');
            if (data.success) {
                cell.dataset.value = newValue;
                cell.innerHTML = formatCellValue(newValue, columnType);
                showToast('Cell updated successfully');
            } else {
                cell.innerHTML = formatCellValue(currentValue, columnType);
                showToast(data.message, 'error');
            }
        })
        .catch(err => {
            cell.classList.remove('editing');
            cell.innerHTML = formatCellValue(currentValue, columnType);
            showToast('Error updating cell', 'error');
        });
    }
    
    // Event listeners
    if (inputType === 'textarea') {
        const saveButton = document.createElement('button');
        saveButton.className = 'mt-2 px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700';
        saveButton.innerHTML = '<i class="fas fa-save mr-1"></i> Save';
        saveButton.onclick = saveEdit;
        cell.appendChild(saveButton);
    } else if (inputType === 'checkbox') {
        inputElement.addEventListener('change', saveEdit);
    } else if (inputType === 'select') {
        inputElement.addEventListener('change', saveEdit);
    } else {
        inputElement.addEventListener('blur', saveEdit);
        inputElement.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && inputType !== 'textarea') saveEdit();
            if (e.key === 'Escape') {
                cell.classList.remove('editing');
                cell.innerHTML = formatCellValue(currentValue, columnType);
            }
        });
    }
}

/**
 * Format cell value for display
 */
function formatCellValue(value, columnType) {
    if (value === null || value === '') {
        return '<span class="text-gray-400 italic">NULL</span>';
    }
    
    const type = columnType.toLowerCase();
    
    // Boolean display
    if (type.includes('tinyint(1)') || type.includes('boolean') || type.includes('bool')) {
        return value == '1' ? '<span class="text-green-600">✓ True</span>' : '<span class="text-red-600">✗ False</span>';
    }
    
    // Truncate long text
    if ((type.includes('text') || type.includes('blob')) && value.length > 100) {
        return escapeHtml(value.substring(0, 100)) + '...';
    }
    
    return escapeHtml(value);
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
