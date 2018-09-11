<?php
namespace RentalManager\Importer\FeedProviders;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use RentalManager\Importer\Interfaces\FeedProviderInterface;
use RentalManager\Importer\Mappers\Mapper;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 6:02 AM
 */

class ApartmentList extends FeedProvider implements FeedProviderInterface {

    /**
     * The name of the parser file
     * @var string
     */
    public $log = 'apartmentlist.txt';


    /**
     * Relative to the app storage in laravel
     * @var string
     */
    public $storage_path = 'feeds/apartmentlist';


    /**
     * What to do before the parsing
     * @var mixed
     */
    public $on_before = false;


    /**
     * How we are sending leads
     *
     * @var string
     */
    public $contact_method = 'api';


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
        $this->files = Config::get('importer.feeds.apartmentlist');
    }


    /**
     * @param array $arr
     * @return array
     */
    public function standardize($arr = [])
    {
        if ( empty( $arr ) )
        {
            return [];
        }

        // first get the pets data
        $petsData = $this->_getPetsData($arr['pet_policies']);

        // map the amenities for the property
        $amenities = ( $arr['amenities'] && !empty( $arr['amenities']) ) ? $arr['amenities'] : null;

        // get the units
        $units = $this->_getUnits($arr['floorplans'], $petsData, $amenities);

        if ( $amenities )
        {
            $mapper = new Mapper('amenities', ['field' => 'type', 'value' => 'community']);
            $amenities = $mapper->get($amenities);
        }

        $photos = null;

        if ( !empty( $arr['photos'] ) )
        {
            $photos = $arr['photos'];
            $photos = array_slice($photos, 0, 10); // only ten images...
        }

        $data = [
            'foreign_id' => $arr['id'],
            'contact_method' => $this->contact_method,
            'contact_owner' => 'company',
            'contact_url' => $arr['website'],
            'contact_email_to' => null,
            'contact_email_cc' => null,
            'contact_phone' => null,
            'property_type_id' => 1, // always an apartment and a community
            'rental_restriction_id' => 1, // no restriction
            'lease_duration_id' => 2, // long term by default
            'rental_type_id' => 1, // always regular for apartmentlist,
            'is_community' => true, // they have only apartment communities
            'name' => @$arr['name'],
            'lease_terms' => null,
            'description' => @$arr['description'],
            'address' => @$arr['street_address'] . ' ' . @$arr['city'] . ' ' . @$arr['state'] . ' ' . @$arr['zip'],
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

        foreach ( $arr as $unit )
        {
            if ( $unit['units_available'] > 0 ) {

                // set the min and max price
                if ( ($unit['prices']['min'] != $unit['prices']['max']) && $unit['prices']['max'] !== null  )
                {
                    $price['min'] = $unit['prices']['min'];
                    $price['max'] = $unit['prices']['max'];
                } else {
                    $price['min'] = $unit['prices']['min'];
                    $price['max'] = null;
                }


                $data[$unit['id']] = [
                    'foreign_id' => @$unit['id'],
                    'type' => 'floor_plan',
                    'name' => @$unit['name'],
                    'total_units' => null,
                    'available_units' => @$unit['units_available'],
                    'beds' => @$unit['bed'],
                    'baths' => (float) @$unit['bath'],
                    'sqft' => null,
                    'security_deposit' => null,
                    'price_min' => $price['min'],
                    'price_max' => $price['max'],
                    'available_at' => ( $unit['date_available'] ) ? Carbon::now()->parse($unit['date_available'])->toDateTimeString() : Carbon::now()->toDateTimeString(),
                    'pets' => ( $petsData['pets'] === null ) ? true : $petsData['pets'],
                    'pets_fee' => $petsData['pets_fee'],
                    'pets_info' => $petsData['pets_info'],
                    'photos' => null,
                    'amenities' => $amenities, // this provider doesnt provide unit amenities
                    'utilities' => null, // this provider doesnt provide the utilities
                    'tags' => null
                ];
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

        if ( !empty( $pets['allowed_pets'] ) )
        {
            $info .= ucwords(implode(', ', $pets['allowed_pets']));
        }

        if ( !empty( $pets['general'] ) )
        {
            if ( $pets['general']['limit'] != null )
            {
                $info .= ', ' .$pets['general']['limit'];
            }
            if ( $pets['general']['restrictions'] != null )
            {
                $info .= ', ' .$pets['general']['restrictions'];
            }
        }

        return [
            'pets' => $pets['allowed'],
            'pets_fee' => null,
            'pets_info' => $info
        ];
    }
}
