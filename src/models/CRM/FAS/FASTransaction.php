<?php
/**
 * Class defines fixed asset transaction class
 *
 * @package Freedom
 * @author Aron Vargas
 */

/**
 * Class defines fixed asset transaction
 */
class FASTransaction extends BaseClass {
    protected $pkey = 'id';
    protected $db_table = 'fas_transaction';
    protected $id;				# integer
    protected $batch_id;		# integer

    public $ownership_id;		# integer
    public $asset_id;			# integer
    public $created_by;			# integer
    public $trans_tstamp;		# timstamp (string)
    public $trans_type;			# integer
    public $trans_ref_type;		# integer
    public $trans_amount;		# float
    public $labor_amount;		# float
    public $freight_amount;		# float
    public $fd_date; 			# timestamp (string)
    public $status;				# integer
    public $record;				# boolean
    public $po_number;			# string (32)
    public $so_number;			# string (32)
    public $acct_ref_1;			# string (32)
    public $acct_ref_2;			# string (32)
    public $acct_ref_3;			# string (32)
    public $tran_comment;		# string (1024)

    static public $NEWUNIT = 1;
    static public $UPDATE = 2;
    static public $DEPRECIATION = 3;
    static public $MARKUP = 4;
    static public $UPGRADE = 5;
    static public $WRITEOFF = 6;
    static public $WRITEOFF_AD = 7;
    static public $WRITEON = 8;
    static public $COSTADJUSTMENT = 9;
    static public $PURCHASE = 10;
    static public $PSWAP = 11;
    static public $WRITEON_AD = 12;
    static public $COMMENT = 13;

    /**
     * Create a class instance
     */
    public function __construct($id = null)
    {
        global $user;

        # Set defaults
        $today = date('Y-m-d H:i:s');
        $this->id = $id;
        $this->created_by = isset ($user) ? $user->getID() : 1;
        $this->trans_tstamp = $today;
        $this->status = 0;
        $this->record = true;
        $this->trans_amount = 0;
        $this->labor_amount = 0;
        $this->freight_amount = 0;
        $this->fd_date = $today;
        $this->tran_comment = 'No Comment';

        $this->load();
    }

    /**
     * @param array
     */
    public function CopyFromArray($new = array())
    {
        ## More selective than BaseClass
        if (isset ($new['trans_tstamp']))
            $this->trans_tstamp = self::ParseTStamp($new['trans_tstamp']);
        if (isset ($new['trans_type']))
            $this->trans_type = (int) $new['trans_type'];
        if (isset ($new['trans_ref_type']))
            $this->trans_ref_type = (int) $new['trans_ref_type'];
        if (isset ($new['trans_amount']))
            $this->trans_amount = (float) $new['trans_amount'];
        if (isset ($new['labor_amount']))
            $this->labor_amount = (float) $new['labor_amount'];
        if (isset ($new['freight_amount']))
            $this->freight_amount = (float) $new['freight_amount'];
        if (isset ($new['status']))
            $this->status = (int) $new['status'];
        if (isset ($new['record']))
            $this->record = (bool) $new['record'];
        if (isset ($new['po_number']))
            $this->po_number = substr($new['po_number'], 0, 32);
        if (isset ($new['so_number']))
            $this->so_number = substr($new['so_number'], 0, 32);
        if (isset ($new['acct_ref_1']))
            $this->acct_ref_1 = substr($new['acct_ref_1'], 0, 32);
        if (isset ($new['acct_ref_2']))
            $this->acct_ref_2 = substr($new['acct_ref_2'], 0, 32);
        if (isset ($new['acct_ref_3']))
            $this->acct_ref_3 = substr($new['acct_ref_3'], 0, 32);
        if (isset ($new['tran_comment']))
            $this->tran_comment = substr($new['tran_comment'], 0, 1024);
    }

