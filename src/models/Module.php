<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;
use Freedom\Traits\PermissionTrait;

/**
 *
CREATE TABLE `module` (
  `pkey` int unsigned NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `modual_status` VARCHAR(64) NOT NULL DEFAULT 'Active',
  `hidden` TINYINT NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL
    REFERENCES user (user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int NOT NULL
    REFERENCES user (user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO module (name, created_by, updated_by)
VALUES ('admin', 1, 1);
INSERT INTO module (name, created_by, updated_by)
VALUES ('crm', 1, 1);
INSERT INTO module (name, created_by, updated_by)
VALUES ('calendar', 1, 1);
INSERT INTO module (name, created_by, updated_by)
VALUES ('blog', 1, 1);
 */
class Module extends CDModel
{
    use PermissionTrait;

    public $pkey;                # integer
    public $key_name = "pkey";   # string
    protected $db_table = "module";   # string
    #{{ClassFields}}
    public $created_at;     #', PDO::PARAM_STR, false, 0);
    public $created_by;     #` int NOT NULL
    public $updated_at;     #', PDO::PARAM_STR, false, 0);
    public $updated_by;     #` int NOT NULL

    public $name;       # VARCHAR NOT NULL
    public $modual_status = 'Active';       # VARCHAR NOT NULL  DEFAULT 'Active'
    public $hidden = '0';       # TINYINT NOT NULL  DEFAULT '0'

    static public $STATUS_ACTIVE = "ACTIVE";
    static public $STATUS_INACTIVE = "INACTIVE";

    /**
     * Create a new instance
     * @param integer $pkey
     */
    public function __construct($pkey = null)
    {
        $this->pkey = $pkey;
        $this->{$this->key_name} = $pkey;
        $this->dbh = DBSettings::DBConnection();
        $this->SetFieldArray();
        $this->Load();
    }

    static public function DefaultFilter()
    {
        return [
            ["field" => "modual_status", "op" => "ne", "match" => "DELETED", "type" => "string"]
        ];
    }

    static public function OptionList($selected = null)
    {
        $list = User::GetAll("module", self::DefaultFilter());

        $option_list = "";

        foreach ($list as $module)
        {
            $sel = ($selected == $module->pkey) ? " selected" : "";

            $className = ($module->modual_status == self::$STATUS_INACTIVE) ? "active" : "inactive";

            if ($module->modual_status == self::$STATUS_ACTIVE || $sel)
                $option_list .= "<option class='{$className}' value='{$module->pkey}'{$sel}>{$module->name}</option>";
        }

        return $option_list;
    }

    private function SetFieldArray()
    {
        $i = 0;
        #{{FieldArray}}
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('updated_by', PDO::PARAM_INT, false, 0);

        $this->field_array[$i++] = new DBField('name', PDO::PARAM_STR, false, 255);
        $this->field_array[$i++] = new DBField('modual_status', PDO::PARAM_STR, false, 64);
        $this->field_array[$i++] = new DBField('hidden', PDO::PARAM_INT, false, 0);
    }
}

