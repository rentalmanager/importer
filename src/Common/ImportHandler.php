<?php
namespace RentalManager\Importer\Common;

use RentalManager\Importer\Common\ImportHelpers\ContactHelper;
use RentalManager\Importer\Common\ImportHelpers\LocationHelper;
use RentalManager\Importer\Common\ImportHelpers\PropertyHelper;
use RentalManager\Importer\Common\ImportHelpers\UnitHelper;
use RentalManager\Importer\Models\ImporterListing;
use InvalidArgumentException;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/10/18
 * Time: 3:14 PM
 */

class ImportHandler
{

    /**
     * Listing object
     *
     * @var ImporterListing
     */
    public $listing;

    /**
     * ImportHandler constructor.
     * @param ImporterListing $listing
     */
    public function __construct(ImporterListing $listing)
    {
        $this->listing = $listing;
    }


    /**
     * Handler
     */
    public function handle()
    {

        $property = false;

        // handle new or update
        switch ( $this->listing->status )
        {
            case 'new':
                $property = $this->_insert();
                break;

            case 'updated':
                $property = $this->_update();
                break;

            default:
                throw new InvalidArgumentException('Not allowed status', 500);
                break;
        }

        if ( !$property )
        {
            throw new InvalidArgumentException('Property is not inserted or has been a massive error', 500);
        } else {
            return $property;
        }

    }


    /**
     * Insert a record in the database
     *
     * @return \App\RentalManager\Main\Property
     */
    private function _insert()
    {
        // Get the location or insert the new one
        $location = LocationHelper::insert($this->listing->location_data);

        // Insert the new property
        $property = PropertyHelper::insert($this->listing, $location);

        // Create a contact
        $contact = ContactHelper::insert($this->listing->data, $property);

        // Insert the new Units
        $units = UnitHelper::insert($property, $this->listing->data['units']);

        // return the property
        return $property;

    }


    /**
     * Returned property
     *
     * @return mixed
     */
    private function _update()
    {
        // Update the property
        $property = PropertyHelper::update($this->listing);

        // Update the contact data
        $contact = ContactHelper::update($this->listing->data, $property);

        // Update the units
        $units = UnitHelper::update($this->listing, $property);

        // Return the property
        return $property;
    }

}
