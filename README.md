# Laravel import Google Spreadsheet using Eloquent Model

## Install

`composer require patrikgrinsvall/laravel-import-spreadsheet`

## Usage Examples
1. Always create a new record, use the ´Post´ model and dont match against existing record, continue even if there are database errors and also add all values to a json column named json_data without caching the response
```
    php artisan import:google-spreadsheet \
    -s <spreadsheetid> \
    --create-new \
    --model=App\\Models\\Post \
    --skip-errors \
    --json-column=json_data \
    --cache-ttl=0
```

2. Always create a new record, use the ´Post´ model and dont match against existing record, save the spreadsheet in `storage_path("mytmpcsv.csv")`
```
php artisan import:google-spreadsheet  \
-s spreadsheetid \
--create-new \
--model=App\\Models\\Post \
--filename=mytmpcsv.csv \
```
3. Import data using ´database_column´ as unique key, meaning all rows will be matched against database record with this corresponding value from csv, use the ´Post´ model, save all fields as json in column ´post_attributes´ and dont cache the response

```
php artisan import:google-spreadsheet -s spreadsheetid \
-u="Spreadsheet header column=datebase_column"  \
--model=App\\Models\\Post \
--json-column=post_attributes \
--cache-ttl=0
```


## Running from command line

`php artisan import:google-spreadsheet` 

Options

- `-s --spreadsheet=   : The id of the spreadsheet, the character sequence after /d/ in the spreadsheet URL. The spreadsheet must be made public, ` - *required*
- `-m --model=         : The model to use. The columns in the spreadsheet must match the columns snake_case in the model or else they are silently skipped` - *required*
- `-u --unique-key=    : eg. title=identifier, this will take header column in csv and match against the column identifier in the model which must be unique ` - *optional*
- `-j --json-column=   : If the model has a json column where all data should be put, specify this here. ` - *optional*
- `-c --create-new     : Always create a new record, never match key and try to update` - *optional*
- `-f --filename=      : Use this filename as temporary storage` - *default=import.csv*
- `-d --cache-ttl=     : By default the response from google docs is cached for 3600 seconds, this option changes this value, use 0 to disable cache` - *optional*
- `-e --skip-errors    : Skip errors if possible and continue with next row, such as duplicate key or error when inserting data` - *optional*

## Config
- If running updates often and only used against one spreadsheet id, it makes sense to run `artisan vendor:publish` and set this id environment variables or the config file. 

From `config/import-spreadsheet.php` 
```
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

```

## Contribute
PR´s are very welcome

## Compability
Only tested with laravel 8.0 and 8.1
