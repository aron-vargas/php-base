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
$image_large_src = ($blog->image_large) ? $blog->image_large : "/images/blog_bg.png";
$image_medium_src = ($blog->image_medium) ? $blog->image_medium : "/images/base_blue.png";
$image_thumbnail_src = ($blog->image_thumbnail) ? $blog->image_thumbnail : "/images/base_blue.png";


//echo "<pre>";
//print_r($blog);
//echo "</pre>";

echo "
<link rel='stylesheet' type='text/css' href='//{$this->config->get('base_url')}/style/blog.css' media='all'>
{$this->render_trail()}
<div role='main' class='container'>
    <div class='card-body p-md-4 mx-md-4'>
        <form action='/blog/blogpost/save' method='POST' enctype='multipart/form-data'>
            <input type='hidden' name='pkey' value='{$blog->pkey}'/>
            <input type='hidden' id='hidden_body' name='hidden_body'/>
            <h4>Post:</h4>
            <button class='blog-bg' type='button' onClick=\"OpenImageUpload('image_large');\">
                <img src='{$image_large_src}' class='border' />
            </button>
            <div class='blog-bg'>
                <span>
                    <button class='border' type='button' onClick=\"OpenImageUpload('image_medium');\">
                        <img class='avatar rounded-circle' src='{$image_medium_src}' />
                    </button>
                    <span>Avatar</span>
                </span>
                <span class='ms-3'>
                    <button class='border' type='button' onClick=\"OpenImageUpload('image_thumbnail');\">
                        <img class='avatar rounded-circle' src='{$image_thumbnail_src}' class='border' />
                    </button>
                    <span>Thumbnail</span>
                </span>
            </div>
            <div class='mb-4'>
                <label class='form-label' for='title' style='margin-left: 0px;'>Title</label>
                <input type='text' id='title' name='title' class='form-control' placeholder='Title' value='{$blog->title}'>
            </div>
            <div class='mb-4'>
                <label class='form-label' for='subtitle' style='margin-left: 0px;'>Sub Title</label>
                <input type='text' id='subtitle' name='subtitle' class='form-control' placeholder='Title' value='{$blog->subtitle}'>
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
                <button type='button' class='btn btn-secondary btn-sm float-start' type='button' onClick=\"$('#meta_info').toggleClass('hidden')\">show meta</button>
                <button type='submit' class='btn btn-primary' type='button' name='act' value='1' onClick='ClickSubmit(this)'>Submit</button>
                <button type='submit' class='btn btn-primary' type='button' name='act' value='-1' onClick='ClickSubmit(this)'>Delete</button>
            </div>
            <div id='meta_info' class='hidden'>
                 <div class='mb-4'>
                    <label class='form-label' for='seo_title' style='margin-left: 0px;'>SEO Title</label>
                    <input type='text' id='seo_title' name='seo_title' class='form-control' placeholder='Title' value='{$blog->seo_title}'>
                </div>
                <div class='mb-4'>
                    <label class='form-label' for='meta_description' style='margin-left: 0px;'>Meta Description</label>
                    <textarea id='meta_description' name='meta_description' class='form-control'>{$blog->meta_description}</textarea>
                </div>
            </div>
        </form>
    </div>
</div>";

$MODAL_TITLE = "Update Image";
$MODAL_CLASS = "modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable";
$MODAL_BODY = <<<BODY
    <form id='image-form' action='/blog/blogpost/save-img' method='POST' enctype='multipart/form-data'>
        <input type='hidden' id='image-form-pkey' name='pkey' value='{$blog->pkey}'/>
        <input type='hidden' id='blog_image' name='blog_image' value='image_medium' />
        <input class='hidden' id='image_file' name='image_file' type='file' />
        <div class="tab-content" id="image-form-content">
            <div id="image-cropper" class='image-cropper'>
                <div class='upload_help'>Click or Drag an Image to Upload</div>
            </div>
        </div>
    </form>
BODY;

$MODAL_FOOTER = "
    <button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">Cancel</button>
    <button type=\"button\" class=\"btn btn-primary\" onClick='SubmitImageForm();'>Save changes</button>";

echo include ('src/templates/bs-modal-template.php');

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
    /*
        document.getElementById('image-getter').onclick = function ()
        {
            var img_crop = document.getElementById('image-cropper');
            var img_res = document.getElementById('image-cropper-result');
            img_res.children[0].src = img_crop.crop.getCroppedImage().src;
        }
    */
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

    function OpenImageUpload(img_input)
    {
        $('#blog_image').val(img_input);
        //document.getElementById('image-cropper').crop.reset();
        $('#BS-MODAL').modal('show');

        if (img_input == 'image_large')
        {
            cropper(document.getElementById('image-cropper'),
                {
                    area: [600, 400],
                    crop: [300, 300],
                })
        }
        else
        {
            cropper(document.getElementById('image-cropper'),
                {
                    area: [180, 180],
                    crop: [60, 60],
                })
        }
    }

    function SubmitImageForm()
    {
        $(this).prop("disabled", true);

        // Transfer the Cropped Image Blob to the file input 'image_file'
        var img_elem = document.getElementById('image-cropper');
        const crop = img_elem.crop;
        const imgImage = crop.getCroppedImage();

        // getCroppedAsBlob uses a callback
        crop.getCroppedAsBlob(function (imgBlob)
        {
            var image_name = $('#blog_image').val() + "_" + $('#image-form-pkey').val() + ".png";

            // Create a new File object
            const ImgFile = new File([imgBlob], image_name, {
                type: 'image/png',
                lastModified: new Date(),
            });

            // Copy the contents over to the hidden file input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(ImgFile);
            document.getElementById('image_file').files = dataTransfer.files;

            // Submit the form
            $('#image-form').submit();
        });
    }
</script>