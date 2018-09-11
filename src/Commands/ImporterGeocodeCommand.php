<?php
namespace RentalManager\Importer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RentalManager\Geolocate\Facades\Geolocate;
use RentalManager\Importer\Models\ImporterListing;
use InvalidArgumentException;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/9/18
 * Time: 12:35 PM
 */

class ImporterGeocodeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:geocode {id} {--D|debug} {--F|force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Geocode the ImporterListing by id';


    /**
     * Handler
     */
    public function handle()
    {

        $arg = $this->argument('id');
        $debug = $this->option('debug');
        $force = $this->option('force');

        // Get the id
        $listing = ImporterListing::find($arg);

        if ( !$listing )
        {
            throw new InvalidArgumentException('Listing does not exists!', 403);
        }

        if ( $listing->location_data !== null && !$force )
        {
            return;
        }

        try {

            // Maybe we have the data already no need for API geocoding
            $affected = DB::table('geocoded')->where('foreign_id', $listing->foreign_id)
                                                   ->where('provider_id', $listing->provider_id)
                                                   ->first();

            $haveAlready = false;

            if ( $affected )
            {
                $listing->location_data = json_decode($affected->location_data, true);
                $listing->save();
                $haveAlready = true;
            } else {
                $data = Geolocate::find($listing->data['address']);
                $listing->location_data = (array) $data;
                $listing->save();

                // Add to the geocoded
                if ( !$haveAlready )
                {
                    DB::table('geocoded')->insert([
                        'provider_id' => $listing->provider_id,
                        'foreign_id' => $listing->foreign_id,
                        'location_data' => json_encode( ( array) $data )
                    ]);
                }

                if ( $debug )
                {
                    dump( $data );
                }
            }

        } catch (\Exception $e)
        {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode());
        }
    }


}
