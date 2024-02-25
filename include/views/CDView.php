<?php

class CDView {
    public $header = "include/templates/header.php";
    public $template = "include/templates/home.php";
    public $footer = "include/templates/footer.php";
    public $status_code = 200;
    public $data;

    protected $header_rendered = false;
    protected $body_rendered = false;
    protected $footer_rendered = false;

    protected $message = array();
    protected $css = array();
    public $js = array();

    public $mode = "html";

    protected $debug = false;

    static public $HTML_MODE = "html";
    static public $JSON_MODE = "json";

    /**
     * Create a new instance
     */
    public function __construct()
    {
        $this->css['main'] = "<link rel='stylesheet' type='text/css' href='style/main.css' media='all'>";
        $this->css['bootstrap'] = "<link rel='stylesheet' type='text/css' href='vendor/twbs/bootstrap/dist/css/bootstrap.min.css' media='all'>";
        $this->css['jquery-ui'] = "<link rel='stylesheet' type='text/css' href='vendor/components/jqueryui/themes/base/all.css' media='all'>";
        $this->css['datatable'] = "<link rel='stylesheet' type='text/css' href='js/DataTables-1.12.1/css/jquery.dataTables.min.css'/>";
        $this->css['fa'] = "<link rel='stylesheet' type='text/css' href='vendor/components/font-awesome/css/all.css' media='all'>";
        $this->css['imgpicker'] = "<link rel='stylesheet' type='text/css' href='style/image-picker.css'/>";
        $this->css['chime'] = "<link rel='stylesheet' type='text/css' href='style/chime.css'/>";

        $this->js['bootstrap'] = "<script type='text/javascript' src='vendor/twbs/bootstrap/dist/js/bootstrap.min.js'></script>";
        $this->js['jquery'] = "<script type='text/javascript' src='vendor/components/jquery/jquery.min.js'></script>";
        $this->js['jquery-ui'] = "<script type='text/javascript' src='vendor/components/jqueryui/jquery-ui.min.js'></script>";
        $this->js['datatable'] = "<script type='text/javascript' src='js/datatables.min.js'></script>";
        $this->js['imgpicker'] = "<script type='text/javascript' src='js/image-picker.min.js'></script>";
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
            "footer_rendered" => $this->footer_rendered,
            "mode" => $this->mode
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

        if (isset($state['mode']))
            $this->mode = ($state['mode']);
    }


    public function process($req)
    {
        # ALL attributes
        $parsed = parse_url($_SERVER['REQUEST_URI']);
        # path only
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        # Change the view based on "v" parameter
        if (isset($req['v']))
        {
            $view = strtolower(CDModel::Clean($req['v']));

            $this->template = "include/templates/{$view}.php";

            if ($view == 'event')
                $this->template = "include/templates/calendar/event.php";
        }
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
        if ($this->mode == self::$HTML_MODE)
        {
            include($this->header);
            $this->menu();
        }
        else if ($this->mode == self::$JSON_MODE)
        {
            if ($this->status_code == 200)
            {
                // No Content
                //if (empty($this->data))
                //    $this->status_code = 204;
            }

            $this->SetStatusCode($this->status_code);
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-Type: application/json; charset=utf-8');
            // TODO: Add additional headers
            //echo "Content-Type: application/json; charset=utf-8";
        }
    }

    public function render_body()
    {
        if ($this->mode == self::$HTML_MODE)
        {
            $this->render_message();

            if ($this->debug)
            {
                echo "<div class='bebug_container'>";
                include("include/templates/debug.php");
                echo "</div>\n";
            }

            if ($this->template)
                include($this->template);
        }
        else if ($this->mode == self::$JSON_MODE)
        {
            $body = new StdClass();
            $body->message = $this->message;
            $body->data = $this->data;
            $body->code = $this->status_code;

            echo json_encode($body);
        }
    }

    public function render_message()
    {
        if ($this->message)
        {
            echo "<div class='alert alert-secondary w-50 mx-auto my-1'>";
            if (is_array($this->message))
            {
                foreach ($this->message as $message)
                {
                    echo "<p>{$message}</p>";
                }
            }
            else
                echo "<p>{$this->message}</p>";
            echo "</div>\n";
        }
    }

    public function render_footer()
    {
        if ($this->mode == self::$HTML_MODE)
        {
            include($this->footer);
        }
    }

