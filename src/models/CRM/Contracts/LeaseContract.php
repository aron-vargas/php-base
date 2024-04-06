<?php
/**
 * @package Freedom
 * @author Aron Vargas
 */

class LeaseContract extends BaseClass {
    protected $db_table = 'contract';	# string
    protected $p_key = 'id_contract';	# string

    protected $id_contract;				# int
    protected $id_facility;				# int
    protected $id_contract_type;		# int
    protected $monthly_revenue;			# float
    protected $comments;				# string
    protected $date_shipped;			# date
    protected $date_lease;				# date
    protected $da_received;				# int
    protected $date_da;					# date
    protected $contract_received;		# int
    protected $date_received;			# date
    protected $boa = 0;					# int
    protected $date_boa;				# date
    protected $date_install;			# date
    protected $date_billing_start;		# date
    protected $date_cancellation;		# date
    protected $date_expiration;			# date
    protected $contract_version;		# string
    protected $sale_amount = 0;			# float
    protected $date_billed_through;		# date
    protected $non_cancellable = false;	# boolean
    protected $market_basket = true;	# boolean
    protected $ip_marketing = true;		# boolean
    protected $nr_service;				# int
    protected $visit_frequency;			# int
    protected $contract_addr_id;		# integer
    protected $lease_addr_id;			# integer
    protected $supply_addr_id;			# integer
    protected $contract_file_name;		# string (64)
    protected $cust_po;					# string (32),
    protected $facility_pay = false;	# boolean
    protected $date_invoice_start;		# date
    protected $date_effective;			# date
    protected $length_term_id = 1;		# integer
    protected $renewal_period = 1;		# integer
    protected $risk_share = false;		# boolean
    protected $payment_term_id = 3;		# integer
    protected $termination = '60 Days';	# string
    protected $pricing_method;			# character varying(32) DEFAULT 'add'::character varying,
    protected $interest_rate;			# double precision DEFAULT 1.5,
    protected $termination_notice_period_id;	# integer,
    protected $confidentiality_period_id;		# integer,
    protected $non_solicitation_period_id;		# integer,
    protected $jurisdiction_state;		# character varying(32) DEFAULT 'NV'::character varying,
    protected $remote_services;			# integer
    protected $lease_agreement_id;		# integer
    protected $original_contract_amount;	# double

    protected $auto_renew;				# bool
    protected $payment_terms;			# string
    protected $po_number;				# string
    protected $file_path = '';			# string
    protected $warranty = 0;			# int
    protected $tax_exempt;				# int
    protected $revenue_stream_no;		# string
    protected $period;

    protected $status;					# string
    protected $ship_install = false;	# bool

    protected $customer_addreses;		# array
    public $line_items;					# array
    protected $transaction_history;		# array

    static public $SRV00000_PROD_ID = 240;
    static public $SRV000WV_PROD_ID = 854;
    static public $SRVDYSWV_PROD_ID = 3043;
    static public $SRV00DYS_PROD_ID = 3042;
    static public $SRV0PAOC_PROD_ID = 855;
    static public $SRV00OSS_PROD_ID = 3254;
    static public $SRV00OSS_ITEM_CODE = "SRV-00OSS";
    static public $SRVWARRANTY_PROD_ID = 905;
    static public $SRVMAINTENANCE_PROD_ID = 3670;
    static public $CYCLE_PROD_IDS = array(243, 1926);
    static public $DYSPH_PROD_IDS = array(3040, 3031, 2929);
    static public $OSTAND_PROD_CODES = array('SRV-OSS01', 'A005-509');
    static public $ZPTAX_PROD_ID = 514;
    static public $ZSHIPPING_PROD_ID = 569;
    static public $EQUIPMENT_WHSE = 'EQUIP';
    static public $EXEMPT_CPS = array('KIN', 'RG'); #,'RG','KIN','KINEX','KINXB');
    static public $EXEMPT_STATES = array('DE', 'NH', 'IL', 'MN', 'SD', 'NY', 'PA', 'IA', 'ND', 'HI', 'NJ', 'OH');
    static public $ADD_TYPE = 1;
    static public $CORP_ADD_TYPE = 2;
    static public $CORP_TYPE = 3;
    static public $FULL_TYPE = 4;
    static public $SIS_TYPE = 5;
    static public $UI_TYPE = 6;
    static public $CYCLE_TYPE = 9;
    static public $PURCHASE_TYPE = 10;
    static public $HH_TYPE = 11;
    static public $LOANER_TYPE = 12;
    static public $OMNIVR_TYPE = 13;
    static public $SUPPLY_TYPE = 14;

    static public $option_termination_notice = 'termination_notice';
    static public $option_ip_confidentiality = 'ip_confidentiality';
    static public $option_non_solicitation = 'non_solicitation';

    static public $REMOTE_SERVICE_CLINICAL = 1;

    /**
     * Create an copy of an instance
     * and unsets undesired attributes
     *
     * @return object
     */
    public function __clone()
    {
        $this->id_contract = null;
    }

    /**
     * Create an instance of a LeaseContract
     *
     * @param integer
     * @return object
     */
    public function __construct($contract_id = null)
    {
        $this->dbh = DataStor::getHandle();

        if ($contract_id)
            $this->id_contract = $contract_id;

        $this->load();
    }

