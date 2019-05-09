<?php

namespace RentalManager\Importer\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RentalManager\Importer\Feed;
use RentalManager\Importer\Models\ImporterParserJob;

/**
 * Class ImporterTxtParserCommand
 *
 * @package \RentalManager\Importer\Commands
 */
class ImporterTxtParserCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:txt {provider} {--file}';



    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse the latest TXT feed of the provider.';


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
            $total = $this->_countItems($file['output']);
        } catch (\Exception $e)
        {
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
            Log::channel(Config::get('importer.log'))->error('TXT parser failed with error: ' . $e->getMessage());
            return;
        }

        $txt = fopen($file['output'], 'r');

        // Init the bar and count of parsed objects
        $bar = $this->output->createProgressBar( $total );
        $parsed = 0;

        $i = 0;
        while (($line = fgetcsv($txt)) !== FALSE) {
            // skip column names
            $i++;
            if($i == 1)
                continue;

            $bar->advance();

            // Standardize the data from a provider
            $standardized = $feed->provider->standardize( $line );

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
        }

        fclose($txt);
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

    private function _countItems($file)
    {
        $file = fopen($file, 'r');

        $count = 0;
        while (($line = fgetcsv($file)) !== FALSE) {
            $count++;
        }

        return $count;

    }
}
