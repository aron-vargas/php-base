<?php

namespace Freedom\Traits;


trait Breadcrumb
{
    protected $seperator = " "; #"<span class='bc-sep'>&gt;</span>";
    protected $trail  = array();

    public function Crumb($url = null, $text = "--missing--", $active = false)
    {
        $this->trail[] = (object)["href" => $url, "text" => $text, "active" => $active];
    }

    public function SetSeperator($new)
    {
        $this->seperator = $new;
    }

    public function render_trail()
    {
        $tags = array();

        if (!empty($this->trail))
        {
            foreach($this->trail as $tag)
            {
                $active = ($tag->active) ? "active" : "";
                if ($tag->href)
                    $tags[] = "<a class='crumb {$active}' href='{$tag->href}'>{$tag->text}</a>";
                else
                    $tags[] = "<span class='crumb {$active}'>{$tag->text}</span>";
            }
        }

        $trail_tags = implode($this->seperator, $tags);

        echo "<div class='breadcrumbs'>$trail_tags</div>";
    }
}