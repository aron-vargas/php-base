<?php

class Bet extends BaseClass
{
	public $pkey;                   # integer
	protected $db_table = "bet";   # string
	public $user_id;                   # integer
	public $entry_id;                   # integer
	public $amount;                   # double
	public $timestamp;                # integer
	public $paid = 0;                   # integer
	public $type;                   # string
	public $game = 'PARTY';             # string

	public $edit_view = "include/templates/bet_edit.php";
	public $display_view = "include/templates/bets.php";

	static public $TYPE_WIN = "WIN";
	static public $TYPE_PLACE = "PLACE";
	static public $TYPE_SHOW = "SHOW";

	public function __construct($id = null)
	{
		$this->SetFieldArray();
		$this->pkey = $id;
		$this->Load();
	}

	public function Copy($assoc)
    {
        if (is_array($assoc))
        {
            foreach($assoc AS $key => $val)
            {
                if ($key == 'db_table')
                    continue;
				if ($key == 'amount')
					$this->amount = preg_replace('/[^\d]/','',$val);
                else if (@property_exists($this, $key))
                    $this->{$key} = $val;
            }
        }
    }

	public function Delete()
	{
		global $dbh;

		$sth = $dbh->prepare("DELETE FROM bet WHERE pkey = ?");
		$sth->bindValue(1, $this->pkey, PDO::PARAM_STR);
		$sth->execute();
	}

	private function SetFieldArray()
	{
		$i = 0;
		$this->field_array[$i++] = new DBField('user_id', PDO::PARAM_INT, false, 0);
		$this->field_array[$i++] = new DBField('entry_id', PDO::PARAM_INT, false, 0);
		$this->field_array[$i++] = new DBField('amount', PDO::PARAM_STR, false, 0);
		$this->field_array[$i++] = new DBField('timestamp', PDO::PARAM_INT, false, 0);
		$this->field_array[$i++] = new DBField('paid', PDO::PARAM_INT, false, 0);
		$this->field_array[$i++] = new DBField('type', PDO::PARAM_STR, false, 45);
		$this->field_array[$i++] = new DBField('game', PDO::PARAM_STR, false, 45);
	}

	public function Save()
	{
		if ($this->pkey)
		{
			$this->db_update();
		}
		else
		{
			$this->timestamp = time();
			$this->db_insert();
		}
	}
}
