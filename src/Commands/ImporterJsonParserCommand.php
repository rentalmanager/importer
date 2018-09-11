<?php
namespace RentalManager\Importer\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use pcrov\JsonReader\JsonReader;
use RentalManager\Importer\Feed;
use RentalManager\Importer\Models\ImporterParserJob;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 11:54 PM
 */


class ImporterJsonParserCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:json {provider} {--file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse the latest JSON feed of the provider.';

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

        // get the file
        $total = $this->getTotalJsonItems($file['output'], $file['parent_element']);

        // Init the bar
        $bar = $this->output->createProgressBar( $total );

        // ang again...
        $reader = new JsonReader();
        $reader->open( $file['output'] );
        $reader->read($file['parent_element']);
        $depth = $reader->depth(); // Check in a moment to break when the array is done.
        $reader->read(); // Step to the first element.

        // Extracted items
        $i = 0;

        do {
            // Get the complete array in one item
            $item = (object) $reader->value();

            // Standardize the data from a provider
            $standardized = $feed->provider->standardize( (array) $item);
            // do feed listing
            try {
                $out = $feed->doFeedListing($standardized);
                $type = $out['type'];
                Log::channel(Config::get('importer.log'))->$type($out['message'], $out['fields']);
                $i++;
            } catch (\Exception $e)
            {
                $i--;
                Log::channel(Config::get('importer.log'))->error($e->getMessage());
            }

            // Bar advance
            $bar->advance();

        } while ($reader->next() && $reader->depth() > $depth);
        // close the reader
        $reader->close();

        // Bar finish
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
            'parsed' => ( $i < 0 ) ? 0 : $i,
            'file' => $file
        ];

        $job->save();

    }

    /**
     * @param $file
     * @param $parentElement
     * @return int
     * @throws \pcrov\JsonReader\Exception
     * @throws \pcrov\JsonReader\InputStream\IOException
     * @throws \pcrov\JsonReader\InvalidArgumentException
     */
    private function getTotalJsonItems($file, $parentElement)
    {
        $reader = new JsonReader();
        $reader->open( $file );
        $reader->read($parentElement);
        $depth = $reader->depth(); // Check in a moment to break when the array is done.
        $reader->read(); // Step to the first element.
        $total = 0;
        do {
            $total++;
        } while ($reader->next() && $reader->depth() > $depth);
        $reader->close();

        return $total;
    }

}
