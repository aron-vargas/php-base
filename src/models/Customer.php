<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `customer` (
  `pkey` int NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(125) NOT NULL,
  `primary_address_id` int DEFAULT NULL,
  `shipping_address_id` int DEFAULT NULL,
  `billing_address_id` int DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `customer_status` varchar(65) NOT NULL DEFAULT 'NEW',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int NOT NULL,
  `customer_type` varchar(125),
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */
class Customer extends CDModel {
    public $pkey;
    public $key_name = "pkey";
    protected $db_table = "customer";

    public $customer_name;            #` varchar(125) NOT NULL,
    public $account_code;            # varchar(45) NOT NULL,
    public $customer_type = "Other"; # varchar(125)
    public $location_id;             # int,
    public $primary_address_id;      #` int DEFAULT NULL,
    public $shipping_address_id;     #` int DEFAULT NULL,
    public $billing_address_id;      #` int DEFAULT NULL,
    public $description;             #` varchar(512) DEFAULT NULL,
    public $customer_status;         #` varchar(65) NOT NULL DEFAULT 'NEW',
    public $created_at;              #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $created_by;              #` int NOT NULL,
    public $updated_at;                #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $updated_by;             #` int NOT NULL,

    public $primary_address;
    public $shipping_address;
    public $billing_address;

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
        //if (!isset($_SESSION['crm']['cusfilter']))
        $_SESSION['crm']['cusfilter'] = self::DefaultFilter();

        $filter = $_SESSION['crm']['cusfilter'];
        if (isset($params['customer_status']))
        {
            $match = CDModel::Clean($params['customer_status']);
            $filter[] = ["field" => "customer_status", "op" => "eq", "match" => $match, "type" => "string"];
        }

        return $filter;
    }

    public function Copy($assoc)
    {
        if (isset($assoc['primary_address']))
            $this->primary_address->Copy($assoc['primary_address']);
        unset($assoc['primary_address']);

        if (isset($assoc['billing_address']))
            $this->billing_address->Copy($assoc['billing_address']);
        unset($assoc['billing_address']);

        if (isset($assoc['shipping_address']))
            $this->shipping_address->Copy($assoc['shipping_address']);
        unset($assoc['shipping_address']);

        parent::Copy($assoc);
    }

    static public function DefaultFilter()
    {
        return [
            ["field" => "customer_status", "op" => "ne", "match" => self::$STATUS_INACTIVE, "type" => "string"]
        ];
    }

    public function Delete()
    {
        if ($this->pkey)
        {
            $this->change('customer_status', self::$STATUS_INACTIVE);
        }
    }

    private function GenerateAccountCode()
    {
        $base = substr($this->customer_name, 0, 3);
        $l = strlen($base);
        while ($l < 3)
        {
            $base .= "X";
            $l = strlen($base);
        }

        $sth = $this->dbh->query("SELET count(*) FROM customer WHERE account_code like '$base%' ");
        $num = (int) $sth->fetchColumn();

        $num = (string) ($num + 1);
        $l = strlen($num);
        while ($l < 3)
        {
            $num = "0{$num}";
            $l = strlen($num);
        }

        return $base . $num;
    }

    public function Load()
    {
        parent::Load();

        $this->primary_address = new Location($this->primary_address_id);
        $this->shipping_address = new Location($this->shipping_address_id);
        $this->billing_address = new Location($this->billing_address_id);
    }

    /**
     * Update DB record
     */
    public function Save()
    {
        $user_id = ($this->container) ? $this->container->get("session")->user->pkey : 1;

        if (empty($this->account_code))
            $this->GenerateAccountCode();

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

        // Update the Address/Locations
        $this->primary_address->Save();
        if (empty($this->primary_address_id))
        {
            $this->Change("primary_address_id", $this->primary_address->location_id);
            $this->AddMsg("Set primary_address_id: {$this->primary_address->location_id}");
        }
        $this->shipping_address->Save();
        if (empty($this->shipping_address_id))
        {
            $this->change("shipping_address_id", $this->shipping_address->location_id);
        }
        $this->billing_address->Save();
        if (empty($this->billing_address_id))
        {
            $this->change("billing_address_id", $this->billing_address->location_id);
        }
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('customer_name', PDO::PARAM_STR, false, 125);
        $this->field_array[$i++] = new DBField('account_code', PDO::PARAM_STR, false, 45);
        $this->field_array[$i++] = new DBField('customer_type', PDO::PARAM_STR, false, 125);
        $this->field_array[$i++] = new DBField('location_id', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('primary_address_id', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('shipping_address_id', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('billing_address_id', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('description', PDO::PARAM_STR, true, 512);
        $this->field_array[$i++] = new DBField('customer_status', PDO::PARAM_STR, false, 65);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('updated_by', PDO::PARAM_INT, false, 0);
    }

    static public function StatusOptions()
    {
        $opt_ary = [
            (object) ["val" => "Active", "text" => "Active"],
            (object) ["val" => "InActive", "text" => "InActive"],
            (object) ["val" => "Suspended", "text" => "Suspended"]
        ];

        return $opt_ary;
    }
}