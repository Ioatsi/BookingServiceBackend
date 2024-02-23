<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Path to Your Schema Files
    |--------------------------------------------------------------------------
    |
    | Here you may specify the path to your schema files. This path is used
    | to load your schema files when generating the database schema.
    |
    */

    'paths' => [
        'draft' => base_path('draft'),
        database_path('schema'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | Here you may specify the path where Blueprint will place generated files.
    | By default, Blueprint will place files in the database/migrations
    | directory.
    |
    */

    'output_path' => database_path('migrations'),

];
