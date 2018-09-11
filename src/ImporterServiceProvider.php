<?php
namespace RentalManager\Importer;

use Illuminate\Support\ServiceProvider;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 5:57 AM
 */

class ImporterServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;


    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'ImporterDownload' => 'command.importer.download',
        'ImporterParser' => 'command.importer.parser',
        'ImporterXmlParser' => 'command.importer.xml-parser',
        'ImporterJsonParser' => 'command.importer.json-parser',
        'ImporterGeocode' => 'command.importer.geocode',
        'ImporterGeocodeNew' => 'command.importer.geocode-new',
        'ImporterImportSingle' => 'command.importer.import',
        'ImporterImport' => 'command.importer.import-all',
        'ImporterRun' => 'command.importer.run',
        'ImporterDeactivate' => 'command.importer.deactivate',
        'ImporterDeactivateAll' => 'command.importer.deactivate-all',
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Merge config file for the current app
        $this->mergeConfigFrom(__DIR__.'/../config/importer.php', 'importer');

        // Publish the config files
        $this->publishes([
            __DIR__.'/../config/importer.php' => config_path('importer.php')
        ], 'importer');

        $this->loadMigrationsFrom(__DIR__.'/../migrations');
    }


    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // Register the app
        $this->registerApp();

        // Register Commands
        $this->registerCommands();
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    private function registerApp()
    {
        $this->app->bind('importer', function ($app) {
            return new Importer($app);
        });

        $this->app->alias('importer', 'RentalManager\Importer');
    }


    /**
     * Register the given commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        foreach (array_keys($this->commands) as $command) {
            $method = "register{$command}Command";
            call_user_func_array([$this, $method], []);
        }
        $this->commands(array_values($this->commands));
    }

    protected function registerImporterDownloadCommand()
    {
        $this->app->singleton('command.importer.download', function () {
            return new \RentalManager\Importer\Commands\ImporterDownloadCommand();
        });
    }

    protected function registerImporterParserCommand()
    {
        $this->app->singleton('command.importer.parser', function () {
            return new \RentalManager\Importer\Commands\ImporterParserCommand();
        });
    }


    protected function registerImporterXmlParserCommand()
    {
        $this->app->singleton('command.importer.xml-parser', function () {
            return new \RentalManager\Importer\Commands\ImporterXmlParserCommand();
        });
    }

    protected function registerImporterJsonParserCommand()
    {
        $this->app->singleton('command.importer.json-parser', function () {
            return new \RentalManager\Importer\Commands\ImporterJsonParserCommand();
        });
    }

    protected function registerImporterGeocodeCommand()
    {
        $this->app->singleton('command.importer.geocode', function () {
            return new \RentalManager\Importer\Commands\ImporterGeocodeCommand();
        });
    }

    protected function registerImporterGeocodeNewCommand()
    {
        $this->app->singleton('command.importer.geocode-new', function () {
            return new \RentalManager\Importer\Commands\ImporterGeocodeNewCommand();
        });
    }

    protected function registerImporterImportSingleCommand()
    {
        $this->app->singleton('command.importer.import', function () {
            return new \RentalManager\Importer\Commands\ImporterImportSingleCommand();
        });
    }

    protected function registerImporterImportCommand()
    {
        $this->app->singleton('command.importer.import-all', function () {
            return new \RentalManager\Importer\Commands\ImporterImportCommand();
        });
    }

    protected function registerImporterRunCommand()
    {
        $this->app->singleton('command.importer.run', function () {
            return new \RentalManager\Importer\Commands\ImporterRunCommand();
        });
    }

    protected function registerImporterDeactivateCommand()
    {
        $this->app->singleton('command.importer.deactivate', function () {
            return new \RentalManager\Importer\Commands\ImporterDeactivateCommand();
        });
    }

    protected function registerImporterDeactivateAllCommand()
    {
        $this->app->singleton('command.importer.deactivate-all', function () {
            return new \RentalManager\Importer\Commands\ImporterDeactivateAllCommand();
        });
    }

    /**
     * Get the services provided.
     *
     * @return array
     */
    public function provides()
    {
        return array_values($this->commands);
    }

}
