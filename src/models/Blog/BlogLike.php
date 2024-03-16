<?php
namespace Freedom\Models\Blog;

use PDO;
use Freedom\Models\CDModel;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `blog_like` (
  `post_id` int unsigned NOT NULL
    REFERENCES blog_post(pkey)
    ON UPDATE CASCADE ON DELETE CASCADE,
  `created_by` int unsigned NOT NULL
    REFERENCES user(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`,`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
 */

class BlogLike extends CDModel
{

    public $pkey;
    public $key_name = "post_id";
    protected $db_table = "blog_like";

    public $post_id;            #` int unsigned NOT NULL,
    public $created_by;         # int unsigned NOT NULL,
    public $created_at;         #` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

    public function __construct($post_id = null, $created_by = null)
    {
        $this->post_id = $post_id;
        $this->created_by = $created_by;
        $this->created_at = date("c");
        $this->dbh = DBSettings::DBConnection();
        $this->SetFieldArray();
    }

    /**
     * Set the field values in the PDO Statement
     * @param \PDOStatement
     */
    public function BindValues(&$sth)
    {
        $i = 1;

        $sth->bindValue($i++, $this->post_id, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->created_by, PDO::PARAM_INT);

        return $i;
    }

    public function Connect($containter)
    {
        $this->container = $containter;
    }

    /**
     * "Delete" the record
     */
    public function Delete()
    {
        $dbh = $this->dbh;

        if ($this->pkey)
        {
            $dbh->exec("DELETE FROM blog_like WHERE post_id = {$this->post_id} AND created_by = {$this->created_by}");
            $this->AddMsg("DELETED BlogLike ({$this->post_id}, {$this->created_by})");
        }
    }

    public function JSON()
    {
        $obj = new \StdClass();
        $obj->post_id = $this->post_id;
        $obj->created_by = $this->created_by;
        $obj->created_at = $this->created_at;

        return json_encode($obj);
    }

    /**
     * Set attribute values from DB record
     */
    public function Load()
    {
        if ($this->post_id && $this->created_by)
        {
            $sth = $this->dbh->prepare("SELECT * FROM blog_like WHERE post_id = ? AND created_by = ?");
            $sth->bindValue(1, $this->post_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->created_by, PDO::PARAM_INT);
            $sth->execute();
            $rec = $sth->fetch(PDO::FETCH_ASSOC);
            $this->Copy($rec);
        }
    }


    public function Save()
    {
        $this->created_by = 1;
        $this->created_at = date("c");
        
        if ($this->container)
        {
            $usr = $this->container->get("session")->user;
            $this->created_by = $usr->pkey;
        }

        $dbh = $this->dbh;

        $sth = $dbh->prepare("REPLACE INTO blog_like (post_id, created_by, created_at) VALUES (?,?,CURRENT_TIMESTAMP)");
        $sth->bindValue(1, (int)$this->post_id, PDO::PARAM_INT);
        $sth->bindValue(2, (int)$this->created_by, PDO::PARAM_INT);
        $sth->execute();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('post_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('created_by', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
    }
}