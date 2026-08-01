<?php
/**
 * Database Connection Handler — supports multiple servers via ServerManager
 */
class Database {
    private static ?Database $instance = null;
    private PDO $conn;
    private string $serverId;

    private function __construct() {
        require_once __DIR__ . '/../config.php';
        require_once __DIR__ . '/ServerManager.php';

        $cfg = ServerManager::getCurrent();
        $this->serverId = ServerManager::getCurrentId();

        try {
            $this->conn = new PDO(
                "mysql:host={$cfg['host']};charset={$cfg['charset']}",
                $cfg['user'],
                $cfg['password']
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES '{$cfg['charset']}'");
        } catch (PDOException $e) {
            throw new \RuntimeException('Connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Called by ServerManager when switching servers */
    public static function resetInstance(): void {
        self::$instance = null;
    }

    public function getConnection(): PDO {
        return $this->conn;
    }

    public function getServerId(): string {
        return $this->serverId;
    }

    public function useDatabase(string $database): void {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $database);
        $this->conn->exec("USE `$safe`");
    }
}

