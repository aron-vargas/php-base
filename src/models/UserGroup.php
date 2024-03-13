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
        if (empty($this->created_on))
            $this->created_on = date("c");

        $this->last_mod = date("c");

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
        $this->field_array[$i++] = new DBField('last_mod', PDO::PARAM_STR, false, 0);
        $this->field_array[$i++] = new DBField('created_on', PDO::PARAM_STR, false, 0);
    }
}