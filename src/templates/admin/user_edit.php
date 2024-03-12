<?php
$usr = $this->model;

$all_roles = \Freedom\Models\Role::GetAllRoles();
$all_models = \Freedom\Models\CDModel::GetAllModels();
$all_perms = \Freedom\Models\Permission::GetAllPermissions();

$role_items = "";
if (!empty($all_roles))
{
	foreach ($all_roles as $role)
	{
		$role_items .= "<li class='nav-item'>{$role->name}</li>";

		$perm_items = "";
		if (!empty($all_perms))
		{
			foreach ($all_perms as $perm)
			{
				$chked = $usr->HasPermission("{$role->name}-{$perm->name}") ? "checked" : "";
				$perm_items .= "<li><input type='checkbox' name='{$role->name}-{$perm->name}' $chked>{$perm->name}</li>";
			}
		}

		$role_panes .= "
		<div class='tab-pane' id='{$role->name}' role='tabpanel' aria-labelledby='{$role->name}-tab'>
			<ul>
				$perm_items
			</ul>
		</div>";
	}
}

$model_items = "";
if (!empty($all_model))
{
	foreach ($all_model as $model)
	{
		$model_items .= "<li class='nav-item'>{$model->name}</li>";

		$perm_items = "";
		if (!empty($all_perms))
		{
			foreach ($all_perms as $perm)
			{
				$chked = $usr->HasPermission("{$model->name}-{$perm->name}") ? "checked" : "";
				$perm_items .= "<li><input type='checkbox' name='{$model->name}-{$perm->name}' $chked>{$perm->name}</li>";
			}
		}

		$model_panes .= "
		<div class='tab-pane' id='{$model->name}' role='tabpanel' aria-labelledby='{$model->name}-tab'>
			<ul>
				$perm_items
			</ul>
		</div>";
	}
}

echo "
<style>
.card-body
{
	border: 1px solid #CCC;
	border-radius: 8px;
}
</style>
<div role='main' class='container'>
	<main class='form-signin w-100 m-auto'>
		<div class='card-body'>
			<form action='/admin/user/save' method='POST'>
				<input type='hidden' name='pkey' value='{$usr->pkey}'>
				<h4 class='card-title'>User Edit</h4>
				<div class='mb-1'>
					<label class='form-label' for='user_name' style='margin-left: 0px;'>Username</label>
					<input type='text' id='user_name' name='user_name' class='form-control' placeholder='Username or email address' value='{$usr->user_name}'>
				</div>
				<div class='mb-1'>
					<label class='form-label' for='first_name' style='margin-left: 0px;'>First Name</label>
					<input type='text' id='first_name' name='first_name' class='form-control' placeholder='First Name'  value='{$usr->first_name}'>
				</div>
				<div class='mb-1'>
					<label class='form-label' for='last_name' style='margin-left: 0px;'>Last Name</label>
					<input type='text' id='last_name' name='last_name' class='form-control' placeholder='Last Name'  value='{$usr->last_name}'>
				</div>
				<div class='mb-1'>
					<label class='form-label' for='nickname' style='margin-left: 0px;'>Nickname</label>
					<input type='text' id='nick_name' name='nick_name' class='form-control' placeholder='What should we call you'  value='{$usr->nick_name}'/>
				</div>
				<div class='mb-1'>
					<label class='form-label' for='email' style='margin-left: 0px;'>Email</label>
					<input type='email' id='email' name='email' class='form-control' placeholder='Email Address' value='{$usr->email}'>
				</div>
				<div class='mb-1'>
					<label class='form-label' for='email' style='margin-left: 0px;'>Phone Number</label>
					<input type='tel' id='phone' name='phone' class='form-control' placeholder='Phone Number' value='{$usr->phone}'>
				</div>
				<div class='text-center buttons'>
					<button type='submit' class='btn btn-primary' type='button' onClick='SubmitFrom(this)''>Submit</button>
				</div>
			</form>
		</div>
	</main>
	<div class='container'>
		<ul class='nav nav-tabs' id='role-tabs' role='tablist'>
			$role_items
		</ul>
		<div class='tab-content' id='role-permissions'>
			$role_panes
		</div>
	</div>
	<div class='container'>
		<ul class='nav nav-tabs' id='model-tabs' role='tablist'>
			$model_items
		</ul>
		<div class='tab-content' id='model-permissions'>
			$model_panes
		</div>
	</div>
</div>";
