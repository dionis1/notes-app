<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupNotes extends Command
{
    protected $signature = 'notes:backup {path : New SQLite snapshot path}';

    protected $description = 'Create a consistent SQLite snapshot using VACUUM INTO';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->error('This command supports SQLite only.');

            return self::FAILURE;
        }
        $path = $this->argument('path');
        if (file_exists($path) || ! is_dir(dirname($path))) {
            $this->error('Choose a new filename in an existing directory.');

            return self::FAILURE;
        }
        DB::connection()->getPdo()->exec('VACUUM INTO '.DB::connection()->getPdo()->quote($path));
        chmod($path, 0600);
        $this->info('Consistent snapshot created. Protect it as private user data.');

        return self::SUCCESS;
    }
}
