<?php

class CDModel
{
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
	 * Perform requestion action
	 * @param string
	 * @param mixed
	 */
    public function ActionHandler($action, $req)
    {
        if ($action == 'save')
        {
            $this->Copy($req);
            $this->Save();
        }
        else if ($action == 'create')
        {
            $this->Copy($req);
            if ($this->Validate())
            {
                $this->Create();
            }
        }
        else if ($action == 'change')
        {
            $field = (isset($req['field'])) ? $req['field'] : null;
            $value = (isset($req['value'])) ? trim($req['value']) : null;

            $this->Change($field, $value);
        }
        else if ($action == 'delete')
        {
            $this->Delete();
        }
    }

    /**
     * Set the field values in the PDO Statement
     * @param PDOStatement
     */
    public function BindValues(&$sth)
    {
        $i = 1;

        foreach($this->field_array as $field)
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
        $dbh = $_SESSION['APPSESSION']->dbh;

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
            foreach($assoc AS $key => $val)
            {
                if ($key == 'db_table')
                    continue;
                else if (@property_exists($this, $key))
                    $this->{$key} = $val;
            }
        }
    }

    /**
     * "Delete" the record
     */
    public function Delete()
    {
        $dbh = $_SESSION['APPSESSION']->dbh;

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
            $dbh = $_SESSION['APPSESSION']->dbh;

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
    public function Validate() {}

    /**
     * Return attribute value
     * @param DBField
     * @return mixed
     */
    public function Val($db_field)
    {
        $val = null;

        if (@property_exists($this, $db_field->name))
        {
            $val =  $this->{$db_field->name};

            if ($db_field->max_length)
                $val = substr($val, 0, $db_field->max_length);
        }

        return $val;
    }

    /**
     * Perform database insert
     * @return mixed
     */
    public function db_insert()
    {
        $dbh = $_SESSION['APPSESSION']->dbh;

        $fields = "";
        $holders = "";
        foreach($this->field_array as $field)
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
        $dbh = $_SESSION['APPSESSION']->dbh;

        $fields = "";
        foreach($this->field_array as $field)
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
