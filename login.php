<?php
session_start();

try
{
	global $session;
	require("include/session_manager.php");

	$session->End();

	$session->controller->view->SetView('include/templates/login_form.php');
}
catch (Exception $exp)
{
	if ($session->controller->view->Empty())
		$session->controller->view->SetView("error.php");

	$session->controller->view->message = $exp->getMessage();
}


echo "</pre>";
# Show the stuff
$session->controller->view->render();
