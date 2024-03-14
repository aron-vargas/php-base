<?php
$blog = $this->model;

if (isset ($_SERVER['HTTP_REFERER']))
    $this->Crumb($_SERVER['HTTP_REFERER'], " <i class='fa fa-angle-left'></i>Back");
$this->Crumb("/home", "Home");
$this->Crumb("/blog/blogpost/show", "All Posts");
$this->Crumb(null, "Edit", true);

/*
TODO: Creat Preview containers and sync with editor real-time
*/
echo "
<link rel='stylesheet' type='text/css' href='//{$this->config->get('base_url')}/style/blog.css' media='all'>
{$this->render_trail()}
<div role='main' class='container'>
    <div class='card-body p-md-4 mx-md-4'>
        <form action='/blog/blogpost/save' method='POST' enctype='multipart/form-data'>
            <input type='hidden' name='pkey' value='{$blog->pkey}'/>
            <input type='hidden' id='hidden_body' name='hidden_body'/>
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
                <label class='form-label' for='image_large' style='margin-left: 0px;'>Background Image</label>
                <div class='mb-4 input-group'>
                    <input type='file' id='image_large' name='image_large' class='form-control' placeholder='Add or Replace the existing Image' />
                    <span class='input-group-text'><a class='disabled' onClick=\"ShowImagePreview('{$blog->image_large}')\"><i class='fa fa-eye'></i></a></span>
                </div>
            </div>
            <div class='mb-4'>
                <label class='form-label' for='image_medium' style='margin-left: 0px;'>User Image</label>
                <div class='mb-4 input-group'>
                    <input type='file' id='image_medium' name='image_medium' class='form-control' placeholder='Add or Replace the existing Image' />
                    <span class='input-group-text'><a class='disabled' onClick=\"ShowImagePreview('{$blog->image_medium}')\"><i class='fa fa-eye'></i></a></span>
                </div>
            </div>
            <div class='mb-4'>
                <label class='form-label' for='image_thumbnail' style='margin-left: 0px;'>Thumbnail Image</label>
                <div class='mb-4 input-group'>
                    <input type='file' id='image_thumbnail' name='image_thumbnail' class='form-control' placeholder='Add or Replace the existing Image' />
                    <span class='input-group-text'><a class='disabled' onClick=\"ShowImagePreview('{$blog->image_thumbnail}')\"><i class='fa fa-eye'></i></a></span>
                </div>
            </div>
            <div class='mb-4'>
                <label class='form-label' for='short_description' style='margin-left: 0px;'>Short Description</label>
                <textarea id='short_description' name='short_description' class='editor form-control'>{$blog->short_description}</textarea>
            </div>
            <div class='mb-4'>
                <label class='form-label' for='editor_body' style='margin-left: 0px;'>Main Body</label>
                <textarea id='post_body' name='post_body' class='editor form-control'>{$blog->post_body}</textarea>
            </div>
            <div class='text-center pt-1 mb-4 pb-1'>
                <button type='submit' class='btn btn-primary' type='button' name='act' value='1' onClick='ClickSubmit(this)'>Submit</button>
                <button type='submit' class='btn btn-primary' type='button' name='act' value='-1' onClick='ClickSubmit(this)'>Delete</button>
            </div>
        </form>
    </div>

</div>";
?>
<script type='text/javascript'>
    // Editor configuration.
    // TODO: find module creating this "Uncaught CKEditorError: ckeditor-duplicated-modules" and remove it
    var e_config =
    {
        removePlugins: ['Markdown', "MediaEmbedToolbar"],

        toolbar: { removeItems: ['imageUpload'] }
    };

    ClassicEditor
        .create(document.querySelector('#post_body'), e_config)
        .catch(cheditorError);

    ClassicEditor
        .create(document.querySelector('#short_description'), e_config)
        .catch(cheditorError);

    function cheditorError(error)
    {
        const issueUrl = 'https://github.com/ckeditor/ckeditor5/issues';

        const message = [
            'Oops, something went wrong!',
            `Please, report the following error on ${issueUrl} with the build id "6lrrspxc6m2x-w3wn11v6wpsy" and the error stack trace:`
        ].join('\n');

        console.error(message);
        console.error(error);
    }

    function ClickSubmit(elem)
    {
        // Disable the button/input by first
        $(elem).prop("disabled", true);
        // Submit the form
        $(elem).closest("form").submit();
    };

    function ShowImagePreview(img_url)
    {
        
    }
</script>