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


class Rentbits extends FeedProvider implements FeedProviderInterface
{
    /**
     * The name of the parser file
     * @var string
     */
    public $log = 'rentbits.txt';

    /**
     * Which parser are we using for this provider
     * @var string
     */
    public $parser = 'xml';


    /**
     * Relative to the app storage in laravel
     * @var string
     */
    public $storage_path = 'feeds/rentbits';


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
        $this->files = Config::get('importer.feeds.rentbits');
    }


    /**
     * Standardize
     *
     * @param array $arr
     * @return array
     */
    public function standardize($arr = [])
    {
        $amenities = null;
        if ( !empty( $arr->amenities ) ) {
            foreach ( $arr->amenities as $figure ) {
                foreach ( $figure as $amenity ) {
                    $amenities[] =  ContentFormatting::safeString( (string) $amenity);
                }
            }
            $mapper = new Mapper('amenities', ['field' => 'type', 'value' => 'community']);
            $amenities = $mapper->get($amenities);
        }

        $photos = null;
        if ( !empty( $arr->photos ) ) {
            foreach ( $arr->photos as $figure ) {
                foreach ( $figure as $image ) {
                    $photos[] = (string) $image;
                }
            }
        }

        // Fix the name
        $name = (string) $arr->marketingName;

        if ( $name === 'Headline' )
        {
            $name = (string) $arr->address;
        }

        $community = ( (string) $arr->propertyType === 'Apartment Community' ) ? true : false;

        // get the units
        $units = $this->_getUnits($arr->floorplans, $community);

        $data = [
            'foreign_id' => (string) $arr->id,
            'contact_method' => $this->contact_method,
            'contact_owner' => 'company',
            'contact_url' => null,
            'contact_email_to' => (string) $arr->contact->email,
            'contact_email_cc' => null,
            'contact_phone' => (string) $arr->contact->phone,
            'property_type_id' => $this->getPropertyType( (string) $arr->propertyType ), // map the fucker
            'rental_restriction_id' => 1, // no restriction
            'lease_duration_id' => 2, // long term by default
            'rental_type_id' => 1, // always regular for apartmentlist,
            'is_community' => $community,
            'name' => ContentFormatting::safeString($name),
            'lease_terms' => ContentFormatting::safeString((string) $arr->leaseTerms),
            'description' => ( (string) !$arr->description || (string) !$arr->description === ' ' ) ? null : ContentFormatting::safeString((string) $arr->description ),
            'address' => (string) $arr->address,
            'amenities' => $amenities,
            'photos' => $photos,
            'tags' => null,
            'units' => $units
        ];
        return $data;
    }

    /**
     * Get the units
     *
     * @param $arr
     * @param bool $community
     * @return array
     */
    protected function _getUnits($arr, $community = false)
    {
        // get only the active units nothing else
        $data = [];

        foreach ( $arr as $floorplan ) {
            foreach ( $floorplan as $unit ) {
                if ( (int) $unit->priceMin > 0 ) {
                    $beds = (int)$unit->beds;
                    $baths = (string) $unit->baths;
                    if ( (string) $unit->halfBaths === '1' )
                    {
                        $baths = $baths . '.5';
                    }
                    $photos = null;
                    if ( !empty( $unit->photos ) ) {
                        foreach ( $unit->photos as $figure ) {
                            foreach ( $figure as $image ) {
                                $photos[] = (string) $image;
                            }
                        }
                    }

                    $amenities = null;
                    if ( !empty( $unit->amenities ) ) {
                        foreach ( $unit->amenities as $figure ) {
                            foreach ( $figure as $amenity ) {
                                $amenities[] =  ContentFormatting::safeString( (string) $amenity);
                            }
                        }
                        $mapper = new Mapper('amenities', ['field' => 'type', 'value' => 'unit']);
                        $amenities = $mapper->get($amenities);
                    }

                    if ( ( (int) $unit->priceMin != (int) $unit->priceMax) && (int) $unit->priceMax !== null  )
                    {
                        $price['min'] = (int) $unit->priceMin;
                        $price['max'] = (int) $unit->priceMax;
                    } else {
                        $price['min'] = (int) $unit->priceMin;
                        $price['max'] = null;
                    }

                    $data[(string)  $unit->id ] = [
                        'foreign_id' => (string) $unit->id,
                        'type' => ( $community ) ? 'floor_plan' : 'unit',
                        'name' => (string) $unit->name,
                        'total_units' => (int) $unit->totalUnits,
                        'available_units' => 1,
                        'beds' => (int) $beds,
                        'baths' => (float) $baths,
                        'sqft' => (int) $unit->sqft,
                        'security_deposit' => (float) $unit->securityDeposit,
                        'price_min' => $price['min'],
                        'price_max' => $price['max'],
                        'available_at' => Carbon::now()->toDateTimeString(),
                        'pets' =>( (int) $unit->pets === 1 ) ? true : false,
                        'pets_fee' => null,
                        'pets_info' => null,
                        'photos' => ( !empty( $photos ) ) ? $photos : null,
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
     * @param $type
     * @return int
     */
    protected function getPropertyType($type)
    {
        switch ( strtolower($type) )
        {
            case 'apartment community':
            case 'apartment':
                return 1;
                break;

            case 'loft':
            case 'condo':
                return 2;
                break;

            case 'single family home':
                return 3;
                break;

            case 'townhouse':
                return 4;
                break;

            case 'duplex/triplex':
                return 5;
                break;

            case 'other':
                return 2;
                break;

            default:
                return 1;
                break;
        }
    }
}
