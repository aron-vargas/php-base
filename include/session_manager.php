<?php
syslog(LOG_DEBUG, "Session Start");

# Setup auto load
// Or, using an anonymous function
spl_autoload_register('LoadClass');
syslog(LOG_DEBUG, "Autoload LoadClass registered");

$dbh = DBConnection();
syslog(LOG_DEBUG, "Database connection established");


$session = new CDSession();
syslog(LOG_DEBUG, "CDSession created");

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
        global $error;
        $error = "<h2>Unable to make a database connection!</h2>";
        $error .= "<div class='error-info'>{$exp->getMessage()}</div>";
	    $error .= "<div class='error-info text-small'>{$exp->getTraceAsString()}</div>";

        include("include/templates/error_page.php");
        exit();
    }
}

function LoadClass($class)
{
    if (file_exists("include/{$class}.php"))
        include_once "include/{$class}.php";
    else if (file_exists("include/classes/{$class}.php"))
        include_once "include/classes/{$class}.php";
}
