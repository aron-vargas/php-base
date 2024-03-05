<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

class CDModel {
    public $pkey;
    public $key_name = "pkey";
    protected $db_table;
    protected $field_array = array();

    protected $container;

    protected $dbh;

    /**
     * Create a new instance
     * @param mixed
     */
    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->dbh = DBSettings::DBConnection();
        $this->SetFieldArray();
        $this->Load();
    }

    /**
     * Append to the message array
     * @param string
     */
    public function AddMsg($message)
    {
        if ($this->container)
        {
            $msg = $this->container->get('message');
            $msg[] = $message;
            $this->container->set('message', $msg);
        }
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

    public function BuildFilter($params)
    {
        return self::DefaultFilter();
    }
    static public function DefaultFilter()
    {
        return [];
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
        $dbh = $this->dbh;

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
     * Create a new Class instance and copy the default values into this instance
     */
    public function Clear()
    {
        $this->pkey = 0;
        $this->{$this->key_name} = 0;
        $this->Load();

        $ClassName = get_class($this);
        $empty = new $ClassName(0);
        foreach ($field_array as $field)
        {
            $this->{$field->name} = $empty->{$field->name};
        }
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
     * @param mixed $filter
     * @return StdClass[] | null
     */
    static public function GetALL($table_name, $filter)
    {
        $dbh = DBSettings::DBConnection();

        if ($dbh)
        {
            $table_name = self::Clean($table_name);
            $AND_WHERE = self::ParseFilter($filter);
            $sth = $dbh->query("SELECT * FROM {$table_name} {$AND_WHERE}");
            $sth->execute();
            return $sth->fetchALL(PDO::FETCH_OBJ);
        }

        return null;
    }

    public function GetTable()
    {
        return $this->db_table;
    }

    /**
     * Build WHERE clause from args array
     * $args = [
     *  [field => sring, type => string, op => string, match => string],
     *  [field => sring, type => string, op => string, match => string],
     *  ...
     *  ]
     *
     * @param array
     * @return string
     */
    static public function ParseFilter($args)
    {
        $dbh = DBSettings::DBConnection();

        # All Valid SM status
        $WHERE = "WHERE true";

        if ($args && !empty($args))
        {
            foreach ($args as $idx => $filter)
            {
                $field = $filter['field'];
                $op = $filter['op'];

                $is_int = $filter['type'] == "int";
                $is_date = $filter['type'] == "date";

                $string = trim(urldecode($filter['match']));
                if (strtoupper($string) == 'YES')
                    $string = "YES";
                if (strtoupper($string) == 'NO')
                    $string = "NO";

                if ($is_date)
                    $date_str = date('Y-m-d', strtotime($string));

                switch ($op)
                {
                    case 'sw':
                        $WHERE .= "\n AND {$field} like " . $dbh->quote("$string%");
                        break;
                    case 'ew':
                        $WHERE .= "\n AND {$field} like " . $dbh->quote("%$string");
                        break;
                    case 'eq':
                        if ($is_int)
                            $WHERE .= "\n AND $field = " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field} = " . $dbh->quote($date_str);
                        else
                            $WHERE .= "\n AND upper($field) = " . $dbh->quote(strtoupper($string));
                        break;
                    case 'ne':
                        if ($is_int)
                            $WHERE .= "\n AND $field <> " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field} != {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND upper($field) <> " . $dbh->quote(strtoupper($string));
                        break;
                    case 'gt':
                        if ($is_int)
                            $WHERE .= "\n AND $field > " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field} > {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND $field > " . $dbh->quote($string);
                        break;
                    case 'lt':
                        if ($is_int)
                            $WHERE .= "\n AND $field < " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field} < {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND $field < " . $dbh->quote($string);
                        break;
                    default:
                        if ($is_date)
                            $WHERE .= "\n AND {$field} = " . $dbh->quote($date_str);
                        else
                            $WHERE .= "\n AND {$field} like " . $dbh->quote("%$string%");
                        break;
                }
            }
        }
        //echo "<pre>$WHERE</pre>";
        return $WHERE;
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
    static public function human_time_diff($start, $end = -1)
    {
        if ($end < 0)
            $end = time();

        // Adjust this definition if you want
        // this assumes all months have 30 days, every year
        // 365 days, and every month 4 weeks. Since it is only
        // to give a very rough estimate of the time elapsed it should
        // be fine though.
        $SECOND = 1;
        $MINUTE = 60 * $SECOND;
        $HOUR = 60 * $MINUTE;
        $DAY = 24 * $HOUR;
        $WEEK = 7 * $DAY;
        $MONTH = 30 * $DAY;
        $YEAR = 365 * $DAY;

        $increments = [
            [$SECOND, 'second'],
            [$MINUTE, 'minute'],
            [$HOUR, 'hour'],
            [$DAY, 'day'],
            [$WEEK, 'week'],
            [$MONTH, 'month'],
            [$YEAR, 'year']
        ];

        $diff = $end - $start;
        $plural = '';
        $units = ceil($diff / $increments[count($increments) - 1][0]);
        $unit = $increments[count($increments) - 1][1];

        for ($i = 1; $i < count($increments); $i++)
        {
            if ($increments[$i - 1][0] <= $diff && $diff < $increments[$i][0])
            {
                $units = ceil($diff / $increments[$i - 1][0]);
                $unit = $increments[$i - 1][1];
                break;
            }
        }

        if ($units > 1)
            $plural = 's';
        return sprintf("%d %s%s", $units, $unit, $plural);
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
        $dbh = $this->dbh;

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
            $sth = $this->dbh->prepare("SELECT * FROM {$this->db_table} WHERE {$this->key_name} = ?");
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

    public function Connect($containter)
    {
        $this->container = $containter;
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
        $dbh = $this->dbh;

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
