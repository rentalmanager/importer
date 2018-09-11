<?php
namespace RentalManager\Importer\Common\ImportHelpers;


use App\RentalManager\Main\Location;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/10/18
 * Time: 4:11 PM
 */


class LocationHelper
{
    /**
     * Insert the new location or get the existing
     * Returns the ID of the location
     *
     * @param $data
     * @return mixed
     */
    public static function insert($data)
    {
        $location = Location::where('google_place_id', $data['google_place_id'])->first();
        if ( $location )
        {
            return $location;
        } else {
            $location = new Location($data);
            $location->save();
            return $location;
        }
    }
}
