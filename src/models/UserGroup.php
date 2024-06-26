<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;
use Freedom\Traits\RoleTrait;
use Freedom\Traits\PermissionTrait;
use Freedom\Models\User;

class UserGroup extends User {

    public $password = "--NO VALUE--";  # string
    public $status = "ACTIVE";  # string
    public $user_type = 'GROUP';            # string

    /**
     * Create a new instance
     * @param integer
     * @param string
     */
    public function __construct($user_id = null, $type = 'GROUP')
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
        return false;
    }

    public function BuildFilter($params)
    {
        return UserGroup::DefaultFilter();
    }

    static public function DefaultFilter()
    {
        return [
            ["field" => "status", "op" => "ne", "match" => "DELETED", "type" => "string"],
            ["field" => "user_type", "op" => "eq", "match" => User::$TYPE_GROUP, "type" => "string"]
        ];
    }

    static public function GetAllGroups($options = 0)
    {
        $dbh = DBSettings::DBConnection();

        if ($dbh)
        {
            if ($options == self::$USER_NICK_NAME)
                $order_by = "ORDER BY nick_name";
            else
                $order_by = "ORDER BY first_name, last_name";

            $type = User::$TYPE_GROUP;
            $sth = $dbh->query("SELECT * FROM user WHERE user_type = '$type' $order_by");
            $list = $sth->fetchAll(PDO::FETCH_OBJ);
        }
        else
            $list = null;

        return $list;
    }

    public function GetAllMembers($options = 0)
    {
        $dbh = DBSettings::DBConnection();

        if ($dbh)
        {
            if ($options == self::$USER_NICK_NAME)
                $order_by = "ORDER BY u.nick_name";
            else
                $order_by = "ORDER BY u.first_name, u.last_name";

            $type = User::$TYPE_GROUP;
            $sth = $dbh->prepare("SELECT
                u.user_id,
                u.first_name,
                u.last_name,
                u.user_name,
                u.nick_name,
                u.email,
                u.phone
            FROM user u
            INNER JOIN user_role_join j on u.user_id = j.user_id
            WHERE j.role_id = ?
            AND u.status = ?
            $order_by");
            $sth->execute(array($this->pkey, User::$STATUS_ACTIVE));
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
        }
    }

    static public function OptionList($selected = null, $options = 0)
    {
        $user_list = UserGroup::GetAllGroups($options);

        $option_list = "";

        foreach ($user_list as $usr)
        {
            if ($options == User::$USER_NICK_NAME)
                $text = "{$usr->nick_name}";
            else
                $text = "{$usr->first_name} {$usr->last_name}";

            $sel = ($selected == $usr->user_id) ? " selected" : "";

            if ($usr->status == User::$STATUS_ACTIVE || $sel)
                $option_list .= "<option value='{$usr->user_id}'{$sel}>$text</option>";
        }

        return $option_list;
    }

    /**
     * Update DB record
     */
    public function Save()
    {
        if (empty($this->created_at))
            $this->created_at = date("c");

        $this->updated_at = date("c");

        if ($this->pkey)
            $this->db_update();
        else
            $this->db_insert();
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
        $this->field_array[$i++] = new DBField('verified', PDO::PARAM_INT, false, 0);
        $this->field_array[$i++] = new DBField('block_expires', PDO::PARAM_STR, true, 0);
        $this->field_array[$i++] = new DBField('password', PDO::PARAM_STR, false, 128);
        $this->field_array[$i++] = new DBField('updated_at', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('created_at', PDO::PARAM_STR, false, 0);
    }
}