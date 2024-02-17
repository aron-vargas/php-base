<?php
require("include/session_manager.php");
$session->controller->SetTemplate("include/templates/showqrcode.php");
$session->controller->view->render();
