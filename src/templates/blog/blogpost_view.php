<?php
use Freedom\Models\CDModel;
use Freedom\Models\User;

if (isset ($_SERVER['HTTP_REFERER']))
    $this->Crumb($_SERVER['HTTP_REFERER'], " <i class='fa fa-angle-left'></i>Back");
$this->Crumb("/home", "Home");
$this->Crumb("/blog/blogpost/show", "All Posts");
$this->Crumb(null, "View", true);

$config = $this->config;
$session_user = $config->get("session")->user;
$post = $this->model;
$now = time();

$num = $post->pkey;
$publised = $post->is_published ? "published" : "un-published";
$author = new User($post->user_id);
$display_date = date("M j, Y", strtotime($post->created_at));
$age = CDModel::human_time_diff(strtotime($post->created_at), $now);
$image_large_src = ($post->image_large) ? $post->image_large : "/images/blog_bg.png";
$image_medium_src = ($post->image_medium) ? $post->image_medium : "/images/base_blue.png";
$view_count = $post->views;
$like_count = count($post->likes);
$comment_count = count($post->comments);

$edit_btn = "";
if ($post->user_id == $session_user->user_id)
{
    $edit_btn = "<a class='btn btn-small btn-primary' href='/blog/blogpost/edit/{$post->pkey}'><i class='fa fa-pencil'></i></a>";
}

echo <<<POST
{$this->render_trail()}
<div class='hidden seo'>{$post->seo_title}</div>
<div class='hidden meta'>{$post->meta_description}</div>
<div role='main' class='container'>
    <main class='container'>
        <div id='blog_post_{$post->pkey}' class="ps-5 pt-5 pe-5 pb-1 rounded text-body-emphasis bg-body-secondary">
            <article class="large">
                <div class='badges text-end'>
                    <span class='badge badge-info bg-info'>$publised</span>
                    $edit_btn
                </div>
                <div class='ps-2'>
                    <h1 class="display-4 fst-italic">
                        {$post->title}
                    </h1>
                    <div class="lead short-desc">{$post->short_description}</div>
                </div>
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
            </article>
        </div>
        <div class='text-end'>
            <div class='d-inline ms-4'>
                <span id='blogpost_views_{$post->pkey}'>{$view_count}</span>
                <i class='fa fa-eye'></i>
            </div>
            <div class='d-inline ms-4'>
                <span id='blogpost_likes_{$post->pkey}'>{$like_count}</span>
                <a href='#blog_post_{$post->pkey}' onClick='Like({$post->pkey});'><i class='fa fa-heart text-danger'></i></a>
            </div>
            <div class='d-inline ms-4'>
                <span>{$comment_count}</span>
                <a href='/blog/blogcomment/edit/0?post_id={$post->pkey}'><i class='fa fa-comment text-primary'></i></a>
            </div>
        </div>
        <article class="lead body">
            {$post->post_body}
        </article>
    </main>
    <form action='/blog/blogcomment/save' method='POST'>
		<input type='hidden' name='pkey' value='0'>
        <input type='hidden' name='post_id' value='{$post->pkey}'>
        <input type='hidden' name='user_id' value='{$session_user->user_id}'>
        <label class='form-label' for='comment' style='margin-left: 0px;'>Comment</label>
        <textarea id='comment' name='comment' class='form-control'></textarea>
        <div class='text-end'>
            <button type='submit' class='btn btn-outline btn-light'>Send</button>
        </div>
    </form>
</div>
POST;

foreach ($post->comments as $comment)
{
    $display_date = date("M j, Y", strtotime($comment->created_at));
    $age = CDModel::human_time_diff(strtotime($comment->created_at), $now);

    echo <<<COMMENT
<div role='main' class='container'>
    <div class='blog-comment'>
        <div class'by-line'>
            <div class='d-inline author-name'>
                {$comment->author_name}
            </div>
            <i class='fa fa-circle dot mx-1'></i>
            <div class='d-inline author-name'>
                <i class='fa fa-evelope'></i>
                {$comment->author_email}
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
        <div class='blog-comment-block'>$comment->comment</div>
    </div>
</div>
COMMENT;
}
?>
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