<?php
$usr = $this->data;

echo "
<style>
.form-signin
{
	max-width: 600px;
	padding: 15px;
}
.card-body
{
	border: 1px solid #CCC;
	border-radius: 8px;
}
</style>
<div role='main' class='container'>
<main class='form-signin w-100 m-auto'>
<div class='card-body p-md-5 mx-md-4'>
	<form action='/auth/profile' method='POST'>
		<input type='hidden' name='pkey' value='{$usr->pkey}'>
		<h4>My Information:</h4>
		<div class='mb-4'>
			<label class='form-label' for='company_id' style='margin-left: 0px;'>Company #</label>
			<input type='text' id='company_id' name='company_id' class='form-control' placeholder='Company #' value='{$usr->company_id}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='bio_conf' style='margin-left: 0px;'>Bio</label>
			<textarea id='bio_conf' name='bio_conf' class='form-control'>{$usr->bio_conf}</textarea>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='about_conf' style='margin-left: 0px;'>About</label>
			<textarea id='about_conf' name='about_conf' class='form-control'>{$usr->about_conf}</textarea>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='info_conf' style='margin-left: 0px;'>Additional Information</label>
			<textarea id='info_conf' name='info_conf' class='form-control'>{$usr->info_conf}</textarea>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' type='button'>Submit</button>
		</div>
	</form>
</div>
</main>
</div>";
