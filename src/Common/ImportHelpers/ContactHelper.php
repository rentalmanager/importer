<?php
namespace RentalManager\Importer\Common\ImportHelpers;

use App\RentalManager\Main\Contact;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/10/18
 * Time: 6:02 PM
 */

class ContactHelper
{


    /**
     * Update the contact
     *
     * @param $data
     * @param $property
     * @return mixed
     */
    public static function update($data, $property)
    {
        $data = (object) $data;

        // Fetch the contact
        $contact = $property->contact;

        // always update
        $contact->method = $data->contact_method;
        $contact->owner = $data->contact_owner;
        $contact->url = $data->contact_url;
        $contact->email_to = $data->contact_email_to;
        $contact->email_cc = $data->contact_email_cc;
        $contact->phone = $data->contact_phone;
        $contact->save();

        return $contact;
    }

    /**
     * @param $data
     * @param $property
     * @return Contact
     */
    public static function insert($data, $property)
    {
        $data = (object)$data;

        $contact = new Contact();
        $contact->method = $data->contact_method;
        $contact->owner = $data->contact_owner;
        $contact->url = $data->contact_url;
        $contact->email_to = $data->contact_email_to;
        $contact->email_cc = $data->contact_email_cc;
        $contact->phone = $data->contact_phone;
        $contact->associateProperty($property->id);
        $contact->save();

        return $contact;
    }
}
