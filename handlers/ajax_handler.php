<?php
/**
 * AJAX Request Handler
 */

session_start();
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ServerManager.php';
require_once __DIR__ . '/../includes/DatabaseOperations.php';
require_once __DIR__ . '/../includes/ColumnTypeHelper.php';
require_once __DIR__ . '/../includes/DatabaseExportImport.php';

header('Content-Type: application/json');

// All AJAX endpoints require authentication
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $db = Database::getInstance();
    $dbOps = new DatabaseOperations($db->getConnection());
    
    switch ($_POST['action']) {
        case 'update_cell':
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $primaryKey = $_POST['primary_key'] ?? '';
            $primaryValue = $_POST['primary_value'] ?? '';
            $column = $_POST['column'] ?? '';
            $value = $_POST['value'] ?? '';
            
            // Validate required fields
            if (!$database || !$table || !$primaryKey || !$column) {
                throw new Exception('Missing required fields');
            }
            
            $result = $dbOps->updateCell($database, $table, $primaryKey, $primaryValue, $column, $value);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Updated successfully'
            ]);
            break;
            
        case 'delete_row':
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $primaryKey = $_POST['primary_key'] ?? '';
            $primaryValue = $_POST['primary_value'] ?? '';
            
            if (!$database || !$table || !$primaryKey) {
                throw new Exception('Missing required fields');
            }
            
            $result = $dbOps->deleteRow($database, $table, $primaryKey, $primaryValue);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Deleted successfully'
            ]);
            break;
            
        case 'execute_query':
            $query = $_POST['query'] ?? '';
            $database = $_POST['database'] ?? null;
            
            if (!$query) {
                throw new Exception('Query is required');
            }
            
            $result = $dbOps->executeQuery($query, $database);
            
            echo json_encode([
                'success' => true, 
                'data' => $result
            ]);
            break;
            
        case 'alter_column':
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $column = $_POST['column'] ?? '';
            $newType = $_POST['new_type'] ?? '';
            
            if (!$database || !$table || !$column || !$newType) {
                throw new Exception('Missing required fields');
            }
            
            $result = $dbOps->alterColumnType($database, $table, $column, $newType);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Column type changed successfully'
            ]);
            break;
            
        case 'get_column_info':
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $column = $_POST['column'] ?? '';
            
            if (!$database || !$table || !$column) {
                throw new Exception('Missing required fields');
            }
            
            $structure = $dbOps->getTableStructure($database, $table);
            $columnInfo = array_filter($structure, fn($col) => $col['Field'] === $column);
            $columnInfo = array_values($columnInfo)[0] ?? null;
            
            if (!$columnInfo) {
                throw new Exception('Column not found');
            }
            
            echo json_encode([
                'success' => true, 
                'data' => $columnInfo,
                'dataTypes' => ColumnTypeHelper::getCommonDataTypes()
            ]);
            break;
            
        case 'import_sql':
            $database = $_POST['database'] ?? '';
            $sqlContent = $_POST['sql_content'] ?? '';
            
            if (!$database || !$sqlContent) {
                throw new Exception('Missing required fields');
            }
            
            $importer = new DatabaseExportImport($db->getConnection());
            $result = $importer->importSQL($database, $sqlContent);
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['success'] 
                    ? "Successfully executed {$result['executed']} of {$result['total']} queries"
                    : "Executed {$result['executed']} of {$result['total']} queries with errors",
                'data' => $result
            ]);
            break;
            
        default:
            throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
