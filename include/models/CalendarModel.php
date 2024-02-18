<?php
# 1 Day = 86400 seconds
define('ONE_DAY', 86400);

class CalendarModel extends CDModel
{
    public $pkey;                   # integer
    public $key_name = "event_id";  # string
	protected $db_table = "event";  # string

    private $_cal;                   # Object : calendar settings

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
    }

    /**
	 * Perform requestion action
	 * @param string
	 * @param mixed
	 */
    public function ActionHandler($action, $req)
    {
        $pkey = (isset($req['pkey'])) ? self::Clean($req['pkey']) : null;

        if ($action == 'save')
        {
            $event = new CalEvent($pkey);
            $event->Copy($req);
            $event->Save();
        }
        else if ($action == 'create')
        {
            $event = new CalEvent($pkey);
            $event->Copy($req);
            if ($event->Validate())
            {
                $event->Create();
            }
        }
        else if ($action == 'change')
        {
            $field = (isset($req['field'])) ? self::Clean($req['field']) : null;
            $value = (isset($req['value'])) ? self::Clean($req['value']) : null;

            $event = new CalEvent($pkey);
            $event->Change($field, $value);
        }
        else if ($action == 'delete')
        {
            $event = new CalEvent($pkey);
            $event->Delete();
        }
        else if ($action == 'reset')
        {
            $sel_date = strtotime("today");
            $_SESSION['cal'] = $this->GetDefaults($sel_date);
            $this->_cal = $_SESSION['cal'];
        }
        else if ($action == 'today')
        {
            $this->_cal->sel_date = strtotime('today');
        }
        else if ($action == 'sel')
        {
            if (isset($req['date']))
            {
                $this->_cal->sel_date = self::Clean($req['date']);
            }
        }
        else if ($action == 'prev')
        {
            if ($this->_cal->view == 'm')
                $this->_cal->sel_date = strtotime("-1 Month", $this->_cal->sel_date);
            else if ($this->_cal->view == "w")
                $this->_cal->sel_date = strtotime("-1 Week", $this->_cal->sel_date);
            else if ($this->_cal->view == "ww")
                $this->_cal->sel_date = strtotime("-1 Week", $this->_cal->sel_date);
            else if ($this->_cal->view == "d")
                $this->_cal->sel_date = strtotime("-1 Day", $this->_cal->sel_date);
        }
        else if ($action == 'next')
        {
            if ($this->_cal->view == 'm')
                $this->_cal->sel_date = strtotime("+1 Month", $this->_cal->sel_date);
            else if ($this->_cal->view == "w")
                $this->_cal->sel_date = strtotime("+1 Week", $this->_cal->sel_date);
            else if ($this->_cal->view == "ww")
                $this->_cal->sel_date = strtotime("+1 Week", $this->_cal->sel_date);
            else if ($this->_cal->view == "d")
                $this->_cal->sel_date = strtotime("+1 Day", $this->_cal->sel_date);
        }
        else if ($action == 'set_view')
        {
            if (isset($req['view']))
                $this->_cal->view = self::Clean($req['view']);
        }

        $this->InitDates();
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
            \"time_slot\": 900
        }");
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
        $_SESSION['cal']->month_end = strtotime("Saturday",  $_SESSION['cal']->month_last);

        $day = date("w", $_SESSION['cal']->sel_date);
        $_SESSION['cal']->week_start = strtotime("-{$day} Days", $_SESSION['cal']->sel_date);
        $_SESSION['cal']->week_end = strtotime("Saturday", $_SESSION['cal']->sel_date);

        $day = date("w", $_SESSION['cal']->sel_date) - 1;
        $_SESSION['cal']->work_week_start = strtotime("-{$day} Days", $_SESSION['cal']->sel_date);
        $_SESSION['cal']->work_week_end = strtotime("Friday", $_SESSION['cal']->work_week_start);
    }

    public function isWeekend($timestamp)
    {
        $the_day = date("w", $timestamp);

        return ($the_day == 0 || $the_day == 6) ? true : false;
    }

    public function isHoliday($timestamp)
    {
        $dbh = $_SESSION['APPSESSION']->dbh;

        $is_holiday = false;

        if ($dbh)
        {
            $sth = $dbh->prepare("SELECT 1 FROM holidays WHERE timestamp = ?");
            $sth->bindValue(1, strtotime(date("Y-m-d", $timestamp)), PDO::PARAM_INT);
            $sth->execute();
            $is_holiday = $sth->fetchColumn();
        }

        return (boolean)$is_holiday;
    }

    public function isToday($timestamp)
    {
        $date = date("Ymd", $timestamp);
        $today = date("Ymd");
        return ($date == $today);
    }

    public function isSelected($selected, $timestamp)
    {
        $date = date("Ymd", $timestamp);
        $today = date("Ymd", $selected);
        return ($date == $today);
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