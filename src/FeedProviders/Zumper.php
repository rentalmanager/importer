<?php
namespace RentalManager\Importer\FeedProviders;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use RentalManager\Importer\Common\ContentFormatting;
use RentalManager\Importer\Interfaces\FeedProviderInterface;
use RentalManager\Importer\Mappers\Mapper;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 6:02 AM
 */


class Zumper extends FeedProvider implements FeedProviderInterface
{

    /**
     * The name of the parser file
     * @var string
     */
    public $log = 'zumper.txt';

    /**
     * Which parser are we using for this provider
     * @var string
     */
    public $parser = 'xml';


    /**
     * Relative to the app storage in laravel
     * @var string
     */
    public $storage_path = 'feeds/zumper';


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
        $this->files = Config::get('importer.feeds.zumper');
    }

    /**
     * @param array $arr
     * @return array
     */
    public function standardize($arr = [])
    {
        $address = ( string ) $arr->{"community-address"}->{"community-street-address"};
        $address .= ' ' . ( string ) $arr->{"community-address"}->{"community-city-name"};
        $address .= ' ' . ( string ) $arr->{"community-address"}->{"community-state-code"};
        $address .= ', ' . ( string ) $arr->{"community-address"}->{"community-zipcode"};

        $amenities = null;
        if ( !empty( $arr->{"community-amenities"}->{"community-other-amenities"} ) ) {
            foreach ( $arr->{"community-amenities"}->{"community-other-amenities"} as $figure ) {
                foreach ( $figure as $amenity ) {
                    $amenities[] =  ContentFormatting::safeString( (string) $amenity);
                }
            }
            $mapper = new Mapper('amenities', ['field' => 'type', 'value' => 'community']);
            $amenities = $mapper->get($amenities);
        }

        $photos = null;
        if ( !empty( $arr->{"community-pictures"} ) ) {
            foreach ( $arr->{"community-pictures"} as $figure ) {
                foreach ( $figure as $image ) {
                    $photos[] = (string) $image->{"community-picture-url"};
                }
            }

            $photos = array_slice($photos, 0, 5); // only five images
        }

        // first get the pets data
        $petsData = $this->_getPetsData($arr->{"community-pets"});

        // get the units
        $units = $this->_getUnits($arr->floorplans, $petsData, $amenities);

        $data = [
            'foreign_id' => (string) $arr->{"community-id"},
            'contact_method' => $this->contact_method,
            'contact_owner' => 'company',
            'contact_url' => null,
            'contact_email_to' => null,
            'contact_email_cc' => null,
            'contact_phone' => (string) $arr->{"community-phone"},
            'property_type_id' => 1, // always an apartment and a community
            'rental_restriction_id' => 1, // no restriction
            'lease_duration_id' => 2, // long term by default
            'rental_type_id' => 1, // always regular for apartmentlist,
            'is_community' => true, // they have only apartment communities
            'name' => ContentFormatting::safeString((string) $arr->{"community-name"}),
            'lease_terms' => null,
            'description' => ContentFormatting::safeString((string) $arr->{"community-description"} ),
            'address' => $address,
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

        foreach ( $arr as $floorplan ) {
            foreach ( $floorplan as $unit ) {
                if ( (int) $unit->{"floorplan-price-from"} > 0 ) {

                    $beds = (int)$unit->{"floorplan-num-bedrooms"};
                    $baths = (string) $unit->{"floorplan-num-full-bathrooms"};
                    if ( (string) $unit->{"floorplan-num-half-bathrooms"} === '1' )
                    {
                        $baths = $baths . '.5';
                    }
                    $baths = (float) $baths;
                    switch($beds)
                    {
                        case 0:
                            $name = 'Studio';
                            break;

                        case 1:
                            $name = '1 Bed';
                            break;

                        default:
                            $name = $beds . ' Beds';
                            break;
                    }

                    $photos = null;
                    if ( !empty( $unit->{"floorplan-layout"} ) ) {
                        foreach ( $unit->{"floorplan-layout"} as $figure ) {
                            foreach ( $figure as $image ) {
                                $photos[] = (string) $image->{"floorplan-layout-url"};
                            }
                        }
                    }

                    if ( ( (int) $unit->{"floorplan-price-from"} != (int) $unit->{"floorplan-price-to"}) && (int) $unit->{"floorplan-price-to"} !== null  )
                    {
                        $price['min'] = (int) $unit->{"floorplan-price-from"};
                        $price['max'] = (int) $unit->{"floorplan-price-to"};
                    } else {
                        $price['min'] = (int) $unit->{"floorplan-price-from"};
                        $price['max'] = null;
                    }

                    $data[(string)  $unit->{"floorplan-id"} ] = [
                        'foreign_id' => (string) $unit->{"floorplan-id"},
                        'type' => 'floor_plan',
                        'name' => $name,
                        'total_units' => null,
                        'available_units' => 1,
                        'beds' => (int) $beds,
                        'baths' => (float) $baths,
                        'sqft' => (int) $unit->{"floorplan-min-square-feet"},
                        'security_deposit' => null,
                        'price_min' => $price['min'],
                        'price_max' => $price['max'],
                        'available_at' => Carbon::now()->toDateTimeString(),
                        'pets' => ( $petsData['pets'] === null ) ? true : $petsData['pets'],
                        'pets_fee' => $petsData['pets_fee'],
                        'pets_info' => $petsData['pets_info'],
                        'photos' => ( !empty( $photos ) ) ? $photos : null, // too many photo requests
                        'amenities' => $amenities,
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

        if ( (string) $pets->{"community-pets-small-dogs-allowed"} === 'yes'
            || (string) $pets->{"community-pets-large-dogs-allowed"} === 'yes'
            || (string) $pets->{"community-pets-cats-allowed"} === 'yes')

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
