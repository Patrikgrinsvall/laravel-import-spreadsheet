<?php

namespace PatrikGrinsvall\ImportSpreadsheet\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportSpreadsheetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:google-spreadsheet - Imports from google spreadsheet to db using a eloquent model, trying to match header with columns, its pretty fast, 3k rows in a few seconds.
                                {--s|spreadsheet=   : The google docs spreadsheet id. The character sequence after /d/ in the spreadsheet URL. This must be made public (required)}
                                {--m|model=         : The eloquent model to match. The columns in the spreadsheet must match the columns snake_case in the model or else they are silently skipped (required)}
                                {--u|unique-key=    : eg. header_column=column_name, this will match spreadsheet header column against the attribute/table column name in the model. Example is to use the name of the primary_key in the table. (optional)}
                                {--j|json-column=   : If the model has a json column where all data should be put, specify this here. Theoreticly this option is the only one needed. (optional)}
                                {--c|create-new     : Always create a new record, never match key and try to update the unique key. (boolean, optional)}
                                {--f|filename=      : Use this filename as intermediate storage. No need to use unless you want to parse csv in another job. (default=storage_path("import.csv")}
                                {--d|cache-ttl=     : By default the response from google docs is cached for 3600 seconds, this option changes this value, use 0 to disable cache}
                                {--e|skip-errors    : Skip errors if possible and continue with next row, such as duplicate key or error when inserting data}
                                ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import from google spreadsheets into a model.Note, in order to use the update existing row feature the model must have soft-deletes enabled. Note! The response from google docs is by default cached for an hour but can be toggled with --disable-cache flag';
    protected $help = 'Examples:
    1) Always create a new record, use the ´Post´ model and dont match against existing record, continue even if there are database errors and also add all values to a json column named json_data without caching the response
    - php artisan import:google-spreadsheet -s spreadsheetid --create-new --model=App\\\\Models\\\\Post --skip-errors --json-column=json_data --cache-ttl=0
    2) Always create a new record, use the ´Post´ model and dont match against existing record, save the spreadsheet in ´storage_path("mytmpcsv.csv")´
    - php artisan import:google-spreadsheet -s spreadsheetid --create-new --model=App\\\\Models\\\\Post --filename=mytmpcsv.csv
    3) Import data using ´database_column´ as unique key, meaning all rows will be matched against database record with this corresponding value from csv, use the ´Post´ model, save all fields as json in column ´post_attributes´ and dont cache the response
    - php artisan import:google-spreadsheet -s spreadsheetid -u="Spreadsheet header column=datebase_column"  --model=App\\\\Models\\\\Post --json-column=post_attributes --cache-ttl=0

    The config can be published using ´artisan vendor:publish´. The config values can also be set as environment variables with the same name but UPPER_CASED. Handy if you want to syncronize data from a spreadsheet into database on a regular basis, for example as a cron-job.
    ';
    public $model;
    public $uniqueAttribute = "identifier";
    public $uniqueCol = null;
    public $spreadsheet = null;
    public $confNamespace = "import-spreadsheet";
    public $baseUrl = "https://docs.google.com/spreadsheets/d/";
    public $filename = "csvimport.csv";
    public $json_column;
    public $cacheTtl = 3600;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->parseOptions();
        $this->downloadCsv();
        $this->importCsv();

        return Command::SUCCESS;
    }

    /**
     * @return bool
     * @throws \Throwable
     */
    public function downloadCsv()
    {
        $url = $this->baseUrl . $this->spreadsheet . "/export?format=csv";
        $body = Cache::remember($url, $this->cacheTtl, function () use ($url) {
            $response = Http::get($url)
                ->onError(function () use ($url) {
                    throw new \Exception('Couldnt download csv from google docs, url: ' . $url);
                }
                );
            throw_if(!$response->successful(), "Error when downloading: client error: " . $response->clientError() . " server: " . $response->serverError());
            return $response->body();
        });
        File::put(storage_path($this->filename), $body);

        return true;
    }

    /**
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    public function importCsv()
    {
        if ($this->hasOption('create-new')) {
            $rowsUdated = Db::table($this->model->getTable())->update(['deleted_at' => Carbon::now()]);
        } else $rowsUdated = 0;
        $cnt = 0;
        (new FastExcel)
            ->import(storage_path($this->filename), function ($line) use (&$cnt) {
                if (blank($line[$this->uniqueCol])) return;

                $record = [
                    $this->uniqueAttribute => $line[$this->uniqueCol]
                ];

                if ($this->option('json-column') || config($this->confNamespace . ".json-column"))
                    $line[$this->json_column] = $line;
                try {
                    if ($this->hasOption('create-new')) {
                        $model = $this->model::withTrashed()->updateOrCreate(
                            $record,
                        )->fill($line);
                        $model->restore();
                    } else {
                        $model = $this->model::create($record)->fill($line);
                    }

                    $model->save();
                } catch (\Exception $e) {
                    $this->info("Use --skip-errors to continue anyway. Exception when inserting model: " . print_R($record, 1) . "Error is:" . $e->getMessage());
                    throw_if($this->option('skip-errors') !== true, "Throwing Error when inserting model, use --skip-errors if you want to run anyway. Error is: " . $e->getMessage());
                }
                $cnt++;
            });
        $this->info((blank($this->option('create-new')) ? 'Created: ' : 'Updated: ') . $cnt . " rows into table: " . $this->model->getTable() . " which had $rowsUdated rows before import");
    }

    /**
     * @return int|void
     */
    public function parseOptions()
    {
        if (blank($this->option('spreadsheet')) && !config($this->confNamespace . ".spreadsheet_id")) {
            $this->error("Missing spreadsheet id");
            return Command::FAILURE;
        }

        $this->filename = $this->option('filename') ?? config($this->confNamespace . ".filename") ?? $this->filename;

        $this->spreadsheet = $this->option('spreadsheet') ?? config($this->confNamespace . ".spreadsheet") ??
            $this->spreadsheet;

        if (blank($this->option('unique-key')) && !(config($this->confNamespace . ".unique-key"))
            && blank($this->option('create-new'))) {
            $this->error("Missing the unique key identifier mapping. Need to know what field to map to model or the flag, --create-new");
        }
        if (blank($this->option('model') && !config($this->confNamespace . '.model'))) {
            $this->error('Missing what model to use');
        }
        $this->cacheTtl = $this->option('cache-ttl') ?? config($this->confNamespace . ".cache-ttl") ?? $this->cacheTtl;

        $this->model = app($this->option('model')) ?? config($this->confNamespace . '.model');
        throw_if(!in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->model)),
            "The model doesnt have soft deletes which are required. Please add softdeletes to the model or override importcsv function");

        if(!is_null($this->uniqueCol)) {
            $tmp = explode("=", $this->option("unique-key") ?? config($this->confNamespace . ".unique-key"));

            $this->uniqueCol = $tmp[0];
            $this->uniqueAttribute = $tmp[1];
            throw_if(!array_key_exists($this->uniqueAttribute, $this->model->getAttributes()), "The attribute: ". $this->uniqueAttribute." was not found in model: ".$this->model::class);
        }
        $this->json_column = $this->option('json-column') ?? null;
        if(!is_null($this->json_column)) {
            throw_if(!array_key_exists($this->json_column, $this->model->getAttributes()), "The json column: ". $this->json_column." was not found in model: ".$this->model::class);
        }

    }

}
