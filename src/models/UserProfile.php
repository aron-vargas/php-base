<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `user_profile` (
  `pkey` int NOT NULL,
  `company_id` int NOT NULL,
  `profile_image` longblob,
  `image_content_type` varchar(128) DEFAULT NULL,
  `image_size` varchar(45) DEFAULT NULL,
  `bio_conf` text,
  `about_conf` text,
  `info_conf` text,
  `createdAt` datetime DEFAULT NULL,
  `updatedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`pkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */

class UserProfile extends CDModel {
    public $pkey;
    public $key_name = "pkey";   # string
    protected $db_table = "user_profile";   # string

    public $company_id;     # integer
    public $profile_image;  #` longblob,
    public $image_content_type;    #` varchar(128) DEFAULT NULL,
    public $image_size;     #` varchar(45) DEFAULT NULL,
    public $bio_conf;       #` text,
    public $about_conf;     #` text,
    public $info_conf;      #` text,
    public $createdAt;      #` datetime DEFAULT NULL,
    public $updatedAt;      #` datetime DEFAULT NULL,

    public $theme = "elegant";

    public $links;

    static public $MEMBER_GROUP_ID = 10;

    /**
     * Create a new instance
     * @param integer
     */
    public function __construct($user_id = null)
    {
        $this->pkey = $user_id;
        $this->dbh = DBSettings::DBConnection();
        $this->SetFieldArray();

        if ($this->pkey)
            $this->Load();
    }

    /**
     * Set the field values in the PDO Statement
     * @param \PDOStatement
     */
    public function BindValues(&$sth)
    {
        $i = 1;

        foreach ($this->field_array as $index => $field)
        {
            $i = $index + 1;
            if ($field->name == "bio_conf" || $field->name == "about_conf" || $field->name == "info_conf")
                $val = $this->Val($field, false);
            else
                $val = $this->Val($field);
            $sth->bindValue($i, $val, $field->Type($val));
        }
        return $i;
    }

    static public function GetMembers()
    {
        $MemberRole = UserProfile::$MEMBER_GROUP_ID;
        $dbh = DBSettings::DBConnection();
        $members = array();

        if ($dbh)
        {
            $sth = $dbh->query("SELECT
                p.pkey
            FROM user_profile p
            INNER JOIN user_role_join j on p.pkey = j.user_id AND j.role_id = $MemberRole");
            //$sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_OBJ))
            {
                $members[] = new UserProfile($row->pkey);
            }
        }

        return $members;
    }

    /**
     * Perform database insert
     * @return mixed
     */
    public function db_insert()
    {
        $dbh = $this->dbh;

        $fields = "";
        $holders = "";
        foreach ($this->field_array as $field)
        {
            $fields .= " {$field->Name()},";
            $holders .= " ?,";
        }

        # remove trailing ','
        $fields = substr($fields, 0, -1);
        $holders = substr($holders, 0, -1);

        $sth = $dbh->prepare("INSERT INTO {$this->db_table} ({$fields}) VALUES ($holders)");
        $this->BindValues($sth);
        $sth->execute();

        return $this->pkey;
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
            $this->AddMsg("DELETED ({$this->pkey})");
        }
    }

    public function Img($name)
    {
        $src = "/images/base_blue.png";

        if ($name == 'background')
        {
            $src = "/images/stock-dark-blue-background.jpg";
        }
        else if ($name == 'headshot' && $this->image_size)
        {
            $src = "data:image/{$this->image_content_type};base64,{$this->profile_image}";
        }

        return $src;
    }

    public function Save()
    {
        $this->Delete();

        if (empty($this->createdAt))
        {
            $this->createdAt = date("c");
            $this->AddMsg("Set createdAt ({$this->createdAt})");
        }

        $this->updatedAt = date("c");
        $this->AddMsg("Set updatedAt ({$this->updatedAt})");

        $this->db_insert();
        $this->AddMsg("Inserted ({$this->pkey})");
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('pkey', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('company_id', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('profile_image', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('image_content_type', PDO::PARAM_STR, true, 128);
        $this->field_array[$i++] = new DBField('image_size', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('bio_conf', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('about_conf', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('info_conf', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('createdAt', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('updatedAt', PDO::PARAM_STR, true, 0);
    }
}
