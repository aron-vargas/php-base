<?php

/**
 * @package Freedom
 */

# We will need to know what status defines a closed status
# ISSUE_CLOSED needs to set to the appropiate DB index
define('ISSUE_CLOSED', 3);

require_once ('include/textfuncs.inc');
require_once ('classes/Validation.php');

/**
 * @author Aron Vargas
 * @package Freedom
 */
class Issue {
    public $dbh = null;

    public $issue_id = null;			# int4
    public $open_by = 0;				# int4
    public $open_by_name = null;		# string
    public $open_date = 0;			# int4
    public $updated_by = 0;			# int4
    public $updated_by_name = null;	#string
    public $updated_at_date = 0;		# int4
    public $closed_by = 0;			# int4
    public $cloded_by_name = null;	#string
    public $closed_date = 0;			# int4
    public $office_id = 0;			# int
    public $office_name = null;		# string
    public $factility_id = 0;		# int
    public $facility_name = null;	# string
    public $accounting_id = null;	# string
    public $corporate_office_id = 0;	# int4 DEFAULT 0
    public $priority = 10;			# int4 DEFAULT 0
    public $priority_text = null;	# string
    public $category = 0;			# int4 DEFAULT 0
    public $category_text = null;	# string
    public $issue_status = 10;		# int4 DEFAULT 0
    public $status_text = null;		# string
    public $subject = null;			# string
    public $assigned_to = 0;			# int4
    public $assigned_to_name = null;	# string
    public $office_issue = true;		# string
    public $due_date;					# integer
    public $notes = array();			# array object Note

    public $mod_fields = '';			#string

    public $patient_facility_id = 0;          # int
    public $patient_facility_name = null;     # string
    public $patient_facility_cust_id = null;  # string

    public $complaint_id = 0;			#int
    public $attachments = array();

    /**
     * Creates a new Issue object.
     *
     * @param integer $id
     */
    public function __construct($issue_id = null)
    {
        $this->dbh = DataStor::getHandle();
        $this->issue_id = $issue_id;
        $this->load();
    }


    /**
     * Changes one field in the database and reloads the object.
     *
     * @param string $field
     * @param mixed $value
     */
    public function change($field, $value)
    {
        if ($this->id)
        {
            $sth = $this->dbh->prepare("UPDATE issue SET $field = ? WHERE issue_id = ?");
            $sth->bindValue(1, $value);
            $sth->bindValue(2, $this->issue_id, PDO::PARAM_INT);
            $sth->execute();
            $this->load();
        }
        else
        {
            throw new Exception('Cannot update a non-existant record.');
        }
    }


    /**
     * Set class property matching the array key
     *
     * @param array $new
     */
    public function copyFromArray($new = array())
    {
        foreach ($new as $key => $value)
        {
            if (@property_exists($this, $key))
            {
                if (is_array($value))
                    $this->{$key} = $value;
                else
                {
                    $new_val = trim($value);
                    if ($this->issue_id > 0 && $this->{$key} != $new_val)
                    {
                        if ($key == 'priority')
                            $this->mod_fields .= "<span style='font-size:x-small;'>Priority: {$this->priority_text} => " . self::GetPriorityText($new_val) . "</span><br />";
                        if ($key == 'category')
                            $this->mod_fields .= "<span style='font-size:x-small;'>Category: {$this->category_text} => " . self::GetCategoryText($new_val) . "</span><br />";
                        if ($key == 'issue_status')
                            $this->mod_fields .= "<span style='font-size:x-small;'>Status: {$this->status_text} => " . self::GetStatusText($new_val) . "</span><br />";
                        if ($key == 'subject')
                            $this->mod_fields .= "<span style='font-size:x-small;'>Subject was changed</span><br />";
                    }
                    $this->{$key} = $new_val;
                }
            }
        }

        // office_id key will be passed as 'entry'
        if (isset($new['entry']))
            $this->office_id = $new['entry'];
    }


    /**
     * Returns the class Property value defined by $var.
     *
     * @param string $var
     * @return mixed
     */
    public function getVar($var = null)
    {
        $ret = null;
        if (@property_exists($this, $var))
        {
            $ret = htmlentities($this->{$var}, ENT_QUOTES);
        }
        return $ret;
    }


    /**
     * Returns the database id.
     *
     * @param integer $edit
     * @return integer
     */
    public function getId($edit = 0)
    {
        $issue_id = (int) $this->issue_id;

        if ($edit)
            $issue_id = "<input type=\"hidden\" name=\"issue_id\" value=\"{$issue_id}\" />";

        return $issue_id;
    }


    /**
     * Returns Formated Date String represented by open_date value
     *
     * @return string
     */
    public function getOpenDateString($format = 'D, M d Y h:i A')
    {
        return date($format, $this->open_date);
    }


    /**
     * Returns Formated Date String represented by updated_at_date value
     *
     * @return string
     */
    public function getLastModDateString($format = 'D, M d Y h:i A')
    {
        return date($format, $this->updated_at_date);
    }


    /**
     * Returns Formated Date String represented by closed_date value
     *
     * @return string
     */
    public function getClosedDateString($format = 'D, M d Y h:i A')
    {
        return date($format, $this->closed_date);
    }


    /**
     * Populates this object from the matching record in the
     * database.
     */
    public function load()
    {
        global $page;

        if ($this->issue_id)
        {
            $sth = $this->dbh->prepare("
				SELECT open_by,
				       u_o.firstname || ' ' ||	u_o.lastname AS open_by_name,
				       open_date,
				       i.updated_by,
				       u_l.firstname || ' ' ||	u_l.lastname AS updated_by_name,
				       updated_at_date,
				       closed_by,
				       u_c.firstname || ' ' || u_c.lastname AS closed_by_name,
				       closed_date,
					   due_date,
				       i.office_id,
				       i.corporate_office_id,
				       i.priority,
				       priority_text,
				       i.category,
				       category_text,
				       i.issue_status,
				       status_text,
				       subject,
				       assigned_to,
				       office_issue,
				       u_a.firstname || ' ' || u_a.lastname AS assigned_to_name,
				       i.patient_facility_id
				FROM issue i
				  LEFT JOIN users u_o ON i.open_by = u_o.id
				  LEFT JOIN users u_l ON i.updated_by = u_l.id
				  LEFT JOIN users u_c ON i.closed_by = u_c.id
				  LEFT JOIN users u_a ON i.closed_by = u_a.id
				  LEFT JOIN issue_priority p ON i.priority = p.priority
				  LEFT JOIN issue_status s ON i.issue_status = s.status_id
				  LEFT JOIN issue_category c ON i.category = c.category_id
				WHERE issue_id = {$this->issue_id}");
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                foreach ($row as $key => $value)
                {
                    $this->{$key} = $value;
                }
            }
            else
            {
                echo '
				<div class="error">
					<h1>Missing record.</h1>
					<p class="error">The Issue you are trying to view does not exist. <a href="javascript:window.close();">Close Window</a></p>
				</div>';
                $page->printSpareFooter();
                exit;
            }

            if ($this->office_issue)
            {
                $sql = 'SELECT office_name FROM corporate_office WHERE office_id = ?';
            }
            else
            {
                $sql = 'SELECT name AS office_name, cust_id FROM v_customer_entity WHERE id = ?';
            }

            # Depending on issue type find the office_name
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->office_name = $row['office_name'];
                $this->accounting_id = isset($row['cust_id']) ? $row['cust_id'] : null;
            }

            # Find complaint form for this issue
            $sql = "SELECT complaint_id FROM complaint_form WHERE issue_id = ?";
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, (int) $this->issue_id, PDO::PARAM_INT);
            $sth->execute();
            list($this->complaint_id) = $sth->fetch(PDO::FETCH_NUM);

