<?php
namespace Freedom\Components;

use PDO;

class DBSettings {
    public $DBDRIVER = "mysql";
    #public $DBHOST = "localhost";
    public $DBHOST = "192.168.1.246";
    public $DBNAME = "freedom";
    public $DBUSER = "railside_app_01";
    #public $DBUSER = "root";
    public $DBPASS = "R@ilside001";
    #public $DBPASS = "cLDsNBVE4R6w";

    public $conn;

    /**
     * Create a new instance
     */
    public function __construct()
    {
        $DNS = "{$this->DBDRIVER}:host={$this->DBHOST};dbname={$this->DBNAME}";
        $this->conn = new PDO($DNS, $this->DBUSER, $this->DBPASS);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    static public function DBConnection()
    {
        $DB = new DBSettings();
        return $DB->conn;
    }
}
