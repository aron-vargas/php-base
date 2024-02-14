<?php

class CDModel
{
    public $ClassName = "CDModel";

    public $edit_view = "include/templates/login_form.php";
	public $display_view = "include/templates/home.php";

    public function __construct()
    {

    }

    // Force Extending classes to define these method
    public function Create() {}
    public function Change($field, $value) {}
    public function Copy($assoc) {}
    public function Delete() {}
    public function Save() {}
    public function Validate() {}
}
