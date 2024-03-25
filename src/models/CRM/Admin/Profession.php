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
class Profession {
    /**
     * @var integer
     */
    private $id = null;

    /**
     * @var string
     */
    private $name = '';

    private $display_order;		# Integer


    /**
     * Creates a new Profession object.
     *
     * @param integer $id
     * @param string $name
     * @param integer $display_order
     */
    public function __construct($id, $name = '', $display_order = 1)
    {
        $this->id = $id;

        if ($name)
        {
            $this->name = $name;
            $this->display_order = $display_order;
        }
        else
        {
            $dbh = DataStor::getHandle();
            $sth = $dbh->prepare("SELECT name, display_order FROM professions WHERE id = ?");
            $sth->bindValue(1, (int) $id, PDO::PARAM_INT);
            $sth->execute();
            list($this->name, $this->display_order) = $sth->fetch(PDO::FETCH_NUM);
        }
    }

    /**
     * Set values in db statement from this
     *
     * @param object
     * @return integer
     */
    public function BindValues(&$sth)
    {
        $i = 1;
        $sth->bindValue($i++, $this->name, PDO::PARAM_STR);
        $sth->bindValue($i++, (int) $this->display_order, PDO::PARAM_INT);

        if ($this->id)
            $sth->bindValue($i++, (int) $this->id, PDO::PARAM_INT);

        return $i;
    }

    /**
     * Set class property matching the array key
     *
     * @param array $new
     */
    public function CopyFromArray($new = array())
    {
        if (is_array($new))
        {
            foreach ($new as $key => $value)
            {
                if (@property_exists($this, $key))
                {
                    # Cant trim an array
                    if (is_array($value))
                        $this->{$key} = $value;
                    else
                        $this->{$key} = trim($value);
                }
            }
        }
    }

    /**
     * Perform INSERT query
     */
    public function DBInsert()
    {
        global $sh;

        $dbh = DataStor::getHandle();

        if (empty ($this->id))
        {
            $sth = $dbh->query("SELECT MAX(id) FROM professions");
            $max = $sth->fetchColumn();
            $this->id = (int) $max + 1;
        }

        $sth = $dbh->prepare("INSERT
		INTO professions (name, display_order, id)
		VALUES (?,?,?)");
        $i = $this->BindValues($sth);
        $sth->execute();
    }

    /**
     * Perform DELETE query, well not really
     *
     * @param boolean
     */
    public function DBDelete($rm = false)
    {
        $dbh = DataStor::getHandle();

        if ($rm)
        {
            $sth = $dbh->prepare("DELETE FROM professions
			WHERE id = ?");
            $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();
        }
    }

    /**
     * Perform UPDATE query
     */
    public function DBupdate()
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("UPDATE professions SET
			name = ?,
			display_order = ?
		WHERE id = ?");
        $this->BindValues($sth);
        $sth->execute();
    }

    /**
     * Returns the ID.
     *
     * @return integer
     */
    public function getDisplayOrder()
    {
        return (int) $this->display_order;
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

    /**
     * Find the id from name
     *
     * @param string
     * @return integer
     */
    static public function LookupId($name)
    {
        $id = null;

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('SELECT id FROM professions WHERE upper(name) = ?');
        $sth->bindValue(1, strtoupper($name), PDO::PARAM_STR);
        $sth->execute();
        $id = $sth->fetchColumn(0);

        return $id;
    }

    /**
     * Insert/Update/Delete record
     *
     * @param integer
     */
    public function Save($rm = 0)
    {
        if (empty ($this->id))
        {
            $this->DBInsert();
        }
        else
        {
            if ($rm)
                $this->DBDelete($rm);
            else
                $this->DBupdate();
        }
    }
}

?>