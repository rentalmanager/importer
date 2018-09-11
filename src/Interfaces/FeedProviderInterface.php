<?php
namespace RentalManager\Importer\Interfaces;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 6:05 AM
 */

interface FeedProviderInterface
{
    /**
     * Standardize the data
     * @param $arr
     * @return array
     */
    public function standardize( $arr = [] );
}
