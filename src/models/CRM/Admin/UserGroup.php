<?php

/**
 * @package Freedom
 */

/**
 *
 * @author Aron Vargas
 * @package Freedom
 */
class UserGroup {
    /**
     * @var integer
     */
    private $id = null;

    /**
     * @var string
     */
    private $name = '';


    /**
     * Creates a new UserGroup object.
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
                'SELECT lastname FROM users WHERE id = ?');
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

    /**
     * Returns array.
     *
     * @return array
     */
    public function getActiveRegions()
    {

        // $region_info = array();
        // $region_ids = array();

        // $dbh = DataStor::getHandle();

        // Fetch table:config data for region_ids
        // $sth_config = $dbh->query(
        // 	'SELECT lastname FROM users WHERE id = ?');
        // $sth_config->execute();
        // $region_ids = $sth_config->fetchColumn();

        // Get region data (id, name)
        // $sth_region_data = $dbh->query("select u.id, u.lastname as name
        // 	from users u
        // 	inner join users_groups_roles div on u.id = div.who_id or u.lastname = 'Corp'
        // 	inner join users_groups_roles reg on reg.role_id = 2000
        // 	where u.type=2 AND u.active
        // 	group by u.id, u.lastname
        // 	order by name");
        // $sth_region_data->execute();
        // $region_info = $sth_region_data->fetchAll();

        // return $region_info;

    }

}

?>