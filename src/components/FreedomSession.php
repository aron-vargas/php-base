<?php
namespace Freedom\Components;

use PDO;
use Freedom\Models\User;

class FreedomSession {
    public $auth;
    public $user;

    /**
     * Create a new instance
     */
    public function __construct()
    {
        # TODO: Define this method
        $this->auth = false;
        $this->user = new User();
    }

    public function End()
    {
        $dbh = DBSettings::DBConnection();

        if ($this->user->pkey)
        {
            $sth = $dbh->prepare("DELETE FROM user_session WHERE user_id = ?");
            $sth->bindValue(1, $this->user->pkey, PDO::PARAM_INT);
            $sth->execute();
        }

        if (isset($_COOKIE["CDSESSIONID"]))
        {
            $sth = $dbh->prepare("DELETE FROM user_session WHERE session_id = ?");
            $sth->bindValue(1, $_COOKIE["CDSESSIONID"], PDO::PARAM_STR);
            $sth->execute();
        }

        $this->auth = false;
        $this->user = new User();
        setcookie('CDSESSIONID', false);
        session_destroy();
        unset($_SESSION);
        unset($_COOKIE);
    }

    public function Insert()
    {
        $dbh = DBSettings::DBConnection();

        $session_id = sha1($this->user->pkey . time() . rand(1, 1000));
        setcookie("CDSESSIONID", $session_id);

        $sth = $dbh->prepare("DELETE FROM user_session WHERE user_id = ?");
        $sth->bindValue(1, $this->user->pkey, PDO::PARAM_INT);
        $sth->execute();

        $sth = $dbh->prepare("INSERT INTO user_session (session_id, user_id, session_type, timestamp) VALUES (?,?,?,CURRENT_TIMESTAMP)");
        $sth->bindValue(1, $session_id, PDO::PARAM_STR);
        $sth->bindValue(2, $this->user->pkey, PDO::PARAM_INT);
        $sth->bindValue(3, $this->user->user_type, PDO::PARAM_INT);
        $sth->execute();
    }

    public function Load($session_id)
    {
        $valid = false;
        $oneday = time() - 86400; # 1 Day old
        $dbh = DBSettings::DBConnection();

        $sth = $dbh->prepare("SELECT user_id, session_type, timestamp FROM user_session WHERE session_id = ?");
        $sth->bindValue(1, $session_id, PDO::PARAM_INT);
        $sth->execute();

        if ($data = $sth->fetch(PDO::FETCH_OBJ))
        {
            if ($data->timestamp > $oneday)
            {
                $this->user = new User($data->user_id, $data->session_type);
                $valid = true;
            }
            else
            {
                throw new Exception("Session not found or is expired. [$session_id] Please login.", 401);
            }
        }

        return $valid;
    }

    public function validate()
    {
        $this->auth = false;

        if (isset($_COOKIE["CDSESSIONID"]))
        {
            if ($this->Load($_COOKIE["CDSESSIONID"]))
            {
                $this->auth = true;
            }
            else
            {
                $this->End();
            }
        }

        return $this->auth;
    }

    public function authenticate($user_name, $password)
    {
        if ($this->user->authenticate($user_name, $password))
        {
            $this->auth = true;
            $this->Insert();
        }
        else
        {
            return "Invalid login credentials. Please try again.";
        }

        return true;
    }
}
