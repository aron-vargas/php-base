<?php
/**
 * Class defines fixed asset ownership class
 *
 * @package Freedom
 * @author Aron Vargas
 */

/**
 * Class defines fixed asset ownership system
 */
class FASOwnership extends BaseClass {
    protected $pkey = 'id';
    protected $db_table = 'fas_ownership';
    protected $id;				# integer

    public $asset_id;			# integer
    public $active;				# boolean
    public $in_service;			# boolean
    public $owning_acct;		# string (32)
    public $depreciation_start_date;	# timestamp (string)
    public $depreciation_cycle_date;	# timestamp
    public $acq_date;			# timstamp (string)
    public $acq_price;			# float
    public $freight_amount;		# float
    public $created_by;			# integer
    public $created_on;			# timestamp (string)
    public $last_mod_by;		# integer
    public $last_mod_date;		# timestamp (string)
    public $accumulated_depreciation; # float
    public $current_value;		# float
    public $fd_date; 			# timestamp (string)
    public $lifespan;			# integer
    public $po_number;			# string (32)
    public $so_number;			# string (32)

    # Extended atttributes
    public $asset;
    public $verbose;

    # Static Vars
    static public $= 'DEFAULT001';
    static public $= 'ACL900';
    static public $INI = 'INN604';

    static public $NEW_MARKUP = 1.68;
    static public $UPGRADE_MARKUP = 1.25;

    static public $ACCOUNTING_GROUP = 3;
    static public $IT_GROUP = 9;

    /**
     * Create a class instance
     */
    public function __construct($id = null)
    {
        global $user;

        # Set defaults
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $this->id = $id;
        $this->verbose = 0;
        $this->created_by = isset ($user) ? $user->getID() : 1;
        $this->created_on = $now;
        $this->last_mod_by = isset ($user) ? $user->getID() : 1;
        $this->last_mod_date = $now;
        $this->owning_acct = self::$DEFAULT002;
        $this->depreciation_start_date = $today;
        $this->active = true;
        $this->in_service = true;
        $this->acq_price = 0;
        $this->accumulated_depreciation = 0;
        $this->current_value = 0;
        $this->acq_date = $today;
        $this->fd_date = $today;
        $this->lifespan = 0;

        $this->load();
    }

    /**
     * Add a comment transaction
     *
     * @param float $amount
     * @param text $tran_comment
     */
    public function AddComment($amount, $tran_comment)
    {
        if ($this->id)
        {
            $now = date('Y-m-d H:i:s');

            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $this->id;
            $fas_trans->asset_id = $this->asset_id;
            $fas_trans->trans_tstamp = $now;
            $fas_trans->trans_type = FASTransaction::$COMMENT;
            $fas_trans->trans_amount = (float) ($amount);
            $fas_trans->freight_amount = 0.0;
            $fas_trans->fd_date = $this->fd_date;
            $fas_trans->tran_comment = $tran_comment;
            $fas_trans->DBInsert();
        }
    }

    /**
     * Add Inactive Owner
     *
     * @param string
     * @param timestamp (string)
     * @param float
     * @param float
     * @param timestamp (string)
     * @param string
     * @param string
     *
     * @return integer
     */
    public function AddOwnership($owning_acct, $acq_date, $acq_price, $freight_amount = 0, $fd_date = null, $po = null, $so = null)
    {
        global $user;

        $user_id = ($user) ? $user->getID() : 1;
        $now = date('Y-m-d H:i:s');

        $acq_price = preg_replace('/[^\d\.]/', '', $acq_price);
        $freight_amount = preg_replace('/[^\d\.]/', '', $freight_amount);
        if (empty ($fd_date))
            $fd_date = $this->fd_date;
        if (empty ($acq_date))
            $acq_date = $now;

        $comment = "New ownership record for $owning_acct";

        if ($this->id)
        {
            # Add a transaction to existing owner inorder to track purchase markup.
            if ($acq_price != $this->acq_price)
            {
                $fas_trans = new FASTransaction();
                $fas_trans->ownership_id = $this->id;
                $fas_trans->asset_id = $this->asset_id;
                $fas_trans->trans_tstamp = $now;
                $fas_trans->trans_type = FASTransaction::$MARKUP;
                $fas_trans->trans_amount = (float) ($acq_price - $this->current_value);
                $fas_trans->freight_amount = (float) ($freight_amount - $this->freight_amount);
                $fas_trans->fd_date = $this->fd_date;
                $fas_trans->po_number = $po;
                $fas_trans->so_number = $so;
                $fas_trans->tran_comment = $comment;
                $fas_trans->DBInsert();
            }
        }

        ## Insert the current values
        $this->id = null;
        $this->asset_id = $this->asset_id;
        $this->active = false;
        $this->in_service = false;
        $this->created_by = $user_id;
        $this->created_on = $now;
        $this->last_mod_by = $user_id;
        $this->last_mod_date = $now;
        $this->owning_acct = $owning_acct;
        $this->acq_date = $this->ParseDate($acq_date);
        $this->acq_price = (float) $acq_price;
        $this->accumulated_depreciation = 0;
        $this->current_value = (float) $acq_price;
        $this->freight_amount = (float) $freight_amount;
        $this->fd_date = $this->ParseDate($fd_date);
        $this->po_number = $po;
        $this->so_number = $so;
        $id = $this->DBInsert();
        if ($id)
        {
            ## Add new unit transaction
            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $id;
            $fas_trans->asset_id = $this->asset_id;
            $fas_trans->trans_tstamp = $this->ParseDate($acq_date);
            $fas_trans->trans_type = FASTransaction::$NEWUNIT;
            $fas_trans->trans_amount = $this->acq_price;
            $fas_trans->freight_amount = $this->freight_amount;
            $fas_trans->fd_date = $this->fd_date;
            $fas_trans->po_number = $this->po_number;
            $fas_trans->so_number = $this->so_number;
            $fas_trans->tran_comment = $comment;
            $fas_trans->DBInsert();
        }

        return $id;
    }

