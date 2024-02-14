<?php

class CDController
{
	private $act = "view";
	private $target = "home";
	private $target_pkey;

	private $model;
	private $view;

	public function __construct()
	{
		$ModelClass = isset($_SESSION["APP-Model"]) ? $_SESSION["APP-Model"] : "CDModel";
		$ViewClass = isset($_SESSION["APP-View"]) ? $_SESSION["APP-View"] : "CDView";
		
		$this->model = new $ModelClass();
		$this->view = new $ViewClass($this->model);
	}

	public function process($req)
	{
		global $session;

		$pkey = (isset($req['pkey'])) ? (int)$req['pkey'] :  null;

		# Change the model based on target
		if (isset($req['target']))
		{
			$ClassName = BaseClass::Clean($req['target']);
			$this->model = new $ClassName($pkey);
		}

		# Perform the action
		if (isset($req['act']))
		{
			$action = strtolower(BaseClass::Clean($req['act']));

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

					if ($ClassName == 'User')
					{
						// Auto login
						$session->user = $this->model;
						$session->Insert();
					}
				}
			}
			else if ($action == 'change')
			{
				$field = (isset($req['field'])) ? $req['field'] : null;
				$value = (isset($req['value'])) ? trim($req['value']) : null;

				$this->model->Change($field, $value);
				$session->user->Load();
			}
			else if ($action == 'delete')
			{
				$this->model->Delete();
			}
			else if ($action == 'edit')
			{
				$this->view->SetView($this->model->edit_view);
			}
			else if ($action == 'view')
			{
				$this->view->SetView($this->model->display_view);
			}
		}

		# Change the model based on target
		if (isset($req['v']))
		{
			$view = BaseClass::Clean($req['v']);

			if (strtolower($view) == 'login')
				$this->view->SetView("include/templates/login_form.php");
			else if (strtolower($view) == 'home')
				$this->view->SetView("include/templates/home.php");
			else if (strtolower($view) == 'entries' || strtolower($view) == 'users')
				$this->view->SetView("include/templates/user_list.php");
			else if (strtolower($view) == 'register')
				$this->view->SetView("include/templates/register_form.php");
			else if (strtolower($view) == 'race')
				$this->view->SetView("include/templates/race_info.php");
			else if (strtolower($view) == 'betters')
				$this->view->SetView("include/templates/better_list.php");
			else if (strtolower($view) == 'bets')
				$this->view->SetView("include/templates/bets.php");
			else if (strtolower($view) == 'odds')
				$this->view->SetView("include/templates/odds.php");
			else if (strtolower($view) == 'results')
				$this->view->SetView("include/templates/results.php");
			else if (strtolower($view) == 'winnings')
				$this->view->SetView("include/templates/winnings.php");
			else if (strtolower($view) == 'avatar')
				$this->view->SetView("include/templates/avatar_edit.php");
		}
	}
}
