<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class ConfigureDatabaseCommand extends Command
{
    protected $signature = 'app:configure-database';

    protected $description = 'Configure the PostgreSQL database connection';

    public function handle(): int
    {
        intro('PostgreSQL Database Setup');

        note('Enter your PostgreSQL connection details. Press Enter to accept the defaults.');

        $host = text(
            label: 'Host',
            default: $this->currentEnvValue('DB_HOST', '127.0.0.1'),
            required: true,
        );

        $port = text(
            label: 'Port',
            default: $this->currentEnvValue('DB_PORT', '5432'),
            required: true,
            validate: fn (string $value) => is_numeric($value) ? null : 'Port must be a number.',
        );

        $database = text(
            label: 'Database name',
            default: $this->currentEnvValue('DB_DATABASE', 'laravel'),
            required: true,
        );

        $username = text(
            label: 'Username',
            default: $this->currentEnvValue('DB_USERNAME', 'postgres'),
            required: true,
        );

        $pass = password(
            label: 'Password',
            hint: 'Leave empty if no password is required.',
        );

        $migrationsTable = text(
            label: 'Migrations table',
            default: $this->currentEnvValue('DB_MIGRATIONS_TABLE', 'migrations'),
            required: true,
            hint: 'Use schema.table notation to store migrations in a specific schema (e.g. database.migrations).',
        );

        $this->writeEnvValue('DB_CONNECTION', 'pgsql');
        $this->writeEnvValue('DB_HOST', $host);
        $this->writeEnvValue('DB_PORT', $port);
        $this->writeEnvValue('DB_DATABASE', $database);
        $this->writeEnvValue('DB_USERNAME', $username);
        $this->writeEnvValue('DB_PASSWORD', $pass);
        $this->writeEnvValue('DB_MIGRATIONS_TABLE', $migrationsTable);

        outro("Database configuration saved. Connecting to «{$database}»…");

        return self::SUCCESS;
    }

    private function currentEnvValue(string $key, string $default = ''): string
    {
        $contents = file_get_contents($this->laravel->environmentFilePath()) ?: '';

        if (preg_match("/^#?\s*{$key}=(.*)$/m", $contents, $matches)) {
            return trim($matches[1], '"\'') ?: $default;
        }

        return $default;
    }

    private function writeEnvValue(string $key, string $value): void
    {
        $envFile = $this->laravel->environmentFilePath();
        $contents = file_get_contents($envFile) ?: '';

        $safeValue = preg_match('/\s/', $value) ? '"'.$value.'"' : $value;

        // Replace active or commented-out lines for this key.
        $updated = preg_replace("/^#?\s*{$key}=.*$/m", "{$key}={$safeValue}", $contents, 1, $count);

        if ($count === 0 || $updated === null) {
            $updated = rtrim($contents)."\n{$key}={$safeValue}\n";
        }

        file_put_contents($envFile, $updated);
    }
}
