<?php
/**
 * @package Freedom
 * @author Aron Vargas
 */

/**
 * Class defines Loaner Contract object
 */
class LoanerContract extends LeaseContract {
    protected $db_table = 'contract';	# string
    protected $p_key = 'id_contract';	# string

    public $id_contract_type = 12;	# int
    public $monthly_revenue = 0;		# float
    public $sale_amount = 0;			# float
    public $warranty = 0;			# int
    public $visit_frequency = 0;		# int
    public $loaner_agreement;		# int

    static public $DEFAULT_RATE = 10;	# int

    /**
     * Create an instance of a LoanerContract
     *
     * @param integer
     * @return object
     */
    public function __construct($contract_id = 0)
    {
        $this->dbh = DataStor::getHandle();

        if ($contract_id)
            $this->id_contract = $contract_id;

        $this->load();
    }

    /**
     * Set values from corrisponding attributes into the sql statement
     *
     * @param object
     */
    public function bindValues(&$sth)
    {
        $sth->bindValue(1, (int) $this->id_facility, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $this->id_contract_type, PDO::PARAM_INT);
        $sth->bindValue(3, $this->monthly_revenue, ($this->monthly_revenue) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(4, $this->comments, PDO::PARAM_STR);
        $sth->bindValue(5, $this->date_shipped, ($this->date_shipped) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(6, $this->date_lease, ($this->date_lease) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(7, (int) $this->da_received, PDO::PARAM_INT);
        $sth->bindValue(8, $this->date_da, ($this->date_da) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(9, (int) $this->contract_received, PDO::PARAM_INT);
        $sth->bindValue(10, $this->date_received, ($this->date_received) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(11, (int) $this->boa, PDO::PARAM_INT);
        $sth->bindValue(12, $this->date_boa, ($this->date_boa) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(13, $this->date_install, ($this->date_install) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(14, $this->date_billing_start, ($this->date_billing_start) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(15, $this->date_cancellation, ($this->date_cancellation) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(16, $this->date_expiration, ($this->date_expiration) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(17, $this->contract_version, PDO::PARAM_STR);
        $sth->bindValue(18, $this->sale_amount, ($this->sale_amount) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(19, $this->date_billed_through, ($this->date_billed_through) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(20, (int) $this->visit_frequency, PDO::PARAM_INT);
        $sth->bindValue(21, (int) $this->payment_term_id, ($this->payment_term_id) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $sth->bindValue(22, (int) $this->length_term_id, ($this->length_term_id) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $sth->bindValue(23, $this->termination, ($this->termination) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $sth->bindValue(24, $this->id_contract, PDO::PARAM_INT);
    }

    /**
     * Create/Update Cancellation Order
     */
    public function saveCancellation()
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
				1 as quantity, c.asset_id, a.serial_num as serial_number,
				p.id as prod_id, p.code, p.name, p.max_quantity,
				m.id as is_device, m.id as model
			FROM contract_line_item c
			LEFT JOIN lease_asset a ON c.asset_id = a.id
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
			AND c.asset_id IS NOT NULL");
            $sth->bindValue(1, (int) $this->id_contract, PDO::PARAM_INT);
            $sth->execute();
            while ($item = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $q = $item['quantity'];
                # One item per line
                while ($q > 0)
                {
                    $item['item_num'] = $item_num;
                    $item['quantity'] = 1;
                    $item['swap_asset_id'] = 0;
                    $item['price'] = '0.0';
                    $item['uom'] = 'EA';
                    $items[$item['item_num']] = $item;
                    $item_num++;
                    $q--;
                }
            }
        }

        if (count($items) > 0)
        {
            if (is_null($order_id))
            {
                $order->create(array(
                    'user_id' => $user->getId(),
                    'order_date' => time(),
                    'status_id' => Order::$QUEUED,
                    'comments' => 'Loaner Cancelled',
                    'ship_to' => 1,
                    'urgency' => 1,
                    'inst_date' => $this->date_cancellation,
                    'facility_id' => $this->id_facility,
                    'type_id' => Order::$CANCELLATION_ORDER)
                );
            }

            $order->change('contract_id', $this->id_contract);
            $order->save(array('items' => $items));
        }
    }

    /**
     * Copy array values into attributes
     *
     * @param $form array
     */
    public function copyFromArray($form = null)
    {
        LeaseContract::copyFromArray($form);
    }

    /**
     * Execute an database insert statement
     */
    public function db_insert()
    {
        $sth = $this->dbh->query("SELECT nextval('contract_id_seq')");
        list($this->id_contract) = $sth->fetch(PDO::FETCH_NUM);

        $sth = $this->dbh->prepare("INSERT INTO contract (
			id_facility,				id_contract_type,
			monthly_revenue,			comments,
			date_shipped,				date_lease,
			da_received,				date_da,
			contract_received,			date_received,
			boa,						date_boa,
			date_install,				date_billing_start,
			date_cancellation,			date_expiration,
			contract_version,			sale_amount,
			date_billed_through,		nr_service,	-- Default to 3 on insert only
			visit_frequency,			payment_term_id,
			length_term_id,				termination,
			id_contract)
		 VALUES (?,?, ?,?, ?,?, ?,?, ?,?, ?,?, ?,?, ?,?, ?,?, ?,3, ?,?, ?,?, ?)");
        $this->bindValues($sth);
        $sth->execute();
    }

    /**
     * Execute an database update statement
     */
    public function db_update()
    {
        global $user;

        self::AddHistory($form['contract_id'], $user->getId(), date('Y-m-d H:i:s'), "LoanerContract", "Update");

        $sth = $this->dbh->prepare("UPDATE contract
		SET
			id_facility = ?,				id_contract_type = ?,
			monthly_revenue = ?,			comments = ?,
			date_shipped = ?,				date_lease = ?,
			da_received = ?,				date_da = ?,
			contract_received = ?,			date_received = ?,
			boa = ?,						date_boa = ?,
			date_install = ?,				date_billing_start = ?,
			date_cancellation = ?,			date_expiration = ?,
			contract_version = ?,			sale_amount = ?,
			date_billed_through = ?,		visit_frequency = ?,
			payment_term_id = ?,			length_term_id = ?,
			termination = ?
		WHERE id_contract = ?");
        $this->bindValues($sth);
        $sth->execute();
    }

    /**
     * Determine the amount to charge for the loaner for the time period and
     * number of items in the contract
     *
     * @param integer $contract_id
     * @param object $start_dt
     * @param object $end_dt
     *
     * @return array
     */
    static public function GetSaleAmount($contract_id, $start_dt, $end_dt)
    {
        $dbh = DataStor::getHandle();

        # Daily rate from the loaner agreement
        #
        $num_devices = 0;
        $sth = $dbh->prepare("SELECT
			l.daily_rate, count(e.id_contract)
		FROM loaner_agreement l
		LEFT JOIN contract_equipment_mas e ON l.contract_id = e.id_contract
		WHERE l.contract_id = ?
		GROUP BY l.daily_rate");
        $sth->bindValue(1, $contract_id, PDO::PARAM_INT);
        $sth->execute();
        $rate = $sth->fetchColumn();

        # Take the rate * num days for the sales amount
        $diff = $end_dt->diff($start_dt);
        $num_days = $diff->days + 1;
        $sales_amt = round($rate * $num_days, 4);

        return $sales_amt;
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
     * Update database record
     */
    public function save($form = array())
    {
        if ($this->id_contract)
            $this->db_update();
        else
            $this->db_insert();

        $this->saveEquipment();

        $this->LogChanges();
    }

    /**
     * Save equipment detail
     */
    protected function saveEquipment()
    {
        if ($this->line_items && count($this->line_items) > 0)
        {
            foreach ($this->line_items as $i => $item)
            {
                $item->SetContractId($this->id_contract);
                $item->ConvertFromSRV();
                $item->save();
            }
        }
    }

    /**
     * Create/Update Install Order
     * [Not done here]
     */
    public function saveOrder()
    {
        return;
    }

    /**
     * Cancel the loaner by setting dates
     *
     * @param string
     * @param integer
     */
    public function SetCancelation($cancellation_date, $from_pickup = 0)
    {
        if ($this->date_cancellation)
        {
            # Indicate this is set from shipping data OR
            # If the date is already set, give a little more information
            if ($from_pickup)
                $note = "Setting the cancellation date to the returning shipment with pickup date ({$cancellation_date}).";
            else
                $note = "Unexpected Cancellation Date of ({$this->date_cancellation}) found.";

            $this->AddNote(1, $note);
            $this->AddLogEntry(1, "Update", "Cancellation Date changed from ({$this->date_cancellation}) to ({$cancellation_date})");
        }
        else
        {
            if ($from_pickup)
            {
                $note = "Setting the cancellation date to the returning shipment with pickup date ({$cancellation_date}).";
                $this->AddNote(1, $note);
            }

            # Log the system change
            $this->AddLogEntry(1, "Update", "Cancellation Date set to {$cancellation_date}");
        }

        # Update contract
        $this->change('date_cancellation', $cancellation_date);

        # Avoid billing from 0 to cancellation date
        # This will happens when date_billed_through is empty
        if (!$this->date_billed_through)
        {
            $minus_one = strtotime("-1 Day", strtotime($cancellation_date));
            $date_billed_through = date('Y-m-d', $minus_one);
            $this->change('date_billed_through', $date_billed_through);
            $this->AddLogEntry(1, "Update", "Billed Through Date changed from (--empty--) to ($date_billed_through)");
        }
    }

    /**
     * Remove an element from equip_purchase array
     *
     * @param integer
     */
    public function UnsetEquipElement($i)
    {
        if (isset ($this->equip_purchase[$i]))
            unset($this->equip_purchase[$i]);
    }
}
?>