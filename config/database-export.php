<?php

return [

    /**
     * The filesystem disk where exported archives are stored. Any disk defined
     * in your application's "filesystems.php" configuration may be used,
     * including remote disks such as Amazon S3 or SFTP.
     */
    'disk' => env('BACKUP_DISK', 'local'),

    /**
     * The base filename for exported archives. A timestamp and extension are
     * appended automatically, e.g. "export-2026-07-26-153000.zip".
     */
    'filename' => env('BACKUP_FILENAME', 'export'),

    /**
     * The directory on the disk where exports are stored. A subdirectory named
     * after the connection is created automatically, e.g. "exports/mysql".
     */
    'directory' => env('BACKUP_DIRECTORY', 'exports'),

    /**
     * The maximum number of seconds the dump process may run before timing out.
     */
    'timeout' => (int) env('BACKUP_TIMEOUT', 3600),

    /**
     * The deflate compression level (0-9) applied to the archived dump file.
     */
    'compression_level' => (int) env('BACKUP_COMPRESSION_LEVEL', 6),

    /**
     * Extra command line options passed to the dump tool for each driver.
     */
    'options' => [
        'mysql' => ['--single-transaction', '--routines', '--triggers', '--no-tablespaces'],
        'mariadb' => ['--single-transaction', '--routines', '--triggers'],
        'pgsql' => [],
    ],

];
