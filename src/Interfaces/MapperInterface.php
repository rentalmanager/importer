<?php
namespace RentalManager\Importer\Interfaces;

/**
 * Created by PhpStorm.
 * Date: 7/9/18
 * Time: 1:06 PM
 * MapperInterface.php
 * @author Goran Krgovic <goran@dashlocal.com>
 */

interface MapperInterface
{
    /**
     * Return the array of mapped ID's
     *
     * @return array
     */
    public function get($data);

    /**
     * Init
     * @return mixed
     */
    public function init();


    /**
     * @param $arguments
     */
    public function setArguments($arguments);
}