    /**
     * (Re)Activate a fas_ownership record
     *
     * @param integer
     * @param string
     * @param float
     * @return FASOwnership
     */
    static public function ActivateOwner($asset_id, $owning_acct, $acq_price = null, $trans_date = null, $is_swap = false)
    {
        $active = self::GetActiveOwner($asset_id);
        $owner = self::GetOwner($asset_id, $owning_acct);

        ## Nothing to do if new owner is already the active one.
        if ($owner && $active && $owner->id == $active->id)
            return $active;

        # Setup variables
        ## When swapping a purchase, the price to Write off is acquisition price
        ## Normal purchases use purchase price
        $acp_owned = ($active->owning_acct == self::$|| $active->owning_acct == self::$DEFAULT001);
        $ini_owned = ($active->owning_acct == self::$INI);

        if (is_null($acq_price) || $is_swap)
            $acq_price = $active->acq_price;
        if (is_null($trans_date))
            $trans_date = date('Y-m-d H:i:s');

        ## Change the active ownership to the new account
        if ($owner)
        {
            ## Simple case: Activate the existing record
            $owner->ClearActive();
            $owner->change('active', true);
            $owner->AddComment(0, "{$owner->owning_acct} has been set as the active owner");
            $active = $owner;

            ## Update the Asset
            $asset = $active->LoadAsset();
            $asset->SetOwnership($active->owning_acct, $active->acq_date, $active->acq_price, $active->freight_amount);
        }
        else
        {
            ## Create a new record for this new owner
            $acq_date = $trans_date;
            $active->asset_id = $asset_id;
            $active->ChangeOwnership($owning_acct, $acq_date, $acq_price, $active->freight_amount, $active->fd_date);
        }

        # Write off unit cost and A.D. but do not fully depreciate indicate this as a purchase
        $wo_value = true;
        $wo_accum_dep = true;
        $fully_dep = false;
        $is_purchase = true;

        # Create write on/off transactions
        if ($acp_owned)
        {
            # Customer Purchase
            if ($owning_acct != self::$&& $owning_acct != self::$DEFAULT001)
            {
                # Default 1
                if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
                    $owner->WriteOff($trans_date, $wo_value, $wo_accum_dep, $fully_dep, $is_purchase, $is_swap);

                # Default 1L
                if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
                    $owner->WriteOff($trans_date, $wo_value, $wo_accum_dep, $fully_dep, $is_purchase, $is_swap);
            }
        }
        else if ($ini_owned)
        {
            # Customer Purchase
            if ($owning_acct != self::$INI)
            {
                # INI
                if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$INI))
                    $owner->WriteOff($trans_date, $wo_value, $wo_accum_dep, $fully_dep, $is_purchase, $is_swap);
            }
        }
        else
        {
            # Purchase Swap (Customer Owned returning)
            if ($owning_acct == self::$|| $owning_acct == self::$DEFAULT001)
            {
                # Write on Unit cost
                # Default 1
                if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
                {
                    $owner->WriteOn($trans_date, "Incoming Purchase Swap");
                    $owner->RecordDepreciation();
                }

                # Default 1L
                if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
                {
                    $owner->WriteOn($trans_date, "Incoming Purchase Swap");
                    $owner->RecordDepreciation();
                }
            }
            else if ($owning_acct == self::$INI)
            {
                # Write on Unit cost
                # INI
                if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$INI))
                {
                    $owner->WriteOn($trans_date, "Incoming Purchase Swap");
                    $owner->RecordDepreciation();
                }
            }
        }

        return $active;
    }

    /**
     * Check User autorization
     *
     * @param User
     * @return boolean
     */
    static public function Authorized($user)
    {
        $authorized = $user->inPermGroup(FASOwnership::$ACCOUNTING_GROUP) || $user->inPermGroup(FASOwnership::$IT_GROUP);
        return $authorized;
    }

    /**
     * Determine Accumulated Depreciation
     *
     * @param integer
     * @return timestamp (string)
     */
    public function CalcAD($months = null)
    {
        if (strtotime($this->fd_date) <= time())
        {
            # Handle case when fd date has been reached
            $this->accumulated_depreciation += $this->current_value;
            $this->current_value = 0;
        }
        else
        {
            # Handle case when start date is in the future
            if (strtotime($this->depreciation_start_date) > time())
                $months = 0;

            # If current value is set use it
            if ($this->current_value > 0)
            {
                $this->accumulated_depreciation = ($this->acq_price - $this->current_value);

                if ($this->accumulated_depreciation < 0)
                    $this->accumulated_depreciation = 0;

                $start = new DateTime($this->depreciation_start_date);
                $now = new DateTime(NULL);
                $life = $start->diff($now);
                $months = $life->format('%y') * 12;
                $months += $life->format('%m');
            }

            # By defualt Calculate from start until now
            if (is_null($months))
            {
                $start = new DateTime($this->depreciation_start_date);
                $now = new DateTime(NULL);
                $eol = new DateTime($this->fd_date);

                $life = $now->diff($eol);
                $months = $life->format('%y') * 12;
                $months += $life->format('%m');

                # Depreciated monthly amount
                $dep_amount = ($months > 0) ? round($this->current_value / $months, 3) : 0;

                $life = $start->diff($now);
                $months = (int) $life->format('%y') * 12;
                $months += (int) $life->format('%m');

                # Depreciated amount is monly amount * months in the cycle
                $dep_amount *= $months;

                $this->accumulated_depreciation = round($dep_amount, 3);
                $this->current_value = $this->acq_price - $this->accumulated_depreciation;

            }
        }
    }

    /**
     * Determine Full Depreciation Date
     *
     * @param integer
     * @return timestamp (string)
     */
    public function CalcFDDate($years)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT '{$this->depreciation_start_date}'::Timestamp + Interval '$years Years'");
        $sth->execute();
        $this->fd_date = $sth->fetchColumn();

        return $this->fd_date;
    }

    /**
     * Show Manual edit Form
     *
     * @return string (html)
     */
    public function ChangeOwnerForm()
    {
        global $date_format;

        $acq_date = date($date_format, strtotime($this->acq_date));

        $asset = $this->LoadAsset();
        $model = $asset->GetModel()->GetName();
        $serial = $asset->GetSerial();
        $tran = $asset->getLastTransaction();
        $fac = ($tran) ? $tran->GetFacility() : null;
        $cust_id = ($fac) ? $fac->getCustId() : 'No Cust ID';

        $form = "<form name='ochange' action='asset_database.php' method='POST'>
		<input type='hidden' name='act' value='o_change' />
		<input type='hidden' name='clicked_owner_id' id='clicked_owner_id' value='{$this->id}' />
		<input type='hidden' name='ownership_array' id='change_ownership_array' value='{$this->id}' />
		<input type='hidden' name='orig_owner' value='{$this->owning_acct}' />
		<input type='hidden' name='orig_price' value='{$this->acq_price}' />
		<table class='e_form' cellpadding=4 cellspacing=0 border=0>
			<tr>
				<th class='hdr' colspan=2>
					$cust_id: $model - $serial
				</th
			</tr>
			<tr>
				<td colspan=2><input type=radio name='setowner' value='{$this->id}' checked onclick='setownershiparray($this->id)'> CHANGE FOR THIS CUSTOMER<br>
				 <input type=radio name='setowner' value='2' onClick='setownershiparray(2)'> CHANGE FOR ALL CHECKED<hr>

				</td>
			</tr>
			<tr>


				<td>
					<label for='owning_acct'>Owner</label><br/>
					<input type='text' id='owning_acct' name='owning_acct' value='{$this->owning_acct}' size='10' maxlength='32' />
				</td>
				<td>
					<label for='acq_date'>Acquisition Date:</label><br/>
					<input type='text' id='acq_date' name='acq_date' value='$acq_date' size='10' maxlength='10' />
					<img title='Calendar' alt='Calendar' src='images/calendar-mini.png' id='acq_date_trg' class='form_bttn' />
				</td>
			</tr>
			<tr>
				<td>
					<label for='acq_date'>Acquisition Price:</label><br/>
					<input type='text' id='acq_price' name='acq_price' value='{$this->acq_price}' size='10' maxlength='10' />
				</td>
				<td>
					<label for='acq_date'>Freight Amount:</label><br/>
					<input type='text' id='freight_amount' name='freight_amount' value='{$this->freight_amount}' size='10' maxlength='10' />
				</td>
			</tr>
			<tr>
		</table>
		</form>";

        return $form;
    }

    /**
     * Handle asset ownership changes
     *
     * @param string
     * @param timestamp (string)
     * @param timestamp (string)
     * @param float
     * @param float
     * @param timestamp (string)
     * @param string
     * @param string
     *
     * @return integer
     */
    public function ChangeOwnership($owning_acct, $acq_date, $acq_price, $freight_amount, $fd_date, $po = null, $so = null)
    {
        global $user;

        # Find out this there is an existing ownere record for this asset and acct
        $owner = self::GetOwner($this->asset_id, $owning_acct);

        # Add new record
        if (empty ($owner))
            $this->AddOwnership($owning_acct, $acq_date, $acq_price, $freight_amount, $fd_date, $po = null, $so = null);
        else
        {
            $this->id = $owner->id;
            $this->load();

            $now = date('Y-m-d H:i:s');
            $acq_price = preg_replace('/[^\d\.]/', '', $acq_price);
            $freight_amount = preg_replace('/[^\d\.]/', '', $freight_amount);
            if (empty ($fd_date))
                $fd_date = $this->fd_date;
            if (empty ($acq_date))
                $acq_date = $now;

            $dbh = DataStor::getHandle();
            $sth = $dbh->prepare("UPDATE fas_ownership set acq_date=?, acq_price=?, freight_amount=? WHERE id=?");
            $sth->bindValue(1, $acq_date, PDO::PARAM_STR);
            $sth->bindValue(2, $acq_price, PDO::PARAM_STR);
            $sth->bindValue(3, $freight_amount, PDO::PARAM_STR);
            $sth->bindValue(4, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();
        }

        ## Activate this record
        $this->ClearActive();
        $this->change('active', true);
        $this->AddComment(0, "{$this->owning_acct} has been set as the active owner");

        ## Update the Asset
        $asset = $this->LoadAsset();
        $asset->SetOwnership($this->owning_acct, $this->acq_date, $this->acq_price, $this->freight_amount);

        return $this->id;
    }

    /**
     * Remove old transaction records
     * @param array $rm_ids
     */
    static public function CleanTrans($rm_ids)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("DELETE FROM fas_transaction WHERE id = ?");
        foreach ($rm_ids as $id)
        {
            $sth->bindValue(1, $id, PDO::PARAM_INT);
            $sth->execute();
        }
    }


    /**
     * Set active flag to false
     * for all records for this asset
     */
    public function ClearActive()
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("UPDATE fas_ownership
		SET active = false
		WHERE asset_id = ?");
        $sth->bindValue(1, $this->asset_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Create a temporary table
     */
    static public function CreateTempTrans()
    {
        $dbh = DataStor::getHandle();

        $dbh->exec("CREATE TEMP TABLE temp_fas_transaction
		(
		  id integer NOT NULL DEFAULT nextval('fas_transaction_id_seq'::regclass) PRIMARY KEY,
		  ownership_id integer NOT NULL,
		  batch_id integer,
		  asset_id integer NOT NULL,
		  created_on timestamp with time zone NOT NULL DEFAULT now(),
		  created_by integer,
		  trans_tstamp timestamp with time zone NOT NULL DEFAULT now(),
		  trans_type integer NOT NULL,
		  trans_amount numeric(15,3),
		  labor_amount numeric(15,3),
		  freight_amount numeric(15,3),
		  fd_date timestamp with time zone NOT NULL DEFAULT now(),
		  status integer NOT NULL DEFAULT 0,
		  po_number character varying(32),
		  so_number character varying(32),
		  acct_ref_1 character varying(32),
		  acct_ref_2 character varying(32),
		  acct_ref_3 character varying(32),
		  tran_comment character varying(1024),
		  record boolean NOT NULL DEFAULT true,
		  trans_ref_type integer
		)");
    }

    /**
     * Assign values to the PDO statement
     *
     * @param object $sth
     *
     * @return integer
     */
    private function BindValues(&$sth)
    {
        $dcp_type = ($this->depreciation_cycle_date) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $po_type = ($this->po_number) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $so_type = ($this->so_number) ? PDO::PARAM_STR : PDO::PARAM_NULL;

        $i = 1;
        $sth->bindValue($i++, $this->asset_id, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->active, PDO::PARAM_BOOL);
        $sth->bindValue($i++, $this->in_service, PDO::PARAM_BOOL);
        $sth->bindValue($i++, $this->owning_acct, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->depreciation_start_date, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->depreciation_cycle_date, $dcp_type);
        $sth->bindValue($i++, $this->acq_date, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->acq_price, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->freight_amount, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->accumulated_depreciation, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->current_value, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->fd_date, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->created_by, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->created_on, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->last_mod_by, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->last_mod_date, PDO::PARAM_STR);
        $sth->bindValue($i++, substr($this->po_number, 0, 32), $po_type);
        $sth->bindValue($i++, substr($this->po_number, 0, 32), $so_type);

        if ($this->id)
            $sth->bindValue($i++, $this->id, PDO::PARAM_INT);

        return $i;
    }

    /**
     * Perform DB Insert query
     *
     * @return integer
     */
    public function DBInsert()
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("INSERT INTO fas_ownership
		(asset_id, active, in_service, owning_acct,
		 depreciation_start_date, depreciation_cycle_date,
		 acq_date, acq_price, freight_amount,
		 accumulated_depreciation, current_value, fd_date,
		 created_by, created_on, last_mod_by, last_mod_date,
		 po_number, so_number)
		VALUES (?,?,?,?, ?,?, ?,?,?, ?,?,?, ?,?,?,?, ?,?)");
        $this->BindValues($sth);
        $sth->execute();

        $this->id = $dbh->lastInsertId('fas_ownership_id_seq');

        return $this->id;
    }

    /**
     * Perform DB Update query
     *
     * @return integer
     */
    public function DBUpdate()
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("UPDATE fas_ownership SET
			asset_id = ?,
			active = ?,
			in_service = ?,
			owning_acct = ?,
			depreciation_start_date = ?,
			depreciation_cycle_date = ?,
			acq_date = ?,
			acq_price = ?,
			freight_amount = ?,
			accumulated_depreciation = ?,
			current_value = ?,
			fd_date = ?,
			created_by = ?,
			created_on = ?,
			last_mod_by = ?,
			last_mod_date = ?,
			po_number = ?,
			so_number = ?
		WHERE id = ?");
        $this->BindValues($sth);
        $sth->execute();

        return $this->id;
    }

    /**
     * Recalculate Depreciation - Add month transaction records upto this month
     */
    public function FixDepreciation()
    {
        global $user;

        $user_id = ($user) ? $user->getId() : 1;

        $trans_type = FASTransaction::$DEPRECIATION;

        # Will remove all old depreciation transactions when done
        $dbh = DataStor::getHandle();
        $sth = $dbh->query("DELETE FROM fas_transaction
		WHERE trans_type = $trans_type AND ownership_id = {$this->id}");

        # Reset ownership records
        $dbh->exec("UPDATE fas_ownership SET
			accumulated_depreciation = 0,
			current_value = acq_price,
			depreciation_cycle_date = null
		WHERE id = {$this->id}");

        $this->accumulated_depreciation = 0;
        $this->current_value = $this->acq_price;

        ## Find initial date
        $tran_time = strtotime($this->acq_date);
        $current_date = time();
        ##echo "Starting at ($tran_time) ".date('Y-m-01', $tran_time)."\n";

        $acp_owned = in_array($this->owning_acct, array('DEFAULT001', 'ACL900', 'INN604'));

        ## Get start end end dates
        $start = new DateTime($this->depreciation_start_date);
        $eol = new DateTime($this->fd_date);

        ## Life in months
        $life = $start->diff($eol);
        $months = $life->format('%y') * 12;
        $months += $life->format('%m');

        ## Depreciated monthly amount
        $monthly_amount = ($months > 0) ? round($this->acq_price / $months, 3) * -1 : 0;

        $fd_date = strtotime($this->fd_date);
        $tran_date = date('Y-m-01', $tran_time);
        $datetime = new DateTime($tran_date);
        $comment = "First Month Depreciation";

        $sth = $dbh->prepare("INSERT INTO temp_fas_transaction
		(ownership_id, asset_id, created_by, trans_type, record,
		 trans_amount, labor_amount, freight_amount, fd_date,
		 trans_tstamp, tran_comment)
		VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->bindValue(2, $this->asset_id, PDO::PARAM_INT);
        $sth->bindValue(3, $user_id, PDO::PARAM_INT);
        $sth->bindValue(4, $trans_type, PDO::PARAM_INT);
        $sth->bindValue(5, $acp_owned, PDO::PARAM_BOOL);
        $sth->bindValue(6, ($monthly_amount / 2), PDO::PARAM_STR);
        $sth->bindValue(7, 0, PDO::PARAM_STR);
        $sth->bindValue(8, 0, PDO::PARAM_STR);
        $sth->bindValue(9, $this->fd_date, PDO::PARAM_STR);
        $sth->bindValue(10, $this->depreciation_start_date, PDO::PARAM_STR);
        $sth->bindValue(11, $comment, PDO::PARAM_STR);
        $sth->execute();

        ## Add first month as half the full amount
        $this->accumulated_depreciation += ($monthly_amount / 2) * -1;
        $this->current_value += ($monthly_amount / 2);
        $this->depreciation_cycle_date = $this->depreciation_start_date;

        # Start normal monthly records at normal rate and time
        $sth->bindValue(6, $monthly_amount, PDO::PARAM_STR);
        $tran_time = strtotime('+1 Month', strtotime($tran_date));
        while ($tran_time < $current_date && $tran_time <= $fd_date)
        {
            # Create a DateTime variable
            $tran_date = date('Y-m-01', $tran_time);
            $datetime = new DateTime($tran_date);
            $comment = "{$datetime->format('F')} Monthly Depreciation";

            if ($this->current_value < ($monthly_amount * -1))
            {
                # Do not go over whats left in value
                $monthly_amount = $this->current_value * -1;
                $sth->bindValue(6, $monthly_amount, PDO::PARAM_STR);
            }

            $sth->bindValue(10, $tran_date, PDO::PARAM_STR);
            $sth->bindValue(11, $comment, PDO::PARAM_STR);
            $sth->execute();

            # Track progress and move on to the next month
            $this->accumulated_depreciation += $monthly_amount * -1;
            $this->current_value += $monthly_amount;
            $this->depreciation_cycle_date = $tran_date;
            $months--;

            # advance 1 month
            $tran_time = strtotime('+1 Month', strtotime($tran_date));
        }

        ## Final record if there is anything left
        if ($current_date > $fd_date && $this->current_value > 0)
        {
            $comment = "Final Depreciation Record";
            $sth->bindValue(6, $this->current_value * -1, PDO::PARAM_STR);
            $sth->bindValue(10, $this->fd_date, PDO::PARAM_STR);
            $sth->bindValue(11, $comment, PDO::PARAM_STR);
            $sth->execute();
            $this->current_value = 0;
            $this->accumulated_depreciation = $this->acq_price;
            $this->depreciation_cycle_date = $this->fd_date;
        }

        # Update the ownership records to reflect the amount of depreciation
        $sth = $dbh->prepare("UPDATE fas_ownership SET
			accumulated_depreciation = ?,
			current_value = ?,
			depreciation_cycle_date = ?
		WHERE id = ?");
        $sth->bindValue(1, $this->accumulated_depreciation, PDO::PARAM_STR);
        $sth->bindValue(2, $this->current_value, PDO::PARAM_STR);
        $sth->bindValue(3, $this->depreciation_cycle_date, PDO::PARAM_STR);
        $sth->bindValue(4, $this->id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Find the active ownership record for the asset
     *
     * @param integer
     * @return FASOwnership
     */
    static public function GetActiveOwner($asset_id)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("SELECT id FROM fas_ownership WHERE asset_id = ? AND active");
        $sth->bindValue(1, (int) $asset_id, PDO::PARAM_INT);
        $sth->execute();
        $id = $sth->fetchColumn();

        $owner = new FASOwnership($id);

        return $owner;
    }

    /**
     * Determine Depreciation Amount
     *
     * @param datetime (string)
     * @param datetime (string)
     * @return float
     */
    public function GetDepAmount($start, $end)
    {
        # Find out how long is left
        $bol = new DateTime($this->acq_date);
        $now = new DateTime($start);
        $eol = new DateTime($this->fd_date);
        $cycle = new DateTime($end);

        $life = $bol->diff($eol);
        $months = (int) $life->format('%y') * 12;
        $months += (int) $life->format('%m');

        # Depreciated monthly amount is cost / life (in months)
        $dep_amount = ($months > 0) ? round($this->acq_price / $months, 3) : 0;

        $life = $now->diff($cycle);
        $months = (int) $life->format('%y') * 12;
        $months += (int) $life->format('%m');

        # Depreciated amount is monly amount * months in the cycle
        $dep_amount *= $months;

        return $dep_amount;
    }

    /**
     * Return fas_ownership record for the owner and asset
     *
     * @param integer
     * @param string
     * @return FASOwnership
     */
    static public function GetOwner($asset_id, $owning_acct)
    {
        global $user;

        $owner = null;

        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("SELECT id FROM fas_ownership WHERE asset_id = ? AND owning_acct = ?");
        $sth->bindValue(1, (int) $asset_id, PDO::PARAM_INT);
        $sth->bindValue(2, $owning_acct, PDO::PARAM_STR);
        $sth->execute();
        $id = $sth->fetchColumn();

        if ($id)
        {
            ## Simple case: Set the old record active
            $owner = new FASOwnership($id);
        }

        return $owner;
    }

    /**
     * Find the amount of upgrade applied to FAS
     *
     * @return float
     */
    public function GetUpgradeValue()
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("SELECT sum(trans_amount)
		FROM fas_transaction
		WHERE ownership_id = ?
		AND trans_type = ?");
        $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
        $sth->bindValue(2, FASTransaction::$UPGRADE, PDO::PARAM_INT);
        $sth->execute();
        $amount = (float) $sth->fetchColumn();

        return $amount;
    }

    /**
     * Populate attributes from DB record
     */
    public function Load()
    {
        $dbh = DataStor::getHandle();

        if ($this->id)
        {
            $sth = $dbh->prepare("SELECT
				o.asset_id, o.active, o.in_service, o.owning_acct,
				o.depreciation_start_date, o.acq_date, o.acq_price,
				o.freight_amount, o.accumulated_depreciation, o.depreciation_cycle_date,
				o.current_value, o.fd_date,
				o.created_by, o.created_on, o.last_mod_by, o.last_mod_date,
				o.po_number, o.so_number,
				date_part('years', age(o.fd_date, o.depreciation_start_date)) as lifespan
			FROM fas_ownership o
			WHERE o.id = ?");
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
     * Load Asset object
     *
     * @param bool
     * @return object
     */
    public function LoadAsset($reload = false)
    {
        if (is_null($this->asset) || $reload)
            $this->asset = new LeaseAsset($this->asset_id);

        return $this->asset;
    }

    /**
     * 1) Update the asset
     * 2) Update Ownership
     * 3) Add FAS transaction
     *
     * @param timestamp (string)
     * @param float
     * @param float
     * @param timestamp (string)
     * @param float
     * @param float
     *
     * @return integer
     */
    public function ManualAdjust($acq_date, $depreciation_start_date, $acq_price, $freight_amount, $fd_date, $current_value, $ad)
    {
        global $user;

        $dbh = DataStor::getHandle();

        $user_id = ($user) ? $user->getID() : 1;
        $now = date('Y-m-d H:i:s');
        $asset = $this->LoadAsset();
        $model = $asset->GetModel()->GetName();
        $serial = $asset->GetSerial();

        # Clean Up floats
        $acq_price = (float) preg_replace('/[^\d\.]/', '', $acq_price);
        $freight_amount = (float) preg_replace('/[^\d\.]/', '', $freight_amount);
        $current_value = (float) preg_replace('/[^\-\d\.]/', '', $current_value);
        $ad = (float) preg_replace('/[^\d\.]/', '', $ad);

        $ad_delta = (float) ($ad - $this->accumulated_depreciation);
        $price_delta = (float) ($acq_price - $this->acq_price);
        $value_delta = (float) ($current_value - $this->current_value);
        $freight_delta = (float) ($freight_amount - $this->freight_amount);
        $new_acq_date = $this->ParseDate($acq_date);
        $new_fd_date = $this->ParseDate($fd_date);
        $new_dsd = $this->ParseDate($depreciation_start_date);

        $this->last_mod_by = $user_id;
        $this->last_mod_date = $now;
        $this->depreciation_start_date = $new_dsd;
        $this->acq_date = $new_acq_date;
        $this->acq_price = (float) $acq_price;
        $this->freight_amount = (float) $freight_amount;
        $this->fd_date = $new_fd_date;
        $this->current_value = (float) $current_value;
        $this->accumulated_depreciation = (float) $ad;
        $this->DBUpdate();

        $sth = $dbh->prepare("UPDATE fas_transaction SET trans_amount = ?, trans_tstamp = ? WHERE ownership_id = ? AND trans_type = ?");
        $sth->bindValue(1, $this->acq_price, PDO::PARAM_STR);
        $sth->bindValue(2, $this->acq_date, PDO::PARAM_STR);
        $sth->bindValue(3, $this->id, PDO::PARAM_STR);
        $sth->bindValue(4, FASTransaction::$NEWUNIT, PDO::PARAM_INT);
        $sth->execute();

        if ($price_delta == $value_delta)
        {
            # Only need 1 transaction to increase value
            # Exmple: Increase Acq and Value by 100 is only a change of +100
            # not +100 for acq change and +100 for nbv change
            # That would result in +200 additional value
            $value_delta = 0;
        }

        ## Track change in Acquisition Price
        if ($price_delta || $freight_delta)
        {
            ## Alternative is to update the original transaction record
            # FASTransaction::UpdateAcqPrice($this->id, $acq_price, $freight_amount);

            $comment = "Manual Updated";
            if ($price_delta)
                $comment .= " - ACQ Price to $acq_price";
            if ($freight_delta)
                $comment .= " - ACQ Freight to $freight_amount";

            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $this->id;
            $fas_trans->asset_id = $this->asset_id;
            $fas_trans->trans_tstamp = $now;
            $fas_trans->trans_type = FASTransaction::$COMMENT;
            $fas_trans->trans_amount = $price_delta;
            $fas_trans->freight_amount = $freight_delta;
            $fas_trans->fd_date = $this->fd_date;
            $fas_trans->record = false;
            $fas_trans->tran_comment = substr($comment, 0, 1024);
            $fas_trans->DBInsert();
        }

        ## Track change in Accumulated Depreciation
        if ($ad_delta)
        {
            $comment = "Manual Updated Accumulated Depreciation to $ad";
            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $this->id;
            $fas_trans->asset_id = $this->asset_id;
            $fas_trans->trans_tstamp = $now;
            $fas_trans->trans_type = FASTransaction::$DEPRECIATION;
            $fas_trans->trans_amount = $ad_delta * -1;
            $fas_trans->fd_date = $this->fd_date;
            $fas_trans->record = true;
            $fas_trans->tran_comment = substr($comment, 0, 1024);
            $fas_trans->DBInsert();
        }

        ## Track change in Current Value
        if ($value_delta)
        {
            $comment = "Manual Updated - Current Value to $current_value";
            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $this->id;
            $fas_trans->asset_id = $this->asset_id;
            $fas_trans->trans_tstamp = $now;
            $fas_trans->trans_type = FASTransaction::$COMMENT;
            $fas_trans->trans_amount = $value_delta;
            $fas_trans->fd_date = $this->fd_date;
            $fas_trans->record = false;
            $fas_trans->tran_comment = substr($comment, 0, 1024);
            $fas_trans->DBInsert();
        }

        $asset->SetOwnership($this->owning_acct, $this->acq_date, $this->acq_price, $this->freight_amount);

        return $this->id;
    }

    /**
     * Change a field value
     */
    public function InlineUpdate($args)
    {
        global $user;

        $dbh = DataStor::getHandle();

        $user_id = ($user) ? $user->getID() : 1;
        $now = date('Y-m-d H:i:s');

        if ($args['field'] == 'depreciation_start_date')
        {
            $this->depreciation_start_date = $this->ParseDate($args['value']);
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				depreciation_start_date = ?,
				last_mod_date = '$now',
				last_mod_by = $user_id
			WHERE id = ?");
            $sth->bindValue(1, $this->depreciation_start_date, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->execute();
        }
        else if ($args['field'] == 'depreciation_cycle_date')
        {
            $this->depreciation_cycle_date = $this->ParseDate($args['value']);
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				depreciation_cycle_date = ?,
				last_mod_date = '$now',
				last_mod_by = $user_id
			WHERE id = ?");
            $sth->bindValue(1, $this->depreciation_cycle_date, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->execute();
        }
        else if ($args['field'] == 'acq_date')
        {
            $this->acq_date = $this->ParseDate($args['value']);
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				acq_date = ?,
				last_mod_date = '$now',
				last_mod_by = $user_id
			WHERE id = ?");
            $sth->bindValue(1, $this->acq_date, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->execute();

            $sth = $dbh->prepare("UPDATE fas_transaction SET trans_tstamp = ? WHERE ownership_id = ? AND trans_type = ?");
            $sth->bindValue(1, $this->acq_date, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->bindValue(3, FASTransaction::$NEWUNIT, PDO::PARAM_INT);
            $sth->execute();

            $asset = $this->LoadAsset();
            $asset->SetOwnership($this->owning_acct, $this->acq_date, $this->acq_price, $this->freight_amount);
        }
        else if ($args['field'] == 'acq_price')
        {
            $this->acq_price = (float) preg_replace('/[^-\d\.]/', '', $args['value']);
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				acq_price = ?,
				last_mod_date = '$now',
				last_mod_by = $user_id
			WHERE id = ?");
            $sth->bindValue(1, $this->acq_price, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->execute();

            $sth = $dbh->prepare("UPDATE fas_transaction SET trans_amount = ? WHERE ownership_id = ? AND trans_type = ?");
            $sth->bindValue(1, $this->acq_price, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->bindValue(3, FASTransaction::$NEWUNIT, PDO::PARAM_INT);
            $sth->execute();

            $asset = $this->LoadAsset();
            $asset->SetOwnership($this->owning_acct, $this->acq_date, $this->acq_price, $this->freight_amount);
        }
        else if ($args['field'] == 'freight_amount')
        {
            $this->freight_amount = (float) preg_replace('/[^-\d\.]/', '', $args['value']);
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				freight_amount = ?,
				last_mod_date = '$now',
				last_mod_by = $user_id
			WHERE id = ?");
            $sth->bindValue(1, $this->freight_amount, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->execute();

            $asset = $this->LoadAsset();
            $asset->SetOwnership($this->owning_acct, $this->acq_date, $this->acq_price, $this->freight_amount);
        }
        else if ($args['field'] == 'accumulated_depreciation')
        {
            $this->accumulated_depreciation = (float) preg_replace('/[^-\d\.]/', '', $args['value']);
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				accumulated_depreciation = ?,
				last_mod_date = '$now',
				last_mod_by = $user_id
			WHERE id = ?");
            $sth->bindValue(1, $this->accumulated_depreciation, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->execute();
        }
        else if ($args['field'] == 'current_value')
        {
            $this->current_value = (float) preg_replace('/[^-\d\.]/', '', $args['value']);
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				current_value = ?,
				last_mod_date = '$now',
				last_mod_by = $user_id
			WHERE id = ?");
            $sth->bindValue(1, $this->current_value, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->execute();
        }
        else if ($args['field'] == 'fd_date')
        {
            $this->fd_date = $this->ParseDate($args['value']);
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				fd_date = ?,
				last_mod_date = '$now',
				last_mod_by = $user_id
			WHERE id = ?");
            $sth->bindValue(1, $this->fd_date, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->execute();
        }
        else if ($args['field'] == 'po_number')
        {
            $this->po_number = trim($args['value']);
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				po_number = ?,
				last_mod_date = '$now',
				last_mod_by = $user_id
			WHERE id = ?");
            $sth->bindValue(1, $this->po_number, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->execute();
        }
        else if ($args['field'] == 'so_number')
        {
            $this->so_number = trim($args['value']);
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				so_number = ?,
				last_mod_date = '$now',
				last_mod_by = $user_id
			WHERE id = ?");
            $sth->bindValue(1, $this->so_number, PDO::PARAM_STR);
            $sth->bindValue(2, $this->id, PDO::PARAM_STR);
            $sth->execute();
        }
        else if ($args['field'] == "mfg_date")
        {
            $sth = $dbh->prepare("UPDATE lease_asset SET
				mfg_date = ?
			WHERE id = ?");
            $sth->bindValue(1, $this->ParseDate($args['value']), PDO::PARAM_STR);
            $sth->bindValue(2, $this->asset_id, PDO::PARAM_INT);
            $sth->execute();
        }
        else if ($args['field'] == "svc_date")
        {
            $sth = $dbh->prepare("UPDATE lease_asset SET
				svc_date = ?
			WHERE id = ?");
            $sth->bindValue(1, $this->ParseDate($args['value']), PDO::PARAM_STR);
            $sth->bindValue(2, $this->asset_id, PDO::PARAM_INT);
            $sth->execute();
        }

        return json_encode(array("state" => "ok", "elem_id" => "{$this->id}-{$args['field']}"));
    }

    /**
     * Show Manual edit Form
     *
     * @return string (html)
     */
    public function ManualEditForm()
    {
        global $date_format;

        $asset = $this->LoadAsset();
        $model = $asset->GetModel()->GetName();
        $serial = $asset->GetSerial();

        $ds_date = date($date_format, strtotime($this->depreciation_start_date));
        $acq_date = date($date_format, strtotime($this->acq_date));
        $fd_date = date($date_format, strtotime($this->fd_date));

        $form = "<form name='oedit' action='asset_database.php' method='POST'>
		<input type='hidden' name='act' value='o_manual' />
		<input type='hidden' name='ownership_id' value='{$this->id}' />
		<table class='e_form' cellpadding=4 cellspacing=0 border=0>
			<tr><th class='hdr' colspan='3'>{$this->owning_acct}<br/>$model - $serial</th></tr>
			<tr>
				<td>
					<label for='acq_date'>Acquisition Date:</label><br/>
					<input type='text' id='acq_date' name='acq_date' value='$acq_date' size='10' maxlength='10' />
					<img title='Calendar' alt='Calendar' src='images/calendar-mini.png' id='acq_date_trg' class='form_bttn' />
				</td>
				<td>
					<label for='ds_date'>Depreciation Start Date:</label><br/>
					<input type='text' id='ds_date' name='ds_date' value='{$ds_date}' size='10' maxlength='10' />
					<img title='Calendar' alt='Calendar' src='images/calendar-mini.png' id='ds_date_trg' class='form_bttn' />
				</td>
				<td>
					<label for='fd_date'>Date of Full Depreciation:</label><br/>
					<input type='text' id='fd_date' name='fd_date' value='{$fd_date}' size='10' maxlength='10' />
					<img title='Calendar' alt='Calendar' src='images/calendar-mini.png' id='fd_date_trg' class='form_bttn' />
				</td>
			</tr>
			<tr>
				<td>
					<label for='acq_price'>Acquisition Price:</label><br/>
					<input type='text' id='acq_price' name='acq_price' value='{$this->acq_price}' size='10' maxlength='10' onkeyup='UpdateCV(this.form);' />
				</td>
				<td>
					<label for='ad'>Accumulated Depreciation:</label><br/>
					<input type='text' id='ad' name='accumulated_depreciation' value='{$this->accumulated_depreciation}' size='10' maxlength='10' onkeyup='UpdateCV(this.form);' />
				</td>
				<td>
					<label for='current_value'>Current Value:</label><br/>
					<input type='text' id='current_value' name='current_value' value='{$this->current_value}' size='10' maxlength='10' readonly style='background-color: #DDDDDD;' />
				</td>
			</tr>
			<tr>
				<td>
					<label for='freight_amount'>Freight Amount:</label><br/>
					<input type='text' id='freight_amount' name='freight_amount' value='{$this->freight_amount}' size='10' maxlength='10' />
				</td>
				<td>
					<label for='fix'>Re-Run Depreciation:</label><br/>
					<input type='checkbox' id='fix' name='fix' value='1' />
				</td>

				<td>&nbsp;</td>
			</tr>
		</table>
		</form>";

        return $form;
    }

    /**
     * 1) Change Current Value and Accumulated Depreciation
     * 2) Add FAS transaction
     *
     * @param DateTime
     * @return float
     */
    public function MonthDepreciation($tran_date)
    {
        $dbh = DataStor::getHandle();

        # Validate
        if (strtotime($this->depreciation_cycle_date) > $tran_date->getTimestamp())
        {
            throw new Exception("{$this->depreciation_cycle_date} is after {$tran_date->format('Y-m-d')}. Asset ID: {$this->asset_id}\n");
        }

        $dep_amount = $this->GetDepAmount($this->depreciation_cycle_date, $tran_date->format('Y-m-d H:i:s'));

        if ($dep_amount)
        {
            $this->accumulated_depreciation += (float) $dep_amount;

            # For upgrades value is added to the unit but acq_price remains
            # Results in higher depreciation than acq_price
            if ($this->accumulated_depreciation > $this->acq_price)
            {
                # Will add upgraded valus
                $sth = $dbh->prepare("SELECT sum(trans_amount) FROM fas_transaction WHERE ownership_id = ?");
                $sth->bindValue(1, $this->id, PDO::PARAM_INT);
                $sth->execute();
                $upgrade_amount = $sth->fetchColumn();

                # now Compare full value and accumulated depreciation
                $full_value = $upgrade_amount + $this->acq_price;
                if ($this->accumulated_depreciation > $full_value)
                {
                    $dep_amount = $full_value - $this->accumulated_depreciation - $dep_amount;
                    $this->accumulated_depreciation = $full_value;
                    $this->current_value = 0;
                }
            }

            # Update current_value
            if ($this->current_value > 0)
            {
                # Depreciate monthly amount
                $this->current_value -= (float) $dep_amount;

                if ($this->current_value < 0)
                    $this->current_value = 0;
            }

            # Will update all ownership records for the asset
            $sth = $dbh->prepare("UPDATE fas_ownership SET
				current_value = ?,
				accumulated_depreciation = ?,
				depreciation_cycle_date = ?
			WHERE id = ?");
            $sth->bindValue(1, $this->current_value, PDO::PARAM_STR);
            $sth->bindValue(2, $this->accumulated_depreciation, PDO::PARAM_STR);
            $sth->bindValue(3, $tran_date->format('Y-m-d'), PDO::PARAM_STR);
            $sth->bindValue(4, $this->id, PDO::PARAM_INT);
            $sth->execute();

            $comment = "{$tran_date->format('F')} Monthly Depreciation";
            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $this->id;
            $fas_trans->asset_id = $this->asset_id;
            $fas_trans->trans_tstamp = $tran_date->format('Y-m-d H:i:s');
            $fas_trans->trans_type = FASTransaction::$DEPRECIATION;
            $fas_trans->trans_amount = $dep_amount * -1;
            $fas_trans->tran_comment = $comment;
            $fas_trans->in_service = $this->in_service;
            $fas_trans->DBInsert();

            if ($this->verbose)
                echo "FAS Transaction - $comment Asset ID: {$fas_trans->asset_id}, Amount: {$fas_trans->trans_amount}\n";
        }

        return $dep_amount;
    }

    /**
     * Move records from temp to permanent storage
     * @param string $temp_table
     */
    static public function MoveTempRecords()
    {
        $dbh = DataStor::getHandle();
        $dbh->exec("INSERT INTO fas_transaction	(id, ownership_id, batch_id, asset_id,
			created_on, created_by, trans_tstamp, trans_type, trans_amount,
			labor_amount, freight_amount, fd_date, status, po_number, so_number,
			acct_ref_1, acct_ref_2, acct_ref_3, tran_comment, record, trans_ref_type)
		SELECT
			id, ownership_id, batch_id, asset_id,
			created_on, created_by, trans_tstamp, trans_type, trans_amount,
			labor_amount, freight_amount, fd_date, status, po_number, so_number,
			acct_ref_1, acct_ref_2, acct_ref_3, tran_comment, record, trans_ref_type
		FROM temp_fas_transaction");
        $dbh->exec("TRUNCATE TABLE temp_fas_transaction");
    }

    /**
     * 1) Log the change
     * 2) Add FAS transaction
     *
     * @param string
     * @param timestamp (string)
     * @param string
     * @param string
     * @param timestamp (string)
     * @param string
     * @param string
     *
     * @return integer
     */
    public function NewUnit($owning_acct, $acq_date, $acq_price, $freight_amount, $depreciation_start_date = null, $po = null, $so = null)
    {
        global $user;

        $user_id = ($user) ? $user->getID() : 1;
        $now = date('Y-m-d H:i:s');
        $asset = $this->LoadAsset();
        $model = $asset->getModel();
        $model_name = $model->GetName();
        $years = (int) $model->GetLifeSpan();
        $serial = $asset->GetSerial();
        $comment = "New Asset Model:$model_name, Serial:$serial";
        if (empty ($acq_date))
            $acq_date = $now;
        if (empty ($depreciation_start_date))
            $depreciation_start_date = $acq_date;

        # Clean Up floats
        $acq_price = preg_replace('/[^\d\.]/', '', $acq_price);
        $freight_amount = preg_replace('/[^\d\.]/', '', $freight_amount);

        $this->asset_id = $asset->GetID();
        $this->in_service = false;
        $this->created_by = $user_id;
        $this->created_on = $now;
        $this->last_mod_by = $user_id;
        $this->last_mod_date = $now;
        $this->owning_acct = $owning_acct;
        $this->depreciation_start_date = $this->ParseDate($depreciation_start_date);
        $this->depreciation_cycle_date = $this->ParseDate($depreciation_start_date);
        $this->acq_date = $this->ParseDate($acq_date);
        $this->acq_price = (float) $acq_price;
        $this->current_value = (float) $acq_price;
        $this->freight_amount = (float) $freight_amount;
        $this->lifespan = $years;
        $this->CalcFDDate($years);
        $this->po_number = $po;
        $this->so_number = $so;

        $id = $this->DBInsert();

        if ($id)
        {
            $asset->SetOwnership($this->owning_acct, $this->acq_date, $this->acq_price, $this->freight_amount);

            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $this->id;
            $fas_trans->asset_id = $this->asset_id;
            $fas_trans->trans_tstamp = $this->ParseDate($acq_date);
            $fas_trans->trans_type = FASTransaction::$NEWUNIT;
            $fas_trans->trans_amount = $this->acq_price;
            $fas_trans->freight_amount = $this->freight_amount;
            $fas_trans->fd_date = $this->fd_date;
            $fas_trans->po_number = $this->po_number;
            $fas_trans->so_number = $this->so_number;
            $fas_trans->tran_comment = $comment;
            $fas_trans->DBInsert();
        }

        return $id;
    }

    /**
     * Handle OOS routine
     *
     * @param integer
     * @param string (datetime)
     */
    static public function OOS($asset_id, $tstamp)
    {
        # Add Writeoff device value AND accumulated depreciate
        $wo_value = true;
        $wo_accum_dep = true;

        # Default 1
        if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
        {
            $owner->WriteOff($tstamp, $wo_value, $wo_accum_dep);
            $owner->change('in_service', false);
        }

        # Default 1L
        if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
        {
            $owner->WriteOff($tstamp, $wo_value, $wo_accum_dep);
            $owner->change('in_service', false);
        }

        # INI
        if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$INI))
        {
            $owner->WriteOff($tstamp, $wo_value, $wo_accum_dep);
            $owner->change('in_service', false);
        }
    }

    /**
     * Handle Return to service routine
     *
     * @param integer
     * @param string (datetime)
     * @param string
     */
    static public function OOSRevert($asset_id, $tstamp, $comment)
    {
        # Add Writeoff device value AND accumulated depreciate
        $wo_value = true;
        $wo_accum_dep = true;

        # Default 1
        if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
        {
            $owner->WriteOn($tstamp, $comment);
            $owner->change('in_service', true);
        }

        # Default 1L
        if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
        {
            $owner->WriteOn($tstamp, $comment);
            $owner->change('in_service', true);
        }

        # INI
        if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$INI))
        {
            $owner->WriteOn($tstamp, $comment);
            $owner->change('in_service', true);
        }
    }

    /**
     * Put the record in service (trigger depreciation to start)
     *
     * @param boolean
     * @param boolean
     */
    public function PutInService($in_service, $force_update = false)
    {
        $dbh = DataStor::getHandle();

        if ($force_update || $this->in_service !== (bool) $in_service)
        {
            $this->in_service = (bool) $in_service;

            # Make sure start date is good
            if (empty ($this->depreciation_start_date))
                $this->depreciation_start_date = date('Y-m-d H:i:s');
            if (empty ($this->depreciation_cycle_date))
                $this->depreciation_cycle_date = $this->depreciation_start_date;

            if (empty ($this->acq_date))
                $this->acq_date = $this->depreciation_start_date;

            ## Set/Reset FD Date to now
            $this->CalcFDDate($this->lifespan);

            ## Get Dates for update to asset
            $acq_date = date('Y-m-d', strtotime($this->acq_date));
            $svc_date = date('Y-m-d', strtotime($this->depreciation_start_date));

            # Update Ownership
            $sth = $dbh->prepare("UPDATE fas_ownership
			SET
				in_service = ?,
				acq_date = ?,
				depreciation_start_date = ?,
				depreciation_cycle_date = ?,
				fd_date = ?
			WHERE id = ?");
            $sth->bindValue(1, $this->in_service, PDO::PARAM_BOOL);
            $sth->bindValue(2, $this->acq_date, PDO::PARAM_STR);
            $sth->bindValue(3, $this->depreciation_start_date, PDO::PARAM_STR);
            $sth->bindValue(4, $this->depreciation_cycle_date, PDO::PARAM_STR);
            $sth->bindValue(5, $this->fd_date, PDO::PARAM_STR);
            $sth->bindValue(6, $this->id, PDO::PARAM_INT);
            $sth->execute();

            ## Update Asset
            $sth = $dbh->prepare("UPDATE lease_asset
			SET
				acq_date = ?,
				svc_date = ?
			WHERE id = ? AND owning_acct = ?");
            $sth->bindValue(1, $acq_date, PDO::PARAM_STR);
            $sth->bindValue(2, $svc_date, PDO::PARAM_STR);
            $sth->bindValue(3, $this->asset_id, PDO::PARAM_INT);
            $sth->bindValue(4, $this->owning_acct, PDO::PARAM_STR);
            $sth->execute();

            ## Record NEW unit transaction
            if ($this->in_service)
            {
                $sth = $dbh->prepare("UPDATE fas_transaction
				SET
					record = true
				WHERE ownership_id = ?
				AND trans_type = ?");
                $sth->bindValue(1, $this->id, PDO::PARAM_INT);
                $sth->bindValue(2, FASTransaction::$NEWUNIT, PDO::PARAM_INT);
                $sth->execute();
            }
        }
    }

    /**
     * Set flag ON for background drepreciation
     */
    public function RecordDepreciation()
    {
        $dbh = DataStor::getHandle();

        # Record NEW unit transaction
        $sth = $dbh->prepare("UPDATE fas_transaction
		SET
			record = true
		WHERE ownership_id = ?
		AND trans_type = ?");
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->bindValue(2, FASTransaction::$DEPRECIATION, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Redoo new unit and markup records
     *
     * @param string
     * @param float
     * @param float
     * @param float
     */
    public function ResetAcquisition($acq_date, $acq_price, $markup_price, $freight_amount = 0, $depreciation_start_date = null, $fd_date = null)
    {
        $dbh = DataStor::getHandle();
        $markup = $markup_price - $acq_price;

        $this->acq_date = $this->ParseDate($acq_date);
        $this->acq_price = $acq_price;
        $this->current_value = $this->acq_price - $this->accumulated_depreciation;
        if ($freight_amount)
            $this->freight_amount = $freight_amount;
        if (!empty ($depreciation_start_date))
            $this->depreciation_start_date = $this->ParseDate($depreciation_start_date);
        if (!empty ($fd_date))
            $this->fd_date = $this->ParseDate($fd_date);
        $comment = "New ownership record for {$this->owning_acct}";

        # Remove transactions
        $sth = $dbh->prepare("DELETE FROM fas_transaction
		WHERE ownership_id = ?
		AND trans_type IN (?, ?)");
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->bindValue(2, FASTransaction::$NEWUNIT, PDO::PARAM_INT);
        $sth->bindValue(3, FASTransaction::$MARKUP, PDO::PARAM_INT);
        $sth->execute();

        $fas_trans = new FASTransaction();
        $fas_trans->ownership_id = $this->id;
        $fas_trans->asset_id = $this->asset_id;
        $fas_trans->trans_tstamp = $this->acq_date;
        $fas_trans->trans_type = FASTransaction::$NEWUNIT;
        $fas_trans->trans_amount = $this->acq_price;
        $fas_trans->freight_amount = $this->freight_amount;
        $fas_trans->fd_date = $this->fd_date;
        $fas_trans->po_number = $this->po_number;
        $fas_trans->so_number = $this->so_number;
        $fas_trans->tran_comment = $comment;
        $fas_trans->DBInsert();

        if ($markup)
        {
            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $this->id;
            $fas_trans->asset_id = $this->asset_id;
            $fas_trans->trans_tstamp = $this->acq_date;
            $fas_trans->trans_type = FASTransaction::$MARKUP;
            $fas_trans->trans_amount = (float) ($markup);
            $fas_trans->freight_amount = (float) ($this->freight_amount);
            $fas_trans->fd_date = $this->fd_date;
            $fas_trans->po_number = $this->po_number;
            $fas_trans->so_number = $this->so_number;
            $fas_trans->tran_comment = $comment;
            $fas_trans->DBInsert();
        }

        $this->DBUpdate();
    }

    /**
     * Sum depreciation as of $effective_date
     *
     * @param string $effective_date
     */
    public function ResetDepreciation($effective_date = null)
    {
        $dbh = DataStor::getHandle();

        if (empty ($effective_date))
            $effective_date = $this->ParseDate(date('Y-m-d'));

        # Record NEW unit transaction
        $sth = $dbh->prepare("SELECT SUM(trans_amount)
		FROM fas_transaction
		WHERE ownership_id = ?
		AND trans_type = ?
		AND trans_tstamp < ?");
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->bindValue(2, FASTransaction::$DEPRECIATION, PDO::PARAM_INT);
        $sth->bindValue(3, $effective_date, PDO::PARAM_STR);
        $sth->execute();

        $this->accumulated_depreciation = (float) $sth->fetchColumn() * -1;
        $this->current_value = $this->acq_price - $this->accumulated_depreciation;
    }

    /**
     * Perform DB Delete query
     */
    public function RMOwner()
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("DELETE FROM fas_ownership WHERE id = ?");
        $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Handle Scrap routine
     *
     * @param integer
     * @param string (datetime)
     */
    static public function Scrap($asset_id, $tstamp)
    {
        # Add Writeoff device value AND fully depreciate
        $wo_value = true;
        $wo_accum_dep = false;
        $fully_dep = true;

        # Default 1
        if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
        {
            $owner->WriteOff($tstamp, $wo_value, $wo_accum_dep, $fully_dep);
            $owner->change('in_service', false);
        }

        # Default 1L
        if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
        {
            $owner->WriteOff($tstamp, $wo_value, $wo_accum_dep, $fully_dep);
            $owner->change('in_service', false);
        }

        # INI
        if ($owner = FASOwnership::GetOwner($asset_id, FASOwnership::$INI))
        {
            $owner->WriteOff($tstamp, $wo_value, $wo_accum_dep, $fully_dep);
            $owner->change('in_service', false);
        }
    }

    /**
     * @param integer
     */
    static public function ShowHistoryTable($id, $all = false)
    {
        $dbh = DataStor::getHandle();

        $owning_acct = "No Owner";
        $transaction_data = "<tr><td colspan=7>No transaction data found</td></tr>";
        if ($id)
        {
            $rc = "on";
            $comment_clause = ($all) ? "" : "AND t.trans_type <> " . FASTransaction::$COMMENT;

            $sth = $dbh->prepare("SELECT
				t.id, t.ownership_id, t.asset_id, t.created_by,
				t.trans_tstamp, t.trans_amount, t.labor_amount, t.freight_amount,
				t.status, t.po_number, t.so_number,
				t.acct_ref_1, t.acct_ref_2, t.acct_ref_3, t.tran_comment,
				o.owning_acct,
				ft.description as trans_type,
				u.firstname, u.lastname
			FROM fas_transaction t
			INNER JOIN fas_ownership o ON o.id = t.ownership_id
			INNER JOIN fas_transaction_type ft ON t.trans_type = ft.id
			INNER JOIN users u ON t.created_by = u.id
			WHERE t.ownership_id = ?
			$comment_clause
			ORDER BY t.trans_tstamp DESC");
            $sth->bindValue(1, $id, PDO::PARAM_INT);
            $sth->execute();

            if ($sth->rowCount() > 0)
                $transaction_data = "";

            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $timestamp = date('Y-m-d g:i:s a', strtotime($row['trans_tstamp']));
                $user = trim("{$row['firstname']} {$row['lastname']}");
                $tran_comment = htmlentities($row['tran_comment'], ENT_QUOTES);
                $owning_acct = "<a href='asset_database.php?act=search&search_type=advanced&search_fields[0]=ao.id&operators[0]=eq&strings[0]={$id}'>{$row['owning_acct']}</a>";

                $transaction_data .= "
				<tr class='$rc'>
					<td>{$timestamp}</td>
					<td>{$user}</td>
					<td>{$row['trans_type']}</td>
					<td>{$row['trans_amount']}</td>
					<td>{$row['po_number']}</td>
					<td>{$row['so_number']}</td>
					<td>{$tran_comment}</td>
					<td><button class='btn btn-default btn-xs' onclick=\"OpenTrxEdit(event, {$row['id']})\"><i class='fa fa-pencil'></i></button></td>
				</tr>";

                $rc = ($rc == 'on') ? 'off' : 'on';
            }
        }

        return "<table class='dt' cellpadding=5 cellspacing=2 border=1>
			<thead>
				<tr><th class='shdr' colspan='8'>$owning_acct</th></tr>
				<tr>
					<th class='shdr'>Transaction Time</th>
					<th class='shdr'>User</th>
					<th class='shdr'>Type</th>
					<th class='shdr'>Amount</th>
					<th class='shdr'>PO</th>
					<th class='shdr'>SO</th>
					<th class='shdr'>Comment</th>
					<th class='shdr'><i class='fa fa-pencil'></i></th>
				</tr>
			</thead>
			<tbody>
				$transaction_data
			</tbody>
		</table>";
    }

    /**
     * Put the device in service and trigger depreciation to start
     *
     * @param integer
     * @param date (string)
     */
    static public function StartDepreciation($asset_id, $service_date = null)
    {
        $dbh = DataStor::getHandle();

        ## Set date on 1st pack (Expect transactions to be up to date)
        $sth = $dbh->prepare("SELECT count(*)
		FROM lease_asset_transaction
		WHERE status = ? AND lease_asset_id = ?");
        $sth->bindValue(1, LeaseAssetTransaction::$PACK, PDO::PARAM_STR);
        $sth->bindValue(2, $asset_id, PDO::PARAM_INT);
        $sth->execute();
        $packs = $sth->fetchColumn();
        $init = ($packs <= 1);
        $force_update = false;

        # When the new service date is passed reset acq and dep start
        $acq_tstamp = date('Y-m-d H:i:s');
        if (strtotime($service_date) > 0)
        {
            $acq_tstamp = date('Y-m-d H:i:s', strtotime($service_date));
            $init = true;
            $force_update = true;
        }

        if ($acp_own = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
        {
            if ($init || empty ($acp_own->acq_date))
                $acp_own->acq_date = $acq_tstamp;
            if ($init || empty ($acp_own->depreciation_start_date))
            {
                $acp_own->depreciation_start_date = $acq_tstamp;
                $acp_own->depreciation_cycle_date = $acq_tstamp;
            }

            $acp_own->PutInService(true, $force_update);
        }

        if ($acl_own = FASOwnership::GetOwner($asset_id, FASOwnership::$DEFAULT001))
        {
            if ($init || empty ($acl_own->acq_date))
                $acl_own->acq_date = $acq_tstamp;
            if ($init || empty ($acl_own->depreciation_start_date))
            {
                $acl_own->depreciation_start_date = $acq_tstamp;
                $acl_own->depreciation_cycle_date = $acq_tstamp;
            }

            $acl_own->PutInService(true, $force_update);
        }

        if ($ini_own = FASOwnership::GetOwner($asset_id, FASOwnership::$INI))
        {
            if ($init || empty ($ini_own->acq_date))
                $ini_own->acq_date = $acq_tstamp;
            if ($init || empty ($ini_own->depreciation_start_date))
            {
                $ini_own->depreciation_start_date = $acq_tstamp;
                $ini_own->depreciation_cycle_date = $acq_tstamp;
            }

            $ini_own->PutInService(true, $force_update);
        }
    }

    /**
     * 1) Update the asset
     * 2) Update Ownership
     * 3) Add FAS transaction
     *
     * @param float
     * @param timestamp (string)
     * @param float
     * @param integer
     *
     * @return integer
     */
    public function Upgrade($added_value, $added_time, $labor_amount, $old_asset_id)
    {
        global $user;

        $dbh = DataStor::getHandle();

        # Clean Up floats
        $user_id = ($user) ? $user->getID() : 1;
        $added_value = preg_replace('/[^\d\.]/', '', $added_value);
        $labor_amount = preg_replace('/[^\d\.]/', '', $labor_amount);

        $prev_asset = new LeaseAsset($old_asset_id);
        $prev_model = $prev_asset->GetModel()->GetName();
        $prev_serial = $prev_asset->GetSerial();
        $acp_owned = ($this->owning_acct == self::$|| $this->owning_acct == self::$DEFAULT001);

        $asset = $this->LoadAsset();
        $model = $asset->GetModel()->GetName();
        $serial = $asset->GetSerial();
        $comment = "Device Ugrade (from Model:$prev_model, Serial:$prev_serial to Model:$model, Serial:$serial)";

        $this->last_mod_by = $user_id;
        $this->last_mod_date = date('Y-m-d H:i:s');
        ## Dont think this is effected: $this->acq_price += (float)$added_value;
        $this->current_value += (float) $added_value;
        $new_eol = strtotime("+$added_time Months", strtotime($this->fd_date));
        $this->fd_date = date('Y-m-d', $new_eol);
        $sth = $dbh->prepare("UPDATE fas_ownership SET
			last_mod_by = ?,
			last_mod_date = ?,
			asset_id = ?,
			current_value = ?,
			fd_date = ?
		WHERE id = ?");
        $sth->bindValue(1, $this->last_mod_by, PDO::PARAM_INT);
        $sth->bindValue(2, $this->last_mod_date, PDO::PARAM_STR);
        $sth->bindValue(3, $this->asset_id, PDO::PARAM_INT);
        $sth->bindValue(4, $this->current_value, PDO::PARAM_STR);
        $sth->bindValue(5, $this->fd_date, PDO::PARAM_STR);
        $sth->bindValue(6, $this->id, PDO::PARAM_INT);
        $sth->execute();

        $fas_trans = new FASTransaction();
        $fas_trans->ownership_id = $this->id;
        $fas_trans->asset_id = $this->asset_id;
        $fas_trans->trans_tstamp = $this->last_mod_date;
        $fas_trans->trans_type = FASTransaction::$UPGRADE;
        $fas_trans->trans_amount = $added_value;
        $fas_trans->labor_amount = $labor_amount;
        $fas_trans->fd_date = $this->fd_date;
        $fas_trans->tran_comment = $comment;
        $fas_trans->record = ($acp_owned) ? true : false;
        $fas_trans->DBInsert();

        return $this->id;
    }

    /**
     * Add Ugrade records for companies
     *
     * @param integer
     * @param integer
     * @param float
     * @param float
     * @param integer
     */
    static public function UpgradeUnit($old_asset_id, $new_asset_id, $cost, $labor, $life_extension)
    {
        ## Move ownership records to new asset
        ## Add Value to the asset

        ## Current owner
        $own = FASOwnership::GetActiveOwner($old_asset_id);
        $acp_owned = ($own->owning_acct == self::$|| $own->owning_acct == self::$DEFAULT001);

        if ($acp_owned == false)
        {
            $own->asset_id = $new_asset_id;
            $own->Upgrade($cost, $life_extension, $labor, $old_asset_id);
        }

        ## Add Upgrade record
        $acp_own = FASOwnership::GetOwner($old_asset_id, FASOwnership::$DEFAULT001);
        if ($acp_own)
        {
            $acp_own->asset_id = $new_asset_id;
            $acp_own->Upgrade($cost, $life_extension, $labor, $old_asset_id);
        }

        $acl_own = FASOwnership::GetOwner($old_asset_id, FASOwnership::$DEFAULT001);
        if ($acl_own)
        {

            $acl_own->asset_id = $new_asset_id;
            $cost = $cost * FASOwnership::$UPGRADE_MARKUP;
            $labor = $labor * FASOwnership::$UPGRADE_MARKUP;
            $acl_own->Upgrade($cost, $life_extension, $labor, $old_asset_id);
        }

        ## Link all previous transactions to this new asset
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("UPDATE fas_transaction
		SET
			asset_id = ?
		WHERE asset_id = ?");
        $sth->bindValue(1, $new_asset_id, PDO::PARAM_INT);
        $sth->bindValue(2, $old_asset_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Show WriteOff Form
     *
     * @return string (html)
     */
    public function WriteOffForm()
    {
        global $date_format;

        $tstamp = date($date_format);

        $asset = $this->LoadAsset();
        $model = $asset->GetModel()->GetName();
        $serial = $asset->GetSerial();

        # Check Value
        if ($this->owning_acct != self::$&& $this->owning_acct != self::$&& $this->owning_acct != self::$INI)
        {
            $form = "<p class='info' style='margin:0;'>Customer Owned ({$this->owning_acct}).</p>";
            return $form;
        }

        $form = "<form name='oedit' action='asset_database.php' method='POST'>
		<input type='hidden' name='act' value='woa' />

		<input type='hidden' name='clicked_owner_id' id='clicked_owner_id' value='{$this->id}' />
		<input type='hidden' name='ownership_array'  id='ownership_array' value='{$this->id}' />

		<input type='hidden' name='asset_id' value='{$this->asset_id}' />
		<table class='e_form' cellpadding=4 cellspacing=0 border=0>
			<tr><th class='hdr' colspan=2>{$this->owning_acct}<br/>$model - $serial</th></tr>

			<tr>
				<td colspan=2><input type=radio name='setowner' value='{$this->id}' checked onclick='setownershiparray($this->id)'> CHANGE FOR THIS CUSTOMER<br>
				 <input type=radio name='setowner' value='2' onclick='setownershiparray(2)'> CHANGE FOR ALL CHECKED<hr>

				</td>
			</tr>

			<tr>
				<th>As of Date:</th>
				<td>
					<input type='text' id='tstamp' name='tstamp' value='$tstamp' size='10' maxlength='10' />
					<img title='Calendar' alt='Calendar' src='images/calendar-mini.png' id='tstamp_trg' class='form_bttn' />
				</td>
			</tr>
			<tr>
				<th>Disposal Type:</th>
				<td>
					<input type='radio' id='disposal_purchase' name='disposal' value='purchase'/>
					<label for='disposal_purchase' />Purchase</label>
					<input type='radio' id='disposal_oos' name='disposal' value='oos' checked />
					<label for='disposal_oos' />Abandonment (OOS/Scrap)</label>
					<input type='radio' id='disposal_purchase_swap' name='disposal' value='purchase_swap'/>
					<label for='disposal_purchase_swap' />Purchase Swap</label>
				</td>
			</tr>
		</table>
		</form>";

        return $form;
    }

    /**
     * 1) Change Current Value to 0
     * 2) Add remaining to Accumulated Depreciation
     * 3) Add FAS transaction
     *
     * @param string (timestamp)
     * @param boolean
     * @param boolean
     * @param boolean
     * @param boolean
     * @param boolean
     *
     * @return float
     */
    public function WriteOff($tstamp = null, $ap = false, $ad = false, $fully_dep = false, $is_purchase = false, $is_pswap = false)
    {
        $dbh = DataStor::getHandle();

        $dep_amount = 0;

        if (strtotime($tstamp) > 0)
            $tstamp = date('Y-m-d H:i:s', strtotime($tstamp));
        else
            $tstamp = date('Y-m-d H:i:s');

        # Write off asset value
        if ($ap)
        {
            $amount = $this->acq_price * -1;
            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $this->id;
            $fas_trans->asset_id = $this->asset_id;
            if ($is_pswap)
                $fas_trans->trans_type = FASTransaction::$PSWAP;
            else if ($is_purchase)
                $fas_trans->trans_type = FASTransaction::$PURCHASE;
            else
                $fas_trans->trans_type = FASTransaction::$WRITEOFF;
            $fas_trans->trans_tstamp = $tstamp;
            $fas_trans->trans_amount = $amount;
            $fas_trans->tran_comment = "Write off asset value [$amount]";

            ## Look for value from an upgrade
            $upg = $this->GetUpgradeValue();
            if ($upg > 0)
            {
                $amount = $upg * -1;
                $fas_trans->trans_amount += $amount;
                $fas_trans->tran_comment .= " Write off upgrade value [$amount]";
            }
            $fas_trans->DBInsert();
        }

        # Write off accumulated_depreciation
        if ($ad)
        {
            $amount = $this->accumulated_depreciation;
            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $this->id;
            $fas_trans->asset_id = $this->asset_id;
            $fas_trans->trans_type = FASTransaction::$WRITEOFF_AD;
            $fas_trans->trans_tstamp = $tstamp;
            $fas_trans->trans_amount = $amount;
            $fas_trans->tran_comment = "Write off accumulated depreciation";
            $fas_trans->DBInsert();
        }

        ## Comment on the ad at this moment in time (Only used in reporting does not effect value)
        if ($is_pswap)
        {
            $action = "Swap";
            $ref_type = FASTransaction::$PSWAP;
        }
        else if ($is_purchase)
        {
            $action = "Purhcase";
            $ref_type = FASTransaction::$PURCHASE;
        }
        else
        {
            $action = "Scrap";
            $ref_type = FASTransaction::$WRITEOFF;
        }
        $amount = $this->accumulated_depreciation * -1;
        $fas_trans = new FASTransaction();
        $fas_trans->ownership_id = $this->id;
        $fas_trans->asset_id = $this->asset_id;
        $fas_trans->trans_type = FASTransaction::$COMMENT;
        $fas_trans->trans_ref_type = $ref_type;
        $fas_trans->trans_tstamp = $tstamp;
        $fas_trans->trans_amount = $amount;
        $fas_trans->record = false;
        $fas_trans->tran_comment = "Accumulated depreciation at time of $action.";
        $fas_trans->DBInsert();

        # Depreciate remaining value
        if ($fully_dep && (int) $this->current_value != 0)
        {
            # Add depreciate transaction for the asset's remainting value
            $dep_amount = $this->current_value;
            $fas_trans = new FASTransaction();
            $fas_trans->ownership_id = $this->id;
            $fas_trans->asset_id = $this->asset_id;
            $fas_trans->trans_tstamp = $tstamp;
            $fas_trans->trans_type = FASTransaction::$DEPRECIATION;
            $fas_trans->trans_ref_type = $ref_type;
            $fas_trans->trans_amount = $dep_amount * -1;
            $fas_trans->tran_comment = "Fully Depreciate unit (Accelerated)";
            $fas_trans->DBInsert();

            $this->accumulated_depreciation += $dep_amount;
            $this->current_value = 0;
        }

        # Will update ownership record
        $sth = $dbh->prepare("UPDATE fas_ownership SET
			current_value = ?,
			accumulated_depreciation = ?
		WHERE id = ?");
        $sth->bindValue(1, (float) $this->current_value, PDO::PARAM_STR);
        $sth->bindValue(2, (float) $this->accumulated_depreciation, PDO::PARAM_STR);
        $sth->bindValue(3, $this->id, PDO::PARAM_INT);
        $sth->execute();

        return $dep_amount;
    }

    /**
     * Add FAS transactions
     *
     * @param boolean
     * @param string (timestamp)
     * @param string
     *
     * @return float
     */
    public function WriteOn($tstamp = null, $comment = null)
    {
        $dbh = DataStor::getHandle();

        # Write on device value
        $v_comment = trim("$comment Write on device value.");
        $fas_trans = new FASTransaction();
        $fas_trans->ownership_id = $this->id;
        $fas_trans->asset_id = $this->asset_id;

        if (strtotime($tstamp) > 0)
            $fas_trans->trans_tstamp = date('Y-m-d H:i:s', strtotime($tstamp));
        else
            $fas_trans->trans_tstamp = date('Y-m-d H:i:s');

        $fas_trans->trans_type = FASTransaction::$WRITEON;

        $fas_trans->trans_amount = $this->acq_price;
        $fas_trans->tran_comment = $v_comment;

        ## Look for value from an upgrade
        $upg = $this->GetUpgradeValue();
        if ($upg > 0)
        {
            $fas_trans->trans_amount += $upg;
            $fas_trans->tran_comment .= " Write on upgrade value [$upg]";
        }

        $fas_trans->DBInsert();

        # Write on devices accumulated depreciation
        $ad_comment = trim("$comment Write on device accumulated depreciation.");
        $fas_trans = new FASTransaction();
        $fas_trans->ownership_id = $this->id;
        $fas_trans->asset_id = $this->asset_id;

        if (strtotime($tstamp) > 0)
            $fas_trans->trans_tstamp = date('Y-m-d H:i:s', strtotime($tstamp));
        else
            $fas_trans->trans_tstamp = date('Y-m-d H:i:s');

        $fas_trans->trans_type = FASTransaction::$WRITEON_AD;
        $fas_trans->trans_amount = $this->accumulated_depreciation * -1;
        $fas_trans->tran_comment = $ad_comment;
        $fas_trans->DBInsert();


        # Add catchup record for depreciation
        $sth_rm = $dbh->prepare("UPDATE fas_transaction SET
			trans_tstamp = ?,
			record = true
		WHERE record = false
		AND ownership_id = ?
		AND trans_type  = ?");
        $sth_rm->bindValue(1, $tstamp, PDO::PARAM_INT);
        $sth_rm->bindValue(2, $this->id, PDO::PARAM_INT);
        $sth_rm->bindValue(3, FASTransaction::$DEPRECIATION, PDO::PARAM_INT);
        $sth_rm->execute();

        return $this->current_value;
    }
}
?>