<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

class Permission extends CDModel {
    public $pkey;
    public $key_name = "id";
    protected $db_table = "permissions";

    public $id;     # bigint UN AI PK
    public $name;     # varchar(255)
    public $guard_name = "web";     # varchar(255)
    public $created_at;     # timestamp
    public $updated_at;     # timestamp

    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->{$this->key_name} = $id;
        $this->dbh = DBSettings::DBConnection();
        $this->SetFieldArray();
        $this->Load();
    }

    public function Save()
    {
        if (empty($this->created_at))
            $this->created_at = date("c");

        $this->updated_at = date("c");

        if ($this->pkey)
            $this->db_update();
        else
            $this->db_insert();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('name', PDO::PARAM_STR, false, 255);
        $this->field_array[$i++] = new DBField('guard_name', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
    }
}

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