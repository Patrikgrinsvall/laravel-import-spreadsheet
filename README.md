# Laravel import Google Spreadsheet using a model

## Install

`composer require patrikgrinsvall/importspreadsheet`

## Config

Can either be from command line, see below, or set hard in .env or in config after artisan vendor:publish The command
tries to match the spreadsheet header columns with attributes in the model. Dont throw an error if they are not matched.
The header columns are tried to be normalized to allowed mysql column names, like spa

### Environment variables and config variables

From `config/import-spreadsheet.php`

If running updates often and only used once it makes sense to set spreadsheet id in environment variables or config
`spreadsheet_id' => env('IMPORT_SPREADSHEET_ID', null),`
If updating existing data set unique-key to what header column to match what attribute in model, example
POST_TITLE=post_title
`'unique-key' => env('IMPORT_SPREADSHEET_UNIQUE_KEY', null),`
What model to use, example App\\Models\\Post
'model' => env('IMPORT_SPREADSHEET_MODEL', null), If there is a column with data type json, insert all columns here
'json-column' => env('IMPORT_SPREADSHEET_JSON_COLUMN', null)

##    

import:google-spreadsheet {--s|spreadsheet=   : The id of the spreadsheet, the character sequence after /d/ in the
spreadsheet URL. This must be made public (required)} {--m|model=         : The model to match. The columns in the
spreadsheet must match the columns snake_case in the model or else they are silently skipped (required)}
{--u|unique-key=    : eg. title=identifier, this will take header column in csv and match against the column identifier
in the model which must be unique (optional)} {--j|json-column=   : If the model has a json column where all data should
be put, specify this here. (optional)} {--c|create-new     : Always create a new record, never match key and try to
update (boolean, optional)} {--f|filename=      : Use this filename as intermediate storage (default=import.csv)}
