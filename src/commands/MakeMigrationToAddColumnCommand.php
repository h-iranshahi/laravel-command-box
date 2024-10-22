<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * This command creates a migration to add a new column to a specified table. This command 
 * allows you to specify the table name, the column name, and the column type.
 * 
 * Parameters
 *  table: (string) The name of the database table to which the column will be added.
 *  column: (string) The name of the new column that will be added to the specified table.
 *  type: (string) The data type of the new column. The following types are commonly supported in Laravel migrations:
 *      string	    A VARCHAR equivalent with a maximum length of 255 characters.
 *      text	    A TEXT equivalent for long text entries.
 *      integer	    An INTEGER equivalent for whole numbers.
 *      bigInteger	A BIGINT equivalent for large whole numbers.
 *      float	    A FLOAT equivalent for floating-point numbers.
 *      double	    A DOUBLE equivalent for double-precision floating-point numbers.
 *      decimal	    A DECIMAL equivalent for fixed-point numbers.
 *      boolean	    A BOOLEAN equivalent for true/false values.
 *      date	    A DATE equivalent for storing date values (YYYY-MM-DD).
 *      time	    A TIME equivalent for storing time values (HH:MM).
 *      dateTime	A DATETIME equivalent for storing both date and time values.
 *      timestamp	A TIMESTAMP equivalent for storing timestamp values.
 *      json	    A JSON equivalent for storing JSON data.
 *      binary	    A BLOB equivalent for storing binary data.
 */
class MakeMigrationToAddColumnCommand extends Command
{
    protected $signature = 'make:add-column {table} {column} {type}';
    protected $description = 'Create a migration to add a column to a specified table';

    public function handle()
    {
        // Retrieve arguments
        $table = $this->argument('table');
        $column = $this->argument('column');
        $type = $this->argument('type');

        // Create migration name
        $migrationName = "add_{$column}_to_{$table}_table";

        // Create the migration file
        $migrationPath = database_path('migrations');
        $migrationFileName = date('Y_m_d_His') . '_' . $migrationName . '.php';
        $migrationFilePath = $migrationPath . '/' . $migrationFileName;

        // Generate migration content
        $migrationContent = $this->getMigrationContent($table, $column, $type);

        // Write to migration file
        file_put_contents($migrationFilePath, $migrationContent);

        $this->info("Migration created: {$migrationFileName}");
    }

    protected function getMigrationContent($table, $column, $type)
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Add{$this->formatColumnName($column)}To{$this->formatTableName($table)}Table extends Migration
{
    public function up()
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->{$type}('{$column}');
        });
    }

    public function down()
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropColumn('{$column}');
        });
    }
}
PHP;
    }

    protected function formatColumnName($column)
    {
        return Str::studly($column);
    }

    protected function formatTableName($table)
    {
        return Str::studly($table);
    }
}
