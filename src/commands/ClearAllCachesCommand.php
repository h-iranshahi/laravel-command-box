<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Instead of running multiple commands to clear various caches like configuration, routes, views, 
 * and application caches, you can create a single custom command to handle it all in one go
 */
class ClearAllCaches extends Command
{
    // The name and signature of the command.
    protected $signature = 'cache:clear-all';

    // The console command description.
    protected $description = 'Clear all caches including application, route, config, view, and event caches';

    // Execute the console command.
    public function handle()
    {
        $this->info('Clearing application cache...');
        Artisan::call('cache:clear');

        $this->info('Clearing route cache...');
        Artisan::call('route:clear');

        $this->info('Clearing configuration cache...');
        Artisan::call('config:clear');

        $this->info('Clearing compiled view files...');
        Artisan::call('view:clear');

        $this->info('Clearing event cache...');
        Artisan::call('event:clear');

        $this->info('All caches have been cleared successfully.');
    }
}
