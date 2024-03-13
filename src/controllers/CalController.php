<?php
namespace Freedom\Controllers;

use Freedom\Models\CalEvent;
use Freedom\Views\CalendarView;
use Freedom\Views\CDView;
use Freedom\Views\ErrorView;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CalController extends CDController {
    public $user;

    public $auth = false;

    protected $act = "view";
    protected $target = "CDModel";
    protected $target_pkey;

    public $model;
    public $view;

    /**
     * Create a new instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->view = new CalendarView($container);
        $this->model = new CalEvent();
        $this->model->Connect($container);
    }

    static public function AddRoutes($app)
    {
        // Main Calendar Page
        $app->get('/calendar[/{page:.*}]', [\Freedom\Controllers\CalController::class, 'show']);
        $app->post('/calendar/{action}[/{info:.*}]', [\Freedom\Controllers\CalController::class, 'process']);
        // API Calls
        $app->get('/cal', [\Freedom\Controllers\CalController::class, 'api_get']);
        $app->post('/cal', [\Freedom\Controllers\CalController::class, 'api_post']);
    }

    /**
     * Perform requestion action
     * @param mixed
     */
    function ActionHandler($model, string $action = "show", array $req = array())
    {
        $pkey = (isset($req['pkey'])) ? CalEvent::Clean($req['pkey']) : null;
        $act = isset($req['act']) ? (int) $req['act'] : 1;

        if ($action == 'save' || $action == 'create')
        {
            if ($act === -1) # Delete Button
            {
                $this->model->Delete();
                $this->AddMsg("CalEvent #{$this->model->pkey}Deleted");
                $this->model->Clear();
                //$this->view->mode = CDView::$JSON_MODE;
                //$this->view->data = $this->model->GetMyEvents($start, $end);
            }
            else
            {
                $this->model->pkey = $pkey;
                $this->model->Load();
                $this->model->Copy($req);
                if ($this->model->Validate())
                {
                    $this->model->Save();
                    $this->AddMsg("CalEvent #{$this->model->pkey}Saved");
                }
                else
                {
                    $this->AddMsg("CalEvent was invalid #{$this->model->pkey}");
                    //$this->AddMsg("<pre>".print_r($this->model->container, true)."</pre>");
                }
            }
        }
        else if ($action == 'change')
        {
            $field = (isset($req['field'])) ? CalEvent::Clean($req['field']) : null;
            $value = (isset($req['value'])) ? CalEvent::Clean($req['value']) : null;

            $event = new CalEvent($pkey);
            $event->Change($field, $value);
        }
        else if ($action == 'delete')
        {
            $event = new CalEvent($pkey);
            $event->Delete();
        }
        else if ($action == 'reset')
        {
            $sel_date = strtotime("today");
            $this->view->Reset($sel_date);
        }
        else if ($action == 'today')
        {
            $this->view->SelectDate(strtotime('today'));
        }
        else if ($action == 'sel')
        {
            if (isset($req['date']))
            {
                $sel_date = CalEvent::Clean($req['date']);
                $this->view->SelectDate($sel_date);
            }
        }
        else if ($action == 'prev')
        {
            $this->view->Prev();
        }
        else if ($action == 'next')
        {
            $this->view->Next();
        }
        else if ($action == 'set_view')
        {
            $view = (isset($req['view'])) ? CalEvent::Clean($req['view']) : 'm';
            $this->view->SetView($view);
        }
        else if ($action == 'getevents')
        {
            $start = (isset($req['start'])) ? CalEvent::Clean($req['start']) : time();
            $end = (isset($req['end'])) ? CalEvent::Clean($req['end']) : time();
            $start = CalEvent::ParseTStamp($start);
            $end = CalEvent::ParseTStamp($end);

            $this->view->mode = CDView::$JSON_MODE;
            $this->view->data = $this->model->GetMyEvents($start, $end);
        }
        else if ($action == 'event')
        {
            $start_date = (isset($_REQUEST['start_date'])) ? (int) CalEvent::Clean($_REQUEST['start_date']) : time();
            $event = new CalEvent($pkey, $start_date);
            //$this->view->mode = CDView::$JSON_MODE;
            $this->view->template = "src/templates/calendar/event.php";
            $this->view->SetEvent($event);
        }
    }
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (isset($args['page']))
        {
            $path_i = pathinfo($args['page']);
            //$this->view->InitDisplay(false, $args['page'], false);

            $this->AddMsg("Page Information");
            $this->AddMsg("Full Page: {$args['page']}");
            $this->AddMsg("dirname: {$path_i['dirname']}");
            $this->AddMsg("basename: {$path_i['basename']}");
            if (isset($path_i['extension']))
                $this->AddMsg("extension: {$path_i['extension']}");
            $this->AddMsg("filename: {$path_i['filename']}");
        }
        $action = (isset($_GET['act'])) ? strtolower(CalEvent::Clean($_GET['act'])) : 'show';

        $this->ActionHandler(null, $action, $_GET);

        return $this->buffer_response($request, $response, $args);
    }

    public function process(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $action = (isset($args['action'])) ? strtolower(CalEvent::Clean($args['action'])) : 'edit';

        $this->AddMsg("CalController::process: {$action}");

        $this->ActionHandler(null, $action, $_POST);

        return $this->buffer_response($request, $response, $args);
    }

    public function api_get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
       //$this->view->mode = CDView::$JSON_MODE;
        $action = (isset($_GET['act'])) ? strtolower(CalEvent::Clean($_GET['act'])) : 'show';

        $this->ActionHandler(null, $action, $_GET);

        return $this->buffer_response($request, $response, $args);
    }

    public function api_post(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->view->mode = CDView::$JSON_MODE;
        $action = (isset($_POST['act'])) ? strtolower(CalEvent::Clean($_POST['act'])) : 'show';

        $this->ActionHandler(null, $action, $_POST);

        return $this->buffer_response($request, $response, $args);
    }
}
