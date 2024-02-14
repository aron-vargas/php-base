<?php
# 1 Day = 86400 seconds
define('ONE_DAY', 86400);

class CalendarModel extends CDModel
{
    public $pkey;                   # integer
    public $key_name = "user_id";   # string
	protected $db_table = "event";   # string

    /**
     * Create a new instanace
     */
    public function __construct($user_id = null)
    {
        $this->SetFieldArray();
    }

    public function isWeekend($timestamp)
    {
        $the_day = date("w", $timestamp);

        return ($the_day == 0 || $the_day == 6) ? true : flase;
    }

    public function isHoliday($timestamp)
    {
        global $dbh;
        $sth = $dbh->prepare("SELECT 1 FROM holidays WHERE timestamp = ?");
        $sth->bindValue(1, strtotime(date("Y-m-d", $timestamp)), PDO::PARAM_INT);
        $sth->execute();
        $is_holiday = $sth->fetchColumn();

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