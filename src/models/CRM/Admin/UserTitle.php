<?php

/**
 * @package Freedom
 */

/**
 *
 */
require_once ('DataStor.php');

/**
 *
 * @author Aron Vargas
 * @package Freedom
 */
class UserTitle {
    /**
     * @var integer
     */
    private $id = null;

    /**
     * @var string
     */
    private $name = '';


    /**
     * Creates a new UserTitle object.
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
                'SELECT name FROM user_titles WHERE id = ?');
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