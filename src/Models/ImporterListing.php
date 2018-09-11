<?php
namespace RentalManager\Importer\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 9:45 AM
 */

class ImporterListing extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'foreign_id',
        'status',
        'hash',
        'updated_fields',
        'errors',
        'data',
        'location_data'
    ];


    /**
     * Belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provider()
    {
        return $this->belongsTo(
            'App\RentalManager\Main\Provider', 'provider_id'
        );
    }

    /**
     * Belongs to
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function property()
    {
        return $this->belongsTo(
            'App\RentalManager\Main\Property',
            'property_id');
    }


    /**
     * Set attribute as the json encoded string
     *
     * @param $value
     */
    public function setErrorsAttribute($value)
    {
        $this->attributes['errors'] = ( $value ) ? json_encode($value, JSON_PRESERVE_ZERO_FRACTION) : null;
    }

    /**
     * Return attribute as the json decoded array
     *
     * @param $value
     * @return mixed|null
     */
    public function getErrorsAttribute($value)
    {
        return ( $value ) ? json_decode( $value, true ) : null;
    }

    /**
     * Set attribute as the json encoded string
     *
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = ( $value ) ? json_encode($value, JSON_PRESERVE_ZERO_FRACTION) : null;
    }

    /**
     * Return attribute as the json decoded array
     *
     * @param $value
     * @return mixed|null
     */
    public function getDataAttribute($value)
    {
        return ( $value ) ? json_decode( $value, true ) : null;
    }

    /**
     * Set attribute as the json encoded string
     *
     * @param $value
     */
    public function setLocationDataAttribute($value)
    {
        $this->attributes['location_data'] = ( $value ) ? json_encode($value, JSON_PRESERVE_ZERO_FRACTION) : null;
    }

    /**
     * Return attribute as the json decoded array
     *
     * @param $value
     * @return mixed|null
     */
    public function getLocationDataAttribute($value)
    {
        return ( $value ) ? json_decode( $value, true ) : null;
    }

    /**
     * Return attribute as the json decoded array
     *
     * @param $value
     * @return mixed|null
     */
    public function getUpdatedFieldsAttribute($value)
    {
        return ( $value ) ? json_decode( $value, true ) : null;
    }

    /**
     * Set attribute as the json encoded string
     *
     * @param $value
     */
    public function setUpdatedFieldsAttribute($value)
    {
        $this->attributes['updated_fields'] = ( $value ) ? json_encode($value, JSON_PRESERVE_ZERO_FRACTION) : null;
    }

}
