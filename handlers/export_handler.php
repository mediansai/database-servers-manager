<?php
/**
 * Export Handler
 */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/ServerManager.php';
require_once __DIR__ . '/../includes/LaravelMigrationGenerator.php';
require_once __DIR__ . '/../includes/DatabaseExportImport.php';
require_once __DIR__ . '/../includes/SchemaExporter.php';

Auth::require();

// Get request parameters
$action = $_GET['action'] ?? '';
$database = $_GET['database'] ?? '';
$table = $_GET['table'] ?? '';
$type = $_GET['type'] ?? 'complete'; // complete, structure, data

if (!$database) {
    die('Database not specified');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    switch ($action) {
        case 'laravel_migration':
            // Generate Laravel migrations
            $generator = new LaravelMigrationGenerator($conn);
            
            if ($table) {
                // Single table migration
                $migration = $generator->generateTableMigration($database, $table);
                
                // Set headers for download
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $migration['filename'] . '"');
                header('Content-Length: ' . strlen($migration['content']));
                
                echo $migration['content'];
            } else {
                // Full database migrations (as ZIP)
                $migrations = $generator->generateDatabaseMigrations($database);
                
                // Create temporary directory
                $tempDir = sys_get_temp_dir() . '/migrations_' . time();
                mkdir($tempDir);
                
                // Write migration files
                foreach ($migrations as $migration) {
                    file_put_contents($tempDir . '/' . $migration['filename'], $migration['content']);
                }
                
                // Create ZIP file
                $zipFile = sys_get_temp_dir() . '/' . $database . '_migrations.zip';
                $zip = new ZipArchive();
                
                if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    $files = scandir($tempDir);
                    foreach ($files as $file) {
                        if ($file !== '.' && $file !== '..') {
                            $zip->addFile($tempDir . '/' . $file, $file);
                        }
                    }
                    $zip->close();
                }
                
                // Download ZIP file
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $database . '_migrations.zip"');
                header('Content-Length: ' . filesize($zipFile));
                
                readfile($zipFile);
                
                // Clean up
                array_map('unlink', glob("$tempDir/*"));
                rmdir($tempDir);
                unlink($zipFile);
            }
            break;
            
        case 'sql':
            // Export as SQL
            $exporter = new DatabaseExportImport($conn);
            
            if ($table) {
                // Single table export
                if ($type === 'structure') {
                    $sql = $exporter->exportTableStructure($database, $table);
                    $filename = $database . '_' . $table . '_structure.sql';
                } else {
                    $sql = $exporter->exportTable($database, $table);
                    $filename = $database . '_' . $table . '.sql';
                }
            } else {
                // Full database export
                if ($type === 'structure') {
                    $sql = $exporter->exportStructure($database);
                    $filename = $database . '_structure.sql';
                } else {
                    $sql = $exporter->exportDatabase($database);
                    $filename = $database . '.sql';
                }
            }
            
            // Set headers for download
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($sql));
            
            echo $sql;
            break;
            
        case 'sqlite':
            $exporter    = new SchemaExporter($conn);
            $includeData = ($type !== 'structure');
            $content     = $exporter->exportSQLite($database, $table ?: null, $includeData);
            $suffix      = $table ? "_{$table}" : '';
            $filename    = $database . $suffix . ($type === 'structure' ? '_structure' : '') . '.sql';
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            break;

        case 'prisma':
            $exporter = new SchemaExporter($conn);
            $content  = $exporter->exportPrisma($database, $table ?: null);
            $suffix   = $table ? "_{$table}" : '';
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $database . $suffix . '.prisma"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            break;

        case 'typescript':
            $exporter = new SchemaExporter($conn);
            $content  = $exporter->exportTypeScript($database, $table ?: null);
            $suffix   = $table ? "_{$table}" : '';
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $database . $suffix . '.types.ts"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            break;

        case 'zod':
            $exporter = new SchemaExporter($conn);
            $content  = $exporter->exportZodSchemas($database, $table ?: null);
            $suffix   = $table ? "_{$table}" : '';
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $database . $suffix . '.schemas.ts"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            break;

        case 'json_schema':
            $exporter = new SchemaExporter($conn);
            $content  = $exporter->exportJSONSchema($database, $table ?: null);
            $suffix   = $table ? "_{$table}" : '';
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $database . $suffix . '.schema.json"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            break;

        case 'django':
            $exporter = new SchemaExporter($conn);
            $content  = $exporter->exportDjango($database, $table ?: null);
            $suffix   = $table ? "_{$table}" : '';
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $database . $suffix . '_models.py"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            break;

        case 'sequelize':
            $exporter = new SchemaExporter($conn);
            $content  = $exporter->exportSequelize($database, $table ?: null);
            $suffix   = $table ? "_{$table}" : '';
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $database . $suffix . '_models.js"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            break;

        case 'mongoose':
            $exporter = new SchemaExporter($conn);
            $content  = $exporter->exportMongoose($database, $table ?: null);
            $suffix   = $table ? "_{$table}" : '';
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $database . $suffix . '_schemas.js"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            break;

        default:
            die('Invalid action');
    }
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
