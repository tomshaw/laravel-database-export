<?php

namespace TomShaw\DatabaseExport\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TomShaw\DatabaseExport\Providers\DatabaseExportServiceProvider;

class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            DatabaseExportServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', __DIR__.'/../database/testing.sqlite');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $dir = __DIR__.'/../database';

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        touch($dir.'/testing.sqlite');
    }
}
