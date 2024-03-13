<?php
$tr = "";

// Hide some stuff
$protected = " hidden";
if ($this->config->get("session"))
    $protected = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->pkey;

        $tr .= "<tr>
            <td class='text-end'>{$num}</td>
            <td class='text-start'>{$row->name}<span class='rounded-circle avatar-sm float-start base_blue'>&nbsp;</span></td>
            <td class='text-start'>{$row->modual_status}</td>
            <td class='text-center'>{$row->hidden}</td>
            <td class='text-end{$protected}'>
                <a class='btn btn-primary btn-xs' href='/admin/module/edit/{$row->pkey}'>Edit</a>
            </td>
        </tr>";
    }
}
?>
<div role='main' class='container'>
    <div class='mt-4 text-start'>
        <a class='btn btn-primary' href="/admin/module/edit/0" alt="Add New Module" title="Add New Module">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Administration: Module listing</h3>
    </div>
    <table id='user_list' class='table table-striped table-bordered cell-border'>
        <thead>
            <tr>
                <th class='text-end'>ID #</th>
                <th class='text-start'>Name</th>
                <th class='text-start'>Status</th>
                <th class='text-center'>Hidden</th>
                <th class='text-end<?php echo $protected; ?>'>Action</th>
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