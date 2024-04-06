<?php
namespace Freedom\Models;

use PDO;
use Freedom\Components\DBField;
use Freedom\Components\DBSettings;

/**
 *
CREATE TABLE `session_token` (
  `session_token_id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `token` blob NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires` int NOT NULL DEFAULT '3600',
  `sub` varchar(64) NOT NULL,
  `iat` varchar(64) NOT NULL,
  `alg` varchar(45) DEFAULT 'HS256',
  PRIMARY KEY (`session_token_id`),
  KEY `session_token_fkey_idx` (`session_id`),
  CONSTRAINT `session_token_seesion_id_fkey` FOREIGN KEY (`session_id`) REFERENCES `user_session` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Holds jwt token for login sessions'
*/

class SessionToken extends CDModel {
    public $pkey;
    public $key_name = "session_id";
    protected $db_table = "session_token";

    public function __construct($id = null)
    {
        $this->pkey = $id;
        $this->session_id = $id;
        $this->dbh = DBSettings::DBConnection();
    }

    private function SetFieldArray()
    {
        $i = 0;
        $this->field_array[$i++] = new DBField('session_id', PDO::PARAM_INT, false, 0);
    }
}