<?php
session_start();

try
{
	global $session;
	require("include/session_manager.php");

	$session->controller->process($_REQUEST);

    # Show the stuff
    $session->controller->view->render_header();
    $session->controller->view->render_body();
}
catch (PDOException $exp)
{
	$error_message = $exp->getMessage();
	$error_message .= $exp->getTraceAsString();
    syslog(LOG_ERR, $error_message);
    include("include/templates/error.php");
}
catch (Exception $exp)
{
    echo "<h3>Exception</h3>";
	$error_message = $exp->getMessage();
    syslog(LOG_ERR, $session->controller->view->message);
    include("include/templates/error.php");
}

$session->controller->view->render_footer();