<?php
session_start();
global $session;
require("include/session_manager.php");

$session->End();
header('Location: index.php');
exit;
