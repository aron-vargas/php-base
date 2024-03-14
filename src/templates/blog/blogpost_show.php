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

        $edit_btn = "";
        if ($row->user_id == $session_user->user_id)
        {
            $edit_btn = "<a class='btn btn-small btn-primary' href='/blog/blogpost/edit?pkey={$row->pkey}'><i class='fa fa-pencil'></i></a>";
        }

        $comment_count = count($row->comments);
        $posts .= "
        <div class='col m-2'>
            <div class='card col {$publised}'>
                <div class='hidden seo'>{$row->seo_title}</div>
                <div class='hidden meta'>{$row->meta_description}</div>
                <img src='/images/Untitled design.png' class='card-img-top' height='200'/>
                <div class='card-body'>
                    <div class='badges text-end'>
                        <span class='badge badge-info bg-info'>$publised</span>
                        $edit_btn
                    </div>
                    <h5 class='card-title'>{$row->subtitle}</h5>
                    <div class'by-line'>
                        <div class='rounded-circle avatar base_blue'>&nbsp;</div>
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
                        <div class='col'>0 <i class='fa fa-eye'></i></div>
                        <div class='col'>
                            {$comment_count}
                            <a href='/blog/blogcomment/edit?pkey=0&post_id={$row->pkey}'>
                                <i class='fa fa-comment text-primary'></i>
                            </a>
                        </div>
                        <div class='col'>
                            0
                            <a href='/blog/blogpost/show?like={$row->pkey}'>
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
<link rel='stylesheet' type='text/css' href='//<?php echo $config->get('base_url'); ?>/style/blog.css' media='all'>
<div role='main' class='container'>
    <div class='mt-4 text-start'>
        <a class='btn btn-primary' href="/edit/blog-blogpost/blog?pkey=0" alt="Add New Post"
            title="Add New Post">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Recent Posts</h3>
    </div>
    <div class='blog row'>
        <?php echo $posts; ?>
    </div>
</div>