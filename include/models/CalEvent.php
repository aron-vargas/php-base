<?php
class CalEvent extends CDModel
{
    public $pkey;
    public $key_name = "event_id";
    protected $db_table = "calendar_event";

    public function __construct($id = null)
    {
        $this->pkey = $id;
    }
}