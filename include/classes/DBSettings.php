<?php

class DBSettings
{
	public $DBDRIVER = "mysql";
	#public $DBHOST = "localhost";
	public $DBHOST = "192.168.1.246";
	public $DBNAME = "freedom";
	public $DBUSER = "railside_app_01";
	#public $DBUSER = "root";
	public $DBPASS = "R@ilside001";
	#public $DBPASS = "cLDsNBVE4R6w";

	public $conn;

	public function __construct()
	{
		$DNS = "{$this->DBDRIVER}:host={$this->DBHOST};dbname={$this->DBNAME}";
		$this->conn = new PDO($DNS, $this->DBUSER, $this->DBPASS);
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
}
