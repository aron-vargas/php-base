<?php
$tr = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->pkey;

        $tr .= "<tr>
            <td class='text-right'>{$num}</td>
            <td class='text-right'>{$row->company_id}</td>
            <td class='text-center'><span class='rounded-circle avatar-md float-start base_blue'>&nbsp;</span></td>
            <td class='text-left'>{$row->bio_conf}</td>
            <td class='text-left'>{$row->about_conf}</td>
            <td class='text-left'>{$row->info_conf}</td>
            <td class='text-center'>{$row->createdAt}</td>
            <td class='text-center'>{$row->updatedAt}</td>
            <td class='text-right'><a class='btn btn-primary btn-xs' href='/auth/profile/{$row->pkey}'>Edit</a></td>
        </tr>";
    }
}
?>
<div role='main' class='container'>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Administration: User Profile listing</h3>
    </div>
    <table id='user_list' class='data striped'>
        <thead>
            <tr>
                <th class='text-right'>User #</th>
                <th class='text-right'>company_id</th>
                <th class='text-center'>profile_image</th>
                <th class='text-left'>bio_conf</th>
                <th class='text-left'>about_conf</th>
                <th class='text-left'>info_conf</th>
                <th class='text-center'>createdAt</th>
                <th class='text-center'>updatedAt</th>
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
        $('.data').DataTable({
            paging: false,
            info: false,
            order: [[2, 'asc']]
        });
    });
</script>