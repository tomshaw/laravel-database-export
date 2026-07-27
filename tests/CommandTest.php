<?php

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->artisan('migrate');
});

afterEach(function () {
    Storage::disk(config('database-export.disk'))->deleteDirectory(config('database-export.directory'));
});

function backupDisk(): Filesystem
{
    return Storage::disk(config('database-export.disk'));
}

function backupFiles(): array
{
    return backupDisk()->files(config('database-export.directory').'/sqlite');
}

it('exports the database successfully', function () {
    $this->artisan('db:export')
        ->expectsOutput('The database has been exported successfully.')
        ->assertExitCode(0);
});

it('creates a zip file in the backup directory', function () {
    $this->artisan('db:export')->assertExitCode(0);

    $zipFiles = array_filter(backupFiles(), fn (string $file) => str_ends_with($file, '.zip'));

    expect($zipFiles)->not->toBeEmpty();
});

it('does not leave a sql file in the backup directory', function () {
    $this->artisan('db:export')->assertExitCode(0);

    $sqlFiles = array_filter(backupFiles(), fn (string $file) => str_ends_with($file, '.sql'));

    expect($sqlFiles)->toBeEmpty();
});

it('archives a readable sql dump', function () {
    $this->artisan('db:export')->assertExitCode(0);

    $zip = new ZipArchive;
    $zip->open(backupDisk()->path(backupFiles()[0]));

    expect($zip->numFiles)->toBe(1)
        ->and($zip->getNameIndex(0))->toEndWith('.sql')
        ->and($zip->getFromIndex(0))->toContain('CREATE TABLE');

    $zip->close();
});

it('encrypts the zip archive when a password is provided', function () {
    $this->artisan('db:export', ['--password' => 'secret'])->assertExitCode(0);

    $zip = new ZipArchive;
    $zip->open(backupDisk()->path(backupFiles()[0]));

    expect($zip->getFromIndex(0))->toBeFalse();

    $zip->setPassword('secret');

    expect($zip->getFromIndex(0))->toContain('CREATE TABLE');

    $zip->close();
});

it('warns when no password is available for encryption', function () {
    $this->artisan('db:export')
        ->expectsOutput('No password available: the zip archive will not be encrypted.')
        ->assertExitCode(0);
});

it('exports a named connection into its own subdirectory', function () {
    $this->artisan('db:export', ['--connection' => 'sqlite'])->assertExitCode(0);

    expect(backupFiles())->not->toBeEmpty();
});

it('uses the configured base filename', function () {
    config()->set('database-export.filename', 'acme');

    $this->artisan('db:export')->assertExitCode(0);

    expect(basename(backupFiles()[0]))->toStartWith('acme-');
});

it('fails for an unconfigured connection', function () {
    config()->set('database.default', 'missing');

    $this->artisan('db:export')
        ->expectsOutput('Database connection [missing] is not configured.')
        ->assertExitCode(1);
});

it('fails for an unsupported database driver', function () {
    config()->set('database.default', 'unsupported');
    config()->set('database.connections.unsupported', ['driver' => 'unsupported', 'database' => 'foo']);

    $this->artisan('db:export')
        ->expectsOutput('Unsupported database driver [unsupported].')
        ->assertExitCode(1);
});
