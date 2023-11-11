<?php

namespace TomShaw\DatabaseExport\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * Class Cfg
 *
 * Helper class for handling configuration related to database exports.
 */
class Cfg
{
    /**
     * Get the backup filename.
     *
     * This method generates a backup filename using the configured filename and the current timestamp.
     *
     * @param  string  $format The format of the backup file. Default is "sql".
     * @return string The backup filename.
     */
    public static function getBackupFilename(string $format = 'sql'): string
    {
        $filename = Config::get('database-export.disks.backup_filename');
        $timestamp = Carbon::now()->valueOf();

        return "{$filename}-{$timestamp}.{$format}";
    }

    /**
     * Get the backup directory.
     *
     * This method retrieves the backup directory for a specific database connection.
     *
     * @param  string  $connection The name of the database connection.
     * @return string The backup directory.
     */
    public static function getBackupDirectory(string $connection): string
    {
        return Config::get('database-export.disks.backup_directory') . DIRECTORY_SEPARATOR . $connection;
    }
}
