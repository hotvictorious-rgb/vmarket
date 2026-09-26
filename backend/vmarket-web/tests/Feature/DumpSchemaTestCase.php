<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Artisan;
use PDO;
use RuntimeException;
use Tests\TestCase;

/**
 * [AI][VM-TEST-001] Test-harness-only SQLite schema support.
 *
 * NOTE ON LOADING: in this workspace `backend/vmarket-web/vendor` is a
 * junction to a shared checkout, so composer's `Tests\` PSR-4 mapping
 * resolves to the shared checkout and NEW test-support classes do NOT
 * autoload. The two feature test files therefore pull this file in with an
 * explicit `require_once __DIR__ . '/DumpSchemaTestCase.php'`. Keep both
 * classes in this single file so one require is enough.
 */

/**
 * Loads the repository's canonical install snapshot
 * (`installation/backup/database.sql`, the same dump the installer imports
 * via `InstallController@importSQL`) into the sqlite `:memory:` test
 * database, converting MySQL DDL to SQLite-compatible DDL.
 *
 * Why not `artisan migrate`: the migration chain in `database/migrations`
 * cannot build a fresh database — the original CREATE TABLE migrations were
 * never committed (e.g. no create migration exists for `users`, `products`,
 * `orders`, `flash_deals`, ...), so `migrate` on an empty database dies at
 * the first ALTER (`SQLSTATE[HY000]: no such table: flash_deals`). Old
 * migrations must never be edited, so the test harness does not run the
 * migrator at all.
 *
 * MySQL -> SQLite conversion rules:
 *   - strips `CHARACTER SET` / `COLLATE` / column `COMMENT` / `ON UPDATE`
 *     clauses and `ENGINE=...` table suffixes (unsupported by SQLite);
 *   - drops inline index/key constraint lines (separate
 *     `ALTER TABLE ... ADD KEY` statements are skipped); uniqueness/indexes
 *     are not required for the behavioural assertions under test;
 *   - injects an `INTEGER PRIMARY KEY AUTOINCREMENT` (or plain `PRIMARY KEY`
 *     for non-integer ids) on the `id` column, because the dump declares
 *     primary keys via separate MySQL-only `ALTER TABLE ... ADD PRIMARY KEY`
 *     statements which SQLite cannot execute;
 *   - unescapes MySQL string-literal escapes (`\"`, `\\`, `\n`, `\r`, ...)
 *     inside INSERT data, which SQLite would otherwise store literally.
 *
 * Idempotent (`DROP TABLE IF EXISTS` before every CREATE), so it is safe to
 * run in `setUp()` regardless of whether Laravel rebuilds the application
 * (and its in-memory connection) between tests.
 *
 * TEST-ONLY. Reads (never writes) product-shipped files. No product code is
 * touched and no assertion is weakened by this loader.
 */
