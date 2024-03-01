<?php

/**
 *
CREATE TABLE `customer` (
  `pkey` int NOT NULL AUTO_INCREMENT,
  `custome_name` varchar(125) NOT NULL,
  `primary_address_id` int DEFAULT NULL,
  `shipping_address_id` int DEFAULT NULL,
  `billing_address_id` int DEFAULT NULL,
  `description` varchar(512) DEFAULT NULL,
  `customer_status` varchar(65) NOT NULL DEFAULT 'NEW',
  `created_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `last_mod` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_mod_by` int NOT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */
class Customer extends CDModel {
    public $pkey;
    public $key_name = "pkey";
    protected $db_table = "db_table_name";

    protected $custome_name;            #` varchar(125) NOT NULL,
    protected $primary_address_id;      #` int DEFAULT NULL,
    protected $shipping_address_id;     #` int DEFAULT NULL,
    protected $billing_address_id;      #` int DEFAULT NULL,
    protected $description;             #` varchar(512) DEFAULT NULL,
    protected $customer_status;         #` varchar(65) NOT NULL DEFAULT 'NEW',
    protected $created_on;              #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    protected $created_by;              #` int NOT NULL,
    protected $last_mod;                #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    protected $last_mod_by;             #` int NOT NULL,

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
        $this->field_array[$i++] = new DBField('custome_name', PDO::PARAM_STR, false, 125);
        $this->field_array[$i++] = new DBField('primary_address_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('shipping_address_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('billing_address_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('description', PDO::PARAM_STR, true, 512);
        $this->field_array[$i++] = new DBField('customer_status', PDO::PARAM_STR, false, 65);
        $this->field_array[$i++] = new DBField('created_on', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('last_mod_by', PDO::PARAM_INT, false, 0);
    }
}