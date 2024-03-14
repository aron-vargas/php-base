<?php
$module = $this->model;
$module->LoadModulePermissions($module->pkey, true);

$this->Crumb("/home", "Home");
$this->Crumb("/admin/module/list", "Modules");
if (isset ($_SERVER['HTTP_REFERER']))
    $this->Crumb($_SERVER['HTTP_REFERER'], " <i class='fa fa-angle-left'></i>Back");
$this->Crumb(null, "Edit", true);

$all_groups = \Freedom\Models\UserGroup::GetAllGroups(\Freedom\Models\UserGroup::$USER_NICK_NAME);

$has_view = \Freedom\Models\Permission::$VIEW_PERM;
$has_add = \Freedom\Models\Permission::$ADD_PERM;
$has_edit = \Freedom\Models\Permission::$EDIT_PERM;
$has_delete = \Freedom\Models\Permission::$DELETE_PERM;

$group_items = "";
if (!empty ($all_groups))
{
    foreach ($all_groups as $group)
    {
        $group_items .= "<div class='perm-cont mb-1'>
            <h6 class='card-title'>Group/Role: <b>{$group->nick_name}</b></h6>";
        $permission = $module->GetPermission($group->user_id, $module->pkey);
        $chk_view = $chk_add = $chk_edit = $chk_delete = "";

        if ($permission)
        {
            $chk_view = ($permission->rights & $has_view) ? "checked" : "";
            $chk_add = ($permission->rights & $has_add) ? "checked" : "";
            $chk_edit = ($permission->rights & $has_edit) ? "checked" : "";
            $chk_delete = ($permission->rights & $has_delete) ? "checked" : "";
        }

        $group_items .= "
            <div class='border p-1'>
                <input type='checkbox' id='permissions_{$group->user_id}_has_view' name='permissions[$group->user_id][has_view]' value='$has_view' $chk_view />
                <label for='permissions_{$group->user_id}_has_view'>View</label>

                <input type='checkbox' id='permissions_{$group->user_id}_has_edit' name='permissions[$group->user_id][has_edit]' value='$has_edit' $chk_edit />
                <label for='permissions_{$group->user_id}_has_edit'>Edit</label>

                <input type='checkbox' id='permissions_{$group->user_id}_has_add' name='permissions[$group->user_id][has_add]' value='$has_add' $chk_add />
                <label for='permissions_{$group->user_id}_has_add'>Add</label>

                <input type='checkbox' id='permissions_{$group->user_id}_has_delete' name='permissions[$group->user_id][has_delete]' value='$has_delete' $chk_delete />
                <label for='permissions_{$group->user_id}_has_delete'>Delete</label>
            </div>
        </div>";
    }
}
else
{
    $group_items .= "<div class='info'>No Permissions found</div>";
}


$status_active = \Freedom\Models\Module::$STATUS_ACTIVE;
$status_inactive = \Freedom\Models\Module::$STATUS_INACTIVE;

$chk_status_active = ($module->modual_status == $status_active) ? "checked" : "";
$chk_status_inactive = ($module->modual_status == $status_inactive) ? "checked" : "";

$chk_hidden_y = ($module->hidden) ? "checked" : "";
$chk_hidden_n = ($module->hidden) ? "" : "checked";

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
                <form action='/admin/module/save' method='POST'>
                    <input type='hidden' name='pkey' value='{$module->pkey}'>
                    <h4 class='card-title mb-2 border-bottom'>Module Edit</h4>
                    <div class='mb-1'>
                        <label class='form-label' for='name' style='margin-left: 0px;'>Display Name</label>
                        <input type='text' id='name' name='name' class='form-control' placeholder='Module Name' value='{$module->name}'>
                    </div>
                    <div class='mb-1'>
                        <div class='form-text'>Status</div>
                        <div class='border p-1'>
                            <div class='form-check form-check-inline'>
                                <input type='radio' id='modual_status_active' name='modual_status' class='form-check-input' value='{$status_active}' $chk_status_active />
                                <label class='form-check-label' for='modual_status_active' style='margin-left: 0px;'>Active</label>
                            </div>
                            <div class='form-check form-check-inline'>
                                <input type='radio' id='modual_status_inactive' name='modual_status' class='form-check-input' value='{$status_inactive}' $chk_status_inactive />
                                <label class='form-check-label' for='modual_status_inactive' style='margin-left: 0px;'>In-Active</label>
                            </div>
                        </div>
                    </div>
                    <div class='mb-1'>
                        <div class='form-text'>Hidden</div>
                        <div class='border p-1'>
                            <div class='form-check form-check-inline'>
                                <input type='radio' id='hidden_y' name='hidden' class='form-check-input' value='1' $chk_hidden_y />
                                <label class='form-check-label' for='hidden_y' style='margin-left: 0px;'>Yes</label>
                            </div>
                            <div class='form-check form-check-inline'>
                                <input type='radio' id='hidden_n' name='hidden' class='form-check-input' value='0' $chk_hidden_n />
                                <label class='form-check-label' for='hidden_n' style='margin-left: 0px;'>No</label>
                            </div>
                        </div>
                    </div>
                    <div class='text-center buttons'>
                        <button type='submit' class='btn btn-primary' type='button' onClick='SubmitFrom(this)'>Submit</button>
                    </div>
                </form>
            </div>
	    </div>
        <div class='col'>
            <div>
                <form action='/admin/module/group-perms' method='POST'>
                    <input type='hidden' name='pkey' value='{$module->pkey}'>
                    <div class='card-body p-2'>
                        <h4 class='card-title mb-2 border-bottom'>Permissions</h4>
                        $group_items
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
                        <div class='col-4'>Created On:</div>
                        <div class='col-8'>{$module->created_at}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Created By:</div>
                        <div class='col-8'>{$module->created_by}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Last Modified:</div>
                        <div class='col-8'>{$module->updated_at}</div>
                    </div>
                    <div class='row'>
                        <div class='col-4'>Last Modified By:</div>
                        <div class='col-8'>{$module->updated_by}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>";
