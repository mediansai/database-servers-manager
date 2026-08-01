<?php
/**
 * Database Operations
 * Handles all database-related queries and operations
 */

class DatabaseOperations {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all databases
     */
    public function getDatabases() {
        $stmt = $this->conn->query("SHOW DATABASES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get tables from a database
     */
    public function getTables($database) {
        $this->conn->exec("USE `$database`");
        $stmt = $this->conn->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get tables with metadata (row count, size, etc.)
     */
    public function getTablesWithMetadata($database) {
        $this->conn->exec("USE `$database`");
        $tables = $this->getTables($database);
        $metadata = [];
        
        foreach ($tables as $table) {
            try {
                $countStmt = $this->conn->query("SELECT COUNT(*) FROM `$table`");
                $rowCount = $countStmt->fetchColumn();
                
                // Get table size
                $sizeStmt = $this->conn->prepare("
                    SELECT 
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                    FROM information_schema.TABLES 
                    WHERE table_schema = :db AND table_name = :table
                ");
                $sizeStmt->execute([':db' => $database, ':table' => $table]);
                $sizeInfo = $sizeStmt->fetch(PDO::FETCH_ASSOC);
                
                $metadata[] = [
                    'name' => $table,
                    'rows' => $rowCount,
                    'size' => $sizeInfo['size_mb'] ?? 0
                ];
            } catch (Exception $e) {
                $metadata[] = [
                    'name' => $table,
                    'rows' => 0,
                    'size' => 0
                ];
            }
        }
        
        return $metadata;
    }
    
    /**
     * Get table data with pagination
     */
    public function getTableData($database, $table, $page = 1, $perPage = 50) {
        $this->conn->exec("USE `$database`");
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countStmt = $this->conn->query("SELECT COUNT(*) FROM `$table`");
        $totalRows = $countStmt->fetchColumn();
        
        // Get data
        $stmt = $this->conn->prepare("SELECT * FROM `$table` LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $totalRows,
            'pages' => ceil($totalRows / $perPage)
        ];
    }
    
    /**
     * Get table structure
     */
    public function getTableStructure($database, $table) {
        $this->conn->exec("USE `$database`");
        $stmt = $this->conn->query("DESCRIBE `$table`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get primary key column
     */
    public function getPrimaryKey($database, $table) {
        $this->conn->exec("USE `$database`");
        $stmt = $this->conn->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['Column_name'] : null;
    }
    
    /**
     * Update a cell value
     */
    public function updateCell($database, $table, $primaryKey, $primaryValue, $column, $value) {
        $this->conn->exec("USE `$database`");
        $stmt = $this->conn->prepare("UPDATE `$table` SET `$column` = :value WHERE `$primaryKey` = :id");
        return $stmt->execute([':value' => $value, ':id' => $primaryValue]);
    }
    
    /**
     * Delete a row
     */
    public function deleteRow($database, $table, $primaryKey, $primaryValue) {
        $this->conn->exec("USE `$database`");
        $stmt = $this->conn->prepare("DELETE FROM `$table` WHERE `$primaryKey` = :id");
        return $stmt->execute([':id' => $primaryValue]);
    }
    
    /**
     * Execute a custom query
     */
    public function executeQuery($query, $database = null) {
        if ($database) {
            $this->conn->exec("USE `$database`");
        }
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Alter table column type
     */
    public function alterColumnType($database, $table, $column, $newType) {
        $this->conn->exec("USE `$database`");
        
        // Get current column info
        $stmt = $this->conn->prepare("
            SELECT COLUMN_DEFAULT, IS_NULLABLE, EXTRA 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :col
        ");
        $stmt->execute([':db' => $database, ':table' => $table, ':col' => $column]);
        $colInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Build ALTER statement
        $sql = "ALTER TABLE `$table` MODIFY COLUMN `$column` $newType";
        
        // Add NULL/NOT NULL
        if ($colInfo['IS_NULLABLE'] === 'NO') {
            $sql .= " NOT NULL";
        }
        
        // Add DEFAULT
        if ($colInfo['COLUMN_DEFAULT'] !== null) {
            $sql .= " DEFAULT '" . $colInfo['COLUMN_DEFAULT'] . "'";
        }
        
        // Add EXTRA (AUTO_INCREMENT, etc.)
        if ($colInfo['EXTRA']) {
            $sql .= " " . $colInfo['EXTRA'];
        }
        
        return $this->conn->exec($sql);
    }
}
