<?php

class CalendarView extends CDView
{
	public $header = "include/templates/header.php";
	public $template = "include/templates/calendar.php";
	public $footer = "include/templates/footer.php";

	public $message;

	public $view = "m";		# string [m,w,ww,d]
	public $cur_date;		# integer (unix timestamp)
	public $sel_date;      # integer (unix timestamp)
	public $css = array();
	public $js = array();

	private $debug = false;

	public function __construct($model = null)
	{
		$this->css['main'] = "<link rel='stylesheet' type='text/css' href='style/main.css' media='all'>";
		$this->css['cal'] = "<link rel='stylesheet' type='text/css' href='style/calendar.css' media='all'>";
		$this->css['bootstrap'] = "<link rel='stylesheet' type='text/css' href='vendor/twbs/bootstrap/dist/css/bootstrap.min.css' media='all'>";
		$this->css['jquery-ui'] = "<link rel='stylesheet' type='text/css' href='vendor/components/jqueryui/themes/base/all.css' media='all'>";
        $this->css['fa'] = "<link rel='stylesheet' type='text/css' href='vendor/components/font-awesome/css/all.css' media='all'>";

		$this->js['bootstrap'] = "<script type='text/javascript' src='vendor/twbs/bootstrap/dist/js/bootstrap.min.js'></script>";
		$this->js['jquery'] = "<script type='text/javascript' src='vendor/components/jquery/jquery.min.js'></script>";
		$this->js['jquery-ui'] = "<script type='text/javascript' src='vendor/components/jqueryui/jquery-ui.min.js'></script>";
		$this->js['cal'] = "<script type='text/javascript' src='js/calendar.js'></script>";
	}

    public function render_body()
	{
		if ($this->message)
			echo "\n<div class='alert alert-secondary w-50 mx-auto my-1'><p>{$this->message}</p></div>\n";

	
		include($this->template);
	}

	public function SetView($template)
	{
		$this->template = $template; 
	}
}
