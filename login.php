<?php
try
{
    require("include/session_manager.php");

    //    // Close any previous session
    // $controller->End();
    // $controller->Init();

    $controller->view->Set('include/templates/login_form.php');

    # Show the stuff
    $controller->view->render();
}
catch (Exception $exp)
{
    // Change the view and process the exception
    $controller->HandleException($exp);
    $controller->view->render();
}
