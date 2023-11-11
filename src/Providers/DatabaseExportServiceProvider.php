<?php
/**
 * Class DatabaseExportServiceProvider
 */

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
            $this->publishes([__DIR__.'/../../config/config.php' => config_path('database-export.php')], 'config');
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'database-export');

        $this->commands([
            DatabaseExportCommand::class,
        ]);
    }
}
