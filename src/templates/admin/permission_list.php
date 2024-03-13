<?php
use Freedom\Models\Permission;

$tr = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->pkey;

        $rights = "";
        if ($row->rights & Permission::$VIEW_PERM)
            $rights .= "<span class='badge bg-success'>VIEW</span>";
        if ($row->rights & Permission::$EDIT_PERM)
            $rights .= "<span class='badge bg-warning'>EDIT</span>";
        if ($row->rights & Permission::$ADD_PERM)
            $rights .= "<span class='badge bg-warning'>ADD</span>";
        if ($row->rights & Permission::$DELETE_PERM)
            $rights .= "<span class='badge bg-danger'>DELETE</span>";

        $tr .= "<tr>
            <td class='text-end'>{$num}</td>
            <td class='text-start'>{$row->group_id} {$row->group_name}</td>
            <td class='text-start'>{$row->module_id} {$row->module_name}</td>
            <td class='text-end'>{$rights}</td>
            <td class='text-center'>{$row->created_at}</td>
            <td class='text-center'>{$row->updated_at}</td>
            <td class='text-end'><a class='btn btn-primary btn-xs' href='/admin/permission/edit/{$row->pkey}'>Edit</a></td>
        </tr>";
    }
}
?>
<style>
    .badge {
        font-size: 11px;
        padding: 2px 6px;
        margin-right: 3px;
    }
</style>
<div role='main' class='container'>
    <div class='mt-4 text-start'>
        <a class='btn btn-primary' href="/admin/permission/edit/0" alt="Add New Permission"
            title="Add New Permission">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Administration: Permission listing</h3>
    </div>
    <table id='user_list' class='table table-striped table-bordered cell-border'>
        <thead>
            <tr>
                <th class='text-end'>#</th>
                <th class='text-start'>Group ID</th>
                <th class='text-start'>Module ID</th>
                <th class='text-end'>Rights</th>
                <th class='text-center'>Created</th>
                <th class='text-center'>Modified</th>
                <th class='text-end'>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php echo $tr; ?>
        </tbody>
    </table>
</div>
<script type='text/javascript'>
    $(function ()
    {
        $('.table').DataTable({
            paging: false,
            info: false,
            order: [[0, 'asc']]
        });
    });
</script>