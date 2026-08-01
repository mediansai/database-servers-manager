<?php
/**
 * Database Export and Import Handler
 * Handles SQL export and import operations
 */

class DatabaseExportImport {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Export entire database to SQL
     */
    public function exportDatabase($database) {
        $this->conn->exec("USE `$database`");
        
        $sql = "-- Database Export: $database\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET time_zone = \"+00:00\";\n\n";
        
        // Get all tables
        $stmt = $this->conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $sql .= $this->exportTable($database, $table);
        }
        
        return $sql;
    }
    
    /**
     * Export single table to SQL
     */
    public function exportTable($database, $table) {
        $this->conn->exec("USE `$database`");
        
        $sql = "\n-- --------------------------------------------------------\n\n";
        $sql .= "-- Table structure for table `$table`\n\n";
        
        // Get CREATE TABLE statement
        $stmt = $this->conn->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $createTable = $row['Create Table'];
        
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $createTable . ";\n\n";
        
        // Get table data
        $sql .= "-- Dumping data for table `$table`\n\n";
        
        $stmt = $this->conn->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            $sql .= "INSERT INTO `$table` ($columnList) VALUES\n";
            
            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $rowValues[] = 'NULL';
                    } else {
                        $rowValues[] = "'" . $this->conn->quote($value) . "'";
                    }
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }
            
            $sql .= implode(",\n", $values) . ";\n\n";
        }
        
        return $sql;
    }
    
    /**
     * Import SQL file
     */
    public function importSQL($database, $sqlContent) {
        $this->conn->exec("USE `$database`");
        
        // Remove comments
        $sqlContent = preg_replace('/--[^\n]*\n/', '', $sqlContent);
        $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);
        
        // Split into individual queries
        $queries = array_filter(
            array_map('trim', explode(';', $sqlContent)),
            function($query) {
                return !empty($query);
            }
        );
        
        $executed = 0;
        $errors = [];
        
        foreach ($queries as $query) {
            try {
                $this->conn->exec($query);
                $executed++;
            } catch (PDOException $e) {
                $errors[] = [
                    'query' => substr($query, 0, 100) . '...',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'success' => count($errors) === 0,
            'executed' => $executed,
            'total' => count($queries),
            'errors' => $errors
        ];
    }
    
    /**
     * Export database structure only (no data)
     */
    public function exportStructure($database) {
        $this->conn->exec("USE `$database`");
        
        $sql = "-- Database Structure Export: $database\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET time_zone = \"+00:00\";\n\n";
        
        // Get all tables
        $stmt = $this->conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $sql .= "\n-- Table structure for table `$table`\n\n";
            
            $stmt = $this->conn->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $createTable = $row['Create Table'];
            
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $createTable . ";\n\n";
        }
        
        return $sql;
    }
    
    /**
     * Export table structure only
     */
    public function exportTableStructure($database, $table) {
        $this->conn->exec("USE `$database`");
        
        $sql = "-- Table structure for table `$table`\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $stmt = $this->conn->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $createTable = $row['Create Table'];
        
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $createTable . ";\n";
        
        return $sql;
    }
}
