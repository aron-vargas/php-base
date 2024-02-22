<?php
# Setup auto load
// Or, using an anonymous function
spl_autoload_register('LoadClass');
syslog(LOG_DEBUG, "Autoload LoadClass registered");

# Create a CDController
session_start();
$controller = new CDController();
syslog(LOG_DEBUG, "CDController created");

if ($controller->config->use_db)
{
    $dbh = CDController::DBConnection();
    syslog(LOG_DEBUG, "Connected to Database");
}

# Validate the session
$controller->validate();
syslog(LOG_DEBUG, "CDController Validated with: " . ($controller->auth) ? "Authorized" : "No Authorization");

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
