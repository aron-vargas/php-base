<?php
session_start();
$_SESSION["APP-Controler"] = "CDControler";
$_SESSION["APP-Model"] = "CDModel";
$_SESSION["APP-View"] = "CDView";

try
{
	global $session, $controller;
	require("include/session_manager.php");

    // Close any previous session
	$session->End();

	$controller->SetTemplate('include/templates/login_form.php');

    # Show the stuff
    $controller->view->render();
}
catch (Exception $exp)
{
    // Change the view and process the exception
    $controller->HandleException($exp);
    $controller->view->render();
}
