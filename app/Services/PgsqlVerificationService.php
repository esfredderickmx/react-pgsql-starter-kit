<?php

namespace App\Services;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PDO;
use Throwable;

use function config;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\warning;

class PgsqlVerificationService
{
    public static function verifyConnection(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if (Str::startsWith($event->command, 'migrate')) {
                PgsqlVerificationService::ensureDatabaseAndSchemaExist();
            }
        });
    }

    /**
     * @throws \Throwable
     */
    public static function ensureDatabaseAndSchemaExist(): void
    {
        intro('PostgreSQL Verification');

        try {
            $connection = DB::connection();

            if ($connection->getDriverName() !== 'pgsql') {
                outro('Non-PostgreSQL driver detected, skipping verification.');

                return;
            }

            $connection->getPdo();
        } catch (Throwable $exception) {
            $isDatabaseMissing = Str::contains($exception->getMessage(), ['does not exist', 'no existe'])
                || ($exception instanceof \PDOException && $exception->getCode() === '3D000');

            if ($isDatabaseMissing) {
                PgsqlVerificationService::createDatabaseViaMaintenanceConnection();
            } else {
                throw $exception;
            }
        }

        PgsqlVerificationService::ensureMigrationSchema();

        outro('PostgreSQL is ready.');
    }

    private static function createDatabaseViaMaintenanceConnection(): void
    {
        $config = config('database.connections.pgsql');
        $db_name = $config['database'];

        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname=postgres";

        note("Attempting to create database «{$db_name}» via maintenance connection…");

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
            $stmt->execute([$db_name]);

            if (! $stmt->fetch()) {
                $pdo->exec("CREATE DATABASE \"$db_name\"");

                info("Database «{$db_name}» created successfully.");
            } else {
                warning("Database «{$db_name}» already exists, skipping creation.");
            }
        } catch (Throwable $exception) {
            error("Could not auto-create database «{$db_name}»: {$exception->getMessage()}");
        }
    }

    private static function ensureMigrationSchema(): void
    {
        $migration_table = config('database.migrations.table', 'migrations');

        if (Str::contains($migration_table, '.')) {
            $schema = Str::before($migration_table, '.');

            DB::statement("CREATE SCHEMA IF NOT EXISTS $schema");

            info("Schema «{$schema}» is ready.");
        }
    }
}
