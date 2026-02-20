<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->artisan('migrate');
});

afterEach(function () {
    $disk = Storage::disk(config('database-export.disks.backup'));
    $directory = config('database-export.disks.backup_directory').DIRECTORY_SEPARATOR.'sqlite';

    foreach ($disk->files($directory) as $file) {
        $disk->delete($file);
    }
});

it('exports the database successfully', function () {
    $this->artisan('db:export')
        ->expectsOutput('The database has been exported successfully.')
        ->assertExitCode(0);
});

it('creates a zip file in the backup directory', function () {
    $this->artisan('db:export');

    $disk = Storage::disk(config('database-export.disks.backup'));
    $directory = config('database-export.disks.backup_directory').DIRECTORY_SEPARATOR.'sqlite';

    $files = $disk->files($directory);
    $zipFiles = array_filter($files, fn (string $file) => str_ends_with($file, '.zip'));

    expect($zipFiles)->not->toBeEmpty();
});

it('removes the sql file after zipping', function () {
    $this->artisan('db:export');

    $disk = Storage::disk(config('database-export.disks.backup'));
    $directory = config('database-export.disks.backup_directory').DIRECTORY_SEPARATOR.'sqlite';

    $files = $disk->files($directory);
    $sqlFiles = array_filter($files, fn (string $file) => str_ends_with($file, '.sql'));

    expect($sqlFiles)->toBeEmpty();
});
