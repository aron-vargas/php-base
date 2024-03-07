<?php
namespace Freedom\Components;

use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

/**
 * Default Slim application HTML Error Renderer
 */
class FreedomHtmlErrorRenderer implements ErrorRendererInterface {

    private $css = array();
    private $js = array();

    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $title = "Error";
        $message = "
        <p>
            <i class='fa fa-triangle-exclamation' color='orange'></i>
            Uh oh, something went terribly wrong!!!
        </p>";

        if ($exception instanceof HttpNotFoundException)
        {
            $title = "Page not found";
            $message = "
            <p>
                <i class='fa fa-triangle-exclamation' color='orange'></i>
                Uh oh, Could not find the page your looking for!!!
            </p>";
        }

        $html = $message;
        if ($displayErrorDetails)
        {
            $html .= $this->renderDetail($exception);
        }
        else
        {
            $html .= $this->renderDescription($exception);
        }
        $html .= "<div>You can go <a href=\"/\">Back to Home</a> or</div>";
        $html .= "<div>try looking at our <a href=\"/static/help\">Help Center</a> if you need a hand.</div>";
        $html .= "</div>";

        return $this->render($title, $html);
    }

    private function renderDetail(Throwable $exception): string
    {
        $html = "<div class='text-end'>
            <a class='btn btn-outline-secondary p-1' onClick=\"$('.alert-detail').toggleClass('hidden');\">
                <i class='fa fa-caret-down'></i>
            </a>
        </div>";
        $html .= "<div class='alert alert-detail alert-warning mx-auto my-1 overflow-auto'>";
        $html .= "<h2>Details</h2>";
        $html .= sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));

        /** @var int|string $code */
        $code = $exception->getCode();
        $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($exception->getMessage()));
        $html .= sprintf('<div><strong>File:</strong> %s</div>', $exception->getFile());
        $html .= sprintf('<div><strong>Line:</strong> %s</div>', $exception->getLine());
        $html .= "</div>";
        $html .= "<div class='text-end'>
            <a class='btn btn-outline-secondary p-1' onClick=\"$('.alert-trace').toggleClass('hidden');\">
                <i class='fa fa-caret-down'></i>
            </a>
        </div>";
        $html .= "<div class='alert alert-trace alert-warning mx-auto my-1 hidden'>";
        $html .= '<h2>Trace</h2>';
        $html .= sprintf('<pre>%s</pre>', htmlentities($exception->getTraceAsString()));
        $html .= "</div>";

        return $html;
    }

    private function renderDescription(Throwable $exception): string
    {
        $html = "<div class='alert alert-warning mx-auto my-1 overflow-auto'>";
        $html .= sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));

        /** @var int|string $code */
        $code = $exception->getCode();
        $html .= sprintf('<div><strong>Message (%s):</strong> %s</div>', $code, htmlentities($exception->getMessage()));
        $html .= sprintf('<div><strong>File (%s):</strong> %s</div>', $exception->getLine(), $exception->getFile());
        $html .= "</div>";

        return $html;
    }

    public function render(string $title = '', string $html = ''): string
    {
        return <<<HTML
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{$title}</title>
        <style>
            body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif}
            h1{margin:0;font-size:48px;font-weight:normal;line-height:48px}
            strong{display:inline-block;width:65px}
            .btn { font-size: 9px !important; line-height: 9px !important; }
        </style>
        {$this->AddToHeader()}
    </head>
    <body>
        <div class='nomatch'>
            <div class='msg'>
                <h1>{$title}</h1>
                <div>{$html}</div>
                <a href="#" onclick="window.history.go(-1)">Go Back</a>
            </div>
        </div>
    </body>
</html>
HTML;
    }

    public function AddToHeader()
    {
        $base_url = $GLOBALS['base_url'];

        $css['main'] = "<link rel='stylesheet' type='text/css' href='//{$base_url}/style/main.css' media='all'>";
        $css['bootstrap'] = "<link rel='stylesheet' type='text/css' href='//{$base_url}/vendor/twbs/bootstrap/dist/css/bootstrap.min.css' media='all'>";
        $css['jquery-ui'] = "<link rel='stylesheet' type='text/css' href='//{$base_url}/vendor/components/jqueryui/themes/base/all.css' media='all'>";
        $css['fa'] = "<link rel='stylesheet' type='text/css' href='//{$base_url}/vendor/components/font-awesome/css/all.css' media='all'>";

        $js['bootstrap'] = "<script type='text/javascript' src='//{$base_url}/vendor/twbs/bootstrap/dist/js/bootstrap.min.js'></script>";
        $js['jquery'] = "<script type='text/javascript' src='//{$base_url}/vendor/components/jquery/jquery.min.js'></script>";
        $js['jquery-ui'] = "<script type='text/javascript' src='//{$base_url}/vendor/components/jqueryui/jquery-ui.min.js'></script>";

        $header = "";
        foreach ($css as $link)
            $header .= "$link\n";

        foreach ($js as $script)
            $header .= "$script\n";

        return $header;
    }
}
