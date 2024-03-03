<?php
namespace Freedom\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AdminController extends CDController {
    protected $act = "view";
    protected $target = "home";
    protected $target_pkey;

    /**
     * Create a new instance
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    public function list_users(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $parsed = $request->getParsedBody();
        $filter = [
            ["field" => "status", "op" => "ne", "match" => "INVALID", "type" => "string"]
        ];
        if (isset($parsed['status']))
        {
            $match = CDModel::Clean($parsed['status']);
            $filter[] = ["field" => "status", "op" => "eq", "match" => $match, "type" => "string"];
        }
        if (isset($parsed['user_type']))
        {
            $match = CDModel::Clean($parsed['user_type']);
            $filter[] = ["field" => "user_type", "op" => "eq", "match" => $match, "type" => "string"];
        }

        $data = $this->model->GetALL("user", $filter);
        $this->view->Set("src/templates/user_list.php");
        $this->view->data = $data;

        return $this->buffer_response($request, $response, $args);
    }

    public function get_user(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("src/templates/user_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $this->model = new User($pkey);
        $this->view->data = $this->model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_user(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("src/templates/user_list.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $pkey = (isset($parsed['pkey'])) ? $parsed['pkey'] : null;
            $this->model = new User($pkey);
            $this->model->Copy($parsed);
            $this->model->Save();

            $filter = [
                ["field" => "status", "op" => "ne", "match" => "INVALID", "type" => "string"]
            ];
            $this->view->data = $this->model->GetALL("user", $filter);
        }
        else
        {
            $this->AddMsg("Missing Data [pkey]");
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function rm_user(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # DELETE the user
        if (isset($args['id']))
        {
            $rm = new User($args['id']);
            $rm->Delete();
        }

        # Show whos left
        $this->container->set("active_page", "home");
        $this->view->Set("src/templates/user_list.php");
        $filter = [
            ["field" => "status", "op" => "ne", "match" => "INVALID", "type" => "string"]
        ];
        $this->view->data = $this->model->GetALL("user", $filter);
        return $this->buffer_response($request, $response, $args);
    }


    public function list_profiles(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        //$parsed = $request->getParsedBody();
        $filter = [
            ["field" => "company_id", "op" => "gt", "match" => 0, "type" => "int"]
        ];

        $data = $this->model->GetALL("user_profile", $filter);
        $this->view->Set("src/templates/profile_list.php");
        $this->view->data = $data;

        return $this->buffer_response($request, $response, $args);
    }

    public function get_profile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("src/templates/profile_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $this->model = new UserProfile($pkey);
        $this->view->data = $this->model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_profile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("src/templates/profile_list.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $pkey = $parsed['pkey'];
            $this->model = new UserProfile($pkey);
            $this->model->Copy($parsed);
            $this->model->Save();

            $filter = [
                ["field" => "company_id", "op" => "gt", "match" => 0, "type" => "int"]
            ];
            $this->view->data = $this->model->GetALL("user_profile", $filter);
        }
        else
        {
            $this->AddMsg("Missing Data [pkey]");
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function rm_profile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # DELETE the user
        if (isset($args['id']))
        {
            $rm = new User($args['id']);
            $rm->Delete();
        }

        # Show whos left
        $this->container->set("active_page", "home");
        $this->view->Set("src/templates/profile_list.php");
        $filter = [
            ["field" => "company_id", "op" => "gt", "match" => 0, "type" => "int"]
        ];
        $this->view->data = $this->model->GetALL("user", $filter);
        return $this->buffer_response($request, $response, $args);
    }

    ## Permission handlers ##
    public function list_permissions(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # This works OK. I would like to replace the ExceptionHandler with my own
        # TODO: ^THAT^
        try
        {
            $this->container->set("active_page", "admin");
            $this->model = new Permission();
            $this->model->Connect($this->container);
            $this->view->Set("src/templates/crm/permission_list.php");
            $this->view->data = $this->model->GetALL("permissions", null);
        }
        catch (Throwable $exp)
        {
            $this->HandleException($exp);
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function get_permission(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "admin");
        $this->view->Set("src/templates/crm/permission_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $this->model = new Permission($pkey);
        $this->model->Connect($this->container);
        $this->view->data = $this->model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_permission(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "admin");
        $this->view->Set("src/templates/crm/permission_edit.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $act = isset($parsed['act']) ? (int) $parsed['act'] : 1;
            $pkey = $parsed['pkey'];
            $this->model = new Permission($pkey);
            $this->model->Connect($this->container);
            if ($act === -1) # Delete Button
            {
                $this->model->Delete();
                $this->AddMsg("Permission #{$pkey} was Deleted");

                // Go back to listing
                $this->view->Set("src/templates/crm/permission_list.php");
                $this->model = new Permission();
                $this->model->Connect($this->container);
                $this->view->data = $this->model->GetALL("permissions", null);
            }
            else
            {
                $this->model->Copy($parsed);
                $this->model->Save();
                $this->AddMsg("Permission #{$pkey} was Updated");
                $this->view->data = $this->model;
            }
        }
        else
        {
            $this->AddMsg("Missing Data [pkey]");
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function list_roles(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # This works OK. I would like to replace the ExceptionHandler with my own
        # TODO: ^THAT^
        try
        {
            $this->container->set("active_page", "admin");
            $this->model = new Role();
            $this->model->Connect($this->container);
            $this->view->Set("src/templates/crm/role_list.php");
            $this->view->data = $this->model->GetALL("roles", null);
        }
        catch (Throwable $exp)
        {
            $this->HandleException($exp);
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function get_role(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "admin");
        $this->view->Set("src/templates/crm/role_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $this->model = new Role($pkey);
        $this->model->Connect($this->container);
        $this->view->data = $this->model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_role(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "admin");
        $this->view->Set("src/templates/crm/role_edit.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $act = isset($parsed['act']) ? (int) $parsed['act'] : 1;
            $pkey = $parsed['pkey'];
            $this->model = new Role($pkey);
            $this->model->Connect($this->container);
            if ($act === -1) # Delete Button
            {
                $this->model->Delete();
                $this->AddMsg("Role #{$pkey} was Deleted");

                // Go back to listing
                $this->view->Set("src/templates/crm/role_list.php");
                $this->model = new Role();
                $this->model->Connect($this->container);
                $this->view->data = $this->model->GetALL("roles", null);
            }
            else
            {
                $this->model->Copy($parsed);
                $this->model->Save();
                $this->AddMsg("Role #{$pkey} was Updated");
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
