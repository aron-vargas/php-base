<?php
/**
 * @package Freedom
 * @author Aron Vargas
 */

/**
 * Class defines Loaner Agreement object
 */
class LoanerAgreement extends BaseClass {
    protected $db_table = 'loaner_agreement';	# string
    protected $p_key = 'loaner_id';				# string

    protected $loaner_id;			# int
    protected $facility_id;			# int
    protected $contract_id;			# int
    protected $sponsor_id;			# int
    protected $active;				# boolean
    protected $daily_rate;			# double
    protected $shipping_charge;		# double
    protected $expiration_date;		# int
    protected $renewal_due_date;	# int

    # extended attributes
    protected $facility_name;		# string
    protected $accounting_id;		# string
    protected $cpt_id;				# int
    protected $install_date;		# datetime
    protected $cancellation_date;	# datetime
    protected $pm_cpm_id;			# int
    protected $region;
    protected $sponsor_name;		# string
    protected $cpm_name;			# string
    protected $pm_cpm_name;			# string
    protected $assets;				# array
    protected $renewal_id;			# int
    protected $renewal;				# object

    public $line_items;

    # Static members
    static public $LOAD_RENEWAL = 1;

    # Shipping items
    static public $RAB_ITEM = 1424;
    static public $RAB_BOX_ITEM = 1425;
    static public $ECT_ITEM = 1426;

    /**
     * Create an copy of an instance
     * and unsets undesired attributes
     *
     * @return object
     */
    public function __clone()
    {
        $this->loaner_id = null;
        $this->renewal_id = null;
        $this->renewal = null;
        $this->active = false;
    }

    /**
     * Create an instance of the LoanerAgreement Class
     *
     * @param int $loaner_id
     *
     * @return object
     */
    public function __construct($loaner_id = 0)
    {
        $this->dbh = DataStor::getHandle();

        if ($loaner_id)
            $this->loaner_id = $loaner_id;

        $this->load();
    }

