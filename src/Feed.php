<?php
namespace RentalManager\Importer;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use RentalManager\Importer\Models\ImporterDownload;
use RentalManager\Importer\Models\ImporterListing;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 8:12 AM
 */

class Feed
{

    /**
     * Provider
     *
     * @var object
     */
    public $provider;


    /**
     * Data from a provider model
     *
     * @var
     */
    public $providerModel;


    /**
     * Feed constructor.
     *
     * @param $providerSlug - it can be a slug, id or a name
     */
    public function __construct($providerSlug)
    {
        // we have the provider by name, so we need to get it
        $this->_setProvider($providerSlug);
    }


    /**
     * Do feed item
     *
     * @param $standardized
     * @return array
     */
    public function doFeedListing($standardized)
    {
        // Get the validated listings
        $validation = new Validation($standardized);

        // chain methods and get the data
        $data = $validation->validate()->getData();

        // Get the errors and error level
        $errors = $validation->errorResponses();

        // Get the hashed object
        $hashedObject = $this->hashListing($data);

        $feedListing = ImporterListing::firstOrNew(['foreign_id' => $data['foreign_id'], 'provider_id' => $this->providerModel->id]);

        if ( $feedListing->exists )
        {
            // ok now we know that the listing exists...
            // if this listing is blocked for whatever reason - skip the update
            if ( $feedListing->status === 'blocked' ) {
                return;
            }
            // Get the new status
            $status = $this->_getNewListingStatus($hashedObject, $errors->error_level, $feedListing->hash, $feedListing->property_id);
            $updated_fields = ( $status === 'unmodified' ) ? null : $this->getUpdatedFields($feedListing->data, $data, ['available_at']);
        } else {
            // Nope we do not have this listing - it's either new or rejected
            $status = $this->_getNewListingStatus($hashedObject, $errors->error_level);
            $updated_fields = null;
        }

        // populate the database
        $feedListing->status = $status;
        $feedListing->hash = $hashedObject;
        $feedListing->errors = $errors->errors;
        $feedListing->error_level = $errors->error_level;
        $feedListing->updated_fields = $updated_fields;
        $feedListing->data = $data;
        $feedListing->provider()->associate($this->providerModel->id);
        $feedListing->save();

        switch ( $errors->error_level )
        {
            case 'none':

                $out  = [
                    'message' => 'OK',
                    'type' => 'info',
                    'fields' => [
                        'status' => $status,
                        'error_level' => 'none',
                        'errors' => $errors->errors,
                        'id' => $feedListing->id,
                        'foreign_id' => $feedListing->foreign_id,
                        'property_id' => $feedListing->property_id,
                    ]
                ];

                break;


            case 'severe':

                $out =  [
                    'message' => 'ERROR',
                    'type' => 'error',
                    'fields' => [
                        'status' => $status,
                        'error_level' => $errors->error_level,
                        'errors' => $errors->errors,
                        'id' => $feedListing->id,
                        'foreign_id' => $feedListing->foreign_id,
                        'property_id' => $feedListing->property_id,
                    ]
                ];

                break;

            case 'warning':
                $out =  [
                    'message' => 'ERROR',
                    'type' => 'warning',
                    'fields' => [
                        'status' => $status,
                        'error_level' => $errors->error_level,
                        'errors' => $errors->errors,
                        'id' => $feedListing->id,
                        'foreign_id' => $feedListing->foreign_id,
                        'property_id' => $feedListing->property_id,
                    ]
                ];
                break;

            default:

                $out =  [
                    'message' => 'EMERGENCY',
                    'type' => 'emergency',
                    'fields' => [
                        'status' => $status,
                        'error_level' => 'none',
                        'errors' => $errors->errors,
                        'id' => $feedListing->id,
                        'foreign_id' => $feedListing->foreign_id,
                        'property_id' => $feedListing->property_id,
                    ]
                ];

                break;

        }

        return $out;
    }

    /**
     * Set the provider and get the data from a model
     *
     * @param $provider
     * @return void
     */
    private function _setProvider($provider)
    {
        // fetch the data from the database for the provider
        $model = 'App\RentalManager\Main\Provider';
        $providerData = $model::where('name', $provider)->orWhere('id', $provider)->orWhere('slug', $provider)->first();

        if ( $providerData )
        {
            // set the provider model
            $this->providerModel = $providerData;

            // instantiate the class
            $className = "\App\Importers\\" . $providerData->name;

            $this->provider = new $className;
            $this->provider->setModel($this->providerModel);

        } else {
            throw new InvalidArgumentException;
        }
    }

