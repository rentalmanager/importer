<?php
namespace RentalManager\Importer\Common\ImportHelpers;

use App\RentalManager\Main\Property;
use RentalManager\Importer\Common\Calculator;
use RentalManager\Importer\Common\ContentFormatting;
use InvalidArgumentException;
use RentalManager\Importer\Common\Slugger;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/10/18
 * Time: 4:26 PM
 */


class PropertyHelper
{

    /**
     * Activate
     *
     * @param $listing
     * @return mixed
     */
    public static function activate($listing)
    {
        $property = self::find(false, false, $listing->property_id);

        if ( !$property ) {
            throw new InvalidArgumentException('Property does not exist in the main database!', 500);
        }

        $property->status = 'active';
        $property->status_reason = null;

        // finally save the property
        $property->save();

        return $property;
    }

    /**
     * Update the property
     *
     * @param $listing
     * @return mixed
     */
    public static function update($listing)
    {
        $property = self::find(false, false, $listing->property_id);

        if ( !$property ) {
            throw new InvalidArgumentException('Property does not exist in the main database!', 500);
        }

        // mark the array of simple fields which needs to be updated
        $property_updates = [];

        foreach ( $listing->updated_fields as $key => $value )
        {
            // OK we need to have a switch here, because of the random stuff we might have
            switch ( $key )
            {
                case 'description':
                    $property_updates['description'] = ContentFormatting::paragraph($listing->data['description']);
                    break;

                case 'name':
                    $property_updates['name'] = $listing->data['name'];
                    break;

                case 'is_community':
                    $property_updates['is_community'] = $listing->data['is_community'];
                    break;

                case 'lease_terms':
                    $property_updates['lease_terms'] = $listing->data['lease_terms'];
                    break;

                case 'property_type_id':
                    $property->associatePropertyType($listing->data['property_type_id']);
                    break;

                case 'rental_type_id':
                    $property->associateRentalType($listing->data['rental_type_id']);
                    break;

                case 'lease_duration_id':
                    $property->associateLeaseDuration($listing->data['lease_duration_id']);
                    break;

                case 'rental_restriction_id':
                    $property->associateRentalRestriction($listing->data['rental_restriction_id']);
                    break;

                case 'amenities':
                    $property->syncAmenities($listing->data['amenities']);
                    break;

                case 'photos':
                    // We just need to remove existing
                    PhotoHelper::detach($property);
                    PhotoHelper::insertAndAttach($property, $listing->data['photos']);
                    break;
            }
        }

        // now if property updates are not empty....
        if ( !empty( $property_updates ) )
        {
            foreach ( $property_updates as $field  => $update )
            {
                $property->{$field} = $update;
            }
        }

        $property->status = 'active';
        $property->status_reason = null;

        // always update the calculated fields
        $property->min_price = Calculator::calculateMin($listing->data['units'], 'price_min');
        $property->min_baths = Calculator::calculateMin($listing->data['units'], 'baths');
        $property->min_beds = Calculator::calculateMin($listing->data['units'], 'beds');
        $property->min_sqft = Calculator::calculateMin($listing->data['units'], 'sqft');

        // finally save the property
        $property->save();

        return $property;
    }

    /**
     * @param $foreign_id
     * @param $provider_id
     * @return mixed
     */
    private static function find($foreign_id = false, $provider_id = false, $property_id = false)
    {
        if ( $property_id )
        {
            $check = Property::find($property_id);
        } else {
            $check = Property::where('foreign_id', $foreign_id)->where('provider_id', $provider_id)->first();
        }
        return $check;
    }

    /**
     * @param $location
     * @param $listing
     * @return Property
     */
    public static function insert($listing, $location)
    {
        $data = (object) $listing->data; // convert to the object

        if ( self::find($data->foreign_id, $listing->provider_id) )
        {
            throw new InvalidArgumentException('New method called but property is in the database', 500);
        }

        $property = new Property();
        $property->foreign_id = $data->foreign_id;
        $property->is_community = $data->is_community;
        $property->name = $data->name;
        $property->lease_terms = $data->lease_terms;
        $property->description = ContentFormatting::paragraph( $data->description );
        $property->slug =  Slugger::generate($location->display_name, true);
        $property->min_price = Calculator::calculateMin($data->units, 'price_min');
        $property->min_beds = Calculator::calculateMin($data->units, 'beds');
        $property->min_baths = Calculator::calculateMin($data->units, 'baths');
        $property->min_sqft = Calculator::calculateMin($data->units, 'sqft');
        $property->featured = ( $listing->provider_id === 1 ) ? true : false; // hardcoded if Rentbits
        $property->associateRentalRestriction($data->rental_restriction_id);
        $property->status = 'active';
        $property->associateProvider($listing->provider_id);
        $property->associateLocation($location->id);
        $property->associateLeaseDuration($data->lease_duration_id);
        $property->associatePropertyType($data->property_type_id);
        $property->associateRentalType($data->rental_type_id);
        $property->save();

        // Attach amenities to the property
        AmenityHelper::attach($property, $listing->data['amenities']);
        // Create and attach photos
        PhotoHelper::insertAndAttach($property, $data->photos);

        return $property;
    }
}
