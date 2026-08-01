<?php
/**
 * Database File Manager
 * Manages database schemas as standalone files without server connection
 * Allows users to create, save, load, and manage database schemas offline
 */

class DatabaseFileManager {
    private $storageDir;
    private $fileExtension = '.dbschema';
    
    public function __construct($storageDir = null) {
        // Default storage directory
        $this->storageDir = $storageDir ?? __DIR__ . '/../storage/schemas';
        
        // Ensure storage directory exists
        $this->ensureStorageDirectory();
    }
    
    /**
     * Ensure storage directory exists
     */
    private function ensureStorageDirectory() {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    /**
     * Save database schema to file
     */
    public function saveSchema($schemaName, $schemaData, $metadata = []) {
        $filename = $this->sanitizeFilename($schemaName) . $this->fileExtension;
        $filepath = $this->storageDir . '/' . $filename;
        
        $fileData = [
            'name' => $schemaName,
            'version' => '1.0',
            'created_at' => $metadata['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'description' => $metadata['description'] ?? '',
            'author' => $metadata['author'] ?? '',
            'tags' => $metadata['tags'] ?? [],
            'schema' => $schemaData
        ];
        
        $json = json_encode($fileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (file_put_contents($filepath, $json) === false) {
            throw new Exception("Failed to save schema file");
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'size' => filesize($filepath)
        ];
    }
    
    /**
     * Load database schema from file
     */
    public function loadSchema($filename) {
        // Add extension if not present
        if (!str_ends_with($filename, $this->fileExtension)) {
            $filename .= $this->fileExtension;
        }
        
        $filepath = $this->storageDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            throw new Exception("Schema file not found: $filename");
        }
        
        $json = file_get_contents($filepath);
        $data = json_decode($json, true);
        
        if ($data === null) {
            throw new Exception("Invalid schema file format");
        }
        
        return $data;
    }
    
    /**
     * List all saved schema files
     */
    public function listSchemas() {
        $files = glob($this->storageDir . '/*' . $this->fileExtension);
        $schemas = [];
        
        foreach ($files as $filepath) {
            $filename = basename($filepath);
            
            try {
                $data = $this->loadSchema($filename);
                $schemas[] = [
                    'filename' => $filename,
                    'name' => $data['name'] ?? pathinfo($filename, PATHINFO_FILENAME),
                    'description' => $data['description'] ?? '',
                    'author' => $data['author'] ?? '',
                    'version' => $data['version'] ?? '1.0',
                    'created_at' => $data['created_at'] ?? '',
                    'updated_at' => $data['updated_at'] ?? '',
                    'tags' => $data['tags'] ?? [],
                    'size' => filesize($filepath),
                    'table_count' => count($data['schema']['tables'] ?? []),
                    'relationship_count' => count($data['schema']['relationships'] ?? [])
                ];
            } catch (Exception $e) {
                // Skip invalid files
                continue;
            }
        }
        
        // Sort by updated_at descending
        usort($schemas, function($a, $b) {
            return strtotime($b['updated_at']) - strtotime($a['updated_at']);
        });
        
        return $schemas;
    }
    
    /**
     * Delete schema file
     */
    public function deleteSchema($filename) {
        if (!str_ends_with($filename, $this->fileExtension)) {
            $filename .= $this->fileExtension;
        }
        
        $filepath = $this->storageDir . '/' . $filename;
        
        if (!file_exists($filepath)) {
            throw new Exception("Schema file not found");
        }
        
        if (!unlink($filepath)) {
            throw new Exception("Failed to delete schema file");
        }
        
        return ['success' => true];
    }
    
    /**
     * Rename schema file
     */
    public function renameSchema($oldFilename, $newName) {
        if (!str_ends_with($oldFilename, $this->fileExtension)) {
            $oldFilename .= $this->fileExtension;
        }
        
        $oldPath = $this->storageDir . '/' . $oldFilename;
        
        if (!file_exists($oldPath)) {
            throw new Exception("Schema file not found");
        }
        
        // Load and update metadata
        $data = $this->loadSchema($oldFilename);
        $data['name'] = $newName;
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Create new filename
        $newFilename = $this->sanitizeFilename($newName) . $this->fileExtension;
        $newPath = $this->storageDir . '/' . $newFilename;
        
        // Save with new name
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($newPath, $json);
        
        // Delete old file
        unlink($oldPath);
        
        return [
            'success' => true,
            'old_filename' => $oldFilename,
            'new_filename' => $newFilename
        ];
    }
    
    /**
     * Duplicate schema file
     */
    public function duplicateSchema($filename, $newName = null) {
        if (!str_ends_with($filename, $this->fileExtension)) {
            $filename .= $this->fileExtension;
        }
        
        $data = $this->loadSchema($filename);
        
        // Generate new name
        if (!$newName) {
            $newName = $data['name'] . ' (Copy)';
        }
        
        // Update metadata
        $data['name'] = $newName;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Save as new file
        return $this->saveSchema($newName, $data['schema'], [
            'description' => $data['description'],
            'author' => $data['author'],
            'tags' => $data['tags']
        ]);
    }
    
    /**
     * Export schema from live database
     */
    public function exportFromDatabase($conn, $database, $metadata = []) {
        require_once __DIR__ . '/DatabaseDesigner.php';
        
        $designer = new DatabaseDesigner($conn);
        $schema = $designer->getDatabaseSchema($database);
        
        // Add positions if available
        try {
            $positions = $designer->getTablePositions($database);
            foreach ($schema['tables'] as &$table) {
                if (isset($positions[$table['name']])) {
                    $table['position'] = $positions[$table['name']];
                }
            }
        } catch (Exception $e) {
            // Positions table might not exist
        }
        
        return $this->saveSchema($database, $schema, $metadata);
    }
    
    /**
     * Import schema to live database
     */
    public function importToDatabase($conn, $filename, $targetDatabase) {
        $data = $this->loadSchema($filename);
        $schema = $data['schema'];
        
        // Create database if not exists
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$targetDatabase` 
                    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->exec("USE `$targetDatabase`");
        
        require_once __DIR__ . '/DatabaseDesigner.php';
        $designer = new DatabaseDesigner($conn);
        
        $results = [
            'success' => true,
            'tables_created' => 0,
            'relationships_created' => 0,
            'errors' => []
        ];
        
        // Create tables first
        foreach ($schema['tables'] as $table) {
            try {
                $designer->createTable(
                    $targetDatabase,
                    $table['name'],
                    $table['columns'],
                    $table['comment'] ?? ''
                );
                $results['tables_created']++;
                
                // Save table position if available
                if (isset($table['position'])) {
                    $designer->saveTablePosition(
                        $targetDatabase,
                        $table['name'],
                        $table['position']['x'],
                        $table['position']['y']
                    );
                }
            } catch (Exception $e) {
                $results['errors'][] = [
                    'table' => $table['name'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Create relationships
        foreach ($schema['relationships'] as $rel) {
            try {
                $designer->addForeignKey(
                    $targetDatabase,
                    $rel['table'],
                    $rel['column'],
                    $rel['referenced_table'],
                    $rel['referenced_column'],
                    $rel['on_update'] ?? 'RESTRICT',
                    $rel['on_delete'] ?? 'RESTRICT'
                );
                $results['relationships_created']++;
            } catch (Exception $e) {
                $results['errors'][] = [
                    'relationship' => "{$rel['table']}.{$rel['column']} -> {$rel['referenced_table']}.{$rel['referenced_column']}",
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $results['success'] = count($results['errors']) === 0;
        
        return $results;
    }
    
    /**
     * Create new empty schema
     */
    public function createEmptySchema($schemaName, $metadata = []) {
        $schema = [
            'tables' => [],
            'relationships' => []
        ];
        
        return $this->saveSchema($schemaName, $schema, $metadata);
    }
    
    /**
     * Update schema metadata
     */
    public function updateMetadata($filename, $metadata) {
        if (!str_ends_with($filename, $this->fileExtension)) {
            $filename .= $this->fileExtension;
        }
        
        $data = $this->loadSchema($filename);
        
        // Update allowed metadata fields
        $allowedFields = ['description', 'author', 'tags'];
        foreach ($allowedFields as $field) {
            if (isset($metadata[$field])) {
                $data[$field] = $metadata[$field];
            }
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $filepath = $this->storageDir . '/' . $filename;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filepath, $json);
        
        return ['success' => true];
    }
    
    /**
     * Export schema as SQL
     */
    public function exportToSQL($filename) {
        $data = $this->loadSchema($filename);
        $schema = $data['schema'];
        
        $sql = "-- Database Schema: {$data['name']}\n";
        $sql .= "-- Generated from file: $filename\n";
        $sql .= "-- Created: {$data['created_at']}\n";
        $sql .= "-- Updated: {$data['updated_at']}\n\n";
        
        if (!empty($data['description'])) {
            $sql .= "-- Description: {$data['description']}\n\n";
        }
        
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET time_zone = \"+00:00\";\n\n";
        
        // Create tables
        foreach ($schema['tables'] as $table) {
            $sql .= $this->generateTableSQL($table) . "\n\n";
        }
        
        // Add foreign keys
        foreach ($schema['relationships'] as $rel) {
            $sql .= $this->generateRelationshipSQL($rel) . "\n";
        }
        
        return $sql;
    }
    
    /**
     * Generate SQL for table
     */
    private function generateTableSQL($table) {
        $sql = "-- Table: {$table['name']}\n";
        $sql .= "DROP TABLE IF EXISTS `{$table['name']}`;\n";
        $sql .= "CREATE TABLE `{$table['name']}` (\n";
        
        $columnDefs = [];
        $primaryKeys = [];
        
        foreach ($table['columns'] as $col) {
            $def = "  `{$col['name']}` {$col['type']}";
            
            if (!$col['null']) {
                $def .= " NOT NULL";
            }
            
            if (!empty($col['default']) && $col['default'] !== 'NULL') {
                if (in_array(strtoupper($col['default']), ['CURRENT_TIMESTAMP'])) {
                    $def .= " DEFAULT {$col['default']}";
                } else {
                    $def .= " DEFAULT '{$col['default']}'";
                }
            }
            
            if (!empty($col['extra'])) {
                $def .= " {$col['extra']}";
            }
            
            if ($col['key'] === 'PRI') {
                $primaryKeys[] = $col['name'];
            }
            
            $columnDefs[] = $def;
        }
        
        if (!empty($primaryKeys)) {
            $columnDefs[] = "  PRIMARY KEY (`" . implode("`, `", $primaryKeys) . "`)";
        }
        
        $sql .= implode(",\n", $columnDefs);
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!empty($table['comment'])) {
            $sql .= " COMMENT='{$table['comment']}'";
        }
        
        $sql .= ";";
        
        return $sql;
    }
    
    /**
     * Generate SQL for relationship
     */
    private function generateRelationshipSQL($rel) {
        $constraintName = "fk_{$rel['table']}_{$rel['column']}";
        
        $sql = "ALTER TABLE `{$rel['table']}` \n";
        $sql .= "  ADD CONSTRAINT `$constraintName` \n";
        $sql .= "  FOREIGN KEY (`{$rel['column']}`) \n";
        $sql .= "  REFERENCES `{$rel['referenced_table']}`(`{$rel['referenced_column']}`);";
        
        return $sql;
    }
    
    /**
     * Search schemas by name or tags
     */
    public function searchSchemas($query) {
        $allSchemas = $this->listSchemas();
        $query = strtolower($query);
        
        return array_filter($allSchemas, function($schema) use ($query) {
            $searchIn = strtolower($schema['name'] . ' ' . $schema['description']);
            
            if (strpos($searchIn, $query) !== false) {
                return true;
            }
            
            foreach ($schema['tags'] as $tag) {
                if (strpos(strtolower($tag), $query) !== false) {
                    return true;
                }
            }
            
            return false;
        });
    }
    
    /**
     * Get schema statistics
     */
    public function getSchemaStats($filename) {
        $data = $this->loadSchema($filename);
        $schema = $data['schema'];
        
        $stats = [
            'tables' => count($schema['tables']),
            'relationships' => count($schema['relationships']),
            'columns' => 0,
            'primary_keys' => 0,
            'foreign_keys' => count($schema['relationships']),
            'indexes' => 0
        ];
        
        foreach ($schema['tables'] as $table) {
            $stats['columns'] += count($table['columns']);
            
            foreach ($table['columns'] as $col) {
                if ($col['key'] === 'PRI') {
                    $stats['primary_keys']++;
                }
            }
            
            if (isset($table['indexes'])) {
                $stats['indexes'] += count($table['indexes']);
            }
        }
        
        return $stats;
    }
    
    /**
     * Sanitize filename
     */
    private function sanitizeFilename($name) {
        // Remove special characters and spaces
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        
        return $name ?: 'unnamed_schema';
    }
    
    /**
     * Validate schema structure
     */
    public function validateSchema($schema) {
        $errors = [];
        
        if (!isset($schema['tables']) || !is_array($schema['tables'])) {
            $errors[] = "Schema must contain 'tables' array";
        }
        
        if (!isset($schema['relationships']) || !is_array($schema['relationships'])) {
            $errors[] = "Schema must contain 'relationships' array";
        }
        
        // Validate tables
        foreach ($schema['tables'] as $i => $table) {
            if (!isset($table['name']) || empty($table['name'])) {
                $errors[] = "Table #$i is missing 'name'";
            }
            
            if (!isset($table['columns']) || !is_array($table['columns']) || empty($table['columns'])) {
                $errors[] = "Table '{$table['name']}' must have at least one column";
            }
        }
        
        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }
}
