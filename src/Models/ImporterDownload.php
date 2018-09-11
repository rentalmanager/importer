<?php
namespace RentalManager\Importer\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 9:45 AM
 */

class ImporterDownload extends Model
{
    /**
     * Fillables
     *
     * @var array
     */
    protected $fillable = [
        'provider_id',
        'data'
    ];

    /**
     * Set data attribute
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = ( $value ) ? json_encode($value, JSON_PRESERVE_ZERO_FRACTION) : null;
    }


    /**
     * Get the data attribute
     *
     * @param $value
     * @return mixed|null
     */
    public function getDataAttribute($value)
    {
        return ( $value ) ? json_decode( $value, true ) : null;
    }

}