            if ($this->patient_facility_id)
            {
                $sth = $this->dbh->prepare('
					SELECT facility_name, accounting_id FROM facilities WHERE id = ?');
                $sth->bindValue(1, $this->patient_facility_id, PDO::PARAM_INT);
                $sth->execute();
                list($this->patient_facility_name, $this->patient_facility_cust_id) = $sth->fetch(PDO::FETCH_NUM);
            }

            # Load attachments
            $this->LoadAttachments();
        }
    }

    /**
     * Fill attachment array
     * @param bool
     */
    public function LoadAttachments($reload = false)
    {
        if ($reload || count($this->attachments) == 0)
        {
            # Reset the array
            $this->attachments = array();

            $sth = $this->dbh->prepare("SELECT *
			FROM attachment
			WHERE reference_elem = 'Issue'
			AND reference_key = {$this->issue_id}
			ORDER BY tstamp DESC");
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $att = new Attachment();
                $att->copyFromArray($row);

                $this->attachments[] = $att;
            }
        }
    }

    /**
     * Build WHERE clause for lead search from args
     *
     * @param array
     * @param string
     *
     * @return string
     */
    static public function ParseAdvanceSearch($args)
    {
        global $dbh;


        # Integer fields should not be typecast to text
        $int_fields = array('i.issue_id ', 'i.office_id', 'i.corporate_office_id');
        $date_fields = array('i.open_date', 'i.updated_at_date', 'i.closed_date', "i.due_date");

        # All Valid SM status
        $WHERE = "";

        if ($args['search_fields'])
        {
            foreach ($args['search_fields'] as $idx => $field)
            {
                $is_int = in_array($field, $int_fields);
                $is_date = in_array($field, $date_fields);

                $op = $args['operators'][$idx];

                $string = trim(urldecode($args['strings'][$idx]));
                if (strtoupper($string) == 'YES')
                    $string = "YES";
                if (strtoupper($string) == 'NO')
                    $string = "NO";

                if ($is_date && strtotime($string))
                    $date_str = date('Y-m-d', strtotime($string));
                else
                    $is_date = false;

                switch ($op)
                {
                    case 'sw':
                        $WHERE .= "\n AND {$field}::text ilike " . $dbh->quote("$string%");
                        break;
                    case 'ew':
                        $WHERE .= "\n AND {$field}::text ilike " . $dbh->quote("%$string");
                        break;
                    case 'eq':
                        if ($is_int)
                            $WHERE .= "\n AND $field = " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND to_timestamp({$field})::Date = " . $dbh->quote($date_str);
                        else
                            $WHERE .= "\n AND upper($field::text) = " . $dbh->quote(strtoupper($string));
                        break;
                    case 'nq':
                        if ($is_int)
                            $WHERE .= "\n AND $field <> " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND to_timestamp({$field})::Date != {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND upper($field::text) <> " . $dbh->quote(strtoupper($string));
                        break;
                    case 'gt':
                        if ($is_int)
                            $WHERE .= "\n AND $field > " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND to_timestamp({$field})::Date > {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND $field::text > " . $dbh->quote($string);
                        break;
                    case 'lt':
                        if ($is_int)
                            $WHERE .= "\n AND $field < " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND to_timestamp({$field})::Date < {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND $field::text < " . $dbh->quote($string);
                        break;
                    default:
                        if ($is_date)
                            $WHERE .= "\n AND to_timestamp({$field})::Date = " . $dbh->quote($date_str);
                        else
                            $WHERE .= "\n AND {$field}::text ilike " . $dbh->quote("%$string%");
                        break;
                }
            }
        }
        //echo "<pre>$WHERE</pre>";
        return $WHERE;
    }

    /**
     * Build WHERE clause for lead search from args
     *
     * @param array
     *
     * @return string
     */
    static public function ParseSimpleSearch($args)
    {
        global $dbh;

        $WHERE = "";

        if ($args['search'])
        {
            if (preg_match('/^#/', $args['search']))
                $WHERE = " AND i.issue_id = " . (int) substr($args['search'], 1);
            else
            {
                $WHERE = "AND (";
                $strings = explode(",", $args['search']);
                $OR = ""; # Dont add keyword OR in the first element
                foreach ($strings as $str)
                {
                    $str = trim(urldecode($str));
                    $time = (preg_match('/[\-\/]/', $str)) ? strtotime($str) : 0;
                    $int = (int) $str;
                    if ($int)
                    {
                        $WHERE .= " $OR i.issue_id = $int";
                        $WHERE .= " OR i.office_id = $int";
                        $WHERE .= " OR i.corporate_office_id = $int";
                        $OR = "OR"; # Use OR for remaining elements
                    }
                    if ($str)
                    {
                        $WHERE .= " $OR i.subject ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR u_o.firstname ILIKE " . $dbh->quote("$str%");
                        $WHERE .= " OR u_o.lastname ILIKE " . $dbh->quote("$str%");
                        #$WHERE .= " OR u_l.firstname ILIKE ".$dbh->quote("%$str%");
                        #$WHERE .= " OR u_l.lastname ILIKE ".$dbh->quote("%$str%");
                        #$WHERE .= " OR u_c.firstname ILIKE ".$dbh->quote("%$str%");
                        #$WHERE .= " OR u_c.lastname ILIKE ".$dbh->quote("%$str%");
                        #$WHERE .= " OR u_a.firstname ILIKE ".$dbh->quote("%$str%");
                        #$WHERE .= " OR u_a.lastname ILIKE ".$dbh->quote("%$str%");
                        $WHERE .= " OR p.priority_text ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR s.status_text ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR c.category_text ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR coalesce(f.accounting_id, o.account_id) ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR coalesce(f.facility_name, o.office_name) ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR cp.account_id ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR cp.office_name ILIKE " . $dbh->quote("%$str%");
                        $OR = "OR"; # Use OR for remaining elements
                    }
                    if ($time)
                    {
                        $date_str = date('Y-m-d', $time);
                        $WHERE .= " OR to_timestamp(i.open_date)::Date = '$date_str'";
                        $WHERE .= " OR to_timestamp(i.updated_at_date)::Date = '$date_str'";
                        $WHERE .= " OR to_timestamp(i.closed_date)::Date = '$date_str'";
                        $WHERE .= " OR to_timestamp(i.due_date)::Date = '$date_str'";
                        $OR = "OR"; # Use OR for remaining elements
                    }
                }
                $WHERE .= ")\n";
            }
        }

        return $WHERE;
    }


    /**
     * Saves the contents of this object to the database. If this object
     * has an id, the record will be UPDATE'd.  Otherwise, it will be
     * INSERT'ed
     *
     * @param array $new
     */
    public function save($new = array())
    {
        global $user;

        $timestamp = time();

        # Do some data validation for fields we want to control behind the scenes

        # Set open_by and date if not set
        if ($this->open_by < 1)
        {
            $this->open_by = $user->getId();
            $this->open_by_name = $user->getName();
            $this->open_date = $timestamp;
        }

        # Set updated_at on every save
        $this->updated_by = $user->getId();
        $this->updated_by_name = $user->getName();
        $this->updated_at_date = $timestamp;

        # Set closed_by and date is issue_status is CLOSED
        # ISSUE_CLOSED needs to set to the appropiate DB index
        if ($this->issue_status == ISSUE_CLOSED)
        {
            # Dont override prior values
            if ($this->closed_by == 0)
            {
                $this->closed_by = $user->getId();
                $this->closed_by_name = $user->getName();
                $this->closed_date = $timestamp;
            }
        }
        else
        {
            $this->closed_by = 0;
            $this->closed_by_name = '';
            $this->closed_date = 0;
        }

        if ($this->issue_id)
        {
            $sth = $this->dbh->prepare("
				UPDATE issue
				SET open_by = ?,
				    open_date = ?,
				    updated_by = ?,
				    updated_at_date = ?,
				    closed_by = ?,
				    closed_date = ?,
				    office_id = ?,
				    corporate_office_id = ?,
				    priority = ?,
				    category = ?,
				    issue_status = ?,
				    subject = ?,
				    assigned_to = ?,
				    office_issue = ?,
				    patient_facility_id = ?,
					due_date = ?
				WHERE issue_id = ?");
            $sth->bindValue(1, (int) $this->open_by, PDO::PARAM_INT);
            $sth->bindValue(2, (int) $this->open_date, PDO::PARAM_INT);
            $sth->bindValue(3, (int) $this->updated_by, PDO::PARAM_INT);
            $sth->bindValue(4, (int) $this->updated_at_date, PDO::PARAM_INT);
            $sth->bindValue(5, (int) $this->closed_by, PDO::PARAM_INT);
            $sth->bindValue(6, (int) $this->closed_date, PDO::PARAM_INT);
            $sth->bindValue(7, (int) $this->office_id, PDO::PARAM_INT);
            $sth->bindValue(8, (int) $this->corporate_office_id, PDO::PARAM_INT);
            $sth->bindValue(9, (int) $this->priority, PDO::PARAM_INT);
            $sth->bindValue(10, (int) $this->category, PDO::PARAM_INT);
            $sth->bindValue(11, (int) $this->issue_status, PDO::PARAM_INT);
            $sth->bindValue(12, $this->subject, PDO::PARAM_STR);
            $sth->bindValue(13, (int) $this->assigned_to, PDO::PARAM_INT);
            $sth->bindValue(14, (int) $this->office_issue, PDO::PARAM_BOOL);
            $sth->bindValue(15, (int) $this->patient_facility_id, PDO::PARAM_INT);
            $sth->bindValue(16, (int) $this->due_date, PDO::PARAM_INT);
            $sth->bindValue(17, (int) $this->issue_id, PDO::PARAM_INT);
        }
        else
        {
            $sth = $this->dbh->prepare("
				INSERT INTO issue (
				  open_by, open_date, updated_by, updated_at_date,
				  closed_by, closed_date, office_id, corporate_office_id,
				  priority, category, issue_status, subject, assigned_to,
				  office_issue, patient_facility_id, due_date)
			 	VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $sth->bindValue(1, (int) $this->open_by, PDO::PARAM_INT);
            $sth->bindValue(2, (int) $this->open_date, PDO::PARAM_INT);
            $sth->bindValue(3, (int) $this->updated_by, PDO::PARAM_INT);
            $sth->bindValue(4, (int) $this->updated_at_date, PDO::PARAM_INT);
            $sth->bindValue(5, (int) $this->closed_by, PDO::PARAM_INT);
            $sth->bindValue(6, (int) $this->closed_date, PDO::PARAM_INT);
            $sth->bindValue(7, (int) $this->office_id, PDO::PARAM_INT);
            $sth->bindValue(8, (int) $this->corporate_office_id, PDO::PARAM_INT);
            $sth->bindValue(9, (int) $this->priority, PDO::PARAM_INT);
            $sth->bindValue(10, (int) $this->category, PDO::PARAM_INT);
            $sth->bindValue(11, (int) $this->issue_status, PDO::PARAM_INT);
            $sth->bindValue(12, $this->subject, PDO::PARAM_STR);
            $sth->bindValue(13, (int) $this->assigned_to, PDO::PARAM_INT);
            $sth->bindValue(14, (int) $this->office_issue, PDO::PARAM_BOOL);
            $sth->bindValue(15, (int) $this->patient_facility_id, PDO::PARAM_INT);
            $sth->bindValue(16, (int) $this->due_date, PDO::PARAM_INT);
        }
        $sth->execute();
        if (!$this->issue_id)
        {
            $this->issue_id = $this->dbh->lastInsertId('issue_issue_id_seq');
        }

        # If we have a new note to add to this issue
        if (isset($new['new_note']) && strlen($new['new_note']) > 0)
        {
            # Dont save note when complaint button is clicked
            if (!isset($new['open_complaint']))
            {
                $new_note = new Note();
                if ($this->mod_fields)
                    $new['new_note'] = $this->mod_fields . $new['new_note'];
                if (isset($new['send_email']))
                {
                    $new_note->setEmailSent(time());
                    $new['new_note'] = "<span style='font-size:x-small;'>Email Sent On {$new_note->getEmailSentDateString()} To: {$new['email_to']}</span><br />\n" . $new['new_note'];
                }
                $new_note->copyFromArray($new);
                $new_note->setIssueId($this->issue_id);
                $new_note->save();
            }
        }
        # No new_note to add so create simple note to record recipient and time sent
        else if (isset($new['send_email']))
        {
            $new_note = new Note();
            $new_note->setEmailSent(time());
            $new['new_note'] = "<span style='font-size:x-small;'>Email Sent On {$new_note->getEmailSentDateString()} To: {$new['email_to']}</span>";
            $new_note->copyFromArray($new);
            $new_note->setIssueId($this->issue_id);
            $new_note->save();
        }

        # Adding a complaint form to this issue
        if (isset($new['open_complaint']) && !$this->office_issue)
        {
            # Issue id wont be set untill issue is saved
            $complaint = new ComplaintForm($this->issue_id);
            $new_ary['issue_id'] = $this->issue_id;
            $new_ary['facility_id'] = $this->office_id;
            $complaint->copyFromArray($new_ary);
            $complaint->save();

            if ($complaint->has_equipment == false)
            {
                $additional_note = str_replace(array('&nbsp;', '<br>'), array(' ', "\n"), ($new['new_note']));

                # Insert a bare bones record and return json array
                $equip = new ComplaintFormEquipment();
                $equip->setVar('issue_id', $this->issue_id);
                $equip->setVar('asset_type', 'Device');
                $equip->setVar('complaint', 'Parts Fix');
                $equip->setVar('additional_note', $additional_note);
                $equip->setVar('order_status', Order::$EDITING);
                $equip->save();
            }
        }

        if (isset($new['attachment_id']))
        {
            $att = new Attachment($new['attachment_id']);
            $att->copyFromArray($new);
            $att->save();

            $this->LoadAttachments(true);
        }
    }

    /**
     * Return query string for document search
     *
     * @param string $doc_type
     */
    static public function SearchSQL($search_type, $params)
    {
        global $preferences;

        $dbh = DataStor::getHandle();

        $ORDER = (isset($params['order'])) ? $params['order'] : "";
        $DIR = (isset($params['dir'])) ? $params['dir'] : "";
        $LIMIT = 0;
        $OFFSET = 0;
        if (isset($params['page']))
        {
            $LIMIT = $preferences->get('general', 'results_per_page');
            $OFFSET = ($params['page'] - 1) * $LIMIT;
        }

        $FILTER = "";
        if ($search_type == 'simple')
            $FILTER = self::ParseSimpleSearch($params);
        else if ($search_type == 'advanced')
            $FILTER = self::ParseAdvanceSearch($params);

        $sql = "SELECT
			i.issue_id as document_id,
			i.open_by,
			to_timestamp(i.open_date)::date as open_date,
			i.updated_by,
			to_timestamp(i.updated_at_date)::date as updated_at_date,
			i.closed_by,
			to_timestamp(i.closed_date)::date as closed_date,
			i.office_id,
			i.corporate_office_id,
			i.priority,
			i.category,
			i.issue_status,
			i.subject,
			i.assigned_to,
			i.office_issue,
			i.due_date,
			u_o.firstname as open_by_first,
			u_o.lastname as open_by_last,
			u_l.firstname as updated_by_first,
			u_l.lastname as updated_by_last,
			u_c.firstname as closed_by_first,
			u_c.lastname as closed_by_last,
			u_a.firstname as assigned_to_first,
			u_a.lastname as assigned_to_last,
			p.priority_text,
			s.status_text,
			c.category_text,
			coalesce(f.accounting_id, o.account_id) as cust_id,
			coalesce(f.facility_name, o.office_name) as cust_name,
			cp.account_id as corp_parent_id,
			cp.office_name as corp_parent_name,
			COUNT(*) OVER() as total_rows
		FROM issue i
		LEFT JOIN users u_o ON i.open_by = u_o.id
		LEFT JOIN users u_l ON i.updated_by = u_l.id
		LEFT JOIN users u_c ON i.closed_by = u_c.id
		LEFT JOIN users u_a ON i.assigned_to = u_a.id
		LEFT JOIN issue_priority p ON i.priority = p.priority
		LEFT JOIN issue_status s ON i.issue_status = s.status_id
		LEFT JOIN issue_category c ON i.category = c.category_id
		LEFT JOIN facilities f on i.office_id = f.id AND i.office_issue = false
		LEFT JOIN corporate_office o on i.office_id = o.office_id AND i.office_issue = true
		LEFT JOIN corporate_office cp on i.corporate_office_id = cp.office_id
		WHERE true ";

        # Search for office records
        # Match on any office name in the company
        #
        $sql .= $FILTER;

        // Order the results
        if ($ORDER)
            $sql .= "\nORDER BY $ORDER $DIR";

        // Page the results
        if ($LIMIT)
            $sql .= "\nLIMIT $LIMIT OFFSET $OFFSET";

        //echo "<pre>$sql</pre>";
        return $sql;
    }

    /**
     * Deletes this issue and its notes from the database.
     */
    public function delete()
    {
        $sth = $this->dbh->prepare('DELETE FROM note WHERE issue_id = ?');
        $sth->bindValue(1, $this->issue_id, PDO::PARAM_INT);
        $sth->execute();

        $sth = $this->dbh->prepare('DELETE FROM issue WHERE issue_id = ?');
        $sth->bindValue(1, $this->issue_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     *
     * @param array $form
     * @return string html text for editing an issue
     */
    public function showForm($form = array())
    {
        global $user, $sh, $preferences;

        # First define the previos notes rows
        $bg = "#f0f0ff";
        $previous_notes = "";
        $all_involved = array();
        if ($this->issue_id > 0)
        {
            $previous_notes .= "
			<tr class='form_section'>
				<th align='left' colspan='6'>Previous Notes</th>
			</tr>";

            $sth = $this->dbh->prepare("SELECT note_id, author FROM note WHERE issue_id = {$this->issue_id} ORDER BY creation_date DESC");
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $bg = ($bg == "#f7f7ff") ? "#f0f0ff" : "#f7f7ff";
                $note = new Note($row['note_id']);

                $previous_notes .= "
			<tr>
				<td class='form' align='left' colspan='6' width='700' style='background-color:{$bg}'>
					<div>By <b>{$note->getVar('author_name')}</b> On <b>{$note->getCreationDateString()}</b></div>
					<div>{$note->getContents()}</div>
				</td>
			</tr>";
                $all_involved[] = $row['author'];
            }
        }

        $date_format = $preferences->get('general', 'dateformat');
        $calendar_format = str_replace(array('Y', 'd', 'm', 'M'), array('%Y', '%d', '%m', '%b'), $date_format);

        # Values for hidden properties.
        # Precedence 1) our value 2) posted array value 3) default value
        $office_id = ($this->office_id > 0) ? $this->office_id : (isset($form['office_id']) ? $form['office_id'] : 0);
        $object = (isset($form['object'])) ? $form['object'] : 'office';
        $corporate_office_id = ($this->corporate_office_id > 0) ? $this->corporate_office_id : (isset($form['corporate_office_id']) ? $form['corporate_office_id'] : 0);
        $patient_facility_id = ($this->patient_facility_id > 0) ? $this->patient_facility_id : (isset($form['patient_facility_id']) ? $form['patient_facility_id'] : 0);
        $office_name = ($this->office_name) ? $this->office_name : (isset($form['office_name']) ? $form['office_name'] : '');
        $entity_type = 0;
        if (isset($form['office_id']))
        {
            $sth = $this->dbh->prepare("SELECT cust_id, entity_type FROM v_customer_entity WHERE id = ?");
            $sth->bindValue(1, $form['office_id'], PDO::PARAM_INT);
            $sth->execute();
            list($this->accounting_id, $entity_type) = $sth->fetch(PDO::FETCH_NUM);
        }
        else if ($this->office_id)
        {
            $sth = $this->dbh->prepare("SELECT entity_type FROM v_customer_entity WHERE id = ?");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            list($entity_type) = $sth->fetch(PDO::FETCH_NUM);
        }
        $patient_access = ($entity_type == CustomerEntity::$ENTITY_PATIENT)
            ? ($sh->isOnLAN() && $user->hasAccessToApplication('hippa_access'))
            : true;

        # Default values for the adjustable properties.
        $issue_status = ($this->issue_status > 0) ? $this->issue_status : 1;
        $category = ($this->category > 0) ? $this->category : 1;
        $priority = ($this->priority > 0) ? $this->priority : 1;
        $office_issue = (!$this->office_issue) ? 0 : (($object == 'facility') ? 0 : 1);

        # Start a timer to log length of time spent on the issue
        $timer_start = time();

        # Only show "by" rows if they have been set
        # No inputs needed we derive them at save()
        $open_by = ($this->open_by > 0) ? "		<tr><th class='form'>Created by</th><td class='form'>{$this->getVar('open_by_name')}</td><th class='form' colspan='4'>{$this->getOpenDateString()}</th></tr>" : "";
        $updated_by = ($this->updated_by > 0) ? "		<tr><th class='form'>Last&nbsp;Modified&nbsp;by</th><td class='form'>{$this->getVar('updated_by_name')}</td><th class='form' colspan='4'>{$this->getLastModDateString()}</th></tr>" : "";
        $closed_by = ($this->closed_by > 0) ? "		<tr><th class='form'>Closed by</th><td class='form'>{$this->getVar('closed_by_name')}</td><th class='form' colspan='4'>{$this->getClosedDateString()}</th></tr>" : "";

        $header = ($this->issue_id > 0) ? "Issue #{$this->issue_id}" : "New Issue";
        $header .= " for ";
        if ($patient_access)
            $header .= htmlentities($office_name, ENT_QUOTES);
        $header .= isset($this->accounting_id) ? " ({$this->accounting_id})" : "";

        $email_to = "";
        $email_cc = "";

        # Get the CPM for facilities and Account Executive for offices
        # Include all who have contributed to this issue in the To line
        if ($office_id > 0)
        {
            $all = (count($all_involved) > 0) ? " OR id IN(" . implode(',', $all_involved) . ")" : "";

            if ($office_issue)
                $sql = "SELECT email FROM users WHERE id = (SELECT account_executive FROM corporate_office WHERE office_id = {$office_id}){$all}";
            else
                $sql = "SELECT email FROM users WHERE id = (SELECT cpt_id FROM v_customer_entity WHERE id = {$office_id}){$all}";

            $sth = $this->dbh->prepare($sql);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                # Attempt to confirm this is a real email address with checking for @
                if (strstr($row['email'], '@'))
                    $email_to .= $row['email'] . ",";
            }
        }
        if ($email_to)
            $email_to = substr($email_to, 0, -1); // Remove trailing ,


        # By default check send email keep uncheck on normal Save
        $show_to_cc = "";
        $check_email = "checked";
        $save_action = "Save & Email";
        if (isset($form['save_action']) && $form['save_action'] == 'Save')
        {
            $show_to_cc = "style='display:none'";
            $check_email = "";
            $save_action = "Save";
        }

        $has_complaint_permission = ($user->inPermGroup(User::$CUSTOMER_SUPPORT) && $patient_access) ? 1 : 0;
        $has_complaint = ($this->complaint_id > 0) ? 1 : 0;

        # Hide the button when the category is not Complaint
        $c_b_style = "style='display: none;'";
        if (preg_match('/complaint/i', $this->category_text) && ($has_complaint || $has_complaint_permission))
            $c_b_style = "";

        # Add Complaint button if this is a facility issue make sure to disable sending email since the form will be submitted.
        # Also open popup if Complaint Form button was clicked
        #
        $OpenCCFormJS = "";
        $previous_note = "";
        if ($office_issue)
        {
            $c_b_onclick = "";
        }
        else
        {
            if ($has_complaint)
                $c_b_onclick = "onClick=\"OpenComplaintWindow('submit_action=edit_cf&issue_id={$this->issue_id}'); this.blur();\"";
            else
                $c_b_onclick = "onClick='return NewComplaint();'";
        }

        if (isset($form['open_complaint']))
        {
            $OpenCCFormJS = "OpenComplaintWindow('submit_action=edit_cf&issue_id={$this->issue_id}');";
            $c_b_onclick = "onClick=\"OpenComplaintWindow('submit_action=edit_cf&issue_id={$this->issue_id}'); this.blur();\"";

            if ($form['new_note'])
                $previous_note = $form['new_note'];
        }


        $issue_form = "
<script type=\"text/javascript\">
var has_complaint = {$has_complaint};
var has_complaint_permission = {$has_complaint_permission};

YAHOO.util.Event.onDOMReady(InitIssue);

function InitIssue()
{
	InitEditor();

	InitRecipientDialog();

	{$OpenCCFormJS}
}
</script>
	<form name='issue_form' action='{$_SERVER['PHP_SELF']}' method='post' onSubmit=\"UpdateOpener({$this->issue_id});\">
	<input type='hidden' name='issue_id' value='{$this->issue_id}'/>
	<input type='hidden' name='object' value='{$object}'/>
	<input type='hidden' name='office_id' value='{$office_id}'/>
	<input type='hidden' name='office_issue' value='{$office_issue}'/>
	<input type='hidden' name='corporate_office_id' value='{$corporate_office_id}'/>
	<input type='hidden' name='patient_facility_id' value='{$patient_facility_id}'>
    <input type='hidden' name='timer_start' value='{$timer_start}'>
	<input type='hidden' name='open_complaint' value='1' disabled />
	<input type='hidden' name='submit_action' value='save'/>
	<table class='form' cellpadding='5' cellspacing='2' style='width:700px;'>
		<tr>
			<th class='subheader' colspan='6'>$header</th>
		</tr>
		<tr>
			<th class='form'>Status</th>
			<td class='form'>
				<select name='issue_status'>
					" . self::CreateStatusList($issue_status) . "
				</select>
			</td>
			<th class='form'>Category</th>
			<td class='form'>
				<select name='category' OnChange='SetComplaint(this.form);'>
					" . self::createCategoryList($category) . "
				</select>
			</td>
			<th class='form'>Priority</th>
			<td class='form'>
				<select name='priority'>
					" . self::CreatePriorityList($priority) . "
				</select>
			</td>
		</tr>
		{$open_by}
		{$updated_by}
		{$closed_by}
		<tr>
			<th class='form'>Send Email</th>
			<td class='form' colspan='5'>
				<input type='checkbox' name='send_email' value='1' onClick=\"ShowEmailInputs()\" {$check_email}/>
				<img id='recip_btn' src='images/search-mini.png' height=15 style='cursor:pointer;' />
			</td>
		</tr>
		<tr id='to' {$show_to_cc}>
			<th class='form'>To</th>
			<td class='form' colspan='5'>
				<input type='text' name='email_to' value='{$email_to}' size='50' onKeyPress=\"return EntertoTab(event, this.form.email_cc);\"/>
			</td>
		</tr>
		<tr id='cc' {$show_to_cc}>
			<th class='form'>CC</th>
			<td class='form' colspan='5'>
				<input type='text' name='email_cc' value='{$email_cc}' size='50' onKeyPress=\"return EntertoTab(event, this.form.subject);\"/>
			</td>
		</tr>
		<tr>
			<th class='form'>Subject</th>
			<td class='form' colspan='5'>
				<input type='text' name='subject' value='{$this->getVar('subject')}' size='50' maxlength='255' onKeyPress=\"return EntertoTab(event, document.getElementById('new_note___Frame').contentWindow);\"/>
			</td>
		</tr>
		<tr>
			<td class='form' colspan='6' style='padding: 0;'>
				<textarea id='new_note' name='new_note' rows=20 cols=90>$previous_note</textarea>
			</td>
		</tr>
		<tr>
			<td class='buttons' align='center' colspan='6'>
				<input type='button' class='submit' value='Close' onClick=\"window.close();\" />
				<input type='submit' class='submit' name='save_action' value='{$save_action}' onClick=\"return ValidateIssue();\" />
				<input type='button' class='submit' name='complaint_button' id='complaint_button' value='Complaint Form' $c_b_onclick {$c_b_style} />
			</td>
		</tr>
		{$previous_notes}
	</table>
	</form>
	<div id='recipient_dialog'>
		<div class='hd'>Email Recipient</div>
		<div class='bd recip' style='padding:0; text-align:left; font-size:small; overflow:scroll; white-space: nowrap; height:400px;'>
		{$this->EmailRecipientsList('issue_form')}
		</div>
		<div class='ft' style='text-align:left;'>Left click adds to [TO]<br/>Right click adds to [CC]</div>
	</div>";

        echo $issue_form;
    }


    /**
     *
     * @param $form array hash of submitted data from previous search
     * @return string html table containing a form for seaching for saved issues
     */
    public static function GetSearchTable($form = array())
    {
        global $preferences;

        # Set to submitted values or defaults
        $date_format = $preferences->get('general', 'dateformat');
        $calendar_format = str_replace(array('Y', 'd', 'm', 'M'), array('%Y', '%d', '%m', '%b'), $date_format);

        $entry = (isset($form['entry'])) ? $form['entry'] : 0;
        $issue_num = (isset($form['issue_num'])) ? $form['issue_num'] : '';
        $object = (isset($form['object'])) ? $form['object'] : 'office';
        $corporate_office_id = (isset($form['corporate_office_id'])) ? $form['corporate_office_id'] : 0;
        $patient_facility_id = (isset($form['patient_facility_id'])) ? $form['patient_facility_id'] : 0;
        $office_name = (isset($form['office_name'])) ? urlencode($form['office_name']) : 'Unk';
        $issue_status = (isset($form['issue_status'])) ? $form['issue_status'] : '';
        $category = (isset($form['category'])) ? $form['category'] : '';
        $priority = (isset($form['priority'])) ? $form['priority'] : '';
        $from_date = (isset($form['from_date'])) ? $form['from_date'] : '';
        $to_date = (isset($form['to_date'])) ? $form['to_date'] : '';
        $show_issues = isset($form['show_issues']) ? $form['show_issues'] : 'office';
        $sel_issue_office = "selected";
        $sel_issue_company = "";
        if ($show_issues == 'company')
        {
            $sel_issue_office = "";
            $sel_issue_company = "selected";
        }

        $search_table = "
	<form name='search_form' action='{$_SERVER['PHP_SELF']}' method='get'>
	<input type='hidden' name='action' value='view'/>
	<input type='hidden' name='entry' value='{$entry}'/>
	<input type='hidden' name='object' value='{$object}'/>
	<table class='form' cellpadding='5' cellspacing='2' style='margin:0;'>
		<tr>
			<th class='subheader' colspan='8'>Issues <span style='font-size:x-small'>(Call customer support for equipment complaints)</span></th>
		</tr>
		<tr>
			<th class='form' style='font-size:x-small'>Issue #</th>
			<td class='form'>
				<input type='text' name='issue_num' size='2' value='{$issue_num}' maxlength='12' />
			</td>
			<th class='form' style='font-size:x-small'>Status</th>
			<td class='form'>
				<select name='issue_status'>
					<option value=''>All</option>
					" . self::CreateStatusList($issue_status) . "
				</select>
			</td>
			<th class='form' style='font-size:x-small'>Category</th>
			<td class='form'>
				<select name='category'>
					<option value=''>All</option>
					" . self::createCategoryList($category, false) . "
				</select>
			</td>
			<th class='form' style='font-size:x-small'>Priority</th>
			<td class='form'>
				<select name='priority'>
					<option value=''>All</option>
					" . self::CreatePriorityList($priority) . "
				</select>
			</td>
		</tr>
		<tr>
			<th class='form' style='font-size:x-small'>Date Range</th>
			<td class='form' colspan='5' style='font-size:x-small'>
				<input type='text' name='from_date' id='from_date' size='9' value='{$from_date}' readonly>
				<img class='form_bttn' id='f_date_button' src='images/calendar-mini.png' alt='Calendar' title='Calendar'>
				<script type=\"text/javascript\">
			    Calendar.setup(
				{
			        inputField	:	\"from_date\",
			        ifFormat	:	\"{$calendar_format}\",
			        button		:	\"f_date_button\",
			        step		:	1,
			        weekNumbers	:	false
			    });
			</script>
			&nbsp;&nbsp;to&nbsp;&nbsp;
			<input type='text' name='to_date' id='to_date' size='9' value='{$to_date}' readonly>
			<img class='form_bttn' id='to_date_button' src='images/calendar-mini.png' alt='Calendar' title='Calendar'>
			<script type=\"text/javascript\">
			    Calendar.setup(
				{
			        inputField	:	\"to_date\",
			        ifFormat	:	\"{$calendar_format}\",
			        button		:	\"to_date_button\",
			        step		:	1,
			        weekNumbers	:	false
			    });
			</script>
			<img class='form_bttn' src='images/cancel.png' onClick='document.search_form.from_date.value=\"\"; document.search_form.to_date.value=\"\";' alt='Clear Dates' title='Clear Dates'>
			</td>
			<th class='form' style='font-size:x-small'>View</th>
			<td class='form' style='font-size:x-small;'>
				<select name='show_issues'>
					<option value='office'$sel_issue_office>Current Office</option>
					<option value='company'$sel_issue_company>Entire Company</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class='buttons' align='center' colspan='8'>
				<input type='submit' class='submit' name='submit_action' value='Search' />
				&nbsp;&nbsp;
				<input type='button' class='submit' value='New Issue' onClick=\"OpenIssueWindow('office_id={$entry}&corporate_office_id={$corporate_office_id}&patient_facility_id={$patient_facility_id}&office_name=$office_name&object={$object}', 'New_Issue')\" />
			</td>
		</tr>
	</table>
	</form>";

        return $search_table;

    }

    /**
     *
     * @param string $on_off
     * @return string html table row ready for insert into resultlist
     * addWholeRow()
     */
    public function GetResultRow($on_off = 'on')
    {
        # Must remove single and double quotes other methods dont work
        $long_subject = htmlentities($this->subject, ENT_QUOTES);
        $short_subject = htmlentities(substr($this->subject, 0, 30), ENT_QUOTES);
        $id_link = "<a onclick=\"OpenIssueWindow('issue_id={$this->getId()}','Edit_Issue_{$this->getId()}')\">{$this->getId()}</a>";

        $patient_facility_cust_id_text = '';
        if ($this->patient_facility_cust_id)
            $patient_facility_cust_id_text = '(' . $this->patient_facility_cust_id . ')';

        # Generate the enitre table row since we need to include id fields for later updates
        $issue_row = "
		<tr class=\"{$on_off}\">
			<td style='text-align:center;'>{$id_link}</td>
			<td style='text-align:center;'>{$this->getVar('office_name')}</td>
			<td style='text-align:center;'>{$this->getOpenDateString('m/d/y')}</td>
			<td style='text-align:center;'>{$this->getVar('open_by_name')}</td>
			<td style='text-align:center;' id=\"issue_{$this->getId()}_status\">{$this->getVar('status_text')}</td>
			<td style='text-align:center;' id=\"issue_{$this->getId()}_category\">{$this->getVar('category_text')}</td>
			<td style='text-align:center;' id=\"issue_{$this->getId()}_priority\">{$this->getVar('priority_text')}</td>
			<td style='text-align:center;' id=\"issue_{$this->getId()}_subject\" alt='$long_subject' title='$long_subject'>$short_subject</td>
			<td style='text-align:center;'>{$this->patient_facility_name} $patient_facility_cust_id_text</td>
		</tr>";

        return $issue_row;
    }

    /**
     *
     * @param $match value of default item to be selected
     * @return string html containing option tags sutable for a select input
     */
    public static function CreateCategoryList($match = null, $restrict = true)
    {
        global $user;

        $dbh = DataStor::getHandle();

        $catagories = "";
        $blocking = false;
        # Block all other categories if match is an admin category
        if ($match > 0)
        {
            $sth = $dbh->prepare("SELECT is_admin FROM issue_category WHERE category_id = {$match}");
            $sth->execute();
            list($blocking) = $sth->fetch(PDO::FETCH_NUM);
        }

        $sth = $dbh->prepare("SELECT category_id, category_text, is_admin FROM issue_category ORDER BY display_order");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($row['category_id'] == $match) ? "selected" : "";
            # Always show the matching category.
            # If unresticted show all
            # If this is admin show all.
            if ($sel || !$restrict || $user->inPermGroup(User::$CUSTOMER_SUPPORT))
            {
                $catagories .= "		<option value='{$row['category_id']}' {$sel}>{$row['category_text']}</option>\n";
            }
            else if (!$row['is_admin'] && !$blocking)
            {
                # Show category if matching id is not admin
                $catagories .= "		<option value='{$row['category_id']}' {$sel}>{$row['category_text']}</option>\n";
            }
        }
        return $catagories;
    }


    /**
     *
     * @param int category to find text for
     * @return category_text for the integer value
     */
    public static function GetCategoryText($category = 0)
    {
        $dbh = DataStor::getHandle();

        $category_text = "unk";
        if ($category > 0)
        {
            $sth = $dbh->prepare("SELECT category_text FROM issue_category WHERE category_id = {$category}");
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $category_text = $row['category_text'];
            }
        }

        return $category_text;
    }


    /**
     *
     * @param $match value of default item to be selected
     * @return string html containing option tags sutable for a select input
     */
    public static function CreatePriorityList($match = null)
    {
        $priorities = "";
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("SELECT priority, priority_text FROM issue_priority ORDER BY display_order");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($row['priority'] == $match) ? " selected" : "";
            $priorities .= "		<option value='{$row['priority']}'{$sel}>{$row['priority_text']}</option>\n";
        }
        return $priorities;
    }


    /**
     *
     * @param int priority to find text for
     * @return string priority_text for the integer value
     */
    public static function GetPriorityText($priority = 0)
    {
        $dbh = DataStor::getHandle();

        $priority_text = "unk";
        if ($priority > 0)
        {
            $sth = $dbh->prepare("SELECT priority_text FROM issue_priority WHERE priority = {$priority}");
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $priority_text = $row['priority_text'];
            }
        }

        return $priority_text;
    }


    /**
     *
     * @param integer $match value of default item to be selected
     * @return string html containing option tags sutable for a select input
     */
    public static function CreateStatusList($match = null)
    {
        $statuses = "";
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("SELECT status_id, status_text FROM issue_status ORDER BY display_order");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($row['status_id'] == $match) ? " selected" : "";
            $statuses .= "		<option value='{$row['status_id']}'{$sel}>{$row['status_text']}</option>\n";
        }
        return $statuses;
    }


    /**
     *
     * @param int status to find text for
     * @return string status_text for the integer value
     */
    public static function GetStatusText($status = 0)
    {
        $dbh = DataStor::getHandle();

        $status_text = "unk";
        if ($status > 0)
        {
            $sth = $dbh->prepare("SELECT status_text FROM issue_status WHERE status_id = {$status}");
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $status_text = $row['status_text'];
            }
        }

        return $status_text;
    }

    /**
     * Build a list of helpful email recipients
     */
    public function EmailRecipientsList($form_name)
    {
        $recipients_list = "";

        if (!isset($form_name))
            $form_name = "issue_email_form";

        # Find all active users
        $sth = $this->dbh->query("SELECT
			u.id,
			u.firstname,
			u.lastname,
			u.email
		FROM users u
		WHERE u.active = true
		AND u.type = 1
		ORDER BY u.lastname, u.firstname");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $name = trim($row['firstname'] . " " . $row['lastname']);
            $email = trim($row['email']);

            # Add a link for this contact
            if ($email)
            {
                if (!$name)
                    $name = "&lt;{$email}&gt;";

                $recipients_list .= "<a href=\"#\"
				alt='Add contact email address' title='Add contact email address'
				onclick=\"AddEmail('$form_name', '{$email}','email_to');\"
				oncontextmenu=\"AddEmail('$form_name', '{$email}','email_cc'); return false;\">
				{$name}</a><br/>";
            }
            else if ($name)
                $recipients_list .= "{$name} &lt;no email&gt;<br/>";
        }

        return $recipients_list;
    }


    /**
     * Send Email of a text only version of this issue
     *
     * List all notes in email body include link to the issue
     *
     */
    public function SendEmail()
    {
        global $user;

        # Setup To and CC arrays
        $to_addresses = array();
        if (isset($_POST['email_to']) && trim($_POST['email_to']) != '')
        {
            $to_addresses = array_map('trim', preg_split('/,/', strtr($_POST['email_to'], ";", ","), -1, PREG_SPLIT_NO_EMPTY));
        }
        else
        {
            ErrorHandler::showError('An error has occurred while trying to send the issue email.', 'Did not get a to: address.', ErrorHandler::$FOOTER, $user);
            exit;
        }

        $cc_addresses = array();
        if (trim($_POST['email_cc']) != '')
        {
            $cc_addresses = array_map('trim', preg_split('/,/', strtr($_POST['email_cc'], ";", ","), -1, PREG_SPLIT_NO_EMPTY));
        }

        # Get this company name
        $company_name = "";
        if ($this->corporate_office_id > 0)
        {
            $sth = $this->dbh->prepare("SELECT office_name FROM corporate_office WHERE office_id = {$this->corporate_office_id}");
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_NUM))
            {
                $company_name = $row[0];
            }
        }

        if ($this->office_issue)
            $sth = $this->dbh->prepare("SELECT account_id, 1 FROM corporate_office WHERE office_id = {$this->office_id}");
        else
            $sth = $this->dbh->prepare("SELECT cust_id, entity_type FROM v_customer_entity WHERE id = {$this->office_id}");
        $sth->execute();

        $acc_id = "";
        list($account_id, $entity_type) = $sth->fetch(PDO::FETCH_NUM);

        if ($entity_type == 1)
        {
            if (preg_match('/\D{3}\d{3}/', $account_id))
                $acc_id = " ({$account_id})";

            $for = "{$this->office_name}{$acc_id}";
        }
        else
            $for = $account_id;

        /*
         * Create email Body
         */
        $email_body = "--------------------------------------------------------------------------------
{$company_name} Issue {$this->issue_id} for {$for}

  Subject: {$this->getVar('subject')}
  Status: {$this->getVar('status_text')}
  Category: {$this->getVar('category_text')}
  Priority: {$this->getVar('priority_text')}

