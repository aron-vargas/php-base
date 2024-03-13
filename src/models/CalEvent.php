<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `event` (
  `pkey` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(512) DEFAULT NULL,
  `created_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `last_mod` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_mod_by` int NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `all_day` tinyint NOT NULL DEFAULT '0',
  `private` tinyint NOT NULL DEFAULT '1',
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
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE `holidays` (
  `pkey` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(125) NOT NULL DEFAULT 'Holiday',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */
class CalEvent extends CDModel {
    public $pkey;
    public $key_name = "pkey";
    protected $db_table = "event";

    public $title;          #` varchar(255) DEFAULT NULL,
    public $description;    #` varchar(512) DEFAULT NULL,
    public $created_on;     #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $created_by;     #` int NOT NULL,
    public $last_mod;       #` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $last_mod_by;    #` int NOT NULL,
    public $start_date;     #` datetime NOT NULL,
    public $end_date;       #` datetime NOT NULL,
    public $all_day;        #` tinyint NOT NULL DEFAULT '0',

    public $private = 1;        # ` tinyint NOT NULL DEFAULT '1',
    public $event_type = 'Event';  #` varchar(45) NOT NULL DEFAULT 'Event',
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

    public function __construct($pkey = null, $start_date = null)
    {
        $this->pkey = $pkey;
        $this->start_date = ($start_date) ? CDModel::ParseTStamp($start_date) : date("Y-m-d H:00");
        $DEFAULT_INTERVAL = self::$DEFAULT_INTERVAL;
        $this->end_date = date("Y-m-d H:00", strtotime("+{$DEFAULT_INTERVAL}", strtotime($this->start_date)));
        $this->dbh = DBSettings::DBConnection();
        $this->SetFieldArray();
        $this->Load();
    }

    /**
     * Copy attributes from array
     * @param array
     */
    public function Copy($assoc)
    {
        parent::Copy($assoc);

        # Add/Append time components
        if (isset($assoc['start_time']))
        {
            $this->start_date = $this->GetDate('start_date') . " " . $assoc['start_time'];
        }
        if (isset($assoc['end_time']))
        {
            $this->end_date = $this->GetDate('end_date') . " " . $assoc['end_time'];
        }
    }

    public function GetMyEvents($from, $to)
    {
        $data = null;
        $dbh = $this->dbh;
        $user = $this->container->get('session')->user;

        if ($user->user_id)
        {
            $from = CDModel::ParseTStamp($from);
            $to = CDModel::ParseTStamp($to);
            $sth = $dbh->prepare("SELECT
                e.*,
                c.first_name as creator_first_name,
                c.last_name as creator_last_name,
                o.first_name as orginizer_first_name,
                o.last_name as orginizer_last_name,
                p.first_name as performer_first_name,
                p.last_name as performer_last_name
            FROM event e
            LEFT JOIN user c on e.created_by = c.user_id
            LEFT JOIN user o on e.orginizer_id = c.user_id
            LEFT JOIN user p on e.performer_id = c.user_id
            WHERE (e.created_by = ? OR e.orginizer_id = ? OR e.performer_id = ?) AND e.start_date BETWEEN ? AND ?");
            $sth->bindValue(1, $user->user_id, PDO::PARAM_INT);
            $sth->bindValue(2, $user->user_id, PDO::PARAM_INT);
            $sth->bindValue(3, $user->user_id, PDO::PARAM_INT);
            $sth->bindValue(4, $from, PDO::PARAM_STR);
            $sth->bindValue(5, $to, PDO::PARAM_STR);
            $sth->execute();
            while ($rec = $sth->fetch(PDO::FETCH_OBJ))
            {
                $rec->id = "event-{$rec->pkey}";
                $rec->cell_id = '#cell-' . date("Ymd", strtotime($rec->start_date));
                # in minutes
                $start_time = strtotime($rec->start_date);
                $rec->start_time = date("H", $start_time) * 60 + date("i", $start_time);
                $end_time = strtotime($rec->end_date);
                $rec->end_time = date("H", $end_time) * 60 + date("i", $end_time);
                $rec->duration = ($rec->end_time - $rec->start_time);
                $data[] = $rec;
            }
        }
        else
        {
            throw new \Exception("User Not Authorized", 401);
        }

        return $data;
    }

    static public function isWeekend($timestamp)
    {
        $the_day = date("w", $timestamp);

        return($the_day == 0 || $the_day == 6) ? true : false;
    }

    static public function isHoliday($timestamp)
    {
        $dbh = DBSettings::DBConnection();

        $is_holiday = false;

        if ($dbh)
        {
            $sth = $dbh->prepare("SELECT 1 FROM holidays WHERE timestamp = ?");
            $sth->bindValue(1, strtotime(date("Y-m-d", $timestamp)), PDO::PARAM_INT);
            $sth->execute();
            $is_holiday = $sth->fetchColumn();
        }

        return (boolean) $is_holiday;
    }

    static public function isSelected($selected, $timestamp)
    {
        $date = date("Ymd", $timestamp);
        $today = date("Ymd", $selected);
        return($date == $today);
    }

    static public function isToday($timestamp)
    {
        $date = date("Ymd", $timestamp);
        $today = date("Ymd");
        return($date == $today);
    }

    public function Save()
    {
        if ($this->pkey)
            $this->db_update();
        else
            $this->db_insert();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('pkey', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('title', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('description', PDO::PARAM_STR, true, 512);
        $this->field_array[$i++] = new DBField('created_on', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('last_mod_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('start_date', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('end_date', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('all_day', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('private', PDO::PARAM_INT, false, 0);
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

    /**
     * Check validity of this record
     */
    public function Validate()
    {
        $user = $this->container->get('session')->user;

        if (empty($user) || empty($user->user_id))
        {
            $this->AddMsg("Please login to create calendar events");
            return false;
        }

        $this->last_mod_by = ($user->user_id) ? $user->user_id : 1;
        $this->last_mod = date("Y-m-d H:i:s");

        if (!$this->created_by)
            $this->created_by = ($user->user_id) ? $user->user_id : 1;

        if (!$this->created_on)
            $this->created_on = date("Y-m-d H:i:s");

        return true;
    }

    public function CreatedBy()
    {
        return new User($this->created_by);
    }

    public function ModifiedBy()
    {
        return new User($this->last_mod_by);
    }

    public function Orginizer()
    {
        $ret = false;

        if ($this->orginizer_id)
            $ret = new User($this->orginizer_id);

        return $ret;
    }

    public function Performer()
    {
        $ret = false;

        if ($this->performer_id)
            $ret = new User($this->performer_id);

        return $ret;
    }
}