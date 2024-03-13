<?php
$tr = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->location_id;
        $tr .= "<tr>
            <td class='text-end'>{$num}</td>
            <td class='text-start'>{$row->short_name}</td>
            <td class='hidden text-start'>{$row->title}</td>
            <td class='hidden text-start'>{$row->role}</td>
            <td class='hidden text-start'>{$row->address_1}</td>
            <td class='hidden text-start'>{$row->address_2}</td>
            <td class='text-start'>{$row->city}</td>
            <td class='text-start'>{$row->state}</td>
            <td class='text-start'>{$row->zip}</td>
            <td class='hidden text-start'>{$row->country}</td>
            <td class='text-start'>{$row->phone}</td>
            <td class='text-start'>{$row->mobile}</td>
            <td class='text-start'>{$row->url}</td>
            <td class='text-start'>{$row->email}</td>
            <td class='text-start desc'>{$row->description}</td>
            <td class='text-start'>{$row->location_status}</td>
            <td class='hidden text-start'>{$row->location_type}</td>
            <td class='hidden text-center'>{$row->created_on}</td>
            <td class='hidden text-center'>{$row->last_mod}</td>
            <td class='text-end'><a class='btn btn-primary btn-xs' href='/crm/location/edit/{$row->location_id}'>Edit</a></td>
        </tr>";
    }
}
?>
<style type='text/css'>
    .desc {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>
<div role='main' class='container'>
    <div class='mt-4 text-start'>
        <a class='btn btn-primary' href="location/0" alt="Add New Location" title="Add New Location">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Location \ Address listing</h3>
    </div>
    <table id='user_list' class='table table-striped table-bordered cell-border text-nowrap'>
        <thead>
            <tr>
                <th class='text-end'>#</th>
                <th class='text-start'>Name</th>
                <th class='hidden text-start'>Title</th>
                <th class='hidden text-start'>Role</th>
                <th class='hidden text-start'>Address_1</th>
                <th class='hidden text-start'>Address_2</th>
                <th class='text-start'>City</th>
                <th class='text-start'>State</th>
                <th class='text-start'>Zip</th>
                <th class='hidden text-start'>country</th>
                <th class='text-start'>Phone</th>
                <th class='text-start'>Mobile</th>
                <th class='text-start'>URL</th>
                <th class='text-start'>Email</th>
                <th class='text-start'>Description</th>
                <th class='text-start'>Status</th>
                <th class='hidden text-start'>Type</th>
                <th class='hidden text-center'>Created</th>
                <th class='hidden text-center'>Modified</th>
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