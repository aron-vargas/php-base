<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
CREATE TABLE `permission` (
  `pkey` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `module_id` int NOT NULL,
  `rights` tinyint NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int NOT NULL
    REFERENCES users(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int NOT NULL
    REFERENCES users(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY (`pkey`),
  UNIQUE KEY `permission_group_id_module_id_unique` (`group_id`,`module_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
 */
class Permission extends CDModel {
    public $pkey;
    public $key_name = "pkey";
    protected $db_table = "permission";

    public $group_id;   # int
    public $module_id;  # int
    public $rights = 0;   # int
    public $created_at;     # timestamp
    public $created_by;     # int
    public $updated_at;     # timestamp
    public $updated_by;     # int

    public $group_name;
    public $model_name;

    static public $VIEW_PERM = 1;
    static public $EDIT_PERM = 1 << 2;
    static public $ADD_PERM = 1 << 3;
    static public $DELETE_PERM = 1 << 4;

    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->{$this->key_name} = $id;
        $this->dbh = DBSettings::DBConnection();
        $this->SetFieldArray();
        $this->Load();
    }

    static public function DefaultFilter()
    {
        return [];
    }

    static public function GetAllPermissions()
    {
        return self::GetAll("permission", null);
    }

    /**
     * Set attribute values from DB record
     */
    public function Load()
    {
        if ($this->pkey)
        {
            $sth = $this->dbh->prepare("SELECT
                p.*,
                g.user_name as \"group_name\",
                m.name as \"module_name\"
            FROM permission p
            INNER JOIN user g on p.group_id = g.user_id
            INNER JOIN module m on p.module_id = m.pkey
            WHERE p.pkey = ?");
            $sth->bindValue(1, $this->pkey, PDO::PARAM_INT);
            $sth->execute();
            $rec = $sth->fetch(PDO::FETCH_ASSOC);
            $this->Copy($rec);
        }
    }

    public function Save()
    {
        $user_id = ($this->container) ? $this->container->get("session")->user->pkey : 1;

        if (empty($this->created_at))
            $this->created_at = date("c");
        if (empty($this->created_by))
            $this->created_by = $user_id;

        $this->updated_at = date("c");
        $this->updated_by = $user_id;

        if ($this->pkey)
            $this->db_update();
        else
            $this->db_insert();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('group_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('module_id', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('rights', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('updated_by', PDO::PARAM_INT, false, 0);
    }
}
