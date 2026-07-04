<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Install Bootstrap Defaults
    |--------------------------------------------------------------------------
    |
    | Installer bootstrap now runs through Laravel migrations and seeders.
    | This legacy SQL path is retained only for exceptional manual import
    | workflows when explicitly set by environment.
    |
    */

    'schema_dump_path' => env('INSTALL_SCHEMA_DUMP_PATH'),
];
