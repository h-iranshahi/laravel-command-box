<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Generate a seeder file based on existing data in a table, which 
 * can be useful for testing or exporting live data for staging.
 */
class ExportDbTablesToSeedsCommand extends Command
{
    protected $signature = 'db:export-seed';
    protected $description = 'Export the database tables to seed files';

    public function handle()
    {
        // Tables needed to export
        $tables = [
            'jobs',
            'failed_jobs',
            'accesses',
            'blog_posts',
        ];

        foreach ($tables as $table) {
            $this->exportTableToSeed($table);
        }

        $this->info('Database tables exported to seed files successfully.');
    }

    protected function exportTableToSeed($table)
    {
        // Fetch data from the table
        $data = DB::table($table)->get();

        if ($data->isEmpty()) {
            $this->info("No data found in table: {$table}");
            return;
        }

        // Generate seed file path
        $seedFileName = ucfirst(camel_case($table)) . 'Seeder.php';
        $seedFilePath = database_path("seeders/{$seedFileName}");

        // Create seed file content
        $seedContent = $this->generateSeedContent($table, $data);

        // Save the seed file
        File::put($seedFilePath, $seedContent);

        $this->info("Seed file created for table: {$table}");
    }

    protected function generateSeedContent($table, $data)
    {
        $records = $data->map(function ($item) {
            return var_export((array) $item, true);
        })->implode(',' . PHP_EOL . '            ');

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class {$table}Seeder extends Seeder
{
    public function run()
    {
        DB::table('{$table}')->insert([
            {$records}
        ]);
    }
}

PHP;
    }
}
