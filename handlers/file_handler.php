<?php
/**
 * File Handler
 * Handles file-based database schema operations
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ServerManager.php';
require_once __DIR__ . '/../includes/DatabaseFileManager.php';

if (!Auth::check()) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthenticated']); exit; }

$action = $_REQUEST['action'] ?? '';
$fileManager = new DatabaseFileManager();

try {
    switch ($action) {
        case 'list':
            // List all saved schemas
            $schemas = $fileManager->listSchemas();
            echo json_encode([
                'success' => true,
                'schemas' => $schemas,
                'count' => count($schemas)
            ]);
            break;
            
        case 'load':
            // Load specific schema
            $filename = $_REQUEST['filename'] ?? '';
            if (empty($filename)) {
                throw new Exception('Filename is required');
            }
            
            $data = $fileManager->loadSchema($filename);
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;
            
        case 'save':
            // Save schema
            $schemaName = $_POST['schema_name'] ?? '';
            $schemaData = json_decode($_POST['schema_data'] ?? '{}', true);
            $metadata = [
                'description' => $_POST['description'] ?? '',
                'author' => $_POST['author'] ?? '',
                'tags' => isset($_POST['tags']) ? explode(',', $_POST['tags']) : []
            ];
            
            if (empty($schemaName)) {
                throw new Exception('Schema name is required');
            }
            
            $result = $fileManager->saveSchema($schemaName, $schemaData, $metadata);
            echo json_encode($result);
            break;
            
        case 'delete':
            // Delete schema
            $filename = $_POST['filename'] ?? '';
            if (empty($filename)) {
                throw new Exception('Filename is required');
            }
            
            $result = $fileManager->deleteSchema($filename);
            echo json_encode($result);
            break;
            
        case 'rename':
            // Rename schema
            $oldFilename = $_POST['old_filename'] ?? '';
            $newName = $_POST['new_name'] ?? '';
            
            if (empty($oldFilename) || empty($newName)) {
                throw new Exception('Old filename and new name are required');
            }
            
            $result = $fileManager->renameSchema($oldFilename, $newName);
            echo json_encode($result);
            break;
            
        case 'duplicate':
            // Duplicate schema
            $filename = $_POST['filename'] ?? '';
            $newName = $_POST['new_name'] ?? null;
            
            if (empty($filename)) {
                throw new Exception('Filename is required');
            }
            
            $result = $fileManager->duplicateSchema($filename, $newName);
            echo json_encode($result);
            break;
            
        case 'create_empty':
            // Create new empty schema
            $schemaName = $_POST['schema_name'] ?? '';
            $metadata = [
                'description' => $_POST['description'] ?? '',
                'author' => $_POST['author'] ?? '',
                'tags' => isset($_POST['tags']) ? explode(',', $_POST['tags']) : []
            ];
            
            if (empty($schemaName)) {
                throw new Exception('Schema name is required');
            }
            
            $result = $fileManager->createEmptySchema($schemaName, $metadata);
            echo json_encode($result);
            break;
            
        case 'export_from_db':
            // Export from live database
            $database = $_POST['database'] ?? '';
            $metadata = [
                'description' => $_POST['description'] ?? '',
                'author' => $_POST['author'] ?? '',
                'tags' => isset($_POST['tags']) ? explode(',', $_POST['tags']) : []
            ];
            
            if (empty($database)) {
                throw new Exception('Database name is required');
            }
            
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $result = $fileManager->exportFromDatabase($conn, $database, $metadata);
            echo json_encode($result);
            break;
            
        case 'import_to_db':
            // Import to live database
            $filename = $_POST['filename'] ?? '';
            $targetDatabase = $_POST['target_database'] ?? '';
            
            if (empty($filename) || empty($targetDatabase)) {
                throw new Exception('Filename and target database are required');
            }
            
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $result = $fileManager->importToDatabase($conn, $filename, $targetDatabase);
            echo json_encode($result);
            break;
            
        case 'export_to_sql':
            // Export schema as SQL file
            $filename = $_REQUEST['filename'] ?? '';
            
            if (empty($filename)) {
                throw new Exception('Filename is required');
            }
            
            $sql = $fileManager->exportToSQL($filename);
            $data = $fileManager->loadSchema($filename);
            
            // Return SQL content
            echo json_encode([
                'success' => true,
                'sql' => $sql,
                'filename' => str_replace('.dbschema', '.sql', $filename)
            ]);
            break;
            
        case 'update_metadata':
            // Update schema metadata
            $filename = $_POST['filename'] ?? '';
            $metadata = [
                'description' => $_POST['description'] ?? null,
                'author' => $_POST['author'] ?? null,
                'tags' => isset($_POST['tags']) ? explode(',', $_POST['tags']) : null
            ];
            
            // Remove null values
            $metadata = array_filter($metadata, function($v) { return $v !== null; });
            
            if (empty($filename)) {
                throw new Exception('Filename is required');
            }
            
            $result = $fileManager->updateMetadata($filename, $metadata);
            echo json_encode($result);
            break;
            
        case 'search':
            // Search schemas
            $query = $_GET['query'] ?? '';
            
            if (empty($query)) {
                $schemas = $fileManager->listSchemas();
            } else {
                $schemas = $fileManager->searchSchemas($query);
            }
            
            echo json_encode([
                'success' => true,
                'schemas' => array_values($schemas),
                'count' => count($schemas)
            ]);
            break;
            
        case 'stats':
            // Get schema statistics
            $filename = $_GET['filename'] ?? '';
            
            if (empty($filename)) {
                throw new Exception('Filename is required');
            }
            
            $stats = $fileManager->getSchemaStats($filename);
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'validate':
            // Validate schema
            $schemaData = json_decode($_POST['schema_data'] ?? '{}', true);
            
            $validation = $fileManager->validateSchema($schemaData);
            echo json_encode([
                'success' => true,
                'validation' => $validation
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
