<div role='main' class='container'>
    <?php
    require_once("freedom/components/phpqrcode/qrlib.php");

    $file = "generic.png";
    QRcode::png("http://localhost:8000", $file, QR_ECLEVEL_L, 9, 4, false);

    echo "<div class='text-center my-4'>
	<img src='/$file'/>
</div>";

    ?>
</div>