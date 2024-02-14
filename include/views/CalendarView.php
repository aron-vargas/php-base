<?php

class CalendarView extends CDView
{
	public $header = "include/templates/header.php";
	public $template = "include/templates/calendar.php";
	public $footer = "include/templates/footer.php";

	public $message;

	public $view = "m";		# string [m,w,ww,d,e]
	public $cur_date;		# integer (unix timestamp)
	public $sel_date;      # integer (unix timestamp)
	public $css = array();
	public $js = array();

	private $debug = false;

    /**
     * Create a new instance
     * @param CDModel
     */
	public function __construct($model = null)
	{
		$this->css['main'] = "<link rel='stylesheet' type='text/css' href='style/main.css' media='all'>";
		$this->css['cal'] = "<link rel='stylesheet' type='text/css' href='style/calendar.css' media='all'>";
		$this->css['bootstrap'] = "<link rel='stylesheet' type='text/css' href='vendor/twbs/bootstrap/dist/css/bootstrap.min.css' media='all'>";
		$this->css['jquery-ui'] = "<link rel='stylesheet' type='text/css' href='vendor/components/jqueryui/themes/base/all.css' media='all'>";
        $this->css['fa'] = "<link rel='stylesheet' type='text/css' href='vendor/components/font-awesome/css/all.css' media='all'>";

		$this->js['bootstrap'] = "<script type='text/javascript' src='vendor/twbs/bootstrap/dist/js/bootstrap.min.js'></script>";
		$this->js['jquery'] = "<script type='text/javascript' src='vendor/components/jquery/jquery.min.js'></script>";
		$this->js['jquery-ui'] = "<script type='text/javascript' src='vendor/components/jqueryui/jquery-ui.min.js'></script>";
		$this->js['cal'] = "<script type='text/javascript' src='js/calendar.js'></script>";

        $this->model = $model;
	}

    public function ClassList($base, $datetime)
    {
        $class_list = $base;
        $class_list .= ($this->model->isWeekend($datetime)) ? " weekend" : "";
        $class_list .= ($this->model->isHoliday($datetime)) ? " holiday" : "";
        $class_list .= ($this->model->isToday($datetime)) ? " today" : "";
        $class_list .= ($this->model->isSelected($this->sel_date, $datetime)) ?" selected" : "";

        return $class_list;
    }

    public function render_body()
	{
		if ($this->message)
			echo "\n<div class='alert alert-secondary w-50 mx-auto my-1'><p>{$this->message}</p></div>\n";

        $CalView = $this;
        $Cal = $this->model;

        if ($this->view == "d")
            echo $this->DayView();
        else if ($this->view == "w")
            echo $this->WeekView(true);
        else if ($this->view == "ww")
            echo $this->WeekView(false);
        else if ($this->view == "e")
            include("include/templates/calendar_event.php");
        else
            echo $this->MonthView();
	}

    private function EventJS($start, $end)
    {
        return <<<JS
<script type="text/javascript">
var strart = {$start};
var end = {$end};
SetEvents(start, end);
SetTasks(start, end);
</script>
JS;
    }

     private function Navigation()
    {
        $selected_month = date('M', $this->sel_date);
        $selected_year = date('Y', $this->sel_date);
        $view_options = $this->ViewOptions($this->view);

        return <<<NAV
        <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <div class="navbar-nav">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#left-nav" aria-controls="left-nav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <i class='fa fa-calendar'></i>
                <div class='h2'>Calendar</div>
                <a class="nav-link btn btn-sm" href="?act=today">Today</a>
                <a class="nav-link" href="?act=prev">&lt;</a>
                <a class="nav-link" href="?act=next">&gt;</a>
                <span class='nav-text'>{$selected_month} {$selected_year}</span>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#search" aria-controls="search" aria-expanded="false" aria-label="Search">
                    <i class='fa fa-search'></i>
                </button>
                <a class="nav-link" href="?v=support">?</a>
                <a class="nav-link" href="?v=settings"><i class='fa fa-gear'></i></a>
                <select class='view-select'>
                    {$view_options}
                </select>
            </div>
        </div>
    </nav>
    <div class='left-nav'></div>
NAV;
    }

