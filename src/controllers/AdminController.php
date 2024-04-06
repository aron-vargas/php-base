<?php
namespace Freedom\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Freedom\Models\CDModel;
use Freedom\Models\User;
use Freedom\Models\UserProfile;
use Freedom\Models\Role;
use Freedom\Models\Permission;

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

    static public function AddRoutes($app)
    {

    }

    public function list_users(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "home");
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

        $data = CDModel::GetALL("user", $filter);
        $this->view->Set("src/templates/user_list.php");
        $this->view->data = $data;

        return $this->buffer_response($request, $response, $args);
    }

    public function get_user(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "home");
        $this->view->Set("src/templates/user_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $usr = new User($pkey);
        $this->view->data = $usr;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_user(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "home");
        $this->view->Set("src/templates/user_list.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $pkey = (isset($parsed['pkey'])) ? $parsed['pkey'] : null;
            $model = new User($pkey);
            $model->Copy($parsed);
            $model->Save();

            $filter = [
                ["field" => "status", "op" => "ne", "match" => "INVALID", "type" => "string"]
            ];
            $this->view->data = $model->GetALL("user", $filter);
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
        //$this->container->set("active_page", "home");
        $this->view->Set("src/templates/user_list.php");
        $filter = [
            ["field" => "status", "op" => "ne", "match" => "INVALID", "type" => "string"]
        ];
        $this->view->data = CDModel::GetALL("user", $filter);
        return $this->buffer_response($request, $response, $args);
    }


    public function list_profiles(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "home");
        //$parsed = $request->getParsedBody();
        $filter = [
            ["field" => "company_id", "op" => "gt", "match" => 0, "type" => "int"]
        ];

        $data = CDModel::GetALL("user_profile", $filter);
        $this->view->Set("src/templates/profile_list.php");
        $this->view->data = $data;

        return $this->buffer_response($request, $response, $args);
    }

    public function get_profile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "home");
        $this->view->Set("src/templates/profile_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $model = new UserProfile($pkey);
        $this->view->data = $model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_profile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "home");
        $this->view->Set("src/templates/profile_list.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $pkey = $parsed['pkey'];
            $model = new UserProfile($pkey);
            $model->Copy($parsed);
            $model->Save();

            $filter = [
                ["field" => "company_id", "op" => "gt", "match" => 0, "type" => "int"]
            ];
            $this->view->data = CDModel::GetALL("user_profile", $filter);
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
        //$this->container->set("active_page", "home");
        $this->view->Set("src/templates/profile_list.php");
        $filter = [
            ["field" => "company_id", "op" => "gt", "match" => 0, "type" => "int"]
        ];
        $this->view->data = CDModel::GetALL("user", $filter);
        return $this->buffer_response($request, $response, $args);
    }

    ## Permission handlers ##
    public function list_permissions(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try
        {
            $this->view->Set("src/templates/crm/permission_list.php");
            $this->view->data = CDModel::GetALL("permissions", null);
        }
        catch (\Throwable $exp)
        {
            $this->HandleException($exp);
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function get_permission(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "admin");
        $this->view->Set("src/templates/crm/permission_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $model = new Permission($pkey);
        $model->Connect($this->container);
        $this->view->data = $model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_permission(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "admin");
        $this->view->Set("src/templates/crm/permission_edit.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $act = isset($parsed['act']) ? (int) $parsed['act'] : 1;
            $pkey = $parsed['pkey'];
            $model = new Permission($pkey);
            $model->Connect($this->container);
            if ($act === -1) # Delete Button
            {
                $model->Delete();
                $this->AddMsg("Permission #{$pkey} was Deleted");

                // Go back to listing
                $this->view->Set("src/templates/crm/permission_list.php");
                $model = new Permission();
                $model->Connect($this->container);
                $this->view->data = $model->GetALL("permissions", null);
            }
            else
            {
                $model->Copy($parsed);
                $model->Save();
                $this->AddMsg("Permission #{$pkey} was Updated");
                $this->view->data = $model;
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
        try
        {
            $this->view->Set("src/templates/crm/role_list.php");
            $this->view->data = CDModel::GetALL("roles", null);
        }
        catch (\Throwable $exp)
        {
            $this->HandleException($exp);
        }

        return $this->buffer_response($request, $response, $args);
    }

    public function get_role(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "admin");
        $this->view->Set("src/templates/crm/role_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $model = new Role($pkey);
        $model->Connect($this->container);
        $this->view->data = $model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_role(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->container->set("active_page", "admin");
        $this->view->Set("src/templates/crm/role_edit.php");
        $parsed = $request->getParsedBody();
        if (isset($parsed['pkey']))
        {
            $act = isset($parsed['act']) ? (int) $parsed['act'] : 1;
            $pkey = $parsed['pkey'];
            $model = new Role($pkey);
            $model->Connect($this->container);
            if ($act === -1) # Delete Button
            {
                $model->Delete();
                $this->AddMsg("Role #{$pkey} was Deleted");

                // Go back to listing
                $this->view->Set("src/templates/crm/role_list.php");
                $model = new Role();
                $model->Connect($this->container);
                $this->view->data = $model->GetALL("roles", null);
            }
            else
            {
                $model->Copy($parsed);
                $model->Save();
                $this->AddMsg("Role #{$pkey} was Updated");
                $this->view->data = $model;
            }
        }
        else
        {
            $this->AddMsg("Missing Data [pkey]");
        }

        return $this->buffer_response($request, $response, $args);
    }
}