    /**
     * Insert history record
     */
    static public function AddHistory($id, $updated_by, $updated_at_date, $change_info, $src)
    {
        global $user;

        $dbh = DataStor::GetHandle();

        $updated_by = (int) $updated_by;
        $updated_at_date = $dbh->quote($updated_at_date);
        $change_info = $dbh->quote($change_info);
        $src = $dbh->quote($src);

        $sth = $dbh->prepare("INSERT INTO contract_cr
		(
			id_contract, id_facility, id_contract_type, monthly_revenue,
            comments, date_shipped, date_lease, da_received, date_da, contract_received,
            date_received, boa, date_boa, date_install, date_billing_start,
            date_cancellation, contract_version, sale_amount, date_billed_through,
            non_cancellable, market_basket, ip_marketing, nr_service, visit_frequency,
            contract_addr_id, lease_addr_id, supply_addr_id, contract_file_name,
            cust_po, facility_pay, date_invoice_start, date_effective, length_term_id,
            risk_share, payment_term_id, date_expiration, lease_agreement_id,
            termination, pricing_method, interest_rate, termination_notice_period_id,
            confidentiality_period_id, non_solicitation_period_id, jurisdiction_state,
            renewal_period,remote_services,original_contract_amount,
			user_id,tstamp,change_info,src
		)
		SELECT
			id_contract, id_facility, id_contract_type, monthly_revenue,
            comments, date_shipped, date_lease, da_received, date_da, contract_received,
            date_received, boa, date_boa, date_install, date_billing_start,
            date_cancellation, contract_version, sale_amount, date_billed_through,
            non_cancellable, market_basket, ip_marketing, nr_service, visit_frequency,
            contract_addr_id, lease_addr_id, supply_addr_id, contract_file_name,
            cust_po, facility_pay, date_invoice_start, date_effective, length_term_id,
            risk_share, payment_term_id, date_expiration, lease_agreement_id,
            termination, pricing_method, interest_rate, termination_notice_period_id,
            confidentiality_period_id, non_solicitation_period_id, jurisdiction_state,
            renewal_period,remote_services,original_contract_amount,
			$updated_by, $updated_at_date, $change_info, $src
		FROM contract
		WHERE id_contract = ?");
        $sth->bindValue(1, $id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Create an array used to create this order
     *
     * @param Order
     * @param array
     * @param array
     */
    public function AddOrderInfo($order, $defaults, $info)
    {
        # Add addtional information to the order_fields array
        if (is_array($info))
        {
            foreach ($info as $key => $value)
            {
                if (@property_exists($order, $key))
                {
                    $defaults[$key] = $value;
                }
            }
        }

        return $defaults;
    }

    /**
     * Insert entry into the note table
     *
     * @param int
     * @param string
     * @param string
     */
    public function AddNote($user_id, $note)
    {
        if ($user_id && $this->id_contract)
        {
            $sth = $this->dbh->prepare("INSERT INTO contract_notes
			(id_contract, date_entry, by_user, note)
			VALUES (?, ?, ?, ?)");
            $sth->bindValue(1, $this->id_contract, PDO::PARAM_INT);
            $sth->bindValue(2, date('Y-m-d'), PDO::PARAM_STR);
            $sth->bindValue(3, $user_id, PDO::PARAM_INT);
            $sth->bindValue(4, substr($note, 0, 256), PDO::PARAM_STR);
            $sth->execute();
        }
    }

    /**
     * Insert entry into the log table
     *
     * @param int
     * @param string
     * @param string
     */
    public function AddLogEntry($user_id, $action, $comment)
    {
        if ($user_id && $this->id_contract)
        {
            $sth = $this->dbh->prepare("INSERT INTO contract_log
			(contract_id, user_id, tstamp, action, comment)
			VALUES (?, ?, ?, ?, ?)");
            $sth->bindValue(1, $this->id_contract, PDO::PARAM_INT);
            $sth->bindValue(2, $user_id, PDO::PARAM_INT);
            $sth->bindValue(3, time(), PDO::PARAM_INT);
            $sth->bindValue(4, substr($action, 0, 64), PDO::PARAM_STR);
            $sth->bindValue(5, $comment, PDO::PARAM_STR);
            $sth->execute();
        }
    }

    /**
     * @param PDOStatement
     * @return int
     */
    public function BindValues(&$sth)
    {
        $monthly_revenue_type = ($this->monthly_revenue) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $original_contract_amount_t = ($this->original_contract_amount) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $date_shipped_type = ($this->date_shipped) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $date_lease_type = ($this->date_lease) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $date_da_type = ($this->date_da) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $date_received_type = ($this->date_received) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $date_boa_type = ($this->date_boa) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $date_install_type = ($this->date_install) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $date_billing_start_type = ($this->date_billing_start) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $date_cancellation_type = ($this->date_cancellation) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $sale_amount_type = ($this->sale_amount) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $date_billed_through_type = ($this->date_billed_through) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $payment_term_type = ($this->payment_term_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $length_term_type = ($this->length_term_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $renewal_period_type = ($this->renewal_period) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $termination_type = ($this->termination) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $pricing_method_type = ($this->pricing_method) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $lease_agreement_id_type = ($this->lease_agreement_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $interest_rate_type = (empty($this->interest_rate)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $termination_notice_period_id_type = ($this->termination_notice_period_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $confidentiality_period_id_type = ($this->confidentiality_period_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $non_solicitation_period_id_type = ($this->non_solicitation_period_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $jurisdiction_state_type = (empty($this->jurisdiction_state)) ? PDO::PARAM_NULL : PDO::PARAM_STR;

        $i = 1;
        $sth->bindValue($i++, (int) $this->id_facility, PDO::PARAM_INT);
        $sth->bindValue($i++, (int) $this->id_contract_type, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->monthly_revenue, $monthly_revenue_type);
        $sth->bindValue($i++, $this->comments, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->date_shipped, $date_shipped_type);
        $sth->bindValue($i++, $this->date_lease, $date_lease_type);
        $sth->bindValue($i++, (int) $this->da_received, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->date_da, $date_da_type);
        $sth->bindValue($i++, (int) $this->contract_received, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->date_received, $date_received_type);
        $sth->bindValue($i++, (int) $this->boa, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->date_boa, $date_boa_type);
        $sth->bindValue($i++, $this->date_install, $date_install_type);
        $sth->bindValue($i++, $this->date_billing_start, $date_billing_start_type);
        $sth->bindValue($i++, $this->date_cancellation, $date_cancellation_type);
        $sth->bindValue($i++, $this->contract_version, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->sale_amount, $sale_amount_type);
        $sth->bindValue($i++, $this->date_billed_through, $date_billed_through_type);
        $sth->bindValue($i++, (int) $this->visit_frequency, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->payment_term_id, $payment_term_type);
        $sth->bindValue($i++, $this->length_term_id, $length_term_type);
        $sth->bindValue($i++, $this->renewal_period, $renewal_period_type);
        $sth->bindValue($i++, $this->termination, $termination_type);
        $sth->bindValue($i++, (int) $this->remote_services, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->pricing_method, $pricing_method_type);
        $sth->bindValue($i++, $this->lease_agreement_id, $lease_agreement_id_type);
        $sth->bindValue($i++, $this->interest_rate, $interest_rate_type);
        $sth->bindValue($i++, $this->termination_notice_period_id, $termination_notice_period_id_type);
        $sth->bindValue($i++, $this->confidentiality_period_id, $confidentiality_period_id_type);
        $sth->bindValue($i++, $this->non_solicitation_period_id, $non_solicitation_period_id_type);
        $sth->bindValue($i++, $this->jurisdiction_state, $jurisdiction_state_type);
        $sth->bindValue($i++, $this->original_contract_amount, $original_contract_amount_t);
        $sth->bindValue($i++, $this->id_contract, PDO::PARAM_INT);

        return $i;
    }

    /**
     * Perform Insert
     */
    public function DBInsert()
    {
        $sth = $this->dbh->prepare("INSERT INTO contract (
			id_facility,				id_contract_type,
			monthly_revenue,			comments,
			date_shipped,				date_lease,
			da_received,				date_da,
			contract_received,			date_received,
			boa,						date_boa,
			date_install,				date_billing_start,
			date_cancellation,			contract_version,
			sale_amount,				date_billed_through,
			nr_service,					-- Default to 3 on insert only
			visit_frequency,			payment_term_id,
			length_term_id,				renewal_period,
			termination,				remote_services,
			pricing_method,				lease_agreement_id,
			interest_rate,				termination_notice_period_id,
			confidentiality_period_id,	non_solicitation_period_id,
			jurisdiction_state,			original_contract_amount,
			id_contract)
		 VALUES (?,?, ?,?, ?,?, ?,?, ?,?, ?,?, ?,?, ?,?, ?,?, 3, ?,?, ?,?, ?,?, ?,?, ?,?, ?,?, ?,?, ?)");
        $this->bindValues($sth);
        $sth->execute();
    }

    /**
     * Perform Update
     */
    public function DBUpdate()
    {
        $sth = $this->dbh->prepare("UPDATE contract SET
			id_facility = ?,				id_contract_type = ?,
			monthly_revenue = ?,			comments = ?,
			date_shipped = ?,				date_lease = ?,
			da_received = ?,				date_da = ?,
			contract_received = ?,			date_received = ?,
			boa = ?,						date_boa = ?,
			date_install = ?,				date_billing_start = ?,
			date_cancellation = ?,			contract_version = ?,
			sale_amount = ?,				date_billed_through = ?,
			visit_frequency = ?,			payment_term_id = ?,
			length_term_id = ?,				renewal_period = ?,
			termination = ?,				remote_services = ?,
			pricing_method = ?,				lease_agreement_id = ?,
			interest_rate = ?,				termination_notice_period_id = ?,
			confidentiality_period_id = ?,	non_solicitation_period_id = ?,
			jurisdiction_state = ?,			original_contract_amount = ?
		WHERE id_contract = ?");
        $this->bindValues($sth);
        $sth->execute();
    }

    /**
     * Locate the first line item with matches
     *
     * @param string
     * @param mixed
     * @param integer
     *
     * @return integer
     */
    public function FindItemIndex($key, $value, $offset = 0)
    {
        $index = null;

        if (count($this->line_items))
        {
            foreach ($this->line_items as $i => $item)
            {
                # Skin N items
                if ($i >= $offset)
                {
                    # Match item attribute to value
                    if ($item->GetVar($key) == $value)
                    {
                        $index = $i;
                        break;
                    }
                }
            }
        }

        return $index;
    }

    /**
     * Locate the first line item with matches
     * (asset_id must be null)
     *
     * @param string
     * @param mixed
     * @param integer
     *
     * @return integer
     */
    public function FindItemNoAsset($key, $value, $offset = 0)
    {
        $index = null;

        if (count($this->line_items))
        {
            foreach ($this->line_items as $i => $item)
            {
                # Skin N items
                if ($i >= $offset)
                {
                    # Match item attribute to value asset_id must be null
                    if (is_null($item->GetVar('asset_id')) && $item->GetVar($key) == $value)
                    {
                        $index = $i;
                        break;
                    }
                }
            }
        }

        return $index;
    }

    /**
     * Populates this object from the matching record in the database.
     */
    public function load()
    {
        BaseClass::load();

        $this->loadEquipment();

        if ($this->id_contract)
            $this->SetOrigin();
    }

    /**
     * Populate line items from database
     */
    public function loadEquipment()
    {
        if ($this->id_contract)
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
			LEFT JOIN equipment_models m ON a.model_id = m.id
			LEFT JOIN maintenance_agreement ma ON l.maintenance_agreement_id = ma.id
			LEFT JOIN warranty_option w ON l.warranty_option_id = w.warranty_id
			WHERE l.contract_id = ?
			ORDER BY l.line_num");
            $sth->bindValue(1, $this->id_contract, PDO::PARAM_INT);
            $sth->execute();

            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $item = new ContractLineItem($row, false);
                $this->line_items[] = $item;
            }
        }
    }

    /**
     * Load existing contract for the facility and type given
     * Used to make sure there is a single lease per facility
     *
     * @param integer
     * @param integer
     * @return integer
     */
    public function LoadExisting($facility_id, $lease_type)
    {
        if (!$this->id_contract)
        {
            $sth = $this->dbh->prepare("SELECT id_contract
			FROM contract
			WHERE contract_version != 'INACTIVE'
			AND (date_cancellation IS NULL OR date_cancellation > CURRENT_DATE)
			AND (CASE WHEN date_install = date_cancellation THEN false ELSE true END)
			AND id_facility = ? AND id_contract_type = ?");
            $sth->bindValue(1, $facility_id, PDO::PARAM_INT);
            $sth->bindValue(2, $lease_type, PDO::PARAM_INT);
            $sth->execute();
            $contract_id = $sth->fetchColumn();
            if ($contract_id)
            {
                $this->id_contract = $contract_id;
                $this->load();
            }
        }

        return $this->id_contract;
    }

    /**
     * (L)ease (T)o (P)urchase
     *
     * @return StdClass
     */
    public function LTP()
    {
        $sth = $this->dbh->prepare("SELECT * FROM lease_to_purchase WHERE purchase_contract_id = ?");
        $sth->bindValue(1, $this->id_contract, PDO::PARAM_INT);
        $sth->execute();
        return $sth->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Used to remove links to the assets
     * Goal is to have links to one contract only
     *
     * @param integer
     */
    public function ClearAssets($asset_id = null)
    {
        if (is_numeric($asset_id))
        {
            $sth = $this->dbh->prepare("UPDATE contract_line_item
			SET asset_id = NULL
			WHERE asset_id = ?");
            $sth->bindValue(1, $asset_id, PDO::PARAM_INT);
            $sth->execute();
        }
        else
        {
            $sth = $this->dbh->prepare("UPDATE contract_line_item
			SET asset_id = NULL
			WHERE contract_id = ?");
            $sth->bindValue(1, $this->id_contract, PDO::PARAM_INT);
            $sth->execute();
        }
    }

    /**
     * Copy array values into attributes
     *
     * @param $form array
     */
    public function copyFromArray($form = array())
    {
        $copy_array = $this->line_items;
        BaseClass::copyFromArray($form);
        $this->line_items = $copy_array;

        if (isset($form['contract_received']))
            $this->contract_received = $this->ParseUnixTime($form['contract_received']);

        if (isset($form['line_items']) && is_array($form['line_items']))
        {
            foreach ($form['line_items'] as $item_ary)
            {
                # Determine which item to update
                #
                $i = null;
                if (isset($item_ary['line_num']) && $item_ary['line_num'] > 0)
                {
                    # Locate based on line_num / primary key
                    #
                    $i = $this->FindItemIndex('line_num', $item_ary['line_num']);
                }
                else if (isset($item_ary['asset_id']) && $item_ary['asset_id'] > 0)
                {
                    # Locate base on asset_id
                    #
                    $i = $this->FindItemIndex('asset_id', $item_ary['asset_id']);
                }

                if (is_null($i))
                {
                    # New Item append it to the array
                    #
                    $line_item = new ContractLineItem();
                    foreach ($item_ary as $key => $value)
                    {
                        # Date validation (will convert unix_times)
                        if (strstr($key, 'date'))
                            $value = $line_item->ParseDate($value);

                        $line_item->SetVar($key, $value);
                    }
                    $line_item->SetVar('date_added', date('Y-m-d'));
                    $line_item->SetVar('contract_id', $this->id_contract);

                    $this->line_items[] = $line_item;
                }
                else
                {
                    foreach ($item_ary as $key => $value)
                    {
                        # Date validation (will convert unix_times)
                        if (strstr($key, 'date'))
                            $value = $this->line_items[$i]->ParseDate($value);

                        $this->line_items[$i]->SetVar($key, $value);
                    }
                }
            }
        }
    }

    /**
     * Convert this object to an associative array
     *
     * @return array
     */
    public function toArray()
    {
        $obj_ary = array(
            'id_contract' => $this->id_contract,
            'id_facility' => $this->id_facility,
            'id_contract_type' => $this->id_contract_type,
            'monthly_revenue' => $this->monthly_revenue,
            'original_contract_amount' => $this->original_contract_amount,
            'comments' => $this->comments,
            'date_shipped' => $this->date_shipped,
            'date_lease' => $this->date_lease,
            'da_received' => $this->da_received,
            'date_da' => $this->date_da,
            'contract_received' => $this->contract_received,
            'date_received' => $this->date_received,
            'boa' => $this->boa,
            'date_boa' => $this->date_boa,
            'date_install' => $this->date_install,
            'date_billing_start' => $this->date_billing_start,
            'date_cancellation' => $this->date_cancellation,
            'contract_version' => $this->contract_version,
            'sale_amount' => $this->sale_amount,
            'date_billed_through' => $this->date_billed_through,
            'non_cancellable' => $this->non_cancellable,
            'market_basket' => $this->market_basket,
            'ip_marketing' => $this->ip_marketing,
            'nr_service' => $this->nr_service,
            'visit_frequency' => $this->visit_frequency,
            'ship_install' => $this->ship_install,
            'termination' => $this->termination,
            'contract_file_name' => $this->contract_file_name,
            'cust_po' => $this->cust_po,
            'facility_pay' => $this->facility_pay,
            'date_invoice_start' => $this->date_invoice_start,
            'date_effective' => $this->date_effective,
            'length_term_id' => $this->length_term_id,
            'risk_share' => $this->risk_share,
            'payment_term_id' => $this->payment_term_id,
            'date_expiration' => $this->date_expiration,
            'pricing_method' => $this->pricing_method,
            'interest_rate' => $this->interest_rate,
            'termination_notice_period_id' => $this->termination_notice_period_id,
            'confidentiality_period_id' => $this->confidentiality_period_id,
            'non_solicitation_period_id' => $this->non_solicitation_period_id,
            'jurisdiction_state' => $this->jurisdiction_state,
            'renewal_period' => $this->renewal_period,
            'remote_services' => $this->remote_services,
            'line_items' => $this->line_items
        );

        return $obj_ary;
    }

    /**
     * Update database record
     */
    public function save($form = array())
    {
        global $user;

        $updated_by = ($user) ? $user->getId() : 1;
        $updated_at_date = date('Y-m-d H:i:s');
        $src = (isset($_REQUEST['src'])) ? $_REQUEST['src'] : "LeaseContract";
        $change_info = (isset($_REQUEST['change_info'])) ? $_REQUEST['change_info'] : "Update";

        $this->copyFromArray($form);

        # Remove characters which cause problems with floats
        $this->monthly_revenue = preg_replace('/[^\d\.]/', '', $this->monthly_revenue);
        $this->sale_amount = preg_replace('/[^\d\.]/', '', $this->sale_amount);

        if ($this->id_contract)
        {
            self::AddHistory($this->id_contract, $updated_by, $updated_at_date, $change_info, $src);
            $this->DBUpdate();
        }
        else
        {
            $sth = $this->dbh->query("SELECT nextval('contract_id_seq')");
            list($this->id_contract) = $sth->fetch(PDO::FETCH_NUM);
            $this->DBInsert();
        }

        # Update the nfif with this contract id so install can be saved
        if (isset($form['nfif_id']))
        {
            $sth = $this->dbh->prepare("UPDATE nfif_contract
			SET contract_id = ?
			WHERE nfif_id = ?");
            $sth->bindValue(1, (int) $this->id_contract, PDO::PARAM_INT);
            $sth->bindValue(2, (int) $form['nfif_id'], PDO::PARAM_INT);
            $sth->execute();
        }

        $this->saveEquipment();

        $this->LogChanges();
    }

    /**
     * Save equipment detail
     */
    protected function saveEquipment()
    {
        global $user;

        if ($this->id_contract)
        {
            # Use special codes based on cycle and state
            $has_cycle = false;
            $has_dysph = false;
            $has_stand = false;

            $lease_amount = $this->monthly_revenue;
            $purchase_amount = 0;

            $sth = $this->dbh->query("SELECT state, corporate_parent FROM facilities WHERE id = {$this->id_facility}");
            list($state, $corporate_parent) = $sth->fetch(PDO::FETCH_NUM);

            # Load Property Tax array
            $tax_rates = array();
            $sth = $this->dbh->query("SELECT p.id, s.ptax FROM products p INNER JOIN service_item_to_product s ON p.code = s.item OR p.code = s.code");
            while (list($id, $tax_rate) = $sth->fetch(PDO::FETCH_NUM))
                $tax_rates[$id] = $tax_rate;

            ## Clear previous record
            $srv_item = $this->RemoveSRVItem();
            $date_added = ($srv_item) ? $srv_item->getVar('date_added') : date('Y-m-d');

            $ptax = 0;
            if (count($this->line_items) > 0)
            {
                ## Update line items
                foreach ($this->line_items as $i => $item)
                {
                    $line_num = $item->GetVar('line_num'); # Determine if this is newly added
                    $item->SetContractId($this->id_contract);
                    $item->save();
                }

                ## Reset array to accurately set tax and srv info
                $this->loadEquipment();

                ## Use line items
                foreach ($this->line_items as $i => $item)
                {
                    $prod_id = $item->GetVar('prod_id');
                    $prod_code = $item->GetVar('item_code');

                    # May use cycle specific code
                    if ($state == 'PA' && in_array($prod_id, self::$CYCLE_PROD_IDS))
                    {
                        $has_cycle = true;
                    }

                    # Check for dysphagia specific code
                    if (in_array($prod_id, self::$DYSPH_PROD_IDS))
                    {
                        $has_dysph = true;
                    }

                    # Check for omnistand specific code
                    # AJV - 8/11/2016 changing to codes since id is a serial field
                    # Plan to change others later
                    if (in_array($prod_code, self::$OSTAND_PROD_CODES))
                    {
                        $has_stand = true;
                    }

                    # May use onetime charge for warranty
                    if ($prod_id == self::$SRVWARRANTY_PROD_ID)
                    {
                        $lease_amount = 0;
                    }

                    # Add property tax if rate is found
                    $ptax += (isset($tax_rates[$prod_id])) ? $tax_rates[$prod_id] : 0;
                }
            }

            ## Set monthly revenue item
            if ($lease_amount > 0)
            {
                if ($this->id_contract_type == self::$PURCHASE_TYPE)
                    $this->InsertRevenueItem(null, self::$SRVWARRANTY_PROD_ID, $date_added, $lease_amount);
                else if ($state == 'WV')
                {
                    if ($has_dysph)
                        $this->InsertRevenueItem(null, self::$SRVDYSWV_PROD_ID, $date_added, $lease_amount);
                    else
                        $this->InsertRevenueItem(null, self::$SRV000WV_PROD_ID, $date_added, $lease_amount);
                }
                else if ($has_cycle)
                    $this->InsertRevenueItem(null, self::$SRV0PAOC_PROD_ID, $date_added, $lease_amount);
                else if ($has_dysph)
                    $this->InsertRevenueItem(null, self::$SRV00DYS_PROD_ID, $date_added, $lease_amount);
                else if ($has_stand)
                    $this->InsertRevenueItem(self::$SRV00OSS_ITEM_CODE, null, $date_added, $lease_amount);
                else
                    $this->InsertRevenueItem(null, self::$SRV00000_PROD_ID, $date_added, $lease_amount);
            }

            ## Set personal property tax
            $this->UpdateTaxItem($ptax, $state, $corporate_parent);
        }
    }

    /**
     * Create/Update Customer Order
     * @param array
     */
    public function savePurchase($order_info = null)
    {
        global $user;

        $items = array();
        $order_id = null;
        $auto_fill_purchase = false;


        # Shipping charge is separated when there is a free trial period
        $shipping_charge = 0;
        $order_comments = "";

        if ($this->id_contract)
        {
            # Look for an existing record
            $order_id = $this->HasOrder(Order::$CUSTOMER_ORDER);

            # -1 indicates Order is already processed, nothing more to do
            if ($order_id == -1)
                return;

            $order = new Order($order_id);
            $items = array();
            $added_ary = array();
            $placed_items = 0;
            ###########################################################
            # Create customer order based on the NFIF for this contract
            # Not the records in contract_line_item
            # Add items with ship option true.
            ###########################################################

            $sth = $this->dbh->prepare("SELECT
				i.item_num,
				i.quantity,
				i.uom,
				i.price,
				i.serial_num as new_used,
				p.id AS prod_id,
				p.code,
				p.name,
				p.max_quantity,
				pr.listprice,
				pr.preferredprice,
				m.id as is_device,
				m.id as model,
				e.id as asset_id,
				e.serial_num,
				e.status
			FROM nfif_detail i
			INNER JOIN nfif_contract n ON i.nfif_id = n.nfif_id AND n.contract_id = ?
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
			LEFT JOIN lease_asset_status e ON m.id = e.model_id AND e.serial_num = i.serial_num
			WHERE i.ship
			ORDER BY i.item_num");
            $sth->bindValue(1, (int) $this->id_contract, PDO::PARAM_INT);
            $sth->execute();
            while ($item = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $item['swap_asset_id'] = 0;

                if (is_null($item['price']))
                    $item['price'] = 0.0;
                else
                    $item['price'] = (float) $item['price'];

                # Set asset_id for purchased devices
                if ($item['is_device'])
                {
                    $serial_num = ($item['serial_num']) ? $item['serial_num'] : $item['new_used'];

                    # Set the appropriate Wharehouse ID
                    if ($item['new_used'] == 'NEW')
                        $item['whse_id'] = Order::$PURCHASE_WHSE;
                    else if ($item['new_used'] == 'REFURB')
                        $item['whse_id'] = Order::$REFURB_WHSE;
                    else
                        $item['whse_id'] = Order::$EQUIP_WHSE;

                    $dev = $this->GetPurchaseDevice($item['model'], $serial_num, $added_ary);
                    $item['asset_id'] = $dev['asset_id'];
                    $item['serial_number'] = $dev['serial_number'];
                    $added_ary[] = $dev['asset_id'];

                    # Track which assets have been added
                    if ($dev['asset_id'])
                    {
                        # If asset is already placed "here" just make sure it has the correct substatus
                        # It is not necessary to ship the device
                        $asset = new LeaseAsset($dev['asset_id']);
                        $tran = $asset->GetLastTransaction();
                        $placed = LeaseAssetTransaction::$PLACED;
                        $purchase = LeaseAssetTransaction::$PURCHASE;
                        $comment = "Converted to purchase";

                        # Placed at $this->id_facility
                        if ($tran->getStatus() == $placed && $tran->getFacility()->getId() == $this->id_facility)
                        {
                            # Place + Purchase
                            if ($tran->getSubStatus() != $purchase)
                            {
                                $asset->addTransaction($this->id_facility, $placed, $purchase, $user, $comment);
                                $owning_acct = Facility::GetCustIdFromId($this->id_facility);
                                FASOwnership::ActivateOwner($asset->GetId(), $owning_acct, $item['price'], date('Y-m-d H:i:s'));
                            }

                            # Dont Ship - Add Comment
                            $placed_items++;
                            $item['shipped'] = 1;
                            $order_comments .= "Item Does not ship: {$item['code']} {$item['name']}, Serial: {$item['serial_number']}, already placed at the facility\n";
                        }
                    }
                }

                $items[$item['item_num']] = $item;
            }
        }

        if (count($items) > 0)
        {
            # Generate header record
            #
            if (is_null($order_id))
            {
                include_once ('classes/CalendarBase.php');

                $cal = new CalendarBase();
                $target_date = $cal->GetPreviousBusinessDay($this->date_install);

                $default_fields = array(
                    'user_id' => $user->getId(),
                    'order_date' => time(),
                    'status_id' => Order::$QUEUED,
                    'ship_to' => 1,
                    'urgency' => 1,
                    'inst_date' => $target_date,
                    'facility_id' => $this->id_facility,
                    'type_id' => Order::$CUSTOMER_ORDER
                );
                $order_fields = $this->AddOrderInfo($order, $default_fields, $order_info);

                $order->create($order_fields);
            }

            # Link to this contract and add items
            #
            $order->change('contract_id', $this->id_contract);
            $order->save(array('items' => $items));

            # If all items are already placed mark the order shipped
            #
            if (count($items) == $placed_items)
            {
                $order->change('status_id', Order::$SHIPPED);
                $order->change('processed_by', 1);
                $order->change('processed_date', time());
                $order->change('shipped_by', 1);
                $order->change('tracking_num', '--no tracking--');
                $order->change('ship_date', time());
            }
        }
    }

    /**
     * Create/Update Install Order
     * @param array
     */
    public function saveInstall($order_info = null)
    {
        global $user;

        $items = array();
        $order_id = null;

        # Shipping charge is separated when there is a free trial period
        $separate_shipping = ($this->date_install != $this->date_billing_start);
        $shipping_charge = 0;

        if ($this->id_contract)
        {
            # Look for an existing record
            $order_id = $this->HasOrder(Order::$INSTALL_ORDER);

            # -1 indicates Order is already processed, nothing more to do
            if ($order_id == -1)
                return;

            $order = new Order($order_id);
            $items = array();
            ###########################################################
            # Create install order based on the NFIF for this contract
            # Not the records in contract_line_item
            # Add items with ship option true.
            ###########################################################
            $sth = $this->dbh->prepare("SELECT
				i.item_num, i.quantity, i.uom, i.price,
				p.id AS prod_id, p.code, p.name, p.max_quantity,
				pr.listprice, pr.preferredprice,
				m.id as is_device, m.id as model,
				e.id as asset_id, e.serial_num, e.status
			FROM nfif_detail i
			INNER JOIN nfif_contract n ON i.nfif_id = n.nfif_id
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
			LEFT JOIN lease_asset_status e ON m.id = e.model_id AND e.serial_num = i.serial_num
			WHERE i.ship AND n.contract_id = ?
			ORDER BY i.item_num");
            $sth->bindValue(1, $this->id_contract, PDO::PARAM_INT);
            $sth->execute();
            while ($item = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $item['swap_asset_id'] = 0;

                ## Want a non null value for price to override pricing
                $item['price'] = (float) $item['price'];

                ## Don't charge for program equipment
                if ($item['is_device'])
                    $item['price'] = 0.0;

                if ($item['prod_id'] == self::$ZSHIPPING_PROD_ID && $separate_shipping)
                    $shipping_charge += $item['price'];
                else
                    $items[$item['item_num']] = $item;
            }
        }

        if (count($items) > 0)
        {
            if (is_null($order_id))
            {
                include_once ('classes/CalendarBase.php');

                $cal = new CalendarBase();
                $target_date = $cal->GetPreviousBusinessDay($this->date_install);

                $default_fields = array(
                    'user_id' => $user->getId(),
                    'order_date' => time(),
                    'status_id' => Order::$QUEUED,
                    'ship_to' => 1,
                    'urgency' => 1,
                    'inst_date' => $target_date,
                    'facility_id' => $this->id_facility,
                    'type_id' => Order::$INSTALL_ORDER
                );
                $order_fields = $this->AddOrderInfo($order, $default_fields, $order_info);

                $order->create($order_fields);
            }

            $order->change('contract_id', $this->id_contract);
            $order->save(array('items' => $items));
        }

        # Generate a separate invoice with the shipping charges
        if ($separate_shipping && $shipping_charge)
            $this->GenShippingInvoice($shipping_charge);
    }

    /**
     * Create/Update Cancellation Order
     * @param array
     */
    public function saveCancellation($order_info = null)
    {
        global $user;

        $items = array();
        $order_id = null;

        if ($this->id_contract)
        {
            # Look for an existing record
            $order_id = $this->HasOrder(Order::$CANCELLATION_ORDER);

            # -1 indicates Order is already processed, nothing more to do
            if ($order_id == -1)
                return;

            $order = new Order($order_id);
            $items = array();
            $item_num = 1;
            ##########################################################
            # Create order based on records in contract_line_item
            ##########################################################
            $sth = $this->dbh->prepare("SELECT
				coalesce(c.asset_id, 0) as asset_id,
				p.id as prod_id, p.code, p.name, p.max_quantity,
				m.id as is_device, m.id as model
			FROM contract_line_item c
			LEFT JOIN service_item_to_product cp ON c.item_code = cp.item
			LEFT JOIN products p ON
				CASE WHEN cp.item IS NULL
					THEN
						c.item_code = p.code
					ELSE
						cp.code = p.code
				END
			LEFT JOIN equipment_models m ON p.code = m.model
			WHERE c.contract_id = ?
			AND m.id IS NOT NULL");
            $sth->bindValue(1, (int) $this->id_contract, PDO::PARAM_INT);
            $sth->execute();
            while ($item = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $item['quantity'] = 1;
                $item['item_num'] = $item_num;
                $item['swap_asset_id'] = 0;
                $item['price'] = '0.0';
                $item['uom'] = 'EA';
                $items[$item['item_num']] = $item;
                $item_num++;
            }
        }

        if (count($items) > 0)
        {
            if (is_null($order_id))
            {
                $cal = new CalendarBase();
                $target_date = $cal->GetPreviousBusinessDay($this->date_cancellation);

                $default_fields = array(
                    'user_id' => $user->getId(),
                    'order_date' => time(),
                    'status_id' => Order::$QUEUED,
                    'comments' => 'Contract Cancelled',
                    'ship_to' => 1,
                    'urgency' => 1,
                    'inst_date' => $target_date,
                    'facility_id' => $this->id_facility,
                    'type_id' => Order::$CANCELLATION_ORDER
                );
                $order_fields = $this->AddOrderInfo($order, $default_fields, $order_info);

                $order->create($order_fields);
            }

            $order->change('contract_id', $this->id_contract);
            $order->save(array('items' => $items));
        }
    }

    /**
     * Determine the expiration date for the warranty
     *
     * @param array
     * @return string
     */
    public function FindWarrantyExpiration($device)
    {
        $expiration_date = null;

        $warranty_option = $this->warranty;
        if (isset($device['warranty_option_id']))
            $warranty_option = $device['warranty_option_id'];

        $start_date = $this->date_install;
        if ($device['date_shipped'])
            $start_date = $device['date_shipped'];
        else if ($this->date_shipped)
            $start_date = $this->date_shipped;

        if ($start_date && $warranty_option)
        {
            $sth = $this->dbh->prepare("SELECT
				? ::Date + wo.year_interval::Interval as expiration_date
			FROM warranty_option wo
			WHERE wo.warranty_id = ?");
            $sth->bindValue(1, $start_date, PDO::PARAM_STR);
            $sth->bindValue(2, $warranty_option, PDO::PARAM_INT);
            $sth->execute();
            $expiration_date = $sth->fetchColumn();
        }

        return $expiration_date;
    }

    /**
     * Look for a "Queued", "Unprocessed" order for this contract
     * Order must match propper order type
     *
     * @param integer
     *
     * @return integer $order_id : -1 existing processed order, null no order found
     */
    public function HasOrder($order_type)
    {
        $order_id = null;

        # "Unprocessed" are order statuses that are not finalized
        $unprocessed = array(Order::$QUEUED, Order::$PROCESSED, Order::$HOLD, Order::$EDITING);
        $sql = "SELECT
			id, status_id
		FROM orders
		WHERE contract_id = ?
		AND type_id = ?
		AND status_id IN (" . implode(",", $unprocessed) . ")";
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, $this->id_contract, PDO::PARAM_INT);
        $sth->bindValue(2, $order_type, PDO::PARAM_INT);
        $sth->execute();
        if ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            # If the status is "Queued" or "Hold" it can be updated
            # Otherwise do nothing
            if ($row['status_id'] == Order::$QUEUED || $row['status_id'] == Order::$HOLD)
                $order_id = $row['id'];
            else
                $order_id = -1;
        }

        return $order_id;
    }

    /**
     * Add a log entry for the change being made
     */
    public function AddLog($field_name, $field, $new_value)
    {
        global $user;

        $changes[$field_name]['old'] = $this->{$field};
        $changes[$field_name]['new'] = $new_value;

        # Log the action
        #
        $sth_log = $this->dbh->prepare("
			INSERT INTO contract_log (contract_id,user_id,tstamp,action,comment)
			VALUES ( ?, ?, extract(epoch from current_timestamp)::integer, 'Update', ?)");
        $sth_log->bindValue(1, $this->id_contract, PDO::PARAM_INT);
        $sth_log->bindValue(2, $user->getId(), PDO::PARAM_INT);
        $sth_log->bindValue(3, serialize($changes), PDO::PARAM_STR);
        $sth_log->execute();
    }

    /**
     * Set the install order to processed
     */
    public function processInstall($form = array())
    {
        global $user;

        if (isset($form['ship_install']))
            $this->ship_install = $form['ship_install'];

        $stage_act = (isset($form['stage_act'])) ? strtolower($form['stage_act']) : '';

        # Approved
        if ($this->id_contract && $this->ship_install && $stage_act == 'approve')
        {
            $sth = $this->dbh->prepare("
			UPDATE orders
			SET status_id = ?,
			    processed_date = ?,
			    processed_by = ?
			WHERE contract_id = ?
			AND status_id = ?
			AND type_id = ?");
            $sth->bindValue(1, Order::$PROCESSED, PDO::PARAM_INT);
            $sth->bindValue(2, time(), PDO::PARAM_INT);
            $sth->bindValue(3, $user->getId(), PDO::PARAM_INT);
            $sth->bindValue(4, $this->id_contract, PDO::PARAM_INT);
            $sth->bindValue(5, Order::$QUEUED, PDO::PARAM_INT);
            $sth->bindValue(6, Order::$INSTALL_ORDER, PDO::PARAM_INT);
            $sth->execute();
        }
    }

    /**
     * Create a invoice batch for the shipping charges.
     * Only attempt to create the invoice batch once so this won't
     * interfere with normal invoicing.
     *
     * @param float $shipping_charge
     */
    public function GenShippingInvoice($shipping_charge)
    {
        global $user;

        require_once ('InvoiceCreator.php');

        # Create a batch and log entry
        #
        try
        {
            # Use the InvoiceCreator to create a shipping charge batch
            # and invoice
            #
            $invoice_date = ($this->date_billing_start) ? $this->date_billing_start : date('Y-m-d');
            $inv_creator = new InvoiceCreator();
            $shipping_batch = $inv_creator->createShippingInvoiceBatch($this, $invoice_date, $shipping_charge);

            # Add a log entry for creation of the shipping charge invoice
            #
            $comment = 'Invoice Batch (#' . $shipping_batch->getId() . ') for separate shipping charge created';
            $this->AddLogEntry($user->getId(), 'Shipping Invoiced', $comment);
        }
        catch (Exception $exc)
        {
            echo '<p class="error">Shipping invoice creation failed due to the following error(s):<br>' . $exc->getMessage() . '</p>';
            return;
        }

        # Post the batch
        #
        $error_msg = '';
        try
        {
            $num_invoices_posted = $shipping_batch->postToMAS(true);
        }
        catch (Exception $exc)
        {
            $error_msg .= $exc->getMessage();
            $num_invoices_posted = 0;
        }

        # If no invoices were posted, perform some error management
        #
        if ($num_invoices_posted < 1)
        {
            try
            {
                # Update the batch's status to Committed regardless of any error.
                #
                $shipping_batch->setPostStatus(InvoiceBatch::$STATUS_COMMITTED);
                $shipping_batch->save();

                # Add an entry into the contract log
                #
                $comment = 'Error occured while posting. Batch #' . $shipping_batch->getId();
                $this->AddLogEntry($user->getId(), 'Invoice Error', $comment);
            }
            catch (Exception $exc)
            {
                error_log("GenShippingInvoice:\n{$exc->getMessage()}");
                $error_msg .= '<br>' . $exc->getMessage();
            }

            echo '<p class="error">Shipping invoice post failed due to the following error(s):<br>' . $error_msg . '</p>';
        }
    }

    /**
     * Build the length term option list
     *
     * @param integer
     * @return string
     */
    public function GetLengthTermList($length_term_id)
    {
        $options = "";
        $sth = $this->dbh->query("SELECT id as value, term as text
		FROM contract_length_term
		WHERE active = true
		ORDER BY display_order");
        while ($option = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($length_term_id == $option['value']) ? "selected" : "";
            $options .= "<option value='{$option['value']}' $sel>{$option['text']}</option>\n";
        }

        return $options;
    }

    /**
     * Build the length term option list
     *
     * @param integer
     * @return string
     */
    static public function GetLengthTermText($length_term_id)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT term
		FROM contract_length_term
		WHERE id = ?");
        $sth->bindValue(1, (int) $length_term_id, PDO::PARAM_INT);
        $sth->execute();
        $option = $sth->fetchColumn();

        return $option;
    }

    /**
     * Build the option list
     *
     * @return string
     */
    public function GetMaintenanceList($selected = null)
    {
        $options = "";

        $dbh = DataStor::getHandle();
        $sth = $dbh->query("SELECT id as value, name || ' ' || term_interval as text, active
		FROM maintenance_agreement
		ORDER BY display_order");
        while ($option = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($option['value'] == $selected) ? "selected" : "";

            if ($option['active'] || $option['value'] == $selected)
                $options .= "<option value='{$option['value']}' $sel>{$option['text']}</option>\n";
        }

        return $options;
    }

    /**
     * Get visit_count
     * @return integer
     */
    public function GetVisitCount()
    {
        $sth = $this->dbh->prepare("SELECT visit_count FROM contract_visit_frequency WHERE id = ?");
        $sth->bindValue(1, $this->visit_frequency, PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchColumn();
    }

    /**
     * Get visit_frequency option list
     */
    public function GetVisitFrequencyList($visit_frequency_id = NULL)
    {
        if (is_null($visit_frequency_id))
            $visit_frequency_id = $this->visit_frequency;

        $visit_frequency = "";
        foreach (array('vd', 'add', 'vp') as $method)
        {
            if ($method == 'vd')
                $label = "Package Pricing";
            else if ($method == 'vp')
                $label = "Volume Pricing";
            else
                $label = "Addon Pricing";

            $visit_frequency .= "<optgroup label='$label' id='vf_og_{$method}'>";

            $sth = $this->dbh->prepare('SELECT
				id, description, visit_count, pricing_method, remote_services
			FROM contract_visit_frequency
			WHERE lower(pricing_method) = ? ORDER BY id');
            $sth->bindValue(1, strtolower($method), PDO::PARAM_STR);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $sel = ($row['id'] == $visit_frequency_id) ? "selected" : "";
                $visit_frequency .= "<option value=\"{$row['id']}\" {$sel}>{$row['description']}</option>";
            }

            $visit_frequency .= "</optgroup>";
        }

        return $visit_frequency;
    }

    /**
     * Build the clinical support text
     *
     * @param integer
     * @param string
     * @return string
     */
    public function GetVisitFrequencyText($visit_frequency_id = NULL)
    {
        if (is_null($visit_frequency_id))
            $visit_frequency_id = $this->visit_frequency;

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT display_text
		FROM contract_visit_frequency
		WHERE id = ? ");
        $sth->bindValue(1, (int) $visit_frequency_id, PDO::PARAM_INT);
        $sth->execute();
        return $sth->fetchColumn();
    }

    /**
     * Build the warranty option js array
     *
     * @return string (json)
     */
    public function GetWarrantyList($selected = null)
    {
        $options = "";

        $dbh = DataStor::getHandle();
        $sth = $dbh->query("SELECT warranty_id as value, warranty_name || ' ' || year_interval as text, active
		FROM warranty_option
		ORDER BY display_order");
        while ($option = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($option['value'] == $selected) ? "selected" : "";

            if ($option['active'] || $option['value'] == $selected)
                $options .= "<option value='{$option['value']}' $sel>{$option['text']}</option>\n";
        }

        return $options;
    }

    /**
     * Overide BaseClass delete
     */
    public function delete()
    {
        return;
    }

    /**
     * Delete any/all srv lease or warranty items
     */
    public function RemoveSRVItem()
    {
        global $user;

        $srv_item = null;

        if (is_array($this->line_items))
        {
            foreach ($this->line_items as $i => $item)
            {
                $srv_id = $item->GetVar('prod_id');
                $item_code = $item->getVar('item_code');

                ## Check item prod_id
                if ($srv_id == self::$SRV00000_PROD_ID ||
                    $srv_id == self::$SRV0PAOC_PROD_ID ||
                    $srv_id == self::$SRV000WV_PROD_ID ||
                    $srv_id == self::$SRVDYSWV_PROD_ID ||
                    $srv_id == self::$SRV00DYS_PROD_ID ||
                    $srv_id == self::$SRVWARRANTY_PROD_ID ||
                    $item_code == self::$SRV00OSS_ITEM_CODE)
                {
                    ## return a copy of this item
                    $srv_item = $item;

                    ## Log the change
                    $amount = $srv_item->getVar('amount');
                    $this->AddLogEntry($user->GetId(), 'Removed', "Removed Item: $item_code Amount: $amount");

                    $item->Delete(true);
                    unset($this->line_items[$i]);
                }
            }
        }

        return $srv_item;
    }

    /**
     * Delete any/all zptax items
     */
    public function RemovePTaxItem()
    {
        global $user;

        $tax_item = null;

        foreach ($this->line_items as $i => $item)
        {
            $tax_id = $item->GetVar('prod_id');
            ## Check item prod_id
            if ($tax_id == self::$ZPTAX_PROD_ID)
            {
                ## return a copy of this item
                $tax_item = $item;

                ## Log the change
                $amount = $tax_item->getVar('amount');
                $item_code = $tax_item->getVar('item_code');
                $this->AddLogEntry($user->GetId(), 'Removed', "Removed Item: $item_code Amount: $amount");

                $item->Delete(true);
                unset($this->line_items[$i]);
            }
        }

        return $tax_item;
    }

    /**
     * Clean up removed/unlinked line items
     */
    public function RemoveUnlinkedItems()
    {
        $sth = $this->dbh->prepare("DELETE FROM contract_line_item
		WHERE contract_id = ?
		AND asset_id IS NULL
		AND
		(
			item_code IN (SELECT item FROM service_item_to_product)
		 OR
			item_code IN (SELECT model FROM equipment_models)
		)");
        $sth->bindValue(1, $this->id_contract, PDO::PARAM_INT);
        $sth->execute();
        $this->loadEquipment();
    }

    /**
     * Display contract view only form
     */
    public function showViewForm($form)
    {
        $this->showEditForm($form);
    }

    /**
     * Display contract form
     */
    public function showEditForm($form)
    {
        include ('../contract_maintenance.php');
    }

    /**
     * Create HTML editing
     *
     * @param integer
     * @param array
     *
     * @return string
     */
    public function showForm($edit = 0, $form = array())
    {
        if (isset($form['alt']))
        {
            if ($form['alt'] == 'receive')
                return $this->ShowReceiveForm($form);
            else if ($form['alt'] == 'send_lease')
                return json_encode(array('subject' => 'Operation Lease Agreement', 'body' => self::GetEmailTemplate('send_lease')));
            else if ($form['alt'] == 'send_install')
                return json_encode(array('subject' => 'Equipment Installation Date', 'body' => self::GetEmailTemplate('send_lease')));
            else
                throw Exception("Unable to process reqest. Unknow form type: {$form['alt']}.");
        }
    }

    /**
     * Display for shipping install order without signed contract
     *
     * @param array
     */
    public function ShowReceiveForm($form = array())
    {
        $contract_received = "";
        if ($this->contract_received)
            $contract_received = $this->ParseDate($this->contract_received);

        # Set to submitted values or defaults
        $form = "
		<form name='e_form' action='{$_SERVER['PHP_SELF']}' method='POST' ENCTYPE='multipart/form-data'>
		<input type='hidden' name='object' value='LeaseContract' />
		<input type='hidden' name='act' value='save_obj' />
		<input type='hidden' name='entry' value='{$this->id_contract}' />
		<input type='hidden' name='id_contract' value='{$this->id_contract}' />
		<table class='form' width='100%' cellpadding='2' cellspacing='2' style='margin:0'>
		<tbody>
			<tr>
				<th class='form'>Received Date:</th>
				<td class='form'>
					<input type='text' id='contract_received' name='contract_received' value='{$contract_received}' size='8' />
					<img class='form_bttn' id='contract_received_btn' src='images/calendar-mini.png' alt='Calendar' title='Calendar' />
				</td>
			</tr>
			<tr id='to' valign='top'>
				<th class='form'>Contract File:</th>
				<td class='form'>
					<input type='file' id='contract_file' name='contract_file' value='' size='40' />
				</td>
			</tr>
		</tbody>
		</table>
		</form>";

        return $form;
    }

    /**
     * Display for shipping install order without signed contract
     */
    public function showShippingForm($form = array())
    {
        global $user, $calendar_format, $date_format;

        # Convert date formats
        $install_date = ($this->date_install) ? date($date_format, strtotime($this->date_install)) : "";
        $amount = number_format($this->monthly_revenue, 2);

        $sth = $this->dbh->query("SELECT name FROM contract_type_options WHERE id_contract_type = {$this->id_contract_type}");
        list($type) = $sth->fetch(PDO::FETCH_NUM);

        $sth = $this->dbh->query("SELECT
			f.facility_name, f.accounting_id, f.corporate_parent,
			u.firstname || ' '  || u.lastname AS cpm_name,
			g.username AS region
		FROM facilities f
		LEFT JOIN users u ON f.cpt_id = u.id
		LEFT JOIN v_users_primary_group upg ON u.id = upg.user_id
		LEFT JOIN users g ON upg.group_id = g.id
		WHERE f.id = {$this->id_facility}");
        list($facility_name, $cust_id, $corporate_parent, $cpm_name, $region) = $sth->fetch(PDO::FETCH_NUM);

        $sth = $this->dbh->query("SELECT id FROM orders WHERE contract_id = {$this->id_contract}");
        list($order_id) = $sth->fetch(PDO::FETCH_NUM);

        $sth = $this->dbh->query("SELECT n.signatory, n.signatory_title
		FROM nfif n
		INNER JOIN nfif_contract c ON n.nfif_id = c.nfif_id
		WHERE c.contract_id = {$this->id_contract}");
        list($signatory, $signatory_title) = $sth->fetch(PDO::FETCH_NUM);

        $item_rows = "";
        $row_class = 'on';
        $sth = $this->dbh->prepare("SELECT
			o.item_num, o.quantity,	o.uom, o.price,
			o.code, o.name,
			a.serial_num
		FROM order_item o
		LEFT JOIN lease_asset a on o.asset_id = a.id
		WHERE o.order_id = ?
		ORDER BY o.item_num");
        $sth->bindValue(1, (int) $order_id, PDO::PARAM_INT);
        $sth->execute();
        while ($item = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $serial = ($item['serial_num']) ? $item['serial_num'] : "NA";
            $price = ($item['price']) ? number_format($item['price'], 2) : '';

            $item_rows .= "
			<tr class='{$row_class}'>
				<td align='left' style='font-size:small;'>{$item['code']} {$item['name']}</td>
				<td align='left' style='font-size:small;'>{$serial}</td>
				<td style='font-size:small;'>{$item['quantity']}</td>
				<td style='font-size:small;'>{$item['uom']}</td>
				<td style='font-size:small;'>{$price}</td>
			</tr>";

            $row_class = ($row_class == 'on') ? 'off' : 'on';
        }

        echo <<<END
		<form name="contract" action="{$_SERVER['PHP_SELF']}" method="POST" onSubmit='return true;'>
		<input type="hidden" name="id_contract" value="{$this->id_contract}"/>
		<input type="hidden" name="ship_install" value="1"/>
		<table class="form" cellpadding="5" cellspacing="2">
			<tr>
				<th class='subheader' colspan='2'>Contract - Install Order</th>
			</tr>
			<tr>
				<th class='form'>Contract #:</th>
				<td class='form'>{$this->id_contract}</td>
			</tr>
			<tr>
				<th class='form'>Lease Amount:</th>
				<td class='form'>{$amount}</td>
			</tr>
			<tr>
				<th class='form'>Type:</th>
				<td class='form'>{$type}</td>
			</tr>
			<tr>
				<th class='form'>Facility:</th>
				<td class='form'>$facility_name ($cust_id)</td>
			</tr>
			<tr>
				<th class='form'>Corp. Parent:</th>
				<td class='form'>{$corporate_parent}</td>
			</tr>
			<tr>
				<th class='form'>CPM Name:</th>
				<td class='form'>{$cpm_name}</td>
			</tr>
			<tr>
				<th class='form'>Region:</th>
				<td class='form'>{$region}</td>
			</tr>
			<tr>
				<th class='form'>Signatory:</th>
				<td class='form'>{$signatory}</td>
			</tr>
			<tr>
				<th class='form'>Signatory Title:</th>
				<td class='form'>{$signatory_title}</td>
			</tr>
			<tr>
				<th class='form'>Install Date:</th>
				<td class='form'>{$install_date}</td>
			</tr>
			<tr>
				<th class='form'>Order #:</th>
				<td class='form'>{$order_id}</td>
			</tr>
			<tr>
				<td colspan='2' style='padding:0;margin:0;'>
				<table width='100%' class="list" cellpadding="5" cellspacing="1" style='padding:0; margin:0; border:0;'>
					<tr>
						<th class="subsubheader">Item</th>
						<th class="subsubheader">Serial Number</th>
						<th class="subsubheader">QTY</th>
						<th class="subsubheader">UOM</th>
						<th class="subsubheader">Price</th>
					</tr>
					{$item_rows}
				</table>
				</td>
			</tr>
		</form>
END;

    }

    /**
     * Generate options list for parts
     *
     * @param $item int
     *
     * @return $item_list tring
     */
    public function createItemList($item)
    {
        $item_list = "";
        $sth = $this->dbh->prepare("SELECT p.id, p.code, p.description
		FROM products p
		WHERE p.active = true
		AND p.code NOT IN (SELECT model FROM equipment_models)
		ORDER BY p.code");
        $sth->execute();
        while (list($id, $code, $name) = $sth->fetch(PDO::FETCH_NUM))
        {
            $name = substr($name, 0, 50);
            $sel = ($id == $item) ? "selected" : "";
            $item_list .= "<option value='{$id}' {$sel}>{$code} :: {$name}</option>";
        }
        return $item_list;
    }

    /**
     * Get required CSS tags for the form display
     *
     * @return string
     */
    public function getCSS(&$css)
    {
        $path = Config::$WEB_PATH;
        $css[] = $path . '/styles/ul_tabs.css';
        $css[] = $path . '/styles/result_list.css';
        $css[] = $path . '/styles/crm.css';
        $css[] = $path . '/styles/calendar-blue.css';
    }

    /**
     * Get required Javascript tags for the form controls
     *
     * @return string
     */
    public function getJS(&$js)
    {
        $path = Config::$WEB_PATH;
        $js[] = $path . '/js/popcal/calendar.js';
        $js[] = $path . '/js/popcal/calendar-en.js';
        $js[] = $path . '/js/popcal/calendar-setup.js';
        $js[] = $path . '/js/util/date_time.js';
        $js[] = $path . '/js/util/crm.js';
    }

    /**
     * Query for the first lease
     * @param integer $id_facility
     */
    static public function GetFirstLease($id_facility)
    {
        $first = null;

        $dbh = DataStor::GetHandle();

        $sth = $dbh->prepare("SELECT
			 id_contract
		FROM contract
		WHERE id_facility = ?
		AND id_contract_type NOT IN (10, 12)
		AND (date_cancellation IS NULL OR date_cancellation > CURRENT_DATE)
		ORDER BY date_install
		LIMIT 1");
        $sth->bindValue(1, (int) $id_facility, PDO::PARAM_INT);
        $sth->execute();
        $first = $sth->fetchColumn();

        return $first;
    }

    /**
     * Query for payment terms string
     *
     * @param boolean
     * @return string
     */
    public function GetPaymentTerms($force_query = false)
    {
        if (!$this->payment_terms || $force_query)
        {
            $sth = $this->dbh->prepare("SELECT term_disp
			FROM contract_payment_term
			WHERE id = ?");
            $sth->bindValue(1, $this->payment_term_id, PDO::PARAM_INT);
            $sth->execute();
            $this->payment_terms = $sth->fetchColumn();
        }

        return $this->payment_terms;
    }

    /**
     * Build the payment term option list
     *
     * @param integer
     * @return string
     */
    public function GetPaymentTermList($term_id)
    {
        $options = "";
        $sth = $this->dbh->query("SELECT id as value, term_disp as text, active
		FROM contract_payment_term
		ORDER BY display_order");
        while ($option = $sth->fetch(PDO::FETCH_ASSOC))
        {
            if ($option['active'] || $term_id == $option['value'])
            {
                $sel = ($term_id == $option['value']) ? "selected" : "";
                $options .= "<option value='{$option['value']}' $sel>{$option['text']}</option>\n";
            }
        }

        return $options;
    }

    /**
     * Build the option list
     *
     * @param integer
     * @param string
     * @return string
     */
    public function GetPeriodList($period_id, $option_name)
    {
        $options = "";
        $option = $this->dbh->quote($option_name);
        $sth = $this->dbh->query("SELECT
			period_id as value,
			period_length as text,
			active
		FROM contract_period
		WHERE option_name = $option
		ORDER BY display_order");
        while ($option = $sth->fetch(PDO::FETCH_ASSOC))
        {
            if ($option['active'] || $period_id == $option['value'])
            {
                $sel = ($period_id == $option['value']) ? "selected" : "";
                $options .= "<option value='{$option['value']}' $sel>{$option['text']}</option>\n";
            }
        }

        return $options;
    }

    /**
     * Lookup text value for the period
     *
     * @param integer
     * @return string
     */
    static public function GetPeriodText($period_id)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("SELECT display_text
		FROM contract_period
		WHERE period_id = ?");
        $sth->bindValue(1, (int) $period_id, PDO::PARAM_INT);
        $sth->execute();
        $text = $sth->fetchColumn();

        return $text;
    }

    /**
     * Build the payment term option list
     *
     * @param integer
     * @return string
     */
    static public function GetPaymentTermText($term_id)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT term_disp
		FROM contract_payment_term
		WHERE id = ?");
        $sth->bindValue(1, $term_id, PDO::PARAM_INT);
        $sth->execute();
        $option = $sth->fetchColumn();

        return $option;
    }

    /**
     * Build the pricing method option list
     *
     * @param string
     * @return string
     */
    public function GetPricingMethodList($selected = 'vd')
    {
        $list = "";
        $options = array(
            array('value' => 'add', 'text' => 'Addon Pricing'),
            array('value' => 'vd', 'text' => 'Package Pricing'),
            array('value' => 'vp', 'text' => 'Volume Pricing'));
        foreach ($options as $option)
        {
            $sel = ($option['value'] == $selected) ? "checked" : "";
            $list .= "<input type='radio' id='pricing_method_{$option['value']}' name='pricing_method' value='{$option['value']}' $sel />
			<label for='pricing_method_{$option['value']}'>{$option['text']}</label>\n";
        }

        return $list;
    }

    /**
     * Build the pricing method option list
     *
     * @param string
     * @return string
     */
    public function GetPricingMethodOptions($selected = null)
    {
        if (is_null($selected))
            $selected = $this->pricing_method;

        $list = "";
        $options = array(
            array('value' => 'add', 'text' => 'Addon Pricing'),
            array('value' => 'vd', 'text' => 'Package Pricing'),
            array('value' => 'vp', 'text' => 'Volume Pricing'));
        foreach ($options as $option)
        {
            $sel = ($option['value'] == $selected) ? " selected" : "";
            $list .= "<option value='{$option['value']}'{$sel}>{$option['text']}</option>\n";
        }

        return $list;
    }

    /**
     * Find the total one-time pruchase amount
     */
    public function GetPurchaseAmount()
    {
        $val = 0;

        // Skip these items
        $lease_rev_items = array(
            self::$SRV00000_PROD_ID, self::$SRV000WV_PROD_ID, self::$SRVDYSWV_PROD_ID,
            self::$SRV0PAOC_PROD_ID, self::$SRV00DYS_PROD_ID, self::$SRV00OSS_PROD_ID,
            self::$ZPTAX_PROD_ID, self::$ZSHIPPING_PROD_ID, self::$SRVWARRANTY_PROD_ID
        );

        foreach ($this->line_items as $i => $item)
        {
            if (!in_array($item->GetVar('prod_id'), $lease_rev_items))
                $val += $item->GetVar('amount');
        }

        return $val;
    }

    /**
     * Return form buttons for saving the form
     */
    public function getSaveButtons()
    {
        $save = " <input type='button' class='submit' name='save' value='Save' onClick='document.contract.submit();'/>";

        return $save;
    }

    /**
     * create option list
     *
     * @param string $default
     * @return string
     */
    public function GetStateList($default = null)
    {
        return Forms::createStateList($default);
    }

    /**
     * Find a valid device
     * Use Purchasing Pool when NEW
     * Use Lease Pool when USED
     *
     * @param integer
     * @param string
     * @param array
     */
    public function GetPurchaseDevice($model, $serial_num, $added_ary)
    {
        require_once ('TConfig.php');
        # Find location to store the file
        $tconf = new TConfig();
        $fill = (bool) $tconf->get('auto_fill_purchase');

        $ret = array('asset_id' => 0, 'serial_number' => '');

        if ($model && $serial_num)
        {
            # Find an available unit
            $limit_asset = "";
            if (count($added_ary) > 0)
                $limit_asset = "AND a.id NOT IN (" . implode($added_ary) . ")";

            if ($fill && $serial_num == "NEW")
            {
                $sql = "SELECT
					a.id as asset_id,
					a.serial_num as serial_number
				FROM lease_asset_status a
				WHERE a.status = 'FGI'
				AND upper(a.owning_acct) = ?
				AND a.model_id = ?
				$limit_asset
				AND a.id NOT IN (
					-- Exclude devices already in an order
					SELECT i.asset_id
					FROM order_item i
					INNER JOIN orders o ON i.order_id = o.id
					WHERE o.status_id IN (1,2,5,99) -- (QUEUED, PROCESSED, HOLD, EDITING)
				)
				LIMIT 1";
                $sth = $this->dbh->prepare($sql);
                $sth->bindValue(1, Config::$PURCHASE_ACCT, PDO::PARAM_STR);
                $sth->bindValue(2, (int) $model, PDO::PARAM_INT);
                $sth->execute();
                if ($sth->rowCount())
                    $ret = $sth->fetch(PDO::FETCH_ASSOC);
            }
            else if ($fill && $serial_num == "REFURB")
            {
                $sql = "SELECT
					a.id as asset_id,
					a.serial_num as serial_number
				FROM lease_asset_status a
				WHERE a.status = 'FGI'
				AND upper(a.owning_acct) = ?
				AND a.model_id = ?
				$limit_asset
				AND a.id IN (
					-- Include devices which have been placed before
					SELECT t.lease_asset_id
					FROM lease_asset_transaction t
					WHERE t.status = 'Placed'
				)
				AND a.id NOT IN (
					-- Exclude devices already in an order
					SELECT i.asset_id
					FROM order_item i
					INNER JOIN orders o ON i.order_id = o.id
					WHERE o.status_id IN (1,2,5,99) -- (QUEUED, PROCESSED, HOLD, EDITING)
				)
				LIMIT 1";
                $sth = $this->dbh->prepare($sql);
                $sth->bindValue(1, Config::$PURCHASE_ACCT, PDO::PARAM_STR);
                $sth->bindValue(2, (int) $model, PDO::PARAM_INT);
                $sth->execute();
                if ($sth->rowCount())
                    $ret = $sth->fetch(PDO::FETCH_ASSOC);
            }
            else if ($serial_num)
            {
                # Valid serial number: Simply find the asset record
                $sql = "SELECT
					a.id as asset_id,
					a.serial_num as serial_number
				FROM lease_asset_status a
				WHERE a.model_id = ?
				AND a.serial_num = ?";
                $sth = $this->dbh->prepare($sql);
                $sth->bindValue(1, (int) $model, PDO::PARAM_INT);
                $sth->bindValue(2, $serial_num, PDO::PARAM_STR);
                $sth->execute();
                if ($sth->rowCount())
                    $ret = $sth->fetch(PDO::FETCH_ASSOC);
            }
        }

        return $ret;
    }

    /**
     * Build option list for termination field
     *
     * @param srting
     * @return string
     */
    public function GetTerminationList($selected = NULL)
    {
        return $this->GetPeriodList($selected, self::$option_termination_notice);
    }

    /**
     * Query for the type name
     *
     * @param integer
     * @return string
     */
    public function GetTypeText($type_id = NULL)
    {
        if (is_null($type_id))
            $type_id = $this->id_contract_type;

        $sth = $this->dbh->prepare("SELECT
			 t.name
		FROM contract_type_options t
		WHERE id_contract_type = ?");
        $sth->bindValue(1, $type_id, PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchColumn();
    }

    /**
     * Insert/Update/ SRV line item
     *
     * @param string
     * @param integer
     * @param string
     * @param float
     */
    public function InsertRevenueItem($item_code, $srv_id, $date_added, $amount)
    {
        global $user;

        # Add monthly revenue item
        if ($srv_id)
        {
            $line_item = new ContractLineItem();
            $line_item->SetVar('date_added', $date_added);
            $line_item->SetVar('contract_id', $this->id_contract);
            $line_item->SetVar('prod_id', $srv_id);
            $line_item->SetVar('amount', $amount);
            $line_item->Save();

            $this->line_items[] = $line_item;
            $this->AddLogEntry($user->GetId(), 'Add', "Monthly revenue: {$amount}");
        }

        # Add monthly revenue item
        if ($item_code)
        {
            $line_item = new ContractLineItem();
            $line_item->SetVar('date_added', $date_added);
            $line_item->SetVar('contract_id', $this->id_contract);
            $line_item->SetVar('item_code', $item_code);
            $line_item->SetVar('amount', $amount);
            $line_item->Save();

            $this->line_items[] = $line_item;
            $this->AddLogEntry($user->GetId(), 'Add', "Monthly revenue: {$amount}");
        }
    }

    /**
     * Query for the first lease
     */
    public function IsFirstLease()
    {
        $first = false;

        if (self::GetFirstLease($this->id_facility) == $this->id_contract)
            $first = true;

        return $first;
    }

    /**
     * Insert/Update/Remove ptax line item
     *
     * @param float
     * @param string
     * @param string
     */
    public function UpdateTaxItem($ptax, $state, $corporate_parent)
    {
        global $user;

        ## Clear previous record
        $this->RemovePTaxItem();

        $state_exempt = in_array($state, self::$EXEMPT_STATES);
        $cp_exempt = in_array($corporate_parent, self::$EXEMPT_CPS);

        # Proper tax exemptions
        if ($state_exempt || $cp_exempt || $this->tax_exempt > 0 || $this->id_contract_type == self::$PURCHASE_TYPE)
            $ptax = 0;

        # Add Property Tax
        if ($ptax)
        {
            # Insert new ptax item
            $line_item = new ContractLineItem();
            $line_item->SetVar('date_added', date('Y-m-d'));
            $line_item->SetVar('contract_id', $this->id_contract);
            $line_item->SetVar('prod_id', self::$ZPTAX_PROD_ID);
            $line_item->SetVar('amount', $ptax);
            $line_item->Save();

            $this->line_items[] = $line_item;
            $this->AddLogEntry($user->GetId(), 'Add', "Property Tax set to $ptax");
        }
    }
}
?>