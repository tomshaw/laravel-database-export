<?php

/**
 * This configuration file defines the settings for the filesystem disks used by your application.
 *
 * @return array Returns an array of disk configurations.
 */
return [
    /**
     * The "disks" array is where you may define all of the disks that your application uses.
     * Laravel's filesystem abstraction layer supports local storage, Amazon S3, and
     * even FTP / SFTP. Of course, you may define your own drivers as well.
     */
    'disks' => [
        /**
         * The "local" disk is used for general file storage in your application's
         * "storage/app" directory. This disk uses the "local" driver, which uses the
         * PHP `file_put_contents` function to store the files.
         *
         * @var array
         */
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        /**
         * The "backup" disk is used for storing database backup files. The disk it uses
         * is defined by the "BACKUP_DISK" environment variable in your .env file. If this
         * variable is not set, it defaults to the "local" disk.
         *
         * @var string
         */
        'backup' => env('BACKUP_DISK', 'local'),

        /**
         * The "backup_filename" is used for defining the name of the backup file. The filename it uses
         * is defined by the "BACKUP_FILENAME" environment variable in your .env file. If this
         * variable is not set, it defaults to 'export'.
         *
         * @var string
         */
        'backup_filename' => env('BACKUP_FILENAME', 'company-name'),

        /**
         * The "backup_directory" is used for defining the directory where the backup file will be stored. The directory it uses
         * is defined by the "BACKUP_DIRECTORY" environment variable in your .env file. If this
         * variable is not set, it defaults to 'exports'.
         *
         * @var string
         */
        'backup_directory' => env('BACKUP_DIRECTORY', 'exports'),
    ],
];
