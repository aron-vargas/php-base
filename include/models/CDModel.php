<?php

class CDModel {
    public $pkey;
    public $key_name = "pkey";
    protected $db_table;
    protected $field_array = array();

    public $ClassName = "CDModel";

    public $edit_view = "include/templates/login_form.php";
    public $display_view = "include/templates/home.php";

    /**
     * Create a new instance
     * @param mixed
     */
    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->SetFieldArray();
        $this->Load();
    }

    /**
     * Set the field values in the PDO Statement
     * @param PDOStatement
     */
    public function BindValues(&$sth)
    {
        $i = 1;

        foreach ($this->field_array as $field)
        {
            $val = $this->Val($field);
            $sth->bindValue($i++, $val, $field->Type($val));
        }

        if ($this->pkey)
            $sth->bindValue($i++, $this->pkey, PDO::PARAM_INT);

        return $i;
    }

    /**
     * Strip HTML tags and trim whitestpace
     * @param mixed
     */
    static public function Clean($param)
    {
        return strip_tags(trim($param));
    }

    /**
     * Add a new rocord
     */
    public function Create()
    {
        $this->db_insert();
    }

    /**
     * Change a field value
     */
    public function Change($field, $value)
    {
        $dbh = CDController::DBConnection();

        if (is_int($value))
            $val_type = PDO::PARAM_INT;
        else if (is_bool($value))
            $val_type = PDO::PARAM_BOOL;
        else if (is_null($value))
            $val_type = PDO::PARAM_NULL;
        else if (is_string($value))
            $val_type = PDO::PARAM_STR;
        else
            $val_type = false;

        $field = trim($field);

        $sth = $dbh->prepare("UPDATE {$this->db_table} SET {$field} = ? WHERE {$this->key_name} = ?");
        $sth->bindValue(1, $value, $val_type);
        $sth->bindValue(2, $this->pkey, PDO::PARAM_INT);
        $sth->execute();

        $this->{$field} = $value;
    }

    /**
     * Copy attributes from array
     * @param array
     */
    public function Copy($assoc)
    {
        if (is_array($assoc))
        {
            foreach ($assoc as $key => $val)
            {
                if ($key == 'db_table')
                    continue;
                else if (@property_exists($this, $key))
                    $this->{$key} = $val;
            }
        }
    }

    /**
     * Find all records matching the field value
     *
     * @param string $table_name
     * @param string $field_name
     * @param mixed $key
     * @return StdClass[] | null
     */
    static public function GetALL($table_name, $field_name, $key)
    {
        $dbh = CDController::DBConnection();

        if ($dbh)
        {
            $table_name = self::Clean($table_name);
            $field_name = self::Clean($field_name);
            $key = self::Clean($key);
            $sth = $dbh->query("SELECT * FROM {$table_name} WHERE {$field_name} = {$key}");
            $sth->execute();
            return $sth->fetchALL(PDO::FETCH_OBJ);
        }

        return null;
    }

    /**
     * Get the date string
     *
     * @param mixed
     * @param boolean
     * @param boolean
     * @return string
     */
    public function GetDate($attribute, $html = true, $clean = true)
    {
        $val = $this->Get($attribute, $html, $clean);
        $parsed = self::ParseDate($val);
        return $parsed;
    }

    /**
     * Parse given value into a date string
     *
     * @param mixed
     * @return string
     */
    static public function ParseDate($date_str)
    {
        $parsed = null;

        if (preg_match('/[\-\/]/', $date_str))	# Date string
            $parsed = date('Y-m-d', strtotime($date_str));
        else if (is_numeric($date_str))			# Unix time
            $parsed = date('Y-m-d', $date_str);

        return $parsed;
    }

    /**
     * Get time string
     *
     * @param mixed
     * @param boolean
     * @param boolean
     * @return string
     */
    public function GetTime($attribute, $html = true, $clean = true)
    {
        $parsed = null;
        $val = $this->Get($attribute, $html, $clean);
        $parsed = self::ParseTime($val);

        return $parsed;
    }

    /**
     * Parse given parameter into a time string
     *
     * @param mixed
     * @return string
     */
    static public function ParseTime($time_str)
    {
        $parsed = null;

        if (preg_match('/[:]/', $time_str))	# Date string
            $parsed = date('H:i:s', strtotime($time_str));
        else if (is_numeric($time_str))			# Unix time
            $parsed = date('H:i:s', $time_str);

        return $parsed;
    }

    /**
     * Parse given parameter into a date string
     *
     * @param mixed
     * @param boolean
     * @param boolean
     * @return string
     */
    public function GetTStamp($date, $html = true, $clean = true)
    {
        $parsed = NULL;
        $date = $this->Get($date, $html, $clean);
        $parsed = self::ParseTStamp($date);

        return $parsed;
    }

    /**
     * Parse given parameter into a datetime string
     *
     * @param mixed
     * @param boolean
     * @param boolean
     * @return string
     */
    static public function ParseTStamp($date, $html = true, $clean = true)
    {
        $parsed = NULL;

        if (preg_match('/[\-\/]/', $date))	# Date string
            $parsed = trim($date);
        else if (is_numeric($date))			# Unix time
            $parsed = date('Y-m-d H:i:s', $date);

        return $parsed;
    }

    /**
     * Get unix timestamp
     *
     * @param mixed
     * @param boolean
     * @param boolean
     * @return integer
     */
    public function GetUnixTime($date, $html = true, $clean = true)
    {
        $parsed = NULL;
        $date = $this->Get($date, $html, $clean);
        $parsed = self::ParseUnixTime($date);

        return $parsed;
    }

    /**
     * Parse given parameter into a unix timestamp
     *
     * @param mixed
     * @param boolean
     * @param boolean
     * @return integer
     */
    static public function ParseUnixTime($date, $html = true, $clean = true)
    {
        $parsed = NULL;

        if (preg_match('/[\-\/]/', $date))	# Date string
            $parsed = strtotime($date);
        else if (is_numeric($date))			# Unix time
            $parsed = trim($date);

        return $parsed;
    }

    /**
     * Return attribute value.
     * Option to replace html entities
     * Option to clean tags
     *
     * @param string $attribute
     * @param boolean $html
     * @param boolean $clean
     * @return mixed
     */
    public function Get($attribute, $html = true, $clean = true)
    {
        $val = null;

        if (@property_exists($this, $attribute))
        {
            $val = $this->{$attribute};

            if ($html)
                $val = htmlentities($val, ENT_QUOTES);
            if ($clean)
                $val = self::Clean($val);
        }

        return $val;
    }

    /**
     * "Delete" the record
     */
    public function Delete()
    {
        $dbh = CDController::DBConnection();

        $sth = $dbh->query("SELECT * FROM {$this->db_table} WHERE {$this->key_name} = {$this->pkey}");
        $sth->bindValue(1, $this->pkey, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Set attribute values from DB record
     */
    public function Load()
    {
        if ($this->pkey)
        {
            $dbh = CDController::DBConnection();

            $sth = $dbh->prepare("SELECT * FROM {$this->db_table} WHERE {$this->key_name} = ?");
            $sth->bindValue(1, $this->pkey, PDO::PARAM_INT);
            $sth->execute();
            $rec = $sth->fetch(PDO::FETCH_ASSOC);
            $this->Copy($rec);
        }
    }

    /**
     * Update DB record
     */
    public function Save()
    {
        if ($this->pkey)
            $this->db_update();
        else
            $this->db_insert();
    }

    /**
     * Define the field array
     */
    private function SetFieldArray()
    {
        $this->field_array = array();
    }

    /**
     * Check validity of this record
     */
    public function Validate()
    {
        return true;
    }

    /**
     * Return attribute value
     * @param DBField
     * @return mixed
     */
    public function Val($db_field, $clean = true)
    {
        $val = null;

        if (@property_exists($this, $db_field->name))
        {
            $val = $this->{$db_field->name};

            if ($val)
            {
                if ($clean)
                    $val = self::Clean($val);

                if ($db_field->max_length)
                    $val = substr($val, 0, $db_field->max_length);
            }
        }

        return $val;
    }

    /**
     * Perform database insert
     * @return mixed
     */
    public function db_insert()
    {
        $dbh = CDController::DBConnection();

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

        # Get id for autogenerated fields
        $this->pkey = $dbh->lastInsertId();

        return $this->pkey;
    }

    /**
     * Perform database update
     * @return integer
     */
    public function db_update()
    {
        $dbh = CDController::DBConnection();

        $fields = "";
        foreach ($this->field_array as $field)
        {
            $fields .= " {$field->Name()} = ?,";
        }

        # remove trailing ','
        $fields = substr($fields, 0, -1);

        $sth = $dbh->prepare("UPDATE {$this->db_table} SET {$fields} WHERE {$this->key_name} = ?");
        $i = $this->BindValues($sth);
        $sth->execute();

        return $i;
    }
}
