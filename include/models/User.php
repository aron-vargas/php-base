<?php

/**
 *
CREATE TABLE `user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `nick_name` varchar(255) DEFAULT 'Sucker',
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `user_type` varchar(255) NOT NULL DEFAULT 'SYSTEM',
  `status` varchar(255) NOT NULL DEFAULT 'NEW',
  `permissions` int DEFAULT '0',
  `verification` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT '0',
  `login_attempts` int DEFAULT '0',
  `block_expires` datetime DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `last_mod` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE `user_session` (
  `session_id` varchar(64) NOT NULL,
  `user_id` int NOT NULL,
  `session_type` varchar(45) NOT NULL DEFAULT 'User',
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
 */

class User extends CDModel {
    public $pkey;                   # integer
    public $key_name = "user_id";   # string
    protected $db_table = "user";   # string
    public $user_id;                   # string
    public $user_type;                   # string
    public $user_name;              # string
    public $first_name;             # string
    public $last_name;              # string
    public $nickname;               # string
    public $email;                  # string
    public $phone;                  # string
    public $status;                 # string
    public $created_on;                 # int
    public $last_mod;                 # int
    public $permissions;                 # int
    public $avatar;                 # string

    protected $profile;             # UserProfile
    protected $bets;                # array

    public $edit_view = "include/templates/user_edit.php";
    public $display_view = "include/templates/user_list.php";

    static public $STATUS_ACTIVE = "ACTIVE";
    static public $STATUS_INACTIVE = "INACTIVE";
    static public $PERM_ADMIN = 1;

    static public $USER_NICKNAME = 1;
    static public $USER_FULLNAME = 0;

    /**
     * Create a new instance
     * @param integer
     * @param string
     */
    public function __construct($user_id = null, $type = 'SYSTEM')
    {
        $this->SetFieldArray();

        $this->pkey = $user_id;
        $this->user_id = $user_id;
        $this->user_type = $type;
        $this->profile = new UserProfile($user_id);
        $this->bets = array();
        $this->Load();
    }

    public function authenticate($user_name, $password)
    {
        $controller = $_SESSION['APPCONTROLLER'];
        $dbh = CDController::DBConnection();

        $valid = false;

        if ($dbh)
        {
            # First try username
            $sth = $dbh->prepare("SELECT user_id, password FROM user WHERE status != 'INACTIVE' AND LOWER(user_name) = ?");
            $sth->bindValue(1, strtolower(trim($user_name)), PDO::PARAM_STR);
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);

            # Then try email
            if (empty($data) && $user_name)
            {
                $sth = $dbh->prepare("SELECT user_id, password FROM user WHERE status != 'INACTIVE' AND LOWER(email) = ?");
                $sth->bindValue(1, strtolower(trim($user_name)), PDO::PARAM_STR);
                $sth->execute();
                $data = $sth->fetch(PDO::FETCH_OBJ);
            }

            if (!empty($data))
            {
                if (!empty($password) && password_verify($password, $data->password))
                {
                    $controller->user = new User($data->user_id);
                    $valid = true;
                }
            }
        }

        return $valid;
    }

    public function Copy($assoc)
    {
        if (is_array($assoc))
        {
            foreach ($assoc as $key => $val)
            {
                if ($key == 'db_table')
                    continue;
                else if ($key == 'password')
                    $this->password = password_hash($val, PASSWORD_BCRYPT);
                else if (@property_exists($this, $key))
                    $this->{$key} = $val;
            }
        }
    }

    public function Delete()
    {
        if ($this->pkey)
        {
            $this->change('status', self::$STATUS_INACTIVE);
        }
    }

    public function isAdmin()
    {
        return $this->permissions & self::$PERM_ADMIN;
    }

    static public function GetAllUsers($options = 0)
    {
        $dbh = CDController::DBConnection();

        if ($dbh)
        {
            if ($options == self::$USER_NICKNAME)
                $order_by = "ORDER BY nickname";
            else
                $order_by = "ORDER BY first_name, last_name";

            $sth = $dbh->query("SELECT * FROM user WHERE user_id > 1 $order_by");
            $list = $sth->fetchAll(PDO::FETCH_OBJ);
        }
        else
            $list = null;

        return $list;
    }

    static public function OptionList($selected = null, $options = 0)
    {
        $user_list = User::GetAllUsers($options);

        $option_list = "";

        foreach ($user_list as $usr)
        {
            if ($options == User::$USER_NICKNAME)
                $text = "{$usr->nick_name}";
            else
                $text = "{$usr->first_name} {$usr->last_name}";

            $sel = ($selected == $usr->user_id) ? " selected" : "";

            if ($usr->status == User::$STATUS_ACTIVE || $sel)
                $option_list .= "<option value='{$usr->user_id}'{$sel}>$text</option>";
        }

        return $option_list;
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('user_type', PDO::PARAM_STR, false, 32);
        $this->field_array[$i++] = new DBField('user_name', PDO::PARAM_STR, false, 32);
        $this->field_array[$i++] = new DBField('first_name', PDO::PARAM_STR, false, 64);
        $this->field_array[$i++] = new DBField('last_name', PDO::PARAM_STR, false, 64);
        $this->field_array[$i++] = new DBField('nick_name', PDO::PARAM_STR, false, 45);
        $this->field_array[$i++] = new DBField('email', PDO::PARAM_STR, true, 128);
        $this->field_array[$i++] = new DBField('phone', PDO::PARAM_STR, true, 32);
        $this->field_array[$i++] = new DBField('status', PDO::PARAM_STR, false, 32);
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('avatar', PDO::PARAM_STR, true, 45);
    }

    public function Save()
    {
        $this->last_mod = time();

        if ($this->pkey)
        {
            $this->db_update();
        }
    }

    public function Create()
    {
        $this->created_on = time();
        $this->last_mod = time();
        $this->status = self::$STATUS_ACTIVE;

        $this->field_array['p'] = new DBField('password', PDO::PARAM_STR, false, 128);
        $this->field_array['c'] = new DBField('created_on', PDO::PARAM_INT, false, 0);
        $this->db_insert();
        unset($this->field_array['p']);
        unset($this->field_array['c']);
    }

    public function Validate()
    {
        $controller = $_SESSION['APPCONTROLLER'];
        $dbh = CDController::DBConnection();

        $valid = true;

        if (empty($this->user_name))
        {
            $controller->view->message .= "<div>Missing Username</div>";
        }
        else if (empty($this->password))
        {
            $valid = false;
            $controller->view->message .= "<div>Missing Password</div>";
        }
        if (empty($this->first_name))
        {
            $valid = false;
            $controller->view->message .= "<div>Missing First Name</div>";
        }
        if (empty($this->nickname))
        {
            $this->nickname = $this->user_name;
        }

        if (empty($dbh))
        {
            $controller->view->message .= "<div>Cannot verfiy request at this time.</div>";
            return false;
        }

        if ($this->user_name)
        {
            $sth = $dbh->prepare("SELECT COUNT(*) FROM user WHERE user_name = ?");
            $sth->bindValue(1, $this->user_name, PDO::PARAM_STR);
            $sth->execute();
            $count = $sth->fetchColumn();

            if ($count)
            {
                $valid = false;
                $controller->view->message .= "<div>This username already exist.</div><div>Login <a href='login.php'>here</a> or select another username.</div>";
                $controller->view->Set('include/templates/register_form.php');
            }
        }

        return $valid;
    }
}
