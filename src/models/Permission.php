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
    public $module_name;

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
    public function Copy($assoc)
    {
        parent::Copy($assoc);

        if (isset($assoc['rights']))
            $this->rights = (int)$assoc['rights'];
        else #if (in_array(array('view_rights', 'edit_rights', 'add_rights', 'delete_rights'), array_keys($assoc)))
        {
            # Do some bit arithmetic
            $view_right = (isset($assoc['view_rights'])) ? $assoc['view_rights'] & self::$VIEW_PERM : 0;
            $edit_right = (isset ($assoc['edit_rights'])) ? $assoc['edit_rights'] & self::$EDIT_PERM : 0;
            $add_right = (isset ($assoc['add_rights'])) ? $assoc['add_rights'] & self::$ADD_PERM : 0;
            $delete_right = (isset ($assoc['delete_rights'])) ? $assoc['delete_rights'] & self::$DELETE_PERM : 0;

            $this->rights = ($view_right | $edit_right | $add_right | $delete_right);
        }
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
     * Find all records matching the field value
     *
     * @param string $table_name
     * @param mixed $filter
     * @return \StdClass[] | null
     */
    static public function GetALL($table_name, $filter)
    {
        $dbh = DBSettings::DBConnection();

        if ($dbh)
        {
            $table_name = self::Clean($table_name);
            $AND_WHERE = self::ParseFilter($filter);
            //echo "SELECT * FROM {$table_name} {$AND_WHERE}";
            $sth = $dbh->query("SELECT
                p.*,
                g.user_name as \"group_name\",
                m.name as \"module_name\"
            FROM permission p
            INNER JOIN user g on p.group_id = g.user_id
            INNER JOIN module m on p.module_id = m.pkey
            {$AND_WHERE}");
            $sth->execute();
            return $sth->fetchALL(PDO::FETCH_OBJ);
        }

        return null;
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
            $this->created_by = (int)$user_id;

        $this->updated_at = date("c");
        $this->updated_by = (int)$user_id;

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
