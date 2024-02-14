<?php

class UserProfile extends CDModel
{
    public $pkey;
    public $key_name = "user_id";   # string
	protected $db_table = "user_profile";   # string

    /**
     * Create a new instance
     * @param integer
     */
    public function __construct($user_id)
    {
        $this->pkey = $user_id;

        if ($this->pkey)
            $this->Load();
    }
}
