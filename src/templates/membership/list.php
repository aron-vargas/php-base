<style>
    .profile {
        border: 1px solid white;
        border-radius: 10px;
    }

    .profile-header {
        position: relative;
        height: 200px;
        margin: 0;
        padding: 0;
    }

    .profile-headshot {
        position: relative;
        padding-bottom: 50px;
    }

    .headshot {
        position: absolute;
        top: -80px;
        left: 10px;
        height: 150px;
        background-color: white;
        border: 1px solid #f1f1f1;
        border-radius: 50%;
    }
</style>
<div class='container'>
    <div class='row'>
<?php
use Freedom\Models\UserProfile;

$data = UserProfile::GetMembers();
foreach($data as $profile)
{
    include("src/templates/profile-medium.php");
}
?>
    </div>
</div>