<?php

namespace PatrikGrinsvall\ImportSpreadsheet;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use PatrikGrinsvall\ImportSpreadsheet\Commands\ImportSpreadsheetCommand;

class ImportSpreadsheetServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . "/../config/import-spreadsheet.php", "import-spreadsheet-config");
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ImportSpreadsheetCommand::class]);
        }
    }

    public function publishes(array $paths, $groups = null)
    {
        parent::publishes([__DIR__ . "/../config/import-spreadsheet.php" => config_path("import-spreadsheet.php")], "import-spreadsheet-config");
    }
}
