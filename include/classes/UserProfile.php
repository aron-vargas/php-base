<?php

class UserProfile extends BaseClass
{
    public $pkey;
    public $key_name = "user_id";   # string
	protected $db_table = "user_profile";   # string

    public function __construct($user_id)
    {
        $this->pkey = $user_id;

        if ($this->pkey)
            $this->Load();
    }
}
