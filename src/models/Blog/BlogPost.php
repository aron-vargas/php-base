<?php
namespace Freedom\Models\Blog;

use PDO;
use Freedom\Models\CDModel;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `blog_post` (
  `pkey` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned
    REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  `slug` varchar(255),
  `title` varchar(255) DEFAULT 'New blog post',
  `subtitle` varchar(255) DEFAULT '',
  `seo_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `short_description` text COLLATE utf8mb4_unicode_ci,
  `post_body` mediumtext COLLATE utf8mb4_unicode_ci,
  `image_large` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_medium` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `blog_post_categories` (
  `pkey` int unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int unsigned NOT NULL,
  `category_id` int unsigned NOT NULL,
  PRIMARY KEY (`pkey`),
  KEY `blog_post_categories_post_id_index` (`post_id`),
  KEY `blog_post_categories_category_id_index` (`category_id`),
  CONSTRAINT `blog_post_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`pkey`) ON DELETE CASCADE,
  CONSTRAINT `blog_post_categories_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `blog_post` (`pkey`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
 */

class BlogPost extends CDModel {
    use hasCategories;

    public $pkey;
    public $key_name = "pkey";
    protected $db_table = "blog_post";   # string
    public $user_id;            #` int unsigned DEFAULT NULL,
    public $slug;               #` varchar(255),
    public $title = 'New blog post'; #` varchar(255) DEFAULT 'New blog post',
    public $subtitle;           #` varchar(255) DEFAULT '',
    public $seo_title;          #` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    public $meta_description;   #` text COLLATE utf8mb4_unicode_ci DEFAULT '',
    public $short_description;  #` text COLLATE utf8mb4_unicode_ci,
    public $post_body;          #` mediumtext COLLATE utf8mb4_unicode_ci,
    public $image_large;        #` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    public $image_medium;       #` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    public $image_thumbnail;    #` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    public $is_published = 0;       #` tinyint(1) NOT NULL DEFAULT '0',
    public $created_at;         #` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $updated_at;         #` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,


    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->dbh = DBSettings::DBConnection();
        $this->SetFieldArray();
        $this->Load();
    }

    /**
     * "Delete" the record
     */
    public function Delete()
    {
        $dbh = $this->dbh;

        if ($this->pkey)
        {
            $dbh->exec("DELETE FROM {$this->db_table} WHERE {$this->key_name} = {$this->pkey}");
            $this->AddMsg("DELETED BlogPost ({$this->pkey})");
        }
    }

    public function Save()
    {
        $user_id = ($this->container) ? $this->container->get("session")->user->pkey : 1;

        if (empty($this->created_at))
            $this->created_at = date("c");
        if (empty($this->user_id))
            $this->user_id = $user_id;

        $this->updated_at = date("c");

        if ($this->pkey)
            $this->db_update();
        else
            $this->db_insert();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('user_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('slug', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('title', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('subtitle', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('seo_title', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('meta_description', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('short_description', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('post_body', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('image_large', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('image_medium', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('image_thumbnail', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('is_published', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
    }
}

trait hasCategories {
    public $categories;

    public function AddCategory(BlogCategory $category)
    {
        if ($this->hasCategory($category) == false)
        {
            $dbh = DBSettings::DBConnection();
            $sth = $dbh->query("INSERT INTO blog_post_category (post_id, category_id) VALUES (?,?)");
            $sth->bindValue(1, $this->pkey, PDO::PARAM_INT);
            $sth->bindValue(2, $category->pkey, PDO::PARAM_INT);
            $this->catgories[$category->pkey] = $category;
        }
    }
    public function RMCategory(BlogCategory $category)
    {
        $dbh = DBSettings::DBConnection();
        $sth = $dbh->query("DELETE FROM blog_post_category WHERE post_id = ? AND category_id = ?");
        $sth->bindValue(1, $this->pkey, PDO::PARAM_INT);
        $sth->bindValue(2, $category->pkey, PDO::PARAM_INT);
        $sth->execute();
        unset($this->categories[$category->pkey]);
    }

    public function AllCategories()
    {
        $dbh = DBSettings::DBConnection();
        $sth = $dbh->query("SELECT * FROM blog_category");
        $all = $sth->fetchAll(PDO::FETCH_OBJ);
        return $all;
    }

    public function getCategory(BlogCategory $match)
    {
        foreach ($this->categories as $pkey => $cat)
        {
            if ($cat->pkey == $match->pkey)
                return $cat;
        }

        return false;
    }

    public function hasCategory(BlogCategory $match)
    {
        foreach ($this->categories as $pkey => $cat)
        {
            if ($cat->pkey == $match->pkey)
                return true;
        }

        return false;
    }
}
