<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

# 1 Day = 86400 seconds
define('ONE_DAY', 86400);

class CalendarModel extends CDModel {
    public $pkey;                   # integer
    public $key_name = "pkey";  # string
    protected $db_table = "event";  # string

    protected $_cal;                   # Object : calendar settings

    /**
     * Create a new instanace
     */
    public function __construct($user_id = null)
    {
        $this->SetFieldArray();

        // Initialize
        if (!isset($_SESSION["cal"]))
        {
            $_SESSION["cal"] = $this->GetDefaults(strtotime("today"));
            $this->InitDates();
        }

        $this->_cal = $_SESSION["cal"];
        $this->dbh = DBSettings::DBConnection();
    }

    /**
     * Set the cal data structure
     * @param integer
     * @return StdClass
     */
    private function GetDefaults($sel_date)
    {
        return json_decode("{
            \"sel_date\": $sel_date,
            \"today\": $sel_date,
            \"month_start\": $sel_date,
            \"month_first\": $sel_date,
            \"month_last\": $sel_date,
            \"month_end\": $sel_date,
            \"week_start\": $sel_date,
            \"week_end\": $sel_date,
            \"work_week_start\": $sel_date,
            \"work_week_end\": $sel_date,
            \"view\": \"m\",
            \"start_hour\": 6,
            \"end_hour\": 20,
            \"work_start\": 8,
            \"work_end\": 18,
            \"time_slot\": 3600
        }");
    }

    public function GetMyEvents($from, $to)
    {
        $data = null;
        $dbh = $this->dbh;
        $user = $this->container->get('session')->user;

        if ($user->user_id)
        {
            /*
            $this->AddMsg("SELECT
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
            WHERE (e.created_by = {$controller->user->user_id} OR e.orginizer_id = {$controller->user->user_id} OR e.performer_id = {$controller->user->user_id}) AND e.start_date BETWEEN '{$from}' AND '{$to}'");
            */

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
            $sth->bindValue(1, $controller->user->user_id, PDO::PARAM_INT);
            $sth->bindValue(2, $controller->user->user_id, PDO::PARAM_INT);
            $sth->bindValue(3, $controller->user->user_id, PDO::PARAM_INT);
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
            throw new Exception("User Not Authorized", 401);
        }

        return $data;
    }

    /**
     * (Re)Calculate dates based on view and sel_date
     */
    private function InitDates()
    {
        // Initialize the session settings
        $_SESSION['cal']->month_first = strtotime(date("Y-m-01", $_SESSION['cal']->sel_date));
        $day = date("w", $_SESSION['cal']->month_first);
        $_SESSION['cal']->month_start = strtotime("-{$day} Days", $_SESSION['cal']->month_first);

        $end = date("t", $_SESSION['cal']->sel_date);
        $_SESSION['cal']->month_last = strtotime(date("Y-m-{$end}", $_SESSION['cal']->sel_date));
        $_SESSION['cal']->month_end = strtotime("Saturday", $_SESSION['cal']->month_last);

        $day = date("w", $_SESSION['cal']->sel_date);
        $_SESSION['cal']->week_start = strtotime("-{$day} Days", $_SESSION['cal']->sel_date);
        $_SESSION['cal']->week_end = strtotime("Saturday", $_SESSION['cal']->sel_date);

        $day = date("w", $_SESSION['cal']->sel_date) - 1;
        $_SESSION['cal']->work_week_start = strtotime("-{$day} Days", $_SESSION['cal']->sel_date);
        $_SESSION['cal']->work_week_end = strtotime("Friday", $_SESSION['cal']->work_week_start);
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

    static public function isToday($timestamp)
    {
        $date = date("Ymd", $timestamp);
        $today = date("Ymd");
        return($date == $today);
    }

    static public function isSelected($selected, $timestamp)
    {
        $date = date("Ymd", $timestamp);
        $today = date("Ymd", $selected);
        return($date == $today);
    }

    public function Next()
    {
        if ($this->_cal->view == 'm')
            $this->_cal->sel_date = strtotime("+1 Month", $this->_cal->sel_date);
        else if ($this->_cal->view == "w")
            $this->_cal->sel_date = strtotime("+1 Week", $this->_cal->sel_date);
        else if ($this->_cal->view == "ww")
            $this->_cal->sel_date = strtotime("+1 Week", $this->_cal->sel_date);
        else if ($this->_cal->view == "d")
            $this->_cal->sel_date = strtotime("+1 Day", $this->_cal->sel_date);
        $this->InitDates();
    }
    public function Prev()
    {
        if ($this->_cal->view == 'm')
            $this->_cal->sel_date = strtotime("-1 Month", $this->_cal->sel_date);
        else if ($this->_cal->view == "w")
            $this->_cal->sel_date = strtotime("-1 Week", $this->_cal->sel_date);
        else if ($this->_cal->view == "ww")
            $this->_cal->sel_date = strtotime("-1 Week", $this->_cal->sel_date);
        else if ($this->_cal->view == "d")
            $this->_cal->sel_date = strtotime("-1 Day", $this->_cal->sel_date);
        $this->InitDates();
    }

    public function Reset($sel_date)
    {
        $_SESSION['cal'] = $this->GetDefaults($sel_date);
        $this->_cal = $_SESSION['cal'];
        $this->InitDates();
    }

    public function SelectDate($new_date)
    {
        $this->_cal->sel_date = $new_date;
        $this->InitDates();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('user_type', PDO::PARAM_STR, false, 32);
        $this->field_array[$i++] = new DBField('user_name', PDO::PARAM_STR, false, 32);
        $this->field_array[$i++] = new DBField('first_name', PDO::PARAM_STR, false, 64);
        $this->field_array[$i++] = new DBField('last_name', PDO::PARAM_STR, false, 64);
        $this->field_array[$i++] = new DBField('nick_name', PDO::PARAM_STR, false, 45);
        $this->field_array[$i++] = new DBField('email', PDO::PARAM_STR, true, 128);
        $this->field_array[$i++] = new DBField('phone', PDO::PARAM_STR, true, 32);
        $this->field_array[$i++] = new DBField('status', PDO::PARAM_STR, false, 32);
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('avatar', PDO::PARAM_STR, true, 45);
    }
}