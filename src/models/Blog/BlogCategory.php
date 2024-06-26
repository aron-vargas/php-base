<?php
namespace Freedom\Models\Blog;

use PDO;
use Freedom\Models\CDModel;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `blog_categories` (
  `pkey` int unsigned NOT NULL AUTO_INCREMENT,
  `created_by` int unsigned
    REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  `category_name` varchar(255) NOT NULL COLLATE utf8mb4_unicode_ci,
  `active` tinyint NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
 */

class BlogCategory extends CDModel {
    public $pkey;
    public $key_name = "pkey";
    protected $db_table = "blog_comment";   # string

    public $active = 1;         # int default 1
    public $category_name;      #` varchar(255) NOT NULL COLLATE utf8mb4_unicode_ci,
    public $created_by;         # int unsigned NOT NULL,
    public $created_at;         #` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    public $updated_at;         #` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,


    /**
     * "Delete" the record
     */
    public function Delete()
    {
        $dbh = $this->dbh;

        if ($this->pkey)
        {
            $this->Change("active", 0);
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
        if (empty($this->created_by))
            $this->created_by = $user_id;

        $this->updated_at = date("c");

        if ($this->pkey)
            $this->db_update();
        else
            $this->db_insert();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('category_name', PDO::PARAM_STR, false, 255);
        $this->field_array[$i++] = new DBField('active', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, true, 0);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
    }
}