    private function DayView()
    {
        $the_first = strtotime("00:00:00", $this->sel_date);
        $the_last = strtotime("24:59:59", $this->sel_date);

        // create day cells
        $calendar_day_cells = "
        <div id='wv-gutter'>
            <div class='cal-header'>
                <div class='hrd'>&nbsp;</div>
                <div class'cell-num'>{&nbsp;}</div>
            </div>
            {$this->TimeCells($this->sel_date, "dv-cell gutter")}
        </div>";

        // Add Cells for this week
        $day_text = date("D", $this->sel_date);
        $day_num = date("j", $this->sel_date);
        $class_list = $this->ClassList("wv-cell", $this->sel_date);

        $calendar_day_cells .= "<div id='cell-{$this->sel_date}' class='{$class_list}'>
            <div class='cal-header'>
                <div class='hrd'>{$day_text}</div>
                <div class'cell-num'>{$day_num}</div>
            </div>
            {$this->TimeCells($this->sel_date, "dv-cell")}
        </div>";

        echo <<<CAL
<div class='ca>
    {$this->Navigation()}
    <div class='cal-cont'>
        <div class="cal-days">
            $calendar_day_cells
        </div>
        <div class='new-item'>
            <select id='new-item-sel' onChange='ShowNewItemDialog(this)'>
                <option value='event'>New Event</option>
                <option value='event'>New Task</option>
            </select>
        </div>
    </div>
</div>
CAL;

        $html .= $this->EventJS($the_first, $the_last);

        return $html;
    }

    private function WeekView($SHOW_WEEKENDS = true)
    {
        $today = date("w", $this->sel_date);
        $last = 6 - $today;

        if ($SHOW_WEEKENDS)
        {
            $the_last = strtotime("+{$last } Days", $this->sel_date);
            $the_first = strtotime("-{$today} Days", $this->sel_date);
        }
        else
        {
            $the_first = strtotime("-{$today} Days", $this->sel_date) + ONE_DAY;
            $the_last = strtotime("+{$last } Days", $this->sel_date) - ONE_DAY;
        }

        // create day cells
        $gutter_timecells = $this->TimeCells($the_first, "wv-cell gutter");
        $calendar_day_cells = "
        <div id='wv-gutter'>
            <div class='cal-header'>
                <div class='hrd'>&nbsp;</div>
                <div class'cell-num'>{&nbsp;}</div>
            </div>
            $gutter_timecells
        </div>";

        // Add Cells for this week
        for($datetime = $the_first; $datetime < $the_last; $datetime += ONE_DAY)
        {
            $day_text = date("D", $datetime);
            $day_num = date("j", $datetime);
            $class_list = $this->ClassList("wv-cell", $datetime);

            $calendar_day_cells .= "<div id='cell-{$datetime}' class='{$class_list}'>
                <div class='cal-header'>
                    <div class='hrd'>{$day_text}</div>
                    <div class'cell-num'>{$day_num}</div>
                </div>
                {$this->TimeCells($datetime, "wv-cell")}
            </div>";
        }

        echo <<<CAL
<div class='ca>
    {$this->Navigation()}
    <div class='cal-cont'>
        <div class="cal-days">
            $calendar_day_cells
        </div>
        <div class='new-item'>
            <select id='new-item-sel' onChange='ShowNewItemDialog(this)'>
                <option value='event'>New Event</option>
                <option value='event'>New Task</option>
            </select>
        </div>
    </div>
</div>
CAL;

        $html .= $this->EventJS($the_first, $the_last);

        return $html;
    }

