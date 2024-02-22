<?php
try
{
    require("include/session_manager.php");

    # Re-Initialize with Calendar Model and View
    $controller->Init("CalendarModel", "CalendarView");
    # For Menu/Navagation
    $_SESSION['ACTIVE_PAGE'] = "events";
    # Perform action
    ActionHandler($_REQUEST);

    # Show the stuff
    # Determine the view
    $controller->view->process($_REQUEST);
    $controller->view->render();
}
catch (PDOException $exp)
{
    // Change the view and process the exception
    $controller->HandleException($exp);
    $controller->view->render();
}
catch (Exception $exp)
{
    // Change the view and process the exception
    $controller->HandleException($exp);
    $controller->view->render();
}

/**
 * Perform requestion action
 * @param mixed
 */
function ActionHandler($req)
{
    $controller = $_SESSION['APPCONTROLLER'];
    $action = (isset($req['act'])) ? strtolower(CDModel::Clean($req['act'])) : null;
    $pkey = (isset($req['pkey'])) ? CDModel::Clean($req['pkey']) : null;

    if ($action == 'save' || $action == 'create')
    {
        $event = new CalEvent($pkey);
        $event->Copy($req);
        if ($event->Validate())
        {
            $event->Save();
        }
    }
    else if ($action == 'change')
    {
        $field = (isset($req['field'])) ? CDModel::Clean($req['field']) : null;
        $value = (isset($req['value'])) ? CDModel::Clean($req['value']) : null;

        $event = new CalEvent($pkey);
        $event->Change($field, $value);
    }
    else if ($action == 'delete')
    {
        $event = new CalEvent($pkey);
        $event->Delete();
    }
    else if ($action == 'reset')
    {
        $sel_date = strtotime("today");
        $controller->model->Reset($sel_date);
    }
    else if ($action == 'today')
    {
        $controller->model->SelectDate(strtotime('today'));
    }
    else if ($action == 'sel')
    {
        if (isset($req['date']))
        {
            $sel_date = CDModel::Clean($req['date']);
            $controller->model->SelectDate($sel_date);
        }
    }
    else if ($action == 'prev')
    {
        $controller->model->Prev();
    }
    else if ($action == 'next')
    {
        $controller->model->Next();
    }
    else if ($action == 'set_view')
    {
        if (isset($req['view']))
            $_SESSION['cal']->view = CDModel::Clean($req['view']);
    }
    else if ($action == 'getevents')
    {
        $start = (isset($req['start'])) ? CDModel::Clean($req['start']) : time();
        $end = (isset($req['end'])) ? CDModel::Clean($req['end']) : time();
        $start = CDModel::ParseTStamp($start);
        $end = CDModel::ParseTStamp($end);

        $events = $controller->model->GetMyEvents($start, $end);
        echo json_encode($events);
        exit();
    }
}