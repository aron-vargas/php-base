<?php
	global $session, $dbh;

	$usr = $session->user;

echo "
<style>
.form-signin 
{
	max-width: 820px;
	padding: 15px;
}
.card-body
{
	border: 1px solid #CCC;
	border-radius: 8px;
}
.image_picker_image
{
	width: 100px;
	height: 100px;
}
</style>
<div role='main' class='container'>
<main class='form-signin w-100 m-auto'>
<div class='card-body p-md-5 mx-md-4'>
	<form action='index.php' method='POST'>
		<input type='hidden' name='act' value='change'>
		<input type='hidden' name='target' value='User'>
		<input type='hidden' name='field' value='avatar'>
		<input type='hidden' name='act' value='change'>
		<input type='hidden' name='pkey' value='{$usr->pkey}'>
		<h4>Select your picture:</h4>
		<div class='mb-4'>
			<label class='form-label' for='value' style='margin-left: 0px;'>Avatar</label>
			<select id='avator-sel' name='value' class='image-picker'>
				<option data-img-src='images/1st.png' value='first'>1st</option>
				<option data-img-src='images/2nd.png' value='second'>2nd</option>
				<option data-img-src='images/3rd.png' value='third'>3rd</option>
				<option data-img-src='images/horseman.png' value='horseman'>horseman</option>
				<option data-img-src='images/base_blue.png' value='base_blue'>base_blue</option>
				<option data-img-src='images/base_drkgreen.png' value='base_drkgreen'>base_drkgreen</option>
				<option data-img-src='images/base_green.png' value='base_green'>base_green</option>
				<option data-img-src='images/base_orange.png' value='base_orange'>base_orange</option>
				<option data-img-src='images/base_pink.png' value='base_pink'>base_pink</option>
				<option data-img-src='images/base_purple.png' value='base_purple'>base_purple</option>
				<option data-img-src='images/base_red.png' value='base_red'>base_red</option>
				<option data-img-src='images/base_sucker.png' value='base_sucker'>base_sucker</option>
				<option data-img-src='images/deadpool.png' value='deadpool'>deadpool</option>
				<option data-img-src='images/gamora.png' value='gamora'>gamora</option>
				<option data-img-src='images/ironman.png' value='ironman'>ironman</option>
				<option data-img-src='images/leia.png' value='leia'>leia</option>
				<option data-img-src='images/loser.png' value='loser'>loser</option>
				<option data-img-src='images/spiderman.png' value='spiderman'>spiderman</option>
				<option data-img-src='images/thor.png' value='thor'>thor</option>
				<option data-img-src='images/barnes.png' value='barnes'>barnes</option>
			</select>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' type='button'>Submit</button>
		</div>
	</form>
</div>
</main>
</div>";
?>
<script type='text/javascript'>
	$("#avator-sel").imagepicker();
</script>
