<?php
$group = $this->model;
$group->LoadRolePermissions($group->pkey, true);

$this->Crumb("/home", "Home");
$this->Crumb("/admin/usergroup/list", "Roles");
if (isset($_SERVER['HTTP_REFERER']))
    $this->Crumb($_SERVER['HTTP_REFERER'], " <i class='fa fa-angle-left'></i>Back");
$this->Crumb(null, "Edit", true);

$mfilter = \Freedom\Models\Module::DefaultFilter();
$all_models = \Freedom\Models\Module::GetAll("module", $mfilter);
//$all_perms = \Freedom\Models\Permission::GetAllPermissions();

$has_view = \Freedom\Models\Permission::$VIEW_PERM;
$has_add = \Freedom\Models\Permission::$ADD_PERM;
$has_edit = \Freedom\Models\Permission::$EDIT_PERM;
$has_delete = \Freedom\Models\Permission::$DELETE_PERM;

$module_items = "";
if (!empty($all_models))
{
    foreach ($all_models as $module)
    {
        $module_items .= "<div class='perm-cont mb-1'>
            <h6 class='card-title'>Module: <b>{$module->name}</b></h6>";
        $permission = $group->GetPermission($group->user_id, $module->pkey);
        $chk_view = $chk_add = $chk_edit = $chk_delete = "";

        if ($permission)
        {
            $chk_view = ($permission->rights & $has_view) ? "checked" : "";
            $chk_add = ($permission->rights & $has_add) ? "checked" : "";
            $chk_edit = ($permission->rights & $has_edit) ? "checked" : "";
            $chk_delete = ($permission->rights & $has_delete) ? "checked" : "";
        }

        $module_items .= "
            <div class='border p-1'>
                <input type='checkbox' id='permissions_{$module->pkey}_has_view' name='permissions[$module->pkey][has_view]' value='$has_view' $chk_view />
                <label for='permissions_{$module->pkey}_has_view'>View</label>

                <input type='checkbox' id='permissions_{$module->pkey}_has_edit' name='permissions[$module->pkey][has_edit]' value='$has_edit' $chk_edit />
                <label for='permissions_{$module->pkey}_has_edit'>Edit</label>

                <input type='checkbox' id='permissions_{$module->pkey}_has_add' name='permissions[$module->pkey][has_add]' value='$has_add' $chk_add />
                <label for='permissions_{$module->pkey}_has_add'>Add</label>

                <input type='checkbox' id='permissions_{$module->pkey}_has_delete' name='permissions[$module->pkey][has_delete]' value='$has_delete' $chk_delete />
                <label for='permissions_{$module->pkey}_has_delete'>Delete</label>
            </div>
        </div>";
    }
}
else
{
    $module_items .= "<div class='info'>No Permissions found</div>";
}

# Get a list of users
$all_users = $group->GetAllMembers();
$user_list = "";
if (!empty($all_users))
{
    foreach ($all_users as $member)
    {
        $user_list .= "
            <div>{$member->first_name} {$member->last_name}
            <a href='/admin/user/edit/{$member->user_id}'><i class='fa fa-pencil'></i></a>
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
{$this->render_trail()}
<div role='main' class='container'>
    <div class='row'>
        <div class='col'>
            <div class='card-body p-2'>
                <form action='/admin/usergroup/save' method='POST'>
                    <input type='hidden' name='pkey' value='{$group->pkey}'>
                    <h4 class='card-title mb-2 border-bottom'>Group/Role Edit</h4>
                    <div class='mb-1'>
                        <label class='form-label' for='user_name' style='margin-left: 0px;'>Display Name</label>
                        <input type='text' id='user_name' name='user_name' class='form-control' placeholder='Username or email address' value='{$group->user_name}'>
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='first_name' style='margin-left: 0px;'>First Name</label>
                        <input type='text' id='first_name' name='first_name' class='form-control' placeholder='First Name'  value='{$group->first_name}'>
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='last_name' style='margin-left: 0px;'>Last Name</label>
                        <input type='text' id='last_name' name='last_name' class='form-control' placeholder='Last Name'  value='{$group->last_name}'>
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='nickname' style='margin-left: 0px;'>Nickname</label>
                        <input type='text' id='nick_name' name='nick_name' class='form-control' placeholder='What should we call you'  value='{$group->nick_name}'/>
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='email' style='margin-left: 0px;'>Email</label>
                        <input type='email' id='email' name='email' class='form-control' placeholder='Email Address' value='{$group->email}'>
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='email' style='margin-left: 0px;'>Phone Number</label>
                        <input type='tel' id='phone' name='phone' class='form-control' placeholder='Phone Number' value='{$group->phone}'>
                    </div>
                    <div class='text-center buttons'>
                        <button type='submit' class='btn btn-primary' type='button' onClick='SubmitFrom(this)'>Submit</button>
                    </div>
                </form>
            </div>
	    </div>
        <div class='col'>
            <div>
                <form action='/admin/usergroup/module-perms' method='POST'>
                    <input type='hidden' name='pkey' value='{$group->pkey}'>
                    <div class='card-body p-2'>
                        <h4 class='card-title mb-2 border-bottom'>Permissions</h4>
                        $module_items
                        <div class='text-center buttons'>
                            <button type='submit' class='btn btn-primary' type='button' onClick='SubmitFrom(this)'>Submit</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class='mt-2'>
                <div class='card-body p-2'>
                    <h4 class='card-title mb-2 border-bottom'>Additional Information</h4>
                    <div class='row'>
                        <div class='col-4'>Status:</div>
                        <div class='col-8'>{$group->status}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Type:</div>
                        <div class='col-8'>{$group->user_type}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Created On:</div>
                        <div class='col-8'>{$group->created_at}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Last Modified:</div>
                        <div class='col-8'>{$group->updated_at}</div>
                    </div>
                </div>
            </div>
            <div class='mt-2'>
                <div class='card-body p-2'>
                    <h4 class='card-title mb-2 border-bottom'>Group Members</h4>
                    $user_list
                </div>
            </div>
        </div>
    </div>
</div>";
