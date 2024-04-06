<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `location` (
  `location_id` int NOT NULL AUTO_INCREMENT,
  `short_name` varchar(45) DEFAULT NULL,
  `title` varchar(125) DEFAULT NULL,
  `role` varchar(45) DEFAULT NULL,
  `address_1` varchar(255) DEFAULT NULL,
  `address_2` varchar(255) DEFAULT NULL,
  `city` varchar(125) DEFAULT NULL,
  `state` varchar(125) DEFAULT NULL,
  `zip` varchar(25) DEFAULT NULL,
  `country` varchar(25) DEFAULT NULL,
  `phone` varchar(65) DEFAULT NULL,
  `mobile` varchar(65) DEFAULT NULL,
  `url` varchar(1024) DEFAULT NULL,
  `email` varchar(125) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `location_status` varchar(45) DEFAULT NULL,
  `location_type` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */

class Location extends CDModel {
    public $pkey;
    public $key_name = "location_id";
    protected $db_table = "location";

    public $location_id;    #` int NOT NULL AUTO_INCREMENT,
    public $short_name;     #` varchar(45) DEFAULT NULL,
    public $title;          #` varchar(125) DEFAULT NULL,
    public $role;           #` varchar(45) DEFAULT NULL,
    public $address_1;      #` varchar(255) DEFAULT NULL,
    public $address_2;      #` varchar(255) DEFAULT NULL,
    public $city;           #` varchar(125) DEFAULT NULL,
    public $state;          #` varchar(125) DEFAULT NULL,
    public $zip;            #` varchar(25) DEFAULT NULL,
    public $country;        #` varchar(25) DEFAULT NULL,
    public $phone;          #` varchar(65) DEFAULT NULL,
    public $mobile;         #` varchar(65) DEFAULT NULL,
    public $url;            #` varchar(1024) DEFAULT NULL,
    public $email;          #` varchar(125) DEFAULT NULL,
    public $description;    #` varchar(255) DEFAULT NULL,
    public $location_status = 'Active';    #` varchar(45) DEFAULT NULL,
    public $location_type;  #` varchar(45) DEFAULT NULL,
    public $created_at;     #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $created_by;     #` int DEFAULT NULL,
    public $updated_at;       #` datetime NOT NULL,
    public $updated_by;    #` int DEFAULT NULL,

    static public $STATUS_INACTIVE = "DELETED";

    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->location_id = $id;
        $this->dbh = DBSettings::DBConnection();
        $this->SetFieldArray();
        $this->Load();
    }

    public function BuildFilter($params)
    {
        if (!isset($_SESSION['crm']['locfilter']) || isset($params['crm']['locfilter']['reset']))
            $_SESSION['crm']['locfilter'] = self::DefaultFilter();

        $filter = $_SESSION['crm']['locfilter'];
        if (isset($params['location_status']))
        {
            $match = CDModel::Clean($params['location_status']);
            $filter[] = ["field" => "location_status", "op" => "eq", "match" => $match, "type" => "string"];
        }
        if (isset($params['location_type']))
        {
            $match = CDModel::Clean($params['location_type']);
            $filter[] = ["field" => "location_type", "op" => "eq", "match" => $match, "type" => "string"];
        }

        return $filter;
    }
    static public function DefaultFilter()
    {
        return [
            ["field" => "location_status", "op" => "ne", "match" => self::$STATUS_INACTIVE, "type" => "string"]
        ];
    }

    public function Delete()
    {
        if ($this->pkey)
        {
            $this->change('location_status', self::$STATUS_INACTIVE);
        }
    }

    /**
     * Update DB record
     */
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
        $this->field_array[$i++] = new DBField('short_name', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('title', PDO::PARAM_STR, true, 125);
        $this->field_array[$i++] = new DBField('role', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('address_1', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('address_2', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('city', PDO::PARAM_STR, true, 125);
        $this->field_array[$i++] = new DBField('state', PDO::PARAM_STR, true, 125);
        $this->field_array[$i++] = new DBField('zip', PDO::PARAM_STR, true, 25);
        $this->field_array[$i++] = new DBField('country', PDO::PARAM_STR, true, 25);
        $this->field_array[$i++] = new DBField('phone', PDO::PARAM_STR, true, 65);
        $this->field_array[$i++] = new DBField('mobile', PDO::PARAM_STR, true, 65);
        $this->field_array[$i++] = new DBField('url', PDO::PARAM_STR, true, 1024);
        $this->field_array[$i++] = new DBField('email', PDO::PARAM_STR, true, 125);
        $this->field_array[$i++] = new DBField('description', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('location_status', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('location_type', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('updated_by', PDO::PARAM_INT, false, 0);
    }

    static public function StatesList()
    {
        return json_decode('[
            {"val": "AL", "text": "ALABAMA"},
            {"val": "AK", "text": "ALASKA"},
            {"val": "AS", "text": "AMERICAN SAMOA"},
            {"val": "AZ", "text": "ARIZONA"},
            {"val": "AR", "text": "ARKANSAS"},
            {"val": "CA", "text": "CALIFORNIA"},
            {"val": "CO", "text": "COLORADO"},
            {"val": "CT", "text": "CONNECTICUT"},
            {"val": "DE", "text": "DELAWARE"},
            {"val": "DC", "text": "DISTRICT OF COLUMBIA"},
            {"val": "FM", "text": "FEDERATED STATES OF MICRONESIA"},
            {"val": "FL", "text": "FLORIDA"},
            {"val": "GA", "text": "GEORGIA"},
            {"val": "GU", "text": "GUAM GU"},
            {"val": "HI", "text": "HAWAII"},
            {"val": "ID", "text": "IDAHO"},
            {"val": "IL", "text": "ILLINOIS"},
            {"val": "IN", "text": "INDIANA"},
            {"val": "IA", "text": "IOWA"},
            {"val": "KS", "text": "KANSAS"},
            {"val": "KY", "text": "KENTUCKY"},
            {"val": "LA", "text": "LOUISIANA"},
            {"val": "ME", "text": "MAINE"},
            {"val": "MH", "text": "MARSHALL ISLANDS"},
            {"val": "MD", "text": "MARYLAND"},
            {"val": "MA", "text": "MASSACHUSETTS"},
            {"val": "MI", "text": "MICHIGAN"},
            {"val": "MN", "text": "MINNESOTA"},
            {"val": "MS", "text": "MISSISSIPPI"},
            {"val": "MO", "text": "MISSOURI"},
            {"val": "MT", "text": "MONTANA"},
            {"val": "NE", "text": "NEBRASKA"},
            {"val": "NV", "text": "NEVADA"},
            {"val": "NH", "text": "NEW HAMPSHIRE"},
            {"val": "NJ", "text": "NEW JERSEY"},
            {"val": "NM", "text": "NEW MEXICO"},
            {"val": "NY", "text": "NEW YORK"},
            {"val": "NC", "text": "NORTH CAROLINA"},
            {"val": "ND", "text": "NORTH DAKOTA"},
            {"val": "MP", "text": "NORTHERN MARIANA ISLANDS"},
            {"val": "OH", "text": "OHIO"},
            {"val": "OK", "text": "OKLAHOMA"},
            {"val": "OR", "text": "OREGON"},
            {"val": "PW", "text": "PALAU"},
            {"val": "PA", "text": "PENNSYLVANIA"},
            {"val": "PR", "text": "PUERTO RICO"},
            {"val": "RI", "text": "RHODE ISLAND"},
            {"val": "SC", "text": "SOUTH CAROLINA"},
            {"val": "SD", "text": "SOUTH DAKOTA"},
            {"val": "TN", "text": "TENNESSEE"},
            {"val": "TX", "text": "TEXAS"},
            {"val": "UT", "text": "UTAH"},
            {"val": "VT", "text": "VERMONT"},
            {"val": "VI", "text": "VIRGIN ISLANDS"},
            {"val": "VA", "text": "VIRGINIA"},
            {"val": "WA", "text": "WASHINGTON"},
            {"val": "WV", "text": "WEST VIRGINIA"},
            {"val": "WI", "text": "WISCONSIN"},
            {"val": "WY", "text": "WYOMING"}
        ]');
    }
}