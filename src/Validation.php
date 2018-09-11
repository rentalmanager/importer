<?php
namespace RentalManager\Importer;

use Illuminate\Support\Facades\Validator;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 12:46 PM
 */


class Validation {


    /**
     * Data as global
     *
     * @var
     */
    protected $data;


    /**
     * Validation errors
     *
     * @var null
     */
    protected $errors = null;


    /**
     * @var array
     */
    public $severeErrors = [
        'foreign_id',
        'contact_method',
        'contact_url',
        'contact_email_to',
        'contact_email_cc',
        'property_type_id',
        'rental_restriction_id',
        'lease_duration_id',
        'rental_type_id',
        'is_community',
        'name',
        'address',
        'units',
        'pets',
        'price_min',
        'beds',
        'baths',
        'available_units'
    ];

    /**
     * Error level
     *
     * @var bool
     */
    protected $errorLevel = 'none';


    /**
     * Validation constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->errors = null;
        $this->data = $this->_cleanData($data);
    }


    /**
     * Error responses
     *
     * @return object
     */
    public function errorResponses()
    {
        return (object) [
            'errors' => $this->errors,
            'error_level' => $this->errorLevel
        ];
    }


    /**
     * Validate method - main
     *
     * @return $this
     */
    public function validate()
    {

        // validate units at first so that we have the full array
        $this->data['units'] = $this->validateUnits($this->data['units']);

        $validate = Validator::make( $this->data, [
            'foreign_id' => 'required|string',
            'contact_method' => 'required|string',
            'contact_url' => 'nullable|url',
            'contact_email_to' => 'nullable|email',
            'contact_email_cc' => 'nullable|email',
            'contact_phone' => 'nullable|string',
            'property_type_id' => 'required|numeric',
            'rental_restriction_id' => 'required|numeric',
            'lease_duration_id' => 'required|numeric',
            'rental_type_id' => 'required|numeric',
            'is_community' => 'boolean',
            'name' => 'required|string|max:255',
            'lease_terms' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
            'address' => 'required|string',
            'amenities' => 'nullable|array',
            'photos' => 'nullable|array',
            'units' => 'required|array|min:1'
        ]);

        if ( $validate->fails() )
        {
            $validationErrors = $validate->errors();

            $errorLevel = $this->_calculateErrorLevel($validationErrors->getMessages());

            foreach ( $validationErrors->all() as $error )
            {
                $this->errors['property'][] = $error;
            }

            // set the error level
            $this->errorLevel = $errorLevel;
        }

        return $this;
    }


    /**
     * Get the data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Validate the units
     *
     * @param $units
     * @return array
     */
    protected function validateUnits($units)
    {
        if ( !empty($units) )
        {
            $cleanUnits = [];

            foreach ( $units as $key => $unit )
            {
                $unitValidated  = $this->_validateUnit($unit);
                if ( $unitValidated )
                {
                    $cleanUnits[$key] = $unitValidated;
                }
            }

            return $cleanUnits;
        } else {
            return [];
        }
    }

    /**
     * Validate single unit
     *
     * @param $unit
     * @return bool
     */
    private function _validateUnit($unit)
    {
        $validate = Validator::make( $unit, [
            'foreign_id' => 'required',
            'foreign_ids' => 'required|array',
            'type' => 'required|in:floor_plan,unit',
            'name' => 'nullable|string|max:255',
            'total_units' => 'nullable|numeric',
            'available_units' => 'required|numeric|min:1',
            'is_community' => 'boolean',
            'beds' => 'required|numeric|min:0',
            'baths' => 'required|numeric|min:1',
            'sqft' => 'nullable|numeric',
            'security_deposit' => 'nullable|numeric',
            'price_min' => 'required|numeric',
            'price_max' => 'nullable|numeric',
            'available_at' => 'required|date',
            'pets' => 'boolean',
            'pets_fee' => 'nullable|numeric',
            'pets_info' => 'nullable|string|max:500',
            'photos' => 'nullable|array',
            'amenities' => 'nullable|array',
            'utilities' => 'nullable|array'
        ]);

        if ( $validate->fails() )
        {
            $validationErrors = $validate->errors();
            $errors = [];

            $errorLevel = $this->_calculateErrorLevel($validationErrors->getMessages());
            foreach ( $validationErrors->all() as $error )
            {
                $errors[] = $error;
            }
            $this->errors['units'][$unit['foreign_id']] = $errors;
            return ( $errorLevel === 'severe' ) ? false : $unit;

        } else {
            return $unit;
        }
    }


    /**
     * Clean the data
     *
     * @param $data
     * @return mixed
     */
    private function _cleanData($data)
    {
        foreach ( $data as $key => &$value )
        {
            if ( is_string( $value ) ) {
                $value = trim( $value );
            }
        }
        return $data;
    }


    /**
     * Calculate the error level
     *
     * @param $errorMessages
     * @return bool
     */
    private function _calculateErrorLevel($errorMessages)
    {
        $severe = false;

        foreach ($errorMessages as $key => $message )
        {
            $severe = ( in_array($key, $this->severeErrors ) ) ? true : false;
        }

        return ( $severe ) ? 'severe' : 'warning';
    }
}
