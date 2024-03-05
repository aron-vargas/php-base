<?php
namespace Freedom\Controllers;

use Freedom\Controllers\CDController;
use Freedom\Views\CDView;
use Freedom\Models\CDModel;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class API1 extends CDController
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->view->mode = CDView::$JSON_MODE;
    }

    public function ActionHandler($action, $req)
    {
        $ModelName = get_class($this->model);
        $act = isset($req['act']) ? (int) $req['act'] : 1;

        # Perform the action
        if ($action == 'save')
        {
            if ($act === -1) # Delete Button
            {
                $this->model->Delete();
                $this->AddMsg("$ModelName #{$this->model->pkey}Deleted");

                // Go back to listing
                $this->model->Clear();
                $filter = $this->model->BuildFilter($req);
                $this->view->data = $this->model->GetALL($this->model->GetTable(), $filter);
            }
            else
            {
                $this->model->Copy($req);
                $this->model->Save();
                $this->AddMsg("$ModelName #{$this->model->pkey} was Updated");
                $this->view->data = $this->model;
            }
        }
        else if ($action == 'create')
        {
            $this->model->Copy($req);
            if ($this->model->Validate())
            {
                $this->model->Create();
                $this->AddMsg("$ModelName was Created");
                $this->view->data = $this->model;
            }
        }
        else if ($action == 'change')
        {
            $field = (isset($req['field'])) ? $req['field'] : null;
            $value = (isset($req['value'])) ? trim($req['value']) : null;

            $this->model->Change($field, $value);
            $this->AddMsg("$ModelName #{$this->model->pkey} was changed");
            $this->view->data = $this->model;
        }
        else if ($action == 'list')
        {
            $filter = $this->model->BuildFilter($req);
            $this->view->data = $this->model->GetALL($this->model->GetTable(), $filter);
        }
        else if ($action == 'show')
        {
            $filter = $this->model->BuildFilter($req);
            $this->view->data = $this->model->GetALL($this->model->GetTable(), $filter);
        }
        else if ($action == 'delete')
        {
            $this->model->Delete();
            $this->AddMsg("$ModelName #{$this->model->pkey} was Deleted");

            // Go back to listing
            $display = "list";
            $this->model->Clear();
            $filter = $this->model->BuildFilter($req);
            $this->view->data = $this->model->GetALL($this->model->GetTable(), $filter);
        }
        else if ($action == 'edit')
        {
            $this->view->data = $this->model;
        }

        return "data";
    }

    public function get_act(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return parent::get_act($request, $response, $args);
    }
    public function post_act(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return parent::post_act($request, $response, $args);
    }
    public function put_act(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return parent::post_act($request, $response, $args);
    }
    public function delete_act(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return parent::post_act($request, $response, $args);
    }

    protected function SetupView(array $args, string $display)
    {
        $this->AddMsg("OK");
    }
}