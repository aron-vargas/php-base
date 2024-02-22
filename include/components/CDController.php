<?php

class CDController {
    public $user;

    public $auth = false;

    protected $act = "view";
    protected $target = "home";
    protected $target_pkey;

    public $model;
    public $view;

    public $config;

    /**
     * Create a new instance
     */
    public function __construct()
    {
        $this->config = json_decode(file_get_contents("include/config.json"));
        $this->user = new User();
        if (!isset($_SESSION['ACTIVE_PAGE']))
            $_SESSION['ACTIVE_PAGE'] = $this->target;
        $_SESSION['APPCONTROLLER'] = $this;
        $this->Init();
    }

    /**
     * Append to the message array
     * @param string
     */
    public function AddMsg($message)
    {
        $this->view->AddMsg($message);
    }

    static public function DBConnection()
    {
        try
        {
            $DB = new DBSettings();
            return $DB->conn;
        }
        catch (Exception $exp)
        {
            $controller = $_SESSION['APPCONTROLLER'];

            if ($controller->config->exit_on_error)
            {
                $error = "<h2>Unable to make a database connection!</h2>";
                $error .= "<div class='error-info'>{$exp->getMessage()}</div>";
                $error .= "<div class='error-info text-small'>{$exp->getTraceAsString()}</div>";
                $controller->AddMsg($error);
                $controller->view->render();
                exit();
            }
            else
            {
                $controller->HandleException($exp);
                $controller->view->render();
            }
        }
    }

    public function End()
    {
        $this->auth = false;
        $dbh = CDController::DBConnection();

        if ($this->user->pkey)
        {
            $sth = $dbh->prepare("DELETE FROM user_session WHERE user_id = ?");
            $sth->bindValue(1, $this->user->pkey, PDO::PARAM_INT);
            $sth->execute();
        }

        if (isset($_COOKIE["CDSESSIONID"]))
        {
            $sth = $dbh->prepare("DELETE FROM user_session WHERE session_id = ?");
            $sth->bindValue(1, $_COOKIE["CDSESSIONID"], PDO::PARAM_STR);
            $sth->execute();
        }

        setcookie('CDSESSIONID', false);
        session_destroy();
        unset($_SESSION);
        unset($_COOKIE);
    }

    public function HandleException($exp)
    {
        $newView = new ErrorView();
        $this->SetView($newView);
        $this->view->AddException($exp);
    }

    public function Init($ModelClass = "CDModel", $ViewClass = "CDView")
    {
        $this->model = new $ModelClass();
        $this->view = new $ViewClass($this->model);
    }

    public function Insert()
    {
        $dbh = CDController::DBConnection();

        $session_id = sha1($this->user->pkey . time() . rand(1, 1000));
        setcookie("CDSESSIONID", $session_id);

        $sth = $dbh->prepare("DELETE FROM user_session WHERE user_id = ?");
        $sth->bindValue(1, $this->user->pkey, PDO::PARAM_INT);
        $sth->execute();

        $sth = $dbh->prepare("INSERT INTO user_session (session_id, user_id, session_type, timestamp) VALUES (?,?,?,CURRENT_TIMESTAMP)");
        $sth->bindValue(1, $session_id, PDO::PARAM_STR);
        $sth->bindValue(2, $this->user->pkey, PDO::PARAM_INT);
        $sth->bindValue(3, $this->user->user_type, PDO::PARAM_INT);
        $sth->execute();
    }

    public function Load($session_id)
    {
        $valid = false;
        $oneday = time() - 86400; # 1 Day old
        $dbh = CDController::DBConnection();

        $sth = $dbh->prepare("SELECT user_id, session_type, timestamp FROM user_session WHERE session_id = ?");
        $sth->bindValue(1, $session_id, PDO::PARAM_INT);
        $sth->execute();

        if ($data = $sth->fetch(PDO::FETCH_OBJ))
        {
            if ($data->timestamp > $oneday)
            {
                $this->user = new User($data->user_id, $data->session_type);
                $valid = true;
            }
            else
            {
                $this->AddMsg("Session not found or is expired. [$session_id] Please login.");
            }
        }

        return $valid;
    }

    public function SetModel($req)
    {
        $this->target_pkey = (isset($req['pkey'])) ? (int) $req['pkey'] : null;

        # Change the model based on target
        if (isset($req['target']))
        {
            $this->target = CDModel::Clean($req['target']);
            $ClassName = $this->target;
            $this->model = new $ClassName($this->target_pkey);
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

    public function validate()
    {
        $this->auth = false;

        if (isset($_COOKIE["CDSESSIONID"]))
        {
            if ($this->Load($_COOKIE["CDSESSIONID"]))
            {
                $this->auth = true;
            }
            else
            {
                $this->End();
                $this->view->Set("include/templates/login_form.php");
            }
        }
        else if (isset($_REQUEST['login']) && isset($_REQUEST['user_name']) && isset($_REQUEST['password']))
        {
            if ($this->user->authenticate($_REQUEST['user_name'], $_REQUEST['password']))
            {
                $this->auth = true;
                $this->Insert();
            }
            else
            {
                $this->AddMsg("Invalid login credentials. Please try again.");
                $this->view->Set("include/templates/login_form.php");
            }
        }

        return $this->auth;
    }
}
