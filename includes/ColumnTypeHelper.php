<?php
/**
 * Column Type Helper
 * Provides utilities for handling different MySQL column types
 */

class ColumnTypeHelper {
    
    /**
     * Get input type based on MySQL column type
     */
    public static function getInputType($columnType) {
        $type = strtolower($columnType);
        
        // Integer types
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)/', $type)) {
            return 'number';
        }
        
        // Decimal/Float types
        if (preg_match('/^(decimal|numeric|float|double|real)/', $type)) {
            return 'number';
        }
        
        // Date/Time types
        if (preg_match('/^date$/', $type)) {
            return 'date';
        }
        if (preg_match('/^datetime/', $type)) {
            return 'datetime-local';
        }
        if (preg_match('/^time/', $type)) {
            return 'time';
        }
        if (preg_match('/^timestamp/', $type)) {
            return 'datetime-local';
        }
        if (preg_match('/^year/', $type)) {
            return 'number';
        }
        
        // Text types (long text)
        if (preg_match('/^(text|mediumtext|longtext|blob)/', $type)) {
            return 'textarea';
        }
        
        // Enum/Set types
        if (preg_match('/^enum/', $type)) {
            return 'select';
        }
        if (preg_match('/^set/', $type)) {
            return 'multiselect';
        }
        
        // Boolean
        if (preg_match('/^(boolean|bool|tinyint\(1\))/', $type)) {
            return 'checkbox';
        }
        
        // Default to text
        return 'text';
    }
    
    /**
     * Get enum values from column type
     */
    public static function getEnumValues($columnType) {
        if (preg_match("/^enum\('(.+)'\)$/i", $columnType, $matches)) {
            return explode("','", $matches[1]);
        }
        return [];
    }
    
    /**
     * Get set values from column type
     */
    public static function getSetValues($columnType) {
        if (preg_match("/^set\('(.+)'\)$/i", $columnType, $matches)) {
            return explode("','", $matches[1]);
        }
        return [];
    }
    
    /**
     * Format value for display based on type
     */
    public static function formatValue($value, $columnType) {
        if ($value === null) {
            return '<span class="text-gray-400 italic">NULL</span>';
        }
        
        $type = strtolower($columnType);
        
        // Boolean display
        if (preg_match('/^(boolean|bool|tinyint\(1\))/', $type)) {
            return $value ? '<span class="text-green-600">✓ True</span>' : '<span class="text-red-600">✗ False</span>';
        }
        
        // Date/Time formatting
        if (preg_match('/^(date|datetime|timestamp)/', $type)) {
            try {
                $date = new DateTime($value);
                return $date->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                return htmlspecialchars($value);
            }
        }
        
        // Truncate long text
        if (preg_match('/^(text|mediumtext|longtext)/', $type) && strlen($value) > 100) {
            return htmlspecialchars(substr($value, 0, 100)) . '...';
        }
        
        return htmlspecialchars($value);
    }
    
    /**
     * Get common MySQL data types for alter column
     */
    public static function getCommonDataTypes() {
        return [
            'Integer Types' => [
                'TINYINT' => 'TINYINT',
                'SMALLINT' => 'SMALLINT',
                'MEDIUMINT' => 'MEDIUMINT',
                'INT' => 'INT',
                'BIGINT' => 'BIGINT',
            ],
            'Decimal Types' => [
                'DECIMAL(10,2)' => 'DECIMAL(10,2)',
                'FLOAT' => 'FLOAT',
                'DOUBLE' => 'DOUBLE',
            ],
            'String Types' => [
                'VARCHAR(255)' => 'VARCHAR(255)',
                'VARCHAR(100)' => 'VARCHAR(100)',
                'VARCHAR(50)' => 'VARCHAR(50)',
                'CHAR(10)' => 'CHAR(10)',
                'TEXT' => 'TEXT',
                'MEDIUMTEXT' => 'MEDIUMTEXT',
                'LONGTEXT' => 'LONGTEXT',
            ],
            'Date/Time Types' => [
                'DATE' => 'DATE',
                'DATETIME' => 'DATETIME',
                'TIMESTAMP' => 'TIMESTAMP',
                'TIME' => 'TIME',
                'YEAR' => 'YEAR',
            ],
            'Binary Types' => [
                'BLOB' => 'BLOB',
                'MEDIUMBLOB' => 'MEDIUMBLOB',
                'LONGBLOB' => 'LONGBLOB',
            ],
            'Other Types' => [
                'BOOLEAN' => 'BOOLEAN',
                'ENUM' => 'ENUM',
                'SET' => 'SET',
                'JSON' => 'JSON',
            ],
        ];
    }
    
    /**
     * Validate value based on column type
     */
    public static function validateValue($value, $columnType) {
        $type = strtolower($columnType);
        
        // Allow NULL
        if ($value === null || $value === '') {
            return true;
        }
        
        // Integer validation
        if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)/', $type)) {
            return filter_var($value, FILTER_VALIDATE_INT) !== false;
        }
        
        // Float validation
        if (preg_match('/^(decimal|numeric|float|double|real)/', $type)) {
            return is_numeric($value);
        }
        
        // Date validation
        if (preg_match('/^date$/', $type)) {
            return (bool) strtotime($value);
        }
        
        return true; // Default to allowing value
    }
}
