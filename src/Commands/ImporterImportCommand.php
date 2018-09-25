<?php
namespace RentalManager\Importer\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RentalManager\Importer\Feed;
use RentalManager\Importer\Models\ImporterJob;
use RentalManager\Importer\Models\ImporterListing;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/10/18
 * Time: 2:59 PM
 */

class ImporterImportCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:import-all {provider} {--F|force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the all new, updated items from a provider into a main database.';


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
            Log::channel(Config::get('importer.log'))->error('Importer stopped since the parser has been failed', ['provider_id' => $feed->providerModel->id]);
            $this->warn('Importer stopped since the parser has been failed');
            return;
        }

        Log::channel(Config::get('importer.log'))->info('Importing started at', ['provider_id' => $feed->providerModel->id]);

        // count new and updated
        $count = ImporterListing::where('provider_id', $feed->providerModel->id)
                                ->whereIn('status', ['new', 'updated', 'unmodified'])
                                ->whereNotNull('location_data')
                                ->count();

        if ( $count == 0 )
        {
            $this->info('There is no properties to import');
            return;
        }


        // Init the bar
        $bar = $this->output->createProgressBar($count);

        // get the new items
        ImporterListing::where('provider_id', $feed->providerModel->id)->whereNotNull('location_data')->chunk(200, function($items) use( $bar, $feed )  {

                foreach( $items as $item )
                {
                    if ( $item->status === 'new' || $item->status === 'updated')
                    {
                        $this->call('rm-importer:import', ['id' => $item->id]);
                        $bar->advance();
                    }
                }
            });

        $bar->finish();

        Log::channel(Config::get('importer.log'))->info('Importing ended', ['provider_id' => $feed->providerModel->id]);

    }




}
