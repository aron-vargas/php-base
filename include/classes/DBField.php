<?php

class DBField
{
    public $name;
    public $val;
    private $type;
    private $nullable;

    public $max_length;

    public function __construct($name = 'pkey', $type = PDO::PARAM_INT, $nullable = true, $max_length = 0)
    {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
    }

    public function Name()
    {
        return $this->name;
    }

    public function Type($val)
    {
        if ($this->nullable)
        {
            if (is_null($val))
                return PDO::PARAM_NULL;
        }

        return $this->type;
    }
}
