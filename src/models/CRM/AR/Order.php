<?php
require_once ('include/textfuncs.inc');

/**
 * @package Freedom
 * @author Aron Vargas
 */

class Order extends BaseClass {

    protected $db_table = 'orders';		# string

    # DB fields
    protected $id;					# int
    protected $user_id = 0;			# int
    protected $cpt = '';			# string
    protected $role_id = 0;			# int
    protected $order_date = 0;		# int
    protected $status_id = 1;		# int
    protected $status_name = 'Queued';	# string
    protected $comments;			# text
    protected $tracking_num;		# string (64)
    protected $ship_method;			# string (32)
    protected $ship_to;				# int
    protected $urgency;				# int
    protected $inst_date;			# int
    protected $ship_date = 0;		# int
    protected $facility_id;			# int
    protected $facility_name = '';	# string
    protected $cpm = '';			# string
    protected $corp_parent = '';	# string
    protected $cust_id = '';		# string
    protected $address;				# string (64)
    protected $address2;			# string (64)
    protected $address3;			# string (64)
    protected $address4;			# string (64)
    protected $address5;			# string (64)
    protected $city;				# string (64)
    protected $state;				# string (32)
    protected $zip;					# string (32)
    protected $country;				# string (32)
    protected $sname;				# string (64)
    protected $ship_attn;				# string (64)
    protected $email;				# string (64)
    protected $phone;				# string (32)
    protected $ordered_by;			# string (64)
    protected $fax;					# string (32)
    protected $ret_tracking_num;	# string (64)
    protected $type_id = 1;			# int
    protected $type_text = 'Supply Order'; # string
    public $in_asset = false;		# bool
    public $out_asset = false;		# bool
    public $in_return = false;	 	# bool
    public $is_purchase = false;	# bool
    public $is_loan = false;		# bool
    protected $contract_id = 0;		# int
    protected $internal_comments = ''; # string
    protected $shipped_by = 0;		# int
    protected $shipper = '';		# int
    protected $shipped;				# int
    protected $parent_order = 0;	# int
    protected $back_order = NULL;
    protected $po_number = null;	# string (32)
    protected $shipping_cost = null; # float
    protected $web_shipping = null; # float
    protected $id_web_order = null; # float
//	protected $ship_service = null;	# int
    protected $service_level = 1;	# int
    protected $service_level_text = 'Standard Delivery';	# int
    protected $source = null;		# string
    protected $third_party = 0;		# int
    protected $code = null;			#string
    protected $updated_at_tstamp = null;	# timestamp
    protected $updated_by = null;		# int
    protected $lastmod = '';		# string
    protected $processed_date = 0;		# int
    protected $processed_by = null;		# int
    protected $tax_amount = 0;		# float
    protected $bill_to_acct;		# string
    protected $shipping_company_id;	# integer

    # Additional attributes
    protected $send_rab = false;	# bool
    protected $send_ect = false;	# bool
    protected $send_box = false;	# bool
    private $items = array();		# array
    private $bo_items = array();	# array
    protected $issue_id = 0;		# int
    public $order_group = null;		# array
    private $temporariesCreated = array(); # array
    protected $cust_entity_type;	# int
    protected $free_shipping = false;	# bool

    # FREIGHT SPECIAL SHIPPING INSTRUCTIONS
    public $has_dock = false;	# bool
    public $requires_liftgate = false;	# bool
    public $requires_inside_delivery = false;	# bool

    # Accounting
    private $SO;						# SalesOrder object
    protected $mas_sales_order = 0;			# int
    protected $mas_address_key = null;		# int
    protected $mas_shipment = false; 		# bool
    protected $base_price_index = 0;		# int
    protected $cust_price_group_key = null;	# int
    protected $supplier_name;

    static public $LOCAL_ID = '001';
    static public $REMOTE_ID = '100';
    static public $ININC_ID = 'INI';
    static public $ININC_NAME = 'Innovative Neurotronics Inc.';
    static public $PURCHASE_WHSE = 'RENO';
    static public $ININC_WHSE = 'ZINC';
    static public $REFURB_WHSE = 'REFURB';
    static public $EQUIP_WHSE = 'EQUIP';

    # Static vars
    static public $SHIP_TO_FACILITY = 1;
    static public $SHIP_TO_CPM = 2;
    static public $SHIP_TO_OTHER = 3;
    static public $SHIPPING_ITEM_CODE = 'Z-SHIPPING';
    # Common shipping items
    static public $RAB_ITEM = 1424;
    static public $RAB_BOX_ITEM = 1425;
    static public $ECT_ITEM = 1426;

    static public $LEASING_GROUP = 4;

    static public $SUPPLY_ORDER = 1;
    static public $PARTS_ORDER = 2;
    static public $SWAP_ORDER = 3;
    static public $INSTALL_ORDER = 4;
    static public $CANCELLATION_ORDER = 5;
    static public $PRO_ROLLOUT_ORDER = 6;
    static public $PM_SWAP_ORDER = 7;
    static public $CUSTOMER_ORDER = 8;
    static public $RMA_ORDER = 9;
    static public $RETURN_ORDER = 10;
    static public $DSSI_ORDER = 11;
    static public $WEB_ORDER = 12;
    static public $DME_ORDER = 13;
    static public $ACCESSORY_SWAP_ORDER = 14;
    static public $VENDOR_RMA_ORDER = 15;

    # These types are charged shipping costs
    static public $CHARGE_SHIPPING = array(8, 11, 12);

    static public $QUEUED = 1;
    static public $PROCESSED = 2;
    static public $SHIPPED = 3;
    static public $EXCEPTION = 4;
    static public $HOLD = 5;
    static public $CANCELED = 6;
    static public $DELETED = 7;
    static public $WRITEOFF = 8;
    static public $EDITING = 99;

    static public $NO_SHIPMENT = 0;
    static public $UPS = 1;
    static public $FEDEX = 2;
    static public $MDX = 3;
    static public $DHL = 4;
    static public $DS = 5;

    # Service levels
    static public $STANDARD = 1;
    static public $WHITE_GLOVE = 2;

    public $update_since_processed = null;

    public $session_user;  # User the user that is logged in

    /**
     * Creates a new Order object.
     *
     * @param $order_id int
     */
    public function __construct($order_id = null)
    {
        $this->dbh = DataStor::getHandle();
        $this->id = $order_id;
        $this->supplier_name = Config::$COMPANY_NAME;
        //$this->shipping_company_id = self::$UPS;
        $this->load();
        $this->set_update_since_processed();

        if (isset($_COOKIE['session_id']))
        {
            if (class_exists('SessionHandler'))
            {
                $sh = new SessionHandler();
                $this->session_user = $sh->getUser($_COOKIE['session_id']);
            }
        }

        # If this class is being instantiated
        # from a command line application, etc.,
        # there is no existing session.
        if (is_null($this->session_user))
            $this->session_user = new User(1);

    }#constructor

    /**
     * Copy this
     */
    public function __clone()
    {
        $this->id = null;
        $this->status_id = 1;
        $this->tracking_num = null;
        $this->ret_tracking_num = null;
        $this->facility_id = null;
        $this->facility_name = null;
        $this->SO = null;
        $this->mas_sales_order = 0;
        $this->mas_shipment = false;
        $this->mas_address_key = null;
        $this->bo_items = array();
        $this->contract_id = 0;
        $this->parent_order = 0;
        $this->back_order = NULL;
        $this->id_web_order = null;
        $this->issue_id = null;
    }

