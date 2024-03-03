<?php
$tr = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->id;

        $tr .= "<tr>
            <td class='text-right'>{$num}</td>
            <td class='text-left'>{$row->name}</td>
            <td class='text-left'>{$row->guard_name}</td>
            <td class='text-center'>{$row->created_at}</td>
            <td class='text-center'>{$row->updated_at}</td>
            <td class='text-right'><a class='btn btn-primary btn-xs' href='/edit/permission/?pkey={$row->id}'>Edit</a></td>
        </tr>";
    }
}
?>
<div role='main' class='container'>
    <div class='mt-4 text-left'>
        <a class='btn btn-primary' href="permission/0" alt="Add New Permission" title="Add New Permission">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Administration: Permission listing</h3>
    </div>
    <table id='user_list' class='table table-striped table-bordered cell-border'>
        <thead>
            <tr>
                <th class='text-right'>#</th>
                <th class='text-left'>Name</th>
                <th class='text-left'>Guard Name</th>
                <th class='text-center'>Created</th>
                <th class='text-center'>Modified</th>
                <th class='text-right'>Action</th>
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