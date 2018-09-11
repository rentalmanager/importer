<?php
namespace RentalManager\Feeder\Mappers;

use App\RentalManager\AddOns\Utility;
use Illuminate\Support\Facades\Cache;
use RentalManager\Feeder\Interfaces\MapperInterface;

/**
 * Created by PhpStorm.
 * Date: 7/9/18
 * Time: 1:04 PM
 * UtilitiesMapper.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

class UtilitiesMapper implements MapperInterface
{


    protected $items;

    /**
     *
     */
    public function init()
    {
        $this->items = Cache::get('_utilities', function() {
            return  Utility::all();
        });
    }

    /**
     * @param $arguments
     */
    public function setArguments($arguments)
    {
        // TODO: Implement setArguments() method.
    }

    /**
     * Data
     *
     * @param $data
     * @return array|null
     */
    public function get($data)
    {
        $out = [];

        foreach ( $data as $item )
        {
            foreach ( $this->items as $a )
            {
                if ( preg_match("/" . preg_quote($item, '/') . "/i", $a->name ) ) {
                    $out[] = $a->id;
                }
            }
        }
        return ( !empty( $out )  ) ? array_keys(array_flip($out)) : null;
    }

}