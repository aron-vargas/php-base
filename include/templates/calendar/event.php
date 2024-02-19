<?php

$title = ($event->event_id) ? "Edit Event" : "New Event";

echo <<<FORM
    <div id='event_form' class='model'>
        <div class='card p-md-5 mx-md-4'>
        <div class='card-header'>
            <div class='title'>$title</div>
        </div>
        <div class='card-body'>
        <form action='index.php' method='POST'>
            <input type='hidden' name='act' value='Save'>
            <input type='hidden' name='target' value='CalEvent'>
            <input type='hidden' name='pkey' value='{$event->event_id}'>
            <div class="mb-4">
                <label class="form-label" for="title">Title</label>
                <input type="text" 
                    id="title" name="title" class="form-control" placeholder="Title" 
                    value="{$event->Get('title')}" />
            </div>
            <div class="mb-4">
                <label class="form-label" for="description">Description</label>
                <textarea 
                    id="description" name="description" class="form-control"
                    placeholder="Description" 
                >{$event->Get('description')}</textrea>
            </div>
            <div class="mb-4">
                <label class="form-label" for="title">Start Time</label>
                <input type="text" 
                    id="start_date" name="start_date" class="form-control" placeholder="Start" 
                    value="{$event->GetDate('start_date')}" />
                    <button id='start_date_btn' type="button" class='btn btn-secondary'
                        onClick="OpenCalendar(this, 'start_date')"><i class='fa fa-calendar'></i>
            </div>
            <div class="mb-4">
                <label class="form-label" for="end_date">End Time</label>
                <input type="text" 
                    id="end_date" name="end_date" class="form-control" placeholder="End" 
                    value="{$event->Get('end_date')}" />
                    <button id='end_date_btn' type="button" class='btn btn-secondary'
                        onClick="OpenCalendar(this, 'end_date')"><i class='fa fa-calendar'></i>
            </div>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="advanced-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    Orginizer / Performer
                </button>
                <div class="dropdown-menu" aria-labelledby="advanced-btn">
                    <div class="mb-4">
                        <label class="form-label" for="end_date">Orginizer</label>
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
                        <label class="form-label" for="end_date">Performer</label>
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
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle" type="button" id="advanced-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    Reminder
                </button>
                <div class="dropdown-menu" aria-labelledby="reminder-btn">
                    <div class="mb-4"
                        <label class="form-label" for="end_date">Reminder</label>
                        <input type='number' 
                            id="reminder_interval" name="reminder_interval" class="form-trol"con 
                            placeholer='1...N' />
                        <select 
                            id="reminder_unit" name="reminder_unit" class="form-control" />
                            <option value='Minute'>Minute(s)</option>
                            <option value='Hour'>Hour(s)</option>
                            <option value='Day'>Day(s)</option>
                        </select>
                        Before Start Time
                    </div>
                </div>
            </div>
            <div class="text-center pt-1 mb-4 pb-1">
                <button type='submit' class="btn btn-primary" type="button">Submit</button>
                <button type='button' 
                    class="btn btn-warning" 
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#event_form"
                    aria-controls="event_form"
                    aria-expanded="true"
                    aria-label="Cancel"
                >Cancel</button>
            </div>
        </form>
        </div>
    </div>
FORM;