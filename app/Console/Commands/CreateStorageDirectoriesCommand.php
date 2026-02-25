<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\warning;

class CreateStorageDirectoriesCommand extends Command
{
    protected $signature = 'app:create-storage-directories';

    protected $description = 'Create the required storage framework directories';

    /** @var string[] */
    private array $directories = [
        'storage/framework/views',
        'storage/framework/sessions',
        'storage/framework/testing',
        'storage/framework/cache/data',
    ];

    public function handle(): int
    {
        intro('Storage Directories');

        foreach ($this->directories as $directory) {
            if (is_dir(base_path($directory))) {
                warning("Already exists: {$directory}");
            } else {
                mkdir(base_path($directory), 0755, true);
                info("Created: {$directory}");
            }
        }

        outro('Storage directories are ready.');

        return self::SUCCESS;
    }
}
