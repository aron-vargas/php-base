<?php
/**
 * Represents a user.
 *
 * Groups and roles are loaded by load().  Applications and Reports are loaded
 * on demand, by getApplications() and getReports().
 *
 * @author Aron Vargas
 * @package Freedom
 */
class User {
    private $dbh = null;

    protected $user_id = null;
    protected $groups = null;
    protected $roles = null;
    protected $username = '';
    protected $firstname = '';
    protected $lastname = '';
    protected $hanger_id = '';
    protected $title = '';
    protected $credentials = '';
    protected $email = '';
    protected $phone = '';
    protected $address = '';
    protected $address2 = '';
    protected $city = '';
    protected $state = '';
    protected $zip = '';
    protected $accounting_cpt = '';
    protected $active = false;
    protected $fte = null;
    protected $hire_date = '';
    protected $term_date = '';
    protected $type = 1;
    protected $pto_hrs = null;
    protected $last_pto_update = null;
    protected $extension = null;
    protected $region = '';
    protected $supervisor_id = null;
    protected $create_date;
    protected $ce_provider_id;

    protected $apps = null;
    protected $reports = null;
    protected $order_types = null;
    protected $access_templates = null;

    protected $browser = null;

    /**
     * Whether this object represents an existing record in the users table
     * @var boolean
     */
    protected $exists = false;

    # User types
    static public $SYSTEM_USER_TYPE = 1;
    static public $SERVICE_ACCOUNT_TYPE = 3;

    # Perm groups
    static public $RDO_AND_ABOVE = 2;
    static public $LEASING = 4;
    static public $CUSTOMER_SUPPORT = 8;
    static public $IT = 9;
    static public $ACCOUNT_EXECUTIVE = 10;
    static public $RECEIVER = 11;
    static public $ASSET_TRACKING = 12;
    static public $ACCOUNTING_ASSET = 13;
    static public $LIMITED_ACCOUNTING = 14;
    static public $WORKORDER_MANAGER = 15;
    static public $QA_MANAGER = 16;
    static public $OPERATIONS_MANAGER = 17;
    static public $SERVICE_CENTER_MANAGER = 18;
    static public $FIELD_SERVICE_TECH = 20;
    static public $HANGER_CE_PERM_GROUP = 29;
    static public $PO_ADMIN_PERM_GROUP = 30;
    static public $WO_EDIT = 32;

    # CEU training Catalog
    static public $DEFAULT_CATALOG = 1;

    static public $CE_PROVIDER_ID = 1;
    static public $HANGER_CE_PROVIDER_ID = 4;

    public $web_user = false;

    /**
     * Create a new User object.
     *
     * @param integer $user_id
     */
    public function __construct($user_id)
    {
        $this->dbh = DataStor::getHandle();
        $this->user_id = $user_id;
        $this->load();
    }


    /**
     * Return the user's name when used in a string context
     */
    public function __toString()
    {
        return $this->getName();
    }


    /**
     * Returns whether this object represents an existing record in the
     * users table
     *
     * @return boolean
     */
    public function exists()
    {
        return $this->exists;
    }


