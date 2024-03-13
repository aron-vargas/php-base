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
        $num = $row->user_id;

        $tr .= "<tr>
            <td class='text-end'>{$num}</td>
            <td class='text-start'>{$row->user_name}<span class='rounded-circle avatar-sm float-start base_blue'>&nbsp;</span></td>
            <td class='text-start'>{$row->first_name} {$row->last_name}</td>
            <td class='text-start'>{$row->nick_name}</td>
            <td class='text-start'>{$row->email}</td>
            <td class='text-start'>{$row->phone}</td>
            <td class='text-start'>{$row->user_type}</td>
            <td class='text-start'>{$row->status}</td>
            <td class='text-end{$protected}'>{$row->verified}</td>
            <td class='text-end{$protected}'>
                <a class='btn btn-primary btn-xs' href='/admin/user/edit/{$row->user_id}'>Edit</a>
                <a class='btn btn-primary btn-xs' href='/admin/userprofile/edit/{$row->user_id}'>Profile</a>
            </td>
        </tr>";
    }
}
?>
<div role='main' class='container'>
    <div class='mt-4 text-start'>
        <a class='btn btn-primary' href="/admin/user/edit/0" alt="Add New User" title="Add New User">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Administration: User listing</h3>
    </div>
    <table id='user_list' class='table table-striped table-bordered cell-border'>
        <thead>
            <tr>
                <th class='text-end'>User #</th>
                <th class='text-start'>Username</th>
                <th class='text-start'>Full Name</th>
                <th class='text-start'>Nickname</th>
                <th class='text-start'>Email</th>
                <th class='text-start'>Phone</th>
                <th class='text-start'>Type</th>
                <th class='text-start'>Status</th>
                <th class='text-end<?php echo $protected; ?>'>Verified</th>
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
            order: [[2, 'asc']]
        });
    });
</script>