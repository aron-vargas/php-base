<?php

class CDSession
{
	public $user;

	public $auth = false;

    public $controller;

    public $dbh;

    public $config;

    /**
     * Create a new instance
     */
	public function __construct()
	{
        $this->config = json_decode(file_get_contents("include/config.json"));
		$this->user = new User();
        if (!isset($_SESSION['ACTIVE_PAGE']))
           $_SESSION['ACTIVE_PAGE'] = "home";
        $_SESSION['APPSESSION'] = $this;
        $this->Init();
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
            $session = $_SESSION['APPSESSION'];

            $error = "<h2>Unable to make a database connection!</h2>";
            $error .= "<div class='error-info'>{$exp->getMessage()}</div>";
            $error .= "<div class='error-info text-small'>{$exp->getTraceAsString()}</div>";
            $session->controller->AddMsg($error);

            if ($session->config->exit_on_error)
            {
                $session->controller->view->render();
                exit();
            }
        }
    }

	public function End()
	{
		$this->auth = false;

		if ($this->user->pkey)
		{
			$sth = $this->dbh->prepare("DELETE FROM user_session WHERE user_id = ?");
			$sth->bindValue(1, $this->user->pkey, PDO::PARAM_INT);
			$sth->execute();
		}

		if (isset($_COOKIE["CDSESSIONID"]))
		{
			$sth = $this->dbh->prepare("DELETE FROM user_session WHERE session_id = ?");
			$sth->bindValue(1, $_COOKIE["CDSESSIONID"], PDO::PARAM_STR);
			$sth->execute();
		}

		setcookie('CDSESSIONID', false);
		session_destroy();
		unset($_SESSION);
		unset($_COOKIE);
	}

    public function Init($ControllerClass = "CDController", $ModelClass = "CDModel", $ViewClass = "CDView")
    {
		$this->controller = new $ControllerClass($ModelClass, $ViewClass);
        $_SESSION['APPCONTROLLER'] = $this->controller;
    }

	public function Insert()
	{
		$session_id = sha1($this->user->pkey . time() . rand(1, 1000));
		setcookie("CDSESSIONID", $session_id);

		$sth = $this->dbh->prepare("DELETE FROM user_session WHERE user_id = ?");
		$sth->bindValue(1, $this->user->pkey, PDO::PARAM_INT);
		$sth->execute();

		$sth = $this->dbh->prepare("INSERT INTO user_session (session_id, user_id, session_type, timestamp) VALUES (?,?,?,?)");
		$sth->bindValue(1, $session_id, PDO::PARAM_STR);
		$sth->bindValue(2, $this->user->pkey, PDO::PARAM_INT);
		$sth->bindValue(3, $this->user->user_type, PDO::PARAM_INT);
		$sth->bindValue(4, time(), PDO::PARAM_INT);
		$sth->execute();
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
				$this->controller->SetTemplate("include/templates/login_form.php");
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
				$this->controller->AddMsg("Invalid login credentials. Please try again.");
				$this->controller->SetTemplate("include/templates/login_form.php");
			}
		}

		return $this->auth;
	}
	public function Load($session_id)
	{
        $valid = false;
		$oneday = time() - 86400; # 1 Day old

		$sth = $this->dbh->prepare("SELECT user_id, session_type, timestamp FROM user_session WHERE session_id = ?");
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
				$this->controller->AddMsg("Session not found or is expired. [$session_id] Please login.");
			}
		}

		return $valid;
	}
}
