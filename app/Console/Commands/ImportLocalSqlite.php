<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ImportLocalSqlite extends Command
{
    protected $signature = 'database:import-local-sqlite {source? : Pad naar de bestaande SQLite-database} {--check : Alleen bron en lege MySQL-doeldatabase controleren}';
    protected $description = 'Kopieer bestaande gegevens naar een lege lokale MySQL-database; SQLite blijft intact';

    private const SKIP = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    private function quoteName(string $name): string
    {
        return '`'.str_replace('`', '``', $name).'`';
    }

    public function handle(): int
    {
        $target = DB::connection();
        if (!app()->environment('local') || $target->getDriverName() !== 'mysql'
            || !in_array($target->getConfig('host'), ['127.0.0.1', 'localhost', '::1'], true)
            || $target->getTablePrefix() !== '') {
            $this->error('Gebruik APP_ENV=local, een lokale MySQL-verbinding en geen tabelprefix.');
            return self::FAILURE;
        }
        $path = realpath($this->argument('source') ?: database_path('database.sqlite'));
        if (!$path || !is_file($path) || !is_readable($path)) {
            $this->error('SQLite-bestand ontbreekt of is niet leesbaar.');
            return self::FAILURE;
        }
        $source = null;
        $checksDisabled = false;
        try {
            $source = new \PDO('sqlite:'.$path, null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::SQLITE_ATTR_OPEN_FLAGS => \PDO::SQLITE_OPEN_READONLY,
            ]);
            // All reads use one consistent snapshot. Never write to the source.
            $source->beginTransaction();
            if ($source->query('PRAGMA quick_check')->fetchColumn() !== 'ok'
                || $source->query('PRAGMA foreign_key_check')->fetch() !== false) {
                throw new RuntimeException('SQLite-integriteitscontrole mislukt.');
            }
            $schema = $target->getSchemaBuilder();
            if (!$schema->hasTable('migrations')) {
                throw new RuntimeException('Voer eerst php artisan migrate uit op de nieuwe MySQL-database.');
            }
            $sourceMigrations = $source->query('SELECT migration FROM migrations ORDER BY migration')->fetchAll(\PDO::FETCH_COLUMN);
            $targetMigrations = $target->table('migrations')->orderBy('migration')->pluck('migration')->all();
            if ($sourceMigrations !== $targetMigrations) {
                throw new RuntimeException('De migratiegeschiedenis verschilt. Werk eerst je lokale SQLite-project bij met dezelfde code en migraties.');
            }
            $sourceTables = $source->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
            $tables = array_values(array_diff($sourceTables, self::SKIP));
            $mysqlTables = $schema->getTables($target->getDatabaseName());
            $targetTables = array_column($mysqlTables, 'name');
            sort($targetTables);
            if ($sourceTables !== $targetTables) {
                throw new RuntimeException('De tabellen verschillen tussen SQLite en MySQL. Er is niets gekopieerd.');
            }
            foreach ($mysqlTables as $table) {
                if (strcasecmp($table['engine'] ?? '', 'InnoDB') !== 0) {
                    throw new RuntimeException('Alle doeltabellen moeten InnoDB gebruiken.');
                }
            }
            $counts = [];
            foreach ($targetTables as $table) {
                if ($table === 'migrations') continue;
                $count = $target->table($table)->count();
                // This single row is inserted by the existing beta migration.
                $seed = $table === 'beta_rounds' && $count === 1
                    && $target->table($table)->where('id', 1)->where('name', 'Eerste proefronde')->exists();
                if ($count && !$seed) throw new RuntimeException('Doeltabel '.$table.' is al gevuld. Gebruik een nieuwe, lege database.');
            }
            foreach ($tables as $table) {
                $columns = array_column($source->query('PRAGMA table_info('.$this->quoteName($table).')')->fetchAll(\PDO::FETCH_ASSOC), 'name');
                $mysqlColumns = $schema->getColumnListing($table);
                sort($columns);
                sort($mysqlColumns);
                if ($columns !== $mysqlColumns) throw new RuntimeException('Kolommen verschillen in '.$table.'.');
                $counts[$table] = (int) $source->query('SELECT COUNT(*) FROM '.$this->quoteName($table))->fetchColumn();
            }
            $this->table(['Tabel', 'Te kopiëren rijen'], array_map(fn ($t, $n) => [$t, $n], array_keys($counts), $counts));
            if ($this->option('check')) {
                $this->info('Controle geslaagd. Er is niets gewijzigd.');
                return self::SUCCESS;
            }
            // Only DML inside the transaction: TRUNCATE/ALTER would implicitly commit.
            $target->statement('SET FOREIGN_KEY_CHECKS=0');
            $checksDisabled = true;
            $target->transaction(function () use ($target, $source, $tables, $counts) {
                $target->table('beta_rounds')->delete();
                foreach ($tables as $table) {
                    $cursor = $source->query('SELECT * FROM '.$this->quoteName($table));
                    $batch = [];
                    while ($row = $cursor->fetch(\PDO::FETCH_ASSOC)) {
                        $batch[] = $row;
                        if (count($batch) === 100) {
                            $target->table($table)->insert($batch);
                            $batch = [];
                        }
                    }
                    if ($batch) $target->table($table)->insert($batch);
                    if ($target->table($table)->count() !== $counts[$table]) {
                        throw new RuntimeException('Aantalcontrole mislukt voor '.$table.'.');
                    }
                }
                // MySQL does not revalidate existing rows when FK checks are enabled.
                foreach ($tables as $table) {
                    foreach ($target->getSchemaBuilder()->getForeignKeys($table) as $fk) {
                        $present = [];
                        $match = [];
                        foreach ($fk['columns'] as $index => $column) {
                            $present[] = 'c.'.$this->quoteName($column).' IS NOT NULL';
                            $match[] = 'p.'.$this->quoteName($fk['foreign_columns'][$index]).'=c.'.$this->quoteName($column);
                        }
                        $sql = 'SELECT 1 FROM '.$this->quoteName($table).' c WHERE '.implode(' AND ', $present)
                            .' AND NOT EXISTS (SELECT 1 FROM '.$this->quoteName($fk['foreign_table']).' p WHERE '.implode(' AND ', $match).') LIMIT 1';
                        if ($target->selectOne($sql)) throw new RuntimeException('Verbroken relatie in '.$table.'.');
                    }
                }
            });
            $this->info('Import voltooid. Inhoud, accounts, wachtwoordhashes en relaties zijn gekopieerd. SQLite is ongewijzigd.');
            $this->line('Sessies, caches en queuejobs zijn overgeslagen. Log opnieuw in en start eventuele workers opnieuw.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            // Do not print QueryException SQL/bindings: these can contain private data.
            $this->error($e instanceof RuntimeException && !$e instanceof \Illuminate\Database\QueryException
                ? $e->getMessage() : 'Import mislukt ('.$e::class.'). Controleer MySQL, PHP-extensies en schema; er is geen gedeeltelijke inhoudimport bewaard.');
            return self::FAILURE;
        } finally {
            if ($checksDisabled) $target->statement('SET FOREIGN_KEY_CHECKS=1');
            if ($source && $source->inTransaction()) $source->rollBack();
        }
    }
}
