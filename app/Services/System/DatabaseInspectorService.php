<?php

namespace App\Services\System;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DatabaseInspectorService
{
    /**
     * @return array<int, array{name: string, label: string, driver: string, is_default: bool}>
     */
    public function listConnections(): array
    {
        $all = array_keys(config('database.connections', []));
        $allowed = config('system_database.allowed_connections');
        $excluded = config('system_database.excluded_connections', []);

        $names = is_array($allowed) && $allowed !== []
            ? array_values(array_intersect($allowed, $all))
            : $all;

        $names = array_values(array_diff($names, $excluded));

        return array_map(function (string $name) {
            return [
                'name' => $name,
                'label' => $name,
                'driver' => (string) config("database.connections.{$name}.driver", ''),
                'is_default' => $name === config('database.default'),
            ];
        }, $names);
    }

    public function resolveConnection(?string $connection): string
    {
        $connection = $connection ?: (string) config('database.default');
        $allowed = array_column($this->listConnections(), 'name');

        if (! in_array($connection, $allowed, true)) {
            throw new \InvalidArgumentException('اتصال قاعدة البيانات غير مسموح.');
        }

        return $connection;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverview(string $connection, bool $refresh = false): array
    {
        if ($refresh) {
            $this->clearCache($connection);
        }

        $ttl = max(60, (int) config('system_database.cache_ttl_seconds', 300));

        return Cache::remember($this->cacheKey('overview', $connection), $ttl, function () use ($connection) {
            $tables = $this->fetchTables($connection);
            $totalData = array_sum(array_column($tables, 'data_bytes'));
            $totalIndex = array_sum(array_column($tables, 'index_bytes'));
            $totalRows = array_sum(array_column($tables, 'rows'));

            $meta = $this->connectionMeta($connection);

            return [
                'connection' => $connection,
                'driver' => $meta['driver'],
                'database' => $meta['database'],
                'host' => $meta['host'],
                'port' => $meta['port'],
                'charset' => $meta['charset'],
                'collation' => $meta['collation'],
                'table_count' => count($tables),
                'total_rows' => $totalRows,
                'rows_approximate' => $meta['rows_approximate'],
                'data_bytes' => $totalData,
                'index_bytes' => $totalIndex,
                'total_bytes' => $totalData + $totalIndex,
                'data_size' => $this->formatBytes($totalData),
                'index_size' => $this->formatBytes($totalIndex),
                'total_size' => $this->formatBytes($totalData + $totalIndex),
                'cached_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTables(string $connection, bool $refresh = false): array
    {
        if ($refresh) {
            $this->clearCache($connection);
        }

        $ttl = max(60, (int) config('system_database.cache_ttl_seconds', 300));

        $tables = Cache::remember($this->cacheKey('tables', $connection), $ttl, fn () => $this->fetchTables($connection));

        $totalBytes = max(1, array_sum(array_map(fn ($t) => $t['total_bytes'], $tables)));

        return array_map(function (array $table) use ($totalBytes) {
            $table['size_percent'] = round(($table['total_bytes'] / $totalBytes) * 100, 1);

            return $table;
        }, $tables);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTableDetail(string $connection, string $table): array
    {
        $table = $this->sanitizeTableName($table);
        $driver = $this->driver($connection);

        return match ($driver) {
            'mysql', 'mariadb' => $this->mysqlTableDetail($connection, $table),
            'sqlite' => $this->sqliteTableDetail($connection, $table),
            'pgsql' => $this->pgsqlTableDetail($connection, $table),
            default => throw new \RuntimeException("السائق «{$driver}» غير مدعوم في المستكشف."),
        };
    }

    public function clearCache(?string $connection = null): void
    {
        if ($connection !== null) {
            Cache::forget($this->cacheKey('overview', $connection));
            Cache::forget($this->cacheKey('tables', $connection));

            return;
        }

        foreach ($this->listConnections() as $conn) {
            $this->clearCache($conn['name']);
        }
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }

    protected function cacheKey(string $type, string $connection): string
    {
        return "system_db_{$type}_{$connection}";
    }

    protected function driver(string $connection): string
    {
        return (string) config("database.connections.{$connection}.driver", '');
    }

    /**
     * @return array{driver: string, database: string, host: ?string, port: ?string, charset: ?string, collation: ?string, rows_approximate: bool}
     */
    protected function connectionMeta(string $connection): array
    {
        $cfg = config("database.connections.{$connection}", []);
        $driver = (string) ($cfg['driver'] ?? '');
        $showHost = (bool) config('system_database.show_connection_host', true);

        $database = (string) ($cfg['database'] ?? '');
        if ($driver === 'sqlite' && $database !== '' && ! str_contains($database, ':')) {
            $database = basename($database);
        }

        return [
            'driver' => $driver,
            'database' => $database,
            'host' => $showHost ? ($cfg['host'] ?? null) : null,
            'port' => $showHost ? ($cfg['port'] ?? null) : null,
            'charset' => $cfg['charset'] ?? null,
            'collation' => $cfg['collation'] ?? null,
            'rows_approximate' => in_array($driver, ['mysql', 'mariadb'], true),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchTables(string $connection): array
    {
        $driver = $this->driver($connection);

        $tables = match ($driver) {
            'mysql', 'mariadb' => $this->mysqlTables($connection),
            'sqlite' => $this->sqliteTables($connection),
            'pgsql' => $this->pgsqlTables($connection),
            default => throw new \RuntimeException("السائق «{$driver}» غير مدعوم."),
        };

        usort($tables, fn ($a, $b) => $b['total_bytes'] <=> $a['total_bytes']);

        return $tables;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mysqlTables(string $connection): array
    {
        $schema = (string) config("database.connections.{$connection}.database");
        $rows = DB::connection($connection)->select(
            'SELECT TABLE_NAME, ENGINE, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH,
                    TABLE_COLLATION, CREATE_TIME, UPDATE_TIME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME',
            [$schema]
        );

        $tables = [];
        foreach ($rows as $row) {
            $name = (string) $row->TABLE_NAME;
            if ($this->isExcludedTable($name)) {
                continue;
            }

            $data = (int) ($row->DATA_LENGTH ?? 0);
            $index = (int) ($row->INDEX_LENGTH ?? 0);

            $tables[] = $this->tableRow(
                name: $name,
                rows: (int) ($row->TABLE_ROWS ?? 0),
                dataBytes: $data,
                indexBytes: $index,
                engine: (string) ($row->ENGINE ?? ''),
                collation: (string) ($row->TABLE_COLLATION ?? ''),
                createdAt: $row->CREATE_TIME ?? null,
                updatedAt: $row->UPDATE_TIME ?? null,
                rowsApproximate: true
            );
        }

        return $tables;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mysqlTableDetail(string $connection, string $table): array
    {
        $schema = (string) config("database.connections.{$connection}.database");

        $columns = DB::connection($connection)->select(
            'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY,
                    COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT, ORDINAL_POSITION
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION',
            [$schema, $table]
        );

        $indexes = DB::connection($connection)->select(
            'SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, INDEX_TYPE, COLLATION
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [$schema, $table]
        );

        $foreignKeys = DB::connection($connection)->select(
            'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION',
            [$schema, $table]
        );

        return [
            'table' => $table,
            'columns' => array_map(fn ($c) => [
                'name' => $c->COLUMN_NAME,
                'type' => $c->COLUMN_TYPE,
                'nullable' => $c->IS_NULLABLE === 'YES',
                'key' => $c->COLUMN_KEY,
                'default' => $c->COLUMN_DEFAULT,
                'extra' => $c->EXTRA,
                'comment' => $c->COLUMN_COMMENT,
            ], $columns),
            'indexes' => array_map(fn ($i) => [
                'name' => $i->INDEX_NAME,
                'unique' => (int) $i->NON_UNIQUE === 0,
                'column' => $i->COLUMN_NAME,
                'type' => $i->INDEX_TYPE,
            ], $indexes),
            'foreign_keys' => array_map(fn ($f) => [
                'name' => $f->CONSTRAINT_NAME,
                'column' => $f->COLUMN_NAME,
                'references_table' => $f->REFERENCED_TABLE_NAME,
                'references_column' => $f->REFERENCED_COLUMN_NAME,
            ], $foreignKeys),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function sqliteTables(string $connection): array
    {
        $master = DB::connection($connection)->select(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );

        $pageSize = (int) (DB::connection($connection)->selectOne('PRAGMA page_size')->page_size ?? 4096);
        $tables = [];

        foreach ($master as $row) {
            $name = (string) $row->name;
            if ($this->isExcludedTable($name)) {
                continue;
            }

            $countRow = DB::connection($connection)->selectOne("SELECT COUNT(*) AS c FROM \"{$name}\"");
            $rows = (int) ($countRow->c ?? 0);

            $dbstat = DB::connection($connection)->selectOne('SELECT SUM(pgsize) AS s FROM dbstat WHERE name = ?', [$name]);
            $totalBytes = (int) ($dbstat->s ?? 0);
            if ($totalBytes < 1) {
                $totalBytes = max(1, $rows * 100);
            }

            $tables[] = $this->tableRow(
                name: $name,
                rows: $rows,
                dataBytes: $totalBytes,
                indexBytes: 0,
                engine: 'SQLite',
                collation: null,
                createdAt: null,
                updatedAt: null,
                rowsApproximate: false
            );
        }

        return $tables;
    }

    /**
     * @return array<string, mixed>
     */
    protected function sqliteTableDetail(string $connection, string $table): array
    {
        $columns = DB::connection($connection)->select("PRAGMA table_info(\"{$table}\")");
        $indexes = DB::connection($connection)->select("PRAGMA index_list(\"{$table}\")");
        $foreignKeys = DB::connection($connection)->select("PRAGMA foreign_key_list(\"{$table}\")");

        return [
            'table' => $table,
            'columns' => array_map(fn ($c) => [
                'name' => $c->name,
                'type' => $c->type,
                'nullable' => (int) $c->notnull === 0,
                'key' => (int) $c->pk > 0 ? 'PRI' : '',
                'default' => $c->dflt_value,
                'extra' => '',
                'comment' => '',
            ], $columns),
            'indexes' => array_map(fn ($i) => [
                'name' => $i->name,
                'unique' => (int) $i->unique === 1,
                'column' => '',
                'type' => '',
            ], $indexes),
            'foreign_keys' => array_map(fn ($f) => [
                'name' => 'fk_'.$f->id,
                'column' => $f->from,
                'references_table' => $f->table,
                'references_column' => $f->to,
            ], $foreignKeys),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function pgsqlTables(string $connection): array
    {
        $schema = (string) (config("database.connections.{$connection}.search_path") ?? 'public');
        $schema = explode(',', $schema)[0];

        $rows = DB::connection($connection)->select(
            'SELECT c.relname AS table_name,
                    pg_total_relation_size(c.oid) AS total_bytes,
                    pg_relation_size(c.oid) AS data_bytes,
                    pg_indexes_size(c.oid) AS index_bytes,
                    c.reltuples::bigint AS row_estimate
             FROM pg_class c
             JOIN pg_namespace n ON n.oid = c.relnamespace
             WHERE n.nspname = ? AND c.relkind = \'r\'
             ORDER BY c.relname',
            [$schema]
        );

        $tables = [];
        foreach ($rows as $row) {
            $name = (string) $row->table_name;
            if ($this->isExcludedTable($name)) {
                continue;
            }

            $data = (int) ($row->data_bytes ?? 0);
            $index = (int) ($row->index_bytes ?? 0);
            $total = (int) ($row->total_bytes ?? ($data + $index));

            $tables[] = $this->tableRow(
                name: $name,
                rows: (int) ($row->row_estimate ?? 0),
                dataBytes: $data,
                indexBytes: $index,
                engine: 'PostgreSQL',
                collation: null,
                createdAt: null,
                updatedAt: null,
                rowsApproximate: true,
                totalBytesOverride: $total
            );
        }

        return $tables;
    }

    /**
     * @return array<string, mixed>
     */
    protected function pgsqlTableDetail(string $connection, string $table): array
    {
        $schema = explode(',', (string) config("database.connections.{$connection}.search_path", 'public'))[0];

        $columns = DB::connection($connection)->select(
            'SELECT column_name, data_type, is_nullable, column_default,
                    character_maximum_length, numeric_precision, ordinal_position
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ?
             ORDER BY ordinal_position',
            [$schema, $table]
        );

        $indexes = DB::connection($connection)->select(
            'SELECT indexname, indexdef FROM pg_indexes WHERE schemaname = ? AND tablename = ?',
            [$schema, $table]
        );

        $foreignKeys = DB::connection($connection)->select(
            'SELECT tc.constraint_name, kcu.column_name,
                    ccu.table_name AS foreign_table_name,
                    ccu.column_name AS foreign_column_name
             FROM information_schema.table_constraints AS tc
             JOIN information_schema.key_column_usage AS kcu
               ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
             JOIN information_schema.constraint_column_usage AS ccu
               ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
             WHERE tc.constraint_type = \'FOREIGN KEY\'
               AND tc.table_schema = ? AND tc.table_name = ?',
            [$schema, $table]
        );

        return [
            'table' => $table,
            'columns' => array_map(function ($c) {
                $type = $c->data_type;
                if ($c->character_maximum_length) {
                    $type .= '('.$c->character_maximum_length.')';
                }

                return [
                    'name' => $c->column_name,
                    'type' => $type,
                    'nullable' => $c->is_nullable === 'YES',
                    'key' => '',
                    'default' => $c->column_default,
                    'extra' => '',
                    'comment' => '',
                ];
            }, $columns),
            'indexes' => array_map(fn ($i) => [
                'name' => $i->indexname,
                'unique' => str_contains(strtolower($i->indexdef ?? ''), 'unique'),
                'column' => '',
                'type' => '',
            ], $indexes),
            'foreign_keys' => array_map(fn ($f) => [
                'name' => $f->constraint_name,
                'column' => $f->column_name,
                'references_table' => $f->foreign_table_name,
                'references_column' => $f->foreign_column_name,
            ], $foreignKeys),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function tableRow(
        string $name,
        int $rows,
        int $dataBytes,
        int $indexBytes,
        string $engine,
        ?string $collation,
        mixed $createdAt,
        mixed $updatedAt,
        bool $rowsApproximate,
        ?int $totalBytesOverride = null
    ): array {
        $total = $totalBytesOverride ?? ($dataBytes + $indexBytes);

        return [
            'name' => $name,
            'rows' => $rows,
            'rows_label' => number_format($rows),
            'rows_approximate' => $rowsApproximate,
            'data_bytes' => $dataBytes,
            'index_bytes' => $indexBytes,
            'total_bytes' => $total,
            'data_size' => $this->formatBytes($dataBytes),
            'index_size' => $this->formatBytes($indexBytes),
            'total_size' => $this->formatBytes($total),
            'engine' => $engine,
            'collation' => $collation,
            'created_at' => $createdAt ? (string) $createdAt : null,
            'updated_at' => $updatedAt ? (string) $updatedAt : null,
        ];
    }

    protected function isExcludedTable(string $name): bool
    {
        $patterns = config('system_database.excluded_tables', []);

        foreach ($patterns as $pattern) {
            if ($pattern === $name) {
                return true;
            }
            if (str_contains($pattern, '*') && fnmatch($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    protected function sanitizeTableName(string $table): string
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException('اسم الجدول غير صالح.');
        }

        return $table;
    }
}
