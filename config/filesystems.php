<?php

/*
|--------------------------------------------------------------------------
| Disques fournis par Laravel Cloud
|--------------------------------------------------------------------------
|
| Laravel Cloud provisionne un stockage objet (S3-compatible) et le décrit
| via la variable LARAVEL_CLOUD_DISK_CONFIG (un JSON listant un ou
| plusieurs disques, chacun avec ses identifiants et son bucket). En local
| ou sur un autre hébergeur, cette variable est absente et les disques
| "local"/"public" définis plus bas restent inchangés.
*/
$cloudDisks = collect(json_decode((string) env('LARAVEL_CLOUD_DISK_CONFIG', ''), true) ?: [])
    ->filter(fn (array $disk) => isset($disk['disk']))
    ->mapWithKeys(fn (array $disk) => [$disk['disk'] => [
        'driver' => 's3',
        'key' => $disk['access_key_id'] ?? null,
        'secret' => $disk['access_key_secret'] ?? null,
        'region' => $disk['default_region'] ?? 'auto',
        'bucket' => $disk['bucket'] ?? null,
        'url' => $disk['url'] ?? null,
        'endpoint' => $disk['endpoint'] ?? null,
        'use_path_style_endpoint' => (bool) ($disk['use_path_style_endpoint'] ?? false),
        'throw' => false,
        'report' => false,
    ]])
    ->all();

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => array_merge([

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ], $cloudDisks),

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
