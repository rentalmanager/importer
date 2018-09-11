<?php
namespace RentalManager\Importer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RentalManager\Importer\Common\Downloader;
use RentalManager\Importer\Feed;
use RentalManager\Importer\Models\ImporterDownload;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 8:10 AM
 */

class ImporterDownloadCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:download {provider}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads a feed file from a provider.';


    /**
     * Handler
     */
    public function handle()
    {
        // get the provider slug
        $arg = $this->argument('provider');

        // get the info
        $this->info('Starting the download sequence...');

        // init the class
        $feed = new Feed($arg);

        // get the files
        $files = $feed->provider->getFeedFiles();

        $table = [];
        $bar = $this->output->createProgressBar( count($files) );

        foreach ( $files as $key => $file )
        {
            // init the downloader
            $downloader = new Downloader();

            // download the file and fetch the result
            $result = $downloader->file($file['location'])
                                 ->extension($file['external_feed_extension'])
                                 ->storage($feed->provider->getStoragePath())
                                 ->protocol($file['from'])
                                 ->download()
                                 ->after($file['do_after_download'], $file['store_as_extension'])
                                 ->info();

            // Add the file to the array
            $files[$key]['output'] = ( !$result->error ) ? $result->output : false;
            $files[$key]['size'] = $result->size;
            $files[$key]['error'] = ( $result->error ) ? true : false;

            if ( $result->error )
            {
                Log::channel( Config::get('importer.log') )->error('A download error', ['file' => $file['location']]);
            }

            // set the table for UI display
            $table[] = [
                $result->output,
                $feed->providerModel->name,
                ( !$result->error ) ? 'OK' : 'FAILED'
            ];

            // advance the bar
            $bar->advance();
        }

        $bar->finish();

        $headers = ['File Location', 'Provider', 'Status'];
        $this->table($headers, $table);

        // Insert the data into the table
        $report = new ImporterDownload();
        $report->data = $files;
        $report->provider_id = $feed->providerModel->id;
        $report->save();
    }


}
