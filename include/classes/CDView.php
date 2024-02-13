<?php

class CDView
{
	public $header = "include/templates/header.php";
	public $template = "include/templates/home.php";
	public $footer = "include/templates/footer.php";

	public $message;
	public $css = array();
	public $js = array();

	private $debug = false;

	public function __construct($model = null)
	{
		$this->css['main'] = "<link rel='stylesheet' type='text/css' href='style/main.css' media='all'>";
		$this->css['bootstrap'] = "<link rel='stylesheet' type='text/css' href='bootstrap/css/bootstrap.min.css' media='all'>";
		$this->css['jquery-ui'] = "<link rel='stylesheet' type='text/css' href='jquery/jquery-ui.min.css' media='all'>";
		$this->css['datatable'] = "<link rel='stylesheet' type='text/css' href='js/DataTables-1.12.1/css/jquery.dataTables.min.css'/>";
		$this->css['imgpicker'] = "<link rel='stylesheet' type='text/css' href='style/image-picker.css'/>";

		$this->js['bootstrap'] = "<script type='text/javascript' src='bootstrap/js/bootstrap.min.js'></script>";
		$this->js['jquery'] = "<script type='text/javascript' src='js/jquery-3.6.0.min.js'></script>";
		$this->js['jquery-ui'] = "<script type='text/javascript' src='jquery/jquery-ui.min.js'></script>";
		$this->js['datatable'] = "<script type='text/javascript' src='js/datatables.min.js'></script>";
		$this->js['imgpicker'] = "<script type='text/javascript' src='js/image-picker.min.js'></script>";
	}

	public function render()
	{
		include($this->header);

		$this->menu();

		if ($this->message)
			echo "\n<div class='alert alert-secondary w-50 mx-auto my-1'><p>{$this->message}</p></div>\n";

		if ($this->debug)
		{
			echo "
			<div class='bebug_container'>";
				include ("debug.php");
			echo "</div>\n";
		}
		include($this->template);
		include($this->footer);
	}

	public function Empty()
	{   return empty($this->template);  }

	public function menu()
	{
		global $session;

		if ($session->user->pkey)
		{
			$buttons = "
			<div class='float-end'>
				<span class=''>
					<a href='index.php?v=avatar'><span class='rounded-circle avatar {$session->user->avatar}'>&nbsp;</span></a>
					<a href='index.php?act=edit&target=User&pkey={$session->user->pkey}'>{$session->user->first_name} {$session->user->last_name}</a>
				</span>
				<a class='btn btn-outline-light me-2' href='logout.php'>Logout</a>
			</div>";
		}
		else
		{
			$buttons = "
			<div class='float-end'>
				<a class='btn btn-outline-light me-2' href='login.php'>Login</a>
				<a class='btn btn-warning' href='index.php?v=register'>Register</a>
			</div>";
		}

		$home_class = (strstr($session->controller->view->template, "home.php") === false) ? "text-white" : "text-secondary";
		$race_class = (strstr($session->controller->view->template, "race_info") === false) ? "text-white" : "text-secondary";
		$betters_class = (strstr($session->controller->view->template, "better_list") === false) ? "text-white" : "text-secondary";
		$kara_class = (strstr($session->controller->view->template, "kara") === false) ? "text-white" : "text-secondary";
		$derek_class = (strstr($session->controller->view->template, "derek") === false) ? "text-white" : "text-secondary";

		echo "
		<header class='text-bg-dark'>
			<nav class='navbar navbar-default navbar-fixed-top bg-dark'>
				<div class='container d-flex flex-wrap'>
					<span class='px-4'>
						<img src='images/K&D (1).png' height='40'/>
					</span>
					<ul class='nav me-auto'>
						<li><a href='index.php?v=home' class='nav-link px-2 $home_class'>Home</a></li>
						<li><a href='index.php?v=race' class='nav-link px-2 $race_class'>Race Info</a></li>
						<li><a href='index.php?v=betters' class='nav-link px-2 $betters_class'>Participants</a></li>
<!--
						<li><a href='index.php?v=kara' class='nav-link px-2 $kara_class'>Kara's Secrets</a></li>
						<li><a href='index.php?v=derek' class='nav-link px-2 $derek_class'>Derek's Secrets</a></li>
-->
					</ul>
					{$buttons}
				</div>
			</nav>";

		if ($session->auth)
		{
			echo "
			<nav class='navbar navbar-default navbar-fixed-top bg-light border-bottom'>
				<div class='container d-flex flex-wrap'>
					<ul class='nav me-auto'>
						<li class='nav-item'><a href='index.php?v=entries' class='nav-link link-dark px-2 text-underline'>Entries</a></li>
						<li class='nav-item'><a href='index.php?v=betters' class='nav-link link-dark px-2'>Betters</a></li>
						<li class='nav-item'><a href='index.php?v=bets' class='nav-link link-dark px-2'>Bets</a></li>
						<li class='nav-item'><a href='index.php?v=odds' class='nav-link link-dark px-2'>Odds</a></li>
						<li class='nav-item'><a href='index.php?v=results' class='nav-link link-dark px-2'>Results</a></li>
						<li class='nav-item'><a href='index.php?v=winnings' class='nav-link link-dark px-2'>Winnings</a></li>
					</ul>
				</div>
			</nav>";
		}

		echo "
		</header>";
	}

	public function SetView($template)
	{   $this->template = $template; }
}
