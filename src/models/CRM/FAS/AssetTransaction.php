<?php

/**
 * @package Freedom
 */


/**
 * @author Aron Vargas
 * @package Freedom
 */
abstract class AssetTransaction {
    /**
     * @var integer
     */
    protected $timestamp;


    /**
     * @return integer
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}


?>