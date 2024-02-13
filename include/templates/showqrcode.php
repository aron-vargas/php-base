<div role='main' class='container'>
<?php

$file = "generic.png";
QRcode::png("http://52.11.240.158/index.php", $file, QR_ECLEVEL_L, 9, 4, false);

echo "<div class='text-center my-4'>
	<img src='$file'>
</div>";

?>
</div>