<?php
namespace Freedom\Traits;

use PDO;
use Freedom\Models\UserGroup;

/**
 *
CREATE TABLE `user_role_join` (
  `user_id` bigint unsigned NOT NULL
	REFERENCES user (user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  `role_id` bigint NOT NULL
	REFERENCES user (user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 */

trait RoleTrait
{
    public function AddUserRole($user_id, $role_id)
    {
        $sth = $this->dbh->prepare("INSERT INTO user_role_join
        (user_id, role_id) VALUES (?,?)");
        $sth->bindParam(1, $user_id, PDO::PARAM_INT);
        $sth->bindParam(2, $role_id, PDO::PARAM_INT);
        $sth->execute();

        $this->roles[$role_id] = new UserGroup($role_id);
    }

    public function SetRole($id, $role)
    {
        $this->roles[$id] = $role;
    }

    protected function LoadUserRoles(bool $reload = false)
    {
        if ($reload || empty($this->roles))
        {
            $this->roles = array();

            $sth = $this->dbh->prepare('SELECT
                role_id
            FROM user_role_join j
            WHERE j.user_id = ?');
            $sth->bindParam(1, $this->user_id, PDO::PARAM_INT);
            $sth->execute();
            while ($data = $sth->fetch(PDO::FETCH_OBJ))
            {
                $this->roles[$data->role_id] = new UserGroup($data->role_id);
            }
        }
    }

    public function HasRole(string $name)
    {
        if (!empty($this->roles))
        {
            foreach ($this->roles as $role)
            {
                if (strtolower($role->user_name) == strtolower($name))
                    return true;
            }
        }

        return false;
    }

    public function RMUserRoles()
    {
        $sth = $this->dbh->prepare('DELETE FROM user_role_join j WHERE j.user_id = ?');
        $sth->execute(array($this->pkey));
        $this->roles = array();
    }
}