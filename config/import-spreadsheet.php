<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Spreadsheet ID
    |--------------------------------------------------------------------------
    | This is the google spreadsheet id, it can be taken from url in browser.
    | If importing from multiple spreadsheets across serveral commands
    | dont set this in config
     */
    'spreadsheet' => env('IMPORT_SPREADSHEET_ID', null),
    /*
    |--------------------------------------------------------------------------
    | unique-key
    |--------------------------------------------------------------------------
    | The attribute in model or column in database to use for mapping
    | csv header column. Example:
    | 'csv_email_column=email'
    | will map `csv_email_column` column in spreadsheet against email
    | column in the table
     */
    'unique-key' => env('IMPORT_SPREADSHEET_UNIQUE_KEY', null),
    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    | The FQCN to the model to use, example App\\Models\\User
     */
    'model' => env('IMPORT_SPREADSHEET_MODEL', null),
    /*
    |--------------------------------------------------------------------------
    | Json column
    |--------------------------------------------------------------------------
    | If all csv columns should be mapped into a json column in databse
    | table, this is the database column name
     */
    'json-column' => env('IMPORT_SPREADSHEET_JSON_COLUMN', null),
    /*
    |--------------------------------------------------------------------------
    | cache ttl
    |--------------------------------------------------------------------------
    | By default requests to spreadsheets are cached for an hour, 0 will disable
     */
    'cache-ttl' => env('IMPORT_SPREADSHEET_CACHE_TTL', 3600),
    /*
    |--------------------------------------------------------------------------
    | filename
    |--------------------------------------------------------------------------
    | If the original csv file needs to be processed further by another job
    | this is the filename the downloaded file will have on disk
     */
    'filename' => env('IMPORT_SPREADSHEET_FILENAME', storage_path('csvimport.csv'))
];
