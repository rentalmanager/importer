<?php
namespace RentalManager\Importer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RentalManager\Importer\Common\ImportHandler;
use RentalManager\Importer\Models\ImporterListing;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/10/18
 * Time: 3:11 PM
 */


class ImporterImportSingleCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:import {id} {--D|debug} {--T|test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the all new, updated items from a provider into a main database.';


    public function handle()
    {
        $id = $this->argument('id');
        $debug = $this->option('debug');
        $test = $this->option('test');

        //
        $listing = ImporterListing::find( $id);

        if ( !$listing )
        {
            Log::channel(Config::get('importer.log'))->error('ID is not found', ['id' => $id]);
            if ( $debug || $test )
            {
                throw new InvalidArgumentException('ID is missing', 404);
            } else {
                return;
            }
        }

        try {
            $handler = new ImportHandler($listing);
            $property = $handler->handle();

            $listing->property_id = $property->id;
            $listing->save();

            if ( $debug )
            {
                dump( $property, $listing);
            }

        } catch ( \Exception $e )
        {
            Log::channel(Config::get('importer.log'))->error('Import failed', ['id' => $listing->id, 'error' => $e->getMessage()]);
        }
    }
}