Please update this issue, if applicable, using Groupware.

";
        # Add the notes to the email
        $sth = $this->dbh->prepare("SELECT note_id, contents FROM note WHERE issue_id = {$this->issue_id} ORDER BY creation_date DESC");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_NUM))
        {
            list($note_id, $contents) = $row;
            $note = new Note($note_id);

            # Add contents to the body
            $email_body .= "===================================\n";
            $email_body .= "By {$note->getVar('author_name')} On {$note->getCreationDateString('m/d/y h:s A')}\n";
            # String all html tags but convert br to \n
            $email_body .= str_replace(array("<br />", "<br>"), array("\n", "\n"), $note->getContents()) . "\n\n";
        }

        $fac_object = ($this->office_issue) ? "" : "&object=facility";
        # Add footer
        $email_body .= "\n________________________________________________________________________________
*** Issue was recorded on Groupware ***
" . Config::$ISSUE_EMAIL_URL . "?action=view&entry={$this->office_id}&issue_id={$this->issue_id}{$fac_object}";

        # Convert line endings back to <BR> and send as html
        $email_body = nl2br($email_body);

        try
        {
            Email::sendEmail(
                $to_addresses, $user, $this->getVar('subject'),
                $email_body, $cc_addresses, null, null, 80, Email::$USE_HTML
            );
        }
        catch (ValidationException $vexc)
        {
            ErrorHandler::showValidationError($vexc->getMessage(), ErrorHandler::$FOOTER);
            exit;
        }
        catch (Exception $exc)
        {
            ErrorHandler::showError('An error occurred while trying to send the issue email.', $exc->getMessage(), ErrorHandler::$FOOTER, $user);
            exit;
        }
    }

    /**
     * Sets the class Property value defined by $var.
     *
     * @return nothing
     */
    public function setVar($key = null, $value = null)
    {
        if (@property_exists($this, $key))
        {
            $this->{$key} = $value;
        }
    }
}
?>