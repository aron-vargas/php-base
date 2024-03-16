<?php
use Freedom\Models\CDModel;
use Freedom\Models\User;

$config = $this->config;
$session_user = $config->get("session")->user;

$posts = "";
$now = time();

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->pkey;
        $publised = $row->is_published ? "published" : "un-published";
        $author = new User($row->user_id);
        $display_date = date("M j, Y", strtotime($row->created_at));
        $age = CDModel::human_time_diff(strtotime($row->created_at), $now);
        $image_large_src = ($row->image_large) ? $row->image_large : "/images/blog_bg.png";
        $image_medium_src = ($row->image_medium) ? $row->image_medium : "/images/base_blue.png";
        $view_count = $row->views;
        $like_count = count($row->likes);

        $edit_btn = "";
        if ($row->user_id == $session_user->user_id)
        {
            $edit_btn = "<a class='btn btn-small btn-primary' href='/blog/blogpost/edit/{$row->pkey}'><i class='fa fa-pencil'></i></a>";
        }

        $comment_count = count($row->comments);
        $posts .= "
        <div class='col m-2'>
            <div id='blog_post_{$row->pkey}' class='card col {$publised}'>
                <div class='hidden seo'>{$row->seo_title}</div>
                <div class='hidden meta'>{$row->meta_description}</div>
                <img src='$image_large_src' class='card-img-top' height='200'/>
                <div class='card-body'>
                    <div class='badges text-end'>
                        <span class='badge badge-info bg-info'>$publised</span>
                        $edit_btn
                    </div>
                    <h5 class='card-title'>{$row->subtitle}</h5>
                    <div class'by-line'>
                        <div class='rounded-circle avatar'>
                            <img src='$image_medium_src' class='avatar'/>
                        </div>
                        <div class='d-inline author-name'>
                            {$author->first_name} {$author->last_name}
                            <i class='fa fa-crown'></i>
                        </div>
                        <i class='fa fa-circle dot mx-1'></i>
                        <div class='d-inline author-alias'>
                            {$author->nick_name}
                        </div>
                        <i class='fa fa-circle dot mx-1'></i>
                        <div class='d-inline datetime'>
                            {$display_date}
                        </div>
                        <i class='fa fa-circle dot mx-1'></i>
                        <div class='d-inline datetime'>
                            {$age}
                        </div>
                    </div>
                    <p class='card-text short-description'>{$row->short_description}</p>
                </div>
                <div class='card-footer'>
                    <div class='row'>
                        <div class='col'>
                            <span id='blogpost_views_{$row->pkey}'>{$view_count}</span>
                            <i class='fa fa-eye'></i>
                        </div>
                        <div class='col'>
                            {$comment_count}
                            <a href='/blog/blogcomment/edit?pkey=0&post_id={$row->pkey}'>
                                <i class='fa fa-comment text-primary'></i>
                            </a>
                        </div>
                        <div class='col'>
                            <span id='blogpost_likes_{$row->pkey}'>{$like_count}</span>
                            <a href='#blog_post_{$row->pkey}' onClick='Like({$row->pkey});'>
                                <i class='fa fa-heart text-danger'></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>";
    }
}
?>
<div role='main' class='container'>
    <div class='mt-4 text-start'>
        <a class='btn btn-primary' href="/blog/blog-blogpost/edit/0" alt="Add New Post" title="Add New Post">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Recent Posts</h3>
    </div>
    <div class='blog row'>
        <?php echo $posts; ?>
    </div>
</div>
<script>
    function Like(post_id)
    {
        $.ajax({
            dataType: 'json',
            url: "/blog/bloglike/like/" + post_id,
            success: function (json)
            {
                var like_elem = $("#blogpost_likes_" + json.data.pkey);
                like_elem.text(json.data.likes);
            },
            error: function ()
            {
                // Error occurred loading language file, continue on as best we can
                alert("There was a problem with the like request");
            }
        });
    }
</script>