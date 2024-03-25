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
class Role {
    /**
     * @var integer
     */
    private $id = null;

    /**
     * @var string
     */
    private $name = '';


    /**
     * Creates a new Role object.
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
                'SELECT name FROM roles WHERE id = ?');
            $sth->bindValue(1, $id, PDO::PARAM_INT);
            $sth->execute();
            $this->name = $sth->fetchColumn();
        }
    }


    /**
     * Returns the ID.
     *
     * @return integer
     */
    public function getId()
    {
        return (int) $this->id;
    }


    /**
     * Returns the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

?>