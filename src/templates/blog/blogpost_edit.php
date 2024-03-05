<?php
$blog = $this->model;
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
	<form action='/blog/blogpost' method='POST'>
		<input type='hidden' name='pkey' value='{$blog->pkey}'>
		<h4>Post:</h4>
		<div class='mb-4'>
			<label class='form-label' for='title' style='margin-left: 0px;'>Title</label>
			<input type='text' id='title' name='title' class='form-control' placeholder='Title' value='{$blog->title}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='subtitle' style='margin-left: 0px;'>Sub Title</label>
			<input type='text' id='subtitle' name='subtitle' class='form-control' placeholder='Title' value='{$blog->subtitle}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='seo_title' style='margin-left: 0px;'>SEO Title</label>
			<input type='text' id='seo_title' name='seo_title' class='form-control' placeholder='Title' value='{$blog->seo_title}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='meta_description' style='margin-left: 0px;'>Meta Description</label>
			<textarea id='meta_description' name='meta_description' class='form-control'>{$blog->meta_description}</textarea>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='short_description' style='margin-left: 0px;'>Short Description</label>
			<textarea id='short_description' name='short_description' class='form-control'>{$blog->short_description}</textarea>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='post_body' style='margin-left: 0px;'>Main Body</label>
			<textarea id='post_body' name='post_body' class='form-control'>{$blog->post_body}</textarea>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' type='button' name='act' value='1' onClick='SubmitFrom(this)'>Submit</button>
            <button type='submit' class='btn btn-primary' type='button' name='act' value='-1' onClick='SubmitFrom(this)'>Delete</button>
		</div>
	</form>
</div>
</main>
</div>";