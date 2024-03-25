<?php

/**
 * @author Aron Vargas
 * @package Freedom
 */

/**
 * Provides Reminder Class implementation.
 *
 * Extension of BaseClass
 */
class Reminder extends BaseClass {
    protected $db_table = 'reminder';
    protected $p_key = 'id';

    protected $id;				# int
    protected $user_id;			# int
    protected $message;			# string
    protected $schedule_date;	# int
    protected $period;			# int
    protected $time_unit;		# string

    protected $recipients = array();	# array

    /**
     * Static vars
     */
    public static $EMAIL_TYPE = 1;
    public static $SMS_TYPE = 2;

    public static $DAY_UNIT = 'Days';
    public static $MIN_UNIT = 'Minutes';
    public static $HOUR_UNIT = 'Hours';

    public static $REM_ARRAY = 1;
    public static $REM_JSON = 2;

    /**
     * Creates a new Reminder object.
     *
     * @param integer $id
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * Set class property matching the array key
     *
     * @param array $form
     */
    public function copyFromArray($form = array())
    {
        parent::copyFromArray($form);

        if (isset ($form['schedule_date']))
        {
            if (is_int($form['schedule_date']))
                $this->schedule_date = $form['schedule_date'];
            else
                $this->schedule_date = strtotime($form['schedule_date']);
        }
    }

