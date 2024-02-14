<?php

class CDView
{
	public $header = "include/templates/header.php";
	public $template = "include/templates/home.php";
	public $footer = "include/templates/footer.php";

    private $header_rendered = false;
    private $body_rendered = false;
    private $footer_rendered = false;

	private $message = array();
	private $css = array();
	private $js = array();

	private $model;

	private $debug = false;

    public $start_hour = 8;     # integer hour (25-hour format)
    public $end_hour = 17;      # integer hour (25-hour format)
    public $time_slot = 900;    # integer in seconds: (900 = 15 minutes * 60 seconds)

    /**
     * Create a new instance
     * @param CDModel
     */
	public function __construct($model = null)
	{
		$this->css['main'] = "<link rel='stylesheet' type='text/css' href='style/main.css' media='all'>";
		$this->css['bootstrap'] = "<link rel='stylesheet' type='text/css' href='vendor/twbs/bootstrap/dist/css/bootstrap.min.css' media='all'>";
		$this->css['jquery-ui'] = "<link rel='stylesheet' type='text/css' href='vendor/components/jqueryui/themes/base/all.css' media='all'>";
		$this->css['datatable'] = "<link rel='stylesheet' type='text/css' href='js/DataTables-1.12.1/css/jquery.dataTables.min.css'/>";
        $this->css['fa'] = "<link rel='stylesheet' type='text/css' href='vendor/components/font-awesome/css/all.css' media='all'>";
		$this->css['imgpicker'] = "<link rel='stylesheet' type='text/css' href='style/image-picker.css'/>";

		$this->js['bootstrap'] = "<script type='text/javascript' src='vendor/twbs/bootstrap/dist/js/bootstrap.min.js'></script>";
		$this->js['jquery'] = "<script type='text/javascript' src='vendor/components/jquery/jquery.min.js'></script>";
		$this->js['jquery-ui'] = "<script type='text/javascript' src='vendor/components/jqueryui/jquery-ui.min.js'></script>";
		$this->js['datatable'] = "<script type='text/javascript' src='js/datatables.min.js'></script>";
		$this->js['imgpicker'] = "<script type='text/javascript' src='js/image-picker.min.js'></script>";

		$this->model = $model;
	}

    /**
     * Append to the message array
     * @param string
     */
    public function AddMsg($message)
    {
        $this->message[] = $message;
    }

    public function GetState()
    {
        return array(
            "header_rendered" => $this->header_rendered,
            "body_rendered" => $this->body_rendered,
            "footer_rendered" => $this->footer_rendered
        );
    }
    public function SetState($state)
    {
        if (isset($state['header_rendered']))
            $this->header_rendered = ($state['header_rendered']);

        if (isset($state['body_rendered']))
            $this->body_rendered = ($state['body_rendered']);

        if (isset($state['footer_rendered']))
            $this->footer_rendered = ($state['footer_rendered']);
    }

    public function render()
    {
        if ($this->header_rendered == false)
        {
            $this->render_header();
            $this->header_rendered = true;
        }

        if ($this->body_rendered == false)
        {
            $this->render_body();
            $this->body_rendered = false;
        }

        if ($this->footer_rendered == false)
        {
            $this->render_footer();
            $this->footer_rendered = true;
        }
    }

	public function render_header()
	{
		include($this->header);

		$this->menu();
	}

    public function render_body()
	{
		if ($this->message)
        {
			echo "<div class='alert alert-secondary w-50 mx-auto my-1'>";
            foreach($this->message as $message)
            {
                echo "<p>{$message}</p>";
            }
            echo "</div>\n";
        }

		if ($this->debug)
		{
			echo "<div class='bebug_container'>";
			include ("include/templates/debug.php");
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

		$home_class = (strstr($this->template, "home") === false) ? "text-white" : "active";
        $membership_class = (strstr($this->template, "membership") === false) ? "text-white" : "active";
		$about_class = (strstr($this->template, "about") === false) ? "text-white" : "active";
		$calendar_class = (strstr($this->template, "calendar") === false) ? "text-white" : "active";

		echo "
		<header class='text-bg-dark'>
			<nav class='navbar navbar-default navbar-fixed-top bg-dark'>
				<div class='container d-flex flex-wrap'>
					<span class='px-4'>
						<img class='round-logo' src='images/logo.png' height='40'/>
					</span>
                    <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbar-menu' aria-controls='navbar-menu' aria-expanded='false' aria-label='Show Menu'>
                        <span class='navbar-toggler-icon'></span>
                    </button>
                    <div class='collapse navbar-collapse' id='navbar-menu'>
                        <div class='navbar-nav me-auto'>
						    <a href='index.php?v=home' class='nav-link px-2 $home_class'>Home</a>
						    <a href='index.php?v=membership' class='nav-link px-2 $membership_class'>Membership</a>
						    <a href='calendar.php' class='nav-link px-2 $calendar_class'>Events</a>
                            <a href='index.php?v=about' class='nav-link px-2 $about_class'>About</a>
                        </div>
					</div>
					{$buttons}
				</div>
			</nav>";

		if ($session->auth)
		{
			echo "
			<nav class='navbar navbar-default navbar-fixed-top bg-light border-bottom p-0'>
				<div class='container d-flex flex-wrap'>
					<ul class='nav me-auto'>
						<li class='nav-item'><a href='index.php?v=schedule' class='nav-link link-dark px-2 text-underline'>My Schedule</a></li>
						<li class='nav-item'><a href='index.php?v=resources' class='nav-link link-dark px-2'>Resources</a></li>
						<li class='nav-item'><a href='index.php?v=rates' class='nav-link link-dark px-2'>Rates</a></li>
						<li class='nav-item'><a href='index.php?v=inquiry' class='nav-link link-dark px-2'>Inquiry</a></li>
					</ul>
				</div>
			</nav>";
		}

		echo "
		</header>";
	}

	public function Set($template)
	{   $this->template = $template; }

    private function DayCells()
    {

    }
}
