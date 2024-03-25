<?
/**
 * @author Aron Vargas
 * @package Freedom
 */
require_once ('classes/BaseClass.php');
require_once ('classes/Reminder.php');

/*
 * Provides Task Class implementation.
 *
 * Extension of BaseClass
 */
class Task extends BaseClass {
    protected $db_table = 'task';
    protected $p_key = 'task_id';

    public $task_id = null;		// int
    public $task_action = null;	// sting
    public $open_by = 0;		// int
    public $open_date = 0;		// int
    public $due_date = 0;		// int
    public $closed_date = 0;	// int
    public $phase_id = 0;		// int
    public $customer_id = 0;	// int
    public $last_mod_by = 0;	// int
    public $last_mod_date = 0;	// int
    public $important = 0;		// bool
    public $required = 0;		// bool
    public $opportunity_id;		// int
    public $opportunity_name;	// string
    public $assigned_to;		// int

    public $elem_type = 'SalesTask';
    public $category_text = '--';
    public $priority_text = '--';
    public $status_text = 'Open';
    public $open_by_name;
    public $assigned_to_name;
    public $customer_name;
    public $title;

    public $reminder;		# object
    public $attachments;

    /*
     * Creates a new Task object.
     *
     * @param integer $id
     */
    public function __construct($task_id = null)
    {
        parent::__construct($task_id);
    }

    /*
     * Set class property matching the array key
     *
     * @param array $form
     */
    public function copyFromArray($form = array())
    {
        parent::copyFromArray($form);

        if (isset ($form['due_date']))
        {
            $duehour = isset ($form['duehour']) ? $form['duehour'] : '4';
            $duemin = isset ($form['duemin']) ? $form['duemin'] : '00';
            $dueampm = isset ($form['dueampm']) ? $form['dueampm'] : 'pm';

            $this->due_date = strtotime("{$form['due_date']} {$duehour}:{$duemin} {$dueampm}");
        }

        $this->important = isset ($form['important']) ? 1 : 0;
        $this->required = isset ($form['required']) ? 1 : 0;

        if (isset ($form['completed']) && $this->closed_date == 0)
        {
            $this->closed_date = time();
        }

        if (isset ($form['closed_date']))
        {
            if (is_int($form['closed_date']))
                $this->closed_date = $form['closed_date'];
            else
                $this->closed_date = strtotime($form['closed_date']);
        }
    }

    public function Delete()
    {
        parent::Delete();

        echo "reload";
    }

