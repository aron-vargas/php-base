<?php

/**
 * Class defines fixed asset transaction batch class
 *
 * @package Freedom
 * @author Aron Vargas
 */

/**
 * Class defines fixed asset batch processor
 */
class FASBatch {
    protected $id;				# Integer
    public $trans_type;			# character varying(32) NOT NULL,
    public $trans_date;			# date NOT NULL,
    protected $created_on;		# timestamp with time zone NOT NULL DEFAULT now(),
    protected $created_by;		# integer
    public $entry_count;		# integer NOT NULL,
    public $total_credit;		# numeric(15,3),
    public $total_debit;		# numeric(15,3),
    public $total_depreciation;	# numeric(15,3),
    public $status;				# integer NOT NULL DEFAULT 0,
    public $posted_tstamp;		# timestamp with time zone,
    public $committed_tstamp;	# timestamp with time zone,
    public $acct_ref_1;			# character varying(32),
    public $acct_ref_2;			# character varying(32),
    public $acct_ref_3;			# character varying(32),

    # Extended
    public $verbose;

    public static $STATUS_HOLD = -1;
    public static $STATUS_NEW = 0;
    public static $STATUS_PROCESSED = 1;
    public static $STATUS_POSTED = 2;
    public static $STATUS_COMMITTED = 3;
    public static $STATUS_ERROR = 99;

    /**
     * Create class instance
     */
    public function __construct($id = null)
    {
        global $user;

        $this->id = $id;

        # Set defaults
        $today = date('Y-m-d H:i:s');
        $this->created_by = isset ($user) ? $user->getID() : 1;
        $this->created_on = $today;
        $this->trans_date = $today;
        $this->entry_count = 0;
        $this->total_credit = 0;
        $this->total_debit = 0;
        $this->total_deprection = 0;
        $this->status = 0;
        $this->verbose = 0;

        $this->Load();
    }

