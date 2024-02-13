<?php

class CDSession
{
	public $user;
	public $controller;

	public $auth = false;

	public function __construct()
	{
		$this->user = new User();
		$this->controller = new CDController();
	}

	public function End()
	{
		global $dbh;

		$this->auth = false;

		if ($this->user->pkey)
		{
			$sth = $dbh->prepare("DELETE FROM user_session WHERE user_id = ?");
			$sth->bindValue(1, $this->user->pkey, PDO::PARAM_INT);
			$sth->execute();
		}

		if (isset($_COOKIE["PHPSESSID"]))
		{
			$sth = $dbh->prepare("DELETE FROM user_session WHERE session_id = ?");
			$sth->bindValue(1, $_COOKIE["PHPSESSID"], PDO::PARAM_STR);
			$sth->execute();
		}

		setcookie('PHPSESSID', false);
		session_destroy();
		unset($_SESSION);
		unset($_COOKIE);
	}


	public function Insert()
	{
		global $dbh;

		$session_id = sha1($this->user->pkey . time() . rand(1, 1000));
		setcookie("PHPSESSID", $session_id);

		$sth = $dbh->prepare("DELETE FROM user_session WHERE user_id = ?");
		$sth->bindValue(1, $this->user->pkey, PDO::PARAM_INT);
		$sth->execute();

		$sth = $dbh->prepare("INSERT INTO user_session (session_id, user_id, type, timestamp) VALUES (?,?,?,?)");
		$sth->bindValue(1, $session_id, PDO::PARAM_STR);
		$sth->bindValue(2, $this->user->pkey, PDO::PARAM_INT);
		$sth->bindValue(3, $this->user->type, PDO::PARAM_INT);
		$sth->bindValue(4, time(), PDO::PARAM_INT);
		$sth->execute();
	}

	public function validate()
	{
		global $session;

		$this->auth = false;

		if (isset($_COOKIE["PHPSESSID"]))
		{
			if ($this->Load($_COOKIE["PHPSESSID"]))
			{
				$this->auth = true;
			}
			else
			{
				$session->End();
				$this->controller->view->SetView("include/templates/login_form.php");
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
				$this->controller->view->message = "Invalid login credentials. Please try again.";
				$this->controller->view->SetView("include/templates/login_form.php");
			}
		}

	  //  if (!$this->auth)
	  //  {
	  //      throw new Exception($this->controller->view->message);
	  //  }

		return $this->auth;
	}
	public function Load($session_id)
	{
		global $dbh;

		$valid = false;
		$oneday = time() - 86400; # 1 Day old

		$sth = $dbh->prepare("SELECT user_id, type, timestamp FROM user_session WHERE session_id = ?");
		$sth->bindValue(1, $session_id, PDO::PARAM_INT);
		$sth->execute();

		if ($data = $sth->fetch(PDO::FETCH_OBJ))
		{
			if ($data->timestamp > $oneday)
			{
				$this->user = new User($data->user_id, $data->type);
				$valid = true;

				//echo "<div style='font-size: 8pt'>{$session_id}, ($data->user_id) + ($data->type) </div>";
			}
			else
			{
				$this->controller->view->message = "Session not found or is expired. [{$_COOKIE["PHPSESSID"]}] Please login.";
			}
		}

		return $valid;
	}
}
