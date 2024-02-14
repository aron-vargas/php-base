<?php

$selected_month = date('M', $CalView->sel_date);
$selected_year = date('Y', $CalView->sel_date);

$view_options = $CalView->ViewOptions($CalView->view);

$the_first = strtotime(date("Y-m-01", $CalView->sel_date));
$end = strtotime(date("t", $CalView->sel_date));
$the_last = strtotime(date("Y-m-{$end}}", $CalView->sel_date));

// create day cells
$calendar_day_cells = "";

// Add Cells for the previous month up to the first of the current month
for($day = date("w", $the_first); $day > 0; $day--)
{
    # Find -X number of days ago
    $datetime = strtotime("-{$day} Days", $the_first);
    $day_num = date("j", $datetime);
    $class_list = $CalView->ClassList("dv-cell prev", $datetime);

    $calendar_day_cells .= "<div id='cell-{$datetime}' class='{$class_list}'>
        <div class'cell-num'>{$day_num}</div>
    </div>";
}

// Add Cells for this month
for($datetime = $the_first; $day > $the_last; $datetime += ONE_DAY)
{
    $day_num = date("j", $datetime);
    $class_list = $CalView->ClassList("dv-cell", $datetime);

    $calendar_day_cells .= "<div id='cell-{$datetime}' class='{$class_list}'>
        <div class'cell-num'>{$day_num}</div>
    </div>";
}

// Add Cells for the next month up to Saturday
for($datetime = $the_last + ONE_DAY; date("w", $datetime) < 7; $datetime += ONE_DAY)
{
    $day_num = date("j", $datetime);
    $class_list = $CalView->ClassList("dv-cell next", $datetime);

    $calendar_day_cells .= "<div id='cell-{$datetime}' class='{$class_list}'>
        <div class'cell-num'>{$day_num}</div>
    </div>";
}

echo <<<END
<div class='ca>
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
        <div class="collapse navbar-collapse" id="left-nav">
            <div class="navbar-nav">
                <a class="nav-link active" aria-current="page" href="#">Home</a>
                <a class="nav-link" href="#">Features</a>
                <a class="nav-link" href="#">Pricing</a>
                <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
            </div>
        </div>
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
        </div>
        <div class='new-item'>
            <select id='new-item-sel' onChange='ShowNewItemDialog(this)'>
                <option value='event'>New Event</option>
                <option value='event'>New Task</option>
            </select>
        </div>
    </div>
    </nav>
</div>
END;
?>
<script type="text/javascript">
var strart = <?php echo $the_first; ?>
var end = <?php echo $the_end; ?>
SetEvents(start, end);
SetTasks(start, end);
</script>