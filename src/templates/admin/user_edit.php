<?php
$usr = $this->model;

$this->Crumb("/home", "Home");
$this->Crumb("/admin/user/list", "Users");
if (isset($_SERVER['HTTP_REFERER']))
    $this->Crumb($_SERVER['HTTP_REFERER'], " <i class='fa fa-angle-left'></i>Back");
$this->Crumb(null, "Edit", true);

$all_roles = \Freedom\Models\UserGroup::GetAllGroups();
$all_perms = $usr->Get('permissions', false, false);

$has_view = \Freedom\Models\Permission::$VIEW_PERM;
$has_edit = \Freedom\Models\Permission::$EDIT_PERM;
$has_add = \Freedom\Models\Permission::$ADD_PERM;
$has_delete = \Freedom\Models\Permission::$DELETE_PERM;

# User status options
$active_status = \Freedom\Models\User::$STATUS_ACTIVE;
$inactive_status = \Freedom\Models\User::$STATUS_INACTIVE;
$status_array = json_decode("[
    {\"val\": \"$active_status\", \"text\": \"$active_status\" },
    {\"val\": \"$inactive_status\", \"text\": \"$inactive_status\" }
]");
$status_options = \Freedom\Views\CDView::OptionsList($usr->status, $status_array);

# User type options
$type_user = \Freedom\Models\User::$TYPE_USER;
$type_group = \Freedom\Models\User::$TYPE_GROUP;
$type_system = \Freedom\Models\User::$TYPE_SYSTEM;
$type_template = \Freedom\Models\User::$TYPE_TEMPLATE;
$user_type_array = json_decode("[
    {\"val\": \"$type_user\", \"text\": \"$type_user\" },
    {\"val\": \"$type_group\", \"text\": \"$type_group\" },
    {\"val\": \"$type_system\", \"text\": \"$type_system\" },
    {\"val\": \"$type_template\", \"text\": \"$type_template\" }
]");
$user_type_options = \Freedom\Views\CDView::OptionsList($usr->user_type, $user_type_array);

# Group/Roles
$default_group_options = "<option value=''>--Select Group/Role--</option>";
$role_items = "";
if (!empty($all_roles))
{
    foreach ($all_roles as $role)
    {
        $checked = $usr->hasRole($role->user_name) ? "checked" : "";

        $role_items .= "<div class='list-item'>
            <input type='checkbox' id='roles_{$role->user_id}' name='roles[$role->user_id]' value='{$role->user_id}' $checked>
            <label for='roles_{$role->user_id}'>{$role->user_name}</label>
        </div>";

        $sel = ($usr->default_group == $role->user_id) ? "selected" : "";
        $default_group_options .= "<option value='{$role->user_id}' $sel>{$role->user_name}</option>";
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
        <div class='row'>
            <div class='col-2'>{$perm->group_name}</div>
            <div class='col-2'>{$perm->module_name}:</div>
            <div class='col-8'>
                <input type='checkbox' id='roles_{$role->user_id}_has_view' name='permissions[$role->user_id][has_view]' value='$has_view' $chk_view disabled />
                <label for='roles_{$role->user_id}_has_view'>View</label>

                <input type='checkbox' id='roles_{$role->user_id}_has_edit' name='permissions[$role->user_id][has_edit]' value='$has_edit' $chk_edit disabled />
                <label for='roles_{$role->user_id}_has_edit'>Edit</label>

                <input type='checkbox' id='roles_{$role->user_id}_has_add' name='permissions[$role->user_id][has_add]' value='$has_add' $chk_add disabled />
                <label for='roles_{$role->user_id}_has_add'>Add</label>

                <input type='checkbox' id='roles_{$role->user_id}_has_delete' name='permissions[$role->user_id][has_delete]' value='$has_delete' $chk_delete disabled />
                <label for='roles_{$role->user_id}_has_delete'>Delete</label>
            </div>
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
	<div class='row'>
        <div class='col'>
            <div class='card-body p-2'>
                <form action='/admin/user/save' method='POST'>
                    <input type='hidden' name='pkey' value='{$usr->pkey}'>
                    <h4 class='card-title mb-2 border-bottom'>User Edit</h4>
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
                    <div class='mb-1'>
                        <label class='form-label' for='status' style='margin-left: 0px;'>Status</label>
                        <select id='status' name='status' class='form-control'>
                            $status_options
                        </select>
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='user_type' style='margin-left: 0px;'>Type</label>
                        <select id='user_type' name='user_type' class='form-control'>
                            $user_type_options
                        </select>
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='default_group' style='margin-left: 0px;'>Default Group</label>
                        <select id='default_group' name='default_group' class='form-control'>
                            $default_group_options
                        </select>
                    </div>
                    <div class='text-center buttons'>
                        <button type='submit' class='btn btn-primary' type='button' onClick='SubmitFrom(this)'>Submit</button>
                    </div>
                </form>
            </div>
        </div>
        <div class='col'>
            <div>
                <form action='/admin/user/user_roles' method='POST'>
                    <input type='hidden' name='pkey' value='{$usr->pkey}' />
                    <input type='hidden' name='user_id' value='{$usr->pkey}' />
                    <div class='card-body p-2'>
                        <h4 class='card-title mb-2 border-bottom'>Roles/Groups</h4>
                        $role_items
                        <div class='text-center buttons'>
                            <button type='submit' class='btn btn-primary' type='button' onClick='SubmitFrom(this)'>Update</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class='mt-2'>
                <div class='card-body p-2'>
                    <h4 class='card-title mb-2 border-bottom'>Permissions</h4>
                    $perm_items
                </div>
            </div>
            <div class='mt-2'>
                <div class='card-body p-2'>
                    <h4 class='card-title mb-2 border-bottom'>Additional Information</h4>
                    <div class='row'>
                        <div class='col-4'>Status:</div>
                        <div class='col-8'>{$usr->status}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Type:</div>
                        <div class='col-8'>{$usr->user_type}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Default Group/Role:</div>
                        <div class='col-8'>{$usr->default_group}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Verified:</div>
                        <div class='col-8'>{$usr->verified}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Verification:</div>
                        <div class='col-8'>{$usr->verification}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Login Attempts:</div>
                        <div class='col-8'>{$usr->login_attempts}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Block Expires:</div>
                        <div class='col-8'>{$usr->block_expires}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Created On:</div>
                        <div class='col-8'>{$usr->created_at}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Last Modified:</div>
                        <div class='col-8'>{$usr->updated_at}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>";
