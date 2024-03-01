<?php

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
  `created_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `last_mod` datetime NOT NULL,
  `last_mod_by` int DEFAULT NULL,
  PRIMARY KEY (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */

class Location extends CDModel
{
    public $pkey;
    public $key_name = "location_id";
    protected $db_table = "db_table_name";

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
    public $location_status;    #` varchar(45) DEFAULT NULL,
    public $location_type;  #` varchar(45) DEFAULT NULL,
    public $created_on;     #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $created_by;     #` int DEFAULT NULL,
    public $last_mod;       #` datetime NOT NULL,
    public $last_mod_by;    #` int DEFAULT NULL,

    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->location_id = $id;
        $this->dbh = DBSettings::DBConnection();
    }

    private function SetFieldArray()
    {
        $i = 0;
		$this->field_array[$i++] = new DBField('location_id', PDO::PARAM_INT, false, 0);
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
        $this->field_array[$i++] = new DBField('created_on', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('last_mod_by', PDO::PARAM_INT, false, 0);
    }
}