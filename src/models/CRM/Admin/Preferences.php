<?php

/**
 * @package Freedom
 */

/**
 *
 */
require_once ('PHPEmail.php');
require_once ('Application.php');
require_once ('DataStor.php');
require_once ('Forms.php');
require_once ('User.php');

/**
 *
 * @author Aron Vargas
 * @package Freedom
 */
class Preferences {
    private $prefs = null;
    private $user = null;


    /**
     * Creates a Preferences object.
     *
     * @param User $user
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->load();
    }

    /**
     * Creates a (copy) Preferences object.
     *
     * @param User $user
     */
    public function __clone()
    {
        $this->user = null;
    }

    /**
     * Convert date format to js calendar format
     * @param string $date_format
     * @return string
     */
    static public function CalendarFormat($date_format)
    {
        return str_replace(array('Y', 'd', 'm', 'M'), array('%Y', '%d', '%m', '%b'), $date_format);
    }

    /**
     * Returns a preference value from a given application and key.
     *
     * @param string $app the short name of the application.
     * @param string $key the key of the preference.
     * @return string
     */
    public function get_col($app, $key)
    {
        if (isset ($this->prefs[$app][$key]))
        {
            return $this->prefs[$app][$key];
        }
        else
        {
            $pref = DTCol::GetDefaults($app, $key, false);
            $this->set($app, $key, $pref);
            return $this->prefs[$app][$key];
        }
    }

    /**
     * Returns a preference value from a given application and key.
     *
     * @param string $app the short name of the application.
     * @param string $key the key of the preference.
     * @return string
     */
    public function get($app, $key)
    {
        if (isset ($this->prefs[$app][$key]))
        {
            return $this->prefs[$app][$key];
        }
        else
        {
            $dbh = DataStor::getHandle();

            $app_id = Application::getAppId($app);

            $sth = $dbh->prepare('
				SELECT value FROM default_preferences
				WHERE application_id = ? AND key = ?');
            $sth->bindValue(1, $app_id, PDO::PARAM_INT);
            $sth->bindValue(2, $key, PDO::PARAM_INT);
            $sth->execute();

            return $sth->fetchColumn();
        }
    }

    /**
     * Loads the preferences into our internal data structure.
     */
    public function load()
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('
			SELECT app.short_name AS app,
			       pref.key AS key,
			       pref.value AS val
			FROM applications app INNER JOIN
			     preferences pref ON app.id = pref.application_id
			WHERE pref.user_id = ?');
        $sth->bindValue(1, $this->user->getId());
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $this->prefs[$row['app']][$row['key']] = $row['val'];
        }
    }

    /**
     * Serilize this without a user
     *
     * @return string
     */
    public function Sanitize()
    {
        $copy = clone $this;

        return serialize($copy);
    }

    /**
     * Assign User attribute
     */
    public function SetUser($user)
    {
        $this->user = $user;
    }

    /**
     * Saves preferences.
     *
     * @param array $pref_arr
     */
    public function save($pref_arr)
    {
        $dbh = DataStor::getHandle();
        $app_name = $pref_arr['app'];

        $keys = array();
        $sth = $dbh->prepare('
			SELECT df.key AS key
			FROM default_preferences df INNER JOIN
			     applications apps ON df.application_id = apps.id
			WHERE apps.short_name = ?');
        $sth->bindValue(1, $app_name);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $keys[] = $row['key'];
        }


        $dbh->beginTransaction();

        foreach ($keys as $key)
        {
            if (isset ($pref_arr[$key]))
            {
                $this->set($app_name, $key, $pref_arr[$key]);
            }
        }

        $dbh->commit();
        $this->load();
    }


