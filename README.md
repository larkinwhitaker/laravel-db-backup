# laravel-db-backup

Based off of https://github.com/schickling/laravel-backup with support for Laravel 5.

Installation
----

Update your `composer.json` file to include this package as a dependency
```json
"witty/laravel-db-backup": "dev-master"
```


Register the service provider by adding it to the providers array in the `config/app.php` file.
```php
'providers' => array(
    'Witty\LaravelDbBackup\DBBackupServiceProvider'
)
```

# Configuration

Copy the config file into your project by running
```
php artisan vendor:publish
```

This will generate a config file like this
```php
return [

    // add a backup folder in the app/database/ or your dump folder
    'path' => app_path() . '/database/backup/',

    // add the path to the restore and backup command of mysql
    // this exemple is if your are using MAMP server on a mac
    // on windows: 'C:\\...\\mysql\\bin\\'
    // on linux: '/usr/bin/'
    // trailing slash is required
    'mysql' => [
        'dump_command_path' => '/Applications/MAMP/Library/bin/',
        'restore_command_path' => '/Applications/MAMP/Library/bin/',
    ],

    // s3 settings
    's3' => [
        'path'  => 'your/s3/dump/folder'
    ]

    // Use GZIP compression
    'compress' => false,
];

```
__All settings are optional and have reasonable default values.__

## Usage

#### Backup
Creates a dump file in `app/storage/dumps`
```sh
$ php artisan db:backup
```

###### Use specific database
```sh
$ php artisan db:backup --database=mysql
```

###### Upload to AWS S3
```sh
$ php artisan db:backup --upload-s3 your-bucket
```

You can use the `--keep-only-s3` option if you don't want to keep a local copy of the SQL dump.

Uses the [aws/aws-sdk-php-laravel](https://github.com/aws/aws-sdk-php-laravel) package which needs to be [configured](https://github.com/aws/aws-sdk-php-laravel#configuration).

#### Restore
Paths are relative to the app/storage/dumps folder.

###### Restore a dump
```sh
$ php artisan db:restore dump.sql
```

###### Restore from last backup dump
```sh
$ php artisan db:restore --last-dump
```

###### List dumps
```sh
$ php artisan db:restore
```




