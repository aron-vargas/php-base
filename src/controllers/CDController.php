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

    public function ActionHandler($action, $req)
    {
        $ModelName = get_class($this->model);
        $display = "list";
        $act = isset($req['act']) ? (int) $req['act'] : 1;

        # Perform the action
        if ($action == 'save')
        {
            if ($act === -1) # Delete Button
            {
                $this->model->Delete();
                $this->AddMsg("$ModelName #{$this->model->pkey}Deleted");

                // Go back to listing
                $display = "list";
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
                $display = "edit";
            }
        }
        else if ($action == 'create')
        {
            $this->model->Copy($req);
            if ($this->model->Validate())
            {
                $this->model->Create();
                $this->AddMsg("$ModelName was Created");
            }
            $display = "list";
        }
        else if ($action == 'change')
        {
            $field = (isset($req['field'])) ? $req['field'] : null;
            $value = (isset($req['value'])) ? trim($req['value']) : null;

            $this->model->Change($field, $value);
            $this->AddMsg("$ModelName #{$this->model->pkey} was changed");
            $display = "list";
        }
        else if ($action == 'list')
        {
            $filter = $this->model->BuildFilter($req);
            $this->view->data = $this->model->GetALL($this->model->GetTable(), $filter);
            $display = "list";
        }
        else if ($action == 'show')
        {
            $filter = $this->model->BuildFilter($req);
            $this->view->data = $this->model->GetALL($this->model->GetTable(), $filter);
            $display = "show";
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

            $display = "list";
        }
        else if ($action == 'edit')
        {
            $display = "edit";
            $this->view->data = $this->model;
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
        //$response->getBody()->write("This is OK right??");
        $this->container->set("active_page", "home");
        return $this->buffer_response($request, $response, $args);
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("src/templates/home.php");
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
        $name = $path_i['filename'];
        $template = "src/templates/{$path_i['dirname']}/{$name}.php";
        $this->container->set("active_page", $name);
        $this->view->Set($template);

        $this->AddMsg("Page Information");
        $this->AddMsg("Full Page: {$args['page']}");
        $this->AddMsg("dirname: {$path_i['dirname']}");
        $this->AddMsg("basename: {$path_i['basename']}");
        if (isset($path_i['extension']))
            $this->AddMsg("extension: {$path_i['extension']}");
        $this->AddMsg("filename: {$path_i['filename']}");
        $this->AddMsg("Constructed: src/templates/{$path_i['dirname']}/{$name}.php");

        return $this->buffer_response($request, $response, $args);
    }

    public function get_act(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->AddMsg("<pre>" . print_r($args, true) . "</pre>");

        # Parse the request and args
        $act = CDModel::Clean($args['act']);
        $data = $_GET;
        $this->SetupModel($args, $data);
        $this->AddMsg("<pre>" . print_r($data, true) . "</pre>");
       
        # Perform action
        $display = $this->ActionHandler($act, $args);

        # Setup the display
        $this->SetupView($args, $display);

        return $this->buffer_response($request, $response, $args);
    }
    public function post_act(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->AddMsg("<pre>" . print_r($args, true) . "</pre>");

        # Parse the request and args
        $act = CDModel::Clean($args['act']);
        $data = $request->getParsedBody();
        $this->SetupModel($args, $data);
        $this->AddMsg("<pre>" . print_r($data, true) . "</pre>");

        # Perform action
        $display = $this->ActionHandler($act, $data);

        # Setup the display
        $this->SetupView($args, $display);

        return $this->buffer_response($request, $response, $args);
    }

    protected function SetupModel(array $args, array $data)
    {
        # Determine the proper Class to use
        $BaseModel = CDModel::Clean($args['model']);
        $ModelName = "\\Freedom\\Models\\" . str_replace("-", "\\", $BaseModel);
        $pkey = (isset($data['pkey'])) ? CDModel::Clean($data['pkey']) : 0;
         
        # Create the model
        $this->model = new $ModelName($pkey);
        $this->model->Connect($this->container);
        $this->model->Copy($data);
    }

    protected function SetupView(array $args, string $display)
    {
        $section = (isset($args['section'])) ? CDModel::Clean($args['section']) : ".";
        $model_name = strtolower(basename(get_class($this->model)));
        $this->container->set("active_page", $model_name);
        $template = "src/templates/$section/{$model_name}_{$display}.php";
        $this->view->Set($template);
        $this->AddMsg("Location: $template");
    }
}
