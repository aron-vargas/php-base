<?php

class Result extends BaseClass
{
	public $pkey;                   # integer
	protected $db_table = "result";   # string
	public $timestamp;                # integer
	public $user_id;                   # integer
	public $bet_type  = 'LOST';	       # string
	public $points = 0;                # integer
	public $game = 'LIFE';             # string

	public $edit_view = "include/templates/result_edit.php";
	public $display_view = "include/templates/results.php";

	static public $TYPE_WIN = "WIN";
	static public $TYPE_PLACE = "PLACE";
	static public $TYPE_SHOW = "SHOW";

	public function __construct($id = null)
	{
		$this->SetFieldArray();
		$this->pkey = $id;
		$this->Load();
	}

	public function Delete()
	{
		global $dbh;

		$sth = $dbh->prepare("DELETE FROM result WHERE pkey = ?");
		$sth->bindValue(1, $this->pkey, PDO::PARAM_STR);
		$sth->execute();
	}

	private function SetFieldArray()
	{
		$i = 0;
		$this->field_array[$i++] = new DBField('timestamp', PDO::PARAM_INT, false, 0);
		$this->field_array[$i++] = new DBField('user_id', PDO::PARAM_INT, false, 0);
		$this->field_array[$i++] = new DBField('bet_type', PDO::PARAM_STR, false, 0);
		$this->field_array[$i++] = new DBField('points', PDO::PARAM_INT, false, 0);
		$this->field_array[$i++] = new DBField('game', PDO::PARAM_STR, false, 45);
	}

	public function Save()
	{
		if (empty($this->timestamp)) $this->timestamp = time();

		if ($this->pkey)
		{
			$this->db_update();
		}
		else
		{
			$this->db_insert();
		}
	}
}
