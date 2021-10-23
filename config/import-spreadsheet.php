<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Spreadsheet ID
    |--------------------------------------------------------------------------
    | This is the google spreadsheet id, it can be taken from url in browser.
    | If importing from multiple spreadsheets across serveral commands
    | dont set this
     */
    'spreadsheet_id' => env('IMPORT_SPREADSHEET_ID', null),
    'unique-key' => env('IMPORT_SPREADSHEET_UNIQUE_KEY', null),
    'model' => env('IMPORT_SPREADSHEET_MODEL', null),
    'json-column' => env('IMPORT_SPREADSHEET_JSON_COLUMN', null),
];
