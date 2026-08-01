<?php
/**
 * Authentication & Server-Switch Handler
 */
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ServerManager.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ------------------------------------------------------------------
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'POST required']);
            exit;
        }
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Auth::login($username, $password)) {
            echo json_encode(['success' => true, 'name' => Auth::name(), 'role' => Auth::role()]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
        break;

    // ------------------------------------------------------------------
    case 'logout':
        Auth::logout();
        echo json_encode(['success' => true]);
        break;

    // ------------------------------------------------------------------
    case 'switch_server':
        Auth::require();
        $serverId = $_POST['server_id'] ?? '';
        if (ServerManager::switchTo($serverId)) {
            $cfg = ServerManager::getCurrent();
            echo json_encode(['success' => true, 'server' => $cfg['label']]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown server ID']);
        }
        break;

    // ------------------------------------------------------------------
    case 'list_servers':
        Auth::require();
        $servers = ServerManager::getServers();
        $current = ServerManager::getCurrentId();
        $out = [];
        foreach ($servers as $id => $cfg) {
            $out[] = ['id' => $id, 'label' => $cfg['label'], 'host' => $cfg['host'], 'active' => ($id === $current)];
        }
        echo json_encode(['success' => true, 'servers' => $out, 'current' => $current]);
        break;

    // ------------------------------------------------------------------
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
