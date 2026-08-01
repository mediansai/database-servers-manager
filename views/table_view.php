<?php 
require_once __DIR__ . '/../includes/ColumnTypeHelper.php';
?>

<!-- Table Content -->
<div class="flex-1 overflow-auto p-6">
    <?php if ($selectedDatabase && $selectedTable && $tableData): ?>
        <!-- Table Actions Bar -->
        <div class="mb-4 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>
                Viewing data from <strong><?php echo htmlspecialchars($selectedTable); ?></strong>
            </div>
            <div class="flex gap-2">
                <button onclick="openExportModal()" 
                        class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-download"></i>
                    <span>Export Table</span>
                </button>
            </div>
        </div>
        
        <!-- Selected Table Data View -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Actions
                            </th>
                            <?php foreach ($tableStructure ?? [] as $column => $value): ?>
                                <?php 
                                $column = $value['Field'];
                                $isPrimary = $column === $primaryKey;
                                $columnInfo = $value;
                                ?>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <?php if ($isPrimary): ?>
                                            <i class="fas fa-key text-yellow-500" title="Primary Key"></i>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($column); ?></span>
                                        <span class="text-gray-400 normal-case text-xs">
                                            (<?php echo $columnInfo['Type'] ?? ''; ?>)
                                        </span>
                                        <button onclick="openAlterColumnModal('<?php echo htmlspecialchars($column); ?>', '<?php echo htmlspecialchars($columnInfo['Type'] ?? ''); ?>')" 
                                                class="ml-1 text-purple-600 hover:text-purple-800" 
                                                title="Change column type">
                                            <i class="fas fa-cog text-xs"></i>
                                        </button>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($tableData['data'] as $row): ?>
                            <tr class="hover:bg-gray-50" data-row-id="<?php echo htmlspecialchars($row[$primaryKey] ?? ''); ?>">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <button onclick="deleteRow('<?php echo htmlspecialchars($row[$primaryKey] ?? ''); ?>')" 
                                            class="text-red-600 hover:text-red-800 transition-colors"
                                            title="Delete row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                                <?php foreach ($row as $column => $value): ?>
                                    <?php 
                                    $isEditable = $column !== $primaryKey;
                                    $columnInfo = array_filter($tableStructure, fn($col) => $col['Field'] === $column)[0] ?? null;
                                    $columnType = $columnInfo['Type'] ?? '';
                                    $inputType = ColumnTypeHelper::getInputType($columnType);
                                    $cellClass = $isEditable ? 'editable-cell' : 'bg-gray-50';
                                    $displayValue = ColumnTypeHelper::formatValue($value, $columnType);
                                    ?>
                                    <td class="px-4 py-3 <?php echo $cellClass; ?>" 
                                        <?php if ($isEditable): ?>
                                            data-column="<?php echo htmlspecialchars($column); ?>"
                                            data-value="<?php echo htmlspecialchars($value ?? ''); ?>"
                                            data-type="<?php echo htmlspecialchars($columnType); ?>"
                                            data-input-type="<?php echo $inputType; ?>"
                                            onclick="editCell(this)"
                                        <?php endif; ?>>
                                        <?php echo $displayValue; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($tableData['pages'] > 1): ?>
            <div class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing <?php echo (($currentPage - 1) * $perPage) + 1; ?> 
                    to <?php echo min($currentPage * $perPage, $tableData['total']); ?> 
                    of <?php echo $tableData['total']; ?> rows
                </div>
                <div class="flex gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a href="?database=<?php echo urlencode($selectedDatabase); ?>&table=<?php echo urlencode($selectedTable); ?>&page=<?php echo $currentPage - 1; ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <div class="flex gap-1">
                        <?php 
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($tableData['pages'], $currentPage + 2);
                        for ($i = $startPage; $i <= $endPage; $i++): 
                            $activeClass = $i === $currentPage ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50';
                        ?>
                            <a href="?database=<?php echo urlencode($selectedDatabase); ?>&table=<?php echo urlencode($selectedTable); ?>&page=<?php echo $i; ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg <?php echo $activeClass; ?> transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>

                    <?php if ($currentPage < $tableData['pages']): ?>
                        <a href="?database=<?php echo urlencode($selectedDatabase); ?>&table=<?php echo urlencode($selectedTable); ?>&page=<?php echo $currentPage + 1; ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    
    <?php elseif ($selectedDatabase && !$selectedTable && $tablesMetadata): ?>
        <!-- Database Overview -->
        <?php include __DIR__ . '/database_overview.php'; ?>
    
    <?php else: ?>
        <!-- Welcome Screen -->
        <div class="flex items-center justify-center h-full">
            <div class="text-center">
                <i class="fas fa-database text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">Welcome to Database Manager</h3>
                <p class="text-gray-500">Select a database from the sidebar to get started</p>
            </div>
        </div>
    <?php endif; ?>
</div>
