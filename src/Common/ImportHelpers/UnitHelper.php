<?php
namespace RentalManager\Importer\Common\ImportHelpers;

use App\RentalManager\Main\Unit;
use InvalidArgumentException;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/10/18
 * Time: 5:45 PM
 */

class UnitHelper
{

    /**
     * @param $listing
     * @param $property
     * @return bool
     */
    public static function update($listing, $property)
    {

        foreach ( $listing->updated_fields as $key => $value ) {
            if ( $key === 'units' )
            {
                //1. NEW units
                if ( !empty( $value['new'] ) )
                {
                    self::insert($property, $value['new']);
                }

                // 2. Updated units
                if ( !empty ( $value['updated'] ) )
                {
                    foreach ( $value['updated'] as $k => $u )
                    {
                        try {
                            $unit = Unit::where('property_id', $property->id)->where('foreign_id', $k)->first()->withTrashed();
                            $updatedUnit = self::_updateUnit($unit, $u);
                            if ( $updatedUnit->trashed() )
                            {
                                $updatedUnit->restore();
                            }
                        } catch( \Exception $e)
                        {
                            throw new InvalidArgumentException($e->getMessage(), 500);
                        }
                    }
                }

                // 3. Removed units
                if ( !empty( $value['removed'] ) )
                {

                    foreach ( $value['removed'] as $k => $unit )
                    {
                        try {
                            $delete = Unit::where('property_id', $property->id)->where('foreign_id', $k)->delete();
                        } catch( \Exception $e)
                        {
                            throw new InvalidArgumentException($e->getMessage(), 500);
                        }
                    }
                }
            }
        }

        return $property;
    }


    /**
     * @param $unit
     * @param $data
     * @return Unit
     */
    private static function _updateUnit($unit, $data)
    {
        foreach ( $data as $field => $val )
        {
            switch ( $field ) {
                case 'amenities':
                    $unit->syncAmenities($val);
                    break;

                case 'photos':
                        PhotoHelper::detach($unit);
                        PhotoHelper::insertAndAttach($unit, $val);
                    break;

                default:
                    if ( $unit->hasAttribute($field))
                    {
                        $unit->{$field} = $val;
                        $unit->save();
                    }
                    break;
            }
        }
        return $unit;
    }

    /**
     * @param array $units
     * @param $property
     * @return bool
     */
    public static function insert( $property, $units = [])
    {
        if ( $units && !empty( $units ) )
        {
            foreach ( $units as $key => $unit )
            {
                // Create units
                $un = new Unit();
                $un->foreign_id = $key;
                $un->type = $unit['type'];
                $un->name = $unit['name'];
                $un->apt_unit_ste = null;
                $un->total_units = $unit['total_units'];
                $un->available_units = $unit['available_units'];
                $un->beds = $unit['beds'];
                $un->baths = $unit['baths'];
                $un->sqft = $unit['sqft'];
                $un->security_deposit = $unit['security_deposit'];
                $un->price_min = $unit['price_min'];
                $un->price_max = $unit['price_max'];
                $un->pets_fee = $unit['pets_fee'];
                $un->pets = $unit['pets'];
                $un->pets_info = $unit['pets_info'];
                $un->available_at = $unit['available_at'];
                $un->associateProperty($property->id);
                $un->save();

                PhotoHelper::insertAndAttach($un, $unit['photos']);
                AmenityHelper::attach($un, $unit['amenities'] );
            }
        }
        return true;
    }
}
