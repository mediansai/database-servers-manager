<?php
/**
 * Query Profiler
 * Profiles SELECT queries: execution time, rows scanned, indexes used,
 * EXPLAIN (classic + JSON tree), and surfaces slow queries via
 * performance_schema.events_statements_summary_by_digest.
 */
class QueryProfiler {
    private PDO $conn;
    private string $historyFile;
    private int $maxHistory = 100;

    public function __construct(PDO $conn) {
        $this->conn = $conn;

        $dir = __DIR__ . '/../storage/profiler';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->historyFile = $dir . '/history.json';
    }

    /**
     * Profile a single read-only query against a database.
     * Only SELECT / WITH statements are allowed — the profiler is a
     * read-only diagnostic tool, not a general query executor.
     */
    public function profile(string $database, string $sql): array {
        $sql = trim($sql);
        $sql = rtrim($sql, "; \t\n\r\0\x0B");

        if ($sql === '') {
            throw new Exception('Query is required');
        }
        if (!preg_match('/^\s*(SELECT|WITH)\b/i', $sql)) {
            throw new Exception('Only SELECT queries can be profiled');
        }
        if (!$database) {
            throw new Exception('Database is required');
        }

        $this->conn->exec('USE `' . str_replace('`', '', $database) . '`');

        $explainJson    = $this->explainJson($sql);
        $explainClassic = $this->explainClassic($sql);
        $summary        = $this->summarizeExplain($explainJson, $explainClassic);

        $error   = null;
        $rows    = [];
        $rowCount = 0;

        $start = microtime(true);
        try {
            $stmt = $this->conn->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rowCount = count($rows);
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
        $elapsedMs = round((microtime(true) - $start) * 1000, 3);

        $result = [
            'query'              => $sql,
            'database'           => $database,
            'execution_time_ms'  => $elapsedMs,
            'rows_returned'      => $rowCount,
            'rows_scanned'       => $summary['rows_scanned'],
            'indexes_used'       => $summary['indexes_used'],
            'full_table_scans'   => $summary['full_table_scans'],
            'is_slow'            => $elapsedMs > 1000 || $summary['full_table_scans'] > 0,
            'explain_json'       => $explainJson,
            'explain_classic'    => $explainClassic,
            'preview_rows'       => array_slice($rows, 0, 25),
            'error'              => $error,
            'timestamp'          => date('Y-m-d H:i:s'),
        ];

        if (!$error) {
            $this->appendHistory($result);
        }

        return $result;
    }

    /** EXPLAIN FORMAT=JSON — used to build the visual tree and compute stats */
    private function explainJson(string $sql): ?array {
        try {
            $stmt = $this->conn->query('EXPLAIN FORMAT=JSON ' . $sql);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);
            $raw  = $row['EXPLAIN'] ?? null;
            return $raw ? json_decode($raw, true) : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /** Classic tabular EXPLAIN — shown alongside the tree view */
    private function explainClassic(string $sql): array {
        try {
            $stmt = $this->conn->query('EXPLAIN ' . $sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Walk the EXPLAIN JSON tree to total rows examined and collect index usage */
    private function summarizeExplain(?array $explainJson, array $explainClassic): array {
        $rowsScanned = 0;
        $indexes     = [];
        $fullScans   = 0;
        $found       = false;

        if ($explainJson) {
            $walk = function ($node) use (&$walk, &$rowsScanned, &$indexes, &$fullScans, &$found) {
                if (!is_array($node)) {
                    return;
                }
                if (isset($node['table']) && is_array($node['table'])) {
                    $t = $node['table'];
                    $found = true;
                    if (isset($t['rows_examined_per_scan'])) {
                        $rowsScanned += (int)$t['rows_examined_per_scan'];
                    }
                    if (!empty($t['key'])) {
                        $indexes[] = $t['key'];
                    } elseif (($t['access_type'] ?? '') === 'ALL') {
                        $fullScans++;
                    }
                }
                foreach ($node as $value) {
                    if (is_array($value)) {
                        $walk($value);
                    }
                }
            };
            $walk($explainJson);
        }

        // Fallback to the classic EXPLAIN rows if JSON parsing found nothing
        if (!$found && $explainClassic) {
            foreach ($explainClassic as $row) {
                $rowsScanned += (int)($row['rows'] ?? 0);
                if (!empty($row['key'])) {
                    $indexes[] = $row['key'];
                } elseif (($row['type'] ?? '') === 'ALL') {
                    $fullScans++;
                }
            }
        }

        return [
            'rows_scanned'     => $rowsScanned,
            'indexes_used'     => array_values(array_unique($indexes)),
            'full_table_scans' => $fullScans,
        ];
    }

    /**
     * Server-wide slow query stats from performance_schema.
     * Falls back gracefully with a status flag if performance_schema is off.
     */
    public function getSlowQueries(int $limit = 25): array {
        $out = ['available' => false, 'rows' => [], 'settings' => [], 'error' => null];

        try {
            $peVal  = $this->fetchVariable('performance_schema');
            $lqtVal = $this->fetchVariable('long_query_time');
            $sqlVal = $this->fetchVariable('slow_query_log');

            $out['settings'] = [
                'performance_schema' => $peVal ?? 'unknown',
                'long_query_time'    => $lqtVal,
                'slow_query_log'     => $sqlVal,
            ];

            if (strtoupper((string)$peVal) !== 'ON') {
                return $out;
            }

            $limit = max(1, min(200, $limit));
            $stmt = $this->conn->prepare("
                SELECT
                    DIGEST_TEXT             AS query_pattern,
                    SCHEMA_NAME              AS db_name,
                    COUNT_STAR               AS exec_count,
                    ROUND(AVG_TIMER_WAIT  / 1000000000, 3) AS avg_time_ms,
                    ROUND(MAX_TIMER_WAIT  / 1000000000, 3) AS max_time_ms,
                    ROUND(SUM_TIMER_WAIT  / 1000000000, 3) AS total_time_ms,
                    SUM_ROWS_EXAMINED       AS rows_examined,
                    SUM_ROWS_SENT           AS rows_sent,
                    SUM_NO_INDEX_USED       AS no_index_used_count,
                    LAST_SEEN               AS last_seen
                FROM performance_schema.events_statements_summary_by_digest
                WHERE DIGEST_TEXT IS NOT NULL
                ORDER BY AVG_TIMER_WAIT DESC
                LIMIT $limit
            ");
            $stmt->execute();
            $out['rows']      = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out['available'] = true;
        } catch (PDOException $e) {
            $out['error'] = $e->getMessage();
        }

        return $out;
    }

    private function fetchVariable(string $name): ?string {
        $stmt = $this->conn->query("SHOW VARIABLES LIKE '$name'");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['Value'] ?? null;
    }

    /** Most recent profiled queries (newest first) */
    public function getHistory(int $limit = 20): array {
        $data = $this->readHistory();
        return array_slice(array_reverse($data), 0, $limit);
    }

    public function clearHistory(): void {
        if (file_exists($this->historyFile)) {
            unlink($this->historyFile);
        }
    }

    private function readHistory(): array {
        if (!file_exists($this->historyFile)) {
            return [];
        }
        $decoded = json_decode((string)file_get_contents($this->historyFile), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function appendHistory(array $entry): void {
        $data = $this->readHistory();

        $data[] = [
            'query'             => mb_substr($entry['query'], 0, 1000),
            'database'          => $entry['database'],
            'execution_time_ms' => $entry['execution_time_ms'],
            'rows_returned'     => $entry['rows_returned'],
            'rows_scanned'      => $entry['rows_scanned'],
            'indexes_used'      => $entry['indexes_used'],
            'full_table_scans'  => $entry['full_table_scans'],
            'timestamp'         => $entry['timestamp'],
        ];

        if (count($data) > $this->maxHistory) {
            $data = array_slice($data, -$this->maxHistory);
        }

        file_put_contents($this->historyFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}
