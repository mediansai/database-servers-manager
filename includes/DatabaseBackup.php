<?php
/**
 * Database Backup & Restore
 * Generates and imports mysqldump-style SQL using PDO (no mysqldump binary required)
 */
class DatabaseBackup {

    private PDO $pdo;
    private string $backupDir;

    public function __construct(PDO $pdo) {
        $this->pdo       = $pdo;
        $this->backupDir = BACKUP_DIR;

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    // ------------------------------------------------------------------ //
    //  CREATE BACKUP
    // ------------------------------------------------------------------ //

    /**
     * Create a SQL backup.
     * @param string   $database  Target database name
     * @param string[] $tables    Specific tables; empty = all tables
     * @param bool     $save      When true, save to BACKUP_DIR and return filename; else return SQL string
     */
    public function createBackup(string $database, array $tables = [], bool $save = true): array {
        $this->pdo->exec("USE `" . $this->safeName($database) . "`");

        if (empty($tables)) {
            $tables = $this->listTables($database);
        }

        $sql  = $this->buildHeader($database);
        foreach ($tables as $table) {
            $sql .= $this->dumpTable($database, $table);
        }
        $sql .= "\n-- Backup complete --\n";

        if (!$save) {
            return ['success' => true, 'sql' => $sql];
        }

        $scope    = count($tables) === count($this->listTables($database)) ? 'full' : 'partial';
        $filename = $this->generateFilename($database, $scope, $tables);
        $path     = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($path, $sql) === false) {
            return ['success' => false, 'message' => 'Could not write backup file'];
        }

        return [
            'success'  => true,
            'filename' => $filename,
            'size'     => filesize($path),
            'path'     => $path,
        ];
    }

    // ------------------------------------------------------------------ //
    //  RESTORE BACKUP
    // ------------------------------------------------------------------ //

    /**
     * Restore from an uploaded SQL file or a previously saved backup filename.
     * @param string $database  Target database
     * @param string $sqlOrFile Raw SQL content OR a filename stored in BACKUP_DIR
     * @param bool   $isFile    When true, $sqlOrFile is treated as a filename in BACKUP_DIR
     */
    public function restoreBackup(string $database, string $sqlOrFile, bool $isFile = false): array {
        if ($isFile) {
            $path = $this->backupDir . DIRECTORY_SEPARATOR . basename($sqlOrFile);
            if (!file_exists($path)) {
                return ['success' => false, 'message' => 'Backup file not found'];
            }
            $sql = file_get_contents($path);
        } else {
            $sql = $sqlOrFile;
        }

        if (empty(trim($sql))) {
            return ['success' => false, 'message' => 'Empty SQL content'];
        }

        // Create database if it doesn't exist
        $safe = $this->safeName($database);
        $this->pdo->exec("CREATE DATABASE IF NOT EXISTS `$safe` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->pdo->exec("USE `$safe`");

        // Split SQL into statements and execute
        $statements = $this->splitSQL($sql);
        $errors = [];

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            try {
                $this->pdo->exec($stmt);
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
            }
        }
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1");

        if (!empty($errors)) {
            return [
                'success'  => false,
                'message'  => 'Restore completed with errors',
                'errors'   => $errors,
            ];
        }

        return ['success' => true, 'message' => "Database '$database' restored successfully"];
    }

    // ------------------------------------------------------------------ //
    //  LIST / DELETE BACKUPS
    // ------------------------------------------------------------------ //

    public function listBackups(): array {
        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        $list  = [];

        foreach ($files as $path) {
            $name    = basename($path);
            $parts   = $this->parseFilename($name);
            $list[]  = [
                'filename' => $name,
                'size'     => filesize($path),
                'size_str' => $this->formatSize(filesize($path)),
                'created'  => date('Y-m-d H:i:s', filemtime($path)),
                'database' => $parts['database'] ?? 'unknown',
                'scope'    => $parts['scope']    ?? 'unknown',
                'tables'   => $parts['tables']   ?? [],
            ];
        }

        // Newest first
        usort($list, fn($a, $b) => strcmp($b['created'], $a['created']));
        return $list;
    }

    public function deleteBackup(string $filename): array {
        $path = $this->backupDir . DIRECTORY_SEPARATOR . basename($filename);
        if (!file_exists($path)) {
            return ['success' => false, 'message' => 'File not found'];
        }
        unlink($path);
        return ['success' => true, 'message' => 'Backup deleted'];
    }

