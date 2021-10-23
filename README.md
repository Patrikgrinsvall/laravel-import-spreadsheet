# Laravel import Google Spreadsheet using Eloquent Model

## Install

`composer require patrikgrinsvall/laravel-import-spreadsheet`

## Usage
- Tries to match and normalize the spreadsheet header columns with attributes in the model. 
- Dont throw an error if they are not matched.
- the `--json-column` option will put the whole row in a json column


## Running from command line

- `php artisan import:google-spreadsheet` 
- `-s --spreadsheet=   : The id of the spreadsheet, the character sequence after /d/ in the spreadsheet URL. The spreadsheet must be made public, required`
- `-m --model=         : The model to use. The columns in the spreadsheet must match the columns snake_case in the model or else they are silently skipped (required)`
- `-u --unique-key=    : eg. title=identifier, this will take header column in csv and match against the column identifier in the model which must be unique (optional)` 
- `-j --json-column=   : If the model has a json column where all data should be put, specify this here. (optional)` 
- `-c --create-new     : Always create a new record, never match key and try to update (optional)`
- `-f --filename=      : Use this filename as temporary storage(default=import.csv)`

## Config
- Can either be from command line, see below, or set hard in .env or in config after `php artisan vendor:publish`
- If running updates often and only used against one spreadsheet id, it makes sense to setup this id environment variables or config, from `config/import-spreadsheet.php`

`spreadsheet_id' => env('IMPORT_SPREADSHEET_ID', null),`

- If updating existing data, set unique-key to what header column to match what attribute in model, example:
`'unique-key' => 'POST_TITLE=post_title'`
- To specify what model to use:
`'model' => 'App\\Models\\Post'`
- If there is a column with data type json, insert all data there (otional)
`'json-column' => 'post_attributes'`
