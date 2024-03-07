<?php

$year = date('Y');

echo "<footer class='footer mt-auto row' id='footer'>
    <div class='row'>
        <div class='col col-md-6'>
            <div class='p-4'>
                <img class='round-logo' src='/images/logo.png' alt='Company Logo' title='Company Logo' />
                <div>Company Name</div>
                <div>1198 Jasmine Ln</div>
                <div>Fernley, Nevada 89408</div>
                <div><a href='mailto:info@railside.com'><i class='fa fa-envelope'></i> info@railside.com</a></div>
                <div><a href='tel:7753037508'><i class='fa fa-phone'></i> 775.303.7508</a></div>
            </div>
        </div>
        <div class='col col-md-6 text-center'>
            <div class='p-4'>
                <a href='?v=contact'>Contact Us</a>
            </div>
        </div>
    </div>
    <div class='row'>
        <div class='col p-2 text-center'>
            &copy; $year <b>Aron Vargas</b>, All Rights Reserved
        </div>
    </div>
</footer>";

?>

</body>
</html>