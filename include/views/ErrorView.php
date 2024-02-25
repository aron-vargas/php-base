<?php

class ErrorView extends CDView {
    public $errors = array();

    /**
     * Create a new instance
     */
    public function __construct()
    {
        parent::__construct();
        $this->status_code = 400;
    }

    public function AddException($exp)
    {
        $this->status_code = $exp->getCode();
        $this->errors[] = $exp->getMessage();
        if ($this->mode == self::$HTML_MODE)
        {
            $this->errors[] = $exp->getTraceAsString();
        }
    }

    static public function WrapError($text)
    {
        return "
        <div class='alert alert-warning mx-auto my-1'>
            <p>{$text}</p>
        </div>";
    }

    public function render_body()
    {
        if ($this->mode == self::$HTML_MODE)
        {
            // Add all the errors
            $error_message = "";
            foreach ($this->errors as $msg)
            {
                $error_message .= self::WrapError($msg);
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