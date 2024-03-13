<?php
$perm = $this->model;

$group_options = \Freedom\Models\UserGroup::OptionList($perm->group_id);
$module_options = \Freedom\Models\Module::OptionList($perm->module_id);

$VIEW_PERM = \Freedom\Models\Permission::$VIEW_PERM;
$EDIT_PERM = \Freedom\Models\Permission::$EDIT_PERM;
$ADD_PERM = \Freedom\Models\Permission::$ADD_PERM;
$DELETE_PERM = \Freedom\Models\Permission::$DELETE_PERM;

$chk_view = ($perm->rights & $VIEW_PERM ) ? "checked" : "";
$chk_edit = ($perm->rights & $EDIT_PERM) ? "checked" : "";
$chk_add = ($perm->rights & $ADD_PERM) ? "checked" : "";
$chk_delete = ($perm->rights & $DELETE_PERM) ? "checked" : "";

# TODO: Add breadcrumb
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
<div class='card-body'>
	<form action='/admin/permission' method='POST'>
		<input type='hidden' name='pkey' value='{$perm->pkey}'>
		<h4>Permissions #{$perm->pkey}:</h4>
		<div class='mb-1'>
			<label class='form-label' for='group_id' style='margin-left: 0px;'>Group</label>
			<select id='group_id' name='group_id' class='form-control'>
                $group_options
            </select>
		</div>
		<div class='mb-1'>
			<label class='form-label' for='module_id' style='margin-left: 0px;'>Module</label>
			<select type='text' id='module_id' name='module_id' class='form-control'>
                $module_options
            </select>
		</div>
        <div class='mb-1 form-group'>
			<label class='form-label' style='margin-left: 0px;'>Rights:</label>
			<input type='checkbox' id='view_rights' name='view_rights' value='$VIEW_PERM' class='form-checkbox' $chk_view />
            <label class='form-label' for='view_rights' style='margin-left: 0px;'>View</label>
            <input type='checkbox' id='edit_rights' name='edit_rights' value='$EDIT_PERM' class='form-checkbox' $chk_edit />
            <label class='form-label' for='edit_rights' style='margin-left: 0px;'>Edit</label>
            <input type='checkbox' id='add_rights' name='add_rights' value='$ADD_PERM' class='form-checkbox' $chk_add />
            <label class='form-label' for='add_rights' style='margin-left: 0px;'>ADD</label>
            <input type='checkbox' id='delete_rights' name='delete_rights' value='$DELETE_PERM' class='form-checkbox' $chk_delete />
            <label class='form-label' for='delete_rights' style='margin-left: 0px;'>Delete</label>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' name='act' value='1' onClick='SubmitFrom(this)'>Submit</button>
            <button type='submit' class='btn btn-primary' name='act' value='-1' onClick='SubmitFrom(this)'>Delete</button>
		</div>
	</form>
</div>
</main>
</div>";
