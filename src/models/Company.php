<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `company` (
  `pkey` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(125) NOT NULL,
  `primary_address_id` int DEFAULT NULL,
  `shipping_address_id` int DEFAULT NULL,
  `billing_address_id` int DEFAULT NULL,
  `created_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `last_mod` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_mod_by` int NOT NULL,
  `status` varchar(125) NOT NULL,
  `description` mediumtext NOT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */
class Company extends CDModel {
    public $key_name = "pkey";
    protected $db_table = "company";

    public $pkey;                   #` int NOT NULL AUTO_INCREMENT,
    public $company_name;        #` varchar(125) NOT NULL,
    public $primary_address_id;  #` int DEFAULT NULL,
    public $shipping_address_id; #` int DEFAULT NULL,
    public $billing_address_id;  #` int DEFAULT NULL,
    public $created_on;          #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $created_by;          #` int NOT NULL,
    public $last_mod;            #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $last_mod_by;         #` int NOT NULL,
    public $status = "ACTIVE";   # varchar(125) DFAULT ACTIVE
    public $description;            # meduimtext

    static public $STATUS_INACTIVE = "DELETED";

    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->{$this->key_name} = $id;
        $this->SetFieldArray();
        $this->dbh = DBSettings::DBConnection();
        $this->Load();
    }

    public function BuildFilter($params)
    {
        if (!isset($_SESSION['crm']['cofilter']))
            $_SESSION['crm']['cofilter'] = Company::DefaultFilter();

        $filter = $_SESSION['crm']['cofilter'];
        if (isset($params['status']))
        {
            $match = CDModel::Clean($params['status']);
            $filter[] = ["field" => "status", "op" => "eq", "match" => $match, "type" => "string"];
        }

        return $filter;
    }
    static public function DefaultFilter()
    {
        return [
            ["field" => "status", "op" => "ne", "match" => self::$STATUS_INACTIVE, "type" => "string"]
        ];
    }

    public function Delete()
    {
        if ($this->pkey)
        {
            $this->change('status', self::$STATUS_INACTIVE);
        }
    }

    /**
     * Update DB record
     */
    public function Save()
    {
        $user_id = ($this->container) ? $this->container->get("session")->user->pkey : 1;

        if (empty($this->created_on))
            $this->created_on = date("c");
        if (empty($this->created_by))
            $this->created_by = $user_id;

        $this->last_mod = date("c");
        $this->last_mod_by = $user_id;

        if ($this->pkey)
            $this->db_update();
        else
            $this->db_insert();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('company_name', PDO::PARAM_STR, false, 125);
        $this->field_array[$i++] = new DBField('primary_address_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('shipping_address_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('billing_address_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('created_on', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('last_mod_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('status', PDO::PARAM_STR, false, 125);
        $this->field_array[$i++] = new DBField('description', PDO::PARAM_STR, true, 1025);
    }
}