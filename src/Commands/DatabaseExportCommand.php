<?php

namespace TomShaw\DatabaseExport\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DatabaseExportCommand extends Command implements Isolatable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export
        {--connection= : The database connection to export (defaults to the default connection)}
        {--password= : The password used to encrypt the zip archive (defaults to the database password)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the database to a compressed zip archive';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! class_exists(ZipArchive::class)) {
            $this->error('The ZipArchive class is not available. Please install the PHP zip extension.');

            return self::FAILURE;
        }

        $connection = $this->resolveConnection();
        $dbConfig = Config::get("database.connections.{$connection}");

        if (! is_array($dbConfig)) {
            $this->error("Database connection [{$connection}] is not configured.");

            return self::FAILURE;
        }

        $driver = $this->toString($dbConfig['driver'] ?? $connection);

        $basename = Config::string('database-export.filename').'-'.Carbon::now()->format('Y-m-d-His');
        $dumpFilename = $basename.'.'.($driver === 'sqlsrv' ? 'bak' : 'sql');
        $zipFilename = $basename.'.zip';
        $directory = Config::string('database-export.directory').'/'.$connection;

        $tempDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'database-export-'.bin2hex(random_bytes(8));

        if (! mkdir($tempDirectory, 0700, true)) {
            $this->error("Unable to create the temporary directory {$tempDirectory}.");

            return self::FAILURE;
        }

        $dumpPath = $tempDirectory.DIRECTORY_SEPARATOR.$dumpFilename;
        $zipPath = $tempDirectory.DIRECTORY_SEPARATOR.$zipFilename;

        try {
            $dump = $this->buildDumpCommand($driver, $dbConfig, $dumpPath);

            if ($dump === null) {
                $this->error("Unsupported database driver [{$driver}].");

                return self::FAILURE;
            }

            if (! $this->runDumpProcess($dump)) {
                return self::FAILURE;
            }

            if (! is_file($dumpPath) || filesize($dumpPath) === 0) {
                $this->error('The database dump was not created.');

                return self::FAILURE;
            }

            if (! $this->createZipArchive($zipPath, $dumpPath, $dumpFilename, $this->resolvePassword($dbConfig))) {
                return self::FAILURE;
            }

            if (! $this->uploadZipArchive($zipPath, $directory.'/'.$zipFilename)) {
                return self::FAILURE;
            }

            $this->info('The database has been exported successfully.');

            return self::SUCCESS;
        } finally {
            $this->cleanupTempFiles($tempDirectory, [$dumpPath, $zipPath]);
        }
    }

    /**
     * Resolve the connection name to export.
     */
    protected function resolveConnection(): string
    {
        $option = $this->option('connection');

        return is_string($option) && $option !== '' ? $option : Config::string('database.default');
    }

    /**
     * Resolve the password used to encrypt the zip archive.
     *
     * @param  array<array-key, mixed>  $dbConfig
     */
    protected function resolvePassword(array $dbConfig): string
    {
        $option = $this->option('password');

        return is_string($option) && $option !== '' ? $option : $this->toString($dbConfig['password'] ?? '');
    }

    /**
     * Build the dump command and environment variables for the given database driver.
     *
     * Credentials are passed through environment variables so they never appear
     * in the process list, and array commands avoid shell escaping entirely.
     *
     * @param  array<array-key, mixed>  $dbConfig
     * @return array{command: list<string>, env: array<string, string>}|null
     */
    protected function buildDumpCommand(string $driver, array $dbConfig, string $dumpPath): ?array
    {
        $database = $this->toString($dbConfig['database'] ?? '');
        $username = $this->toString($dbConfig['username'] ?? '');
        $password = $this->toString($dbConfig['password'] ?? '');
        $host = $this->toString($dbConfig['host'] ?? '');
        $port = $this->toString($dbConfig['port'] ?? '');

        return match ($driver) {
            'sqlite' => [
                'command' => ['sqlite3', $database, '.once "'.$dumpPath.'"', '.dump'],
                'env' => [],
            ],
            'mysql', 'mariadb' => [
                'command' => [
                    'mysqldump',
                    '--user='.$username,
                    ...($host !== '' ? ['--host='.$host] : []),
                    ...($port !== '' ? ['--port='.$port] : []),
                    ...$this->dumpOptions($driver),
                    '--result-file='.$dumpPath,
                    $database,
                ],
                'env' => ['MYSQL_PWD' => $password],
            ],
            'pgsql' => [
                'command' => [
                    'pg_dump',
                    '--username='.$username,
                    ...($host !== '' ? ['--host='.$host] : []),
                    ...($port !== '' ? ['--port='.$port] : []),
                    ...$this->dumpOptions($driver),
                    '--file='.$dumpPath,
                    $database,
                ],
                'env' => ['PGPASSWORD' => $password],
            ],
            'sqlsrv' => [
                'command' => [
                    'sqlcmd',
                    '-S', $port !== '' ? "{$host},{$port}" : $host,
                    '-U', $username,
                    '-b',
                    '-Q', sprintf(
                        "BACKUP DATABASE [%s] TO DISK = N'%s' WITH FORMAT, INIT",
                        str_replace(']', ']]', $database),
                        str_replace("'", "''", $dumpPath)
                    ),
                ],
                'env' => ['SQLCMDPASSWORD' => $password],
            ],
            default => null,
        };
    }

    /**
     * Get the configured extra dump options for the given driver.
     *
     * @return list<string>
     */
    protected function dumpOptions(string $driver): array
    {
        $options = Config::get("database-export.options.{$driver}", []);

        if (! is_array($options)) {
            return [];
        }

        return array_values(array_map(fn (mixed $option): string => $this->toString($option), $options));
    }

    /**
     * Run the dump process and report any failure.
     *
     * @param  array{command: list<string>, env: array<string, string>}  $dump
     */
    protected function runDumpProcess(array $dump): bool
    {
        try {
            $result = Process::timeout(Config::integer('database-export.timeout'))
                ->env($dump['env'])
                ->run($dump['command']);
        } catch (Exception $exception) {
            $this->error('Command execution failed: '.$exception->getMessage());

            return false;
        }

        if ($result->failed()) {
            $this->error('Command execution failed: '.$result->errorOutput());

            return false;
        }

        return true;
    }

    /**
     * Create the zip archive, encrypting it when a password is available.
     */
    protected function createZipArchive(string $zipPath, string $dumpPath, string $dumpFilename, string $password): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Unable to create the zip archive {$zipPath}.");

            return false;
        }

        $zip->addFile($dumpPath, $dumpFilename);
        $zip->setCompressionName($dumpFilename, ZipArchive::CM_DEFLATE, Config::integer('database-export.compression_level'));

        if ($password !== '') {
            $zip->setEncryptionName($dumpFilename, ZipArchive::EM_AES_256, $password);
        } else {
            $this->warn('No password available: the zip archive will not be encrypted.');
        }

        $zip->close();

        return true;
    }

    /**
     * Stream the zip archive to the configured storage disk.
     */
    protected function uploadZipArchive(string $zipPath, string $destination): bool
    {
        $stream = fopen($zipPath, 'rb');

        if ($stream === false) {
            $this->error("Unable to read the zip archive {$zipPath}.");

            return false;
        }

        $written = Storage::disk(Config::string('database-export.disk'))->writeStream($destination, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if (! $written) {
            $this->error("Unable to write {$destination} to the backup disk.");

            return false;
        }

        return true;
    }

    /**
     * Remove the temporary files and directory created during the export.
     *
     * @param  list<string>  $files
     */
    protected function cleanupTempFiles(string $tempDirectory, array $files): void
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($tempDirectory)) {
            rmdir($tempDirectory);
        }
    }

    /**
     * Coerce a connection configuration value to a string.
     */
    protected function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
