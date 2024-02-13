<?php

class DBSettings
{
	public $DBDRIVER = "mysql";
	#public $DBHOST = "localhost";
	public $DBHOST = "localhost";
	public $DBNAME = "chicks";
	public $DBUSER = "web";
	#public $DBUSER = "root";
	public $DBPASS = "ChickenD!nner";
	#public $DBPASS = "cLDsNBVE4R6w";

	public $conn;

	public function __construct()
	{
		$DNS = "{$this->DBDRIVER}:host={$this->DBHOST};dbname={$this->DBNAME}";
		$this->conn = new PDO($DNS, $this->DBUSER, $this->DBPASS);
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
}
