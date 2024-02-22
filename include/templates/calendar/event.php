<?php

$title = ($event->event_id) ? "Edit Event" : "New Event";

echo <<<FORM
<div id='event_form' class="modal-dialog" role="document">
    <div id='event-modal-content' class="modal-content">
        <form action='calendar.php' method='POST'>
            <input type='hidden' name='act' value='Save'>
            <input type='hidden' name='target' value='CalEvent'>
            <input type='hidden' name='pkey' value='{$event->event_id}'>
            <div class='modal-header'>
                <div class='title'>$title</div>
                {$event->Get('start_date')}
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
                <div class="form-group mt-4">
                    <button class="btn btn-light" type="button" id="advanced-btn" onClick="Toggle('#orginizer-cont');">
                        Orginizer / Performer
                    </button>
                    <div id='orginizer-cont' class='hidden'>
                        <div class="mb-4">
                            <label class="form-label" for="end_date">Select: </label>
                            <select
                                id="orginizer_id" name="orginizer_id" class="form-control" />
                                <option value='1'>Orginizer 1</option>
                                <option value='2'>Orginizer 2</option>
                                <option value='3'>Orginizer 3</option>
                                <option value='4'>Orginizer 4</option>
                                <option value='5'>Orginizer 5</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="end_date">Performer: </label>
                            <select
                                id="performer_id" name="performer_id" class="form-control" />
                                <option value='1'>Performer 1</option>
                                <option value='2'>Performer 2</option>
                                <option value='3'>Performer 3</option>
                                <option value='4'>Performer 4</option>
                                <option value='5'>Performer 5</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group mt-4">
                    <button class="btn btn-light" type="button" id="advanced-btn" onClick="Toggle('#reminder-cont');">
                        Reminder
                    </button>
                    <div id="reminder-cont" class='hidden'>
                        <div class="input-group">
                            <input type='number'
                                id="reminder_interval" name="reminder_interval" class="form-control"con
                                placeholer='1...N' />
                            <select
                                id="reminder_unit" name="reminder_unit" class="form-control" />
                                <option value='Minute'>Minute(s)</option>
                                <option value='Hour'>Hour(s)</option>
                                <option value='Day'>Day(s)</option>
                            </select>
                            <label class='ms-1'>Before Start Time</label>
                        </div>`
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center pt-1 mb-4 pb-1">
                <button type='submit' class="btn btn-primary" type="button">Submit</button>
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
FORM;