<?php
/**
 * Database Designer
 * Handles database schema analysis and diagram data generation
 */

class DatabaseDesigner {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get complete database schema for designer
     */
    public function getDatabaseSchema($database) {
        $this->conn->exec("USE `$database`");
        
        $schema = [
            'tables' => [],
            'relationships' => []
        ];
        
        // Get all tables
        $stmt = $this->conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $schema['tables'][] = $this->getTableSchema($table);
        }
        
        // Get all relationships
        $schema['relationships'] = $this->getRelationships($database);
        
        return $schema;
    }
    
    /**
     * Get detailed table schema
     */
    private function getTableSchema($table) {
        // Get columns
        $stmt = $this->conn->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get indexes
        $stmt = $this->conn->query("SHOW INDEXES FROM `$table`");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get row count
        $stmt = $this->conn->query("SELECT COUNT(*) FROM `$table`");
        $rowCount = $stmt->fetchColumn();
        
        // Get table comment
        $stmt = $this->conn->prepare("
            SELECT TABLE_COMMENT 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);
        $comment = $stmt->fetchColumn();
        
        return [
            'name' => $table,
            'columns' => $this->formatColumns($columns),
            'indexes' => $this->formatIndexes($indexes),
            'rowCount' => $rowCount,
            'comment' => $comment ?: ''
        ];
    }
    
    /**
     * Format columns for designer
     */
    private function formatColumns($columns) {
        $formatted = [];
        
        foreach ($columns as $col) {
            $formatted[] = [
                'name' => $col['Field'],
                'type' => $col['Type'],
                'null' => $col['Null'] === 'YES',
                'key' => $col['Key'],
                'default' => $col['Default'],
                'extra' => $col['Extra']
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Format indexes for designer
     */
    private function formatIndexes($indexes) {
        $formatted = [];
        $grouped = [];
        
        // Group by index name
        foreach ($indexes as $index) {
            $name = $index['Key_name'];
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'name' => $name,
                    'type' => $name === 'PRIMARY' ? 'PRIMARY' : ($index['Non_unique'] == 0 ? 'UNIQUE' : 'INDEX'),
                    'columns' => []
                ];
            }
            $grouped[$name]['columns'][] = $index['Column_name'];
        }
        
        return array_values($grouped);
    }
    
    /**
     * Get all foreign key relationships
     */
    private function getRelationships($database) {
        $stmt = $this->conn->prepare("
            SELECT 
                TABLE_NAME as 'table',
                COLUMN_NAME as 'column',
                CONSTRAINT_NAME as 'constraint_name',
                REFERENCED_TABLE_NAME as 'referenced_table',
                REFERENCED_COLUMN_NAME as 'referenced_column'
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([$database]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Save table positions
     */
    public function saveTablePosition($database, $table, $x, $y) {
        // Store in a positions table (create if not exists)
        $this->ensurePositionsTable();
        
        $stmt = $this->conn->prepare("
            INSERT INTO _designer_positions (database_name, table_name, pos_x, pos_y, updated_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE pos_x = ?, pos_y = ?, updated_at = NOW()
        ");
        
        return $stmt->execute([$database, $table, $x, $y, $x, $y]);
    }
    
    /**
     * Get saved table positions
     */
    public function getTablePositions($database) {
        $this->ensurePositionsTable();
        
        $stmt = $this->conn->prepare("
            SELECT table_name, pos_x, pos_y
            FROM _designer_positions
            WHERE database_name = ?
        ");
        $stmt->execute([$database]);
        
        $positions = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $positions[$row['table_name']] = [
                'x' => (float)$row['pos_x'],
                'y' => (float)$row['pos_y']
            ];
        }
        
        return $positions;
    }
    
    /**
     * Ensure positions table exists
     */
    private function ensurePositionsTable() {
        try {
            $this->conn->exec("
                CREATE TABLE IF NOT EXISTS _designer_positions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    database_name VARCHAR(255) NOT NULL,
                    table_name VARCHAR(255) NOT NULL,
                    pos_x DECIMAL(10,2) NOT NULL,
                    pos_y DECIMAL(10,2) NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_position (database_name, table_name)
                ) ENGINE=InnoDB
            ");
        } catch (PDOException $e) {
            // Table might already exist
        }
    }
    
    /**
     * Create new table
     */
    public function createTable($database, $tableName, $columns, $comment = '') {
        $this->conn->exec("USE `$database`");
        
        $sql = "CREATE TABLE `$tableName` (";
        $columnDefs = [];
        $primaryKeys = [];
        
        foreach ($columns as $col) {
            $def = "`{$col['name']}` {$col['type']}";
            
            // Handle NULL constraint
            if (isset($col['null'])) {
                if ($col['null'] === false || $col['null'] === 0 || $col['null'] === '0') {
                    $def .= " NOT NULL";
                } else {
                    $def .= " NULL";
                }
            }
            
            // Handle default value
            if (isset($col['default']) && $col['default'] !== null && $col['default'] !== '') {
                if (strtoupper($col['default']) === 'NULL') {
                    $def .= " DEFAULT NULL";
                } else if (strtoupper($col['default']) === 'CURRENT_TIMESTAMP') {
                    $def .= " DEFAULT CURRENT_TIMESTAMP";
                } else {
                    $def .= " DEFAULT " . $this->conn->quote($col['default']);
                }
            }
            
            // Handle extra (AUTO_INCREMENT, etc.)
            if (!empty($col['extra'])) {
                $def .= " " . $col['extra'];
            }
            
            // Track primary keys
            if (!empty($col['key']) && $col['key'] === 'PRI') {
                $primaryKeys[] = $col['name'];
            }
            
            $columnDefs[] = $def;
        }
        
        // Add primary key constraint
        if (!empty($primaryKeys)) {
            $columnDefs[] = "PRIMARY KEY (`" . implode("`, `", $primaryKeys) . "`)";
        }
        
        $sql .= implode(", ", $columnDefs) . ")";
        
        // Add table options
        $sql .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($comment) {
            $sql .= " COMMENT=" . $this->conn->quote($comment);
        }
        
        return $this->conn->exec($sql);
    }
    
    /**
     * Add column to table
     */
    public function addColumn($database, $table, $columnName, $columnType, $nullable = true, $defaultValue = null, $after = null) {
        $this->conn->exec("USE `$database`");
        
        $sql = "ALTER TABLE `$table` ADD COLUMN `$columnName` $columnType";
        
        if (!$nullable) {
            $sql .= " NOT NULL";
        }
        
        if ($defaultValue !== null) {
            $sql .= " DEFAULT '$defaultValue'";
        }
        
        if ($after) {
            $sql .= " AFTER `$after`";
        }
        
        return $this->conn->exec($sql);
    }
    
    /**
     * Drop column from table
     */
    public function dropColumn($database, $table, $columnName) {
        $this->conn->exec("USE `$database`");
        return $this->conn->exec("ALTER TABLE `$table` DROP COLUMN `$columnName`");
    }
    
    /**
     * Add foreign key relationship
     */
    public function addForeignKey($database, $table, $column, $refTable, $refColumn, $onUpdate = 'RESTRICT', $onDelete = 'RESTRICT') {
        $this->conn->exec("USE `$database`");
        
        $constraintName = "fk_{$table}_{$column}";
        
        $sql = "ALTER TABLE `$table` 
                ADD CONSTRAINT `$constraintName` 
                FOREIGN KEY (`$column`) 
                REFERENCES `$refTable`(`$refColumn`)
                ON UPDATE $onUpdate
                ON DELETE $onDelete";
        
        return $this->conn->exec($sql);
    }
    
    /**
     * Drop foreign key relationship
     */
    public function dropForeignKey($database, $table, $constraintName) {
        $this->conn->exec("USE `$database`");
        return $this->conn->exec("ALTER TABLE `$table` DROP FOREIGN KEY `$constraintName`");
    }
    
    /**
     * Rename table
     */
    public function renameTable($database, $oldName, $newName) {
        $this->conn->exec("USE `$database`");
        return $this->conn->exec("RENAME TABLE `$oldName` TO `$newName`");
    }
    
    /**
     * Drop table
     */
    public function dropTable($database, $table) {
        $this->conn->exec("USE `$database`");
        return $this->conn->exec("DROP TABLE `$table`");
    }
    
    /**
     * Generate SQL for entire schema
     */
    public function generateSQL($database) {
        $this->conn->exec("USE `$database`");
        
        $sql = "-- Database: $database\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $stmt = $this->conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $stmt = $this->conn->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $sql .= $row['Create Table'] . ";\n\n";
        }
        
        return $sql;
    }
}
