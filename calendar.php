<?php
session_start();
$_SESSION["APP-Controler"] = "CDControler";
$_SESSION["APP-Model"] = "CalendarModel";
$_SESSION["APP-View"] = "CalendarView";

try
{
	global $session, $controller;
	require("include/session_manager.php");

	$controller->process($_REQUEST);

    # Show the stuff
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