<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * This command will allow developers to easily back up the application's database to 
 * a specified location, which can be especially helpful during development, testing, 
 * or before making major changes.
 */
class BackupDatabase extends Command
{
    // The name and signature of the command.
    protected $signature = 'db:backup {--path= : The location to store the backup}';

    // The console command description.
    protected $description = 'Backup the database and save the SQL file to a specified location';

    // Execute the console command.
    public function handle()
    {
        // Set the default storage path
        $path = $this->option('path') ?? storage_path('app/backups');

        // Ensure the backup directory exists
        if (!Storage::exists($path)) {
            Storage::makeDirectory($path);
        }

        // Get the database connection details from the config
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Define the filename for the backup
        $filename = 'backup_' . Carbon::now()->format('Y_m_d_His') . '.sql';

        // Build the mysqldump command
        $command = "mysqldump --host={$dbHost} --port={$dbPort} --user={$dbUser} --password={$dbPass} {$dbName} > {$path}/{$filename}";

        // Run the command
        $process = Process::fromShellCommandline($command);

        try {
            $process->mustRun();
            $this->info("Database backup was successful. Saved as {$filename} in {$path}");
        } catch (ProcessFailedException $exception) {
            $this->error("Database backup failed: " . $exception->getMessage());
        }
    }
}
