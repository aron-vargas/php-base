<?php

class CDController
{
	private $act = "view";
	private $target = "home";
	private $target_pkey;

	public $model;
	public $view;

    /**
     * Create a new instance
     */
    public function __construct($ModelClass = "CDModel", $ViewClass = "CDView")
	{
		$this->model = new $ModelClass();
		$this->view = new $ViewClass($this->model);
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
        $newView = new ErrorView();
        $this->SetView($newView);
        $this->view->AddException($exp);
    }

	public function process($req)
	{
		$pkey = (isset($req['pkey'])) ? (int)$req['pkey'] :  null;

		# Change the model based on target
		if (isset($req['target']))
		{
			$ClassName = CDModel::Clean($req['target']);
			$this->model = new $ClassName($pkey);
		}

		# Perform the action
		if (isset($req['act']))
		{
			$action = strtolower(CDModel::Clean($req['act']));

			$this->model->ActionHandler($action, $req);
		}
	}

    public function SetTemplate($req)
	{
		if (isset($req['act']))
		{
	        # Change the view based on "act" parameter
    	    $action = strtolower(CDModel::Clean($req['act']));

			if ($action == 'edit')
			{
				$this->view->Set($this->model->edit_view);
			}
			else if ($action == 'view')
			{
				$this->view->Set($this->model->display_view);
			}
		}

		# Change the view based on "v" parameter
		if (isset($req['v']))
		{
			$view = CDModel::Clean($req['v']);

			if (strtolower($view) == 'login')
				$this->view->Set("include/templates/login_form.php");
			else if (strtolower($view) == 'home')
				$this->view->Set("include/templates/home.php");
			else if (strtolower($view) == 'entries' || strtolower($view) == 'users')
				$this->view->Set("include/templates/user_list.php");
			else if (strtolower($view) == 'register')
				$this->view->Set("include/templates/register_form.php");
			else if (strtolower($view) == 'race')
				$this->view->Set("include/templates/race_info.php");
			else if (strtolower($view) == 'betters')
				$this->view->Set("include/templates/better_list.php");
			else if (strtolower($view) == 'bets')
				$this->view->Set("include/templates/bets.php");
			else if (strtolower($view) == 'odds')
				$this->view->Set("include/templates/odds.php");
			else if (strtolower($view) == 'results')
				$this->view->Set("include/templates/results.php");
			else if (strtolower($view) == 'winnings')
				$this->view->Set("include/templates/winnings.php");
			else if (strtolower($view) == 'avatar')
				$this->view->Set("include/templates/avatar_edit.php");
		}
        # Change the model based on "view" parameter
		else if (isset($req['view']))
		{
            $ClassName = CDModel::Clean($req['view']);
            $this->view = new $ClassName($this->model);
        }
        else if (is_string($req) && file_exists($req))
        {
            $this->view->Set($req);
        }
	}

    public function SetView($newView)
    {
        $state = $this->view->GetState();
        $newView->SetState($state);
        $this->view = $newView;
    }
}
