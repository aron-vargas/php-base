<?php
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
        $this->view->Set("freedom/templates/user_list.php");
        $this->view->data = $data;

        return $this->buffer_response($request, $response, $args);
    }

    public function get_user(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("freedom/templates/user_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $this->model = new User($pkey);
        $this->view->data = $this->model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_user(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("freedom/templates/user_list.php");
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
        $this->view->Set("freedom/templates/user_list.php");
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
        $this->view->Set("freedom/templates/profile_list.php");
        $this->view->data = $data;

        return $this->buffer_response($request, $response, $args);
    }

    public function get_profile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("freedom/templates/profile_edit.php");
        $pkey = (isset($args['id'])) ? $args['id'] : null;
        $this->model = new UserProfile($pkey);
        $this->view->data = $this->model;

        return $this->buffer_response($request, $response, $args);
    }
    public function update_profile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("freedom/templates/profile_list.php");
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
        $this->view->Set("freedom/templates/profile_list.php");
        $filter = [
            ["field" => "company_id", "op" => "gt", "match" => 0, "type" => "int"]
        ];
        $this->view->data = $this->model->GetALL("user", $filter);
        return $this->buffer_response($request, $response, $args);
    }
}
