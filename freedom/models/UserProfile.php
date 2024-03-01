<?php
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

    public $profile_image;  #` longblob,
    public $image_content_type;    #` varchar(128) DEFAULT NULL,
    public $image_size;     #` varchar(45) DEFAULT NULL,
    public $bio_conf;       #` text,
    public $about_conf;     #` text,
    public $info_conf;      #` text,
    public $createdAt;      #` datetime DEFAULT NULL,
    public $updatedAt;      #` datetime DEFAULT NULL,

    public $links;

    /**
     * Create a new instance
     * @param integer
     */
    public function __construct($user_id = null)
    {
        $this->pkey = $user_id;
        $this->dbh = DBSettings::DBConnection();

        if ($this->pkey)
            $this->Load();
    }

    public function Load()
    {
        parent::Load();
        $filter = [
            0 => [
                'field' => 'user_id',
                'type' => 'int',
                'op' => 'eq',
                'match' => $this->pkey
            ]
        ];
        $this->links = ProfileLink::GetAll("profile_link", $filter);
    }
    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('pkey', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('profile_image', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('image_content_type', PDO::PARAM_STR, true, 128);
        $this->field_array[$i++] = new DBField('image_size', PDO::PARAM_STR, true, 45);
        $this->field_array[$i++] = new DBField('bio_conf', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('about_conf', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('info_conf', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('createdAt', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('updatedAt', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('company_id', PDO::PARAM_STR, true, 0);
    }
}
