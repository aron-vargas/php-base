<?php
$comment = $this->data;
if (empty($comment->user_id))
{
    $user = $this->config->get("session")->user;
    $comment->user_id = $user->pkey;
    $comment->author_email = $user->email;
}
/*
   public $title = 'New blog post'; #` varchar(255) DEFAULT 'New blog post',
    public $subtitle;           #` varchar(255) DEFAULT '',
    public $seo_title;          #` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    public $meta_description;   #` text COLLATE utf8mb4_unicode_ci DEFAULT '',
    public $short_description;  #` text COLLATE utf8mb4_unicode_ci,
    public $post_body;          #` mediumtext COLLATE utf8mb4_unicode_ci,

    public $is_published;       #` tinyint(1) NOT NULL DEFAULT '0',
    public $created_at;         #` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $updated_at;         #` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
*/
/*
$chk_published_y = $blog->is_published ? "checked" : "";
$chk_published_n = $blog->is_published ? "" : "checked";
*/
echo "
<style>
.form-signin
{
	max-width: 600px;
	padding: 15px;
}
.card-body
{
	border: 1px solid #CCC;
	border-radius: 8px;
}
</style>
<div role='main' class='container'>
<main class='form-signin w-100 m-auto'>
<div class='card-body p-md-5 mx-md-4'>
	<form action='/save/blog-blogcomment/blog' method='POST'>
		<input type='hidden' name='pkey' value='{$comment->pkey}'>
        <input type='hidden' name='post_id' value='{$comment->post_id}'>
        <input type='hidden' name='user_id' value='{$comment->user_id}'>
        <input type='hidden' name='pkey' value='{$comment->pkey}'>
		<h4>Comment:</h4>
		<div class='mb-4'>
			<label class='form-label' for='title' style='margin-left: 0px;'>Email</label>
			<input type='text' id='email' name='email' class='form-control' placeholder='Email Address' value='{$comment->author_email}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='website' style='margin-left: 0px;'>Website</label>
			<input type='text' id='website' name='website' class='form-control' placeholder='Your Website' value='{$comment->author_website}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='comment' style='margin-left: 0px;'>Comment</label>
			<textarea id='comment' name='comment' class='form-control'>{$comment->comment}</textarea>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' type='button' name='act' value='1'>Submit</button>
            <button type='submit' class='btn btn-primary' type='button' name='act' value='-1'>Delete</button>
		</div>
	</form>
</div>
</main>
</div>";