class SqliteDumpLoader
{
    /**
     * Load the canonical install-snapshot schema (+ seed rows) into $pdo.
     */
    public static function load(PDO $pdo, string $dumpPath): void
    {
        $sql = @file_get_contents($dumpPath);
        if ($sql === false) {
            throw new RuntimeException('Test schema dump not found: ' . $dumpPath);
        }

        $statements = self::splitStatements($sql);

        $tables = [];
        foreach ($statements as $stmt) {
            if (preg_match('/^\s*CREATE\s+TABLE\s+`?([A-Za-z0-9_]+)`?/i', $stmt, $m)) {
                $tables[] = $m[1];
            }
        }

        $pdo->exec('PRAGMA foreign_keys = OFF');

        foreach (array_unique($tables) as $table) {
            $pdo->exec('DROP TABLE IF EXISTS "' . str_replace('"', '""', $table) . '"');
        }

        foreach ($statements as $stmt) {
            $converted = self::convert($stmt);
            if ($converted === null) {
                continue;
            }
            try {
                $pdo->exec($converted);
            } catch (\Throwable $e) {
                throw new RuntimeException(
                    'Test schema load failed: ' . $e->getMessage()
                        . "\nStatement head: " . substr($converted, 0, 400),
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Split raw SQL into statements, honouring quotes and comments so that
     * semicolons inside string literals do not split.
     *
     * @return string[]
     */
    private static function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $len = strlen($sql);
        $i = 0;
        $state = 'normal';

        while ($i < $len) {
            $c = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($state === 'normal') {
                $afterNext = $i + 2 < $len ? $sql[$i + 2] : '';
                if ($c === '-' && $next === '-' && ($afterNext === '' || $afterNext === ' ' || $afterNext === "\t" || $afterNext === "\n" || $afterNext === "\r")) {
                    $state = 'linecomment';
                    $i += 2;
                    continue;
                }
                if ($c === '#') {
                    $state = 'linecomment';
                    $i++;
                    continue;
                }
                if ($c === '/' && $next === '*') {
                    $state = 'blockcomment';
                    $i += 2;
                    continue;
                }
                if ($c === "'") {
                    $state = 'squote';
                    $current .= $c;
                    $i++;
                    continue;
                }
                if ($c === '"') {
                    $state = 'dquote';
                    $current .= $c;
                    $i++;
                    continue;
                }
                if ($c === '`') {
                    $state = 'backtick';
                    $current .= $c;
                    $i++;
                    continue;
                }
                if ($c === ';') {
                    $statements[] = $current;
                    $current = '';
                    $i++;
                    continue;
                }
                $current .= $c;
                $i++;
                continue;
            }

            if ($state === 'linecomment') {
                if ($c === "\n") {
                    $state = 'normal';
                }
                $i++;
                continue;
            }

            if ($state === 'blockcomment') {
                if ($c === '*' && $next === '/') {
                    $state = 'normal';
                    $i += 2;
                    continue;
                }
                $i++;
                continue;
            }

            if ($state === 'backtick') {
                $current .= $c;
                $i++;
                if ($c === '`') {
                    $state = 'normal';
                }
                continue;
            }

            if ($state === 'dquote') {
                if ($c === '\\' && $i + 1 < $len) {
                    $current .= $c . $sql[$i + 1];
                    $i += 2;
                    continue;
                }
                $current .= $c;
                $i++;
                if ($c === '"') {
                    $state = 'normal';
                }
                continue;
            }

            // squote
            if ($c === '\\' && $i + 1 < $len) {
                $current .= $c . $sql[$i + 1];
                $i += 2;
                continue;
            }
            if ($c === "'" && $next === "'") {
                $current .= "''";
                $i += 2;
                continue;
            }
            $current .= $c;
            $i++;
            if ($c === "'") {
                $state = 'normal';
            }
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }

    /**
     * Convert one MySQL statement to SQLite. Returns null for statements that
     * must be skipped in the test schema.
     */
    private static function convert(string $stmt): ?string
    {
        $trimmed = trim($stmt);
        if ($trimmed === '') {
            return null;
        }
        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
            return null;
        }

        $upper = strtoupper(substr($trimmed, 0, 18));
        foreach (['SET ', 'SET;', 'START TRANSACTION', 'START ', 'COMMIT', 'ROLLBACK', 'USE ', 'CREATE DATABASE', 'LOCK ', 'UNLOCK ', 'ALTER TABLE', 'DROP '] as $prefix) {
            if (str_starts_with($upper, $prefix) || $upper === rtrim($prefix)) {
                return null;
            }
        }

        if (preg_match('/^\s*CREATE\s+TABLE\b/i', $trimmed)) {
            return self::convertCreateTable($trimmed);
        }

        if (preg_match('/^\s*INSERT\s+(INTO\s+)?/i', $trimmed)) {
            return self::unescapeStringLiterals($trimmed);
        }

        return null;
    }

    /**
     * Convert a MySQL CREATE TABLE to SQLite-compatible DDL.
     */
    private static function convertCreateTable(string $stmt): string
    {
        preg_match('/^\s*CREATE\s+TABLE\s+`?([A-Za-z0-9_]+)`?\s*\(/i', $stmt, $m);
        $table = $m[1];

        $openPos = strpos($stmt, '(');
        $closePos = strrpos($stmt, ')');
        $body = substr($stmt, $openPos + 1, $closePos - $openPos - 1);

        $out = [];
        foreach (explode("\n", $body) as $line) {
            $t = trim($line);
            if ($t === '') {
                continue;
            }
            if (preg_match('/^(PRIMARY\s+KEY|UNIQUE(\s+KEY|\s+INDEX)?|KEY|INDEX|FULLTEXT|SPATIAL|CONSTRAINT|FOREIGN\s+KEY|CHECK)\b/i', $t)) {
                continue;
            }

            $line = preg_replace('/\s+CHARACTER\s+SET\s+[A-Za-z0-9_]+/i', '', $line);
            $line = preg_replace('/\s+COLLATE\s+[A-Za-z0-9_]+/i', '', $line);
            $line = preg_replace("/\s+COMMENT\s+'[^']*'/", '', $line);
            $line = preg_replace('/\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $line);

            // Strip MySQL-only numeric type attributes SQLite does not accept.
            $line = preg_replace('/\b(bigint|smallint|mediumint|tinyint|int|integer|decimal|numeric|float|double|real|bit)\s*\(\s*\d+\s*(,\s*\d+\s*)?\)/i', '$1', $line);
            $line = preg_replace('/\s+(UNSIGNED|ZEROFILL|SIGNED)\b/i', '', $line);

            if (preg_match('/^\s*`id`\s+/i', $line)) {
                $comma = str_ends_with(trim($line), ',') ? ',' : '';
                if (preg_match('/^\s*`id`\s+(tinyint|smallint|mediumint|integer|int|bigint)\b/i', $line)) {
                    $line = '  `id` INTEGER PRIMARY KEY AUTOINCREMENT' . $comma;
                } else {
                    $line = rtrim(rtrim($line), ',') . ' PRIMARY KEY' . $comma;
                }
            }

            $out[] = $line;
        }

        $last = count($out) - 1;
        $out[$last] = rtrim(rtrim($out[$last]), ',');

        return 'CREATE TABLE IF NOT EXISTS `' . $table . "` (\n" . implode("\n", $out) . "\n)";
    }

    /**
     * Tables that `Schema::create(...)` inside `database/migrations/**` but
     * that are absent from the install snapshot. The snapshot predates the
     * 2026 architecture work, so these 16 tables (canonical geography,
     * delivery lanes, checkout intents, cashback, POS/omnichannel, audit
     * logs) exist only in migrations. The schema is otherwise produced by the
     * dump alone, so product code that queries them — e.g.
     * `HomeController::theme_vmarket()` reading `lgas` — would hit
     * "no such table" during a feature test.
     *
     * @return array<string, string> table name => absolute migration path
     */
    public static function migrationsCreatingMissingTables(string $dumpPath): array
    {
        $sql = @file_get_contents($dumpPath);
        if ($sql === false) {
            throw new RuntimeException('Test schema dump not found: ' . $dumpPath);
        }

        preg_match_all('/^\s*CREATE\s+TABLE\s+`?([A-Za-z0-9_]+)`?/im', $sql, $m);
        $dumpTables = array_map('strtolower', $m[1]);

        $found = [];
        foreach ((array) glob(base_path('database/migrations/*.php')) as $file) {
            $source = @file_get_contents($file);
            if ($source === false) {
                continue;
            }
            if (! preg_match('/Schema::create\(\s*[\'"]([A-Za-z0-9_]+)[\'"]/', $source, $mm)) {
                continue;
            }
            $table = strtolower($mm[1]);
            if (in_array($table, $dumpTables, true)) {
                continue;
            }
            // `migrate --path` is resolved against the app base path, so hand
            // back a base-relative path rather than the absolute glob result.
            $found[] = [
                'table' => $table,
                'file' => $file,
                'relative' => 'database/migrations/' . basename($file),
            ];
        }

        // Apply in migration order: several of these declare
        // `foreignId(...)->constrained('states')` etc., so their parents must
        // exist first. The filename timestamp prefix already encodes that.
        usort($found, fn ($a, $b) => strcmp(basename($a['file']), basename($b['file'])));

        $missing = [];
        foreach ($found as $entry) {
            $missing[$entry['table']] = $entry['relative'];
        }

        return $missing;
    }

    /**
     * Unescape MySQL string-literal escapes inside single-quoted literals so
     * INSERT data stores the same values MySQL would store.
     */
    private static function unescapeStringLiterals(string $stmt): string
    {
        // `'\'` maps to a DOUBLED quote (''), not a bare one: the escape only
        // carries meaning inside a MySQL single-quoted literal, and a bare `'
        // would terminate the SQLite literal early and break the statement.
        $map = [
            'n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08",
            '0' => "\0", 'Z' => "\x1a", "'" => "''", '"' => '"', '\\' => '\\',
        ];

        $out = '';
        $len = strlen($stmt);
        $i = 0;
        $inString = false;

        while ($i < $len) {
            $c = $stmt[$i];

            if (! $inString) {
                $out .= $c;
                $i++;
                if ($c === "'") {
                    $inString = true;
                }
                continue;
            }

            if ($c === '\\' && $i + 1 < $len) {
                $n = $stmt[$i + 1];
                $out .= $map[$n] ?? $n;
                $i += 2;
                continue;
            }
            if ($c === "'" && $i + 1 < $len && $stmt[$i + 1] === "'") {
                $out .= "''";
                $i += 2;
                continue;
            }
            $out .= $c;
            $i++;
            if ($c === "'") {
                $inString = false;
            }
        }

        return $out;
    }
}

/**
 * [AI][VM-TEST-001] Base class for feature tests that need a real schema.
 *
 * Loads the repository's canonical install snapshot
 * (`installation/backup/database.sql`) into the sqlite `:memory:` test
 * database before each test (see `SqliteDumpLoader` above for why the
 * migration chain cannot be used and for the MySQL -> SQLite rules).
 *
 * TEST-ONLY scaffolding. No product code, no assertions affected.
 */
abstract class DumpSchemaTestCase extends TestCase
{
    /**
     * Load the test schema BEFORE the framework boots.
     *
     * `CreatesApplication::createApplication()` runs `$kernel->bootstrap()`,
     * which boots every service provider — and `AppServiceProvider::boot()`
     * queries the database to build the `$web_config` array that every Blade
     * view reads. If the schema is loaded afterwards (in `setUp()`), that
     * provider sees an empty database, skips its `Schema::hasTable(...)`
     * branch, and never calls `View::share(['web_config' => ...])` — leaving
     * the storefront templates to die on "Undefined variable $web_config".
     *
     * So the connection is opened and populated here, between building the
     * application container and bootstrapping it.
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';

        // Bootstrapping the Kernel boots every service provider — including
        // AppServiceProvider, which runs queries to build `$web_config` for
        // the Blade views. If it boots against an empty database, it silently
        // skips those queries, and rendering fails later with "Undefined variable
        // $web_config".
        //
        // By hooking `beforeBootstrapping(BootProviders::class)`, we intercept
        // the frame right after the DatabaseServiceProvider is registered but
        // right before ANY provider is booted, letting us populate the
        // SQLite test schema exactly when the framework needs it.
        $app->beforeBootstrapping(
            \Illuminate\Foundation\Bootstrap\BootProviders::class,
            function ($app) {
                $this->loadTestSchema($app);
            }
        );

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Populate the sqlite `:memory:` test database with the install snapshot
     * plus the 2026 architecture tables it predates.
     */
    private function loadTestSchema($app): void
    {
        $config = $app->make('config');
        if (($config->get('database.default')) !== 'sqlite') {
            return;
        }

        $dumpPath = $app->basePath('installation/backup/database.sql');
        $pdo = $app->make('db')->connection()->getPdo();
        SqliteDumpLoader::load($pdo, $dumpPath);

        // The dump is a point-in-time snapshot: it predates the 2026
        // architecture migrations, so the tables those create (`countries`,
        // `states`, `lgas`, `delivery_lanes`, `checkout_intents`, ...) are
        // absent. Product code reads them during ordinary web requests
        // (`HomeController::theme_vmarket()` reads `lgas`), so a feature test
        // that boots the app would 500 on "no such table".
        //
        // Only the table-creating migrations are applied, via `migrate --path`
        // so Laravel's own migrator and schema builder run the canonical
        // `up()`. The full chain is deliberately not run: it contains ALTERs
        // and raw statements written against MySQL (e.g.
        // `DATE_ADD(..., INTERVAL 7 DAY)` in
        // 2026_09_14_000001_add_availability_lifecycle_columns_to_products_table)
        // that SQLite rejects, and migrations must never be edited to suit a
        // test harness.
        foreach (SqliteDumpLoader::migrationsCreatingMissingTables($dumpPath) as $path) {
            Artisan::call('migrate', [
                '--force' => true,
                '--path' => $path,
            ]);
        }
    }
}
