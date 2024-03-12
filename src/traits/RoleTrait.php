<?php
namespace Freedom\Traits;

use PDO;
use Freedom\Models\Role;

trait RoleTrait
{
    public function AddUserRole($user_id, $role_id)
    {
        $sth = $this->dbh->prepare("INSERT INTO use_role_join
        (user_id, role_id) VALUES (?,?)");
        $sth->bindParam(1, $user_id, PDO::PARAM_INT);
        $sth->bindParam(2, $role_id, PDO::PARAM_INT);
        $sth->execute();

        $this->roles[$role_id] = new Role($role_id);
    }
    public function SetRole($id, $role)
    {
        $this->role[$id] = $role;
    }

    protected function LoadUserRoles(bool $reload = false)
    {
        if ($reload || empty($this->permissions))
        {
            $this->permissions = array();

            $sth = $this->dbh->prepare('SELECT
                role_id
            FROM user_role_join j
            WHERE j.user_id = ?');
            while ($data = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->roles[] = new Role($data->role_id);
            }
        }
    }

    public function HasRole(string $name)
    {
        if (!empty($this->roles))
        {
            foreach ($this->roles as $role)
            {
                if (strtolower($role->name) == strtolower($name))
                    return true;
            }
        }

        return false;
    }
}