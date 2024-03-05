<?php
$perm = $this->data;

# TODO: Add breadcrumb
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
	<form action='/crm/permission' method='POST'>
		<input type='hidden' name='pkey' value='{$perm->pkey}'>
		<h4>Permission #{$perm->pkey}:</h4>
		<div class='mb-4'>
			<label class='form-label' for='name' style='margin-left: 0px;'>Name</label>
			<input type='text' id='name' name='name' class='form-control' placeholder='Name' value='{$perm->name}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='guard_name' style='margin-left: 0px;'>Guard Name</label>
			<input type='text' id='guard_name' name='guard_name' class='form-control' placeholder='Guard Name' value='{$perm->guard_name}'>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' name='act' value='1'>Submit</button>
            <button type='submit' class='btn btn-primary' name='act' value='-1'>Delete</button>
		</div>
	</form>
</div>
</main>
</div>";
