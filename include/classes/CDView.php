<?php

class CDView
{
	public $header = "include/templates/header.php";
	public $template = "include/templates/home.php";
	public $footer = "include/templates/footer.php";

	public $message;
	public $css = array();
	public $js = array();

	public $model;

	private $debug = false;

	public function __construct($model = null)
	{
		$this->css['main'] = "<link rel='stylesheet' type='text/css' href='style/main.css' media='all'>";
		$this->css['bootstrap'] = "<link rel='stylesheet' type='text/css' href='vendor/twbs/bootstrap/dist/css/bootstrap.min.css' media='all'>";
		$this->css['jquery-ui'] = "<link rel='stylesheet' type='text/css' href='vendor/components/jqueryui/themes/base/all.css' media='all'>";
		$this->css['datatable'] = "<link rel='stylesheet' type='text/css' href='js/DataTables-1.12.1/css/jquery.dataTables.min.css'/>";
        $this->css['jquery-ui'] = "<link rel='stylesheet' type='text/css' href='vendor/components/font-awesome/css/all.css' media='all'>";
		$this->css['imgpicker'] = "<link rel='stylesheet' type='text/css' href='style/image-picker.css'/>";

		$this->js['bootstrap'] = "<script type='text/javascript' src='vendor/twbs/bootstrap/dist/js/bootstrap.min.js'></script>";
		$this->js['jquery'] = "<script type='text/javascript' src='vendor/components/jquery/jquery.min.js'></script>";
		$this->js['jquery-ui'] = "<script type='text/javascript' src='vendor/components/jqueryui/jquery-ui.min.js'></script>";
		$this->js['datatable'] = "<script type='text/javascript' src='js/datatables.min.js'></script>";
		$this->js['imgpicker'] = "<script type='text/javascript' src='js/image-picker.min.js'></script>";

		$this->model = $model;
	}

	public function render_header()
	{
		include($this->header);

		$this->menu();
	}

    public function render_body()
	{
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
	}

    public function render_footer()
	{
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

		echo "
		<header class='text-bg-dark'>
			<nav class='navbar navbar-default navbar-fixed-top bg-dark'>
				<div class='container d-flex flex-wrap'>
					<span class='px-4'>
						<img class='round-logo' src='images/logo.png' height='40'/>
					</span>
					<ul class='nav me-auto'>
						<li><a href='index.php?v=home' class='nav-link px-2 $home_class'>Home</a></li>
						<li><a href='index.php?v=membership' class='nav-link px-2 $race_class'>Membership</a></li>
						<li><a href='index.php?v=events' class='nav-link px-2 $betters_class'>Events</a></li>
                        <li><a href='index.php?v=about' class='nav-link px-2 $betters_class'>About</a></li>
					</ul>
					{$buttons}
				</div>
			</nav>";

		if ($session->auth)
		{
			echo "
			<nav class='navbar navbar-default navbar-fixed-top bg-light border-bottom p-0'>
				<div class='container d-flex flex-wrap'>
					<ul class='nav me-auto'>
						<li class='nav-item'><a href='index.php?v=entries' class='nav-link link-dark px-2 text-underline'>My Schedule</a></li>
						<li class='nav-item'><a href='index.php?v=betters' class='nav-link link-dark px-2'>Resources</a></li>
						<li class='nav-item'><a href='index.php?v=bets' class='nav-link link-dark px-2'>Rates</a></li>
						<li class='nav-item'><a href='index.php?v=odds' class='nav-link link-dark px-2'>Inquiry</a></li>
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
