<?php
namespace RentalManager\Importer\Mappers;

use App\RentalManager\AddOns\Amenity;
use Illuminate\Support\Facades\Cache;
use RentalManager\Importer\Interfaces\MapperInterface;

/**
 * Created by PhpStorm.
 * Date: 7/9/18
 * Time: 1:04 PM
 * AmenitiesMapper.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

class AmenitiesMapper implements MapperInterface
{


    /**
     * Items from the database
     *
     * @var \Illuminate\Database\Eloquent\Collection|static[]
     */
    protected $items;


    /**
     * The argument on Eloquent
     * @var
     */
    protected $argument = [
        'field' => 'type',
        'value' => 'community'
    ];


    /**
     * Init
     */
    public function init()
    {
        $this->items = Cache::get('_amenities_' . $this->argument['value'], function() {
            return Amenity::where($this->argument['field'], $this->argument['value'])->get();
        });
    }


    /**
     * @param $arguments
     */
    public function setArguments($arguments)
    {
        $this->argument['field'] = $arguments['field'];
        $this->argument['value'] = $arguments['value'];
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