    /**
     * Move (catch up) fas transactions to this batch
     *
     * @param DateTime
     */
    public function AddCatchUp($trans_date)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("UPDATE fas_transaction SET
			batch_id = ?,
			status = ?,
			trans_tstamp = ?
		WHERE record = true
		AND status = ?
		AND trans_type = ?");
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->bindValue(2, self::$STATUS_NEW, PDO::PARAM_INT);
        $sth->bindValue(3, $trans_date->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $sth->bindValue(4, self::$STATUS_HOLD, PDO::PARAM_INT);
        $sth->bindValue(5, FASTransaction::$DEPRECIATION, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Insert journal entries for the fas transactions
     *
     * @param integer
     */
    public function AddJournalEntries($trans_type)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("SELECT
			t.id, t.trans_amount, t.labor_amount,
			t.freight_amount, t.trans_tstamp::Date, o.owning_acct
		FROM fas_transaction t
		INNER JOIN fas_ownership o ON t.ownership_id = o.id
		WHERE t.status = 0 -- NEW
		AND t.trans_type = ?");
        $sth->bindValue(1, $trans_type, PDO::PARAM_INT);
        $sth->execute();
        while (list($id, $trans, $labor, $freight, $tran_date, $owning_acct) = $sth->fetch(PDO::FETCH_NUM))
        {
            $debit = new FASJournalEntry(null, $this->id, $id);
            $debit->trans_type = FASJournalEntry::$DEBIT_TYPE;
            $credit = new FASJournalEntry(null, $this->id, $id);
            $debit->trans_type = FASJournalEntry::$CREDIT_TYPE;
            $labor_credit = new FASJournalEntry(null, $this->id, $id);
            $debit->trans_type = FASJournalEntry::$CREDIT_TYPE;
            $labor_credit->amount = 0;

            if ($trans_type == FASTransaction::$NEWUNIT)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_1710;
                $debit->amount = $trans + $freight;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "New asset ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Debit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_1300;
                $credit->amount = ($trans + $freight) * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "New asset ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else if ($trans_type == FASTransaction::$UPDATE)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_1710;
                $debit->amount = $trans + $labor + $freight;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "Updated asset ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Dedit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $labor_credit->gl_account_id = FASJournalEntry::$GL_5400;
                $labor_credit->amount = $labor * -1;
                $labor_credit->tran_date = $tran_date;
                $labor_credit->tran_comment = "Labor Updated asset ({$this->id}, {$id})";
                $labor_credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Lablor Credit - {$labor_credit->tran_comment} GL: {$labor_credit->gl_account_id}, Amount: {$labor_credit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_6170;
                $credit->amount = ($trans + $freight) * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "CoGS Updated asset ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else if ($trans_type == FASTransaction::$DEPRECIATION)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_8210;
                $debit->amount = $trans;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "Monthly depreciation ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Debit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_1716;
                $credit->amount = $trans * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "Monthly depreciation ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else if ($trans_type == FASTransaction::$WRITEOFF_AD)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_1716;
                $debit->amount = $trans + $freight;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "Accumulated Depreciation ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Debit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_5210;
                $credit->amount = ($trans + $freight) * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "Purchased asset ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else if ($trans_type == FASTransaction::$UPGRADE)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_1710;
                $debit->amount = $trans + $labor + $freight;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "Upgraded asset ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Debit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $labor_credit->gl_account_id = FASJournalEntry::$GL_5400;
                $labor_credit->amount = $labor * -1;
                $labor_credit->tran_date = $tran_date;
                $labor_credit->tran_comment = "Labor Upgraded asset ({$this->id}, {$id})";
                $labor_credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Labor Credit - {$labor_credit->tran_comment} GL: {$labor_credit->gl_account_id}, Amount: {$labor_credit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_6170;
                $credit->amount = ($trans + $freight) * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "CoGS Upgraded asset ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else if ($trans_type == FASTransaction::$WRITEOFF)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_1716;
                $debit->amount = $trans;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "Write off asset ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Debit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_1710;
                $credit->amount = $trans * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "Write off asset ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else if ($trans_type == FASTransaction::$PURCHASE)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_1716;
                $debit->amount = $trans;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "Write off purchased asset ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Debit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_1710;
                $credit->amount = $trans * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "Write off purchased asset ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else if ($trans_type == FASTransaction::$PSWAP)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_1716;
                $debit->amount = $trans;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "Write off purchased swap ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Debit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_1710;
                $credit->amount = $trans * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "Write off purchased swap ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else if ($trans_type == FASTransaction::$WRITEON)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_1710;
                $debit->amount = $trans;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "Return to asset ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Debit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_1716;
                $credit->amount = $trans * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "Return to asset ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else if ($trans_type == FASTransaction::$WRITEON_AD)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_5210;
                $debit->amount = $trans + $freight;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "Asset Return to  ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Debit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_1716;
                $credit->amount = ($trans + $freight) * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "Asset Return to ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else if ($trans_type == FASTransaction::$COSTADJUSTMENT)
            {
                $debit->gl_account_id = FASJournalEntry::$GL_1716;
                $debit->amount = $trans;
                $debit->tran_date = $tran_date;
                $debit->tran_comment = "Cost Adjustment asset ({$this->id}, {$id})";
                $debit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Debit - {$debit->tran_comment} GL: {$debit->gl_account_id}, Amount: {$debit->amount}\n";

                $credit->gl_account_id = FASJournalEntry::$GL_5210;
                $credit->amount = $trans * -1;
                $credit->tran_date = $tran_date;
                $credit->tran_comment = "Cost Adjustment asset ({$this->id}, {$id})";
                $credit->SetCompanyInfo($owning_acct);

                if ($this->verbose)
                    echo "Credit - {$credit->tran_comment} GL: {$credit->gl_account_id}, Amount: {$credit->amount}\n";
            }
            else
            {
                throw Exception("Unknown transaction type ($trans_type)\n");
            }

            # Insert records
            try
            {
                $dbh->beginTransaction();
                $debit->DBInsert();
                $credit->DBInsert();
                if ($labor_credit->amount)
                    $labor_credit->DBInsert();
                $dbh->commit();

                $this->entry_count += 2;
                $this->total_debit += $debit->amount;
                $this->total_credit += $credit->amount;

                if ($labor_credit->amount)
                {
                    $this->entry_count += 1;
                    $this->total_credit += $labor_credit->amount;
                }
            }
            catch (Exception $exc)
            {
                $dbh->rollBack();
                echo "{$exc->getMessage()}\n";
            }
        }
    }

    /**
     * Do batch processing for specific transaction type
     *
     * @param integer
     * @return integer
     * @throws Exception
     */
    public function CreateBatch($tran_type, $trans_date = null)
    {
        if (is_null($this->id))
        {
            if (!is_null($trans_date))
                $this->trans_date = $this->ParseDate($trans_date);
            $this->DBInsert();
        }

        if ($this->status == 0)
        {
            $this->AddJournalEntries(FASTransaction::$NEWUNIT);
            $this->AddJournalEntries(FASTransaction::$UPDATE);
            $this->AddJournalEntries(FASTransaction::$UPGRADE);
            $this->AddJournalEntries(FASTransaction::$PURCHASE);
            $this->AddJournalEntries(FASTransaction::$DEPRECIATION);
            $this->AddJournalEntries(FASTransaction::$WRITEOFF);
            $this->AddJournalEntries(FASTransaction::$WRITEOFF_AD);
            $this->AddJournalEntries(FASTransaction::$COSTADJUSTMENT);
            $this->AddJournalEntries(FASTransaction::$WRITEON);
            $this->AddJournalEntries(FASTransaction::$WRITEON_AD);
            $this->AddJournalEntries(FASTransaction::$PSWAP);

            $this->SetProcessed();
            $this->DBUpdate();
        }

        if ($this->entry_count == 0)
            $this->Delete();

        return $this->entry_count;
    }

    /**
     * Add depreciation journal entries
     *
     * @param DateTime
     * @return integer
     *
     * @throws Exception
     */
    public function CreateDepJournal($tran_date)
    {

        $dbh = DataStor::getHandle();

        $trans_date = $dbh->quote($tran_date->format('Y-m-d'));
        $default = $dbh->quote(FASJournalEntry::$DEFAULT002);
        $accounting_id = $dbh->quote(FASJournalEntry::$ACL900);

        try
        {
            ## Debit from GL: 8210
            $gl = $dbh->quote(FASJournalEntry::$GL_8210);
            $trans_type = $dbh->quote(FASJournalEntry::$DEBIT_TYPE);
            $comment = $dbh->quote("Debit $gl for {$tran_date->format('F')} Montly Depreciation");

            $sth = $dbh->exec("INSERT INTO fas_journal_entry
			(created_by, batch_id, company_id, accounting_id,
		 	 fas_trans_id, gl_account_id, amount,
			 trans_type, trans_date, tran_comment)
			SELECT
				1, t.batch_id,
				CASE o.owning_acct WHEN $ininc THEN $ini ELSE $acp END,
				$accounting_id,	t.id, $gl, coalesce(t.trans_amount * -1, 0),
				$trans_type, $trans_date, $comment
			FROM fas_transaction t
			INNER JOIN fas_ownership o ON t.ownership_id = o.id AND o.active = true
			WHERE t.record = true
			AND t.batch_id = {$this->id}");

            ## Credit to GL: 1716
            $gl = $dbh->quote(FASJournalEntry::$GL_1716);
            $trans_type = $dbh->quote(FASJournalEntry::$CREDIT_TYPE);
            $comment = $dbh->quote("Credit $gl for {$tran_date->format('F')} Montly Depreciation");

            $sth = $dbh->exec("INSERT INTO fas_journal_entry
			(created_by, batch_id, company_id, accounting_id,
		 	 fas_trans_id, gl_account_id, amount,
			 trans_type, trans_date, tran_comment)
			SELECT
				1, t.batch_id,
				CASE o.owning_acct WHEN $ininc THEN $ini ELSE $acp END,
				$accounting_id,	t.id, $gl, coalesce(t.trans_amount, 0),
				$trans_type, $trans_date, $comment
			FROM fas_transaction t
			INNER JOIN fas_ownership o ON t.ownership_id = o.id AND o.active = true
			WHERE t.record = true
			AND t.batch_id = {$this->id}");

        }
        catch (Exception $exc)
        {
            echo "{$exc->getMessage()}\n";
        }

        return $this->entry_count;
    }

    /**
     * Add initial depreciation transactions for each valid owership record
     *
     * @param DateTime
     * @param mixed
     * @return integer
     * @throws Exception
     */
    public function CreateFirstMonth($tran_date, $force = false)
    {
        $dbh = DataStor::getHandle();
        $this->entry_count = 0;

        $trans_tstamp = $dbh->quote($tran_date->format('Y-m-d H:i:s'));
        $trans_type = FASTransaction::$DEPRECIATION;
        $comment = $dbh->quote("First Month Depreciation");

        # Option to force creation of out of service records
        if ($force === false)
            $IN_SERVICE = "AND o.in_service = true";
        else if ($force === true)
            $IN_SERVICE = "";
        else
        {
            $force = (int) $force;
            $IN_SERVICE = "AND o.asset_id = $force";
        }

        $batch_id = ($this->id) ? $this->id : "NULL";

        try
        {
            ## INSERT Transactions
            $sql = "INSERT INTO fas_transaction
			(ownership_id, asset_id, batch_id, created_by, trans_tstamp, trans_type, record,
			 trans_amount, labor_amount, freight_amount, fd_date, tran_comment)
			SELECT
				o.id, o.asset_id, {$batch_id}, 1, o.depreciation_start_date, $trans_type,
				a.record,
				a.dep_amount / 2,
	 			0, -- labor_amount,
				0, -- freight_amount,
				o.fd_date,
				$comment
			FROM fas_ownership o
			INNER JOIN owner_dep_amount a ON o.id = a.id
			WHERE o.accumulated_depreciation = 0
			AND o.acq_price > 0
			$IN_SERVICE";
            if ($this->verbose)
                echo "First Month's Depreciation Query:\n$sql\n";

            $sth = $dbh->exec($sql);
        }
        catch (Exception $exc)
        {
            echo "{$exc->getMessage()}\n";
        }
    }
    /**
     * Add depreciation transactions for each valid owership record
     *
     * @param DateTime
     * @param mixed
     * @return integer
     * @throws Exception
     */
    public function CreateDepTransactions($tran_date, $force = false)
    {
        $dbh = DataStor::getHandle();
        $this->entry_count = 0;

        $trans_tstamp = $dbh->quote($tran_date->format('Y-m-d H:i:s'));
        $T_YEAR = $dbh->quote($tran_date->format('Y'));
        $T_MONTH = $dbh->quote($tran_date->format('n'));
        $C_YEAR = date('Y');
        $C_MONTH = date('n');
        $trans_type = FASTransaction::$DEPRECIATION;
        $comment = $dbh->quote("{$tran_date->format('F')} Monthly Depreciation");

        # Option to force creation of out of service records
        if ($force === false)
            $IN_SERVICE = "AND o.in_service = true";
        else if ($force === true)
            $IN_SERVICE = "";
        else
        {
            $force = (int) $force;
            $IN_SERVICE = "AND o.asset_id = $force";
        }

        $batch_id = ($this->id) ? $this->id : "NULL";

        try
        {
            ## INSERT Transactions
            $sql = "INSERT INTO fas_transaction
			(ownership_id, asset_id, batch_id, created_by, trans_tstamp, trans_type, record,
			 trans_amount, labor_amount, freight_amount, fd_date, tran_comment)
			SELECT
				o.id, o.asset_id, {$batch_id}, 1, $trans_tstamp, $trans_type,
				a.record,
				CASE
					WHEN age(o.fd_date, $trans_tstamp::TIMESTAMP) < interval '1 Month'
					THEN o.current_value * -1
					ELSE a.dep_amount
				END,
	 			0, -- labor_amount,
				0, -- freight_amount,
				o.fd_date,
				$comment
			FROM fas_ownership o
			INNER JOIN owner_dep_amount a ON o.id = a.id
			WHERE o.current_value > 0
			$IN_SERVICE
			AND (o.depreciation_cycle_date IS NULL OR o.depreciation_cycle_date < $trans_tstamp)
			AND o.depreciation_start_date <= $trans_tstamp";
            if ($this->verbose)
                echo "Monthly Depreciation Query:\n$sql\n";

            $sth = $dbh->exec($sql);
        }
        catch (Exception $exc)
        {
            echo "{$exc->getMessage()}\n";
        }

        return $this->entry_count;
    }

    /**
     * Do batch processing for month depreciation
     *
     * @param DateTime
     * @param boolean
     * @return integer
     * @throws Exception
     */
    public function CreateMonthDepBatch($trans_date, $force = false)
    {
        $dbh = DataStor::getHandle();

        $this->trans_date = $trans_date->format('Y-m-d H:i:s');
        $this->entry_count = 0;

        if (is_null($this->id))
        {
            $this->DBInsert();

            if ($this->verbose)
                echo "Monthly Depreciation Batch Created ({$this->id})\n";
        }

        if ($force === false)
            $this->SetDepAmount();

        if ($this->status == 0)
        {
            $this->AddCatchUp($trans_date);
            $this->CreateFirstMonth($trans_date, $force);
            $this->UpdateOwnership($this->id);
            $this->CreateDepTransactions($trans_date, $force);
            $this->GetDepreciationCounts();
            $this->UpdateOwnership();
            if ($force === false)
                $this->CreateDepJournal($trans_date);
            $this->SetProcessed();
            $this->DBUpdate();
        }

        if ($this->entry_count == 0)
            $this->Delete();

        return (int) $this->entry_count;
    }

    /**
     * Insert database record
     *
     * @return integer
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

        $ref_1_type = is_null($this->acct_ref_1) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $ref_2_type = is_null($this->acct_ref_2) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $ref_3_type = is_null($this->acct_ref_3) ? PDO::PARAM_STR : PDO::PARAM_NULL;

        $i = 1;
        $sth = $dbh->prepare("INSERT INTO fas_batch
		(created_by, trans_date,
		 entry_count, total_credit, total_debit, total_depreciation,
		 acct_ref_1, acct_ref_2, acct_ref_3)
		VALUES (?,?, 0,0,0,0, ?,?,?)");
        $sth->bindValue($i++, $this->created_by, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->trans_date, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->acct_ref_1, $ref_1_type);
        $sth->bindValue($i++, $this->acct_ref_2, $ref_2_type);
        $sth->bindValue($i++, $this->acct_ref_3, $ref_3_type);
        $sth->execute();

        $this->id = $dbh->lastInsertId('fas_batch_id_seq');

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

        $ref_1_type = is_null($this->acct_ref_1) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $ref_2_type = is_null($this->acct_ref_2) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $ref_3_type = is_null($this->acct_ref_3) ? PDO::PARAM_STR : PDO::PARAM_NULL;

        $i = 1;
        $sth = $dbh->prepare("UPDATE fas_batch
		SET
			entry_count = ?,
			total_credit = ?,
			total_debit = ?,
			total_depreciation = ?,
			acct_ref_1 = ?,
			acct_ref_2 = ?,
			acct_ref_3 = ?
		WHERE id = ?");
        $sth->bindValue($i++, (int) $this->entry_count, PDO::PARAM_INT);
        $sth->bindValue($i++, (float) $this->total_credit, PDO::PARAM_STR);
        $sth->bindValue($i++, (float) $this->total_debit, PDO::PARAM_STR);
        $sth->bindValue($i++, (float) $this->total_depreciation, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->acct_ref_1, $ref_1_type);
        $sth->bindValue($i++, $this->acct_ref_2, $ref_2_type);
        $sth->bindValue($i++, $this->acct_ref_3, $ref_3_type);
        $sth->bindValue($i++, $this->id, PDO::PARAM_INT);
        $sth->execute();

        return $this->id;
    }

    /**
     * Remove database record
     */
    public function Delete()
    {
        $dbh = DataStor::getHandle();

        if ($this->id)
        {
            $sth = $dbh->prepare("DELETE FROM fas_batch WHERE id = ?");
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();
        }
    }

    /** @returns integer */
    public function GetId()
    {
        return $this->id;
    }

    /**
     * Query for depreciation batch counts
     */
    public function GetDepreciationCounts()
    {
        ## Update count and total
        if ($this->id)
        {
            $dbh = DataStor::getHandle();

            $sth = $dbh->query("SELECT count(*), SUM(trans_amount)
			FROM fas_transaction
			WHERE batch_id = {$this->id}
			AND record = true
			GROUP BY batch_id");
            $sum = $sth->fetch(PDO::FETCH_NUM);
            $this->entry_count = $sum[0];
            $this->total_depreciation = $sum[1] * -1;
        }
    }

    /**
     * Load database record
     */
    public function Load()
    {
        $dbh = DataStor::getHandle();

        if ($this->id)
        {
            $sth = $dbh->prepare("SELECT
				trans_type, trans_date, created_on, created_by,
				entry_count, total_credit, total_debit,
				status, posted_tstamp, committed_tstamp,
				acct_ref_1, acct_ref_2, acct_ref_3
			FROM fas_batch
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
     * Create temp table holding month depreciation amounts
     */
    public function SetDepAmount()
    {
        $dbh = DataStor::getHandle();
        $dbh->exec("CREATE TEMP TABLE owner_dep_amount AS
		SELECT
			o.id as id,
			CASE co.owning_acct
				WHEN 'DEFAULT001' THEN true
				WHEN 'ACL900' THEN true
				WHEN 'INN604' THEN true
				ELSE false
			END as record,
			o.acq_price * -1 / ((EXTRACT(year FROM o.fd_date) - EXTRACT(year FROM o.depreciation_start_date)) * 12 + EXTRACT(month FROM o.fd_date) - EXTRACT(month FROM o.depreciation_start_date)) as dep_amount
		FROM fas_ownership o
		LEFT JOIN fas_ownership co ON co.active AND o.asset_id = co.asset_id -- Link active owner
		WHERE co.owning_acct IN ('DEFAULT001', 'ACL900', 'INN604')
		AND ((EXTRACT(year FROM o.fd_date) - EXTRACT(year FROM o.depreciation_start_date)) * 12 + EXTRACT(month FROM o.fd_date) - EXTRACT(month FROM o.depreciation_start_date)) > 0");
    }

    /**
     * Set posted flag and time for the entire batch
     */
    public function SetCommitted()
    {
        $dbh = DataStor::getHandle();

        $this->posted = self::$STATUS_COMMITTED;
        $this->posted_stamp = date('Y-m-d H:i:s');
        $this->committed_stamp = date('Y-m-d H:i:s');

        $sth = $dbh->prepare("UPDATE fas_transaction SET status = ? WHERE record = true AND batch_id = ?");
        $sth->bindValue(1, self::$STATUS_POSTED, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();

        $sth = $dbh->prepare("UPDATE fas_batch
		SET
			status = ?,
			committed_stamp = CURRENT_TIMESTAMP
		WHERE id = ?");
        $sth->bindValue(1, self::$STATUS_POSTED, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();

        if ($this->verbose)
            echo "Batch ({$this->id}) has been committed.\n";
    }

    /**
     * Set posted flag and time for the entire batch
     */
    public function SetProcessed()
    {
        $dbh = DataStor::getHandle();

        $this->posted = 1;
        $this->posted_stamp = date('Y-m-d H:i:s');

        $sth = $dbh->prepare("UPDATE fas_transaction SET status = ? WHERE record = false AND batch_id = ?");
        $sth->bindValue(1, self::$STATUS_HOLD, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();

        $sth = $dbh->prepare("UPDATE fas_transaction SET status = ? WHERE record = true AND batch_id = ?");
        $sth->bindValue(1, self::$STATUS_PROCESSED, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();

        $sth = $dbh->prepare("UPDATE fas_journal_entry SET post_date = CURRENT_DATE WHERE batch_id = ?");
        $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();

        $sth = $dbh->prepare("UPDATE fas_batch SET status = ? WHERE id = ?");
        $sth->bindValue(1, self::$STATUS_PROCESSED, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();

        if ($this->verbose)
            echo "Batch ({$this->id}) has been proccessed.\n";
    }

    /**
     * Set posted flag and time for the entire batch
     */
    public function SetPosted()
    {
        $dbh = DataStor::getHandle();

        $this->posted = 1;
        $this->posted_stamp = date('Y-m-d H:i:s');

        $sth = $dbh->prepare("UPDATE fas_transaction SET status = ? WHERE record = true AND batch_id = ?");
        $sth->bindValue(1, self::$STATUS_POSTED, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();

        $sth = $dbh->prepare("UPDATE fas_journal_entry
		SET post_date = CURRENT_DATE
		WHERE batch_id = ? AND post_date IS NULL");
        $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();

        $sth = $dbh->prepare("UPDATE fas_batch
		SET
			status = ?,
			posted_tstamp = CURRENT_TIMESTAMP
		WHERE id = ?");
        $sth->bindValue(1, self::$STATUS_POSTED, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();

        if ($this->verbose)
            echo "Batch ({$this->id}) has been posted.\n";
    }

    /**
     * Show Lookup form
     *
     * @return string
     */
    static public function ShowLookupForm()
    {
        global $date_format;

        $from_date = date($date_format, strtotime(date('Y-m-01')));
        $to_date = date($date_format, strtotime(date('Y-m-t')));

        return "
	<form enctype='multipart/form-data' id='lookup_form' name='lookup_form' action='asset_database.php' method='post'>
	<input type='hidden' name='act' value='batch' />
	<table class='e_form' cellpadding='5' cellspacing='2' style='margin:0;'>
		<tbody>
			<tr><th colspan=2 style='text-align:left'>Batch Date Range</th></tr>
			<tr valign='bottom'>
				<td>
					<label for='from_date'>From:</lable><br/>
            		<input type='text' id='from_date' name='from_date' size='10' maxlength='10' value='$from_date' />
            		<img class='form_bttn' id='from_date_trg' src='images/calendar-mini.png' alt='Calendar' title='Calendar'>
				</td>
				<td>
					<label for='to_date'>To:</lable><br/>
            		<input type='text' id='to_date' name='to_date' size='10' maxlength='10' value='$to_date' />
            		<img class='form_bttn' id='to_date_trg' src='images/calendar-mini.png' alt='Calendar' title='Calendar'>
				</td>
			</tr>
		</tbody>
	</table>
	</form>";
    }

    /**
     * Update ownershpip records for the batch
     *
     * @param integer
     */
    public function UpdateOwnership($batch_id = null)
    {
        $dbh = DataStor::getHandle();

        $trans_type = FASTransaction::$DEPRECIATION;

        $batch_filter = "";
        if (!is_null($batch_id))
            $batch_filter = "AND batch_id = " . (int) $batch_id;

        ## update current value and AD
        $dbh->exec("UPDATE fas_ownership SET
			current_value = acq_price - (t.depreciation * -1),
			accumulated_depreciation = (t.depreciation * -1),
			depreciation_cycle_date = t.new_cycle_date
		FROM (SELECT
				ownership_id,
				MAX(trans_tstamp) as new_cycle_date,
				SUM(trans_amount) as depreciation
			FROM fas_transaction
			WHERE trans_type = $trans_type
			$batch_filter
			GROUP BY ownership_id
		) t
		WHERE fas_ownership.id = t.ownership_id");

        ## Turn off service flag for EoL
        ##$dbh->exec("UPDATE fas_ownership
        ##SET
        ##	in_service = false
        ##WHERE current_value <= 0");
    }
}
?>