    /**
     * Get the latest downloaded files for provider
     * Check if the last feed file matches the current in size - to speed up the parser import
     * Option to force to skip this check
     *
     * @param bool $force
     * @return mixed
     */
    public function getLatestFeeds($force = false)
    {
        $latest = ImporterDownload::where('provider_id', $this->providerModel->id)->orderBy('id', 'desc')->first();

        $beforeLatest = ImporterDownload::where('provider_id', $this->providerModel->id)->orderBy('id', 'desc')->skip(1)->take(1)->get();

        if ( !$latest )
        {
            return false;
        }

        // we need to check if the files are ready for parsing
        $files = $latest->data;

        foreach ( $files as $key => $file )
        {
            // always add the ID
            $files[$key]['id'] = $latest->id;
            // Check the file size to continue or not
            if ( !$force && $beforeLatest->count() > 0 )
            {
                // check the stuff
                $oldFiles = $beforeLatest[0]->data;

                if ( $oldFiles[$key]['size'] === $file['size'] )
                {
                    // file size is the same
                    $files[$key]['do_parse'] = false;
                } else {
                    $files[$key]['do_parse'] = true;
                }

            } else {
                $files[$key]['do_parse'] = true;
            }
        }

       return $files;
    }

    /**
     * Hash object as a string
     *
     * @param $listing
     * @return string
     */
    public function hashListing($listing)
    {
        // we will need to remove the dates
        if (!empty($listing['units']))
        {
            foreach ( $listing['units'] as $k => $v )
            {
                unset( $listing['units'][$k]['available_at']);
            }
        }
        return md5( json_encode($listing) );
    }

    /**
     * Get the new listing status
     *
     * @param $hash
     * @param $error_level
     * @param null $oldHash
     * @param null $property_id
     * @return string
     */
    private function _getNewListingStatus($hash, $error_level, $oldHash = null, $property_id = null)
    {
        // The first logical switch is to switch if we have severe error or not right?
        switch ( $error_level )
        {
            case 'severe':

                // ok we have the severe error - we do not need to look no further - it's rejected in any case
                return 'rejected';

                break;

            default:

                // The second logical exception is if we have the property id or not right?
                if ( $property_id ) {
                    // Now we need to check the hashes right? Updated or unmodified?
                    if ( $hash === $oldHash )
                    {
                        return 'unmodified';
                    } else {
                        return 'updated';
                    }

                } else {
                    // It should stay new
                    return 'new';
                }

                break;
        }
    }



    /**
     * @param $oldValue
     * @param $newValue
     * @param $ignored
     * @return array|null
     */
    public function getUpdatedFields($oldValue, $newValue, $ignored = [])
    {
        $differences = [];
        foreach ( $newValue as $key => $value )
        {
            if ( $key !== 'units' && !in_array($key, $ignored) )
            {

                if ( !is_array( $value ) )
                {
                    // if its not array
                    if ( !array_key_exists($key,$oldValue) || $oldValue[$key] !== $value )
                    {
                        $differences[$key] = $value;
                    }
                } else {
                    if ( !array_key_exists($key,$oldValue) || md5(json_encode($value)) !== md5( json_encode($oldValue[$key])) )
                    {
                        $differences[$key] = true;
                    }
                }
            }
        }

        // check units
        $units = $this->getUpdatedUnitFields($oldValue['units'], $newValue['units'], $ignored);
        if ( !empty( $units ) )
        {
            $differences['units'] = $units;
        }

        return ( !empty($differences) ) ? $differences : null;
    }

    /**
     * Unit differences
     *
     * @param $oldValue
     * @param $newValue
     * @param $ignored
     * @return array|null
     */
    public function getUpdatedUnitFields($oldValue, $newValue, $ignored = [])
    {
        $differences = [];

        if ( empty( $newValue ) )
        {
            return ['removed' => $oldValue];
        }
        foreach ( $newValue as $key => $value )
        {
            // we need to check the key, because if the key doesn exist... it's new (or deleted in the past)
            if ( !array_key_exists( $key, $oldValue ) )
            {
                // completely new unit
                $differences['new'][$key] = $value;
            } else {
                // the key exists, now match the values
                foreach( $value as $k => $v )
                {
                    if ( !in_array($k, $ignored ) )

                        if ( !is_array( $v ) )
                        {
                            if ( !array_key_exists($k,$oldValue[$key]) || $oldValue[$key][$k] !== $v )
                            {
                                // definitely an update
                                $differences['updated'][$key][$k] = $v;
                            }
                        } else {
                            // its an array check the md5 hash
                            if ( !array_key_exists($k,$oldValue[$key]) || md5(json_encode($v)) !== md5( json_encode($oldValue[$key][$k])) )
                            {
                                $differences['updated'][$key][$k] = true;
                            }
                        }
                }
            }
        }

        // now we need to know the removed items
        if ( !empty( $oldValue ) )
        {
            foreach ( $oldValue as $key => $value )
            {
                if ( !array_key_exists($key, $newValue ) )
                {
                    $differences['removed'][$key] = $oldValue[$key];
                }
            }
        }
        return ( !empty($differences) ) ? $differences : null;
    }
}
