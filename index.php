<?php
session_start();

try
{
	global $session;
	require("include/session_manager.php");

	$session->controller->process($_REQUEST);
}
catch (PDOException $exp)
{
	if ($session->controller->view->Empty())
		$session->controller->view->SetView("error.php");

	$session->controller->view->message = $exp->getMessage();
	$session->controller->view->message .= $exp->getTraceAsString();
}
catch (Exception $exp)
{
	if ($session->controller->view->Empty())
		$session->controller->view->SetView("error.php");

	$session->controller->view->message = $exp->getMessage();
}

# Show the stuff
$session->controller->view->render();
