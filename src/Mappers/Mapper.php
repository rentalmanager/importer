<?php
namespace RentalManager\Importer\Mappers;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/8/18
 * Time: 12:10 PM
 */

class Mapper {

    /**
     * What is all about
     *
     * @var
     */
    protected $what;

    /**
     * Mapper class instantiated
     * @var
     */
    protected $mapper;


    /**
     * Map constructor.
     * @param $what
     * @param $arguments
     */
    public function __construct($what, $arguments = [])
    {
        $this->what = $what;
        $this->getMapper($arguments);
    }

    /**
     * @param $data
     * @return null
     */
    public function get($data)
    {
        if ( !$data || empty( $data ) )
        {
            return null;
        }
        return $this->mapper->get($data);
    }

    /**
     * Instantiate the mapper
     */
    protected function getMapper($arguments)
    {
        $className = "\RentalManager\Importer\Mappers\\" . studly_case($this->what) . 'Mapper';
        // run the task
        $mapper = new $className();
        if ( !empty( $arguments ) )
        {
            $mapper->setArguments($arguments);
        }
        $mapper->init();
        $this->mapper = $mapper;
    }


}
