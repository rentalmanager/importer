<?php
namespace RentalManager\Importer\FeedProviders;


use Carbon\Carbon;
use RentalManager\Importer\Common\ContentFormatting;
use RentalManager\Importer\Interfaces\FeedProviderInterface;
use Illuminate\Support\Facades\Config;
use RentalManager\Importer\Mappers\Mapper;
use RentalManager\Importer\Models\ImporterDownload;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 6:02 AM
 */

class RentLingo extends FeedProvider implements FeedProviderInterface
{
    /**
     * The name of the parser file
     * @var string
     */
    public $log = 'rentlingo.txt';

    /**
     * Which parser are we using for this provider
     * @var string
     */
    public $parser = 'xml';


    /**
     * Relative to the app storage in laravel
     * @var string
     */
    public $storage_path = 'feeds/rentlingo';


    /**
     * What to do before the parsing
     * @var mixed
     */
    public $on_before = 'optimize';


    /**
     * How we are sending leads
     *
     * @var string
     */
    public $contact_method = 'email';


    /**
     * The files for the download and parse
     *
     * @var array
     */
    public $files = [];

    /**
     * ApartmentList constructor.
     */
    public function __construct()
    {
        // Get the files from the config
        $this->files = Config::get('importer.feeds.rentlingo');
    }

    /**
     * Optimize the feed
     *
     * @param ImporterDownload $file
     */
    public function optimize(ImporterDownload $file)
    {
        $newData = $file->data;

        foreach ( $file->data as $key => $val )
        {
            if ( strpos($val['output'], '-recreated-') === false ) {
                $optimizer = new RentLingoOptimizer();
                $newPath = $optimizer->run($val['output']);
                $newData[$key]['output'] = $newPath;
            }
        }

        // Now we have the new output
        $file->data = $newData;
        $file->save();
    }

    /**
     * @param array $arr
     * @return array
     */
    public function standardize($arr = [])
    {
        // map the amenities for the property
        $amenities = ( (string) $arr->amenities  ) ? explode(',', (string) $arr->amenities) : null;

        // first get the pets data
        $petsData = $this->_getPetsData((string) $arr->pets);

        // get the units
        $units = $this->_getUnits($arr->units, $petsData, $amenities);

        if ( $amenities )
        {
            $mapper = new Mapper('amenities', ['field' => 'type', 'value' => 'community']);
            $amenities = $mapper->get($amenities);
        }

        $photos = null;

        if ( (string) $arr->img1 ) {
            $photos[] = (string) $arr->img1;
        }
        if ( (string) $arr->img2 ) {
            $photos[] = (string) $arr->img2;
        }
        if ( (string) $arr->img3 ) {
            $photos[] = (string) $arr->img3;
        }

        $data = [
            'foreign_id' => (string) $arr->id,
            'contact_method' => $this->contact_method,
            'contact_owner' => 'company',
            'contact_url' => (string) $arr->url,
            'contact_email_to' => (string) $arr->email,
            'contact_email_cc' => null,
            'contact_phone' => null,
            'property_type_id' => 1, // always an apartment and a community
            'rental_restriction_id' => 1, // no restriction
            'lease_duration_id' => 2, // long term by default
            'rental_type_id' => 1, // always regular for apartmentlist,
            'is_community' => true, // they have only apartment communities
            'name' => (string) $arr->community_name,
            'lease_terms' => null,
            'description' => ContentFormatting::safeString((string) $arr->description ),
            'address' => (string) $arr->address . ' ' . (string) $arr->city . ' ' . (string) $arr->state . ', ' . (string) $arr->zip,
            'amenities' => $amenities,
            'photos' => $photos,
            'tags' => null,
            'units' => $units
        ];

        return $data;
    }

    /**
     * @param $arr
     * @param $petsData
     * @param $amenities
     * @return array
     */
    private function _getUnits($arr, $petsData, $amenities = null)
    {
        // get only the active units nothing else
        $data = [];

        // because it's the same for all :)
        if ( $amenities )
        {
            $mapper = new Mapper('amenities', ['field' => 'type', 'value' => 'unit']);
            $amenities = $mapper->get($amenities);
        }

        foreach ( $arr as $u )
        {
            foreach( $u as $unit ) {
                if ( (int) $unit->priceMin > 0 ) {
                    // set the min and max price
                    if ( ( (int) $unit->priceMin != (int) $unit->priceMax) && (int) $unit->priceMax !== null  )
                    {
                        $price['min'] = (int) $unit->priceMin;
                        $price['max'] = (int) $unit->priceMax;
                    } else {
                        $price['min'] = (int) $unit->priceMin;
                        $price['max'] = null;
                    }

                    $data[(string) $unit->id] = [
                        'foreign_id' => (string) $unit->id,
                        'type' => 'floor_plan',
                        'name' => ContentFormatting::safeString((string) $unit->name),
                        'total_units' => null,
                        'available_units' => 1,
                        'beds' => (int) $unit->beds,
                        'baths' => (float) $unit->baths,
                        'sqft' => (int) $unit->sqft,
                        'security_deposit' => null,
                        'price_min' => $price['min'],
                        'price_max' => $price['max'],
                        'available_at' => Carbon::now()->toDateTimeString(),
                        'pets' => ( $petsData['pets'] === null ) ? true : $petsData['pets'],
                        'pets_fee' => $petsData['pets_fee'],
                        'pets_info' => $petsData['pets_info'],
                        'photos' => ( !empty( $unit['photos'] ) ) ? $unit['photos'] : null,
                        'amenities' => $amenities, // this provider doesnt provide unit amenities
                        'utilities' => null, // this provider doesnt provide the utilities
                        'tags' => null
                    ];
                }
            }
        }
        return $data;
    }


    /**
     * @param $pets
     * @return array
     */
    private function _getPetsData($pets)
    {

        $info = null;
        $allowed = false;

        if ( strpos('dogs', $pets) !== false || strpos('cats', $pets) !== false)
        {
            $allowed = true;
        }

        return [
            'pets' => $allowed,
            'pets_fee' => null,
            'pets_info' => $info
        ];
    }
}
