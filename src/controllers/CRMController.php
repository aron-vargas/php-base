<?php
namespace Freedom\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CRMController extends CDController {

    /**
     * Create a new instance
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    static public function AddRoutes($app)
    {

    }

    public function list_companies(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "crm");
        $this->model = new \Freedom\Models\Company();
        $this->model->Connect($this->container);
        $filter = $this->model->BuildFilter($args);
        $data = $this->model->GetALL("company", $filter);
        $this->view->Set("src/templates/crm/company_list.php");
        $this->view->data = $data;

        return $this->buffer_response($request, $response, $args);
    }

    public function get_company(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "crm");
        $this->view->Set("src/templates/crm/company_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $this->model = new \Freedom\Models\Company($pkey);
        $this->model->Connect($this->container);
        $this->view->data = $this->model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_company(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
       // $this->container->set("active_page", "crm");
        $this->view->Set("src/templates/crm/company_edit.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $pkey = $parsed['pkey'];
            $act = isset($parsed['act']) ? (int) $parsed['act'] : 1;
            $this->model = new \Freedom\Models\Company($pkey);
            $this->model->Connect($this->container);
            if ($act === -1) # Delete Button
            {
                $this->model->Delete();
                $this->AddMsg("Company #{$pkey} was Deleted");

                // Go back to listing
                $this->view->Set("src/templates/crm/company_list.php");
                $this->model = new \Freedom\Models\Company();
                $this->model->Connect($this->container);
                $filter = $this->model->BuildFilter($args);
                $this->view->data = $this->model->GetALL("company", $filter);
            }
            else
            {
                $this->model->Copy($parsed);
                $this->model->Save();
                $this->AddMsg("Company #{$pkey} was Updated");
                $this->view->data = $this->model;
            }
        }
        else
        {
            $this->AddMsg("Missing Data [pkey]");
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function rm_company(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # DELETE the user
        if (isset($args['id']))
        {
            $rm = new \Freedom\Models\Company($args['id']);
            $rm->Delete();
            $this->AddMsg("Company #{$args['id']} was Deleted");
        }

        # Show whos left
        //$this->container->set("active_page", "crm");
        $this->view->Set("src/templates/crm/company_list.php");
        $this->model = new \Freedom\Models\Company();
        $this->model->Connect($this->container);
        $filter = $this->model->BuildFilter($args);
        $this->view->data = $this->model->GetALL("company", $filter);

        return $this->buffer_response($request, $response, $args);
    }

    public function list_customers(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # This works OK. I would like to replace the ExceptionHandler with my own
        # TODO: ^THAT^
        try
        {
            //$this->container->set("active_page", "crm");
            $this->model = new \Freedom\Models\Customer();
            $this->model->Connect($this->container);
            $filter = $this->model->BuildFilter($args);
            $this->view->Set("src/templates/crm/customer_list.php");
            $this->view->data = $this->model->GetALL("customer", $filter);
        }
        catch (\Throwable $exp)
        {
            $this->HandleException($exp);
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function get_customer(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "crm");
        $this->view->Set("src/templates/crm/customer_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $this->model = new \Freedom\Models\Customer($pkey);
        $this->model->Connect($this->container);
        $this->view->data = $this->model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_customer(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "crm");
        $this->view->Set("src/templates/crm/customer_edit.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $act = isset($parsed['act']) ? (int) $parsed['act'] : 1;
            $this->AddMsg("<pre>" . print_r($parsed, true) . "</pre>");
            $pkey = $parsed['pkey'];
            $this->model = new \Freedom\Models\Customer($pkey);
            $this->model->Connect($this->container);
            if ($act === -1) # Delete Button
            {
                $this->model->Delete();
                $this->AddMsg("Customer #{$pkey} ({$this->model->account_code}) was Deleted");

                // Go back to listing
                $this->view->Set("src/templates/crm/customer_list.php");
                $this->model = new \Freedom\Models\Customer();
                $this->model->Connect($this->container);
                $filter = $this->model->BuildFilter($args);
                $this->view->data = $this->model->GetALL("customer", $filter);
            }
            else
            {
                $this->model->Copy($parsed);
                $this->model->Save();
                $this->AddMsg("Customer #{$pkey} ({$this->model->account_code}) was Updated");
                $this->view->data = $this->model;
            }
        }
        else
        {
            $this->AddMsg("Missing Data [pkey]");
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function rm_customer(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # DELETE the user
        if (isset($args['id']))
        {
            $rm = new \Freedom\Models\Customer($args['id']);
            $this->model->Connect($this->container);
            $rm->Delete();
            $this->AddMsg("Customer #{$args['id']} ({$this->model->account_code}) was Deleted");
        }

        # Show whos left
        //$this->container->set("active_page", "crm");
        $this->view->Set("src/templates/crm/customer_list.php");
        $this->model = new \Freedom\Models\Customer();
        $this->model->Connect($this->container);
        $filter = $this->model->BuildFilter($args);
        $this->view->data = $this->model->GetALL("customer", $filter);

        return $this->buffer_response($request, $response, $args);
    }

    public function list_locations(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try
        {
            //$this->container->set("active_page", "admin");
            $this->model = new \Freedom\Models\Location();
            $this->model->Connect($this->container);
            $this->view->Set("src/templates/crm/location_list.php");
            $filter = $this->model->BuildFilter($args);
            $this->view->data = $this->model->GetALL("location", $filter);
        }
        catch (\Throwable $exp)
        {
            $this->HandleException($exp);
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function get_location(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "admin");
        $this->view->Set("src/templates/crm/location_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $this->model = new \Freedom\Models\Location($pkey);
        $this->model->Connect($this->container);
        $this->view->data = $this->model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_location(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "admin");
        $this->view->Set("src/templates/crm/location_edit.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $act = isset($parsed['act']) ? (int) $parsed['act'] : 1;
            $pkey = $parsed['pkey'];
            $this->model = new \Freedom\Models\Location($pkey);
            $this->model->Connect($this->container);
            if ($act === -1) # Delete Button
            {
                $this->model->Delete();
                $this->AddMsg("Location #{$pkey} was Deleted");

                // Go back to listing
                $this->view->Set("src/templates/crm/location_list.php");
                $this->model = new \Freedom\Models\Location();
                $this->model->Connect($this->container);
                $filter = $this->model->BuildFilter($args);
                $this->view->data = $this->model->GetALL("location", $filter);
            }
            else
            {
                $this->model->Copy($parsed);
                //$this->AddMsg("<pre>" . print_r($this->model, true) . "</pre>");
                $this->model->Save();
                $this->AddMsg("Location #{$pkey} was Updated");
                $this->view->data = $this->model;
            }
        }
        else
        {
            $this->AddMsg("Missing Data [pkey]");
        }

        return $this->buffer_response($request, $response, $args);
    }
}
