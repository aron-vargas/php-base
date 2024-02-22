function ShowNewItemDialog(elem)
{
    var $sel = $(elem);

    alert("Create your own: "+$sel.Val());
}

function AttachEvents(data, textStatus, jqXHR)
{
    if (data)
    {
        alert("Got some data back");
        for (var event in data)
        {
            var eventElem = CreateEventElem(event);
            $('#'+event.id).append(eventElem);
        }
    }
}

function CreateEventElem(event)
{
    var title = $("<div></div>").text(event.title);
    var descr = $("<div></div>").text(event.description);
    title.addClass('event-title');
    descr.addClass('event-descr');

    var elem = $("<div></div>");
    elem.addClass("cal-event");
    elem.append(title,descr);

    return elem;
}
function SetEvents(start, end)
{
    var url = "calendar.php?act=getevents&start="+start+"&end="+end;
    $.get(url, {}, AttachEvents, 'json');
}

function SetTasks(start, end)
{

}

function ShowEventDialog(event_id, start_date)
{
    var event_url = "calendar.php?do=1&v=event&pkey="+event_id;
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
