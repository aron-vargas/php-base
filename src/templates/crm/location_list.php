<?php
$tr = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->location_id;
        $tr .= "<tr>
            <td class='text-right'>{$num}</td>
            <td class='text-left'>{$row->short_name}</td>
            <td class='text-left'>{$row->title}</td>
            <td class='text-left'>{$row->role}</td>
            <td class='text-left'>{$row->address_1}</td>
            <td class='text-left'>{$row->address_2}</td>
            <td class='text-left'>{$row->city}</td>
            <td class='text-left'>{$row->state}</td>
            <td class='text-left'>{$row->zip}</td>
            <td class='hidden text-left'>{$row->country}</td>
            <td class='text-left'>{$row->phone}</td>
            <td class='text-left'>{$row->mobile}</td>
            <td class='text-left'>{$row->url}</td>
            <td class='text-left'>{$row->email}</td>
            <td class='text-left'>{$row->description}</td>
            <td class='text-left'>{$row->location_status}</td>
            <td class='text-left'>{$row->location_type}</td>
            <td class='text-center'>{$row->created_on}</td>
            <td class='text-center'>{$row->last_mod}</td>
            <td class='text-right'><a class='btn btn-primary btn-xs' href='/crm/location/edit/{$row->location_id}'>Edit</a></td>
        </tr>";
    }
}
?>
<div role='main' class='container'>
    <div class='mt-4 text-left'>
        <a class='btn btn-primary' href="location/0" alt="Add New Location" title="Add New Location">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Administration: Loction listing</h3>
    </div>
    <table id='user_list' class='table table-striped table-bordered cell-border text-nowrap'>
        <thead>
            <tr>
                <th class='text-right'>#</th>
                <th class='text-left'>Name</th>
                <th class='text-left'>Title</th>
                <th class='text-left'>Role</th>
                <th class='text-left'>Address_1</th>
                <th class='text-left'>Address_2</th>
                <th class='text-left'>City</th>
                <th class='text-left'>State</th>
                <th class='text-left'>Zip</th>
                <th class='hidden text-left'>country</th>
                <th class='text-left'>Phone</th>
                <th class='text-left'>Mobile</th>
                <th class='text-left'>URL</th>
                <th class='text-left'>Email</th>
                <th class='text-left'>Description</th>
                <th class='text-left'>Status</th>
                <th class='text-left'>Type</th>
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