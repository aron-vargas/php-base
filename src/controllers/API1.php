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

    static public function AddRoutes($app)
    {
        $app->get('/api/v1/{act}/{model}', [API1::class, 'get_act']);
        $app->post('/api/v1/{act}/{model}', [API1::class, 'post_act']);
        $app->put('/api/v1/{act}/{model}', [API1::class, 'put_act']);
        $app->delete('/api/v1/{act}/{model}', [API1::class, 'delete_act']);
    }

    public function ActionHandler($model, string $action = "show", array $req = array())
    {
        $ModelName = get_class($model);
        $act = isset($req['act']) ? (int) $req['act'] : 1;

        # Perform the action
        if ($action == 'save')
        {
            if ($act === -1) # Delete Button
            {
                $model->Delete();
                $this->AddMsg("$ModelName #{$model->pkey}Deleted");

                // Go back to listing
                $model->Clear();
                $filter = $model->BuildFilter($req);
                $this->view->data = $model->GetALL($model->GetTable(), $filter);
            }
            else
            {
                $model->Copy($req);
                $model->Save();
                $this->AddMsg("$ModelName #{$model->pkey} was Updated");
                $this->view->data = $model;
            }
        }
        else if ($action == 'create')
        {
            $model->Copy($req);
            if ($model->Validate())
            {
                $model->Create();
                $this->AddMsg("$ModelName was Created");
                $this->view->data = $model;
            }
        }
        else if ($action == 'change')
        {
            $field = (isset($req['field'])) ? $req['field'] : null;
            $value = (isset($req['value'])) ? trim($req['value']) : null;

            $model->Change($field, $value);
            $this->AddMsg("$ModelName #{$model->pkey} was changed");
            $this->view->data = $model;
        }
        else if ($action == 'list')
        {
            $filter = $model->BuildFilter($req);
            $this->view->data = $model->GetALL($model->GetTable(), $filter);
        }
        else if ($action == 'show')
        {
            $filter = $model->BuildFilter($req);
            $this->view->data = $model->GetALL($model->GetTable(), $filter);
        }
        else if ($action == 'delete')
        {
            $model->Delete();
            $this->AddMsg("$ModelName #{$model->pkey} was Deleted");

            $model->Clear();
            $filter = $model->BuildFilter($req);
            $this->view->data = $model->GetALL($model->GetTable(), $filter);
        }
        else if ($action == 'edit')
        {
            $this->view->data = $model;
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
}