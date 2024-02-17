<?php
try
{
    require("include/session_manager.php");

    # Re-Initialize with Calendar Model and View
    $session->Init("CDController", "CalendarModel", "CalendarView");
    $_SESSION['ACTIVE_PAGE'] = "events";

	$session->controller->process($_REQUEST);

    # Show the stuff
    $session->controller->view->render();
}
catch (PDOException $exp)
{
    // Change the view and process the exception
	$session->controller->HandleException($exp);
    $session->controller->view->render();
}
catch (Exception $exp)
{
    // Change the view and process the exception
    $session->controller->HandleException($exp);
    $session->controller->view->render();
}