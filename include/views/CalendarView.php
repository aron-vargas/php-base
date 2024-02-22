<?php

class CalendarView extends CDView {
    public $template = null;

    public $message;

    public $css = array();
    public $js = array();

    protected $debug = false;

    protected $_cal;                  # Object : calendar settings
    protected $active_event;

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

        $this->_cal = $_SESSION["cal"];
    }

    public function ClassList($base, $datetime)
    {
        $class_list = array($base);
        if (CalendarModel::isWeekend($datetime))
            $class_list[] = "weekend";
        if (CalendarModel::isHoliday($datetime))
            $class_list[] = "holiday";
        if (CalendarModel::isToday($datetime))
            $class_list[] = "today";

        if ($datetime < $this->_cal->month_first)
            $class_list[] = "prev";
        if ($datetime > $this->_cal->month_last)
            $class_list[] = "next";

        if (CalendarModel::isSelected($this->_cal->sel_date, $datetime))
            $class_list[] = "selected";

        return implode(" ", $class_list);
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
SetEvents(start, end);
SetTasks(start, end);
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
                <div class="col col-md-3 me-auto">
                    <button class="btn btn-primary" type="button" onClick="ToggleSideBar()">
                        <i class='fa fa-calendar-minus'></i>
                    </button>
                    <span class='h3 text-primary mx-3'>Calendar Events</span>
                </div>
                <div class="col col-md-4 mx-auto text-center">
                    <a class="mx-2 col btn btn-light" href="?act=today">Today</a>
                    <a class="mx-2 col btn btn-light" href="?act=prev"><i class='fa fa-caret-left'></i></a>
                    <span class='col nav-text h4'>{$selected_month} {$selected_year}</span>
                    <a class="mx-2 col btn btn-light" href="?act=next"><i class='fa fa-caret-right'></i></a>
                    <span class="mx-2 col dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="view-btn" data-bs-toggle="dropdown" aria-expanded="false">
                            View
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="view-btn">
                            $view_options
                        </ul>
                    </span>
                </div>
                <div class="col col-md-3 ms-auto">
                    <div class="collapse" id="search">
                        <input class='form-control' name="seach" placeholder="Search Events" />
                    </div>
                </div>
                <div class="col col-md-2 ms-auto text-end">
                    <button
                        class="btn btn-light me-2"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#search"
                        aria-controls="search"
                        aria-expanded="false"
                        aria-label="Search">
                        <i class='fa fa-search'></i>
                    </button>
                    <a class="btn btn-light me-2" href="?v=support"><i class="fa fa-circle-question"></i></a>
                    <a class="btn btn-light me-2" href="?v=settings"><i class='fa fa-gear'></i></a>
                </div>
            </div>
        </nav>
NAV;
    }

    private function SideBar()
    {
        return <<<BAR
        <div id='cal-left-nav' class='col col-md-2'>
            {$this->SelectCal()}
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item mt-1">
                    <a class='nav-link btn-default' onClick="ShowEventDialog()" href="#">New Event</a>
                </li>
                <li class="nav-item mt-1">
                    <a class='nav-link btn-default' onClick="ShowTaskDialog()" href="#">New Task</a>
                </li>
            </ul>
        </div>
BAR;
    }

    private function DayView()
    {
        // Add Cells for this week
        $day_text = date("l", $this->_cal->sel_date);
        $day_num = date("j", $this->_cal->sel_date);
        $class_list = $this->ClassList("flexcol", $this->_cal->sel_date);

        return <<<CAL
<div class='cal'>
    {$this->Navigation()}
    <div class='row cal-row'>
        {$this->SideBar()}
        <div id="MainView" class='cal-cont'>
            <div class="row g-0">
                <div class='col gutter'>&nbsp;</div>
                <div class='col hdr-cell'>{$day_text}<br />{$day_num}</div>
            </div>
            <div class='scroll-cont'>
                <div class='scroll-y'>
                    <div class='col gutter'>
                        {$this->GutterCells(0, "dv-cell")}
                    </div>
                    <div class='col cal-days'>
                        <div id='cell-{$this->_cal->sel_date}' class='{$class_list}'>
                            {$this->TimeCells($this->_cal->sel_date, "dv-cell")}
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
CAL;
    }

    private function WeekView()
    {
        // create day cells
        $calendar_hdr_cells = "<div class='col gutter'>&nbsp;</div>";

        $calendar_day_cells = "
            <div class='col gutter'>
                {$this->GutterCells(0, "wv-cell")}
            </div>
        ";

        // Add Cells for this week
        $datetime = ($this->_cal->view == "ww") ? $this->_cal->work_week_start : $this->_cal->week_start;
        $end_date = ($this->_cal->view == "ww") ? $this->_cal->work_week_end : $this->_cal->week_end;
        while ($datetime <= $end_date)
        {
            $day_text = date("D", $datetime);
            $day_num = date("j", $datetime);

            $class_list = $this->ClassList("hdr-cell", $datetime);
            $calendar_hdr_cells .= "<div class='col $class_list'>{$day_text}<br/>{$day_num}</div>";

            $class_list = $this->ClassList("flexcol", $datetime);
            $calendar_day_cells .= "<div class='col cal-days'>
                <div id='cell-{$datetime}' class='$class_list'>
                    {$this->TimeCells($datetime, "wv-cell")}
                </div>
            </div>";
            $datetime += ONE_DAY;
        }

        return <<<CAL
<div class='cal'>
    {$this->Navigation()}
    <div class='row cal-row'>
        {$this->SideBar()}
        <div id="MainView" class='cal-cont'>
            <div class="row row-hdr g-0">
                $calendar_hdr_cells
            </div>
            <div class='scroll-cont'>
                <div class='scroll-y'>
                    $calendar_day_cells
                </div>
            </div>
        </div>
    </div>
</div>
CAL;
    }

    private function MonthView()
    {
        // create day cells
        $calendar_day_cells = "";
        $datetime = $this->_cal->month_start;

        $today = strtotime(date("Y-m-d"));
        $now = strtotime(date("Y-m-d H:00:00"));
        $todays_secods = $now - $today;

        // Add Cells for this month
        // Including left-over days from the previous month
        // and remainder for the next
        while ($datetime <= $this->_cal->month_end)
        {
            $calendar_day_cells .= "<div class='row g-0'>";
            for ($i = 0; $i < 7; $i++)
            {
                $day_num = date("j", $datetime);
                $class_list = $this->ClassList("mv-cell", $datetime);
                $event_datetime = $datetime + $todays_secods;

                $calendar_day_cells .= "<div class='col'>
                    <div class='{$class_list}' onClick=\"ShowEventDialog(0, '$event_datetime');\"'>
                        <div class='cell-num'>{$day_num}</div>
                        <div id='cell-{$datetime}' class='cal-days'></div>
                    </div>
                </div>";

                $datetime += ONE_DAY;
            }
            $calendar_day_cells .= "</div>";
        }

        return <<<CAL
        <div class='cal'>
            {$this->Navigation()}
            <div class='row cal-row'>
                {$this->SideBar()}
                <div id="MainView" class='cal-cont flexcol stretch'>
                    <div class='row hdr-row g-0'>
                        <div class='col hdr-cell weekend'>Sun</div>
                        <div class='col hdr-cell'>Mon</div>
                        <div class='col hdr-cell'>Tues</div>
                        <div class='col hdr-cell'>Wed</div>
                        <div class='col hdr-cell'>Thu</div>
                        <div class='col hdr-cell'>Fri</div>
                        <div class='col hdr-cell weekend'>Sat</div>
                    </div>
                    $calendar_day_cells
                </div>
            </div>
        </div>
CAL;
    }

    public function process($req)
    {
        if (isset($req['v']))
        {
            if ($req['v'] == 'event')
            {
                $pkey = (isset($req['event_id'])) ? CDModel::Clean($req['event_id']) : 0;
                $start_date = (isset($req['start_date'])) ? CDModel::Clean($req['start_date']) : null;
                $this->active_event = new CalEvent($pkey, $start_date);
                $this->template = "include/templates/calendar/event.php";
            }
        }
    }

    public function render_body()
    {
        if ($this->message)
            echo "\n<div class='alert alert-secondary w-50 mx-auto my-1'><p>{$this->message}</p></div>\n";

        if ($this->template)
        {
            $event = $this->active_event;
            include("include/templates/calendar/event.php");
        }
        else if ($this->_cal->view == "d")
            echo $this->DayView();
        else if ($this->_cal->view == "w")
            echo $this->WeekView();
        else if ($this->_cal->view == "ww")
            echo $this->WeekView();
        else
            echo $this->MonthView();

        // class='modal fade' tabindex='-1' role='dialog' data-backdrop='false' aria-hidden='true'
        echo "\n<div id='event-dialog' class='modal fade'></div>\n";

        echo $this->EventJS($this->_cal->month_first, $this->_cal->month_end);
    }

    public function SelectCal()
    {
        // create day cells
        $calendar_day_cells = "";
        $datetime = $this->_cal->month_start;

        // Add Cells for this month
        // Including left-over days from the previous month
        // and remainder for the next
        while ($datetime <= $this->_cal->month_end)
        {
            $calendar_day_cells .= "<div class='row g-0'>";
            for ($i = 0; $i < 7; $i++)
            {
                $day_num = date("j", $datetime);
                $day_num = "<a href='calendar.php?act=sel&date=$datetime' alt='Select Date' title='Select Date'>{$day_num}</a>";
                $calendar_day_cells .= "
                    <div class='col'>
                        <div class='full-num'>{$day_num}</div>
                    </div>";

                $datetime += ONE_DAY;
            }
            $calendar_day_cells .= "</div>";
        }

        return <<<CAL
        <div class='cal-cont small'>
            <div class="row g-0">
                <div class='col hdr-cell weekend'>Su</div>
                <div class='col hdr-cell'>Mo</div>
                <div class='col hdr-cell'>Tu</div>
                <div class='col hdr-cell'>We</div>
                <div class='col hdr-cell'>Th</div>
                <div class='col hdr-cell'>Fr</div>
                <div class='col hdr-cell'>Sa</div>
            </div>
            $calendar_day_cells
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
        for ($time = $start; $time < $end; $time += $this->_cal->time_slot)
        {
            $class_list = $base_class;
            if (date("G", $time) < $this->_cal->work_start)
                $class_list .= " prev";
            if (date("G", $time) > $this->_cal->work_end)
                $class_list .= " next";

            $cells .= "<div class='{$class_list}' onClick=\"ShowEventDialog(0, '$time');\"'>
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
        for ($time = $start; $time < $end; $time += $this->_cal->time_slot)
        {
            $class_list = $base_class;
            if (date("G", $time) < $this->_cal->work_start)
                $class_list .= " prev";
            if (date("G", $time) > $this->_cal->work_end)
                $class_list .= " next";

            $time_text = date("g:i A", $time);

            $cells .= "<div id='gutter-{$time}' class='{$class_list}'>
                <div class='cell-lbl'>$time_text</div>
            </div>";
        }

        return $cells;
    }

    public function ViewOptions($sel_view = "m")
    {
        if (empty($sel_view))
            $sel_view = $this->_cal->view;

        $opt_ary = json_decode('
        [
            {"val": "m", "text": "Month"},
            {"val": "w", "text": "Week"},
            {"val": "ww", "text": "WorkWeek"},
            {"val": "d", "text": "Day"}
        ]');

        $options = "";
        foreach ($opt_ary as $opt)
        {
            $sel = ($opt->val == $sel_view) ? "active" : "";
            $options .= "<li><a class='dropdown-item {$sel}' href='calendar.php?act=set_view&view={$opt->val}' alt='Set View' title='Set View'>{$opt->text}</a></li>";
        }

        return $options;
    }
}
