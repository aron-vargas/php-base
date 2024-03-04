<?php

namespace Freedom\Modeles\Blog;

use PDO;
use Freedom\Models\CDModel;

class Category extends CDModel
{
    public $db_table = "blog_category";

    public function __construct($pkey = 0)
    {
        parent::__construct($pkey);
    }
}

trait CategotyTrait
{
    public function HasCategory($name)
    {
        foreach($this->categories as $cat)
        {
            if ($cat->name == $name)
                return true;
        }

        return false;
    }
    public function LoadBlogPostCategories($post_id)
    {
        $sth = $this->dbh->prepare("SELECT
            j.category_id
        FROM blog_post_categories j
        WHERE j.post_id = ?");
        while($cat = $sth->fetch(PDO::FETCH_OBJ))
        {
            $this->categories[] = new Category($cat->category_id);
        }
    }
}