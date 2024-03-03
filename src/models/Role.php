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