    /**
     *
     * @param integer $default
     * @return string (HTML)
     */
    static public function CreateTypeOptions($default)
    {
        $options = "";
        $dbh = DataStor::getHandle();

        $sth = $dbh->query("SELECT
			id,
			description,
			active
		FROM fas_transaction_type
		ORDER BY sort_order");
        while ($type = $sth->fetch(PDO::FETCH_OBJ))
        {
            if ($type->active || $type->id == $default)
            {
                $sel = ($type->id == $default) ? " selected" : "";
                $options .= "<option value='{$type->id}'$sel>{$type->description}</option>";
            }
        }

        return $options;
    }

    /**
     *
     * @param integer $default
     * @return string (HTML)
     */
    static public function CreateStatusOptions($default)
    {
        $options = "";
        $dbh = DataStor::getHandle();

        $hold = FASBatch::$STATUS_HOLD;
        $new = FASBatch::$STATUS_NEW;
        $proc = FASBatch::$STATUS_PROCESSED;
        $post = FASBatch::$STATUS_POSTED;
        $com = FASBatch::$STATUS_COMMITTED;
        $err = FASBatch::$STATUS_ERROR;

        $sel_hold = ($default == $hold) ? " selected" : "";
        $sel_new = ($default == $new) ? " selected" : "";
        $sel_proc = ($default == $proc) ? " selected" : "";
        $sel_post = ($default == $post) ? " selected" : "";
        $sel_com = ($default == $com) ? " selected" : "";
        $sel_err = ($default == $err) ? " selected" : "";

        return "
		<option value='$hold'$sel_hold>Hold</option>
		<option value='$new'$sel_new>New</option>
		<option value='$proc'$sel_proc>Processed</option>
		<option value='$post'$sel_post>Posted</option>
		<option value='$com'$sel_com>Committed</option>
		<option value='$err'$sel_err>Error</option>";

    }

    /**
     * Assign the values to the PDO statement
     *
     * @param object : $sth
     * @return integer
     */
    private function BindValues(&$sth)
    {
        $i = 1;

        ## Set types for nullable fields
        $batch_type = (is_null($this->batch_id)) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $ref_type = (is_null($this->trans_ref_type)) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $amount_type = (is_null($this->trans_amount)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $labor_type = (is_null($this->labor_amount)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $freight_type = (is_null($this->freight_amount)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $po_type = ($this->po_number) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $so_type = ($this->so_number) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $ref_1_type = (is_null($this->acct_ref_1)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $ref_2_type = (is_null($this->acct_ref_2)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $ref_3_type = (is_null($this->acct_ref_3)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $comment_type = (is_null($this->tran_comment)) ? PDO::PARAM_NULL : PDO::PARAM_STR;

        ## Set the values
        $sth->bindValue($i++, $this->ownership_id, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->asset_id, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->batch_id, $batch_type);
        $sth->bindValue($i++, $this->created_by, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->trans_tstamp, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->trans_type, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->trans_ref_type, $ref_type);
        $sth->bindValue($i++, (float) $this->trans_amount, $amount_type);
        $sth->bindValue($i++, (float) $this->labor_amount, $labor_type);
        $sth->bindValue($i++, (float) $this->freight_amount, $freight_type);
        $sth->bindValue($i++, $this->fd_date, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->status, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->record, PDO::PARAM_BOOL);
        $sth->bindValue($i++, $this->po_number, $po_type);
        $sth->bindValue($i++, $this->so_number, $so_type);
        $sth->bindValue($i++, $this->acct_ref_1, $ref_1_type);
        $sth->bindValue($i++, $this->acct_ref_2, $ref_2_type);
        $sth->bindValue($i++, $this->acct_ref_3, $ref_3_type);
        $sth->bindValue($i++, substr($this->tran_comment, 0, 1024), $comment_type);

        return $i;
    }

    /**
     * Populate attributes from DB record
     *
     * @return integer
     */
    public function DBInsert()
    {
        global $user;

        $dbh = DataStor::getHandle();

        ## Already exists
        if ($this->id)
            return;

        if (is_null($this->created_by))
            $this->created_by = isset ($user) ? $user->getID() : 1;

        if (is_null($this->trans_tstamp))
            $this->trans_tstamp = date('Y-m-d H:i:s');

        $sth = $dbh->prepare("INSERT INTO fas_transaction (
			ownership_id, asset_id, batch_id, created_by, trans_tstamp, trans_type, trans_ref_type,
			trans_amount, labor_amount, freight_amount, fd_date,
			status, record, po_number, so_number,
			acct_ref_1, acct_ref_2, acct_ref_3, tran_comment)
		VALUES (?,?,?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?)");
        $this->BindValues($sth);
        $sth->execute();

        $this->id = $dbh->lastInsertId('fas_transaction_id_seq');

        return $this->id;
    }

    /**
     * limited updates
     *
     * @return boolean
     */
    public function DBUpdate()
    {
        $dbh = DataStor::getHandle();

        if (is_null($this->trans_tstamp))
            $this->trans_tstamp = date('Y-m-d H:i:s');

        ## Set null type if missing
        $ref_type = (empty ($this->trans_ref_type)) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $amount_type = (is_null($this->trans_amount)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $labor_type = (is_null($this->labor_amount)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $freight_type = (is_null($this->freight_amount)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $po_type = ($this->po_number) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $so_type = ($this->so_number) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $ref_1_type = (is_null($this->acct_ref_1)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $ref_2_type = (is_null($this->acct_ref_2)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $ref_3_type = (is_null($this->acct_ref_3)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $comment_type = (is_null($this->tran_comment)) ? PDO::PARAM_NULL : PDO::PARAM_STR;

        $sth = $dbh->prepare("UPDATE fas_transaction SET
			trans_tstamp = ?,
			trans_type = ?,
			trans_ref_type = ?,
			trans_amount = ?,
			labor_amount = ?,
			freight_amount = ?,
			status = ?,
			record = ?,
			po_number = ?,
			so_number = ?,
			acct_ref_1 = ?,
			acct_ref_2 = ?,
			acct_ref_3 = ?,
			tran_comment = ?
		WHERE id = ?");
        $i = 1;
        $sth->bindValue($i++, $this->trans_tstamp, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->trans_type, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->trans_ref_type, $ref_type);
        $sth->bindValue($i++, (float) $this->trans_amount, $amount_type);
        $sth->bindValue($i++, (float) $this->labor_amount, $labor_type);
        $sth->bindValue($i++, (float) $this->freight_amount, $freight_type);
        $sth->bindValue($i++, $this->status, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->record, PDO::PARAM_BOOL);
        $sth->bindValue($i++, $this->po_number, $po_type);
        $sth->bindValue($i++, $this->so_number, $so_type);
        $sth->bindValue($i++, $this->acct_ref_1, $ref_1_type);
        $sth->bindValue($i++, $this->acct_ref_2, $ref_2_type);
        $sth->bindValue($i++, $this->acct_ref_3, $ref_3_type);
        $sth->bindValue($i++, substr($this->tran_comment, 0, 1024), $comment_type);
        $sth->bindValue($i++, $this->id, PDO::PARAM_INT);
        $sth->execute();

        # Aquition price needs to match the new unit transaction
        if ($this->trans_type == self::$NEWUNIT)
        {
            $sth = $dbh->prepare("UPDATE fas_ownership SET
			acq_price = ?,
  			freight_amount = ?
			WHERE id = ?");
            $sth->bindValue(1, (float) $this->trans_amount, $amount_type);
            $sth->bindValue(2, (float) $this->labor_amount, $labor_type);
            $sth->bindValue(3, $this->ownership_id, PDO::PARAM_INT);
            $sth->execute();
        }
    }

    /**
     * Show Manual edit Form
     *
     * @return string (html)
     */
    public function DialogForm()
    {
        global $date_format;

        $trans_tstamp = date($date_format, strtotime($this->trans_tstamp));

        $type_options = self::CreateTypeOptions($this->trans_type);
        $ref_type_options = self::CreateTypeOptions($this->trans_ref_type);
        $status_options = self::CreateStatusOptions($this->status);

        if ($this->trans_type != self::$NEWUNIT)
            $status_options .= "\n		<option value='-1'>RM</option>";

        $chk_record_y = ($this->record) ? "checked" : "";
        $chk_record_n = ($this->record) ? "" : "checked";

        $form = "<form name='oedit' action='asset_database.php' method='POST'>
		<input type='hidden' name='act' value='tedit' />
		<input type='hidden' name='id' value='{$this->id}' />
		<input type='hidden' name='rm' value='0' />
		<table class='e_form' cellpadding=4 cellspacing=0 border=0>
			<tr>
				<td>
					<label for='trans_tstamp'>Transaction Date:</label><br/>
					<input type='text' id='trans_tstamp' name='trans_tstamp' value='$trans_tstamp' size='10' maxlength='10' />
					<img title='Calendar' alt='Calendar' src='images/calendar-mini.png' id='trans_tstamp_trg' class='form_bttn' />
				</td>
				<td>
					<label for='trans_type'>Type:</label><br/>
					<select name='trans_type'>
						$type_options
					</select>
				</td>
				<td>
					<label for='trans_ref_type'>Reference Type:</label><br/>
					<select name='trans_ref_type'>
						<option value=''>None</option>
						$ref_type_options
					</select>
				</td>
				<td>
					<label for='status'>Status:</label><br/>
					<select name='status' onchange=\"this.form.rm.value = (this.value == -1) ? '1' : '0'\">
						$status_options
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<label for='trans_amount'>Transaction Amount:</label><br/>
					<input type='text' id='trans_amount' name='trans_amount' value='{$this->trans_amount}' size='10' maxlength='10'/>
				</td>
				<td>
					<label for='freight_amount'>Freight Amount:</label><br/>
					<input type='text' id='freight_amount' name='freight_amount' value='{$this->freight_amount}' size='10' maxlength='10'/>
				</td>
				<td>
					<label for='labor_amount'>Labor Amount:</label><br/>
					<input type='text' id='labor_amount' name='labor_amount' value='{$this->labor_amount}' size='10' maxlength='10'/>
				</td>
				<td>
					<label>Record:</label><br/>
					<input type=radio id='record_y' name='record' value='1' $chk_record_y />
					<label for='record_y'>Yes</label>
					<input type=radio id='record_n' name='record' value='0' $chk_record_n />
					<label for='record_n'>No</label>
				</td>
			</tr>
			<tr>
				<td colspan='2'>
					<label for='po_number'>PO Number:</label><br/>
					<input type='text' id='po_number' name='po_number' value='{$this->po_number}' size='20' maxlength='32'/>
				</td>
				<td colspan='2'>
					<label for='so_number'>SO Number:</label><br/>
					<input type='text' id='so_number' name='so_number' value='{$this->so_number}' size='20' maxlength='32'/>
				</td>
			</tr>
			<tr>
				<td>
					<label for='acct_ref_1'>Reference Fld1:</label><br/>
					<input type='text' id='acct_ref_1' name='acct_ref_1' value='{$this->acct_ref_1}' size='20' maxlength='32'/>
				</td>
				<td>
					<label for='acct_ref_2'>Reference Fld2:</label><br/>
					<input type='text' id='acct_ref_2' name='acct_ref_2' value='{$this->acct_ref_2}' size='20' maxlength='32'/>
				</td>
				<td>
					<label for='acct_ref_3'>Reference Fld3:</label><br/>
					<input type='text' id='acct_ref_3' name='acct_ref_3' value='{$this->acct_ref_3}' size='20' maxlength='32'/>
				</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td colspan='4'>
					<label for='tran_comment'>Comment:</label><br/>
					<textarea cols='80' rows='2'  name='tran_comment'>{$this->tran_comment}</textarea>
				</td>
			</tr>
		</table>
		</form>";

        return $form;
    }

    /**
     * Get defaults from ownership record
     */
    public function LoadOwnerData()
    {
        if ($this->ownership_id)
        {
            $sth = $dbh->prepare("SELECT
				o.asset_id, o.fd_date
			FROM fas_transaction t
			WHERE t.id = ?");
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
     * Populate attributes from DB record
     */
    public function Load()
    {
        $dbh = DataStor::getHandle();

        if ($this->id)
        {
            $sth = $dbh->prepare("SELECT
				t.ownership_id, t.asset_id, t.batch_id, t.created_by,
				t.trans_tstamp, t.trans_type, t.trans_ref_type,
				t.trans_amount, t.labor_amount, t.freight_amount,
				t.fd_date, t.status, t.record, t.po_number, t.so_number,
				t.acct_ref_1, t.acct_ref_2, t.acct_ref_3, t.tran_comment
			FROM fas_transaction t
			WHERE t.id = ?");
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
     * Perform DB Delete query
     */
    public function RMTrx()
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("DELETE FROM fas_transaction WHERE id = ?");
        $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Update database record
     * @param integer $rm
     */
    public function Save($rm = null)
    {
        if ($rm)
            $this->RMTrx();
        else
        {
            if ($this->id)
                $this->DBUpdate();
            else
                $this->DBInsert();
        }
    }

    /**
     * Update first new unit record for the asset
     *
     * @param integer
     * @param float
     * @param float
     */
    static public function UpdateAcqPrice($ownership_id, $acq_price, $freight_amount)
    {
        $dbh = DataStor::getHandle();
        $sql = "SELECT MIN(id)
		FROM fas_transaction
		WHERE ownership_id = ? AND trans_type = ?";
        $sth = $dbh->prepare($sql);
        $sth->bindValue(1, (int) $ownership_id, PDO::PARAM_INT);
        $sth->bindValue(2, self::$NEWUNIT, PDO::PARAM_INT);
        $sth->execute();
        $id = $sth->fetchColumn();

        if ($id)
        {
            $sql = "UPDATE fas_transaction
			SET trans_amount = ?, freight_amount = ?
			WHERE id = ?";
            $sth = $dbh->prepare($sql);
            $sth->bindValue(1, (double) $acq_price, PDO::PARAM_STR);
            $sth->bindValue(2, (double) $freight_amount, PDO::PARAM_STR);
            $sth->bindValue(3, (int) $id, PDO::PARAM_INT);
            $sth->execute();
        }
    }
}
?>