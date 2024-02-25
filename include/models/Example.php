<?php
class Example extends CDModel
{
    public $pkey;
    public $key_name = "pkey";
    protected $db_table = "db_table_name";

    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->{$this->key_name} = $id;
    }

    private function SetFieldArray()
    {
        $i = 0;
		$this->field_array[$i++] = new DBField('pkey', PDO::PARAM_INT, false, 0);
    }
}