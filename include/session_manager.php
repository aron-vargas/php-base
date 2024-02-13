<?php
# Setup auto load
// Or, using an anonymous function
spl_autoload_register('LoadClass');

$dbh = DBConnection();

$session = new CDSession();
$session->validate();

function DBConnection()
{
    try
    {
        $DB = new DBSettings();
        return $DB->conn;

    }
    catch (Exception $exp)
    {
        $error = "Unable to make a database connection!";
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
