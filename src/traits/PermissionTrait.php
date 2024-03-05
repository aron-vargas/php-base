<?php
namespace Freedom\Traits;

use PDO;
use Freedom\Models\Permission;

trait PermissionTrait
{
    public function AddModelPermission($model_id, $model_type, $permission_id)
    {
        $sth = $this->dbh->prepare("INSERT INTO role_has_permissions
        (model_id, model_type, permission_id) VALUES (?,?,?)");
        $sth->bindParam(1, $model_id, PDO::PARAM_INT);
        $sth->bindParam(2, substr($model_type, 0, 25), PDO::PARAM_INT);
        $sth->bindParam(3, $permission_id, PDO::PARAM_INT);
        $sth->execute();

        $this->permissions[] = new Permission($permission_id);
    }

    public function AddRolePermission($role_id, $permission_id)
    {
        $sth = $this->dbh->prepare("INSERT INTO role_has_permissions
        (role_id, permission_id) VALUES (?,?)");
        $sth->bindParam(1, $role_id, PDO::PARAM_INT);
        $sth->bindParam(2, $permission_id, PDO::PARAM_INT);
        $sth->execute();

        $this->permissions[] = new Permission($permission_id);
    }
    public function SetPermission($id, $permission)
    {
        $this->permissions[$id] = $permission;
    }

    protected function LoadModelPermissions(int $model_id, bool $reload = false)
    {
        if ($reload || empty($this->permissions))
        {
            $this->permissions = array();

            $sth = $this->dbh->prepare('SELECT
                permission_id 
            FROM role_has_permissions j
            WHERE j.role_id = p.id');
            while ($data = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->permissions[] = new Permission($data->permission_id);
            }
        }
    }

    protected function LoadRolePermissions(int $role_id, bool $reload = false)
    {
        if ($reload || empty($this->permissions))
        {
            $this->permissions = array();

            $sth = $this->dbh->prepare('SELECT
                permission_id 
            FROM role_has_permissions j
            WHERE j.role_id = p.id');
            while ($data = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->permissions[] = new Permission($data->permission_id);
            }
        }
    }

    public function HasPermission(string $name)
    {
        foreach ($this->permissions as $permission)
        {
            if ($permission->name == $name)
                return true;
        }

        // Supper Admin have all permissions
        if (method_exists($this, "HasRole") && $this->HasRole('Super-Admin'))
           return true;

        return false;
    }
}