    private function SetStatusCode($code)
    {
        switch ($code)
        {
            case 100:
                $text = 'Continue';
                break;
            case 101:
                $text = 'Switching Protocols';
                break;
            case 200:
                $text = 'OK';
                break;
            case 201:
                $text = 'Created';
                break;
            case 202:
                $text = 'Accepted';
                break;
            case 203:
                $text = 'Non-Authoritative Information';
                break;
            case 204:
                $text = 'No Content';
                break;
            case 205:
                $text = 'Reset Content';
                break;
            case 206:
                $text = 'Partial Content';
                break;
            case 300:
                $text = 'Multiple Choices';
                break;
            case 301:
                $text = 'Moved Permanently';
                break;
            case 302:
                $text = 'Moved Temporarily';
                break;
            case 303:
                $text = 'See Other';
                break;
            case 304:
                $text = 'Not Modified';
                break;
            case 305:
                $text = 'Use Proxy';
                break;
            case 400:
                $text = 'Bad Request';
                break;
            case 401:
                $text = 'Unauthorized';
                break;
            case 402:
                $text = 'Payment Required';
                break;
            case 403:
                $text = 'Forbidden';
                break;
            case 404:
                $text = 'Not Found';
                break;
            case 405:
                $text = 'Method Not Allowed';
                break;
            case 406:
                $text = 'Not Acceptable';
                break;
            case 407:
                $text = 'Proxy Authentication Required';
                break;
            case 408:
                $text = 'Request Time-out';
                break;
            case 409:
                $text = 'Conflict';
                break;
            case 410:
                $text = 'Gone';
                break;
            case 411:
                $text = 'Length Required';
                break;
            case 412:
                $text = 'Precondition Failed';
                break;
            case 413:
                $text = 'Request Entity Too Large';
                break;
            case 414:
                $text = 'Request-URI Too Large';
                break;
            case 415:
                $text = 'Unsupported Media Type';
                break;
            case 500:
                $text = 'Internal Server Error';
                break;
            case 501:
                $text = 'Not Implemented';
                break;
            case 502:
                $text = 'Bad Gateway';
                break;
            case 503:
                $text = 'Service Unavailable';
                break;
            case 504:
                $text = 'Gateway Time-out';
                break;
            case 505:
                $text = 'HTTP Version not supported';
                break;
            default:
                $code = 200;
                $text = 'OK';
                break;
        }

        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header($protocol . ' ' . $code . ' ' . $text);
        $GLOBALS['http_response_code'] = $code;
    }


    public function Empty()
    {
        return empty($this->template);
    }

    public function menu()
    {
        $controller = $_SESSION['APPCONTROLLER'];

        if ($controller->user->pkey)
        {
            $buttons = "<div class='float-end'>
				<span class='pe-2'>
					<a href='index.php?v=avatar'>
                        <span class='rounded-circle avatar {$controller->user->avatar}'>&nbsp;</span>
                    </a>
					<a href='index.php?act=edit&target=User&pkey={$controller->user->pkey}'>
                        {$controller->user->first_name} {$controller->user->last_name}
                    </a>
				</span>
				<a class='btn btn-light me-2' href='logout.php'>Logout</a>
			</div>";
        }
        else
        {
            $buttons = "
			<div class='float-end'>
				<a class='btn btn-light me-2' href='login.php'>Login</a>
				<a class='btn btn-warning' href='index.php?v=register'>Register</a>
			</div>";
        }

        $home_class = (strstr($this->template, "home") === false) ? "" : "active";
        $membership_class = (strstr($this->template, "membership") === false) ? "" : "active";
        $about_class = (strstr($this->template, "about") === false) ? "" : "active";
        $calendar_class = (strstr($this->template, "calendar") === false) ? "" : "active";

        # Get the menu from "mega_menu.php"
        # This uses the pages defined in config.json
        $menu = include("include/templates/mega_menu.php");

        echo <<<HEADER
		<header>
            <nav class='navbar navbar-default navbar-fixed-top navbar-dark bg-dark'>
                <span class='navbar-brand ms-5'>
                    <div class="nav-item dropdown">
                        <button
                            id="menu-dd-btn"
                            class="btn btn-light"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#nav_menu"
                            aria-expanded="false"
                            aria-controls="nav_menu">
                            <i class='fa fa-bars'></i>
                        </button>
                        <div id='nav_menu' class="dropdown-menu" aria-labelledby="menu-dd-btn">
                            {$menu}
                        </div>
                    </div>
                </span>
                <span class='navbar-brand p2 me-auto'>
                    <img class='round-logo' src='images/logo.png' height='40'/>
                </span>
                <span class='p2 ms-auto me-5'>
                    {$buttons}
                </span>
			</nav>
        </header>
HEADER;

        if ($controller->auth)
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
    {
        $this->template = $template;
    }

    private function DayCells()
    {

    }

    static public function ListItemLinks($base_url, $selected, $opt_ary)
    {
        $options = "";
        if (is_array($opt_ary))
        {
            foreach ($opt_ary as $opt)
            {
                $sel = ($opt->val == $selected) ? "active" : "";
                $options .= "<li><a class='dropdown-item {$sel}' href='{$base_url}={$opt->val}'>{$opt->text}</a></li>";
            }
        }

        return $options;
    }

    static public function OptionsList($selected, $opt_ary)
    {
        $options = "";
        if (is_array($opt_ary))
        {
            foreach ($opt_ary as $opt)
            {
                $sel = ($opt->val == $selected) ? "active" : "";
                $options .= "<option value='{$opt->val}' $sel>{$opt->text}</a></li>";
            }
        }

        return $options;
    }
}
