function ShowNewItemDialog(elem)
{
    var $sel = $(elem);

    alert("Create your own: "+$sel.Val());
}

function HandleError(jqXHR, textStatus, errorThrown)
{
    console.log(textStatus);
    console.log(errorThrown);
    // TODO: create error alert element and show it
    alert(errorThrown);
}

function ShowEventDialog(pkey, start_date)
{
    var event_url = "calendar.php?do=1&v=event&pkey="+pkey;
    if (start_date) event_url += "&start_date="+start_date;
    event_url += " #event_form";

    $("#event-dialog").load(event_url, function () {
        $("#event-modal-content").resizable({minHeight: 600, minWidth: 300});
        $("#event-dialog").draggable({handle: ".modal-header"});
        $("#event-dialog").modal({backdrop: false, show: true});
        $("#event-dialog").modal('show');
    });
}

function ToggleSideBar()
{
    $bar = $("#cal-left-nav");

    if ($bar.hasClass('hidden')) $bar.removeClass('hidden');
    else $bar.addClass('hidden');
}

function Toggle(selector)
{
    if ($(selector).hasClass('hidden')) $(selector).removeClass('hidden');
    else $(selector).addClass('hidden');
}


/**
 * AJV:
 *
 * This is the code base for calendar implementation.
 *
 * When existing functions are needed they will be moved
 * below. Otherwise I plan to remove them after this is
 * finalized.
 */
function EventMgr(container, config)
{
    this.container = '#cal-cont';
    this.base_uri = 'calendar.php';
    this.draw_args = { act : 'view' };
    this.gev_args = { act : 'getevents' };
    this.view = 'm';
    this.sel_date = new Date();
    this.cal_events = new Array();
    this.TB_BORDER = 1;
    this.TIC_PERIOD = 16;
    this.PIX_PER_MIN = 1; //3.2;
    this.FIRST_TIC = 360; // 05:00 = 5 hr * 60 min/hr = 300 min
    this.LAST_TIC = 1200; // 20:00 = 20 hr * 60 min/hr = 1200 min
    this.FIRST_POS;
    this.LAST_POS;
    this._track_scroll = false;

    // Set display container
    if (container)
        this.container = container;

    // Assign the property from config object
    if (config)
    {
        if (config.base_uri) this.base_uri = config.base_uri;
        if (config.draw_args) this.draw_args = config.draw_args;
        if (config.gev_args) this.gev_args = config.gev_args;
        if (config.view) this.view = config.view;
        if (config.cal_events) this.cal_events = config.cal_events;
    }

    this.FIRST_POS = this.FIRST_TIC * this.PIX_PER_MIN;
    this.LAST_POS = this.TimeToPix(this.LAST_TIC);
}

/**
 * Add records to the calendar
 */
EventMgr.prototype.AddEvents = function(resp, textStatus, jqXHR)
{
    // Set globals used to track first and last position
    this.cal_events = resp.data;

    try
    {
        if (this.cal_events != null)
        {
            for (var i = 0; i < this.cal_events.length; i++)
            {
                this.AppendEvent(this.cal_events[i]);
            }
        }
    }
    catch (exc)
    {
        alert("AddEvents: " + exc);
    }

    if (this._track_scroll)
        this.AddScrollListener();
};

EventMgr.prototype.AddScrollListener = function()
{
    var main_div = $(this.container);

    main_div.on("scroll", MouseScroll);
};

/**
 * Build an event element and append it to the proper
 * parent container for the day it represents.
 *
 * @param object
 * @param object
 */
EventMgr.prototype.AppendEvent = function(cal_event)
{
    var title = $("<div></div>").text(cal_event.title);
    var descr = $("<div></div>").text(cal_event.description);
    title.addClass('event-title');
    descr.addClass('event-descr');

    var newDiv = $("<div></div>");
    newDiv.append(title,descr);
    newDiv.id = cal_event.id;
    newDiv.css("left", "0px");
    newDiv.startTime = cal_event.start_time;
    newDiv.endTime = cal_event.end_time;
    newDiv.addClass("cal-event");
    if (cal_event.public)
        newDiv.addClass('pub');
    if (cal_event.tt)
        SetupMO(newDiv, cal_event.tt)
    newDiv.on("click", function (event) { event.stopPropagation(); ShowEventDialog(cal_event.pkey) } );

    var parent = $(cal_event.cell_id);
    if (parent.length)
    {
        var last = parent.children('.cal-event').last();
        if (last.hasClass('cal-event'))
        {
            // When the events overlap move over a bit
            if (this.view != 'm' && this.view != 'pl')
            {
                var last_l = parseInt(last.css("left"));
                var left = (last_l + 15) + 'px';
                newDiv.css("left", left);
            }
        }

        parent.append(newDiv);

        // Adjust position and height based on the start and end
        // of the time period relative to the parent containers time frame
        if (this.view != 'm' && this.view != 'pl')
            this.SetDivPosition(newDiv);
    }
};

