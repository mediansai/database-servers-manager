<?php
/**
 * Database Designer Handler
 * Handles AJAX requests for the database designer
 */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ServerManager.php';
require_once __DIR__ . '/../includes/DatabaseDesigner.php';
require_once __DIR__ . '/../includes/DatabaseFileManager.php';

header('Content-Type: application/json');

if (!Auth::check()) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthenticated']); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $db = Database::getInstance();
    $designer = new DatabaseDesigner($db->getConnection());
    $fileManager = new DatabaseFileManager();
    
    switch ($_POST['action']) {
        case 'get_schema':
            $database = $_POST['database'] ?? '';
            $file = $_POST['file'] ?? '';
            
            // Check if loading from file or database
            if ($file) {
                // Load from file
                $fileData = $fileManager->loadSchema($file);
                $schema = $fileData['schema'];
                $positions = [];
                
                // Extract positions from table data
                foreach ($schema['tables'] as $table) {
                    if (isset($table['position'])) {
                        $positions[$table['name']] = $table['position'];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'schema' => $schema,
                    'positions' => $positions,
                    'mode' => 'file',
                    'filename' => $file,
                    'metadata' => [
                        'name' => $fileData['name'],
                        'description' => $fileData['description'] ?? '',
                        'author' => $fileData['author'] ?? ''
                    ]
                ]);
            } else {
                // Load from database
                if (!$database) {
                    throw new Exception('Database not specified');
                }
                
                $schema = $designer->getDatabaseSchema($database);
                $positions = $designer->getTablePositions($database);
                
                echo json_encode([
                    'success' => true,
                    'schema' => $schema,
                    'positions' => $positions,
                    'mode' => 'database',
                    'database' => $database
                ]);
            }
            break;
            
        case 'save_position':
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $x = $_POST['x'] ?? 0;
            $y = $_POST['y'] ?? 0;
            
            if (!$database || !$table) {
                throw new Exception('Missing required fields');
            }
            
            $designer->saveTablePosition($database, $table, $x, $y);
            
            echo json_encode([
                'success' => true,
                'message' => 'Position saved'
            ]);
            break;
            
        case 'create_table':
            $database = $_POST['database'] ?? '';
            $tableName = $_POST['table_name'] ?? '';
            $columns = json_decode($_POST['columns'] ?? '[]', true);
            $comment = $_POST['comment'] ?? '';
            
            if (!$database || !$tableName || empty($columns)) {
                throw new Exception('Missing required fields');
            }
            
            $designer->createTable($database, $tableName, $columns, $comment);
            
            echo json_encode([
                'success' => true,
                'message' => 'Table created successfully'
            ]);
            break;
            
        case 'add_column':
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $columnName = $_POST['column_name'] ?? '';
            $columnType = $_POST['column_type'] ?? '';
            $nullable = isset($_POST['nullable']) ? (bool)$_POST['nullable'] : true;
            $defaultValue = $_POST['default_value'] ?? null;
            $after = $_POST['after'] ?? null;
            
            if (!$database || !$table || !$columnName || !$columnType) {
                throw new Exception('Missing required fields');
            }
            
            $designer->addColumn($database, $table, $columnName, $columnType, $nullable, $defaultValue, $after);
            
            echo json_encode([
                'success' => true,
                'message' => 'Column added successfully'
            ]);
            break;
            
        case 'drop_column':
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $columnName = $_POST['column_name'] ?? '';
            
            if (!$database || !$table || !$columnName) {
                throw new Exception('Missing required fields');
            }
            
            $designer->dropColumn($database, $table, $columnName);
            
            echo json_encode([
                'success' => true,
                'message' => 'Column dropped successfully'
            ]);
            break;
            
        case 'add_foreign_key':
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $column = $_POST['column'] ?? '';
            $refTable = $_POST['ref_table'] ?? '';
            $refColumn = $_POST['ref_column'] ?? '';
            $onUpdate = $_POST['on_update'] ?? 'RESTRICT';
            $onDelete = $_POST['on_delete'] ?? 'RESTRICT';
            
            if (!$database || !$table || !$column || !$refTable || !$refColumn) {
                throw new Exception('Missing required fields');
            }
            
            $designer->addForeignKey($database, $table, $column, $refTable, $refColumn, $onUpdate, $onDelete);
            
            echo json_encode([
                'success' => true,
                'message' => 'Foreign key added successfully'
            ]);
            break;
            
        case 'drop_foreign_key':
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            $constraintName = $_POST['constraint_name'] ?? '';
            
            if (!$database || !$table || !$constraintName) {
                throw new Exception('Missing required fields');
            }
            
            $designer->dropForeignKey($database, $table, $constraintName);
            
            echo json_encode([
                'success' => true,
                'message' => 'Foreign key dropped successfully'
            ]);
            break;
            
        case 'rename_table':
            $database = $_POST['database'] ?? '';
            $oldName = $_POST['old_name'] ?? '';
            $newName = $_POST['new_name'] ?? '';
            
            if (!$database || !$oldName || !$newName) {
                throw new Exception('Missing required fields');
            }
            
            $designer->renameTable($database, $oldName, $newName);
            
            echo json_encode([
                'success' => true,
                'message' => 'Table renamed successfully'
            ]);
            break;
            
        case 'drop_table':
            $database = $_POST['database'] ?? '';
            $table = $_POST['table'] ?? '';
            
            if (!$database || !$table) {
                throw new Exception('Missing required fields');
            }
            
            $designer->dropTable($database, $table);
            
            echo json_encode([
                'success' => true,
                'message' => 'Table dropped successfully'
            ]);
            break;
            
        case 'generate_sql':
            $database = $_POST['database'] ?? '';
            
            if (!$database) {
                throw new Exception('Database not specified');
            }
            
            $sql = $designer->generateSQL($database);
            
            echo json_encode([
                'success' => true,
                'sql' => $sql
            ]);
            break;
            
        case 'save_file_schema':
            // Save schema changes back to file
            $filename = $_POST['filename'] ?? '';
            $schemaData = json_decode($_POST['schema_data'] ?? '{}', true);
            
            if (!$filename) {
                throw new Exception('Filename not specified');
            }
            
            // Load existing file data
            $fileData = $fileManager->loadSchema($filename);
            
            // Update schema
            $fileData['schema'] = $schemaData;
            $fileData['updated_at'] = date('Y-m-d H:i:s');
            
            // Save back to file
            $result = $fileManager->saveSchema(
                $fileData['name'],
                $schemaData,
                [
                    'description' => $fileData['description'] ?? '',
                    'author' => $fileData['author'] ?? '',
                    'tags' => $fileData['tags'] ?? [],
                    'created_at' => $fileData['created_at'] ?? date('Y-m-d H:i:s')
                ]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Schema saved to file successfully'
            ]);
            break;
            
        case 'save_as_file':
            // Save current database schema as a new file
            $database = $_POST['database'] ?? '';
            $schemaName = $_POST['schema_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $author = $_POST['author'] ?? '';
            
            if (!$database) {
                throw new Exception('Database not specified');
            }
            
            if (!$schemaName) {
                $schemaName = $database;
            }
            
            $result = $fileManager->exportFromDatabase(
                $db->getConnection(),
                $database,
                [
                    'description' => $description,
                    'author' => $author
                ]
            );
            
            echo json_encode($result);
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
