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
?>

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