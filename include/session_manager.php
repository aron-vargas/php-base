<?php
$config = json_decode(file_get_contents("include/config.json"));
syslog(LOG_DEBUG, "Session Start");

# Setup auto load
// Or, using an anonymous function
spl_autoload_register('LoadClass');
syslog(LOG_DEBUG, "Autoload LoadClass registered");

$session = new CDSession();
syslog(LOG_DEBUG, "CDSession created");

$dbh = null;
if ($config->use_db)
{
    $dbh = DBConnection();
    syslog(LOG_DEBUG, "Database connection established");
}

$session->validate();
syslog(LOG_DEBUG, "CDSession Validated with: " . ($session->auth) ? "Authorized" : "No Authorization");

function DBConnection()
{
    try
    {
        $DB = new DBSettings();
        return $DB->conn;

    }
    catch (Exception $exp)
    {
        global $config, $controller, $error;

        $error = "<h2>Unable to make a database connection!</h2>";
        $error .= "<div class='error-info'>{$exp->getMessage()}</div>";
	    $error .= "<div class='error-info text-small'>{$exp->getTraceAsString()}</div>";

        if ($config->exit_on_error)
        {
            include("include/templates/error_page.php");
            exit();
        }
        else
        {
            $controller->AddMsg($error);
        }
    }
}

function LoadClass($class)
{
    if (file_exists("include/{$class}.php"))
        include_once "include/{$class}.php";
    else if (file_exists("include/components/{$class}.php"))
        include_once "include/components/{$class}.php";
    else if (file_exists("include/models/{$class}.php"))
        include_once "include/models/{$class}.php";
    else if (file_exists("include/views/{$class}.php"))
        include_once "include/views/{$class}.php";
    else if (file_exists("include/controlers/{$class}.php"))
        include_once "include/controlers/{$class}.php";
}
