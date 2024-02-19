<?php

/**
 * 
CREATE TABLE `event` (
  `event_id` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(512) DEFAULT NULL,
  `created_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `last_mod` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_mod_by` int NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `event_type` varchar(45) NOT NULL DEFAULT 'Public',
  `event_status` varchar(45) NOT NULL DEFAULT 'NEW',
  `location_id` int DEFAULT NULL,
  `location_info` varchar(255) DEFAULT NULL,
  `performer_id` int DEFAULT NULL,
  `orginizer_id` int DEFAULT NULL,
  `source_id` int DEFAULT NULL,
  `source_type` varchar(45) DEFAULT NULL,
  `active` tinyint NOT NULL DEFAULT '1',
  `reminder_count` int DEFAULT NULL,
  `reminder_interval` int DEFAULT NULL,
  `reminder_unit` varchar(45) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `content` text,
  `url` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */
class CalEvent extends CDModel
{
    public $pkey;
    public $key_name = "event_id";
    protected $db_table = "calendar_event";

    public $event_id;       #` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    public $title;          #` varchar(255) DEFAULT NULL,
    public $description;    #` varchar(512) DEFAULT NULL,
    public $created_on;     #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $created_by;     #` int NOT NULL,
    public $last_mod;       #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $last_mod_by;    #` int NOT NULL,
    public $start_date;     #` datetime NOT NULL,
    public $end_date;       #` datetime NOT NULL,
    public $event_type = 'Public';  #` varchar(45) NOT NULL DEFAULT 'Public',
    public $event_status = 'NEW';   #` varchar(45) NOT NULL DEFAULT 'NEW',
    public $location_id;    #` int DEFAULT NULL,
    public $location_info;  #` varchar(255) DEFAULT NULL,
    public $performer_id;   #` int DEFAULT NULL,
    public $orginizer_id;   #` int DEFAULT NULL,
    public $source_id;      #` int DEFAULT NULL,
    public $source_type;    #` varchar(45) DEFAULT NULL,
    public $active = 1;     #` tinyint NOT NULL DEFAULT '1',
    public $reminder_count; #` int DEFAULT NULL,
    public $reminder_interval;   #` int DEFAULT NULL,
    public $reminder_unit;  #` varchar(45) DEFAULT NULL,
    public $scheduled_at;   #` datetime DEFAULT NULL,
    public $content;        #` text,
    public $url;            #` varchar(1024) DEFAULT NULL,

    protected $location;

    public static $DEFAULT_INTERVAL = "1 hour";

    public function __construct($id = null, $sart_date = null)
    {
        $this->pkey = $id;
        $this->event_id = $id;
        $this->start_date = $sart_date;
        $DEFAULT_INTERVAL = self::$DEFAULT_INTERVAL;
        $this->end_date = date("Y-m-d", strtotime("+{$DEFAULT_INTERVAL}",$sart_date));
    }

    private function SetFieldArray()
    {
        $i = 0;
		$this->field_array[$i++] = new DBField('event_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('title', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('description', PDO::PARAM_STR, true, 512);
        $this->field_array[$i++] = new DBField('created_on', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('last_mod_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('start_date', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('end_date', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('event_type', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('event_status', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('location_id', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('location_info', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('performer_id', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('orginizer_id', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('source_id', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('source_type', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('active', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('reminder_count', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('reminder_interval', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('reminder_unit', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('scheduled_at', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('content', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('url', PDO::PARAM_STR, true, 1024);
    }
}