/**
 * Send a request to retrieve calendar events
 *
 * @param string
 */
EventMgr.prototype.GetEvents = function(add_args)
{
    // Append filter options to args list
    var args = {...this.gev_args, ...add_args};
    args.view = this.view;
    var self = this;

    // Remove all existing events
    $(".cal-event").remove();

    //var url = "calendar.php?act=getevents&start="+start+"&end="+end;
    $.get(self.base_uri, args, 'json')
        .done(function(resp, textStatus, jqXHR) {  self.AddEvents(resp, textStatus, jqXHR); })
        .fail(HandleError);
};

/**
 * Set top position and element height
 * according to the event start and end times
 *
 * @param object timeDiv
 */
EventMgr.prototype.SetDivPosition = function(eventDiv)
{
    var top = this.TimeToPix(eventDiv.startTime);
    var height = this.TimeToPix(eventDiv.endTime) - top - this.TB_BORDER;

    if (top < 0)
    {
        height -= (0 - top);
        top = 0;
    }

    if (top > this.LAST_POS)
        top = this.LAST_POS;

    // Height past bottom of div
    if ((top + height) > this.LAST_POS)
        height = this.LAST_POS - top;

    // No time tick go to top of 0
    eventDiv.css("top", top + 'px');
    eventDiv.css("height", height + 'px');
};

/**
 * Convert time to pixel position
 * Get absolute position for this time (in pixels)
 * Subtract absolute first position to Set the relative
 * position for this time (in pixels)
 *
 * @param integer time
 * @return integer
 */
EventMgr.prototype.TimeToPix = function(time)
{
    return time * this.PIX_PER_MIN - this.FIRST_POS;
};

/**
 * Adjust calendar view based on mouse wheel scroll
 *
 * @param Event
 */
EventMgr.prototype.MouseScroll = function(event)
{
    var rolled = 0;

    if ('wheelDelta' in event)
    {
        rolled = event.wheelDelta;
    }
    else
    { // Firefox: The measurement units of the detail and wheelDelta properties are different.
        rolled = -40 * event.detail;
    }

    if (rolled > 1)
        this.Adjust(-1);
    else
        this.Adjust(1);
};

/**
 * Handle action: category selected
 *
 * @param string
 */
EventMgr.prototype.SelectCat = function(cat)
{
    var args = this.draw_args;
    args.cat = cat;
    this.SendRedraw(args);
};

/**
 * Handle action: date selected
 *
 * @param string
 * @param string
 * @param
 */
EventMgr.prototype.SelectDate = function(dateStr, view, move)
{
    var tim_inp = $('#cal_selected_time');
    var args = this.draw_args;

    // Send selected time
    if (typeof dateStr != 'undefined')
    {
        args.selected_time = dateStr;

        // Update input value
        if (tim_inp)
            tim_inp.value = dateStr;
    }

    // Optional: Change view
    if (view != null && typeof view === 'string')
    {
        this.view = view;
        args.view = view;
    }
    // Optional: move focus
    if (typeof move != 'undefined')
    {
        args.focused_time = dateStr;
    }

    this.SendRedraw(args);
};

/**
 * Send request to redaw calendar
 *
 * @param string
 */
EventMgr.prototype.SelectPub = function(pub)
{
    var args = this.draw_args;
    args.pub = pub;
    this.SendRedraw(args);
};

/**
 * Send request to redaw calendar with selected date
 *
 * @param string
 */
EventMgr.prototype.SendRedraw = function(args)
{
    $.get(this.base_uri, args, 'json')
        .done(ReDraw)
        .fail(HandleError);
}

/**
 * Send request to redaw calendar with selected view
 *
 * @param string
 */
EventMgr.prototype.SetView = function(view)
{
    var args = this.draw_args;
    args.view = view;
    this.view = view;

    this.SendRedraw(args);
};

/*************************************************************************
 * EventMgr: Helper functions
 *
 *************************************************************************/
/**
 * Replace calendar contentents with respose text
 *
 * @param object
 */
function ReDraw(response, textStatus, jqXHR)
{
    try
    {
        if (response.innerHTML)
        {
            var div = $('<div></div>');
            div.innerHTML = response.innerHTML;

            var parent = $(mgr.container);
            parent.replaceChild(div.firstChild, parent.children[0]);
        }

        if (response.events)
        {
            mgr.cal_events = response.events;
            mgr.AddEvents();
        }
        else
        {
            mgr.GetEvents();
        }
    }
    catch (exc)
    {
        alert('Error (Redraw): ' + exc);
    }
}
