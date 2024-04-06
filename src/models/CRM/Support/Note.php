<?php

/**
 * @package Freedom
 * @author Aron Vargas
 */

require_once ('Exception.php');
require_once ('Country.php');
require_once ('DataStor.php');
require_once ('Forms.php');
require_once ('User.php');

/**
 * Issue Notes definition
 */
class Note {
    protected $dbh = null;

    public $note_id = null;		# int4
    public $author = null;		# int4
    public $author_name = null;	# string (derived from users table )
    public $issue_id = null;		# int4
    public $timer_start;			# int unix time
    public $creation_date = null;	# int4 unix time
    public $contents = null;		# text
    public $is_resolution = false;	# bool
    public $email_sent = 0;		# int4 epoc date

    protected $db_table = "note";
    protected $history;


    /**
     * Creates a new Note object.
     *
     * @param integer $note_id
     * @param string $db_table
     */
    public function __construct($note_id = null, $db_table = 'note')
    {
        $this->dbh = DataStor::getHandle();
        $this->note_id = $note_id;
        $this->setDBTable($db_table);
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
        if ($this->note_id)
        {
            $sth = $this->dbh->prepare("UPDATE {$this->db_table} SET $field = ? WHERE note_id = ?");
            $sth->bindValue(1, $value);
            $sth->bindValue(2, $this->note_id, PDO::PARAM_INT);
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
                $this->{$key} = trim($value);
        }

        // new_note will the form input not contents
        if (isset($new['new_note']))
            $this->contents = $new['new_note'];
    }

