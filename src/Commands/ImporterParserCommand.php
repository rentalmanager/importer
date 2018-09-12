<?php
namespace RentalManager\Importer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RentalManager\Importer\Feed;
use RentalManager\Importer\Importer;
use RentalManager\Importer\Models\ImporterDownload;
use RentalManager\Importer\Models\ImporterJob;
use RentalManager\Importer\Models\ImporterListing;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 10:20 AM
 */

class ImporterParserCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:parse {provider} {--force}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse the latest feed of the provider.';

    /**
     * Handler
     */
    public function handle()
    {
        $arg = $this->argument('provider');
        $force = $this->option('force');

        // Init the Feed class
        $feed = new Feed($arg);


        // Get the latest files from downloader
        $latest = $feed->getLatestFeeds($force);

        if ( !$latest )
        {
            $this->info('There are no latest files to be parsed');
            return;
        }

        $parserToContinue = true;

        foreach ( $latest as $key => $file )
        {
            if ( !$file['do_parse'] || !$file['error'] )
            {
                $parserToContinue = false;
                continue;
            }
            $parserToContinue = true;
        }

        if ( $parserToContinue )
        {
            // update status of all items before the parser
            $update = ImporterListing::where('provider_id', $feed->providerModel->id)
                ->where('status', '!=', 'blocked')
                ->where('status', '!=','rejected')
                ->update(['status' => 'removed']);
        }


        // Support for multiple provider files
        foreach ( $latest as $key => $file )
        {
            if ( !$file['do_parse'] )
            {
                Log::channel(Config::get('importer.log'))->error('File is the same as the previous one. Operation is stopped.', ['file' => $file]);
                $this->info('File ' . $file['output'] . ' is the same as the previous one. Operation is stopped.');
                continue;
            }

            if ( $file['error'] ) {
                Log::channel(Config::get('importer.log'))->error('A file as not been downloaded correctly. Parser stopped', ['file' => $file]);
                $this->warn('A file as not been downloaded correctly. Parser stopped');
                continue;
            }
            // If we have on before method
            if ( $feed->provider->getOnBefore() )
            {
                $before = $feed->provider->getOnBefore();
                $fileDB = ImporterDownload::find($file['id']);
                $feed->provider->$before($fileDB); // on before method should always receive the file model instance
                // Get the file again :)
                $fileModel = ImporterDownload::find($file['id']);
                // find the key
                $file['output'] = $fileModel->data[$key]['output'];
            }

            $this->info('Preparing to parse the ' . $file['output']);
            $parser = $file['parser'];
            $command = 'rm-importer:' . $parser;
            // call the command
            $this->call($command, ['provider' => $this->argument('provider'), '--file' => $file]);
        }

        // Mark the parser job
        $job = new ImporterJob();
        $job->provider_id = $feed->providerModel->id;
        $job->parser = $parserToContinue;
        $job->save();
    }
}
