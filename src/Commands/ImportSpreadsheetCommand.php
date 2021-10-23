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
    protected $signature = 'import:google-spreadsheet
                                {--s|spreadsheet=   : The id of the spreadsheet, the character sequence after /d/ in the spreadsheet URL. This must be made public (required)}
                                {--m|model=         : The model to match. The columns in the spreadsheet must match the columns snake_case in the model or else they are silently skipped (required)}
                                {--u|unique-key=    : eg. title=identifier, this will take header column in csv and match against the column identifier in the model which must be unique (optional)}
                                {--j|json-column=   : If the model has a json column where all data should be put, specify this here. (optional)}
                                {--c|create-new     : Always create a new record, never match key and try to update (boolean, optional)}
                                {--f|filename=      : Use this filename as intermediate storage (default=import.csv)}
                                ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import from google spreadsheets into a model.Note, in order to use the update existing row feature the model must have softdeletes.';
    protected $help = 'Examples:
    - php artisan import:google-spreadsheet -s spreadsheetid --create-new --model App\\\\Models\\\\Post
    - php artisan import:google-spreadsheet -s spreadsheetid --create-new --model App\\\\Models\\\\Post --filename=mytmpcsv.csv
    - php artisan import:google-spreadsheet -s spreadsheetid -u "Spreadsheet header column=datebase_column"  --model App\\\\Models\\\\Post --json-column post_attributes

    Config can be published and also set in environment variables. Handy if supposed to syncronize data from a spreadsheet into database on a regular basis.
    ';
    public $model;
    public $uniqueAttribute = "identifier";
    public $uniqueCol = null;
    public $spreadsheet = null;
    public $confNamespace = "import-spreadsheet";
    public $baseUrl = "https://docs.google.com/spreadsheets/d/";
    public $filename = "csvimport.csv";
    public $json_column;

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
        $body = Cache::remember($url, 3600, function () use ($url) {
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

                if ($this->hasOption('create-new')) {
                    $model = $this->model::withTrashed()->updateOrCreate(
                        $record,
                    )->fill($line);
                    $model->restore();
                } else {
                    $model = $this->model::create($record)->fill($line);
                }

                $model->save();
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
        if (!blank($this->option('filename'))) $this->filename = $this->option('filename');

        $this->spreadsheet = $this->option('spreadsheet') ?? config($this->confNamespace . ".spreadsheet_id") ??
            $this->spreadsheet;

        if (blank($this->option('unique-key')) && !(config($this->confNamespace . ".unique-key"))
            && blank($this->option('create-new'))) {
            $this->error("Missing the unique key identifier mapping. Need to know what field to map to model");
        }
        if (blank($this->option('model') && !config($this->confNamespace . '.model'))) {
            $this->error('Missing what model to use');
        }


        $this->model = app($this->option('model'));
        throw_if(!in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->model)),
            "The model doesnt have soft deletes which are required. Please add softdeletes to the model or override importcsv function");

        $tmp = explode("=", $this->option("unique-key") ?? config($this->confNamespace . ".unique-key"));

        $this->uniqueCol = $tmp[0];
        $this->uniqueAttribute = $tmp[1];
        $this->json_column = $this->option('json-column') ?? null;
    }

}

