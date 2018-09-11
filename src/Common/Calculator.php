<?php
namespace RentalManager\Importer\Common;

/**
 * Created by PhpStorm.
 * Date: 7/9/18
 * Time: 5:03 PM
 * Calculator.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

class Calculator
{

    /**
     * Calcuate the min
     * @param $array
     * @param $key
     * @return mixed
     */
    public static function calculateMin($array, $key)
    {

        $minMax = self::calculateMinMax($array, $key);
        return $minMax['min'];
    }

    /**
     * Calculate max
     * @param $array
     * @param $key
     * @return mixed
     */
    public static function calculateMax($array, $key)
    {
        $minMax = self::calculateMinMax($array, $key);
        return $minMax['max'];
    }

    /**
     * Calculate the min and max
     *
     * @param array $array
     * @return array
     */
    public static function calculateMinMax($array, $key)
    {
        if ( is_object( $array ) )
        {
            $array = (array) $array;
        }
        $out = [];

        foreach ( $array as $item )
        {
            $out[] = $item[$key];
        }

        if ( !empty( $out ) )
        {
            return [
                'min' => min( $out ),
                'max' => max( $out )
            ];
        } else {
            return [
                'min' => null,
                'max' => null,
            ];
        }
    }

}
