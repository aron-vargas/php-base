<link rel='stylesheet' type='text/css' href='//localhost:8000/style/calendar.css' media='all'>
<script type='text/javascript' src='//localhost:8000/js/fullcalendar-scheduler/dist/index.global.min.js'></script>
<script type='text/javascript' src='//localhost:8000/js/calendar.js'></script>

<?php

$data = $this->data;
?>
<div id='calendar-cont'>
    <script type='text/javascript'>
        $(function ()
        {
            var DayGrid = {};
            var TimeGrid = {};
            var Week = {};
            var Day = {};

            var CalView = {
                dayGrid: DayGrid,
                timeGrid: TimeGrid,
                week: Week,
                day: Day
            }


            var cal_element = document.getElementById('calendar-cont');
            var cal_conf =
            {
                editable: true,
                aspectRatio: 1.8,
                scrollTime: '00:00',
                themeSystem: 'bootstrap5',
                headerToolbar: {
                    left: 'today prev,next',
                    center: 'title',
                    right: 'timeGridWeek,dayGridMonth'
                },
                initialView: 'dayGridMonth',
                dateClick: function (info)
                {
                    alert('a day has been clicked!');
                    ShowEventDialog(0, info.dateStr);
                }
            }

            const calendar = new FullCalendar.Calendar(cal_element, cal_conf);
            calendar.render();
            window.calendar = calendar;
        });
    </script>