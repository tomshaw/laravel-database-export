<?php

namespace TomShaw\DatabaseExport\Providers;

use Illuminate\Support\ServiceProvider;
use TomShaw\DatabaseExport\Commands\DatabaseExportCommand;

/**
 * Service provider for the DatabaseExport package.
 */
class DatabaseExportServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/database-export.php' => config_path('database-export.php'),
            ], 'database-export-config');

            $this->commands([
                DatabaseExportCommand::class,
            ]);
        }
    }

    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/database-export.php', 'database-export');
    }
}
