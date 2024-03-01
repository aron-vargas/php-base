<div role='main' class='container'>
<?php
require_once("phpqrcode/qrlib.php");

$file = "generic.png";
QRcode::png("http://localhost/index.php", $file, QR_ECLEVEL_L, 9, 4, false);

echo "<div class='text-center my-4'>
	<img src='$file'/>
</div>";

?>
</div>