    /**
     * Saves a preference in the database.
     *
     * @param string $app_name
     * @param string $key
     * @param string $value
     * @param string $session_id
     */
    public function set($app_name, $key, $value, $session_id = null)
    {
        $dbh = DataStor::getHandle();
        $use_insert_query = $use_update_query = "NO";

        $sth_query = $dbh->prepare('
			SELECT prefs.application_id
			FROM preferences prefs INNER JOIN
			     applications apps ON prefs.application_id = apps.id
			WHERE prefs.user_id = ? AND
			      apps.short_name = ? AND
			      prefs.key = ?');
        $sth_query->bindValue(1, $this->user->getId(), PDO::PARAM_INT);
        $sth_query->bindValue(2, $app_name, PDO::PARAM_STR);
        $sth_query->bindValue(3, $key, PDO::PARAM_STR);
        $sth_query->execute();

        if ($sth_query->rowCount() > 0)
        {
            $use_update_query = "YES";
            $app_id = $sth_query->fetchColumn();

            $sth = $dbh->prepare('
				UPDATE preferences SET value = ?, session_id = ?
				WHERE user_id = ? AND application_id = ? AND key = ?');
        }
        else
        {
            $use_insert_query = "YES";
            $app_id = Application::getAppId($app_name);

            $sth = $dbh->prepare('
				INSERT INTO preferences (value,session_id,user_id,
				  application_id,key)
				VALUES (?,?,?,?,?)');
        }

        if (is_array($value))
            $value = serialize($value);

        $sth->bindValue(1, $value, PDO::PARAM_STR);
        $sth->bindValue(2, $session_id, (is_null($session_id) ? PDO::PARAM_NULL : PDO::PARAM_STR));
        $sth->bindValue(3, $this->user->getId(), PDO::PARAM_INT);
        $sth->bindValue(4, $app_id, PDO::PARAM_INT);
        $sth->bindValue(5, $key, PDO::PARAM_STR);

        try
        {
            $sth->execute();
        }
        catch (PDOException $pdo_exc)
        {
            $error_output = 'NOTE: THE USER DID NOT SEE THIS ERROR\n\nA database error has occurred while trying to save Preferences:\n' . $pdo_exc->getMessage() . "\n" . $pdo_exc->getTraceAsString();
            $error_output .= "
-- SELECTION QUERY
SELECT prefs.application_id
FROM preferences prefs
INNER JOIN applications apps ON prefs.application_id = apps.id
WHERE prefs.user_id = " . $this->user->getId() . "
AND apps.short_name = '{$app_name}'
AND prefs.key = {$key}

-- UPDATE QUERY {$use_update_query}
UPDATE preferences
SET value = {$value},
    session_id = {$session_id}
WHERE user_id = " . $this->user->getId() . "
AND application_id = {$app_id}
AND key = {$key}

-- INSERT QUERY {$use_insert_query}
INSERT INTO preferences ( value, session_id, user_id, application_id, key )
VALUES ( '{$value}', '{$session_id}', " . $this->user->getId() . ", {$app_id}, '{$key}' )";

            try
            {
                PHPEmail::sendEmail(
                    Config::$DEV_EMAIL,
                    $this->user,
                    "Error Report from " . $this->user->getFirstname() . ' ' . $this->user->getLastname(),
                    $error_output
                );
            }
            catch (ValidationException $vexc)
            {
                error_log('Caught ValidationException with message "' . $vexc->getMessage . '" trying to send error email from ' . $_SERVER["SCRIPT_NAME"] . ' with body {' . $error_output . '}');
            }
            catch (Exception $exc)
            {
                error_log('Caught Exception with message "' . $exc->getMessage . '" trying to send error email from ' . $_SERVER["SCRIPT_NAME"] . ' with body {' . $error_output . '}');
            }
        }

        $this->prefs[$app_name][$key] = $value;
    }

    /**
     * Track form submits server side
     */
    public function track_submit($app_name, $form_name)
    {
        $this->set($app_name, "_{$form_name}_", 1, $_COOKIE['session_id']);
    }

    /**
     * @return number of form sumbitals
     */
    public function submit_count($app_name, $form_name)
    {
        $submit_count = $this->get($app_name, "_{$form_name}_");

        $this->set($app_name, "_{$form_name}_", $submit_count + 1, $_COOKIE['session_id']);

        return $submit_count;
    }
}

?>