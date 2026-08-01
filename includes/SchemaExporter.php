<?php
/**
 * Schema Exporter — converts a live MySQL database into various formats
 */
class SchemaExporter {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    // ── Public API ──────────────────────────────────────────────────────────

    public function exportSQLite(string $database, ?string $table = null, bool $includeData = true): string {
        $this->use($database);
        $tables = $table ? [$table] : $this->listTables();

        $out  = "-- SQLite Export: $database\n-- Generated: " . date('Y-m-d H:i:s') . "\nPRAGMA foreign_keys = ON;\n\n";
        foreach ($tables as $tbl) {
            $out .= $this->sqliteTable($database, $tbl);
            if ($includeData) $out .= $this->sqliteData($tbl);
        }
        return $out;
    }

    public function exportPrisma(string $database, ?string $table = null): string {
        $this->use($database);
        $tables = $table ? [$table] : $this->listTables();

        $out  = "// Prisma Schema — generated from \"$database\"\n// Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $out .= "generator client {\n  provider = \"prisma-client-js\"\n}\n\n";
        $out .= "datasource db {\n  provider = \"mysql\"\n  url      = env(\"DATABASE_URL\")\n}\n\n";
        foreach ($tables as $tbl) $out .= $this->prismaModel($database, $tbl);
        return $out;
    }

    public function exportTypeScript(string $database, ?string $table = null): string {
        $this->use($database);
        $tables = $table ? [$table] : $this->listTables();

        $out  = "// TypeScript Interfaces — generated from \"$database\"\n// Generated: " . date('Y-m-d H:i:s') . "\n\n";
        foreach ($tables as $tbl) $out .= $this->tsInterface($tbl);
        return $out;
    }

    public function exportZodSchemas(string $database, ?string $table = null): string {
        $this->use($database);
        $tables = $table ? [$table] : $this->listTables();

        $out  = "// Zod Schemas — generated from \"$database\"\n// Generated: " . date('Y-m-d H:i:s') . "\n";
        $out .= "import { z } from 'zod';\n\n";
        foreach ($tables as $tbl) $out .= $this->zodSchema($tbl);
        return $out;
    }

    public function exportJSONSchema(string $database, ?string $table = null): string {
        $this->use($database);
        $tables = $table ? [$table] : $this->listTables();

        $defs = [];
        foreach ($tables as $tbl) $defs[$tbl] = $this->jsonSchemaTable($tbl);

        return json_encode([
            '$schema'     => 'http://json-schema.org/draft-07/schema#',
            'title'       => $database,
            'generated'   => date('Y-m-d H:i:s'),
            'definitions' => $defs,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function exportDjango(string $database, ?string $table = null): string {
        $this->use($database);
        $tables = $table ? [$table] : $this->listTables();

        $out  = "# Django Models — generated from \"$database\"\n# Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $out .= "from django.db import models\n\n\n";
        foreach ($tables as $tbl) $out .= $this->djangoModel($database, $tbl);
        return $out;
    }

    public function exportSequelize(string $database, ?string $table = null): string {
        $this->use($database);
        $tables = $table ? [$table] : $this->listTables();

        $out  = "// Sequelize Models — generated from \"$database\"\n// Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $out .= "const { DataTypes } = require('sequelize');\n\n";
        foreach ($tables as $tbl) $out .= $this->sequelizeModel($database, $tbl);
        return $out;
    }

    public function exportMongoose(string $database, ?string $table = null): string {
        $this->use($database);
        $tables = $table ? [$table] : $this->listTables();

        $out  = "// Mongoose Schemas — generated from \"$database\"\n// Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $out .= "const mongoose = require('mongoose');\nconst { Schema } = mongoose;\n\n";
        foreach ($tables as $tbl) $out .= $this->mongooseSchema($tbl);
        return $out;
    }

    // ── SQLite ───────────────────────────────────────────────────────────────

    private function sqliteTable(string $database, string $table): string {
        $cols  = $this->cols($table);
        $fks   = $this->fks($database, $table);
        $hasAI = false;
        $pkCols = [];
        $defs  = [];

        foreach ($cols as $c) {
            $type = $this->sqliteType($c['Type']);
            $def  = "  `{$c['Field']}` $type";
            $isAI = stripos($c['Extra'], 'auto_increment') !== false;

            if ($isAI) {
                $def    .= " PRIMARY KEY AUTOINCREMENT";
                $hasAI   = true;
            } else {
                if ($c['Key'] === 'PRI') $pkCols[] = $c['Field'];
                if ($c['Null'] === 'NO') $def .= " NOT NULL";
                if ($c['Key'] === 'UNI') $def .= " UNIQUE";
                if ($c['Default'] !== null) $def .= " DEFAULT " . $this->sqliteLiteral($c['Default'], $type);
            }
            $defs[] = $def;
        }

        if (!$hasAI && !empty($pkCols)) {
            $defs[] = "  PRIMARY KEY (`" . implode("`, `", $pkCols) . "`)";
        }
        foreach ($fks as $fk) {
            $def = "  FOREIGN KEY (`{$fk['COLUMN_NAME']}`) REFERENCES `{$fk['REFERENCED_TABLE_NAME']}`(`{$fk['REFERENCED_COLUMN_NAME']}`)";
            if (($fk['DELETE_RULE'] ?? 'RESTRICT') !== 'RESTRICT') $def .= " ON DELETE {$fk['DELETE_RULE']}";
            if (($fk['UPDATE_RULE'] ?? 'RESTRICT') !== 'RESTRICT') $def .= " ON UPDATE {$fk['UPDATE_RULE']}";
            $defs[] = $def;
        }

        return "CREATE TABLE IF NOT EXISTS `$table` (\n" . implode(",\n", $defs) . "\n);\n\n";
    }

    private function sqliteData(string $table): string {
        $rows = $this->conn->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) return '';

        $out  = "-- Data for `$table`\n";
        $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
        foreach ($rows as $row) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $this->conn->quote($v), array_values($row));
            $out .= "INSERT INTO `$table` ($cols) VALUES (" . implode(', ', $vals) . ");\n";
        }
        return $out . "\n";
    }

    private function sqliteType(string $t): string {
        $base = strtolower(preg_replace('/\s*\(.*\)/', '', $t));
        $map  = [
            'tinyint' => 'INTEGER', 'smallint' => 'INTEGER', 'mediumint' => 'INTEGER',
            'int' => 'INTEGER', 'integer' => 'INTEGER', 'bigint' => 'INTEGER',
            'bit' => 'INTEGER', 'year' => 'INTEGER',
            'float' => 'REAL', 'double' => 'REAL', 'decimal' => 'REAL', 'numeric' => 'REAL',
            'binary' => 'BLOB', 'varbinary' => 'BLOB',
            'tinyblob' => 'BLOB', 'blob' => 'BLOB', 'mediumblob' => 'BLOB', 'longblob' => 'BLOB',
        ];
        return $map[trim($base)] ?? 'TEXT';
    }

    private function sqliteLiteral(string $val, string $type): string {
        $up = strtoupper($val);
        if (in_array($up, ['NULL', 'CURRENT_TIMESTAMP', 'CURRENT_DATE', 'CURRENT_TIME'])) return $val;
        if (in_array($type, ['INTEGER', 'REAL']) && is_numeric($val)) return $val;
        return $this->conn->quote($val);
    }

    // ── Prisma ───────────────────────────────────────────────────────────────

    private function prismaModel(string $database, string $table): string {
        $cols    = $this->cols($table);
        $fks     = $this->fks($database, $table);
        $fkIdx   = array_column($fks, null, 'COLUMN_NAME');
        $model   = $this->pascal($table);
        $fields  = [];
        $rels    = [];

        foreach ($cols as $c) {
            $f     = $c['Field'];
            $pType = $this->prismaType($c['Type']);
            $null  = $c['Null'] === 'YES' ? '?' : '';
            $attrs = [];

            if ($c['Key'] === 'PRI') {
                $attrs[] = '@id';
                if (stripos($c['Extra'], 'auto_increment') !== false) $attrs[] = '@default(autoincrement())';
            }
            if ($c['Key'] === 'UNI') $attrs[] = '@unique';
            if (stripos($c['Extra'], 'on update current_timestamp') !== false) $attrs[] = '@updatedAt';
            if ($c['Default'] !== null && $c['Key'] !== 'PRI') {
                $d = $c['Default'];
                if ($d === 'CURRENT_TIMESTAMP')           $attrs[] = '@default(now())';
                elseif (is_numeric($d))                   $attrs[] = "@default($d)";
                elseif (in_array(strtolower($d), ['true','false'])) $attrs[] = "@default(" . strtolower($d) . ")";
                else                                      $attrs[] = "@default(\"$d\")";
            }

            $fields[] = "  $f $pType$null" . ($attrs ? ' ' . implode(' ', $attrs) : '');

            if (isset($fkIdx[$f])) {
                $fk  = $fkIdx[$f];
                $ref = $this->pascal($fk['REFERENCED_TABLE_NAME']);
                $rels[] = "  {$fk['REFERENCED_TABLE_NAME']} {$ref} @relation(fields: [$f], references: [{$fk['REFERENCED_COLUMN_NAME']}])";
            }
        }

        $fields[] = '';
        $fields[] = "  @@map(\"$table\")";
        $body      = implode("\n", $fields);
        if ($rels) $body .= "\n\n" . implode("\n", $rels);

        return "model $model {\n$body\n}\n\n";
    }

    private function prismaType(string $t): string {
        if (strtolower(trim($t)) === 'tinyint(1)') return 'Boolean';
        $base = strtolower(preg_replace('/\s*\(.*\)/', '', $t));
        $map  = [
            'tinyint' => 'Int', 'smallint' => 'Int', 'mediumint' => 'Int',
            'int' => 'Int', 'integer' => 'Int', 'bit' => 'Int',
            'bigint' => 'BigInt',
            'float' => 'Float', 'double' => 'Float',
            'decimal' => 'Decimal', 'numeric' => 'Decimal',
            'date' => 'DateTime', 'datetime' => 'DateTime', 'timestamp' => 'DateTime',
            'binary' => 'Bytes', 'varbinary' => 'Bytes',
            'tinyblob' => 'Bytes', 'blob' => 'Bytes', 'mediumblob' => 'Bytes', 'longblob' => 'Bytes',
            'json' => 'Json',
        ];
        return $map[trim($base)] ?? 'String';
    }

    // ── TypeScript ────────────────────────────────────────────────────────────

    private function tsInterface(string $table): string {
        $cols   = $this->cols($table);
        $name   = $this->pascal($table);
        $lines  = [];
        foreach ($cols as $c) {
            $lines[] = "  {$c['Field']}: " . $this->tsType($c['Type']) . ($c['Null'] === 'YES' ? ' | null' : '') . ';';
        }
        return "export interface $name {\n" . implode("\n", $lines) . "\n}\n\n";
    }

    private function tsType(string $t): string {
        if (strtolower(trim($t)) === 'tinyint(1)') return 'boolean';
        $base = strtolower(preg_replace('/\s*\(.*\)/', '', $t));
        $map  = [
            'tinyint' => 'number', 'smallint' => 'number', 'mediumint' => 'number',
            'int' => 'number', 'integer' => 'number', 'bigint' => 'number',
            'float' => 'number', 'double' => 'number', 'decimal' => 'number', 'numeric' => 'number',
            'year' => 'number', 'bit' => 'boolean',
            'tinyblob' => 'Buffer', 'blob' => 'Buffer', 'mediumblob' => 'Buffer', 'longblob' => 'Buffer',
            'binary' => 'Buffer', 'varbinary' => 'Buffer',
            'json' => 'Record<string, unknown>',
        ];
        return $map[trim($base)] ?? 'string';
    }

    // ── Zod ──────────────────────────────────────────────────────────────────

    private function zodSchema(string $table): string {
        $cols  = $this->cols($table);
        $name  = $this->pascal($table);
        $lines = [];
        foreach ($cols as $c) {
            $zType   = $this->zodType($c['Type']);
            $optional = $c['Null'] === 'YES' ? '.nullable()' : '';
            $lines[] = "  {$c['Field']}: $zType$optional,";
        }
        $schema = lcfirst($name) . 'Schema';
        return "export const $schema = z.object({\n" . implode("\n", $lines) . "\n});\n"
             . "export type $name = z.infer<typeof $schema>;\n\n";
    }

    private function zodType(string $t): string {
        if (strtolower(trim($t)) === 'tinyint(1)') return 'z.boolean()';
        $base = strtolower(preg_replace('/\s*\(.*\)/', '', $t));
        $map  = [
            'tinyint' => 'z.number().int()', 'smallint' => 'z.number().int()',
            'mediumint' => 'z.number().int()', 'int' => 'z.number().int()',
            'integer' => 'z.number().int()', 'bigint' => 'z.bigint()',
            'float' => 'z.number()', 'double' => 'z.number()',
            'decimal' => 'z.number()', 'numeric' => 'z.number()', 'year' => 'z.number().int()',
            'bit' => 'z.boolean()',
            'date' => 'z.string().date()', 'datetime' => 'z.string().datetime()',
            'timestamp' => 'z.string().datetime()', 'time' => 'z.string()',
            'json' => 'z.record(z.unknown())',
            'tinyblob' => 'z.instanceof(Buffer)', 'blob' => 'z.instanceof(Buffer)',
            'mediumblob' => 'z.instanceof(Buffer)', 'longblob' => 'z.instanceof(Buffer)',
        ];
        return $map[trim($base)] ?? 'z.string()';
    }

    // ── JSON Schema ───────────────────────────────────────────────────────────

    private function jsonSchemaTable(string $table): array {
        $cols  = $this->cols($table);
        $props = [];
        $req   = [];
        foreach ($cols as $c) {
            [$jType, $fmt] = $this->jsonSchemaType($c['Type']);
            $prop = ['type' => $jType];
            if ($fmt) $prop['format'] = $fmt;
            $props[$c['Field']] = $prop;
            if ($c['Null'] === 'NO' && $c['Default'] === null && stripos($c['Extra'], 'auto_increment') === false) {
                $req[] = $c['Field'];
            }
        }
        return ['type' => 'object', 'properties' => $props, 'required' => $req];
    }

    private function jsonSchemaType(string $t): array {
        if (strtolower(trim($t)) === 'tinyint(1)') return ['boolean', null];
        $base = strtolower(preg_replace('/\s*\(.*\)/', '', $t));
        $map  = [
            'tinyint' => ['integer', null], 'smallint' => ['integer', null],
            'mediumint' => ['integer', null], 'int' => ['integer', null],
            'integer' => ['integer', null], 'bigint' => ['integer', null], 'year' => ['integer', null],
            'float' => ['number', null], 'double' => ['number', null],
            'decimal' => ['number', null], 'numeric' => ['number', null],
            'date' => ['string', 'date'], 'datetime' => ['string', 'date-time'],
            'timestamp' => ['string', 'date-time'], 'time' => ['string', 'time'],
            'json' => ['object', null], 'bit' => ['boolean', null],
            'binary' => ['string', 'binary'], 'varbinary' => ['string', 'binary'],
            'tinyblob' => ['string', 'binary'], 'blob' => ['string', 'binary'],
            'mediumblob' => ['string', 'binary'], 'longblob' => ['string', 'binary'],
        ];
        return $map[trim($base)] ?? ['string', null];
    }

    // ── Django ────────────────────────────────────────────────────────────────

    private function djangoModel(string $database, string $table): string {
        $cols    = $this->cols($table);
        $fks     = $this->fks($database, $table);
        $fkIdx   = array_column($fks, null, 'COLUMN_NAME');
        $model   = $this->pascal($table);
        $lines   = [];

        foreach ($cols as $c) {
            $f = $c['Field'];
            // Skip standard auto-PK named 'id' (Django adds it automatically)
            if ($c['Key'] === 'PRI' && stripos($c['Extra'], 'auto_increment') !== false && $f === 'id') continue;

            if (isset($fkIdx[$f])) {
                $fk      = $fkIdx[$f];
                $ref     = $this->pascal($fk['REFERENCED_TABLE_NAME']);
                $onDel   = $this->djangoOnDelete($fk['DELETE_RULE'] ?? 'RESTRICT');
                $extra   = $c['Null'] === 'YES' ? ', null=True, blank=True' : '';
                $lines[] = "    $f = models.ForeignKey('$ref', on_delete=models.$onDel, db_column='$f'$extra)";
                continue;
            }
            $lines[] = "    $f = " . $this->djangoField($c);
        }

        $body = $lines ? implode("\n", $lines) : "    pass";
        return "class $model(models.Model):\n$body\n\n    class Meta:\n        db_table = '$table'\n\n\n";
    }

    private function djangoField(array $c): string {
        $t    = $c['Type'];
        $null = $c['Null'] === 'YES';
        $uniq = $c['Key'] === 'UNI';
        $attrs = [];
        if ($null) { $attrs[] = 'null=True'; $attrs[] = 'blank=True'; }
        if ($uniq) $attrs[] = 'unique=True';
        $a = $attrs ? ', ' . implode(', ', $attrs) : '';

        if (strtolower(trim($t)) === 'tinyint(1)') return "models.BooleanField($a)";

        $base = strtolower(preg_replace('/\s*\(.*\)/', '', $t));
        preg_match('/\((\d+)(?:,(\d+))?\)/', $t, $m);
        $len = $m[1] ?? null;
        $dec = $m[2] ?? null;

        $map = [
            'tinyint'   => "models.SmallIntegerField($a)",
            'smallint'  => "models.SmallIntegerField($a)",
            'mediumint' => "models.IntegerField($a)",
            'int'       => "models.IntegerField($a)",
            'integer'   => "models.IntegerField($a)",
            'bigint'    => "models.BigIntegerField($a)",
            'float'     => "models.FloatField($a)",
            'double'    => "models.FloatField($a)",
            'decimal'   => "models.DecimalField(max_digits=" . ($len ?? 10) . ", decimal_places=" . ($dec ?? 2) . "$a)",
            'numeric'   => "models.DecimalField(max_digits=" . ($len ?? 10) . ", decimal_places=" . ($dec ?? 2) . "$a)",
            'char'      => "models.CharField(max_length=" . ($len ?? 255) . "$a)",
            'varchar'   => "models.CharField(max_length=" . ($len ?? 255) . "$a)",
            'tinytext'  => "models.TextField($a)",
            'text'      => "models.TextField($a)",
            'mediumtext'=> "models.TextField($a)",
            'longtext'  => "models.TextField($a)",
            'date'      => "models.DateField($a)",
            'datetime'  => "models.DateTimeField($a)",
            'timestamp' => "models.DateTimeField(auto_now_add=True$a)",
            'time'      => "models.TimeField($a)",
            'year'      => "models.SmallIntegerField($a)",
            'binary'    => "models.BinaryField($a)",
            'varbinary' => "models.BinaryField($a)",
            'tinyblob'  => "models.BinaryField($a)",
            'blob'      => "models.BinaryField($a)",
            'mediumblob'=> "models.BinaryField($a)",
            'longblob'  => "models.BinaryField($a)",
            'json'      => "models.JSONField($a)",
        ];
        return $map[trim($base)] ?? "models.TextField($a)";
    }

    private function djangoOnDelete(string $rule): string {
        $map = [
            'CASCADE' => 'CASCADE', 'SET NULL' => 'SET_NULL',
            'NO ACTION' => 'DO_NOTHING', 'SET DEFAULT' => 'SET_DEFAULT',
        ];
        return $map[strtoupper(trim($rule))] ?? 'RESTRICT';
    }

    // ── Sequelize ─────────────────────────────────────────────────────────────

    private function sequelizeModel(string $database, string $table): string {
        $cols    = $this->cols($table);
        $fks     = $this->fks($database, $table);
        $model   = $this->pascal($table);
        $fields  = [];

        foreach ($cols as $c) {
            $f     = $c['Field'];
            $type  = $this->seqType($c['Type']);
            $props = ["type: DataTypes.$type"];
            if ($c['Key'] === 'PRI')                                    $props[] = "primaryKey: true";
            if (stripos($c['Extra'], 'auto_increment') !== false)       $props[] = "autoIncrement: true";
            $props[] = ($c['Null'] === 'YES') ? "allowNull: true" : "allowNull: false";
            if ($c['Key'] === 'UNI')                                    $props[] = "unique: true";
            if ($c['Default'] !== null) {
                $d = $c['Default'];
                if ($d === 'CURRENT_TIMESTAMP') $props[] = "defaultValue: DataTypes.NOW";
                elseif (is_numeric($d))         $props[] = "defaultValue: $d";
                else                            $props[] = "defaultValue: " . json_encode($d);
            }
            $fields[] = "    $f: {\n      " . implode(",\n      ", $props) . "\n    }";
        }

        $assoc = array_map(
            fn($fk) => "  // $model.belongsTo(" . $this->pascal($fk['REFERENCED_TABLE_NAME']) . ", { foreignKey: '{$fk['COLUMN_NAME']}' });",
            $fks
        );
        $assocBlock = $assoc ? "\n  // Associations:\n" . implode("\n", $assoc) . "\n" : '';

        return "module.exports = (sequelize) => {\n"
             . "  const $model = sequelize.define('$model', {\n"
             . implode(",\n", $fields) . "\n"
             . "  }, { tableName: '$table', timestamps: false });\n"
             . $assocBlock
             . "\n  return $model;\n};\n\n";
    }

    private function seqType(string $t): string {
        if (strtolower(trim($t)) === 'tinyint(1)') return 'BOOLEAN';
        $base = strtolower(preg_replace('/\s*\(.*\)/', '', $t));
        preg_match('/\((\d+)\)/', $t, $m);
        $len = $m[1] ?? null;
        $map = [
            'tinyint' => 'TINYINT', 'smallint' => 'SMALLINT', 'mediumint' => 'MEDIUMINT',
            'int' => 'INTEGER', 'integer' => 'INTEGER', 'bigint' => 'BIGINT',
            'float' => 'FLOAT', 'double' => 'DOUBLE',
            'decimal' => 'DECIMAL', 'numeric' => 'DECIMAL',
            'tinytext' => 'TEXT', 'text' => 'TEXT', 'mediumtext' => 'TEXT', 'longtext' => 'TEXT',
            'date' => 'DATEONLY', 'datetime' => 'DATE', 'timestamp' => 'DATE', 'time' => 'TIME',
            'year' => 'INTEGER', 'bit' => 'BOOLEAN',
            'binary' => 'BLOB', 'varbinary' => 'BLOB',
            'tinyblob' => 'BLOB', 'blob' => 'BLOB', 'mediumblob' => 'BLOB', 'longblob' => 'BLOB',
            'json' => 'JSON',
        ];
        if (in_array(trim($base), ['char', 'varchar'])) return $len ? "STRING($len)" : 'STRING';
        return $map[trim($base)] ?? 'STRING';
    }

    // ── Mongoose ──────────────────────────────────────────────────────────────

    private function mongooseSchema(string $table): string {
        $cols  = $this->cols($table);
        $name  = $this->pascal($table);
        $lines = [];

        foreach ($cols as $c) {
            $f = $c['Field'];
            if ($c['Key'] === 'PRI' && stripos($c['Extra'], 'auto_increment') !== false) continue;
            $mType    = $this->mongooseType($c['Type']);
            $required = $c['Null'] === 'NO' && $c['Default'] === null ? ', required: true' : '';
            $unique   = $c['Key'] === 'UNI' ? ', unique: true' : '';
            $lines[]  = "    $f: { type: $mType$required$unique }";
        }

        return "const {$name}Schema = new Schema({\n" . implode(",\n", $lines) . "\n}, { collection: '$table' });\n"
             . "module.exports = mongoose.model('$name', {$name}Schema);\n\n";
    }

    private function mongooseType(string $t): string {
        if (strtolower(trim($t)) === 'tinyint(1)') return 'Boolean';
        $base = strtolower(preg_replace('/\s*\(.*\)/', '', $t));
        $map  = [
            'tinyint' => 'Number', 'smallint' => 'Number', 'mediumint' => 'Number',
            'int' => 'Number', 'integer' => 'Number', 'bigint' => 'Number',
            'float' => 'Number', 'double' => 'Number', 'decimal' => 'Number', 'numeric' => 'Number',
            'year' => 'Number', 'bit' => 'Boolean',
            'date' => 'Date', 'datetime' => 'Date', 'timestamp' => 'Date',
            'json' => 'Schema.Types.Mixed',
        ];
        return $map[trim($base)] ?? 'String';
    }

    // ── Shared Helpers ────────────────────────────────────────────────────────

    private function use(string $db): void {
        $this->conn->exec("USE `" . preg_replace('/[^a-zA-Z0-9_]/', '', $db) . "`");
    }

    private function listTables(): array {
        return $this->conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    }

    private function cols(string $table): array {
        return $this->conn->query("DESCRIBE `" . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . "`")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fks(string $database, string $table): array {
        $stmt = $this->conn->prepare("
            SELECT kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME,
                   rc.DELETE_RULE, rc.UPDATE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_NAME  = kcu.CONSTRAINT_NAME
               AND rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
            WHERE kcu.TABLE_SCHEMA = :db AND kcu.TABLE_NAME = :tbl
              AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([':db' => $database, ':tbl' => $table]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function pascal(string $s): string {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $s)));
    }
}
