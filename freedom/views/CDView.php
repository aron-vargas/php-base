<?php
use Psr\Container\ContainerInterface;
use DI\Container;

class CDView {
    public $header = "freedom/templates/header.php";
    public $template = "freedom/templates/home.php";
    public $footer = "freedom/templates/footer.php";
    public $status_code = 200;
    public $config;
    public $data;

    protected $header_rendered = false;
    protected $body_rendered = false;
    protected $footer_rendered = false;
    protected $css = array();
    public $js = array();

    public $mode = "html";

    protected $debug = false;

    static public $HTML_MODE = "html";
    static public $JSON_MODE = "json";

    /**
     * Create a new instance
     */
    public function __construct(Container $config)
    {
        $this->config = $config;

        $this->css['main'] = "<link rel='stylesheet' type='text/css' href='//{$config->get('base_url')}/style/main.css' media='all'>";
        $this->css['bootstrap'] = "<link rel='stylesheet' type='text/css' href='//{$config->get('base_url')}/vendor/twbs/bootstrap/dist/css/bootstrap.min.css' media='all'>";
        $this->css['jquery-ui'] = "<link rel='stylesheet' type='text/css' href='//{$config->get('base_url')}/vendor/components/jqueryui/themes/base/all.css' media='all'>";
        $this->css['datatable'] = "<link rel='stylesheet' type='text/css' href='//{$config->get('base_url')}/js/DataTables-1.12.1/css/jquery.dataTables.min.css'/>";
        $this->css['fa'] = "<link rel='stylesheet' type='text/css' href='//{$config->get('base_url')}/vendor/components/font-awesome/css/all.css' media='all'>";
        $this->css['imgpicker'] = "<link rel='stylesheet' type='text/css' href='//{$config->get('base_url')}/style/image-picker.css'/>";
        $this->css['chime'] = "<link rel='stylesheet' type='text/css' href='//{$config->get('base_url')}/style/chime.css'/>";

        $this->js['bootstrap'] = "<script type='text/javascript' src='//{$config->get('base_url')}/vendor/twbs/bootstrap/dist/js/bootstrap.min.js'></script>";
        $this->js['jquery'] = "<script type='text/javascript' src='//{$config->get('base_url')}/vendor/components/jquery/jquery.min.js'></script>";
        $this->js['jquery-ui'] = "<script type='text/javascript' src='//{$config->get('base_url')}/vendor/components/jqueryui/jquery-ui.min.js'></script>";
        $this->js['datatable'] = "<script type='text/javascript' src='//{$config->get('base_url')}/js/datatables.min.js'></script>";
        $this->js['imgpicker'] = "<script type='text/javascript' src='//{$config->get('base_url')}/js/image-picker.min.js'></script>";
    }

    /**
     * Append to the message array
     * @param mixed
     */
    public function AddMsg($message)
    {
        $msg = $this->config->get('message');
        $msg[] = $message;
        $this->config->set('message', $msg);
    }

    public function DebugMsg($mixed)
    {
        echo "<pre class='debug'>";
        print_r($mixed);
        echo "</pre>";
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
            $css = $this->css;
            $js = $this->js;
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
            $body->message = $this->config->get('message');
            $body->data = $this->data;
            $body->code = $this->status_code;

            echo json_encode($body);
        }
    }

    public function render_message()
    {
        $message = $this->config->get('message');
        if ($message)
        {
            echo "<div class='alert alert-secondary w-50 mx-auto my-1'>";
            if (is_array($message))
            {
                foreach ($message as $msg)
                {
                    echo "<p>{$msg}</p>";
                }
            }
            else
                echo "<p>{$message}</p>";
            echo "</div>\n";
        }
        else
        {
            //$this->DebugMsg($this->config);
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
        $user = $this->config->get("session")->user;

        if ($user->pkey)
        {
            $buttons = "<div class='float-end'>
				<span class='pe-2'>
					<a href='avatar'>
                        <span class='rounded-circle avatar {$user->avatar}'>&nbsp;</span>
                    </a>
					<a href='/user/{$user->pkey}'>
                        {$user->first_name} {$user->last_name}
                    </a>
				</span>
				<a class='btn btn-light me-2' href='/logout'>Logout</a>
			</div>";
        }
        else
        {
            $buttons = "
			<div class='float-end'>
				<a class='btn btn-light me-2' href='login'>Login</a>
				<a class='btn btn-warning' href='register'>Register</a>
			</div>";
        }

        $home_class = (strstr($this->template, "home") === false) ? "" : "active";
        $membership_class = (strstr($this->template, "membership") === false) ? "" : "active";
        $about_class = (strstr($this->template, "about") === false) ? "" : "active";
        $calendar_class = (strstr($this->template, "calendar") === false) ? "" : "active";

        # Get the menu from "mega_menu.php"
        # This uses the pages defined in config.json
        $pages = $this->config->get('pages');
        $active_page = $this->config->get('active_page');
        $menu = include("freedom/templates/mega_menu.php");

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

        if ($user->pkey)
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
