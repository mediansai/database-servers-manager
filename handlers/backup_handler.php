<?php
/**
 * Backup & Restore Handler
 */
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ServerManager.php';
require_once __DIR__ . '/../includes/DatabaseBackup.php';

Auth::require();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Download is a GET request — handle before setting JSON header
if ($action === 'download') {
    $filename = basename($_GET['file'] ?? '');
    if (!$filename) { die('No file specified'); }

    $backup = new DatabaseBackup(Database::getInstance()->getConnection());
    $path   = $backup->getBackupPath($filename);

    if (!$path) { die('Backup not found'); }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

header('Content-Type: application/json');

try {
    $db     = Database::getInstance();
    $backup = new DatabaseBackup($db->getConnection());

    switch ($action) {

        // ------------------------------------------------------------------
        case 'create':
            if (!Auth::isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin role required']);
                exit;
            }
            $database = $_POST['database'] ?? '';
            $tables   = !empty($_POST['tables']) ? (array)$_POST['tables'] : [];

            if (!$database) {
                echo json_encode(['success' => false, 'message' => 'Database required']);
                exit;
            }

            $result = $backup->createBackup($database, $tables, true);
            echo json_encode($result);
            break;

        // ------------------------------------------------------------------
        case 'restore':
            if (!Auth::isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin role required']);
                exit;
            }
            $database = $_POST['database'] ?? '';
            $source   = $_POST['source']   ?? 'upload'; // 'upload' | 'stored'

            if (!$database) {
                echo json_encode(['success' => false, 'message' => 'Target database required']);
                exit;
            }

            if ($source === 'stored') {
                $filename = $_POST['filename'] ?? '';
                $result   = $backup->restoreBackup($database, $filename, true);
            } else {
                // Handle file upload
                if (empty($_FILES['sql_file']['tmp_name'])) {
                    echo json_encode(['success' => false, 'message' => 'No SQL file uploaded']);
                    exit;
                }
                $uploadedFile = $_FILES['sql_file'];
                $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
                if ($ext !== 'sql') {
                    echo json_encode(['success' => false, 'message' => 'Only .sql files are accepted']);
                    exit;
                }
                if ($uploadedFile['size'] > 50 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'File exceeds 50 MB limit']);
                    exit;
                }
                $sql    = file_get_contents($uploadedFile['tmp_name']);
                $result = $backup->restoreBackup($database, $sql, false);
            }

            echo json_encode($result);
            break;

        // ------------------------------------------------------------------
        case 'list':
            $list = $backup->listBackups();
            echo json_encode(['success' => true, 'backups' => $list]);
            break;

        // ------------------------------------------------------------------
        case 'delete':
            if (!Auth::isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin role required']);
                exit;
            }
            $filename = $_POST['filename'] ?? '';
            echo json_encode($backup->deleteBackup($filename));
            break;

        // ------------------------------------------------------------------
        case 'get_tables':
            $database = $_GET['database'] ?? $_POST['database'] ?? '';
            if (!$database) {
                echo json_encode(['success' => false, 'message' => 'Database required']);
                exit;
            }
            $safe  = preg_replace('/[^a-zA-Z0-9_\-]/', '', $database);
            $stmt  = $db->getConnection()->query("SHOW TABLES FROM `$safe`");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode(['success' => true, 'tables' => $tables]);
            break;

        // ------------------------------------------------------------------
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
