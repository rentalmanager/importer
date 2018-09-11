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
 * Date: 9/11/18
 * Time: 11:22 AM
 */

class ImporterDeactivateAllCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:deactivate-all {provider} {--F|force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivates all items from a provider.';

    public function handle()
    {
        // get the provider slug
        $arg = $this->argument('provider');
        $force = $this->option('force');

        // Init the Feed class
        $feed = new Feed($arg);

        // Check the latest job
        $job = ImporterJob::where('provider_id', $feed->providerModel->id)->orderBy('id', 'desc')->first();
        if ( !$force && !$job->parser )
        {
            Log::channel(Config::get('importer.log'))->error('Deactivation stopped since the parser has been failed', ['provider_id' => $feed->providerModel->id]);
            $this->warn('Deactivation stopped since the parser has been failed');
            return;
        }


        // count new and updated
        $count = ImporterListing::where('provider_id', $feed->providerModel->id)
            ->whereIn('status', ['blocked', 'removed', 'rejected'])
            ->whereNotNull('property_id')
            ->count();


        if ( $count == 0 )
        {
            $this->info('There is no properties to deactivate');
            return;
        }

        // Init the bar
        $bar = $this->output->createProgressBar($count);

        // get the new items
        ImporterListing::where('provider_id', $feed->providerModel->id)->whereIn('status', ['blocked', 'removed', 'rejected'])->whereNotNull('property_id')->chunk(200, function($items) use( $bar, $feed )  {

            foreach( $items as $item )
            {
                $this->call('rm-importer:deactivate', ['id' => $item->id]);
                $bar->advance();
            }
        });

        $bar->finish();
    }

}
