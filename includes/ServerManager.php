<?php
/**
 * Manages multiple MySQL server connections via session
 */
class ServerManager {

    /** Return all configured servers */
    public static function getServers(): array {
        require_once __DIR__ . '/../config.php';
        return $GLOBALS['DB_SERVERS'] ?? [];
    }

    /** Return current server ID (stored in session) */
    public static function getCurrentId(): string {
        $servers = self::getServers();
        $id = $_SESSION['active_server'] ?? array_key_first($servers);
        // Fall back to first if stored ID no longer exists in config
        return isset($servers[$id]) ? $id : array_key_first($servers);
    }

    /** Return config array for the active server */
    public static function getCurrent(): array {
        $servers = self::getServers();
        return $servers[self::getCurrentId()];
    }

    /**
     * Switch the active server.
     * Resets the Database singleton so the next call reconnects.
     * Returns false when $id is not configured.
     */
    public static function switchTo(string $id): bool {
        $servers = self::getServers();
        if (!isset($servers[$id])) {
            return false;
        }
        $_SESSION['active_server'] = $id;
        Database::resetInstance();
        return true;
    }
}
