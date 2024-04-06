<?php
$tr = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->pkey;
        if ($row->profile_image)
            $src = "data:image/{$row->image_content_type};base64,{$row->profile_image}";
        else
            $src = "/images/base_blue.png";

        $tr .= "<tr>
            <td class='text-end'>{$num}</td>
            <td class='text-end'>{$row->company_id}</td>
            <td class='text-center'><img class='rounded thumbnail' src='$src' height='15' width='15' /></td>
            <td class='text-start'>
                <button class='btn btn-outline-secondary' onClick=\"SetOCInfo('Bio','#profile_bio_$num');\"><i class='fa fa-eye'></i> Bio</button>
                <div id='profile_bio_$num' class='hidden'>{$row->bio_conf}</div>
            </td>
            <td class='text-start'>
                <button class='btn btn-outline-secondary' onClick=\"SetOCInfo('About Me', '#profile_about_$num');\"><i class='fa fa-eye'></i> About</button>
                <div id='profile_about_$num' class='hidden'>{$row->bio_conf}</div>
            </td>
            <td class='text-start'>
                <button class='btn btn-outline-secondary' onClick=\"SetOCInfo('Additional Information', '#profile_info_$num');\"><i class='fa fa-eye'></i> Info</button>
                <div id='profile_info_$num' class='hidden'>{$row->bio_conf}</div>
            </td>
            <td class='text-center'>{$row->created_at}</td>
            <td class='text-center'>{$row->updated_at}</td>
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
                <th class='text-end'>Company #</th>
                <th class='text-center'>Image</th>
                <th class='text-start'>Bio</th>
                <th class='text-start'>About</th>
                <th class='text-start'>Information</th>
                <th class='text-center'>Created On</th>
                <th class='text-center'>Updated At</th>
                <th class='text-end'>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php echo $tr; ?>
        </tbody>
    </table>
</div>
<div class="offcanvas offcanvas-end" id="info-cont" tabindex="-1" aria-labelledby="offcanvas-title">
    <div id="offcanvas-header" class="offcanvas-header">
        <h5 id="offcanvas-title">Info</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div id="offcanvas-body" class="offcanvas-body"> ... </div>
</div>
<style>
.offcanvas-header
{
    background-color: #f0f0f0;
    border-bottom: 1px solid gray;
}
</style>
<script type='text/javascript'>
$(function ()
{
    $('.table').DataTable({
        paging: false,
        info: false,
        order: [[2, 'asc']]
    });
});

function SetOCInfo(title, body_id)
{
    $('#offcanvas-header').html(title);
    $('#offcanvas-body').html($(body_id).html());
    $('#info-cont').offcanvas('show');
}
</script>