<?php
class NoteHistory
{
	protected $note_id;			# integer
	protected $history;

	public function __construct($note_id)
	{
		$dbh = DataStor::getHandle();
		$this->note_id = $note_id;

		if ($this->note_id)
		{
			$sth = $dbh->prepare("SELECT
				n.author,
				u.firstname || ' ' || 	u.lastname AS author_name,
				n.issue_id,
				n.modified_date,
				n.creation_date,
				n.contents
			FROM note_history n
			LEFT JOIN users u ON n.author = u.id
			WHERE n.note_id = ?
			ORDER BY n.modified_date DESC");
			$sth->bindValue(1, $this->note_id, PDO::PARAM_INT);
			$sth->execute();
			while ($note = $sth->fetch(PDO::FETCH_OBJ))
			{
				$this->history[] = $note;
			}
		}
	}

	/**
	 * Create display html
	 *
	 * @return string
	 */
	static public function HistoryDisplay($note)
	{
		global $user;

		$date = date('D, M d Y h:i A', $note->modified_date);

		$contents = "<div class='snote'>
			<div class='nleft'>
				By <b>{$note->author_name}</b><br/>
				{$date}
			</div>
			<div class='nright'>{$note->contents}</div>
		</div>";

		return $contents;
	}

	/**
	 * Output edit note dialog form
	 *
	 * @return string html text for editing an issue
	 */
	public function ShowDialog()
	{
		$history = "";
		if ($this->history)
		{
			foreach($this->history as $note)
				$history .= $this->HistoryDisplay($note);
		}

		$issue_form = "
		<div>
			<ul class='comments' style='margin:0;'>
				$history
			</ul>
		</div>";

		echo $issue_form;
	}

	/**
	 * Output requested form html
	 *
	 * @param integer
	 * @param array $form
	 * @return string html
	 */
	public function showForm($edit=0, $form=null)
	{
		return $this->ShowDialog();
	}
}
?>