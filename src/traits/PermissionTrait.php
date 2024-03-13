<?php
namespace Freedom\Traits;

use PDO;
use Freedom\Models\Permission;

trait PermissionTrait {

    public function AddPermission($role_id, $module_id, $rights)
    {
       $perm = new Permission();
       $perm->group_id = $role_id;
       $perm->module_id = $module_id;
       $perm->rights = $rights;
       $perm->Save();

        $this->permissions[$perm->pkey] = $perm;
    }

    public function GetPermission(int $group_id, int $module_id)
    {
        if (!empty($this->permissions))
        {
            foreach ($this->permissions as $permission)
            {
                if ($permission->group_id == $group_id && $permission->module_id == $module_id)
                    return $permission;
            }
        }

        return null;
    }

    public function GetRights(int $group_id, int $module_id)
    {
        // Supper Admin have all permissions
        if (method_exists($this, "HasRole") && $this->HasRole('Super-Admin'))
            return (Permission::$VIEW_PERM | Permission::$EDIT_PERM | Permission::$ADD_PERM | Permission::$DELETE_PERM);

        if (!empty($this->permissions))
        {
            foreach ($this->permissions as $permission)
            {
                if ($permission->group_id == $group_id && $permission->module_id == $module_id)
                    return $permission->rights;
            }
        }

        return 0;
    }

    public function SetPermission($id, $permission)
    {
        $this->permissions[$id] = $permission;
    }

    public function LoadModulePermissions(int $model_id, bool $reload = false)
    {
        if ($reload || empty($this->permissions))
        {
            $this->permissions = array();

            $sth = $this->dbh->prepare('SELECT
                pkey
            FROM permission j
            WHERE j.module_id = ?');
            $sth->execute(array($model_id));
            while ($data = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->permissions[$data->pkey] = new Permission($data->pkey);
            }
        }
    }

    public function LoadRolePermissions(int $role_id, bool $reload = false)
    {
        if ($reload || empty($this->permissions))
        {
            $this->permissions = array();

            $sth = $this->dbh->prepare('SELECT
                pkey
            FROM permission j
            WHERE j.group_id = ?');
            $sth->execute(array($role_id));
            while ($data = $sth->fetch(PDO::FETCH_OBJ))
            {
                $this->permissions[$data->pkey] = new Permission($data->pkey);
            }
        }
    }

    public function HasPermission(int $group_id, int $module_id)
    {
        if (!empty($this->permissions))
        {
            foreach ($this->permissions as $permission)
            {
                if ($permission->group_id == $group_id && $permission->module_id == $module_id)
                    return true;
            }
        }

        // Supper Admin have all permissions
        if (method_exists($this, "HasRole") && $this->HasRole('Super-Admin'))
            return true;

        return false;
    }

    public function RMGroupPermissions()
    {
        $sth = $this->dbh->prepare('DELETE FROM permission j WHERE j.group_id = ?');
        $sth->execute(array($this->pkey));
        $this->permissions = array();
    }
}