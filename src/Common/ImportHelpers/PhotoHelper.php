<?php
namespace RentalManager\Importer\Common\ImportHelpers;

use App\RentalManager\AddOns\Photo;
use RentalManager\Photos\Facades\Photos;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/10/18
 * Time: 5:37 PM
 */

class PhotoHelper
{

    /**
     * Detach photos from the object
     * @param $object
     */
    public static function detach($object)
    {
        $object->detachPhotos();
    }

    /**
     * Insert the photos
     *
     * @param array $photos
     * @param $object
     */
    public static function insertAndAttach($object, $photos = [])
    {
        if ( $photos && !empty( $photos ) ) {
            $i = 0;
            foreach ( $photos as $photo )
            {
                $pathInfo = Photos::parseExternalPhoto($photo);

                $photo = Photo::create([
                    'disk' => 's3',
                    'is_external' => true,
                    'external_url' => $pathInfo->external_url,
                    'path' =>  $pathInfo->path,
                    'has_thumbnails' => false,
                    'file_type' => $pathInfo->file_type,
                    'file_name' => $pathInfo->file_name,
                    'file_extension' => $pathInfo->file_extension
                ]);
                $object->attachPhoto($photo->id, $i);
                $i++;
            }
        }
    }
}
