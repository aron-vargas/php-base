<?php
session_start();

global $session;
require("include/session_manager.php");
require_once("phpqrcode/qrlib.php");

include($session->controller->view->header);
$session->controller->view->menu();
include("include/templates/showqrcode.php");
include($session->controller->view->footer);
