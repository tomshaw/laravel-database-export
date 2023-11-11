<?php

namespace TomShaw\DatabaseExport\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TomShaw\DatabaseExport\Providers\DatabaseExportServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            DatabaseExportServiceProvider::class,
        ];
    }
}
