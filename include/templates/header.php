<!DOCTYPE=html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="Download Bootstrap to get the compiled CSS and JavaScript, source code, or include it with your favorite package managers like npm, RubyGems, and more.">
		<meta name="author" content="Aron Vargas">
<?php
    $controller = $_SESSION['APPCONTROLLER'];

	foreach($controller->view->css as $link)
		echo "$link\n";

	foreach($controller->view->js as $script)
		echo "$script\n";
?>
	</head>
	<body>