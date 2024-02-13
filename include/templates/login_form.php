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
		<input type='hidden' name='login' value='1'>
		<h4>Sign In</h4>
		<div class="form-outline mb-4">
			<label class="form-label" for="user_name" style="margin-left: 0px;">Username</label>
			<input type="text" id="user_name" name="user_name" class="form-control" placeholder="Username or email address" value="<?php echo $user_name; ?>">
			<div class="form-notch">
				<div class="form-notch-leading" style="width: 9px;"></div>
				<div class="form-notch-middle" style="width: 66.4px;"></div>
				<div class="form-notch-trailing"></div>
			</div>
		</div>
		<div class="form-outline mb-2">
			<label class="form-label" for="password" style="margin-left: 0px;">Password</label>
			<input type="password" id="password" name="password" class="form-control">
			<div class="form-notch">
				<div class="form-notch-leading" style="width: 9px;"></div>
				<div class="form-notch-middle" style="width: 64.8px;"></div>
				<div class="form-notch-trailing"></div>
			</div>
		</div>
		<div class="text-center pt-1 mb-4 pb-1">
			<button type='submit' class="btn btn-primary" type="button">Log in</button>
		</div>
		<div class="text-center pt-2 pb-2 mb-1 bg-light">
			<a class="text-muted" href="reset_password.php">Forgot password?</a>
		</div>
	</form>
</div>
<div class="text-center pt-2 mt-2">
	<p class="mb-0 me-2">Don't have an account?</p>
	<a type="button" class="btn btn-outline-danger" href='index.php?v=register'>Create new</a>
</div>
</main>
</div>