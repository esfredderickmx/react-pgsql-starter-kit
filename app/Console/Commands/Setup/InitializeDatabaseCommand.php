<?php

namespace App\Console\Commands\Setup;

use Illuminate\Console\Command;

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

        foreach (explode("\n", $contents) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\'');

                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        // Clear the cached config so Laravel re-reads the environment.
        $this->laravel['config']->set('database.default', 'pgsql');
        $this->laravel['config']->set('database.connections.pgsql.host', env('DB_HOST'));
        $this->laravel['config']->set('database.connections.pgsql.port', env('DB_PORT'));
        $this->laravel['config']->set('database.connections.pgsql.database', env('DB_DATABASE'));
        $this->laravel['config']->set('database.connections.pgsql.username', env('DB_USERNAME'));
        $this->laravel['config']->set('database.connections.pgsql.password', env('DB_PASSWORD'));
        $this->laravel['config']->set('database.migrations.table', env('DB_MIGRATIONS_TABLE', 'migrations'));

        // Purge the cached DB connection so the next query uses the new config.
        $this->laravel['db']->purge('pgsql');
    }
}
