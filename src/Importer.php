<?php
namespace RentalManager\Importer;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 5:59 AM
 */

/**
 * Class Importer
 * @package RentalManager\Importer
 */
class Importer {

    /**
     * Laravel application.
     *
     * @var \Illuminate\Foundation\Application
     */
    public $app;


    /**
     * Base constructor.
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }
}
