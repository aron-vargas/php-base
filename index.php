<?php
session_start();

try
{
	global $session;
    $_SESSION["APP-Controler"] = "CDControler";
    $_SESSION["APP-Model"] = "CDModel";
    $_SESSION["APP-View"] = "CDView";
	require("include/session_manager.php");

    # Process the request
	$session->controller->process($_REQUEST);
    # Determine the view
    $session->controller->SetView($_REQUEST);

    # Show the stuff
    $session->controller->view->render_header();
    $session->controller->view->render_body();
}
catch (PDOException $exp)
{ 
    $session->controller->view = new ErrorView();
    $session->controller->view->HandleException($exp);
    $session->controller->view->render_body();
}
catch (Exception $exp)
{
    $session->controller->view = new ErrorView();
    $session->controller->view->HandleException($exp);
    $session->controller->view->render_body();
}

# Close it up
$session->controller->view->render_footer();