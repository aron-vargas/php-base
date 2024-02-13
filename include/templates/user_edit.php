<?php
	global $session, $dbh;

	$usr = $session->controller->model;

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
	<form action='index.php' method='POST'>
		<input type='hidden' name='act' value='Save'>
		<input type='hidden' name='target' value='User'>
		<input type='hidden' name='pkey' value='{$usr->pkey}}'>
		<h4>My Information:</h4>
		<div class='mb-4'>
			<label class='form-label' for='user_name' style='margin-left: 0px;'>Username</label>
			<input type='text' id='user_name' name='user_name' class='form-control' placeholder='Username or email address' value='{$usr->user_name}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='first_name' style='margin-left: 0px;'>First Name</label>
			<input type='text' id='first_name' name='first_name' class='form-control' placeholder='First Name'  value='{$usr->first_name}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='last_name' style='margin-left: 0px;'>Last Name</label>
			<input type='text' id='last_name' name='last_name' class='form-control' placeholder='Last Name'  value='{$usr->last_name}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='nickname' style='margin-left: 0px;'>Nickname</label>
			<input type='text' id='nickname' name='nickname' class='form-control' placeholder='What should we call you'  value='{$usr->nickname}'/>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='email' style='margin-left: 0px;'>Email</label>
			<input type='email' id='email' name='email' class='form-control' placeholder='Email Address' value='{$usr->email}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='email' style='margin-left: 0px;'>Phone Number</label>
			<input type='tel' id='phone' name='phone' class='form-control' placeholder='Phone Number' value='{$usr->phone}'>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' type='button'>Submit</button>
		</div>
	</form>
</div>
</main>
</div>";
