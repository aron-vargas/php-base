<?php
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
        $this->model = new CDmodel();
        $this->model->Connect($container);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->buffer_response($request, $response, $args);
    }

    public function ActionHandler($req)
    {
        # Perform the action
        if (isset($req['act']))
        {
            $action = (isset($req['act'])) ? $req['act'] : null;

            if ($action == 'save')
            {
                $this->model->Copy($req);
                $this->model->Save();
            }
            else if ($action == 'create')
            {
                $this->model->Copy($req);
                if ($this->model->Validate())
                {
                    $this->model->Create();
                }
            }
            else if ($action == 'change')
            {
                $field = (isset($req['field'])) ? $req['field'] : null;
                $value = (isset($req['value'])) ? trim($req['value']) : null;

                $this->model->Change($field, $value);
            }
            else if ($action == 'delete')
            {
                $this->model->Delete();
            }
        }
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

    public function Process($req)
    {
        # Perform the action
        if (isset($req['act']))
        {
            $action = strtolower(CDModel::Clean($req['act']));

            if (isset($req['target']))
                $ClassName = CDModel::Clean($req['target']);

            if (isset($req['pkey']))
                $this->target_pkey = CDModel::Clean($req['pkey']);
        }
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
        $this->container->set("active_page", "home");
        return $this->buffer_response($request, $response, $args);
    }

    public function home(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->container->set("active_page", "home");
        $this->view->Set("freedom/templates/home.php");
        return $this->buffer_response($request, $response, $args);
    }

    public function buffer_response(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        ob_start();

        try
        {
            $this->view->render();
        }
        catch (Throwable $e)
        {
            $this->HandleException($e);
            $this->view->render();
        }

        $output = ob_get_clean();
        $response->getBody()->write($output);
        return $response;
    }
}
