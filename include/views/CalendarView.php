<?php

class CalendarView extends CDView
{
	public $template = "include/templates/calendar.php";

	public $message;

	public $css = array();
	public $js = array();

	private $debug = false;

    private $time_slot = 900;   # integer (900 = 15 min * 60 sec/min)

    /**
     * Create a new instance
     * @param CDModel
     */
	public function __construct($model = null)
	{
        $this->css['main'] = "<link rel='stylesheet' type='text/css' href='style/main.css' media='all'>";
		$this->css['bootstrap'] = "<link rel='stylesheet' type='text/css' href='vendor/twbs/bootstrap/dist/css/bootstrap.min.css' media='all'>";
		$this->css['jquery-ui'] = "<link rel='stylesheet' type='text/css' href='vendor/components/jqueryui/themes/base/all.css' media='all'>";
		$this->css['fa'] = "<link rel='stylesheet' type='text/css' href='vendor/components/font-awesome/css/all.css' media='all'>";
		
		$this->js['bootstrap'] = "<script type='text/javascript' src='vendor/twbs/bootstrap/dist/js/bootstrap.min.js'></script>";
		$this->js['jquery'] = "<script type='text/javascript' src='vendor/components/jquery/jquery.min.js'></script>";
		$this->js['jquery-ui'] = "<script type='text/javascript' src='vendor/components/jqueryui/jquery-ui.min.js'></script>";
		

		$this->css['cal'] = "<link rel='stylesheet' type='text/css' href='style/calendar.css' media='all'>";
		
		$this->js['cal'] = "<script type='text/javascript' src='js/calendar.js'></script>";

        $this->model = $model;
	}

    public function ClassList($base, $datetime)
    {
        $class_list = $base;
        $class_list .= ($this->model->isWeekend($datetime)) ? " weekend" : "";
        $class_list .= ($this->model->isHoliday($datetime)) ? " holiday" : "";
        $class_list .= ($this->model->isToday($datetime)) ? " today" : "";

        if ($this->model->view == "m")
        {
            if ($datetime < $this->model->view_start_date) $class_list .= " prev";
            if ($datetime > $this->model->view_last_date) $class_list .= " next";
        }
        else
        {
            if (date("h", $datetime) < $this->model->work_start) $class_list .= " prev";
            if (date("h", $datetime) > $this->model->work_end) $class_list .= " next";
        }
        $class_list .= ($this->model->isSelected($this->model->sel_date, $datetime)) ?" selected" : "";

        return $class_list;
    }

    public function render_body()
	{
		if ($this->message)
			echo "\n<div class='alert alert-secondary w-50 mx-auto my-1'><p>{$this->message}</p></div>\n";

        $Cal = $this->model;

        if ($Cal->view == "d")
            echo $this->DayView();
        else if ($Cal->view == "w")
            echo $this->WeekView();
        else if ($Cal->view == "ww")
            echo $this->WeekView();
        else if ($Cal->view == "e")
            include("include/templates/calendar_event.php");
        else
            echo $this->MonthView();

        echo $this->EventJS($Cal->view_start_date, $Cal->view_last_date);
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
        $selected_month = date('M', $this->model->sel_date);
        $selected_year = date('Y', $this->model->sel_date);
        $view_options = $this->ViewOptions($this->model->view);

        return <<<NAV
        <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
          
                <div class="nav-item">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#left-nav" aria-controls="left-nav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
                <div class="nav-item">
                    <div class='h4'><i class='fa fa-calendar'></i> Calendar</div>
                </div>
                <div class="nav-item">
                    <a class="button btn btn-sm btn-outline" href="?act=today">Today</a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="?act=prev">&lt;</a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="?act=next">&gt;</a>
                </div>
                <div class="nav-item">
                    <span class='nav-text'>{$selected_month} {$selected_year}</span>
                </div>
                <div class="nav-item">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#search" aria-controls="search" aria-expanded="false" aria-label="Search">
                        <i class='fa fa-search'></i>
                    </button>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="?v=support">?</a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="?v=settings"><i class='fa fa-gear'></i></a>
                </div>
                <div class="nav-item">
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
        // Add Cells for this week
        $day_text = date("D", $this->model->sel_date);
        $day_num = date("j", $this->model->sel_date);
        $class_list = $this->ClassList("wv-cell", $this->model->sel_date);

        return <<<CAL
<div class='cal'>
    {$this->Navigation()}
    <div class='cal-cont'>
        <div class="cal-days">
            <div id='wv-gutter'>
                <div class='cal-header'>
                    <div class='hrd'>&nbsp;</div>
                    <div class'cell-num'>{&nbsp;}</div>
                </div>
                {$this->TimeCells($this->model->sel_date, "dv-cell gutter")}
            </div>
        </div>
        <div id='cell-{$this->model->sel_date}' class='{$class_list}'>
            <div class='cal-header'>
                <div class='hrd'>{$day_text}</div>
                <div class'cell-num'>{$day_num}</div>
            </div>
            {$this->TimeCells($this->model->sel_date, "dv-cell")}
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
    }

