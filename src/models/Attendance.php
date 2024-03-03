<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `attendance` (
  `attendance_id` int NOT NULL AUTO_INCREMENT,
  `status` varchar(45) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `short_name` varchar(45) DEFAULT NULL,
  `description` int DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `last_mod` datetime DEFAULT NULL,
  `confirmation` varchar(65) DEFAULT NULL,
  `attendee_id` int DEFAULT NULL,
  PRIMARY KEY (`attendance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */

class Attendace extends CDModel {
    public $pkey;
    public $key_name = "attendace_id";
    protected $db_table = "attendance";

    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->{$this->key_name} = $id;
        $this->dbh = DBSettings::DBConnection();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('attendance_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('status', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('type', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('short_name', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('description', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('created_on', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('confirmation', PDO::PARAM_STR, true, 65);
        $this->field_array[$i++] = new DBField('attendee_id', PDO::PARAM_INT, false, 0);
    }
}