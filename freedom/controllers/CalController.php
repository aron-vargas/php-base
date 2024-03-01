<?php
use Psr\Container\ContainerInterface;

class CalController extends CDController {
    public $user;

    public $auth = false;

    protected $act = "view";
    protected $target = "CDModel";
    protected $target_pkey;
    public $active_page = "calendar";

    public $model;
    public $view;

    /**
     * Create a new instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->view = new CDView($container);
        $this->model = new CalendarModel($container);
        $this->model->Connect($container);
    }

    /**
     * Perform requestion action
     * @param mixed
     */
    function ActionHandler($req)
    {
        $action = (isset($req['act'])) ? strtolower(CDModel::Clean($req['act'])) : null;
        $pkey = (isset($req['pkey'])) ? CDModel::Clean($req['pkey']) : null;

        if ($action == 'save' || $action == 'create')
        {
            $event = new CalEvent($pkey);
            $event->Copy($req);
            if ($event->Validate())
            {
                $event->Save();
            }
        }
        else if ($action == 'change')
        {
            $field = (isset($req['field'])) ? CDModel::Clean($req['field']) : null;
            $value = (isset($req['value'])) ? CDModel::Clean($req['value']) : null;

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
            $this->model->Reset($sel_date);
        }
        else if ($action == 'today')
        {
            $this->model->SelectDate(strtotime('today'));
        }
        else if ($action == 'sel')
        {
            if (isset($req['date']))
            {
                $sel_date = CDModel::Clean($req['date']);
                $this->model->SelectDate($sel_date);
            }
        }
        else if ($action == 'prev')
        {
            $this->model->Prev();
        }
        else if ($action == 'next')
        {
            $this->model->Next();
        }
        else if ($action == 'set_view')
        {
            if (isset($req['view']))
                $_SESSION['cal']->view = CDModel::Clean($req['view']);
        }
        else if ($action == 'getevents')
        {
            $start = (isset($req['start'])) ? CDModel::Clean($req['start']) : time();
            $end = (isset($req['end'])) ? CDModel::Clean($req['end']) : time();
            $start = CDModel::ParseTStamp($start);
            $end = CDModel::ParseTStamp($end);

            $this->view->mode = CDView::$JSON_MODE;
            $this->view->data = $this->model->GetMyEvents($start, $end);
        }
    }
}
