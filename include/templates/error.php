<?php
global $exp, $error;

if ($exp)
    $message = $exp->message();
else if (isset($error))
    $message = $error;
else
    $message = "An unknow error has occured!";

echo "
<div class='error'>
    <div class='error_msg'>$message</div>
</div>";