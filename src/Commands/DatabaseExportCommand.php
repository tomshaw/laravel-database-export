<?php

namespace TomShaw\DatabaseExport\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use TomShaw\DatabaseExport\Helpers\Cfg;
use TomShaw\DatabaseExport\Helpers\Env;
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
     *
     * @return int
     */
    public function handle()
    {
        if (! class_exists('ZipArchive')) {
            $this->error('The ZipArchive class is not available. Please install the PHP zip extension.');

            return 1;
        }

        $optionPass = $this->option('password');

        $database = Env::get('DB_DATABASE');
        $username = Env::get('DB_USERNAME');
        $password = Env::get('DB_PASSWORD');
        $host = Env::get('DB_HOST');
        $port = Env::get('DB_PORT');
        $connection = Env::get('DB_CONNECTION');

        $filename = Cfg::getBackupFilename('sql');
        $zipFilename = Cfg::getBackupFilename('zip');
        $directory = Cfg::getBackupDirectory($connection);

        $zipFilePass = ($optionPass) ? $optionPass : $password;

        $pgCommand = (Env::isWindows()) ?
            "set PGPASSWORD={$password} && pg_dump -U {$username} --password={$password} -h {$host} -p {$port} {$database}" :
            "PGPASSWORD={$password} pg_dump -U {$username} -h {$host} -p {$port} {$database}";

        $command = match ($connection) {
            'sqlite' => "sqlite3 {$database} \".dump\"",
            'mysql' => "mysqldump --user={$username} --password={$password} {$database}",
            'pgsql' => $pgCommand,
            'sqlsrv' => "sqlcmd -S {$host},{$port} -U {$username} -P {$password} -Q \"BACKUP DATABASE [{$database}] TO DISK = N'{$filename}' WITH NOFORMAT, NOINIT, NAME = 'Full Backup of {$database}', SKIP, NOREWIND, NOUNLOAD, STATS = 10\"",
            default => null,
        };

        if ($command === null) {
            $this->error('Unsupported database connection.');

            return 1;
        }

        $output = shell_exec($command);

        if ($output === null) {
            $this->error('Command execution failed.');

            return 1;
        }

        $disk = Storage::disk(config('database-export.disks.backup'));
        $disk->put($directory.DIRECTORY_SEPARATOR.$filename, $output);

        $zip = new ZipArchive;

        $zipFilePath = $disk->path($directory.DIRECTORY_SEPARATOR.$zipFilename);

        if (! $zip->open($zipFilePath, ZIPARCHIVE::CREATE | ZipArchive::OVERWRITE)) {
            $this->error("Unable to open {$zipFilePath}");

            return 1;
        }
        $zip->addFile($disk->path($directory.DIRECTORY_SEPARATOR.$filename), $filename);

        $zip->setCompressionName($filename, ZipArchive::CM_DEFLATE, 6);
        $zip->setCompressionIndex(0, ZipArchive::CM_DEFLATE, 6);

        // @todo error
        if ($connection !== 'sqlite') {
            $zip->setEncryptionName($filename, ZipArchive::EM_AES_256, $zipFilePass);
        }

        $zip->close();

        Storage::disk(config('database-export.disks.backup'))->delete($directory.DIRECTORY_SEPARATOR.$filename);

        $this->info('The database has been exported successfully.');

        return 0;
    }
}
