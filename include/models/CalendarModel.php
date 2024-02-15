<?php
# 1 Day = 86400 seconds
define('ONE_DAY', 86400);

class CalendarModel extends CDModel
{
    public $pkey;                   # integer
    public $key_name = "event_id";   # string
	protected $db_table = "event";   # string

    public $sel_date;    # unix timestamp
    public $today;    # unix timestamp
    public $view_start_date;     # unix timestamp
    public $view_last_date;      # unix timestamp
    public $cal_start_date;      # unix timestamp
    public $cal_end_date;        # unix timestamp
    public $view;                # string  "m,w,ww,d",
    public $start_hour;          # integer
    public $end_hour;            # integer
    public $work_start;          # integer
    public $work_end;            # integer

    /**
     * Create a new instanace
     */
    public function __construct($user_id = null)
    {
        $this->SetFieldArray();

        // Initialize
        if (!isset($_SESSION["cal"]))
        {
            $this->sel_date = strtotime("today");
            $defaults = $this->GetDefaults($this->sel_date);
            $_SESSION["cal"] = $defaults;
            $this->Copy($defaults);
        }
        else
        {
            $this->Copy($_SESSION["cal"]);
        }
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
        else if ($action == 'today')
        {
            $this->sel_date = strtotime('today');
        }
        else if ($action == 'prev')
        {
            if ($this->view == 'm')
                $this->sel_date = strtotime("-1 Month", $this->sel_date);
            else if ($this->view == "w")
                $this->sel_date = strtotime("-1 Week", $this->sel_date);
            else if ($this->view == "ww")
                $this->sel_date = strtotime("-1 Week", $this->sel_date);
            else if ($this->view == "d")
                $this->sel_date = strtotime("-1 Day", $this->sel_date);
        }
        else if ($action == 'next')
        {
            if ($this->view == 'm')
                $this->sel_date = strtotime("-1 Month", $this->sel_date);
            else if ($this->view == "w")
                $this->sel_date = strtotime("-1 Week", $this->sel_date);
            else if ($this->view == "ww")
                $this->sel_date = strtotime("-1 Week", $this->sel_date);
            else if ($this->view == "d")
                $this->sel_date = strtotime("-1 Day", $this->sel_date);
        }
        else if ($action == 'set_view')
        {
            if (isset($req['view']))
                $this->view = self::Clean($req['view']);
        }

        $this->InitDates();
    }

    private function GetDefaults($sel_date)
    {
        $view_start_date = strtotime(date("Y-m-01", $sel_date));
        $cal_start_date = strtotime("Sunday", $view_start_date);

        $end = date("t", $sel_date);
        $view_last_date = strtotime(date("Y-m-{$end}", $sel_date));
        $cal_end_date = strtotime("Saturday",  $view_last_date);

        $defaults = array(
            "sel_date" => $sel_date,
            "today" => $sel_date,
            "view_start_date" => $view_start_date,
            "view_last_date" => $view_last_date,
            "cal_start_date" => $cal_start_date,
            "cal_end_date" => $cal_end_date,
            "view" => "m",
            "start_hour" => 6,
            "end_hour" => 20,
            "work_start" => 8,
            "work_end" => 18,
        );

        return $defaults;
    }

    /**
     * (Re)Calculate dates based on view and sel_date
     */
    private function InitDates()
    {
        // Initialize the session settings
        if ($this->view == 'm')
        {
            $this->view_start_date = strtotime(date("Y-m-01", $this->sel_date));
            $day = date("w", $this->view_start_date);
            $this->cal_start_date = strtotime("-{$day} Days", $this->view_start_date);

            $end = date("t", $this->sel_date);
            $this->view_last_date = strtotime(date("Y-m-{$end}", $this->sel_date));
            $this->cal_end_date = strtotime("Saturday",  $this->view_last_date);
        }
        else if ($this->view == "w")
        {
            $day = date("w", $this->sel_date);
            $this->view_start_date = strtotime("-{$day} Days", $this->sel_date);
            $this->cal_start_date = $this->view_start_date;

            $this->view_last_date = strtotime("Saturday", $this->sel_date);
            $this->cal_end_date = $this->view_last_date;
        }
        else if ($this->view == "ww")
        {
            $day = date("w", $this->sel_date) - 1;
            $this->view_start_date = strtotime("-{$day} Days", $this->sel_date);
            $this->cal_start_date = $this->view_start_date;

            $this->view_last_date = strtotime("Friday", $this->sel_date);
            $this->cal_end_date = $this->view_last_date;
        }
        else if ($this->view == "d")
        {
            $this->view_start_date = strtotime("00:00:00", $this->sel_date);
            $this->cal_start_date = $this->view_start_date;

            $this->view_last_date = strtotime("23:59:59", $this->sel_date);
            $this->cal_end_date = $this->view_last_date;
        }

        // Copy new values into the session
        foreach(array_keys($_SESSION["cal"]) AS $key)
        {
            $_SESSION["cal"][$key] = $this->{$key};
        }
    }

    public function isWeekend($timestamp)
    {
        $the_day = date("w", $timestamp);

        return ($the_day == 0 || $the_day == 6) ? true : false;
    }

    public function isHoliday($timestamp)
    {
        global $dbh;

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