    /**
     * Deletes this note from the database.
     */
    public function delete()
    {
        $sth = $this->dbh->prepare('DELETE FROM ' . $this->db_table . ' WHERE note_id = ?');
        $sth->bindValue(1, $this->note_id, PDO::PARAM_INT);
        $sth->execute();
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
     * @return mixed
     */
    public function getId($edit = 0)
    {
        $note_id = (int) $this->note_id;

        if ($edit)
            $note_id = "<input type=\"hidden\" name=\"note_id\" value=\"{$note_id}\" />";

        return $note_id;
    }


    /**
     * Returns contents property without cleansing html.
     *
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }


    /**
     * Returns Formated Date String represented by creation_date value.
     *
     * @param string $format
     * @return string
     */
    public function getCreationDateString($format = 'D, M d Y h:i A')
    {
        return date($format, $this->creation_date);
    }

    /**
     * Create display html
     *
     * @return string
     */
    static public function GetDisplay($note)
    {
        global $user;

        $edit_note = "";
        if ($note->author == $user->GetId())
        {
            $req_url = "templates/crm/note_edit.php?note_id={$note->note_id}";
            $tag[0] = array('text' => "edit", 'alt' => 'Edit this Note', 'click' => "ShowNote('{$req_url}')");
            $edit_note = "[" . BaseClass::BuildATags($tag) . "]";
        }

        $history = "";
        if ($note->history)
        {
            $req_url = "corporateoffice.php?act=getform&object=NoteHistory&entry={$note->note_id}";
            $tag[0] = array('text' => "H", 'alt' => 'View Change History', 'click' => "ShowHistory('{$req_url}')");
            $history = "[" . BaseClass::BuildATags($tag) . "]";
        }

        $created_at = date('D, M d Y h:i A', $note->creation_date);

        return "<div class='snote'>
			<div class='nleft'>
				By <b>{$note->author_name}</b><br/>
				{$created_at}
				<div class='nested'>$edit_note $history</div>
			</div>
			<div class='nright'>{$note->contents}</div>
		</div>";
    }

    /**
     * Returns Formated Date String represented by timer start value.
     *
     * @param string $format
     * @return string
     */
    public function getTimerStartString($format = 'D, M d Y h:i A')
    {
        return date($format, $this->timer_start);
    }


    /**
     * Returns email_sent as Formated Date String.
     *
     * @param string $format
     * @return string
     */
    public function getEmailSentDateString($format = 'D, M d Y h:i A')
    {
        return date($format, $this->email_sent);
    }


    /**
     * Set the issue_id to passed in
     *
     * @param int issue_id this note belongs to
     */
    public function setIssueId($issue_id)
    {
        $this->issue_id = $issue_id;
    }


    /**
     * Set the email_sent timestamp
     *
     * @param int timestamp epoc time to set to
     */
    public function setEmailSent($timestamp = 0)
    {
        $this->email_sent = $timestamp;
    }


    /**
     * Set the database table
     *
     * @param string $table
     */
    public function setDBTable($table = 'note')
    {
        if ($table == 'note' || $table == 'sales_note')
            $this->db_table = $table;
    }


    /**
     * Populates this object from the matching record in the
     * database.
     */
    public function load()
    {
        if ($this->note_id)
        {
            $sth = $this->dbh->prepare("
				SELECT
					n.author,
					u.firstname || ' ' || 	u.lastname AS author_name,
					n.issue_id,
					n.timer_start,
					n.creation_date,
					n.contents,
					n.is_resolution,
					n.email_sent,
					h.history
				FROM {$this->db_table} n
				LEFT JOIN users u ON n.author = u.id
				LEFT JOIN (
					SELECT
						note_id,
						count(*) as history
					FROM note_history
					GROUP BY note_id
				) h ON n.note_id = h.note_id
				WHERE n.note_id = ?");
            $sth->bindValue(1, $this->note_id, PDO::PARAM_INT);
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                foreach ($row as $key => $value)
                {
                    $this->{$key} = $value;
                }
            }
        }
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

        if ($this->author < 1)
            $this->author = $user->getId();

        if ($this->note_id)
        {
            ## Log change history
            if ($this->db_table == "note")
            {
                $sth = $this->dbh->prepare("INSERT INTO note_history
				(note_id, author, creation_date, modified_date, contents, issue_id)
				SELECT note_id, author, creation_date, ?, contents, issue_id
				FROM note
				WHERE note_id = ?");
                $sth->bindValue(1, time(), PDO::PARAM_INT);
                $sth->bindValue(2, (int) $this->note_id, PDO::PARAM_INT);
                $sth->execute();
            }

            $sth = $this->dbh->prepare("UPDATE {$this->db_table} SET
				contents = ?,
				is_resolution = ?,
				email_sent = ?
			WHERE note_id = ?");
            $sth->bindValue(1, fix_encoding($this->contents), PDO::PARAM_STR);
            $sth->bindValue(2, (int) $this->is_resolution, PDO::PARAM_INT);
            $sth->bindValue(3, (int) $this->email_sent, PDO::PARAM_INT);
            $sth->bindValue(4, (int) $this->note_id, PDO::PARAM_INT);
        }
        else
        {
            $sth = $this->dbh->prepare("
			INSERT INTO {$this->db_table}
			(author, issue_id, timer_start, creation_date, contents, is_resolution, email_sent)
			VALUES (?, ?, ?, ?, ?, ?, ?)");
            $sth->bindValue(1, (int) $this->author, PDO::PARAM_INT);
            $sth->bindValue(2, (int) $this->issue_id, PDO::PARAM_INT);
            $sth->bindValue(3, (int) $this->timer_start, PDO::PARAM_INT);
            $sth->bindValue(4, (int) $timestamp, PDO::PARAM_INT);
            $sth->bindValue(5, fix_encoding($this->contents), PDO::PARAM_STR);
            $sth->bindValue(6, (int) $this->is_resolution, PDO::PARAM_BOOL);
            $sth->bindValue(7, (int) $this->email_sent, PDO::PARAM_INT);
        }
        $sth->execute();
        if (!$this->note_id)
        {
            if ($this->db_table == "note")
                $this->note_id = $this->dbh->lastInsertId('note_note_id_seq');
            else
                $this->note_id = $this->dbh->lastInsertId('sales_note_note_id_seq');
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

    /**
     * Output edit note dialog form
     *
     * @return string html text for editing an issue
     */
    public function ShowDialog()
    {
        $header = ($this->issue_id > 0) ? "Thread #{$this->issue_id}" : "New Thread";

        $issue_form = "
	<form name='issue_form' action='{$_SERVER['PHP_SELF']}' method='post'>
	<input type='hidden' name='note_id' value='{$this->note_id}'/>
	<input type='hidden' name='entry' value='{$this->note_id}'/>
	<input type='hidden' name='parent' value='issue_{$this->issue_id}'/>
	<input type='hidden' name='object' value='Note'/>
	<input type='hidden' name='act' value='save'/>
	<table class='form' cellpadding='2' cellspacing='2' width='100%' style='margin:0;'>
		<tr>
			<th class='subheader' colspan='6'>$header</th>
		</tr>
		<tr>
			<td class='form' colspan='6' style='padding: 0; min-width:500px;'>
				<textarea id='new_note' name='new_note' rows=20 cols=90>{$this->GetContents()}</textarea>
			</td>
		</tr>
	</table>
	</form>";

        echo $issue_form;
    }

    /**
     * Output requested form html
     *
     * @param integer
     * @param array $form
     * @return string html text for editing an note
     */
    public function showForm($edit = 1, $form = null)
    {
        ## Assign new values to allowed fields
        if (isset($form['note_id']))
            $this->note_id = $form['note_id'];
        if (isset($form['office_id']))
            $this->office_id = $form['office_id'];

        return $this->ShowDialog();
    }
}
?>