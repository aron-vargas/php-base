<?php
global $exp, $error;

if ($exp)
{
    $message = $exp->message();
    $message .= $exp->getTraceAsString();
}
else if (isset($error))
{
    if (is_string($error))
        $message = $error;
    else
        $message = print_r($error, true);
}
else
    $message = "An unknow error has occured!";

echo <<<END
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text/css" href="style/main.css" media='all'>
    </head>
    <body>
        <div class='error'>
            <div class='error_msg'>$message</div>
        </div>
    </body>
</html>
END;