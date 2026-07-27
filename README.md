# Laravel Database Export 💾

![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/tomshaw/laravel-database-export/run-tests.yml?branch=master&style=flat-square&label=tests)
![issues](https://img.shields.io/github/issues/tomshaw/laravel-database-export?style=flat&logo=appveyor)
![forks](https://img.shields.io/github/forks/tomshaw/laravel-database-export?style=flat&logo=appveyor)
![stars](https://img.shields.io/github/stars/tomshaw/laravel-database-export?style=flat&logo=appveyor)
[![GitHub license](https://img.shields.io/github/license/tomshaw/laravel-database-export)](https://github.com/tomshaw/laravel-database-export/blob/master/LICENSE)

A Laravel console command that exports your database to a compressed, optionally AES-256 encrypted zip archive on any Laravel filesystem disk. Supports SQLite, MySQL, MariaDB, PostgreSQL and SQL Server.

## Features

- **Streams to disk** — dumps are written directly to a temporary file by the native dump tool, so large databases never exhaust PHP's memory.
- **Credentials stay off the command line** — passwords are passed via `MYSQL_PWD`, `PGPASSWORD` and `SQLCMDPASSWORD` environment variables so they never appear in the process list.
- **Any filesystem disk** — archives are uploaded via streams, so remote disks such as Amazon S3 or SFTP work out of the box.
- **AES-256 zip encryption** — archives are encrypted with the database password, or a password you provide.
- **Driver aware** — connections are resolved by driver, so custom-named connections work, and each driver's dump options are configurable.

## Requirements

- PHP 8.5
- Laravel 13.0
- The PHP `zip` extension and the dump tool for your database (`sqlite3`, `mysqldump`, `pg_dump` or `sqlcmd`) on your `PATH`.

## Installation

You can install the package via composer:

```bash
composer require tomshaw/laravel-database-export
```

Optionally publish the configuration file:

```bash
php artisan vendor:publish --tag=database-export-config
```

## Usage

Export the default database connection:

```bash
php artisan db:export
```

Export a specific connection:

```bash
php artisan db:export --connection=pgsql
```

Encrypt the archive with a custom password (defaults to the database password when omitted):

```bash
php artisan db:export --password=yourpassword
```

Archives are stored as `exports/{connection}/{filename}-{timestamp}.zip` on the configured disk.

### Scheduling

The command implements `Isolatable`, so overlapping scheduled runs are prevented automatically:

```php
Schedule::command('db:export', ['--isolated' => true])->daily();
```

## Configuration

| Key | Env variable | Default | Description |
| --- | --- | --- | --- |
| `database-export.disk` | `BACKUP_DISK` | `local` | Filesystem disk where archives are stored. |
| `database-export.filename` | `BACKUP_FILENAME` | `export` | Base filename; a timestamp is appended. |
| `database-export.directory` | `BACKUP_DIRECTORY` | `exports` | Directory on the disk; a per-connection subdirectory is added. |
| `database-export.timeout` | `BACKUP_TIMEOUT` | `3600` | Maximum seconds the dump process may run. |
| `database-export.compression_level` | `BACKUP_COMPRESSION_LEVEL` | `6` | Deflate compression level (0–9). |
| `database-export.options` | — | sensible defaults | Extra CLI options passed to the dump tool, per driver. |

For example, the default MySQL options produce consistent, complete dumps without table locks:

```php
'options' => [
    'mysql' => ['--single-transaction', '--routines', '--triggers', '--no-tablespaces'],
],
```

## Notes

- SQLite connections have no database password, so their archives are only encrypted when `--password` is provided.
- SQL Server backups use `BACKUP DATABASE`, which is executed by the database server itself — the server must have access to the local temporary directory, so this is intended for setups where the server runs on the same host.

## Support

If you have any issues or questions, please open an issue on the GitHub repository.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). See [License File](LICENSE) for more information.
