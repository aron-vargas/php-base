<?php
/**
 * Class defines fixed asset transaction batch class
 *
 * @package Freedom
 * @author Aron Vargas
 */

/**
 * Class defines fixed asset journal entries
 */
class FASJournalEntry {
    protected $id;				# integer
    protected $created_at;		# timestamp with time zone NOT NULL DEFAULT now(),
    protected $created_by;		# integer
    public $batch_id;			# integer NOT NULL
    public $company_id;			# character varying(32) NOT NULL
    public $accounting_id;		# character varying(32) NOT NULL
    public $fas_trans_id; 		# integer
    public $gl_account_id;		# character varying(32) NOT NULL,
    public $amount;				# numeric(15,3),
    public $post_date;			# date
    public $trans_type;			# character varying(32) NOT NULL,
    public $trans_date;			# date NOT NULL,
    public $tran_comment;		# character varying(1024),
    public $acct_ref_1;			# character varying(32),
    public $acct_ref_2;			# character varying(32),
    public $acct_ref_3;			# character varying(32),

    # Static Vars
    static public $= '001';
    static public $= '100';
    static public $INI = 'INI';
    static public $DEFAULT001 = 'DEFAULT001';
    static public $ACL900 = 'ACL900';
    static public $ININC = 'INN604';
    static public $DEBIT_TYPE = 'Debit';
    static public $CREDIT_TYPE = 'Credit';

    static public $GL_1300 = '1300-00-0000';
    static public $GL_1710 = '1710-00-0000';
    static public $GL_1716 = '1716-00-0000';
    static public $GL_5210 = '5210-50-0000';
    static public $GL_5400 = '5400-50-0000';
    static public $GL_5600 = '5600-50-0000';
    static public $GL_6170 = '6170-90-0000';
    static public $GL_8210 = '8210-00-0000';

    /**
     * Create class instance
     *
     * @param integer
     * @param integer
     * @param integer
     * @param string
     * @param string
     */
    public function __construct($id = null, $batch_id = null, $fas_trans_id = null)
    {
        global $user;

        $this->id = $id;

        # Set defaults
        $now = date('Y-m-d');
        $today = date('Y-m-d H:i:s');
        $this->id = $id;
        $this->batch_id = $batch_id;
        $this->fas_trans_id = $fas_trans_id;
        $this->created_by = isset ($user) ? $user->getID() : 1;
        $this->created_at = $today;
        $this->trans_date = $today;
        $this->trans_type = self::$DEBIT_TYPE;
        $this->amount = 0;

        $this->Load();
    }

    /**
     * Assign values to SQL statement
     */
    public function BindValues(&$sth)
    {
        $trans_id_type = is_null($this->fas_trans_id) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $post_date_type = is_null($this->post_date) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $ref_1_type = is_null($this->acct_ref_1) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $ref_2_type = is_null($this->acct_ref_2) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $ref_3_type = is_null($this->acct_ref_3) ? PDO::PARAM_STR : PDO::PARAM_NULL;

        $i = 1;
        $sth->bindValue($i++, $this->created_by, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->batch_id, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->company_id, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->accounting_id, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->fas_trans_id, $trans_id_type);
        $sth->bindValue($i++, $this->gl_account_id, PDO::PARAM_STR);
        $sth->bindValue($i++, (float) $this->amount, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->post_date, $post_date_type);
        $sth->bindValue($i++, $this->trans_type, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->trans_date, PDO::PARAM_STR);
        $sth->bindValue($i++, substr($this->tran_comment, 0, 1024), PDO::PARAM_STR);
        $sth->bindValue($i++, $this->acct_ref_1, $ref_1_type);
        $sth->bindValue($i++, $this->acct_ref_2, $ref_2_type);
        $sth->bindValue($i++, $this->acct_ref_3, $ref_3_type);

        return $i;
    }

    /**
     * Copy database record
     */
    public function DBInsert()
    {
        global $user;

        $dbh = DataStor::getHandle();

        if ($this->id)
            return;

        if (is_null($this->created_by))
            $this->created_by = isset ($user) ? $user->getID() : 1;

        if (is_null($this->trans_date))
            $this->trans_date = date('Y-m-d');

        $sth = $dbh->prepare("INSERT INTO fas_journal_entry
		(created_by, batch_id, company_id, accounting_id,
		 fas_trans_id, gl_account_id, amount, post_date,
		 trans_type, trans_date, tran_comment,
		 acct_ref_1, acct_ref_2, acct_ref_3)
		VALUES (?,?,?,?, ?,?,?,?, ?,?,?, ?,?,?)");
        $this->BindValues($sth);
        $sth->execute();

        $this->id = $dbh->lastInsertId('fas_journal_entry_id_seq');

        return $this->id;
    }

    /**
     * Limited update to the database record
     *
     * @return integer
     */
    public function DBUpdate()
    {
        global $user;

        $dbh = DataStor::getHandle();

        $trans_id_type = is_null($this->fas_trans_id) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $post_date_type = is_null($this->post_date) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $ref_1_type = is_null($this->acct_ref_1) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $ref_2_type = is_null($this->acct_ref_2) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $ref_3_type = is_null($this->acct_ref_3) ? PDO::PARAM_STR : PDO::PARAM_NULL;

        $i = 1;
        $sth = $dbh->prepare("UPDATE fas_journal_entry
		SET
			fas_trans_id = ?,
			post_date = ?,
			acct_ref_1 = ?,
			acct_ref_2 = ?,
			acct_ref_3 = ?
		WHERE id = ?");
        $sth->bindValue($i++, $this->fas_trans_id, $trans_id_type);
        $sth->bindValue($i++, $this->post_date, $post_date_type);
        $sth->bindValue($i++, $this->acct_ref_1, $ref_1_type);
        $sth->bindValue($i++, $this->acct_ref_2, $ref_2_type);
        $sth->bindValue($i++, $this->acct_ref_3, $ref_3_type);
        $sth->execute();

        return $this->id;
    }

    /**
     * Copy database record
     */
    public function Load()
    {
        $dbh = DataStor::getHandle();

        if ($this->id)
        {
            $sth = $dbh->prepare("SELECT
				created_at, created_by, batch_id, company_id, accounting_id,
       			fas_trans_id, gl_account_id, amount, post_date, trans_type, trans_date,
       			tran_comment, acct_ref_1, acct_ref_2, acct_ref_3
			FROM fas_journal_entry
			WHERE id = ?");
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            foreach ($row as $key => $val)
            {
                $this->{$key} = $val;
            }
        }
    }

    /**
     * Set company and customer based on owning account
     *
     * @param string $owning_acct
     */
    public function SetCompanyInfo($owning_acct)
    {
        if ($owning_acct == FASOwnership::$DEFAULT001)
        {
            $this->company_id = FASJournalEntry::$DEFAULT001;
            $this->accounting_id = FASJournalEntry::$DEFAULT001;
        }
        else
        {
            $this->company_id = FASJournalEntry::$DEFAULT002;
            $this->accounting_id = FASJournalEntry::$DEFAULT002;
        }
    }
}
?>