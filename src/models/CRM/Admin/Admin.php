<?php

/**
 * @package Freedom
 */
require_once ('classes/Validation.php');

/**
 * Encapsulates the administration tasks for users, groups, and applications.
 *
 * @author Aron Vargas
 * @package Freedom
 */
class Admin {
    /**
     * @var PDO the database handle
     */
    private $dbh = null;

    private $lastName = array('All', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
        'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    static private $CRON_LOCATION = "/home/crontab-web";

    /**
     * Creates an Admin object.
     */
    public function __construct()
    {
        $this->dbh = DataStor::getHandle();
    }

    /**
     * Deletes a group.
     *
     * @param integer $group_id the group id
     */
    public function deleteGroup($group_id)
    {
        $this->delete($group_id, 'users');
    }

    /**
     * Deletes a user permissions group.
     *
     * @param integer $perm_group the id
     */
    public function deletePermGroup($perm_group)
    {
        $sth = $this->dbh->prepare("DELETE FROM user_perm_group WHERE perm_group_id = ?");
        $sth->bindValue(1, $perm_group, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Deletes a user.
     *
     * @param integer $user_id the user id
     */
    public function deleteUser($user_id)
    {
        $this->delete($user_id, 'users');
    }

    /**
     * Deletes an application.
     *
     * @param integer $app_id the application id
     */
    public function deleteApp($app_id)
    {
        $this->delete($app_id, 'applications');
    }

    /**
     * Deletes a holiday.
     *
     * @param integer $hol_id the holiday id
     */
    public function deleteHoliday($hol_id)
    {
        $this->delete($hol_id, 'holidays');
    }

    /**
     * Deletes a report.
     *
     * @param integer $report_id the report id
     */
    public function deleteReport($report_id)
    {
        $this->delete($report_id, 'reports');
    }

    /**
     * Deletes a contact role.
     *
     * @param integer $role_id the role id
     */
    public function deleteContactRole($role_id)
    {
        $sth = $this->dbh->prepare("DELETE FROM contact_roles WHERE id = ?");
        $sth->bindValue(1, $role_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Deletes a role.
     *
     * @param integer $role_id the role id
     */
    public function deleteRole($role_id)
    {
        $sth = $this->dbh->prepare("DELETE FROM role_relationships WHERE subject_role_id = ?");
        $sth->bindValue(1, $role_id, PDO::PARAM_INT);
        $sth->execute();

        $sth = $this->dbh->prepare("DELETE FROM role_relationships WHERE target_role_id = ?");
        $sth->bindValue(1, $role_id, PDO::PARAM_INT);
        $sth->execute();

        $sth = $this->dbh->prepare("DELETE FROM roles WHERE id = ?");
        $sth->bindValue(1, $role_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Deletes a role relationship.
     *
     * @param integer
     * @param integer
     * @param integer
     */
    public function deleteRoleRelationship($subject_role_id, $location, $target_role_id)
    {
        $sth = $this->dbh->prepare("DELETE FROM role_relationships
        WHERE subject_role_id = ?
        AND location = ?
        AND target_role_id = ?");
        $sth->bindValue(1, (int) $subject_role_id, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $location, PDO::PARAM_INT);
        $sth->bindValue(3, (int) $target_role_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Deletes an order type.
     *
     * @param integer $order_type the order type
     */
    public function deleteOrder($order_type)
    {
        # Not implemented
        # $this->delete($order_type, 'order_type');
    }

    /**
     * Deletes a template.
     *
     * @param integer $template_id
     */
    public function deleteTemplate($template_id)
    {
        $this->delete($template_id, 'users');
    }

    /**
     * Deletes a cron job
     *
     * @param integer $job_id
     */
    public function deleteJob($job_id)
    {
        $this->delete($job_id, 'crontab');
    }

    /**
     * Delete tconf record
     *
     * @param array
     */
    public function DeleteTConf($args)
    {
        $var_name = isset($args['var']) ? $args['var'] : null;

        if ($var_name)
        {
            $sth = $this->dbh->prepare("DELETE FROM config WHERE \"name\" = ?");
            $sth->bindValue(1, $var_name, PDO::PARAM_STR);
            $sth->execute();
        }
    }

    /**
     * Disables a cron job
     *
     * @param integer $job_id
     */
    public function disableJob($job_id)
    {
        $sth = $this->dbh->prepare("UPDATE crontab SET active = false WHERE id = ?");
        $sth->bindValue(1, $job_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Perform job routine
     * @param integer
     * @throws Exception
     */
    public function RunJob($job_id, &$output = null, &$ret_val = false)
    {
        if ($job_id)
        {
            $sth = $this->dbh->prepare("SELECT command FROM crontab WHERE id = ?");
            $sth->bindValue(1, $job_id, PDO::PARAM_INT);
            $sth->execute();
            $cmd = $sth->fetchColumn();

            ## Want to get output from the command so remove the any reirect to /dev/null
            $cmd = preg_replace('/> \/dev\/null/', '', $cmd);
            exec($cmd, $output, $ret_val);

            if (count($output) > 0)
            {
                $msg = implode("\n", $output);
                throw new Exception("\n{$cmd}\nRetVal: $ret_val\n$msg");
            }
        }
        else
        {
            throw new Exception("No identifier given");
        }
    }

    /**
     * Saves an application.
     *
     * @param integer $app_id the application id
     * @param array $app_arr
     */
    public function saveApp($app_id, $app_arr)
    {
        $full_name = $app_arr['full_name'];
        $short_name = $app_arr['short_name'];
        $order = $app_arr['order'];
        $groups = isset($app_arr['group']) ? $app_arr['group'] : array();

        # Validate the form.
        if (trim($full_name) == '')
        {
            throw new ValidationException('You must provide a full name for the application.');
        }

        if (trim($short_name) == '')
        {
            throw new ValidationException('You must provide a short name for the application.');
        }

        if (!IntValidator::validate($order))
        {
            throw new ValidationException('You must provide an order number for the application.');
        }

        # Start a transaction.
        $this->dbh->beginTransaction();

        # Update the application.
        $sth_upd = $this->dbh->prepare('
            UPDATE applications
            SET full_name = ?, short_name = ?, display_order = ?
            WHERE id = ?');
        $sth_upd->bindValue(1, $full_name, PDO::PARAM_STR);
        $sth_upd->bindValue(2, $short_name, PDO::PARAM_STR);
        $sth_upd->bindValue(3, $order, PDO::PARAM_INT);
        $sth_upd->bindValue(4, $app_id, PDO::PARAM_INT);
        $sth_upd->execute();

        # Clear the old groups.
        #
        $sth_del_grp = $this->dbh->prepare('DELETE FROM apps_groups_join WHERE app_id = ?');
        $sth_del_grp->bindValue(1, $app_id, PDO::PARAM_INT);
        $sth_del_grp->execute();

        # Insert the new groups.
        $sth_ins_grp = $this->dbh->prepare('INSERT INTO apps_groups_join (app_group_id, app_id) VALUES (?, ?)');
        foreach ($groups as $group_id)
        {
            $sth_ins_grp->bindValue(1, $group_id, PDO::PARAM_INT);
            $sth_ins_grp->bindValue(2, $app_id, PDO::PARAM_INT);
            $sth_ins_grp->execute();
        }

        # Commit the transaction.
        $this->dbh->commit();
    }

    /**
     * Update countries DB record
     *
     * @param array
     * @param integer
     *
     * @return integer
     */
    public function SaveCountry($args, $op = 1)
    {
        $id = isset($args['id']) ? $args['id'] : null;
        $abbr = isset($args['abbr']) ? substr($args['abbr'], 0, 4) : 'XXX';
        $name = isset($args['name']) ? substr($args['name'], 0, 64) : null;
        $display_order = isset($args['display_order']) ? (int) $args['display_order'] : null;

        ## Find next available id
        if (!is_numeric($id) && $op > 0)
        {
            $sth = $this->dbh->query("SELECT MAX(id) FROM countries");
            $id = $sth->fetchColumn();
            $id++;
        }

        $ret = $id;

        if ($id)
        {
            $sth = $this->dbh->prepare("SELECT id, abbr, name, display_order FROM countries WHERE id = ?");
            $sth->bindValue(1, $id, PDO::PARAM_INT);
            $sth->execute();
            $con = $sth->fetch(PDO::FETCH_OBJ);

            if ($con)
            {
                if (!is_null($abbr))
                    $con->abbr = $abbr;

                if (!is_null($name))
                    $con->name = $name;

                if (!is_null($display_order))
                    $con->display_order = $display_order;

                if ($op > 0)
                {
                    $sth = $this->dbh->prepare("UPDATE countries SET
						abbr = ?,
						name = ?,
						display_order = ?
					WHERE id = ?");
                    $sth->bindValue(1, $con->abbr, PDO::PARAM_STR);
                    $sth->bindValue(2, $con->name, PDO::PARAM_STR);
                    $sth->bindValue(3, $con->display_order, PDO::PARAM_INT);
                    $sth->bindValue(4, $id, PDO::PARAM_INT);
                    $sth->execute();
                }
                else
                {
                    $sth = $this->dbh->prepare("DELETE FROM countries WHERE id = ?");
                    $sth->bindValue(1, $id, PDO::PARAM_INT);
                    $sth->execute();
                    $ret = null;
                }
            }
            else if ($op > 0)
            {
                if (is_null($name))
                    $name = $abbr;
                if (is_null($display_order))
                    $display_order = 100;

                $sth = $this->dbh->prepare("INSERT INTO countries (id, abbr, name, display_order) VALUES (?, ?, ?, ?)");
                $sth->bindValue(1, $id, PDO::PARAM_INT);
                $sth->bindValue(2, $abbr, PDO::PARAM_STR);
                $sth->bindValue(3, $name, PDO::PARAM_STR);
                $sth->bindValue(4, $display_order, PDO::PARAM_INT);
                $sth->execute();
            }
        }

        return $ret;
    }

    /**
     * Saves a group.
     *
     * @param integer $group_id the group id
     * @param array $group_arr
     */
    public function saveGroup($group_id, $group_arr)
    {
        $name = $group_arr['group_name'];
        $children = isset($group_arr['children']) ? $group_arr['children'] : array();
        $active = isset($group_arr['active']) ? $group_arr['active'] : 1;

        # Validate the form.
        if (trim($name) == '')
        {
            throw new ValidationException('You must provide a name for the group.');
        }

        # Start a transaction.
        $this->dbh->beginTransaction();

        # Save the group.
        if ($group_id)
        {
            $sth = $this->dbh->prepare('UPDATE users SET lastname = ?, username = ?, active = ? WHERE id = ?');
            $sth->bindValue(1, $name, PDO::PARAM_STR);
            $sth->bindValue(2, $name, PDO::PARAM_STR);
            $sth->bindValue(3, $active, PDO::PARAM_BOOL);
            $sth->bindValue(4, $group_id, PDO::PARAM_INT);
            $sth->execute();
        }
        else
        {
            $sth = $this->dbh->prepare("INSERT INTO users (username, password, firstname, lastname, type, active) VALUES (?, '', '', ?, 2, ?)");
            $sth->bindValue(1, $name, PDO::PARAM_STR);
            $sth->bindValue(2, $name, PDO::PARAM_STR);
            $sth->bindValue(3, $active, PDO::PARAM_BOOL);
            $sth->execute();
            $group_id = $this->dbh->lastInsertId('users_id_seq');
        }

        # Delete the old child groups.
        $sth = $this->dbh->prepare('DELETE FROM users_groups_roles WHERE where_id = ? AND role_id = 2000');
        $sth->bindValue(1, $group_id, PDO::PARAM_INT);
        $sth->execute();

        # Insert the new child groups.
        $sth = $this->dbh->prepare('INSERT INTO users_groups_roles (who_id, where_id, role_id) VALUES (?, ?, 2000)');
        foreach ($children as $child)
        {
            $sth->bindValue(1, $child, PDO::PARAM_INT);
            $sth->bindValue(2, $group_id, PDO::PARAM_INT);
            $sth->execute();
        }

        # Commit the transaction
        $this->dbh->commit();
    }

    /**
     * Saves a user permissions group.
     *
     * @param integer $perm_group the group id
     * @param array $group_arr
     */
    public function savePermGroup($perm_group, $group_arr)
    {
        $name = $group_arr['group_name'];
        $role_members = isset($group_arr['role_members']) ? $group_arr['role_members'] : array();
        $group_members = isset($group_arr['group_members']) ? $group_arr['group_members'] : array();
        $user_members = isset($group_arr['user_members']) ? $group_arr['user_members'] : array();

        # Validate the form.
        if (trim($name) == '')
        {
            throw new ValidationException('You must provide a name for the group.');
        }

        # Start a transaction.
        $this->dbh->beginTransaction();

        # Save the group.
        if ($perm_group)
        {
            $sth = $this->dbh->prepare('UPDATE user_perm_group SET group_name = ? WHERE perm_group_id = ?');
            $sth->bindValue(1, $name, PDO::PARAM_STR);
            $sth->bindValue(2, $perm_group, PDO::PARAM_INT);
            $sth->execute();
        }
        else
        {
            # Find the highest id
            # Increment this to find the next available id
            $sth = $this->dbh->prepare("SELECT MAX(perm_group_id) FROM user_perm_group");
            $sth->execute();
            $perm_group = $sth->fetchColumn();
            $perm_group++;

            $sth = $this->dbh->prepare("INSERT INTO user_perm_group (perm_group_id, group_name) VALUES (?, ?)");
            $sth->bindValue(1, $perm_group, PDO::PARAM_INT);
            $sth->bindValue(2, $name, PDO::PARAM_STR);
            $sth->execute();
        }

        # Delete the old members of the group.
        $sth = $this->dbh->prepare('DELETE FROM perm_group_member  WHERE perm_group_id = ?');
        $sth->bindValue(1, $perm_group, PDO::PARAM_INT);
        $sth->execute();

        # Insert the role members.
        $sth = $this->dbh->prepare('INSERT INTO perm_group_member (perm_group_id, group_type, member_id) VALUES (?,3,?)');
        $sth->bindValue(1, $perm_group, PDO::PARAM_INT);
        foreach ($role_members as $id)
        {
            $sth->bindValue(2, (int) $id, PDO::PARAM_INT);
            $sth->execute();
        }

        # Insert the group members.
        $sth = $this->dbh->prepare('INSERT INTO perm_group_member (perm_group_id, group_type, member_id) VALUES (?,2,?)');
        $sth->bindValue(1, $perm_group, PDO::PARAM_INT);
        foreach ($group_members as $id)
        {
            $sth->bindValue(2, (int) $id, PDO::PARAM_INT);
            $sth->execute();
        }

        # Insert the user members.
        $sth = $this->dbh->prepare('INSERT INTO perm_group_member (perm_group_id, group_type, member_id) VALUES (?,1,?)');
        $sth->bindValue(1, $perm_group, PDO::PARAM_INT);
        foreach ($user_members as $id)
        {
            $sth->bindValue(2, (int) $id, PDO::PARAM_INT);
            $sth->execute();
        }

        # Commit the transaction
        $this->dbh->commit();
    }

    /**
     * Saves a role.
     *
     * @param integer $role_id the role id
     * @param array $role_arr
     */
    public function saveContactRole($role_id, $role_arr)
    {
        $identifier = $role_arr['identifier'];
        $description = $role_arr['description'];
        $active = (bool) $role_arr['active'];

        # Validate the form.
        /*if (trim($identifier) == '')
              {
                  throw new ValidationException("You must provide a identifier for the contact role.");
              }*/

        if (trim($description) == '')
        {
            throw new ValidationException("You must provide a description for the contact role.");
        }

        # Check for id uniqueness
        $exists = null;
        if (is_numeric($role_id))
        {
            $sth = $this->dbh->prepare("SELECT id, identifier FROM contact_roles WHERE id = ?");
            $sth->bindValue(1, $role_id, PDO::PARAM_INT);
            $sth->execute();
            $exists = $sth->fetch(PDO::FETCH_OBJ);
        }
        else
        {
            $sth = $this->dbh->query("SELECT max(id) FROM contact_roles");
            $role_id = 1 + (int) $sth->fetchColumn();
        }

        # Start a transaction.
        $this->dbh->beginTransaction();

        # Save the group.
        if ($exists)
        {
            $sth = $this->dbh->prepare('UPDATE contact_roles SET
				identifier = ?,
                description = ?,
				active = ?
			WHERE id = ?');
            $sth->bindValue(1, strtoupper($identifier), PDO::PARAM_STR);
            $sth->bindValue(2, $description, PDO::PARAM_STR);
            $sth->bindValue(3, $active, PDO::PARAM_BOOL);
            $sth->bindValue(4, $role_id, PDO::PARAM_INT);
            $sth->execute();
        }
        else
        {
            $sth = $this->dbh->prepare("INSERT INTO contact_roles
			(id, identifier, description, active)
			VALUES (?, ?, ?, ?)");
            $sth->bindValue(1, $role_id, PDO::PARAM_INT);
            $sth->bindValue(2, strtoupper($identifier), PDO::PARAM_STR);
            $sth->bindValue(3, $description, PDO::PARAM_STR);
            $sth->bindValue(4, $active, PDO::PARAM_BOOL);
            $sth->execute();
        }

        # Commit the transaction
        $this->dbh->commit();
    }

    /**
     * Saves a role.
     *
     * @param integer $role_id the role id
     * @param array $role_arr
     */
    public function saveRole($role_id, $role_arr)
    {
        $name = $role_arr['name'];
        $gla = (bool) $role_arr['gla'];

        # Validate the form.
        if (trim($name) == '')
        {
            throw new ValidationException("You must provide a name for the role.");
        }

        # Check for id uniqueness
        $exists = null;
        if (is_numeric($role_id))
        {
            $sth = $this->dbh->prepare("SELECT id, name FROM roles WHERE id = ?");
            $sth->bindValue(1, $role_id, PDO::PARAM_INT);
            $sth->execute();
            $exists = $sth->fetch(PDO::FETCH_OBJ);
        }
        else
        {
            $sth = $this->dbh->query("SELECT max(id) FROM roles");
            $role_id = 1 + (int) $sth->fetchColumn();
        }

        # Start a transaction.
        $this->dbh->beginTransaction();

        # Save the group.
        if ($exists)
        {
            $sth = $this->dbh->prepare('UPDATE roles SET
				name = ?,
				group_list_access = ?
			WHERE id = ?');
            $sth->bindValue(1, $name, PDO::PARAM_STR);
            $sth->bindValue(2, $gla, PDO::PARAM_BOOL);
            $sth->bindValue(3, $role_id, PDO::PARAM_INT);
            $sth->execute();
        }
        else
        {
            $sth = $this->dbh->prepare("INSERT INTO roles
			(id, name, group_list_access)
			VALUES (?, ?, ?)");
            $sth->bindValue(1, $role_id, PDO::PARAM_INT);
            $sth->bindValue(2, $name, PDO::PARAM_STR);
            $sth->bindValue(3, $gla, PDO::PARAM_BOOL);
            $sth->execute();
        }

        # Commit the transaction
        $this->dbh->commit();
    }

    /**
     * Saves a role.
     *
     * @param integer $role_id the role id
     * @param array $role_arr
     */
    public function saveRoleRelationship($subject_role_id, $location, $target_role_id)
    {
        $sth = $this->dbh->prepare("SELECT *
		FROM role_relationships
		WHERE subject_role_id = ?
		AND \"location\" = ?
		AND target_role_id = ?");
        $sth->bindValue(1, $subject_role_id, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $location, PDO::PARAM_INT);
        $sth->bindValue(3, $target_role_id, PDO::PARAM_INT);
        $sth->execute();
        $exists = $sth->fetch(PDO::FETCH_OBJ);
        if (!empty($exists))
        {
            throw new ValidationException("A Role Relationship with Subject ID: {$subject_role_id}, Location: {$location}, and Target ID: {$target_role_id} already exists.");
        }

        # Start a transaction.
        $this->dbh->beginTransaction();
        $sth = $this->dbh->prepare("INSERT INTO role_relationships
		(subject_role_id, \"location\", target_role_id) VALUES (?,?,?)");
        $sth->bindValue(1, $subject_role_id, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $location, PDO::PARAM_INT);
        $sth->bindValue(3, $target_role_id, PDO::PARAM_INT);
        $sth->execute();

        # Commit the transaction
        $this->dbh->commit();
    }

    /**
     * Update state DB record
     *
     * @param array
     * @param integer
     */
    public function SaveState($args, $op = 1)
    {
        $code = isset($args['code']) ? substr($args['code'], 0, 2) : null;
        $name = isset($args['name']) ? substr($args['name'], 0, 64) : null;
        $display_order = isset($args['display_order']) ? (int) $args['display_order'] : null;
        $country_id = isset($args['country_id']) ? (int) $args['country_id'] : 228;

        if ($code)
        {
            $sth = $this->dbh->prepare("SELECT code, name, display_order, country_id FROM states WHERE code = ?");
            $sth->bindValue(1, $code, PDO::PARAM_STR);
            $sth->execute();
            $state = $sth->fetch(PDO::FETCH_OBJ);
            if ($state)
            {
                if (!is_null($name))
                    $state->name = $name;

                if (!is_null($display_order))
                    $state->display_order = $display_order;

                if (!is_null($country_id))
                    $state->country_id = $country_id;

                if ($op > 0)
                {
                    $sth = $this->dbh->prepare("UPDATE states SET
						name = ?,
						display_order = ?,
    					country_id = ?
					WHERE code = ?");
                    $sth->bindValue(1, $name, PDO::PARAM_STR);
                    $sth->bindValue(2, $display_order, PDO::PARAM_INT);
                    $sth->bindValue(3, $country_id, PDO::PARAM_INT);
                    $sth->bindValue(4, $code, PDO::PARAM_STR);
                    $sth->execute();
                }
                else
                {
                    $sth = $this->dbh->prepare("DELETE FROM states WHERE code = ?");
                    $sth->bindValue(1, $code, PDO::PARAM_STR);
                    $sth->execute();
                }
            }
            else if ($op > 0)
            {
                if (is_null($name))
                    $name = $code;
                if (is_null($display_order))
                    $display_order = 100;
                if (is_null($country_id))
                    $country_id = 228;

                $sth = $this->dbh->prepare("INSERT INTO states (code, name, display_order, country_id) VALUES (?, ?, ?, ?)");
                $sth->bindValue(1, $code, PDO::PARAM_STR);
                $sth->bindValue(2, $name, PDO::PARAM_STR);
                $sth->bindValue(3, $display_order, PDO::PARAM_INT);
                $sth->bindValue(4, $country_id, PDO::PARAM_INT);
                $sth->execute();
            }
        }
    }

    /**
     * Update/Insert tconf record
     *
     * @param array
     */
    public function SaveTConf($args)
    {
        $orig_name = isset($args['orig_name']) ? $args['orig_name'] : null;
        $var_name = isset($args['var_name']) ? $args['var_name'] : null;
        $var_val = isset($args['val']) ? $args['val'] : null;
        $var_comment = isset($args['comment']) ? $args['comment'] : null;
        $comment_t = ($var_comment) ? PDO::PARAM_STR : PDO::PARAM_NULL;

        if ($var_name && !is_null($var_val))
        {
            # Remove old record
            if ($orig_name)
                $this->DeleteTConf(array('var' => $orig_name));

            $sth = $this->dbh->prepare("INSERT INTO config
			(\"name\", val, \"comment\") VALUES (?, ? ,?)");
            $sth->bindValue(1, $var_name, PDO::PARAM_STR);
            $sth->bindValue(2, $var_val, PDO::PARAM_STR);
            $sth->bindValue(3, $var_comment, $comment_t);
            $sth->execute();
        }
    }

    /**
     * Saves a user.
     *
     * @param integer $user_id the user id
     * @param array $user_arr
     */
    public function saveUser($user_id, $user_arr)
    {
        $user = new User($user_id);

        if ($user_id)
        {
            $sth_pw = $this->dbh->prepare('UPDATE users SET
				username = ?,
				firstname = ?,
				lastname = ?,
            	email = ?,
				active = ?,
				title = ?,
				type = ?
			WHERE id = ?');
            $sth_pw->bindValue(1, $user_arr['username'], PDO::PARAM_STR);
            $sth_pw->bindValue(2, $user_arr['firstname'], ($user_arr['firstname'] ? PDO::PARAM_STR : PDO::PARAM_NULL));
            $sth_pw->bindValue(3, $user_arr['lastname'], PDO::PARAM_STR);
            $sth_pw->bindValue(4, $user_arr['email'], ($user_arr['email'] ? PDO::PARAM_STR : PDO::PARAM_NULL));
            $sth_pw->bindValue(5, (bool) $user_arr['active'], PDO::PARAM_BOOL);
            $sth_pw->bindValue(6, $user_arr['title'], ($user_arr['title'] ? PDO::PARAM_STR : PDO::PARAM_NULL));
            $sth_pw->bindValue(7, (int) $user_arr['type'], PDO::PARAM_INT);
            $sth_pw->bindValue(8, (int) $user_id, PDO::PARAM_INT);
            $sth_pw->execute();
        }
        else
        {
            $sql = "INSERT INTO users (
                username, password, firstname, lastname,
                email, active, title, type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $sth_create = $this->dbh->prepare($sql);
            $sth_create->bindValue(1, $user_arr['username'], PDO::PARAM_STR);
            $sth_create->bindValue(2, md5('EMPTY'), PDO::PARAM_STR);
            $sth_create->bindValue(3, $user_arr['firstname'], ($user_arr['firstname'] ? PDO::PARAM_STR : PDO::PARAM_NULL));
            $sth_create->bindValue(4, $user_arr['lastname'], PDO::PARAM_STR);
            $sth_create->bindValue(5, $user_arr['email'], ($user_arr['email'] ? PDO::PARAM_STR : PDO::PARAM_NULL));
            $sth_create->bindValue(6, (bool) $user_arr['active'], PDO::PARAM_BOOL);
            $sth_create->bindValue(7, $user_arr['title'], ($user_arr['title'] ? PDO::PARAM_STR : PDO::PARAM_NULL));
            $sth_create->bindValue(8, (int) $user_arr['type'], PDO::PARAM_INT);
            $sth_create->execute();

            $user_id = $this->dbh->lastInsertId('users_id_seq');

            $this->dbh->exec("INSERT INTO preferences (user_id, application_id, key,value) SELECT $user_id, application_id, key, value FROM default_preferences");
        }

        # Update password if posted
        $new_password = false;
        if (in_array($user->getUsername(), Config::$AUTH_BYPASS_AD) && $user_arr['pwd'] != '')
        {
            if ($user_arr['pwd'] != $user_arr['pwd2'])
                throw new ValidationException('The passwords are not the same.');

            $new_password = md5($user_arr['pwd']);
        }

        if ($new_password)
        {
            $sth_pw = $this->dbh->prepare('UPDATE users SET password = ? WHERE id = ?');
            $sth_pw->bindValue(1, $new_password, PDO::PARAM_STR);
            $sth_pw->bindValue(2, (int) $user_id, PDO::PARAM_INT);
            $sth_pw->execute();
        }
    }

    /**
     * Saves a holiday.
     *
     * @param integer $hol_id the holiday id
     * @param array $new
     */
    public function saveHoliday($hol_id, $new)
    {
        $name = $new['label'];
        $date = $new['date'];

        # Validate the form.
        if (trim($name) == '')
        {
            throw new ValidationException('You must provide a name for the holiday.');
        }

        if (trim($date) == '')
        {
            throw new ValidationException('You must provide a date for the holiday.');
        }

        # Save the group.
        if ($hol_id)
        {
            $sth = $this->dbh->prepare('UPDATE holidays SET name = ?, date = ? WHERE id = ?');
            $sth->bindValue(3, $hol_id, PDO::PARAM_INT);
        }
        else
        {
            $sth = $this->dbh->prepare('INSERT INTO holidays (name, date) VALUES (?, ?)');
        }

        $sth->bindValue(1, $name, PDO::PARAM_STR);
        $sth->bindValue(2, $date, PDO::PARAM_STR);
        $sth->execute();
    }

    /**
     * Saves a report.
     *
     * @param $report_id integer
     * @param $new array
     */
    public function saveReport($report_id, $new)
    {
        $name = $new['report_name'];
        $group_id = $new['report_group'];

        # Validate the form.
        if (trim($name) == '')
        {
            throw new ValidationException('You must provide a name for the report.');
        }

        # Start a transaction.
        $this->dbh->beginTransaction();

        # Update the application.
        $sth = $this->dbh->prepare('UPDATE reports SET name = ?, group_id = ? WHERE id = ?');
        $sth->bindValue(1, $name, PDO::PARAM_STR);
        $sth->bindValue(2, $group_id, PDO::PARAM_INT);
        $sth->bindValue(3, $report_id, PDO::PARAM_INT);
        $sth->execute();

        # Commit the transaction.
        $this->dbh->commit();
    }

    /**
     * Saves an order type.
     *
     * @param $order_type integer
     * @param $new array
     */
    public function saveOrder($order_type, $new)
    {
        $description = $new['description'];
        $display_order = (int) $new['display_order'];
        $in_asset = isset($new['in_asset']);
        $out_asset = isset($new['out_asset']);
        $in_return = isset($new['in_return']);
        $is_purchase = isset($new['is_purchase']);

        # Validate the form.
        if (trim($description) == '')
        {
            throw new ValidationException('You must provide a name for the order type.');
        }

        # Start a transaction.
        $this->dbh->beginTransaction();

        # Update the application.
        $sth = $this->dbh->prepare('UPDATE order_type
             SET
                description = ?,
                display_order = ?,
                in_asset = ?,
                out_asset = ?,
                in_return = ?,
                is_purchase = ?
             WHERE type_id = ?');
        $sth->bindValue(1, $description, PDO::PARAM_STR);
        $sth->bindValue(2, $display_order, PDO::PARAM_INT);
        $sth->bindValue(3, $in_asset, PDO::PARAM_BOOL);
        $sth->bindValue(4, $out_asset, PDO::PARAM_BOOL);
        $sth->bindValue(5, $in_return, PDO::PARAM_BOOL);
        $sth->bindValue(6, $is_purchase, PDO::PARAM_BOOL);
        $sth->bindValue(7, (int) $order_type, PDO::PARAM_INT);
        $sth->execute();

        # Commit the transaction.
        $this->dbh->commit();
    }

    /**
     * Saves a cron job
     *
     * @param integer $job_id
     * @param array $new
     */
    public function saveJobs($new)
    {
        $this->dbh->beginTransaction();

        $sth_upd = $this->dbh->prepare('UPDATE crontab SET
			active = ?,
			server = ?,
			minute = ?,
			hour = ?,
			day = ?,
			month = ?,
			dow = ?,
			command = ?,
			updated_by = ?,
			comments = ?
		WHERE id = ?');

        $sth_ins = $this->dbh->prepare('
		INSERT INTO crontab (active,server,minute,hour,day,month,dow,command,updated_by,comments)
		VALUES (?,?,?,?,?,?,?,?,?,?)');

        foreach ($new['job'] as $i => $job)
        {
            $job['minute'] = trim($job['minute']);
            $job['hour'] = trim($job['hour']);
            $job['day'] = trim($job['day']);
            $job['month'] = trim($job['month']);
            $job['dow'] = trim($job['dow']);
            $job['command'] = trim($job['command']);
            $job['comments'] = trim($job['comments']);

            if ($job['id'] || $job['minute'] || $job['hour'] || $job['day'] ||
                $job['month'] || $job['dow'] || $job['command'])
            {
                # Validate the form
                if ($job['minute'] == '')
                    throw new ValidationException('Minute cannot be empty');

                if ($job['hour'] == '')
                    throw new ValidationException('Hour cannot be empty');

                if ($job['day'] == '')
                    throw new ValidationException('Day cannot be empty');

                if ($job['month'] == '')
                    throw new ValidationException('Month cannot be empty');

                if ($job['dow'] == '')
                    throw new ValidationException('Day of Week cannot be empty');

                if ($job['command'] == '')
                    throw new ValidationException('Command cannot be empty');

                if ($job['id'])
                {
                    $sth = $sth_upd;
                    $sth->bindValue(11, $job['id'], PDO::PARAM_INT);
                }
                else
                {
                    $sth = $sth_ins;
                }

                $sth->bindValue(1, isset($job['active']), PDO::PARAM_BOOL);
                $sth->bindValue(2, $job['server'], PDO::PARAM_STR);
                $sth->bindValue(3, $job['minute'], PDO::PARAM_STR);
                $sth->bindValue(4, $job['hour'], PDO::PARAM_STR);
                $sth->bindValue(5, $job['day'], PDO::PARAM_STR);
                $sth->bindValue(6, $job['month'], PDO::PARAM_STR);
                $sth->bindValue(7, $job['dow'], PDO::PARAM_STR);
                $sth->bindValue(8, $job['command'], PDO::PARAM_STR);
                $sth->bindValue(9, $job['user_id'], PDO::PARAM_INT);
                $sth->bindValue(10, $job['comments'], PDO::PARAM_STR);
                $sth->execute();
            }
        }

        $this->dbh->commit();
    }

    /**
     * Prints the form used to edit applications.
     *
     * @param integer $app_id
     */
    public function showAppForm($app_id = '')
    {
        $short_name = '';
        $full_name = '';
        $order = '';
        $group_options = '';

        if ($app_id)
        {
            $sth = $this->dbh->prepare('SELECT * FROM applications WHERE id = ?');
            $sth->bindValue(1, $app_id, PDO::PARAM_INT);
            $sth->execute();

            $app_arr = $sth->fetch(PDO::FETCH_ASSOC);

            $short_name = $app_arr['short_name'];
            $full_name = $app_arr['full_name'];
            $order = $app_arr['display_order'];

            $sth = $this->dbh->prepare('
                SELECT ag.id,
                       ag.name,
                       agj.app_id
                FROM app_group ag
                    LEFT OUTER JOIN apps_groups_join agj
                    ON agj.app_group_id = ag.id AND agj.app_id = ?
                ORDER BY ag.name');
            $sth->bindValue(1, $app_id, PDO::PARAM_INT);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $selected = (!is_null($row['app_id'])) ? 'selected' : '';
                $group_options .= "\n<option value=\"{$row['id']}\" $selected>{$row['name']}</option>";
            }
        }
        else
        {
            $sth = $this->dbh->query('SELECT id, name FROM app_group ORDER BY name');
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $group_options .= "\n<option value=\"{$row['id']}\">{$row['name']}</option>";
            }
        }

        echo <<<END
        <form name="app_form" action="{$_SERVER['PHP_SELF']}" method="post"
              onSubmit="return validateAppForm(this)">
        <input type="hidden" name="target" value="app">
        <input type="hidden" name="app" value="{$app_id}">
        <table class="form" cellpadding="5" cellspacing="2">
            <tr>
                <th class="subheader" colspan="2">Admin</th>
            </tr>
            <tr>
                <th class="subsubheader" colspan="2">Add/Edit Application</th>
            </tr>
            <tr>
                <th class="form">Full Name:</th>
                <td class="form">
                    <input type="text" name="full_name" value="{$full_name}" size="20" maxlength="256">
                    <span class="required">(Required)</span>
                </td>
            </tr>
            <tr>
                <th class="form">Short Name:</th>
                <td class="form">
                    <input type="text" name="short_name" value="{$short_name}" size="10" maxlength="64" readonly>
                    <span class="required">(Required)</span>
                </td>
            </tr>
            <tr>
                <td class="form_help" colspan="2">For now, Short Name is not editable because the code depends on it</td>
            </tr>
            <tr>
                <th class="form">Order:</th>
                <td class="form">
                    <input type="text" name="order" value="{$order}" size="5" maxlength="5">
                    <span class="required">(Required)</span>
                </td>
            </tr>
            <tr>
                <th class="form">Group:</th>
                <td class="form">
                    <select name="group[]" size="5" multiple>
                        {$group_options}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="buttons" colspan="2"><input type="submit" name="action" value="Save"></td>
            </tr>
        </table>
        </form>
END;

    }

    /**
     * Prints a list of applications.
     */
    public function showAppList()
    {
        $sth = $this->dbh->query("
            SELECT app.id,
                   app.full_name,
                   array_to_string(array_accum(ag.name), ', ') AS group_name,
                   app.display_order
            FROM applications app
              LEFT OUTER JOIN apps_groups_join agj ON app.id = agj.app_id
              LEFT OUTER JOIN app_group ag ON agj.app_group_id = ag.id
            WHERE accessible = true
            GROUP BY app.id, app.full_name, app.display_order
            ORDER BY full_name");

        echo <<<END
        <!-- Begin the app list table -->
        <table class="recs" cellpadding="5" cellspacing="2">
        <tr>
            <th class="subheader" colspan="4">Applications</th>
        </tr>
        <tr>
            <th class="recs">Application Name</th>
            <th class="recs">Application Group</th>
            <th class="recs">Order</th>
            <th class="recs">Actions</th>
        </tr>
END;

        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            echo <<<END
            <tr>
                <td class="recs">
                    {$row['full_name']}
                </td>
                <td class="recs">
                    {$row['group_name']}
                </td>
                <td class="recs">
                    {$row['display_order']}
                </td>
                <td class="recs">
                    <form action="{$_SERVER['PHP_SELF']}" method="get">
                        <input type="hidden" name="target" value="app">
                        <input type="hidden" name="app" value="{$row['id']}">
                        <input type="submit" name="action" value="Edit">
                    </form>
                </td>
            </tr>
END;
        }

        echo <<<END
        <!-- For now, we do not show the add button.  The id field of
             the applications table is not serial, so it cannot auto-
             increment.  We assume developers would add new applications
             directly to the database.
        <tr>
            <td class="buttons" colspan="3">
                <form action="{$_SERVER['PHP_SELF']}" method="get">
                    <input type="hidden" name="target" value="app">
                    <input type="submit" name="action" value="Add">
                </form>
            </td>
        </tr>
        -->
        </table>
        <!-- End the app list table -->
END;
    }

    /**
     * Prints the form used to edit cron jobs
     */
    public function showCronForm()
    {
        $max_inputs = ini_get('max_input_vars');
        $sth_servers = $this->dbh->query('SELECT DISTINCT server FROM crontab');
        $servers = $sth_servers->fetchAll(PDO::FETCH_COLUMN, 0);

        # Get a list of eligible users
        $sth_users = $this->dbh->query('
		SELECT u.id, u.firstname, u.lastname
		FROM users u
		INNER JOIN v_users_primary_group upg ON u.id = upg.user_id
		WHERE upg.group_id = 388
		AND u.active = true
		ORDER BY lastname, firstname');
        $it_users = $sth_users->fetchAll(PDO::FETCH_ASSOC);

        $job_rows = '';
        $sth_jobs = $this->dbh->query('
		SELECT
			id, server, active, minute, hour, day, month, dow,
			command, updated_at, updated_by, comments
		FROM crontab
		ORDER BY id');
        $i = 0;
        if ($sth_jobs->rowCount() > 0)
        {
            $jobs = $sth_jobs->fetchAll(PDO::FETCH_ASSOC);
            $jobs[] = array(
                'id' => '',
                'server' => '',
                'active' => false,
                'minute' => '',
                'hour' => '',
                'day' => '',
                'month' => '',
                'dow' => '',
                'command' => '',
                'updated_by' => null,
                'comments' => ''
            );

            foreach ($jobs as $i => $job)
            {
                $user_options = '';
                foreach ($it_users as $u)
                {
                    $selected = ($u['id'] == $job['updated_by']) ? 'selected' : '';
                    $user_options .= '<option value="' . $u['id'] . '" ' . $selected . '>' . $u['firstname'] . ' ' . $u['lastname'] . '</option>';
                }

                $server_options = '';
                foreach ($servers as $server)
                {
                    $selected = ($server == $job['server']) ? 'selected' : '';
                    $server_options .= '<option value="' . $server . '" ' . $selected . '>' . $server . '</option>';
                }

                if ($job['id'])
                {
                    $job_num_txt = "Job {$job['id']}";
                    $delete_disabled = '';
                }
                else
                {
                    $job_num_txt = "New Job";
                    $delete_disabled = 'disabled';
                }

                $active_checked = ($job['active']) ? 'checked' : '';
                $command = htmlentities($job['command']);
                $run_cls = ($job['server'] == 'web') ? "submit" : "hidden";

                $job_rows .= <<<END
				<tr>
					<td colspan="9" style="background-color:#dddddd">{$job_num_txt}</td>
				</tr>
				<tr>
					<th class="form" style="text-align:center">Active</th>
					<th class="form" style="text-align:center">Server</th>
					<th class="form" style="text-align:center">Min</th>
					<th class="form" style="text-align:center">Hour</th>
					<th class="form" style="text-align:center">Day</th>
					<th class="form" style="text-align:center">Mth</th>
					<th class="form" style="text-align:center">DoW</th>
					<th class="form" colspan="2">Command</th>
				</tr>
				<tr>
					<td class="form" style="text-align:center">
						<input type="hidden" name="job[$i][id]" value="{$job['id']}">
						<input type="checkbox" name="job[$i][active]" value="1" {$active_checked}>
					</td>
					<td class="form" style="text-align:center">
						<select name="job[$i][server]" id='server{$job['id']}' onchange="SetRunClass({$job['id']});">
							{$server_options}
						</select>
					</td>
					<td class="form" style="text-align:center">
						<input type="text" name="job[$i][minute]" value="{$job['minute']}" size="4">
					</td>
					<td class="form" style="text-align:center">
						<input type="text" name="job[$i][hour]" value="{$job['hour']}" size="4">
					</td>
					<td class="form" style="text-align:center">
						<input type="text" name="job[$i][day]" value="{$job['day']}" size="4">
					</td>
					<td class="form" style="text-align:center">
						<input type="text" name="job[$i][month]" value="{$job['month']}" size="4">
					</td>
					<td class="form" style="text-align:center">
						<input type="text" name="job[$i][dow]" value="{$job['dow']}" size="4">
					</td>
					<td class="form" style="text-align:center">
						<input type="text" name="job[$i][command]" value="{$command}" size="50" onkeydown="DisableRun({$job['id']}, event);">
					</td>
					<td class="form" rowspan="2" style="text-align:center">
						<button type="button" onclick="deleteJob({$job['id']})" {$delete_disabled}>Delete</button>
						<br/> <br/>
						<button id='run{$job['id']}' class='{$run_cls}' type='button' onclick="RunJob({$job['id']});">Run</button>
					</td>
				</tr>
				<tr>
					<td class="form" colspan="3" style="vertical-align:top">
						<label>User (choose yourself)</label><br>
						<select name="job[$i][user_id]">
					        {$user_options}
					    </select>
					</td>
					<td class="form" colspan="5">
						<label>Comments</label><br>
						<textarea name="job[$i][comments]" rows="2" cols="80">{$job['comments']}</textarea>
					</td>
				</tr>
END;
            }
        }
        else
        {
            $job_rows = '<tr><td class="form" colspan="9">No jobs found</td></tr>';
        }

        $num_vars_per_job = 11;
        $hidden_var_count = 3;
        $i = $i * $num_vars_per_job;
        $i += $hidden_var_count;

        echo <<<END
		<form id="cron_form" action="{$_SERVER['PHP_SELF']}" method="post" onSubmit="return validateCronForm(this)">
			<input type="hidden" name="target" value="cron">
			<input type="hidden" name="showcron" value="1">
			<input type="hidden" name="num_jobs" value="{$i}">
			<table class="form" cellpadding="5" cellspacing="2">
				<tr>
				    <th class="subheader" colspan="9">Admin</th>
				</tr>
				<tr>
				    <th class="subsubheader" colspan="9">Add/Edit Cron Jobs</th>
				</tr>
				{$job_rows}
				<tr>
				    <td class="buttons" colspan="9">
						<input type="submit" name="action" value="Save">
						<div class='note'>
							PHP Max input vars is set to $max_inputs the current var count is $i. If the form is too large, it will no save properly.
						</div>
					</td>
			    </tr>
			</table>
		</form>
		<form id="delete_job_form" action="{$_SERVER['PHP_SELF']}" method="post">
			<input type="hidden" name="target" value="cron">
			<input type="hidden" name="action" value="delete">
			<input type="hidden" name="job_id" value="">
		</form>
END;
    }

    /**
     * Prints the form used to edit groups.
     *
     * @param integer $group_id
     */
    public function showGroupForm($group_id = '')
    {
        $group_name = '';
        $chk_active_y = "checked";
        $chk_active_n = "";
        $group_options = Forms::createGroupList();

        if ($group_id)
        {
            $sth = $this->dbh->prepare('SELECT lastname AS name, active FROM users WHERE id = ?');
            $sth->bindValue(1, $group_id, PDO::PARAM_INT);
            $sth->execute();
            $g = $sth->fetch(PDO::FETCH_OBJ);
            $group_name = htmlentities($g->name, ENT_QUOTES);
            $chk_active_y = ($g->active) ? "checked" : "";
            $chk_active_n = ($g->active) ? "" : "checked";

            $sth = $this->dbh->prepare('SELECT who_id FROM users_groups_roles WHERE where_id = ? AND role_id = 2000');
            $sth->bindValue(1, $group_id, PDO::PARAM_INT);
            $sth->execute();
            $children = $sth->fetchAll(PDO::FETCH_COLUMN);

            $group_options = Forms::createGroupList(null, ($children) ? $children : array());
        }

        echo <<<END
        <form name="group_form" action="{$_SERVER['PHP_SELF']}" method="post"
              onSubmit="return validateGroupForm(this)">
        <input type="hidden" name="target" value="group">
        <input type="hidden" name="group" value="{$group_id}">
        <table class="form" cellpadding="5" cellspacing="2">
            <tr>
                <th class="subheader" colspan="2">Admin</th>
            </tr>
            <tr>
                <th class="subsubheader" colspan="2">Add/Edit Group</th>
            </tr>
            <tr>
                <th class="form">Group Name:</th>
                <td class="form">
                    <input type="text" name="group_name" value="{$group_name}" size="20" maxlength="256">
                    <span class="required">(Required)</span>
                </td>
            </tr>
			<tr>
                <th class="form">Active:</th>
                <td class="form">
                    <input id='active_y' type="radio" name="active" value="1" $chk_active_y />
                    <label for='active_y'>Yes</span>
					<input id='active_n' type="radio" name="active" value="0" $chk_active_n />
                    <label for='active_n'>No</span>
                </td>
            </tr>
            <tr>
                <th class="form">Child Groups:</th>
                <td class="form">
                    <select name="children[]" size="5" multiple>{$group_options}</select>
                </td>
            </tr>
            <tr>
                <td class="buttons" colspan="2"><input type="submit" name="action" value="Save"></td>
            </tr>
        </table>
        </form>
END;
    }

    /**
     * Prints the form used to edit permissions groups.
     *
     * @param integer $perm_group
     */
    public function showPermGroupForm($perm_group = null)
    {
        $group_name = "";
        $role_options = "";
        $group_options = "";
        $user_options = "";

        # Build options list for roles
        $sth = $this->dbh->prepare("SELECT
            r.id,
            r.name,
            m.member_id
        FROM roles r
        LEFT JOIN perm_group_member m ON r.id = m.member_id AND m.group_type = 3 AND perm_group_id = ?
        ORDER BY r.id");
        $sth->bindValue(1, $perm_group, PDO::PARAM_INT);
        $sth->execute();
        while (list($id, $role, $member) = $sth->fetch(PDO::FETCH_NUM))
        {
            $sel = ($member) ? " selected" : "";
            $role_options .= "    <option value='$id'$sel>$role</option>\n";
        }

        # Build options list for groups
        $sth = $this->dbh->prepare("SELECT
            u.id,
            u.lastname,
            m.member_id
        FROM users u
        LEFT JOIN perm_group_member m ON u.id = m.member_id AND m.group_type = 2 AND perm_group_id = ?
        WHERE u.active AND u.type = 2
        ORDER BY lastname, firstname");
        $sth->bindValue(1, (int) $perm_group, PDO::PARAM_INT);
        $sth->execute();
        while (list($id, $group, $member) = $sth->fetch(PDO::FETCH_NUM))
        {
            $sel = ($member) ? " selected" : "";
            $group_options .= "    <option value='$id'$sel>$group</option>\n";
        }

        # Build options list for users
        $sth = $this->dbh->prepare("SELECT
            u.id,
            u.lastname,
            u.firstname,
            u.active,
            m.member_id
        FROM users u
        LEFT JOIN perm_group_member m ON u.id = m.member_id AND m.group_type = 1 AND perm_group_id = ?
        WHERE u.type = 1
        ORDER BY lastname, firstname");
        $sth->bindValue(1, (int) $perm_group, PDO::PARAM_INT);
        $sth->execute();
        while (list($id, $last, $first, $active, $member) = $sth->fetch(PDO::FETCH_NUM))
        {
            # Build users full name
            $name = htmlentities(trim($last . ", " . $first));
            if (!$active)
                $name = "--" . $name;

            $sel = ($member) ? " selected" : "";
            $user_options .= "    <option value='$id'$sel>$name</option>\n";
        }

        if ($perm_group)
        {
            # Find perm group name
            $sth = $this->dbh->prepare("SELECT group_name
            FROM user_perm_group
            WHERE perm_group_id = ?");
            $sth->bindValue(1, $perm_group, PDO::PARAM_INT);
            $sth->execute();
            $group_name = htmlentities($sth->fetchColumn(), ENT_QUOTES);
        }
        else
        {
            $perm_group = 0;
        }

        echo <<<END
        <form name="perm_group_form" action="{$_SERVER['PHP_SELF']}"
              method="post" onSubmit="return validatePermGroupForm(this)">
        <input type="hidden" name="target" value="perm_group">
        <input type="hidden" name="perm_group" value="{$perm_group}">
        <table class="form" cellpadding="5" cellspacing="2">
            <tr>
                <th class="subheader" colspan="2">Admin</th>
            </tr>
            <tr>
                <th class="subsubheader" colspan="2">Add/Edit Permission Group</th>
            </tr>
            <tr>
                <th class="form">Group Name:</th>
                <td class="form">
                    <input type="text" name="group_name" value="{$group_name}" size="20" maxlength="32">
                    <span class="required">(Required)</span>
                </td>
            </tr>
            <tr>
                <th class="form">Role Members:</th>
                <td class="form">
                    <select name="role_members[]" size="10" multiple style='width:100%;'>{$role_options}</select>
                </td>
            </tr>
            <tr>
                <th class="form">Group Members:</th>
                <td class="form">
                    <select name="group_members[]" size="10" multiple style='width:100%;'>{$group_options}</select>
                </td>
            </tr>
            <tr>
                <th class="form">User Members:</th>
                <td class="form">
                    <select name="user_members[]" size="30" multiple style='width:100%;'>{$user_options}</select>
                </td>
            </tr>
            <tr>
                <td class="buttons" colspan="2"><input type="submit" name="action" value="Save"></td>
            </tr>
        </table>
        </form>
END;
    }

    /**
     * Prints a list of groups.
     */
    public function showGroupList()
    {
        $sth = $this->dbh->query("
            SELECT u.id AS id,
                   u.lastname AS name,
                   ARRAY_TO_STRING(ARRAY_ACCUM(u2.lastname), ',') AS children
            FROM users u
              LEFT OUTER JOIN users_groups_roles ugr ON u.id = ugr.where_id AND
                                                        role_id = 2000
              LEFT OUTER JOIN users u2 ON ugr.who_id = u2.id
            WHERE u.type = 2
            GROUP BY u.id, u.lastname
            ORDER BY u.id");

        echo <<<END
        <!-- Begin the group list table -->
        <table class="recs" cellpadding="5" cellspacing="2">
        <tr>
            <th class="subheader" colspan="3">Groups</th>
        </tr>
        <tr>
            <th class="recs">Group Name</th>
            <th class="recs">Children</th>
            <th class="recs">Actions</th>
        </tr>
END;
        while ($group_arr = $sth->fetch(PDO::FETCH_ASSOC))
        {
            echo <<<END
            <tr>
                <td class="recs">{$group_arr['name']}</td>
                <td class="recs">{$group_arr['children']}</td>
                <td class="recs">
                    <form action="{$_SERVER['PHP_SELF']}" method="get">
                        <input type="hidden" name="target" value="group">
                        <input type="hidden" name="group" value="{$group_arr['id']}">
                        <input type="submit" name="action" value="Edit">
                        <input type="submit" name="action" value="Delete" onClick="return confirm('Are you sure you want to delete this group? (Hint: probably not)');">
                    </form>
                </td>
            </tr>
END;
        }

        echo <<<END
        <tr>
            <td class="buttons" colspan="3">
                <form action="{$_SERVER['PHP_SELF']}" method="get">
                    <input type="hidden" name="target" value="group">
                    <input type="submit" name="action" value="Add">
                </form>
            </td>
        </tr>
        </table>
        <!-- End the group list table -->
END;
    }

    /**
     * Prints a list of permissions groups.
     */
    public function showPermGroupList()
    {
        $sth = $this->dbh->query("SELECT perm_group_id, group_name
        FROM user_perm_group
        ORDER BY perm_group_id");

        echo <<<END
        <!-- Begin the group list table -->
        <table class="recs" cellpadding="5" cellspacing="2">
        <tr>
            <th class="subheader" colspan="3">Permission Groups</th>
        </tr>
        <tr>
            <th class="recs">Group Name</th>
            <th class="recs">Actions</th>
        </tr>
END;
        while (list($perm_group, $name) = $sth->fetch(PDO::FETCH_NUM))
        {
            echo <<<END
            <tr>
                <td class="recs">$name</td>
                <td class="recs">
                    <form action="{$_SERVER['PHP_SELF']}" method="get">
                        <input type="hidden" name="target" value="perm_group">
                        <input type="hidden" name="perm_group" value="$perm_group">
                        <input type="submit" name="action" value="Edit">
                        <input type="submit" name="action" value="Delete" onClick="return confirm('Are you sure you want to delete this permissions group? (Hint: probably not)');">
                    </form>
                </td>
            </tr>
END;
        }

        echo <<<END
        <tr>
            <td class="buttons" colspan="3">
                <form action="{$_SERVER['PHP_SELF']}" method="get">
                    <input type="hidden" name="target" value="perm_group">
                    <input type="submit" name="action" value="Add">
                </form>
            </td>
        </tr>
        </table>
        <!-- End the group list table -->
END;
    }

    /**
     * Shows the Holiday edit form
     */
    public function showHolidayForm()
    {
        $date_format = 'm/d/Y';
        $calendar_format = str_replace(array('Y', 'd', 'm', 'M'), array('%Y', '%d', '%m', '%b'), $date_format);

        $holiday_list = '';
        $sth = $this->dbh->query('SELECT id, date, name FROM holidays ORDER BY date');
        while ($row = $sth->fetch())
        {
            $date = date($date_format, strtotime($row['date']));
            $holiday_list .= "<option value=\"{$row['id']}\">$date {$row['name']}</option>";
        }

        echo <<<END
        <form name="holiday" action="{$_SERVER['PHP_SELF']}" method="post">
        <input type="hidden" name="action" value="">
        <input type="hidden" name="target" value="hol">
        <input type="hidden" name="hol" value="">
        <table class="form" cellpadding="5" cellspacing="2">
            <tr>
                <td class="form" rowspan="3">
                    <label>Current Holidays:</label><br>
                    <select name="holidays" size="6" onChange="this.form.editButton.disabled=false;this.form.deleteButton.disabled=false">
                        {$holiday_list}
                    </select>
                </td>
                <td class="form">
                    <label>Date:</label>
                    <input type="text" name="date" id="date" size="10" value="" readonly disabled>
                    <img class="form_bttn" id="ev_date_trg" src="images/calendar-mini.png" alt="Calendar" title="Calendar">
                    <script type="text/javascript">
                        Calendar.setup({
                            inputField    :    "date",            // id of the input field
                            ifFormat    :    "{$calendar_format}",        // format of the input field
                            button        :    "ev_date_trg",    // trigger for the calendar (button ID)
                            step        :    1,                // show all years in drop-down boxes (instead of every other year as default)
                            weekNumbers    :    false            // hides the week numbers
                        });
                    </script>
                </td>
            </tr>
            <tr>
                <td class="form">
                    <label>Label:</label>
                    <input type="text" name="label" value="" size="30" maxlength="128" disabled>
                </td>
            </tr>
            <tr>
                <td class="buttons">
                    <input type="button" name="addButton" value="Add" onClick="addHoliday(this.form)">
                    <input type="button" name="saveButton" value="Save"  onClick="saveHoliday(this.form)" disabled>
                    <input type="button" name="cancelButton" value="Cancel" onClick="cancelHoliday(this.form)">
                </td>
            </tr>
            <tr>
                <td class="buttons" style="text-align:left" colspan="3">
                    <input type="button" name="editButton" value="Edit" onClick="editHoliday(this.form)" disabled>
                    <input type="button" name="deleteButton" value="Delete" onClick="deleteHoliday(this.form)" disabled>
                </td>
            </tr>
        </table>
        </form>
END;
    }

    /**
     *
     */
    public function showOrderForm($order_type = '')
    {
        $description = '';
        $display_order = '';
        $in_asset_chk = '';
        $out_asset_chk = '';
        $in_return_chk = '';
        $is_purchase_chk = '';

        $sth = $this->dbh->prepare('SELECT
            description,
            display_order,
            in_asset,
            out_asset,
            in_return,
            is_purchase
        FROM order_type
        WHERE type_id = ?');
        $sth->bindValue(1, $order_type, PDO::PARAM_INT);
        $sth->execute();
        if ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $description = $row['description'];
            $display_order = $row['display_order'];
            $in_asset_chk = ($row['in_asset']) ? "checked" : "";
            $out_asset_chk = ($row['out_asset']) ? "checked" : "";
            $in_return_chk = ($row['in_return']) ? "checked" : "";
            $is_purchase_chk = ($row['is_purchase']) ? "checked" : "";
        }

        echo <<<END
        <form name="order_type_form" action="{$_SERVER['PHP_SELF']}" method="post"
              onSubmit="return validateOrderForm(this)">
        <input type="hidden" name="target" value="order">
        <input type="hidden" name="order_type" value="{$order_type}">
        <table class="form" cellpadding="5" cellspacing="2">
            <tr>
                <th class="subheader" colspan="2">Admin</th>
            </tr>
            <tr>
                <th class="subsubheader" colspan="2">Add/Edit Order Type</th>
            </tr>
            <tr>
                <th class="form">Name:</th>
                <td class="form">
                    <input type="text" name="description" value="{$description}" size="32" maxlength="32">
                    <span class="required">(Required)</span>
                </td>
            </tr>
            <tr>
                <th class="form">Display Order:</th>
                <td class="form">
                    <input type="text" name="display_order" value="{$display_order}" size="3">
                </td>
            </tr>
            <tr>
                <th class="form">Inbound Asset:</th>
                <td class="form">
                    <input type="checkbox" name="in_asset" value="1" {$in_asset_chk}>
                </td>
            </tr>
            <tr>
                <th class="form">Outbound Asset:</th>
                <td class="form">
                    <input type="checkbox" name="out_asset" value="1" {$out_asset_chk}>
                </td>
            </tr>
            <tr>
                <th class="form">Is Swap:</th>
                <td class="form">
                    <input type="checkbox" name="in_return" value="1" {$in_return_chk}>
                </td>
            </tr>
            <tr>
                <th class="form">For Purchase:</th>
                <td class="form">
                    <input type="checkbox" name="is_purchase" value="1" {$is_purchase_chk}>
                </td>
            </tr>
            <tr>
                <td class="buttons" colspan="2"><input type="submit" name="action" value="Save"></td>
            </tr>
        </table>
        </form>
END;
    }#showOrderForm()



    /**
     *
     */
    public function showOrderList()
    {
        echo <<<END
        <table class="recs" cellpadding="5" cellspacing="2">
            <tr>
                <th class="subheader" colspan="7">Order Types</th>
            </tr>
            <tr>
                <th class="subsubheader">Name</th>
                <th class="subsubheader">Order</th>
                <th class="subsubheader">In Asset</th>
                <th class="subsubheader">Out Asset</th>
                <th class="subsubheader">Swap</th>
                <th class="subsubheader">Purchase</th>
                <th class="subsubheader">Action</th>
            </tr>
END;

        $sth = $this->dbh->query('
            SELECT type_id, description, display_order,
                in_asset, out_asset, in_return, is_purchase
            FROM order_type
            ORDER BY display_order');

        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $in = ($row['in_asset']) ? 'X' : '';
            $out = ($row['out_asset']) ? 'X' : '';
            $swap = ($row['in_return']) ? 'X' : '';
            $purchase = ($row['is_purchase']) ? 'X' : '';

            echo <<<END
            <tr>
                <td class="recs">{$row['description']}</td>
                <td class="recs" align="center">{$row['display_order']}</td>
                <td class="recs" align="center">{$in}</td>
                <td class="recs" align="center">{$out}</td>
                <td class="recs" align="center">{$swap}</td>
                <td class="recs" align="center">{$purchase}</td>
                <td class="recs">
                    <form action="{$_SERVER['PHP_SELF']}" method="get">
                        <input type="hidden" name="target" value="order">
                        <input type="hidden" name="order_type" value="{$row['type_id']}">
                        <input type="submit" name="action" value="Edit">
                    </form>
                </td>
            </tr>
END;
        }

        echo '</table>';
    }#showOrderList()



    /**
     *  Updates the distribution email list
     *  in the Config table for AutoReports
     *
     *  @param $which_report string required name of report
     *  @param $prefix string required for prepending to name of report
     *  @param $suffix string required for appending to name of report
     *  @param $list_value array() of user ids -OR-
     *                     comma delimited string of user ids
     */
    public function saveDistribLists($which_report, $prefix, $suffix, $list_value)
    {

        # Validate the report name:
        if (trim($which_report) == '')
            throw new ValidationException('Error:  Missing autoreport name.');

        # Validate the email list prefix:
        if (trim($prefix) == '')
            throw new ValidationException('Error:  Missing email distribution list prefix.');

        # Validate the email list suffix:
        if (trim($suffix) == '')
            throw new ValidationException('Error:  Missing email distribution list suffix.');



        $list_name = $prefix . '-' . $which_report . '-' . $suffix;

        if (isset($list_value) && !empty($list_value))
        {
            $user_array = array();

            # Test type of list value:
            if (is_array($list_value))
                $user_array = $list_value;
            elseif (isset($list_value))
                $user_array = explode(',', $list_value);

            # Get emails for the selected user ids:
            $email_array = $this->getMatchingEmailsForUserIds($user_array);

            if (count($email_array) > 0)
                $email_list = implode(',', $email_array);
            else
                $email_list = "";

        }#if
        else
        {
            $email_list = "";
        }

        $count = 0;
        $sth = $this->dbh->prepare('SELECT count(*) FROM Config WHERE name = ?');
        $sth->bindValue(1, $list_name, PDO::PARAM_STR);
        $sth->execute();
        $rows = $sth->fetch(PDO::FETCH_NUM);
        $count = $rows[0];


        # If this report distribution list does not exist,
        # add it to the Config table.
        if ($count == 0)
        {
            # Start a transaction.
            $this->dbh->beginTransaction();

            # Update the email distribution list.
            $sth = $this->dbh->prepare('INSERT INTO Config (name, comment, val)
                                        VALUES (?, ?, ?)');

            $sth->bindValue(1, $list_name, PDO::PARAM_STR);
            $sth->bindValue(2, 'AutoReport Email Distribution List', PDO::PARAM_STR);
            $sth->bindValue(3, $email_list, PDO::PARAM_STR);
            $sth->execute();

            # Commit the transaction.
            $this->dbh->commit();

        }#if
        # Otherwise, update the existing report distribution list.
        else
        {
            # Start a transaction.
            $this->dbh->beginTransaction();

            # Update the email distribution list.
            $sth = $this->dbh->prepare('UPDATE Config
                                        SET    val  = ?
                                        WHERE  name = ?');

            $sth->bindValue(1, $email_list, PDO::PARAM_STR);
            $sth->bindValue(2, $list_name, PDO::PARAM_STR);
            $sth->execute();

            # Commit the transaction.
            $this->dbh->commit();
        }#else

    }#saveDistribLists()



    /**
     *  Returns array of user ids for each matching
     *  email address in the input array
     *
     *  @param  $email_array    array of emails
     *  @return $user_id_array  array of matching user ids
     */
    public function getMatchingUserIdsForEmails($email_array)
    {

        $user_id_array = array();

        if (!isset($email_array) || empty($email_array))
            return ($user_id_array);

        $sth = $this->dbh->prepare("SELECT id FROM Users WHERE lower(email) = ?");

        foreach ($email_array as $email)
        {
            $sth->bindValue(1, strtolower($email), PDO::PARAM_STR);
            $sth->execute();
            if ($sth->rowCount() > 0)
                $user_id_array[] = $sth->fetchColumn();

        }#foreach

        return $user_id_array;

    }#getMatchingUserIdsForEmails()


    /**
     *  Returns array of emails for each matching
     *  user id in the input array
     *
     *  @param  $user_id_array  array of user ids
     *  @return $email_array    array of matching emails
     */
    public function getMatchingEmailsForUserIds($user_id_array)
    {

        $email_array = array();

        if (!isset($user_id_array) || empty($user_id_array))
            return ($email_array);

        $sth = $this->dbh->prepare("SELECT email FROM Users WHERE id = ?");

        foreach ($user_id_array as $id)
        {
            $sth->bindValue(1, $id, PDO::PARAM_STR);
            $sth->execute();
            if ($sth->rowCount() > 0)
                $email_array[] = $sth->fetchColumn();

        }#foreach

        return $email_array;

    }#getMatchingEmailsForUserIds()


    /**
     *  Display form to edit the distribution lists
     *  for email recipients of the given report output.
     *  This form will allow updating the current
     *  email distribution lists for the chosen report.
     *
     *  @param $current_email_recipients string delimited by <BR>
     */
    public function showEditDistribListsForm($current_to_emails = null,
        $current_cc_emails = null,
        $current_bcc_emails = null,
        $which_report,
        $distrib_list_prefix)
    {



        $to_email_array = preg_split('/<BR>/i', $current_to_emails);
        $cc_email_array = preg_split('/<BR>/i', $current_cc_emails);
        $bcc_email_array = preg_split('/<BR>/i', $current_bcc_emails);

        $to_users_array = $this->getMatchingUserIdsForEmails($to_email_array);
        $cc_users_array = $this->getMatchingUserIdsForEmails($cc_email_array);
        $bcc_users_array = $this->getMatchingUserIdsForEmails($bcc_email_array);

        $to_email_option_list = Forms::createUserOptionList($to_users_array);
        $cc_email_option_list = Forms::createUserOptionList($cc_users_array);
        $bcc_email_option_list = Forms::createUserOptionList($bcc_users_array);

        $user_list = $to_email_option_list;

        echo <<<END
        <form name="edit_distrib_lists_form" action="{$_SERVER['PHP_SELF']}" method="post">
        <input type="hidden" name="target" value="distrib_list">
        <input type="hidden" name="which_report" value="{$which_report}">
        <input type="hidden" name="distrib_list_prefix" value="{$distrib_list_prefix}">
        <input type="hidden" name="distrib_list_suffix" id="distrib_list_suffix" value="TO">
        <input type="hidden" name="email_distribution_user_list" id="email_distribution_user_list" value="">


        <div id="to_email_option_list_div" style="display: none;">
            $to_email_option_list
        </div>
        <div id="cc_email_option_list_div" style="display: none;">
            $cc_email_option_list
        </div>
        <div id="bcc_email_option_list_div" style="display: none;">
            $bcc_email_option_list
        </div>


        <table class="form" cellpadding="5" cellspacing="2">
            <tr>
                <th class="subheader" colspan="2">Edit Email Distribution Lists</th>
            </tr>
            <tr>
                <th class="subsubheader" colspan="2">Add/Edit Report:&nbsp;  $which_report</th>
            </tr>
            <tr>
                <th class="form">Select Email List:</th>
                <td class="form">
                    <select id="select_email_distribution_list" onchange="changeEmailDistributionUserList()">
                       <option value='TO'>'TO' email recipients</option>
                       <option value='CC'>'CC' carbon copy recipients</option>
                       <option value='BCC'>'BCC' blind carbon copy recipients</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th class="form">User List:</th>
                <td class="form">
                    <div id="select_email_distribution_user_list_div">
                        <select id="select_email_distribution_user_list" name="user_list[]" size="30" multiple>
                            {$user_list}
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="buttons" colspan="2">
                    <input type="submit" name="action" value="Save" onClick="storeEmailDistributionListOptions();">
                    <input type="button" name="clear" value="Clear All" onClick="clearEditDistributionList();">
                </td>
            </tr>
        </table>
        </form>
END;

    }#showEditDistribListForm()

    /**
     * Get server location of autoreport.php
     * @return location of file
     */

    public function getAutoReportLocation()
    {
        $location = dirname(__DIR__) . "/autoreport/autoreport.php";
        return $location;

    }#getAutoReportLocation()

    /**
     * Create list of reports.functions run by
     * autoreport.php, with function name appended
     * to the report name separated with a '.'
     *
     * Used by showDistribLists()
     *
     * @return array of report.function names
     *
     **/

    public function generateAutoReportsList()
    {

        $list = array();

        $autoreport_location = $this->getAutoReportLocation();


        $file_handle = fopen($autoreport_location, "r");

        if ($file_handle)
        {

            while (!feof($file_handle))
            {
                $line = fgets($file_handle);

                if (preg_match('/^\$routine\[.*\].*include.*=>.*function.*=>.*/', $line))
                {

                    $line_array = explode(',', $line);

                    $part1_array = explode("]", $line_array[0]);
                    $part2_array = preg_split("/=>/", $line_array[1]);

                    $program_name = $part1_array[0];
                    $program_name = str_replace("\"", "", $program_name);
                    $program_name = preg_replace("/.*routine\[/", "", $program_name);

                    $function_name = str_replace("\"", "", $part2_array[1]);
                    $function_name = preg_replace('/\);.*/', '', $function_name);
                    $list[] = trim($program_name) . '.' . trim($function_name);

                }#if

            }#while

        }#if fh

        fclose($file_handle);

        return $list;

    }#generateAutoReportsList()







    /**
     * Displays all known autoreports along
     * with their email distribution lists, if any.
     * Allows edit of these email distribution lists.
     *
     * Currently part of Admin section of an application.
     *
     */
    public function showDistribLists()
    {

        # Report and function names acquired from autoreport.php:
        $autoreports_list = $this->generateAutoReportsList();


        echo <<<END
        <table class="recs" cellpadding="5" cellspacing="2">
            <tr>
                <th class="subheader" colspan="5">Distribution Lists</th>
            </tr>
            <tr>
                <th class="subsubheader">Report</th>
                <th class="subsubheader">&apos;TO&apos; List</th>
                <th class="subsubheader">&apos;CC&apos; List</th>
                <th class="subsubheader">&apos;BCC&apos; List</th>
                <th class="subsubheader">Action</th>
            </tr>
END;


        $prefix = "autoreport_output_distrib_list";

        # Get distribution lists for each report
        # listed above:
        $sql = " SELECT name as rpt_name, val as email_list " .
            " FROM Config " .
            " WHERE name like '{$prefix}%' " .
            " ORDER BY name";


        $sth = $this->dbh->query($sql);
        $distrib_lists = array();

        # Fill the distribution lists array
        # with any email lists found:
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $next_report = ($row['rpt_name']) ? $row['rpt_name'] : '';
            $email_list = ($row['email_list']) ? $row['email_list'] : '';

            if (!empty($next_report))
            {
                list($prefix, $rpt_name, $suffix) = explode('-', $next_report);
                $distrib_lists[$rpt_name . '-' . $suffix] = $email_list;
            }#if

        }#while


        # Print list of each report given above, along with
        # its matching distribution lists, if any:

        asort($autoreports_list);
        foreach ($autoreports_list as $report)
        {

            $to = (isset($distrib_lists[$report . '-TO'])) ? $distrib_lists[$report . '-TO'] : '';
            $cc = (isset($distrib_lists[$report . '-CC'])) ? $distrib_lists[$report . '-CC'] : '';
            $bcc = (isset($distrib_lists[$report . '-BCC'])) ? $distrib_lists[$report . '-BCC'] : '';

            #Break up long email strings
            $to = str_replace(',', '<BR>', $to);
            $cc = str_replace(',', '<BR>', $cc);
            $bcc = str_replace(',', '<BR>', $bcc);

            echo <<<END
            <tr>
                <td class="recs">$report</td>
                <td class="recs" align="left">{$to}</td>
                <td class="recs" align="left">{$cc}</td>
                <td class="recs" align="left">{$bcc}</td>
                <td class="recs">
                    <form action="{$_SERVER['PHP_SELF']}" method="get">
                        <input type="hidden" name="target" value="distrib_list">
                        <input type="hidden" name="which_report" value="$report">
                        <input type="hidden" name="current_to_recipients" value="$to">
                        <input type="hidden" name="current_cc_recipients" value="$cc">
                        <input type="hidden" name="current_bcc_recipients" value="$bcc">
                        <input type="hidden" name="distrib_list_prefix" value="$prefix">
                        <input type="submit" name="action" value="Edit">
                    </form>
                </td>
            </tr>
END;
        }#foreach

        echo '</table>';

    }#showDistribLists()

    /**
     * Create HTMLM form
     *
     */
    public function ShowRoleForm($role_id)
    {
        $role_name = "";
        $gla = true;

        # Find role fields
        if (is_numeric($role_id))
        {
            $sth = $this->dbh->prepare("SELECT
				name, group_list_access
			FROM roles
			WHERE id = ?");
            $sth->bindValue(1, $role_id, PDO::PARAM_INT);
            $sth->execute();
            $role = $sth->fetch(PDO::FETCH_OBJ);
            $role_name = $role->name;
            $gla = $role->group_list_access;

            $role_id = "<input type='hidden' name='role' value='{$role_id}' />$role_id";
        }
        else
        {
            $sth = $this->dbh->query("SELECT
				max(id)
			FROM roles");
            $role_id = 1 + (int) $sth->fetchColumn();

            $role_id = "<input type='text' name='role' value='{$role_id}' size='10'/>";
        }

        $role_name = htmlentities($role_name, ENT_QUOTES);
        $sel_gla_y = ($gla) ? "checked" : "";
        $sel_gla_n = ($gla) ? "" : "checked";

        echo "<form name='role_form' action='{$_SERVER['PHP_SELF']}' method='post'>
			<input type='hidden' name='target' value='role'>
			<table class='form' cellpadding='5' cellspacing='2'>
				<tr>
					<th class='subheader' colspan='2'>Add/Edit Role</th>
				</tr>
				<tr>
					<th class='form'>ID:</th>
					<td class='form'>{$role_id}</td>
				</tr>
				<tr>
					<th class='form'>Name:</th>
					<td class='form'>
						<input type='text' name='name' value='{$role_name}' size='20' maxlength='64'/>
					</td>
				</tr>
				<tr>
					<th class='form'>Group List Access:</th>
					<td class='form'>
						<input id='gla_y' type='radio' name='gla' value='1' $sel_gla_y/>
						<label for='gla_y'>Yes</label>
						<input id='gla_n' type='radio' name='gla' value='0' $sel_gla_n/>
						<label for='gla_n'>No</label>
					</td>
				</tr>
				<tr>
					<td class='buttons' colspan='2'>
						<input type='submit' name='action' value='Save'/>
					</td>
				</tr>
			</table>
		</form>";
    }

    /**
     * Create HTMLM form
     *
     */
    public function ShowContactRoleForm($role_id)
    {
        $role_name = "";
        $role_identifier = "";
        $role_description = "";
        $active = true;

        # Find role fields
        if (is_numeric($role_id))
        {
            $sth = $this->dbh->prepare("SELECT
				identifier, description, active
			FROM contact_roles
			WHERE id = ?");
            $sth->bindValue(1, $role_id, PDO::PARAM_INT);
            $sth->execute();
            $role = $sth->fetch(PDO::FETCH_OBJ);
            $role_identifier = $role->identifier;
            $role_description = $role->description;
            $active = $role->active;

            $role_id = "<input type='hidden' name='role' value='{$role_id}' />$role_id";
        }
        else
        {
            $sth = $this->dbh->query("SELECT
				max(id)
			FROM contact_roles");
            $role_id = 1 + (int) $sth->fetchColumn();

            $role_id = "<input type='text' name='role' value='{$role_id}' size='10' disabled/>";
        }

        $role_name = htmlentities($role_name, ENT_QUOTES);
        $sel_active_y = ($active) ? "checked" : "";
        $sel_active_n = ($active) ? "" : "checked";

        echo "<form name='contact_role_form' action='{$_SERVER['PHP_SELF']}' method='post'>
			<input type='hidden' name='target' value='contact_role'>
			<table class='form' cellpadding='5' cellspacing='2'>
				<tr>
					<th class='subheader' colspan='2'>Add/Edit Role</th>
				</tr>
				<tr>
					<th class='form'>ID:</th>
					<td class='form'>{$role_id}</td>
				</tr>
				<tr>
					<th class='form'>Identifier:</th>
					<td class='form'>
						<input type='text' name='identifier' value='{$role_identifier}' size='20' maxlength='64'/>
					</td>
				</tr>
                <tr>
					<th class='form'>Description:</th>
					<td class='form'>
						<input type='text' name='description' value='{$role_description}' size='20' maxlength='64'/>
					</td>
				</tr>
				<tr>
					<th class='form'>Active:</th>
					<td class='form'>
						<input id='active_y' type='radio' name='active' value='1' $sel_active_y/>
						<label for='active_y'>Yes</label>
						<input id='active_n' type='radio' name='active' value='0' $sel_active_n/>
						<label for='active_n'>No</label>
					</td>
				</tr>
				<tr>
					<td class='buttons' colspan='2'>
						<input type='submit' name='action' value='Save'/>
					</td>
				</tr>
			</table>
		</form>";
    }

    /**
     * List Contact Roles
     */
    public function showContactRoleList()
    {
        echo "<!-- Begin the contact role list table -->
		<table class='recs' cellpadding='5' cellspacing='2'>
			<tr>
				<th class='subheader' colspan='5'>Contact Roles</th>
			</tr>
			<tr>
				<th class='recs'>ID</th>
				<th class='recs'>Identifier</th>
				<th class='recs'>Description</th>
                <th class='recs'>Active</th>
				<th class='recs'>Action</th>
			</tr>";

        $sth = $this->dbh->query("SELECT
			id, identifier, description, active
		FROM contact_roles
		ORDER BY description");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $active = ($row['active']) ? "Yes" : "No";
            echo "<tr>
				<td class='recs'>{$row['id']}</td>
				<td class='recs'>{$row['identifier']}</td>
                <td class='recs'>{$row['description']}</td>
				<td class='recs'>$active</td>
				<td class='recs'>
					<form action='{$_SERVER['PHP_SELF']}' method='get'>
						<input type='hidden' name='role' value='{$row['id']}'>
						<input type='hidden' name='target' value='contact_role'>
						<input type='submit' name='action' value='Edit'>
						<input type='submit' name='action' value='Delete' onclick=\"return confirm('We really should not delete Contact Roles. Are you sure?');\">
					</form>
				</td>
			</tr>";
        }
        echo "<tr>
				<td class='buttons' colspan='5'>
					<form action='{$_SERVER['PHP_SELF']}' method='get'>
						<input type='hidden' name='target' value='contact_role'>
						<input type='hidden' name='role' value=''>
						<input type='submit' name='action' value='Add'>
					</form>
				</td>
			</tr>
		</table>
		<!-- End the contact role list table -->";
    }

    /**
     * List Role records
     */
    public function showRoleList()
    {
        echo "<!-- Begin the role list table -->
		<table class='recs' cellpadding='5' cellspacing='2'>
			<tr>
				<th class='subheader' colspan='4'>Roles</th>
			</tr>
			<tr>
				<th class='recs'>Role ID</th>
				<th class='recs'>Name</th>
				<th class='recs'>Group List Access</th>
				<th class='recs'>Action</th>
			</tr>";

        $sth = $this->dbh->query("SELECT
			id, name, group_list_access
		FROM roles
		ORDER BY ID");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $gla = ($row['group_list_access']) ? "Yes" : "No";
            echo "<tr>
				<td class='recs'>{$row['id']}</td>
				<td class='recs'>{$row['name']}</td>
				<td class='recs'>$gla</td>
				<td class='recs'>
					<form action='{$_SERVER['PHP_SELF']}' method='get'>
						<input type='hidden' name='role' value='{$row['id']}'>
						<input type='hidden' name='target' value='role'>
						<input type='submit' name='action' value='Edit'>
						<input type='submit' name='action' value='Delete' onclick=\"return confirm('We realy should not delete Roles. Are you sure?');\">
					</form>
				</td>
			</tr>";
        }
        echo "<tr>
				<td class='buttons' colspan='4'>
					<form action='{$_SERVER['PHP_SELF']}' method='get'>
						<input type='hidden' name='target' value='role'>
						<input type='hidden' name='role' value=''>
						<input type='submit' name='action' value='Add'>
					</form>
				</td>
			</tr>
		</table>
		<!-- End the role list table -->";

        echo <<<END
 		<!-- Begin the role relationship list table -->
		<table class="recs" cellpadding="5" cellspacing="2">
			<tr>
				<th class="subheader" colspan="4">Roles Relationships</th>
			</tr>
			<tr>
				<th class="recs">This Role</th>
				<th class="recs">In This location</th>
				<th class="recs">Can Access</th>
				<th class="recs">Action</th>
			</tr>
END;

        $sth = $this->dbh->query("SELECT
			r.subject_role_id,
			r.location,
			r.target_role_id,
			s.name as subject_role,
			t.name as target_role,
			CASE
				WHEN r.location = 0 THEN 'same'::text
				WHEN r.location = (-1) THEN 'lower'::text
				WHEN r.location = 1 THEN 'higher'::text
				ELSE NULL::text
 			END AS location_str
		FROM role_relationships r
		INNER JOIN roles s ON s.id = r.subject_role_id
		INNER JOIN roles t ON t.id = r.target_role_id");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            echo <<<END
			<tr>
				<td class="recs">{$row['subject_role']}</td>
				<td class="recs">{$row['location_str']}</td>
				<td class="recs">{$row['target_role']}</td>
				<td class="recs">
					<form action="{$_SERVER['PHP_SELF']}" method="get">
						<input type="hidden" name="target" value="role_relationship">
						<input type="hidden" name="subject_role_id" value="{$row['subject_role_id']}">
						<input type="hidden" name="location" value="{$row['location']}">
						<input type="hidden" name="target_role_id" value="{$row['target_role_id']}">
						<input type="submit" name="action" value="Delete" onclick="return confirm('We realy should not delete Roles. Are you sure?');">
				 	</form>
				</td>
			</tr>
END;
        }

        echo <<<END
		<tr>
			<td class="buttons" colspan="4">
				<form action="{$_SERVER['PHP_SELF']}" method="get">
					<input type="hidden" name="target" value="role_relationship">
					<input type="hidden" name="subject_role_id" value="0">
					<input type="hidden" name="location" value="0">
					<input type="hidden" name="target_role_id" value="0">
					<input type="submit" name="action" value="Add">
				</form>
			</td>
		</tr>
		</table>
		<!-- End the role relationship list table -->
END;
    }

    /**
     * Create HTMLM form
     *
     */
    public function ShowRoleRelationshipForm($subject_role_id, $location, $target_role_id)
    {
        $subject_role_list = "";
        $target_role_list = "";

        # role option list
        $sth = $this->dbh->query("SELECT
			id, name
		FROM roles
		ORDER BY id");
        while ($role = $sth->fetch(PDO::FETCH_OBJ))
        {
            $sel = ($role->id == $subject_role_id) ? " selected" : "";
            $subject_role_list .= "<option value='{$role->id}'$sel>{$role->name}</option>";

            $sel = ($role->id == $target_role_id) ? " selected" : "";
            $target_role_list .= "<option value='{$role->id}'$sel>{$role->name}</option>";
        }

        # Selected Location
        $location = (int) $location;
        $sel_lower = ($location == -1) ? "selected" : "";
        $sel_higher = ($location == 1) ? "selected" : "";
        $sel_same = ($location == 0) ? "selected" : "";

        echo "<form name='role_form' action='{$_SERVER['PHP_SELF']}' method='post'>
			<input type='hidden' name='target' value='role_relationship'>
			<table class='form' cellpadding='5' cellspacing='2'>
				<tr>
					<th class='subheader' colspan='2'>Add/Edit Role Relationship</th>
				</tr>
				<tr>
					<th class='form'>Subject Role:</th>
					<td class='form'>
						<select name='subject_role_id'>
							$subject_role_list
						</select>
					</td>
				</tr>
				<tr>
					<th class='form'>Location:</th>
					<td class='form'>
						<select name='location'>
							<option value='-1' $sel_lower>Lower</option>
							<option value='0' $sel_same>Same</option>
							<option value='1' $sel_higher>Higher</option>
						</select>
					</td>
				</tr>
				<tr>
					<th class='form'>Target Role:</th>
					<td class='form'>
						<select name='target_role_id'>
							$target_role_list
						</select>
					</td>
				</tr>
				<tr>
					<td class='buttons' colspan='2'>
						<input type='submit' name='action' value='Save'/>
					</td>
				</tr>
			</table>
		</form>";
    }

    /**
     * @param $report_id integer
     */
    public function showReportForm($report_id = '')
    {
        $name = '';
        $group_id = '';
        $group_name = '';
        $group_options = '';

        if ($report_id)
        {
            $sth = $this->dbh->prepare('
                SELECT r.name AS name,
                       r.group_id AS group_id,
                       g.name AS group_name
                FROM reports r INNER JOIN
                     report_groups g ON r.group_id = g.id
                WHERE r.id = ?
                ORDER BY name');
            $sth->bindValue(1, $report_id, PDO::PARAM_INT);
            $sth->execute();

            $report_arr = $sth->fetch(PDO::FETCH_ASSOC);

            $name = $report_arr['name'];
            $group_id = $report_arr['group_id'];
            $group_name = $report_arr['group_name'];


            $sth = $this->dbh->query('
                SELECT id, name FROM report_groups');
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $selected = ($row['id'] == $group_id) ? 'selected' : '';
                $group_options .= "<option value=\"{$row['id']}\" $selected>{$row['name']}</option>";
            }
        }

        echo <<<END
        <form name="report_form" action="{$_SERVER['PHP_SELF']}" method="post"
              onSubmit="return validateReportForm(this)">
        <input type="hidden" name="target" value="report">
        <input type="hidden" name="report" value="{$report_id}">
        <table class="form" cellpadding="5" cellspacing="2">
            <tr>
                <th class="subheader" colspan="2">Admin</th>
            </tr>
            <tr>
                <th class="subsubheader" colspan="2">Add/Edit Report</th>
            </tr>
            <tr>
                <th class="form">Report Name:</th>
                <td class="form">
                    <input type="text" name="report_name" value="{$name}" size="32" maxlength="64">
                    <span class="required">(Required)</span>
                </td>
            </tr>
            <tr>
                <th class="form">Report Group:</th>
                <td class="form">
                    <select name="report_group">{$group_options}</select>
                </td>
            </tr>
            <tr>
                <td class="buttons" colspan="2"><input type="submit" name="action" value="Save"></td>
            </tr>
        </table>
        </form>
END;
    }

    /**
     *
     */
    public function showReportList()
    {
        echo <<<END
        <table class="recs" cellpadding="5" cellspacing="2">
            <tr>
                <th class="subheader" colspan="3">Reports</th>
            </tr>
            <tr>
                <th class="recs">Name</th>
                <th class="recs">Group</th>
                <th class="recs">Actions</th>
            </tr>
END;

        $sth = $this->dbh->query('
            SELECT r.id AS id,
                   r.name AS name,
                   r.group_id AS group_id,
                   g.name AS group_name
            FROM reports r INNER JOIN
                 report_groups g ON r.group_id = g.id
            ORDER BY name');

        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            echo <<<END
            <tr>
                <td class="recs">{$row['name']}</td>
                <td class="recs">{$row['group_name']}</td>
                <td class="recs">
                    <form action="{$_SERVER['PHP_SELF']}" method="get">
                        <input type="hidden" name="target" value="report">
                        <input type="hidden" name="report" value="{$row['id']}">
                        <input type="submit" name="action" value="Edit">
                    </form>
                </td>
            </tr>
END;
        }

        echo '</table>';
    }

    /**
     * Prints links to the various things we can administer.
     */
    public function showTabs()
    {
        $target = isset($_REQUEST['target']) ? strtolower($_REQUEST['target']) : '';

        $u_a = ($target == 'user') ? " class='active'" : "";
        $g_a = ($target == 'group') ? " class='active'" : "";
        $r_a = ($target == 'role') ? " class='active'" : "";
        $p_a = ($target == 'perm_group') ? " class='active'" : "";
        $a_a = ($target == 'app') ? " class='active'" : "";
        $rp_a = ($target == 'report') ? " class='active'" : "";
        $o_a = ($target == 'order') ? " class='active'" : "";
        $d_a = ($target == 'distrib_list') ? " class='active'" : "";
        $h_a = ($target == 'hol') ? " class='active'" : "";
        $c_a = ($target == 'cron') ? " class='active'" : "";
        $s_a = ($target == 'settings') ? " class='active'" : "";

        echo <<<END
        <h3 class='text-center'>What would you like to administer?</h3>
		<div>
            <nav class='navbar navbar-default'>
				<div class='col-md-8 col-md-offset-2'>
					<ul class='nav navbar-nav'>
		                <li$u_a>
		                    <a href="{$_SERVER['PHP_SELF']}?action=show&amp;target=user&amp;active=2">Users</a>
		                </li>
		                <li$g_a>
		                    <a href="{$_SERVER['PHP_SELF']}?action=show&amp;target=group">Groups</a>
		                </li>
		                <li$r_a>
		                    <a href="{$_SERVER['PHP_SELF']}?action=show&amp;target=role">Roles</a>
		                </li>
		                <li$p_a>
		                    <a href="{$_SERVER['PHP_SELF']}?action=show&amp;target=perm_group">Perm. Groups</a>
		                </li>
		                <li$a_a>
		                    <a href="{$_SERVER['PHP_SELF']}?action=show&amp;target=app">Applications</a>
		                </li>
		                <li$rp_a>
		                    <a href="{$_SERVER['PHP_SELF']}?action=show&amp;target=report">Reports</a>
		                </li>
		                <li$o_a>
		                    <a href="{$_SERVER['PHP_SELF']}?action=show&amp;target=order">Order Types</a>
		                </li>
		                <li$d_a>
		                    <a href="{$_SERVER['PHP_SELF']}?action=show&amp;target=distrib_list">Distrib List</a>
		                </li>
		                <li$h_a>
		                    <a href="{$_SERVER['PHP_SELF']}?action=show&amp;target=hol">Holidays</a>
		                </li>
		                <li$c_a>
		                    <a href="{$_SERVER['PHP_SELF']}?action=show&amp;target=cron">Cron</a>
		                </li>
		                <li$s_a>
		                	<a href='{$_SERVER['PHP_SELF']}?action=show&amp;target=settings'>Settings</a>
		           		</li>
		            </ul>
				</div>
			</nav>
        </div>
END;
    }

    /**
     * Show edit form
     *
     * @param string
     */
    public function ShowTConfForm($args)
    {
        $var_name = isset($args['var']) ? $args['var'] : null;
        $val = "";
        $comment = "";

        if ($var_name)
        {
            $sth = $this->dbh->prepare("SELECT val, comment FROM config WHERE \"name\"= ?");
            $sth->bindValue(1, $var_name, PDO::PARAM_STR);
            $sth->execute();
            list($val, $comment) = $sth->fetch(PDO::FETCH_NUM);
        }

        echo "
        <form action='{$_SERVER['PHP_SELF']}' method='post'>
        <input type='hidden' name='target' value='settings'>
        <input type='hidden' name='orig_name' value='{$var_name}'>
        <table class='form' cellpadding='5' cellspacing='2'>
            <tr>
                <th class='subheader' colspan='2'>Setting</th>
            </tr>
            <tr>
                <th class='subsubheader' colspan='2'>Add/Edit Entry</th>
            </tr>
            <tr>
                <th class='form'>Name:<span class='required'>*</span></th>
                <td class='form'>
                    <input type='text' name='var_name' value='{$var_name}' size='30' maxlength='128' />
                </td>
            </tr>
            <tr>
                <th class='form'>Value:<span class='required'>*</span></th>
                <td class='form'>
                    <textarea name='val' cols=80 rows=5>{$val}</textarea>
                </td>
            </tr>
			<tr>
                <th class='form'>Comment:</th>
                <td class='form'>
                    <textarea name='comment' cols=80 rows=5>{$comment}</textarea>
                </td>
            </tr>
            <tr>
                <td class='buttons' colspan='2'>
					<input type='submit' name='action' value='Save'>
				</td>
            </tr>
        </table>
        </form>";
    }

    /**
     * Display records for tconf
     */
    public function showTConfList()
    {
        $rows = "";
        $rc = "on";
        $sth = $this->dbh->prepare("SELECT
			\"name\", val, \"comment\", updated_at
		FROM config
		ORDER BY \"name\"");
        $sth->execute();
        while ($rec = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $val = htmlentities(substr($rec['val'], 0, 500), ENT_QUOTES);
            $comment = htmlentities($rec['comment'], ENT_QUOTES);

            $rows .= "<tr class='$rc'>
				<td class='txal'>{$rec['name']}</td>
				<td class='txal pwrap'><div>$val</div></td>
				<td class='txal pwrap'><div>$comment</div></td>
				<td class='txac'>{$rec['updated_at']}</td>
				<td class='txar' nowrap>
					<form action=\"{$_SERVER['PHP_SELF']}\" method='post'>
                    <input type='hidden' name='target' value='settings' />
                    <input type='hidden' name='var' value=\"{$rec['name']}\"/>
					<input type='submit' class='submit' name='action' value='Edit' />
					<input type='submit' class='submit' name='action' value='Delete' onclick=\"return confirm('Are you sure you want to delete this record?')\"; />
					</form>
				</td>
			</tr>";

            $rc = ($rc == "on") ? "off" : "on";
        }

        echo "<h3 class='text-center'>Settings:</h3>
		<table class='dt' style='width: 100%; table-layout:fixed;'>
		<thead>
			<tr>
				<th class='shdr c1'>Name</th>
				<th class='shdr c2'>Value</th>
				<th class='shdr c3'>Comment</th>
				<th class='shdr c4'>Last Modified</th>
				<th class='shdr c5'>Action</th>
			</tr>
		</thead>
		<tbody>
			$rows
			<tr>
				<td class='buttons' colspan='5'>
					<form action='{$_SERVER['PHP_SELF']}' method='post'>
					<input type='hidden' name='target' value='settings'>
					<input type='submit' name='action' value='Add'>
					</form>
				</td>
			</tr>
		</tbody>
		</table>";
    }

    /**
     * Prints the form used to edit users.
     *
     * @param integer $user_id
     * @param string $action
     */
    public function showUserForm($user_id, $action = 'Edit')
    {
        $header = $action;

        $user = new User($user_id);
        $username = htmlentities($user->getUsername(), ENT_QUOTES);
        $firstname = htmlentities($user->getFirstname(), ENT_QUOTES);
        $lastname = htmlentities($user->getLastname(), ENT_QUOTES);
        $title = htmlentities($user->getTitle(), ENT_QUOTES);
        $credentials = htmlentities($user->getCredentials(), ENT_QUOTES);
        $email = htmlentities($user->getEmail(), ENT_QUOTES);
        $accounting_cpt = htmlentities($user->getAccountingCPT(), ENT_QUOTES);
        $supervisor = $user->getSupervisor();
        $address = $this->assembleContactInfo($user);
        $extension = $user->getExt();
        $supervisor_name = $supervisor ? $supervisor->getLastname() . ", " . $supervisor->getFirstname() : "";
        $type = $user->getType();
        $hire_date = $user->getHireDate();
        $sel_type_1 = ($type == User::$SYSTEM_USER_TYPE) ? "selected" : "";
        $sel_type_3 = ($type == User::$SERVICE_ACCOUNT_TYPE) ? "selected" : "";
        $chk_active_y = ($user->isActive()) ? "checked" : "";
        $chk_active_n = ($user->isActive()) ? "" : "checked";

        $term_date_txt = '';

        if (!$user->isActive())
        {
            $term_date = $user->getTerminationDate();
            $term_date_txt = ($term_date) ?
                '<span style="font-size:small;font-weight:bold">(disabled on ' . date('m/d/Y', strtotime($term_date)) . ')</span>' :
                '<span style="font-size:small;font-weight:bold">(no term date is set)</span>';
        }

        $password_row = "";
        if (in_array($username, Config::$AUTH_BYPASS_AD) || $type == User::$SERVICE_ACCOUNT_TYPE)
        {
            $password_row = "
			<tr>
				<th>Password:</th>
				<td>
					<input type='password' name='pwd' value='' size='20' maxlength='64' autocomplete='off' />
				</td>
			</tr>
			<tr>
				<th>Password (verify):</th>
				<td>
					<input type='password' name='pwd2' value='' size='20' maxlength='64' autocomplete='off' />
				</td>
			</tr>";
        }

        // Can not change these attributes for system users
        $disabled = ($user_id && $type == User::$SYSTEM_USER_TYPE) ? "readonly" : "";

        echo "<div class='row'>
			<div class='col-md-6 col-md-offset-3'>
				<div class='box box-primary'>
					<div class='box-header with-border'>
						<h3 class='box-title'>Admin $header</h3>
					</div>
					<div class='box-body'>
					<form name='user_form' action='{$_SERVER['PHP_SELF']}' method='post' onSubmit='return validateUserForm(this)'>
					<input type='hidden' name='target' value='user'>
					<input type='hidden' name='user' value='{$user_id}'>
						<table class='table face'>
							<tr>
								<th>Username:</th>
								<td>
									<input class='form-control' type='text' name='username' value='{$username}' size='20' maxlength='64' $disabled/>
								</td>
							</tr>
							{$password_row}
							<tr>
								<th>Type:</th>
								<td>
									<select name='type'>
										<option value='1'$sel_type_1>System User</option>
										<option value='3'$sel_type_3>Service Account</option>
									</select>
								</td>
							</tr>
							<tr>
								<th>First Name:</th>
								<td>
									<input class='form-control' type='text' name='firstname' value='{$firstname}' size='20' maxlength='64' $disabled/>
								</td>
							</tr>
							<tr>
								<th>Last Name:</th>
								<td>
									<input class='form-control' type='text' name='lastname' value='{$lastname}' size='20' maxlength='64' $disabled/>
								</td>
							</tr>
							<tr>
								<th>Email:</th>
								<td>
									<input class='form-control' type='text' name='email' value='{$email}' size='20' maxlength='64' $disabled/>
								</td>
							</tr>
							<tr>
								<th>Title:</th>
								<td>
									<input class='form-control' type='text' name='title' value='{$title}' size='20' maxlength='64' $disabled/>
								</td>
							</tr>
							<tr>
								<th>Active:</th>
								<td>
									<label for='active_y'>
										<input type='radio' id='active_y' name='active' value='1' $chk_active_y>
										Yes
									</lable>
									<label for='active_n'>
										<input type='radio' id='active_n' name='active' value='0' $chk_active_n>
										No
									</lable>
									{$term_date_txt}
								</td>
							</tr>
							<tr>
								<th>Hire Date:</th>
								<td>
									{$hire_date}
								</td>
							</tr>
							<tr>
								<th>Accounting Key:</th>
								<td>
									{$accounting_cpt}
								</td>
							</tr>
							<tr>
								<th>Supervisor:</th>
								<td>
									{$supervisor_name}
								</td>
							</tr>
							<tr>
								<th>Contact Info:</th>
								<td>
									{$address}
								</td>
							</tr>
							<tr>
								<th>Credentials:</th>
								<td>{$credentials}</td>
							</tr>
							<tr>
								<th>Extension:</th>
								<td>{$extension}</td>
							</tr>
							<tr>
								<td class='buttons' colspan='2'>
									<input type='submit' name='action' value='Save'>
								</td>
							</tr>
						</table>
					</form>
					</div>
				</div>
			</div>
		</div>";
    }

    /**
     * Prints a list of groups.
     */
    public function showUserList($form = array())
    {
        if (!isset($form['active']))
            $form['active'] = 2;

        // If lastname is not set, set it
        if (!isset($form['lastname']))
            $form['lastname'] = "All";

        $page_change_loc = $_SERVER['PHP_SELF'];

        $all = ($form['active'] == 1) ? 'checked' : '';
        $active = ($form['active'] == 2) ? 'checked' : '';
        $active_term = ($form['active'] == 2) ? ' AND u.active = TRUE ' : '';
        // Get Last Name initial for query
        $getLastName = ($form['lastname'] != "All") ? ' and u.lastname like \'' . $form['lastname'] . '%\'' : '';

        $sql = "SELECT
			u.id,
			u.username,
			u.firstname,
			u.lastname,
			u.active,
			u.type,
			ARRAY_TO_STRING(ARRAY_ACCUM(DISTINCT r.name || ' in ' || u2.lastname), ',<br>') AS groups
		FROM users u
		LEFT OUTER JOIN users_groups_roles ugr ON u.id = ugr.who_id
		LEFT OUTER JOIN users u2 ON ugr.where_id = u2.id
		LEFT OUTER JOIN roles r ON ugr.role_id = r.id
		WHERE u.type in (1,3)
		$active_term
		$getLastName
		GROUP BY u.id, u.username, u.firstname, u.lastname, u.active, u.type
		ORDER BY lastname, firstname";

        $sth = $this->dbh->prepare($sql);
        $sth->execute();

        // Make List of last name initials
        $nav_bar = "Last Name:<br>";

        foreach ($this->lastName as $name)
        {
            if ($name == $form['lastname'])
                $nav_bar .= "&nbsp;$name&nbsp;";
            else
                $nav_bar .= "<a href=\"$page_change_loc?action=show&target=user&lastname=$name&active=" . $form['active'] . "\">[$name]</a>";
        }

        echo "
		<div class='row'>
			<div class='col-md-6 col-md-offset-3'>
				<div class='box box-primary box-solid'>
					<div class='box-header'>
						<h3 class='box-title'>Users</h3>
					</div>
					<div class='box-body'>
						<div style='text-align:center'>$nav_bar</div>
						<form method='GET' action='{$_SERVER['PHP_SELF']}'>Show:
							<input type='hidden' name='action' value='show'>
							<input type='hidden' name='target' value='user'>
							<input type='hidden' name='lastname' value={$form['lastname']}>
							<input type='radio' name='active' value='1' {$all} onFocus=\"if( this.checked == false ) { this.checked = true; this.form.submit() }\"/>All
							<input type='radio' name='active' value='2' {$active} onFocus=\"if( this.checked == false ) { this.checked = true; this.form.submit() }\"/>Active only
						</form>
					</div>
					<div class='box-body'>
						<!-- Begin the user list table -->
						<table class='dt table table-bordered table-striped table-condensed'>
							<tr>
								<th>First Name</th>
								<th>Last Name</th>
								<th>Roles and Groups</th>
								<th>Active</th>
								<th>Type</th>
								<th>Actions</th>
							</tr>";

        $be_this_user_button = '';
        if (isset($_GET['showbethisuser']))
            $be_this_user_button = '<input type="submit" name="action" value="Impersonate User" onClick="return confirm(\'This will log you out. Are you sure you want to be this user?\');">';

        while ($user_arr = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $active = ($user_arr['active'] == 1) ? 'Yes' : 'No';
            $type = ($user_arr['type'] == User::$SERVICE_ACCOUNT_TYPE) ? 'Service Account' : 'User';

            echo "
				<tr>
					<td>{$user_arr['firstname']}</td>
					<td>{$user_arr['lastname']}</td>
					<td>{$user_arr['groups']}</td>
					<td>{$active}</td>
					<td>{$type}</td>
					<td class='buttons'>
						<form action='{$_SERVER['PHP_SELF']}' method='get'>
							<input type='hidden' name='target' value='user'>
							<input type='hidden' name='user' value='{$user_arr['id']}'>
							<input type='submit' name='action' value='Edit'>
							{$be_this_user_button}
						</form>
					</td>
				</tr>";
        }

        echo "
						</table>
					</div>
					<div class='box-footer buttons'>
						<form action='{$_SERVER['PHP_SELF']}' method='get'>
							<input type='hidden' name='target' value='user'>
							<input type='submit' name='action' value='Add'>
						</form>
					</div>
				</div>
			</div>
		</div>";
    }

    /**
     * Assembles an address line from a User object.
     *
     * @param User $user
     * @return string
     */
    private function assembleContactInfo($user)
    {
        $info = '';

        if ($user->getAddress())
            $info .= $user->getAddress();

        if ($user->getAddress2())
            $info .= '<br>' . $user->getAddress2();

        if ($user->getCity() && $user->getState() && $user->getZip())
            $info .= '<br>' . $user->getCity() . ', ' . $user->getState() . ' ' . $user->getZip();

        if ($user->getPhone())
            $info .= '<br>' . $user->getPhone();

        return $info;
    }

    /**
     * Deletes a record from a given table.
     *
     * @param integer $id
     * @param string $table
     */
    private function delete($id, $table)
    {
        $sth = $this->dbh->prepare("DELETE FROM $table WHERE id = ?");
        $sth->bindValue(1, $id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Create a temporary crontab file
     * @param string
     */
    public function WriteCronFile($server = 'web')
    {
        $date = date('m/d/Y');
        $time = date('H:i:s');

        $lines = "#\n# This crontab file was generated for server '$server' on $date at $time\n#\n\n";
        $lines .= "SHELL=/bin/bash\n";
        $lines .= "PATH=/sbin:/bin:/usr/sbin:/usr/bin\n";
        $lines .= "MAILTO=support@company.com\n\n";

        # Find location to store the file
        $tconf = new TConfig();
        $file_loc = $tconf->get('temp_cron_location');
        if (is_null($file_loc))
            $file_loc = self::$CRON_LOCATION;

        $sth = $this->dbh->prepare("SELECT
			id, minute, hour, day, month, dow, command, comments
		FROM crontab
		WHERE active = true
		AND server = ?
		ORDER BY id");
        $sth->bindValue(1, $server, PDO::PARAM_STR);
        $sth->execute();
        while ($job = $sth->fetch(PDO::FETCH_OBJ))
        {
            $lines .= "# {$job->id}: {$job->comments}\n";
            $lines .= "{$job->minute} {$job->hour} {$job->day} {$job->month} {$job->dow} {$job->command}\n\n";
        }

        if (file_put_contents($file_loc, $lines) === false)
            throw new Exception("Unable to write cron file to $file_loc");
    }
}
?>