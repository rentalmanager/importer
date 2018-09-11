<?php
namespace RentalManager\Importer\Common;

/**
 * Created by PhpStorm.
 * Date: 7/9/18
 * Time: 5:11 PM
 * Slugger.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

class Slugger
{

    /**
     * Generate a slug
     *
     * @param $address
     * @param $uniq
     * @return string
     */
    public static function generate($address, $uniq = true)
    {
        $slug = $address;
        if ( $uniq )
        {
            $slug .= '-' . uniqid();
        }
        return str_slug($slug);
    }
}
