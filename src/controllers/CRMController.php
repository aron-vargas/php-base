<?php
namespace Freedom\Controllers;

use Freedom\Models\CDModel;
use Freedom\Views\CRMView;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CRMController extends CDController
{

    /**
     * Create a new instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->view = new CRMView($container);
    }

    static public function AddRoutes($app)
    {

    }

    public function list_companies(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $pkey = (isset($args['pkey'])) ? (int) $args['pkey'] : 0;

        $this->container->set("active_page", "companies");
        $model = $this->view->InitModel("crm", $page, $pkey);
        $model = new \Freedom\Models\Company();
        $model->Connect($this->container);
        $filter = $model->BuildFilter($args);
        $data = $model->GetALL("company", $filter);
        $this->view->InitDisplay("crm", $page, "show");
        $this->view->data = $data;

        return $this->buffer_response($request, $response, $args);
    }

    public function get_company(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $pkey = (isset($args['pkey'])) ? (int) $args['pkey'] : 0;

        $model = $this->view->InitModel("crm", $page, $pkey);
        $model->Connect($this->container);

        $this->view->InitDisplay("crm", $page, "show");
        $this->view->data = $model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_company(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";

        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $pkey = $parsed['pkey'];
            $act = isset($parsed['act']) ? (int) $parsed['act'] : 1;
            $model = $this->view->InitModel("crm", "company", $pkey);
            $model->Connect($this->container);
            if ($act === -1) # Delete Button
            {
                $model->Delete();
                $this->AddMsg("Company #{$pkey} was Deleted");

                // Go back to listing
                //$this->view->Set("src/templates/crm/company_list.php");
                //$model = new \Freedom\Models\Company();
                //$model->Connect($this->container);
                $filter = $model->BuildFilter($args);
                $this->view->data = $model->GetALL("company", $filter);
            }
            else
            {
                $model->Copy($parsed);
                $model->Save();
                $this->AddMsg("Company #{$pkey} was Updated");
                $this->view->data = $model;
            }
        }
        else
        {
            $this->AddMsg("Missing Data [pkey]");
        }

        $this->view->InitDisplay("crm", $page, "show");
        return $this->buffer_response($request, $response, $args);
    }

    public function rm_company(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $pkey = (isset($args['pkey'])) ? (int) $args['pkey'] : 0;

        $model = $this->view->InitModel("crm", "company", $pkey);
        $model->Connect($this->container);

        # DELETE the user
        if (isset($args['id']))
        {
            $model->Delete();
            $this->AddMsg("Company #{$args['id']} was Deleted");
        }

        # Show whos left
        $filter = $model->BuildFilter($args);
        $this->view->data = $model->GetALL("company", $filter);

        $this->view->InitDisplay("crm", $page, "show");
        return $this->buffer_response($request, $response, $args);
    }

    public function list_customers(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";

        try
        {
            $model = $this->view->InitModel("crm", "customer", 0);
            $model->Connect($this->container);
            $filter = $model->BuildFilter($args);
            $this->view->data = $model->GetALL("customer", $filter);
        }
        catch (\Throwable $exp)
        {
            $this->HandleException($exp);
        }

        $this->view->InitDisplay("crm", $page, "show");
        return $this->buffer_response($request, $response, $args);
    }

    public function get_customer(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $pkey = (isset($args['pkey'])) ? $args['pkey'] : null;
        $model = $this->view->InitModel("crm", "customer", $pkey);
        $model->Connect($this->container);
        $this->view->data = $model;

        $this->view->InitDisplay("crm", $page, "show");
        return $this->buffer_response($request, $response, $args);
    }
    public function update_customer(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";

        $parsed = $request->getParsedBody();
        $pkey = (isset($parsed['pkey'])) ? $parsed['pkey'] : null;
        $model = $this->view->InitModel("crm", "customer", $pkey);
        $model->Connect($this->container);

        if (isset($parsed['pkey']))
        {
            $act = isset($parsed['act']) ? (int) $parsed['act'] : 1;
            if ($act === -1) # Delete Button
            {
                $model->Delete();
                $this->AddMsg("Customer #{$pkey} ({$model->account_code}) was Deleted");

                // Go back to listing
                $filter = $model->BuildFilter($args);
                $this->view->data = $model->GetALL("customer", $filter);
            }
            else
            {
                $model->Copy($parsed);
                $model->Save();
                $this->AddMsg("Customer #{$pkey} ({$model->account_code}) was Updated");
                $this->view->data = $model;
            }
        }
        else
        {
            $this->AddMsg("Missing Data [pkey]");
        }

        $this->view->InitDisplay("crm", $page, "show");
        return $this->buffer_response($request, $response, $args);
    }

    public function rm_customer(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $pkey = (isset($args['pkey'])) ? $args['pkey'] : null;
        $model = $this->view->InitModel("crm", "customer", $pkey);
        $model->Connect($this->container);

        # DELETE the user
        if ($pkey)
        {
            $model->Delete();
            $this->AddMsg("Customer #{$args['id']} ({$model->account_code}) was Deleted");
        }

        # Show whos left
        $filter = $model->BuildFilter($args);
        $this->view->data = $model->GetALL("customer", $filter);

        $this->view->InitDisplay("crm", $page, "show");
        return $this->buffer_response($request, $response, $args);
    }

    public function list_locations(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try
        {
            $model = $this->view->InitModel("crm", "location", 0);
            $model->Connect($this->container);
            $filter = $model->BuildFilter($args);
            $this->view->data = $model->GetALL("location", $filter);
        }
        catch (\Throwable $exp)
        {
            $this->HandleException($exp);
        }

        $this->view->InitDisplay("crm", "location", "show");
        return $this->buffer_response($request, $response, $args);
    }

    public function get_location(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $pkey = (isset($args['pkey'])) ? $args['pkey'] : null;
        $model = $this->view->InitModel("crm", "location", $pkey);
        $model->Connect($this->container);
        $this->view->data = $model;

        $this->view->InitDisplay("crm", $page, "show");
        return $this->buffer_response($request, $response, $args);
    }
    public function update_location(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $act = isset($parsed['act']) ? (int) $parsed['act'] : 1;
            $pkey = $parsed['pkey'];
            $model = $this->view->InitModel("crm", "location", $pkey);
            $model->Connect($this->container);

            if ($act === -1) # Delete Button
            {
                $model->Delete();
                $this->AddMsg("Location #{$pkey} was Deleted");

                // Go back to listing
                $filter = $model->BuildFilter($args);
                $this->view->data = $model->GetALL("location", $filter);
            }
            else
            {
                $model->Copy($parsed);
                //$this->AddMsg("<pre>" . print_r($model, true) . "</pre>");
                $model->Save();
                $this->AddMsg("Location #{$pkey} was Updated");
                $this->view->data = $model;
            }
        }
        else
        {
            $this->AddMsg("Missing Data [pkey]");
        }

        $this->view->InitDisplay("crm", $page, "show");
        return $this->buffer_response($request, $response, $args);
    }
}