    public function getBackupPath(string $filename): ?string {
        $path = $this->backupDir . DIRECTORY_SEPARATOR . basename($filename);
        return file_exists($path) ? $path : null;
    }

    // ------------------------------------------------------------------ //
    //  INTERNAL HELPERS
    // ------------------------------------------------------------------ //

    private function listTables(string $database): array {
        $safe  = $this->safeName($database);
        $stmt  = $this->pdo->query("SHOW TABLES FROM `$safe`");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function buildHeader(string $database): string {
        $ts = date('Y-m-d H:i:s');
        $server = ServerManager::getCurrentId();
        return implode("\n", [
            "-- Database Manager Backup",
            "-- Database : $database",
            "-- Server   : $server",
            "-- Created  : $ts",
            "-- PHP      : " . PHP_VERSION,
            "",
            "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";",
            "SET time_zone = \"+00:00\";",
            "SET FOREIGN_KEY_CHECKS=0;",
            "",
            "CREATE DATABASE IF NOT EXISTS `" . $this->safeName($database) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
            "USE `" . $this->safeName($database) . "`;",
            "",
        ]);
    }

    private function dumpTable(string $database, string $table): string {
        $safeDb  = $this->safeName($database);
        $safeTbl = $this->safeName($table);

        $out = "\n-- --------------------------------------------------------\n";
        $out .= "-- Table structure for `$table`\n";
        $out .= "-- --------------------------------------------------------\n\n";
        $out .= "DROP TABLE IF EXISTS `$safeTbl`;\n";

        // CREATE TABLE
        $row = $this->pdo->query("SHOW CREATE TABLE `$safeDb`.`$safeTbl`")->fetch(PDO::FETCH_NUM);
        $out .= $row[1] . ";\n\n";

        // Row data
        $count = $this->pdo->query("SELECT COUNT(*) FROM `$safeDb`.`$safeTbl`")->fetchColumn();
        if ($count > 0) {
            $out .= "-- Data for table `$table`\n\n";
            $stmt = $this->pdo->query("SELECT * FROM `$safeDb`.`$safeTbl`");
            $cols = null;
            $chunk = [];

            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($cols === null) {
                    $cols = '`' . implode('`, `', array_keys($r)) . '`';
                }
                $values = array_map(fn($v) => $v === null ? 'NULL' : $this->pdo->quote($v), array_values($r));
                $chunk[] = '(' . implode(', ', $values) . ')';

                if (count($chunk) >= 500) {
                    $out   .= "INSERT INTO `$safeTbl` ($cols) VALUES\n" . implode(",\n", $chunk) . ";\n";
                    $chunk  = [];
                }
            }
            if (!empty($chunk)) {
                $out .= "INSERT INTO `$safeTbl` ($cols) VALUES\n" . implode(",\n", $chunk) . ";\n";
            }
        }

        return $out . "\n";
    }

    /** Split a SQL dump into individual statements (handles delimiters & quoted strings) */
    private function splitSQL(string $sql): array {
        $statements = [];
        $current    = '';
        $lines      = explode("\n", $sql);

        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '--') || $trimmed === '') {
                continue;
            }
            $current .= $line . "\n";
            if (str_ends_with(rtrim($line), ';')) {
                $statements[] = $current;
                $current = '';
            }
        }
        if (trim($current) !== '') {
            $statements[] = $current;
        }
        return $statements;
    }

    private function safeName(string $name): string {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);
    }

    private function generateFilename(string $database, string $scope, array $tables): string {
        $ts     = date('Ymd_His');
        $tblStr = $scope === 'partial' ? '_' . implode('-', array_slice($tables, 0, 3)) : '';
        if ($scope === 'partial' && count($tables) > 3) {
            $tblStr .= '_and_more';
        }
        return "{$database}_{$scope}{$tblStr}_{$ts}.sql";
    }

    private function parseFilename(string $name): array {
        // Pattern: dbname_scope_timestamp.sql
        $name = str_replace('.sql', '', $name);
        $parts = explode('_', $name, 3);
        return [
            'database' => $parts[0] ?? 'unknown',
            'scope'    => $parts[1] ?? 'unknown',
            'tables'   => [],
        ];
    }

    private function formatSize(int $bytes): string {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
