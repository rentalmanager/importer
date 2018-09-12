<?php
namespace RentalManager\Importer\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RentalManager\Importer\Feed;
use RentalManager\Importer\Models\ImporterJob;
use RentalManager\Importer\Models\ImporterListing;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/9/18
 * Time: 5:12 PM
 */


class ImporterGeocodeNewCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:geocode-new {provider} {--F|force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Geocode the all new ImporterListing';

    /**
     * Handler
     */
    public function handle()
    {

        $arg = $this->argument('provider');
        $force = $this->option('force');
        // Init the Feed class
        $feed = new Feed($arg);

        // Check the latest job
        $job = ImporterJob::where('provider_id', $feed->providerModel->id)->orderBy('id', 'desc')->first();
        if ( !$force && !$job->parser )
        {
            Log::channel(Config::get('importer.log'))->error('Geocoder stopped since the parser has been failed', ['provider_id' => $feed->providerModel->id]);
            $this->warn('Geocoder stopped since the parser has been failed');
            return;
        }
        // count new
        $count = ImporterListing::where('provider_id', $feed->providerModel->id)->where('status', 'new')->count();

        if ( $count == 0 )
        {
            $this->info('There is no new properties to geocode');
            return;
        }

        // Init the bar
        $bar = $this->output->createProgressBar($count);

        Log::channel(Config::get('importer.log'))->info('Geocoding started', ['provider_id' => $feed->providerModel->id]);
        ImporterListing::where('provider_id', $feed->providerModel->id)->where('status', 'new')->chunk(100, function($items) use( $bar, $feed ) {
            foreach( $items as $item )
            {
                if ( $item->location_data === null )
                {
                    try {
                        // Call the command
                        $this->call('rm-importer:geocode', ['id' => $item->id]);
                        $bar->advance();
                    } catch (\Exception $e)
                    {
                        Log::channel(Config::get('importer.log'))->error($e->getMessage(), ['id' => $item->id, 'provider_id' => $feed->providerModel->id, 'foreign_id' => $item->foreign_id]);
                        $bar->advance();
                    }
                }
            }
        });
        $bar->finish();
        Log::channel(Config::get('importer.log'))->info('Geocoding stopped', ['provider_id' => $feed->providerModel->id]);
    }
}
