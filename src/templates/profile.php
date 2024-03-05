<?php
$user = $this->config->get("session")->user;
$profile = $user->get('profile', false, false);
$theme = $profile->theme;
$profile_bg_url = $profile->Img('background');
$verified = ($user->verified) ? "Verified" : "Get Verification";
$comment = "There should be some additional information here";
$bio = json_decode($profile->bio_conf);
$about = json_decode($profile->about_conf);
$info = json_decode($profile->info_conf);
?>
<style>
    .profile {
        border: 1px solid white;
        border-radius: 10px;
    }

    .profile-header {
        position: relative;
        height: 300px;
        margin: 0;
        padding: 0;
    }

    .profile-headshot {
        position: relative;
        padding-bottom: 50px;
    }

    .header-img {
        background-image: url('<?php echo $profile_bg_url; ?>');
        height: 100%;
        width: 100%;
        background-size: cover;
        background-position: bottom;
    }

    .header-img .link {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 5px;
        background-color: white;
        font-size: 25px;
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

    .edit-btn {
        position: absolute;
        bottom: 0px;
        right: 30px;
        border: 1px solid #f1f1f1;
        border-radius: 50%;
        font-size: 20px;
    }
</style>

<?php
echo <<<HTML
<div role='main' class='container'>
    <div class='profile {$theme}'>
        <div class="row">
            <div class='col col-md-10'>
                <div class='card'>
                    <div class='profile-header'>
                        <div class='header-img'>
                            <div class='link rounded-circle top right'>
                                <a href="/user/profile/profile_img_edit" alt='Change Profile Background' title='Change Profile Background'>
                                    <i class='fa fa-camera'></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class='profile-headshot'>
                        <img class='headshot rounded-circle' src="{$profile->Img('headshot')}"/>
                        <a class='edit-btn' href="/user/profile/edit/{$profile->pkey}" alt='Edit Profile' title='Edit Profile'>
                            <i class='fa fa-pencil'/></i>
                        </a>
                    </div>
                    <div class='card-body'>
                        <h2>{$user->first_name} {$user->last_name}</h2>
                        <span class="badge">{$verified}</span>
                        <div class='location'>
                            City, State
                            <i class='fa fa-circle dot mx-1'></i>
                            <a href="/user/location/edit/{$user->pkey}">
                                <i class="fa fa-pencil"></i>
                            </a>
                        </div>
                        <div class='profile-comment'>
                            {$comment}
                        </div>
                    </div>
                </div>
                <div class='card'>
                    <div class='card-title'>About Me</div>
                    <div class='card-body'>
                        <div class='profile-comment'>
                            {$about}
                        </div>
                    </div>
                </div>
                <div class='card'>
                    <div class='card-title'>Bio</div>
                    <div class='card-body'>
                        <div class='profile-comment'>
                            {$bio}
                        </div>
                    </div>
                </div>
            </div>
            <div class='col col-md-2'>
                <div class='card'>
                    <div class='card-body'>
                        <div class='profile-comment'>
                            {$info}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type='text/javascript'>
    $(function ()
    {

    });
</script>
HTML;