<?php
try
{
    $ControllerClass = "CalController";
    require("../include/session_manager.php");

    # Perform action
    $controller->ActionHandler($_REQUEST);
    # Determine the view
    $controller->view->process($_REQUEST);
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
