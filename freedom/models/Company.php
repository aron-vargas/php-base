<?php

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
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */
class Company extends CDModel {
    public $key_name = "pkey";
    protected $db_table = "company";

    public $pkey;                   #` int NOT NULL AUTO_INCREMENT,
    protected $company_name;        #` varchar(125) NOT NULL,
    protected $primary_address_id;  #` int DEFAULT NULL,
    protected $shipping_address_id; #` int DEFAULT NULL,
    protected $billing_address_id;  #` int DEFAULT NULL,
    protected $created_on;          #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    protected $created_by;          #` int NOT NULL,
    protected $last_mod;            #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    protected $last_mod_by;         #` int NOT NULL,

    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->{$this->key_name} = $id;
        $this->dbh = DBSettings::DBConnection();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('pkey', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('company_name', PDO::PARAM_STR, false, 125);
        $this->field_array[$i++] = new DBField('primary_address_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('shipping_address_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('billing_address_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('created_on', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('last_mod_by', PDO::PARAM_INT, false, 0);
    }
}