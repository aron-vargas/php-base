<?php
//$user = $this->config->get("session")->user;
//$profile = $user->get('profile', false, false);

$theme = $profile->theme;
$profile_bg_url = $profile->Img('background');
$comment = "There should be some additional information here";
$bio = json_decode($profile->bio_conf);
$about = json_decode($profile->about_conf);
$info = json_decode($profile->info_conf);

echo <<<HTML
<div role='main' class='container'>
    <div class='profile {$theme}'>
        <div class="row">
            <div class='col col-md-10'>
                <div class='card'>
                    <div class='profile-header'>
                        <div class='header-img' style="background-image: url('{$profile_bg_url}');"></div>
                    </div>
                    <div class='profile-headshot'>
                        <img class='headshot rounded-circle' src="{$profile->Img('headshot')}"/>
                    </div>
                    <div class='card-body'>
                        <h2>{$user->first_name} {$user->last_name}</h2>
                        <div class='location'>
                            City, State
                            <i class='fa fa-circle dot mx-1'></i>
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
        </div>
    </div>
</div>
HTML;