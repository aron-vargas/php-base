<?php

class CalendarView extends CDView
{
	public $template = "include/templates/calendar.php";

	public $message;

	public $css = array();
	public $js = array();

	private $debug = false;

    private $_cal;                  # Object : calendar settings

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
		$this->css['cal'] = "<link rel='stylesheet' type='text/css' href='style/calendar.css' media='all'>";

		$this->js['bootstrap'] = "<script type='text/javascript' src='vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js'></script>";
		$this->js['jquery'] = "<script type='text/javascript' src='vendor/components/jquery/jquery.min.js'></script>";
		$this->js['jquery-ui'] = "<script type='text/javascript' src='vendor/components/jqueryui/jquery-ui.min.js'></script>";
		$this->js['cal'] = "<script type='text/javascript' src='js/calendar.js'></script>";

        $this->model = $model;
        $this->_cal = $_SESSION["cal"];
	}

    public function ClassList($base, $datetime)
    {
        $class_list = $base;
        $class_list .= ($this->model->isWeekend($datetime)) ? " weekend" : "";
        $class_list .= ($this->model->isHoliday($datetime)) ? " holiday" : "";
        $class_list .= ($this->model->isToday($datetime)) ? " today" : "";

        if ($datetime < $this->_cal->view_start_date) $class_list .= " prev";
        if ($datetime > $this->_cal->view_last_date) $class_list .= " next";

        $class_list .= ($this->model->isSelected($this->_cal->sel_date, $datetime)) ?" selected" : "";

        return $class_list;
    }

    public function render_body()
	{
		if ($this->message)
			echo "\n<div class='alert alert-secondary w-50 mx-auto my-1'><p>{$this->message}</p></div>\n";

        if ($this->_cal->view == "d")
            echo $this->DayView();
        else if ($this->_cal->view == "w")
            echo $this->WeekView();
        else if ($this->_cal->view == "ww")
            echo $this->WeekView();
        else if ($this->_cal->view == "e")
            include("include/templates/calendar_event.php");
        else
            echo $this->MonthView();

        echo $this->EventJS($this->_cal->view_start_date, $this->_cal->view_last_date);
	}

    public function render_footer()
	{
        echo "</body></html>";
    }

    private function EventJS($start, $end)
    {
        return <<<JS
<script type="text/javascript">
var start = {$start};
var end = {$end};
//SetEvents(start, end);
//SetTasks(start, end);
</script>
JS;
    }

     private function Navigation()
    {
        $selected_month = date('M', $this->_cal->sel_date);
        $selected_year = date('Y', $this->_cal->sel_date);
        $view_options = $this->ViewOptions($this->_cal->view);

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
        $day_text = date("D", $this->_cal->sel_date);
        $day_num = date("j", $this->_cal->sel_date);
        $class_list = $this->ClassList("wv-cell", $this->_cal->sel_date);

        return <<<CAL
<div class='cal'>
    {$this->Navigation()}
    <div class='cal-cont'>
        <div class='flexcol' style="height: 100%;">
            <div class="week-hdr">
                <div class='gutter'>
                    <div class='hdr-cell'>
                        <div class='hdr-cell-text'>&nbsp;</div>
                        <div class='hdr-cell-text'>&nbsp;</div>
                    </div>
                </div>
                <div class='cal-header'>
                    <div class='hdr-cell'>
                        <div class='hdr-cell-text'>{$day_text}</div>
                        <div class='hdr-cell-text'>{$day_num}</div>
                    </div>
                </div>
            </div>
            <div class="week">
                <div class='scroll-y'>
                    <div class='flex'>
                        <div class='gutter'>
                            {$this->GutterCells(0, "dv-cell")}
                        </div>
                        <div class='cal-days flexcol'>
                            <div id='cell-{$this->_cal->sel_date}' class='{$class_list}'>
                                {$this->TimeCells($this->_cal->sel_date, "dv-cell")}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
        $calendar_hdr_cells = "
        <div class='gutter'>
            <div class='hdr-cell'>
                <div class='hdr-cell-text'>&nbsp;</div>
                <div class='hdr-cell-text'>&nbsp;</div>
            </div>
        </div>";

        $calendar_day_cells = "

            <div class='gutter'>
                {$this->GutterCells(0, "wv-cell")}
            </div>
        ";

        // Add Cells for this week
        $datetime = $this->_cal->cal_start_date;
        while($datetime <= $this->_cal->cal_end_date)
        {
            $day_text = date("D", $datetime);
            $day_num = date("j", $datetime);
            $class_list = $this->ClassList("flexcol", $datetime);

            $calendar_hdr_cells .= "
            <div class='cal-header'>
                <div class='hdr-cell'>
                    <div class='hdr-cell-text'>{$day_text}</div>
                    <div class='hdr-cell-text'>{$day_num}</div>
                </div>
            </div>";

            $calendar_day_cells .= "<div class='cal-days'>
                <div id='cell-{$datetime}' class='$class_list'>
                    {$this->TimeCells($datetime, "wv-cell")}
                </div>
            </div>";
            $datetime += ONE_DAY;
        }

        return <<<CAL
<div class='cal'>
    {$this->Navigation()}
    <div class='cal-cont'>
        <div class='flexcol' style="height: 100%;">
            <div class="week-hdr">
                $calendar_hdr_cells
            </div>
            <div class="week">
                <div class='scroll-y'>
                    <div class='flex'>
                        $calendar_day_cells
                    </div>
                </div>
            </div>
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
        $datetime = $this->_cal->cal_start_date;

        // Add Cells for this month
        // Including left-over days from the previous month
        // and remainder for the next
        while($datetime <= $this->_cal->cal_end_date)
        {
            $calendar_day_cells .= "<div class='week flex ffill'>";
            for($i = 0; $i < 7; $i++)
            {
                $day_num = date("j", $datetime);
                $class_list = $this->ClassList("mv-cell", $datetime);

                $calendar_day_cells .= "<div class='cal-days flex'>
                    <div class='{$class_list}'>
                        <div class='cell-num'>{$day_num}</div>
                        <div id='cell-{$datetime}' class='event_list'></div>
                    </div>
                </div>";

                $datetime += ONE_DAY;
            }
            $calendar_day_cells .= "</div>";
        }

        return <<<CAL
<div class='cal'>
    {$this->Navigation()}
    <div class='cal-cont'>
        <div class='flexcol' style='height: 100%'>
            <div class="week flex">
                <div class='cal-header'><div class='hdr-cell weekend'>Sun</div></div>
                <div class='cal-header'><div class='hdr-cell'>Mon</div></div>
                <div class='cal-header'><div class='hdr-cell'>Tues</div></div>
                <div class='cal-header'><div class='hdr-cell'>Wed</div></div>
                <div class='cal-header'><div class='hdr-cell'>Thu</div></div>
                <div class='cal-header'><div class='hdr-cell'>Fri</div></div>
                <div class='cal-header'><div class='hdr-cell weekend'>Sat</div></div>
            </div>
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

    private function TimeCells($datetime, $base_class, $show_labels = false)
    {
        $hr = $this->_cal->start_hour;
        $start = strtotime("{$hr}:00", $datetime);
        $hr = $this->_cal->end_hour;
        $end = strtotime("{$hr}:00", $datetime);

        $cells = "";
        for($time = $start; $time < $end; $time += $this->_cal->time_slot)
        {
            $class_list = $base_class;
            if (date("G", $time) < $this->_cal->work_start) $class_list .= " prev";
            if (date("G", $time) > $this->_cal->work_end) $class_list .= " next";

            $cells .= "<div class='{$class_list}'>
                <div id='cell-{$time}' class='cell-events'></div>
            </div>";
        }

        return $cells;
    }

    private function GutterCells($datetime, $base_class)
    {
        $hr = $this->_cal->start_hour;
        $start = strtotime("{$hr}:00", $datetime);
        $hr = $this->_cal->end_hour;
        $end = strtotime("{$hr}:00", $datetime);

        $cells = "";
        for($time = $start; $time < $end; $time += $this->_cal->time_slot)
        {
            $class_list = $base_class;
            if (date("G", $time) < $this->_cal->work_start) $class_list .= " prev";
            if (date("G", $time) > $this->_cal->work_end) $class_list .= " next";

            $time_text = date("g:i A", $time);

            $cells .= "<div id='gutter-{$time}' class='{$class_list}'>
                <div class='cell-lbl'>$time_text</div>
            </div>";
        }

        return $cells;
    }

    public function ViewOptions($sel_view = "m")
    {
        if (empty($sel_view)) $sel_view = $this->_cal->view;

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
