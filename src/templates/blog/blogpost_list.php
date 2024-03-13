<?php
$tr = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->pkey;

        $tr .= "<tr>
            <td class='text-end'>{$num}</td>
            <td class='text-start'>{$row->title}</td>
            <td class='text-start'>{$row->subtitle}</td>
            <td class='text-start'>{$row->seo_title}</td>
            <td class='text-start'>{$row->meta_description}</td>
            <td class='text-start'>{$row->short_description}</td>
            <td class='text-center'>{$row->is_published}</td>
            <td class='text-center'>{$row->created_at}</td>
            <td class='text-center'>{$row->updated_at}</td>
            <td class='text-end'><a class='btn btn-primary btn-xs' href='/blog/blogpost/edit/{$row->pkey}'>Edit</a></td>
        </tr>";
    }
}
?>
<div role='main' class='container'>
    <div class='mt-4 text-start'>
        <a class='btn btn-primary' href="/edit/blog-blogpost/blog?pkey=0" alt="Add New Post"
            title="Add New Post">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Administration: Posts</h3>
    </div>
    <table id='user_list' class='table table-striped table-bordered cell-border'>
        <thead>
            <tr>
                <th class='text-end'>Post #</th>
                <th class='text-start'>Title</th>
                <th class='text-start'>Subtitle</th>
                <th class='text-start'>SEO</th>
                <th class='text-start'>Meta</th>
                <th class='text-start'>Short</th>
                <th class='text-center'>Published</th>
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