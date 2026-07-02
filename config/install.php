<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legacy Install Schema Dump
    |--------------------------------------------------------------------------
    |
    | The legacy installer imports this dump when creating the baseline schema.
    | Keep the default outside the public web root so web servers cannot expose
    | the dump as a static asset.
    |
    */

    'schema_dump_path' => env('INSTALL_SCHEMA_DUMP_PATH') ?: database_path('schema/install.sql'),
];
