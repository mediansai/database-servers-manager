<!-- Database Overview - Show all tables -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-table mr-2"></i>
                    Tables in <?php echo htmlspecialchars($selectedDatabase); ?>
                </h3>
                <p class="text-sm text-gray-500 mt-1">Click on any table to view its data</p>
            </div>
            <div class="flex gap-2">
                <button onclick="openExportModal()" 
                        class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-download"></i>
                    <span>Export Database</span>
                </button>
                <button onclick="openImportModal()" 
                        class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-upload"></i>
                    <span>Import</span>
                </button>
            </div>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Table Name
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Rows
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Size (MB)
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($tablesMetadata as $tableInfo): ?>
                    <tr class="hover:bg-blue-50 transition-colors">
                        <td class="px-6 py-4">
                            <a href="?database=<?php echo urlencode($selectedDatabase); ?>&table=<?php echo urlencode($tableInfo['name']); ?>" 
                               class="flex items-center gap-2 text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fas fa-table text-sm"></i>
                                <?php echo htmlspecialchars($tableInfo['name']); ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            <span class="inline-flex items-center gap-1">
                                <i class="fas fa-database text-xs text-gray-400"></i>
                                <?php echo number_format($tableInfo['rows']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            <span class="inline-flex items-center gap-1">
                                <i class="fas fa-hdd text-xs text-gray-400"></i>
                                <?php echo number_format($tableInfo['size'], 2); ?> MB
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="?database=<?php echo urlencode($selectedDatabase); ?>&table=<?php echo urlencode($tableInfo['name']); ?>" 
                               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-eye"></i>
                                View Data
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between text-sm text-gray-600">
            <span>
                <i class="fas fa-info-circle mr-1"></i>
                Total tables: <strong><?php echo count($tablesMetadata); ?></strong>
            </span>
            <span>
                <i class="fas fa-database mr-1"></i>
                Total rows: <strong><?php echo number_format(array_sum(array_column($tablesMetadata, 'rows'))); ?></strong>
            </span>
            <span>
                <i class="fas fa-hdd mr-1"></i>
                Total size: <strong><?php echo number_format(array_sum(array_column($tablesMetadata, 'size')), 2); ?> MB</strong>
            </span>
        </div>
    </div>
</div>
