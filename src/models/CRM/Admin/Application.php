<?php

/**
 * @package Freedom
 *
 * @author Aron Vargas
 * @package Freedom
 */
class Application {
    /**
     * @var integer
     */
    private $id = null;

    /**
     * @var string
     */
    private $short_name = '';

    /**
     * @var string
     */
    private $full_name = '';

    /**
     * @var boolean
     */
    private $assignable = true;

    /**
     * @var boolean
     */
    private $hidden = false;

    /**
     * @var integer
     */
    private $display_order = null;

    /**
     * @var integer All Application
     */
    public static $ALL_APPS = 1;

    /**
     * @var integer Visable Applications
     */
    public static $VISABLE_APPS = 2;

    /**
     * @var integer Hidden Applications
     */
    public static $HIDDEN_APPS = 3;

    /**
     * Creates an Application object.
     *
     * @param integer $id
     */
    public function __construct($id)
    {
        $this->id = $id;

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('
			SELECT short_name, full_name, accessible, hidden, display_order
			FROM applications WHERE id = ?');
        $sth->bindValue(1, $id, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetch(PDO::FETCH_ASSOC);

        $this->short_name = $row['short_name'];
        $this->full_name = $row['full_name'];
        $this->assignable = $row['accessible'];
        $this->hidden = $row['hidden'];
        $this->display_order = $row['display_order'];
    }


    /**
     * Returns the id of this application.
     *
     * @return integer
     */
    public function getId()
    {
        return (int) $this->id;
    }


    /**
     * Returns the short name (for code) of this application.
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->short_name;
    }


    /**
     * Returns the full name (for people) of this application.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->full_name;
    }


    /**
     * Returns the display order of this application.
     *
     * @return integer
     */
    public function getDisplayOrder()
    {
        return (int) $this->display_order;
    }


    /**
     * Returns whether this Application is assignable, meaning it can be
     * assigned permissions.
     *
     * @return boolean
     */
    public function isAssignable()
    {
        return (boolean) $this->assignable;
    }

    /**
     *
     * @param integer $id
     * @return array
     */
    static public function ListTemplates($id)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare('SELECT
			t.id, t.name
		FROM access_template t
		INNER JOIN access_template_application ata on t.id = ata.access_template_id
		WHERE ata.application_id = ?
		ORDER BY t.name');
        $sth->bindValue(1, (int) $id, PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     *
     * @param integer $id
     * @return array
     */
    static public function ListUsers($id)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare('SELECT DISTINCT
			u.id, u.firstname, u.lastname, u.email, u.hanger_id
		FROM users u
		INNER JOIN access_template_user atu ON u.id = atu.user_id
		INNER JOIN access_template_application ata on atu.access_template_id = ata.access_template_id
		WHERE ata.application_id = ?
		ORDER BY u.firstname, u.lastname');
        $sth->bindValue(1, (int) $id, PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Returns whether this Application is hidden, meaning it doesn't show
     * up on the navbar.
     *
     * @return boolean
     */
    public function isHidden()
    {
        return (boolean) $this->hidden;
    }


    /**
     * Returns the ID of an application given its short name.
     *
     * @param string $app_name
     * @return integer|null
     */
    public static function getAppId($app_name)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare(
            'SELECT id FROM applications WHERE short_name = ?');
        $sth->bindValue(1, $app_name, PDO::PARAM_STR);
        $sth->execute();
        if ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            return (int) $row['id'];
        }

        return null;
    }


    /**
     * Returns the short name of an application given its id.
     *
     * @param integer $app_id
     * @return string|null
     */
    public static function getAppName($app_id)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare(
            'SELECT short_name FROM applications WHERE id = ?');
        $sth->bindValue(1, $app_id, PDO::PARAM_INT);
        $sth->execute();
        if ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            return $row['short_name'];
        }

        return null;
    }

}

?>