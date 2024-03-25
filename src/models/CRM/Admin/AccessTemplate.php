<?php

/**
 * A model class to store access templates.
 *
 * @package Freedom
 * @author Aron Vargas
 */

class AccessTemplate {
    /**
     * @var object
     */
    protected $dbh;

    /**
     * @var boolean
     */
    protected $loaded = false;

    /**
     * @var integer
     */
    protected $id = null;

    /**
     * @var string
     */
    protected $name = null;


    protected $default_group;
    protected $default_role;
    protected $default_ce_provider;




    public function __construct($id = null)
    {
        $this->dbh = DataStor::getHandle();
        $this->setId($id);
        $this->load();
    }

    protected function load()
    {
        if ($this->getId())
        {
            $sth = $this->dbh->prepare('SELECT * FROM access_template WHERE id = ?');
            $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
            $sth->execute();

            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->copyFromArray($row);

                $this->setLoaded(true);
            }
        }
    }

    public function copyFromArray(array $new = array())
    {
        foreach ($new as $key => $value)
        {
            if (@property_exists($this, $key))
                $this->{$key} = $value;
        }
    }

    public function save(array $new = array())
    {
        $this->copyFromArray($new);

        if ($this->isLoaded())
        {
            $sth = $this->dbh->prepare('UPDATE access_template SET name = ?,default_group=?, default_role=?, default_ce_provider=? WHERE id = ?');
            $sth->bindValue(1, $this->getName(), PDO::PARAM_STR);
            $sth->bindValue(2, $this->getdefault_group(), PDO::PARAM_INT);
            $sth->bindValue(3, $this->getdefault_role(), PDO::PARAM_INT);
            $sth->bindValue(4, $this->getdefault_ce_provider(), PDO::PARAM_INT);
            $sth->bindValue(5, $this->getId(), PDO::PARAM_INT);
            $sth->execute();
        }
        else
        {
            $sth = $this->dbh->prepare('INSERT INTO access_template (name,default_group,default_role,default_ce_provider) VALUES (?,?,?,?)');
            $sth->bindValue(1, $this->getName(), PDO::PARAM_STR);
            $sth->bindValue(2, $this->getdefault_group(), PDO::PARAM_INT);
            $sth->bindValue(3, $this->getdefault_role(), PDO::PARAM_INT);
            $sth->bindValue(4, $this->getdefault_ce_provider(), PDO::PARAM_INT);
            $sth->execute();

            $this->setId($this->dbh->lastInsertId('access_template_id_seq'));
            $this->load();
        }
    }

    public function delete()
    {
        if ($this->isLoaded())
        {
            $sth = $this->dbh->prepare('DELETE FROM access_template WHERE id = ?');
            $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
            $sth->execute();

            $this->isLoaded(false);
        }
    }

    public function isLoaded()
    {
        return $this->loaded;
    }

    public function setLoaded($value)
    {
        $this->loaded = (bool) $value;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setId($value)
    {
        $this->id = (int) $value;
    }

    public function setName($value)
    {
        $this->name = $value;
    }




    public function getdefault_group()
    {
        return $this->default_group;
    }
    public function getdefault_role()
    {
        return $this->default_role;
    }
    public function getdefault_ce_provider()
    {
        return $this->default_ce_provider;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////where group_list_access
    public function fetchRoleList()
    {
        $role_select = '';
        $sth = $this->dbh->query("SELECT * from roles where id>0 order by id");

        while ($role_lst = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $selected = ($role_lst['id'] == $this->default_role) ? 'selected' : '';

            $role_select .= "<option value={$role_lst['id']} $selected>{$role_lst['name']}</option>";
        }
        return $role_select;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////
    public function fetchGroupList()
    {
        $group_select = '';
        $sth = $this->dbh->query("
            SELECT u.id AS id,
                   u.lastname AS name
            FROM users u
              LEFT OUTER JOIN users_groups_roles ugr ON u.id = ugr.where_id AND role_id = 2000

            WHERE u.type = 2 AND active
            GROUP BY u.id, u.lastname
            ORDER BY u.id");

        while ($group_lst = $sth->fetch(PDO::FETCH_ASSOC))
        {

            $selected = ($group_lst['id'] == $this->default_group) ? 'selected' : '';

            $group_select .= "<option value={$group_lst['id']} $selected>{$group_lst['name']}</option>";
        }
        return $group_select;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////
    public function fetchceproviderList()
    {
        $cep_select = '';
        $sth = $this->dbh->query("SELECT * from ce_provider where active order by id");

        while ($cep_lst = $sth->fetch(PDO::FETCH_ASSOC))
        {

            $selected = ($cep_lst['id'] == $this->default_ce_provider) ? 'selected' : '';

            $cep_select .= "<option value={$cep_lst['id']} $selected>{$cep_lst['provider_name']}</option>";
        }
        return $cep_select;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////
    public function save_additional_template_values($group_id, $role_id, $ce_provider, $id)
    {

        $group_pdo = ($group_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $role_pdo = ($role_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $provider_pdo = ($ce_provider) ? PDO::PARAM_INT : PDO::PARAM_NULL;

        $sth = $this->dbh->prepare('UPDATE access_template SET default_group=?, default_role=?, default_ce_provider=? WHERE id = ?');
        $sth->bindValue(1, $group_id, $group_pdo);
        $sth->bindValue(2, $role_id, $role_pdo);
        $sth->bindValue(3, $ce_provider, $provider_pdo);
        $sth->bindValue(4, $id, PDO::PARAM_INT);
        $sth->execute();
    }
    ////////////////////////////////////////////////////////////////////////////////////////////



    public static function listAccessTemplates($unused_templates = false)
    {
        if ($unused_templates)
        {
            $where_clause = "WHERE at.name != '*All'";
        }
        else
        {
            $where_clause = "WHERE at.name = '*All'";
        }

        $sql = <<<SQL
SELECT
    at.id,
    at.name,
    COUNT(DISTINCT atu.user_id) AS user_count,
    COUNT(DISTINCT ata.application_id) AS app_count,
    COUNT(DISTINCT atr.report_id) AS report_count,
    COUNT(DISTINCT ato.order_type_id) AS order_type_count
FROM access_template at
LEFT JOIN access_template_user atu ON at.id = atu.access_template_id
LEFT JOIN access_template_application ata ON at.id = ata.access_template_id
LEFT JOIN access_template_report atr ON at.id = atr.access_template_id
LEFT JOIN access_template_order_type ato ON at.id = ato.access_template_id
{$where_clause}
GROUP BY at.id, at.name
ORDER BY at.name
SQL;

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function loadADGroups(array $groups = array())
    {
        $dbh = DataStor::getHandle();

        $select = $dbh->prepare('SELECT id FROM access_template WHERE name = :name');
        $insert = $sth = $dbh->prepare('INSERT INTO access_template (name) VALUES (:name)');
        $ids = array();

        $dbh->beginTransaction();

        foreach ($groups as $group)
        {
            $select->execute(array(':name' => $group));
            if ($id = $select->fetchColumn())
            {
                $ids[] = $id;
            }
            else
            {
                $insert->execute(array(':name' => $group));
                $ids[] = $dbh->lastInsertId('access_template_id_seq');
            }
        }

        $dbh->commit();

        return $ids;
    }

    public static function getOrCreate($name)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('SELECT * FROM access_template WHERE name = ?');
        $sth->bindValue(1, $this->getName(), PDO::PARAM_STR);
        $sth->execute();

        if ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $template = new AccessTemplate();
            $template->copyFromArray($row);
            return $template;
        }
        else
        {
            $template = new AccessTemplate();
            $template->save(array('name' => $name));
            return $template;
        }
    }

    public function getUsers()
    {
        $sql = <<<SQL
SELECT u.id, u.firstname || ' ' || u.lastname AS name, u.title
FROM users u
INNER JOIN access_template_user atu ON u.id = atu.user_id
WHERE atu.access_template_id = ?
ORDER BY name
SQL;
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getApplications()
    {
        $sql = <<<SQL
SELECT a.id, a.short_name AS name
FROM applications a
INNER JOIN access_template_application ata ON a.id = ata.application_id
WHERE ata.access_template_id = ?
SQL;
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resetApplications()
    {
        $sth = $this->dbh->prepare('DELETE FROM access_template_application WHERE access_template_id = ?');
        $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
        $sth->execute();
    }

    public function setApplications(array $applications, $reset = true)
    {
        if ($reset)
        {
            $this->resetApplications();
        }

        $this->dbh->beginTransaction();

        $sth = $this->dbh->prepare('INSERT INTO access_template_application (access_template_id, application_id) VALUES (?, ?)');

        foreach ($applications as $application)
        {
            try
            {
                $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
                $sth->bindValue(2, $application, PDO::PARAM_INT);
                $sth->execute();
            }
            catch (PDOException $e)
            {
                continue;
            }
        }

        $this->dbh->commit();
    }

    public function getReports()
    {
        $sql = <<<SQL
SELECT r.id, r.name
FROM reports r
INNER JOIN access_template_report atr ON r.id = atr.report_id
WHERE atr.access_template_id = ?
SQL;
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resetReports()
    {
        $sth = $this->dbh->prepare('DELETE FROM access_template_report WHERE access_template_id = ?');
        $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
        $sth->execute();
    }

    public function setReports(array $reports, $reset = true)
    {
        if ($reset)
        {
            $this->resetReports();
        }

        $this->dbh->beginTransaction();

        $sth = $this->dbh->prepare('INSERT INTO access_template_report (access_template_id, report_id) VALUES (?, ?)');

        foreach ($reports as $report)
        {
            try
            {
                $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
                $sth->bindValue(2, $report, PDO::PARAM_INT);
                $sth->execute();
            }
            catch (PDOException $e)
            {
                continue;
            }
        }

        $this->dbh->commit();
    }

    public function getOrderTypes()
    {
        $sql = <<<SQL
SELECT o.type_id AS id, o.description AS name
FROM order_type o
INNER JOIN access_template_order_type ato ON o.type_id = ato.order_type_id
WHERE ato.access_template_id = ?
SQL;
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resetOrderTypes()
    {
        $sth = $this->dbh->prepare('DELETE FROM access_template_order_type WHERE access_template_id = ?');
        $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
        $sth->execute();
    }

    public function setOrderTypes(array $order_types, $reset = true)
    {
        if ($reset)
        {
            $this->resetOrderTypes();
        }


        $this->dbh->beginTransaction();

        $sth = $this->dbh->prepare('INSERT INTO access_template_order_type (access_template_id, order_type_id) VALUES (?, ?)');

        foreach ($order_types as $order_type)
        {
            try
            {
                $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
                $sth->bindValue(2, $order_type, PDO::PARAM_INT);
                $sth->execute();
            }
            catch (PDOException $e)
            {
                continue;
            }
        }

        $this->dbh->commit();
    }

    public static function resetUserAccessTemplates($user)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('DELETE FROM access_template_user WHERE user_id = ?');
        $sth->bindValue(1, $user, PDO::PARAM_INT);
        $sth->execute();
    }

    public static function assignUserAccessTemplates($user, array $templates = array(), $reset = true)
    {
        if ($reset)
        {
            AccessTemplate::resetUserAccessTemplates($user);
        }

        $dbh = DataStor::getHandle();

        $select = $dbh->prepare('SELECT * FROM access_template_user WHERE access_template_id = :access_template_id AND user_id = :user_id');
        $insert = $dbh->prepare('INSERT INTO access_template_user (access_template_id, user_id) VALUES (:access_template_id, :user_id)');

        $dbh->beginTransaction();

        foreach ($templates as $template)
        {
            $select->execute(array(':access_template_id' => $template, ':user_id' => $user));
            if ($select->rowCount() == 0)
            {
                $insert->execute(array(':access_template_id' => $template, ':user_id' => $user));

            } /// Ends select row count ==0
        } /// Ends foreach

        $dbh->commit();
    }

    public static function assignNEWUserAccessTemplates($user, array $templates = array(), $reset = true)
    {
        if ($reset)
        {
            AccessTemplate::resetUserAccessTemplates($user);
        }

        $dbh = DataStor::getHandle();

        $select = $dbh->prepare('SELECT * FROM access_template_user WHERE access_template_id = :access_template_id AND user_id = :user_id');
        $insert = $dbh->prepare('INSERT INTO access_template_user (access_template_id, user_id) VALUES (:access_template_id, :user_id)');

        $dbh->beginTransaction();

        foreach ($templates as $template)
        {
            $select->execute(array(':access_template_id' => $template, ':user_id' => $user));
            if ($select->rowCount() == 0)
            {
                $insert->execute(array(':access_template_id' => $template, ':user_id' => $user));


                $sth = $dbh->prepare('SELECT default_group,default_role,default_ce_provider from access_template where id=? ');
                $sth->bindValue(1, $template, PDO::PARAM_INT);
                $sth->execute();
                list($group_id, $role_id, $provider_id) = $sth->fetch(PDO::FETCH_NUM);
                if ($provider_id)
                {
                    $updu = $dbh->prepare('UPDATE users set ce_provider_id=? WHERE id=?');
                    $updu->bindValue(1, $provider_id, PDO::PARAM_INT);
                    $updu->bindValue(2, $user, PDO::PARAM_INT);
                    $updu->execute();
                } /// Ends if providerid

                if ($group_id && $role_id)
                {

                    /// First, see if we are in this table

                    $cugr = $dbh->prepare('SELECT * from users_groups_roles where who_id=? AND where_id=? AND role_id=?');
                    $cugr->bindValue(1, $user, PDO::PARAM_INT);
                    $cugr->bindValue(2, $group_id, PDO::PARAM_INT);
                    $cugr->bindValue(3, $role_id, PDO::PARAM_INT);
                    $cugr->execute();

                    /// If we are in this table - Insert
                    if ($cugr->rowCount() == 0)
                    {
                        $iugr = $dbh->prepare('INSERT INTO users_groups_roles (who_id,where_id,role_id) VALUES(?,?,?)');
                        $iugr->bindValue(1, $user, PDO::PARAM_INT);
                        $iugr->bindValue(2, $group_id, PDO::PARAM_INT);
                        $iugr->bindValue(3, $role_id, PDO::PARAM_INT);
                        $iugr->execute();
                    }
                } // Ends group and role

            } /// Ends select row count ==0
        } /// Ends foreach

        $dbh->commit();
    }


    public function applicationSelect()
    {
        $sth = $this->dbh->prepare('SELECT id, full_name AS name FROM applications ORDER BY full_name ASC');
        $sth->execute();
        $applications = $sth->fetchAll(PDO::FETCH_ASSOC);

        $sth = $this->dbh->prepare('SELECT application_id FROM access_template_application WHERE access_template_id = ?');
        $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
        $sth->execute();
        $selected_applications = $sth->fetchAll(PDO::FETCH_ASSOC);

        $selected = array();
        foreach ($selected_applications as $selected_application)
            $selected[] = $selected_application['application_id'];

        foreach ($applications as &$application)
        {
            $application['selected'] = in_array($application['id'], $selected);
        }

        return $applications;
    }

    public function reportSelect()
    {
        $sth = $this->dbh->prepare('SELECT id, name FROM reports ORDER BY name ASC');
        $sth->execute();
        $reports = $sth->fetchAll(PDO::FETCH_ASSOC);

        $sth = $this->dbh->prepare('SELECT report_id FROM access_template_report WHERE access_template_id = ?');
        $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
        $sth->execute();
        $selected_reports = $sth->fetchAll(PDO::FETCH_ASSOC);

        $selected = array();
        foreach ($selected_reports as $selected_report)
            $selected[] = $selected_report['report_id'];

        foreach ($reports as &$report)
        {
            $report['selected'] = in_array($report['id'], $selected);
        }

        return $reports;
    }

    public function orderTypeSelect()
    {
        $sth = $this->dbh->prepare('SELECT type_id AS id, description AS name FROM order_type ORDER BY description ASC');
        $sth->execute();
        $order_types = $sth->fetchAll(PDO::FETCH_ASSOC);

        $sth = $this->dbh->prepare('SELECT order_type_id FROM access_template_order_type WHERE access_template_id = ?');
        $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
        $sth->execute();
        $selected_order_types = $sth->fetchAll(PDO::FETCH_ASSOC);

        $selected = array();
        foreach ($selected_order_types as $selected_order_type)
            $selected[] = $selected_order_type['order_type_id'];

        foreach ($order_types as &$order_type)
        {
            $order_type['selected'] = in_array($order_type['id'], $selected);
        }

        return $order_types;
    }

    /**
     * Create review structure
     *
     * @return array
     */
    public static function getReview()
    {
        $dbh = DataStor::getHandle();

        $sql = "SELECT
		    u.username, u.firstname, u.lastname, u.title, at.templates
		FROM users u
        INNER JOIN (SELECT
        		atu.user_id,
        		array_to_string(array_accum(DISTINCT at.name), ',') as templates,
				COUNT(ta.access_template_id) as app_count
        	FROM access_template_user atu
			INNER JOIN access_template at ON atu.access_template_id = at.id
        	INNER JOIN access_template_application ta ON at.id = ta.access_template_id
			GROUP BY atu.user_id
		) at ON u.id = at.user_id
		WHERE u.type = 1 AND u.active AND hanger_id IS NOT NULL
		ORDER BY u.lastname, u.firstname";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        $users = $sth->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT id, full_name FROM applications ORDER BY full_name";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($app = $sth->fetch(PDO::FETCH_ASSOC))
            $applications[$app['id']] = $app['full_name'];

        $sql = "SELECT
			id, name
		FROM access_template at
		INNER JOIN (SELECT DISTINCT access_template_id
        	FROM access_template_application
        ) aa ON at.id = aa.access_template_id
        WHERE at.name = '*All'
		ORDER BY name";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($app = $sth->fetch(PDO::FETCH_ASSOC))
            $templates[$app['id']] = $app['name'];

        $sql = "SELECT access_template_id, application_id FROM access_template_application";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($app = $sth->fetch(PDO::FETCH_NUM))
            $template_application[$app[0]][$app[1]] = 1;

        $sql = "SELECT access_template_id, report_id FROM access_template_report";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($app = $sth->fetch(PDO::FETCH_NUM))
            $template_report[$app[0]][$app[1]] = 1;

        $sql = "SELECT access_template_id, order_type_id FROM access_template_order_type";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($app = $sth->fetch(PDO::FETCH_NUM))
            $template_order_type[$app[0]][$app[1]] = 1;

        $sql = "SELECT r.id, r.name FROM reports r ORDER BY r.name";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($app = $sth->fetch(PDO::FETCH_ASSOC))
            $reports[$app['id']] = $app['name'];

        $sql = "SELECT t.type_id, t.description
        FROM order_type t
        WHERE type_id IN (SELECT order_type_id FROM access_template_order_type)
        ORDER BY t.description";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($app = $sth->fetch(PDO::FETCH_ASSOC))
            $order_types[$app['type_id']] = $app['description'];

        return array(
            'users' => $users,
            'reports' => $reports,
            'order_types' => $order_types,
            'templates' => $templates,
            'applications' => $applications,
            'template_application' => $template_application,
            'template_report' => $template_report,
            'template_order_type' => $template_order_type
        );
    }

    /**
     * Create review structure
     *
     * @return array
     */
    public static function getReview()
    {
        $dbh = DataStor::getHandle();

        $sql = "SELECT
			LTRIM(RTRIM(tsmUserCompanyGrp.UserID)) as UserID,
			LTRIM(RTRIM(tsmUser.UserName)) as UserName,
			LTRIM(RTRIM(tsmUserCompanyGrp.CompanyID)) as CompanyID,
			LTRIM(RTRIM(tsmUserCompanyGrp.UserGroupID)) as Security_Group,
			LTRIM(RTRIM(tsmuser.IntegratedSecurityAcct)) as Domain_Account,
			IsNTUser
		FROM tsmUser,tsmUserCompanyGrp
		WHERE tsmUserCompanyGrp.UserID = tsmUser.UserID
		  AND left(tsmUser.IntegratedSecurityAcct, 6) <> 'URONET'
		  AND CompanyID NOT IN ('CAD','COA','CON','DKS','SGE','SLS','SOA')
		ORDER BY  tsmUserCompanyGrp.UserID, tsmUserCompanyGrp.CompanyID, isNtUser DESC";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        $users = $sth->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT
			LTRIM(RTRIM(g.UserGroupID)) as UserGroupID,
			LTRIM(RTRIM(g.UserGroupName)) as UserGroupName
		FROM tsmUserGroup g
		ORDER BY g.UserGroupID";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        $groups = $sth->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT
			LTRIM(RTRIM(md.ModuleID)),
			LTRIM(RTRIM(t.taskID)),
			LTRIM(RTRIM(tsd.TaskDesc + ' --  '+ tsd.TaskLongName))
		FROM tsmTask t
		INNER JOIN tsmModuleDef md with (nolock) on t.ModuleNo = md.ModuleNo
		INNER JOIN tsmTaskTypeDef ttd with (nolock) on t.TaskTypeID = ttd.TaskTypeID
		INNER JOIN tsmTaskStrDef tsd with (nolock) on t.TaskID = tsd.TaskID
		ORDER BY md.ModuleID, tsd.TaskDesc";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($task = $sth->fetch(PDO::FETCH_NUM))
        {
            $tasks[$task[0]][$task[1]] = $task[2];
        }

        $sql = "SELECT
			LTRIM(RTRIM(md.ModuleID)),
			LTRIM(RTRIM(t.taskID)),
			LTRIM(RTRIM(tp.UserGroupID)),
			CASE tp.Rights
				WHEN 1 THEN 'Excluded'
				WHEN 2 THEN 'Display Only'
				WHEN 3 THEN 'Normal'
				WHEN 4 THEN 'Supervisory'
				ELSE 'Unk'
			END
		FROM tsmTask t with (nolock)
		INNER JOIN tsmTaskPerm tp with (nolock) on tp.TaskID = t.TaskID
		INNER JOIN tsmModuleDef md with (nolock) on t.ModuleNo = md.ModuleNo
		INNER JOIN tsmTaskStrDef tsd with (nolock) on t.TaskID = tsd.TaskID";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($perm = $sth->fetch(PDO::FETCH_NUM))
        {
            $group_task[$perm[0]][$perm[1]][$perm[2]] = $perm[3];
        }

        $sql = "SELECT
			LTRIM(RTRIM(md.ModuleID)),
			LTRIM(RTRIM(se.SecurEventID)),
			LTRIM(RTRIM(ls.LocalText))
		FROM tsmSecurEvent se with (nolock)
		INNER JOIN tsmModuleDef md with (nolock) on md.ModuleNo = se.ModuleNo
		INNER JOIN tsmLocalString ls with (nolock) on se.DescStrNo = ls.StringNo
		ORDER BY md.ModuleID, ls.LocalText";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($event = $sth->fetch(PDO::FETCH_NUM))
        {
            $events[$event[0]][$event[1]] = $event[2];
        }

        $sql = "SELECT
			LTRIM(RTRIM(md.ModuleID)),
			LTRIM(RTRIM(se.SecurEventID)),
			LTRIM(RTRIM(sem.UserGroupID)),
			CASE ISNULL(sem.Authorized,0)
				WHEN 0 THEN 'No'
				WHEN 1 THEN 'Yes'
				ELSE 'Unk'
			END as 'Permission'
		FROM tsmSecurEvent se with (nolock)
		INNER JOIN tsmModuleDef md with (nolock) on md.ModuleNo = se.ModuleNo
		INNER JOIN tsmSecurEventPerm sem with (nolock) on se.SecurEventID = sem.SecurEventID
		INNER JOIN tsmLocalString ls with (nolock) on se.DescStrNo = ls.StringNo";
        $sth = $dbh->prepare($sql);
        $sth->execute();
        while ($perm = $sth->fetch(PDO::FETCH_NUM))
        {
            $group_event[$perm[0]][$perm[1]][$perm[2]] = $perm[3];
        }

        return array(
            'users' => $users,
            'groups' => $groups,
            'tasks' => $tasks,
            'events' => $events,
            'group_task' => $group_task,
            'group_event' => $group_event
        );
    }

    public static function saveApproval($user_id, array $snapshot)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('INSERT INTO access_template_approval (user_id, snapshot) VALUES (?, ?)');
        $sth->bindValue(1, $user_id, PDO::PARAM_INT);
        $sth->bindValue(2, json_encode($snapshot), PDO::PARAM_STR);
        $sth->execute();
    }

    public static function listApprovals()
    {
        $sql = <<<SQL
SELECT
    ata.id,
    u.lastname || ', ' || u.firstname AS user,
    ata.tstamp
FROM access_template_approval ata
JOIN users u ON ata.user_id = u.id
ORDER BY tstamp DESC
LIMIT 10
SQL;
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getApproval($id)
    {
        $sql = <<<SQL
SELECT
    u.lastname || ', ' || u.firstname AS user,
    ata.snapshot,
    ata.tstamp
FROM access_template_approval ata
JOIN users u ON ata.user_id = u.id
WHERE ata.id = ?
SQL;

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare($sql);
        $sth->bindValue(1, $id, PDO::PARAM_INT);
        $sth->execute();
        $approval = $sth->fetch(PDO::FETCH_ASSOC);
        $approval['snapshot'] = json_decode($approval['snapshot'], true);
        return $approval;
    }
}