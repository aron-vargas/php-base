<?php
namespace Freedom\Models\Blog;

use PDO;
use Freedom\Models\CDModel;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `blog_comment` (
  `pkey` int unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int unsigned NOT NULL
    REFERENCES blog_post (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  `user_id` int unsigned
    REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  `ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Anonymous',
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `author_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pkey`),
  KEY `blog_comment_post_id_index` (`post_id`),
  KEY `blog_comment_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
 */

class BlogComment extends CDModel {
    public $pkey;
    public $key_name = "pkey";
    protected $db_table = "blog_comment";   # string
    public $post_id;            #` int unsigned NOT NULL,
    public $user_id;            #` int unsigned DEFAULT NULL,
    public $ip;                 #` varchar(255),
    public $author_name = 'Anonymous'; #` varchar(255) DEFAULT 'Anonymous',
    public $comment;            #` text COLLATE utf8mb4_unicode_ci DEFAULT '',
    public $approved = 0;       #`approved` tinyint(1) NOT NULL DEFAULT '0',
    public $author_email;       #` varchar(255) COLLATE utf8mb4_unicode_ci,
    public $author_website;     #` varchar(255) COLLATE utf8mb4_unicode_ci,
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
        $user_id = 1;
        $author_name = "Anonymous";
        $email = null;
        if ($this->container)
        {
            $usr = $this->container->get("session")->user;
            $user_id = $usr->pkey;
            $author_name = "{$usr->first_name} {$usr->last_name}";
            $email = $usr->email;
        }

        if (empty($this->created_at))
            $this->created_at = date("c");
        if (empty($this->user_id))
            $this->user_id = $user_id;
        if (empty($this->author_name))
            $this->author_name = $author_name;
        if (empty($this->author_email))
            $this->author_email = $email;

        $this->updated_at = date("c");

        if ($this->pkey)
            $this->db_update();
        else
            $this->db_insert();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('post_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('user_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('ip', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('comment', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('approved', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('author_name', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('author_email', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('author_website', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
    }
}