<?php
/**
 * @package Freedom
 * @author Aron Vargas
 */

/**
 * Class defines Loaner Contract object
 */
class LoanerRenewal extends BaseClass {
    protected $db_table = 'loaner_renewal';	# string
    protected $p_key = 'renewal_id';				# string

    protected $renewal_id;				# int
    protected $orig_loaner_id;			# int
    protected $new_loaner_id;			# int
    protected $new_sponsor_id;			# int
    protected $new_facility_id;			# int
    protected $new_expiration_date;		# int
    protected $new_daily_rate;			# float
    protected $request_id;				# int
    protected $transfer_id;				# int
    protected $nfif_id;					# int
    protected $cancellation_order_id;	# int
    protected $requested_by;			# int
    protected $requested_date;			# int
    protected $approved_by;				# int
    protected $approved_date;			# int
    protected $status_id = 1;			# int

    # extended attributes
    protected $outcomes;				# array
    protected $status_txt;				# int
    protected $requested_by_name;		# int
    protected $approved_by_name;		# int
    protected $new_facility_name;		# string
    protected $new_accounting_id;		# string

    # Static Vars
    static public $NEW_STATUS = 1;
    static public $DUE_STATUS = 2;
    static public $PENDING_STATUS = 3;
    static public $APPROVED_STATUS = 4;
    static public $DENIED_STATUS = 5;
    static public $DELETED_STATUS = 6;

    static public $REQUEST_STAGE = 1;
    static public $APPROVAL_STAGE = 2;
    static public $COMPLETE_STAGE = 3;

    static public $TRANSFER_PROCESS = 4;
    static public $TRANSFER_FIRST_STAGE = 7;
    static public $PURCHASE_PROCESS = 6;
    static public $PURCHASE_FIRST_STAGE = 11;
    static public $LOANER_PROCESS = 7;
    static public $LOANER_FIRST_STAGE = 13;

    static public $DEFAULT_WARRANTY_OPTION = 7;
    static public $LEASING_PERM_GROUP = 4;

