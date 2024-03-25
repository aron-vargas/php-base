<?php
/**
 * @package Freedom
 * @author Aron Vargas
 */

/**
 * Class defines Loaner Request workflow process
 *
 */
class LoanerRequest extends BaseClass {
    protected $db_table = 'loaner_request';	# string
    protected $p_key = 'request_id';		# string

    public $request_id;			# int
    public $work_flow_id;		# int
    public $facility_id;		# int
    public $loaner_id;			# int
    public $contract_id;		# int
    public $order_id;			# int
    public $install_date;		# int
    public $billing_start_date;	# int
    public $expiration_date;	# int
    public $sponsor_id;			# int
    public $daily_rate = 0;		# double
    public $shipping_charge = 0;	# double
    public $shipping_attention;	# int
    public $shipping_contact;	# int
    public $user_id;			# int
    public $open_date;			# int
    public $comments;			# string
    public $shipping_notes;		# string
    public $status;				# int

    # Extended
    public $facility_name;		# string
    public $facility_cpm;		# int
    public $accounting_id;		# string
    public $status_txt;			# string

    # Addition detail
    public $s_contact;			# object
    public $equipment;			# array
    public $supplies;			# array

    # Static Vars
    static public $CONTRACT_ITEM_CAT = 12;
    static public $AUTO_ASSIGN_ASSET = false;

    /**
     * Create new Loaner Request instance
     *
     * @param integer
     */
    public function __construct($request_id = null)
    {
        $this->dbh = DataStor::getHandle();

        if ($request_id)
            $this->request_id = $request_id;

        $this->load();
    }

