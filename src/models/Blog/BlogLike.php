<?php
namespace Freedom\Models\Blog;

use PDO;
use Freedom\Models\CDModel;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `blog_like` (
  `pkey` int unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int unsigned NOT NULL
    REFERENCES blog_post (pkey)
    ON UPDATE CASCADE ON DELETE CASCADE,
  `created_by` int unsigned
    REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
 */

class BlogLike extends CDModel {
    public $pkey;
    public $key_name = "pkey";
    protected $db_table = "blog_like";   # string
    public $post_id;            #` int unsigned NOT NULL,
    public $created_by;         # int unsigned NOT NULL,
    public $created_at;         #` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $updated_at;         #` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,


    public function __construct($id = null)
    {
        parent::__construct($id);
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
            $this->AddMsg("DELETED BlogLike ({$this->pkey})");
        }
    }

    public function Save()
    {
        $user_id = 1;
         
        if ($this->container)
        {
            $usr = $this->container->get("session")->user;
            $user_id = $usr->pkey;
        }

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
        $this->field_array[$i++] = new DBField('post_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
    }
}