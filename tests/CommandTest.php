<?php

namespace TomShaw\DatabaseExport\Tests;

use Illuminate\Support\Facades\Artisan;

class CommandTest extends TestCase
{

    public function setup(): void
    {
        parent::setUp();

        Artisan::call('migrate');
    }

    /**
     * Test DatabaseExportCommand.
     *
     * @return void
     */
    public function testDatabaseExportCommand()
    {
        $this->artisan('db:export')
            ->expectsOutput('The database has been exported successfully.')
            ->assertExitCode(0);
    }
}
