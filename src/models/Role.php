<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

class Role extends CDModel {
    public $pkey;
    public $key_name = "id";
    protected $db_table = "roles";

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

    public function HasRole(string $name)
    {
        foreach ($this->roles as $role)
        {
            if (strtolower($role->name) == strtolower($name))
                return true;
        }

        return false;
    }
}