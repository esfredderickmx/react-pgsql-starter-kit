<?php

namespace App\Console\Commands\Setup;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class InitializeDatabaseCommand extends Command
{
    protected $signature = 'app:initialize-database';

    protected $description = 'Configure and migrate the database on first run';

    public function handle(): int
    {
        if (! $this->needsInitialization()) {
            return self::SUCCESS;
        }

        intro('First-Run Database Initialization');

        info('The database connection is not yet configured for PostgreSQL.');
        info('Running the database configuration wizard…');

        $this->call('app:configure-database');

        // Reload environment so the migrate command picks up the new values.
        $this->refreshEnvironment();

        $this->call('migrate', ['--graceful' => true]);

        outro('Database initialization complete.');

        return self::SUCCESS;
    }

    private function needsInitialization(): bool
    {
        $envFile = $this->laravel->environmentFilePath();

        if (! file_exists($envFile)) {
            return true;
        }

        $contents = file_get_contents($envFile) ?: '';

        // Check if DB_CONNECTION is still set to sqlite (laravel new default).
        if (preg_match('/^DB_CONNECTION=sqlite$/m', $contents)) {
            return true;
        }

        // Check if DB_HOST is still commented out (laravel new default).
        if (preg_match('/^#\s*DB_HOST=/m', $contents)) {
            return true;
        }

        return false;
    }

    private function refreshEnvironment(): void
    {
        $envFile = $this->laravel->environmentFilePath();
        $contents = file_get_contents($envFile) ?: '';

        /** @var array<string, string> $envValues */
        $envValues = [];

        foreach (explode("\n", $contents) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\'');

                $envValues[$key] = $value;

                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        // Update the cached config so Laravel uses the new values.
        Config::set('database.default', 'pgsql');
        Config::set('database.connections.pgsql.host', $envValues['DB_HOST'] ?? '127.0.0.1');
        Config::set('database.connections.pgsql.port', $envValues['DB_PORT'] ?? '5432');
        Config::set('database.connections.pgsql.database', $envValues['DB_DATABASE'] ?? 'laravel');
        Config::set('database.connections.pgsql.username', $envValues['DB_USERNAME'] ?? 'postgres');
        Config::set('database.connections.pgsql.password', $envValues['DB_PASSWORD'] ?? '');
        Config::set('database.migrations.table', $envValues['DB_MIGRATIONS_TABLE'] ?? 'migrations');

        // Purge the cached DB connection so the next query uses the new config.
        DB::purge('pgsql');
    }
}
