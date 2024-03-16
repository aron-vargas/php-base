<?php
$comment = $this->model;
if (empty($comment->user_id))
{
    $user = $this->config->get("session")->user;
    $comment->user_id = $user->pkey;
    $comment->author_email = $user->email;
}

if (isset ($_SERVER['HTTP_REFERER']))
    $this->Crumb($_SERVER['HTTP_REFERER'], " <i class='fa fa-angle-left'></i>Back");
$this->Crumb("/home", "Home");
$this->Crumb("/blog/blogpost/show", "All Posts");
$this->Crumb("/blog/blogpost/view/{$comment->post_id}", "Post");
$this->Crumb(null, "Edit", true);

echo "
<link rel='stylesheet' type='text/css' href='//{$this->config->get('base_url')}/style/blog.css' media='all'>
{$this->render_trail()}
<div role='main' class='container'>
<main class='form-signin w-100 m-auto'>
<div class='card-body p-md-5 mx-md-4'>
	<form action='/blog/blogcomment/save' method='POST'>
		<input type='hidden' name='pkey' value='{$comment->pkey}'>
        <input type='hidden' name='post_id' value='{$comment->post_id}'>
        <input type='hidden' name='user_id' value='{$comment->user_id}'>
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
			<button type='submit' class='btn btn-primary' type='button' name='act' value='1' onClick='SubmitFrom(this)'>Submit</button>
            <button type='submit' class='btn btn-primary' type='button' name='act' value='-1' onClick='SubmitFrom(this)'>Delete</button>
		</div>
	</form>
</div>
</main>
</div>";