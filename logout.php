<?php
require("include/session_manager.php");
$session->End();
header('Location: index.php');
exit;
