<?php
$tr = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->pkey;

        $tr .= "<tr>
            <td class='text-right'>{$num}</td>
            <td class='text-left'>{$row->title}</td>
            <td class='text-left'>{$row->subtitle}</td>
            <td class='text-left'>{$row->seo_title}</td>
            <td class='text-left'>{$row->meta_description}</td>
            <td class='text-left'>{$row->short_description}</td>
            <td class='text-center'>{$row->is_published}</td>
            <td class='text-center'>{$row->created_at}</td>
            <td class='text-center'>{$row->updated_at}</td>
            <td class='text-right'><a class='btn btn-primary btn-xs' href='/edit/blog-blogpost/blog?pkey={$row->pkey}'>Edit</a></td>
        </tr>";
    }
}
?>
<div role='main' class='container'>
    <div class='mt-4 text-left'>
        <a class='btn btn-primary' href="/edit/blog-blogpost/blog?pkey=0" alt="Add New Post"
            title="Add New Post">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Administration: Posts</h3>
    </div>
    <table id='user_list' class='table table-striped table-bordered cell-border'>
        <thead>
            <tr>
                <th class='text-right'>Post #</th>
                <th class='text-left'>Title</th>
                <th class='text-left'>Subtitle</th>
                <th class='text-left'>SEO</th>
                <th class='text-left'>Meta</th>
                <th class='text-left'>Short</th>
                <th class='text-center'>Published</th>
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