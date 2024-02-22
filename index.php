<?php
try
{
    require("include/session_manager.php");

    # Process the request
    $controller->SetModel($_REQUEST);
    HandleAct($_REQUEST);

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

function HandleAct($req)
{
    $controller = $_SESSION['APPCONTROLLER'];

    # Perform the action
    if (isset($req['act']))
    {
        $action = (isset($req['act'])) ? $req['act'] : null;

        if ($action == 'save')
        {
            $controller->model->Copy($req);
            $controller->model->Save();
        }
        else if ($action == 'create')
        {
            $controller->model->Copy($req);
            if ($controller->model->Validate())
            {
                $controller->model->Create();
            }
        }
        else if ($action == 'change')
        {
            $field = (isset($req['field'])) ? $req['field'] : null;
            $value = (isset($req['value'])) ? trim($req['value']) : null;

            $controller->model->Change($field, $value);
        }
        else if ($action == 'delete')
        {
            $controller->model->Delete();
        }
    }
}