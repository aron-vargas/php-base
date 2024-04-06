<?php
use Freedom\Models\User;

// Set the profile to the view model
if (isset($this->model) && str_contains(get_class($this->model), 'UserProfile'))
{
    $profile = $this->model;
    $user = new User($profile->pkey);
}

//
// Find the user
if (!isset($profile))
{
    $user = $this->config->get("session")->user;
    // Load profile information
    $profile = $user->get('profile', false, false);
}

$theme = $profile->theme;
$profile_bg_url = $profile->Img('background');
$verified = ($user->verified) ? "Verified" : "Get Verification";
$comment = "There should be some additional information here";
//$bio = json_decode($profile->bio_conf);
//$about = json_decode($profile->about_conf);
//$info = json_decode($profile->info_conf);
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

    .dot {
        vertical-align: middle;
        font-size: 4px;
    }
</style>

<?php

echo <<<HTML
<div role='main' class='container'>
    <div class='profile {$theme}'>
        <div class="row">
            <div class='col col-md-9'>
                <div class='card'>
                    <div class='profile-header'>
                        <div class='header-img'>
                            <div class='hidden link rounded-circle top right'>
                                <a href="/user/profile/profile_img_edit" onClick="OpenImageUpload('background');" alt='Change Profile Background' title='Change Profile Background'>
                                    <i class='fa fa-camera'></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class='profile-headshot'>
                        <a href='#' onClick="OpenImageUpload('headshot');" alt='Change Profile Image' title='Change Profile Image'>
                            <img class='headshot rounded-circle' src="{$profile->Img('headshot')}"/>
                        </a>
                        <a class='edit-btn' href="/admin/userprofile/edit/{$profile->pkey}" alt='Edit Profile' title='Edit Profile'>
                            <i class='fa fa-pencil'/></i>
                        </a>
                    </div>
                    <div class='profile p-4'>
                        <div class='d-inline h4'>
                            <span>{$user->first_name} {$user->last_name}</span>
                            <span class="badge badge-info bg-info">{$verified}</span>
                        </div>
                        <div class='author-name'>
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
                <div class='card mt-4 p-4'>
                    <h4 class='card-title border-bottom'>About Me</h4>
                    <div class='profile-comment'>
                        {$profile->about_conf}
                    </div>
                </div>
                <div class='card mt-4 p-4'>
                    <h4 class='card-title border-bottom'>Bio</h4>
                    <div class='profile-comment'>
                        {$profile->bio_conf}
                    </div>
                </div>
            </div>
            <div class='col col-md-3'>
                <div class='card p-2'>
                    <h4 class='card-title border-bottom'>Additional Information</h4>
                    <div class='profile-comment'>
                        {$profile->info_conf}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

$MODAL_TITLE = "Update Image";
$MODAL_CLASS = "modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable";
$MODAL_BODY = <<<BODY
    <form id='image-form' action='/profile/save-img' method='POST' enctype='multipart/form-data'>
        <input type='hidden' id='image-form-pkey' name='pkey' value='{$profile->pkey}'/>
        <input type='hidden' id='profile_image' name='profile_image' value='headshot' />
        <input class='hidden' id='image_file' name='image_file' type='file' />
        <div class="tab-content" id="image-form-content">
            <div id="image-cropper" class='image-cropper'>
                <div class='upload_help'>Click or Drag an Image to Upload</div>
            </div>
        </div>
    </form>
BODY;

$MODAL_FOOTER = "
    <button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancel</button>
    <button type=\"button\" class=\"btn btn-primary\" onClick='SubmitImageForm();'>Save changes</button>";

echo include ('src/templates/bs-modal-template.php');

?>
<script type='text/javascript'>
    function OpenImageUpload(img_input)
    {
        $('#profile_image').val(img_input);
        $('#BS-MODAL').modal('show');

        cropper(document.getElementById('image-cropper'),
            {
                area: [400, 400],
                crop: [300, 300],
            })
    }

    function SubmitImageForm()
    {
        $(this).prop("disabled", true);

        // Transfer the Cropped Image Blob to the file input 'image_file'
        var img_elem = document.getElementById('image-cropper');
        const crop = img_elem.crop;
        const imgImage = crop.getCroppedImage();

        // getCroppedAsBlob uses a callback
        crop.getCroppedAsBlob(function (imgBlob)
        {
            var image_name = $('#profile_image').val() + "_" + $('#image-form-pkey').val() + ".png";

            // Create a new File object
            const ImgFile = new File([imgBlob], image_name, {
                type: 'image/png',
                lastModified: new Date(),
            });

            // Copy the contents over to the hidden file input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(ImgFile);
            document.getElementById('image_file').files = dataTransfer.files;

            // Submit the form
            $('#image-form').submit();
        });
    }
</script>