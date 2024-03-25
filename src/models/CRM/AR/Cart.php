<?php
/***
 *
 * update all in group at processing
 */
class Cart {
    # Protected
    protected $user_id = 0; 				# int
    protected $user_type = 'User'; 			# int
    protected $order_id = 0; 				# int
    protected $order_type = 1;				# int
    protected $facility_id = null;			# int
    protected $facility_name = null;		# int
    protected $accounting_id = null;		# int
    protected $base_price_index = 0;		# int
    protected $cust_price_group_key = null;	# int
    protected $corporate_office_id;			# int
    protected $corporate_parent;			# string
    protected $corporate_office_name;		# string
    protected $tax_amount = 0;				# float
    protected $shipping_amount = 0;			# float

    # Private
    public $loaded = false;				# Bool
    public $price_device = false;		# Bool
    private $changes;						# string

    # Public
    public $items = array();	# array
    public $order;				# Order object

    /**
     * Creates a new Cart object.
     *
     * @param $order_id int
     */
    public function __construct($user_id = null, $user_type = null)
    {
        $this->dbh = DataStor::getHandle();

        $this->user_id = $user_id;
        $this->user_type = $user_type;

        $this->load();
    }

    /**
     * Save new values to the cart record
     */
    public function save($new)
    {
        if ($this->user_id && $this->user_type)
        {
            # Get new values from the array
            #
            $this->order_id = (isset ($new['order_id'])) ? $new['order_id'] : $this->order_id;
            $this->facility_id = (isset ($new['facility_id'])) ? $new['facility_id'] : $this->facility_id;
            $this->order_type = (isset ($new['order_type'])) ? $new['order_type'] : $this->order_type;
            $this->tax_amount = (isset ($new['tax_amount'])) ? $new['tax_amount'] : $this->tax_amount;
            $this->shipping_amount = (isset ($new['shipping_amount'])) ? $new['shipping_amount'] : $this->shipping_amount;

            try
            {
                # INSERT cart record if this is new
                # Otherwise UPDATE
                #
                if ($this->loaded)
                {
                    $sth = $this->dbh->prepare("UPDATE cart SET
						facility_id = ?,
						order_id  = ?,
						order_type = ?,
						tax_amount = ?,
						shipping_amount = ?
					WHERE user_id = ?
					AND user_type = ?");
                }
                else
                {
                    $sth = $this->dbh->prepare("INSERT INTO cart
					(facility_id, order_id, order_type, tax_amount, shipping_amount, user_id, user_type)
					VALUES (?,?,?,?,?,?,?)");
                }

                $sth->bindValue(1, $this->facility_id, ($this->facility_id ? PDO::PARAM_INT : PDO::PARAM_NULL));
                $sth->bindValue(2, $this->order_id, ($this->order_id ? PDO::PARAM_INT : PDO::PARAM_NULL));
                $sth->bindValue(3, (int) $this->order_type, PDO::PARAM_INT);
                $sth->bindValue(4, (float) $this->tax_amount, PDO::PARAM_STR);
                $sth->bindValue(5, (float) $this->shipping_amount, PDO::PARAM_STR);
                $sth->bindValue(6, $this->user_id, PDO::PARAM_INT);
                $sth->bindValue(7, $this->user_type, PDO::PARAM_STR);
                $sth->execute();

                $this->loaded = true;
            }
            catch (PDOException $pdo_exc)
            {
                # Catch unique key violation
                # This is not harmful and can be ingored
                if ($pdo_exc->getCode() == 23505)
                    return;
                else
                    throw $pdo_exc;
            }
        }
    }

    /**
     * Load Cart Object from database
     */
    public function load()
    {
        if ($this->user_id && $this->user_type)
        {
            # Cart record may not exist
            $sth = $this->dbh->prepare("SELECT
				c.order_id, c.order_type, c.facility_id, c.tax_amount, c.shipping_amount,
				ce.name, ce.cust_id, ce.base_price_index, ce.cust_price_group_key
			FROM cart c
			LEFT JOIN v_customer_entity ce ON c.facility_id = ce.id
			WHERE c.user_id = ?
			AND c.user_type = ?");
            $sth->bindValue(1, (int) $this->user_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->user_type, PDO::PARAM_STR);
            $sth->execute();

            if ($cart_row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->order_id = $cart_row['order_id'];
                $this->order_type = $cart_row['order_type'];
                $this->facility_id = $cart_row['facility_id'];
                $this->tax_amount = $cart_row['tax_amount'];
                $this->shipping_amount = $cart_row['shipping_amount'];
                $this->facility_name = $cart_row['name'];
                $this->accounting_id = $cart_row['cust_id'];
                $this->base_price_index = $cart_row['base_price_index'];
                $this->cust_price_group_key = $cart_row['cust_price_group_key'];

                # Indicate the record exists
                $this->loaded = true;

                $this->loadItems();
            }

            # Get the corporate parent information
            if ($this->facility_id)
                $this->loadCP();

        }
    }

    /**
     * Load line items from the database
     */
    public function loadItems()
    {
        if ($this->user_id && $this->user_type)
        {
            $this->items = array();

            $sth = $this->dbh->prepare("SELECT
				c.item_num
			FROM cart_item c
			WHERE c.user_id = ?
			AND c.user_type = ?
			ORDER BY c.item_num");
            $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->user_type, PDO::PARAM_STR);
            $sth->execute();
            while (list($item_num) = $sth->fetch(PDO::FETCH_NUM))
            {
                $this->items[$item_num] = new CartLineItem($this->user_id, $this->user_type, $item_num);
            }
        }
    }

    /**
     * Load Object from database
     */
    public function mimic($order_id)
    {
        if ($order_id)
        {
            # Cart record may not exist
            $sth = $this->dbh->prepare("SELECT
				o.id as order_id, o.type_id as order_type, o.facility_id, o.tax_amount, o.shipping_cost as shipping_amount,
				ce.name, ce.cust_id, ce.base_price_index, ce.cust_price_group_key
			FROM orders o
			LEFT JOIN v_customer_entity ce ON o.facility_id = ce.id
			WHERE o.id = ?");
            $sth->bindValue(1, (int) $order_id, PDO::PARAM_INT);
            $sth->execute();

            if ($cart_row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->order_id = $cart_row['order_id'];
                $this->order_type = $cart_row['order_type'];
                $this->facility_id = $cart_row['facility_id'];
                $this->tax_amount = $cart_row['tax_amount'];
                $this->shipping_amount = $cart_row['shipping_amount'];
                $this->facility_name = $cart_row['name'];
                $this->accounting_id = $cart_row['cust_id'];
                $this->base_price_index = $cart_row['base_price_index'];
                $this->cust_price_group_key = $cart_row['cust_price_group_key'];

                $this->items = array();
                $sth = $this->dbh->prepare("SELECT
					c.item_num,			c.order_id,
					c.prod_id,			c.quantity,
					c.asset_id,			c.swap_asset_id,
					c.uom,				c.price,
					c.whse_id,			c.upsell,
					c.code,				c.name,
					c.description,		p.max_quantity,
					p.price_uom,		p.prod_price_group_key,
					pr.listprice,		pr.preferredprice,
					pr.sheet2price,		pr.sheet3price,
					pr.sheet4price,
					coalesce(NULLIF(u.conversion_factor,0), 1) as conversion_factor,
					e.id as is_device
				FROM order_item c
				INNER JOIN products p ON c.prod_id = p.id
				LEFT JOIN product_pricing pr ON p.id = pr.id
				LEFT JOIN product_uom u ON p.code = u.code AND c.uom = u.uom
				LEFT JOIN equipment_models e on p.code = e.model
				WHERE c.order_id = ?
				ORDER BY c.item_num");

                $sth->bindValue(1, $this->order_id, PDO::PARAM_INT);
                $sth->execute();
                while ($row = $sth->fetch(PDO::FETCH_ASSOC))
                {
                    $this->items[$row['item_num']] = new CartLineItem($this->user_id, $this->user_type, $row['item_num']);
                    $this->items[$row['item_num']]->copyFromArray($row);
                }
            }

            # Get the corporate parent information
            if ($this->facility_id)
                $this->loadCP();
        }
    }

    /**
     * Load the Corporate Parent Info
     *
     */
    public function loadCP()
    {
        if ($this->facility_id)
        {
            $sth = $this->dbh->prepare("SELECT
				o.office_id,
				o.account_id,
				o.office_name
			FROM corporate_office o
			INNER JOIN facilities f
				ON o.account_id = f.corporate_parent
				AND f.id = ?");
            $sth->bindValue(1, $this->facility_id, PDO::PARAM_INT);
            $sth->execute();
            $cp = $sth->fetch(PDO::FETCH_ASSOC);

            $this->corporate_office_id = $cp['office_id'];
            $this->corporate_parent = $cp['account_id'];
            $this->corporate_office_name = $cp['office_name'];
        }

        if ($this->corporate_parent)
        {
            # Set pricing flag
            $tconf = new TConfig();
            $price_equip = explode(",", $tconf->get('price_equip_customers'));
            if (is_array($price_equip) && in_array($this->corporate_parent, $price_equip))
            {
                $this->price_device = true;
            }
        }
    }

    /**
     * Load cart from existing order
     *
     * @param $order_id int
     */
    public function reload($order_id)
    {
        global $user;

        if ($order_id > 0)
        {
            $this->user_id = $user->getId();
            $this->user_type = get_class($user);
            $this->order_id = $order_id;
            $this->items = array();

            $this->dbh->beginTransaction();

            $this->dbh->query("DELETE FROM cart WHERE user_id = {$user->getId()} AND user_type = '{$this->user_type}'");
            $this->dbh->query("DELETE FROM cart_item WHERE user_id = {$user->getId()} AND user_type = '{$this->user_type}'");

            $order = new Order($order_id);
            $this->order_type = $order->getVar('type_id');
            $this->facility_id = $order->getVar('facility_id');

            $sth = $this->dbh->prepare("INSERT
			INTO cart (user_id, user_type, order_id, order_type, facility_id, tax_amount, shipping_amount)
			VALUES (?,?,?,?,?,?,?)");
            $sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->user_type, PDO::PARAM_STR);
            $sth->bindValue(3, $this->order_id, ($this->order_id) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $sth->bindValue(4, $this->order_type, ($this->order_type) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $sth->bindValue(5, $this->facility_id, ($this->facility_id) ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $sth->bindValue(6, $this->tax_amount, ($this->tax_amount) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $sth->bindValue(7, $this->shipping_amount, ($this->shipping_amount) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $sth->execute();

            /*
             * Going to load items for a single order but change status for the whole group
             */
            $sth_ins = $this->dbh->prepare("INSERT INTO cart_item
				(user_id,user_type,item_num,prod_id,order_id,quantity,asset_id,swap_asset_id,uom,price,whse_id,upsell)
			SELECT ?,?,item_num,prod_id,order_id,quantity,asset_id,swap_asset_id,uom,price,whse_id,upsell
			FROM order_item WHERE order_id = ?");
            $sth_ins->bindValue(1, $this->user_id, PDO::PARAM_INT);
            $sth_ins->bindValue(2, $this->user_type, PDO::PARAM_STR);
            $sth_ins->bindValue(3, $order_id, PDO::PARAM_INT);
            $sth_ins->execute();

            // Update group status
            $this->dbh->query("UPDATE orders SET status_id = 99 WHERE id IN (" . implode(",", $order->order_group) . ")");

            # Get the corporate parent information
            if ($this->facility_id)
                $this->loadCP();

            $this->dbh->commit();

            $this->order = new Order($order_id);
        }
    }

    /**
     * Convert item elements to array
     *
     * @return array
     */
    public function GetItemAry()
    {
        $item_ary = array();

        foreach ($this->items as $item)
        {
            $num = $item->getVar('item_num');
            $item_ary[$num] = $item->toArray();
        }

        return $item_ary;
    }

    /**
     * Return the user_id
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Return the user_type
     */
    public function getUserType()
    {
        return $this->user_type;
    }

    /**
     * Return the order id
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Return the order type id
     */
    public function getOrderType()
    {
        return $this->order_type;
    }

    /**
     * Return the facility id
     */
    public function getFacilityId()
    {
        return $this->facility_id;
    }

    /**
     * Return facility name and accounting_id
     * Include corporate parent accounting id if any
     *
     * @return string
     */
    public function getCustomerName()
    {
        $customer_name = htmlentities($this->facility_name, ENT_QUOTES);

        $more = null;

        if ($this->accounting_id)
            $more[] = $this->accounting_id;

        if ($this->corporate_parent)
            $more[] = $this->corporate_parent;

        if ($more)
            $customer_name .= " (" . implode(",", $more) . ")";

        return $customer_name;
    }

    /**
     * Return base price index
     */
    public function getBPI()
    {
        return $this->base_price_index;
    }

    /**
     * Return customer price group
     */
    public function getCPGK()
    {
        return $this->cust_price_group_key;
    }

    /**
     * Return tax amount
     */
    public function getTaxAmount()
    {
        return $this->tax_amount;
    }

    /**
     * Return shipping amount
     */
    public function getShippingAmount()
    {
        return $this->shipping_amount;
    }

    /**
     * Add item to the cart
     *
     * @param $prod_id int
     * @param $quantity int
     * @param $asset_id int
     */
    public function addToCart($prod_id, $quantity, $asset_id, $swap_asset_id, $uom, $price, $whse_id, $upsell, $setprice)
    {
        global $user;

        $result = true;

        if ($quantity > 0)
        {
            $next_item = count($this->items) + 1;

            # Get converted total
            $qty = $quantity * LineItem::UOMConversion($prod_id, $uom);
            $total_qty = 0;

            foreach ($this->items as $i => $item)
            {
                # item_num must be unique
                if ($item->getVar('item_num') >= $next_item)
                    $next_item = $item->getVar('item_num') + 1;

                # accumulate total qty for this product
                if ($item->getVar('prod_id') == $prod_id)
                    $total_qty += $item->getVar('quantity') * $item->getVar('conversion_factor');
            }

            $max_ord_qty = self::GetMaxOrdQty($prod_id);

            if ($max_ord_qty > 0 && ($total_qty + $qty) > $max_ord_qty && $user->inField())
            {
                echo "<p class='error_msg'>ALERT: Max Order quantity exceeded ({$max_ord_qty}).</p>";
                $result = false;
            }
            else
            {
                $total_qty += $qty;

                $item = new CartLineItem($this->user_id, $this->user_type, $next_item);

                # Build an array containing new values
                $item_ary['order_id'] = $this->order_id;
                $item_ary['prod_id'] = $prod_id;
                $item_ary['quantity'] = $quantity;
                $item_ary['asset_id'] = $asset_id;
                $item_ary['swap_asset_id'] = $swap_asset_id;
                $item_ary['uom'] = $uom;
                $item_ary['price'] = $price;
                $item_ary['whse_id'] = $whse_id;
                $item_ary['upsell'] = $upsell;

                # Fill additional product fields and copy data
                # into the class
                #
                $item->copyFromArray($item_ary);
                $item->loadProductInfo();
                $item->price_device = $this->price_device;

                # Some instances require price to be set/reset
                # Updates to existing orders may require charge
                #
                if ($price != "*T.B.D." && $user->hasAccessToApplication('priceoverride'))
                {
                    # User has set the price
                    $setprice = false;
                }

                if ($setprice || $price < 0)
                {
                    # Find proper charge amount
                    $price = $item->SetPriceInfo($this->order_type, $this->getBPI(), $this->getCPGK());
                }
                else
                {
                    # Validate the price, force double
                    $price = (double) $price;
                }

                if ($price < 0)
                    $price = 0.0;
                $item->setVar('price', $price);

                $item->insert();
                $this->items[$next_item] = $item;

                # Order was changed
                $order_id = ($this->order_id) ? $this->order_id : "New";
                $this->changes .= "Order: {$order_id}\n" .
                    "Product Added\n" .
                    "Type: {$this->order_type}\n" .
                    "Customer ID: {$this->facility_id}\n" .
                    "Product: {$prod_id}\n" .
                    "Quatity: {$quantity}\n" .
                    "UOM: {$uom}\n" .
                    "Price: $price\n";
            }
        }

        return $result;
    }

    /**
     * Modify quantity
     *
     * @param $prod_id int
     * @param $quantity int
     * @param $asset_id int
     */
    public function changeQuantity($item_num, $quantity, $uom, $price, $prod_id, $upsell, $setprice)
    {
        global $user;

        $result = true;

        $total_qty = 0;
        $found = -1;

        # Find the item
        foreach ($this->items as $i => $item)
        {
            # Found the Match
            if ($i == $item_num)
                $found = $i;
            # Total for this product (Exclude matched item)
            else if ($item->getVar('prod_id') == $prod_id)
                $total_qty += $item->getVar('quantity') * $item->getVar('conversion_factor');
        }

        # Update the item
        if ($found >= 0)
        {
            $i = $found;
            $item = $this->items[$i];

            if ($quantity > 0) # Update the quantity
            {
                $qty = $quantity * LineItem::UOMConversion($prod_id, $uom);
                $max_qty = $item->getVar('max_quantity');

                if ($max_qty > 0 && ($total_qty + $qty) > $max_qty && $user->inField())
                {
                    echo "<p class='error_msg'>ALERT: Maximum order quantity for item #{$item->getVar('code')} is {$max_qty}.</p>";
                    $result = false;
                }
                else
                {
                    $total_qty += $qty;

                    # Set new item properties
                    #
                    $item->setVar('uom', $uom);
                    $item->setVar('quantity', $quantity);
                    $item->setVar('upsell', $upsell);
                    $item->price_device = $this->price_device;

                    # Some instances require price to be set/reset
                    # Updates to existing orders may require charge
                    #
                    if ($price != "*T.B.D." && $user->hasAccessToApplication('priceoverride'))
                    {
                        # User has set the price
                        $setprice = false;
                    }

                    if ($setprice || $price < 0)
                    {
                        # Find proper charge amount
                        $price = $item->SetPriceInfo($this->order_type, $this->getBPI(), $this->getCPGK());
                    }
                    else
                    {
                        # Validate the price, force double
                        $price = (double) $price;
                    }

                    if ($price < 0)
                        $price = 0.0;
                    $item->setVar('price', $price);

                    # Save
                    $item->update();

                    # Order was changed
                    $order_id = ($this->order_id) ? $this->order_id : "New";
                    $this->changes .= "Order: {$order_id}\n" .
                        "Quantity Changed\n" .
                        "Type: {$this->order_type}\n" .
                        "Customer ID: {$this->facility_id}\n" .
                        "Product: {$prod_id}\n" .
                        "Quatity: {$quantity}\n" .
                        "UOM: {$uom}\n" .
                        "Price: $price\n";
                }
            }
            else
            {
                #
                # Remove the asset transaction
                #
                self::RevertAssetTransaction($item, $this->order_type);

                # Remove the db record
                #
                $item->delete();

                # Order was changed
                $order_id = ($this->order_id) ? $this->order_id : "New";
                $this->changes .= "Order: {$order_id}\n" .
                    "Item Removed\n" .
                    "Type: {$this->order_type}\n" .
                    "Customer ID: {$this->facility_id}\n" .
                    "Product: {$prod_id}\n";

                unset($this->items[$i]);
            }
        }

        return $result;
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
                $this->{$key} = trim($value);
            }
        }
    }

    /**
     * Create a result array for all matching records
     *
     * @param $cust_pricing_group_key int
     * @param $prod_pricing_group_key int
     *
     * @return array
     */
    static public function GetPriceGroup($cust_price_group_key, $prod_price_group_key)
    {
        $ppg = array();

        $dbh = DataStor::getHandle();
        if ($cust_price_group_key && $prod_price_group_key)
        {
            $sth = $dbh->prepare("SELECT
				price_method, range_start, range_end, percent_adj, amount_adj
			FROM product_pricing_group
			WHERE cust_price_group_key = ?
			AND prod_price_group_key = ?
			AND date_effective < CURRENT_DATE
			AND date_expiration >= CURRENT_DATE");
            $sth->bindValue(1, $cust_price_group_key, PDO::PARAM_INT);
            $sth->bindValue(2, $prod_price_group_key, PDO::PARAM_INT);
            $sth->execute();

            $ppg = $sth->fetchAll(PDO::FETCH_ASSOC);
        }

        return $ppg;
    }

    /**
     * Get Max order amount for the product
     *
     * @param $prod_id int
     *
     * @return $max_qty int
     */
    static public function GetMaxOrdQty($prod_id)
    {
        $max_qty = 0;

        $dbh = DataStor::getHandle();
        if ($dbh && $prod_id > 0)
        {
            $sth = $dbh->query("SELECT max_quantity FROM products p WHERE p.id = {$prod_id}");
            $max_qty = (int) $sth->fetchColumn();
        }

        return $max_qty;
    }

    /**
     * Get available inventory amount for the product converted to the given UOM
     *
     * @param $prod_code mixed (can be prod_id or code)
     * @param $is_code bool
     *
     * @return $avail_qty int
     */
    static public function GetAvailQty($prod_code, $is_code = false, $WhseID = "RENO", $Company = null)
    {
        global $conf;

        # Non inventoried parts return null
        $avail_qty = null;
        $mdbh = null;
        /**
         * For now using Configuration File var
         *
         *
                try
                {
                    if ($conf)
                        $do_lookup = $conf->get('perform_inventory_lookup');
                    else
                        $do_lookup = 0;
                }
                catch (Exception $exc)
                {
                    # Just making sure this does not stop normal execution
                    $do_lookup = 0;
                }
        */
        $do_lookup = Config::$perform_inventory_lookup;

        # Inventory lookup is configurable
        if ($do_lookup)
        {
            $mdbh = DataStor::getHandle(false);
            $dbh = DataStor::getHandle();
            if (!$Company)
                $Company = Config::$Company;
            if (!$WhseID)
                $WhseID = "RENO";

            $code = ($is_code) ? $prod_code : null;

            if ($dbh && is_null($code))
            {
                $sth = $dbh->prepare("SELECT code FROM products p WHERE p.id = ?");
                $sth->bindValue(1, (int) $prod_code, PDO::PARAM_INT);
                $sth->execute();
                if ($row = $sth->fetch(PDO::FETCH_NUM))
                    $code = $row[0];
            }
        }

        # Find the available qty for the product
        if ($mdbh && $code)
        {
            $ItemType = SalesOrder::GetItemType($code, $Company);

            if ($ItemType >= 5)
            {
                $avail_qty = 0;

                # -- For now get all bins AND wb.WhseBinID = ?
                $sth = $mdbh->prepare("SELECT
				sum(bi.QtyOnHand - bi.PendQtyDecrease)
				FROM timWhseBinInvt bi
				INNER JOIN timItem t ON bi.ItemKey = t.itemKey
				INNER JOIN timWhseBin wb ON bi.WhseBinKey = wb.WhseBinKey
				INNER JOIN timWarehouse w ON wb.WhseKey = w.WhseKey
				WHERE w.CompanyID = ?
				AND w.WhseID = ?
				AND t.ItemID = ?");
                $sth->bindValue(1, $Company, PDO::PARAM_STR);
                $sth->bindValue(2, $WhseID, PDO::PARAM_STR);
                $sth->bindValue(3, $code, PDO::PARAM_STR);
                $sth->execute();
                if ($row = $sth->fetch(PDO::FETCH_NUM))
                {
                    $avail_qty = (int) $row[0];
                }
            }

        }

        return $avail_qty;
    }

    /**
     * Get total quantity from all queded and processed orders
     * for the product converted to the base UOM
     *
     * @param $prod_id int
     * @param $order_id int
     *
     * @return int
     */
    static public function GetPendingQty($prod_id, $order_id)
    {
        $pend_qty = 0;

        $dbh = DataStor::getHandle();
        if ($dbh)
        {
            # If working on an existing order, dont include it in the count
            $exclude_order = ($order_id > 0) ? "AND  o.id != {$order_id}" : "";

            $sth = $dbh->prepare("SELECT sum(i.quantity * coalesce(NULLIF(u.conversion_factor,0), 1))
			FROM order_item i
			INNER JOIN orders o ON i.order_id = o.id
			INNER JOIN products p ON i.prod_id = p.id
			LEFT JOIN product_uom u ON p.code = u.code AND i.uom = u.uom
			WHERE p.id = ?
			AND o.status_id IN (1,2)
			{$exclude_order}");
            $sth->bindValue(1, (int) $prod_id, PDO::PARAM_INT);
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_NUM))
            {
                $pend_qty = $row[0];
            }
        }

        return $pend_qty;
    }

    /**
     * Goal is to return the device to the previous state before order/complaint form update
     * Duplicate the previous Transaction's facility, status and substatus
     *
     * @param object (mixed) $item
     * @param string
     */
    static public function RevertAssetTransaction($item, $order_type)
    {
        global $user;

        $line_item = $item;

        # No action for cancellation
        if ($order_type == Order::$CANCELLATION_ORDER)
            return;

        # Convert to CartLineItem
        if (is_array($item))
        {
            $line_item = new CartLineItem();
            $line_item->copyFromArray($item);
        }

        # Common components
        $order_id = $line_item->getVar('order_id');
        $asset_id = $line_item->getVar('asset_id');
        $swap_asset_id = $line_item->getVar('swap_asset_id');
        $comment = "Device was removed from Order ($order_id)";

        # Return status of the asset to Placed
        if (isset ($asset_id) && $asset_id > 0)
        {
            $device = new LeaseAsset($asset_id);
            $history = $device->getAllTransactions();

            # Make sure these is history
            if (isset ($history[0]))
            {
                # Current values
                $current_status = $history[0]->getStatus();
                $status = $history[0]->GetStatus();
                $sub_status = $history[0]->GetSubStatus();
                $location = $history[0]->GetFacility()->getId();

                # Get values from Previous Transaction
                if (isset ($history[1]))
                {
                    $status = $history[1]->GetStatus();
                    $sub_status = $history[1]->GetSubStatus();
                    $location = $history[1]->GetFacility()->getId();
                }
                else
                {
                    $comment .= "\nCould not determine previous status!";
                }

                # Incoming Device
                if ($current_status == LeaseAssetTransaction::$TRANSIT)
                {
                    # Expect to see Placed transaction
                    if ($status != LeaseAssetTransaction::$PLACED)
                        $comment .= "\nUnexpected status found ($status), Placed expected!";

                    # Duplicate Previous Transaction
                    $device->addTransaction($location, $status, $sub_status, $user, $comment);
                }
                # Outgoing Device
                else if ($current_status == LeaseAssetTransaction::$PLACED)
                {
                    # Expect to see FGI transaction
                    if ($status != LeaseAssetTransaction::$FGI)
                        $comment .= "\nUnexpected status found ($status), FGI expected!";

                    # Duplicate Previous Transaction
                    $device->addTransaction($location, $status, $sub_status, $user, $comment);
                }
            }
        }

        # Return status of the swapped asset to Placed
        if (isset ($swap_asset_id) && $swap_asset_id > 0)
        {
            $device = new LeaseAsset($swap_asset_id);
            $history = $device->getAllTransactions();

            # Make sure these is history
            if (isset ($history[0]))
            {
                $current_status = $history[0]->getStatus();
                $status = $history[0]->GetStatus();
                $sub_status = $history[0]->GetSubStatus();
                $location = $history[0]->GetFacility()->getId();

                # Get values from Previous Transaction
                if (isset ($history[1]))
                {
                    $status = $history[1]->GetStatus();
                    $sub_status = $history[1]->GetSubStatus();
                    $location = $history[1]->GetFacility()->getId();
                }
                else
                {
                    $comment .= "\nCould not determine previous status!";
                }

                # Inbound Device
                if ($current_status == LeaseAssetTransaction::$TRANSIT)
                {
                    # Expect to see Placed transaction
                    if ($status != LeaseAssetTransaction::$PLACED)
                        $comment .= "\nUnexpected status found ($status), Placed expected!";

                    # Duplicate Previous Transaction
                    $device->addTransaction($location, $status, $sub_status, $user, $comment);
                }
            }
        }
    }

    /**
     * Set bpi value
     *
     * @param integer
     */
    public function setBPI($bpi)
    {
        $this->base_price_index = $bpi;
    }

    /**
     * Set customer price group value
     *
     * @param integer
     */
    public function setCPGK($CPGK)
    {
        $this->cust_price_group_key = $CPGK;
    }

    /**
     * Set the order type
     */
    public function setOrderType($type)
    {
        $this->order_type = $type;
    }

    /**
     * Update Items in the cart
     *
     * @param $form array
     */
    public function UpdateItems($form, $action)
    {
        global $user;

        $prod_ids = isset ($form['prod_ids']) ? $form['prod_ids'] : null;
        $quantities = isset ($form['quantities']) ? $form['quantities'] : array();
        $assets = isset ($form['assets']) ? $form['assets'] : array();
        $swap_assets = isset ($form['swap_assets']) ? $form['swap_assets'] : array();
        $uom_ary = isset ($form['uoms']) ? $form['uoms'] : array();
        $price_ary = isset ($form['prices']) ? $form['prices'] : array();
        $setprice_ary = isset ($form['setprice']) ? $form['setprice'] : array();
        $whse_ary = isset ($form['whses']) ? $form['whses'] : array();
        $upsell_ary = isset ($form['upsell']) ? $form['upsell'] : array();

        $success = true;

        if ($prod_ids && is_array($prod_ids))
        {
            foreach ($prod_ids as $i => $prod_id)
            {
                $prod_id = (int) $prod_id;
                $quantity = (isset ($quantities[$i])) ? (int) $quantities[$i] : 0;
                $asset_id = (isset ($assets[$i])) ? (int) $assets[$i] : 0;
                $swap_asset_id = (isset ($swap_assets[$i])) ? (int) $swap_assets[$i] : 0;
                $uom = (isset ($uom_ary[$i])) ? $uom_ary[$i] : 'EA';
                $price = (isset ($price_ary[$i])) ? str_replace(array("\$", ","), array("", ""), $price_ary[$i]) : -1;
                $setprice = (isset ($setprice_ary[$i])) ? $setprice_ary[$i] : 0;
                $whse_id = (isset ($whse_ary[$i])) ? $whse_ary[$i] : '';
                $upsell = (isset ($upsell_ary[$i])) ? $upsell_ary[$i] : 0;

                # Either add new item or update the quantity
                if ($action == 'add to cart' && $prod_id > 0 && $quantity > 0)
                    $success &= $this->addToCart($prod_id, $quantity, $asset_id, $swap_asset_id, $uom, $price, $whse_id, $upsell, $setprice);
                else if ($action == 'update quantity' && $prod_id > 0)
                    $success &= $this->changeQuantity($i, $quantity, $uom, $price, $prod_id, $upsell, $setprice);
            }
        }

        if ($this->changes)
        {
            require_once ('DataChangeLog.php');
            DataChangeLog::log_changes($user, "Cart Update", $this->changes);
        }

        return $success;
    }
}
?>