    /**
     * @return string
     */
    public function getAccountingCPT()
    {
        return $this->accounting_cpt;
    }


    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }


    /**
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }


    /**
     * @return array an array of {@link Application} objects.
     */
    public function getApplications($set = 0)
    {
        # unable to assign default value for $set in argument list
        if ($set == 0)
        {
            $set = APPLICATION::$ALL_APPS;
        }

        if (is_null($this->apps))
        {
            # Get an array of applications that this user has access to.
            #
            $this->apps = array();
            $sth = $this->dbh->prepare('
				SELECT DISTINCT ata.application_id AS id
                FROM access_template_application ata
                INNER JOIN access_template_user atu ON ata.access_template_id = atu.access_template_id
				WHERE atu.user_id = ?
				ORDER BY id');
            $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
            $sth->execute();

            while ($row = $sth->fetch(PDO::FETCH_NUM))
            {
                $this->apps[] = new Application($row[0]);
            }
        }

        $app_ary = array();

        foreach ($this->apps as $app)
        {
            if ($set == APPLICATION::$VISABLE_APPS)
            {
                if (!$app->isHidden())
                    $app_ary[] = $app;
            }
            else if ($set == APPLICATION::$HIDDEN_APPS)
            {
                if ($app->isHidden())
                    $app_ary[] = $app;
            }
            else
            {
                $app_ary[] = $app;
            }
        }
        return $app_ary;
    }


    /**
     * @return integer|null
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * #return integer
     */
    public function GetCEProviderId()
    {
        return $this->ce_provider_id;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return integer
     */
    public function GetCourseCatalog()
    {
        $catalog_id = self::$DEFAULT_CATALOG;

        if ($this->ce_provider_id)
        {
            $sth = $this->dbh->prepare("SELECT id
			FROM course_catalog
			WHERE active AND ce_provider_id = ?");
            $sth->bindValue(1, $this->ce_provider_id, PDO::PARAM_INT);
            $sth->execute();
            if ($sth->rowCount() > 0)
                $catalog_id = $sth->fetchColumn();
        }

        return $catalog_id;
    }


    /**
     * @return string
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @return mixed
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }


    /**
     * @return float
     */
    public function getFTE()
    {
        return (float) $this->fte;
    }


    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }


    /**
     * @return array an array of {@link UserGroup} objects.
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return string
     */
    public function getHireDate()
    {
        return $this->hire_date;
    }


    /**
     * @return integer
     */
    public function getId()
    {
        return (int) $this->user_id;
    }


    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return ($this->firstname . ' ' . $this->lastname);
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getExt()
    {
        return $this->extension;
    }


    /**
     * @return array an array of Report objects.
     */
    public function getReports()
    {
        if (is_null($this->reports))
        {
            $this->reports = array();

            $sth = $this->dbh->prepare('
				SELECT DISTINCT atr.report_id AS id
				FROM access_template_report atr
                INNER JOIN access_template_user atu ON atr.access_template_id = atu.access_template_id
				WHERE atu.user_id = ?
				ORDER BY id');
            $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
            $sth->execute();

            while ($report_id = $sth->fetchColumn())
            {
                $rpt = ReportSet::createReport($this, $report_id);

                if (!is_null($rpt))
                    $this->reports[] = $rpt;
            }
        }

        return $this->reports;
    }

    /**
     * @return array an array of order types
     */
    public function getOrderTypes()
    {
        if (is_null($this->order_types))
        {
            $this->order_types = array();

            $sql = <<<SQL
SELECT DISTINCT ato.order_type_id AS type_id
FROM access_template_order_type ato
INNER JOIN access_template_user atu ON ato.access_template_id = atu.access_template_id
WHERE atu.user_id = ?
SQL;
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
            $sth->execute();
            while ($type_id = $sth->fetchColumn())
            {
                $this->order_types[] = $type_id;
            }
        }

        return $this->order_types;
    }


    /**
     * @return array an array of {@link Role} objects.
     */
    public function getRoles()
    {
        return $this->roles;
    }


    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }


    /**
     * Returns this user's supervisor (or null if he/she doesn't have one)
     *
     * @return User|null
     */
    public function getSupervisor()
    {
        if (!is_null($this->supervisor_id))
            return new User($this->supervisor_id);

        return null;
    }


    /**
     * @return string
     */
    public function getTerminationDate()
    {
        return $this->term_date;
    }


    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }


    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @return float
     */
    public function getPtoHrs()
    {
        return (float) $this->pto_hrs;
    }

    /**
     * @return string
     */
    public function getLastPtoUpdate()
    {
        return $this->last_pto_update;
    }

    /**
     * Returns whether this user in the access group
     *
     * @param mixed
     * @return boolean whether this user is a member
     */
    public function hasAccessTemplates($template)
    {
        $found = false;
        $templates_ary = $this->getAccessTemplates();
        if (is_array($templates_ary))
        {
            foreach ($templates_ary as $at)
            {
                if ($at['id'] == $template || $at['name'] == $template)
                {
                    $found = true;
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Placeholder
     *
     * @param boolean
     */
    public function GetPaymentTerms($full = true)
    {
        return "N/A";
    }

    /**
     * @param string $app_name
     * @return boolean
     */
    public function hasAccessToApplication($app_name)
    {
        global $user;

        if ($app_name == 'admin' && $user->inPermGroup(User::$IT))
            return true;

        if (is_null($this->apps))
        {
            $sql = "SELECT
				COUNT(*) AS cnt
			FROM access_template_application ata
			INNER JOIN access_template_user atu ON ata.access_template_id = atu.access_template_id
			INNER JOIN applications a ON ata.application_id = a.id
			WHERE atu.user_id = ?
			AND a.short_name = ?";

            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
            $sth->bindValue(2, $app_name, PDO::PARAM_STR);
            $sth->execute();
            return (bool) $sth->fetchColumn() > 0;
        }
        else
        {
            foreach ($this->apps as $app)
            {
                if ($app->getShortName() == $app_name)
                    return true;
            }
        }

        return false;
    }


    /**
     * Finds whether this user has access to the given report (id).
     *
     * @param integer $report_id
     * @return boolean
     */
    public function hasAccessToReport($report_id)
    {
        if (is_null($this->reports))
        {
            $sth = $this->dbh->prepare('
                SELECT COUNT(*)
                FROM access_template_report atr
                INNER JOIN access_template_user atu ON atr.access_template_id = atu.access_template_id
				WHERE atu.user_id = ? AND atr.report_id = ?');
            $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
            $sth->bindValue(2, $report_id, PDO::PARAM_INT);
            $sth->execute();
            return (bool) $sth->fetchColumn() > 0;
        }
        else
        {
            foreach ($this->reports as $report)
            {
                if ($report->getId() == $report_id)
                    return true;
            }
        }

        return false;
    }

    /**
     * Finds whether this user has access to the given order type (id).
     *
     * @param integer $order_type
     * @return boolean
     */
    public function hasAccessToOrderType($order_type)
    {
        if (is_null($this->order_types))
        {
            $sql = <<<SQL
SELECT COUNT(*)
FROM access_template_order_type ato
INNER JOIN access_template_user atu ON ato.access_template_id = atu.access_template_id
WHERE
    atu.user_id = ?
    AND ato.order_type_id = ?
SQL;
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
            $sth->bindValue(2, $order_type, PDO::PARAM_INT);
            $sth->execute();
            $cnt = $sth->fetchColumn();
            return ($cnt > 0);
        }
        else
        {
            foreach ($this->order_types as $type_id)
            {
                if ($type_id == $order_type)
                    return true;
            }
        }

        return false;
    }

    /**
     * Finds whether this user has access to another user or group.
     *
     * @param User|UserGroup $other_user
     * @param string $access_right either 'read' or 'write'
     * @return boolean
     */
    public function hasAccessToUser($other_user, $access_right = 'read')
    {
        $sth = $this->dbh->prepare('
			SELECT COUNT(*) FROM acl
			WHERE grantor = ? AND grantee = ? AND access = ?');
        $sth->bindValue(1, $other_user->getId(), PDO::PARAM_INT);
        $sth->bindValue(2, $this->user_id, PDO::PARAM_INT);
        $sth->bindValue(3, $access_right, PDO::PARAM_STR);
        $sth->execute();
        $cnt = $sth->fetchColumn();
        return ($cnt > 0);
    }


    /**
     * Finds whether this user is active.
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }


    /**
     * Finds whether this user is an admin.
     *
     * @return boolean
     */
    public function isAdmin()
    {
        foreach ($this->roles as $role)
        {
            if ($role->getId() == 1)
                return true;
        }

        return false;
    }


    /**
     * Returns whether this user is primarily a CPM.
     *
     * @return boolean whether this user is primarily a CPM.
     */
    public function isCPM()
    {
        $sth = $this->dbh->prepare("
			SELECT COUNT(*) FROM v_users_primary_group
			WHERE user_id = ? AND role_id = 800");
        $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
        $sth->execute();
        if ($sth->rowCount() > 0)
        {
            return ($sth->fetchColumn() > 0);
        }

        return false;
    }


    /**
     * Returns whether this user is CPM, RDO, DVP, DMS, Asst RDO, RMTC
     *
     * @return boolean whether this user is primarily a CPM.
     */
    public function inField()
    {
        $sth = $this->dbh->prepare("
			SELECT COUNT(*) FROM v_users_primary_group
			WHERE user_id = ? AND role_id IN (800,700,650,600,500,400)");
        $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
        $sth->execute();
        if ($sth->rowCount() > 0)
        {
            return ($sth->fetchColumn() > 0);
        }

        return false;
    }

    /**
     * Find all user ids in the perm group
     *
     * @param int
     * @return array
     */
    static public function PermGroupMembers($group_id)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->Prepare("SELECT DISTINCT u.id
		FROM users u
		INNER JOIN users_groups_roles upr ON u.id = upr.who_id
		INNER JOIN perm_group_member pgm ON
			CASE
				WHEN pgm.group_type = 3 THEN upr.role_id = pgm.member_id
				WHEN pgm.group_type = 2 THEN upr.where_id = pgm.member_id
				ELSE upr.who_id = pgm.member_id
			END
		WHERE pgm.perm_group_id = ?");
        $sth->bindValue(1, $group_id, PDO::PARAM_INT);
        $sth->execute();
        $user_list = $sth->fetchAll(PDO::FETCH_COLUMN);

        return $user_list;
    }

    /**
     * Returns the primary group of a user.
     *
     * @return integer of the users primary group.
     */
    public function primaryGroup()
    {
        $sth = $this->dbh->prepare("
			SELECT group_id FROM v_users_primary_group
			WHERE user_id = ?");
        $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchColumn();
    }


    /**
     * Returns the primary role of a user.
     *
     * @return integer of the users primary role.
     */
    public function primaryRole()
    {
        $sth = $this->dbh->prepare("
			SELECT role_id FROM v_users_primary_group
			WHERE user_id = ?");
        $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchColumn();
    }

    /**
     * Returns whether this user is part of the permissions group.
     *
     * @return boolean whether this user is found.
     */
    public function inPermGroup($perm_group)
    {
        $sth = $this->dbh->prepare("SELECT COUNT(*)
		FROM users u
		INNER JOIN users_groups_roles upr ON u.id = upr.who_id
		INNER JOIN perm_group_member pgm ON
			CASE
				WHEN pgm.group_type = 3 THEN upr.role_id = pgm.member_id
				WHEN pgm.group_type = 2 THEN upr.where_id = pgm.member_id
				ELSE upr.who_id = pgm.member_id
			END
		WHERE pgm.perm_group_id = ?
		AND u.id = ?");
        $sth->bindValue(1, (int) $perm_group, PDO::PARAM_INT);
        $sth->bindValue(2, $this->user_id, PDO::PARAM_INT);
        $sth->execute();
        if ($sth->rowCount() > 0)
        {
            return ($sth->fetchColumn() > 0);
        }

        return false;
    }

    /**
     * Returns whether this user is a member of $group
     *
     * @param UserGroup $group
     * @param boolean $submember if this is true, this function will also
     * return true if this user is a member of a subgroup of $group.
     */
    public function isMemberOf($group, $submember = false)
    {
        if ($submember)
        {
            $sth = $this->dbh->prepare("
				SELECT COUNT(*)
				FROM v_all_child_groups acg
				  INNER JOIN users_groups_roles ugr ON acg.subgroup_id = ugr.where_id
				WHERE ugr.who_id = ? AND
				      acg.group_id = ?");
        }
        else
        {
            $sth = $this->dbh->prepare("
				SELECT COUNT(*) FROM users_groups_roles
				WHERE who_id = ? AND where_id = ?");
        }

        $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
        $sth->bindValue(2, $group->getId(), PDO::PARAM_INT);
        $sth->execute();
        if ($sth->rowCount() > 0)
        {
            return ($sth->fetchColumn() > 0);
        }

        return false;
    }


    /**
     *
     */
    public function load()
    {
        # Get this user's attributes.
        #
        $sth = $this->dbh->prepare('
			SELECT u.id AS id,
			       u.username AS username,
			       u.firstname AS firstname,
			       u.lastname AS lastname,
			       u.hanger_id AS hanger_id,
			       u.title AS title,
			       u.credentials AS credentials,
			       u.email AS email,
			       u.phone AS phone,
			       u.address AS address,
			       u.address2 AS address2,
			       u.city AS city,
			       u.state AS state,
			       u.zip AS zip,
			       u.accounting_cpt AS accounting_cpt,
			       u.active AS active,
			       u.fte AS fte,
			       u.hire_date AS hire_date,
			       u.term_date AS term_date,
				   u.type,
				   u.title AS title,
				   u.pto_hrs,
  				   u.last_pto_update,
				   u.extension,
			       u.supervisor_id,
				   u.create_date,
				   u.ce_provider_id,
				   g.username as region
			FROM users u
			LEFT JOIN v_users_primary_group upg ON u.id = upg.user_id
			LEFT JOIN users g ON upg.group_id = g.id
			WHERE u.id = ?');
        $sth->bindValue(1, (int) $this->user_id, PDO::PARAM_INT);
        $sth->execute();
        if ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $this->exists = true;

            $this->user_id = $row['id'];
            $this->username = $row['username'];
            $this->firstname = trim($row['firstname']);
            $this->lastname = trim($row['lastname']);
            $this->title = trim($row['title']);
            $this->credentials = trim($row['credentials']);
            $this->email = trim($row['email']);
            $this->hanger_id = trim($row['hanger_id']);
            $this->phone = trim($row['phone']);
            $this->address = trim($row['address']);
            $this->address2 = trim($row['address2']);
            $this->city = trim($row['city']);
            $this->state = trim($row['state']);
            $this->zip = trim($row['zip']);
            $this->accounting_cpt = $row['accounting_cpt'];
            $this->active = $row['active'];
            $this->fte = $row['fte'];
            $this->hire_date = $row['hire_date'];
            $this->term_date = $row['term_date'];
            $this->type = $row['type'];
            $this->title = $row['title'];
            $this->pto_hrs = $row['pto_hrs'];
            $this->last_pto_update = $row['last_pto_update'];
            $this->extension = $row['extension'];
            $this->region = $row['region'];
            $this->create_date = $row['create_date'];
            $this->ce_provider_id = $row['ce_provider_id'];

            if (!is_null($row['supervisor_id']))
                $this->supervisor_id = $row['supervisor_id'];
        }
        else
        {
            $this->exists = false;
        }

        # Get the groups and roles for this user.  Sort the results because we
        # want the higher-importance roles first.
        #
        $this->groups = array();
        $this->roles = array();
        $sth = $this->dbh->prepare('
			SELECT g.id AS group_id,
			       g.lastname AS group_name,
			       r.id AS role_id,
			       r.name AS role_name
			FROM users_groups_roles ugr
			  INNER JOIN users g ON ugr.where_id = g.id
			  INNER JOIN roles r ON ugr.role_id = r.id
			WHERE ugr.who_id = ?
			ORDER BY r.id');
        $sth->bindValue(1, (int) $this->user_id, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $this->groups[] = new UserGroup($row['group_id'], $row['group_name']);
            $this->roles[] = new Role($row['role_id'], $row['role_name']);
        }


        # Blank the apps and reports and allow them to be reloaded as needed.
        #
        $this->apps = null;
        $this->reports = null;
        $this->order_types = null;
    }


    /**
     * Sets the browser that this user is using.
     *
     * @param integer $browser
     */
    public function setBrowser($browser)
    {
        $this->browser = $browser;
    }

    public function UpdateADPassword($form)
    {
        $url = "http://" . Config::$MAIL_SERVER;
        $tmpfile = tempnam("/tmp", "owa");

        $headers = array();
        $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
        $headers[] = "Accept-Language: en-us,en;q=0.5";
        $headers[] = "Accept-Encoding: gzip,deflate";
        $headers[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $headers[] = "Keep-Alive: 300";
        $headers[] = "Connection: keep-alive";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . "/exchange/");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $tmpfile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $tmpfile);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.5)");
        curl_setopt($ch, CURLOPT_REFERER, $url . "/exchange/");
        curl_setopt($ch, CURLOPT_USERPWD, $form["acct"] . ":" . $form["old"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $html = curl_exec($ch);
        curl_close($ch);

        if (filesize($tmpfile) != 0)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url . "/iisadmpwd/achg.htr?http://{$_SERVER['HTTP_HOST']}");
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $tmpfile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $tmpfile);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.5)");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_REFERER, $url . "/exchange/");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "domain={$form['domain']}&acct={$form['acct']}&old={$form['old']}&new={$form['new']}&new2={$form['new2']}");

            $html = curl_exec($ch);

            curl_close($ch);

            $pattern = "/<p>(.*?)<br>/";
            preg_match($pattern, $html, $matches);

            if (substr_count($html, "Password successfully changed.") > 0)
            {
                echo "ok";

                $sql = <<<END
UPDATE users
SET last_password_update = CURRENT_DATE
WHERE id = {$this->user_id};
END;

                $this->dbh->exec($sql);
            }
            else if (substr_count($html, "Error number: -2147023569"))
                echo "Your password has already been changed once today.\nIf you need to change it again, please contact your System Administrator.";
            else if (substr_count($html, "Either the password is too short or password uniqueness restrictions have not been met."))
                echo "The new password has been used within your last six password changes.";
            else if (isset ($matches[1]))
                echo $matches[1];
            else
                echo "Unknown error detected in changing your password.\nPlease contact support@company.com to report this.";
        }
        else
            echo "Invalid Username and Password";

        unset($tmpfile);
    }

    /**
     * Returns an array of User objects that represent the field regions.
     *
     * @param bool $active_only whether to return on the active regions
     * @return array
     * @throws PDOException
     */
    public static function getRegions($active_only = true)
    {
        require_once ('TConfig.php');
        $conf = new TConfig();
        $all_div_id = $conf->get('all_divisions_id');

        $dbh = DataStor::getHandle();

        $active_condition = ($active_only) ? 'AND g.active = true' : '';

        $ret = array();
        $sth = $dbh->prepare("
			SELECT ugr.who_id
			FROM users_groups_roles ugr
			  INNER JOIN users g ON ugr.who_id = g.id $active_condition
			WHERE ugr.where_id IN (
			    SELECT who_id FROM users_groups_roles
			    WHERE where_id = ? AND role_id = 2000
			  )
			AND ugr.role_id = 2000");
        $sth->bindValue(1, $all_div_id, PDO::PARAM_INT);
        $sth->execute();
        while ($id = $sth->fetchColumn())
        {
            $ret[] = new User($id);
        }

        return $ret;
    }

    /**
     *
     * @return array an array of User objects
     */
    public static function getUsers()
    {
        $dbh = DataStor::getHandle();

        $ret = array();

        $sth = $dbh->query('
			SELECT id FROM users WHERE type = 1 ORDER BY lastname, firstname');
        while ($id = $sth->fetchColumn())
        {
            $ret[] = new User($id);
        }

        return $ret;
    }

    /**
     *
     * @param string $tag
     * @param string $html
     * @param integer $strict
     * @return multitype:NULL
     */
    private function GetTextBetweenTags($tag, $html, $strict = 0)
    {
        /*** a new dom object ***/
        $dom = new domDocument;

        /*** load the html into the object ***/
        if ($strict == 1)
            $dom->loadXML($html);
        else
            $dom->loadHTML($html);

        /*** discard white space ***/
        $dom->preserveWhiteSpace = false;

        /*** the tag by its tag name ***/
        $content = $dom->getElementsByTagname($tag);

        /*** the array to return ***/
        $out = array();
        foreach ($content as $item)
        {
            /*** add node value to the out array ***/
            $out[] = $item->nodeValue;
        }
        /*** return the results ***/
        return $out;
    }

    /**
     * Query and return list of the users access templates
     *
     * @param boolean
     */
    public function getAccessTemplates($refresh = false)
    {
        if (is_null($this->access_templates) || $refresh)
        {
            $sql = "SELECT
	        	at.id, at.name
			FROM access_template at
			INNER JOIN access_template_user atu ON at.id = atu.access_template_id
			WHERE atu.user_id = ?
			ORDER BY at.name";
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
            $sth->execute();
            $this->access_templates = $sth->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->access_templates;
    }

    /**
     * Temporarily set title
     * @param string $title
     */
    public function SetTitle($title)
    {
        $this->title = $title;
    }
}

?>