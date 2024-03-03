<?php
namespace Freedom\Views;

class ErrorView extends CDView {
    public $errors = array();

    /**
     * Create a new instance
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->status_code = 400;
    }

    public function AddException($exp)
    {
        $this->status_code = $exp->getCode();
        if ($this->mode == self::$HTML_MODE)
        {
            $this->errors[] = $this->WrapError($exp);
        }
        else
        {
            $this->errors[] = $exp->getMessage();
        }
    }

    public function WrapError($exp)
    {
        $class = get_class($exp);
        $code = $exp->getCode();
        $file = $exp->getFile();
        $line = $exp->getLine();
        $message = htmlentities($exp->getMessage());
        $trace = htmlentities($exp->getTraceAsString());

        $html = "<div class='text-end'>
            <a class='btn btn-outline-secondary p-1' onClick=\"$('.alert-detail').toggle();\">
                <i class='fa fa-caret-down'></i>
            </a>
        </div>
        <div class='alert-detail alert alert-warning mx-auto my-1 overflow-auto'>
            <h2>Details</h2>
            <div><strong>Type:</strong> $class</div>
            <div><strong>Code:</strong> $code</div>
            <div><strong>Message:</strong> $message</div>
            <div><strong>File:</strong> $file</div>
            <div><strong>Line:</strong> $line</div>
        </div>
        <div class='text-end'>
            <a class='btn btn-outline-secondary p-1' onClick=\"$('.alert-trace').toggle();\">
                <i class='fa fa-caret-down'></i>
            </a>
        </div>
        <div class='alert-trace alert alert-warning mx-auto my-1' style='display:none;'>
            <h2>Trace</h2>
            <pre>$trace</pre>
        </div>";

        return $html;
    }

    public function render_body()
    {
        if ($this->mode == self::$HTML_MODE)
        {
            // Add all the errors
            $error_message = "";
            foreach ($this->errors as $msg)
            {
                $error_message .= $msg;
            }

            echo "
            <div class='nomatch'>
                <div class='msg'>
                    <h1>
                        <i class='fa fa-triangle-exclamation' color='orange'></i>
                        Uh oh, something went terribly wrong!!!
                    </h1>
                    {$error_message}
                    <div>You can go <a href=\"/\">Back to Home</a> or</div>
                    <div>try looking at our <a href=\"/help\">Help Center</a> if you need a hand.</div>
                </div>
            </div>";
        }
        else if ($this->mode == self::$JSON_MODE)
        {
            $body = new StdClass();
            $body->code = $this->status_code;
            $body->message = "ERROR FOUND";
            $body->erorr = $this->errors;
            $body->data = null;

            echo json_encode($body);
        }
    }
}