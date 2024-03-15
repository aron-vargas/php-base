<?php
namespace Freedom\Controllers;

use Freedom\Models\CDModel;
use Freedom\Views\BlogView;
use Freedom\Views\ErrorView;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Bloger extends CDController
{
    protected $container;
    public $model;
    public $view;

    /**
     * Create a new instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->view = new BlogView($container);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->buffer_response($request, $response, $args);
    }

    static public function AddRoutes($app)
    {
        // General Get Page
        $app->get('/blog/{page}[/{act}[/{pkey:[0-9]+}]]', [Bloger::class, 'get_act']);
        // General Post Page
        $app->post('/blog/{page}[/{act}]', [Bloger::class, 'post_act']);
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
        else if ($action == 'save-img')
        {
            if (isset($_FILES["image_file"]))
            {
                # The uploaded image can be set as one of the following:
                # image_large, image_medium, or image_thumbnail
                # blog_image contains the target field
                $image = $_FILES["image_file"];
                $uploads_dir = $this->container->get('image_upload_dir');
                $imagePath = "{$uploads_dir}/{$image['name']}";

                # First remove the old image
                if (file_exists($imagePath))
                    unlink($imagePath);

                # Find root directory
                $base_dir = dirname(dirname(__DIR__));
                # Save new image
                move_uploaded_file($image["tmp_name"], $base_dir.$imagePath);

                if (isset($req['blog_image']))
                {
                    # Update the image src/path in the post
                    $model->Change($req['blog_image'], $imagePath);
                }
            }
            $display = "edit";
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
            $model->Copy($req);
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
     * @param \Freedom\Views\CDView
     */
    public function SetView($newView)
    {
        $state = $this->view->GetState();
        $newView->SetState($state);
        $this->view = $newView;
    }

    public function get_act(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # Parse the request and args
        $section = "blog";
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $act = (isset($args['act'])) ? CDModel::Clean($args['act']) : "show";
        $pkey = (isset($args['pkey'])) ? CDModel::Clean($args['pkey']) : 0;

        //$this->view->active_page = $view;
        $model = $this->view->InitModel($section, $page, $pkey);
        $model->Connect($this->container);

        # Perform action
        $display = $this->ActionHandler($model, $act, $_GET);

        # Setup the display
        $this->view->InitDisplay($section, $page, $display);

        return $this->buffer_response($request, $response, $args);
    }
    public function post_act(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # Parse the route args
        $section = "blog";
        $page = (isset($args['page'])) ? CDModel::Clean($args['page']) : "home";
        $act = (isset($args['act'])) ? CDModel::Clean($args['act']) : "edit";

        # Get pkey from POST
        $pkey = (isset($_POST['pkey'])) ? CDModel::Clean($_POST['pkey']) : 0;

        # Create the model
        $model = $this->view->InitModel($section, $page, $pkey);
        $model->Connect($this->container);

        # Perform action
        $display = $this->ActionHandler($model, $act, $_POST);

        # Setup the display
        $this->view->InitDisplay($section, $page, $display);

        return $this->buffer_response($request, $response, $args);
    }
}
