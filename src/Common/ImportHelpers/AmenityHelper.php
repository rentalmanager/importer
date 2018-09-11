<?php
namespace RentalManager\Importer\Common\ImportHelpers;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/10/18
 * Time: 4:42 PM
 */

class AmenityHelper
{

    /**
     * Attach amenities
     *
     * @param $object
     * @param array $amenities
     */
    public static function attach($object, $amenities = [])
    {
        if ( $amenities && !empty ( $amenities ) )
        {
            $object->attachAmenities($amenities);
        }
    }
}
