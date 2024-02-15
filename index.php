<?php
session_start();
$_SESSION["APP-Controler"] = "CDControler";
$_SESSION["APP-Model"] = "CDModel";
$_SESSION["APP-View"] = "CDView";

try
{
	global $session, $controller;
	require("include/session_manager.php");

    # Process the request
	$controller->process($_REQUEST);

    # Determine the view
    $controller->SetTemplate($_REQUEST);

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