    /**
     * Populates this Order object from the matching record in the database.
     */
    public function load()
    {
        if ($this->id)
        {
            # Load additional attributes
            $sth = $this->dbh->prepare("
			SELECT
				o.*,
				u.firstname || ' ' || u.lastname AS cpt,
				g.role_id,
				s.name as status_name,
				t.description as type_text,
				t.in_asset,
				t.out_asset,
				t.in_return,
				t.is_purchase,
				sl.description as service_level_text,
				ce.cust_id as cust_id,
				ce.name AS facility_name,
				ce.corporate_parent as corp_parent,
				ce.cust_price_group_key,
				ce.base_price_index,
				ce.entity_type as cust_entity_type,
				lr.loaner_id AS is_loan,
				uf.firstname || ' ' || uf.lastname AS cpm,
				cf.internal_comments,
				cf.issue_id,
				cf.send_rab,
				cf.send_ect,
				cf.send_box,
				o.shipped_by,
                		ow.shipping_charge as web_shipping,
                		ow.id_web_order,
				substr(sb.firstname, 1, 1) || sb.lastname AS shipper,
				substr(lm.firstname, 1, 1) || lm.lastname AS lastmod,
				o.po_number as po_number,
				f.requires_liftgate,
				f.requires_inside_delivery,
				f.has_dock
			FROM (orders o
			INNER JOIN order_status s ON o.status_id = s.id
			INNER JOIN order_type t ON o.type_id = t.type_id
			INNER JOIN order_service_level sl ON o.service_level = sl.id)
			LEFT JOIN facilities f ON o.facility_id = f.id
			LEFT JOIN users u ON o.user_id = u.id
			LEFT JOIN v_users_primary_group g ON u.id = g.user_id
			LEFT JOIN v_customer_entity ce ON o.facility_id = ce.id
			LEFT JOIN contract con ON o.contract_id = con.id_contract
			LEFT JOIN loaner_agreement lr ON o.contract_id = lr.contract_id AND lr.active
			LEFT JOIN users uf ON ce.cpt_id = uf.id
			LEFT JOIN users sb ON o.shipped_by = sb.id
			LEFT JOIN users lm ON o.updated_by = lm.id
			LEFT JOIN complaint_form_equipment cf ON (o.id = cf.order_id OR o.id = cf.return_id)
            		LEFT JOIN order_web ow ON ow.id__order = o.id
			WHERE o.id = ?");
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
                $this->copyFromArray($row);

            # Get orders in the same complaint form
            $this->loadOrderGroup();

            # Get items for this order
            $this->loadItems();

            # Get back order (if any)
            # Assume only one back order per group
            if (is_array($this->order_group))
            {
                $sth = $this->dbh->query("SELECT DISTINCT id FROM orders WHERE parent_order IN (" . implode(',', $this->order_group) . ")");
                if ($sth->rowCount())
                    list($this->back_order) = $sth->fetch(PDO::FETCH_NUM);
            }

            # Supplier name was set upon obj construction.
            # Altering it here if necessary.
            if ($this->cust_id && preg_match('/^\d*$/', $this->cust_id))
                $this->supplier_name = self::$ININC_NAME;
        }

        $this->SetOrigin();
    }

    /**
     * Populate Order Item array
     */
    public function loadItems()
    {
        if (is_array($this->order_group))
        {
            # Start fresh
            $this->items = array();
            $item_numbers = array();
            $neg_asset = -1;

            $sth = $this->dbh->prepare("SELECT
				o.item_num,
				o.order_id,
				o.prod_id,
				o.asset_id,
				o.quantity,
				o.swap_asset_id,
				o.shipped,
				o.uom,
				o.price,
				o.whse_id,
				o.upsell,
				o.code,
				o.name,
				o.item_lot,
				o.description,
				p.prod_price_group_key, p.price_uom,
				oo.contract_id,
				m.model as is_device,
				m.track_inventory,
				pd.lot_required,
				pd.featured,
				pd.onhold
			FROM order_item o
			INNER JOIN products p ON o.prod_id = p.id
			INNER JOIN orders oo ON o.order_id = oo.id
			LEFT JOIN equipment_models m ON p.code = m.model AND m.active = true
			LEFT JOIN product_detail pd ON o.prod_id = pd.prod_id
			WHERE o.order_id IN (" . implode(',', $this->order_group) . ")
			ORDER BY o.item_num");
            $sth->execute();
            while ($item = $sth->fetch(PDO::FETCH_ASSOC))
            {
                // Find a unique item number
                $item_num = $item['item_num'];
                while (in_array($item_num, $item_numbers))
                    $item_num++;
                $item['item_num'] = $item_num;

                // Split the item if is_device
                if ($item['quantity'] > 1 && $item['is_device'])
                {
                    $unit_price = $this->GetUnitPrice($item);

                    for ($q = $item['quantity']; $q > 0; $q--)
                    {
                        $item['item_num'] = $item_num;
                        $item['quantity'] = 1;
                        $item['asset_id'] = $neg_asset;
                        $item['price'] = $unit_price;
                        $this->items[] = $item;
                        $item_numbers[] = $item_num;
                        $item_num++;
                        $neg_asset--;
                    }
                }
                else
                {
                    if ($item['asset_id'] < 1)
                    {
                        $item['asset_id'] = $neg_asset;
                        $neg_asset--;
                    }

                    $this->items[] = $item;
                    $item_numbers[] = $item_num;
                }
            }
        }
    }

    /**
     * Has a change been made to this order since the process date?
     * (CR 2088) If a change has been made, alert on page load
     */
    private function set_update_since_processed()
    {
        if ($this->id > 1)
        {
            $dcl_id = null;
            $sth = $this->dbh->prepare("SELECT dcl.id
			from data_change_log dcl
			where dcl.object_desc = 'Order_" . $this->id . "'
			AND  date_trunc('second', dcl.tstamp) > (select to_timestamp(processed_date) from orders where id={$this->id} AND processed_date >0)
			order by dcl.id DESC
			LIMIT 1");
            $sth->execute();
            list($dcl_id) = $sth->fetch(PDO::FETCH_NUM);

            $this->update_since_processed = $dcl_id;
        }
    }

    /**
     * Load other orders that are part of the same complaint_form.
     * RMA complaints are not part of a group and are processed seperately
     */
    private function loadOrderGroup()
    {
        $this->order_group = null;

        if ($this->issue_id > 0 && $this->type_id <> self::$RETURN_ORDER && $this->type_id <> self::$RMA_ORDER)
        {
            $sth = $this->dbh->prepare("SELECT DISTINCT cfe.order_id
			FROM complaint_form_equipment cfe
			INNER JOIN orders o ON o.id = cfe.order_id
			WHERE cfe.order_id > 0
			AND cfe.complaint <> 'RMA'
			AND NOT ( cfe.complaint = 'Accessory' AND
					  ( o.type_id IN ( " . self::$RMA_ORDER . ", " . self::$RETURN_ORDER . " ) ) )
			AND cfe.issue_id = ?
			ORDER BY cfe.order_id");
            $sth->bindValue(1, (int) $this->issue_id, PDO::PARAM_INT);
            $sth->execute();
            while (list($order_id) = $sth->fetch(PDO::FETCH_NUM))
            {
                $this->order_group[] = $order_id;
            }
        }

        if (is_null($this->order_group))
            $this->order_group[] = $this->id;
        else
            $this->id = min($this->order_group);
    }

    /**
     * Determine Company ID
     */
    public function CompanyId()
    {
        $id = self::$REMOTE_ID;

        if ($this->cust_id && preg_match('/^\d*$/', $this->cust_id))
        {
            $id = self::$ININC_ID;
        }

        return $id;
    }

    /**
     * Determine Company name
     */
    public function CompanyName()
    {
        $company_name = Config::$COMPANY_NAME;

        if ($this->cust_id && preg_match('/^\d*$/', $this->cust_id))
        {
            $company_name = self::$ININC_NAME;
        }

        return $company_name;
    }

    /**
     * Set class property matching the array key
     *
     * @param array $new
     */
    public function copyFromArray($new = array())
    {
        foreach ($new as $key => $value)
        {
            if (@property_exists($this, $key))
            {
                # Cant trim an array
                if (is_array($value))
                    $this->{$key} = $value;
                else
                    $this->{$key} = trim($value);
            }
        }

        # determine if this is a string date or unix date
        if (isset($new['ship_date']))
        {
            if (preg_match('/[\-\/]/', $new['ship_date']))
                $this->ship_date = strtotime($new['ship_date']);
            else
                $this->ship_date = $new['ship_date'];
        }
        if (isset($new['tracking_num']))
            $this->tracking_num = strtoupper($new['tracking_num']);
        if (isset($new['ret_tracking_num']))
            $this->ret_tracking_num = strtoupper($new['ret_tracking_num']);

        # Set the install Date
        $method = $this->ship_method;
        if (!$method || $method == 'Freight')
            $method = 'Ground';
        if (isset($new['inst_date']))
        {
            # (-1) Set Date from Shipping Carrier Estimate
            if ($new['inst_date'] == -1)
            {
                $this->inst_date = $this->GetTargetDate($this->city, $this->state, $this->zip, $method);
            }
            else
            {
                # determine if this is a string date or unix date
                if (preg_match('/[\-\/]/', $new['inst_date']))
                    $this->inst_date = strtotime($new['inst_date']);
                else
                    $this->inst_date = $new['inst_date'];
            }
        }
        else
        {
            if ($this->urgency == 1)
                $inst_date = $this->GetTargetDate($this->city, $this->state, $this->zip, $method);
        }
    }

    /**
     * Perform sql query
     *
     * @param $save_fields
     */
    private function db_save($save_fields)
    {
        # Build SQL text
        if ($this->id)
        {
            $sql = "UPDATE orders SET " .
                implode(" = ?, ", array_keys($save_fields));
            $sql .= " = ? ";

            # Update all orders in group
            if ($this->order_group)
                $sql .= " WHERE orders.id IN (" . implode(', ', $this->order_group) . ")";
            # Update single order
            else
                " WHERE id = {$this->id}";
        }
        else
        {
            $sql = "INSERT INTO orders (" .
                implode(",", array_keys($save_fields)) .
                ") VALUES (" .
                str_repeat("?,", (count($save_fields) - 1)) .
                "?)";
        }

        # Bind the values
        $sth = $this->dbh->prepare($sql);
        $i = 1;
        foreach ($save_fields as $field => $type)
        {
            if ($type == PDO::PARAM_INT || $type == PDO::PARAM_BOOL)
                $val = (int) $this->{$field};
            else
                $val = fix_encoding(trim($this->{$field}));

            $sth->bindValue($i, $val, $type);
            $i++;
        }
        $sth->execute();

        if ($this->id == 0)
            $this->id = $this->dbh->lastInsertId('orders_id_seq');

        # Update order group
        if ($this->order_group && count($this->order_group) > 1)
        {
            $sql = "UPDATE orders
			SET
				ship_to = main.ship_to,
				urgency = main.urgency,
				inst_date = main.inst_date,
				facility_id = main.facility_id,
				type_id = main.type_id,
				parent_order = main.parent_order,
				address = main.address,
				address2 = main.address2,
				city = main.city,
				state = main.state,
				zip = main.zip,
				sname = main.sname,
				ship_attn = main.ship_attn,
				email = main.email,
				fax = main.fax,
				mas_sales_order = main.mas_sales_order,
				source = main.source,
				third_party = main.third_party,
				code = main.code,
				updated_at_tstamp = main.updated_at_tstamp,
				updated_by = main.updated_by,
				ordered_by = main.ordered_by
			FROM orders main
			WHERE main.id = {$this->id} AND orders.id IN (" . implode(', ', $this->order_group) . ")";
            $this->dbh->query($sql);
        }
        else
            $this->order_group = array($this->id);
    }

    /**
     * Convert DigitalShipper service type to  shipping method
     *
     * @param $service int
     *
     * @return $shipping_method string
     */
    static public function DSto_SM($service)
    {
        $dbh = DataStor::GetHandle();

        # Default
        $shipping_method = "Ground";

        # Not sure what we are going to get
        if ($service)
        {
            $sth = $dbh->prepare("SELECT service_code
			FROM ship_method
			WHERE carrier_service_code = ?
			ORDER BY active
			LIMIT 1");
            $sth->bindValue(1, strtoupper($service), PDO::PARAM_STR);
            $sth->execute();
            $column = $sth->fetchColumn();
            if ($column)
                $shipping_method = $column;
        }

        return $shipping_method;
    }

    /**
     * Create a basic order with minimal form inputs
     *
     * @param $form array mixed
     */
    public function create($form)
    {
        # Dont allow user to fudge this
        #
        $this->order_date = time();
        $form['order_date'] = $this->order_date;

        # Copy address info from facility
        if (!isset($form['facility_id']))
            $form['facility_id'] = $this->facility_id;
        $customer = new CustomerEntity($form['facility_id']);

        if (!$this->sname)
            $this->sname = $customer->getName();
        if (!$this->city)
            $this->city = $customer->getCity();
        if (!$this->state)
            $this->state = $customer->getState();
        if (!$this->zip)
            $this->zip = $customer->getZip();
        if (!$this->address)
            $this->address = $customer->getAddress();
        if (!$this->address2)
            $this->address2 = $customer->getAddress2();

        # save handles the order items and status
        $this->save($form);

        return $this->id;
    }


    /**
     * Update object with new values
     *
     * @param $form array
     */
    public function save($form = array())
    {
        $user = $this->session_user;
        if (is_null($user) || $user->web_user)
            $user = new User(1);

        # Get user id
        if (!isset($form['user_id']))
            $form['user_id'] = $user->getId();

        if (!$this->order_date)
            $form['order_date'] = time();

        # Used to detect multiple user updates
        $load_tstamp = (isset($form['load_tstamp'])) ? $form['load_tstamp'] : 0;
        $updated_at_tstamp = strtotime($this->updated_at_tstamp);

        # Keep status_id unchanged
        $original_status = $this->status_id;

        # Copy new values into the object
        $this->copyFromArray($form);

        # Keep status_id unchanged
        $this->status_id = $original_status;

        # Used to determine if order status is to be changed
        $status_id = (isset($form['status_id'])) ? $form['status_id'] : $this->status_id;

        # Set new values
        $this->updated_at_tstamp = date('Y-m-d G:i:s');
        $this->updated_by = $user->getId();

        # Make sure ship method is set
        if (!$this->ship_method)
            $this->ship_method = "Ground";

        # Set PDO Types for null or missing data
        $ship_to_type = ($this->ship_to) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $code_type = is_null($this->code) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $ordered_by_type = ($this->ordered_by) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $fax_type = ($this->fax) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $inst_date_type = ($this->inst_date) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $mas_addr_key_type = ($this->mas_address_key) ? PDO::PARAM_INT : PDO::PARAM_NULL;

        $shipping_data = Order::$NO_SHIPMENT;

        #
        # Define what fields to save and the PDO Param Type
        # This will be used to construct the SQL query
        #
        $save_fields = array(
            'order_date' => PDO::PARAM_INT,
            'ship_to' => $ship_to_type,
            'ship_method' => PDO::PARAM_STR,
            'urgency' => PDO::PARAM_INT,
            'inst_date' => $inst_date_type,
            'facility_id' => PDO::PARAM_INT,
            'mas_address_key' => $mas_addr_key_type,
            'address' => PDO::PARAM_STR,
            'address2' => PDO::PARAM_STR,
            'address3' => PDO::PARAM_STR,
            'address4' => PDO::PARAM_STR,
            'address5' => PDO::PARAM_STR,
            'city' => PDO::PARAM_STR,
            'state' => PDO::PARAM_STR,
            'zip' => PDO::PARAM_STR,
            'country' => PDO::PARAM_STR,
            'sname' => PDO::PARAM_STR,
            'ship_attn' => PDO::PARAM_STR,
            'email' => PDO::PARAM_STR,
            'phone' => PDO::PARAM_STR,
            'type_id' => PDO::PARAM_INT,
            'parent_order' => PDO::PARAM_INT,
            'mas_sales_order' => PDO::PARAM_INT,
            'source' => PDO::PARAM_STR,
            'third_party' => PDO::PARAM_INT,
            'po_number' => PDO::PARAM_STR,
            'code' => $code_type,
            'updated_at_tstamp' => PDO::PARAM_STR,
            'updated_by' => PDO::PARAM_INT,
            'ordered_by' => $ordered_by_type,
            'service_level' => PDO::PARAM_INT,
            'shipping_company_id' => PDO::PARAM_INT,
            'fax' => $fax_type);

        if ($this->id == 0)
        {
            $save_fields['user_id'] = PDO::PARAM_INT;
        }

        # Add tracking_num and ret_tracking_num
        #
        if ($this->tracking_num)
        {
            $save_fields['tracking_num'] = PDO::PARAM_STR;
            $ret_type = ($this->ret_tracking_num) ? PDO::PARAM_STR : PDO::PARAM_NULL;
            $save_fields['ret_tracking_num'] = $ret_type;
        }

        if ($this->shipping_company_id)
        {
            if (empty($this->bill_to_acct))
                $this->SetDefaultAccount();
        }

        if ($this->bill_to_acct)
            $save_fields['bill_to_acct'] = PDO::PARAM_STR;
        else
        {
            $save_fields['bill_to_acct'] = PDO::PARAM_NULL;
        }

        if ($this->status_id < self::$SHIPPED && $status_id == self::$SHIPPED)
        {
            if (!$this->ship_date)
                $this->ship_date = time();

            $save_fields['ship_date'] = PDO::PARAM_INT;
            if (is_null($this->shipping_cost))
            {
                $save_fields['shipping_cost'] = PDO::PARAM_NULL;
            }
            else
            {
                $this->shipping_cost = (float) $this->shipping_cost;
                $save_fields['shipping_cost'] = PDO::PARAM_STR;
            }
            $shipping_data = Order::PingShippingData($this->id);

            # Confirm complaint form exists for
            # Complaint based orders
            # Do not perform check if this is a back order

            if (!$this->parent_order && in_array($this->type_id, array(self::$SWAP_ORDER, self::$PARTS_ORDER, self::$RMA_ORDER, self::$ACCESSORY_SWAP_ORDER, self::$RETURN_ORDER)))
            {
                $field = ($this->type_id == self::$RETURN_ORDER) ? "return_id" : "order_id";

                $sth = $this->dbh->prepare("SELECT count(*)
					FROM complaint_form_equipment
					WHERE $field = ?");
                foreach ($this->order_group as $order_id)
                {
                    $sth->bindValue(1, $order_id, PDO::PARAM_INT);
                    $sth->execute();
                    $complaint_count = $sth->fetchColumn();

                    if (!$complaint_count)
                    {
                        echo "<p class='error' style='background-color:#E0C0C0;'>
							Invalid order! Cannot ship because the complaint form is missing.</p>";
                        return false;
                    }
                }
            }
        }

        # May be negetive
        $this->urgency = abs($this->urgency);

        #
        # General update to requested fields other information is save when
        # Order is validated
        #
        $this->db_save($save_fields);
        $this->LogChanges();
        // Clear change listing there are more important changes coming
        $this->_delta = array();

        # Printing nothing else to be done
        #
        if (isset($form['print']) && $form['print'] > 0)
        {
            $this->load();
            return true;
        }

        # Addition save detail
        #

        # Add transaction when saving a PROCESSED order and new status is PROCESSED or SHIPPED
        if ($this->status_id == self::$PROCESSED && !in_array($status_id, array(self::$PROCESSED, self::$SHIPPED)))
        {
            if (!$this->processed_date)
                $this->processed_date = time();

            if (!$this->processed_by)
                $this->processed_by = $user->getId();
            else
            {
                # Validate the user (WEB orders are set to facility id)
                #
                $p_user = new User($this->processed_by);
                if (!$p_user->isActive())
                {
                    $this->processed_by = $this->session_user->getId();
                    $this->processed_date = time();
                }
            }

            $sql = "DELETE from order_transaction where order_id = {$this->id} AND transaction_date = {$this->processed_date}";
            $this->dbh->exec($sql);

            $sql = "INSERT INTO order_transaction (order_id, order_status_id, transaction_date, transaction_by)
			VALUES ({$this->id}, {$this->status_id}, {$this->processed_date}, {$this->processed_by})";
            $this->dbh->exec($sql);
        }

        # Detect if the order was modified by another user
        if ($load_tstamp && $load_tstamp < $updated_at_tstamp)
        {
            echo "<p class='error' style='background-color:#E0C0C0;'>This order has been modified by {$this->lastmod}. Please verify your changes and re-save.</p>";
            return false;
        }

        # Saves limited to updating order header
        # and status wont be updated unless there is a need to.
        $update = false;

        # Avoid re-saving items when order is complete (shipped,exception,etc.)
        if ($this->status_id == self::$QUEUED || $this->status_id == self::$PROCESSED)
        {
            # Change order status if item update is OK
            $update = $this->saveItems($form);
        }
        # Allow status change Eg. (Shipped => Exception)
        else if ($status_id != $this->status_id)
            $update = true;

        if ($update)
        {
            # If order is changed to shipped set the shipped by, and date
            if ($this->status_id != self::$SHIPPED && $status_id == self::$SHIPPED)
            {
                $this->LoadShippingData($shipping_data);
                $this->shipped_by = $user->getId();
                $this->ship_date = time();
            }
            # If order is processed set the processed by, and date
            if ($this->status_id != self::$PROCESSED && $status_id == self::$PROCESSED)
            {
                $this->processed_by = $user->getId();
                $this->processed_date = time();
            }

            # Update the order status, proccess info and shipp info
            $this->status_id = $status_id;
            $sth = $this->dbh->prepare("UPDATE orders
			SET status_id = ?,
			    ship_date = ?,
			    shipped_by = ?,
			    processed_date = ?,
			    processed_by = ?
			WHERE id IN ( " . implode(',', $this->order_group) . " )");
            $sth->bindValue(1, (int) $status_id, PDO::PARAM_INT);
            $sth->bindValue(2, (int) $this->ship_date, PDO::PARAM_INT);
            $sth->bindValue(3, (int) $this->shipped_by, PDO::PARAM_INT);
            $sth->bindValue(4, (int) $this->processed_date, PDO::PARAM_INT);
            $sth->bindValue(5, (int) $this->processed_by, PDO::PARAM_INT);
            $sth->execute();

            # Create BO for items that where not shipped
            if ($status_id == self::$SHIPPED && count($this->bo_items) > 0)
            {
                $this->CreateBO();
            }
        }

        # Update comments for this order only
        # Keep other order in group unchanged
        if (isset($form['comments']))
        {
            $this->comments = strip_tags(trim($form['comments']));
            $sth = $this->dbh->prepare("UPDATE orders SET comments = ? WHERE id = ?");
            $sth->bindValue(1, $this->comments, PDO::PARAM_STR);
            $sth->bindValue(2, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();
        }

        # Update tax_amount for this order only
        # Keep other order in group unchanged
        if (isset($form['tax_amount']))
        {
            $this->tax_amount = $form['tax_amount'];
            $tax_type = ($this->tax_amount) ? PDO::PARAM_STR : PDO::PARAM_NULL;

            $sth = $this->dbh->prepare("UPDATE orders SET tax_amount = ? WHERE id = ?");
            $sth->bindValue(1, $this->tax_amount, PDO::PARAM_STR);
            $sth->bindValue(2, (int) $this->id, $tax_type);
            $sth->execute();
        }

        $this->LogChanges();

        $this->load();

        $this->CheckReturnOrders();

        return $update;
    }

    private function saveItems($form)
    {
        global $sh;

        # Used to determine if order status is to be changed
        $status_id = (isset($form['status_id'])) ? $form['status_id'] : $this->status_id;
        $printing = (isset($form['print']) && $form['print'] > 0);

        # Need to verify asset and update status
        $check_asset = ($status_id == self::$SHIPPED || ($status_id == self::$PROCESSED && $this->in_asset));

        # Make sure same asset is not entered twice
        $unique_assets = array();
        # Each item requires a unique item number
        $unique_numbers = array();

        $bo_items = array();

        $update = true;

        # Do an update if have model and serial for the order items
        if (isset($form['items']))
        {
            $temporary_asset_record_created = false;
            $this->dbh->query("DELETE FROM order_item WHERE order_id IN (" . implode(',', $this->order_group) . ")");

            # Validate the item and save the item record
            #
            foreach ($form['items'] as $i => $item)
            {
                $this->ValidateItem($item, $unique_numbers);

                # Get Asset
                if ($item['e_model'] <> "Unknown")
                {
                    $error_msg = $this->GetAssetId($item, $unique_assets, $status_id);
                    if ($error_msg)
                    {
                        # Ignor erros until items are shipped
                        if ($check_asset)
                        {
                            $update = false;
                            echo $error_msg;
                        }
                        else
                        {
                            $error_msg = "";
                        }
                    }
                }

                ###
                # Add order_item
                #
                # Set new asset from lease asset
                # Set new prod_id from products table
                $asset_sth = $this->dbh->prepare("INSERT INTO order_item
				(order_id, item_num,
				 prod_id, code, \"name\", description,
				 quantity, asset_id, swap_asset_id,
				 shipped, uom, price, whse_id, upsell,item_lot)
				VALUES (?,?, ?,?,?,?, ?,?,?, ?,?,?,?,?,?)");
                $asset_sth->bindValue(1, (int) $item['order_id'], PDO::PARAM_INT);
                $asset_sth->bindValue(2, (int) $item['item_num'], PDO::PARAM_INT);
                $asset_sth->bindValue(3, (int) $item['prod_id'], PDO::PARAM_INT);
                $asset_sth->bindValue(4, trim($item['code']), PDO::PARAM_STR);
                $asset_sth->bindValue(5, trim($item['name']), PDO::PARAM_STR);
                $asset_sth->bindValue(6, trim($item['description']), PDO::PARAM_STR);
                $asset_sth->bindValue(7, (int) $item['quantity'], PDO::PARAM_INT);
                $asset_sth->bindValue(8, (int) $item['asset_id'], PDO::PARAM_INT);
                $asset_sth->bindValue(9, (int) $item['swap_asset_id'], PDO::PARAM_INT);
                $asset_sth->bindValue(10, (int) $item['shipped'], PDO::PARAM_INT);
                $asset_sth->bindValue(11, $item['uom'], PDO::PARAM_STR);
                $asset_sth->bindValue(12, $item['price'], (is_null($item['price']) ? PDO::PARAM_NULL : PDO::PARAM_STR));
                $asset_sth->bindValue(13, $item['whse_id'], ($item['whse_id'] ? PDO::PARAM_STR : PDO::PARAM_NULL));
                $asset_sth->bindValue(14, (int) $item['upsell'], PDO::PARAM_BOOL);
                $asset_sth->bindValue(15, trim($item['item_lot']), (is_null($item['item_lot']) ? PDO::PARAM_NULL : PDO::PARAM_STR));
                $asset_sth->execute();

                if ($item['quantity'] > $item['shipped'])
                {
                    $qty = ($item['quantity'] - $item['shipped']);
                    $unit_price = $this->GetUnitPrice($item);

                    $bo_items[] = array(
                        'item_num' => $item['item_num'],
                        'prod_id' => $item['prod_id'],
                        'code' => $item['code'],
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'quantity' => $qty,
                        'price' => ($qty * $unit_price),
                        'shipped' => 0,
                        'uom' => $item['uom'],
                        'asset_id' => (int) $item['asset_id'],
                        'swap_asset_id' => (int) $item['swap_asset_id'],
                        'whse_id' => $item['whse_id'],
                        'item_lot' => $item['item_lot'],
                        'upsell' => $item['upsell']);
                }

                $form['items'][$i] = $item;
            }

            # May be in a trasnaction from Complaint Form
            //$cf_trans = $this->dbh->inTransaction();
            $cf_trans = $sh->in_trans;

            # Update the asset records
            if ($update === true && $printing === false)
            {
                if (!$cf_trans)
                {
                    $sh->in_trans = true;
                    $this->dbh->beginTransaction();
                }

                foreach ($form['items'] as $i => $item)
                {
                    # The asset is known and requires an update
                    if ($check_asset && $item['asset_id'] > 0 && $item['shipped'] == 1)
                    {
                        $error_msg = $this->UpdateAsset($item, $status_id);
                        if ($error_msg)
                        {
                            $update = false;
                            echo $error_msg;
                        }
                    }
                }

                if (!$cf_trans)
                {
                    if ($update)
                    {
                        $sh->in_trans = false;
                        $this->dbh->commit();
                    }
                    else
                    {
                        $sh->in_trans = false;
                        $this->dbh->rollBack();
                    }
                }
            }
        }

        $this->bo_items = $bo_items;

        return $update;
    }

    /**
     * Set any missing fields
     *
     * @param array
     * @param array
     */
    private function ValidateItem(&$item, &$unique_numbers)
    {
        # Do some item number validation
        # Must be > 0 and unique
        $item_num = (isset($item['item_num']) && $item['item_num'] > 0) ? $item['item_num'] : 1;

        while (in_array($item_num, $unique_numbers))
            $item_num++;
        $unique_numbers[] = $item_num;

        # Set/Reset a valid value
        $item['item_num'] = $item_num;

        # Maintain valid order id
        if (!isset($item['order_id']))
            $item['order_id'] = $this->id;
        else if ($item['order_id'] == 0)
            $item['order_id'] = $this->id;

        # Force integer value
        if (!isset($item['shipped']))
            $item['shipped'] = 0;
        else
            $item['shipped'] = (int) $item['shipped'];

        # Clear asset_id unless its negative.
        # Negative used to keep primary key unique
        if ($item['asset_id'] > 0)
            $item['asset_id'] = 0;

        # Add missing uom
        if (!$item['uom'])
            $item['uom'] = 'EA';

        # Validate price
        # Empty or missing price is null
        # Other values icluding 0 gets saved as float
        if (!isset($item['price']) || $item['price'] === '')
            $item['price'] = null;
        else
            $item['price'] = (float) $item['price'];

        # Add missing whse_id
        if (!isset($item['whse_id']))
            $item['whse_id'] = '';

        # Add missing upsell
        if (!isset($item['upsell']))
            $item['upsell'] = 0;

        # Add missing product info
        if (!isset($item['code']))
            $item['code'] = "";
        if (!isset($item['name']))
            $item['name'] = "";
        if (!isset($item['description']))
            $item['description'] = "";
        if (!isset($item['item_lot']))
            $item['item_lot'] = "";

        # Set defaults
        $item['e_model'] = "Unknown";
        $item['unit_type'] = EquipmentModel::$BASEUNIT;

        # Match model id to product id
        if (isset($item['model']) && $item['model'] > 0)
        {
            # Find the product id, code, and unit type for this model
            $prod_sth = $this->dbh->query("SELECT
				p.id, p.code, p.name, p.description
			FROM products p
			INNER JOIN equipment_models e ON p.code = e.model
			WHERE e.id = {$item['model']}");
            if ($prod_sth->rowCount() > 0)
            {
                $prod = $prod_sth->fetch(PDO::FETCH_ASSOC);
                $item['prod_id'] = $prod['id'];
                $item['code'] = $prod['code'];
                $item['name'] = $prod['name'];
                $item['description'] = $prod['description'];
            }
        }

        # Find the equipment model type and product description
        if ($item['prod_id'])
        {
            $prod_sth = $this->dbh->query("SELECT
				p.code, p.name, p.description,
				em.model, em.type_id,em.id
			FROM products p
			LEFT JOIN equipment_models em ON p.code = em.model
			WHERE p.id = {$item['prod_id']}");
            if ($prod_sth->rowCount() > 0)
            {
                $detail = $prod_sth->fetch(PDO::FETCH_ASSOC);

                if (trim($item['code']) == "")
                    $item['code'] = $detail['code'];
                if (trim($item['name']) == "")
                    $item['name'] = $detail['name'];
                if (trim($item['description']) == "")
                    $item['description'] = $detail['description'];
                if ($detail['model'])
                    $item['e_model'] = $detail['model'];
                if ($detail['id'])
                    $item['model'] = $detail['id'];
                if ($detail['type_id'])
                    $item['unit_type'] = $detail['type_id'];
            }
        }

        $item['serial_number'] = (isset($item['serial_number'])) ? strtoupper($item['serial_number']) : "";
        $item['bar_code'] = "";
    }

    /**
     * @param array
     * @param array
     *
     * @return string
     */
    private function GetAssetId(&$item, &$unique_assets, $status_id)
    {
        # Declare and assign 0
        $converted_serial = 0;
        $asset_id = false;
        $swap_asset_id = isset($item['swap_asset_id']) ? (int) $item['swap_asset_id'] : 0;
        $temp = false;

        # No message by default
        $found = false;
        $error_msg = "";

        # If this is a barcode convert it to a serial number
        if (!empty($item['serial_number']))
        {
            # Convert barcode
            $converted_serial = LeaseAsset::BarcodeToSerial($item['serial_number']);

            # Lookup asset
            if ($converted_serial)
            {
                $asset_id = LeaseAsset::exists((int) $item['model'], $converted_serial);
                if ($asset_id)
                {
                    $item['bar_code'] = $item['serial_number'];
                    $item['serial_number'] = $converted_serial;
                }
            }
        }

        # Purchases are limited to "Purchase Pool" and serials must be confirmed
        if (!empty($item['confirm_serial']))
        {
            if ($item['confirm_serial'] <> $item['serial_number'])
            {
                $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
				Unconfirmed Serial! Item: {$item['e_model']}, Serial: {$item['serial_number']} does not match {$item['confirm_serial']}</p>";

                # Unconfirmed return serial to its original value
                $item['serial_number'] = $item['confirm_serial'];
                $asset_id = false;
            }
        }

        if ($asset_id === false)
        {
            # Find asset using original serial_num
            $asset_id = LeaseAsset::exists((int) $item['model'], $item['serial_number']);

            # If accessory there may be no asset record
            if ($asset_id === false && $item['unit_type'] == EquipmentModel::$ACCESSORY)
            {
                if ($this->in_asset && ($status_id == self::$SHIPPED))
                {
                    if ($temporaryCreated = $this->createTemporaryAssetRecord((int) $item['model']))
                    {
                        $this->temporariesCreated[] = $temporaryCreated;
                        $temp = true;
                    }
                }
            }
        }

        $item['asset_id'] = ($asset_id > 0) ? $asset_id : $item['asset_id'];

        if ($asset_id === false)
        {
            if ($item['unit_type'] == EquipmentModel::$BASEUNIT)
            {
                if (isset($item['barcode']) && $item['barcode'])
                    $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
					Item: {$item['e_model']}, Serial: {$item['serial_number']}, Barcode: {$item['barcode']} was not found!</p>";
                else if ($item['serial_number'])
                    $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
					Item: {$item['e_model']}, Serial: {$item['serial_number']} was not found!</p>";
            }
            else
            {
                # Shipping accessory
                if ($this->out_asset)
                {
                    if ($status_id == self::$SHIPPED && $item['serial_number'])
                    {
                        $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
						Accessory Item: {$item['e_model']}, Serial: {$item['serial_number']} was not found!</p>";
                    }
                }
                else if ($this->in_asset)
                {
                    # RMA unknown accessory
                    if (!$temp && $status_id == self::$SHIPPED)
                    {
                        if ($temporaryCreated = $this->createTemporaryAssetRecord((int) $item['model']))
                            $this->temporariesCreated[] = $temporaryCreated;
                    }
                }
            }
        }
        else
        {
            # Check for uniqueness using asset id
            if (in_array($item['asset_id'], $unique_assets))
            {
                # Change asset id to the next available number
                # This will be something negative
                $u_id = min($unique_assets) - 1;
                if ($u_id > 0)
                    $u_id = -1;

                $item['asset_id'] = $u_id;

                $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
				Duplicate Item: {$item['e_model']}, Serial: {$serial} was found!</p>";
            }
            else
            {
                # Add this asset_id to array
                $unique_assets[] = $item['asset_id'];
            }

            $asset = new LeaseAsset($asset_id);

            # Purchases NEW check
            if ($item['whse_id'] == self::$PURCHASE_WHSE)
            {
                # Check if this asset has been placed (Exclude DEFAULT001 and this facility)
                # Used to verify New items have never been placed
                #
                $placed = LeaseAssetTransaction::$PLACED;
                $exclude_facilities = array($this->facility_id, Config::$DEFAULT001_ID, Config::$ACL900_ID);
                $been_placed = $asset->HasTransaction($placed, null, null, null, null, $exclude_facilities);

                if ($asset->getPreviousAssetID() || $been_placed)
                {
                    $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
					Purchase Equipment: {$item['e_model']}, Serial: {$item['serial_number']} is not new.</p>";

                    $item['asset_id'] = 0;
                }
            }

            # Verify the destination is the same facility "location"
            if ($asset->CustomerOwned())
            {
                $last_t = $asset->getLastTransaction();
                $placement = ($last_t) ? $last_t->getFacility() : null;
                $location_id = ($placement) ? strtoupper(trim($placement->getCustId())) : null;

                $facility = new Facility($this->facility_id);
                $current_destination = strtoupper(trim($facility->getCustId()));

                if ($current_destination != $location_id)
                {
                    $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
					Device (Model: {$item['e_model']}, Serial: {$item['serial_number']}) was located at $location_id but attempting to ship to $current_destination
					</p>";

                    $item['asset_id'] = 0;
                }
            }
        }

        # For returns of customer owned equipment
        # Verify the destination is the same facility "location"
        if ($swap_asset_id > 0)
        {
            $return = new LeaseAsset($item['swap_asset_id']);
            if ($return && $return->CustomerOwned())
            {
                $last_t = $return->getLastTransaction();
                $placement = ($last_t) ? $last_t->getFacility() : null;
                $location_id = ($placement) ? strtoupper(trim($placement->getCustId())) : null;

                $facility = new Facility($this->facility_id);
                $current_destination = strtoupper(trim($facility->getCustId()));

                if ($current_destination != $location_id)
                {
                    $model = $return->getModel()->getNumber();
                    $serial = $return->getSerial();

                    $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
					Returning Device (Model: $model, Serial: $serial) was located at $location_id but attempting to ship to $current_destination
					</p>";

                    $item['asset_id'] = 0;
                }
            }
        }

        return $error_msg;

    }#GetAssetId()


    /**
     * Deletes an order.
     *
     * @param string
     * @return integer
     */
    public function delete()
    {
        global $reason;

        # Check order status
        if ($this->status_id != 1 && $this->status_id != 99)
        {
            $reason = 'The status of the order does not allow you to delete it.';
            return 0;
        }

        # Default to leased at Warhouse
        $location = Config::$DEFAULT001_ID;
        $place_sub = 'Lease'; # LeaseAssetTransaction::$LEASE;

        # For RMA,Return and Customer orders
        # retain the facility and use purchased substatus
        if ($this->is_purchase)
        {
            $location = $this->facility_id;
            $place_sub = 'Purchase'; # LeaseAssetTransaction::$PURCHASE;
        }

        # Update complaint forms which may have initiated the order
        $sth = $this->dbh->prepare('UPDATE complaint_form_equipment SET order_id = null WHERE order_id = ?');
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->execute();

        # Update complaint forms which may have initiated the return order
        $sth = $this->dbh->prepare('UPDATE complaint_form_equipment SET return_id = null WHERE return_id = ?');
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->execute();

        # Override Asset transaction added in "save"
        foreach ($this->items as $item)
        {
            Cart::RevertAssetTransaction($item, $this->type_id);
        }

        # Remove the order_transactions
        $this->dbh->exec("DELETE FROM order_transaction WHERE order_id = {$this->id}");

        # Finally remove the order
        if ($this->dbh->query("DELETE FROM orders WHERE id = {$this->id}"))
        {
            $reason = '';
            return 1;
        }
        else
        {
            $reason = "The delete query failed!";
            return 0;
        }
    }


    /**
     * Prints the shipping form.
     */
    public function showShippingForm($cat_id = null)
    {
        global $preferences;
        global $date_format;

        $calendar_format = str_replace(array('Y', 'd', 'm', 'M'), array('%Y', '%d', '%m', '%b'), $date_format);

        $ship_to_facility = ($this->ship_to == self::$SHIP_TO_FACILITY) ? 'checked' : '';
        $ship_to_home = ($this->ship_to == self::$SHIP_TO_CPM) ? 'checked' : '';
        $ship_to_other = ($this->ship_to == self::$SHIP_TO_OTHER) ? 'checked' : '';

        $urgency_order = (abs($this->urgency) == 1) ? 'checked' : '';
        $urgency_install = (abs($this->urgency) == 2) ? 'checked' : '';
        $inst_date = ($this->inst_date) ? date($date_format, $this->inst_date) : '';

        $state_list = Forms::createStateList($this->state);

        return <<<END
	<form name="checkout" action="{$_SERVER['PHP_SELF']}" method="post" onSubmit="return validateForm(this)">
	<input type="hidden" name="order_id" value="{$this->id}">
	<input type="hidden" name="cat_id" value="{$cat_id}">
	<table class="form" cellpadding="5" cellspacing="2" style="margin-top:0; margin-left:0;">
		<tr>
			<th class="subheader" colspan="4">Shipping Information</th>
		</tr>
		<tr>
			<th class="form">Ship to*:</th>
			<td class="form" colspan="3">
				<input type="radio" name="ship_to" value="1" onClick="enableShippingFields()" {$ship_to_facility}> Facility:
				<input type="hidden" name="fac_id" value="{$this->facility_id}">
				<input type="hidden" name="facility_id" value="{$this->facility_id}">
				<input type="text" name="fac_name" value="{$this->facility_name}" size="30" readonly>
				<img class="form_bttn" src="images/facilities-mini.png" onClick="document.checkout.ship_to[0].checked=true;Window1=window.open('facility_lookup.php?cos=1&amp;sc=1','Facility_Lookup','width=650,height=500,toolbar=no,scrollbars=yes,resizable=yes')" alt="Facility List" title="Facility List">
				<img class="form_bttn" src="images/cancel.png" onClick="clearFacility()" alt="Clear Facility" title="Clear Facility">
				<br>
				<input type="radio" name="ship_to" value="2" onClick="enableShippingFields();setHome(this)" {$ship_to_home}> CPM Home
				<br>
				<input type="radio" name="ship_to" value="3" onClick="enableShippingFields();setOther()" {$ship_to_other}> Other
			</td>
		</tr>
		<tr>
			<th class="form">Name:</th>
			<td class="form" colspan="3">
				<input type="text" name="sname" value="{$this->sname}" size="20" maxlength="64">
			</td>
		</tr>
		<tr>
			<th class="form">Attn:</th>
			<td class="form" colspan="3">
				<input type="text" name="ship_attn" value="{$this->ship_attn}" size="20" maxlength="64">
			</td>
		</tr>
		<tr>
			<th class="form">Address Line 1*:</th>
			<td class="form" colspan="3">
				<input type="text" name="address" value="{$this->address}" size="25" maxlength="64">
			</td>
		</tr>
		<tr>
			<th class="form">Address Line 2:</th>
			<td class="form" colspan="3">
				<input type="text" name="address2" value="{$this->address2}" size="25" maxlength="64">
			</td>
		</tr>
		<tr>
			<th class="form">City*:</th>
			<td class="form">
				<input type="text" name="city" value="{$this->city}" size="15" maxlength="64">
			</td>

			<th class="form">State*:</th>
			<td class="form">
				<select name="state">
					{$state_list}
				</select>
			</td>
		</tr>
		<tr>
			<th class="form">Zip*:</th>
			<td class="form">
				<input type="text" name="zip" value="{$this->zip}" size="10">
			</td>

			<td class="form" colspan="2">
				<input type="hidden" name="email" value="">
			</td>

			<!-- Keep this around in case they change their minds
			<th class="form">Email:</th>
			<td class="form">
				<input type="text" name="email" value="{$this->email}" size="25" maxlength="64">
			</td>
			-->

		</tr>
		<tr>
			<th class="form">Send Shipment*:</th>
			<td class="form" colspan="3">
				<input type="radio" name="urgency" value="1" {$urgency_order}> Upon Order**
				<br>
				<input type="radio" name="urgency" value="2" {$urgency_install}> On Date:
				<input type="text" name="inst_date" id="inst_date" size="10" value="{$inst_date}" readonly>
				<img class="form_bttn" id="trg" src="images/calendar-mini.png" alt="Calendar" title="Calendar">
				<script type="text/javascript">

					function setUrgency(cal)
					{
						document.checkout.urgency[1].checked = true;
						cal.hide();
						return true;
					};

				    Calendar.setup({
				        inputField	:	"inst_date",	// id of the input field
				        ifFormat	:	"{$calendar_format}",	// format of the input field
				        button		:	"trg",		// trigger for the calendar (button ID)
				        step		:	1,				// show all years in drop-down boxes (instead of every other year as default)
				        weekNumbers	:	false,			// hides the week numbers
				        onClose		:	setUrgency
				    });
				</script>
			</td>
		</tr>
		<tr>
			<th class="form">Order Notes:</th>
			<td class="form" colspan="3">
				<textarea name="comments" rows="4" cols="50">{$this->comments}</textarea>
			</td>
		</tr>
		<tr>
			<td class="form_help" colspan="4">
				* Denotes a <span class="required">required</span> field
			</td>
		</tr>
		<tr>
			<td class="form_help" colspan="4">
				** Orders placed before 2:00PM (PST) will generally ship Ground Service same day
			</td>
		</tr>
		<tr>
			<td class="buttons" colspan="4">
				<input type="submit" name="action" value="Submit">
			</td>
		</tr>
	</table>
	</form>
END;
    }

    /**
     * Save order and clear contents of the shopping cart
     */
    public function processOrder($form)
    {
        global $cat_id;

        $user = $this->session_user;

        # Set order type_id and status_id
        $type_id = (isset($form['type_id'])) ? $form['type_id'] : 1;
        $status_id = self::$QUEUED;
        $user_type = get_class($user);

        # Credit hold will be queued otherwise straight to processed.
        if ($type_id == self::$CUSTOMER_ORDER || $type_id == self::$SUPPLY_ORDER || $type_id == self::$WEB_ORDER)
            $status_id = (isset($form['status_id'])) ? $form['status_id'] : self::$PROCESSED;

        $form['status_id'] = $status_id;
        $form['comments'] = $this->comments;

        $this->dbh->beginTransaction();

        $this->save($form);

        if ($this->issue_id)
            $is_complaint_based = true;
        else if (in_array($this->type_id, array(Order::$SUPPLY_ORDER, Order::$PARTS_ORDER, Order::$SWAP_ORDER)))
            $is_complaint_based = true;
        else
            $is_complaint_based = false;

        # Save the estimated shipping cost
        if ($is_complaint_based)
            $this->change('shipping_cost', 0);
        else if (isset($form['shipping_cost']))
            $this->change('shipping_cost', $form['shipping_cost']);

        # Avoid conflicts with multiple users editing this order
        if ($this->id)
            $this->dbh->query("DELETE FROM order_item WHERE order_id = {$this->id}");

        # Add all items from the cart into the order
        $sth_sel = $this->dbh->prepare("INSERT INTO order_item
			(order_id, item_num,
			 prod_id, code, \"name\", description,
			 quantity, asset_id, swap_asset_id,
			 uom, price, whse_id, upsell)
		SELECT
			{$this->id}, i.item_num,
			i.prod_id, p.code, p.name, p.description,
			i.quantity, i.asset_id, i.swap_asset_id,
			i.uom, i.price, i.whse_id, i.upsell
		FROM cart_item i
		INNER JOIN products p ON i.prod_id = p.id
		WHERE i.user_id = ? AND i.user_type = ?");
        $sth_sel->bindValue(1, $user->getId(), PDO::PARAM_INT);
        $sth_sel->bindValue(2, $user_type, PDO::PARAM_STR);
        $sth_sel->execute();

        $this->dbh->query("DELETE FROM cart WHERE user_id = {$user->getId()} AND user_type = '{$user_type}'");
        $this->dbh->query("DELETE FROM cart_item WHERE user_id = {$user->getId()} AND user_type = '{$user_type}'");
        $this->dbh->commit();

        # Populate items array
        //$this->loadItems();

        return $this->id;
    }

    /**
     * Return item array
     */
    public function GetItems()
    {
        $item_obj_ary = array();
        foreach ($this->items as $i => $item)
        {
            if ($this->id == $item['order_id'])
            {
                $item_obj_ary[$i] = new LineItem();
                $item_obj_ary[$i]->copyFromArray($item);
                $item_obj_ary[$i]->loadProductInfo();
            }
        }
        return $item_obj_ary;
    }

    public function GetActionLog()
    {
        $sth = $this->dbh->prepare("SELECT * FROM action_log WHERE params LIKE ? ORDER BY tstamp DESC");
        $sth->bindValue(1, '%' . $this->id . '%', PDO::PARAM_STR);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    /**
     * Return order type
     */
    public function getOrderType()
    {
        return $this->type_id;
    }

    /**
     * Prints the contents of an order.
     */
    public function showOrder()
    {
        global $preferences;
        global $date_format;
        global $action;
        global $sh, $hippa_access;

        $user = $this->session_user;

        $ship_date = $this->ship_date ? date($date_format, $this->ship_date) : '';
        $inst_date = $this->inst_date ? date($date_format, $this->inst_date) : '';

        $show_patient_info = ($this->cust_entity_type == CustomerEntity::$ENTITY_PATIENT)
            ? ($sh->isOnLAN() && $user->hasAccessToApplication($hippa_access))
            : true;

        $sname = ($show_patient_info) ? $this->sname : "Customer";
        $ship_attn = ($this->ship_attn) ? $this->ship_attn : '';

        $ship_to = 'Other';
        if ($this->ship_to == self::$SHIP_TO_FACILITY)
            $ship_to = ($show_patient_info) ? $this->facility_name : "Customer";
        elseif ($this->ship_to == self::$SHIP_TO_CPM)
            $ship_to = 'Home';


        $so_number = ($this->mas_sales_order > 0) ? $this->mas_sales_order : '';

        $comments_row = "";
        if ($this->comments)
        {
            $comments_row = "
			<tr>
				<th colspan=\"2\" class=\"subheader\">Order Notes</th>
			</tr>
			<tr>
				<td colspan=\"2\" class=\"view\">{$this->comments}</td>
			</tr>";
        }

        $tracking = self::FormatTrackingNo($this->tracking_num);
        $tracking .= self::FormatTrackingNo($this->ret_tracking_num);

        $tracking_row = ($tracking) ?
            "<tr>
				<th class='view'>Tracking #</th>
				<td class='view'>{$tracking}</td>
			</tr>" : "";


        $item_rows = "";
        $row_class = 'on';
        foreach ($this->items as $item)
        {
            if ($item['order_id'] == $this->id)
            {
                $item_rows .= "
			<tr class=\"{$row_class}\">
				<td>{$item['quantity']} {$item['uom']}</td>
				<td>{$item['shipped']}</td>
				<td>{$item['code']}</td>
				<td align='left'>{$item['name']}</td>
			</tr>";
                $row_class = ($row_class == 'on') ? 'off' : 'on';
            }
        }

        $parent_row = "";
        if ($this->parent_order)
            $parent_row = "
			<tr>
				<th class='view'>Reference Order</th>
				<td class='view'>{$this->parent_order}</td>
			</tr>";

        $bo_row = "";
        if ($this->back_order)
        {
            $bo_row = "
			<tr>
				<th class='view'>Back Order #</th>
				<td class='view'>{$this->back_order}</td>
			</tr>";
        }

        $email = ($this->email) ? "
			<tr>
		    	<th class='form'>Outgoing Email</th>
				<td class='form' align='left'>
					{$this->email}
				</td>
			</tr>" : '';
        $ordered_by = ($this->ordered_by) ? "
			<tr>
		    	<th class='form'>Customer Name</th>
				<td class='form' align='left'>
					{$this->ordered_by}
				</td>
			</tr>" : '';
        $fax = ($this->fax) ? "
			<tr>
		    	<th class='form'>Fax</th>
				<td class='form' align='left'>
					{$this->fax}
				</td>
			</tr>" : '';

        //$alignment = ($action=='view') ? "style=\"margin-left:0; margin-top:0;\"" : "";

        $batch_link = "";
        if ($this->type_id == self::$SUPPLY_ORDER)
            $batch_link = "<a href='{$_SERVER['PHP_SELF']}?act=bulk&order_id={$this->id}' alt='Batch Copy Order' title='Batch Copy Order'>Batch</a>";

        return <<<END
		<table cellpadding="5" cellspacing="2" class="view">
			<tr>
				<th class="subheader" colspan="2">Order Information</th>
			</tr>
			<tr>
				<th class="view">Order #:</th>
				<td class="view">
					{$this->id}
					<div class='submenu' style='float:right;'>$batch_link</div>
				</td>
			</tr>
			{$bo_row}
			{$parent_row}
			<tr>
				<th class="view">Ship To:</th>
				<td class="view">{$ship_to}</td>
			</tr>
			<tr>
				<th class="view">Cust ID:</th>
				<td class="view">{$this->cust_id}</td>
			</tr>
			<tr>
				<th class="view">Shipping<br>Information:</th>
				<td class="view">
					{$sname}<br>
					{$ship_attn}<br>
					{$this->address}<br>
					{$this->address2}<br>
					{$this->city}, {$this->state} {$this->zip}<br>
				</td>
			</tr>
			<tr>
				<th class="view">Status:</th>
				<td class="view">{$this->status_name}</td>
			</tr>
			<tr>
				<th class="view">Delivery Date:</th>
				<td class="view">{$inst_date}</td>
			</tr>
			<tr>
				<th class="view">Ship Date:</th>
				<td class="view">{$ship_date}</td>
			</tr>
			{$tracking_row}
			<tr>
				<th class="view">Phone #:</th>
				<td class="view">{$this->phone}</td>
			</tr>
			<tr>
				<th class="view">PO #:</th>
				<td class="view">{$this->po_number}</td>
			</tr>
			<tr>
				<th class="view">SO #:</th>
				<td class="view">{$so_number}</td>
			</tr>
			{$email}
			{$ordered_by}
			{$fax}
			<tr>
				<th class="subheader" colspan="2">Items</th>
			</tr>
			<tr>
				<td colspan="2" style="padding:0;">
					<table cellpadding="5" width="100%" class="list" style="margin:0; padding:0; border:0;">
						<tr>
							<th class="list">Quantity</th>
							<th class="list">Shipped</th>
							<th class="list">Item #</th>
							<th class="list">Description</th>
						</tr>
						{$item_rows}
					</table>
				</td>
			</tr>
			{$comments_row}
		</table>
END;
    }

    /**
     * Display form for updating single order
     *
     */
    public function showEditForm()
    {
        global $date_format, $calendar_format;

        $user = $this->session_user;
        $id = $this->id;
        $display_id = implode(',', $this->order_group);
        $current_tstamp = time();
        $equip_no_serial = "";

        # Get the regular expressions for the serial numbers / barcodes.
        #
        $equipment_regexs = array();
        $equipment_regex_js_array = '';
        $sth = $this->dbh->query('SELECT id, serial_regex FROM equipment_models');
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            if (!is_null($row['serial_regex']))
            {
                $equipment_regexs[$row['id']] = $row['serial_regex'];
                $equipment_regex_js_array .= "equipRegex[\"{$row['id']}\"]=/^{$row['serial_regex']}|(\d{8})\$/i;\n";
            }
        }

        $process_err = '';
        if ($this->update_since_processed)
            $process_err = "<div class='alert alert-danger alert-dismissible ' style='width: 500px; text-align: center; margin: auto;'><h4><i class='icon fa fa-ban'></i> ALERT! ORDER HAS CHANGED, PLEASE REVIEW.</h4></div><br>";

        if ($this->status_id == self::$SHIPPED)
            $process_err = '';

        $has_dock = ($this->has_dock) ? 'Has Dock  &nbsp;&nbsp; ' : '';
        $liftgate_required = ($this->requires_liftgate) ? ' &nbsp;&nbsp; Lift Gate Required' : '';
        $inside_delivery_required = ($this->requires_inside_delivery) ? ' &nbsp;&nbsp; Inside Delivery Required' : '';
        $display_special_shiiping_instructions = "";
        if ($has_dock || $liftgate_required || $inside_delivery_required)
            $display_special_shiiping_instructions = "<div class='alert alert-warning alert-dismissible' style='width: 500px; text-align: center; margin: auto;'><h4><i class='icon fa fa-warning'></i> FREIGHT SPECIAL SHIPPING INSTRUCTIONS<br>{$has_dock}{$liftgate_required} &nbsp;&nbsp; {$inside_delivery_required}</h4></div><br>";

        # Print javascript serial number validation vars.
        #
        echo "
<script type=\"text/javascript\">
var equipRegex = new Object();
{$equipment_regex_js_array}
var equip_no_serial = new Object();
{$equip_no_serial}

function Init()
{
	GetTNTData($id, {$this->facility_id});
}
YAHOO.util.Event.onDOMReady(Init);
</script>\n";

        # Status options are limited. Admin priviledge allows more options.
        #
        $exclude = array(self::$DELETED, self::$EDITING);
        $is_admin = $user->hasAccessToApplication('orderfilladmin');

        if ($is_admin)
        {
            # Select the current status
            $default = $this->status_id;

            if ($this->status_id == self::$PROCESSED)
                $exclude = array(self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$SHIPPED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$EXCEPTION)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$HOLD)
                $exclude = array(self::$SHIPPED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$CANCELED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$DELETED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$WRITEOFF)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$EDITING, self::$DELETED);
            else if ($this->status_id == self::$EDITING)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$DELETED, self::$WRITEOFF);
            else
                $exclude = array(self::$DELETED, self::$EDITING, self::$WRITEOFF);
        }
        else
        {
            # If order is active default to shipped status (non-admin basic function is to ship orders).
            # Otherwise select the current status. It will not be allowed to be modified.
            #
            $default = ($this->status_id <= self::$SHIPPED) ? self::$SHIPPED : $this->status_id;

            if ($this->status_id == self::$PROCESSED)
                $exclude = array(self::$QUEUED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$SHIPPED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$EXCEPTION)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$HOLD)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$CANCELED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$DELETED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$WRITEOFF)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$EDITING, self::$DELETED);
            else if ($this->status_id == self::$EDITING)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$DELETED, self::$WRITEOFF);
            else
                $exclude = array(self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
        }

        # Create options list
        $status_list = Forms::createOrderStatusList($default, $exclude);

        # Create options list
        $service_level_options = Forms::createServiceLevelList($this->service_level);

        # If Order is being edited in Supply Order disable changes.
        $disable_editing = ($this->status_id == self::$EDITING) ? "disabled" : "";

        # Format dates
        # Set default ship date to today if the status is processed
        $order_date = date($date_format, $this->order_date);
        $ship_date = ($this->ship_date > 0) ? date($date_format, $this->ship_date) : '';
        $inst_date = ($this->inst_date > 0) ? date($date_format, $this->inst_date) : '';

        $ship_date_row = "";
        $inst_date_row = "";
        if ($this->status_id == Order::$QUEUED)
        {
            $ship_date_row = "
			<tr>
				<th class=\"form\" colspan='2'>Ship&nbsp;Date</th>
				<td class=\"form\" colspan='4' align=\"left\">
					<input type='text' name='ship_date' id='ship_date' size='8' value='{$ship_date}' readonly>
					<img class='form_bttn' id='ship_date_trg' src='images/calendar-mini.png' alt='Calendar' title='Calendar'>
					<script type=\"text/javascript\">
				    Calendar.setup({
				        inputField	:	\"ship_date\",		// id of the input field
				        ifFormat	:	\"{$calendar_format}\",	// format of the input field
				        button		:	\"ship_date_trg\",	// trigger for the calendar (button ID)
				        step		:	1,				// show all years in drop-down boxes (instead of every other year as default)
				        weekNumbers	:	false			// hides the week numbers
				    });
					</script>
				</td>
			</tr>";

            // All leasing group to change target date when QUEUED
            if ($user->InPermGroup(self::$LEASING_GROUP))
            {
                include_once ('classes/CalendarBase.php');

                $cal = new CalendarBase();
                $next_b_day = date($date_format, $cal->GetNextBusinessDay());

                $inst_date_row = "
			<tr>
				<th class=\"form\" colspan='2'>Target&nbsp;Date</th>
				<td class=\"form\" colspan='4' align=\"left\">
					<input type='hidden' name='next_b_day' value='$next_b_day' />
					<input type='text' name='inst_date' id='inst_date' size='8' value='{$inst_date}' />
					<img class='form_bttn' id='inst_date_trg' src='images/calendar-mini.png' alt='Calendar' title='Calendar'>
					<script type=\"text/javascript\">
				    Calendar.setup({
				        inputField	:	\"inst_date\",		// id of the input field
				        ifFormat	:	\"{$calendar_format}\",	// format of the input field
				        button		:	\"inst_date_trg\",	// trigger for the calendar (button ID)
				        step		:	1,				// show all years in drop-down boxes (instead of every other year as default)
				        weekNumbers	:	false			// hides the week numbers
				    });
					</script>
				</td>
			</tr>";
            }
        }

        # Set shipping method control
        $this->ship_method = ($this->ship_method == "") ? "Ground" : $this->ship_method;
        $ship_method_control = "
		<select name=\"ship_method\">
			<option value=\"\">-Select-</option>";
        $ship_method_control .= self::createShippingMethodList($this->ship_method, null, $this->shipping_company_id);
        $ship_method_control .= "</select>";


        # Create the form items array to match objects items
        $row_class = 'on';
        $order_item = "";
        $matches = array();
        $serial_match = array();
        $prod_sth = $this->dbh->prepare("SELECT id, type_id FROM equipment_models WHERE model = ? AND active IS TRUE");
        $i_count = count($this->items);
        foreach ($this->items as $i => $item)
        {
            # Setup this up to ignore Enter key
            $next_input = "this.form['items[$i][item_lot]']";
            $item_lot_input = "<input type='text' name='items[$i][item_lot]' value='{$item['item_lot']}' onKeyPress=\"return EntertoTab(event, {$next_input});\" size='10' maxlength='64'/>";

            $pic = $this->GetProductImg($item['code'], null, null, 30);

            # Check for equipment id for this code if found add model serial inputs
            $prod_sth->bindValue(1, $item['code'], PDO::PARAM_STR);
            $prod_sth->execute();
            if ($row = $prod_sth->fetch(PDO::FETCH_ASSOC))
            {
                $model_id = $row['id'];
                $serial = $match_serial = "";
                if ($item['asset_id'] > 0)
                {
                    $asset = new LeaseAsset($item['asset_id']);
                    $model_id = $asset->getModel()->getId();
                    $serial = $asset->getSerial();
                }
                else if ($row['type_id'] == EquipmentModel::$ACCESSORY)
                {
                    /* Steps for updating an accessory asset
                     * 1. Get base unit asset id
                     * 2. Get old accessory asset serial number
                     */
                    $id_sql = null;
                    if ($this->type_id == self::$RMA_ORDER)
                        $id_sql = "order_id";
                    else if ($this->type_id == self::$RETURN_ORDER)
                        $id_sql = "return_id";

                    if ($id_sql)
                    {
                        // Step 1 - Get base unit asset id
                        $sth_buai = $this->dbh->query("SELECT la.id
						FROM complaint_form_equipment cfe
						INNER JOIN lease_asset la ON la.model_id = cfe.model AND la.serial_num = cfe.serial_number
						WHERE {$id_sql} = {$item['order_id']}
						LIMIT 1");

                        $base_unit_asset_id = $sth_buai->fetchColumn();

                        // Step 2 - Get old accessory asset serial number
                        $base_unit = new LeaseAsset($base_unit_asset_id);
                        $accessories = $base_unit->getAccessoryUnits();

                        if (isset($accessories[$model_id]))
                        {
                            $accessory = new LeaseAsset($accessories[$model_id]);
                            $serial = $accessory->getSerial();
                        }
                    }
                }

                # No editing devices once shipped
                if ($this->status_id == self::$SHIPPED)
                {
                    $model_input = "<input type='hidden' name='items[$i][model]' value='{$model_id}'>{$item['name']}";
                    $serial_input = "<input type='hidden' name='items[$i][serial_number]' value='{$serial}' />{$serial}";
                }
                else
                {
                    # Set up model and serial inputs as editable
                    if ($this->type_id == self::$CANCELLATION_ORDER)
                    {
                        # Match model+serial otherwise the Nth occurrence of this model
                        if (!isset($serial_match[$model_id]))
                            $serial_match[$model_id] = 1;
                        else
                            $serial_match[$model_id]++;
                        $match_serial = ($serial) ? $serial : 'ANY' . $serial_match[$model_id];

                        $model_options = Forms::createCustomerDeviceList($this->facility_id, $model_id, $match_serial, true);
                        if (preg_match("/selected.*Serial:([\w\(.*)-]+)/", $model_options, $matches))
                        {
                            if (!$serial)
                                $serial = $matches[1];
                        }
                        else if (!preg_match("/selected/", $model_options))
                            $model_options .= "<option value='{$model_id}' selected>{$item['code']} {$item['name']} &nbsp;( NOT FOUND )</option>";
                    }
                    else if (in_array($this->type_id, array(self::$RMA_ORDER, self::$RETURN_ORDER, self::$INSTALL_ORDER, self::$CUSTOMER_ORDER)))
                    {
                        # Cannot change models on these order types
                        $model_options = "<option value='{$model_id}' selected>{$item['code']} {$item['name']}</option>";
                    }
                    else
                    {
                        $model_options = Forms::createEquipmentList($model_id, true);
                    }

                    $model_input = "<select name='items[$i][model]' OnChange='PopulateSerial($i)'>
						{$model_options}
					</select>";

                    # Determine if the serial should be readonly or have confirmation
                    $clear_bttn = $readonly = $confirm = "";
                    if ($serial)
                    {
                        # Allow admin to clear current value
                        if ($user->hasAccessToApplication("orderfilladmin") || $user->InPermGroup(User::$WO_EDIT))
                            $clear_bttn = " <img src='images/cancel.png' class='form_bttn'
							alt='Clear Serial Number' title='Clear Serial Number'
							onclick=\"ClearSerial($i);\" />";

                        # Perform serial confirmation on purchases
                        if ($this->is_purchase)
                        {
                            $confirm = "<input type='hidden' name='items[$i][confirm_serial]' value='{$serial}' />
							<div id='confirm_txt_$i'>Confirm Serial: $serial</div>";
                            $serial = "";
                        }
                        else if (in_array($this->type_id, array(self::$RMA_ORDER, self::$RETURN_ORDER, self::$INSTALL_ORDER)))
                        {
                            $readonly = "readonly";
                        }
                    }

                    # Find the next form element
                    $next_input = ($i + 1 == $i_count) ? "this.form.save_act" : "this.form['items[" . ($i + 1) . "][shipped]']";

                    $placeholder = "";
                    $help = "";
                    if ($item['whse_id'] == "RENO")
                    {
                        $placeholder = "placeholder=' **New Device**'";
                        $help = "<span class='help'>Expecting New OEM device</span";
                    }
                    if ($item['whse_id'] == "REFURB")
                    {
                        $placeholder = "placeholder=' **Refurbished Device**'";
                        $help = "<span class='help'>Expecting Refurbished device</span>";
                    }

                    # Add text input
                    $serial_input = "
					$confirm
					<input type='text' name='items[$i][serial_number]' $placeholder value='{$serial}' size='20' maxlength='64' $readonly onKeyPress=\"return EntertoTab(event, {$next_input});\"/>
					$help $clear_bttn";

                    # Highlight purchased devices
                    if ($this->is_purchase)
                        $row_class = "form_section prod warning";
                }

                $avail_qty = $item['quantity'];
            }
            # Non asset product no inputs needed
            else
            {
                $model_input = $item['name'];
                $serial_input = 'NA';
                /// $item_lot_input = 'NA';
                $avail_qty = $item['quantity'];
            }

            // Show as general info if already shipped
            if ($this->status_id == self::$SHIPPED)
                $ship_qty_input = "<input type='hidden' name='items[$i][shipped]' value='{$item['shipped']}'/>{$item['shipped']}";
            else
            {
                // Check for onhold flag and force backorder for this item.
                $shipped_disabled = "";
                if ($item['onhold'])
                {
                    $item['shipped'] = '0';
                    $shipped_disabled = "class='inactive' disabled";
                }

                $ship_qty_input = "<input $shipped_disabled type='text' name='items[$i][shipped]' value='{$item['shipped']}' size='2' maxlength='4' onFocus=\"if (this.value < 1) this.value='';\"/>";
            }

            $order_item .= "
			<tr class='{$row_class}'>
				<input type='hidden' name='items[$i][order_id]' value ='{$item['order_id']}' />
				<input type='hidden' name='items[$i][contract_id]' value ='{$item['contract_id']}' />
				<input type='hidden' name='items[$i][item_num]' value ='{$item['item_num']}' />
				<input type='hidden' name='items[$i][prod_id]' value ='{$item['prod_id']}' />
				<input type='hidden' name='items[$i][code]' value ='{$item['code']}' />
				<input type='hidden' name='items[$i][lot_required]' value ='{$item['lot_required']}' />
				<input type='hidden' name='items[$i][name]' value ='{$item['name']}' />
				<input type='hidden' name='items[$i][description]' value ='{$item['description']}' />
				<input type='hidden' name='items[$i][asset_id]' value ='{$item['asset_id']}' />
				<input type='hidden' name='items[$i][quantity]' value ='{$item['quantity']}' />
				<input type='hidden' name='items[$i][avail_qty]' value ='{$avail_qty}' />
				<input type='hidden' name='items[$i][swap_asset_id]' value ='{$item['swap_asset_id']}' />
				<input type='hidden' name='items[$i][uom]' value ='{$item['uom']}' />
				<input type='hidden' name='items[$i][price]' value ='{$item['price']}' />
				<input type='hidden' name='items[$i][whse_id]' value ='{$item['whse_id']}' />
				<input type='hidden' name='items[$i][upsell]' value ='{$item['upsell']}' />
				<td align='center' id='qty_$i'>
					{$item['quantity']} {$item['uom']}
				</td>
				<td align='center'>
					{$ship_qty_input}
				</td>
				<td align='center'>
					$pic <br/>
					#{$item['code']}
				</td>
				<td align='left'>
					$model_input
				</td>
				<td align='left'>
					{$item_lot_input}
				</td>
				<td class='field' align='left'>
					$serial_input
				</td>
			</tr>";
            $row_class = ($row_class == 'on') ? 'off' : 'on';
        }

        # Format date
        $order_date = date($date_format, $this->order_date);

        $comments = $comment_txt = $this->comments;
        if ($this->order_group && count($this->order_group) > 1)
        {
            $comment_txt = "";
            $sth = $this->dbh->prepare("SELECT DISTINCT o.id, o.comments
			FROM orders o WHERE o.id IN (" . implode(',', $this->order_group) . ") ORDER BY o.id");
            $sth->execute();
            while (list($oid, $comment) = $sth->fetch(PDO::FETCH_NUM))
            {
                $comment_txt .= ($comment) ? "Order {$oid}: {$comment}</br>" : "";
            }
        }

        $comments_disp = ($comment_txt) ? "" : "style='display:none;'";

        $comments = "
			<tr id='comments_row' {$comments_disp}>
				<th class='subsubheader' colspan='6'>Order Notes</th>
			</tr>
			<tr id='comments_txt' {$comments_disp}>
				<td class='form' colspan='6' align='left'>
					{$comment_txt}
				</td>
			</tr>
			<tr id='comments_input' style='display:none'>
				<td class='form' colspan='6' align='left'>
					<textarea name='comments' rows='3' cols='80' disabled>{$this->comments}</textarea>
				</td>
			</tr>";

        $equipment_link = "";
        if ($this->facility_id > 0 && $user->hasAccessToApplication('facilities'))
            $equipment_link = "&nbsp;&nbsp;&nbsp;
		<a alt='View Equipment' title='View Equipment' onMouseOver=\"this.style.cursor='pointer';\" style='font-size:smaller;'" .
                " onClick=\"window.open('equipment.php?facility_id={$this->facility_id}', '_blank'," .
                " 'width=900,height=700,toolbar=no,scrollbars=yes,resizable=yes');\"/>Equipment</a>";


        # Find other orders which have the same assets
        if ($this->status_id == Order::$QUEUED || $this->status_id == Order::$PROCESSED)
        {
            # There are no equipment conflicts between RMA and Return Orders
            $exclude_clause = "";
            if ($this->type_id == Order::$RMA_ORDER)
                $exclude_clause = "AND o.type_id <> " . Order::$RETURN_ORDER;

            # Find conflicts for queued and processed orders
            $sql = "SELECT i.swap_asset_id, i.order_id, 'Returning'
			FROM order_item i
			INNER JOIN (
				SELECT swap_asset_id
				FROM order_item
				WHERE swap_asset_id > 0
				AND order_id IN ({$display_id})
			) s on i.swap_asset_id = s.swap_asset_id
			INNER JOIN orders o ON i.order_id = o.id
			WHERE (o.status_id = " . Order::$QUEUED . " or o.status_id = " . Order::$PROCESSED . " or o.status_id = " . Order::$EDITING . ")
			AND i.order_id NOT IN ({$display_id})
			UNION
			SELECT i.asset_id, i.order_id, 'Outgoing'
			FROM order_item i
			INNER JOIN (
				SELECT asset_id
				FROM order_item
				WHERE asset_id > 0
				AND order_id IN ({$display_id})
			) s on i.asset_id = s.asset_id
			INNER JOIN orders o ON i.order_id = o.id
			WHERE (o.status_id in (" . Order::$QUEUED . "," . Order::$PROCESSED . "," . Order::$EDITING . "))
			{$exclude_clause}
			AND i.order_id NOT IN ({$display_id})";

            $sth = $this->dbh->query($sql);
            while (list($asset_id, $order_id, $dir) = $sth->fetch(PDO::FETCH_NUM))
            {
                $device = new LeaseAsset($asset_id);
                echo "<p class='error' style='background-color:#E0C0C0;'>
				Multiple Orders for the {$dir} Device!<br />
				Order # {$order_id}, Item: {$device->getModel()->getNumber()}, Serial/Barcode: {$device->getSerial()}</p>";

                # Disable ship option and recreate options list
                if (!$is_admin)
                {
                    $exclude[] = self::$SHIPPED;
                    $status_list = Forms::createOrderStatusList($default, $exclude);
                }
            }
        }


        # If back order already placed do not validate ship qty against order qty
        $check_qty = ($this->back_order) ? 0 : 1;

        $parent_row = "";
        $parent_id = -1;
        if ($this->parent_order)
        {
            $parent_row = "<tr>
			<th class='form' colspan='2'>Reference Order</th>
			<td class='form' colspan='4' align='left'>{$this->parent_order}</td>
		</tr>";
            $parent_id = $this->parent_order;
        }

        $otype_style = "style='text-align:left'";
        if ($this->type_id == self::$RETURN_ORDER)
            $otype_style = "style='text-align:center;font-weight:bold; font-size:12pt; background-color:#E0C0C0;'";

        $chk_third_party = ($this->third_party) ? "checked" : "";

        $carrier_control = "<select name='shipping_company_id'>
				<option value=0>Unspecified</option>";
        $carrier_control .= ShippingCompany::OptionList($this->shipping_company_id, false /*$show_inactive*/ , true /*$show_long_name*/);
        #self::createShippingCompanyList($this->shipping_company_id);
        $carrier_control .= "</select>";

        $status_control = "
		<select name=\"status_id\" onChange='EnableComments()'>
			{$status_list}
		</select>";

        $form = <<<END
		<form name="orders" action="{$_SERVER['PHP_SELF']}" method="post" >
		<input type="hidden" name="act" value="save"/>
		<input type="hidden" name="order_id" value="{$id}"/>
		<input type="hidden" name="parent_id" value="{$parent_id}"/>
		<input type="hidden" name="facility_id" value="{$this->facility_id}"/>
		<input type="hidden" name="print" value="0"/>
		<input type="hidden" name="o_type" value="{$this->type_id}"/>
		<input type="hidden" name="check_qty" value="{$check_qty}"/>
		<input type="hidden" name="load_tstamp" value="{$current_tstamp}"/>
		{$process_err}{$display_special_shiiping_instructions}
		<table class="list" cellpadding="3" cellspacing="1">
			<tr>
				<th class="subheader" colspan="6">Order # {$display_id} {$equipment_link}</th>
			</tr>
			<tr>
				<th class="form" colspan='2'>Type</th>
				<td class="form" colspan='4' {$otype_style}>{$this->type_text}</td>
			</tr>
			{$parent_row}
			<tr>
				<th class="form" colspan='2'>Status</th>
				<td class="form" colspan='4' align="left">
					{$status_control}
				</td>
			</tr>
			{$inst_date_row}
			{$ship_date_row}
			<tr>
				<th class="form" colspan='2'>Outgoing Tracking #</th>
				<td class="form" colspan='4' align="left">
					<input type='text' name='tracking_num' value='{$this->tracking_num}' size='18' maxlength='64' onKeyPress="return EntertoTab(event, this.form.ret_tracking_num});"/>
				</td>
			</tr>
			<tr>
				<th class="form" colspan='2'>Return Tracking #</th>
				<td class="form" colspan='4' align="left">
					<input type='text' name='ret_tracking_num' value='{$this->ret_tracking_num}' size='18' maxlength='64' onKeyPress="return EntertoTab(event, this.form.ship_method);"/>
				</td>
			</tr>
			<tr>
				<th class="form" colspan='2'>Carrier</th>
				<td class="form" colspan='4' align="left">
					{$carrier_control}
				</td>
			</tr>
			<tr>
				<th class="form" colspan='2'>Ship Via</th>
				<td class="form" colspan='4' align="left">
					{$ship_method_control}
					&nbsp;&nbsp;
					<input type='checkbox' name='third_party' value='1' {$chk_third_party}/> Third Party
				</td>
			</tr>
			<tr>
				<th class="form" colspan='2'>Service</th>
				<td class="form" colspan='4' align="left">
					<select name='service_level'>
						{$service_level_options}
					</select>
				</td>
			</tr>
			<tr>
				<th class="subsubheader">QTY</th>
				<th class="subsubheader">Ship</th>
				<th class="subsubheader" colspan=2>Item</th>
				<th class="subsubheader">LOT</th>
				<th class="subsubheader">Serial or Barcode</th>
			</tr>
			{$order_item}
			{$comments}
			<tr>
				<td class="buttons" colspan="6">
					<input type="submit" class="submit" name="save_act" value="Save" alt="Save Order" title="Save Order"
					onclick='if (validateEditForm(this.form)) { this.disabled=true;  this.form.submit(); } else return false;' {$disable_editing}>
					<img class='form_bttn' src='images/print.png'
						onmouseover="GetPackList(event,{$id});"
						onmouseout="HidePackList($id);"
						onClick="PrintPackingList(document.orders);"/>
				</td>
			</tr>
		</table>
		</form>
END;
        return $form;
    }

    /**
     * Display form for updating single order
     *
     */
    public function showAdminEditForm()
    {
        global $date_format, $calendar_format;

        $user = $this->session_user;
        $id = $this->id;
        $display_id = implode(',', $this->order_group);
        $current_tstamp = time();
        $equip_no_serial = "";

        # Get the regular expressions for the serial numbers / barcodes.
        #
        $equipment_regexs = array();
        $equipment_regex_js_array = '';
        $sth = $this->dbh->query('SELECT id, serial_regex FROM equipment_models');
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            if (!is_null($row['serial_regex']))
            {
                $equipment_regexs[$row['id']] = $row['serial_regex'];
                $equipment_regex_js_array .= "equipRegex[\"{$row['id']}\"]=/^{$row['serial_regex']}|(\d{8})\$/i;\n";
            }
        }

        $process_err = '';
        if ($this->update_since_processed)
            $process_err = "<div class='alert alert-danger alert-dismissible ' style='width: 500px; text-align: center; margin: auto;'><h4><i class='icon fa fa-ban'></i> ALERT! ORDER HAS CHANGED, PLEASE REVIEW.</h4></div><br>";

        if ($this->status_id == self::$SHIPPED)
            $process_err = '';

        $has_dock = ($this->has_dock) ? 'Has Dock &nbsp;&nbsp; ' : '';
        $liftgate_required = ($this->requires_liftgate) ? ' &nbsp;&nbsp; Lift Gate Required' : '';
        $inside_delivery_required = ($this->requires_inside_delivery) ? ' &nbsp;&nbsp; Inside Delivery Required' : '';
        $display_special_shiiping_instructions = "";
        if ($liftgate_required || $inside_delivery_required)
            $display_special_shiiping_instructions = "<div class='alert alert-warning alert-dismissible' style='width: 500px; text-align: center; margin: auto;'><h4><i class='icon fa fa-warning'></i> FREIGHT SPECIAL SHIPPING INSTRUCTIONS<br>{$has_dock}{$liftgate_required} &nbsp;&nbsp; {$inside_delivery_required}</h4></div><br>";

        # Print javascript serial number validation vars.
        #
        echo "
        <script type=\"text/javascript\">
        var equipRegex = new Object();
        {$equipment_regex_js_array}
        var equip_no_serial = new Object();
        {$equip_no_serial}

        function Init()
        {
        	GetTNTData($id, {$this->facility_id});
        }
        YAHOO.util.Event.onDOMReady(Init);
        </script>\n";

        # Status options are limited. Admin priviledge allows more options.
        #
        $exclude = array(self::$DELETED, self::$EDITING);
        $is_admin = $user->hasAccessToApplication('orderfilladmin');

        if ($is_admin)
        {
            # Select the current status
            $default = $this->status_id;

            if ($this->status_id == self::$PROCESSED)
                $exclude = array(self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$SHIPPED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$EXCEPTION)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$HOLD)
                $exclude = array(self::$SHIPPED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$CANCELED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$DELETED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$WRITEOFF)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$EDITING, self::$DELETED);
            else if ($this->status_id == self::$EDITING)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$DELETED, self::$WRITEOFF);
            else
                $exclude = array(self::$DELETED, self::$EDITING, self::$WRITEOFF);
        }
        else
        {
            # If order is active default to shipped status (non-admin basic function is to ship orders).
            # Otherwise select the current status. It will not be allowed to be modified.
            #
            $default = ($this->status_id <= self::$SHIPPED) ? self::$SHIPPED : $this->status_id;

            if ($this->status_id == self::$PROCESSED)
                $exclude = array(self::$QUEUED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$SHIPPED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$EXCEPTION)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$HOLD)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$CANCELED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$DELETED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$DELETED)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$EDITING, self::$WRITEOFF);
            else if ($this->status_id == self::$WRITEOFF)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$EDITING, self::$DELETED);
            else if ($this->status_id == self::$EDITING)
                $exclude = array(self::$QUEUED, self::$PROCESSED, self::$SHIPPED, self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$DELETED, self::$WRITEOFF);
            else
                $exclude = array(self::$EXCEPTION, self::$HOLD, self::$CANCELED, self::$DELETED, self::$EDITING, self::$WRITEOFF);
        }

        # Create options list
        $status_list = Forms::createOrderStatusList($default, $exclude);

        # Create options list
        $service_level_options = Forms::createServiceLevelList($this->service_level);

        # If Order is being edited in Supply Order disable changes.
        $disable_editing = ($this->status_id == self::$EDITING) ? "disabled" : "";

        # Format dates
        # Set default ship date to today if the status is processed
        $order_date = date($date_format, $this->order_date);
        $ship_date = ($this->ship_date > 0) ? date($date_format, $this->ship_date) : '';
        $inst_date = ($this->inst_date > 0) ? date($date_format, $this->inst_date) : '';

        $ship_date_row = "";
        $inst_date_row = "";
        if ($this->status_id == Order::$QUEUED)
        {
            $ship_date_row = "
                    <div class='form-group'>
                        <label for='inputShipDate' class='col-sm-4 control-label'>Ship Date</label>

                        <div class='col-sm-6'>
                            <div class='input-group date'>
                                <div class='input-group-addon'>
                                    <i class='fa fa-calendar'></i>
                                </div>
                                <input name='ship_date' id='ship_date' class='form-control pull-right' type='text' value='{$ship_date}' readonly>
                                <script type=\"text/javascript\">
                              	$('#ship_date').datepicker({
                                  autoclose: true
                            	});
            					</script>
                            </div>
                        </div>
                    </div>";

            // All leasing group to change target date when QUEUED
            if ($user->InPermGroup(self::$LEASING_GROUP))
            {
                include_once ('classes/CalendarBase.php');

                $cal = new CalendarBase();
                $next_b_day = date($date_format, $cal->GetNextBusinessDay());

                $inst_date_row = "
                    <div class='form-group'>
                        <label for='inputInstDate' class='col-sm-4 control-label'>Target Date</label>

                        <div class='col-sm-6'>
                            <div class='input-group date'>
                                <div class='input-group-addon'>
                                    <i class='fa fa-calendar'></i>
                                </div>
                                <input type='hidden' name='next_b_day' value='$next_b_day' />
                                <input name='inst_date' id='inst_date' class='form-control pull-right' type='text' value='{$inst_date}' readonly>
                                <script type=\"text/javascript\">
            				    $('#inst_date').datepicker({
                                  autoclose: true
                                });
            					</script>
                            </div>
                        </div>
                    </div>";
            }
        }

        # Set shipping method control
        $this->ship_method = ($this->ship_method == "") ? "Ground" : $this->ship_method;
        $ship_method_control = "
        		<select class='form-control' name=\"ship_method\">
        			<option value=\"\">-Select-</option>";
        $ship_method_control .= self::createShippingMethodList($this->ship_method, null, $this->shipping_company_id);
        $ship_method_control .= "</select>";


        # Create the form items array to match objects items
        $row_class = 'on';
        $order_item = "";
        $matches = array();
        $serial_match = array();
        $prod_sth = $this->dbh->prepare("SELECT id, type_id FROM equipment_models WHERE model = ? AND active IS TRUE");
        $i_count = count($this->items);
        foreach ($this->items as $i => $item)
        {
            # Setup this up to ignore Enter key
            $next_input = "this.form['items[$i][item_lot]']";
            $item_lot_input = "<input type='text' name='items[$i][item_lot]' value='{$item['item_lot']}' onKeyPress=\"return EntertoTab(event, {$next_input});\" size='10' maxlength='64'/>";

            $pic = $this->GetProductImg($item['code'], null, null, 30);

            # Check for equipment id for this code if found add model serial inputs
            $prod_sth->bindValue(1, $item['code'], PDO::PARAM_STR);
            $prod_sth->execute();
            if ($row = $prod_sth->fetch(PDO::FETCH_ASSOC))
            {
                $model_id = $row['id'];
                $serial = $match_serial = "";

                if ($item['asset_id'] > 0)
                {
                    $asset = new LeaseAsset($item['asset_id']);
                    $model_id = $asset->getModel()->getId();
                    $serial = $asset->getSerial();

                }
                else if ($row['type_id'] == EquipmentModel::$ACCESSORY)
                {
                    /* Steps for updating an accessory asset
                     * 1. Get base unit asset id
                     * 2. Get old accessory asset serial number
                     */
                    $id_sql = null;
                    if ($this->type_id == self::$RMA_ORDER)
                        $id_sql = "order_id";
                    else if ($this->type_id == self::$RETURN_ORDER)
                        $id_sql = "return_id";

                    if ($id_sql)
                    {
                        // Step 1 - Get base unit asset id
                        $sth_buai = $this->dbh->query("SELECT la.id
        						FROM complaint_form_equipment cfe
        						INNER JOIN lease_asset la ON la.model_id = cfe.model AND la.serial_num = cfe.serial_number
        						WHERE {$id_sql} = {$item['order_id']}
        						LIMIT 1");

                        $base_unit_asset_id = $sth_buai->fetchColumn();

                        // Step 2 - Get old accessory asset serial number
                        $base_unit = new LeaseAsset($base_unit_asset_id);
                        $accessories = $base_unit->getAccessoryUnits();

                        if (isset($accessories[$model_id]))
                        {
                            $accessory = new LeaseAsset($accessories[$model_id]);
                            $serial = $accessory->getSerial();
                        }
                    }
                }

                # No editing devices once shipped
                if ($this->status_id == self::$SHIPPED)
                {
                    $model_input = "<input type='hidden' name='items[$i][model]' value='{$model_id}'>{$item['name']}";
                    $serial_input = "<input type='hidden' name='items[$i][serial_number]' value='{$serial}' />{$serial}";
                }
                else
                {
                    # Set up model and serial inputs as editable
                    if ($this->type_id == self::$CANCELLATION_ORDER)
                    {
                        # Match model+serial otherwise the Nth occurrence of this model
                        if (!isset($serial_match[$model_id]))
                            $serial_match[$model_id] = 1;
                        else
                            $serial_match[$model_id]++;
                        $match_serial = ($serial) ? $serial : 'ANY' . $serial_match[$model_id];

                        $model_options = Forms::createCustomerDeviceList($this->facility_id, $model_id, $match_serial, true);
                        if (preg_match("/selected.*Serial:([\w\(.*)-]+)/", $model_options, $matches))
                        {
                            if (!$serial)
                                $serial = $matches[1];
                        }
                        else if (!preg_match("/selected/", $model_options))
                            $model_options .= "<option value='{$model_id}' selected>{$item['code']} {$item['name']} &nbsp;( NOT FOUND )</option>";
                    }
                    else if (in_array($this->type_id, array(self::$RMA_ORDER, self::$RETURN_ORDER, self::$INSTALL_ORDER, self::$CUSTOMER_ORDER)))
                    {
                        # Cannot change models on these order types
                        $model_options = "<option value='{$model_id}' selected>{$item['code']} {$item['name']}</option>";
                    }
                    else
                    {
                        $model_options = Forms::createEquipmentList($model_id, true);
                    }

                    $model_input = "<select name='items[$i][model]' OnChange='PopulateSerial($i)'>
        						{$model_options}
        					</select>";

                    # Determine if the serial should be readonly or have confirmation
                    $clear_bttn = $readonly = $confirm = "";
                    if ($serial)
                    {
                        # Allow admin to clear current value
                        if ($user->hasAccessToApplication("orderfilladmin"))
                            $clear_bttn = " <img src='images/cancel.png' class='form_bttn'
        							alt='Clear Serial Number' title='Clear Serial Number'
        							onclick=\"ClearSerial($i);\" />";

                        # Perform serial confirmation on purchases
                        if ($this->is_purchase)
                        {
                            $confirm = "<input type='hidden' name='items[$i][confirm_serial]' value='{$serial}' />
        							<div id='confirm_txt_$i'>Confirm Serial: $serial</div>";
                            $serial = "";
                        }
                        else if (in_array($this->type_id, array(self::$RMA_ORDER, self::$RETURN_ORDER, self::$INSTALL_ORDER)))
                        {
                            $readonly = "readonly";
                        }
                    }

                    # Find the next form element
                    $next_input = ($i + 1 == $i_count) ? "this.form.save_act" : "this.form['items[" . ($i + 1) . "][shipped]']";

                    # Add text input
                    $serial_input = "
        					$confirm
        					<input type='text' name='items[$i][serial_number]' value='{$serial}' size='20' maxlength='64' $readonly onKeyPress=\"return EntertoTab(event, {$next_input});\"/>
        					$clear_bttn";

                    # Highlight purchased devices
                    if ($this->is_purchase)
                        $row_class = "form_section prod warning";
                }

                $avail_qty = $item['quantity'];
            }
            # Non asset product no inputs needed
            else
            {
                $model_input = $item['name'];
                $serial_input = 'NA';
                $avail_qty = $item['quantity'];
            }

            $ship_qty_input = "<input type='text' name='items[$i][shipped]' value='{$item['shipped']}' size='2' maxlength='4' onFocus=\"if (this.value < 1) this.value='';\"/>";
            if ($this->status_id == self::$SHIPPED)
                $ship_qty_input = "<input type='hidden' name='items[$i][shipped]' value='{$item['shipped']}'/>{$item['shipped']}";

            $order_item .= "
        			<tr class='{$row_class}'>
        				<input type='hidden' name='items[$i][order_id]' value ='{$item['order_id']}' />
        				<input type='hidden' name='items[$i][contract_id]' value ='{$item['contract_id']}' />
        				<input type='hidden' name='items[$i][item_num]' value ='{$item['item_num']}' />
        				<input type='hidden' name='items[$i][prod_id]' value ='{$item['prod_id']}' />
        				<input type='hidden' name='items[$i][code]' value ='{$item['code']}' />
        				<input type='hidden' name='items[$i][name]' value ='{$item['name']}' />
        				<input type='hidden' name='items[$i][description]' value ='{$item['description']}' />
        				<input type='hidden' name='items[$i][asset_id]' value ='{$item['asset_id']}' />
        				<input type='hidden' name='items[$i][quantity]' value ='{$item['quantity']}' />
        				<input type='hidden' name='items[$i][avail_qty]' value ='{$avail_qty}' />
        				<input type='hidden' name='items[$i][swap_asset_id]' value ='{$item['swap_asset_id']}' />
        				<input type='hidden' name='items[$i][uom]' value ='{$item['uom']}' />
        				<input type='hidden' name='items[$i][price]' value ='{$item['price']}' />
        				<input type='hidden' name='items[$i][whse_id]' value ='{$item['whse_id']}' />
        				<input type='hidden' name='items[$i][upsell]' value ='{$item['upsell']}' />
        				<td align='center' id='qty_$i'>
        					{$item['quantity']} {$item['uom']}
        				</td>
        				<td align='center'>
        					{$ship_qty_input}
        				</td>
        				<td align='center'>
        					$pic <br/>
        					#{$item['code']}
        				</td>
        				<td align='left'>
        					$model_input
        				</td>
					<td align='left'>
						$item_lot_input
					</td>
        				<td align='left'>
        					$serial_input
        				</td>
        			</tr>";
            $row_class = ($row_class == 'on') ? 'off' : 'on';
        }

        # Hide return tracking number if nothing is inbound
        # Next input is ret_tracking_num or ship method
        $return_style = "";
        $after_tracking = "this.form.ret_tracking_num";
        if (!$this->in_asset && !$this->in_return)
        {
            $return_style = "style='display:none'";
            $after_tracking = "this.form.ship_method";
        }

        # Format date
        $order_date = date($date_format, $this->order_date);

        $comments = $comment_txt = $this->comments;
        if ($this->order_group && count($this->order_group) > 1)
        {
            $comment_txt = "";
            $sth = $this->dbh->prepare("SELECT DISTINCT o.id, o.comments
        			FROM orders o WHERE o.id IN (" . implode(',', $this->order_group) . ") ORDER BY o.id");
            $sth->execute();
            while (list($oid, $comment) = $sth->fetch(PDO::FETCH_NUM))
            {
                $comment_txt .= ($comment) ? "Order {$oid}: {$comment}</br>" : "";
            }
        }

        $comments_disp = ($comment_txt) ? "" : "style='display:none;'";

        $comments = "
			<tr id='comments_row' {$comments_disp}>
				<th class='subsubheader' colspan='6'>Order Notes</th>
			</tr>
			<tr id='comments_txt' {$comments_disp}>
				<td class='form' colspan='6' align='left'>
					{$comment_txt}
				</td>
			</tr>
			<tr id='comments_input' style='display:none'>
				<td class='form' colspan='6' align='left'>
					<textarea name='comments' rows='3' cols='80' disabled>{$this->comments}</textarea>
				</td>
			</tr>";

        $equipment_link = "";
        if ($this->facility_id > 0 && $user->hasAccessToApplication('facilities'))
            $equipment_link = "&nbsp;&nbsp;&nbsp;
        		<a class='btn btn-primary' alt='View Equipment' title='View Equipment' onMouseOver=\"this.style.cursor='pointer';\" style='font-size:smaller;'" .
                " onClick=\"window.open('equipment.php?facility_id={$this->facility_id}', '_blank'," .
                " 'width=900,height=700,toolbar=no,scrollbars=yes,resizable=yes');\"/>Equipment</a>";


        # Find other orders which have the same assets
        if ($this->status_id == Order::$QUEUED || $this->status_id == Order::$PROCESSED)
        {
            # There are no equipment conflicts between RMA and Return Orders
            $exclude_clause = "";
            if ($this->type_id == Order::$RMA_ORDER)
                $exclude_clause = "AND o.type_id <> " . Order::$RETURN_ORDER;

            # Find conflicts for queued and processed orders
            $sql = "SELECT i.swap_asset_id, i.order_id, 'Returning'
    			FROM order_item i
    			INNER JOIN (
    				SELECT swap_asset_id
    				FROM order_item
    				WHERE swap_asset_id > 0
    				AND order_id IN ({$display_id})
    			) s on i.swap_asset_id = s.swap_asset_id
    			INNER JOIN orders o ON i.order_id = o.id
    			WHERE (o.status_id = " . Order::$QUEUED . " or o.status_id = " . Order::$PROCESSED . " or o.status_id = " . Order::$EDITING . ")
    			AND i.order_id NOT IN ({$display_id})
    			UNION
    			SELECT i.asset_id, i.order_id, 'Outgoing'
    			FROM order_item i
    			INNER JOIN (
    				SELECT asset_id
    				FROM order_item
    				WHERE asset_id > 0
    				AND order_id IN ({$display_id})
    			) s on i.asset_id = s.asset_id
    			INNER JOIN orders o ON i.order_id = o.id
    			WHERE (o.status_id in (" . Order::$QUEUED . "," . Order::$PROCESSED . "," . Order::$EDITING . "))
    			{$exclude_clause}
    			AND i.order_id NOT IN ({$display_id})";

            $sth = $this->dbh->query($sql);
            while (list($asset_id, $order_id, $dir) = $sth->fetch(PDO::FETCH_NUM))
            {
                $device = new LeaseAsset($asset_id);
                echo "<p class='error' style='background-color:#E0C0C0;'>
    				Multiple Orders for the {$dir} Device!<br />
    				Order # {$order_id}, Item: {$device->getModel()->getNumber()}, Serial/Barcode: {$device->getSerial()}</p>";

                # Disable ship option and recreate options list
                if (!$is_admin)
                {
                    $exclude[] = self::$SHIPPED;
                    $status_list = Forms::createOrderStatusList($default, $exclude);
                }
            }
        }


        # If back order already placed do not validate ship qty against order qty
        $check_qty = ($this->back_order) ? 0 : 1;

        $parent_row = "";
        $parent_id = -1;
        if ($this->parent_order)
        {
            $parent_row = "<div class='form-group'>
                                    <label for='inputReferenceOrder' class='col-sm-4 control-label'>Reference Order</label>
                                    <div class='col-sm-6'>
                                        <input class='form-control' type='text' value='{$this->parent_order}' size='5'/>
                                    </div>
                                </div>";
            $parent_id = $this->parent_order;
        }

        $otype_style = "style='text-align:left'";
        if ($this->type_id == self::$RETURN_ORDER)
            $otype_style = "style='text-align:center;font-weight:bold; font-size:12pt; background-color:#E0C0C0;'";

        $chk_third_party = ($this->third_party) ? "checked" : "";

        $carrier_control = "<select class='form-control' name='shipping_company_id'>
	<option value=0>Unspecified</option>";
        $carrier_control .= ShippingCompany::OptionList($this->shipping_company_id, false /*$show_inactive*/ , true /*$show_long_name*/);
        #self::createShippingCompanyList($this->shipping_company_id);
        $carrier_control .= "</select>";

        $status_control = "
                <select class='form-control' name=\"status_id\" onChange='EnableComments()'>
                	{$status_list}
                </select>";

        $form = <<<END

		{$process_err}{$display_special_shiiping_instructions}
            <div class="col-md-8 col-md-offset-2">
            <div class="box box-primary bg-gray-light">
              <div class="box-header with-border">
                <i class="fa fa-file-archive-o"></i>

                <h3 class="box-title">{$display_id}</h3>
                <div class="pull-right">
                    {$equipment_link}
                </div>
              </div>
              <!-- /.box-header -->
              <div class="box-body">
        		<form class="form-horizontal" name="orders" action="{$_SERVER['PHP_SELF']}" method="post" >
                	<input type="hidden" name="act" value="save"/>
                	<input type="hidden" name="order_id" value="{$id}"/>
                	<input type="hidden" name="parent_id" value="{$parent_id}"/>
                	<input type="hidden" name="facility_id" value="{$this->facility_id}"/>
                	<input type="hidden" name="print" value="0"/>
                	<input type="hidden" name="o_type" value="{$this->type_id}"/>
                	<input type="hidden" name="check_qty" value="{$check_qty}"/>
                	<input type="hidden" name="load_tstamp" value="{$current_tstamp}"/>
                    <div class="form-group">
                        <label for="inputOrderNo" class="col-sm-4 control-label">Order #</label>

                        <div class="col-sm-6">
                            <input class="form-control" type="text" value="{$display_id}" size="5" disabled/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputType" class="col-sm-4 control-label">Type</label>

                        <div class="col-sm-6">
                            <input class="form-control" type="text" value="{$this->type_text}" size="5" disabled {$otype_style}/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputStatus" class="col-sm-4 control-label">Status</label>

                        <div class="col-sm-6">
                            {$status_control}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="inputOutgoingTrackingNo" class="col-sm-4 control-label">Outgoing Tracking #</label>

                        <div class="col-sm-6">
                			<input type='text' class='form-control' name='tracking_num' value='{$this->tracking_num}' onKeyPress="return EntertoTab(event, {$after_tracking});"/>
                        </div>
                    </div>
                    {$inst_date_row}
			        {$ship_date_row}
                    <div class="form-group">
                        <label for="inputCarrier" class="col-sm-4 control-label">Carrier</label>

                        <div class="col-sm-6">
                            {$carrier_control}
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputOutgoingTrackingNo" class="col-sm-4 control-label">Ship Via</label>

                        <div class="col-sm-6">
                            {$ship_method_control}
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputThirdParty" class="col-sm-4 control-label">Third Party</label>

                        <div class="col-sm-6">
                            <input type='checkbox' name='third_party' value='1' {$chk_third_party}/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inputService" class="col-sm-4 control-label">Service</label>

                        <div class="col-sm-6">
                            <select class='form-control' name='service_level'>
            					{$service_level_options}
            				</select>
                        </div>
                    </div>
                <div class="box box-default">
              <div class="box-header with-border">
                <i class="fa fa-list-alt"></i>

                <h3 class="box-title">Items</h3>
              </div>
              <!-- /.box-header -->
              <div class="box-body">
                	<table class="table table-striped" cellpadding="3" cellspacing="1" border=0>
                		<thead>
                			<th>QTY</th>
                			<th>Ship</th>
                			<th>Item</th>
                			<th>Model</th>
                			<th>Lot</th>
                			<th>Serial or Barcode</th>
                		</thead>
                        <tbody>
                    		{$order_item}
                    		{$comments}
                        </tbody>
                	</table>
                </div>
                <!-- /.box-body -->
            </div>
            <!-- /.box -->
           </div>
            <!-- /.box-body -->
            <div class="box-footer bg-gray-light">
              <div class='pull-right'>
                <input type="submit" class="btn submit" name="save_act" value="Save" alt="Save Order" title="Save Order"
				onclick='if (validateEditForm(this.form)) { this.disabled=true;  this.form.submit(); } else return false;' {$disable_editing}>
				<button class='btn btn-default'
					onmouseover="GetPackList(event,{$id});"
					onmouseout="HidePackList($id);"
					onClick="PrintPackingList(document.orders);"><i class='fa fa-print'></i></button>
              </div>
            </div>
            <!-- /.box-footer -->
          </div>
        </form>
    </div>
    <!-- /.box -->
</div>
END;
        return $form;
    }

    /**
     * Compare funtion to sort items by bin location.
     */
    public static function compare_bin($a, $b)
    {
        return strnatcmp($a['bin'], $b['bin']);
    }

    /**
     * Prints the packing list.
     */
    public function showPackingList($fullpage = true, $start_printing = true, $show_barcode = false, $orderItems = array())
    {
        $dbh = DataStor::GetHandle();

        $products = array();

        $ship_method = $this->ship_method;

        if ($this->status_id != self::$SHIPPED && is_array($orderItems))
        {
            if (isset($orderItems["items"]))
            {
                $ship_method = empty($orderItems["shipVia"]) ? $this->ship_method : $orderItems["shipVia"];

                $assetSearchSQL = "SELECT serial_num
				FROM lease_asset_status las
				JOIN equipment_models em on em.id = las.model_id
				WHERE length(barcode) > 0 AND barcode = ?";
                $asset_sth = $dbh->prepare($assetSearchSQL);

                foreach ($orderItems["items"] as $key => $product)
                {
                    $products[$product["item_num"]]["shipped"] = (int) $product["shipped"];

                    if (isset($product["serial_number"]))
                    {
                        $asset_sth->bindValue(1, $product["serial_number"], PDO::PARAM_STR);
                        $asset_sth->execute();
                        if ($serial_num = $asset_sth->fetchColumn())
                            $products[$product["item_num"]]["serial_number"] = $serial_num;
                    }
                }
            }
        }

        $order_id = implode(',', $this->order_group);
        $barcode = min($this->order_group);

        $today = date('m/d/Y');
        $ship_date = $this->ship_date ? date('m/d/Y', $this->ship_date) : '';
        $inst_date = $this->inst_date ? date('m/d/Y', $this->inst_date) : '';

        $ship_to_name = ($this->sname) ? "<br/>{$this->sname}" : "<br/>{$this->facility_name}";

        $ship_attn = ($this->ship_attn) ? "<br/>{$this->ship_attn}" : '';

        # Take a short cut and never show shipto name on packing list
        if ($this->cust_entity_type == CustomerEntity::$ENTITY_PATIENT)
            $ship_to_name = "<span class='hippa'>{$ship_to_name}</span>";

        $address = "<br/>" . $this->address;
        if ($this->address2)
            $address .= "<br>{$this->address2}";
        if ($this->address3)
            $address .= "<br>{$this->address3}";
        if ($this->address4)
            $address .= "<br>{$this->address4}";
        if ($this->address5)
            $address .= "<br>{$this->address5}";

        $country_output = "";
        if (isset($this->country) && !in_array(strtolower($this->country), array("", "us", "usa")))
            $country_output = $this->country;

        # Order placed by the facility
        if ($this->facility_name)
        {
            $customer = new CustomerEntity($this->facility_id);

            $order_by = "<span class='hippa'>{$this->facility_name}</span>";
            $order_by .= "<br>Phone: {$customer->getPhone()}";
        }
        # Order placed by CPM
        else
            $order_by = $this->cpt;

        $CO = $this->CompanyId();
        $WHSE = ($CO == self::$ININC_ID) ? self::$ININC_WHSE : self::$PURCHASE_WHSE;

        $item_array_list = array();
        $item_list = "";
        $return_items = "";
        foreach ($this->items as $prod_row)
        {
            # Filter based on whse_id HERE
            #if ($prod_row['whse_id'])
            #	continue;

            $unit_type = 'base_unit';
            $bin = '';
            $zone = '';
            $sql = "SELECT em.type_id
			FROM products p
			INNER JOIN equipment_models em ON em.model = p.code
			WHERE p.id = {$prod_row['prod_id']}";
            $sth = $this->dbh->query($sql);
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                if ($row['type_id'] == 2)
                    $unit_type = "accessory";
            }

            $sql = "SELECT
					l.prod_id, l.warehouse_bin, z.zone
				FROM inventory_location l
				INNER JOIN warehouse w ON l.warehouse_id = w.id AND w.abbr = '$WHSE'
				INNER JOIN warehouse_bins b ON l.warehouse_bin_id = b.id
				LEFT JOIN warehouse_zone z ON b.warehouse_zone_id = z.id
				WHERE w.company_id = '$CO'
				AND l.prod_id = {$prod_row['prod_id']}
				ORDER BY l.preferred_location
				LIMIT 1";
            $sth = $this->dbh->query($sql);
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $bin = $row['warehouse_bin'];
                $zone = $row['zone'];
            }

            $serial_num = isset($products[$prod_row["item_num"]]["serial_number"]) ? $products[$prod_row["item_num"]]["serial_number"] : "";

            # From the model_id of the item
            if ($prod_row['asset_id'] > 0)
            {
                $sth = $this->dbh->query("SELECT serial_num FROM lease_asset WHERE id = {$prod_row['asset_id']}");
                list($serial_num) = $sth->fetch(PDO::FETCH_NUM);
            }
            else if ($unit_type == 'accessory')
            {
                /* Steps for updating an accessory asset
                 * 1. Get base unit asset id
                 * 2. Get old accessory asset serial number
                 */

                $id_sql = ($this->type_id == self::$RMA_ORDER) ? "order_id" : (($this->type_id == self::$RETURN_ORDER) ? "return_id" : null);

                $prod_sth = $this->dbh->query("SELECT id FROM equipment_models WHERE model = '{$prod_row['code']}' AND active IS TRUE");
                $model_id = $prod_sth->fetchColumn();

                if ($id_sql && $id_sql != '' && $model_id)
                {
                    // Step 1 - Get base unit asset id
                    $sth_buai = $this->dbh->query("SELECT
						la.id
					FROM complaint_form_equipment cfe
					INNER JOIN lease_asset la ON la.model_id = cfe.model
					AND la.serial_num = cfe.serial_number
					WHERE {$id_sql} = {$prod_row['order_id']}
					LIMIT 1");

                    $base_unit_asset_id = $sth_buai->fetchColumn();

                    // Step 2 - Get old accessory asset serial number
                    $base_unit = new LeaseAsset($base_unit_asset_id);
                    $accessories = $base_unit->getAccessoryUnits();

                    if (isset($accessories[$model_id]))
                    {
                        $accessory = new LeaseAsset($accessories[$model_id]);
                        $serial_num = $accessory->getSerial();
                    }
                }
            }

            # Show the return device
            if ($prod_row['swap_asset_id'] > 0)
            {
                $sth = $this->dbh->query("SELECT e.model, a.serial_num
				FROM lease_asset a
				INNER JOIN equipment_models e ON a.model_id = e.id
				WHERE a.id = {$prod_row['swap_asset_id']}");
                list($m, $s) = $sth->fetch(PDO::FETCH_NUM);
                $return_items .= "<div style=\"background-color: white; text-align: left;\">Return Model: {$m}, Serial: {$s}</div>";

            }

            if ($this->in_asset)
            {
                if ($this->type_id == self::$CANCELLATION_ORDER)
                    $ship_field = "RAB and Box only";
                else
                {
                    if ($this->send_rab)
                        $ship_field = "RAB only";
                    else if ($this->send_box)
                        $ship_field = "RAB and Box only";
                    else if ($this->send_ect)
                        $ship_field = "Electronic Call Tag";
                    else
                        $ship_field = "None";
                }
                $bo_field = "";
            }
            else
            {
                # Dont show null or 0 ship qty unless order is shipped
                $formShipField = ($products != array() && is_int($products[$prod_row["item_num"]]["shipped"]) && $products[$prod_row["item_num"]]["shipped"] > 0) ? $products[$prod_row["item_num"]]["shipped"] : '';
                $ship_field = (is_int($prod_row['shipped']) && ($prod_row['shipped'] > 0 || $this->status_id == self::$SHIPPED)) ? $prod_row['shipped'] : $formShipField;
                $bo_field = (is_int($ship_field) && $prod_row['quantity'] > $ship_field) ? $prod_row['quantity'] - $ship_field : "";
            }

            $item_array = array
            (
                'quantity' => $prod_row['quantity'],
                'uom' => $prod_row['uom'],
                'ship_field' => $ship_field,
                'zone' => $zone,
                'bin' => $bin,
                'code' => $prod_row['code'],
                'item_lot' => $prod_row['item_lot'],
                'name' => $prod_row['name'],
                'serial_num' => $serial_num,
                'bo_field' => $bo_field
            );
            array_push($item_array_list, $item_array);
        }

        // Sort items by bin location
        uasort($item_array_list, 'Order::compare_bin');

        foreach ($item_array_list as $item)
        {
            $item_list .= "
			<tr>
				<td style=\"text-align: center;\">{$item['quantity']} {$item['uom']}</td>
				<td style=\"text-align: center;\">{$item['ship_field']}</td>
				<td style=\"text-align: center;\">{$item['zone']}</td>
				<td style=\"text-align: center;\">{$item['bin']}</td>
				<td>{$item['code']}</td>
				<td>{$item['item_lot']}</td>
				<td>{$item['name']}</td>
				<td>{$item['serial_num']}</td>
				<td style=\"text-align: center;\">{$item['bo_field']}</td>
			</tr>";
        }

        $internal_comments = $comments = "";

        if ($this->order_group && count($this->order_group) > 1)
        {
            $sth = $this->dbh->prepare("SELECT DISTINCT o.id, o.comments, cf.internal_comments
			FROM orders o
			LEFT JOIN complaint_form_equipment cf ON o.id = cf.order_id
			WHERE o.id IN (" . implode(',', $this->order_group) . ")
			ORDER BY o.id");
            $sth->execute();
            while (list($oid, $comment, $internal) = $sth->fetch(PDO::FETCH_NUM))
            {
                $internal_comments .= ($internal) ? "Order {$oid}: {$internal}</br>" : "";
                $comments .= ($comment) ? "Order {$oid}: {$comment}</br>" : "";
            }
        }
        else
        {
            $comments = $this->comments;
            $internal_comments = $this->internal_comments;
        }

        $parent_row = "";
        if ($this->parent_order)
            $parent_row = "<tr><td colspan='2' style='text-align: center'><b>B/O</b> (Ref. {$this->parent_order})</td>";

        $packing_list = "";

        $table_attr = ($fullpage) ? 'cellpadding="5" cellspacing="1"' : 'cellpadding="1" cellspacing="1"';

        $load_printing = ($start_printing) ? "<body onLoad='window.print();'>" : '';

        if ($fullpage)
            $packing_list = <<<END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Packing List</title>
	<style type="text/css">
		body {
			font-size: 80%;
			margin-top: 0;
		}
		table
		{
			border-style: solid;
			border-color: black;
			border-collapse: collapse;
		}
		th, td
		{
			border-color: black;
		}
		.hippa { display: none; }
	</style>
	<style type="text/css" media="print">
		.hippa { display: block; }
		.comments { display: none; }
	</style>
</head>
$load_printing
END;

        $packing_list .= <<<END
	<div>
	    <h1 style="float: right;">PACKING LIST</h1>
    	<div style="float: left; text-align: left;">
	    	{$this->CompanyName()}<br>
	4999 Aircenter Circle Ste 103<br/>
	Reno, NV 89502

	    </div>
    	<table {$table_attr} border="1" style="clear: both; float: right;">
	    	<tr>
    		    <th>Date</th>
		    <th>Order No.</th>
		    <th>PO No.</th>
		</tr>
	    	<tr>
    		    <td>{$today}</td>
		    <td style="text-align: center;">{$order_id}</td>
		    <td style="text-align: center;">{$this->po_number}</td>
	        </tr>
	    	{$parent_row}
	    </table>
	</div>

	<table {$table_attr} border="1" style="clear: both; float: left; margin-top: 15px; margin-left: 0; width: 40%;">
		<tr>
			<th>ORDERED BY</th>
		</tr>
		<tr>
			<td style="height:7em">
				{$order_by}
			</td>
		</tr>
	</table>

	<table {$table_attr} border="1" style="float: right; margin-top: 15px; margin-right: 0; width: 40%;">
		<tr>
			<th>SHIP TO</th>
		</tr>
		<tr>
			<td style="font-weight: bold; height: 7em;">
				{$ship_to_name}
				{$ship_attn}
				{$address}<br>
				{$this->city}, {$this->state} {$this->zip}<br>
				{$country_output}<br>
			</td>
		</tr>
	</table>

	<table {$table_attr} border="1" style="clear: both; margin-top: 15px; margin-left: 0; text-align: center; width: 100%;">
		<tr>
			<th style="width: 17%;">SHIP DATE</th>
			<th style="width: 17%;">SHIP VIA</th>
			<th style="width: 17%;">DELIVERY DATE</th>
			<th style="width: 16%;">CUST ID</th>
			<th style="width: 17%;">CPM</th>
			<th style="width: 16%;">REP</th>
		</tr>
		<tr>
			<td>{$ship_date}</td>
			<td>{$ship_method}</td>
			<td>{$inst_date}</td>
			<td>{$this->cust_id}</td>
			<td>{$this->cpm}</td>
			<td>{$this->corp_parent}</td>
		</tr>
	</table>

	<table {$table_attr} border="1" style="clear: both; margin-top: 15px; margin-left: 0; width:100%;">
		<tr>
			<th style="width: 15%;">QTY</th>
			<th style="width: 15%;">SHIP</th>
			<th style="width: 1%;">ZONE</th>
			<th style="width: 1%;">LOCATION</th>
			<th style="width: 20%;">ITEM NO.</th>
			<th style="width: 10%;">LOT</th>
			<th>DESCRIPTION</th>
			<th>SERIAL</th>
			<th>B/O</th>
		</tr>
		{$item_list}
	</table>
END;

        if ($comments != '')
        {
            $packing_list .= <<<END
			<table cellpadding="5" cellspacing="1" border="1"
			       style="clear: both; margin-top: 15px; margin-left: 0; width: 100%;">
				<tr>
					<th>ORDER NOTES</th>
				</tr>
				<tr>
					<td>{$comments}</td>
				</tr>
			</table>
END;
        }

        if ($internal_comments != '')
        {
            $packing_list .= <<<END
			<table cellpadding="5" cellspacing="1" class="comments" border="1"
			       style="clear: both; margin-top: 15px; margin-left: 0; width: 100%;">
				<tr>
					<th>Internal Comments</th>
				</tr>
				<tr>
					<td>{$internal_comments}</td>
				</tr>
			</table>
END;
        }

        $dssi = "";
        if ($this->type_id == self::$DSSI_ORDER)
            $packing_list .= "<br/><div class=\"comments\" style=\"text-align: left;\"><b>DSSI Order</b></div>";

        if ($this->id_web_order)
            $packing_list .= "<br /><div class=\"comments\" style=\"text-align: left;\">Web Ref. No.: {$this->id_web_order}</div>";

        if (strlen($this->tracking_num) > 2)
        {
            $packing_list .= "<br /><div style=\"text-align: left;\">Tracking #: {$this->tracking_num}";
            if (strlen($this->ret_tracking_num) > 2)
                $packing_list .= "&nbsp;&nbsp;&nbsp; Return Tracking #: {$this->ret_tracking_num}";
            $packing_list .= "</div>";
        }

        if ($this->service_level == self::$WHITE_GLOVE)
            $packing_list .= "<br /><div class=\"comments\" style=\"text-align: left;\">Service Option: <b>{$this->service_level_text}</b></div>";

        if ($this->in_return)
            $packing_list .= "<br /><div style=\"text-align: left;\">Swap Initiated By: {$this->cpt}</div>";

        if ($this->shipper)
            $packing_list .= "<br /><div class=\"comments\" style=\"text-align: left;\">Shipped By: {$this->shipper}</div>";

        if ($return_items)
            $packing_list .= $return_items;

        if ($fullpage || $show_barcode)
            $packing_list .= <<<END
	<table cellpadding="1" cellspacing="1" border="0" style="border: 0; clear: both; float: right;">
		<tr><td align='center'>
			<span style='font-family: barcode3of9; font-size: 32pt;'>*{$barcode}*</span>
			<br/>{$barcode}
			<br/><br/>
			<span style='font-family: barcode3of9; font-size: 32pt;'>*A{$barcode}*</span>
			<br/>DS: A{$barcode}
		</td></tr>
	</table>
END;

        if ($fullpage)
            $packing_list .= "</body>\n	</html>";

        return $packing_list;
    }

    /**
     * Use id to find 900 series facility id
     *
     * @param integer $user_id
     * @return integer the facility id
     */
    public static function LookupCPMFacility($user_id)
    {
        $facility_id = null;

        if ($user_id > 0)
        {
            $dbh = DataStor::getHandle();

            $sql = "SELECT facility_id FROM user_to_facility
			WHERE user_id = ?";
            $sth = $dbh->prepare($sql);
            $sth->bindValue(1, $user_id, PDO::PARAM_INT);
            $sth->execute();
            if ($sth->rowCount() == 1)
            {
                list($facility_id) = $sth->fetch(PDO::FETCH_NUM);
            }
            else
            {
                while ($row = $sth->fetch(PDO::FETCH_NUM))
                    $facility_id[] = $row[0];
            }
        }

        return $facility_id;
    }

    /**
     * Find estimated delivery date from UPS transit time tool
     */
    public function GetTargetDate($city, $state, $zip, $method = "Ground", $pickup_date = 0)
    {
        $target_date = strtotime("+6 Days");

        return $target_date;
    }

    private function GetUPSDeliveryDate($city, $state, $zip, $method = "Ground", $pickup_date = 0)
    {
        $target_date = strtotime("+6 Days");

        # Service does not work for freight but, Frieght times same as Ground
        if ($method == "")
            $method = "Ground";
        if ($method == "Freight")
            $method = "Ground";
        if ($method == "2 Day")
            $method = "2nd Day";

        $zip_low = $zip;
        $zip_high = "";
        if (strstr($zip, "-"))
        {
            list($zip_low, $zip_high) = explode("-", $zip);
        }

        if (!$pickup_date)
            $pickup_date = date('Ymd', strtotime("tomorrow"));

        $xml_request = "<?xml version=\"1.0\"?>		<AccessRequest xml:lang=\"en-US\">
			<AccessLicenseNumber>" . Config::$UPS_ACCESS_KEY . "</AccessLicenseNumber>
			<UserId>" . Config::$UPS_IT_USER . "</UserId>
			<Password>" . Config::$UPS_IT_PASS . "</Password>
		</AccessRequest>
		<?xml version=\"1.0\"?>		<TimeInTransitRequest xml:lang=\"en-US\">
			<Request>
				<TransactionReference>
					<CustomerContext>Time InTransit Request</CustomerContext>
					<XpciVersion>1.0002</XpciVersion>
				</TransactionReference>
				<RequestAction>TimeInTransit</RequestAction>
			</Request>
			<TransitFrom>
				<AddressArtifactFormat>
					<StreetNumberLow>4999</StreetNumberLow>
					<StreetName>Aircenter Circle</StreetName>
					<StreetType>Street</StreetType>
					<PoliticalDivision2>Reno</PoliticalDivision2>
					<PoliticalDivision1>NV</PoliticalDivision1>
					<PostcodePrimaryLow>89502</PostcodePrimaryLow>
					<PostcodeExtendedLow></PostcodeExtendedLow>
					<CountryCode>US</CountryCode>
				</AddressArtifactFormat>
			</TransitFrom>
			<TransitTo>
				<AddressArtifactFormat>
					<PostcodePrimaryLow>{$zip_low}</PostcodePrimaryLow>
					<PostcodeExtendedLow>{$zip_high}</PostcodeExtendedLow>
					<PoliticalDivision2>{$city}</PoliticalDivision2>
					<PoliticalDivision1>{$state}</PoliticalDivision1>
					<CountryCode>US</CountryCode>
				</AddressArtifactFormat>
			</TransitTo>
			<PickupDate>{$pickup_date}</PickupDate>
		</TimeInTransitRequest>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onlinetools.ups.com/ups.app/xml/TimeInTransit");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_request);
        $xml_result = curl_exec($ch);

        if (curl_errno($ch))
        {
            #print "Error: " . curl_error($ch);
        }
        else
        {
            curl_close($ch);

            # Get the available service summary objects from the xml result
            $svc_summary = "";
            if (preg_match_all('/<ServiceSummary>(.*?)<\/ServiceSummary>/', $xml_result, $svc_summary))
            {
                # Find the ground service date
                foreach ($svc_summary[1] as $i => $summary)
                {
                    # Match based on method string eg.('2nd Day','3 Day','Ground'...)
                    if (strpos($summary, $method))
                    {
                        # Get the Date field
                        $date = "";
                        if ($i > 0 && preg_match('/<Date>(.*?)<\/Date>/', $summary, $date))
                        {
                            # Assign inst_date the date found
                            $target_date = strtotime($date[1]);
                        }
                    }
                }
            }
        }

        return $target_date;
    }

    /**
     * Ping FedEx and UPS info
     *
     * @param $udbh PDO Object
     */
    static public function PingShippingData($order_id)
    {
        $ret = Order::$NO_SHIPMENT;

        if ($ret == Order::$NO_SHIPMENT)
            $ret = Order::PingDS($order_id);

        if ($ret == Order::$NO_SHIPMENT)
            $ret = Order::PingFedEx($order_id);

        if ($ret == Order::$NO_SHIPMENT)
            $ret = Order::PingUPS($order_id);

        return $ret;
    }

    /**
     * Query DB for existance of Digital Shipper record
     *
     * @param integer
     * @return interger
     */
    static public function PingDS($order_id)
    {
        $ret = Order::$NO_SHIPMENT;
        $dbh = DataStor::GetHandle();

        $order = new Order($order_id);

        # Find all orders in the order group
        $order_group = $order->order_group;

        if (is_null($order_group))
            $order_group[] = $order_id;

        # Use order group to find record in UPS DB
        if ($dbh)
        {
            // Find order_id in calPackage or calFreightShipment
            $sql = "SELECT count(*)
			FROM tOrderShipment_DS s
			WHERE trim('A' FROM OrderNumber) IN ('" . implode("','", $order_group) . "')
			AND upper(Status) <> 'VOID'";
            $sth = $dbh->query($sql);
            $found = $sth->fetchColumn();

            if ($found)
                $ret = Order::$DS;
        }

        return $ret;
    }

    /**
     * Query UPS World Ship DB
     *
     * @param $udbh PDO Object
     */
    static public function PingUPS($order_id)
    {
        $udbh = DataStor::GetUPSHandle();
        $dbh = DataStor::GetHandle();

        $ret = 0;

        $order = new Order($order_id);

        # Find all orders in the order group
        $order_group = $order->order_group;

        if (is_null($order_group))
            $order_group[] = $order_id;

        # Use order group to find record in UPS DB
        if ($udbh)
        {
            // Find order_id in calPackage or calFreightShipment
            $sql = "
SELECT 1
FROM calPackage
WHERE substring(ltrim(rtrim(sm_referenceText1)),1,7) IN ('" . implode("','", $order_group) . "')
UNION
SELECT 1
FROM calFreightShipment A
INNER JOIN calFreightReferenceNum R ON A.m_primarykey = R.m_foreignkey
                                    AND R.m_RefNumberType = 2
WHERE substring(ltrim(rtrim(R.Sm_referenceNum)),1,7) IN ('" . implode("','", $order_group) . "')";
            $sth = $udbh->query($sql);
            list($ret) = $sth->fetch(PDO::FETCH_NUM);
        }

        return ($ret) ? Order::$UPS : Order::$NO_SHIPMENT;
    }

    /**
     * Query FedEx DB
     *
     * @param $udbh PDO Object
     */
    static public function PingFedEx($order_id)
    {
        $dbh = DataStor::GetHandle();

        $ret = 0;

        # Find all orders in the order group
        $order_group = null;

        if ($dbh)
        {
            $sql = "
SELECT 1
FROM fedex_shipped
WHERE order_id = {$order_id}";
            $sth = $dbh->query($sql);

            if ($sth->rowCount() > 0)
                return Order::$FEDEX;
        }

        return Order::$NO_SHIPMENT;
    }

    /**
     * 1. Call LoadDSData
     * 2. Call LoadFedExData
     * 2. Call LoadUPSData
     *
     * @param
     */
    public function LoadShippingData($shipping_data = -1)
    {
        if ($shipping_data == Order::$DS)
            $this->LoadDSData();
        else if ($shipping_data == Order::$UPS)
        {
            $udbh = DataStor::GetUPSHandle();
            $this->LoadUPSData($udbh);
        }
        else if ($shipping_data == Order::$FEDEX)
            $this->LoadFedExData();
        else if ($shipping_data == -1)
        {
            $load_data = $this->LoadDSData($udbh);

            if ($load_data)
            {
                $load_data = $this->LoadFedExData();
            }

            if ($load_data)
            {
                $udbh = DataStor::GetUPSHandle();
                $load_data = $this->LoadUPSData($udbh);
            }

            $return_ary = array();

            $return_ary['tracking_num'] = ($this->tracking_num && $this->tracking_num != '') ? $this->tracking_num : null;
            $return_ary['ret_tracking_num'] = ($this->ret_tracking_num && $this->ret_tracking_num != '') ? $this->ret_tracking_num : null;

            return $return_ary;
        }
    }

    /**
     * Pull data from DS shipment table about this order
     * Return value of true indicates we need to continue looking
     * A false value indicates we found the info so stop looking
     *
     * return boolean
     */
    public function LoadDSData()
    {
        # Check for shipping item and upsell flag
        $sth = $this->dbh->prepare("SELECT 1 FROM order_item i
		INNER JOIN products p ON i.prod_id = p.id
		WHERE p.code = ?
		AND order_id IN (" . implode(",", $this->order_group) . ")");
        $sth->bindValue(1, self::$SHIPPING_ITEM_CODE, PDO::PARAM_STR);
        $sth->execute();
        $has_shipping_item = $sth->fetchColumn();

        if ($this->issue_id)
            $is_complaint_based = true;
        else if (in_array($this->type_id, array(Order::$SUPPLY_ORDER, Order::$PARTS_ORDER, Order::$SWAP_ORDER)))
            $is_complaint_based = true;
        else
            $is_complaint_based = false;

        $shipping_cost = 0;
        $shipping_company_id = null;
        $tracking_num = null;
        $ship_method = null;

        $sql = "SELECT
			s.ShipmentNumber,
			s.PackageNumber,
			s.TrackingNumber,
			s.ShipWeight,
			s.GrandTotalPubRate,
			s.GrandTotalNegRate,
			s.RequestedService,
			m.carrier_id
		FROM tOrderShipment_DS s
		LEFT JOIN ship_method m ON s.requestedservice = m.carrier_service_code
		WHERE upper(status) <> 'VOID'
		AND trim('A' FROM OrderNumber) IN ('" . implode("','", $this->order_group) . "')
		ORDER BY ShipmentNumber, PackageNumber";
        $sth = $this->dbh->query($sql);
        $sth->execute();
        while ($info = $sth->fetch(PDO::FETCH_OBJ))
        {
            if (empty($tracking_num))
                $tracking_num = trim($info->trackingnumber);

            if (empty($ship_method))
                $ship_method = self::DSto_SM($info->requestedservice);

            if (empty($shipping_company_id))
                $shipping_company_id = (int) $info->carrier_id;

            ## Add charge for all packages
            $shipping_cost += $info->grandtotalpubrate;
        }

        ## Set field values
        $save_fields = array();
        if ($tracking_num && $tracking_num != $this->tracking_num)
        {
            $this->tracking_num = $tracking_num;
            $save_fields['tracking_num'] = PDO::PARAM_STR;
        }
        if ($shipping_cost != $this->shipping_cost && $has_shipping_item == false && $is_complaint_based == false)
        {
            $save_fields['shipping_cost'] = PDO::PARAM_STR;
            $this->shipping_cost = $shipping_cost;
        }
        if ($ship_method != $this->ship_method)
        {
            $this->ship_method = $ship_method;
            $save_fields['ship_method'] = PDO::PARAM_STR;
        }
        if ($shipping_company_id && $shipping_company_id != $this->shipping_company_id)
        {
            $save_fields['shipping_company_id'] = PDO::PARAM_INT;
            $this->shipping_company_id = (int) $shipping_company_id;
        }

        if (count($save_fields) > 0)
        {
            $this->dbh->beginTransaction();
            $this->db_save($save_fields);
            $this->dbh->commit();
            return false;
        }

        ## Return value of true indicates we need to continue looking
        return true;
    }

    /**
     * Query UPS World Ship DB
     *
     * @param $udbh PDO Object
     */
    public function LoadUPSData($udbh)
    {
        if (!$udbh)
        {
            echo "No Connection to the UPS Database.";
            return;
        }

        if ($this->issue_id)
            $is_complaint_based = true;
        else if (in_array($this->type_id, array(Order::$SUPPLY_ORDER, Order::$PARTS_ORDER, Order::$SWAP_ORDER)))
            $is_complaint_based = true;
        else
            $is_complaint_based = false;

        # Check for shipping item and upsell flag
        $sth = $this->dbh->prepare("SELECT 1 FROM order_item i
		INNER JOIN products p ON i.prod_id = p.id
		WHERE p.code = ?
		AND order_id IN (" . implode(",", $this->order_group) . ")");
        $sth->bindValue(1, self::$SHIPPING_ITEM_CODE, PDO::PARAM_STR);
        $sth->execute();
        $has_shipping_item = $sth->fetchColumn();

        // Find min order_id to use for matching
        $lead_id = trim(min($this->order_group));
        $sql = "SELECT
			P2.sm_trackingno AS trackingno,
			S.cost,
			CASE WHEN substring(P2.sm_trackingno,9,2) in ('84','87','89','90')
				THEN 1
				ELSE 0
			END AS return_shipment,
			S2.m_serviceType,
			S.third_party
		FROM (
			SELECT
				max(T.m_primarykey) AS shipmentkey,
				sum(BI.m_shipperamountActual *  1 * 0.01) AS cost,
				max(BI.m_isThirdPartyPayee) as third_party,
				substring(ltrim(rtrim(A1.sm_referenceText1)),1,7) AS order_id
			FROM (
				SELECT
					m_foreignkey,
					min(m_primarykey) as packagekey
   				FROM calPackage
				WHERE m_isPkgVoid=0
   				AND m_isUserVoid=0
   				GROUP BY m_foreignkey) A
			INNER JOIN calShipment T ON A.m_foreignkey=T.m_primarykey
			INNER JOIN calBilling BI ON BI.m_foreignkey01=T.m_primarykey
			INNER JOIN calPackage A1 on A1.m_primarykey=A.packagekey
			WHERE T.m_isVoid=0
			AND T.m_isUserVoid=0
			AND substring(ltrim(rtrim(A1.sm_referenceText1)),1,7) = ?
			GROUP BY substring(ltrim(rtrim(A1.sm_referenceText1)),1,7),
			CASE WHEN substring(A1.sm_trackingno,9,2) in ('84','87','89','90')
				THEN 1
				ELSE 0
			END
		) S
		INNER JOIN calShipment S2 ON S.shipmentkey=S2.m_primarykey
		INNER JOIN (calPackage P2
			INNER JOIN (
				SELECT B.m_foreignkey, min(B.m_primarykey) AS m_primarykey
				FROM calPackage B
				WHERE B.m_isPkgVoid = 0
				AND B.m_isUserVoid = 0
				GROUP BY B.m_foreignkey
			) P1 ON P2.m_primarykey = P1.m_primarykey
		) ON S.shipmentkey = P2.m_foreignkey
UNION
SELECT A.sm_shipmentID,
       B.sm_amount * 1 * 0.01,
       0,
       -1,
       0
FROM calFreightShipment A
INNER JOIN calFreightReferenceNum R ON A.m_primarykey = R.m_foreignkey
                                    AND R.m_RefNumberType = 2
LEFT JOIN calFreightCharges B ON A.m_primarykey = B.m_foreignkey
AND B.sm_chargeCode = 'LND_GROSS'
WHERE A.sm_shipmentID <> ''
AND substring(ltrim(rtrim(R.Sm_referenceNum)),1,7) = ?";

        $sth = $udbh->prepare($sql);
        $sth->bindValue(1, $lead_id, PDO::PARAM_STR);
        $sth->bindValue(2, $lead_id, PDO::PARAM_STR);
        $sth->execute();

        $save_fields = array();
        while (list($tracking_num, $cost, $return, $service, $third_party) = $sth->fetch(PDO::FETCH_NUM))
        {
            // convert ups shipping method
            // Update order
            if ($return == 0)
            {
                $ship_method = self::UPSto_SM($service);
                $tracking_num = trim($tracking_num);

                if ($tracking_num && $tracking_num != $this->tracking_num)
                {
                    $this->tracking_num = $tracking_num;
                    $save_fields['tracking_num'] = PDO::PARAM_STR;
                }
                if ($cost != $this->shipping_cost && $has_shipping_item == false && $is_complaint_based == false)
                {
                    $save_fields['shipping_cost'] = PDO::PARAM_STR;
                    $this->shipping_cost = $cost;
                }
                if ($ship_method != $this->ship_method)
                {
                    $this->ship_method = $ship_method;
                    $save_fields['ship_method'] = PDO::PARAM_STR;
                }
                if ($third_party != $this->third_party)
                {
                    $this->third_party = $third_party;
                    $save_fields['third_party'] = PDO::PARAM_INT;
                }
            }
            if ($return == 1)
            {
                if ($tracking_num && $tracking_num != $this->ret_tracking_num)
                {
                    $save_fields['ret_tracking_num'] = PDO::PARAM_STR;
                    $this->ret_tracking_num = $tracking_num;
                }
            }
        }

        // Check for no results, and report it
        if (count($save_fields) == 0)
        {
            $this->dbh->query("INSERT INTO system_log (log_by, note) VALUES ('Order.LoadUPSData','WARNING: Unable to find order ($lead_id) information in UPS database.')");
        }
        else
        {

            $save_fields['shipping_company_id'] = Order::$UPS;
            $this->dbh->beginTransaction();

            $this->db_save($save_fields);

            $this->dbh->commit();

            return false;
        }

        return true;
    }

    /**
     * Lookup shipping data from carrier and update order
     * @return boolean
     */
    public function LoadFedExData()
    {
        if ($this->issue_id)
            $is_complaint_based = true;
        else if (in_array($this->type_id, array(Order::$SUPPLY_ORDER, Order::$PARTS_ORDER, Order::$SWAP_ORDER)))
            $is_complaint_based = true;
        else
            $is_complaint_based = false;

        # Check for shipping item and upsell flag
        $sth = $this->dbh->prepare("SELECT 1 FROM order_item i
		INNER JOIN products p ON i.prod_id = p.id
		WHERE p.code = ?
		AND order_id IN (" . implode(",", $this->order_group) . ")");
        $sth->bindValue(1, self::$SHIPPING_ITEM_CODE, PDO::PARAM_STR);
        $sth->execute();
        $has_shipping_item = (int) $sth->fetchColumn();

        $sql = "SELECT
			fs1.master_tracking AS tracking_num,
			fs2.master_tracking AS ret_tracking_num,
			fs1.shipping_cost
		FROM (
			SELECT
				order_id,
				master_tracking,
				SUM(cost_list) as shipping_cost
			FROM fedex_shipped
			WHERE is_return = FALSE
			AND ( status <> 'void' OR status IS NULL )
			GROUP BY order_id, master_tracking
		) fs1
		FULL OUTER JOIN (
			SELECT
				order_id,
				master_tracking
			FROM fedex_shipped
			WHERE is_return
			AND ( status <> 'void' OR status IS NULL )
			GROUP BY order_id, master_tracking
		) fs2 ON fs1.order_id = fs2.order_id
		WHERE COALESCE(fs1.order_id, fs2.order_id) IN (" . implode(',', $this->order_group) . ")";
        $save_fields = array();
        while (list($tracking_num, $ret_tracking_num, $cost) = $sth->fetch(PDO::FETCH_NUM))
        {
            // convert ups shipping method
            // Update order
            if ($return == 0)
            {
                $tracking_num = trim($tracking_num);

                if ($tracking_num && $tracking_num != $this->tracking_num)
                {
                    $this->tracking_num = $tracking_num;
                    $save_fields['tracking_num'] = PDO::PARAM_STR;
                }
                if ($cost > 0 && $has_shipping_item == 0 && $is_complaint_based == false)
                {
                    $save_fields['shipping_cost'] = PDO::PARAM_STR;
                    $this->shipping_cost = $cost;
                }
                if ($ret_tracking_num && $ret_tracking_num != $this->ret_tracking_num)
                {
                    $save_fields['ret_tracking_num'] = PDO::PARAM_STR;
                    $this->ret_tracking_num = $tracking_num;
                }
            }
        }

        // Check for no results, and report it
        if (count($save_fields) == 0)
        {
            $this->dbh->query("INSERT INTO system_log (log_by, note) VALUES ('Order.LoadFedExData','WARNING: Unable to find order ($lead_id) information in Fedex shipping table.')");
        }
        else
        {

            $save_fields['shipping_company_id'] = Order::$FEDEX;
            $this->dbh->beginTransaction();

            $this->db_save($save_fields);

            $this->dbh->commit();

            return false;
        }

        return true;
    }

    /**
     * For creating Sales orders
     *
     * @param $mdbh PDO Object
     */
    public function CreateMasSalesOrder()
    {
        $user = $this->session_user;
        if ($user->web_user)
            $user = new User(1);

        $SO_ary = array();
        $SO_ary['TranDate'] = date('Y-m-d');
        $SO_ary['ReqDate'] = date('Y-m-d', $this->order_date);
        $SO_ary['PromDate'] = ($this->inst_date) ? date('Y-m-d', $this->inst_date) : $SO_ary['ReqDate'];
        $SO_ary['ShipDate'] = ($this->ship_date) ? date('Y-m-d', $this->ship_date) : NULL;
        $SO_ary['AddrKey'] = ($this->mas_address_key) ? $this->mas_address_key : NULL;
        $SO_ary['OrderID'] = $this->GetBaseOrder($this->id);
        $SO_ary['ShipMethod'] = ($this->third_party) ? "Third Party" : $this->ship_method;
        $SO_ary['ShipCarrier'] = $this->GetShippingCompany();
        $SO_ary['CustPONo'] = ($this->po_number) ? $this->po_number : NULL;
        if ($this->source)
            $SO_ary['SalesSourceID'] = $this->source;
        $SO_ary['ShipCost'] = $this->GetShippingCharge();
        $SO_ary['items'] = array();

        if ($this->mas_sales_order == 0)
        {
            $facility = new CustomerEntity($this->facility_id);

            # Need Company ID to do lookups
            # This is determined by customer format
            $this->SO = new SalesOrder();
            $this->SO->SetCustID($facility->getCustId());
            $this->SO->ValidateCompany();

            # Slightly different for DME Orders
            if ($this->type_id == self::$DME_ORDER || $facility->getEntityType() == CustomerEntity::$ENTITY_PATIENT)
            {
                $facility = new CustomerEntity(Config::$DEFAULT_ID);
                $SO_ary['EmptyBins'] = 1;						# Override empty bin check
                $SO_ary['PickOrdQty'] = 1;						# No Back orders
            }

            # Add the items to the array
            $this->CreateMasSalesOrderItems($SO_ary);

            # Need at least 1 line item to create sales order
            $sales_order = -2;
            if (count($SO_ary['items']) > 0)
            {
                $this->SO->save($SO_ary);
                $sales_order = $this->SO->getTranNo();

                # Indicate Error (-1) when SO generation failed
                if (!$sales_order)
                    $sales_order = -1;
            }
            # Update Groupware Database
            $this->SetAccountingSO($sales_order);
        }
        else
        {
            # Process as a backorder. Load the SO and fill staging tables
            # This will help create the shipment
            if ($this->mas_sales_order > 0)
            {
                $this->SO = new SalesOrder($this->mas_sales_order);
                $this->SO->copyFromArray($SO_ary);
                $SO_ITEMS = $this->CreateMasSalesOrderItems($SO_ary);
                $this->SO->FillStagingTable($SO_ITEMS);
            }

            # Perform an update to set backorder reference
            $this->SetAccountingSO($this->mas_sales_order);
        }
    }

    /**
     * For creating Sales orders items
     *
     * @param array
     */
    public function CreateMasSalesOrderItems(&$SO_ary)
    {
        $SO_ary['items'] = array();

        # Prepare sql statement to find the service item code
        $sth_sitp = $this->dbh->prepare("SELECT
			item, loaner_item
		FROM service_item_to_product
		WHERE code = ?
		AND code NOT IN ( -- exclude accessories
			SELECT model
			FROM equipment_models
			WHERE type_id = 2 -- accessories
		)");

        # Get a list of product codes that require price setting
        # This is a special set of MISC items that are charged
        $tconf = new TConfig();
        $price_override_products = json_decode($tconf->get('price_override_products'), true);

        # Get details for pricing
        $sth = $this->dbh->prepare("SELECT
			ce.base_price_index,
			ce.cust_price_group_key
		FROM v_customer_entity ce
		WHERE ce.id = ?");
        $sth->bindValue(1, (int) $this->facility_id, PDO::PARAM_INT);
        $sth->execute();

        $item_list = $this->items;
        $shipping_item_price = null;
        # Add items to mas item array
        # NULL : calculate price
        # "0.00" are no charge items
        # Otherwise the unit price is passed
        foreach ($item_list as $item)
        {
            # Save original price of the shipping item
            if ($item['code'] == self::$SHIPPING_ITEM_CODE)
            {
                $shipping_item_price = $item['price'];

                # Only charge once for shipping
                $SO_ary['ShipCost'] = 0;
            }

            # Complaint based and CPM orders have price of 0 unless item is an upsell
            if ($this->issue_id > 0 || in_array($this->type_id, array(Order::$SUPPLY_ORDER, Order::$PARTS_ORDER, Order::$SWAP_ORDER)))
            {
                $item['price'] = 0.0;

                # set charge amount
                if ($item['upsell'])
                    $item['price'] = NULL;
            }
            else if ($this->type_id == Order::$INSTALL_ORDER)
            {
                $item['price'] = (float) $item['price'];

                # Convert device code to SRV code (Non-Inventory part)
                if ($item['is_device'] && $item['track_inventory'] == false)
                {
                    $sth_sitp->bindValue(1, $item['code'], PDO::PARAM_STR);
                    $sth_sitp->execute();
                    if ($row = $sth_sitp->fetch(PDO::FETCH_ASSOC))
                    {
                        if ($this->is_loan && $row['loaner_item'])
                            $item['code'] = $row['loaner_item'];
                        else
                            $item['code'] = $row['item'];
                        $item['is_device'] = false;
                    }
                }
            }
            else if ($this->type_id == Order::$CUSTOMER_ORDER)
            {
                # Convert device code to SRV code
                if ($item['is_device'])
                {
                    # Price for devices will have been set
                    $item['price'] = (float) $item['price'];

                    if ($item['track_inventory'] == false)
                    {
                        # Convert device model to "-EQUIP" code
                        # No longer use refurb codes
                        $use_refurb = false;
                        $item['code'] = MasSalesOrder::GetEquipmentCode($item['code'], $use_refurb);

                        # Decrement Inventory out of the Correct Warehouse
                        $item['whse_id'] = $this->SO->GetPurchaseWhseID();
                    }
                }
                else
                {
                    # Customer purchases will have price set in work_flow
                    # Other orders will have price set
                    #
                    if ($this->contract_id)
                        $item['price'] = (float) $item['price'];
                    else if (in_array($item['code'], $price_override_products))
                    {
                        $item['price'] = (float) $item['price'];

                        # Price will be saved in the line_item so no need to do this again
                        #$line_item = new LineItem($item['item_num'], $item['order_id']);
                        #$item['price'] = (float)$line_item->SetPriceInfo($this->type_id, $this->base_price_index, $this->cust_price_group_key);
                    }
                    else
                        $item['price'] = NULL;
                }

                if ($item['price'] == 0 && $this->SO->GetCO() == SalesOrder::$ININC_CO_ID)
                {
                    ## set price
                    $item['price'] = NULL;
                }
            }
            else if ($this->type_id == Order::$DSSI_ORDER)
            {
                if ($item['price'] == NULL)
                {
                    $item['price'] = NULL;
                }
                else
                {
                    $item['price'] = (float) $item['price'];
                }
            }
            else if ($this->type_id == Order::$WEB_ORDER)
            {
                if (in_array($item['code'], $price_override_products))
                {
                    $line_item = new LineItem($item['item_num'], $item['order_id']);
                    $item['price'] = (float) $line_item->SetPriceInfo($this->type_id, $this->base_price_index, $this->cust_price_group_key);
                }
                else
                    $item['price'] = NULL;
            }
            else if ($this->type_id == Order::$DME_ORDER)
            {
                $item['price'] = NULL;
            }
            else
            {
                $item['price'] = 0.0;
            }

            # Convert price total to Unit Price
            if ($item['price'] > 0)
                $item['price'] = $this->GetUnitPrice($item);

            # Keep this shipping items original price
            if ($item['code'] == self::$SHIPPING_ITEM_CODE)
                $item['price'] = $shipping_item_price;

            # Skip devices unless purchased.
            # Reset so_item array
            $so_item = array();
            if ((!$item['is_device'] || $item['track_inventory'] || $this->type_id == Order::$CUSTOMER_ORDER) && $item['quantity'] > 0)
            {
                # Older orders may not have uom
                $so_item['LineNo'] = $item['item_num'];
                if (!$item['uom'])
                    $item['uom'] = 'EA';
                $so_item['UOMKey'] = SalesOrder::GetUOMKey($item['uom'], $this->SO->GetCO());
                $so_item['CmntOnly'] = 0;
                $so_item['ItemID'] = $item['code'];
                $so_item['QtyOrd'] = $item['quantity'];
                $so_item['QtyShipped'] = $item['shipped'];
                $so_item['UnitPrice'] = $item['price'];

                # Set WhseKey for track_inventory items only
                $ItemType = SalesOrder::GetItemType($item['code'], $this->SO->GetCO());
                if ($item['whse_id'] && ($item['is_device'] || $ItemType > 5))
                    $so_item['WhseKey'] = SalesOrder::GetWhseKey($item['whse_id'], $this->SO->GetCO());

                $SO_ary['items'][] = $so_item;
            }
        }

        return $SO_ary['items'];
    }

    /**
     * For creating Sales orders
     *
     * @param $mdbh PDO Object
     */
    public function CreateRmaMasSalesOrder()
    {
        $user = $this->session_user;
        if ($user->web_user)
            $user = new User(1);

        if ($this->mas_sales_order == 0)
        {
            $facility = new CustomerEntity($this->facility_id);
            $sales_order = -1;

            # Need Company ID to do lookups
            # This is determined by customer format
            $this->SO = new SalesOrder();
            $this->SO->SetCustID($facility->getCustId());
            $this->SO->ValidateCompany();

            $SO_ary = array();
            $SO_ary['TranDate'] = date('Y-m-d');
            $SO_ary['ReqDate'] = date('Y-m-d', $this->order_date);
            $SO_ary['PromDate'] = ($this->inst_date) ? date('Y-m-d', $this->inst_date) : $SO_ary['ReqDate'];
            $SO_ary['ShipDate'] = ($this->ship_date) ? date('Y-m-d', $this->ship_date) : NULL;
            $SO_ary['AddrKey'] = ($this->mas_address_key) ? $this->mas_address_key : NULL;
            $SO_ary['OrderID'] = $this->id;
            $convert_method = ($this->third_party) ? "Third Party" : $this->ship_method;
            $shipping_company = $this->GetShippingCompany();
            $SO_ary['ShipMethod'] = $convert_method;
            $SO_ary['ShipCarrier'] = $shipping_company;
            $SO_ary['CustPONo'] = ($this->po_number) ? $this->po_number : NULL;
            if ($this->source)
                $SO_ary['SalesSourceID'] = $this->source;
            $SO_ary['ShipCost'] = $this->GetShippingCharge();
            $SO_ary['items'] = array();

            $shipping_cost = 0;
            # Get the sum of all the "shipping" items
            $item_list = $this->items;
            foreach ($item_list as $item)
            {
                if ($item['quantity'] > 0)
                {
                    if ($item['prod_id'] == self::$RAB_ITEM)
                        $shipping_cost += $item['price'];
                    else if ($item['prod_id'] == self::$RAB_BOX_ITEM)
                        $shipping_cost += $item['price'];
                    else if ($item['prod_id'] == self::$ECT_ITEM)
                        $shipping_cost += $item['price'];
                    else if ($item['code'] == self::$SHIPPING_ITEM_CODE)
                        $shipping_cost += $item['price'];
                }
            }

            # Create SO when there is something to charge
            if ($shipping_cost > 0)
            {
                # Create and add a single so_item
                $so_item = array();
                $so_item['LineNo'] = 1;
                $so_item['UOMKey'] = SalesOrder::GetUOMKey('EA', $this->SO->GetCO());
                $so_item['CmntOnly'] = 0;
                $so_item['ItemID'] = self::$SHIPPING_ITEM_CODE;
                $so_item['QtyOrd'] = 1;
                $so_item['QtyShipped'] = 1;
                $so_item['UnitPrice'] = $shipping_cost;

                $SO_ary['items'][0] = $so_item;

                # Need at least 1 line item to create sales order
                $this->SO->save($SO_ary);
                $sales_order = $this->SO->getTranNo();

                # Indicate Error (-1) when SO generation failed
                if (!$sales_order)
                    $sales_order = -1;
            }

            # Update Groupware Database
            $this->SetAccountingSO($sales_order);
        }
        else
        {
            ## Perform an update to set backorder reference
            $this->SetAccountingSO($this->mas_sales_order);
        }
    }

    /**
     * Only called one when order is put into shipped status
     *
     * @param $mdbh PDO object
     */
    public function CreateMasShipment()
    {
        # Only attempt once per order
        if (!$this->mas_shipment)
        {
            $this->mas_shipment = true;

            $sth = $this->dbh->prepare("UPDATE orders
			SET	mas_shipment = true
			WHERE id IN (" . implode(',', $this->order_group) . ") ");
            $sth->execute();

            # Back Order may not have created SO instance
            if (!$this->SO)
            {
                $SO_ary = array();
                $SO_ary['TranDate'] = date('Y-m-d');
                $SO_ary['ReqDate'] = date('Y-m-d', $this->order_date);
                $SO_ary['PromDate'] = ($this->inst_date) ? date('Y-m-d', $this->inst_date) : $SO_ary['ReqDate'];
                $SO_ary['ShipDate'] = ($this->ship_date) ? date('Y-m-d', $this->ship_date) : NULL;
                $SO_ary['AddrKey'] = ($this->mas_address_key) ? $this->mas_address_key : NULL;
                $SO_ary['OrderID'] = $this->GetBaseOrder($this->id);
                $SO_ary['ShipMethod'] = ($this->third_party) ? "Third Party" : $this->ship_method;
                $SO_ary['ShipCarrier'] = $this->GetShippingCompany();
                $SO_ary['CustPONo'] = ($this->po_number) ? $this->po_number : NULL;
                if ($this->source)
                    $SO_ary['SalesSourceID'] = $this->source;
                $SO_ary['ShipCost'] = $this->GetShippingCharge();
                $SO_ary['items'] = array();

                $this->SO = new SalesOrder($this->mas_sales_order);
                $this->SO->copyFromArray($SO_ary);
                $SO_ITEMS = $this->CreateMasSalesOrderItems($SO_ary);
                $this->SO->FillStagingTable($SO_ITEMS);
            }

            $res = $this->SO->PickListFromSO();
        }
    }

    /**
     * Remove items from the staging table
     *
     * @param $mdbh PDO object
     */
    public function CleanStagingTable()
    {
        if ($this->SO)
            $this->SO->CleanStagingTable();
    }

    /**
     * Use bo_items to create a back order for the unfulfilled items.
     */
    public function CreateBO()
    {
        $user = $this->session_user;
        if ($user->web_user)
            $user = new User(1);

        if (!$this->back_order && is_array($this->bo_items))
        {
            $sql = "INSERT INTO orders
			(	status_id, parent_order,
				order_date, inst_date, user_id, comments, ship_method, ship_to, urgency, facility_id,
				address, address2, city, state, zip, sname, ship_attn, email, type_id, mas_sales_order,
				phone, po_number, updated_at_tstamp, updated_by, ordered_by, fax, contract_id )
			SELECT
				?, -- status_id
				?, -- parent_order
				order_date, inst_date, user_id, comments, ship_method, ship_to, abs(urgency), facility_id,
				address, address2, city, state, zip, sname, ship_attn, email, type_id, mas_sales_order,
				phone, po_number, CURRENT_TIMESTAMP, ?, ordered_by, fax, contract_id
			FROM orders
			WHERE id = ?";

            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, self::$PROCESSED, PDO::PARAM_INT);
            $sth->bindValue(2, $this->id, PDO::PARAM_INT);
            $sth->bindValue(3, $user->getId(), PDO::PARAM_INT);
            $sth->bindValue(4, $this->id, PDO::PARAM_INT);
            $sth->execute();

            $order_id = $this->dbh->lastInsertId('orders_id_seq');

            # All items are added with item_num intact
            $sql = "INSERT INTO order_item (
			  order_id,item_num,prod_id,
			  code,\"name\",description,
			  quantity,asset_id,swap_asset_id,
			  shipped,uom,price,whse_id)
			VALUES (?,?,?, ?,?,?, ?,?,?, ?,?,?,?)";
            $sth = $this->dbh->prepare($sql);
            foreach ($this->bo_items as $item)
            {
                $sth->bindValue(1, $order_id, PDO::PARAM_INT);
                $sth->bindValue(2, $item['item_num'], PDO::PARAM_INT);
                $sth->bindValue(3, $item['prod_id'], PDO::PARAM_INT);
                $sth->bindValue(4, $item['code'], PDO::PARAM_STR);
                $sth->bindValue(5, $item['name'], PDO::PARAM_STR);
                $sth->bindValue(6, $item['description'], PDO::PARAM_STR);
                $sth->bindValue(7, (int) $item['quantity'], PDO::PARAM_INT);
                $sth->bindValue(8, (int) $item['asset_id'], PDO::PARAM_INT);
                $sth->bindValue(9, (int) $item['swap_asset_id'], PDO::PARAM_INT);
                $sth->bindValue(10, 0 /*shipped*/ , PDO::PARAM_INT);
                $sth->bindValue(11, $item['uom'], PDO::PARAM_STR);
                $sth->bindValue(12, $item['price'], PDO::PARAM_STR);
                $sth->bindValue(13, $item['whse_id'], ($item['whse_id'] ? PDO::PARAM_STR : PDO::PARAM_NULL));
                $sth->execute();
            }

            # Generate DSSI backorder acknowledgment UPDATE
            if ($this->type_id == self::$DSSI_ORDER)
            {
                if ($this->parent_order == 0 && $this->po_number)
                {
                    include_once ('DSSIProcessor.php');
                    $dssiPro = new DSSIProcessor();
                    $dssiPro->writeBackorderAck($order_id);
                }
            }
        }
    }

    /**
     * Generate options list for shipping company
     *
     * @param integer $shipping_company_id
     * @return string $method_list
     */
    public static function createShippingCompanyList($shipping_company_id)
    {
        $carrier_list = "";
        $dbh = DataStor::GetHandle();
        $sql = "SELECT
      		id, long_name, active
      	FROM shipping_company
      	WHERE active = true AND id > 0
      	ORDER BY sort_order";
        $sth = $dbh->query($sql);
        while (list($val, $text, $active) = $sth->fetch(PDO::FETCH_NUM))
        {
            if ($active || $val == $shipping_company_id)
            {
                $sel = ($val == $shipping_company_id) ? "selected" : "";
                $carrier_list .= "<option value='{$val}' {$sel}>{$text}</option>\n";
            }
        }

        return $carrier_list;
    }

    /**
     * Generate options list for shipping method
     *
     * @param string $shipping_method
     * @param array $eclude
     * @param integer
     *
     * @return string $method_list
     */
    public static function createShippingMethodList($shipping_method = 'Ground', $exclude = NULL, $carrier_id = NULL)
    {
        $method_list = "";

        $tconf = new TConfig();
        $use_db = $tconf->get('use_sm_table');

        if ($use_db)
        {
            $dbh = DataStor::GetHandle();
            $LIMIT_CARRIER = (is_null($carrier_id)) ? "" : "AND carrier_id = $carrier_id";
            $sql = "SELECT
      			carrier_service_code, description
      		FROM ship_method
      		WHERE active = true
      		$LIMIT_CARRIER
      		ORDER BY carrier_id, sort_order";
            echo $sql;
            $sth = $dbh->query($sql);
            while (list($code, $text) = $sth->fetch(PDO::FETCH_NUM))
            {
                $sel = ($code == $shipping_method) ? "selected" : "";
                $method_list .= "<option value='{$code}' {$sel}>{$text}</option>\n";
            }
        }
        else
        {
            $available = array('Ground', '3 Day', '2 Day', '2 Day Early AM', 'Next Day', 'Next Day Early AM', 'Freight', 'International');

            # Remove exlucded methods
            if (!is_array($exclude))
                $exclude = array($exclude);

            # Options match those from the Order Fulfillment application

            foreach ($available as $m)
            {
                if (!in_array($m, $exclude))
                {
                    $sel = ($m === $shipping_method) ? "selected" : "";
                    $method_list .= "<option value='{$m}' {$sel}>{$m}</option>\n";
                }
            }
        }
        return $method_list;
    }

    /**
     * Creates a temporary asset record in the product_due_back table.
     *
     * @param integer
     *
     * @return integer
     */
    public function createTemporaryAssetRecord($model_id)
    {
        $base_order = $this->id;
        $order_group = $this->order_group;

        $useOrderIDNumber = null;

        if ($model_id)
        {
            if ($this->parent_order)
            {
                $base_order = $this->GetBaseOrder($this->parent_order);
                $parent_order = new Order($base_order);
                $order_group = $parent_order->getVar("order_group");
            }

            $order_ids = implode(',', $order_group);

            $exclude_orders = "";
            if (count($this->temporariesCreated))
            {
                $ids = implode(',', $this->temporariesCreated);
                $exclude_orders = "AND o.order_id NOT IN ($ids)";
            }

            $orderItemCheckSQL = "SELECT o.order_id
			FROM order_item o
			INNER JOIN products p ON o.prod_id = p.id
			INNER JOIN equipment_models m ON p.code = m.model AND m.id = {$model_id}
			WHERE o.order_id IN ( {$order_ids} )
			{$exclude_orders}
			AND o.swap_asset_id = 0
			ORDER BY o.item_num";
            $orderItemCheckSTH = $this->dbh->query($orderItemCheckSQL);

            $productDueBackSTH = $this->dbh->prepare("SELECT
				count(*)
			FROM product_due_back
			WHERE order_id = ?");

            $complaintFormEquipmentSTH = $this->dbh->prepare("SELECT
				count(*)
			FROM complaint_form_equipment
			WHERE order_id = ?");

            while (list($order_id) = $orderItemCheckSTH->fetch(PDO::FETCH_NUM))
            {
                if (!isset($useOrderIDNumber))
                {
                    $productDueBackSTH->bindValue(1, $order_id, PDO::PARAM_INT);
                    $productDueBackSTH->execute();
                    if ($productDueBackSTH->fetchColumn() == 0)
                    {

                        $complaintFormEquipmentSTH->bindValue(1, $order_id, PDO::PARAM_INT);
                        $complaintFormEquipmentSTH->execute();

                        if ($complaintFormEquipmentSTH->fetchColumn() > 0)
                        {
                            $useOrderIDNumber = $order_id;
                            break;
                        }
                    }
                }
            }

            if ($useOrderIDNumber)
            {
                $this->dbh->query("INSERT INTO product_due_back ( order_id )
				VALUES ( {$useOrderIDNumber} )");
            }
        }

        return $useOrderIDNumber;
    }

    /**
     * Find base unit at the facility without this accessory
     */
    public function FindBaseWithout($accessory_model)
    {
        $sql = "SELECT
			las.id
		FROM lease_asset_status las
		WHERE las.facility_id = ?
		AND find_in_array(
				(SELECT base_assets FROM equipment_models WHERE id = ?),
				las.model_id
		)
		AND las.id NOT IN ( -- Find all base units with an attached accessory of this model
			SELECT a.base_unit_asset_id
			FROM accessory_to_base_unit a
			INNER JOIN ( -- Find most recent record for the accessory
				SELECT
					accessory_asset_id, MAX(tstamp) as tstamp
				FROM accessory_to_base_unit
				GROUP BY accessory_asset_id
			) m ON a.accessory_asset_id = m.accessory_asset_id AND a.tstamp = m.tstamp
			INNER JOIN lease_asset la ON a.accessory_asset_id = la.id AND la.model_id = ?
			WHERE a.base_unit_asset_id IS NOT NULL
		)
		LIMIT 1";

        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, $this->facility_id, PDO::PARAM_INT);
        $sth->bindValue(2, $accessory_model, PDO::PARAM_INT);
        $sth->bindValue(3, $accessory_model, PDO::PARAM_INT);
        $sth->execute();

        $asset_id = $sth->fetchColumn();

        return $asset_id;
    }

    /**
     * Find a base unit
     *
     * @param object
     * @param object
     * @param int
     *
     * return object
     */
    private function FindBaseUnit($lease_asset, $swap_asset, $base_order)
    {
        $base_unit = null;

        # CASE: accessory currently attached to base unit (Return Orders)
        if ($lease_asset->getBaseUnit())
        {
            $base_unit = new LeaseAsset($lease_asset->getBaseUnit());
        }

        # Find BASE from swap asset
        if ($swap_asset)
        {
            # This will be the inbound asset
            if ($swap_asset->getBaseUnit())
                $base_unit = new LeaseAsset($swap_asset->getBaseUnit());
        }

        # Find BASE from this order's item list
        if (is_null($base_unit))
        {
            $base_assets = $lease_asset->getModel()->base_assets;
            if (is_array($base_assets))
            {
                $sth = $this->dbh->query("SELECT oi.asset_id
				FROM order_item oi
				INNER JOIN equipment_models m ON oi.code = m.model
				WHERE oi.order_id = {$this->id}
				AND oi.asset_id > 0
				AND m.id IN (" . implode(',', $base_assets) . ")
				LIMIT 1");
                $base_unit_id = $sth->fetchColumn();

                if ($base_unit_id)
                    $base_unit = new LeaseAsset($base_unit_id);
            }
        }

        # Find BASE from Complaint Form
        if (is_null($base_unit) && $base_order)
        {
            $id_field = "order_id";
            if ($this->type_id == self::$RETURN_ORDER)
                $id_field = "return_id";

            # "Attempt" to obtain base unit from complaint form
            $sth = $this->dbh->query("SELECT la.id
			FROM complaint_form_equipment cfe
			INNER JOIN lease_asset la ON la.model_id = cfe.model AND la.serial_num = cfe.serial_number
			INNER JOIN equipment_models m ON cfe.model = m.id AND m.type_id = 1
			WHERE order_id = {$base_order}
			LIMIT 1");
            $base_unit_id = $sth->fetchColumn();

            if (!$base_unit_id)
                $base_unit_id = $this->FindBaseWithout($lease_asset->getModel()->GetId());

            if (!$base_unit_id)
            {
                echo "<p class='error' style='background-color:#E0C0C0;'>
				Warning! Could not find base unit for <br />
				Item: {$lease_asset->getModel()->GetNumber()},
				Serial/Barcode: {$lease_asset->getSerial()}</p>";
            }
            else
                $base_unit = new LeaseAsset($base_unit_id);
        }

        return $base_unit;
    }

    /**
     * Generate a link to carriers tracking site
     * @param string $tracking_num
     * @param bool $use_img
     * @param integer $carrier
     */
    static public function FormatTrackingNo($tracking_num, $use_img = true, $carrier = null)
    {
        $tracking = "";

        if (strlen($tracking_num) > 1)
        {
            if ($carrier == self::$UPS || preg_match('/^1Z/i', $tracking_num) == 1)
            {
                $alt = "UPS Tracking";
                $href = "http://wwwapps.ups.com/WebTracking/processInputRequest?loc=en_US&tracknum={$tracking_num}&AgreeToTermsAndConditions=yes";
                $innerHTML = ($use_img) ? "<img class='form_bttn' src='images/ups_logo.png'/>" : $tracking_num;
            }
            else if ($carrier == self::$DHL || preg_match('/\b([A-Za-z]{3}[0-9]{7})\b/i', $tracking_num) == 1)
            {
                $alt = "DHL Tracking";
                $href = "https://interactive1.dhl.com/TrackReport/controller/AnonShipmentTracking?SearchType=HBN&SearchValue={$tracking_num}";
                $innerHTML = ($use_img) ? "<img class='form_bttn' src='images/dhl_logo.png'/>" : $tracking_num;
            }
            else if ($carrier == self::$MDX || preg_match('/\b(H ?[0-9]*)\b/i', $tracking_num) == 1)
            {
                $alt = "MDX Tracking";
                $href = "http://shipmdx.com/Clarity/application/tracker/liftairbill.asp?TransID=&Airbill={$tracking_num}";
                $innerHTML = ($use_img) ? "<img class='form_bttn' src='images/mdx_logo.png'>" : $tracking_num;
            }
            else if ($carrier == self::$DS)
            {
                return $tracking;
            }
            else
            {
                $alt = "FedEx Tracking";
                $href = "https://www.fedex.com/fedextrack/?trknbr={$tracking_num}";
                $innerHTML = ($use_img) ? "<img class='form_bttn' src='images/fedex_logo.png'>" : $tracking_num;
            }

            $tracking = "<a href=\"$href\" alt='$alt' title='$alt' target='_blank'>$innerHTML</a>";
        }

        return $tracking;
    }

    /**
     * Create an anchor tag with an image for the given product
     * Handles both large and thumbnail images
     *
     * @param string $prod_code
     * @param int width
     * @param int height
     *
     * @return string
     */
    static public function GetProductImg($prod_code, $thumb_href = null, $width = null, $height = null, $clickable = true)
    {
        $pic = "No Picture";

        $prod_code = strtolower($prod_code);

        if ($prod_code)
        {
            $server_port = '';
            # For uncommon html port
            if (isset($_SERVER['SERVER_PORT']))
                $server_port = ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') ? ":{$_SERVER['SERVER_PORT']}" : "";

            # Set the path of the image file
            #
            $img_dir = Config::$MEDIA_FILE_PATH . "/product_image/";
            $img_path = Config::$MEDIA_FILE_PATH_FULL . "/product_image/";

            # Set the file name
            $img_name = $prod_code;
            if ($thumb_href)
                $img_name .= "_thumb";
            $img_name .= ".jpg";

            # Set image SRC
            $img_src = "{$img_dir}{$img_name}";

            # Set the attributes for the a tag
            if ($thumb_href)
            {
                $a_href = $thumb_href;
                $a_target = "_self";
                //if (strstr('databroker_cf', $thumb_href))
                $tt_url = preg_replace(array('/^javascript.*databroker_cf/', '/act\=product/', '/\"\);$/'), array('databroker_cf', 'act=tt', ''), $thumb_href);
                //else
                //	$tt_url = "orderplacement.php?section=Product&act=TT&prod_code={$prod_code}";

                $tooltip = "onmouseover=\"GetTooltip(event,'$prod_code','$tt_url');\" onmouseout=\"HideTooltip('$prod_code');\"";
            }
            else
            {
                $a_href = $img_src;
                $a_target = "_blank";
                $tooltip = "";
            }

            # Check if file exists
            if (file_exists($img_path . $img_name))
            {
                $alt = "Product Image";
            }
            else
            {
                # Missing file
                //$alt = "No Picture";
                $alt = "$img_path/$img_name";

                if (isset($_SERVER['SERVER_NAME']))
                    $server_name = $_SERVER['SERVER_NAME'];
                else
                    $server_name = "localhost.localdomain";

                # Reset image SRC to logo
                $img_src = "//{$server_name}{$server_port}" . Config::$WEB_PATH . "/images/acpl_logo_348x268.png";
                if ($thumb_href)
                {
                    if (is_null($height))
                        $height = 30;
                    $width = null;
                }
                else
                {
                    if (is_null($height))
                        $height = 100;
                    $width = null;
                    $a_href = $img_src;
                }
            }

            if (!$clickable)
            {
                $tooltip = "style='pointer-events:none;'";
            }

            # Build the html
            $w = ($width) ? "width='{$width}'" : "";
            $h = ($height) ? "height='{$height}'" : "";
            $pic = "
			<a href='$a_href' target='$a_target' alt='$alt' title='$alt' $tooltip>
				<img id='product_image' $w $h src='$img_src'/>
			</a>";
        }

        return $pic;
    }

    /**
     * Determine if shipping should be charged
     *
     * @return float
     */
    public function GetShippingCharge()
    {
        $charge = 0;

        # Check for shipping item and upsell flag
        $has_shipping_item = $has_upsell = false;
        foreach ($this->items as $i)
        {
            if ($i['code'] == self::$SHIPPING_ITEM_CODE)
            {
                $has_shipping_item = true;
            }

            if ($i['upsell'])
                $has_upsell = true;
        }

        # Charge shipping on chargable types and those which have upsell items
        # Exclude third party
        if ($this->third_party == 0 && $this->facility_id <> Config::$DEFAULT_ID)
            $charge_shipping = (in_array($this->type_id, self::$CHARGE_SHIPPING) || $has_upsell);
        else
            $charge_shipping = false;

        # Do not double charge for shipping
        # Z Shipping will be passed in on equipment purchases
        if ($has_shipping_item)
        {
            $charge_shipping = false;
            $charge = 0;
        }

        if ($charge_shipping)
        {
            $charge = $this->shipping_cost;

            if ($this->web_shipping)
                $charge = $this->web_shipping;
        }

        return $charge;
    }

    /**
     * Determine shipping company from tracking number
     *
     * @return string
     */
    public function GetShippingCompany()
    {
        $shipping_company = "FedEx";

        $ups_regex = '/^1Z/i';
        $mdx_regex = '/\b(H ?[0-9]*)\b/i';

        if (preg_match($ups_regex, $this->tracking_num))
            $shipping_company = "UPS";
        else if (preg_match($ups_regex, $this->ret_tracking_num))
            $shipping_company = "UPS";
        else if (preg_match($mdx_regex, $this->tracking_num))
            $shipping_company = "MDX";
        else if (preg_match($mdx_regex, $this->ret_tracking_num))
            $shipping_company = "MDX";

        ##echo "GetShippingCompany() :: shipping_company $shipping_company<br/>\n";

        return $shipping_company;
    }

    private function CheckReturnOrders()
    {
        foreach ($this->items as $key => $item)
        {
            if ($item['swap_asset_id'] > 0)
            {
                $lease_asset = new LeaseAsset($item['swap_asset_id']);
                $accessory_units = $lease_asset->getAccessoryUnits();

                $accessories = "";
                if (is_array($accessory_units))
                {
                    foreach ($accessory_units as $key => $accessory_id)
                        if (isset($accessory_id))
                            $accessories .= ($accessories != "") ? ", {$accessory_id}" : $accessory_id;
                }

                $order_statuses = implode(", ", array(Order::$QUEUED, Order::$PROCESSED));
                $order_types = implode(", ", array(Order::$RMA_ORDER, Order::$RETURN_ORDER));
                $exception_status = Order::$EXCEPTION;

                $sql = "UPDATE orders
				SET
					status_id = {$exception_status},
					comments = 'This order was set to Exception because the Base Unit: {$lease_asset->getSerial()} of the Accessory is being Swapped out through Order# {$this->id}.'
				WHERE id IN (
					SELECT o.id
					FROM orders o
					JOIN order_item oi ON o.id = oi.order_id
					  AND oi.swap_asset_id IN ( {$accessories} )
					WHERE o.status_id IN ( {$order_statuses} )
					AND o.type_id IN ( {$order_types} )
				UNION
					SELECT o.id
					FROM orders o
					WHERE id IN (
						SELECT order_id
						FROM complaint_form_equipment cfe
						JOIN lease_asset la ON la.model_id = cfe.model
						  AND la.serial_num = cfe.serial_number
						  AND la.id = {$item['swap_asset_id']}
					UNION
						SELECT return_id
						FROM complaint_form_equipment cfe
						JOIN lease_asset la ON la.model_id = cfe.model
						  AND la.serial_num = cfe.serial_number
						  AND la.id = {$item['swap_asset_id']}
					)
					AND o.status_id IN ({$order_statuses})
					AND o.type_id IN ( {$order_types} )
				)";

                if ($accessories != "")
                    $this->dbh->exec($sql);
            }
        }
    }

    /**
     * Recursively find original order
     *
     * @param int
     * @return int
     */
    private function GetBaseOrder($order_id)
    {
        $return_array = array();

        $sql = "SELECT
			po.id, po.parent_order
		FROM orders bo
		JOIN orders po ON bo.parent_order = po.id
		WHERE bo.id = {$order_id}";
        $sth = $this->dbh->query($sql);

        list($current_parent, $next_parent) = $sth->fetch(PDO::FETCH_NUM);

        if (isset($next_parent) && $next_parent != 0)
            return $this->GetBaseOrder($next_parent);

        if (isset($current_parent))
            return $current_parent;

        return $order_id;
    }

    /**
     * Find the Unit Price for the item
     *
     * @param array
     * @return float
     */
    private function GetUnitPrice($item)
    {
        # Find the price of 1
        if ($item['quantity'] > 0)
            $unit_price = round($item['price'] / $item['quantity'], 3);
        else
            $unit_price = $item['price'];

        return $unit_price;
    }

    /**
     * Update mas_sales_order attribute.
     * All group memebers and back orders share a single SO number.
     *
     * @param integer
     */
    function SetAccountingSO($so_num)
    {
        # Set SO for all orders in the group
        # And any BO created from the group
        $this->mas_sales_order = $so_num;

        $sth = $this->dbh->prepare("UPDATE orders
		SET
			mas_sales_order = ?
		WHERE id IN (" . implode(',', $this->order_group) . ")
		OR parent_order IN (" . implode(',', $this->order_group) . ")");
        $sth->bindValue(1, $this->mas_sales_order, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Lookup default account for the carrier
     */
    public function SetDefaultAccount()
    {
        if ($this->shipping_company_id)
        {
            $sth = $this->dbh->prepare("SELECT default_account_no
			FROM shipping_company
			WHERE id = ?");
            $sth->bindValue(1, $this->shipping_company_id, PDO::PARAM_INT);
            $sth->execute();
            $this->bill_to_acct = $sth->fetchColumn();
        }
    }

    /**
     * Update shipping company id field
     *
     * @param integer
     */
    public function SetShippingCompany($shiping_company_id)
    {
        # Update order
        $sth = $this->dbh->prepare("UPDATE orders
		SET
			shipping_company_id = ?
		WHERE id IN (" . implode(',', $this->order_group) . ")");
        $sth->bindValue(1, $shiping_company_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Add new Asset Record transaction
     *
     * FROM Original facility + status
     * TO New facility + status + substatus
     *
     * @param LeaseAsset $device
     * @param int $orig_facility
     * @param string $orig_status
     * @param int $new_facility
     * @param string $new_status
     * @param string $new_substatus
     *
     * @return bool
     */
    public function UpdateAssetRecord($device, $orig_facility, $orig_status, $new_facility, $new_status, $new_substatus = '')
    {
        $update = false;

        $trans = $device->getLastTransaction();
        $dev_facility = ($trans) ? $trans->getFacility() : null;

        # Set a comment for the new transaction
        $comment = "Updated from Order ({$this->id})";

        # Allow Asset Update if this is NOT A leased device
        $non_lease = ($new_substatus != LeaseAssetTransaction::$LEASE);

        # Do not allow OOS devices to be shipped.
        if ($trans->getStatus() == LeaseAssetTransaction::$OUT_OF_SERVICE)
        {
            return $update;
        }

        # Make sure last transaction is valid
        if ($dev_facility)
        {
            # Facility and status match in order to add new transaction
            # (All Leases must follow the first condition)
            if (is_array($orig_facility))
                $at_location = in_array($dev_facility->getId(), $orig_facility);
            else
                $at_location = ($dev_facility->getId() == $orig_facility);

            if ($at_location && $trans->getStatus() == $orig_status)
            {
                $device->addTransaction($new_facility, $new_status, $new_substatus, $this->session_user, $comment);
                $update = true;
            }
            # Purchasing or Renting the device
            else if ($orig_status == LeaseAssetTransaction::$FGI && $non_lease)
            {
                $device->addTransaction($new_facility, $new_status, $new_substatus, $this->session_user, $comment);
                $update = true;
            }
            # Facility status already set return true
            else if ($dev_facility->getId() == $new_facility && $trans->getStatus() == $new_status)
            {
                $update = true;
            }
        }
        else if ($device)
        {
            # If no valid last transaction then add new
            $device->addTransaction($new_facility, $new_status, $new_substatus, $this->session_user, $comment);
            $update = true;
        }

        return $update;
    }

    /**
     * Set status and move asset
     *
     * @param array
     * @param integer
     */
    private function UpdateAsset(&$item, $status_id)
    {
        $error_msg = "";

        # Default : Any Location/Warhouse
        $tconf = new TConfig();
        $location = $tconf->get('facility_list');
        if (is_null($location))
            $location = Config::$DEFAULT001_ID;
        else
            $location = explode(',', $location);

        $place_sub = LeaseAssetTransaction::$LEASE;

        # For Purchasing orders (RMA, Return and Customer)
        # retain the facility and use purchased substatus
        if ($this->is_purchase)
        {
            $location = $this->facility_id;
            $place_sub = LeaseAssetTransaction::$PURCHASE;
        }
        # For Medical Supply devices are placed+rental
        else if ($this->type_id == self::$DME_ORDER)
        {
            $place_sub = LeaseAssetTransaction::$RENTAL;
        }
        # Loaner devices are placed+loan
        else if ($this->is_loan)
        {
            $place_sub = LeaseAssetTransaction::$LOAN;
        }
        else if ($this->type_id == self::$VENDOR_RMA_ORDER)
        {
            $location = $this->facility_id;
            $place_sub = LeaseAssetTransaction::$RTN;
        }

        # Default sub status based on Order type
        $sub_status = $place_sub;

        # Define Unit Ordered
        # Create an Asset instance
        $lease_asset = new LeaseAsset($item['asset_id']);
        $swap_asset = NULL;
        $base_unit = NULL;
        $temporaryCreated = NULL;
        $order_id = (isset($item['order_id'])) ? $item['order_id'] : $this->id;
        $contract_id = (isset($item['contract_id'])) ? $item['contract_id'] : $this->contract_id;

        # Define swap Unit
        if ($item['swap_asset_id'] > 0)
        {
            $swap_asset = new LeaseAsset($item['swap_asset_id']);
            if ($swap_asset->OnLoan())
                $place_sub = LeaseAssetTransaction::$LOAN;
        }
        else if ($this->in_return)
        {
            # No swap asset
            # In return (SWAP) should have a swap_asset defined
            # May happen if accessory swap and accessory coming back is unknown
            if ($item['unit_type'] == EquipmentModel::$ACCESSORY)
            {
                # Will be updated when received
                if ($temporaryCreated = $this->createTemporaryAssetRecord((int) $item['model']))
                    $this->temporariesCreated[] = $temporaryCreated;
            }
        }

        # Define the (NEW) base unit for the accessory
        # The accessory will maintain same sub status as base unit
        # Base unit can be attached to swap asset from complaint form
        if ($item['unit_type'] == EquipmentModel::$ACCESSORY)
        {
            $base_order = $order_id;

            if ($temporaryCreated)
                $base_order = $temporaryCreated;
            else if ($this->parent_order)
                $base_order = $this->GetBaseOrder($this->parent_order);

            $base_unit = $this->FindBaseUnit($lease_asset, $swap_asset, $base_order);

            # Get sub_status of the base
            $last_base_tran = ($base_unit) ? $base_unit->getLastTransaction() : null;
            $sub_status = ($last_base_tran) ? $last_base_tran->getSubStatus() : $place_sub;
        }

        #
        # Update the Asset Records.
        # Add new transaction.
        # Attach/Detach asseccories
        #
        if ($this->out_asset)
        {
            # Update device/accessory outgoing to customer
            # From FGI to PLACED

            # Swap orders:
            # Maintain same substatus as the swapped device/accessory.
            if ($swap_asset)
            {
                $tran_ary = $swap_asset->getAllTransactions();

                # Find the last "Placed" transaction and use that sub status.
                foreach ($tran_ary as $tran)
                {
                    if ($tran->getStatus() == LeaseAssetTransaction::$PLACED)
                    {
                        $sub_status = $tran->getSubStatus();
                        break;
                    }
                }
            }

            # Check calibration date (lease only)
            $calibration_uptodate = $lease_asset->CalibrationUptodate();
            $purchased = $lease_asset->CustomerOwned();
            $is_rma = ($this->type_id == self::$VENDOR_RMA_ORDER);

            # Skip calibration check when returning to vendor on RMA
            if ($is_rma)
                $calibration_uptodate = true;

            if ($purchased || $calibration_uptodate)
            {
                ## On installs accessories may be not be clearly set if not attached to a base
                if (empty($sub_status))
                    $sub_status = $place_sub;

                ## Going to customer => Placed:$sub_status
                ## Going to Vendor => Transit:Return
                if ($is_rma)
                {
                    $new_status = LeaseAssetTransaction::$TRANSIT;
                    $sub_status = LeaseAssetTransaction::$RTN;
                }
                else
                {
                    $new_status = LeaseAssetTransaction::$PLACED;

                    # All loaners will remain Placed/Loan
                    if ($lease_asset->OnLoan())
                        $sub_status = LeaseAssetTransaction::$LOAN;
                }

                ## Add the transaction record
                $updated = $this->UpdateAssetRecord($lease_asset,
                    $location, LeaseAssetTransaction::$FGI, $this->facility_id,
                    $new_status, $sub_status);

                if ($updated)
                {
                    ## Update ownership
                    if ($sub_status == LeaseAssetTransaction::$PURCHASE)
                    {
                        $is_swap = ($swap_asset) ? true : false;
                        $owning_acct = Facility::GetCustIdFromId($this->facility_id);
                        FASOwnership::ActivateOwner($lease_asset->GetId(), $owning_acct, $item['price'], date('Y-m-d H:i:s'), $is_swap);
                    }

                    # Set replacement fields in complaint form
                    $this->UpdateComplaintForm($item);

                    if ($item['unit_type'] == EquipmentModel::$ACCESSORY && $base_unit)
                    {
                        # Attach to base
                        $lease_asset->Attach($base_unit->getId());

                        # Update software version
                        if (preg_match("/^.*[1-9].*/", $lease_asset->getSWVersion()))
                        {
                            $sql = "UPDATE lease_asset
							SET software_version = '{$lease_asset->getSWVersion()}'
							WHERE id = {$base_unit->getId()}";
                            $this->dbh->exec($sql);
                        }
                    }
                }
                else
                {
                    if (is_array($location))
                        $facility = new Facility(Config::$DEFAULT001_ID);
                    else
                        $facility = new Facility($location);

                    $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
					Order can not be set to Shipped!<br />
					Item: {$item['e_model']}, Serial/Barcode: {$item['serial_number']} is not " . LeaseAssetTransaction::$FGI . " at {$facility->getName()}</p>";
                }
            }
            else
            {
                $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
				Order can not be set to Shipped!<br />
				Item: {$item['e_model']}, Serial/Barcode: {$item['serial_number']} is past six(6) months of Certification</p>";
            }
        }
        else if ($this->in_asset)
        {
            # Update device/accessory comming in
            # from PLACED to IN-TRANSIT

            if (!$this->UpdateAssetRecord($lease_asset,
                $this->facility_id, LeaseAssetTransaction::$PLACED,
                $this->facility_id, LeaseAssetTransaction::$TRANSIT, LeaseAssetTransaction::$RTN))
            {
                $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
				Order can not be set to Shipped!<br />
				Item: {$item['e_model']}, Serial/Barcode: {$item['serial_number']} is not " . LeaseAssetTransaction::$PLACED . " at the facility</p>";
            }
            else
            {
                # Detach accessory coming back
                if ($item['unit_type'] == EquipmentModel::$ACCESSORY)
                {
                    $lease_asset->Attach(NULL);

                    # Set replacement fields in complaint form
                    $this->UpdateComplaintForm($item);
                }
            }
        }

        if ($swap_asset && $swap_asset->GetId() != $item['asset_id'])
        {
            # Update swapped device/accessory comming in
            # FROM PLACED TO IN-TRANSIT

            if ($this->in_return)
            {
                # From placed to transit
                if (!$this->UpdateAssetRecord($swap_asset,
                    $this->facility_id, LeaseAssetTransaction::$PLACED,
                    $this->facility_id, LeaseAssetTransaction::$TRANSIT, LeaseAssetTransaction::$SWAP))
                {
                    $error_msg .= "<p class='error' style='background-color:#E0C0C0;'>
					Order can not be set to Shipped!<br />
					Returning Item: {$swap_asset->getModel()->getNumber()}, Serial/Barcode: " . $swap_asset->getSerial() . " is not " . LeaseAssetTransaction::$PLACED . " at the facility</p>";
                }
                else
                {
                    ## Return ownership
                    if ($sub_status == LeaseAssetTransaction::$PURCHASE)
                    {
                        $active_owner = ($swap_asset->GetDefaultOwner() == FASOwnership::$INI) ? FASOwnership::$INI : FASOwnership::$DEFAULT_ID;
                        FASOwnership::ActivateOwner($swap_asset->GetId(), $active_owner, null, date('Y-m-d H:i:s'));
                    }

                    # Detach accessory coming back
                    if ($item['unit_type'] == EquipmentModel::$ACCESSORY)
                        $swap_asset->Attach(NULL);
                }
            }
        }

        # Update Contract Equipment link for out going assets
        if ($lease_asset && $this->out_asset)
        {
            $previous_id = ($swap_asset) ? (int) $swap_asset->getId() : null;
            $this->UpdateContractEquipment($contract_id, $lease_asset->getId(), $previous_id);
        }

        return $error_msg;
    }

    /**
     * Set replacement fields of the complaint Form
     *
     * @parma array
     */
    private function UpdateComplaintForm($item)
    {
        $order_id = (isset($item['order_id'])) ? $item['order_id'] : $this->id;

        # Update the Complaint Form
        # NOTE: An assumption is made, there will be only one device per swap order
        $sth_cf = $this->dbh->prepare("UPDATE complaint_form_equipment
		SET replacement_model = ?, replacement_serial_number = ?
		WHERE order_id = ?");
        $sth_cf->bindValue(1, (int) $item['model'], PDO::PARAM_INT);
        $sth_cf->bindValue(2, $item['serial_number'], PDO::PARAM_STR);
        $sth_cf->bindValue(3, (int) $order_id, PDO::PARAM_INT);
        $sth_cf->execute();
    }

    /**
     * Update asset_id link in contract_line_item
     *
     * @param integer
     * @param integer
     * @param integer
     */
    private function UpdateContractEquipment($contract_id, $asset_id, $swap_asset_id)
    {
        # Update the Contract Line Item record
        #
        $note = "Equipment updated from order {$this->id}.";
        $date_shipped = date('Y-m-d', $this->ship_date);

        if ($asset_id)
        {
            $line_num = null;
            if ($swap_asset_id)
            {
                $sth = $this->dbh->prepare("SELECT line_num
				FROM contract_line_item
				WHERE asset_id = ?");
                $sth->bindValue(1, $swap_asset_id, PDO::PARAM_INT);
                $sth->execute();
                $line_num = $sth->fetchColumn();

                $note .= "\nAsset ID changed from $swap_asset_id to $asset_id";
            }
            else if ($contract_id)
            {
                # FIND SRV code and model
                # When srv is not found use model
                #
                $sth = $this->dbh->prepare("SELECT coalesce(s.item, e.model), e.model
				FROM lease_asset a
				INNER JOIN equipment_models e ON a.model_id = e.id
				LEFT JOIN service_item_to_product s ON e.model = s.code
				WHERE a.id = ?");
                $sth->bindValue(1, $asset_id, PDO::PARAM_INT);
                $sth->execute();
                list($srv_code, $model) = $sth->fetch(PDO::FETCH_NUM);

                /*
                 * Update the contract equipment record
                 *
                 * Attempt to update a single item if the contract has more
                 * than 1 of the same device model it will get updated later
                 * since each asset is on its own line item
                 *
                 * NOTE: SRV code may be used in contract records
                 */
                $sth = $this->dbh->prepare("SELECT line_num
				FROM contract_line_item
				WHERE contract_id = ?
				AND (item_code = ? OR item_code = ?)
				AND (asset_id IS NULL OR asset_id = 0)
				LIMIT 1");
                $sth->bindValue(1, $contract_id, PDO::PARAM_INT);
                $sth->bindValue(2, $srv_code, PDO::PARAM_STR);
                $sth->bindValue(3, $model, PDO::PARAM_STR);
                $sth->execute();
                $line_num = $sth->fetchColumn();

                $note .= "\nAsset ID for $model ($srv_code) set to $asset_id";
            }
            else
            {
                # Look for a transfer for this asset
                # Need to find specific line number in contract_line_item to link to
                $sth = $this->dbh->prepare("SELECT
					ci.line_num, te.contract_id
				FROM lease_asset a
				INNER JOIN equipment_models e ON a.model_id = e.id
				LEFT JOIN service_item_to_product s ON e.model = s.code
				INNER JOIN lease_transfer_equip_new te ON a.model_id = te.model_id
				INNER JOIN lease_transfer t ON te.transfer_id = t.transfer_id
				INNER JOIN contract_line_item ci
					ON te.contract_id = ci.contract_id
					AND (s.item = ci.item_code OR e.model = ci.item_code)
				WHERE a.id = ?
				AND (t.install_order = ? OR t.purchase_order = ?)
				AND (ci.asset_id IS NULL OR ci.asset_id = 0)");
                $sth->bindValue(1, $asset_id, PDO::PARAM_INT);
                $sth->bindValue(2, $this->id, PDO::PARAM_INT);
                $sth->bindValue(3, $this->id, PDO::PARAM_INT);
                $sth->execute();
                list($line_num, $contract_id) = $sth->fetch(PDO::FETCH_NUM);
            }

            # Assign the value
            if ($line_num)
                ContractLineItem::LinkAsset($line_num, $contract_id, $asset_id, $date_shipped);

            # Add Note to the contract
            if ($contract_id && $this->session_user)
            {
                $sth = $this->dbh->prepare("INSERT INTO contract_notes
				(id_contract,by_user,note) VALUES (?,?,?)");
                $sth->bindValue(1, $contract_id, PDO::PARAM_INT);
                $sth->bindValue(2, $this->session_user->getName(), PDO::PARAM_INT);
                $sth->bindValue(3, $note, PDO::PARAM_STR);
                $sth->execute();
            }
        }
    }

    /**
     * Convert ups service type to  shipping method
     *
     * @param $service int
     *
     * @return $shipping_method string
     */
    static public function UPSto_SM($service)
    {
        # 2,3,4,7,8,12,13,15,17,19,59,65,308
        $shipping_method = "Ground";
        switch ($service)
        {
            case 3:
            case 13:
                $shipping_method = '2 Day';
                break;
            case 59:
                $shipping_method = '2 Day Early AM';
                break;
            case 12:
                $shipping_method = '3 Day';
                break;
            case 2:
                $shipping_method = 'Next Day';
                break;
            case 19:
                $shipping_method = 'Next Day Early AM';
                break;
            case -1:
                $shipping_method = 'Freight';
                break;
            default:
                break;
        }

        return $shipping_method;
    }
}
?>