    /**
     * Set values from corrisponding attributes into the sql statement
     *
     * @param object
     */
    private function BindValues(&$sth, $pkey = 0)
    {
        # Set NULL type if empty
        #
        $sponsor_id_type = ($this->sponsor_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $expiration_date_type = ($this->expiration_date) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $renewal_due_date_type = ($this->renewal_due_date) ? PDO::PARAM_INT : PDO::PARAM_NULL;

        $i = 1;
        $sth->bindValue($i++, $this->facility_id, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->contract_id, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->sponsor_id, $sponsor_id_type);
        $sth->bindValue($i++, (bool) $this->active, PDO::PARAM_BOOL);
        $sth->bindValue($i++, $this->daily_rate, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->shipping_charge, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->expiration_date, $expiration_date_type);
        $sth->bindValue($i++, $this->renewal_due_date, $renewal_due_date_type);

        if ($pkey)
            $sth->bindValue($i, $this->loaner_id, PDO::PARAM_INT);

        return $i;
    }

    /**
     * Deactivate the agreement
     * May also, create a cancellation order for the contract
     *
     * @param array (mixed)
     * @param string (datetime)
     * @param bool
     *
     * @return string
     */
    public function Cancel($item_list, $effective_date, $send = 'none')
    {
        $feedback = "";

        # Define asset status
        $trans_user = new User($this->sponsor_id);
        $status = LeaseAssetTransaction::$TRANSIT;
        $substatus = LeaseAssetTransaction::$RTN;
        $comment = "Loaner cancelled";

        # asset_list can be an array of IDs or array of LeseAssets
        #
        if (count($item_list))
        {
            $line_items = array();
            foreach ($item_list as $item)
            {
                if ($item instanceof LoanerItem)
                    $line = $item;
                else if ($item instanceof ContractLineItem)
                    $line = $item;
                else
                    $line = new LoanerItem($item, true);

                $line_items[] = $line->ToArray();
                $asset_id = $line->GetVar('asset_id');

                # Asset required to be in transit.
                # Customer return is tracked only when assets are In-Transit
                if ($asset_id)
                {
                    $asset = new LeaseAsset($asset_id);
                    $asset->addTransaction($this->facility_id, $status, $substatus, $trans_user, $comment);
                }
                else
                {
                    $feedback .= "Unlinked Asset found. Item: {$line->GetVar('item_code')}\n";
                }

                if ($send == 'none' && $this->IsForCPM())
                    $line->Delete();
                else
                    $line->Delete($effective_date);
            }

            if ($send != 'none')
                $this->CancellationOrder($line_items, $effective_date, $send);

            $feedback .= "(Assets are now $status)\n";
        }

        return $feedback;
    }

    /**
     * Create form for cancelling loaners
     */
    public function CancelForm($args)
    {
        # Determine if the user is the sponsor or supervisor
        # of the loaner agreement
        $editable = $this->Editable();

        $dbh = DataStor::getHandle();

        $hide_overide = ($editable) ? "" : "style='display: none;'";
        $hidden = "";
        $chk_none = "";
        $chk_box = "checked";
        if (isset ($args['line_num']))
        {
            foreach ($args['line_num'] as $line_num)
            {
                $hidden .= "<input type='hidden' name='line_num[]' value='{$line_num}'/>";
            }
        }
        else
        {
            $chk_none = "checked";
        }

        $employee_options = "";
        $sth = $dbh->query("SELECT f.accounting_id, u.firstname, u.lastname
		FROM facilities f
		INNER JOIN users u ON f.cpt_id= u.id
		WHERE f.accounting_id like '___9__'
		ORDER BY u.lastname, u.firstname");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $employee_options .= "<option value='{$row['accounting_id']}'>{$row['lastname']}, {$row['firstname']}</option>";
        }

        $html = "<form name='dlg_form' action='{$_SERVER['PHP_SELF']}' method='post'>
		<input type='hidden' name='act' value='db'/>
		<input type='hidden' name='req' value='update'/>
		<input type='hidden' name='outcome' value='cancel'/>
		<input type='hidden' name='id' value='{$this->loaner_id}'/>
		$hidden
		<table class='e_form' cellspacing=0 cellpadding=4 border=0>
			<tr>
				<th>Effective Date:</th>
				<td>
					<input type='text' id='effective_date' name='effective_date' size='10' maxlength='10'/>
					<img class='form_bttn' src='images/calendar-mini.png' id='calendar_button' alt='Calendar' title='Calendar'/>
				</td>
			</tr>
			<tr>
				<th>Send:</th>
				<td nowrap>
					<input type='radio' id='send_box' name='send' value='box' $chk_box />
					<lable for='send_box'>Rab and Box</lable>
					<input type='radio' id='send_rab' name='send' value='rab'/>
					<lable for='send_rab'>Rab Only</lable>
					<input type='radio' id='send_ect' name='send' value='ect' />
					<lable for='send_ect'>Electronic Call Tag</lable>
					<input type='radio' id='send_none' name='send' value='none' $chk_none />
					<lable for='send_none'>Nothing</lable>
				</td>
			</tr>
			<tr $hide_overide>
				<th>Cancel when empty:</th>
				<td>
					<input type='checkbox' name='force_cxl' value='1' />
				</td>
		</form>";

        return $html;
    }

    /**
     * Create a cancellation order
     * @param string (datetime)
     * @param array
     */
    public function CancellationOrder($line_items, $effective_date, $send)
    {
        global $user;

        $items = array();

        $order = new Order();

        $item_num = 1;

        # Add items based on the send value
        if ($send == 'box')
        {
            # RAB+Box Item
            $items[$item_num] = array(
                'asset_id' => -1,
                'prod_id' => self::$RAB_BOX_ITEM,
                'is_device' => 0,
                'quantity' => 1,
                'item_num' => $item_num,
                'swap_asset_id' => 0,
                'price' => '0.0',
                'uom' => 'EA');
            $item_num++;

            # Add the assets
            foreach ($line_items as $line)
            {
                $items[$item_num] = array(
                    'asset_id' => (int) $line['asset_id'],
                    'prod_id' => $line['prod_id'],
                    'code' => $line['model_name'],
                    'name' => $line['prod_name'],
                    'is_device' => $line['model_id'],
                    'model' => $line['model_id'],
                    'serial_number' => $line['serial_num'],
                    'quantity' => 1,
                    'item_num' => $item_num,
                    'swap_asset_id' => 0,
                    'price' => '0.0',
                    'uom' => 'EA');
                $item_num++;
            }
        }
        else if ($send == 'rab')
        {
            # RAB Item
            $items[$item_num] = array(
                'asset_id' => -1,
                'prod_id' => self::$RAB_ITEM,
                'is_device' => 0,
                'quantity' => 1,
                'item_num' => $item_num,
                'swap_asset_id' => 0,
                'price' => '0.0',
                'uom' => 'EA');
        }
        else if ($send == 'ect')
        {
            # ECT Item
            $items[$item_num] = array(
                'asset_id' => -1,
                'prod_id' => self::$ECT_ITEM,
                'is_device' => 0,
                'quantity' => 1,
                'item_num' => $item_num,
                'swap_asset_id' => 0,
                'price' => '0.0',
                'uom' => 'EA');
        }

        if (count($items) > 0)
        {
            $order->create(array(
                'user_id' => $user->getId(),
                'order_date' => time(),
                'status_id' => Order::$QUEUED,
                'comments' => 'Contract Cancelled',
                'ship_to' => 1,
                'urgency' => 1,
                'inst_date' => $effective_date,
                'facility_id' => $this->facility_id,
                'type_id' => Order::$CANCELLATION_ORDER)
            );

            $order->change('contract_id', $this->contract_id);
            $order->save(array('items' => $items));
        }
    }

    /**
     * When all items are removed the loaner is to be fully canceled
     *
     * @param string (datetime)
     */
    public function CheckIfEmpty($date_cancellation)
    {
        $feedback = "";

        $this->line_items = null;
        $this->loadAssets();

        if (count($this->line_items) == 0)
        {
            $contract = new LoanerContract($this->contract_id);

            # Cancellation will be updated from shipping status update
            # Set the value but do not save to database
            $contract->change('date_cancellation', $date_cancellation);

            $this->change('active', false);
            $this->load();

            $renewal = $this->GetRenewal();
            if ($renewal)
            {
                # "Remove" the renewal record
                $renewal->setVar('status_id', LoanerRenewal::$DELETED_STATUS);
                $renewal->save();
            }

            $feedback = "Loaner Contract#{$this->contract_id} Has been canceled.";
        }

        return $feedback;
    }

    /**
     * Copy array values into attributes
     *
     * @param $form array
     */
    public function copyFromArray($form = null)
    {
        BaseClass::copyFromArray($form);
    }

    /**
     * Execute an database insert statement
     *
     */
    public function db_insert()
    {
        $sth = $this->dbh->prepare("INSERT INTO loaner_agreement
		(facility_id, contract_id, sponsor_id, active, daily_rate, shipping_charge,
		 expiration_date, renewal_due_date)
		 VALUES (?,?,?,?,?,?, ?,?)");
        $this->BindValues($sth);
        $sth->execute();

        $this->loaner_id = $this->dbh->lastInsertId('loaner_agreement_loaner_id_seq');
    }

    /**
     * Execute an database update statement
     *
     */
    public function db_update()
    {
        if ($this->loaner_id)
        {
            $sth = $this->dbh->prepare("UPDATE loaner_agreement
			SET
				facility_id = ?,
				contract_id = ?,
				sponsor_id = ?,
				active = ?,
				daily_rate = ?,
				shipping_charge = ?,
				expiration_date = ?,
				renewal_due_date = ?
			WHERE loaner_id = ?");
            $this->BindValues($sth, $this->loaner_id);
            $sth->execute();
        }
    }

    /**
     * Enforces a single agreement to be active per contract
     *
     */
    public function DeactivateOldAgreements()
    {
        if ($this->loaner_id && $this->contract_id)
        {
            $sth = $this->dbh->prepare("UPDATE loaner_agreement
			SET
				active = false
			WHERE loaner_id != ? AND contract_id = ?");
            $sth->bindValue(1, $this->loaner_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->contract_id, PDO::PARAM_INT);
            $sth->execute();
        }
    }

    /**
     * Build table rows for GUI Display
     *
     * @return string (html)
     */
    public function DeviceRows()
    {
        $tr = "";

        $this->loadAssets();
        $rc = 'on';

        foreach ($this->line_items as $line)
        {
            $serial_num = ($line->GetVar('serial_num')) ? $line->GetVar('serial_num') : "--missing--";

            $tr .= "<div class='dev' id='{$line->GetVar('line_num')}'>
				Model: {$line->GetVar('model_name')} {$line->GetVar('model_description')}<br/>
				Serial Number: <b>{$serial_num}</b>
			</div>";

            $rc = ($rc == 'on') ? 'off' : 'on';
        }

        return $tr;
    }

    /**
     * Display GUI
     *
     * @return string (html)
     */
    public function Display()
    {
        global $date_format;

        # Determine if the user is the sponsor or supervisor
        # of the loaner agreement
        $editable = $this->Editable();

        $facility = new Facility($this->facility_id);

        $install_date = date($date_format, strtotime($this->install_date));

        if ($this->expiration_date)
            $expiration_date = date($date_format, $this->expiration_date);
        else
            $expiration_date = "No Expiration";

        if ($this->renewal_due_date)
            $renewal_due_date = date($date_format, $this->renewal_due_date);
        else
            $renewal_due_date = "Not Set";

        $cancellation_date = "";
        if ($this->cancellation_date)
            $cancellation_date = date($date_format, strtotime($this->cancellation_date));

        $active = ($this->active) ? "Active" : "In-Active";

        if (preg_match('/^...9../', $this->accounting_id))
            $cust_t = "empl";
        else
            $cust_t = "cust";

        $html = "
<style type='text/css'>
.dev
{
	font-size: 9pt;
	color: #333333;
	text-align: left;
	background-color: #FBFBFB;
	border: 1px solid #DBDBDB;
	border-radius: 5px;
	padding: 4px;
	z-index: 10;
	cursor: pointer;
}
.dev_trg
{
	background-color: white;
	border: 2px solid #C5D7EF;
	padding: 4px 4px 35px 4px;
	min-height: 80px;
	overflow: visible;
	position: relative;
}
.help_txt
{
	text-align: center;
	font-size: 8pt;
	color: #AAAAAA;
	z-index: 1;
}
.empl
{
	text-align: left;
	font-size: 9pt;
	font-weight: bold;
	background-color: #C5E3BF;
}
.cust
{
	text-align: left;
	font-size: 9pt;
	font-weight: bold;
	background-color: #F0F8FF;
}
.btn
{
	position: absolute;
	right: 2px;
	bottom: 2px;
	z-index: 20;
	background-color: #FBFBFB;
}

</style>
<script type='text/javascript'>
YAHOO.util.Event.onDOMReady(InitializeDD);
YAHOO.util.Event.onDOMReady(InitializeLP);
</script>
		<table cellspacing='0' cellpadding='2' border='0'>
		<tr><td>
			<table class='form' width='100%' cellspacing='1' cellpadding='4' border='0' style='margin:0;'>
				<tr>
					<th class='sec_hdr' colspan='6'>
						Loaner Agreement for
						<u>{$this->facility_name} ({$this->accounting_id})</u>
					</th>
				</tr>
				<tr>
					<th class='hdr' colspan=2>Customer</th>
					<th class='hdr' colspan=2>Loaner Agreement</th>
					<th class='hdr' colspan=2>Contract Details</th>
				</tr>
				<tr>
					<th class='view'>Name</th>
					<td class='$cust_t'>{$this->facility_name}</td>
					<th class='view'>Loaner #</th>
					<td>{$this->loaner_id}</td>
					<th class='view'>Contract #</th>
					<td><a href='contract_maintenance.php?facility_id={$this->facility_id}&contract_id={$this->contract_id}'>{$this->contract_id}</td>
				</tr>
				<tr>
					<th class='view'>Cust ID</th>
					<td class='$cust_t'>{$this->accounting_id}</td>
					<th class='view'>Daily Rate</th>
					<td>{$this->daily_rate}</td>
					<th class='view'>Install Date</th>
					<td>$install_date</td>
				</tr>
				<tr>
					<th class='view'>CPM</th>
					<td align='left'>{$this->cpm_name}</td>
					<th class='view'>Responsible Party</th>
					<td>{$this->sponsor_name}</td>
					<th class='view'>Expiration Date</th>
					<td id='exp_date'>$expiration_date</td>
				</tr>
				<tr>
					<th class='view'>Region</th>
					<td align='left'>{$this->region}</td>
					<th class='view'>Status</th>
					<td>$active</td>
					<th class='view'>Cancellation Date</th>
					<td id='cancellation_date'>{$cancellation_date}</td>
				</tr>
				<tr>
					<td colspan=2></td>
					<th class='view'>Renewal Due</th>
					<td id='renewal_due_date'>{$renewal_due_date}</td>
					<td colspan=2></td>
				</tr>
			</table>
		</td></tr>
		<tr><td>";

        # Determine customer_type
        if (preg_match('/^...9../', $this->accounting_id))
            $html .= $this->DisplayEmployeeOptions($editable);
        else
            $html .= $this->DisplayCustomerOptions($editable);

        $html .= "
		</td></tr>
		</table>
<div id='load_pnl' style='visibility: none;'>
	<div class='hd'>Working, please wait...</div>
	<div class='bd'><img src=\"images/loading.gif\" /></div>
	<div class='fd'></div>
</div>";

        return $html;
    }

    /**
     * Show equipment options for customer loaners
     *
     * @param boolean
     * @return string (html)
     */
    private function DisplayCustomerOptions($editable)
    {
        $device_rows = $this->DeviceRows();

        $html = "
		<table class='form' width='100%' cellspacing=1 cellpadding=0 border=0 style='margin:0;'>
			<tr>
				<th class='sec_hdr' colspan='4'>Equipment Options</th>
			</tr>
			</tr>
			<tr>
				<th class='hdr' style='padding: 6px;'>Extend (Keep)</th>
				<th class='hdr' style='padding: 6px;'>Cancel (Return)</th>
				<th class='hdr' style='padding: 6px;'>Transfer to Lease</th>
				<th class='hdr' style='padding: 6px;'>Convert to Purchase</th>
			</tr>
			<tr valign='top'>
				<td width='280'>
					<div id='extend_target' class='dev_trg'>
						<div class='help_txt' style='display: none;'>Drop device here</div>
						<button id='extend_btn' class='dev btn' type='button' title='Extend agreement' onclick=\"OutcomeClick({$this->loaner_id},'extend');\">Save</button>
						$device_rows
					</div>
				</td>
				<td width='280'>
					<div id='cancel_target' class='dev_trg'>
						<div class='help_txt'>Drop device here</div>
						<button id='cancel_btn' class='dev btn' type='button' title='Cancel/Return equipment' onclick=\"OutcomeClick({$this->loaner_id},'cancel');\">Save</button>
					</div>
				</td>
				<td width='280'>
					<div id='transfer_target' class='dev_trg'>
						<div class='help_txt'>Drop device here</div>
						<button id='transfer_btn' class='dev btn' type='button' title='Transfer to lease' onclick=\"OutcomeClick({$this->loaner_id},'tolease');\">Save</button>
					</div>
				</td>
				<td width='280'>
					<div id='convert_target' class='dev_trg'>
						<div class='help_txt'>Drop device here</div>
						<button id='convert_btn' class='dev btn' type='button' title='Convert to purchase' onclick=\"OutcomeClick({$this->loaner_id},'convert');\">Save</button>
					</div>
				</td>
			</tr>
		</table>";

        return $html;
    }

    /**
     * Determine if this is editable by the session user
     *
     * @return boolean
     */
    public function Editable()
    {
        global $user;

        # Find User information
        $uid = ($user) ? $user->getId() : null;
        $in_it = ($user) ? $user->inPermGroup(User::$IT) || $user->inPermGroup(User::$LEASING) : false;
        $sponsor = new User($this->sponsor_id);
        $supervisor = $sponsor->getSuperVisor();
        $supervisor_id = ($supervisor) ? $supervisor->getId() : 0;

        # Determine if the user is the sponsor or supervisor
        # of the loaner agreement
        $editable = ($this->sponsor_id == $uid || $supervisor_id == $uid || $in_it);

        return $editable;
    }

    /**
     * Show equipment options for customer loaners
     *
     * @param boolean
     * @return string (html)
     */
    private function DisplayEmployeeOptions($editable)
    {
        $device_rows = $this->DeviceRows();

        if ($editable)
        {
            $help_txt = "Drop device here";
            $btn_cls = "dev btn";

            if (count($this->line_items) == 0)
            {
                $force_cxl = "<button id='force_cancel_btn' class='$btn_cls' type='button' title='Force Cancel' onclick=\"window.location={$_SERVER['PHP_SELF']}?act=cancel&loaner_id={$this->loaner_id}');\">Save</button>";
            }
        }
        else
        {
            $help_txt = "Not authorized to change";
            $btn_cls = " hidden";
        }

        $html = "
		<table class='form' width='100%' cellspacing=1 cellpadding=0 border=0 style='margin:0;'>
			<tr>
				<th class='sec_hdr' colspan='4'>Equipment Options</th>
			</tr>
			</tr>
			<tr>
				<th class='hdr' style='padding: 6px;'>Confirm (Keep)</th>
				<th class='hdr' style='padding: 6px;'>Cancel (Return)</th>
				<th class='hdr' style='padding: 6px;'>Transfer</th>
				<th class='hdr' style='padding: 6px;'>Lost or Stolen</th>
			</tr>
			<tr valign='top'>
				<td width='280' id='confirm_cont'>
					<div id='confirm_target' class='dev_trg'>
						<div class='help_txt' style='display: none;'>$help_txt</div>
						<button id='confirm_btn' class='$btn_cls' type='button' title='Confirm/Keep equipment' onclick=\"OutcomeClick({$this->loaner_id},'confirm');\">Save</button>
						$device_rows
					</div>
				</td>
				<td width='280' id='cancel_cont'>
					<div id='cancel_target' class='dev_trg'>
						<div class='help_txt'>$help_txt</div>
						<button id='cancel_btn' class='$btn_cls' type='button' title='Cancel/Return equipment' onclick=\"OutcomeClick({$this->loaner_id},'cancel');\">Save</button>
					</div>
				</td>
				<td width='280' id='transfer_cont'>
					<div id='transfer_target' class='dev_trg'>
						<div class='help_txt'>$help_txt</div>
						<button id='transfer_btn' class='$btn_cls' type='button' title='Transfer equipment' onclick=\"OutcomeClick({$this->loaner_id},'transfer');\">Save</button>
					</div>
				</td>
				<td width='280' id='lost_cont'>
					<div id='lost_target' class='dev_trg'>
						<div class='help_txt'>$help_txt</div>
						<button id='lost_btn' class='$btn_cls' type='button' title='Report equipment Lost/Stolen' onclick=\"OutcomeClick({$this->loaner_id},'lost');\">Save</button>
					</div>
				</td>
			</tr>
		</table>";

        return $html;
    }

    /**
     * Update the loaner and set new expiration date
     *
     * @return string
     */
    public function Confirm()
    {
        global $date_format;

        $feedback = "";

        if ($this->IsForCPM())
        {
            $this->change('renewal_due_date', LoanerAgreement::NextCPMRenewal());
            $new_date = date($date_format, $this->renewal_due_date);
            $feedback = "Loaner #{$this->loaner_id} Extened to $new_date\n";

            $contract = new LoanerContract($this->contract_id);
            $contract->change('date_cancellation', null);
        }
        else
        {
            $feedback = "Loaner is customer and must be extended within workflow.\n";
        }

        return $feedback;
    }

    /**
     * Returns list of assets linked to the contract
     * Populates equipment array if empty.
     *
     * @param string $default_outcome
     * @return array
     */
    public function GetContractAssets($default_outcome = NULL)
    {
        # Set the default expected outcome
        if ($default_outcome)
            $outcome = $default_outcome;
        else if ($this->isForCPM())
            $outcome = 'extend';
        else
            $outcome = 'transfer';

        if (!is_array($this->assets))
        {
            $this->assets = array();

            # Fill asset Array
            #
            if ($this->contract_id)
            {
                # Add a LeaseAsset instance
                # for each asset in the contrat
                $sth = $this->dbh->prepare("SELECT
					e.asset_id, e.line_num
				FROM contract_line_item e
				WHERE e.asset_id IS NOT NULL
				AND e.contract_id = ?");
                $sth->bindValue(1, $this->contract_id, PDO::PARAM_INT);
                $sth->execute();
                while (list($asset_id, $line_num) = $sth->fetch(PDO::FETCH_NUM))
                {
                    if ($asset_id)
                    {
                        $this->assets[$asset_id] = new LeaseAsset($asset_id);

                        # Set outcome attribute
                        $this->assets[$asset_id]->outcome = $outcome;
                        $this->assets[$asset_id]->line_num = $line_num;
                    }
                }
            }
        }

        return $this->assets;
    }

    /**
     * Return the renewal object
     *
     * @return object
     */
    public function GetRenewal()
    {
        if (!is_object($this->renewal) && !empty ($this->renewal_id))
            $this->renewal = new LoanerRenewal($this->renewal_id);

        return $this->renewal;
    }

    /**
     * Populates this object from the matching record in the database.
     *
     */
    public function load()
    {
        if ($this->loaner_id)
        {
            $sth = $this->dbh->prepare("SELECT
				l.facility_id,
				l.contract_id,
				l.sponsor_id,
				l.active,
				l.daily_rate,
				l.shipping_charge,
				l.expiration_date,
				l.renewal_due_date,
				f.facility_name,
				f.accounting_id,
				f.cpt_id,
				f.pm_cpm_id,
				c.date_install as install_date,
				c.date_cancellation as cancellation_date,
				s.firstname || ' ' || s.lastname as sponsor_name,
				cpm.firstname || ' ' || cpm.lastname as cpm_name,
				r.lastname as region,
				pmcpm.firstname || ' ' || pmcpm.lastname as pm_cpm_name,
				rr.renewal_id
			FROM loaner_agreement l
			INNER JOIN facilities f ON l.facility_id = f.id
			INNER JOIN contract c ON l.contract_id = c.id_contract
			LEFT JOIN users s ON l.sponsor_id = s.id
			LEFT JOIN users cpm ON f.cpt_id = cpm.id
			LEFT JOIN v_users_primary_group upg ON cpm.id = upg.user_id
			LEFT JOIN users r ON upg.group_id = r.id
			LEFT JOIN users pmcpm ON f.pm_cpm_id = pmcpm.id
			LEFT JOIN loaner_renewal rr ON l.loaner_id = rr.orig_loaner_id AND rr.status_id IN (1,2) -- New, Due
			WHERE loaner_id = ?");
            $sth->bindValue(1, $this->loaner_id, PDO::PARAM_INT);
            $sth->execute();
            $this->copyFromArray($sth->fetch(PDO::FETCH_ASSOC));

            $this->SetOrigin();
        }
    }

    /**
     * Assign the accounting_id from the facility record
     */
    public function LoadAccountingId()
    {
        if ($this->facility_id)
        {
            $sth = $this->dbh->prepare("SELECT accounting_id FROM facilities WHERE id = ?");
            $sth->bindValue(1, $this->facility_id, PDO::PARAM_INT);
            $sth->execute();
            $this->accounting_id = $sth->fetchColumn();
        }
    }

    /**
     * Populate line items from database
     */
    public function LoadAssets()
    {
        if ($this->contract_id && !is_array($this->line_items))
        {
            $this->line_items = array();

            $sth = $this->dbh->prepare("SELECT
				l.line_num,					l.contract_id,
				l.item_code,				l.asset_id,
				l.amount,					l.lease_amount,
				l.date_added,				l.date_removed,				l.date_shipped,
				l.maintenance_agreement_id,	l.maintenance_expiration_date,
				l.warranty_option_id,		l.warranty_expiration_date,
				l.pm_expiration_date,
				c.id_contract_type as contract_type,
				a.model_id,				a.serial_num,
				a.facility_id,			a.status,
				a.substatus,
				m.model as model_name,
				m.description as model_description,
				p.id as prod_id,		p.name as prod_name,
				ma.name as maintenance_agreement_name,
				ma.term_interval as maintenance_agreement_term,
				w.warranty_name as warranty_option_name,
				w.year_interval as warranty_option_term
			FROM contract_line_item l
			INNER JOIN contract c ON l.contract_id = c.id_contract
			INNER JOIN products p ON l.item_code = p.code
			LEFT JOIN lease_asset_status a ON l.asset_id = a.id
			LEFT JOIN equipment_models m ON l.item_code = m.model
			LEFT JOIN maintenance_agreement ma ON l.maintenance_agreement_id = ma.id
			LEFT JOIN warranty_option w ON l.warranty_option_id = w.warranty_id
			WHERE l.contract_id = ? AND date_removed IS NULL
			ORDER BY l.line_num");
            $sth->bindValue(1, $this->contract_id, PDO::PARAM_INT);
            $sth->execute();

            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $item = new LoanerItem($row, false);
                $this->line_items[] = $item;
            }
        }
    }

    /**
     * Determine if the facility is a CPM facility
     */
    public function IsForCPM()
    {
        return preg_match('/^\w\w\w9\d\d/', $this->accounting_id);
    }

    /**
     * Determine the next date on which CPM loaners are Due
     *
     * @param integer
     * @return integer
     */
    static public function NextCPMRenewal()
    {
        $today = time();
        $jan15 = strtotime(date('Y-01-15'));
        $june15 = strtotime(date('Y-06-15'));
        $nextjan15 = strtotime('+1 Year', $jan15);
        $nexjune15 = strtotime('+1 Year', $june15);

        # Next due date will be the next cycle date
        #
        if ($today <= $jan15)
            $next_due_date = $june15;
        else if ($today <= $june15)
            $next_due_date = $nextjan15;
        else
            $next_due_date = $nexjune15;

        return $next_due_date;
    }

    /**
     * Update lost assets by setting them to OOS:Lost
     *
     * @param object
     * @param object
     * @param array
     */
    public function OOSAssets($items = array())
    {
        global $user;

        $conf = new TConfig();
        $to = explode(",", $conf->get('asset_notification_list'));

        if (count($items) > 0)
        {
            $facility_id = $this->facility_id;
            $status = LeaseAssetTransaction::$OUT_OF_SERVICE;
            $substatus = LeaseAssetTransaction::$LOST;
            $trans_comment = "Asset was lost while on loan";
            $subject = "Asset was lost while on loan";
            $email_body = "Assets reported as lost or stolen.\n";

            foreach ($items as $line_num)
            {
                $line = new LoanerItem($line_num, false);
                $asset_id = $line->GetVar('asset_id');
                if ($asset_id)
                {
                    $asset = new LeaseAsset($asset_id);
                    $model = $asset->getModel();
                    $tran = $asset->getLastTransaction();
                    $facility = $tran->getFacility();
                    $serial = $asset->GetSerial();

                    ## Add write off transactions
                    FASOwnership::OOS($asset_id, date('Y-m-d H:i:s'));
                }
                else
                {
                    $model = new EquipmentModel($line->GetVar('model_id'));
                    $facility = new Facility($this->facility_id);
                    $serial = "Unknown";
                }

                $email_body .= "    Facility: {$facility->GetName()}, Model: {$model->GetName()}, Serial: {$serial}\n";

                $line->Delete();

                #$facility_change = ($facility_id != $facility->GetId());
                #$status_change = ($status != $tran->GetStatus());
                #$substatus_change = ($substatus != $tran->GetSubstatus());

                # If going to new facility Place the device there
                #if ($facility_change || $status_change || $substatus_change)
                #	$asset->addTransaction($facility_id, $status, $substatus, $user, $trans_comment);
            }

            # Send notification
            Email::sendEmail($to, $user, $subject, $email_body, null, null, null, 80);
        }
    }
    /**
     * Save changes to the database record
     *
     */
    public function save($form = array())
    {
        $this->copyFromArray($form);

        if ($this->loaner_id)
            $this->db_update();
        else
            $this->db_insert();

        if ($this->active)
            $this->DeactivateOldAgreements();

        $this->LogChanges();
    }

    /**
     * Clone original contract and move items
     *
     * @param object (LoanerContract)
     * @param array
     *
     * @return object (LoanerContract)
     */
    private function SplitContract($contract, $items)
    {
        $new_con = clone $contract;
        $new_con->line_items = null;
        $new_con->save();

        $new_lnr = clone $this;
        $new_lnr->setVar('contract_id', $new_con->getVar('id_contract'));
        $new_lnr->save();

        foreach ($items as $line_num)
        {
            $line = new LoanerItem($line_num, false);
            $line->change('contract_id', $new_con->getVar('id_contract'));
        }

        return $new_con;
    }

    /**
     * Detect if this is a pickup for a cancellation.
     * Contracts for a loaner will be cancelled on this date.
     *
     * @param string
     * @param string
     */
    static public function SetBillingDelivery($tracking_num, $delivery_date)
    {
        $dbh = DataStor::getHandle();

        # Find the loaner agreement and contract for this tracking_num.
        #
        # Find all the install orders linked to a loaner contract for the given tracking_number.
        # Only need to look for contracts without a date_billing_start
        #
        $sth = $dbh->prepare("SELECT DISTINCT
			o.contract_id, l.loaner_id
		FROM orders o
		INNER JOIN contract c ON o.contract_id = c.id_contract
		INNER JOIN loaner_agreement l ON o.contract_id = l.contract_id
		WHERE o.type_id = ? -- Install Order
		AND c.date_billing_start IS NULL
		AND (
			o.tracking_num = ? -- Tracking number
			OR o.id::TEXT IN (
				SELECT order_id -- Orders linked to the tracking number via UPS
				FROM ups_shipment
				WHERE return = false AND tracking_num = ?
			)
		)");
        $sth->bindValue(1, Order::$INSTALL_ORDER, PDO::PARAM_INT);
        $sth->bindValue(2, $tracking_num, PDO::PARAM_STR);
        $sth->bindValue(3, $tracking_num, PDO::PARAM_STR);
        $sth->execute();
        while (list($contract_id, $loaner_id) = $sth->fetch(PDO::FETCH_NUM))
        {
            if ($loaner_id && $contract_id)
            {
                # Update billing start for customer loaners
                $lnr_agreement = new LoanerAgreement($loaner_id);

                if ($lnr_agreement->IsForCPM() == false)
                {
                    $lnr_contract = new LoanerContract($contract_id);

                    # $delivery_date will have time component wich needs removed
                    $date_billing_start = date('Y-m-d', strtotime($delivery_date));

                    # Log the change
                    $lnr_contract->AddLogEntry(1, "Update", "Install delivered: Billing Start Date set to {$date_billing_start}");

                    # Update contract
                    $lnr_contract->change('date_billing_start', $date_billing_start);
                }
            }
        }
    }

    /**
     * Detect if this is a pickup for a cancellation.
     * Contracts for a loaner will be cancelled on this date.
     *
     * @param string
     * @param string
     */
    static public function SetCancellationPickup($tracking_num, $pickup_date)
    {
        $dbh = DataStor::getHandle();

        # Find the loaner agreement and contract for this tracking_num.
        #
        # There are a number of scenerios which could effect the results.
        # The goal of this query is to find all the cancellation orders
        # linked to a loaner contract for the given tracking_number.
        #
        $sth = $dbh->prepare("SELECT DISTINCT
			o.contract_id,
			l.loaner_id,
			SUM(CASE WHEN i.date_removed IS NULL THEN 1 ELSE 0 END)
		FROM orders o
		INNER JOIN loaner_agreement l ON o.contract_id = l.contract_id
		LEFT JOIN contract_line_item i ON l.contract_id = i.contract_id
		WHERE o.type_id = ? -- Cancellation Order
		AND (
			o.ret_tracking_num = ? -- Return tracking number
			OR o.id::TEXT IN (
				SELECT order_id -- Orders linked to the tracking number via UPS
				FROM ups_shipment
				WHERE return = true AND tracking_num = ?
			)
		)
		GROUP BY o.contract_id, l.loaner_id");
        $sth->bindValue(1, Order::$CANCELLATION_ORDER, PDO::PARAM_INT);
        $sth->bindValue(2, $tracking_num, PDO::PARAM_STR);
        $sth->bindValue(3, $tracking_num, PDO::PARAM_STR);
        $sth->execute();
        while (list($contract_id, $loaner_id, $num_placed) = $sth->fetch(PDO::FETCH_NUM))
        {
            if ($contract_id && $num_placed < 1)
            {
                $lnr_contract = new LoanerContract($contract_id);
                $lnr_contract->SetCancelation($pickup_date, 1);
            }

            if ($loaner_id && $num_placed < 1)
            {
                # Deactive the loaner agreement
                $lnr_agreement = new LoanerAgreement($loaner_id);
                $lnr_agreement->change('active', false);
            }
        }
    }

    /**
     * Detect if this is a recieved asset is on loan
     * Contracts for a loaner will be cancelled on this date.
     *
     * @param string
     * @param string
     */
    static public function SetCancellationReceived($asset_id)
    {
        $dbh = DataStor::getHandle();

        # Find the loaner agreement and contract for this asset
        #
        # When all assets are received cancel the loaner
        #
        $sth = $dbh->prepare("SELECT
			l.loaner_id,
			l.contract_id,
			ia.line_num,
			SUM(CASE WHEN i.date_removed IS NULL THEN 1 ELSE 0 END)
		FROM loaner_agreement l
		INNER JOIN contract_line_item ia ON l.contract_id = ia.contract_id AND ia.asset_id = ?
		INNER JOIN contract_line_item i ON l.contract_id = i.contract_id
		GROUP BY l.loaner_id, l.contract_id, ia.line_num");
        $sth->bindValue(1, $asset_id, PDO::PARAM_INT);
        $sth->execute();
        while (list($loaner_id, $contract_id, $line_num, $num_placed) = $sth->fetch(PDO::FETCH_NUM))
        {
            # Set cancellation when all assets are no longer placed
            if ($contract_id && $num_placed < 1)
            {
                $lnr_contract = new LoanerContract($contract_id);

                if (!$lnr_contract->GetVar('date_cancellation'))
                    $lnr_contract->SetCancelation(date('Y-m-d'), 1);

                $lnr_contract->ClearAssets($asset_id);
            }

            if ($loaner_id && $num_placed < 1)
            {
                $lnr_agreement = new LoanerAgreement($loaner_id);
                $lnr_agreement->change('active', false);
            }
        }
    }

    /**
     * Convert this to an Associative Array
     *
     * @return array
     */
    public function ToArray()
    {
        global $user;

        $this->loadAssets();
        $assets = array();

        if (is_array($this->line_items))
        {
            foreach ($this->line_items as $dev)
            {
                $assets[] = $dev->ToArray();
            }
        }

        # Determine customer_type
        if (preg_match('/^...9../', $this->accounting_id))
            $customer_type = 'employee';
        else
            $customer_type = 'customer';

        return array(
            'loaner_id' => $this->loaner_id,
            'facility_id' => $this->facility_id,
            'contract_id' => $this->contract_id,
            'sponsor_id' => $this->sponsor_id,
            'active' => $this->active,
            'daily_rate' => $this->daily_rate,
            'shipping_charge' => $this->shipping_charge,
            'expiration_date' => $this->expiration_date,
            'renewal_due_date' => $this->renewal_due_date,
            'cancellation_date' => $this->cancellation_date,
            'facility_name' => $this->facility_name,
            'accounting_id' => $this->accounting_id,
            'customer_type' => $customer_type,
            'renewal_id' => $this->renewal_id,
            'cpt_id' => $this->cpt_id,
            'pm_cpm_id' => $this->pm_cpm_id,
            'cpm_name' => $this->cpm_name,
            'pm_cpm_name' => $this->pm_cpm_name,
            'sponsor_name' => $this->sponsor_name,
            'assets' => $assets
        );
    }

    /**
     * Create form for transfering loaners between employees
     */
    public function TransferForm($args)
    {
        $dbh = DataStor::getHandle();

        $hidden = "";
        if (isset ($args['line_num']))
        {
            foreach ($args['line_num'] as $line_num)
            {
                $hidden .= "<input type='hidden' name='line_num[]' value='{$line_num}'/>";
            }
        }

        $employee_options = "";
        $sth = $dbh->query("SELECT f.accounting_id, u.firstname, u.lastname
		FROM facilities f
		INNER JOIN users u ON f.cpt_id= u.id
		WHERE f.accounting_id like '___9__'
		ORDER BY u.lastname, u.firstname");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $employee_options .= "<option value='{$row['accounting_id']}'>{$row['lastname']}, {$row['firstname']}</option>";
        }

        $html = "<form name='dlg_form' action='{$_SERVER['PHP_SELF']}' method='post'>
		<input type='hidden' name='act' value='db'/>
		<input type='hidden' name='req' value='update'/>
		<input type='hidden' name='outcome' value='transfer'/>
		<input type='hidden' name='id' value='{$this->loaner_id}'/>
		$hidden
		<label for='cust_id'>Transfer To:</label>
		<select name='cust_id'>
			$employee_options
		</select>
		</form>";

        return $html;
    }

    /**
     * Move equipment to another Employee
     */
    public function TransferEquipment($cust_id, $line_nums)
    {
        global $user;

        $dbh = DataStor::getHandle();

        $feedback = "Equipment Moved to ";

        # Find existing loaner for the facility.
        # Active returned first then first inactive
        $sth = $dbh->prepare("SELECT f.id, f.cpt_id, l.loaner_id
		FROM facilities f
		LEFT JOIN loaner_agreement l ON f.id = l.facility_id
		WHERE f.accounting_id = ?
		ORDER BY l.active DESC, l.loaner_id
		LIMIT 1");
        $sth->bindValue(1, $cust_id, PDO::PARAM_STR);
        $sth->execute();
        $row = $sth->fetch(PDO::FETCH_NUM);
        $facility_id = $row[0];
        $sponsor_id = $row[1];
        $loaner_id = $row[2];

        if ($loaner_id)
        {
            # Use the loaner
            $loaner = new LoanerAgreement($loaner_id);

            $contract_id = $loaner->GetVar('contract_id');
            $contract = new LoanerContract($contract_id);

            # Make sure this is active
            $loaner->change('active', true);
            $contract->change('date_cancellation', null);

            $feedback .= "Contract # $contract_id\n";
        }
        else
        {
            $contract = new LoanerContract($this->contract_id);

            # Clone the contract
            $newcontract = clone $contract;

            # Will replace the line items
            $newcontract->line_items = array();

            $newcontract->SetVar('id_facility', $facility_id);
            $newcontract->Save();
            $contract_id = $newcontract->getVar('id_contract');

            $feedback .= "New Contract # $contract_id\n";

            # Clone this loaner
            $loaner = clone $this;
            $loaner->SetVar('sponsor_id', $sponsor_id);
            $loaner->SetVar('facility_id', $facility_id);
            $loaner->SetVar('contract_id', $contract_id);
            $loaner->SetVar('active', true);
            $loaner->save();
        }

        # Move the line items to the new contract
        $email_body = "Equipment has been transferred into your loaner agreement\n" . $feedback;
        foreach ($line_nums as $num)
        {
            $line = new LoanerItem($num, false);
            $line->change('contract_id', $contract_id);

            $email_body .= "Item: {$line->getVar('item_code')}\n";

            $asset_id = $line->GetVar('asset_id');
            if ($asset_id)
            {
                $asset = new LeaseAsset($asset_id);
                $asset->addTransaction($facility_id, LeaseAssetTransaction::$PLACED, LeaseAssetTransaction::$LOAN, $user, "Loaner transferred");
            }
        }

        # Send notification
        $to = new User($sponsor_id);
        Email::sendEmail($to, $user, "Loaner transferred", $email_body, null, null, null, 80);

        return $feedback;
    }

    /**
     * Handler for renewal updates
     *
     * @param array
     * @throws EXception
     * @return string
     */
    public function UpdateRenewal($args)
    {
        global $user;

        # Validate arguments
        #
        $outcome = (isset ($args['outcome'])) ? $args['outcome'] : null;
        $cust_id = (isset ($args['cust_id'])) ? $args['cust_id'] : null;

        $daily_rate = 0;
        if (isset ($args['daily_rate']))
            $daily_rate = (float) preg_replace('[^\d\.]', '', $args['daily_rate']);

        $expiration_date = 0;
        if (isset ($args['expiration_date']))
        {
            // Look for formatted date string
            // Otherwise will assume unix_time
            if (preg_match('/[\-\/]/', $args['expiration_date']))
                $tstamp = strtotime($args['expiration_date']);
            else
                $tstamp = (int) $args['expiration_date'];

            if ($tstamp > 0)
                $expiration_date = $tstamp;
        }

        # Handle empty list
        if (!isset ($args['line_num']))
            $args['line_num'] = array();

        # These must be valid
        if (!$this->loaner_id)
            throw new Exception("No loaner identified.");
        if (!$this->sponsor_id)
            throw new Exception("Missing or Invalid Sponsor.");
        if (!$this->facility_id)
            throw new Exception("Missing or Invalid Cust ID ($cust_id).");
        if (!$outcome)
            throw new Exception("Missing renewal outcome. Unable to process.");

        $orig_contract = new LoanerContract($this->contract_id);
        $this->renewal = new LoanerRenewal($this->renewal_id);
        $renewal = $this->renewal;
        $renewal->setVar('requested_by', $user->getId());
        $renewal->setVar('requested_date', time());
        $renewal->setVar('orig_loaner_id', $this->loaner_id);
        $renewal->setVar('new_sponsor_id', $this->sponsor_id);
        $renewal->setVar('new_facility_id', $this->facility_id);
        $renewal->setVar('new_expiration_date', $this->expiration_date);
        $renewal->setVar('new_daily_rate', $this->daily_rate);

        if ($outcome == 'confirm')
        {
            $renewal->save();
            $renewal->change('status_id', LoanerRenewal::$APPROVED_STATUS);

            $feedback_msg = $this->Confirm();
        }
        else if ($outcome == 'extend')
        {
            # Mark renewal removed and start extension task
            $renewal->setVar('status_id', LoanerRenewal::$NEW_STATUS);
            $renewal->save();

            $feedback_msg = $renewal->WorkFlowExtend($this, $orig_contract, $args['line_num']);
        }
        else if ($outcome == 'cancel')
        {
            $effective_date = $this->ParseDate($args['effective_date']);
            $feedback_msg = $this->Cancel($args['line_num'], $effective_date, $args['send']);
            $force = isset ($args['force_cxl']) ? (bool) $args['force_cxl'] : false;

            if ($this->IsForCPM() || $force)
            {
                # When all items removed cancel this record
                $feedback_msg .= $this->CheckIfEmpty($effective_date);
            }
        }
        else if ($outcome == 'transfer')
        {
            if ($this->IsForCPM())
            {
                $feedback_msg = $this->TransferEquipment($args['cust_id'], $args['line_num']);

                # When all items removed cancel this record
                $feedback_msg .= $this->CheckIfEmpty(date('Y-m-d'));
            }
            else
            {
                $this->LoadAssets();
                if (count($args['line_num']) == count($this->line_items))
                {
                    # When transfering all assets then end the renewal (no split necessary)
                    $split = $orig_contract;
                    $renewal->setVar('status_id', LoanerRenewal::$APPROVED_STATUS);
                }
                else
                {
                    $split = $this->SplitContract($orig_contract, $args['line_num']);
                }

                $renewal->save();
                $feedback_msg = $renewal->WorkFlowTransfer($this, $split, $args['line_num']);
                $this->CheckIfEmpty(date('Y-m-d'));
            }
        }
        else if ($outcome == 'convert')
        {
            $renewal->save();
            $feedback_msg = $renewal->WorkFlowConvert($this, $orig_contract, $args['line_num']);
            $this->CheckIfEmpty(date('Y-m-d'));
        }
        else if ($outcome == 'lost')
        {
            $feedback_msg = $this->OOSAssets($args['line_num']);
            $feedback_msg .= $this->CheckIfEmpty(date('Y-m-d'));
        }
        else
        {
            throw new Exception("Invalid renewal outcome ($outcome). Unable to process.");
        }

        return $feedback_msg;
    }
}
?>