<?php

class ErrorView extends CDView
{
    public $errors = array();

    /**
     * Create a new instance
     * @param CDModel
     */
    public function __construct($model = null)
    {
        parent::__construct($model);
    }

    public function AddException($exp)
    {
        $this->errors[] = $exp->getMessage();
        $this->errors[] = $exp->getTraceAsString();
    }

    static public function WrapError($text)
    {
        return "
        <div class='alert alert-warning mx-auto my-1'>
            <p>{$text}</p>
        </div>";
    }

    public function render_footer()
    {
        // Add all the errors
        foreach($this->errors AS $msg)
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

        // Add the normal footer template
        include($this->footer);
    }
}