<?php
namespace Freedom\Views;

use Freedom\Traits\Breadcrumb;
use Psr\Container\ContainerInterface;
use DI\Container;

class BlogView extends CDView {
    use Breadcrumb;

    public $header = "src/templates/header.php";
    public $template = "src/templates/home.php";
    public $footer = "src/templates/footer.php";
    public $active_page = "blog";
    public $status_code = 200;

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
        $this->css['jsuites'] = "<link rel='stylesheet' href='//{$config->get('base_url')}/node_modules/jsuites/dist/jsuites.css' type='text/css' media='all'>";
        $this->css['cropper'] = "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/@jsuites/cropper/cropper.min.css' type='text/css' />";
        $this->css['blog'] = "<link rel='stylesheet' type='text/css' href='//{$config->get('base_url')}/style/blog.css' media='all'>";

        $this->js['bootstrap'] = "<script type='text/javascript' src='//{$config->get('base_url')}/vendor/twbs/bootstrap/dist/js/bootstrap.min.js'></script>";
        $this->js['jquery'] = "<script type='text/javascript' src='//{$config->get('base_url')}/vendor/components/jquery/jquery.min.js'></script>";
        $this->js['jquery-ui'] = "<script type='text/javascript' src='//{$config->get('base_url')}/vendor/components/jqueryui/jquery-ui.min.js'></script>";
        $this->js['datatable'] = "<script type='text/javascript' src='//{$config->get('base_url')}/js/datatables.min.js'></script>";
        $this->js['imgpicker'] = "<script type='text/javascript' src='//{$config->get('base_url')}/js/image-picker.min.js'></script>";
        $this->js['ckeditor'] = "<script type='text/javascript' src='//{$config->get('base_url')}/js/ckeditor5-custom/build/ckeditor.js'></script>";
        $this->js['jsuites'] = "<script type='text/javascript' src='//{$config->get('base_url')}/node_modules/jsuites/dist/jsuites.js'></script>";
        $this->js['cropper'] = "<script type='text/javascript' src='https://cdn.jsdelivr.net/npm/@jsuites/cropper/cropper.min.js'></script>";
        $this->js['forms'] = "<script type='text/javascript' src='//{$config->get('base_url')}/js/forms.js'></script>";
    }

    public function InitDisplay($section, $page, $display)
    {

        if ($page == "blogcomment")
            $this->template = "src/templates/blog/blogcomment_{$display}.php";
        else if ($page == "blogcategory")
            $this->template = "src/templates/blog/blogcategory_{$display}.php";
        else if ($page == "blogimage")
        {
            $this->header_rendered = true;
            $this->footer_rendered = true;
            $this->template = "src/templates/blog/blogimage_{$display}.php";
        }
        else if ($page == "bloglike")
        {
            $this->mode = self::$JSON_MODE;
            $this->status_code = 200;
        }
        else if (file_exists("src/templates/{$page}_{$display}.php"))
            $this->template = "src/templates/{$page}_{$display}.php";
        else if (file_exists("src/templates/{$page}.php"))
            $this->template = "src/templates/{$page}.php";
        else
            $this->template = "src/templates/blog/blogpost_{$display}.php";
    }

    public function InitModel($section, $page, $pkey)
    {
        $this->model = false;

        if ($page == "blogcomment")
            $this->model = new \Freedom\Models\Blog\BlogComment($pkey);
        else if ($page == "blogcategory")
            $this->model = new \Freedom\Models\Blog\BlogCategory($pkey);
        else if ($page == "blogimage")
            $this->model = new \Freedom\Models\Blog\BlogImage($pkey);
        else if ($page == "bloglike")
            $this->model = new \Freedom\Models\Blog\BlogLike($pkey);
        else
            $this->model = new \Freedom\Models\Blog\BlogPost($pkey);

        return $this->model;
    }
}