    /**
     * Assign values to the sql statement
     *
     * @param object
     * @param integer
     *
     * @return integer
     */
    public function BindValues(&$sth, $pkey = 0)
    {
        # Set NULL type if empty
        #
        $work_flow_id_type = ($this->work_flow_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $facility_id_type = ($this->facility_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $contact_id_type = ($this->contract_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $loaner_id_type = ($this->loaner_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $order_id_type = ($this->order_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $install_date_type = ($this->install_date) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $billing_start_date_type = ($this->billing_start_date) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $expiration_date_type = ($this->expiration_date) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $sponsor_id_type = ($this->sponsor_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $shipping_contact_type = ($this->shipping_contact) ? PDO::PARAM_INT : PDO::PARAM_NULL;

        $i = 1;
        $sth->bindValue($i++, $this->work_flow_id, $work_flow_id_type);
        $sth->bindValue($i++, $this->facility_id, $facility_id_type);
        $sth->bindValue($i++, $this->contract_id, $contact_id_type);
        $sth->bindValue($i++, $this->loaner_id, $loaner_id_type);
        $sth->bindValue($i++, $this->order_id, $order_id_type);
        $sth->bindValue($i++, $this->install_date, $install_date_type);
        $sth->bindValue($i++, $this->billing_start_date, $billing_start_date_type);
        $sth->bindValue($i++, $this->expiration_date, $expiration_date_type);
        $sth->bindValue($i++, $this->sponsor_id, $sponsor_id_type);
        $sth->bindValue($i++, (float) $this->daily_rate, PDO::PARAM_STR);
        $sth->bindValue($i++, (float) $this->shipping_charge, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->shipping_attention, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->shipping_contact, $shipping_contact_type);
        $sth->bindValue($i++, $this->user_id, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->open_date, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->comments, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->shipping_notes, PDO::PARAM_STR);

        if ($pkey)
            $sth->bindValue($i, $this->request_id, PDO::PARAM_INT);

        return $i;
    }

    /**
     * Assign values to the sql statement
     *
     * @param object
     * @param array
     *
     * @return integer
     */
    public function BindDetailValues(&$sth, $detail)
    {
        $i = 0;

        $item_num = (isset ($detail['item_num'])) ? (int) $detail['item_num'] : 0;
        $prod_id = (isset ($detail['prod_id'])) ? (int) $detail['prod_id'] : 0;
        $quantity = (isset ($detail['quantity'])) ? (int) $detail['quantity'] : 0;
        $asset_id = (isset ($detail['asset_id'])) ? (int) $detail['asset_id'] : 0;
        $asset_id_type = ($asset_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;

        if ($item_num && $prod_id && $quantity)
        {
            # Set NULL type if empty
            #
            $price = (isset ($detail['price'])) ? (float) $detail['price'] : 0;
            $uom = (isset ($detail['uom'])) ? $detail['uom'] : 'EA';

            $i = 1;
            $sth->bindValue($i++, $item_num, PDO::PARAM_INT);
            $sth->bindValue($i++, $this->request_id, PDO::PARAM_INT);
            $sth->bindValue($i++, $asset_id, $asset_id_type);
            $sth->bindValue($i++, $prod_id, PDO::PARAM_INT);
            $sth->bindValue($i++, $price, PDO::PARAM_STR);
            $sth->bindValue($i++, $quantity, PDO::PARAM_INT);
            $sth->bindValue($i++, $uom, PDO::PARAM_STR);
        }

        return $i;
    }

    /**
     * Do Final step in work flow process
     * Create Loaner Contract
     * Create Loaner Agreement
     * Create Install Order
     *
     * @param array
     */
    public function Complete(&$form)
    {
        global $user;

        // Detect (Refresh/F5)
        if ($this->isComplete())
            return;

        $this->save($form);

        $stage_act = (isset ($form['stage_act'])) ? strtolower($form['stage_act']) : '';

        # Create Contract when approved
        if ($stage_act == 'approve')
        {
            # Save Contract

            # Can use existing loaner for employees
            if (preg_match('/^\w\w\w9\d\d/', $this->accounting_id))
                $this->FindExisting();

            $contract_id = $this->SaveContract();
            $loaner_id = $this->SaveLoanerAgreement();
            $order_id = $this->SaveOrder();
        }
    }

    /**
     * Set class property matching the array key
     *
     * @param $form array
     */
    public function CopyFromArray($form = array())
    {
        global $user;

        # Keep original value
        BaseClass::copyFromArray($form);

        # Save contact id.
        if (isset ($form['contact_shipping']['contact_id']))
        {
            $this->shipping_contact = $form['contact_shipping']['contact_id'];
        }

        if (isset ($form['wf_id']))
            $this->work_flow_id = $form['wf_id'];

        # Set dates, either as strings or unix time (integer)
        # BaseClass::copyFromArray sets integer value
        if (isset ($form['install_date']))
        {
            if (preg_match('/[\-\/]/', $form['install_date']))
                $this->install_date = strtotime($form['install_date']);
        }
        if (isset ($form['billing_start_date']))
        {
            if (preg_match('/[\-\/]/', $form['billing_start_date']))
                $this->billing_start_date = strtotime($form['billing_start_date']);
        }
        if (isset ($form['expiration_date']))
        {
            if (preg_match('/[\-\/]/', $form['expiration_date']))
                $this->expiration_date = strtotime($form['expiration_date']);
        }

        # Each line item is defined as...
        $item_num = 1;
        if (isset ($form['loaner_equip']))
        {
            $this->equipment = array();
            $this->supplies = array();

            foreach ($form['loaner_equip'] as $item)
            {
                if ($item['prod_id'])
                {
                    $detail['item_num'] = $item_num;
                    $detail['request_id'] = $this->request_id;
                    $device['is_device'] = 1;
                    $detail['prod_id'] = $item['prod_id'];
                    $detail['quantity'] = 1;
                    $detail['price'] = 0;
                    $detail['uom'] = 'EA';

                    if (isset ($item['asset_id']))
                        $detail['asset_id'] = $item['asset_id'];
                    else
                        $detail['asset_id'] = null;

                    $this->equipment[$item_num++] = $detail;
                }
            }
        }
        if (isset ($form['loaner_supply']))
        {
            foreach ($form['loaner_supply'] as $item)
            {
                if ($item['prod_id'] && $item['quantity'] > 0)
                {
                    $detail['item_num'] = $item_num;
                    $detail['request_id'] = $this->request_id;
                    $device['is_device'] = 0;
                    $detail['prod_id'] = $item['prod_id'];
                    $detail['quantity'] = $item['quantity'];
                    $detail['asset_id'] = null;

                    if (isset ($item['price']))
                        $detail['price'] = preg_replace('/[^\d\.]/', '', $item['price']);
                    if (isset ($item['uom']))
                        $detail['uom'] = $item['uom'];

                    $this->supplies[$item_num++] = $detail;
                }
            }
        }
    }

    /**
     * Execute an insert statement
     */
    public function db_insert()
    {
        $sth = $this->dbh->prepare("INSERT INTO loaner_request
		(work_flow_id, facility_id, contract_id, loaner_id, order_id,
		 install_date, billing_start_date, expiration_date,
		 sponsor_id, daily_rate, shipping_charge, shipping_attention, shipping_contact,
		 user_id, open_date, comments, shipping_notes)
		VALUES(?,?,?,?,?, ?,?,?, ?,?,?,?,?, ?,?,?,?)");
        $this->BindValues($sth);
        $sth->execute();

        $this->request_id = $this->dbh->lastInsertId('loaner_request_request_id_seq');
    }

    /**
     * Find asset which is
     *
     * 1) Not in a pending order
     * 2) Not in a active contract
     * 2) Not locked
     * 3) Ownded by Leasing
     * 4) FGI status
     * 5) At wharehouse
     *
     * @param integer
     * @return integer
     */
    private function FindAsset($prod_id)
    {
        # Find an available asset
        $sth = $this->dbh->prepare("SELECT
			a.id
		FROM lease_asset_status a
		INNER JOIN equipment_models m ON a.model_id = m.id
		INNER JOIN products p ON m.model = p.code
		LEFT JOIN (
			SELECT i.asset_id
			FROM order_item i
			INNER JOIN orders o ON i.order_id = o.id AND o.status_id IN (1,2,99)
			WHERE i.asset_id > 0
		) oi ON a.id = oi.asset_id
		LEFT JOIN (
			SELECT e.asset_id
			FROM contract_line_item e
			INNER JOIN contract c ON e.contract_id = c.id_contract
			WHERE c.date_cancellation IS NULL OR c.date_cancellation > CURRENT_DATE
			AND e.asset_id > 0
		) ce ON a.id = ce.asset_id
		LEFT JOIN lease_asset_lock lal ON a.id = lal.lease_asset_id
		LEFT JOIN loaner_request_detail lrd
			ON a.id = lrd.asset_id
			AND lrd.asset_id IS NOT NULL
		WHERE p.id = ?
			AND a.owning_acct IN('DEFAULT001','DEFAULT002')
			AND a.status = ?
			AND a.facility_id = ?
			AND oi.asset_id IS NULL
			AND ce.asset_id IS NULL
			AND lal.lease_asset_id IS NULL
			AND lrd.asset_id IS NULL
			AND a.last_cert_date::TIMESTAMP > CURRENT_TIMESTAMP - Interval '6 months'
		LIMIT 1");
        $sth->BindValue(1, $prod_id, PDO::PARAM_INT);
        $sth->BindValue(2, LeaseAssetTransaction::$FGI, PDO::PARAM_STR);
        $sth->BindValue(3, Config::$DEFAULT001_ID, PDO::PARAM_INT);
        $sth->execute();
        $asset_id = $sth->fetchColumn();

        return $asset_id;
    }

    /**
     * Use existing loaner for employees
     */
    private function FindExisting()
    {
        if (!$this->loaner_id)
        {
            $sth = $this->dbh->prepare("SELECT
				l.loaner_id,
				l.contract_id
			FROM loaner_agreement l
			WHERE l.facility_id = ?
			ORDER BY l.active DESC, l.loaner_id
			LIMIT 1");
            $sth->bindValue(1, $this->facility_id, PDO::PARAM_STR);
            $sth->execute();
            list($loaner_id, $contract_id) = $sth->fetch(PDO::FETCH_NUM);

            if ($loaner_id)
                $this->loaner_id = $loaner_id;

            if ($contract_id && !$this->contract_id)
                $this->contract_id = $contract_id;
        }
    }

    /**
     * Execute an update statement
     */
    public function db_update()
    {
        if ($this->request_id)
        {
            $sth = $this->dbh->prepare("UPDATE loaner_request
			SET
				work_flow_id = ?,
				facility_id = ?,
				contract_id = ?,
				loaner_id = ?,
				order_id = ?,
				install_date = ?,
				billing_start_date = ?,
				expiration_date = ?,
				sponsor_id = ?,
				daily_rate = ?,
				shipping_charge = ?,
				shipping_attention = ?,
				shipping_contact = ?,
				user_id = ?,
				open_date = ?,
				comments = ?,
				shipping_notes = ?
			WHERE request_id = ?");
            $this->BindValues($sth, $this->request_id);
            $sth->execute();
        }
    }

    /**
     * Get required CSS tags for the form display
     *
     * @return string
     */
    public function GetCSS(&$css)
    {
        $path = Config::$WEB_PATH;
        $css[] = $path . '/styles/ul_tabs.css';
        $css[] = $path . '/styles/result_list.css';
        $css[] = $path . '/styles/calendar-blue.css';
        $css[] = $path . '/styles/nfif.css';
        $css[] = $path . '/styles/crm.css';
        $css[] = $path . '/js/yui/assets/skins/sam/autocomplete.css';
        $css[] = $path . '/js/yui/assets/skins/sam/container.css';
        $css[] = $path . '/js/yui/assets/skins/sam/button.css';

    }

    /**
     * Return email body
     */
    public function GetEmailDetail($stage_act)
    {
        $install_date = ($this->install_date) ? date('Y-m-d', $this->install_date) : 'NA';

        $email_body = "
	Task : Loaner Request
	Lease Type : Loaner
	Facility Name : {$this->facility_name}
	Install Date: {$install_date}
	Order:
		Item			Serial Number	QTY	UOM";

        if ($stage_act == 'approve' || $stage_act == 'req_approval')
        {
            $item_rows = "";
            $sth = $this->dbh->prepare("SELECT name, code FROM products WHERE id = ?");
            if ($this->equipment)
            {
                foreach ($this->equipment as $device)
                {
                    $price = ($device['price']) ? number_format($device['price'], 2) : '--';
                    $sth->execute(array($device['prod_id']));
                    list($name, $code) = $sth->fetch(PDO::FETCH_NUM);
                    $item_rows .= "		$code::$name	{$device['quantity']}	{$device['uom']}\n";
                }
            }

            if ($this->supplies)
            {
                foreach ($this->supplies as $item)
                {
                    $price = ($item['price']) ? number_format($item['price'], 2) : '--';
                    $sth->execute(array($item['prod_id']));
                    list($name, $code) = $sth->fetch(PDO::FETCH_NUM);
                    $item_rows .= "		$code::$name	{$item['quantity']}	{$item['uom']}\n";
                }
            }
            $email_body .= "\n	Order:\n		Item			QTY	UOM	Price\n{$item_rows}";
        }

        return $email_body;
    }

    /**
     * Return email addresses
     */
    public function GetEmailRecipients($stage_act)
    {
        $recipients = array();
        if ($stage_act == 'approve')
        {
            # Add facility cpm, rdo, dvp, and coo
            if ($this->facility_cpm)
            {
                $sth = $this->dbh->prepare("SELECT u.email, rdo.email, dvp.email, rds.email, dds.email
				FROM users u
				INNER JOIN v_users_primary_group upg ON u.id = upg.user_id
				INNER JOIN users g on upg.group_id = g.id
				LEFT JOIN (
					SELECT u.email, g.group_id, u.supervisor_id
					FROM users u
					INNER JOIN v_users_primary_group g ON u.id = g.user_id AND g.role_id = 600
					WHERE u.active = true
				) rdo ON upg.group_id = rdo.group_id
				LEFT JOIN users dvp ON rdo.supervisor_id = dvp.id
				LEFT JOIN (
					SELECT u.email, g.group_id, u.supervisor_id
					FROM users u
					INNER JOIN v_users_primary_group g ON u.id = g.user_id AND g.role_id = 500
					WHERE u.active = true
				) rds ON upg.group_id = rds.group_id
				LEFT JOIN users dds ON rds.supervisor_id = dds.id
				WHERE u.id = ?");
                $sth->execute(array((int) $this->facility_cpm));
                if (list($cpm, $rdo, $dvp, $rds, $dds) = $sth->fetch(PDO::FETCH_NUM))
                {
                    if ($cpm)
                        $recipients[] = $cpm;
                    if ($rdo)
                        $recipients[] = $rdo;
                    if ($dvp)
                        $recipients[] = $dvp;
                    if ($rds)
                        $recipients[] = $rds;
                    if ($dds)
                        $recipients[] = $dds;
                }

                $sth = $this->dbh->query("SELECT u.email FROM users u
				INNER JOIN v_users_primary_group upg ON u.id = upg.user_id AND upg.role_id = 300
				WHERE u.active = true
				AND u.id <> 298 -- No emails to Mark Ritchards");
                while (list($coo) = $sth->fetch(PDO::FETCH_NUM))
                    $recipients[] = $coo;
            }
        }

        return $recipients;
    }

    /**
     * Build a JS Array of available euipment
     */
    public function GetEquipList()
    {
        $i = 0;
        $equip_options = "";
        $sth = $this->dbh->query("SELECT
			p.id AS value,
			m.model || ': ' || m.description as text
		FROM equipment_models m
		INNER JOIN products p ON m.model = p.code
		WHERE m.active = true
		ORDER BY m.display_order");
        while ($item = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $equip_options .= "equip_options[$i] = " . json_encode($item) . ";\n";
            $i++;
        }

        return $equip_options;
    }

    /**
     * Build a JS Array of available supply items
     */
    public function GetItemList()
    {
        $i = 0;
        $item_options = "";
        $sth = $this->dbh->prepare("SELECT DISTINCT
			p.id as value,
			p.code || ' :: ' || p.name as text,
			p.price_uom,
			u.uom_list
		FROM products p
		LEFT JOIN equipment_models m on p.code = m.model
		LEFT JOIN product_category_join i on p.id = i.prod_id
		LEFT JOIN install_suite_product si ON p.id = si.prod_id
		LEFT JOIN (
			SELECT
				code,
				array_to_string(array_accum(uom),',') as uom_list
			FROM product_uom
			WHERE active = true
			GROUP BY code
		) u ON p.code = u.code
		WHERE m.id IS NULL
		AND (
			(p.active = true AND i.cat_id = " . self::$CONTRACT_ITEM_CAT . ")
			OR si.prod_id IS NOT NULL)
		ORDER BY 2");
        $sth->execute();
        while ($item = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $item_options .= "item_options[$i] = " . json_encode($item) . ";\n";
            $i++;
        }

        return $item_options;
    }

    /**
     * Get required Javascript tags for the form controls
     *
     * @return string
     */
    public function GetJS(&$js)
    {
        $path = Config::$WEB_PATH;
        $js[] = $path . '/js/yui/yahoo-dom-event/yahoo-dom-event.js';
        $js[] = $path . '/js/yui/element/element-min.js';
        $js[] = $path . '/js/yui/dragdrop/dragdrop-min.js';
        $js[] = $path . '/js/yui/button/button-min.js';
        $js[] = $path . '/js/yui/container/container.js';
        $js[] = $path . '/js/yui/connection/connection-min.js';
        $js[] = $path . '/js/yui/datasource/datasource-min.js';
        $js[] = $path . '/js/yui/autocomplete/autocomplete-min.js';
        $js[] = $path . '/js/yui/json/json-min.js';
        $js[] = $path . '/js/item_search.js';
        $js[] = $path . '/js/popcal/calendar.js';
        $js[] = $path . '/js/popcal/calendar-en.js';
        $js[] = $path . '/js/popcal/calendar-setup.js';
        $js[] = $path . '/js/util/date_time.js';
        $js[] = $path . '/js/util/validation.js';
        $js[] = $path . '/js/ajax/xmlhttprequest.js';
        $js[] = $path . '/js/crm.js';
        $js[] = $path . '/js/loaner.js';
    }

    /**
     * Find code field for the product
     *
     * @param int
     * @return string
     */
    public function GetProductCode($prod_id)
    {
        # Look product.code
        $sth = $this->dbh->prepare("SELECT p.code FROM products p WHERE p.id = ?");
        $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
        $sth->execute();
        $code = $sth->fetchColumn();

        return $code;
    }

    /**
     * Return form buttons for saving the form
     *
     * @return string : html
     */
    public function GetSaveButtons()
    {
        $save = " <input type='button' class='submit' name='save' value='Save' onClick='SaveForm(this.form);'/>";

        return $save;
    }

    /**
     * Get detailed status text
     *
     * @return string
     */
    public function GetStatusText()
    {
        return $this->status_txt;
    }

    /**
     * Populate item and equipment arrays from Database records
     */
    protected function LoadDetail()
    {
        $this->supplies = array();
        $this->equipment = array();

        if ($this->request_id)
        {
            $sth = $this->dbh->prepare("SELECT
				i.item_num, i.request_id,
				i.asset_id, i.prod_id, i.quantity, i.uom, i.price,
				p.code, p.name,
				a.serial_num, m.id as model_id,
				m.id as is_device
			FROM loaner_request_detail i
			INNER JOIN products p ON i.prod_id = p.id
			LEFT JOIN equipment_models m ON p.code = m.model
			LEFT JOIN lease_asset a on i.asset_id = a.id
			WHERE i.request_id = ?
			ORDER BY i.item_num");
            $sth->bindValue(1, (int) $this->request_id, PDO::PARAM_INT);
            $sth->execute();
            while ($item = $sth->fetch(PDO::FETCH_ASSOC))
            {
                if ($item['is_device'])
                    $this->equipment[$item['item_num']] = $item;
                else
                    $this->supplies[$item['item_num']] = $item;
            }
        }
    }

    /**
     * Load details from database
     *
     * @return string
     */
    public function Load()
    {
        if ($this->request_id)
        {
            $sth = $this->dbh->prepare("
			SELECT
				l.work_flow_id,
				l.facility_id,			l.contract_id,
				l.loaner_id,			l.order_id,
				l.install_date,			l.billing_start_date,
				l.expiration_date,		l.sponsor_id,
				l.daily_rate,			l.shipping_charge,
				l.shipping_attention,	l.shipping_contact,
				l.user_id,				l.open_date,
				l.comments,				l.status,
				l.shipping_notes,
				f.facility_name,		f.cpt_id as facility_cpm,
				f.accounting_id
			FROM loaner_request l
			LEFT JOIN facilities f ON l.facility_id = f.id
			WHERE l.request_id = ?");
            $sth->bindValue(1, $this->request_id, PDO::PARAM_INT);
            $sth->execute();
            $this->copyFromArray($sth->fetch(PDO::FETCH_ASSOC));

            $this->isComplete();
        }

        // Load Equipment AND Supplies
        $this->loadDetail();

        $this->s_contact = new Contact($this->shipping_contact);

        if ($this->request_id)
            $this->setOrigin(true);
    }

    /**
     * Check workflow record for complete flag
     */
    public function isComplete()
    {
        $complete = false;

        if (in_array($this->status, BaseClass::$STATUS_COMPLETE_LIST))
            $complete = true;

        if ($complete)
            $this->status_txt = "Complete";
        else
            $this->status_txt = "In-Progress";

        return $complete;
    }

    /**
     * Create/Update the contract record
     *
     * @return integer
     */
    public function SaveContract()
    {
        # Build an array based contract
        $con_ary['comments'] = substr($this->comments, 0, 512);
        $con_ary['id_contract_type'] = LoanerContract::$LOANER_TYPE;
        $con_ary['id_facility'] = $this->facility_id;
        $con_ary['monthly_revenue'] = 0;
        $con_ary['master_agreement'] = 0;
        $con_ary['visit_frequency'] = 0;
        $con_ary['request_id'] = $this->request_id;
        $con_ary['date_cancellation'] = null;
        # Convert unix dates to strings
        if ($this->install_date)
        {
            $con_ary['date_lease'] = date('Y-m-d', $this->install_date);
            $con_ary['date_install'] = date('Y-m-d', $this->install_date);
        }
        if ($this->billing_start_date)
        {
            $con_ary['date_billing_start'] = date('Y-m-d', $this->billing_start_date);
        }
        if ($this->expiration_date)
        {
            $con_ary['date_expiration'] = date('Y-m-d', $this->expiration_date);
        }

        $con_ary['line_items'] = array();
        foreach ($this->equipment as $i => $equip)
        {
            # Set shipped date for already placed devices
            if ($equip['asset_id'])
                $equip['date_shipped'] = date('Y-m-d', $this->install_date);

            $equip['item_code'] = $this->GetProductCode($equip['prod_id']);
            $equip['amount'] = $equip['price'];

            $con_ary['line_items'][] = $equip;
        }

        # Save the Contract record
        $contract = new LoanerContract($this->contract_id);
        $contract->copyFromArray($con_ary);
        $contract->save();

        # Find contract id
        $contract_id = $contract->getVar('id_contract');
        $this->change('contract_id', $contract_id);

        # Add log entries
        $this->UpdateContractLog();

        return $contract_id;
    }

    /**
     * Save request line item detail
     *
     * @param array
     */
    public function SaveDetail($form = null)
    {
        if ($this->request_id)
        {
            # Remove old records
            $this->dbh->exec("DELETE FROM loaner_request_detail WHERE request_id = {$this->request_id}");

            $sth = $this->dbh->prepare("INSERT INTO loaner_request_detail
			(item_num, request_id, asset_id, prod_id, price, quantity, uom)
			VALUES
			(?,?,?,?,?,?,?)");

            foreach ($this->equipment as $item_num => $detail)
            {
                if (!$detail['asset_id'] && self::$AUTO_ASSIGN_ASSET)
                    $detail['asset_id'] = $this->FindAsset($detail['prod_id']);

                $this->bindDetailValues($sth, $detail);
                $sth->execute();
            }
            foreach ($this->supplies as $item_num => $detail)
            {
                # Set price for the shipping item
                if ($detail['prod_id'] == LeaseContract::$ZSHIPPING_PROD_ID)
                {
                    if ($this->shipping_charge)
                        $detail['price'] = (float) $this->shipping_charge;
                    else
                        $this->change('shipping_charge', (float) $detail['price']);
                }

                $this->bindDetailValues($sth, $detail);
                $sth->execute();
            }
        }
    }

    /**
     * Create/Update the loaner_agreement record
     *
     * @return integer
     */
    public function SaveLoanerAgreement()
    {
        $loaner_id = (int) $this->loaner_id;

        # Create the loaner agreement
        #
        if ($this->contract_id)
        {
            $loaner_agreement = new LoanerAgreement($loaner_id);
            $loaner_agreement->setVar('facility_id', $this->facility_id);
            $loaner_agreement->LoadAccountingId();
            $loaner_agreement->setVar('contract_id', $this->contract_id);
            $loaner_agreement->setVar('sponsor_id', $this->sponsor_id);
            $loaner_agreement->setVar('active', true);
            $loaner_agreement->setVar('daily_rate', (float) $this->daily_rate);
            $loaner_agreement->setVar('shipping_charge', (float) $this->shipping_charge);
            $loaner_agreement->setVar('expiration_date', $this->expiration_date);
            if ($loaner_agreement->IsForCPM())
            {
                $loaner_agreement->setVar('expiration_date', 0);
                $loaner_agreement->setVar('renewal_due_date', LoanerAgreement::NextCPMRenewal($this->install_date));
            }
            else
            {
                # Renewal is due when term expires
                # Otherwise default to 6 Months after install date
                if ($this->expiration_date)
                    $loaner_agreement->setVar('renewal_due_date', $this->expiration_date);
                else
                    $loaner_agreement->setVar('renewal_due_date', strtotime("+6 Months", $this->install_date));
            }

            $loaner_agreement->save(null);

            # Update loaner_id attribute which
            # Links loaner agreement to contract
            #
            $loaner_id = $loaner_agreement->getVar('loaner_id');
            $this->change('loaner_id', $loaner_id);

            # Update the renewal records
            $sth = $this->dbh->prepare("UPDATE loaner_renewal SET
				new_loaner_id = ?,
				status_id = 4 -- Approved
			WHERE request_id = ?");
            $sth->bindValue(1, $loaner_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->request_id, PDO::PARAM_INT);
            $sth->execute();
        }

        return $loaner_id;
    }

    /**
     * Create/Update Install Order
     *
     * @param string
     */
    public function SaveOrder()
    {
        global $user;

        $items = array();
        $order_id = (int) $this->order_id;

        # Shipping charge is separated when there is a free trial period
        if ($this->request_id && $this->contract_id)
        {
            $has_zshipping = false;
            $last_i = 0;

            ###########################################################
            # Create install order based on the loaner_request_detail
            ###########################################################
            $sth = $this->dbh->prepare("SELECT
				i.item_num, i.quantity, i.uom, i.price,
				p.id AS prod_id, p.code, p.name, p.max_quantity,
				pr.listprice, pr.preferredprice,
				m.id as is_device, m.id as model,
				e.id as asset_id, e.serial_num as serial_number,
				e.facility_id, e.status, e.substatus
			FROM loaner_request_detail i
			INNER JOIN products sp ON i.prod_id = sp.id
			LEFT JOIN service_item_to_product stp ON sp.code = stp.item
			LEFT JOIN products p ON
				CASE WHEN stp.item IS NULL
					THEN
						i.prod_id = p.id
					ELSE
						stp.code = p.code
				END
			LEFT JOIN product_pricing pr ON p.id = pr.id
			LEFT JOIN equipment_models m ON p.code = m.model AND m.active = true
			LEFT JOIN lease_asset_status e ON i.asset_id = e.id
			WHERE i.request_id = ?
			ORDER BY i.item_num");
            $sth->bindValue(1, (int) $this->request_id, PDO::PARAM_INT);
            $sth->execute();
            while ($item = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $item['swap_asset_id'] = 0;

                if (!$item['price'])
                    $item['price'] = '0.0';
                else
                    $item['price'] = $item['price'] * $item['quantity'];

                # Special shipping item, set the price
                if ($item['prod_id'] == LeaseContract::$ZSHIPPING_PROD_ID)
                {
                    $has_zshipping = true;
                    $item['price'] = $this->shipping_charge;
                }

                # Determin if the item needs to be added to the install
                $add_item = true;
                if ($item['asset_id'])
                {
                    # Install required when the asset is not placed+loan at the facility
                    $add_item = false;

                    if ($item['facility_id'] != $this->facility_id)
                        $add_item = true;
                    if ($item['status'] != LeaseAssetTransaction::$PLACED)
                        $add_item = true;
                    if ($item['substatus'] != LeaseAssetTransaction::$LOAN)
                        $add_item = true;
                }

                $last_i = $item['item_num'];

                if ($add_item)
                    $items[$item['item_num']] = $item;
            }

            # Add shipping item
            #
            if ($this->shipping_charge && $has_zshipping == false)
            {
                $last_i++;
                $items[$last_i] = array(
                    'item_num' => $last_i, 'quantity' => 1,
                    'uom' => 'EA', 'price' => $this->shipping_charge,
                    'prod_id' => LeaseContract::$ZSHIPPING_PROD_ID,
                    'code' => 'Z-SHIPPING', 'name' => 'Z-SHIPPING',
                    'is_device' => 0, 'model' => null,
                    'asset_id' => 0, 'serial_number' => '',
                    'swap_asset_id' => 0);
            }
        }

        if (count($items) > 0)
        {
            # Order is already processed, nothing more to do
            $order = new Order($order_id);

            # Populate fields used to update in order save
            $order->create(array(
                'user_id' => $this->user_id,
                'order_date' => time(),
                'status_id' => Order::$QUEUED,
                'comments' => "Install Order\n{$this->shipping_notes}",
                'ship_to' => 1,
                'urgency' => 1,
                'inst_date' => $this->install_date,
                'facility_id' => $this->facility_id,
                'type_id' => Order::$INSTALL_ORDER,
                'sname' => $this->shipping_attention,
                'address' => $this->s_contact->getVar('address1'),
                'address2' => $this->s_contact->getVar('address2'),
                'city' => $this->s_contact->getVar('city'),
                'state' => $this->s_contact->getVar('state'),
                'zip' => $this->s_contact->getVar('zip'),
                'phone' => $this->s_contact->getVar('phone'),
                'fax' => $this->s_contact->getVar('fax'),
                'items' => $items));

            # Add shipping charge
            $order->change('shipping_cost', $this->shipping_charge);

            # Link to the contract
            if ($this->contract_id)
                $order->change('contract_id', $this->contract_id);

            # Set order_id
            $order_id = $order->getId();
            $this->change('order_id', $order_id);
        }

        return $order_id;
    }

    /**
     * Save to database
     */
    public function Save($form = null)
    {
        global $user;

        # First save contact ID
        if ($this->s_contact)
        {
            if (!$this->shipping_contact)
                $this->shipping_contact = $this->s_contact->getId();
        }

        if ($this->request_id)
        {
            $this->db_update();
        }
        else
        {
            $this->open_date = time();
            $this->user_id = $user->getId();
            $this->db_insert();
        }

        $this->SaveDetail($form);
    }

    /**
     * View Editable version of the form
     *
     * @param array
     *
     * @return string
     */
    public function ShowEditForm($form)
    {
        $this->ShowForm($form, 0);
    }

    /**
     * Build form html
     *
     * @param array
     * @param integer
     *
     * @return string
     */
    public function ShowForm($form, $VIEW_ONLY = 1)
    {
        global $user, $date_format, $calendar_format;

        # Validate that the corporate client exits, in stage 2
        $step_no = isset ($form['step_no']) ? $form['step_no'] : 0;

        # Show CustID
        $cust_id = ($this->accounting_id) ? "({$this->accounting_id})" : '(Unknown Facility)';

        $s_state_list = Forms::createStateList($this->s_contact->getVar('state'));

        # No changes to existing facilities
        $disable_facility_fields = ($this->facility_id) ? "<script type=\"text/javascript\">\nDisableFacilityFields();\n</script>\n" : "";

        # Populate equipment and supply tabs
        $equipment_ary = "";
        if ($this->equipment)
        {
            $i = 0;
            foreach ($this->equipment as $device)
            {
                $equipment_ary .= "equipment[$i] = " . json_encode($device) . ";\n";
                $i++;
            }
        }

        $supply_ary = "";
        if ($this->supplies)
        {
            $i = 0;
            foreach ($this->supplies as $item)
            {
                # No null prices for purchase 0.0 is needed in SO generation
                $item['price'] = (float) $item['price'];
                $supply_ary .= "supplies[$i] = " . json_encode($item) . ";\n";
                $i++;
            }
        }

        if (!$this->sponsor_id)
            $this->sponsor_id = $user->GetId();
        $sponsor_options = "<option value=0>-- Select User --</option>\n";
        # Sponsor User list
        $f_cpm_options = $t_cpm_options = "";
        $sth = $this->dbh->query("SELECT DISTINCT
			u.id,
			u.lastname || ', ' || u.firstname AS fullname,
			u.active AS active
		FROM users u
		WHERE u.type = 1
			AND u.active = true
			AND u.id NOT IN (" . implode(',', Config::$PROTECTED_USERS) . ")
		ORDER BY fullname");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($row['id'] == $this->sponsor_id) ? "selected" : "";
            $sponsor_options .= "<option value='{$row['id']}' {$sel}>{$row['fullname']}</option>\n";
        }

        # Convert time to date string
        $install_date = "";
        if ($this->install_date)
            $install_date = date($date_format, $this->install_date);

        # Convert time to date string
        $billing_start_date = "";

        if ($this->billing_start_date)
            $billing_start_date = date($date_format, $this->billing_start_date);

        # Convert time to date string
        $expiration_date = "";
        if ($this->expiration_date)
            $expiration_date = date($date_format, $this->expiration_date);

        $form = "
<script type=\"text/javascript\">
obj_form = document.loaner;
qs_form = document.supply_form;
var DATE_FORMAT = '$date_format';
var CAL_FORMAT = '$calendar_format';
var VIEW_ONLY = $VIEW_ONLY;
var MIN_LEASE_AMOUNT = 0;

var equip_options = new Array();
{$this->getEquipList()}
var item_options = new Array();
{$this->getItemList()}
var equipment = new Array();
$equipment_ary
var supplies = new Array();
$supply_ary

</script>
<style type='text/css'>
// custom styles for provider number auto complete
.prov
{
	font-style:italic;
}
.yui-ac-highlight .prov
{
	font-style:italic;
}
</style>

	<form name='loaner' method='post' action='{$_SERVER['PHP_SELF']}' onSubmit='return ValidateLoaner(this);'>
	<input type='hidden' name='request_id' value='{$this->request_id}'/>
	<input type='hidden' name='facility_id' value='{$this->facility_id}'/>
	<input type='hidden' name='shipping_contact' value='{$this->shipping_contact}'/>
	<input type='hidden' name='user_id' value='{$this->user_id}'/>
	<input type='hidden' name='act' value='save'/>
	<input type='hidden' name='step_no' value='{$step_no}'/>

	<div class='wf_body'>
		<div class='hdr'>Loaner Request Form</div>
		<div class='note'>
			Completed By: Aron Vargas
		</div>
		<br/>
		<div class='wf_ib'>
			<table class='e_form grey_border' width='500' cellpadding='4' cellspacing='0'>
				<tr>
					<th colspan=2 class='subheader' style='padding: 5px;'>Customer:</th>
				</tr>
				<tr>
					<th>Facility Name:</th>
					<td nowrap>
						<div id='fac_container' style='position:relative;'>
							<input type='text' id='facility_name' name='facility_name' value='" . htmlentities($this->facility_name, ENT_QUOTES) . "' maxlength='64' style='width:20em; position:static; overflow: visible;' />
							<img class='form_bttn' id='dis_facility' src='images/cancel.png' onclick=\"ClearFacility();\" alt='Clear Facility' title='Clear Facility' />
							<span id='facility_indicator'>{$cust_id}</span>
							<div id='acc_facility' style='font-size: 8pt; position:static;'></div>
						</div>
					</td>
				</tr>
				<tr>
					<th>Scheduled Install Date:</th>
					<td>
						<input type='text' name='install_date' id='install_date' size='8' value='$install_date' />
						<img class='form_bttn' id='install_date_trg' src='images/calendar-mini.png' onclick=\"OpenCal('install_date');\" alt='Calendar' title='Calendar' />
					</td>
				</tr>
				<tr>
					<th>Billing Start Date:</th>
					<td>
						<input type='text' name='billing_start_date' id='billing_start_date' size='8' value='$billing_start_date' />
						<img class='form_bttn' id='billing_start_date_trg' src='images/calendar-mini.png' onclick=\"OpenCal('billing_start_date');\" alt='Calendar' title='Calendar' />
					</td>
				</tr>
				<tr>
					<th>Expiration Date:</th>
					<td>
						<input type='text' name='expiration_date' id='expiration_date' size='8' value='$expiration_date' />
						<img class='form_bttn' id='expiration_date_trg' src='images/calendar-mini.png' onclick=\"OpenCal('expiration_date');\" alt='Calendar' title='Calendar' />
					</td>
				</tr>
				<tr>
					<th>Responsible Party:</th>
					<td>
						<select id='sponsor_id' name='sponsor_id'>
							$sponsor_options
						</select>
					</td>
				</tr>
				<tr>
					<th>Daily Rate:</th>
					<td>
						<input type='text' name='daily_rate' id='daily_rate' size='8' value='{$this->daily_rate}' />
					</td>
				</tr>
				<tr>
					<th>Shipping Charge:</th>
					<td>
						<input type='text' name='shipping_charge' id='shipping_charge' size='8' value='{$this->shipping_charge}' />
					</td>
				</tr>
				<tr valign='top'>
					<th>Ship To:</th>
					<td>
						<div id='addr_select'>
						</div>
						<input type='hidden' name='contact_shipping[contact_id]' value='{$this->s_contact->getID()}' />
						<table class='e_form' cellpadding='2' cellspacing='0'>
							<tr>
								<td colspan='3'>
									<label>Attention:</label><br/>
									<input type='text' name='shipping_attention' value='" . htmlentities($this->shipping_attention, ENT_QUOTES) . "' size='30' maxlength='64' />
								</td>
							</tr>
							<tr>
								<td colspan='3'>
									<label>Address:</label><br/>
									<input type='text' name='contact_shipping[address1]' value='" . htmlentities($this->s_contact->getVar('address1'), ENT_QUOTES) . "' size='30' disabled />
								</td>
							</tr>
							<tr>
								<td colspan='3'>
									<label>Line 2:</label><br/>
									<input type='text' name='contact_shipping[address2]' value='" . htmlentities($this->s_contact->getVar('address2'), ENT_QUOTES) . "' size='30' disabled />
								</td>
							</tr>
							<tr>
								<td>
									<label>City:</label><br/>
									<input type='text' name='contact_shipping[city]' value='" . htmlentities($this->s_contact->getVar('city'), ENT_QUOTES) . "' size='15' disabled />
								</td>
								<td>
									<label>State:</label><br/>
									<select name='contact_shipping[state]'  disabled >
										{$s_state_list}
									</select>
								</td>
								<td>
									<label>Zip:</label><br/>
									<input type='text' name='contact_shipping[zip]' value='{$this->s_contact->getVar('zip')}' size='7' disabled />
								</td>
							</tr>
							<tr>
								<td>
									<label>Phone:</label><br/>
									<input type='text' name='contact_shipping[phone]' value='{$this->s_contact->getVar('phone')}' size='12' maxlength='20' disabled />
								</td>
								<td colspan='3'>
									<label>Fax:</label><br/>
									<input type='text' name='contact_shipping[fax]' value='{$this->s_contact->getVar('fax')}' size='12' maxlength='20' disabled />
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<br/>
			<table class='e_form grey_border' width='500' cellpadding='4' cellspacing='0'>
				<tr>
					<th class='subheader' style='padding: 5px;'>Special Needs or Comments:</th>
				</tr>
				<tr>
					<td class='form'>
						<textarea name='comments' style='width: 480px; height: 100px;'>{$this->comments}</textarea>
					</td>
				</tr>
			</table>
			<br/>
			<table class='e_form grey_border' width='500' cellpadding='4' cellspacing='0'>
				<tr>
					<th class='subheader' style='padding: 5px;'>Shipping Notes:</th>
				</tr>
				<tr>
					<td class='form'>
						<textarea name='shipping_notes' style='width: 480px; height: 100px;'>{$this->shipping_notes}</textarea>
					</td>
				</tr>
			</table>
		</div>
		<div class='wf_ib'>
			<table id='equipment' class='e_form grey_border' width='500' cellpadding='5' cellspacing='0'>
				<tr>
					<th class='subheader' colspan=2>Equipment:
						<span style='float: right;'>
							<input type='button' name='add_equip' value='Add' onclick='OpenEquipDialog(-1, event);' >
						</span>
					</th>
				</tr>
				<tr>
					<th class='list'>Model</th>
					<th class='list'>&nbsp;</th>
				</tr>
			</table>
			<br/>
			<table id='supplies' class='e_form  grey_border' width='500' cellpadding='5' cellspacing='0'>
				<tr>
					<th class='subheader' colspan=5>Supplies:
						<span style='font-size: x-small'>(please enter quantities)</span>
						<span style='float: right;'>
							<input type='button' name='add_supply' value='Add' onclick='OpenSupplyDialog(-1, event);' >
						</span>
					</th>
				</tr>
				<tr>
					<th class='list'>Item</th>
					<th class='list'>Qty</th>
					<th class='list'>UOM</th>
					<th class='list'>Price</th>
					<th class='list'>&nbsp;</th>
				</tr>
			</table>
		</div>
	</div>
	</form>
	<div id='equip_dialog' style='visibility:hidden;'>
		<div class='hd'>Device</div>
		<div class='bd' style='background-color:white;padding:0;'>
			<form name='device_form' action='work_flow.php'>
			<input type='hidden' name='dev_id' value=0 />
			<input type='hidden' name='df_lease_type' value='12' />
			<input type='hidden' name='df_asset_id' value=0 />
			<table class='form' width='100%' cellpadding=2 cellspacing=1 border=0 style='margin:0;'>
				<tr>
					<th class='form'>Device Model:</th>
					<td class='form'>
						<select name='df_prod_id'>
						</select>
					</td>
				</tr>
			</table>
			</form>
		</div>
		<div class='ft' style='padding:5px;'></div>
	</div>
	<div id='supply_dialog' style='visibility:hidden;'>
		<div class='hd'>Item</div>
		<div class='bd' style='background-color:white;padding:0;'>
			<form id='supply_form' name='supply_form' action='work_flow.php'>
			<input type='hidden' name='item_id' value=0 />
			<input type='hidden' name='sf_lease_type' value='12' />
			<input type='hidden' name='facility_id' value='{$this->facility_id}'/>
			<table class='form' width='100%' cellpadding=2 cellspacing=1 border=0 style='margin:0;'>
				<tr>
					<th class='form'>Product:</th>
					<td class='form'>
						<select id='sf_prod_id' name='sf_prod_id' onChange=\"SetUOMOptions(this, this.form['sf_uom'], null, 'default');\">
						</select>
						<img src='images/search-mini.png' onclick=\"OpenQuickSearch('sf_prod_id',1,event);\" height='16' alt='Search Items' title='Search Items' />
					</td>
				</tr>
				<tr>
					<th class='form'>Quantity:</th>
					<td class='form'>
						<input type='text' id='sf_qty' name='sf_qty' value='1' size='5'/>
					</td>
				</tr>
				<tr>
					<th class='form'>UOM:</th>
					<td class='form'>
						<select id='sf_uom' name='sf_uom'>
						</select>
					</td>
				</tr>
				<tr>
					<th class='form'>Unit Price:</th>
					<td class='form'>
						<input type='text' id='sf_price' name='sf_price' value='' size='5' />
					</td>
				</tr>
			</table>
			</form>
		</div>
		<div class='ft' style='padding:5px;'></div>
	</div>
	{$disable_facility_fields}
	<script type=\"text/javascript\"> YAHOO.util.Event.onDOMReady(InitializeForm); </script>\n";

        echo $form;
    }

    /**
     * View Only version of the form
     *
     * @param array
     *
     * @return string
     */
    public function ShowViewForm($form)
    {
        $this->ShowForm($form, 1);
    }

    /**
     * Delete and Insert Log entries
     */
    private function UpdateContractLog()
    {
        # Define Loaner request action text
        $action_txt = get_class($this);

        # Remove old notes
        $sth = $this->dbh->prepare("DELETE FROM contract_log
		WHERE action like '$action_txt:%' AND contract_id = ?");
        $sth->bindValue(1, $this->contract_id, PDO::PARAM_INT);
        $sth->execute();

        # Copy task notes to contract log
        #
        if ($this->work_flow_id && $this->contract_id)
        {
            $sth = $this->dbh->prepare("INSERT INTO contract_log
			(contract_id, user_id, tstamp, action, comment)
			SELECT ?, n.user_id, n.creation_date, '$action_txt: ' || n.action, n.note
			FROM wf_stage_note n
			INNER JOIN work_flow_stage s ON n.wf_stage_id = s.id
			WHERE s.work_flow_id = ?
			AND n.note <> ''");
            $sth->bindValue(1, $this->contract_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->work_flow_id, PDO::PARAM_INT);
            $sth->execute();
        }
    }
}
?>