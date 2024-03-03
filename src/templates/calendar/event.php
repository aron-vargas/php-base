<?php

$title = ($event->pkey) ? "Edit Event" : "New Event";
$check_all_day = ($event->all_day == 1) ? "checked" : "";
$chk_private_y = ($event->private == 1) ? "checked" : "";
$chk_private_n = ($event->private == 1) ? "" : "checked";
$event_type_options = CalendarView::EventTypeOptions($event->event_type);
$orginizer_options = CalendarView::OrginizerOptions($event->event_type);
$performer_options = CalendarView::PerformerOptions($event->event_type);

$delete_btn = "";
if ($event->pkey)
    $delete_btn = "<button type=\"act\" class=\"btn btn-primary\" type=\"button\" onClick=\"this.form.act='delete'; this.form.submit();\">Delete</button>";

echo <<<FORM
<style>

</style>
<div class='container my-5 p-5 main'>
    <div id='event_form' class="modal-dialog" role="document">
        <div id='event-modal-content' class="modal-content">
            <form action='calendar.php' method='POST'>
                <input type='hidden' name='act' value='Save'>
                <input type='hidden' name='target' value='CalEvent'>
                <input type='hidden' name='pkey' value='{$event->pkey}'>
                <input type='hidden' name='all_day' value='{$event->all_day}'>
                <div class='modal-header'>
                    <div class='title'>$title</div>
                    <button type="button" class="close btn p-0" aria-label="Close" onClick="$('#event-dialog').modal('hide');">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div id='event-modal-body' class='modal-body'>
                    <div class="form-group">
                        <label class="form-label" for="title">Title</label>
                        <input type="text"
                            id="title" name="title" class="form-control" placeholder="Title"
                            value="{$event->Get('title')}" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea
                            id="description" name="description" class="form-control"
                            placeholder="Description"
                        >{$event->Get('description')}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="title">Start Time</label>
                        <div class="input-group">
                            <input type="date"
                                id="start_date" name="start_date" class="form-control" placeholder="YY-MM-DD"
                                value="{$event->GetDate('start_date')}" />
                            <input type="time"
                                id="start_time" name="start_time" class="form-control" placeholder="HH:MM"
                                value="{$event->GetTime('start_date')}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_date">End Time</label>
                        <div class="input-group">
                            <input type="date"
                                id="end_date" name="end_date" class="form-control" placeholder="YY-MM-DD"
                                value="{$event->GetDate('end_date')}" />
                            <input type="time"
                                id="end_time" name="end_time" class="form-control" placeholder="HH:MM {$event->GetTime('end_date')}"
                                value="{$event->GetTime('end_date')}" />
                        </div>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox"
                            id="all_day_cb" name="all_day_cb" class='form-check-input'
                            {$check_all_day}" onClick="this.form.all_day.value = (this.checked) ? 1 : 0;" />
                        <label class="form-label" for="end_date">All Day</label>
                    </div>
                    <div class="form-group>
                        <label class="form-label" for="end_date">Public/Private</label>
                        <div class='form-check">
                            <input type='radion' id="private_y" name="private" class="form-check-input" $chk_private_y value=1>
                            <label class="form-check-label" for="private_y">Private</label>
                            &nbsp;
                            <input type='radion' id="private_n" name="private" class="form-check-input" $chk_private_n value=0>
                            <label class="form-check-label" for="private_y">Public</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_date">Type</label>
                        <div class="input-group">
                            <select id="event_type" name="event_type" class="form-control">
                                $event_type_options
                            </select>
                        </div>
                    </div>
                    <div class="form-group mt-4">
                        <button class="btn btn-light" type="button" id="advanced-btn" onClick="Toggle('#orginizer-cont');">
                            Orginizer / Performer
                        </button>
                        <div id='orginizer-cont' class='hidden'>
                            <div class="mb-4">
                                <label class="form-label" for="end_date">Orginizer: </label>
                                <select id="orginizer_id" name="orginizer_id" class="form-control" />
                                    {$orginizer_options}
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="end_date">Performer: </label>
                                <select id="performer_id" name="performer_id" class="form-control" />
                                    {$performer_options}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-4">
                        <button class="btn btn-light" type="button" id="advanced-btn" onClick="$('#reminder-cont').toggle();">
                            Reminder
                        </button>
                        <div id="reminder-cont" style="display: none">
                            <label class="form-label" for="reminder_interval">Before Start Time: </label>
                            <div class="input-group">
                                <input type='number'
                                    id="reminder_interval" name="reminder_interval" class="form-control"
                                    placeholer='1...N' />
                                <select
                                    id="reminder_unit" name="reminder_unit" class="form-control" />
                                    <option value='Minute'>Minute(s)</option>
                                    <option value='Hour'>Hour(s)</option>
                                    <option value='Day'>Day(s)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer text-center pt-1 mb-4 pb-1">
                    <button type='submit' class="btn btn-primary" type="button">Submit</button>
                    {$delete_btn}
                    <button type='button'
                        class="btn btn-warning"
                        type="button"
                        onClick="$('#event-dialog').modal('hide');"
                        data-bs-target="#event_form"
                    >Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
FORM;