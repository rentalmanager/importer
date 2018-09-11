<?php
namespace RentalManager\Importer\Commands;

use App\RentalManager\Main\Property;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RentalManager\Importer\Models\ImporterListing;
use InvalidArgumentException;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/11/18
 * Time: 11:25 AM
 */

class ImporterDeactivateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:deactivate {id} {--D|debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivates item from a provider.';


    /**
     * Deactivate the item
     */
    public function handle()
    {
        $id = $this->argument('id');
        $debug = $this->option('debug');

        $listing = ImporterListing::find( $id);

        if ( !$listing )
        {
            Log::channel(Config::get('importer.log'))->error('ID is not found', ['id' => $id]);
            if ( $debug )
            {
                throw new InvalidArgumentException('ID is missing', 404);
            } else {
                return;
            }
        }

        try  {

            if ( !$listing->property_id )
            {
                throw new InvalidArgumentException('Property ID is missing', 500);
            }

            // Get the property which we need to deactivate
            $property = Property::find($listing->property_id);
            if ( !$property )
            {
                throw new InvalidArgumentException('Property ID: ' . $listing->property_id  . ' is missing in the main database', 500);
            }

            $allowed_statuses = ['removed', 'rejected', 'blocked'];

            if ( !in_array( $listing->status, $allowed_statuses) )
            {
                throw new InvalidArgumentException('Listing status must be: ' . implode( ', ', $allowed_statuses), 403);
            }

            // Everything is ok
            $property->status = ( $listing->status === 'blocked' ) ? 'blocked' : 'expired';
            $property->status_reason = ( $listing->status === 'blocked' ) ? 'Blocked at ' . Carbon::now()->toDateTimeString() : 'Expired or Rejected at ' . Carbon::now()->toDateTimeString();
            $property->save();

        } catch (\Exception $e)
        {
            Log::channel(Config::get('importer.log'))->error('Deactivation failed', ['id' => $listing->id, 'error' => $e->getMessage()]);
            if ( $debug )
            {
                throw new InvalidArgumentException('Property deactivation has failed', 404);
            } else {
                return;
            }
        }

    }
}
