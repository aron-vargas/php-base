<?php
namespace Freedom\Models\Blog;

use PDO;
use Freedom\Models\CDModel;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `blog_image` (
  `pkey` int unsigned NOT NULL AUTO_INCREMENT,
  `uploaded_images` text COLLATE utf8mb4_unicode_ci,
  `image_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `user_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
 */
class BlogImage extends CDModel {
    public $pkey;
    public $key_name = "id";
    protected $db_table = "blog_image";   # string
    public $uploaded_images;    #` text COLLATE utf8mb4_unicode_ci,
    public $image_title;        #` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    public $source = "Unknown"; #` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
    public $user_id;            #` int unsigned DEFAULT NULL,
    public $created_at;         #` timestamp NULL DEFAULT NULL,
    public $updated_at;         #` timestamp NULL DEFAULT NULL,

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

    /**
     * "Delete" the record
     */
    public function Delete()
    {
        $dbh = $this->dbh;

        if ($this->pkey)
        {
            $dbh->exec("DELETE FROM {$this->db_table} WHERE {$this->key_name} = {$this->pkey}");
            $this->AddMsg("DELETED BlogImage ({$this->pkey})");
        }
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('user_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('uploaded_images', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('image_title', PDO::PARAM_STR, true, 255);
        $this->field_array[$i++] = new DBField('source', PDO::PARAM_STR, false, 255);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
    }
}