    private function MonthView()
    {
        $the_first = strtotime(date("Y-m-01", $this->sel_date));
        $end = strtotime(date("t", $this->sel_date));
        $the_last = strtotime(date("Y-m-{$end}}", $this->sel_date));

        // create day cells
        $calendar_day_cells = "";

        // Add Cells for the previous month up to the first of the current month
        for($day = date("w", $the_first); $day > 0; $day--)
        {
            # Find -X number of days ago
            $datetime = strtotime("-{$day} Days", $the_first);
            $day_num = date("j", $datetime);
            $class_list = $this->ClassList("dv-cell prev", $datetime);

            $calendar_day_cells .= "<div id='cell-{$datetime}' class='{$class_list}'>
                <div class'cell-num'>{$day_num}</div>
            </div>";
        }

        // Add Cells for this month
        for($datetime = $the_first; $datetime <= $the_last; $datetime += ONE_DAY)
        {
            $day_num = date("j", $datetime);
            $class_list = $this->ClassList("dv-cell", $datetime);

            $calendar_day_cells .= "<div id='cell-{$datetime}' class='{$class_list}'>
                <div class'cell-num'>{$day_num}</div>
            </div>";
        }

        // Add Cells for the next month up to Saturday
        for($datetime = $the_last + ONE_DAY; date("w", $datetime) < 7; $datetime += ONE_DAY)
        {
            $day_num = date("j", $datetime);
            $class_list = $this->ClassList("dv-cell next", $datetime);

            $calendar_day_cells .= "<div id='cell-{$datetime}' class='{$class_list}'>
                <div class'cell-num'>{$day_num}</div>
            </div>";
        }

        $html = <<<CAL
<div class='ca>
    {$this->Navigation()}
    <div class='cal-cont'>
        <div class='cal-header'>
            <div class='col hrd'>Sun</div>
            <div class='col hrd'>Mon</div>
            <div class='col hrd'>Tues</div>
            <div class='col hrd'>Wed</div>
            <div class='col hrd'>Thu</div>
            <div class='col hrd'>Fri</div>
            <div class='col hrd'>Sat</div>
        </div>
        <div class="cal-days">
            $calendar_day_cells
        <div>
        <div class='new-item'>
            <select id='new-item-sel' onChange='ShowNewItemDialog(this)'>
                <option value='event'>New Event</option>
                <option value='event'>New Task</option>
            </select>
        </div>
    </div>
</div>
CAL;

        $html .= $this->EventJS($the_first, $the_last);

        return $html;
    }

    private function TimeCells($datetime, $base_class)
    {
        $hr = $this->start_hour;
        $start = strtotime("$hr:00", $datetime);
        $end = strtotime("{$this->end_hour}}:00", $datetime);

        $cells = "";
        for($time = $start, $time < $end; $time += $this->time_slot;)
        {
            $class_list = $this->ClassList($base_class, $datetime);
            $cells .= "<div id='$time' class='{$class_list}'></div>";
        }

        return $cells;
    }

    private function GutterCells($datetime, $base_class)
    {
        $hr = $this->start_hour;
        $start = strtotime("$hr:00", $datetime);
        $end = strtotime("{$this->end_hour}}:00", $datetime);

        $cells = "";
        for($time = $start, $time < $end; $time += $this->time_slot;)
        {
            $class_list = $this->ClassList($base_class, $datetime);
            $time_text = date("g:i A", $time);
            $cells .= "<div id='$time' class='{$class_list}'>{$time_text}</div>";
        }

        return $cells;
    }

    public function ViewOptions($sel_view = "m")
    {
        if (empty($sel_view)) $sel_view = $this->view;

        $opt_ary = [
            (object)[ "val" => 'm', "text" => "Month" ],
            (object)[ "val" => 'w', "text" => "Week"],
            (object)[ "val" => 'ww', "text" => "WorkWeek"],
            (object)[ "val" => 'd', "text" => "Day"]
        ];

        $options = "";
        foreach($opt_ary as $opt)
        {
            $sel = ($opt->val == $sel_view) ? " selected" : "";
            $options .= "<options value='{$opt->val}'{$sel}>{$opt->text}</options>";
        }

        return $options;
    }
}
