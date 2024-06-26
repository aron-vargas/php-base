<?php
use Freedom\Models\Schedule\Event;
use Freedom\Models\User;

$start = (isset($_REQUEST['start'])) ? Event::Clean($_REQUEST['start']) : date("Y-m-d", strtotime("-1 Month"));
$end = (isset($_REQUEST['end'])) ? Event::Clean($_REQUEST['end']) : date("Y-m-d", strtotime("+1 Month"));
$search = (isset($_REQUEST['search'])) ? Event::Clean($_REQUEST['search']) : "";

$model = $this->model;
$filter = $model->BuildFilter($_REQUEST);
$events = $model->GetAll('event', $filter);

if ($events)
{
    $event_list = "";
    foreach ($events as $e)
    {
        $usr = new User($e->created_by);

        $orginized_by = "";
        if ($e->orginizer_id)
        {
            $orgizer = new User($e->orginizer_id);
            $orginized_by = "
                <small class='ms-3 text-body-secondary'>
                    Orgized By: {$orgizer->first_name} {$orgizer->last_name}
                </small>";
        }

        $performed_by = "";
        if ($e->performer_id)
        {
            $orgizer = new User($e->performer_id);
            $performed_by = "
                <small class='ms-3 text-body-secondary'>
                    Performed By: {$orgizer->first_name} {$orgizer->last_name}
                </small>";
        }

        $event_list .= "<div class='col mt-4 border rounded'>
            <h2 class='fst-italic border-bottom p-2'>
                {$e->title}

                <a class='float-end' href='/calendar/event/{$e->pkey}'>
                    <i class='fs-6 fa fa-pencil'></i>
                </a>
            </h2>
            <article class='blog-post-meta p-2'>
                <div>
                    <small class='text-body-secondary'>
                        From: {$e->start_date} to {$e->end_date}
                    </small>
                </div>
                <div>
                    <small class='text-body-secondary'>
                        Created by: {$usr->first_name} to {$usr->last_name}
                    </small>
                    {$orginized_by}
                    {$performed_by}
                </div>
                <p>{$e->description}</p>
            </article>
        </div>";
    }
}
else
{
    $event_list = "<div class='info'>No Events Found</div>";
}

echo <<<TABLE
<div class='container mt-2'>
    <form action='calendar.php' method='GET'>
    <input type='hidden' name='v' value='list' />
    <div class='row search justify-content-end'>
        <div class='col col-3'>
            <div class='input-group'>
                <span class="input-group-text">From:</span>
                <input type='date' id='start' name='start' class='form-control' value='{$start}' onChange="this.form.submit();"/>
            </div>
        </div>
        <div class='col col-3'>
            <div class='input-group'>
                <span class="input-group-text">To:</span>
                <input type='date' id='end' name='end' class='form-control' value='{$end}' onChange="this.form.submit();"/>
            </div>
        </div>
        <div class='col col-6'>
            <div class='input-group'>
                <span class="input-group-text"><i class='fa fa-search'></i></span>
                <input type='text' id='search' class='form-control' name='search' value='{$search}' placeholder='Event Filter' />
            </div>
        </div>
    </div>
    </form>
    <div id='event-cont' class='event-cont'>
        {$event_list}
    </div>
</div>
<script>
$("#search").on("keyup", function() {
    var text = $(this).val();

    $('.card').filter(function () {
        var idx = $(this).text().indexOf(text);
        $(this).toggle(idx > -1);
    });
});
</script>
TABLE;