    private function WeekView()
    {
        // create day cells
        $calendar_day_cells = "
        <div class='cal-days'>
            <div id='wv-gutter'>
                <div class='cal-header'>
                    <div class='hrd'>&nbsp;</div>
                    <div class'cell-num'>&nbsp;</div>
                </div>
                {$this->TimeCells($this->model->sel_date, "wv-cell gutter")}
            </div>
        </div>";

        echo "<div> Start: ";
        echo date("Y-m-d", $this->model->cal_start_date);
        echo "End: ";
        echo date("Y-m-d", $this->model->cal_end_date);
        echo "</div>";

        echo "<div> Start: ";
        echo date("Y-m-d", $this->model->view_start_date);
        echo "End: ";
        echo date("Y-m-d", $this->model->view_last_date);
        echo "</div>";

        // Add Cells for this week
        $datetime = $this->model->cal_start_date;
        while($datetime <= $this->model->cal_end_date)
        {
            $day_text = date("D", $datetime);
            $day_num = date("j", $datetime);
            $class_list = $this->ClassList("wv-cell", $datetime);

            $calendar_day_cells .= "<div class='cal-days'>
                <div id='cell-{$datetime}' class='{$class_list}'>
                    <div class='hrd text-center'>{$day_text}</div>
                    <div class='text-center'>{$day_num}</div>
                    {$this->TimeCells($datetime, "wv-cell")}
                
                </div>
            </div>";
            $datetime += ONE_DAY;
        }

        return <<<CAL
<div class='cal'>
    {$this->Navigation()}
    <div class='cal-cont'>
        <div class="week">
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
    }

    private function MonthView()
    {
        // create day cells
        $calendar_day_cells = "";
        $datetime = $this->model->cal_start_date;

        // Add Cells for this month 
        // Including left-over days from the previous month
        // and remainder for the next
        while($datetime <= $this->model->cal_end_date)
        {
            $calendar_day_cells .= "<div class='week'>";
            for($i = 0; $i < 7; $i++)
            {
                $day_num = date("j", $datetime);
                $class_list = $this->ClassList("dv-cell", $datetime);

                $calendar_day_cells .= "<div class='{$class_list}'>
                    <div class='cell-num'>{$day_num}</div>
                    <div id='cell-{$datetime}' class='event_list'></div>
                </div>";

                $datetime += ONE_DAY;
            }
            $calendar_day_cells .= "</div>";
        }

        return <<<CAL
<div class='cal'>
    {$this->Navigation()}
    <div class='cal-cont'>
        <div class='cal-header'>
            <div class='hdr-cell weekend'>Sun</div>
            <div class='hdr-cell'>Mon</div>
            <div class='hdr-cell'>Tues</div>
            <div class='hdr-cell'>Wed</div>
            <div class='hdr-cell'>Thu</div>
            <div class='hdr-cell'>Fri</div>
            <div class='hdr-cell weekend'>Sat</div>
        </div>
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
    }

    private function TimeCells($datetime, $base_class)
    {
        $hr = $this->model->start_hour;
        $start = strtotime("{$hr}:00", $datetime);
        $hr = $this->model->end_hour;
        $end = strtotime("{$hr}:00", $datetime);

        $cells = "";
        for($time = $start; $time < $end; $time += $this->time_slot)
        {
            $class_list = $this->ClassList($base_class, $datetime);
            $cells .= "<div class='{$class_list}'>
                <div id='cell-{$time}' class='cell-events'></div>
            </div>";
        }

        return $cells;
    }

    private function GutterCells($datetime, $base_class)
    {
        $hr = $this->model->start_hour;
        $start = strtotime("$hr:00", $datetime);
        $end = strtotime("{$this->model->end_hour}}:00", $datetime);

        $cells = "";
        for($time = $start; $time < $end; $time += $this->time_slot)
        {
            $class_list = $this->ClassList($base_class, $datetime);
            $time_text = date("g:i A", $time);
            $cells .= "<div id='$time' class='{$class_list}'>{$time_text}</div>";
        }

        return $cells;
    }

    public function ViewOptions($sel_view = "m")
    {
        if (empty($sel_view)) $sel_view = $this->model->view;

        $opt_ary = json_decode('
        [
            {"val": "m", "text": "Month"},
            {"val": "w", "text": "Week"},
            {"val": "ww", "text": "WorkWeek"},
            {"val": "d", "text": "Day"}
        ]');

        $options = "";
        foreach($opt_ary as $opt)
        {
            $sel = ($opt->val == $sel_view) ? " selected" : "";
            $options .= "<option value='{$opt->val}'{$sel}>{$opt->text}</option>";
        }

        return $options;
    }
}