    public function __construct($renewal_id = 0)
    {
        $this->dbh = DataStor::getHandle();

        if ($renewal_id)
            $this->renewal_id = $renewal_id;

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
        $new_loaner_id_type = ($this->new_loaner_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $new_sponsor_id_type = ($this->new_sponsor_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $new_facility_id_type = ($this->new_facility_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $new_expiration_date_type = ($this->new_expiration_date) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $request_id_type = ($this->request_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $transfer_id_type = ($this->transfer_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $nfif_id_type = ($this->nfif_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $cancellation_order_id_type = ($this->cancellation_order_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $approved_by_type = ($this->approved_by) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $approved_date_type = ($this->approved_date) ? PDO::PARAM_INT : PDO::PARAM_NULL;

        $i = 1;
        $sth->bindValue($i++, $this->orig_loaner_id, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->new_loaner_id, $new_loaner_id_type);
        $sth->bindValue($i++, $this->new_sponsor_id, $new_sponsor_id_type);
        $sth->bindValue($i++, $this->new_facility_id, $new_facility_id_type);
        $sth->bindValue($i++, $this->new_expiration_date, $new_expiration_date_type);
        $sth->bindValue($i++, (float) $this->new_daily_rate, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->request_id, $request_id_type);
        $sth->bindValue($i++, $this->transfer_id, $transfer_id_type);
        $sth->bindValue($i++, $this->nfif_id, $nfif_id_type);
        $sth->bindValue($i++, $this->cancellation_order_id, $cancellation_order_id_type);
        $sth->bindValue($i++, $this->requested_by, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->requested_date, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->approved_by, $approved_by_type);
        $sth->bindValue($i++, $this->approved_date, $approved_date_type);
        $sth->bindValue($i++, $this->status_id, PDO::PARAM_INT);

        if ($pkey)
            $sth->bindValue($i, $this->renewal_id, PDO::PARAM_INT);

        return $i;
    }

    /**
     * Create a new cancellation order for the given assets
     *
     * @param object
     * @param object
     * @param array
     */
    public function Cancel($o_loaner, $o_contract, $asset_list = array())
    {
        global $user;

        if (count($asset_list) > 0)
        {
            $facility_id = $o_contract->getVar('id_facility');
            $facility = new CustomerEntity($facility_id);

            # Define new asset status
            $trans_user = new User($this->new_sponsor_id);
            $status = LeaseAssetTransaction::$TRANSIT;
            $substatus = LeaseAssetTransaction::$RTN;
            $comment = "Loaner cancelled";

            # Initialize the item array
            #
            $items = array();
            $item_num = 1;
            $sth = $this->dbh->prepare("SELECT
				l.id as asset_id,	l.model_id,			l.serial_num as serial_number,
				p.id as prod_id,	p.code,				p.name,
				p.max_quantity,		m.id as is_device,	m.id as model
			FROM lease_asset l
			INNER JOIN equipment_models m ON l.model_id = m.id
			INNER JOIN products p ON p.code = m.model
			WHERE l.id = ?");
            foreach ($asset_list as $asset_id)
            {
                # Define asset as a line item
                $sth->bindValue(1, (int) $asset_id, PDO::PARAM_INT);
                $sth->execute();
                if ($item = $sth->fetch(PDO::FETCH_ASSOC))
                {
                    # Add item to the array
                    $item['item_num'] = $item_num;
                    $item['quantity'] = 1;
                    $item['swap_asset_id'] = 0;
                    $item['price'] = '0.0';
                    $item['uom'] = 'EA';
                    $items[$item_num++] = $item;
                }

                # Asset required to be in transit.
                # Customer return is tracked only when assets are In-Transit
                #
                $asset = new LeaseAsset($asset_id);
                $asset->addTransaction($facility_id, $status, $substatus, $trans_user, $comment);
                $o_contract->ClearAssets($asset->GetId());
            }

            # Create the order
            $order = new Order($this->cancellation_order_id);
            $order->setVar('status_id', Order::$PROCESSED);
            $order->setVar('type_id', Order::$CANCELLATION_ORDER);
            $order->setVar('user_id', $user->getId());
            $order->setVar('facility_id', $facility_id);
            $order->setVar('order_date', time());
            $order->setVar('comments', 'Loaner Contract Cancelled');
            $order->setVar('ship_to', 1);
            $order->setVar('urgency', 1);
            $order->setVar('inst_date', $o_loaner->getVar('expiration_date'));
            $order->setVar('sname', $facility->getName());
            $order->setVar('city', $facility->getCity());
            $order->setVar('state', $facility->getState());
            $order->setVar('zip', $facility->getZip());
            $order->setVar('address', $facility->getAddress());
            $order->setVar('address2', $facility->getAddress2());
            $order->save(array('items' => $items));

            # Not updated in the save method
            $order->change('contract_id', $o_contract->getVar('id_contract'));

            # Set the link to the order
            if (!$this->cancellation_order_id)
                $this->change('cancellation_order_id', $order->getVar('id'));
        }
    }

    /**
     * Create a new customer purchase contract
     *
     * @param object
     * @param object
     * @param array
     */
    public function Convert($o_loaner, $o_contract, $asset_list = array())
    {
        global $user;

        if (count($asset_list) > 0)
        {
            # Use original loaners expiration date as the new contract starting point
            #
            if ($o_loaner->getVar('expiration_date'))
                $start_date = date('Y-m-d', $o_loaner->getVar('expiration_date'));
            else
                $start_date = date('Y-m-d');

            # Create a contract
            #
            $n_contract = new LeaseContract();
            $id = $this->SaveContract($n_contract, LeaseContract::$PURCHASE_TYPE, $start_date, $asset_list);

            # If going to new facility Place the asset there
            #
            if ($o_loaner->getVar('facility_id') != $this->new_facility_id)
            {
                $substatus = LeaseAssetTransaction::$PURCHASE;
                $comment = "Loaner converted to purchase";

                $this->PlaceAssets($n_contract->line_items, $substatus, $comment);
            }
        }
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
     * Build an array of equipment used in contract
     *
     * @param array
     * @return array
     */
    private function CreateContractEquipment($items, $lease_type)
    {
        # Various line item arrays
        #
        $line_items = array();
        $monthly_revenue = 0;
        foreach ($items as $line_item)
        {
            $line = new LoanerItem($line_item);
            $item = $line->ToArray();

            if ($lease_type != LeaseContract::$PURCHASE_TYPE)
                $monthly_revenue += $item['amont'];

            if ($lease_type != LeaseContract::$LOANER_TYPE)
                $item['prod_id'] = ContractLineItem::ConvertToSRV($item['prod_id']);

            $line_items[] = $item;
        }

        return array(
            'monthly_revenue' => $monthly_revenue,
            'line_items' => $line_items);
    }

    /**
     * Build a contract item array from the asset id
     *
     * @param integer
     * @return array
     */
    private function CreateLoanerItem($asset_id)
    {
        # Query for the proper product id and price
        $sth = $this->dbh->prepare("SELECT
			a.id as asset_id,
			p.id as prod_id
		FROM lease_asset a
		INNER JOIN loaner_renewal_outcome o ON a.id = o.asset_id
		INNER JOIN equipment_models m ON a.model_id = m.id
		INNER JOIN products p ON m.model = p.code
		WHERE a.id = ?
		AND o.renewal_id = ?");
        $sth->bindValue(1, $asset_id, PDO::PARAM_INT);
        $sth->bindValue(2, $this->renewal_id, PDO::PARAM_INT);
        $sth->execute();
        $item = $sth->fetch(PDO::FETCH_ASSOC);

        $item['quantity'] = 1;
        $item['price'] = 0;
        $item['expiration_date'] = null;
        $item['date_shipped'] = null;

        return $item;
    }

    /**
     * Build an array of addresss used in work flow application
     *
     * @param object (Facility)
     * @return array
     */
    private function CreateWFAddresses($facility)
    {
        $addr_ary = array();

        $sth = $this->dbh->prepare("
		SELECT
			j.default_shipping,
			j.default_billing,
			c.contact_id,
			c.first_name,
			c.last_name,
			c.accounting_reference_key AS addr_key,
			c.title,
			c.address1,
			c.address2,
			c.city,
			rtrim(ltrim(c.state)) AS state,
			c.zip,
			c.phone,
			c.fax
		FROM contact c
		  INNER JOIN facility_contact_join j ON c.contact_id = j.contact_id
		WHERE j.facility_id = ?");
        $sth->bindValue(1, $facility->getId(), PDO::PARAM_STR);
        $sth->execute();
        while ($addr = $sth->fetch(PDO::FETCH_ASSOC))
        {
            # Shipping Address
            if ($addr['default_shipping'])
                $addr_ary['ship_contact'] = $addr;
            if (!isset ($addr_ary['ship_contact']) && stristr("shipping", $addr['first_name']))
                $addr_ary['ship_contact'] = $addr;

            # Billing Address
            if ($addr['default_billing'])
                $addr_ary['bill_contact'] = $addr;
            if (!isset ($addr_ary['bill_contact']) && stristr("billing", $addr['first_name']))
                $addr_ary['bill_contact'] = $addr;

            # Contract Address
            if (stristr("cntctaddr", $addr['first_name']))
                $addr_ary['con_contact'] = $addr;
        }

        $corp_parent = new CorporateOffice($facility->getCorporateParent());

        # Address from facility record
        $fac_address = array(
            'address1' => $facility->getAddress(),
            'address2' => $facility->getAddress2(),
            'city' => $facility->getCity(),
            'state' => $facility->getState(),
            'zip' => $facility->getZip(),
            'phone' => $facility->getPhone(),
            'fax' => $corp_parent->getFax()); # Note: No fax stored with facility address

        # Address from corporate office record
        $cp_address = array(
            'address1' => $corp_parent->getAddress1(),
            'address2' => $corp_parent->getAddress2(),
            'city' => $corp_parent->getCity(),
            'state' => $corp_parent->getState(),
            'zip' => $corp_parent->getZip(),
            'phone' => $corp_parent->getPhone(),
            'fax' => $corp_parent->getFax());

        # Shipping Address
        if (!isset ($addr_ary['ship_contact']))
            $addr_ary['ship_contact'] = $fac_address;

        # Supply billing address
        if (!isset ($addr_ary['bill_contact']))
        {
            if ($corp_parent->supply_bill_to_corporate)
                $addr_ary['bill_contact'] = $cp_address;
            else
                $addr_ary['bill_contact'] = $fac_address;
        }

        # Contract billing address
        if (!isset ($addr_ary['con_contact']))
        {
            if ($corp_parent->monthly_bill_to_corporate)
                $addr_ary['con_contact'] = $cp_address;
            else
                $addr_ary['con_contact'] = $fac_address;
        }

        return $addr_ary;
    }

    /**
     * Execute an database insert statement
     */
    public function db_insert()
    {
        global $user;

        # Validate NON NULL "requested" attributes
        #
        if (!$this->requested_by)
            $this->requested_by = $user->getId();

        if (!$this->requested_date)
            $this->requested_date = time();

        $sth = $this->dbh->prepare("INSERT INTO loaner_renewal
		(orig_loaner_id, new_loaner_id, new_sponsor_id,	new_facility_id,
		 new_expiration_date, new_daily_rate, request_id, transfer_id, nfif_id,
		 cancellation_order_id, requested_by, requested_date,
		 approved_by, approved_date, status_id)
		 VALUES (?,?,?,?, ?,?,?,?,?, ?,?,?, ?,?,?)");
        $this->BindValues($sth);
        $sth->execute();

        $this->renewal_id = $this->dbh->lastInsertId('loaner_renewal_renewal_id_seq');
    }

    /**
     * Execute an database update statement
     */
    public function db_update()
    {
        if ($this->renewal_id)
        {
            $sth = $this->dbh->prepare("UPDATE loaner_renewal
			SET
				orig_loaner_id = ?,
				new_loaner_id = ?,
				new_sponsor_id = ?,
				new_facility_id = ?,
				new_expiration_date = ?,
				new_daily_rate = ?,
				request_id = ?,
				transfer_id = ?,
				nfif_id = ?,
				cancellation_order_id = ?,
				requested_by = ?,
				requested_date = ?,
		 		approved_by = ?,
				approved_date = ?,
				status_id = ?
			WHERE renewal_id = ?");
            $this->BindValues($sth, $this->renewal_id);
            $sth->execute();
        }
    }

    /**
     * Create a new loaner from original, with the given assets
     *
     * @param object
     * @param object
     * @param array
     *
     * @return string
     */
    public function Extend($o_loaner, $o_contract, $asset_list = array())
    {
        global $user;

        $feedback = "";

        if (count($asset_list) > 0)
        {
            # Initially the new loaner (will be a clone of the original)
            #
            $new_loaner_id = $this->getVar('new_loaner_id');
            if ($new_loaner_id)
                $n_loaner = new LoanerAgreement($new_loaner_id);
            else
                $n_loaner = clone $o_loaner;

            # Use original loaners expiration date as the new contract starting point
            #
            if ($o_loaner->getVar('expiration_date'))
                $start_date = date('Y-m-d', $o_loaner->getVar('expiration_date'));
            else
                $start_date = date('Y-m-d');

            # Create a contract
            #
            $new_contract_id = $n_loaner->getVar('contract_id');
            if ($new_contract_id)
                $n_contract = new LoanerContract($new_contract_id);
            else
                $n_contract = clone $o_contract;

            # Save contract record
            #
            $new_contract_id = $this->SaveContract($n_contract, LeaseContract::$LOANER_TYPE, $start_date, $asset_list);

            # If going to new facility Place the asset there
            #
            if ($o_loaner->getVar('facility_id') != $this->new_facility_id)
            {
                $substatus = LeaseAssetTransaction::$LOAN;
                $comment = "Loaner extended at new facility";

                $this->PlaceAssets($n_contract->line_items, $substatus, $comment);
            }

            # Save the new Loaner Agreement
            #
            $n_loaner->setVar('sponsor_id', $this->new_sponsor_id);
            $n_loaner->setVar('facility_id', $this->new_facility_id);
            $n_loaner->setVar('contract_id', $new_contract_id);
            $n_loaner->setVar('expiration_date', $this->new_expiration_date);
            $n_loaner->setVar('daily_rate', $this->new_daily_rate);
            $n_loaner->setVar('active', true);

            if ($n_loaner->IsForCPM())
                $n_loaner->setVar('renewal_due_date', LoanerAgreement::NextCPMRenewal($o_loaner->getVar('renewal_due_date')));
            else
                $n_loaner->setVar('renewal_due_date', $this->new_expiration_date);

            $n_loaner->save();

            # Set the link to the new loaner
            #
            if (!$new_loaner_id)
                $this->change('new_loaner_id', $n_loaner->getVar('loaner_id'));

            $feedback = "Loaner #{$this->orig_loaner_id} Extened, New Loaner #{$this->new_loaner_id}<br/>\n";
        }

        return $feedback;
    }

    /**
     * Returns list of assets linked to the renew request
     *
     * @param integer
     * @return array
     */
    public function GetOutcomeAssets($contract_id)
    {
        $assets = array();

        # Fill asset Array
        # Create a LeaseAsset instance with additional outcome attribute
        #
        if ($this->renewal_id)
        {
            $sth = $this->dbh->prepare("SELECT
				o.asset_id,
				o.outcome,
				i.line_num
			FROM loaner_renewal_outcome o
			LEFT JOIN contract_line_item i ON o.asset_id = i.asset_id AND i.contract_id = ?
			WHERE o.renewal_id = ?");
            $sth->bindValue(1, $contract_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->renewal_id, PDO::PARAM_INT);
            $sth->execute();
            while (list($asset_id, $outcome, $line_num) = $sth->fetch(PDO::FETCH_NUM))
            {
                $assets[$asset_id] = new LeaseAsset($asset_id);
                $assets[$asset_id]->outcome = $outcome;
                $assets[$asset_id]->line_num = $line_num;
            }
        }

        return $assets;
    }

    /**
     * Determine what stage of the renewal process
     *
     * @param int
     * @return int
     */
    public function GetProcessStage($sponsor_id)
    {
        global $user;

        # Default to no stage
        $stage = 0;

        # Find User information
        $uid = $user->getId();
        $sponsor = new User($sponsor_id);
        $supervisor = $sponsor->getSuperVisor();
        $supervisor_id = ($supervisor) ? $supervisor->getId() : 0;

        # Determine if the user is the sponsor or supervisor
        # for the loaner agreement
        $is_sponsor = ($sponsor_id && $sponsor_id == $uid);
        $is_supervisor = ($supervisor_id && $supervisor_id == $uid);

        # No leasing privileges yet
        #$in_leasing = $user->InPermGroup(self::$LEASING_PERM_GROUP);
        $in_leasing = false;

        # Request has been initiated
        //if ($this->renewal_id || $in_leasing)
        //{
        # Find stage using status_id
        if ($this->status_id == self::$DUE_STATUS || $this->status_id == self::$NEW_STATUS)
        {
            if ($is_sponsor || $is_supervisor || $in_leasing)
                $stage = self::$REQUEST_STAGE;
        }
        else if ($this->status_id == self::$PENDING_STATUS)
        {
            if ($is_supervisor || $in_leasing)
                $stage = self::$COMPLETE_STAGE; # AJV: now considered complete self::$APPROVAL_STAGE;
        }
        else if ($this->status_id == self::$DENIED_STATUS)
        {
            if ($is_sponsor || $in_leasing)
                $stage = self::$REQUEST_STAGE;
        }
        else if ($this->renewal_id)
            $stage = self::$COMPLETE_STAGE;
        //}

        return $stage;
    }

    /**
     * Populates this object from the matching record in the database.
     */
    public function load()
    {
        if ($this->renewal_id)
        {
            $sth = $this->dbh->prepare("SELECT
				r.orig_loaner_id,
				r.new_loaner_id,
				r.new_sponsor_id,
				r.new_facility_id,
				r.new_expiration_date,
				r.new_daily_rate,
				r.request_id,
				r.transfer_id,
				r.nfif_id,
				r.cancellation_order_id,
				r.requested_by,
				r.requested_date,
		 		r.approved_by,
				r.approved_date,
				r.status_id,
				rs.status_txt,
				rb.firstname || ' ' || rb.lastname as requested_by_name,
				ab.firstname || ' ' || ab.lastname as approved_by_name,
				s.firstname || ' ' || s.lastname as new_sponsor_name,
				f.accounting_id as new_accounting_id
			FROM loaner_renewal r
			INNER JOIN loaner_renew_status rs ON r.status_id = rs.status_id
			LEFT JOIN users rb ON r.requested_by = rb.id
			LEFT JOIN users ab ON r.approved_by = ab.id
			LEFT JOIN users s ON r.new_sponsor_id = s.id
			LEFT JOIN facilities f ON r.new_facility_id = f.id
			WHERE renewal_id = ?");
            $sth->bindValue(1, $this->renewal_id, PDO::PARAM_INT);
            $sth->execute();
            $this->copyFromArray($sth->fetch(PDO::FETCH_ASSOC));

            $this->SetOrigin();
        }
    }

    /**
     * Add transaction to place the asset at the new facility
     *
     * @param array
     * @param string
     * @param string
     */
    private function PlaceAssets($line_items, $substatus, $comment)
    {
        # Use the new sponsor as transaction user
        #
        $trans_user = new User($this->new_sponsor_id);
        $status = LeaseAssetTransaction::$PLACED;

        foreach ($line_items as $line)
        {
            # Add transaction
            $asset_id = $line->GetVar('asset_id');
            if ($asset_id)
            {
                $asset = new LeaseAsset($asset_id);
                $asset->addTransaction($this->new_facility_id, $status, $substatus, $trans_user, $comment);
            }
        }
    }

    /**
     * Delete/Insert outcome records
     */
    private function SaveOutcomes()
    {
        // Remove old records
        $sth = $this->dbh->prepare("DELETE FROM loaner_renewal_outcome WHERE renewal_id = ?");
        $sth->bindValue(1, $this->renewal_id, PDO::PARAM_INT);
        $sth->execute();

        if ($this->outcomes)
        {
            $sth = $this->dbh->prepare("INSERT INTO loaner_renewal_outcome
			(renewal_id, asset_id, outcome) VALUES (?,?,?)");
            $sth->bindValue(1, $this->renewal_id, PDO::PARAM_INT);
            foreach ($this->outcomes as $asset_id => $value)
            {
                // Check length > 32
                $value = substr($value, 0, 32);

                $sth->bindValue(2, $asset_id, PDO::PARAM_INT);
                $sth->bindValue(3, $value, PDO::PARAM_STR);

                // Execute Insert
                if ($asset_id > 0 and strlen($value) > 0)
                    $sth->execute();
            }
        }
    }

    /**
     * Update database record
     */
    public function save($form = null)
    {
        $this->copyFromArray($form);

        if ($this->renewal_id)
            $this->db_update();
        else
            $this->db_insert();

        $this->SaveOutcomes();

        $this->LogChanges();
    }

    /**
     * Create new contract record
     *
     * @param mixed
     * @param integer
     * @param array
     *
     * @return integer
     */
    private function SaveContract(&$n_contract, $type, $period_start_date, $asset_list)
    {
        # Setup a comment based on contract type
        #
        if ($type == LeaseContract::$PURCHASE_TYPE)
            $comment = "Loaner converted to purchase";
        else if ($type == LeaseContract::$LOANER_TYPE)
            $comment = "Loaner extended";
        else
            $comment = "Loaner transfered to lease";

        # Set contract attributes
        #
        $equipment_ary = $this->CreateContractEquipment($asset_list, $type);

        # Set contract attributes
        #
        if ($type == LeaseContract::$LOANER_TYPE)
        {
            $n_contract->setVar('monthly_revenue', $this->new_daily_rate);
            $n_contract->setVar('sale_amount', $this->new_daily_rate);
        }
        else
        {
            $n_contract->setVar('monthly_revenue', $equipment_ary['monthly_revenue']);
            $n_contract->setVar('sale_amount', $equipment_ary['monthly_revenue']);
        }
        $n_contract->setVar('comments', $comment);
        $n_contract->setVar('id_facility', $this->new_facility_id);
        $n_contract->setVar('id_contract_type', $type);
        $n_contract->setVar('date_lease', $period_start_date);
        $n_contract->setVar('date_install', $period_start_date);
        $n_contract->setVar('date_billed_through', NULL);
        $n_contract->setVar('date_billing_start', $period_start_date);
        $n_contract->setVar('date_expiration', date('Y-m-d', $this->new_expiration_date));

        # May need to set this?
        if ($type == LeaseContract::$PURCHASE_TYPE)
            $n_contract->setVar('warranty', 3);  # 3: Standard Warranty

        # Save the new Contract record
        #
        $n_contract->save(array('line_items' => $equipment_ary['line_items']));

        return $n_contract->getVar('id_contract');
    }

    /**
     * Copy values from facility into the object
     *
     * @param object (mixed) $obj
     * @param object (Facility) $facility
     */
    private function SetFacilityAttributes($obj, $facility)
    {
        $obj->setVar('facility_id', $facility->getID());
        $obj->setVar('new_facility_id', $facility->getID());
        $obj->setVar('new_facility_name', $facility->getName());
        $obj->setVar('short_name', $facility->getName());
        $obj->setVar('legal_name', $facility->getName());
        $obj->setVar('accounting_id', $facility->getCustId());
        $obj->setVar('cust_id', $facility->getCustId());
        $obj->setVar('corporate_client', $facility->getCorporateParent());
        $obj->setVar('corporate_parent', $facility->getCorporateParent());

        #$obj->setVar('fax', $facility->getFax());
        $rh_prov = $facility->GetRehabProvider();
        if ($rh_prov)
        {
            $obj->setVar('rehab_provider', $rh_prov->getId());
            $obj->setVar('rehab_provider_name', $rh_prov->getName());
        }
        $obj->setVar('provnum', $facility->getProvnum());
        #$obj->setVar('nursinghomename', $facility->getProvnum());
    }

    /**
     * Create a new contract record
     *
     * @param object
     * @param object
     * @param array
     */
    public function Transfer($o_loaner, $o_contract, $asset_list = array())
    {
        global $user;

        if (count($asset_list) > 0)
        {
            # Use original loaners expiration date as the new contract starting point
            #
            if ($o_loaner->getVar('expiration_date'))
                $start_date = date('Y-m-d', $o_loaner->getVar('expiration_date'));
            else
                $start_date = date('Y-m-d');

            # Create a contract
            #
            $n_contract = new LeaseContract();
            $id = $this->SaveContract($n_contract, LeaseContract::$FULL_TYPE, $start_date, $asset_list);

            # If going to new facility Place the device there
            #
            if ($o_loaner->getVar('facility_id') != $this->new_facility_id)
            {
                $substatus = LeaseAssetTransaction::$LEASE;
                $comment = "Loaner transfered to lease";

                $this->PlaceAssets($n_contract->line_items, $substatus, $comment);
            }
        }
    }

    /**
     * Create a new customer purchase workflow to handle converting to purchase
     *
     * @param object
     * @param object
     * @param array
     *
     * @return string
     */
    public function WorkFlowConvert($o_loaner, $o_contract, $asset_list = array())
    {
        global $user;

        $feedback = "";

        if (count($asset_list) > 0)
        {
            $contract_id = $o_contract->getVar('id_contract_type');

            # Initialize contract array
            #
            $contract_ary[0]['contract_id'] = 0;
            $contract_ary[0]['lease_amount'] = $o_contract->getVar('monthly_revenue');
            if ($o_loaner->getVar('expiration_date'))
                $contract_ary[0]['install_date'] = date('Y-m-d', $o_loaner->getVar('expiration_date'));
            $contract_ary[0]['transfered'] = 1;
            $contract_ary[0]['lease_type'] = LeaseContract::$PURCHASE_TYPE;
            $contract_ary[0]['warranty'] = self::$DEFAULT_WARRANTY_OPTION;

            # Initialize the equipment array
            #
            $equipment_ary = array();
            $item_num = 1;
            foreach ($asset_list as $line_num)
            {
                $item = new LoanerItem($line_num, true);

                $device = array(
                    'item_num' => $item_num, 'quantity' => 1,
                    'uom' => 'EA', 'price' => 0.0,
                    'prod_id' => ContractLineItem::ConvertToSRV($item->GetVar('prod_id')),
                    'serial_num' => $item->GetVar('serial_num'),
                    'is_device' => 1, 'lease_type' => LeaseContract::$PURCHASE_TYPE);

                $equipment_ary[$item_num++] = $device;

                $item->Delete(date('Y-m-d'));
            }

            # Create the CustomerPurchase
            #
            $facility = new Facility($this->new_facility_id);

            $cpf = new CustomerPurchase($this->nfif_id);
            $this->SetFacilityAttributes($cpf, $facility);
            $addr_ary = $this->CreateWFAddresses($facility);

            # Use existing contacts if they are available
            if (isset ($addr_ary['ship_contact']['contact_id']))
                $cpf->setVar('shipping_contact', $addr_ary['ship_contact']['contact_id']);
            if (isset ($addr_ary['bill_contact']['contact_id']))
                $cpf->setVar('billing_contact', $addr_ary['bill_contact']['contact_id']);
            if (isset ($addr_ary['con_contact']['contact_id']))
                $cpf->setVar('contract_contact', $addr_ary['con_contact']['contact_id']);

            # Note: $form is passed by refernence
            $form = array(
                'ship_contact' => $addr_ary['ship_contact'],
                'bill_contact' => $addr_ary['bill_contact'],
                'con_contact' => $addr_ary['con_contact'],
                'contract' => $contract_ary,
                'nfif_equip' => $equipment_ary);
            $cpf->copyFromArray($form, false);
            $cpf->save(null);

            # Set the link to the order
            if (!$this->nfif_id)
                $this->change('nfif_id', $cpf->getVar('nfif_id'));

            # Add workflow
            #
            $work_flow = new WorkFlow();
            $work_flow->setVar('process_id', self::$PURCHASE_PROCESS);
            $work_flow->setVar('description', "{$facility->getName()} ({$facility->getCustId()}) - Loaner to Purchase");

            # In the save method an array is passed by reference
            # $fake_form is used to avoid errors.
            $fake_form = array();
            $work_flow->save_wf($fake_form);

            # Sponsor should by owner appose to approver/supervisor
            $work_flow->change('user_id', $this->new_sponsor_id);

            # Link the stages to the transfer object
            $stages = $work_flow->getVar('stages');
            foreach ($stages as $stage)
            {
                $stage->change('object_id', $this->nfif_id);
                $stage->change('assigned_to', $this->new_sponsor_id);
            }

            # Advance workflow to request approval
            $aprv_form['status_id'] = WorkFlowStage::$EDIT_STATUS;
            $aprv_form['approved'] = WorkFlowStage::$APPROVAL_UNSET;
            $aprv_form['approval_id'] = 0;
            $aprv_form['new_stage_note'] = "Loaner to be converted to purchase";
            $aprv_form['stage_act'] = 'save';
            $work_flow->save_wf($aprv_form);

            $feedback = "workflow.php?act=edit&wf_id={$work_flow->getId()}";
        }

        return $feedback;
    }

    /**
     * Create a new loaner workflow to handle extending loaner
     *
     * @param object
     * @param object
     * @param array
     *
     * @return string
     */
    public function WorkFlowExtend($o_loaner, $o_contract, $asset_list = array())
    {
        global $user;

        $feedback = "";

        if (count($asset_list) > 0)
        {
            # Initialize the equipment array
            #
            $equipment_ary = array();
            foreach ($asset_list as $line_num)
            {
                $item = new LoanerItem($line_num, true);
                $equipment_ary[] = $item->ToArray();
            }

            # Create the Transfer
            #
            $facility = new Facility($this->new_facility_id);

            if ($this->request_id)
            {
                ## Check for unfinished workflow
                $request = new LoanerRequest($this->request_id);
                if ($request->isComplete())
                {
                    $this->request_id = null;
                    $request = new LoanerRequest();
                }
                else
                {
                    throw new Exception("Extention already in-progress.");
                }
            }
            else
            {
                $request = new LoanerRequest();
            }

            $request->setvar('loaner_id', $this->new_loaner_id);
            $request->setvar('contract_id', $o_contract->getVar('id_contract'));
            $request->setVar('facility_id', $this->new_facility_id);
            $request->setVar('open_date', time());
            $request->setVar('install_date', $o_loaner->getVar('expiration_date'));
            $request->setVar('billing_start_date', $o_loaner->getVar('expiration_date'));
            $request->setVar('expiration_date', $this->new_expiration_date);
            $request->setVar('sponsor_id', $this->new_sponsor_id);
            $request->setVar('daily_rate', $this->new_daily_rate);
            $request->setVar('user_id', $this->new_sponsor_id);

            # Set Shipping Address
            $addr_ary = $this->CreateWFAddresses($facility);
            if (isset ($addr_ary['ship_contact']['contact_id']))
                $request->setVar('shipping_contact', $addr_ary['ship_contact']['contact_id']);

            $request->copyFromArray(array('loaner_equip' => $equipment_ary));
            $request->save(null);

            # Set the link to the order
            $this->change('request_id', $request->getVar('request_id'));

            # Add workflow
            #
            $work_flow = new WorkFlow();
            $work_flow->setVar('process_id', self::$LOANER_PROCESS);
            $work_flow->setVar('description', "{$facility->getName()} ({$facility->getCustId()}) - Extended Loaner");

            # In the save method an array is passed by reference
            # $fake_form is used to avoid errors.
            $fake_form = array('assigned_to' => $this->new_sponsor_id);
            $work_flow->save_wf($fake_form);

            # Sponsor should by owner appose to approver/supervisor
            $work_flow->change('user_id', $this->new_sponsor_id);

            # Link the stages to the transfer object
            $stages = $work_flow->getVar('stages');
            foreach ($stages as $stage)
            {
                $stage->change('object_id', $this->request_id);
                $stage->change('assigned_to', $this->new_sponsor_id);
            }

            # Link the workflow back to request
            $request->change('work_flow_id', $work_flow->getID());

            # Advance workflow to request approval
            $aprv_form['status_id'] = WorkFlowStage::$EDIT_STATUS;
            $aprv_form['approved'] = WorkFlowStage::$APPROVAL_UNSET;
            $aprv_form['approval_id'] = 0;
            $aprv_form['new_stage_note'] = "Loaner extension requested";
            $aprv_form['stage_act'] = 'req_approval';
            $work_flow->save_wf($aprv_form);

            $feedback = "workflow.php?act=edit&wf_id={$work_flow->getId()}";
        }

        return $feedback;
    }

    /**
     * Create a new transfer workflow to handle converting to lease
     *
     * @param object
     * @param object
     * @param array
     *
     * @return string;
     */
    public function WorkFlowTransfer($o_loaner, $o_contract, $asset_list = array())
    {
        global $user;

        $feedback = "";

        if (count($asset_list) > 0)
        {
            $contract_id = $o_contract->getVar('id_contract');
            $lease_type = $o_contract->getVar('id_contract_type');

            # Initialize contract array
            #
            $contract_ary[0]['contract_id'] = $o_contract->getVar('id_contract');
            $contract_ary[0]['orig_lease_type'] = $lease_type;
            $contract_ary[0]['new_lease_type'] = LeaseContract::$ADD_TYPE;
            $contract_ary[0]['orig_lease_amount'] = $o_contract->getVar('monthly_revenue');
            $contract_ary[0]['lease_amount'] = $o_contract->getVar('monthly_revenue');
            $contract_ary[0]['install_date'] = $this->ParseDate($o_loaner->getVar('expiration_date'));
            $contract_ary[0]['billing_start_date'] = $this->ParseDate($o_loaner->getVar('expiration_date'));

            # Initialize the equipment array
            #
            $equipment_ary = array();
            foreach ($asset_list as $line_num)
            {
                $line = new LoanerItem($line_num, false);
                $asset_id = $line->getVar('asset_id');
                $model_id = $line->getVar('model_id');
                $equipment_ary[] = array(
                    'line_num' => $line_num,
                    'asset_id' => $asset_id,
                    'contract_id' => $contract_id,
                    'model_id' => $model_id,
                    'price' => null);
            }

            # Create the Transfer
            #
            $facility = new Facility($this->new_facility_id);

            $transfer = new LeaseTransfer($this->transfer_id);
            $transfer->setVar('orig_facility_id', $o_contract->getVar('id_facility'));
            $transfer->setVar('open_date', time());
            $transfer->setVar('transfer_date', $o_loaner->getVar('expiration_date'));
            $transfer->setVar('transfer_type', LeaseTransfer::$CHANGE_TYPE);
            $transfer->setVar('contract', $contract_ary);

            $this->SetFacilityAttributes($transfer, $facility);
            $addr_ary = $this->CreateWFAddresses($facility);

            # Use existing contacts if they are available
            if (isset ($addr_ary['ship_contact']['contact_id']))
                $transfer->setVar('shipping_contact', $addr_ary['ship_contact']['contact_id']);
            if (isset ($addr_ary['bill_contact']['contact_id']))
                $transfer->setVar('billing_contact', $addr_ary['bill_contact']['contact_id']);
            if (isset ($addr_ary['con_contact']['contact_id']))
                $transfer->setVar('contract_contact', $addr_ary['con_contact']['contact_id']);

            $transfer->copyFromArray(array(
                'ship_contact' => $addr_ary['ship_contact'],
                'bill_contact' => $addr_ary['bill_contact'],
                'con_contact' => $addr_ary['con_contact'],
                'contract' => $contract_ary,
                'equipment' => $equipment_ary));
            $transfer->save(null);

            # Set the link to the order
            if (!$this->transfer_id)
                $this->change('transfer_id', $transfer->getVar('transfer_id'));

            # Add workflow
            #
            $work_flow = new WorkFlow();
            $work_flow->setVar('process_id', self::$TRANSFER_PROCESS);
            $work_flow->setVar('description', "{$facility->getName()} ({$facility->getCustId()}) - Loaner to Lease");

            # In the save method an array is passed by reference
            # $fake_form is used to avoid errors.
            $fake_form = array();
            $work_flow->save_wf($fake_form);

            # Sponsor should by owner appose to approver/supervisor
            $work_flow->change('user_id', $this->new_sponsor_id);

            # Link the stages to the transfer object
            $stages = $work_flow->getVar('stages');
            foreach ($stages as $stage)
            {
                $stage->change('object_id', $this->transfer_id);
                $stage->change('assigned_to', $this->new_sponsor_id);
            }

            # Advance workflow to request approval
            $aprv_form['status_id'] = WorkFlowStage::$EDIT_STATUS;
            $aprv_form['approved'] = WorkFlowStage::$APPROVAL_UNSET;
            $aprv_form['approval_id'] = 0;
            $aprv_form['new_stage_note'] = "Loaner to be transfered to lease";
            $aprv_form['stage_act'] = 'req_approval';
            $work_flow->save_wf($aprv_form);

            $feedback = "workflow.php?act=edit&wf_id={$work_flow->getId()}";
        }

        return $feedback;
    }
}
?>