    /*
     * Populates this object from the matching record in the database.
     */
    public function load()
    {
        parent::load();

        # For searching
        $this->title = $this->task_action;

        if (!empty ($this->opportunity_id))
        {
            # Load Opportunity Name
            $sth = $this->dbh->query("SELECT
				o.name
			FROM sales_opportunity o
			WHERE o.opportunity_id = {$this->opportunity_id}");
            $sth->execute();
            $this->opportunity_name = $sth->fetchColumn();
        }

        if (!empty ($this->customer_id))
        {
            # Load Customer Name
            $sth = $this->dbh->query("SELECT
				office_name
			FROM corporate_office
			WHERE office_id = {$this->customer_id}");
            $this->customer_name = $sth->fetchColumn();
        }

        if ($this->closed_date)
            $this->status_text = 'Closed';

        if ($this->open_by > 0)
        {
            $sth = $this->dbh->prepare("SELECT firstname || ' ' || lastname AS fullname  FROM users WHERE id = {$this->open_by}");
            $sth->execute();
            $this->open_by_name = $sth->fetchColumn();
        }

        if ($this->assigned_to > 0)
        {
            $sth = $this->dbh->prepare("SELECT firstname || ' ' || lastname AS fullname  FROM users WHERE id = {$this->assigned_to}");
            $sth->execute();
            $this->assigned_to_name = $sth->fetchColumn();
        }

        # Load the reminder if any and attachments
        $this->attachments = null;
        $reminder_id = null;
        if ($this->task_id)
        {
            # For now only get one reminder per task
            $sth = $this->dbh->prepare("SELECT reminder_id FROM task_reminder WHERE task_id = ? LIMIT 1");
            $sth->bindValue(1, $this->task_id, PDO::PARAM_INT);
            $sth->execute();
            list($reminder_id) = $sth->fetch(PDO::FETCH_NUM);

            $sth = $this->dbh->prepare("SELECT
				a.*
			FROM attachment a
			WHERE a.reference_key = ? AND a.reference_elem = 'Task'");
            $sth->bindValue(1, $this->task_id, PDO::PARAM_INT);
            $sth->execute();
            $this->attachments = $sth->fetchAll(PDO::FETCH_OBJ);
        }

        $this->reminder = new Reminder($reminder_id);

    }

    /**
     * Saves the contents of this object to the database. If this object
     * has an id, the record will be UPDATE'd.  Otherwise, it will be
     * INSERT'ed
     */
    public function save()
    {
        global $user;

        $now = time();

        if (!$this->open_by)
            $this->open_by = $user->getId();

        # Date validation
        if ($this->open_date < 1)
            $this->open_date = time();
        if ($this->due_date < 1)
            $this->due_date = 0;
        if ($this->closed_date < 1)
            $this->closed_date = 0;

        if (strlen($this->task_action) > 255)
            $this->task_action = substr($this->task_action, 0, 255);

        $opp_t = ($this->opportunity_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $assigned_to_t = ($this->assigned_to) ? PDO::PARAM_INT : PDO::PARAM_NULL;

        if ($this->task_id)
        {
            $sth = $this->dbh->prepare("
				UPDATE task
				SET
					task_action = ?,
					open_by = ?,
					open_date = ?,
					due_date = ?,
					closed_date = ?,
					customer_id = ?,
					phase_id = ?,
					last_mod_by = ?,
					last_mod_date = ?,
					important = ?,
					required = ?,
					opportunity_id = ?,
					assigned_to = ?
				WHERE task_id = ?");
            $sth->bindValue(14, (int) $this->task_id, PDO::PARAM_INT);
        }
        else
        {
            $sth = $this->dbh->prepare("
				INSERT INTO task (task_action,open_by,open_date,due_date,
					closed_date,customer_id,phase_id,last_mod_by,last_mod_date,
					important, required, opportunity_id, assigned_to)
			 	VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        }
        $sth->bindValue(1, $this->task_action, PDO::PARAM_STR);
        $sth->bindValue(2, (int) $this->open_by, PDO::PARAM_INT);
        $sth->bindValue(3, (int) $this->open_date, PDO::PARAM_INT);
        $sth->bindValue(4, (int) $this->due_date, PDO::PARAM_INT);
        $sth->bindValue(5, (int) $this->closed_date, PDO::PARAM_INT);
        $sth->bindValue(6, (int) $this->customer_id, PDO::PARAM_INT);
        $sth->bindValue(7, (int) $this->phase_id, PDO::PARAM_INT);
        $sth->bindValue(8, (int) $user->getId(), PDO::PARAM_INT);
        $sth->bindValue(9, (int) $now, PDO::PARAM_INT);
        $sth->bindValue(10, (int) $this->important, PDO::PARAM_BOOL);
        $sth->bindValue(11, (int) $this->required, PDO::PARAM_BOOL);
        $sth->bindValue(12, (int) $this->opportunity_id, $opp_t);
        $sth->bindValue(13, (int) $this->assigned_to, $assigned_to_t);
        $sth->execute();
        if (!$this->task_id)
        {
            $this->task_id = $this->dbh->lastInsertId('task_task_id_seq');
        }

        # Update the reminder if user is task owner
        if (isset ($_REQUEST['reminder_btn']) && $this->open_by == $user->getId())
        {
            if ($_REQUEST['reminder_btn'])
            {
                $this->saveReminder($_REQUEST);
            }
            else
            {
                $this->reminder->delete();
                $this->reminder = new Reminder();
            }
        }
    }

    /**
     * Save reminder to DB
     *
     * @param array
     */
    public function saveReminder($form)
    {
        global $user;

        # Remove Join for this task
        $sth = $this->dbh->prepare("DELETE FROM task_reminder WHERE task_id = ?");
        $sth->bindValue(1, $this->task_id, PDO::PARAM_INT);
        $sth->execute();

        # The reminder schedule_date will be the task due date
        $form['schedule_date'] = $this->due_date;

        # Nesessary fields
        $recipient_id = isset ($form['recipient_id']) ? $form['recipient_id'] : null;
        $user_id = isset ($form['recipient_user']) ? $form['recipient_user'] : $user->getId();
        $tzone = isset ($form['tzone']) ? $form['tzone'] : 'PST';
        $alert_type = isset ($form['alert_type']) ? $form['alert_type'] : self::$SMS_TYPE;

        # Alert type dictates the "address" to use
        if ($alert_type == Reminder::$EMAIL_TYPE)
            $address = $user->getEmail();
        else if ($alert_type == Reminder::$_TYPE)
            $address = 'popup';
        else
        {
            $address = $user->getPhone() . "@" . Reminder::GetSMSGateway($user_id);
        }
        # Get customer name
        list($customer_name) = $this->dbh->query("select office_name FROM corporate_office WHERE office_id = {$this->customer_id}")->fetch(PDO::FETCH_NUM);
        $form['message'] = "<div class='ev_pub' style='font-size: 9pt;'>" . htmlentities("Task Reminder\nCustomer: {$customer_name}\nAction: {$this->task_action}", ENT_QUOTES) . "</div>";

        # Build an array of recipients (only one for now)
        $recipients['recipients'][] = array(
            'id' => $recipient_id,
            'reminder_id' => $this->reminder->getVar('id'),
            'alert_type' => $alert_type,
            'user_id' => $user_id,
            'address' => $address,
            'tzone' => $tzone
        );

        $this->reminder->copyFromArray($form);
        $this->reminder->save($form);
        $this->reminder->saveRecipients($recipients);

        # Join reminder and task
        $sth = $this->dbh->prepare("INSERT INTO task_reminder (task_id, reminder_id) VALUES (?,?)");
        $sth->bindValue(1, $this->task_id, PDO::PARAM_INT);
        $sth->bindValue(2, $this->reminder->getVar('id'), PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Set customer and opportunity name
     */
    public function SetRefNames()
    {
        $cust_url = "salesmanagement.php?object=SalesCustomer&entry=";
        $opp_url = "salesmanagement.php?object=SalesOpportunity&entry=";

        if ($this->customer_id)
        {
            $sth = $this->dbh->prepare("SELECT office_name FROM corporate_office WHERE office_id = ?");
            $sth->bindValue(1, $this->customer_id, PDO::PARAM_INT);
            $sth->execute();
            $office_name = $sth->fetchColumn();

            $customer_name = "<a href='{$cust_url}{$this->customer_id}'";
            $customer_name .= " alt='View Lead Record' title='View Lead Record'>";
            $customer_name .= "{$office_name}</a>";

            $this->customer_name = $customer_name;
        }

        if ($this->opportunity_id)
        {
            $sth = $this->dbh->prepare("SELECT name FROM sales_opportunity WHERE opportunity_id = ?");
            $sth->bindValue(1, $this->opportunity_id, PDO::PARAM_INT);
            $sth->execute();
            $name = $sth->fetchColumn();

            $opportunity_name = "<a href='{$opp_url}{$this->opportunity_id}'";
            $opportunity_name .= " alt='View Opportunity Record' title='View Opportunity Record'>";
            $opportunity_name .= "{$name}</a>";

            $this->opportunity_name = $opportunity_name;
        }
    }

    /*
     * Changes one field in the database and reloads the object.
     *
     * @param string $field
     * @param mixed $value
     */
    public function change($field, $value)
    {
        global $user;

        $now = time();

        $id = (@property_exists($this, $this->p_key)) ? $this->{$this->p_key} : 0;

        if ($id)
        {
            $sth = $this->dbh->prepare("UPDATE {$this->db_table} SET {$field} = ?, last_mod_by = ?, last_mod_date = ? WHERE {$this->p_key} = ?");
            $sth->bindValue(1, $value);
            $sth->bindValue(2, (int) $user->getId(), PDO::PARAM_INT);
            $sth->bindValue(3, (int) $now, PDO::PARAM_INT);
            $sth->bindValue(4, $id, PDO::PARAM_INT);
            $sth->execute();
            $this->{$field} = $value;
        }
        else
        {
            throw new Exception('Cannot update a non-existant record.');
        }
    }

    /**
     * Handel SalesCustomer Delete event
     */
    static public function HandleCustomerDelete($customer)
    {
        $dbh = DataStor::getHandle();

        /*
         * Remove task records
         */
        $sth = $dbh->prepare("DELETE FROM task WHERE customer_id = ?");
        $sth->bindValue(1, $customer->getId(), PDO::PARAM_INT);
        $sth->execute();
    }
}
?>