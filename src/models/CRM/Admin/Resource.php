<?php

/**
 * @package Freedom
 */

/**
 *
 */
require_once ('classes/DataStor.php');

/**
 *
 * @author Aron Vargas
 * @package Freedom
 */
class Resource {
    /**
     * @var integer
     */
    private $id = null;

    /**
     * @var string
     */
    private $name = '';


    /**
     * Creates a new Resource object.
     *
     * @param integer $id
     * @param string $name
     */
    public function __construct($id, $name = '')
    {
        $this->id = $id;

        if ($name)
        {
            $this->name = $name;
        }
        else
        {
            $dbh = DataStor::getHandle();
            $sth = $dbh->prepare(
                'SELECT name FROM resources WHERE id = ?');
            $sth->bindParam(1, $id, PDO::PARAM_INT);
            $sth->execute();
            $this->name = $sth->fetchColumn();
        }
    }


    /**
     * Returns the ID of this Resource.
     *
     * @return integer
     */
    public function getId()
    {
        return (int) $this->id;
    }


    /**
     * Returns the name of this Resource.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

?>