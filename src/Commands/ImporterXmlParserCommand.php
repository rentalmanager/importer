<?php
namespace RentalManager\Importer\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RentalManager\Importer\Feed;
use RentalManager\Importer\Models\ImporterParserJob;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 10:53 AM
 */


class ImporterXmlParserCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:xml {provider} {--file}';



    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse the latest XML feed of the provider.';


    /**
     * Handler
     */
    public function handle()
    {
        $arg = $this->argument('provider');
        $file = $this->option('file');
        $startTime = Carbon::now();

        // Init the Feed class
        $feed = new Feed($arg);

        // count the total of XML items
        try {
            $total = $this->_countItems($file['output'], $file['parent_element']);
        } catch (\Exception $e)
        {
            // Something is really really wrong
            $job = new ImporterParserJob();
            $job->provider_id = $feed->providerModel->id;
            $job->feed_file = $file['output'];
            $job->error = true;
            $job->error_msg = 'Technical error. File is not parseable';
            $job->data = [
                'time' => [
                  'start' => $startTime,
                  'end' => Carbon::now()
                ],
                'total' => 0,
                'parsed' => 0,
                'file' => $file,
            ];
            $job->save();

            // Log the error just to be sure
            Log::channel(Config::get('importer.log'))->error('XML parser failed with error: ' . $e->getMessage());
            return;
        }

        $xml = new \XMLReader();
        $xml->open($file['output']);

        // create the new DomDocument
        $doc = new \DOMDocument();

        // Init the bar and count of parsed objects
        $bar = $this->output->createProgressBar( $total );
        $parsed = 0;

        // move to the first node
        while ( $xml->read() && $xml->name !== $file['parent_element'] );

        while ( $xml->name === $file['parent_element'] ) {

            // create the node object
            try {

                // get the node
                $node = simplexml_import_dom( $doc->importNode( $xml->expand(), true));

                // Standardize the data from a provider
                $standardized = $feed->provider->standardize( $node );

                // do feed listing
                try {
                    $out = $feed->doFeedListing($standardized);
                    $type = $out['type'];
                    Log::channel(Config::get('importer.log'))->$type($out['message'], $out['fields']);
                    $parsed++;
                } catch (\Exception $e)
                {
                    $parsed--;
                    Log::channel(Config::get('importer.log'))->error($e->getMessage());
                }

            } catch (\Exception $e)
            {
                Log::channel(Config::get('importer.log'))->error($e->getMessage());
            }
            // Bar advance
            $bar->advance();

            try {
                $xml->next($file['parent_element']);
            } catch ( \Exception $e )
            {
                Log::channel(Config::get('importer.log'))->error($e->getMessage());
            }

        }
        $xml->close();
        $bar->finish();

        $job = new ImporterParserJob();
        $job->provider_id = $feed->providerModel->id;
        $job->feed_file = $file['output'];
        $job->error = false;
        $job->data = [
            'time' => [
                'start' => $startTime,
                'end' => Carbon::now()
            ],
            'total' => $total,
            'parsed' => ( $parsed < 0 ) ? 0 : $parsed,
            'file' => $file
        ];

        $job->save();
    }

    /**
     * @param $file
     * @param $element
     * @return int
     */
    private function _countItems($file, $element)
    {

        $xml = new \XMLReader();
        $xml->open($file);
        $countItems = 0;

        // move to the first node
        while ( $xml->read() && $xml->name !== $element );

        while ( $xml->name === $element ) {

            $countItems++;

            try {

                $xml->next($element);
            } catch ( \Exception $e )
            {
                $countItems--;
            }
        }

        $xml->close();

        return $countItems;
    }
}
