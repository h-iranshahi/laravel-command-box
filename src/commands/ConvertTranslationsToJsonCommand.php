<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Converts multilingual data stored in the translations table (as used by the Voyager package) 
 * into a JSON structure compatible with Spatie's translation package. This command updates the 
 * specified model's records by transforming translatable columns into a unified JSON format, 
 * ensuring seamless integration with Spatie's translation system.
 */
class ConvertTranslationsToJsonCommand extends Command
{
    protected $signature = 'model:convert-translations-to-json {model}';
    protected $description = 'Convert a model\'s data from Voyager format to Spatie JSON format';

    private $translations;
    private $defaultLanguage = 'en';
    private $langs = [];

    public function handle()
    {
        $modelName = $this->argument('model');
        $modelClass = "App\\Models\\" . Str::studly($modelName);

        // Check if the model class exists
        if (!class_exists($modelClass)) {
            $this->error("Model {$modelClass} does not exist.");
            return;
        }

        // Fetch all records from the specified model
        $items = DB::table((new $modelClass)->getTable())->get();

        // Get the translatable columns defined in the model
        $translatableColumns = $this->getTranslatableColumns($modelClass);

        // Fetch translations for the model
        $translations = DB::table('translations')
            ->where('table_name', (new $modelClass)->getTable())
            ->whereIn('column_name', $translatableColumns)
            ->get();

        // Gather all languages, including the default language
        $this->langs[] = $this->defaultLanguage;
        foreach ($translations->groupBy('locale') as $key => $item) {
            $this->langs[] = $key;
        }

        // Begin database transaction
        try {
            DB::beginTransaction();

            // Check if the conversion has already been done
            $this->checkToPreventRewrite($modelClass, $items);

            foreach ($items as $item) {
                $row = [];
                
                foreach ($translatableColumns as $column) {
                    $data = $this->getTranslationData($translations, $column, $item->id);
                    $row[$column] = json_encode($data, JSON_UNESCAPED_UNICODE);
                }

                // Update the model's row with the new JSON formatted data
                if (count($row)) {
                    DB::table((new $modelClass)->getTable())
                        ->where('id', $item->id)
                        ->update($row);
                }
            }

            DB::commit();
            $this->info('All rows converted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->handleSqlError($e);
            $this->error('Operation failed!');
        }
    }

    /**
     * Get translatable columns from the model class.
     *
     * @param string $modelClass
     * @return array
     */
    private function getTranslatableColumns($modelClass)
    {
        $reflection = new \ReflectionClass($modelClass);
        $property = $reflection->getProperty('translatable');
        $property->setAccessible(true);
        return $property->getValue(new $modelClass);
    }

    /**
     * Handle SQL error messages and provide user feedback.
     *
     * @param \Exception $e
     */
    private function handleSqlError($e)
    {
        if ($e->getCode() == 22001) { // Data too long error
            preg_match("/Data too long for column '([^']+)'/", $e->getMessage(), $matches);
            if (isset($matches[1])) {
                $columnName = $matches[1];
                $this->error("Increase the length of the column '$columnName'.");
            }
        } else {
            $this->error($e->getMessage());
        }
    }

    /**
     * Prevent rewriting of already converted data.
     *
     * @param string $modelClass
     * @param \Illuminate\Support\Collection $items
     * @throws \Exception
     */
    private function checkToPreventRewrite($modelClass, $items)
    {
        $item = $items->first();
        $cols = $this->getTranslatableColumns($modelClass);
        $col = $cols[0];

        if ($json = json_decode($item->$col, true)) {
            $keys = array_keys($json);
            if (sort($this->langs) == sort($keys)) {
                throw new \Exception("The conversion has already been completed.");
            }
        }
    }

    /**
     * Retrieve translation data for a specific column and item ID.
     *
     * @param \Illuminate\Support\Collection $translations
     * @param string $column
     * @param int $itemId
     * @return array
     */
    private function getTranslationData($translations, $column, $itemId)
    {
        $data = [];

        foreach ($this->langs as $lang) {
            $word = $translations
                ->where('column_name', $column)
                ->where('foreign_key', $itemId)
                ->where('locale', $lang)
                ->first();

            if ($word) {
                $data[$lang] = $word->value;
            } elseif ($lang == $this->defaultLanguage) {
                $data[$lang] = ''; // Default to an empty string if no translation exists
            } else {
                $data[$lang] = '';
            }
        }

        return $data;
    }
}
