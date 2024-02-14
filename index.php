<?php
session_start();
$_SESSION["APP-Controler"] = "CDControler";
$_SESSION["APP-Model"] = "CDModel";
$_SESSION["APP-View"] = "CDView";

try
{
	global $session;
	require("include/session_manager.php");

    # Process the request
	$session->controller->process($_REQUEST);

    # Determine the view
    $session->controller->SetTemplate($_REQUEST);

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