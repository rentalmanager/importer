<?php
namespace RentalManager\Importer\FeedProviders;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 6:04 AM
 */

class FeedProvider {

    /**
     * A model from the database
     * @var
     */
    protected $model;


    /**
     * @return array
     */
    public function getFeedFiles()
    {
        return $this->files;
    }

    /**
     * @return string
     */
    public function getStoragePath()
    {
        return $this->storage_path;
    }

    /**
     * @return string
     */
    public function getContactMethod()
    {
        return $this->contact_method;
    }

    /**
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * @return object
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return boolean | string
     */
    public function getOnBefore()
    {
        return $this->on_before;
    }
}
