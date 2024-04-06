<?php
namespace Freedom\Controllers;

use Freedom\Models\Schedule\Event;
use Freedom\Views\CalendarView;
use Freedom\Views\CDView;
use Freedom\Views\ErrorView;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Scheduler extends CDController {
    /**
     * Create a new instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->view = new CalendarView($container);
    }

    static public function AddRoutes($app)
    {
        // Main Calendar Page
        $app->get('/calendar[/{page}[/{pkey:[0-9]+}]]', [\Freedom\Controllers\Scheduler::class, 'show']);
        $app->post('/calendar/{page}[/{act}]', [\Freedom\Controllers\Scheduler::class, 'process']);

        // API Calls
        $app->get('/cal', [\Freedom\Controllers\Scheduler::class, 'api_get']);
        $app->post('/cal', [\Freedom\Controllers\Scheduler::class, 'api_post']);
    }

    /**
     * Perform requestion action
     * @param mixed
     */
    function ActionHandler($model, string $action = "show", array $req = array())
    {
        $pkey = (isset($req['pkey'])) ? Event::Clean($req['pkey']) : null;
        $act = isset($req['act']) ? (int) $req['act'] : 1;

        if ($action == 'save' || $action == 'create')
        {
            if ($act === -1) # Delete Button
            {
                $model->Delete();
                $this->AddMsg("Event #{$model->pkey}Deleted");
                $model->Clear();
                //$this->view->mode = CDView::$JSON_MODE;
                //$this->view->data = $model->GetMyEvents($start, $end);
            }
            else
            {
                $model->pkey = $pkey;
                $model->Load();
                $model->Copy($req);
                if ($model->Validate())
                {
                    $model->Save();
                    $this->AddMsg("Event #{$model->pkey}Saved");
                }
                else
                {
                    $this->AddMsg("Event was invalid #{$model->pkey}");
                }
            }
        }
        else if ($action == 'change')
        {
            $field = (isset($req['field'])) ? Event::Clean($req['field']) : null;
            $value = (isset($req['value'])) ? Event::Clean($req['value']) : null;

            $event = new Event($pkey);
            $event->Change($field, $value);
        }
        else if ($action == 'delete')
        {
            $event = new Event($pkey);
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
                $sel_date = Event::Clean($req['date']);
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
            $view = (isset($req['view'])) ? Event::Clean($req['view']) : 'm';
            $this->view->SetView($view);
        }
        else if ($action == 'getevents')
        {
            $start = (isset($req['start'])) ? Event::Clean($req['start']) : time();
            $end = (isset($req['end'])) ? Event::Clean($req['end']) : time();
            $start = Event::ParseTStamp($start);
            $end = Event::ParseTStamp($end);

            $this->view->mode = CDView::$JSON_MODE;
            $this->view->data = $model->GetMyEvents($start, $end);
        }
        else if ($action == 'event')
        {
            $start_date = (isset($_REQUEST['start_date'])) ? (int) Event::Clean($_REQUEST['start_date']) : time();
            $event = new Event($pkey, $start_date);
            //$this->view->mode = CDView::$JSON_MODE;
            $this->view->template = "src/templates/calendar/event.php";
            $this->view->SetEvent($event);
        }
    }
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $page = (isset($args['page'])) ? strtolower(Event::Clean($args['page'])) : 'index';
        $pkey = (isset($args['pkey'])) ? (int) Event::Clean($args['pkey']) : 0;

        $model = $this->view->InitModel("calendar", $page, $pkey);
        $this->AddMsg(print_r($args, true));
        $model->Connect($this->container);

        $this->ActionHandler(null, $page, $_GET);
        $this->view->InitDisplay("calendar", $page, "show");

        return $this->buffer_response($request, $response, $args);
    }

    public function process(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $action = (isset($args['action'])) ? strtolower(Event::Clean($args['action'])) : 'edit';

        $this->AddMsg("Scheduler::process: {$action}");

        $this->ActionHandler(null, $action, $_POST);

        return $this->buffer_response($request, $response, $args);
    }

    public function api_get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->view->mode = CDView::$JSON_MODE;
        $action = (isset($_GET['act'])) ? strtolower(Event::Clean($_GET['act'])) : 'show';
        $pkey = (isset($_GET['pkey'])) ? (int) $_GET['pkey'] : 0;
        $start_date = (isset($_GET['start_date'])) ? (int) $_GET['start_date'] : null;

        $model = $this->view->InitModel("calendar", $action, $pkey, $start_date);

        $this->ActionHandler($model, $action, $_GET);

        return $this->buffer_response($request, $response, $args);
    }

    public function api_post(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        //$this->view->mode = CDView::$JSON_MODE;
        $action = (isset($_POST['act'])) ? strtolower(Event::Clean($_POST['act'])) : 'show';
        $pkey = (isset($_POST['pkey'])) ? (int) $_POST['pkey'] : 0;

        $model = $this->view->InitModel("calendar", $action, $pkey);

        $this->ActionHandler($model, $action, $_POST);

        return $this->buffer_response($request, $response, $args);
    }
}
