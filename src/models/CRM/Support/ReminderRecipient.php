<?php
/**
 * Provides ReminderRecipient Class implementation.
 *
 * Extension of BaseClass
 */
class ReminderRecipient extends BaseClass
{
	protected $db_table = 'reminder_recipient';
	protected $p_key = 'id';

	protected $id;				# int
	protected $reminder_id;		# int
	protected $alert_type;		# int
	protected $user_id;			# int
	protected $address;			# string
	protected $tzone;			# string
	protected $sent;			# int

	/**
	 * Creates a new ReminderRecipient object.
	 *
	 * @param integer $id
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);
	}

	public function change($field, $value)
	{
		parent::change($field, $value);

		return "Reminder Acknowledged";
	}

	/**
	 * Writes updates to reminder DB table
	 */
	public function save()
	{
		global $user;

		# populate user if missing
		if (!$this->user_id) $this->user_id = $user->getId();

		if ($this->id)
		{
			$sth = $this->dbh->prepare("UPDATE {$this->db_table}
			SET
				reminder_id = ?,
				alert_type = ?,
				user_id = ?,
				address = ?,
				tzone = ?
			WHERE id = ?");
			$sth->bindValue(6, $this->id, PDO::PARAM_INT);
		}
		else
		{
			$sth = $this->dbh->prepare("INSERT INTO {$this->db_table}
				(reminder_id, alert_type, user_id, address, tzone)
			VALUES (?, ?, ?, ?, ?)");
		}

		$sth->bindValue(1, (int)$this->reminder_id, PDO::PARAM_INT);
		$sth->bindValue(2, (int)$this->alert_type, PDO::PARAM_INT);
		$sth->bindValue(3, (int)$this->user_id, PDO::PARAM_INT);
		$sth->bindValue(4, $this->address, PDO::PARAM_STR);
		$sth->bindValue(5, $this->tzone, PDO::PARAM_STR);
		$sth->execute();

		if (!$this->id)
			$this->id = $this->dbh->lastInsertId('reminder_recipient_id_seq');
	}

	/**
	 * Return options html for Timezone select
	 *
	 * @param string
	 *
	 * @return string
	 */
	static public function GetTimezoneOptions($tzone='PST')
	{
		$PST = ($tzone == 'PST') ? " selected" : "";
		$MST = ($tzone == 'MST') ? " selected" : "";
		$CST = ($tzone == 'CST') ? " selected" : "";
		$EST = ($tzone == 'EST') ? " selected" : "";

		return "
		<option value='PST'{$PST}>Pacific</options>
		<option value='MST'{$MST}>Mountain</options>
		<option value='CST'{$CST}>Central</options>
		<option value='EST'{$EST}>Eastern</options>";
	}
}
?>