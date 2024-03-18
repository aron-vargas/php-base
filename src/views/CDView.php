<?php
namespace Freedom\Views;

use Freedom\Traits\Breadcrumb;
use Psr\Container\ContainerInterface;
use DI\Container;

class CDView {
    use Breadcrumb;

    public $header = "src/templates/header.php";
    public $template = "src/templates/home.php";
    public $footer = "src/templates/footer.php";
    public $active_page = "home";
    public $status_code = 200;
    public $config;
    public $data;
    public $model;

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
        $this->js['ckeditor'] = "<script type='text/javascript' src='//{$config->get('base_url')}/js/ckeditor5-custom/build/ckeditor.js'></script>";
        $this->js['forms'] = "<script type='text/javascript' src='//{$config->get('base_url')}/js/forms.js'></script>";
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

    public function Empty()
    {
        return empty($this->template);
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

    public function InitDisplay($section, $page, $display)
    {
        if ($section == "admin")
        {
            if ($page == "user")
                $this->template = "src/templates/admin/user_{$display}.php";
            else if ($page == "userprofile")
            {
                if ($display == "list")
                    $this->template = "src/templates/admin/userprofile_{$display}.php";
                else
                    $this->template = "src/templates/profile_{$display}.php";
            }
            else if ($page == "permission")
                $this->template = "src/templates/admin/permission_{$display}.php";
            else if ($page == "usergroup")
                $this->template = "src/templates/admin/usergroup_{$display}.php";
            else if ($page == "module")
                $this->template = "src/templates/admin/module_{$display}.php";
        }
        else if ($section == "crm")
        {
            if ($page == "company")
                $this->template = "src/templates/crm/company_{$display}.php";
            else if ($page == "customer")
                $this->template = "src/templates/crm/customer_{$display}.php";
            else if ($page == "location")
                $this->template = "src/templates/crm/location_{$display}.php";
        }
        else if (file_exists("src/templates/{$section}/{$page}_{$display}.php"))
        {
            $this->template = "src/templates/{$section}/{$page}_{$display}.php";
        }
        else if (file_exists("src/templates/{$section}/{$page}.php"))
        {
            $this->template = "src/templates/{$section}/{$page}.php";
        }
        else if (file_exists("src/templates/{$page}_{$display}.php"))
        {
            $this->template = "src/templates/{$page}_{$display}.php";
        }
        else if (file_exists("src/templates/{$page}.php"))
        {
            $this->template = "src/templates/{$page}.php";
        }
    }

    public function InitModel($section, $page, $pkey)
    {
        $this->model = false;

        if ($section == "admin")
        {
            if ($page == "user")
                $this->model = new \Freedom\Models\User($pkey);
            else if ($page == "userprofile")
                $this->model = new \Freedom\Models\UserProfile($pkey);
            else if ($page == "permission")
                $this->model = new \Freedom\Models\Permission($pkey);
            else if ($page == "usergroup")
                $this->model = new \Freedom\Models\UserGroup($pkey);
            else if ($page == "module")
                $this->model = new \Freedom\Models\Module($pkey);
        }
        else if ($section == "crm")
        {
            if ($page == "customer")
                $this->model = new \Freedom\Models\Customer($pkey);
            else if ($page == "company")
                $this->model = new \Freedom\Models\Company($pkey);
            else if ($page == "location")
                $this->model = new \Freedom\Models\Location($pkey);
        }
        else
        {
            $this->model = new \Freedom\Models\CDModel();
        }

        return $this->model;
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
				<a class='btn btn-light me-2' href='/login'>Login</a>
				<a class='btn btn-warning' href='/register'>Register</a>
			</div>";
        }

        $home_class = (strstr($this->template, "home") === false) ? "" : "active";
        $membership_class = (strstr($this->template, "membership") === false) ? "" : "active";
        $about_class = (strstr($this->template, "about") === false) ? "" : "active";
        $calendar_class = (strstr($this->template, "calendar") === false) ? "" : "active";

        # Get the menu from "mega_menu.php"
        # This uses the pages defined in config.json
        $pages = $this->config->get('pages');
        //$active_page = $this->config->get('active_page');
        $menu = include("src/templates/mega_menu.php");

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
                    <img class='round-logo' src='/images/logo.png' height='40'/>
                </span>
                <span class='p2 ms-auto me-5'>
                    {$buttons}
                </span>
			</nav>
        </header>
HEADER;
    }
    static public function OptionsStateList($selected, $name = true, $abv = false)
    {
        $opt_ary = \Freedom\Models\Location::StatesList();
        $options = "<option value=''></option>";
        if (is_array($opt_ary))
        {
            foreach ($opt_ary as $opt)
            {
                $sel = ($opt->val == $selected) ? "selected" : "";

                $text = "{$opt->text}";
                if ($name && $abv)
                    $text = "{$opt->val}: {$opt->text}";
                else if ($abv)
                    $text = "{$opt->val}";

                $options .= "<option value='{$opt->val}' $sel>{$text}</a></li>";
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
                $sel = ($opt->val == $selected) ? "selected" : "";
                $options .= "<option value='{$opt->val}' $sel>{$opt->text}</a></li>";
            }
        }

        return $options;
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
                include("src/templates/debug.php");
                echo "</div>\n";
            }

            if (file_exists($this->template))
                include($this->template);
            else
                throw new \Exception("Could not find Template: {$this->template}");
        }
        else if ($this->mode == self::$JSON_MODE)
        {
            $body = new \StdClass();
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
            echo "<div class='alert alert-secondary w-50 mx-auto my-1'>
                <a class='dropdown-toggle float-end border border-white p-1' onClick=\"$('#msg-cont').toggle();\"></a>
                <div id='msg-cont'>";
            if (is_array($message))
            {
                foreach ($message as $msg)
                {
                    echo "<p>{$msg}</p>";
                }
            }
            else
                echo "<p>{$message}</p>";
            echo "
                </div>
            </div>\n";
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
}
