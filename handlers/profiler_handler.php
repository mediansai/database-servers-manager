<?php
/**
 * Query Profiler AJAX Handler
 */

session_start();
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ServerManager.php';
require_once __DIR__ . '/../includes/DatabaseOperations.php';
require_once __DIR__ . '/../includes/QueryProfiler.php';

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

if (!ALLOW_SQL_QUERIES) {
    echo json_encode(['success' => false, 'message' => 'SQL query execution is disabled on this server']);
    exit;
}

try {
    $db       = Database::getInstance();
    $profiler = new QueryProfiler($db->getConnection());

    switch ($_POST['action']) {

        case 'profile_query':
            $database = $_POST['database'] ?? '';
            $query    = $_POST['query'] ?? '';

            if (!$database || !$query) {
                throw new Exception('Database and query are required');
            }

            $result = $profiler->profile($database, $query);

            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'slow_queries':
            $limit  = isset($_POST['limit']) ? (int)$_POST['limit'] : 25;
            $result = $profiler->getSlowQueries($limit);

            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'history':
            $limit  = isset($_POST['limit']) ? (int)$_POST['limit'] : 20;
            $result = $profiler->getHistory($limit);

            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'clear_history':
            $profiler->clearHistory();
            echo json_encode(['success' => true, 'message' => 'History cleared']);
            break;

        default:
            throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
