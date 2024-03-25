<?php

/**
 * @package Freedom
 */

/**
 * This is an abstract class for an asset.
 *
 * @author Aron Vargas
 * @package Freedom
 */
abstract class Asset {
    /**
     * @var string the manufacturer
     */
    protected $manufacturer = '';

    /**
     * @var mixed the model.  Subclasses may implement model as a string,
     * object, or whatever else.
     */
    protected $model = null;

    /**
     * @var string the serial number
     */
    protected $serial = '';


    /**
     * @return string
     */
    public function getManufacturer()
    {
        return $this->manufacturer;
    }

    /**
     * Subclasses may implement model as a string, object, or whatever else.
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return string
     */
    public function getSerial()
    {
        return $this->serial;
    }


    /**
     * @param string the manufacturer
     */
    public function setManufacturer($manufacturer)
    {
        $this->manufacturer = $manufacturer;
    }

    /**
     * @param mixed the model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * @param string the serial number
     */
    public function setSerial($serial)
    {
        $this->serial = $serial;
    }
}

?>