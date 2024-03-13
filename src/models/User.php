<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;
use Freedom\Traits\RoleTrait;
use Freedom\Traits\PermissionTrait;
use Freedom\Models\UserProfile;

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
  `user_type` enum('USER','GROUP','TEMPLATE','SYSTEM') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USER',
  `status` varchar(255) NOT NULL DEFAULT 'NEW',
  `default_group` int NULL DEFAULT NULL,
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
    use RoleTrait, PermissionTrait;

    public $pkey;                   # integer
    public $key_name = "user_id";   # string
    protected $db_table = "user";   # string
    public $user_id;                # string
    public $user_type = 'USER';              # string
    public $user_name;              # string
    public $first_name;             # string
    public $last_name;              # string
    public $nick_name;               # string
    public $email;                  # string
    public $phone;                  # string
    public $status;                 # string
    public $default_group;          # int
    public $created_on;             # int
    public $last_mod;               # int

    public $avatar = "base_blue";

    protected $container;           # Container
    protected $roles;               # Role[]
    protected $permissions;         # Permission[]
    public $profile;                # UserProfile
    public $verified = 0;
    public $verification;
    public $login_attempts = 0;
    public $block_expires;


    public $edit_view = "include/templates/user_edit.php";
    public $display_view = "include/templates/user_list.php";

    static public $STATUS_ACTIVE = "ACTIVE";
    static public $STATUS_INACTIVE = "INACTIVE";
    static public $TYPE_USER = "USER";
    static public $TYPE_GROUP = "GROUP";
    static public $TYPE_TEMPLATE = "TEMPLATE";
    static public $TYPE_SYSTEM = "SYSTEM";
    static public $PERM_ADMIN = 1;

    static public $USER_NICK_NAME = 1;
    static public $USER_FULLNAME = 0;

    static public $SUPER_ADMIN_ROLE = "super-admin";
    static public $ADMIN_ROLE = "admin";

    /**
     * Create a new instance
     * @param integer
     * @param string
     */
    public function __construct($user_id = null, $type = 'USER')
    {
        $this->SetFieldArray();
        $this->pkey = $user_id;
        $this->user_id = $user_id;
        $this->user_type = $type;
        $this->dbh = DBSettings::DBConnection();
        $this->Load();
    }

    public function authenticate($user_name, $password)
    {
        $valid = false;
        $dbh = $this->dbh;

        if (empty($user_name))
        {
            $this->AddMsg("Username/Email is missing");
            return $valid;
        }

        if (empty($password))
        {
            $this->AddMsg("Password is missing");
            return $valid;
        }

        if ($dbh)
        {
            # First try username or email
            $sth = $this->dbh->prepare("SELECT user_id, password FROM user WHERE status != 'INACTIVE' AND (LOWER(user_name) = ? OR LOWER(email) = ?)");
            $sth->bindValue(1, strtolower(trim($user_name)), PDO::PARAM_STR);
            $sth->bindValue(2, strtolower(trim($user_name)), PDO::PARAM_STR);
            $sth->execute();
            $data = $sth->fetch(PDO::FETCH_OBJ);

            if (!empty($data))
            {
                if (!empty($password) && password_verify($password, $data->password))
                {
                    $session = $this->container->get('session');
                    $session->user = new User($data->user_id);
                    $session->Insert();
                    $session->auth = true;
                    $this->container->set('session', $session);
                    $valid = true;
                }
                else
                {
                    $this->AddMsg("Invalid Username and or Password");
                }
            }
            else
            {
                $this->AddMsg("Could not find an account matching those credentials");
            }
        }

        return $valid;
    }

    public function BuildFilter($params)
    {
        return User::DefaultFilter();
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

    static public function DefaultFilter()
    {
        return [
            ["field" => "status", "op" => "ne", "match" => "DELETED", "type" => "string"],
            ["field" => "user_type", "op" => "eq", "match" => User::$TYPE_USER, "type" => "string"]
        ];
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
        return $this->hasRole(self::$ADMIN_ROLE) || $this->hasRole(self::$SUPER_ADMIN_ROLE);
    }

    static public function GetAllUsers($options = 0)
    {
        $dbh = DBSettings::DBConnection();

        if ($dbh)
        {
            if ($options == self::$USER_NICK_NAME)
                $order_by = "ORDER BY nick_name";
            else
                $order_by = "ORDER BY first_name, last_name";

            $type = User::$TYPE_USER;
            $sth = $dbh->query("SELECT * FROM user WHERE user_type = $type $order_by");
            $list = $sth->fetchAll(PDO::FETCH_OBJ);
        }
        else
            $list = null;

        return $list;
    }

    public function Load()
    {
        if ($this->pkey)
        {
            parent::Load();
            $this->LoadUserRoles();
            if (!empty($this->roles))
            {
                foreach ($this->roles as $role)
                {
                    $this->LoadRolePermissions($role->pkey);
                }
            }
        }
        $this->profile = new UserProfile($this->pkey);
    }

    static public function OptionList($selected = null, $options = 0)
    {
        $user_list = User::GetAllUsers($options);

        $option_list = "";

        foreach ($user_list as $usr)
        {
            if ($options == User::$USER_NICK_NAME)
                $text = "{$usr->nick_name}";
            else
                $text = "{$usr->first_name} {$usr->last_name}";

            $className = ($usr->status == self::$STATUS_INACTIVE) ? "active" : "inactive";
            $sel = ($selected == $usr->user_id) ? " selected" : "";

            if ($usr->status == User::$STATUS_ACTIVE || $sel)
                $option_list .= "<option class='{$className}' value='{$usr->user_id}'{$sel}>$text</option>";
        }

        return $option_list;
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('user_type', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('user_name', PDO::PARAM_STR, false, 128);
        $this->field_array[$i++] = new DBField('first_name', PDO::PARAM_STR, false, 128);
        $this->field_array[$i++] = new DBField('last_name', PDO::PARAM_STR, false, 128);
        $this->field_array[$i++] = new DBField('nick_name', PDO::PARAM_STR, false, 128);
        $this->field_array[$i++] = new DBField('email', PDO::PARAM_STR, true, 128);
        $this->field_array[$i++] = new DBField('phone', PDO::PARAM_STR, true, 128);
        $this->field_array[$i++] = new DBField('status', PDO::PARAM_STR, false, 128);
        $this->field_array[$i++] = new DBField('default_group', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('verified', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('block_expires', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_STR, false, 0);
    }

    public function Save()
    {
        $this->last_mod = date("c");

        if ($this->pkey)
        {
            $this->db_update();
        }
    }

    public function Create()
    {
        $this->created_on = date("c");
        $this->last_mod = date("c");
        $this->status = self::$STATUS_ACTIVE;

        $this->field_array['p'] = new DBField('password', PDO::PARAM_STR, false, 128);
        $this->field_array['c'] = new DBField('created_on', PDO::PARAM_STR, false, 0);
        $this->db_insert();
        unset($this->field_array['p']);
        unset($this->field_array['c']);
    }

    public function Validate()
    {
        $dbh = $this->dbh;

        $valid = true;

        if (empty($this->user_name))
        {
            $this->AddMsg("<div>Missing Username</div>");
        }
        else if (empty($this->password))
        {
            $valid = false;
            $this->AddMsg("<div>Missing Password</div>");
        }
        if (empty($this->first_name))
        {
            $valid = false;
            $this->AddMsg("<div>Missing First Name</div>");
        }
        if (empty($this->status))
        {
            $valid = false;
            $this->AddMsg("<div>Missing User's Status</div>");
        }
        if (empty($this->nick_name))
        {
            $this->nick_name = $this->user_name;
        }
        if (empty($this->email))
        {
            $this->email = $this->user_name;
        }

        if (empty($dbh))
        {
            $this->AddMsg("<div>Cannot verfiy request at this time.</div>");
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
                $this->AddMsg("<div>This username already exist.</div><div>Login <a href='/login'>here</a> or select another username.</div>");
            }
        }

        if ($this->email)
        {
            $sth = $dbh->prepare("SELECT COUNT(*) FROM user WHERE email = ?");
            $sth->bindValue(1, $this->email, PDO::PARAM_STR);
            $sth->execute();
            $count = $sth->fetchColumn();

            if ($count)
            {
                $valid = false;
                $this->AddMsg("<div>This email already exist.</div><div>Login <a href='/login'>here</a> or select another email.</div>");
            }
        }

        return $valid;
    }
}
