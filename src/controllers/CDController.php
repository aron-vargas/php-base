<?php
namespace Freedom\Controllers;

use Freedom\Models\CDModel;
use Freedom\Views\CDView;
use Freedom\Views\ErrorView;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CDController {
    protected $container;
    public $model;
    public $view;

    /**
     * Create a new instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->view = new CDView($container);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->buffer_response($request, $response, $args);
    }

    static public function AddRoutes($app)
    {
        // Home
        // Static Pages
        $app->get('/static/{page:.*}', [CDController::class, 'static']);
        // General Get Page
        $app->get('/{section}/{page}[/{act}[/{pkey:[0-9]+}]]', [CDController::class, 'get_act']);
        // General Post Page
        $app->post('/{section}/{page}[/{act}]', [CDController::class, 'post_act']);
    }

    public function ActionHandler($model, string $action = "show", array $req = array())
    {
        $ModelName = get_class($model);
        $display = "list";
        $act = isset($req['act']) ? (int) $req['act'] : 1;

        # Perform the action
        if ($action == 'save')
        {
            if ($act === -1) # Delete Button
            {
                $model->Delete();
                $this->AddMsg("$ModelName #{$model->pkey}Deleted");

                // Go back to listing
                $display = "list";
                $model->Clear();
                $filter = $model->BuildFilter($req);
                $this->view->data = $model->GetALL($model->GetTable(), $filter);
            }
            else
            {
                $model->Copy($req);
                if ($model->Validate())
                {
                    $model->Save();
                    $this->AddMsg("$ModelName #{$model->pkey} was Updated");
                }
                $display = "edit";
            }
        }
        else if ($action == 'user_roles')
        {
            $model->RMUserRoles();
            $model->RMGroupPermissions();
            $this->AddMsg("User Roles/Groups Cleared");
            if (isset ($req['roles']) && is_array($req['roles']))
            {
                foreach ($req['roles'] as $role_id)
                {
                    if ($role_id)
                    {
                        $model->AddUserRole($model->pkey, $role_id);
                        $this->AddMsg("Role ($role_id) was added");
                        $model->LoadRolePermissions($role_id, true);
                    }
                }
            }
            $display = "edit";
        }
        else if ($action == 'module-perms')
        {
            $model->RMGroupPermissions();
            $this->AddMsg("User Roles/Groups Cleared");
            if (is_array($req['permissions']))
            {
                foreach ($req['permissions'] as $module_id => $rights)
                {
                    if ($module_id)
                    {
                        # Set the bitmask
                        $perms = 0;
                        if (isset($rights['has_view'])) $perms |= $rights['has_view'];
                        if (isset($rights['has_edit'])) $perms |= $rights['has_edit'];
                        if (isset($rights['has_add'])) $perms |= $rights['has_add'];
                        if (isset($rights['has_delete'])) $perms |= $rights['has_delete'];

                        $model->AddPermission($model->pkey, $module_id, $perms);
                        $this->AddMsg("Permissions ({$model->pkey}, $module_id, $perms) was added");
                    }
                }
            }
            $display = "edit";
        }
        else if ($action == 'create')
        {
            $model->Copy($req);
            if ($model->Validate())
            {
                $model->Create();
                $this->AddMsg("$ModelName was Created");
            }
            $display = "list";
        }
        else if ($action == 'change')
        {
            $field = (isset($req['field'])) ? $req['field'] : null;
            $value = (isset($req['value'])) ? trim($req['value']) : null;

            $model->Change($field, $value);
            $this->AddMsg("$ModelName #{$model->pkey} was changed");
            $display = "list";
        }
        else if ($action == 'list')
        {
            $filter = $model->BuildFilter($req);
            $this->view->data = $model->GetALL($model->GetTable(), $filter);
            $display = "list";
        }
        else if ($action == 'show')
        {
            $filter = $model->BuildFilter($req);
            $this->view->data = $model->GetALL($model->GetTable(), $filter);
            $display = "show";
        }
        else if ($action == 'delete')
        {
            $model->Delete();
            $this->AddMsg("$ModelName #{$model->pkey} was Deleted");

            // Go back to listing
            $display = "list";
            $model->Clear();
            $filter = $model->BuildFilter($req);
            $this->view->data = $model->GetALL($model->GetTable(), $filter);

            $display = "list";
        }
        else
        {
            # Just show it (view,edit,ect..)
            $display = $action;
        }

        return $display;
    }

    /**
     * Append to the message array
     * @param string
     */
    public function AddMsg($message)
    {
        $this->view->AddMsg($message);
    }

    public function HandleException($exp)
    {
        $newView = new ErrorView($this->container);
        $this->SetView($newView);
        $this->view->AddException($exp);
    }

    /**
     * Change the view but keep its state
     * @param CDView
     */
    public function SetView($newView)
    {
        $state = $this->view->GetState();
        $newView->SetState($state);
        $this->view = $newView;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "home";
        $this->view->template = "src/templates/home.php";
        return $this->buffer_response($request, $response, $args);
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->view->active_page = "home";
        $this->view->template = "src/templates/home.php";
        return $this->buffer_response($request, $response, $args);
    }

    public function buffer_response(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        ob_start();

        try
        {
            $this->view->render();
        }
        catch (\Throwable $e)
        {
            $this->HandleException($e);
            $this->view->render();
        }

        $output = ob_get_clean();
        $response->getBody()->write($output);
        return $response;
    }

    public function static(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $path_i = pathinfo($args['page']);
        $this->view->InitDisplay(false, $args['page'], false);

        $this->AddMsg("Page Information");
        $this->AddMsg("Full Page: {$args['page']}");
        $this->AddMsg("dirname: {$path_i['dirname']}");
        $this->AddMsg("basename: {$path_i['basename']}");
        if (isset($path_i['extension']))
            $this->AddMsg("extension: {$path_i['extension']}");
        $this->AddMsg("filename: {$path_i['filename']}");

        return $this->buffer_response($request, $response, $args);
    }

    public function get_act(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->AddMsg("<pre>" . print_r($args, true) . "</pre>");

        # Parse the request and args
        $section = (isset($args['section'])) ? CDModel::Clean($args['section']) : ".";
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $act = (isset($args['act'])) ? CDModel::Clean($args['act']) : "show";
        $pkey = (isset($args['pkey'])) ? CDModel::Clean($args['pkey']) : 0;

        //$this->view->active_page = $view;
        $model = $this->view->InitModel($section, $page, $pkey);
        $model->Connect($this->container);
        //$this->AddMsg("<pre>" . print_r($model, true) . "</pre>");

        # Perform action
        $display = $this->ActionHandler($model, $act, $_GET);

        # Setup the display
        $this->view->InitDisplay($section, $page, $display);

        return $this->buffer_response($request, $response, $args);
    }
    public function post_act(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->AddMsg("<pre>" . print_r($args, true) . "</pre>");

        # Parse the route args
        $section = (isset($args['section'])) ? CDModel::Clean($args['section']) : ".";
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $act = (isset($args['act'])) ? CDModel::Clean($args['act']) : "edit";

        # Get pkey from POST Body
        $parsed = $request->getParsedBody();
        $pkey = (isset($parsed['pkey'])) ? CDModel::Clean($parsed['pkey']) : 0;

        # Create the model
        $model = $this->view->InitModel($section, $page, $pkey);
        $model->Connect($this->container);

        # Perform action
        $display = $this->ActionHandler($model, $act, $parsed);
        //$this->AddMsg("<pre>" . print_r($model, true) . "</pre>");

        # Setup the display
        $this->view->InitDisplay($section, $page, $display);

        return $this->buffer_response($request, $response, $args);
    }
}
