# Laravel Database Export 💾

![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/tomshaw/laravel-database-export/run-tests.yml?branch=master&style=flat-square&label=tests)
![issues](https://img.shields.io/github/issues/tomshaw/laravel-database-export?style=flat&logo=appveyor)
![forks](https://img.shields.io/github/forks/tomshaw/laravel-database-export?style=flat&logo=appveyor)
![stars](https://img.shields.io/github/stars/tomshaw/laravel-database-export?style=flat&logo=appveyor)
[![GitHub license](https://img.shields.io/github/license/tomshaw/laravel-database-export)](https://github.com/tomshaw/laravel-database-export/blob/master/LICENSE)

A Laravel database export console command. Supports MySQL, PostgreSQL and SQL Server.

## Requirements

- PHP 8.3, 8.4, or 8.5
- Laravel 12

## Installation

You can install the package via composer:

```bash
composer require tomshaw/laravel-database-export
```

## Usage

You can use the command like this:

```bash
php artisan db:export
```

You can also provide a password for the zip file:

```bash
php artisan db:export --password=yourpassword
```

## Configuration

The command uses the following configuration keys:

- `database-export.disks.backup_filename`: The base filename for the backup.
- `database-export.disks.backup_directory`: The directory where the backup files will be stored.

## Description

The `Database Export` command does the following:

1. Checks if the `ZipArchive` class is available.
2. Retrieves the database connection details from the environment variables.
3. Builds the command to export the database based on the database connection.
4. Executes the command and saves the output to a file.
5. Creates a zip file and adds the output file to it.
6. Deletes the output file.
7. Prints a success message.

## Support

If you have any issues or questions, please open an issue on the GitHub repository.

## License

This package is open-source software licensed under the [License](LICENSE) for more information.