    /**
     * Writes updates to reminder DB table
     */
    public function save()
    {
        global $user;

        # populate user if missing
        if (!$this->user_id)
            $this->user_id = $user->getId();

        if ($this->id)
        {
            $sth = $this->dbh->prepare("UPDATE {$this->db_table}
			SET
				user_id = ?,
				message = ?,
				schedule_date = ?,
				period = ?,
				time_unit = ?
			WHERE id = ?");
            $sth->bindValue(6, $this->id, PDO::PARAM_INT);
        }
        else
        {
            $sth = $this->dbh->prepare("INSERT INTO {$this->db_table}
				(user_id, message, schedule_date, period, time_unit)
			VALUES (?, ?, ?, ?, ?)");
        }

        $sth->bindValue(1, (int) $this->user_id, PDO::PARAM_INT);
        $sth->bindValue(2, $this->message, PDO::PARAM_STR);
        $sth->bindValue(3, (int) $this->schedule_date, PDO::PARAM_INT);
        $sth->bindValue(4, (int) $this->period, PDO::PARAM_INT);
        $sth->bindValue(5, $this->time_unit, PDO::PARAM_STR);
        $sth->execute();

        if (!$this->id)
            $this->id = $this->dbh->lastInsertId('reminder_id_seq');
    }

    /**
     * Writes updates to reminder_recipients DB table
     *
     * @param $form
     */
    public function saveRecipients($form)
    {
        global $user;

        if (is_array($form['recipients']))
        {
            # Track ids which where saved
            $updated = array();
            foreach ($form['recipients'] as $recip)
            {
                $recip_id = (isset ($recip['id'])) ? $recip['id'] : null;
                $recipient = new ReminderRecipient($recip_id);

                # Save the recipient
                #
                $recipient->copyFromArray($recip);
                $recipient->setVar('reminder_id', $this->id);
                $recipient->save($recip);

                # Add recipient id to the updated array
                $updated[] = $recipient->getVar('id');
            }

            # Find all ids which are in our list of recipients and where not updated
            # These will be removed since they were not passed in the forms recipients array
            $rm_ary = array_diff(array_keys($this->recipients), $updated);
            foreach ($rm_ary as $id)
            {
                # Remove the record
                $sth = $this->dbh->prepare("DELETE FROM reminder_recipient WHERE id = ?");
                $sth->bindValue(1, (int) $id, PDO::PARAM_INT);
                $sth->execute();
            }
        }
        else
        {
            # Clear old values
            $sth = $this->dbh->prepare("DELETE FROM reminder_recipient WHERE reminder_id = ?");
            $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();
        }

        $this->loadRecipients();
    }

    /**
     * Reads fields from reminder DB table
     */
    public function load()
    {
        parent::load();

        $this->loadRecipients();
    }

    /**
     * Reads records from reminder_recipient DB table
     * Creates an array of recipients
     */
    public function loadRecipients()
    {
        if ($this->id)
        {
            $this->recipients = array();

            $sth = $this->dbh->prepare("SELECT id
			FROM reminder_recipient
			WHERE reminder_id = ?");
            $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();
            while (list($recip_id) = $sth->fetch(PDO::FETCH_NUM))
            {
                $this->recipients[$recip_id] = new ReminderRecipient($recip_id);
            }
        }
    }


    /**
     * Return options html for Alert Type select
     *
     * @param string
     *
     * @return string
     */
    static public function GetAlertTypeOptions($user, $alert_type = null)
    {
        $dbh = DataStor::getHandle();

        # Get potential addresses
        # Limit options based on available data
        $email = $user->getEmail();
        $phone = $user->getPhone();

        $options = "";
        $sth = $dbh->query("SELECT id, name FROM reminder_alert_type WHERE active = true ORDER BY display_order");
        while (list($id, $name) = $sth->fetch(PDO::FETCH_NUM))
        {
            $sel = ($id == $alert_type) ? " selected" : "";
            if ($id == self::$EMAIL_TYPE)
            {
                if ($email)
                    $name .= " &lt;$email&gt;";
                else
                    $id = null; # Cannot sent email alert
            }
            else if ($id == self::$SMS_TYPE)
            {
                if ($phone)
                    $name .= " &lt;$phone&gt;";
                else
                    $id = null; # Cannot sent sms alert
            }

            if ($id)
                $options .= "<option value='$id'{$sel}>$name</options>";
        }
        return $options;
    }

    /**
     * Return options html for Time Unit select
     *
     * @param string
     *
     * @return string
     */
    static public function GetTimeUnitOptions($unit = null)
    {
        $mn = ($unit == self::$MIN_UNIT) ? "selected" : "";
        $hr = ($unit == self::$HOUR_UNIT) ? "selected" : "";
        $dy = ($unit == self::$DAY_UNIT) ? "selected" : "";

        $options = "
		<option value='Minutes' {$mn}>Minute(s)</options>
		<option value='Hours' {$hr}>Hour(s)</options>
		<option value='Days' {$dy}>Day(s)</options>";

        return $options;
    }

    /**
     * Return messages for reminders which are due
     *
     * @param string
     * @param integer
     *
     * @return mixed
     */
    static public function GetReminderAlerts($format = 1 /*REM_ARRAY*/ , $alert_type = null)
    {
        $dbh = DataStor::getHandle();

        # Filter on Alert Type
        $at_filter = ($alert_type) ? "AND rr.alert_type = " . (int) $alert_type : "";

        /**
         * Take age + tzone adjustment of the recipient
         * Gives an interval indicating how long until reminder is due
         *
         * Return all reminders due within the period interval
         */
        $sth = $dbh->query("SELECT
			r.id as reminder_id,
			r.message,
			rr.user_id,
			rr.address,
			rr.alert_type,
			rr.id as recipient_id
		FROM reminder r
		INNER JOIN reminder_recipient rr ON r.id = rr.reminder_id
		WHERE to_timestamp(r.schedule_date) - (r.period || ' ' || r.time_unit)::Interval -
			CASE rr.tzone
				WHEN 'MST' THEN '1 Hours'
				WHEN 'CST' THEN '2 Hours'
				WHEN 'EST' THEN '3 Hours'
				ELSE '0 Hours'
			END::Interval
			<= CURRENT_TIMESTAMP
		AND rr.sent = 0
		$at_filter");
        $alerts = $sth->fetchAll(PDO::FETCH_ASSOC);

        # Format the results as requested
        if ($format == self::$REM_JSON)
        {

            $alerts = json_encode($alerts);
        }

        return $alerts;
    }

    /**
     * Return SMS gatway for the users mobile carrier
     */
    static public function GetSMSGateway($user_id)
    {
        $dbh = DataStor::getHandle();

        $rem_user = new User($user_id);
        $pref = new Preferences($rem_user);

        $sth = $dbh->prepare("SELECT sms_gateway FROM mobile_carrier WHERE carrier_name = ?");
        $sth->bindValue(1, trim($pref->get('general', 'mobile_carrier')), PDO::PARAM_STR);
        $sth->execute();
        list($gw) = $sth->fetch(PDO::FETCH_NUM);

        return $gw;
    }
}
?>