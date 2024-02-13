<?php
	$user_name = (isset($_REQUEST['user_name'])) ? BaseClass::Clean($_REQUEST['user_name']) : "";

?>
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
<div class="card-body p-md-5 mx-md-4">
	<form action='index.php' method='POST'>
		<input type='hidden' name='act' value='Create'>
		<input type='hidden' name='target' value='User'>
		<input type='hidden' name='avatar' value='base_sucker'>
		<h4>Sign In</h4>
		<div class="mb-4">
			<label class="form-label" for="user_name" style="margin-left: 0px;">Username</label>
			<input type="text" id="user_name" name="user_name" class="form-control" placeholder="Username or email address" value="<?php echo $user_name; ?>">
		</div>
		<div class="mb-4">
			<label class="form-label" for="password" style="margin-left: 0px;">Password</label>
			<input type="password" id="password" name="password" class="form-control">
		</div>
		<div class="mb-4">
			<label class="form-label" for="first_name" style="margin-left: 0px;">First Name</label>
			<input type="text" id="first_name" name="first_name" class="form-control" placeholder="First Name">
		</div>
		<div class="mb-4">
			<label class="form-label" for="last_name" style="margin-left: 0px;">Last Name</label>
			<input type="text" id="last_name" name="last_name" class="form-control" placeholder="Last Name">
		</div>
		<div class="mb-4">
			<label class="form-label" for="nickname" style="margin-left: 0px;">Nickname</label>
			<input type="text" id="nickname" name="nickname" class="form-control" placeholder="What should we call you" />
		</div>
		<div class="mb-4">
			<label class="form-label" for="email" style="margin-left: 0px;">Email</label>
			<input type="email" id="email" name="email" class="form-control" placeholder="Email Address">
		</div>
		<div class="mb-4">
			<label class="form-label" for="email" style="margin-left: 0px;">Phone Number</label>
			<input type="tel" id="phone" name="phone" class="form-control" placeholder="Phone Number">
		</div>
		<div class="text-center pt-1 mb-4 pb-1">
			<button type='submit' class="btn btn-primary" type="button">Submit</button>
		</div>
	</form>
</div>
</main>
</div>