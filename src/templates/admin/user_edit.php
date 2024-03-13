<?php
$usr = $this->model;
$this->Crumb("/home", "Home");
$this->Crumb("/admin/user/list", "Users");
$this->Crumb(null, "Edit", true);

$all_roles = \Freedom\Models\UserGroup::GetAllGroups();
$all_perms = $usr->Get('permissions', false, false);

$has_view = \Freedom\Models\Permission::$VIEW_PERM;
$has_edit = \Freedom\Models\Permission::$EDIT_PERM;
$has_add = \Freedom\Models\Permission::$ADD_PERM;
$has_delete = \Freedom\Models\Permission::$DELETE_PERM;

$role_items = "";
if (!empty($all_roles))
{
    foreach ($all_roles as $role)
    {
        $checked = $usr->hasRole($role->user_name)?"checked":"";

        $role_items .= "<div class='list-item'>
            <input type='checkbox' id='roles_{$role->user_id}' name='roles[$role->user_id]' value='{$role->user_id}' $checked>
            <label for='roles_{$role->user_id}'>{$role->user_name}</label>
        </div>";
    }
}
else
{
    $perm_items .= "<div class='info'>No roles available</div>";
}

$perm_items = "";
if (!empty($all_perms))
{
    foreach ($all_perms as $perm)
    {
        $chk_view = ($perm->rights & $has_view) ? "checked" : "";
        $chk_edit = ($perm->rights & $has_edit) ? "checked" : "";
        $chk_add = ($perm->rights & $has_add) ? "checked" : "";
        $chk_delete = ($perm->rights & $has_delete) ? "checked" : "";

        $perm_items .= "
        <div class='list-item'>
            <span class='nav-text'>{$perm->group_name} {$perm->module_name}</span>
            <input type='checkbox' id='roles_{$role->user_id}_has_view' name='permissions[$role->user_id][has_view]' value='$has_view' $chk_view />
            <label for='roles_{$role->user_id}_has_view'>{$role->user_name}</label>

            <input type='checkbox' id='roles_{$role->user_id}_has_edit' name='permissions[$role->user_id][has_edit]' value='$has_edit' $chk_edit />
            <label for='roles_{$role->user_id}_has_edit'>{$role->user_name}</label>

            <input type='checkbox' id='roles_{$role->user_id}_has_add' name='permissions[$role->user_id][has_add]' value='$has_add' $chk_add />
            <label for='roles_{$role->user_id}_has_add'>{$role->user_name}</label>

            <input type='checkbox' id='roles_{$role->user_id}_has_delete' name='permissions[$role->user_id][has_delete]' value='$has_delete' $chk_delete />
            <label for='roles_{$role->user_id}_has_delete'>{$role->user_name}</label>
        </div>";
    }
}
else
{
     $perm_items .= "<div class='info'>No Permissions found</div>";
}

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
	<main class='form-signin w-100 m-auto'>
		<div class='card-body p-2'>
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
					<button type='submit' class='btn btn-primary' type='button' onClick='SubmitFrom(this)'>Submit</button>
				</div>
			</form>
		</div>
	</main>
	<div class='container mt-2'>
        <form action='/admin/user/user_roles' method='POST'>
            <input type='hidden' name='pkey' value='{$usr->pkey}' />
            <input type='hidden' name='user_id' value='{$usr->pkey}' />
            <div class='card-body p-2'>
                <h4 class='card-title'>Roles/Groups</h4>
                $role_items
                <div class='text-center buttons'>
					<button type='submit' class='btn btn-primary' type='button' onClick='SubmitFrom(this)'>Update</button>
				</div>
            </div>
        </form>
	</div>
    <div class='container mt-2'>
        <div class='card-body p-2'>
            <h4 class='card-title'>Permissions</h4>
    		$perm_items
        </div>
	</div>
</div>";
