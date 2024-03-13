<?php
$tr = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->pkey;

        $tr .= "<tr>
            <td class='text-end'>{$num}</td>
            <td class='text-end'>{$row->company_id}</td>
            <td class='text-center'><span class='rounded-circle avatar-md float-start base_blue'>&nbsp;</span></td>
            <td class='text-start'>{$row->bio_conf}</td>
            <td class='text-start'>{$row->about_conf}</td>
            <td class='text-start'>{$row->info_conf}</td>
            <td class='text-center'>{$row->createdAt}</td>
            <td class='text-center'>{$row->updatedAt}</td>
            <td class='text-end'><a class='btn btn-primary btn-xs' href='/admin/userprofile/edit/{$row->pkey}'>Edit</a></td>
        </tr>";
    }
}
?>
<div role='main' class='container'>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Administration: User Profile listing</h3>
    </div>
    <table id='user_list' class='table table-striped table-bordered cell-border'>
        <thead>
            <tr>
                <th class='text-end'>User #</th>
                <th class='text-end'>company_id</th>
                <th class='text-center'>profile_image</th>
                <th class='text-start'>bio_conf</th>
                <th class='text-start'>about_conf</th>
                <th class='text-start'>info_conf</th>
                <th class='text-center'>createdAt</th>
                <th class='text-center'>updatedAt</th>
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
            order: [[2, 'asc']]
        });
    });
</script>