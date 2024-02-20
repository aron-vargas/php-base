<?php

/**
 * 
CREATE TABLE `profile_link` (
  `profile_link_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `link_status` varchar(45) DEFAULT NULL,
  `link_type` varchar(45) DEFAULT NULL,
  `address` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`profile_link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */

class ProfileLink extends CDModel
{
    public $pkey;
    public $key_name = "profile_link_id";
    protected $db_table = "profile_link";

    public $profile_link_id;    #` int NOT NULL AUTO_INCREMENT,
    public $user_id;            #` int NOT NULL,
    public $link_status;        #` varchar(45) DEFAULT NULL,
    public $link_type;          #` varchar(45) DEFAULT NULL,
    public $address;            #` varchar(1024) DEFAULT NULL,
    
    public function __construct($id = null)
    {
        $this->{$this->key_name} = $id;
        $this->profile_link_id = $id;
    }

    private function SetFieldArray()
    {
        $i = 0;
		$this->field_array[$i++] = new DBField('profile_link_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('user_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('link_status', PDO::PARAM_STR, false, 45);
        $this->field_array[$i++] = new DBField('link_type', PDO::PARAM_STR, false, 45);
        $this->field_array[$i++] = new DBField('address', PDO::PARAM_STR, true, 1024);
    }
}