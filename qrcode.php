<?php
session_start();

global $session, $controller;
require("include/session_manager.php");

$controller->SetTemplate("include/templates/showqrcode.php");
$controller->view->render();
