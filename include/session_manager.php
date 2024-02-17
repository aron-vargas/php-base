<?php
# Setup auto load
// Or, using an anonymous function
spl_autoload_register('LoadClass');
syslog(LOG_DEBUG, "Autoload LoadClass registered");

# Create a CDSession
session_start();
$session = new CDSession();
syslog(LOG_DEBUG, "CDSession created");

if ($session->config->use_db)
{
    $session->dbh = self::DBConnection();
    syslog(LOG_DEBUG, "Connected to Database");
}

# Validate the session
$session->validate();
syslog(LOG_DEBUG, "CDSession Validated with: " . ($session->auth) ? "Authorized" : "No Authorization");

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
