<?php
try
{
    require("include/session_manager.php");

//    // Close any previous session
//	$session->End();
//    $session->Init();

	$session->controller->view->Set('include/templates/login_form.php');

    # Show the stuff
    $session->controller->view->render();
}
catch (Exception $exp)
{
    // Change the view and process the exception
    $session->controller->HandleException($exp);
    $session->controller->view->render();
}
