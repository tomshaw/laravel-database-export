<?php

namespace TomShaw\DatabaseExport\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use TomShaw\DatabaseExport\Helpers\Cfg;
use ZipArchive;

class DatabaseExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! class_exists('ZipArchive')) {
            $this->error('The ZipArchive class is not available. Please install the PHP zip extension.');

            return self::FAILURE;
        }

        $optionPass = $this->option('password');

        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        $database = $dbConfig['database'] ?? '';
        $username = $dbConfig['username'] ?? '';
        $password = $dbConfig['password'] ?? '';
        $host = $dbConfig['host'] ?? '';
        $port = $dbConfig['port'] ?? '';

        $filename = Cfg::getBackupFilename('sql');
        $zipFilename = Cfg::getBackupFilename('zip');
        $directory = Cfg::getBackupDirectory($connection);

        $zipFilePass = $optionPass ?: $password;

        $isWindows = PHP_OS_FAMILY === 'Windows';
        $pgCommand = $isWindows ?
            'set PGPASSWORD='.escapeshellarg($password).' && pg_dump -U '.escapeshellarg($username).' -h '.escapeshellarg($host).' -p '.escapeshellarg($port).' '.escapeshellarg($database) :
            'PGPASSWORD='.escapeshellarg($password).' pg_dump -U '.escapeshellarg($username).' -h '.escapeshellarg($host).' -p '.escapeshellarg($port).' '.escapeshellarg($database);

        $command = match ($connection) {
            'sqlite' => 'sqlite3 '.escapeshellarg($database).' ".dump"',
            'mysql' => 'mysqldump --user='.escapeshellarg($username).' --password='.escapeshellarg($password).' -h '.escapeshellarg($host).' -P '.escapeshellarg($port).' '.escapeshellarg($database),
            'pgsql' => $pgCommand,
            'sqlsrv' => 'sqlcmd -S '.escapeshellarg($host.','.$port).' -U '.escapeshellarg($username).' -P '.escapeshellarg($password).' -Q '.escapeshellarg("BACKUP DATABASE [{$database}] TO DISK = N'{$filename}' WITH NOFORMAT, NOINIT, NAME = 'Full Backup of {$database}', SKIP, NOREWIND, NOUNLOAD, STATS = 10"),
            default => null,
        };

        if ($command === null) {
            $this->error('Unsupported database connection.');

            return self::FAILURE;
        }

        $result = Process::run($command);

        if ($result->failed()) {
            $this->error('Command execution failed: '.$result->errorOutput());

            return self::FAILURE;
        }

        $output = $result->output();

        $disk = Storage::disk(config('database-export.disks.backup'));
        $disk->put($directory.DIRECTORY_SEPARATOR.$filename, $output);

        $zip = new ZipArchive;

        $zipFilePath = $disk->path($directory.DIRECTORY_SEPARATOR.$zipFilename);

        if (! $zip->open($zipFilePath, ZIPARCHIVE::CREATE | ZipArchive::OVERWRITE)) {
            $this->error("Unable to open {$zipFilePath}");

            return self::FAILURE;
        }
        $zip->addFile($disk->path($directory.DIRECTORY_SEPARATOR.$filename), $filename);

        $zip->setCompressionName($filename, ZipArchive::CM_DEFLATE, 6);
        $zip->setCompressionIndex(0, ZipArchive::CM_DEFLATE, 6);

        if ($connection !== 'sqlite') {
            $zip->setEncryptionName($filename, ZipArchive::EM_AES_256, $zipFilePass);
        } else {
            $this->warn('SQLite exports do not support zip encryption.');
        }

        $zip->close();

        $disk->delete($directory.DIRECTORY_SEPARATOR.$filename);

        $this->info('The database has been exported successfully.');

        return self::SUCCESS;
    }
}
