<?php
global $error_message;

if (!isset($error_message))
    $error_message = "An unknow error has occured!";

echo "
    <div class='nomatch'>
        <div class='msg'>
            <h1>
                <i class='fa fa-triangle-exclamation' color='orange'></i>
                Uh oh, something went terribly wrong!!!
            </h1>
            <div class='alert alert-warning mx-auto my-1'><p>{$error_message}</p></div>
            <div>You can go <a href=\"/\">Back to Home</a> or</div>
            <div>try looking at our <a href=\"/help\">Help Center</a> if you need a hand.</div>
        </div>
    </div>";