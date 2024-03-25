<?php
$usr = $this->model;

if (isset ($_SERVER['HTTP_REFERER']))
    $this->Crumb($_SERVER['HTTP_REFERER'], " <i class='fa fa-angle-left'></i>Back");
$this->Crumb("/home", "Home");
$this->Crumb("/admin/user/list", "Users");
$this->Crumb(null, "Edit", true);

echo "
<style>
.card-body
{
	border: 1px solid #CCC;
	border-radius: 8px;
}
</style>
{$this->render_trail()}
<div role='main' class='container'>
<main class='w-100 m-auto'>
<div class='card-body p-md-5 mx-md-4'>
	<form action='/admin/userprofile/save' method='POST'>
		<input type='hidden' name='pkey' value='{$usr->pkey}'>
		<h4>My Information:</h4>
		<div class='hidden mb-4'>
			<label class='form-label' for='company_id' style='margin-left: 0px;'>Company #</label>
			<input type='text' id='company_id' name='company_id' class='form-control' placeholder='Company #' value='{$usr->company_id}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='bio_conf' style='margin-left: 0px;'>Bio</label>
			<textarea id='bio_conf' name='bio_conf' class='form-control' rows='10'>{$usr->bio_conf}</textarea>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='about_conf' style='margin-left: 0px;'>About</label>
			<textarea id='about_conf' name='about_conf' class='form-control' rows='10'>{$usr->about_conf}</textarea>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='info_conf' style='margin-left: 0px;'>Additional Information</label>
			<textarea id='info_conf' name='info_conf' class='form-control' rows='10'>{$usr->info_conf}</textarea>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' type='button' name='act' value='1' onClick='SubmitFrom(this)'>Submit</button>
            <button type='submit' class='btn btn-primary' type='button' name='act' value='-1' onClick='SubmitFrom(this)'>Delete</button>
		</div>
	</form>
</div>
</main>
</div>";
?>
<script type='text/javascript'>
$(function () {

    // Editor configuration.
    var e_config =
    {
        removePlugins: ['Markdown', "MediaEmbedToolbar"],

        toolbar: { removeItems: ['imageUpload'] }
    };

    ClassicEditor
        .create(document.querySelector('#bio_conf'), e_config)
        .catch(cheditorError);

    ClassicEditor
        .create(document.querySelector('#about_conf'), e_config)
        .catch(cheditorError);

    ClassicEditor
        .create(document.querySelector('#info_conf'), e_config)
        .catch(cheditorError);
});

function cheditorError(error)
{
    const issueUrl = 'https://github.com/ckeditor/ckeditor5/issues';

    const message = [
        'Oops, something went wrong!',
        `Please, report the following error on ${issueUrl} with the build id "6lrrspxc6m2x-w3wn11v6wpsy" and the error stack trace:`
    ].join('\n');

    console.error(message);
    console.error(error);
}
</script>