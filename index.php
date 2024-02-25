<?php
try
{
    require("include/session_manager.php");

    # Process the request
    $controller->SetModel($_REQUEST);
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