<?
require_once ('classes/Config.php');
require_once ('classes/Order.php');

/**
 * @package Freedom
 * @author Aron Vargas
 */

class OrderProcess {
    protected $dbh = null;	# Object

    protected $order_id = null;		# Int
    protected $type_ary = null;		# Array
    protected $order_type = 1;		# Int
    protected $facility_id = null;	# Int
    protected $cat_id = null;		# Int
    protected $dev_id = null;		# Int
    protected $section = 'Catalog';	# Int
    protected $catalog = 1;			# Int
    protected $user_type;			# string
    protected $customer = null;		# object
    protected $left = '';			# string
    protected $contents = '';		# string
    protected $right = '';			# string
    protected $cart_count = 0;		# Int
    protected $navigation = '';		# string
    protected $allowed_types;		# Array
    protected $disabled = false;	# bool

    protected $show_patient = false; # bool
    protected $bpi = 0;				# int
    protected $cpgk = 0;			# int

    public $limit_user = true;
    public $field_user = true;
    public $site_options;
    public $per_page = 20;

    public $msg = '';				# string
    protected $checkout_form;		# array

    # Static Vars
    static public $CORPORATE_CATALOG = 1;
    static public $CPM_CATALOG = 2;
    static public $FACILITY_CATALOG = 3;
    static public $SERVICE_CATALOG = 3;
    static public $ININC_CATALOG = 6;

    # Show these types only for webuser
    # 1: Supplies,	2: Parts,	3: Swaps,	8: Customer,
    # 9: RMA,		10: Return,	12: Web,	13: DME,
    # 14: Accessory Swap
    static public $LIMITED_TYPES = array(1, 2, 3, 8, 9, 10, 12, 13, 14);

    static public $WIDE_BANNER = "images/Banner800x162.png";
    static public $MED_BANNER = "images/Champions-in-Common-250x250.gif";
    static public $price_override;

    static public $ABR = "ABR";

    public function __construct($args = array())
    {
        global $user, $sh, $allowed_types, $hippa_access;

        $this->dbh = DataStor::getHandle();

        if (isset ($args['order_id']))
            $this->order_id = $args['order_id'];
        if (isset ($args['facility_id']))
            $this->facility_id = $args['facility_id'];
        if (isset ($args['cat_id']))
            $this->cat_id = $args['cat_id'];
        if (isset ($args['dev_id']))
            $this->dev_id = $args['dev_id'];
        if (isset ($args['section']))
            $this->section = $args['section'];

        # Determine these fields first avoid multiple lookups
        $this->field_user = $user->inField();
        $this->limit_user = ($this->field_user || $user->web_user);

        if ($this->order_id)
        {
            if (isset ($args['order_type']))
                $this->order_type = $args['order_type'];
        }
        else
        {
            if ($this->field_user)
                $this->order_type = Order::$SUPPLY_ORDER;
            else if ($user->web_user)
                $this->order_type = Order::$WEB_ORDER;
            else
                $this->order_type = Order::$CUSTOMER_ORDER;
        }

        $this->allowed_types = $allowed_types;
        $this->user_type = get_class($user);

        # Determine Facility ID for web users and field users
        # These users are limited into which account they can access
        if ($user->web_user)
            $this->facility_id = $user->getID();
        else
        {
            $this->site_options = Order::LookupCPMFacility($user->getId());
            if ($this->field_user)
            {
                if (is_array($this->site_options))
                {
                    if (!in_array($this->facility_id, $this->site_options))
                        $this->facility_id = $this->site_options[0];
                }
                else
                    $this->facility_id = $this->site_options;
            }

            $preferences = new Preferences($user);
            $this->per_page = $preferences->get('general', 'results_per_page');
        }

        # Allow patient information to be shown?
        $this->show_patient = ($sh && $sh->isOnLAN() && $user->hasAccessToApplication($hippa_access));

        # Populate customer
        $this->customer = new CustomerEntity($this->facility_id);

        # Get users catalog id
        $this->SetCatalog();
    }

    /**
     * Perform requested action
     *
     * @param string
     * @param array
     */
    public function ActionHandler($act, $form)
    {
        if ($this->facility_id)
        {
            $this->SetCC();

            # Perform Cart Update
            #
            if ($act == 'add to cart' || $act == 'update quantity')
            {
                $this->UpdateCart($form);
            }
            else if ($act == 'duplicate')
            {
                $this->Duplicate($form);
            }
            else if ($act == 'supplies')
            {
                $this->AddSupplies($form);
            }
            else if ($act == 'edit' && isset ($form['order_id']))
            {
                if ($this->cart_count > 0)
                    $this->HoldOrder();

                $this->EditOrder($form);
            }
            else if ($act == 'hold')
            {
                $this->HoldOrder();
            }
            else if ($act == 'cancel' && isset ($form['order_id']))
            {
                $this->CancelOrder($form);

                # Avoid displaying cancelled order
                unset($_REQUEST['order_id']);
            }
            else if ($act == 'update_tax')
            {
                $this->UpdateTax();
            }
            else if ($act == 'update_shipping')
            {
                $this->UpdateShipping();
            }
            else if ($act == 'set_fid')
            {
                $this->SetCustomer($form);
            }
            else if ($act == 'process')
            {
                # Build the order use save form
                $this->order = $this->CreateOrder($form);
            }
        }
    }

    /**
     * Update order by adding items defined for the device model
     *
     * @param array $form
     */
    private function AddSupplies($form)
    {
        global $user, $this_app_name, $preferences;

        $model_id = (isset ($form['model_id'])) ? (int) $form['model_id'] : 0;
        $update_shipping = (isset ($form['update_shipping'])) ? $form['update_shipping'] : 0;
        $update_tax = (isset ($form['update_tax'])) ? $form['update_tax'] : 0;

        $cart = new Cart($user->getId(), $this->user_type, $this->order_type, $this->facility_id);
        $cart->save(array(
            'order_type' => (int) $this->order_type,
            'facility_id' => $this->facility_id)
        );
        $car->loadCP();

        # UpdateItems handles adding new, updating quantity, and removing items
        if ($model_id)
        {
            $items = array();
            $sth = $this->dbh->prepare("SELECT
				e.prod_id,
				1 as quantity,
				p.price_uom as uom
			FROM equipment_supply e
			INNER JOIN products p on e.prod_id = p.id AND p.active = true
			WHERE e.model_id = ?");
            $sth->bindValue(1, $model_id, PDO::PARAM_INT);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                if ($this->InCatalog($row['prod_id']))
                {
                    $items['prod_ids'][] = $row['prod_id'];
                    $items['quantities'][] = $row['quantity'];
                    $items['uoms'][] = $row['uom'];
                }
            }

            $cart->UpdateItems($items, 'add to cart');
        }

        # Remove cart record if this has no items
        if (count($cart->items) == 0)
        {
            $sth = $this->dbh->prepare("DELETE FROM cart WHERE user_id = ?");
            $sth->bindValue(1, (int) $user->getId(), PDO::PARAM_INT);
            $sth->execute();

            # Current options
            $this->order_id = 0;

            $args = array(
                'order_id' => 0,
                'order_type' => $this->order_type,
                'facility_id' => $this->facility_id,
                'cat_id' => $this->cat_id,
                'section' => $this->section);
            $preferences->set($this_app_name, '_order', serialize($args), $_COOKIE['session_id']);

        }
        else # Update the tax and shipping amounts
        {
            if ($update_tax)
                $this->UpdateTax($cart);
            if ($update_shipping)
                $this->UpdateShipping($cart);
        }
    }

    /**
     * Cancel an order
     *
     * @param array $form
     */
    protected function CancelOrder($form)
    {
        global $user, $this_app_name, $preferences;

        $order_id = (isset ($form['order_id'])) ? $form['order_id'] : 0;

        if ($order_id)
        {
            $order = new Order($order_id);
            if ($order->getVar('status_id') == Order::$QUEUED ||
                $order->getVar('status_id') == Order::$EDITING)
            {
                $reason = '';
                $order->change('status_id', Order::$DELETED);

                # Order was changed
                $params["change"] = "Order: {$order_id} Cancelled\n" .
                    "Updated By: {$user->getName()}\n" .
                    "Status: {$order->getVar('status_id')}\n" .
                    "Type: {$order->getVar('type_id')}\n" .
                    "Customer ID: {$order->getVar('facility_id')}";
                ActionLog::log($user, "Cancel", $this_app_name, basename($_SERVER['PHP_SELF']), $params);

                $this->msg .= "<p class=\"info\" style=\"text-align:center\">Order # <b>{$order_id}</b> has been cancelled.</p>";

                $this->ClearSession();
            }
            else
                $this->msg .= "<p class=\"error\" style=\"text-align:center\">This order cannot be Canceled.</p>";
        }
    }

    /**
     * Remove / Reset session information
     */
    private function ClearSession()
    {
        global $user, $this_app_name, $preferences;

        # Reset vars to release features
        $this->order_id = null;
        $this->cart_count = 0;

        # Delete old preference record
        #
        $sth = $this->dbh->prepare("DELETE FROM preferences
		WHERE user_id = ?
		AND (key = ? OR key = ?)
		AND application_id = (SELECT id FROM applications WHERE short_name = ?)");
        $sth->bindValue(1, $user->getId(), PDO::PARAM_INT);
        $sth->bindValue(2, '_order', PDO::PARAM_STR);
        $sth->bindValue(3, '_checkout', PDO::PARAM_STR);
        $sth->bindValue(4, $this_app_name, PDO::PARAM_STR);
        $sth->execute();

        # Reset current parameters.
        #
        if (isset ($_COOKIE['session_id']))
        {
            $order_type = Order::$CUSTOMER_ORDER;
            $this->site_options = Order::LookupCPMFacility($user->getId());
            if ($this->field_user)
            {
                $order_type = Order::$SUPPLY_ORDER;

                if (is_array($this->site_options))
                {
                    if (!in_array($this->facility_id, $this->site_options))
                        $this->facility_id = $this->site_options[0];
                }
                else
                    $this->facility_id = $this->site_options;
            }
            else if ($user->web_user)
            {
                $order_type = Order::$WEB_ORDER;
                $this->facility_id = $user->getID();
            }

            # Current options
            $args = array(
                'order_id' => 0,
                'order_type' => $order_type,
                'facility_id' => $this->facility_id,
                'cat_id' => $this->cat_id,
                'section' => $this->section);
            $preferences->set($this_app_name, '_order', serialize($args), $_COOKIE['session_id']);
        }

    }

    /**
     * Determine Company name
     */
    public function CompanyName($cust_id)
    {
        $company_name = self::$ABR;

        if ($cust_id && preg_match('/^\d*$/', $cust_id))
        {
            $company_name = self::$ININC_ABR;
        }

        return $company_name;
    }

    /**
     * Display html for Order Confirmation
     *
     * @param array
     * @return object
     */
    private function CreateOrder($form)
    {
        global $user, $preferences, $this_app_name;

        // Only need to update tax and shipping cost
        // Other fields will be stored in _checkout form in preferences table
        # Save new form
        if (isset ($_COOKIE['session_id']) && isset ($form['load_tstamp']))
            $preferences->set($this_app_name, '_checkout', serialize($form), $_COOKIE['session_id']);

        $cart = new Cart($user->getId(), $this->user_type);

        $has_items = count($cart->items);

        // If tax or shipping has not been set perform webservice request
        if (empty ($form['tax_amount']))
            $form['tax_amount'] = $this->UpdateTax($cart);

        if (empty ($form['shipping_cost']))
            $form['shipping_cost'] = $this->UpdateShipping($cart);

        $existingOrder = false;
        if ($this->order_id)
        {
            $existingOrder = true;
        }
        # Init
        $order = null;

        # Queue order if customer is a web user
        if ($user->web_user)
            $form['status_id'] = Order::$QUEUED;

        # Check customer status
        #
        if ($this->facility_id > 0 && $this->order_type != Order::$SUPPLY_ORDER)
        {
            $sth = $this->dbh->query("SELECT credit_hold FROM v_customer_entity WHERE id = {$this->facility_id}");
            list($credit_hold) = $sth->fetch(PDO::FETCH_NUM);

            # Queue order if customer is on credit hold
            if ($credit_hold)
                $form['status_id'] = Order::$QUEUED;

            # Type must be set to process installs in queued status
            if ($this->order_type == Order::$INSTALL_ORDER)
                $form['type_id'] = $this->order_type;
        }

        # Make sure there are items to be processed (Refresh can cause this)
        if ($this->cart_count == 0)
        {
            $this->msg .= "<p class='error_msg'>This order has already been processed.</p>";
            $this->order_id = null;
        }
        else
        {
            # Create order
            #
            ## Can be removed at checkout (but we know who you are)
            if (empty ($form['facility_id']))
                $form['facility_id'] = $this->facility_id;

            $order = $this->InitOrder($form);
            $order_id = $order->processOrder($form);
            if ($order_id)
            {
                # Order was changed
                $params["change"] = "Order: {$order_id}\n" .
                    "Updated By: {$user->getName()}\n" .
                    "Status: {$order->getVar('status_id')}\n" .
                    "Type: {$order->getVar('type_id')}\n" .
                    "Customer ID: {$order->getVar('facility_id')}";
                ActionLog::log($user, 'process', $this_app_name, basename($_SERVER['PHP_SELF']), $params);
                $order->load();

                $this->ClearSession();

                # Reset Navigation sections
                $this->navigation = $this->ShowOptions();
                $this->navigation .= $this->ShowCustomerInfo();

                $this->msg = "<p class='error_msg'>Thank You for your order!</p>";

                // Check for code 99997
                $codes = array();
                if ($has_items)
                {
                    $items = $cart->items;
                    foreach ($items as $item)
                    {
                        $codes[] = $item->getVar('code');
                    }
                }
                // Send email if found 9999x
                $codesToCheck = $this->GetCodesToCheck();
                foreach ($codesToCheck as $code)
                {
                    if (in_array($code, $codes))
                    {
                        $this->sendMail($order, $existingOrder, $code);
                    }
                }

                //echo "<pre>";print_r($codes);echo "</pre>";exit;
            }
            else
            {
                $this->msg = "<p class=\"info\" style=\"text-align:center\">There was a problem processing your order!</p>";
            }
        }

        return $order;
    }

    /**
     * Upload this file to the Estore
     *
     * @param string
     */
    static public function EStoreImageAdd($file_path)
    {
        $ESTORE_HOSTNAME = Config::$ESTORE_HOSTNAME;
        $WPATH = Config::$WEB_PATH;

        $mime = mime_content_type($file_path);
        $info = pathinfo($file_path);
        $file_name = $info['basename'];
        $output = new CURLFile($file_path, $mime, $file_name);
        $data = array('act' => 'a', 'img_file' => $output);

        $ch = curl_init("https://{$ESTORE_HOSTNAME}{$WPATH}/secure/imgupload.php");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        # Debug Information
        echo "<pre>
		PATH: $file_path
		MIME: $mime
		NAME: $file_name

		INFO:";
        print_r($info);

        echo "RESULT:";
        print_r($result);
        echo "</pre>";

        if (curl_errno($ch))
        {
            $eror_msg = curl_error($ch);

            # Debug Information
            echo "<pre>ERROR:";
            print_r($eror_msg);
            echo "</pre>";
        }
        curl_close($ch);

        # Note: Not really doing anything with this error for now
    }

    /**
     * Send a request to remove the file from the estore
     *
     * @param string
     */
    static public function EStoreImageRemove($file_path)
    {
        $ESTORE_HOSTNAME = Config::$ESTORE_HOSTNAME;
        $WPATH = Config::$WEB_PATH;

        $info = pathinfo($file_path);
        $file_name = $info['basename'];
        $data = array('act' => 'r', 'image' => $file_name);

        $ch = curl_init("https://{$ESTORE_HOSTNAME}{$WPATH}/secure/imgupload.php");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $result = curl_exec($ch);

        # Debug Information
        echo "<pre class='hidden'>";
        print_r($result);
        echo "</pre>";

        if (curl_errno($ch))
        {
            $eror_msg = curl_error($ch);

            # Debug Information
            echo "<pre class='hidden'>ERROR:";
            print_r($eror_msg);
            echo "</pre>";
        }
        curl_close($ch);

        # Note: Not really doing anything with this error for now
    }


    /**
     * Load address information from Accounting System
     *
     * @return array
     */
    protected function GetAccountingAddr()
    {
        $addresses = array();

        $sth = $this->dbh->prepare("SELECT
			c.first_name,
			c.last_name,
			c.title,
			c.address1,
			c.address2,
			c.city,
			trim(c.state) AS state,
			c.zip,
			c.phone,
			c.fax,
			coalesce(cn.abbr,'US') as country,
			c.accounting_reference_key,
			j.default_billing,
			j.default_shipping
		FROM contact c
		INNER JOIN facility_contact_join j ON c.contact_id = j.contact_id
		LEFT JOIN countries cn ON c.country_id = cn.id
		WHERE j.facility_id = ?
		AND c.accounting_reference_key > 0");
        $sth->bindValue(1, $this->facility_id, PDO::PARAM_STR);
        if ($sth->execute())
        {
            $addresses = $sth->fetchALL(PDO::FETCH_ASSOC);
        }

        return $addresses;
    }

    /**
     * Load address information
     * for Other Address if this type address was
     * stored for this order.
     * If not, just return an empty array.
     *
     * @return array
     */
    protected function getOtherAddress($order_id)
    {
        $addresses = array();

        if (!$order_id)
            return $addresses;

        $sql = "SELECT
                            o.sname AS sname,
	    		    o.ship_attn AS ship_attn,
			    o.address AS address1,
			    o.address2 AS address2,
			    o.city AS city,
			    trim(o.state) AS state,
			    o.zip AS zip,
			    o.phone AS phone,
			    o.fax AS fax,
			    coalesce(o.country,'US') AS country
		        FROM orders o
		        WHERE o.id = ?";


        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, $order_id, PDO::PARAM_INT);
        if ($sth->execute())
        {
            $addresses = $sth->fetchALL(PDO::FETCH_ASSOC);
        }

        return $addresses;

    }#getOrderAddress()




    /**
     * Build input html for address inputs
     *
     * @param object
     * @return string
     */
    private function GetAddressInputs($order)
    {
        global $user;

        $sname = $order->getHTMLVar('sname');
        $sname = ($sname && $user->web_user)
            ? "<input type='hidden' name='sname' value='$sname' />$sname"
            : "<input type='text' name='sname' value='$sname' size=20 maxlength=64 />";

        $ship_attn = $order->getHTMLVar('ship_attn');
        $ship_attn = ($ship_attn && $user->web_user)
            ? "<input type='hidden' name='ship_attn' value='$ship_attn' />$ship_attn"
            : "<input type='text' name='ship_attn' value='$ship_attn' size=20 maxlength=64 />";

        $address = $order->getHTMLVar('address');
        $address = ($address && $user->web_user)
            ? "<input type='hidden' name='address' value='$address' />$address"
            : "<input type='text' name='address' value='$address' size=25 maxlength=64 />";

        $address2 = $order->getHTMLVar('address2');
        $address2 = ($address2 && $user->web_user)
            ? "<input type='hidden' name='address2' value='$address2' />$address2"
            : "<input type='text' name='address2' value='$address2' size=25 maxlength=64 />";

        $city = $order->getHTMLVar('city');
        $city = ($city && $user->web_user)
            ? "<input type='hidden' name='city' value='$city' />$city"
            : "<input type='text' name='city' value='$city' size=15 maxlength=64 />";

        $country = $order->getVar('country');
        $state = trim($order->getVar('state'));
        $state = ($state && $user->web_user)
            ? "<input type='hidden' name='state' value='$state' />$state"
            : "<select name='state' id='state_list'><option value=''>  </option>" . Forms::createStateList($state, $country) . "</select>";

        $phone = $order->getVar('phone');
        $phone = ($phone && $user->web_user)
            ? "<input type='hidden' name='phone' value='$phone' />$phone"
            : "<input type='text' name='phone' value='$phone' size=10 maxlength=32 />";

        $zip = $order->getVar('zip');
        $zip = ($zip && $user->web_user)
            ? "<input type='hidden' name='zip' value='$zip' />$zip"
            : "<input type='text' name='zip' value='$zip' size=10 />";

        $country = ($country && $user->web_user)
            ? "<input type='hidden' name='country' value='$country' />$country"
            : "<select name='country' id='country_list' onchange='FillStateOptions(this);'>" . Forms::createCountryAbvList($country, true) . "</select>";

        $email = $order->getHTMLVar('email');
        if (!$email)
        {
            $facility = new Facility($this->facility_id);
            $email = htmlentities($facility->getRehabDirectorEmail(), ENT_QUOTES);
        }

        if ($this->order_type == Order::$CUSTOMER_ORDER)
        {
            $email = ($email && $user->web_user)
                ? "<input type='hidden' name='email' value='$email' />$email"
                : "<input type='text' name='email' value='$email' size=25 maxlength=64 />";

            $email_row = "<tr><th class=\"form\">Email:</th>
			<td class=\"form\" colspan='3'>$email</td></tr>";

            $fax = $order->getHTMLVar('fax');
            $fax = ($user->web_user)
                ? "<input type='hidden' name='fax' value='$fax' />"
                : "<input type='text' name='fax' value='$fax' size=10 />";

            $fax_row = "
			<th class=\"form\">Fax:</th>
			<td class=\"form\" colspan='3'>
				$fax
				<span style='font-size:x-small;font-style:italic;'>(When filled in CS gets e-mail)</span>
			</td>";
        }
        else
        {
            $email_row = "<input type='hidden' name='email' value='$email'/>";
            $fax_row = "";
        }

        $input_rows = <<<END
		<tr>
                        <th class="form">Ship To<span class='required'>*</span>:</th>
			<td class="form" colspan="3">$sname</td>
		</tr>
		<tr>
			<th class="form">Attention Line:</th>
			<td class="form" colspan="3">$ship_attn</td>
		</tr>
		<tr>
			<th class="form" nowrap>Address Line 1<span class='required'>*</span>:</th>
			<td class="form" colspan="3">$address</td>
		</tr>
		<tr>
			<th class="form" nowrap>Address Line 2:</th>
			<td class="form" colspan="3">$address2</td>
		</tr>
		<tr>
			<th class="form">City<span class='required'>*</span>:</th>
			<td class="form">$city</td>
			<th class="form">State<span class='required'>*</span>:</th>
			<td class="form">$state</td>
		</tr>
		<tr>
			<th class="form">Zip<span class='required'>*</span>:</th>
			<td class="form">$zip</td>
			<th class="form">Country<span class='required'>*</span>:</th>
			<td class="form">$country</td>
		</tr>
		{$email_row}
		<tr>
			<th class="form">Phone:</th>
			<td colspan='3' class="form">$phone</td>
		</tr>
		{$fax_row}
END;
        return $input_rows;
    }

    /**
     * Return various button inputs
     *
     * @param string
     * @return mixed
     */
    protected function GetButton($btn, $arg = null)
    {
        $input = "";

        if ($btn == 'cart')
            $input = "<input class='submit' type=\"submit\" name=\"update_btn\"
			value=\"Update Quantity\" onclick=\"document.wsorder.act.value=this.value;\">";
        else if ($btn == 'add')
            $input = "<input class='submit' type='submit' id='add_to_cart' name='act' value='Add to Cart' />";
        else if ($btn == 'remove')
            $input = "<img src='images/cancel.png' class='form_bttn'
			alt='Remove Item' title='Remove Item'
			onclick=\"
				document.wsorder.elements['quantities[$arg]'].value=0;
				document.wsorder.act.value='Update Quantity';
				document.wsorder.submit();\">";

        return $input;
    }

    /**
     * Get HTML table with cart contents
     *
     * @param bool
     * @return string
     */
    protected function GetCartTable($show_button = true)
    {
        global $user, $sh;

        $cart = new Cart($user->getId(), $this->user_type);

        $Company = is_numeric($this->customer->getCustId()) ? SalesOrder::$ININC_CO_ID : SalesOrder::$CO_ID;
        $WhseID = is_numeric($this->customer->getCustId()) ? SalesOrder::$ININC_WHSE : SalesOrder::$PURCHASE_WHSE;

        $check_out = "";
        if (count($cart->items))
            $check_out = "<span style='float:right'><a href='{$_SERVER['PHP_SELF']}?section=Checkout' style='font-size:small;color:#FBFBFB;'>checkout</a></span>";

        $cart_table = "
		<table class=\"list\" cellpadding=\"4\" cellspacing=\"0\" style=\"font-size:10pt;\">
			<tr class='hr'>
				<th width='240' class='subheader' colspan='4'>Shopping Cart{$check_out}</td>
			</tr>
			<tr>
				<th class=\"list\">Product</th>
				<th class=\"list\">Qty</th>
				<th class=\"list\">Avl</th>
				<th class=\"list\">Max</th>
			</tr>";

        if (count($cart->items) > 0)
        {
            $list_sum = $base_sum = $sale_sum = 0;

            $row_class = 'on';
            foreach ($cart->items as $item)
            {
                $avail_qty = 'NA';
                $hl = "";

                # Each Quantity
                $qty = $item->getVar('quantity');
                $ea_qty = $qty * $item->getVar('conversion_factor');
                $max_qty = $item->getVar('max_quantity');

                # Check inventory for non device items
                if (!$item->getVar('is_device'))
                {
                    if ($this->field_user && $ea_qty > $max_qty && $max_qty > 0)
                    {
                        $hl = "_hl";
                    }
                    else
                    {
                        $avail_qty = Cart::GetAvailQty($item->getVar('code'), true, $WhseID, $Company);
                        # Non Inventory will be null
                        if (!is_null($avail_qty))
                        {
                            $avail_qty -= Cart::GetPendingQty($item->getVar('prod_id'), $cart->getOrderId());
                            if ($ea_qty > $avail_qty)
                                $hl = "_hl";
                        }
                        else
                            $avail_qty = "NA";
                    }
                }

                $link = $this->ProductLink($item->getVar('prod_id'), $item->getVar('name'), 'Catalog');

                $cart_table .= "
				<tr class=\"{$row_class}{$hl}\">
					<td style=\"text-align:left\">$link</td>
					<td>{$qty}&nbsp;{$item->getVar('uom')}</td>
					<td>{$avail_qty}</td>
					<td>$max_qty</td>
				</tr>";

                $row_class = ($row_class == 'on') ? 'off' : 'on';
            }

            if ($show_button)
                $cart_table .= "
			<tr>
				<td class='buttons' colspan='4'>
					<input class='submit' type='submit' name='submit_btn' value='Change Quantity' onclick=\"this.form.section.value='Cart';\"/>
				</td>
			</tr>";

        }
        else
        {
            $cart_table .= "<tr><td colspan='4' style=\"font-size:small;font-style:italic\">Your cart is empty.</td></tr>";
        }

        $cart_table .= "</table>";

        return $cart_table;
    }

    /**
     * Get HTML table for the Catalog
     *
     * @param integer
     * @return string
     */
    private function GetCatalogTable($catalog_id)
    {
        $sth = $this->dbh->query("SELECT c.catalog_name
		FROM product_catalog c
		WHERE c.id = $catalog_id
		ORDER BY c.catalog_name");
        list($catalog_name) = $sth->fetch(PDO::FETCH_NUM);

        $edit_table = "
		<input type='hidden' name='act' value='save'/>
		<table class='view' cellpadding='2' style='margin:0;'>
			<tr>
				<th class='subheader' colspan='2'>Catalog</th>
			</tr>
			<tr>
				<th class='form'>Name:</th>
				<td class='form'>
					<input type='text' name='catalog_name' value='$catalog_name' size='20' maxlength='128'/>
				</td>
			</tr>
			<tr>
				<td class='buttons' colspan='2'>
					<input class='submit' type='submit' name='save' value='Submit'/>
				</td>
			</tr>
		</table>";

        return $edit_table;
    }

    /**
     * Prints a list of categories in a given catalog.
     *
     * @param integer
     *
     * @return string
     */
    private function GetCatalogCategories($catalog_id)
    {
        global $this_app_name;

        $category_table = "
			<table class='list' cellpadding='5' cellspacing='2' style='margin:0;'>
				<tr>
					<td class='form_help'>No Categories are defined for the Catalog</td>
				</tr>
				<tr>
					<td class='buttons'></td>
				</tr>
			</table>";

        $save_button = "<tr>
							<td style='text-align:center;padding:5px;'>
								<input class='submit' type='submit' name='update_btn' value='Update' onclick=\"this.form.act.value='update';\"/>
							</td>
						</tr>";

        # Get search string
        $categories = "";
        $and_search = "";
        $available = "Available Categories";
        $search = (isset ($_REQUEST['search'])) ? trim($_REQUEST['search']) : '';
        $ht_search = htmlentities($search, ENT_QUOTES);
        $qu_search = addslashes($search);
        if ($search)
        {
            $available = "Search Results For \"{$ht_search}\"";

            if (preg_match('/^#/', $search))
            {
                $qu_search = strtoupper(substr($qu_search, 1));
                $and_search .= "AND upper(p.code) = {$this->dbh->quote($qu_search)}";
            }
            else
            {
                $and_search .= " AND (
				similarity(c.category_name, " . $this->dbh->quote($qu_search) . ") > 0.2
				OR position(lower(" . $this->dbh->quote($qu_search) . ") in lower(c.category_name)) != 0
				OR c.category_name ~* '( |^)($qu_search)( |\$)')";
            }
        }

        $page = (isset ($_REQUEST['page'])) ? (int) $_REQUEST['page'] : 1;
        if ($page < 1)
            $page = 1;
        $per_page = $this->per_page;
        $offset = ($page - 1) * $per_page;

        # Define Query specifics

        $sth = $this->dbh->query("SELECT
			c.id AS id,
			c.category_name,
			CASE WHEN j.category_id IS NOT NULL THEN 1 ELSE 0 END as in_cat
		FROM product_category c
		LEFT JOIN product_catalog_category j ON c.id = j.category_id AND j.catalog_id = {$catalog_id}
		WHERE true
		$and_search
		ORDER BY in_cat DESC, c.display_order");
        $total = $sth->rowCount();
        $prod_count = 0;
        $count = 0;
        $cur_page = 1;
        $rolling_count = 1;
        # Show page row if there are more than 1 page of products
        if ($total > 0)
        {
            $row_class = 'on';
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                # Is member of this category
                #
                $chk = ($row['in_cat']) ? "checked" : "";

                # Track the actual number of products in the categroy
                if ($chk)
                    $prod_count++;

                # Display details for the requested page only.
                # Pass others as a hidden input.
                #
                if ($cur_page == $page)
                {

                    $categories .= "
						<tr onmouseover='this.className=\"focus\";' onmouseout='this.className=\"\";'>
							<td class='prod' style='text-align:left'>
								<b><u>{$row['category_name']}</u></b>
							</td>
							<td class='prod' style=\"text-align:center\">
								<input type='hidden' name='category_ids[{$row['id']}]' value='{$row['id']}'/>
								<input type='checkbox' name='chk_category_ids[{$row['id']}]' value='{$row['id']}' $chk />
							</td>
						</tr>";

                    $row_class = ($row_class == 'on') ? 'off' : 'on';

                    $count++;
                }

                if ($rolling_count == $per_page)
                {
                    # Reset the rolling count and increment the current page
                    $rolling_count = 1;
                    $cur_page++;
                }
                else
                {
                    # Increment the rolling count of the page
                    $rolling_count++;
                }
            }

            $args = null;
            if ($search)
                $args['search'] = $ht_search;

            $page_row = $this->GetPageBar($total, $count, $page, $per_page, $args);

            $category_table = "
			<table width='600' cellpadding='5' cellspacing='0'>
				<tr>
					<td>
						<div style='font-size:18pt;font-:bold;'>{$available}</div>
					</td>
				</tr>
				<tr>
					<td>
						<div style='background-color:#cccccc;padding:5px;'>
							$prod_count of $total Categories Selected.
						</div>
					</td>
				</tr>
				{$save_button}
				<tr>
					<td>
						<table cellpadding='5' cellspacing='0' class='list' style='margin:0;'>
							{$page_row}
							<tr>
								<th class='list'>Category</th>
								<th class='list'>Included</th>
							</tr>
							{$categories}
							{$page_row}
						</table>
					</td>
				</tr>
				{$save_button}
			</table>";
        }

        return $category_table;
    }

    /**
     * Prints a list of editable supplies in a given category.
     *
     * @param $cat_id int
     *
     * @return string
     */
    private function GetCategoryEditSupplies()
    {
        global $this_app_name;

        $supplies_table = "
			<table class='list' cellpadding='5' cellspacing='2' style='margin:0;'>
				<tr>
					<td class='form_help'>Please select a category from the left or enter search text.</td>
				</tr>
				<tr>
					<td class='buttons'></td>
				</tr>
			</table>";

        $save_button = "<tr>
							<td style='text-align:center;padding:5px;'>
								<input class='submit' type='submit' name='update_btn' value='Update' onclick=\"this.form.act.value='update';\"/>
							</td>
						</tr>";
        $supplies = "";
        $WHERE = "";
        $search = (isset ($_REQUEST['search'])) ? addslashes(trim($_REQUEST['search'])) : '';
        $ht_search = htmlentities($search, ENT_QUOTES);
        $qu_search = addslashes($search);
        if ($search)
        {
            $available = "Search Results For \"{$ht_search}\"";

            $cat_id = $this->cat_id;
            if (!$cat_id)
            {
                $save_button = "";
                $cat_id = 0;
            }

            if (preg_match('/^#/', $search))
            {
                $qu_search = strtoupper(substr($qu_search, 1));
                $WHERE = "WHERE upper(p.code) = {$this->dbh->quote($qu_search)}";
            }
            else
            {
                $WHERE = "WHERE similarity(p.name, {$this->dbh->quote($qu_search)}) + similarity(p.description, {$this->dbh->quote($qu_search)}) > 0.4
				OR p.name ilike '%{$qu_search}%'
				OR p.description ilike '%{$qu_search}%'
				OR p.code ilike '%{$qu_search}%'
				OR p.code ilike {$this->dbh->quote($qu_search)}";
            }

            $sql = "SELECT
				p.id AS id,
				p.name AS name,
				p.code AS code,
				p.description AS description,
				p.active,
				lower(p.code) AS pic,
				d.track_inventory,
				CASE WHEN j.prod_id IS NOT NULL THEN 1 ELSE 0 END as in_cat
			FROM products p
			LEFT JOIN product_detail d on p.id = d.prod_id
			LEFT JOIN product_category_join j ON p.id = j.prod_id AND j.cat_id = {$cat_id}
			$WHERE
			ORDER BY in_cat DESC, p.name";
        }
        else if ($this->cat_id)
        {
            $available = "Available Products";
            $sql = "SELECT
				p.id AS id,
				p.name AS name,
				p.code AS code,
				p.description AS description,
				p.active,
				lower(p.code) AS pic,
				d.track_inventory,
				CASE WHEN j.prod_id IS NOT NULL THEN 1 ELSE 0 END as in_cat
			FROM products p
			LEFT JOIN product_detail d on p.id = d.prod_id
			LEFT JOIN product_category_join j ON p.id = j.prod_id AND j.cat_id = {$this->cat_id}
			-- WHERE p.active = true
			ORDER BY in_cat DESC, p.name";
        }
        else
        {
            $sql = "SELECT 0 WHERE false";
        }

        $page = (isset ($_REQUEST['page'])) ? (int) $_REQUEST['page'] : 1;
        if ($page < 1)
            $page = 1;
        $per_page = $this->per_page;
        $offset = ($page - 1) * $per_page;

        # Define Query specifics
        $sth = $this->dbh->query($sql);
        $total = $sth->rowCount();
        $prod_count = 0;
        $count = 0;
        $cur_page = 1;
        $rolling_count = 1;
        # Show page row if there are more than 1 page of products
        if ($total > 0)
        {
            $row_class = 'on';
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                # Is member of this category
                #
                $chk = ($row['in_cat']) ? "checked" : "";

                # Track the actual number of products in the categroy
                if ($chk)
                    $prod_count++;

                # Display details for the requested page only.
                # Pass others as a hidden input.
                #
                if ($cur_page == $page)
                {
                    $pic = $this->ProductImg($row['id'], $row['pic'], 'Admin', null, 30);
                    $link = $this->ProductLink($row['id'], "<b><u>{$row['name']}</u></b>", 'Admin&tab=Categories');

                    $inv = ($row['track_inventory']) ? "Yes" : "No";
                    $act = ($row['active']) ? "" : " inactive";

                    $dis = ($this->cat_id) ? "" : "disabled";

                    $supplies .= "
						<tr onmouseover='this.className=\"focus\";' onmouseout='this.className=\"\";'>
							<td class='prod{$act}' style=\"text-align:center\">
								$pic
								<br>
								#{$row['code']}
							</td>
							<td class='prod{$act}' style='text-align:left'>
								$link
								<br/>
								<b>{$row['description']}</b><br/>
							</td>
							<td class='prod{$act}' style=\"text-align:center\">
								<input type='hidden' name='prod_ids[{$row['id']}]' value='{$row['id']}'/>
								<input type='checkbox' name='chk_prod_ids[{$row['id']}]' value='{$row['id']}' $chk $dis />
							</td>
						</tr>";

                    $row_class = ($row_class == 'on') ? 'off' : 'on';

                    $count++;
                }

                if ($rolling_count == $per_page)
                {
                    # Reset the rolling count and increment the current page
                    $rolling_count = 1;
                    $cur_page++;
                }
                else
                {
                    # Increment the rolling count of the page
                    $rolling_count++;
                }
            }

            $args = null;
            if ($search)
                $args['search'] = $ht_search;
            $page_row = $this->GetPageBar($total, $count, $page, $per_page, $args);

            $supplies_table = "
			<table width='600' cellpadding='5' cellspacing='0'>
				<tr>
					<td>
						<div style='font-size:18pt;font-weight:bold;'>{$available}</div>
					</td>
				</tr>
				<tr>
					<td>
						<div style='background-color:#cccccc;padding:5px;'>
							Showing $count of $total Products ($prod_count Selected)
						</div>
					</td>
				</tr>
				{$save_button}
				<tr>
					<td>
						<table cellpadding='5' cellspacing='0' class='list' style='margin:0;'>
							{$page_row}
							<tr>
								<th class='list'>Product</th>
								<th class='list'>Description</th>
								<th class='list'>Included</th>
							</tr>
							{$supplies}
							{$page_row}
						</table>
					</td>
				</tr>
				{$save_button}
			</table>";
        }

        return $supplies_table;
    }

    /**
     * Prints a list of supplies in a given category.
     *
     * @return string
     */
    protected function GetCategorySupplies()
    {
        global $user, $this_app_name;

        $supplies_table = "
			<table class='list' cellpadding='5' cellspacing='2' style='margin:0;'>
				<tr>
					<td>
						<img src='images/Banner800x162.png' alt='Web Banner' title='Web Banner'/>
					</td>
				</tr>
			</table>";

        # Get search string
        $search = (isset ($_REQUEST['search'])) ? addslashes(trim($_REQUEST['search'])) : '';
        $ht_search = htmlentities($search, ENT_QUOTES);
        $qu_search = addslashes($search);

        # Find sort order and direction
        $match = array();
        $dir = (isset ($_REQUEST['dir']) && $_REQUEST['dir']) ? strtolower($_REQUEST['dir']) : 'ASC';
        $sort = (isset ($_REQUEST['sort']) && $_REQUEST['sort']) ? strtolower($_REQUEST['sort']) : 'p.name';
        $page = (isset ($_REQUEST['page'])) ? (int) $_REQUEST['page'] : 1;
        if ($page < 1)
            $page = 1;
        $per_page = $this->per_page;
        $offset = ($page - 1) * $per_page;

        $args = null;
        if ($sort)
            $args['sort'] = $sort;
        if ($search)
            $args['search'] = $ht_search;

        $sort_ary = array(
            'p.name' => 'Product Name',
            'pr.preferredprice_desc' => 'Price - High to Low',
            'pr.preferredprice_asc' => 'Price - Low to High'
        );

        # Validate sort
        if (!in_array($sort, array_keys($sort_ary)))
            $sort = 'p.name';

        # Sort and Dir may be passed in one variable
        if (preg_match("/^[\w\.]+_(desc|asc)\$/i", $sort, $match))
        {
            $sort = str_replace("_{$match[1]}", "", $sort);
            $dir = strtoupper($match[1]);
        }

        # Define Query specifics
        $WHERE = "p.active = true";

        if ($search)
        {
            $sort = 'p.name';
            $cat_name = "Search Results For \"{$ht_search}\"";
            $match_str = $this->dbh->quote($qu_search);
            $no_ws_str = $this->dbh->quote(preg_replace('/\s+/', '', $qu_search));

            $supplies_table = "
			<table cellpadding='5' cellspacing='0'>
				<tr>
					<td><p class='info'>No results for \"{$ht_search}\" found</p></td>
				</tr>
				<tr>
					<td class='buttons'></td>
				</tr>
			</table>";

            ## '#' Prefix means exact prod code match
            if (preg_match('/^#/', $search))
            {
                $match_str = $this->dbh->quote(strtoupper(substr($qu_search, 1)));
                $WHERE .= " AND upper(p.code) = $match_str";
            }
            else
            {
                $WHERE .= " AND (
				similarity(p.name, $no_ws_str) + similarity(p.description, $no_ws_str) > 0.175
				OR position(lower($match_str) in lower(p.name)) != 0
				OR position(lower($match_str) in lower(p.description)) != 0
		        OR p.code = $match_str)";
            }
        }
        else if ($this->cat_id)
        {
            $supplies_table = "
			<table cellpadding='5' cellspacing='0'>
				<tr>
					<td><p class='info'>No products in this category</p></td>
				</tr>
				<tr>
					<td class='buttons'></td>
				</tr>
			</table>";

            $sth = $this->dbh->prepare('SELECT category_name FROM product_category WHERE id = ?');
            $sth->bindValue(1, $this->cat_id, PDO::PARAM_INT);
            $sth->execute();
            $cat_name = $sth->fetchColumn();

            $WHERE .= " AND j.cat_id = " . (int) $this->cat_id;

        }
        else
        {
            # Dont Show any results
            $cat_name = "";
        }

        # Limit to vizable items for CPM and Web users
        if ($user->web_user)
            $WHERE .= " AND pc.viz_public = true AND pr.id IS NOT NULL";

        if ($this->field_user)
            $WHERE .= " AND pc.viz_internal = true";

        # Only show ADD button if the user is known
        $add_button_row = "";
        if ($this->facility_id && $this->disabled == false)
        {
            $add_button_row = "<div style='text-align:center;padding:5px;'>
				{$this->GetButton('add')}
			</div>";
        }

        # Load a cart object for calculating price
        $line_item = new LineItem();

        $sth = $this->dbh->query("SELECT
			count(DISTINCT p.id),
			array_to_string(array_accum(DISTINCT p.id), ',')
		FROM products p
		INNER JOIN product_category_join j ON p.id = j.prod_id
		INNER JOIN product_category pc ON j.cat_id = pc.id
		INNER JOIN product_catalog_category c ON j.cat_id = c.category_id
			AND c.catalog_id = {$this->catalog}
		LEFT JOIN product_detail d on p.id = d.prod_id
		LEFT JOIN product_pricing pr ON p.id = pr.id
		WHERE $WHERE");
        list($total, $p_ids) = $sth->fetch(PDO::FETCH_NUM);

        # If there is exactly one match show it
        if ($total == 1)
            return $this->ShowProduct(array('prod_id' => $p_ids));

        $sth = $this->dbh->query("SELECT DISTINCT
			p.id AS id,
			p.id AS prod_id,
			p.name AS name,
			p.code AS code,
			p.description AS description,
			p.unit AS unit,
			p.prod_price_group_key,
			p.price_uom,
			lower(p.code) AS pic,
			(	SELECT array_to_string(array_accum(u.uom),',') as options
				FROM
				(
					SELECT uom
					FROM product_uom
					WHERE product_uom.code = p.code
					AND active = true
					ORDER BY conversion_factor
				) u
			) as uoms,
			p.max_quantity,
			pr.listprice,
			pr.preferredprice,
			pr.sheet2price,
			pr.sheet3price,
			pr.sheet4price
		FROM products p
		INNER JOIN product_category_join j ON p.id = j.prod_id
		INNER JOIN product_category pc ON j.cat_id = pc.id
		INNER JOIN product_catalog_category c ON j.cat_id = c.category_id AND c.catalog_id = {$this->catalog}
		LEFT JOIN product_detail d on p.id = d.prod_id
		LEFT JOIN product_pricing pr ON p.id = pr.id
		WHERE $WHERE
		ORDER BY {$sort} {$dir}
		LIMIT $per_page OFFSET $offset");
        $count = $sth->rowCount();

        $page_row = $this->GetPageBar($total, $count, $page, $per_page, $args);
        $sort_div = $this->GetSortBar($total, $count, 'products', $sort_ary, $args['sort']);

        if ($count > 0)
        {
            $upsell_th = ($this->limit_user) ? "&nbsp;" : "Upsell";

            $supplies_table = "
			<div style='font-size:18pt;font-weight:bold;'>{$cat_name}</div>
			$sort_div
			$add_button_row
			<table width='100%' cellpadding='5' cellspacing='0' class='list'>
				{$page_row}
				<tr>
					<th class='list'>Product</th>
					<th class='list'>Description</th>
					<th class='list'>Quantity</th>
					<th class='list'>UOM</th>
					<th class='list'>$upsell_th</th>
				</tr>";
            $i = 1;
            $row_class = 'on';
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $pic = $this->ProductImg($row['id'], $row['pic'], 'Catalog', null, 30);
                $link = $this->ProductLink($row['id'], "<b><u>{$row['name']}</u></b>", 'Catalog');

                # Create UOM options select and determine default uom
                self::ParseUOM($row);

                # Set item price
                $row['price'] = NULL;
                $line_item->copyFromArray($row);
                $line_item->SetPriceInfo($this->order_type, $this->bpi, $this->cpgk);
                $row['list_amount'] = $line_item->getVar('list_amount');
                $row['amount'] = $line_item->getVar('amount');

                # Format prices for money
                $list_price = (is_numeric($row['list_amount'])) ? money_format('%.2n', $row['list_amount']) : "NA";
                $sale_price = money_format('%.2n', $row['amount']);

                if (is_numeric($line_item->getVar('sale_amount')))
                    $sale_price = money_format('%.2n', (double) $line_item->getVar('sale_amount'));
                else if (is_numeric($line_item->getVar('base_amount')))
                    $sale_price = money_format('%.2n', (double) $line_item->getVar('base_amount'));
                else
                    $sale_price = 'NA';

                $ups_input = $this->UpsellCheckbox(false, $i);

                $supplies_table .= "
					<tr onmouseover='this.className=\"focus\";' onmouseout='this.className=\"\";'>
						<td class='prod' style=\"text-align:center\">
							$pic
							<br>
							#{$row['code']}
						</td>
						<td class='prod' style='text-align:left'>
							$link
							<br/>
							<b>{$row['description']}</b><br/>
							List Price: {$list_price}<br/>
							<span style='color:#006699;'>Your Price: {$sale_price}</span>
						</td>
						<td class='prod' style=\"text-align:center\">
							<input type='hidden' name='prod_ids[$i]' value='{$row['id']}' />
							<input type='hidden' id='upsell_$i' name='upsell[$i]' value='0' />
							<input type='text' name='quantities[$i]' value='0' size='2' maxlength='5' onFocus=\"this.value = '';\" onKeyPress=\"return EntertoTab(event, this);\"/>
						</td>
						<td class='prod' style=\"text-align:center\">
							<select name='uoms[$i]'>
								{$row['uom_options']}
							</select>
						</td>
						<td class='prod' style=\"text-align:center\">
							$ups_input
						</td>
					</tr>";

                $i++;
                $row_class = ($row_class == 'on') ? 'off' : 'on';
            }

            $supplies_table .= "
				$page_row
			</table>
			$add_button_row";
        }

        return $supplies_table;
    }

    /**
     * Build json string for the country/state options
     *
     * @return string
     */
    static public function GetCountryStateJSON()
    {
        $dbh = DataStor::getHandle();

        $countr_states_array = array();
        $sth = $dbh->query("SELECT
			c.abbr,  array_to_string(array_accum(coalesce(s.code, c.abbr)), ',') as states
		FROM countries c
		LEFT JOIN (
			SELECT
				s.country_id, s.code
			FROM states s
			ORDER BY s.country_id, s.display_order DESC
		) s ON c.id = s.country_id
		group by c.abbr, c.display_order
		ORDER BY c.display_order");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $countr_states_array[$row['abbr']] = $row['states'];
        }

        return json_encode($countr_states_array);
    }

    /**
     * Prints a list of equipment to be added
     *
     * @return string
     */
    protected function GetFacilityEquipment()
    {
        # Define back button based on order type and if its an existing order
        $back_button = "";
        if (!$this->order_id)
            $back_button = "<input class='submit' type='submit' name='act' value='Back' onClick=\"return confirm('This will remove items from you Cart.')\"/>";

        if ($this->type_ary['out_asset'] && !$this->type_ary['in_return'])
        {
            # Install Order show equipment models as items
            $sth = $this->dbh->prepare("SELECT
				0 as asset_id,
				p.id AS id,
				p.name AS name,
				p.code AS code,
				p.description AS description,
				p.unit AS unit,
				p.pic AS pic,
				(	SELECT array_to_string(array_accum(u.uom),',') as options
					FROM
					(
						SELECT uom
						FROM product_uom
						WHERE product_uom.code = p.code
						AND active = true
						ORDER BY conversion_factor
					) u
				) as uoms
			FROM products p
			INNER JOIN equipment_models e ON p.code = e.model
			WHERE e.active = TRUE
			ORDER BY e.display_order");
            $sth->execute();
        }
        else
        {
            # Show customer devices as items
            $sth = $this->dbh->prepare("SELECT
				a.id as asset_id,
				p.id AS id,
				p.name || ' (Serial: ' || a.serial_num || ')' AS name,
				p.code AS code,
				p.description AS description,
				p.unit AS unit,
				p.pic AS pic,
				(	SELECT uom
					FROM product_uom
					WHERE product_uom.code = p.code
					AND active = true
					AND conversion_factor = 1
				) as uoms
			FROM lease_asset_status a
			INNER JOIN equipment_models e on a.model_id = e.id
			INNER JOIN products p ON e.model = p.code
			WHERE a.facility_id = ?
			AND a.status = 'Placed'
			AND a.id NOT IN (SELECT asset_id FROM cart_item)
			AND a.id NOT IN (SELECT swap_asset_id FROM cart_item)
			ORDER BY a.model_id, a.serial_num");
            $sth->bindValue(1, (int) $this->facility_id, PDO::PARAM_INT);
            $sth->execute();
        }

        $supplies_table = "
		<table class='list' cellpadding='5' cellspacing='2' style='margin-left:0;'>
			<tr>
				<td class='buttons' colspan='4'>
					{$back_button}
					<input class='submit' type='submit' name='act' value='Add to Cart' />
					<input class='submit' type='submit' name='act' value='Continue' />
				</td>
			</tr>
			<tr>
				<th class='subheader' colspan='4'>Equipment</th>
			</tr>
			<tr>
				<th class='list'>Item #</th>
				<th class='list'>Product</th>
				<th class='list'>Quantity</th>
				<th class='list'>UOM</th>
			</tr>";

        $i = 0;
        $row_class = 'on';
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $unique_asset = $i * -1;

            $qty = ($row['asset_id'])
                ? $qty = "<input type='checkbox' name='quantities[$i]' value='1'/>1"
                : "<input type='text' name='quantities[$i]' value='0' size='2' maxlength='5' onFocus=\"this.value=''\"/>";

            $pic = $this->ProductImg($row['id'], $row['pic'], 'Catalog', null, 200);

            self::ParseUOM($row);

            # For SWAP set use swap_asset_id
            # Otherwise use asset_id
            if ($this->type_ary['out_asset'] && $this->type_ary['in_return'])
            {
                $asset_inputs = "
				<input type='hidden' name='assets[$i]' value='{$unique_asset}'/>
				<input type='hidden' name='swap_assets[$i]' value='{$row['asset_id']}'/>";
            }
            else
            {
                $asset_inputs = "
				<input type='hidden' name='assets[$i]' value='{$row['asset_id']}'/>
				<input type='hidden' name='swap_assets[$i]' value='0'/>";
            }

            $supplies_table .= "
			<tr class=\"{$row_class}\">
				{$asset_inputs}
				<td rowspan='2' style=\"font-size:small;text-align:center\">{$pic}<br>#{$row['code']}</td>
				<td  align='left' style=\"font-weight:bold\">{$row['name']}</td>
				<td rowspan='2' align='center'>
					<input type='hidden' name='prod_ids[$i]' value='{$row['id']}' />
					{$qty}
				</td>
				<td rowspan='2' align='center'>
					<select name='uoms[$i]'>
						{$row['uom_options']}
					</select>
				</td>
			</tr>
			<tr class='{$row_class}'>
				<td align='left' style=\"font-size:small;vertical-align:top\">{$row['description']}</td>
			</tr>";

            $row_class = ($row_class == 'on') ? 'off' : 'on';
            $i++;
        }

        $supplies_table .= "
			<tr>
				<td class='buttons' colspan='4'>
					{$back_button}
					<input class='submit' type='submit' name='act' value='Add to Cart' />
					<input class='submit' type='submit' name='act' value='Continue' />
				</td>
			</tr>
		</table>";

        return $supplies_table;
    }

    /**
     * Generate a tr for pagination
     *
     * @param int
     * @param int
     * @param int
     * @param int
     *
     * @return string
     */
    protected function GetPageBar($total, $count, $page, $per_page, $params)
    {
        if ($total > $count)
        {
            # Initialize values as if page is somewhere in the middle of the total
            $num_pages = ceil($total / $per_page);
            $start_page = $page - 4;
            $end_page = $page + 5;

            # When start is less than 1 adjust
            if ($start_page < 1)
            {
                $end_page += ($start_page * -1) + 1;
                $start_page = 1;

                if ($end_page > $num_pages)
                    $end_page = $num_pages;
            }
            # When the page is within 5 of the last pages adjust
            else if ($end_page > $num_pages)
            {
                $start_page -= ($end_page - $num_pages);
                $end_page = $num_pages;

                if ($start_page < 1)
                    $start_page = 1;
            }

            # ResultList will build request string from current options
            $args = array(
                'order_id' => $this->order_id,
                'order_type' => $this->order_type,
                'facility_id' => $this->facility_id,
                'cat_id' => $this->cat_id,
                'dev_id' => $this->dev_id,
                'section' => $this->section);

            # Add the additional args
            if (is_array($params))
                $args = array_merge($params, $args);

            # Only exists for admininstators
            if (isset ($_REQUEST['tab']))
                $args['tab'] = $_REQUEST['tab'];

            # Get the page html
            $nav_bar = ResultList2::getNavigationBar($args, $_SERVER['PHP_SELF'], $page, $start_page, $end_page, $num_pages);
            $page_row = "<tr><td class=\"list_nav\" colspan=\"9\">$nav_bar</td></tr>";
        }
        else
            $page_row = "";

        return $page_row;
    }

    /**
     * Build list of images used to display the product
     *
     * @param array
     *
     * @return array
     */
    public function GetProductImages($args)
    {
        $prod_id = (isset ($args['prod_id'])) ? $args['prod_id'] : 0;
        $act = (isset ($args['act'])) ? $args['act'] : 'ib';

        # Set the path of the image file
        #
        $img_dir = Config::$MEDIA_FILE_PATH . "/product_image/";

        # Empty by default
        $images = null;

        # Find all the image records
        $sth = $this->dbh->prepare("SELECT i.id, lower(i.name) as name, i.description, lower(p.code) as code
		FROM product_img i
		INNER JOIN products p ON i.prod_id = p.id
		WHERE p.id = ? AND i.name NOT LIKE ('%_thumb') ORDER BY id");
        $sth->bindValue(1, (int) $prod_id, PDO::PARAM_INT);
        $sth->execute();
        while ($img = $sth->fetch(PDO::FETCH_ASSOC))
        {
            # Expect to find multiple files with at least a default image
            # and a thumbnail. Alternative images may also be present.
            # Key == 0 is the default image
            #
            $key = 0;
            if (preg_match('/^' . $img['code'] . '(_\w+)?/', $img['name'], $tail))
            {
                # Alternative images have formatted names
                # Format: {code}_{key}.jpg
                # Examples: 50881.jpg, 50881_1.jpg, 50881_2.jpg
                #
                $key = (isset ($tail[1])) ? substr($tail[1], 1) : 0;
            }

            # For image bar just load image src in a formatted string
            if ($act == 'ib')
            {
                # Dont include the thumb nail image
                if (is_numeric($key))
                    $images .= "{$img_dir}{$img['name']}|";
            }
            # For other requests return the array of images
            else
                $images[$key] = $img;
        }

        # Remove trailing '|';
        if ($act == 'ib' && $images)
            $images = substr($images, 0, -1);

        return $images;
    }

    /**
     * Generate a sort section div
     *
     * @param integer $total
     * @param integer $count
     * @param string $label
     * @param array $option_ary
     * @param mixed $sel_opt
     */
    protected function GetSortBar($total, $count, $label, $option_ary, $sel_opt = "")
    {
        $options = "";

        foreach ($option_ary as $value => $text)
        {
            $sel = ($value == $sel_opt) ? " selected" : "";
            $options .= "	<option value='$value'$sel>$text</option>\n";
        }

        $sort_bar = "<div style='background-color:#cccccc;padding:5px;'>
			Showing $count of $total $label
			<div style='float:right'>
				Sort by
				<select name='sort' onChange='this.form.submit();'>
					$options
				</select>
			</div>
		</div>";

        return $sort_bar;
    }

    /**
     * Perform a Get Transit Time request and return result
     *
     * @return string
     */
    public function GetTNT()
    {
        global $preferences, $user, $this_app_name;

        $transit_time = array("method" => "Ground", "tnt" => time(), "price" => "");

        # Customer may not have been initialized
        if (is_null($this->customer))
            $this->customer = new CustomerEntity($this->facility_id);

        # Load new cart information
        #
        $cart = new Cart($user->getId(), $this->user_type);

        if (isset ($_REQUEST['address']))
            $cart->copyFromArray($_REQUEST);

        # Create an array of items with weight and dimentions
        $sth = $this->dbh->prepare("SELECT
			d.weight
		FROM product_uom d
		WHERE d.uom = ?
		AND d.code = (SELECT code FROM products WHERE id = ?)");

        $items = array();
        foreach ($cart->items as $item)
        {
            $sth->bindValue(1, $item->getVar('uom'), PDO::PARAM_STR);
            $sth->bindValue(2, $item->getVar('prod_id'), PDO::PARAM_INT);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_ASSOC);

            $weight = ($row['weight'] > 0) ? $row['weight'] : 0.5;

            # Type cast so there are no null values
            $items[] = array(
                'weight' => (float) ($weight * $item->getVar('quantity')),
                'length' => '',
                'width' => '',
                'height' => ''
            );
        }

        if ($this->checkout_form)
            $chko_form = $this->checkout_form;
        else
            $chko_form = @unserialize($preferences->get($this_app_name, '_checkout'));

        # Get most recent ship_method default to Ground if nothing is defined
        $ship_method = "Ground";
        if (isset ($chko_form['ship_method']))
            $ship_method = $chko_form['ship_method'];
        if (isset ($_REQUEST['ship_method']))
            $ship_method = $_REQUEST['ship_method'];

        # Get most relevant shipping address
        if (isset ($_REQUEST['zip']))
            $address = $this->FillAddress($_REQUEST);
        else if (isset ($chko_form['zip']))
            $address = $this->FillAddress($chko_form);
        else
            $address = $this->FillAddress($this->customer);

        # Add CustId to address
        $address['cust_id'] = $this->customer->getCustId();

        # Create an array of available options
        $delivery['err'] = "";
        $delivery['options'] = array();
        $rate_ary = $tnt_ary = array();

        if (count($items))
        {
            # Perform Rate and TNT Webservice requests
            $ship = new ShippingService();
            #if ($ship->RateRequest($this->order_id, 'Shop', $address, $items) && $ship->TNTRequest($this->order_id, $address, $items))
            if ($ship->RateRequest($this->order_id, 'Shop', $address, $items))
            {
                # Get results of requests
                $rate_ary = $ship->GetAmount();

                # Add an element for each rate returned
                #
                foreach ($rate_ary as $rate)
                {
                    $rate['price'] = "\$" . number_format($rate['price'], 2);
                    //$rate['date'] = "";

                    $delivery['options'][] = $rate;
                }
            }
            else
            {
                $delivery['err'] = $ship->GetMsg();
            }
        }

        return json_encode($delivery);
    }

    /**
     * Create a list of like items from order history
     *
     * @param integer
     * @param integer
     * @param integer
     */
    protected function GetUpsaleProducts($prod_id, $prod_count = 5, $prod_width = 120)
    {
        $upsell_list = "";

        if ($prod_id)
        {
            // Commonly ordered with
            $sql = "SELECT
				p.id, p.code, p.name,
				p.description,
				lower(p.code) AS pic,
				oi.count
			FROM products p
			INNER JOIN product_pricing pr ON p.id = pr.id
			INNER JOIN (
				SELECT prod_id, count(prod_id)
				FROM order_item
				WHERE order_id IN (
					SELECT order_id FROM order_item where prod_id = ?
				)
				GROUP BY prod_id
			) oi ON p.id = oi.prod_id
			LEFT JOIN product_detail d ON p.id = d.prod_id
			WHERE p.id != ?
			AND p.id IN (
				SELECT prod_id
				FROM product_category_join j
				INNER JOIN product_catalog_category c
					ON j.cat_id = c.category_id
				WHERE c.catalog_id = ?
			)
			AND p.active = true
			ORDER BY oi.count DESC
			LIMIT $prod_count";
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
            $sth->bindValue(2, $prod_id, PDO::PARAM_INT);
            $sth->bindValue(3, $this->catalog, PDO::PARAM_INT);
        }
        else
        {
            // Featured Items
            $sql = "SELECT
				p.id, p.code, p.name,
				p.description,
				lower(p.code) AS pic
			FROM products p
			INNER JOIN product_detail d ON p.id = d.prod_id
			WHERE d.featured = true
			AND p.active = true
			AND p.id IN (
				SELECT prod_id
				FROM product_category_join j
				INNER JOIN product_catalog_category c
					ON j.cat_id = c.category_id
				WHERE c.catalog_id = ?
			)
			LIMIT $prod_count";
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, $this->catalog, PDO::PARAM_INT);
        }
        $sth->execute();
        $count = $sth->rowCount();
        if ($count)
        {
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $pic = $this->ProductImg($row['id'], $row['pic'], 'Catalog', null, 30);
                $link = $this->ProductLink($row['id'], $row['name'], 'Catalog');

                $upsell_list .= "
				<div class='prod_ad' style='width: {$prod_width}px;'>
					$pic
					<br/>
					$link
				</div>";
            }
        }
        else
        {
            $upsell_list = "<p class='info'>No products defined.</p>";
        }

        return $upsell_list;
    }

    /**
     * Returns the class Property value defined by $var.
     *
     * @param $var string
     *
     * @return mixed
     */
    public function getVar($var = null)
    {
        $ret = null;
        if (@property_exists($this, $var))
        {
            $ret = $this->{$var};
        }
        return $ret;
    }

    /**
     * Duplicate an order by adding items from that order to cart
     *
     * @param array $form
     */
    private function Duplicate($form)
    {
        global $user, $this_app_name, $preferences;

        $act = (isset ($form['act'])) ? strtolower($form['act']) : '';
        $order_id = (isset ($form['order_id'])) ? strtolower($form['order_id']) : 0;
        $update_shipping = (isset ($form['update_shipping'])) ? $form['update_shipping'] : 0;
        $update_tax = (isset ($form['update_tax'])) ? $form['update_tax'] : 0;

        $this->setBPI();

        $cart = new Cart($user->getId(), $this->user_type);
        $cart->save(array(
            'order_type' => (int) $this->order_type,
            'facility_id' => $this->facility_id)
        );
        $cart->setBPI($this->bpi);
        $cart->setCPGK($this->cpgk);
        $cart->loadCP();

        # UpdateItems handles adding new, updating quantity, and removing items
        if ($order_id)
        {
            $items = array();
            $sth = $this->dbh->prepare("SELECT
				i.prod_id, i.quantity, i.asset_id, i.swap_asset_id,
				i.uom, i.whse_id
			FROM order_item i
			WHERE order_id = ?");
            $sth->bindValue(1, $order_id, PDO::PARAM_INT);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                if ($this->InCatalog($row['prod_id']))
                {
                    $items['prod_ids'][] = $row['prod_id'];
                    $items['quantities'][] = $row['quantity'];
                    $items['assets'][] = $row['asset_id'];
                    $items['swap_assets'][] = $row['swap_asset_id'];
                    $items['uoms'][] = $row['uom'];
                    $items['prices'][] = -1;
                    $items['whses'][] = $row['whse_id'];
                    $items['setprice'][] = 1;
                }
            }
            $cart->UpdateItems($items, 'add to cart');
        }

        # Current options
        $this->order_id = 0;
        $this->section = 'Cart';

        # Remove cart record if this has no items
        if (count($cart->items) == 0)
        {
            $sth = $this->dbh->prepare("DELETE FROM cart WHERE user_id = ? AND user_type = ?");
            $sth->bindValue(1, (int) $user->getId(), PDO::PARAM_INT);
            $sth->bindValue(2, $this->user_type, PDO::PARAM_STR);
            $sth->execute();

            $args = array(
                'order_id' => 0,
                'order_type' => $this->order_type,
                'facility_id' => $this->facility_id,
                'cat_id' => $this->cat_id,
                'section' => $this->section);
            $preferences->set($this_app_name, '_order', serialize($args), $_COOKIE['session_id']);
        }
        else # Update the tax and shipping amounts
        {
            if ($update_tax)
                $this->UpdateTax($cart);
            if ($update_shipping)
                $this->UpdateShipping($cart);
        }
    }

    /**
     * Edit an order by setting status to Edit and move items to cart
     *
     * @param array $form
     */
    private function EditOrder($form)
    {
        global $user, $this_app_name, $preferences;

        $act = (isset ($form['act'])) ? strtolower($form['act']) : '';
        $order_id = (isset ($form['order_id'])) ? strtolower($form['order_id']) : 0;

        # Verify this order is QUEUED
        $sth_check = $this->dbh->query("SELECT status_id FROM orders WHERE id = {$form['order_id']}");
        $status_id = $sth_check->fetchColumn();

        if ($status_id == Order::$QUEUED || $status_id == Order::$EDITING)
        {
            $cart = new Cart();
            $cart->reload($form['order_id']);

            # Current options
            $args = array(
                'order_id' => $cart->getOrderId(),
                'order_type' => $cart->getOrderType(),
                'facility_id' => $cart->getFacilityId(),
                'cat_id' => $this->cat_id,
                'section' => $this->section);

            $this->order_id = $order_id;
            $this->section = 'Cart';

            # Save the current parameters.
            #
            if (isset ($_COOKIE['session_id']))
            {
                # Save options
                $preferences->set($this_app_name, '_order', serialize($args), $_COOKIE['session_id']);
            }

            # Reset the customer
            $this->facility_id = $cart->getFacilityId();
            $this->customer = new CustomerEntity($this->facility_id);

            # Order was changed
            $params["change"] = "Order: {$form['order_id']} Loaded into cart for editing\n" .
                "Updated By: {$user->getName()}\n" .
                "Status: {$status_id}\n" .
                "Type: {$cart->getOrderType()}\n" .
                "Customer ID: {$cart->getFacilityId()}";
            ActionLog::log($user, $act, $this_app_name, basename($_SERVER['PHP_SELF']), $params);
        }
        else
        {
            # Alert user does not match
            $this->msg .= "<p class='error_msg'>You do not have permission to edit this order {$form['act']}!</p>";
        }
    }

    /**
     * Generate a unique element id
     *
     * @param string $base
     * @param integer $idx
     */
    protected function element_id($base, $idx)
    {
        return "{$base}_{$idx}";
    }

    /**
     * Fill an array with address fields.
     * Takes an CustomerEntity object or array as a parameter.
     *
     * @param mixed
     *
     * @return array
     */
    public function FillAddress($addr_elem)
    {
        $address = array();

        if (is_object($addr_elem))
        {
            $address = array(
                'line1' => $addr_elem->getAddress(),
                'line2' => $addr_elem->getAddress2(),
                'line3' => "",
                'city' => $addr_elem->getCity(),
                'state' => $addr_elem->getState(),
                'region' => $addr_elem->getState(),
                'postal_code' => $addr_elem->getZip());
        }
        else if (is_array($addr_elem))
        {
            $address = array(
                'line1' => $addr_elem['address'],
                'line2' => $addr_elem['address2'],
                'line3' => "",
                'city' => $addr_elem['city'],
                'region' => $addr_elem['state'],
                'state' => $addr_elem['state'],
                'postal_code' => $addr_elem['zip']);
        }

        return $address;
    }

    /**
     * Determine if product is available
     *
     * @param integer prod_id
     *
     * @return boolean
     */
    public function InCatalog($prod_id)
    {
        global $user;

        $avail = false;

        $limit_user = "";
        if ($user->web_user)
            $limit_user .= " AND pc.viz_public = true";
        if ($this->field_user)
            $limit_user .= " AND pc.viz_internal = true";

        if ($prod_id)
        {
            $sth = $this->dbh->prepare("SELECT
				p.id, p.name, p.active, count(j.catalog_id)
			FROM products p
			LEFT JOIN (
				product_category_join j
				INNER JOIN product_category pc on j.cat_id = pc.id $limit_user
				INNER JOIN product_catalog_category c ON pc.id = c.category_id AND c.catalog_id = {$this->catalog}
			) j ON p.id = j.prod_id
			WHERE p.id = ?
			GROUP BY p.id, p.name, p.active");
            $sth->bindValue(1, (int) $prod_id, PDO::PARAM_INT);
            $sth->execute();
            list($id, $name, $active, $in_cat) = $sth->fetch(PDO::FETCH_NUM);

            if ($active && $in_cat)
                $avail = true;
            else
                $this->msg .= "<p class='error_msg'>{$name} is not available.</p>";
        }

        return $avail;
    }

    /**
     * Create an order instance and copy saved values into it
     *
     * @param array
     * @param object
     * @return object
     */
    private function InitOrder($form = null)
    {
        global $user, $preferences, $this_app_name;

        if (isset ($form['type_id']))
            $this->order_type = $form['type_id'];

        $order = new Order($this->order_id);

        # Load facility defaults
        if ($this->facility_id)
        {
            $order->setVar('cust_id', $this->customer->getCustId());
            if (!$this->order_id)
            {
                $order->setVar('type_id', $this->order_type);
                $order->setVar('order_date', time());
                $order->setVar('ship_to', Order::$SHIP_TO_FACILITY);
                $order->setVar('urgency', 1);
                $order->setVar('facility_id', $this->customer->getId());
                $order->setVar('facility_name', $this->customer->getName());
                $order->setVar('sname', $this->customer->getName());
                $order->setVar('ship_attn', 'ATTN: Rehab Dept.');
                $order->setVar('address', $this->customer->getAddress());
                $order->setVar('address2', $this->customer->getAddress2());
                $order->setVar('city', $this->customer->getCity());
                $order->setVar('state', $this->customer->getState());
                $order->setVar('zip', $this->customer->getZip());
                $order->setVar('country', $this->customer->getcountry()->getAbbr());
                $order->setVar('phone', $this->customer->getPhone());
            }
            else
            {
                if ($order->getVar('issue_id'))
                {
                    $order->setVar('shipping_cost', 0);
                }
            }

            $sth = $this->dbh->query("SELECT credit_hold FROM v_customer_entity WHERE id = {$this->facility_id}");
            list($credit_hold) = $sth->fetch(PDO::FETCH_NUM);

            if ($credit_hold && $user->web_user == false)
                $this->msg = "<p class='error_msg' style='background-color:#E0C0C0;text-align:center;'><b>This account is on Credit Hold!<br>This Order will be &quot;Queued&quot;.</b></p>";
        }

        # Copy previosly saved form
        $co_form = @unserialize($preferences->get($this_app_name, '_checkout'));
        if ($co_form)
            $order->copyFromArray($co_form);

        # Copy additional form fields
        if ($form)
        {
            # Dont overwright facility id
            $form['facility_id'] = $this->facility_id;

            # Copy current form
            $order->copyFromArray($form);
        }

        return $order;
    }

    /**
     * Move cart contents to "editing" order
     *
     * @param $form array
     */
    private function HoldOrder()
    {
        global $user, $this_app_name, $preferences;

        $msg = "";

        # Make sure there are items to be processed (Refresh can cause this)
        if ($this->cart_count == 0)
        {
            $this->msg .= "<p class='error_msg'>This order has already been processed.</p>";
            $this->order_id = null;
        }
        else
        {
            # Create order
            #
            $order = new Order($this->order_id);

            # Get any saved options
            $form = @unserialize($preferences->get($this_app_name, '_checkout'));

            # Queue order if customer is on credit hold
            $form['facility_id'] = $this->facility_id;
            $form['type_id'] = $this->order_type;
            $form['status_id'] = Order::$EDITING;
            $order_id = $order->processOrder($form);
            if ($order_id)
            {
                # Force an Editing status
                $order->change('status_id', Order::$EDITING);

                # Order was changed
                $params["change"] = "Order: {$order_id}\n" .
                    "Updated By: {$user->getName()}\n" .
                    "Status: {$order->getVar('status_id')}\n" .
                    "Type: {$order->getVar('type_id')}\n" .
                    "Customer ID: {$order->getVar('facility_id')}";
                ActionLog::log($user, "Hold", $this_app_name, basename($_SERVER['PHP_SELF']), $params);
                $order->load();
            }

            # Reset vars to release features
            $this->order_id = null;
            $this->cart_count = 0;

            $this->msg .= "<p class=\"info\" style=\"text-align:center\">Items in you shopping Cart are On Hold. Order # <b>$order_id</b> is in Editing status.</p>";

            $this->ClearSession();
        }
    }

    /**
     * Build Address HTML/JS for setting shipping information
     *
     * @param string
     * @param integer
     */
    protected function loadAddresses($addrKey, $order_id = 0, $facility_name = '')
    {
        $chck_other = "checked";
        $chck_hadndeliver = '';
        $sel = ($addrKey) ? "" : "checked";
        if ($sel)
            $chck_other = "";
        $chck_hadndeliver = '';

        $addresses = "<tr> <th class='form'>Saved Address</th><td colspan='3' class='form'>
		<input type='radio' name='mas_address_key' value='0' onClick='SetAddress_(this.form)' {$sel}> Default Ship To <br/>";


        $addr_array = $this->GetAccountingAddr();

        foreach ($addr_array as $addr)
        {
            $sel = ($addr['accounting_reference_key'] == $addrKey) ? "checked" : "";
            if ($sel)
                $chck_other = "";
            $addr['state'] = trim($addr['state']);

            $addresses .= "<input type='radio' name='mas_address_key' value='" . $addr['accounting_reference_key'] . "'" .
                "       onClick='SetAddress(this.form, address_" . $addr['accounting_reference_key'] . ");' " .
                $sel . ">" . $addr['first_name'] .

                "<script type=\"text/javascript\">
				var address_{$addr['accounting_reference_key']} = {
                                        sname     : \"{$facility_name}\",
					ship_attn : \"{$addr['title']}\",
					address   : \"{$addr['address1']}\",
					address2  : \"{$addr['address2']}\",
					city      : \"{$addr['city']}\",
					zip       : \"{$addr['zip']}\",
					phone     : \"{$addr['phone']}\",
					state     : \"{$addr['state']}\",
					country   : \"{$addr['country']}\"
				};
			 </script><br/>";

        }#foreach

        $addresses .= "<input type='radio' name='mas_address_key' value='0' onClick=\"enableShippingFields(this.form);setHandDeliver(this.form)\" $chck_hadndeliver /> Hand Deliver<br/>";
        $addresses .= "<input type='radio' name='mas_address_key' value='0' onClick=\"enableShippingFields(this.form);setOther(this.form)\" $chck_other /> Other";

        $addr_array = $this->getOtherAddress($order_id);

        if (!empty ($addr_array))
        {
            $addresses .=
                "<script type=\"text/javascript\">
                                var address_other = {
                                        sname     : \"{$addr_array[0]['sname']}\",
                                        ship_attn : \"{$addr_array[0]['ship_attn']}\",
                                        address   : \"{$addr_array[0]['address1']}\",
                                        address2  : \"{$addr_array[0]['address2']}\",
                                        city      : \"{$addr_array[0]['city']}\",
                                        zip       : \"{$addr_array[0]['zip']}\",
                                        phone     : \"{$addr_array[0]['phone']}\",
                                        state     : \"{$addr_array[0]['state']}\",
                                        country   : \"{$addr_array[0]['country']}\"
                                };
                         </script><br/>";


        }#if other

        $addresses .= "</td></tr>";

        return $addresses;
    }

    /**
     * Load order type details
     */
    private function LoadType()
    {
        if ($this->order_type)
        {
            $sth = $this->dbh->prepare("SELECT
				description, in_asset, out_asset,
				in_return, is_purchase
			FROM order_type
			WHERE type_id = ?");
            $sth->bindValue(1, (int) $this->order_type, PDO::PARAM_INT);
            $sth->execute();
            $this->type_ary = $sth->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     * 1) Build uom option list
     * 2) Select default uom
     *
     * @param array
     */
    static public function ParseUOM(&$item)
    {
        $item['uom'] = $item['price_uom'];
        $item['uom_options'] = "<option value='{$item['price_uom']}' selected>{$item['price_uom']}</option>";

        if (!empty ($item['uoms']))
        {
            $item['uom'] = null;
            $item['uom_options'] = "";

            foreach (explode(",", $item['uoms']) as $uom)
            {
                # Remove extra data (#factor) from the end
                $uom = trim(preg_replace('/\(\d+\)$/', '', $uom));

                $sel = "";
                # Select first available
                if (is_null($item['uom']))
                    $item['uom'] = $uom;

                # Use price_uom as default
                if ($uom == $item['price_uom'])
                {
                    $item['uom'] = $uom;
                    $sel = " selected";
                }

                $item['uom_options'] .= "<option value='$uom'$sel>$uom</option>";
            }
        }
    }

    /**
     * Perform update
     *
     * @param array $form
     */
    private function ProcessAdminRequest(&$form)
    {
        global $user;

        $tab = (isset ($form['tab'])) ? $form['tab'] : 'Categories';
        $act = (isset ($form['act'])) ? $form['act'] : '';
        $model_id = (isset ($form['model_id'])) ? (int) $form['model_id'] : 0;
        $prod_id = (isset ($form['prod_id'])) ? (int) $form['prod_id'] : 0;
        $cat_id = (isset ($form['cat_id'])) ? (int) $form['cat_id'] : 0;
        $dev_id = (isset ($form['dev_id'])) ? (int) $form['dev_id'] : 0;
        $sth = NULL;
        $error_msg = "";

        if ($tab == 'Categories')
        {
            if ($act == 'save')
            {
                $name = (isset ($form['category_name'])) ? $form['category_name'] : '';
                $viz_internal = (isset ($form['viz_internal'])) ? (int) $form['viz_internal'] : 0;
                $viz_public = (isset ($form['viz_public'])) ? (int) $form['viz_public'] : 0;
                $display_order = (isset ($form['display_order'])) ? (int) $form['display_order'] : 99;

                if (!$name)
                    $error_msg .= "<p class='error_msg'>Missing Category Name!</p>";
                if (!$display_order)
                    $error_msg .= "<p class='error_msg'>Invalid Value for Display Order!</p>";

                if ($error_msg == "")
                {
                    if ($cat_id)
                    {
                        $sql = "UPDATE product_category SET category_name = ?, viz_public = ?, viz_internal = ?, display_order = ? WHERE id = ?";
                        $sth = $this->dbh->prepare($sql);
                        $sth->bindValue(5, $cat_id, PDO::PARAM_INT);
                    }
                    else
                    {
                        $sql = "INSERT INTO product_category (category_name, viz_public, viz_internal, display_order) VALUES (?, ?, ?, ?)";
                        $sth = $this->dbh->prepare($sql);
                    }
                    $sth->bindValue(1, $name, PDO::PARAM_STR);
                    $sth->bindValue(2, $viz_public, PDO::PARAM_BOOL);
                    $sth->bindValue(3, $viz_internal, PDO::PARAM_BOOL);
                    $sth->bindValue(4, $display_order, PDO::PARAM_INT);
                    $sth->execute();

                    # New category set the cat_id
                    if (!$cat_id)
                        $this->cat_id = $this->dbh->lastInsertId('product_category_id_seq');

                    # Show the edit cart display form again
                    #
                    $form['act'] = 'edit_cat';
                }
            }
            if ($act == 'delete')
            {
                if (!$cat_id)
                    $error_msg = "<p class='error_msg'>No Category Selected!</p>";

                if ($error_msg == "")
                {
                    $sql = "DELETE FROM product_category WHERE id = ?";
                    $sth = $this->dbh->prepare($sql);
                    $sth->bindValue(1, $cat_id, PDO::PARAM_INT);
                    $sth->execute();

                    if ($cat_id == $this->cat_id)
                    {
                        $this->cat_id = null;
                        $form['cat_id'] = 0;
                    }
                }
            }
            if ($act == 'update')
            {
                # prod_ids - Complete Set of products
                # chk_prod_ids - Subset of the products selected

                $prod_ids = (isset ($form['prod_ids']) && is_array($form['prod_ids'])) ? $form['prod_ids'] : array();
                $chk_prod_ids = (isset ($form['chk_prod_ids']) && is_array($form['chk_prod_ids'])) ? $form['chk_prod_ids'] : array();

                if (!$cat_id)
                    $error_msg = "<p class='error_msg'>No Category Selected!</p>";

                if ($error_msg == "")
                {
                    # Remove Old
                    $sql = "DELETE FROM product_category_join WHERE cat_id = ? AND prod_id = ?";
                    $remove = $this->dbh->prepare($sql);
                    $remove->bindValue(1, $cat_id, PDO::PARAM_INT);

                    # THEN

                    # Add new record
                    $sql = "INSERT INTO product_category_join (cat_id, prod_id) VALUES (?, ?)";
                    $insert = $this->dbh->prepare($sql);
                    $insert->bindValue(1, $cat_id, PDO::PARAM_INT);

                    foreach ($prod_ids as $prod_id)
                    {
                        # Remove Old
                        $remove->bindValue(2, (int) $prod_id, PDO::PARAM_INT);
                        $remove->execute();

                        # Add new record if selected
                        if (isset ($chk_prod_ids[$prod_id]))
                        {
                            $insert->bindValue(2, (int) $prod_id, PDO::PARAM_INT);
                            $insert->execute();
                        }
                    }
                }
            }
        }
        else if ($tab == 'Devices')
        {
            if ($act == 'update')
            {
                $prod_ids = (isset ($form['prod_ids']) && is_array($form['prod_ids'])) ? $form['prod_ids'] : array();
                $chk_prod_ids = (isset ($form['chk_prod_ids']) && is_array($form['chk_prod_ids'])) ? $form['chk_prod_ids'] : array();

                if (!$dev_id)
                    $error_msg = "<p class='error_msg'>No Device Selected!</p>";

                if ($error_msg == "")
                {
                    # Remove Old
                    $sql = "DELETE FROM equipment_supply WHERE model_id = ? AND prod_id = ?";
                    $remove = $this->dbh->prepare($sql);
                    $remove->bindValue(1, $dev_id, PDO::PARAM_INT);

                    # THEN

                    # Add new record
                    $sql = "INSERT INTO equipment_supply (model_id, prod_id) VALUES (?, ?)";
                    $insert = $this->dbh->prepare($sql);
                    $insert->bindValue(1, $dev_id, PDO::PARAM_INT);

                    foreach ($prod_ids as $prod_id)
                    {
                        # Remove Old
                        $remove->bindValue(2, (int) $prod_id, PDO::PARAM_INT);
                        $remove->execute();

                        # Add new record if selected
                        if (isset ($chk_prod_ids[$prod_id]))
                        {
                            $insert->bindValue(2, (int) $prod_id, PDO::PARAM_INT);
                            $insert->execute();
                        }
                    }
                }
            }
        }
        else if ($tab == 'CategoriesProduct' || $tab == 'DevicesProduct')
        {
            if ($act == 'save')
            {
                $cat_ids = (isset ($form['cat_ids']) && is_array($form['cat_ids'])) ? $form['cat_ids'] : array();
                $dev_ids = (isset ($form['dev_ids']) && is_array($form['dev_ids'])) ? $form['dev_ids'] : array();

                $track_inventory = (isset ($form['track_inventory'])) ? (int) $form['track_inventory'] : 0;
                $lot_required = (isset ($form['lot_required'])) ? (int) $form['lot_required'] : '0';
                $long_description = (isset ($form['long_description'])) ? trim($form['long_description']) : '';
                $specifications = (isset ($form['specifications'])) ? trim($form['specifications']) : '';
                $purpose = (isset ($form['purpose'])) ? trim($form['purpose']) : '';
                $onhold = (isset ($form['onhold'])) ? (int) $form['onhold'] : 0;
                $featured = (isset ($form['featured'])) ? (int) $form['featured'] : 0;
                $special = (isset ($form['special'])) ? (int) $form['special'] : 0;
                $email_subject = (isset ($form['email_subject'])) ? trim($form['email_subject']) : '';
                $email_body = (isset ($form['email_body'])) ? html_entity_decode($form['email_body']) : '';

                if (!$prod_id)
                    $error_msg = "<p class='error_msg'>No Product Selected!</p>";


                // Change the hold status
                if (isset ($form['hold_submit']))
                {
                    $capa_id = (isset ($form['capa_id'])) ? trim($form['capa_id']) : NULL;
                    $eco_number = (isset ($form['eco_number'])) ? trim($form['eco_number']) : NULL;
                    $comments = (isset ($form['comments'])) ? trim($form['comments']) : NULL;

                    # Clear the active flag on existing record
                    $this->dbh->exec("UPDATE product_hold_history SET active = FALSE WHERE prod_id = {$prod_id}");

                    if ($form['hold_submit'] == 'release')
                    {
                        $onhold = 0;
                        $hold_action = "Release Hold";
                    }
                    else if ($form['hold_submit'] == 'hold')
                    {
                        $onhold = 1;
                        $hold_action = "Hold Product";
                    }

                    # Add new history record
                    $sql = "INSERT INTO product_hold_history
					(prod_id, user_id, tstamp, action, active, capa_id, eco_number, comments)
					VALUES (?,?,?,?,?,?,?,?)";
                    $sth = $this->dbh->prepare($sql);
                    $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
                    $sth->bindValue(2, $user->getID(), PDO::PARAM_INT);
                    $sth->bindValue(3, date('Y-m-d h:i:s'), PDO::PARAM_STR);
                    $sth->bindValue(4, $hold_action, PDO::PARAM_STR);
                    $sth->bindValue(5, true, PDO::PARAM_BOOL);
                    $sth->bindValue(6, $capa_id, PDO::PARAM_STR);
                    $sth->bindValue(7, $eco_number, PDO::PARAM_STR);
                    $sth->bindValue(8, fix_encoding($comments), PDO::PARAM_STR);
                    $sth->execute();
                }

                if ($error_msg == "")
                {
                    # Check if there are existing records for this category
                    $check_query = $this->dbh->query("SELECT * FROM product_detail WHERE prod_id = {$prod_id}");
                    $existing_record = $check_query->fetch();

                    # Update the existing records if there is any
                    if ($existing_record)
                    {
                        $sql = "UPDATE product_detail SET track_inventory = ?, long_description = ?, specifications = ?, purpose = ?, lot_required = ?, onhold = ?, featured = ?, special = ?, email_subject = ?, email_body = ? WHERE prod_id = ?";
                        $sth = $this->dbh->prepare($sql);
                        $sth->bindValue(1, $track_inventory, PDO::PARAM_BOOL);
                        $sth->bindValue(2, $long_description, PDO::PARAM_STR);
                        $sth->bindValue(3, $specifications, PDO::PARAM_STR);
                        $sth->bindValue(4, $purpose, PDO::PARAM_STR);
                        $sth->bindValue(5, $lot_required, PDO::PARAM_BOOL);
                        $sth->bindValue(6, $onhold, PDO::PARAM_BOOL);
                        $sth->bindValue(7, $featured, PDO::PARAM_BOOL);
                        $sth->bindValue(8, $special, PDO::PARAM_BOOL);
                        $sth->bindValue(9, $email_subject, PDO::PARAM_STR);
                        $sth->bindValue(10, $email_body, PDO::PARAM_STR);
                        $sth->bindValue(11, $prod_id, PDO::PARAM_INT);
                        $sth->execute();
                    }
                    else
                    {
                        # Add new records
                        $sql = "INSERT INTO product_detail (prod_id, track_inventory, long_description, specifications, purpose, lot_required, onhold, featured, special, email_subject, email_body)
											VALUES (?,?,?,?,?,?,?,?,?,?,?)";
                        $sth = $this->dbh->prepare($sql);
                        $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
                        $sth->bindValue(2, $track_inventory, PDO::PARAM_BOOL);
                        $sth->bindValue(3, $long_description, PDO::PARAM_STR);
                        $sth->bindValue(4, $specifications, PDO::PARAM_STR);
                        $sth->bindValue(5, $purpose, PDO::PARAM_STR);
                        $sth->bindValue(6, $lot_required, PDO::PARAM_BOOL);
                        $sth->bindValue(7, $onhold, PDO::PARAM_BOOL);
                        $sth->bindValue(8, $featured, PDO::PARAM_BOOL);
                        $sth->bindValue(9, $special, PDO::PARAM_BOOL);
                        $sth->bindValue(10, $email_subject, PDO::PARAM_STR);
                        $sth->bindValue(11, $email_body, PDO::PARAM_STR);
                        $sth->execute();
                    }

                    # Clear the existing records for this categroy
                    $sth = $this->dbh->query("DELETE FROM product_category_join WHERE prod_id = {$prod_id}");

                    # Add new records
                    $sql = "INSERT INTO product_category_join (prod_id, cat_id) VALUES (?, ?)";
                    $sth = $this->dbh->prepare($sql);
                    $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
                    foreach ($cat_ids as $cat_id)
                    {
                        $sth->bindValue(2, (int) $cat_id, PDO::PARAM_INT);
                        $sth->execute();
                    }

                    # Clear the existing records for this device
                    $sth = $this->dbh->query("DELETE FROM equipment_supply WHERE prod_id = {$prod_id}");

                    # Add new records
                    $sql = "INSERT INTO equipment_supply (prod_id, model_id) VALUES (?, ?)";
                    $sth = $this->dbh->prepare($sql);
                    $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
                    foreach ($dev_ids as $dev_id)
                    {
                        $sth->bindValue(2, (int) $dev_id, PDO::PARAM_INT);
                        $sth->execute();
                    }
                }

                # Show the edit cart display form again
                # Set propper tab
                #
                $form['act'] = 'product';
                $form['tab'] = ($form['tab'] == 'DevicesProduct') ? 'Devices' : 'Categories';
            }
        }
        else if ($tab == 'CategoriesImage')
        {
            $prod_id = (isset ($form['prod_id'])) ? $form['prod_id'] : 0;
            $img_id = (isset ($form['img_id'])) ? $form['img_id'] : 0;
            $img_name = (isset ($form['img_name'])) ? strtolower($form['img_name']) : 0;
            $img_description = (isset ($form['img_description'])) ? htmlentities($form['img_description'], ENT_QUOTES) : '';
            $img_dir = Config::$MEDIA_FILE_PATH_FULL . "/product_image/";

            # Validation
            if (!$prod_id)
                $error_msg = "<p class='error_msg'>Unknow Product!</p>";

            $ext = '';
            if (isset ($_FILES['img_file']) && is_uploaded_file($_FILES['img_file']['tmp_name']))
            {
                # Only allow .jpg images
                $ext = strtolower(end(explode('.', $_FILES['img_file']['name'])));
                if ($ext != 'jpg' && $ext != 'png')
                    $error_msg = "<p class='error_msg'>Unrecognised File Type!</p>";
            }

            if ($error_msg == "")
            {
                $sth = $this->dbh->prepare("SELECT p.code, count(i.*)
				FROM products p
				LEFT JOIN product_img i ON p.id = i.prod_id AND i.name NOT LIKE ('%_thumb')
				WHERE p.id = ?
				GROUP BY p.code");
                $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
                $sth->execute();
                list($prod_code, $img_count) = $sth->fetch(PDO::FETCH_NUM);
                $prod_code = strtolower($prod_code);

                $sth = $this->dbh->prepare("SELECT id
				FROM product_img
				WHERE name = ?");
                $sth->bindValue(1, $prod_code, PDO::PARAM_STR);
                $sth->execute();
                list($main_image_is_there) = $sth->fetch(PDO::FETCH_NUM);

                if ($act == 'save')
                {
                    # The main image will hava a thumbnail
                    $add_thumnail = false;

                    # Update
                    if ($img_id)
                    {
                        # Write the file
                        if (isset ($_FILES['img_file']))
                            move_uploaded_file($_FILES['img_file']['tmp_name'], "{$img_dir}{$img_name}.{$ext}");

                        $sth = $this->dbh->prepare("UPDATE product_img SET
							name = ?, description = ?
						WHERE id = ?");
                        $sth->bindValue(1, $img_name . '.' . $ext, PDO::PARAM_STR);
                        $sth->bindValue(2, $img_description, (($img_description) ? PDO::PARAM_STR : PDO::PARAM_NULL));
                        $sth->bindValue(3, $img_id, PDO::PARAM_INT);
                        $sth->execute();

                        /// Replacing the MAIN image - if the img does NOT have an '_' - then make a new thumbnail

                        $sth = $this->dbh->prepare("SELECT name
						FROM product_img
						WHERE id = ?");
                        $sth->bindValue(1, $img_id, PDO::PARAM_INT);
                        $sth->execute();
                        list($img_name) = $sth->fetch(PDO::FETCH_NUM);

                        if (!preg_match('/_/', $img_name))
                        {
                            $add_thumnail = ($img_name == $prod_code);
                            $res = 1;
                        }
                    }
                    # Add new records
                    else
                    {
                        # Set the image name
                        # Format is {product code}_{number}
                        # The main image will not have a number appended
                        /// $img_name = ($img_count) ? "{$prod_code}_{$img_count}" : "$prod_code";

                        if (!$main_image_is_there)
                            $img_name = $prod_code;

                        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        /// The user could delete images out of numberical order.
                        /// EXAMPLE WHEN USING $img_count: When you have images named $prod_code_1 and $prod_code_2 and $prod_code_3
                        /// If you delete $prod_code_1 then come back and upload a new image, it will end up being $prod_code_3. Now you have 2 $prod_code_3's
                        /// Let's avoid this.

                        if ($main_image_is_there)
                        {
                            $image_is_there = null;

                            $sth = $this->dbh->prepare("SELECT name
							FROM product_img
							WHERE prod_id = ?
							AND name NOT LIKE '%thumb'
							AND name LIKE '%\\_%'
							order by id DESC
							LIMIT 1");
                            $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
                            $sth->execute();
                            list($image_is_there) = $sth->fetch(PDO::FETCH_NUM);
                            if ($image_is_there && $image_is_there != $prod_code)
                            {
                                $p = explode("_", $image_is_there);
                                $img_count = $p[1] + 1;
                                $img_name = "{$prod_code}_{$img_count}";
                            }
                            else
                            {
                                $img_name = "{$prod_code}_1";
                            }
                        }
                        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                        # Write the file
                        $res = move_uploaded_file($_FILES['img_file']['tmp_name'], "{$img_dir}{$img_name}.{$ext}");
                        if ($res)
                        {
                            # Add DB record
                            $sth = $this->dbh->prepare("INSERT INTO product_img (prod_id,name,description)
							VALUES (?,?,?)");
                            $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
                            $sth->bindValue(2, $img_name . '.' . $ext, PDO::PARAM_STR);
                            $sth->bindValue(3, $img_description, (($img_description) ? PDO::PARAM_STR : PDO::PARAM_NULL));
                            $sth->execute();

                            # Add a thumbnail if this is the first image
                            $add_thumnail = ($img_name == $prod_code);
                        }
                        else
                            $error_msg = "<p class='error_msg'>Failed to Write File!</p>";
                    }

                    if ($add_thumnail)
                    {
                        $thumb_name = "{$prod_code}_thumb";

                        # Remove any exiting thumbnail
                        # Then use convert to resize the original image to 30x30
                        exec("rm {$img_dir}{$thumb_name}.{$ext}");
                        exec("convert -size 60x60 {$img_dir}{$img_name}.{$ext} -resize 60x60 +profile '*' {$img_dir}{$thumb_name}.{$ext}");

                        $sth = $this->dbh->prepare("DELETE FROM product_img
							WHERE prod_id=? AND name=? AND description=?");
                        $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
                        $sth->bindValue(2, $thumb_name, PDO::PARAM_STR);
                        $sth->bindValue(3, "Thumbnail", PDO::PARAM_STR);
                        $sth->execute();


                        if ($res)
                        {
                            $sth = $this->dbh->prepare("INSERT INTO product_img
							(prod_id, name, description) VALUES (?,?,?)");
                            $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
                            $sth->bindValue(2, $thumb_name, PDO::PARAM_STR);
                            $sth->bindValue(3, "Thumbnail", PDO::PARAM_STR);
                            $sth->execute();
                        }
                        else
                            $error_msg = "<p class='error_msg'>Failed to Write Thumbnail File {$img_name}.{$ext}!</p>";
                    }
                }
                if ($act == 'delete')
                {
                    $thumb_id = null;
                    # Delete File
                    if ($img_name)
                    {
                        if (file_exists("{$img_dir}{$img_name}.jpg"))
                        {
                            if (!unlink("{$img_dir}{$img_name}.jpg"))
                                $error_msg = "<p class='error_msg'>Failed to Remove File!</p>";
                        }

                        if (!preg_match('/_/', $img_name))
                        {
                            $img_name .= "_thumb";

                            if (!unlink("{$img_dir}{$img_name}.jpg"))
                                $error_msg = "<p class='error_msg'>Failed to Remove File!</p>";

                            $sth = $this->dbh->prepare("SELECT id
							FROM product_img
							WHERE name = ?");
                            $sth->bindValue(1, $img_name, PDO::PARAM_STR);
                            $sth->execute();
                            list($thumb_id) = $sth->fetch(PDO::FETCH_NUM);
                        }
                    }

                    # Delete Record
                    if ($img_id)
                    {
                        $sth = $this->dbh->prepare("DELETE FROM product_img WHERE id = ?");
                        $sth->bindValue(1, $img_id, PDO::PARAM_INT);
                        $sth->execute();
                    }
                    if ($thumb_id)
                    {
                        $sth = $this->dbh->prepare("DELETE FROM product_img WHERE id = ?");
                        $sth->bindValue(1, $thumb_id, PDO::PARAM_INT);
                        $sth->execute();
                    }
                }
            }

            # Show the edit product image form
            # Set propper tab
            #
            $form['act'] = 'image';
            $form['tab'] = 'Categories';
        }
        else if ($tab == 'Catalog')
        {
            $catalog_id = (isset ($form['catalog'])) ? (int) $form['catalog'] : 0;
            $cat_ids = (isset ($form['category_ids']) && is_array($form['category_ids'])) ? $form['category_ids'] : array();
            $chk_cat_ids = (isset ($form['chk_category_ids']) && is_array($form['chk_category_ids'])) ? $form['chk_category_ids'] : array();

            if (!$catalog_id)
                $error_msg .= "<p class='error_msg'>No Catalog Selected!</p>";

            if ($error_msg == "")
            {
                if ($act == 'save')
                {
                    # Clear the existing records
                    $catalog_name = (isset ($form['catalog_name'])) ? htmlentities($form['catalog_name']) : 'Default';
                    $sth = $this->dbh->prepare("UPDATE product_catalog SET catalog_name = ? WHERE id = ?");
                    $sth->bindValue(1, $catalog_name, PDO::PARAM_STR);
                    $sth->bindValue(2, $catalog_id, PDO::PARAM_INT);
                    $sth->execute();
                }
                else
                {
                    # Clear the existing records
                    $sql = "DELETE FROM product_catalog_category WHERE catalog_id = ? AND category_id = ?";
                    $sth_rm = $this->dbh->prepare($sql);
                    $sth_rm->bindValue(1, $catalog_id, PDO::PARAM_INT);

                    # Add new records
                    $sql = "INSERT INTO product_catalog_category (catalog_id, category_id) VALUES (?, ?)";
                    $sth_in = $this->dbh->prepare($sql);
                    $sth_in->bindValue(1, $catalog_id, PDO::PARAM_INT);

                    foreach ($cat_ids as $cat_id)
                    {
                        $sth_rm->bindValue(2, (int) $cat_id, PDO::PARAM_INT);
                        $sth_rm->execute();

                        if (isset ($chk_cat_ids[$cat_id]))
                        {
                            $sth_in->bindValue(2, (int) $cat_id, PDO::PARAM_INT);
                            $sth_in->execute();
                        }
                    }
                }
            }
        }
        else
        {
            $error_msg = "<p class='error_msg'>Unknow Option !</p>";
        }

        if ($error_msg)
            $this->msg .= $error_msg;
    }

    /**
     * Build a product img link for the item
     *
     * @param integer
     * @param string
     * @param string
     * @param float
     * @param float
     *
     * @return string
     */
    protected function ProductImg($prod_id, $prod_code, $section = null, $width = null, $height = null)
    {
        $url = null;
        if ($section)
            $url = "{$_SERVER['PHP_SELF']}?section=$section&act=product&prod_id=$prod_id";

        $pic = Order::GetProductImg($prod_code, $url, $width, $height);

        return $pic;
    }

    /**
     * Build a product link for the item
     *
     * @param integer
     * @param string
     * @param string
     *
     * @return string
     */
    protected function ProductLink($prod_id, $text, $section = null)
    {
        $link = "<a alt='Product Details' title='Product Details'";
        $link .= " href='{$_SERVER['PHP_SELF']}";
        $link .= "?section=$section&act=product&prod_id=$prod_id'>$text</a>";

        return $link;
    }

    /**
     * Show Printer freindly version of the order
     *
     * @param object
     */
    public function PrintOrder($order)
    {
        global $user, $sh, $hippa_access;

        $order_date = ($order->getVar('order_date') > 0) ? date('D j M Y', $order->getVar('order_date')) : '--';
        $ship_date = ($order->getVar('ship_date') > 0) ? date('D j M Y', $order->getVar('ship_date')) : '--';
        $inst_date = ($order->getVar('inst_date') > 0) ? date('D j M Y', $order->getVar('inst_date')) : '--';

        $show_patient_info = ($order->getVar('cust_entity_type') == CustomerEntity::$ENTITY_PATIENT)
            ? $this->show_patient : true;

        $sname = ($show_patient_info) ? $order->getVar('sname') : "Customer";

        $so_number = ($order->getVar('mas_sales_order') > 0) ? $order->getVar('mas_sales_order') : '--';
        $po_number = ($order->getVar('po_number')) ? $order->getVar('po_number') : '--';
        $phone = ($order->getVar('phone')) ? $order->getVar('phone') : '--';

        $comments_row = "";
        if ($order->getVar('comments'))
        {
            $comments_row = "
			<tr>
				<th class=\"subheader\">Order Notes</th>
			</tr>
			<tr>
				<td>{$order->getVar('comments')}</td>
			</tr>";
        }

        $tracking = Order::FormatTrackingNo($order->getVar('tracking_num'), false);
        if ($order->getVar('ret_tracking_num'))
            $tracking .= "<br/>" . Order::FormatTrackingNo($order->getVar('ret_tracking_num'), false);

        if (!$tracking)
            $tracking = '--';

        # Build Item list
        $item_rows = "";
        $sub_total = 0;
        $tax = (double) $order->getVar('tax_amount');
        $shipping = (double) $order->getVar('shipping_cost');
        $this->ShowItemRows($order, $item_rows, $sub_total);
        $total = "\$" . number_format($sub_total + $tax + $shipping, 2);
        $shipping = "\$" . number_format($shipping, 2);
        $tax = "\$" . number_format($tax, 2);
        $sub_total = "\$" . number_format($sub_total, 2);

        $parent_row = "";
        if ($order->getVar('parent_order'))
            $parent_row = "
			<tr>
				<th align=right>Reference Order:</th>
				<td>{$order->getVar('parent_order')}</td>
			</tr>";

        $bo_row = "";
        if ($order->getVar('back_order'))
        {
            $bo_row = "
			<tr>
				<th align=right>Back Order #</th>
				<td>{$order->getVar('back_order')}</td>
			</tr>";
        }

        # Show these rows for Customer Orders
        #
        $email = $ordered_by = $fax = "";
        if ($order->getVar('type_id') == Order::$CUSTOMER_ORDER)
        {
            if ($order->getVar('email'))
                $email = "
			<tr>
		    	<th align=right>Email</th>
				<td>
					{$order->getVar('email')}
				</td>
			</tr>";

            if ($order->getVar('fax'))
                $fax = "
			<tr>
		    	<th align=right>Fax</th>
				<td>
					{$order->getVar('fax')}
				</td>
			</tr>";
        }

        if ($order->getVar('ordered_by'))
            $ordered_by = "
		<tr>
	    	<th align=right>Customer Name</th>
			<td>
				{$order->getVar('ordered_by')}
			</td>
		</tr>";

        $order_id = implode(',', $order->order_group);

        return <<<END
<style type='text/css'>
.hdr
{
	font-size: 11pt;
	font-weight: bold;
	color: white;
	background-color: #5E8AB0;
	padding: 5px;
}

.cl_r
{
	clear: right;
}

.cl_l
{
	clear: left;
}
.cl_b
{
	clear: both;
}
th.view
{
	text-align: right;
}
.view.il
{
	margin: 0;
	clear: none;
}
.print th
{
	font-size: 10pt;
	font-weight: bold;
}
.print td
{
	font-size: 10pt;
}
.notes
{
	font-size: 8pt;
	min-height: 3em;
	_height: 3em;
	padding: 3px;
	margin-bottom: 5px;
	border: 1px solid #CECECE;
}
</style>
	<div style='padding: 5px; border: 1px solid #5E8AB0;'>
		<table cellpadding=0 cellspacing=0>
			<tr>
				<td align='center'>Thank You For Your Order!</td>
			</tr>
			<tr>
				<td>
					<div class='hdr'>Order Summary</div>
					<table class='print' align=left cellspacing=0 cellpadding=2 >
						<tr>
							<th align=right>Order #:</th>
							<td>{$order_id}</td>
						</tr>
						<tr>
							<th align=right>Date:</th>
							<td>{$order_date}</td>
						</tr>
						<tr>
							<th align=right>Cust ID:</th>
							<td>{$order->getVar('cust_id')}</td>
						</tr>
						<tr>
							<th align=right>PO #:</th>
							<td>$po_number</td>
						</tr>
						<tr>
							<th align=right>SO #:</th>
							<td>$so_number</td>
						</tr>
						<tr>
							<th align=right>Type:</th>
							<td>{$order->getVar('type_text')}</td>
						</tr>
						<tr>
							<th align=right>Status:</th>
							<td>{$order->getVar('status_name')}</td>
						</tr>
					</table>
					<table class="print" align=right cellpadding=2 cellspacing=0>
						<tr>
							<th align=right>Shipping Method:</th>
							<td>{$order->getVar('ship_method')}</td>
						</tr>
						<tr>
							<th align=right>Service:</th>
							<td>{$order->getVar('service_level_text')}</td>
						</tr>
						<tr>
							<th align=right>Tracking #</th>
							<td>$tracking</td>
						</tr>
						<tr>
							<th align=right>Shipped Date:</th>
							<td>$ship_date</td>
						</tr>
						<tr>
							<th align=right>Delivery Date:</th>
							<td>$inst_date</td>
						</tr>
						<tr>
							<th align=right>Phone #:</th>
							<td>$phone</td>
						</tr>
						{$fax}
						{$email}
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<div class='hdr'>Ship To</div>
					<div class='notes'>
						{$sname}<br>
						{$order->getVar('address')}
						{$order->getVar('address2')}<br>
						{$order->getVar('city')}, {$order->getVar('state')} {$order->getVar('zip')}
					</div>
					<table align=right cellpadding="5" cellspacing="2" class="print il">
						{$bo_row}
						{$parent_row}
						{$ordered_by}
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<div class='hdr'>Notes</div>
					<div class='notes'>
						{$order->getVar('comments')}
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class='hdr'>Items</div>
					<table cellpadding="2" width=700 class="view" style='margin:0;'>
						{$item_rows}
						<tr>
							<td class='view' colspan='2' style='text-align:right;line-height:25px;'>
								Sub Total: {$sub_total}<br/>
								Estimated Shipping: {$shipping}<br/>
								<u>Estimated Tax: {$tax}</u><br/>
								<b>Estimated Total: <span style='border: 1px solid grey;background-color:#D3DCE3;'>{$total}</span></b>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
END;
    }#PrintOrder()




    public function SetBPI()
    {
        # Base price index used to find prefered price different per customer
        $cpgk = $bpi = 0;
        if ($this->facility_id)
        {
            $sth = $this->dbh->prepare("SELECT ce.base_price_index, cust_price_group_key
				FROM v_customer_entity ce WHERE ce.id = ?");
            $sth->bindValue(1, (int) $this->facility_id, PDO::PARAM_INT);
            $sth->execute();
            list($bpi, $cpgk) = $sth->fetch(PDO::FETCH_NUM);
        }

        $this->bpi = $bpi;
        $this->cpgk = $cpgk;
    }

    /**
     * Determine catalog based on user
     */
    protected function SetCatalog()
    {
        global $user;

        $this->catalog = self::$CORPORATE_CATALOG;

        if ($this->field_user)
            $this->catalog = self::$CPM_CATALOG;
        else if ($user->web_user)
            $this->catalog = self::$FACILITY_CATALOG;

        if ($this->customer && is_numeric($this->customer->getCustId()))
            $this->catalog = self::$ININC_CATALOG;

        /**
               * # FUTURE USE
               * # Customer catalogs
               *
              if ($this->facility_id)
              {
                  $sth = $this->dbh->prepare("SELECT catalog_id FROM customer_catalog WHERE customer_id = ?");
                  $sth->bindValue($this->facility_id, PDO::PARAM_INT);
                  if ($row = $sth->fetch(PDO::PARAM_ASSOC))
                      $this->catalog_id = $row['catalog_id'];
              }
              */

    }

    /**
     * Populate the cart_count field
     */
    protected function SetCC()
    {
        global $user;

        # Find the number of items currently in the cart_item table for this account/session
        $sth = $this->dbh->prepare("SELECT count(*) as cc FROM cart_item WHERE user_id = ? AND user_type = ?");
        $sth->bindValue(1, $user->getId(), PDO::PARAM_INT);
        $sth->bindValue(2, $this->user_type, PDO::PARAM_STR);
        $sth->execute();
        $this->cart_count = $sth->fetchColumn();
    }

    /**
     * Set the facility id for this session
     *
     * @param array
     */
    public function SetCustomer($req)
    {
        global $user, $preferences, $this_app_name;

        if ($user->web_user)
            return;
        if (!is_array($this->site_options))
            return;

        $new_id = (isset ($req['facility_id'])) ? $req['facility_id'] : null;

        # If no change ignore the action
        if ($this->facility_id <> $new_id)
        {
            # Change if user is not limited and cart is empty
            if ($this->cart_count > 0)
                $this->HoldOrder();

            $this->facility_id = $new_id;

            # Populate customer
            $this->customer = new CustomerEntity($this->facility_id);

            $this->ClearSession();
        }

        if (isset ($req['switch']))
        {
            $sth = $this->dbh->prepare("UPDATE sessions SET user_id = ?, user_type = 'FacilityUser' WHERE id = ?");
            $sth->bindParam(1, $this->facility_id, PDO::PARAM_INT);
            $sth->bindParam(2, $_COOKIE['session_id'], PDO::PARAM_STR);
            $sth->execute();

            # Redirect to reflect session change
            //echo "<script type='text/javascript'> window.location.href = '{$_SERVER['PHP_SELF']}'; </script>";
            //return;
        }
    }

    /**
     * Sets the class Property value defined by $var.
     *
     * @param $key string
     * @param $value mixed
     *
     * @return nothing
     */
    public function SetVar($key = null, $value = null)
    {
        if (@property_exists($this, $key))
        {
            $this->{$key} = $value;
        }
    }

    /**
     * Display The appropriate section
     *
     * @param $form array
     */
    public function Show($form)
    {
        global $user, $preferences, $this_app_name;

        $act = (isset ($form['act'])) ? strtolower($form['act']) : '';
        $this->SetCC();

        # DEBUG
#echo "<pre>";
#print_r($this);
#echo "</pre>";
# DEBUG

        # Setup Navigation Bar
        $this->navigation = $this->ShowOptions();

        if ($this->facility_id)
        {
            # Protect Patient Data
            if ($this->customer && $this->customer->getEntityType() == CustomerEntity::$ENTITY_PATIENT)
            {
                # Make sure user is on LAN and has Hippa access
                if ($this->show_patient === false)
                {
                    $this->facility_id = 0;
                    $this->customer = new CustomerEntity($this->facility_id);
                    $this->disabled = true;
                    $this->msg .= "<p class='error_msg'>Access to patient information is restricted!</p>";
                }
            }

            # Show customer info
            $this->navigation .= $this->ShowCustomerInfo();

            # Infuture May have customer catalogs per customer
            # $this->SetCatalog();

            # Set Pricing information for the customer
            $this->SetBPI();
        }

        # Display Section
        #
        $show_method = "Show{$this->section}";
        if (!@method_exists($this, $show_method))
            $show_method = "ShowHome";

        #$this->left = $this->ShowHelpTable();

        # Call the Appropriate "Show" Method
        # This will set the content section of the page
        $this->$show_method($form);

        ## Show entrust seal for "store.acplus.com" host
        $seal = "";
        if (strtolower($_SERVER['HTTP_HOST']) == "store.acplus.com")
        {
            $seal = "<br/>
			<script language='javascript' src='https://seal.entrust.net/seal.js?domain=store.acplus.com&img=11'></script>
			<a href='http://www.entrust.net'>SSL</a>
			<script language='javascript' type='text/javascript'>goEntrust();</script>";
        }

        echo <<<END
		<div class='ws_contents'>
		<form id="wsorder" name="wsorder" action="{$_SERVER['PHP_SELF']}" method="post">
			<input type="hidden" name="order_id" value="{$this->order_id}"/>
			<input type="hidden" name="facility_id" value="{$this->facility_id}"/>
			<input type="hidden" name="section" value="{$this->section}"/>
			<input type="hidden" name="act" value=""/>
			{$this->navigation}
			{$this->msg}
			<table cellspacing='10' cellpadding='0' width='100%'>
				<tr valign='top'>
					<td align='left' style='padding-left: 20px;'>
						{$this->left}
						$seal
					</td>
					<td align='left'>
						{$this->contents}
					</td>
					<td align='right' style='padding-right: 20px;'>
						{$this->right}
					</td>
				</tr>
			</table>
		</form>
		</div>
END;
        #		echo "<pre>";
#		print_r($this);
#		echo "</pre>";
    }

    /**
     * Display html for Home Page Summary
     *
     * param array $form
     *
     * return string (html)
     */
    protected function ShowAccountInfo($form)
    {
        global $user;

        $reset_bttn = "";
        $leased_equipment = '';

        $addr_info = "<p>No address available</p>";

        # Show last order
        if ($this->facility_id)
        {
            # Include password rest button for facilities
            #
            if (($this->customer->getEntityType() == CustomerEntity::$ENTITY_FACILITY ||
                $this->customer->getEntityType() == CustomerEntity::$ENTITY_DEALER) &&
                $user->hasAccessToApplication('reset_xcart_pwd'))
            {
                $cust_id = $this->customer->getCustId();
                $reset_bttn = "<div><button type=\"button\" onClick=\"resetPassword('$cust_id')\">Reset Webstore Password</button></div>";
            }

            if ($user->web_user)
            {
                // Reset the array
                $addr_array = array();

                // Add facility address
                $addr_array[] = array(
                    'first_name' => "Shipping",
                    'title' => "Rehab Dept:",
                    'address1' => $this->customer->getAddress(),
                    'address2' => $this->customer->getAddress2(),
                    'city' => $this->customer->getCity(),
                    'state' => $this->customer->getState(),
                    'zip' => $this->customer->getZip(),
                    'phone' => $this->customer->getPhone(),
                    'fax' => '',
                    'email' => '');


            }

            $addr_array = $this->GetAccountingAddr();

            if (count($addr_array))
            {
                $addr_info = "";
                foreach ($addr_array as $addr)
                {
                    if ($user->web_user)
                    {
                        if ($addr['first_name'] == 'CntctAddr')
                            continue;

                        if ($addr['default_billing'])
                            $addr['first_name'] = "Bill To:";
                        else if ($addr['default_shipping'])
                            $addr['first_name'] = "Ship To:";
                        else
                            continue;
                    }

                    $street = $addr['address1'];
                    if ($addr['address2'])
                        $street .= "<br/>{$addr['address2']}";

                    $addr_info .= "
					<div class='addr'>
						<u>{$addr['first_name']}</u><br/>
						{$addr['title']}<br/>
						$street<br/>
						{$addr['city']}, {$addr['state']} {$addr['zip']}<br/>
						<span style='color:#000000;'>Phone:</span> {$addr['phone']}
					</div>";
                }
            }

            $equip_rows = "";
            $sth = $this->dbh->prepare("SELECT
				m.description, m.model, a.model_id, a.serial_num, a.status
			FROM lease_asset_status a
			INNER JOIN equipment_models m ON a.model_id = m.id AND m.type_id = 1
			WHERE facility_id = ?");
            $sth->bindValue(1, $this->facility_id, PDO::PARAM_INT);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $supply_link = "";

                /**
                             * No supply link until they are defined
                             *
                            # Check user permissions
                            if (($user->web_user || in_array(Order::$CUSTOMER_ORDER, $this->allowed_types)) && $this->disabled == false)
                                $supply_link = "<img class=\"form_bttn\" src=\"images/medical-supplies_32.png\" height='24'
                                onClick=\"window.location='{$_SERVER['PHP_SELF']}?act=supplies&amp;model_id={$row['model_id']}'\"
                                alt=\"Add Supplies\" title=\"Add Supplies\">";
                             */

                $class = "";
                if ($row['status'] == 'In Transit')
                {
                    $class = "class='alert'";
                    $supply_link = $row['status'];
                }

                $equip_rows .= "<tr $class style='font-size:small;'>
					<td>{$row['description']}</td>
					<td>{$row['model']}</td>
					<td>{$row['serial_num']}</td>
					<td>{$supply_link}</td>
				<tr>";
            }
        }

        $this->left = "";

        $this->contents = <<<END
		<div class='banner' style='margin:15px 0 0 0;'>Addresses</div>
		<table cellspacing='0' cellpadding="4" class="view" style='width:100%; margin-top:2px'>
			<tr>
				<td>
					$addr_info
				</td>
			</tr>
		</table>

		<div class='banner' style='margin:0;'>My Equipment</div>
		<table cellspacing='0' cellpadding="4" class="view" style='width:100%; margin-top:2px'>
			<tr>
				<th class='list'>Device</th>
				<th class='list'>Model</th>
				<th class='list'>Serial</th>
				<th class='list'>&nbsp;</th>
			</tr>
			{$equip_rows}
		</table> $reset_bttn
END;
    }

    /**
     * Show Admin content
     *
     * @param array
     */
    protected function ShowAdmin($form)
    {
        global $user, $preferences, $this_app_name, $this_app_admin_name;

        # Process Admin Request
        #
        if (isset ($form['act']) && isset ($_COOKIE['session_id']))
        {
            # Save Changes
            $this->ProcessAdminRequest($form);

            # Reset session args
            $args = array(
                'order_id' => $this->order_id,
                'order_type' => $this->order_type,
                'facility_id' => $this->facility_id,
                'cat_id' => $this->cat_id,
                'dev_id' => $this->dev_id,
                'section' => $this->section);
            $preferences->set($this_app_name, '_order', serialize($args), $_COOKIE['session_id']);
        }

        # Find tab contents to display
        #
        $act = (isset ($form['act'])) ? $form['act'] : '';
        $tab = (isset ($form['tab'])) ? $form['tab'] : 'Categories';
        $Catalog = ($tab == 'Catalog') ? "_active" : "";
        $Categories = ($tab == 'Categories') ? "_active" : "";
        $Devices = ($tab == 'Devices') ? "_active" : "";

        if ($user->hasAccessToApplication($this_app_admin_name))
        {
            # Dont Show Help or Cart info
            $this->left = '';
            $this->right = '';

            $this->contents = <<<END
			<div class='tabing'>
				<ul>
					<li class='tab{$Catalog}'>
						<a href='{$_SERVER['PHP_SELF']}?section=Admin&tab=Catalog' alt='Edit Product Catalog' title='Edit Product Catalog'>
							<em>Catalogs</em>
						</a>
					</li>
					<li class='tab{$Categories}'>
						<a href='{$_SERVER['PHP_SELF']}?section=Admin&tab=Categories' alt='Edit Product Categories' title='Edit Product Categories'>
							<em>Product Categories</em>
						</a>
					</li>
					<li class='tab{$Devices}'>
						<a href='{$_SERVER['PHP_SELF']}?section=Admin&tab=Devices' alt='Edit Device Supplies' title='Edit Device Supplies'>
							<em>Device Supplies</em>
						</a>
					</li>
				</ul>
			</div>
			<div style='border-top:4px solid #5E8AB0;'>&nbsp;</div>
END;

            if ($act == 'product')
                $this->contents .= $this->ShowProductEdit($form);
            else if ($act == 'image')
                $this->contents .= $this->ShowImageEdit($form);
            else if ($tab == 'Devices')
                $this->contents .= $this->ShowDeviceSupplies($form);
            else if ($tab == 'Catalog')
                $this->contents .= $this->ShowCatalogEdit($form);
            else
                $this->contents .= $this->ShowCategoryEdit($form);
        }
        else
        {
            $this->ShowHome($form);
        }
    }

    /**
     * Create text for requested banner AD
     *
     * @param string
     * @param integer
     * @param integer
     *
     * @return string
     */
    public function ShowBannerAd($img_src, $width = null, $height = null)
    {
        global $user;

        $banner = "";

        if ($user->web_user)
        {
            $width = (is_float($width)) ? "width='$width'" : $width;
            $height = (is_float($height)) ? "height='$height'" : $height;

            $banner = "
			<p class='banner_ad'>
				<img src='$img_src' $width $height alt='Web Banner' title='Web Banner'/>
			</p>";
        }

        return $banner;
    }

    /**
     * Show cart details.
     *
     * @param array form
     *
     * @return string
     */
    protected function ShowCart($form)
    {
        global $user;

        if ($this->facility_id)
        {
            $this->left = $this->ShowCategoryList();
            $this->left .= $this->ShowHelpTable();
            $this->right = "<div style='width:100px;'>&nbsp;</div>";

            if ($this->cart_count)
            {
                $cart = new Cart($user->getId(), $this->user_type);
                $cart_detail = $this->showCartDetail($cart);
                $check_out = "<span style='float:right'><a href='{$_SERVER['PHP_SELF']}?section=Checkout' style='font-size:medium;'>checkout</a></span>";
                $button = "<div style=\"text-align:center;padding:5px;\">{$this->GetButton('cart')}</div>";
            }
            else
            {
                $cart_detail = "<p class='info' align=center><i>Your cart is empty.</i></p>";
                $check_out = "<span style='float:right'><a href='{$_SERVER['PHP_SELF']}?section=ClearCart' style='font-size:medium;'>clear</a></span>";
                $button = "";
            }

            $this->contents = "
			<div class='ttl'>Shopping Cart{$check_out}</div>
			<div style='background-color: #CCCCCC; padding: 5px;'>Showing {$this->cart_count} products</div>
			{$button}
			{$cart_detail}
			{$button}";
        }
        else
        {
            if ($user->web_user)
                $this->ShowWelcome($form);
            else
                $this->ShowFacilitySelect($form);
        }
    }

    /**
     * Prints the contents of the cart.
     *
     * @param object
     * @param boolean
     *
     * @return string
     */
    public function showCartDetail($cart, $show_totals = true)
    {
        global $user;

        $Company = is_numeric($this->customer->getCustId()) ? SalesOrder::$ININC_CO_ID : SalesOrder::$CO_ID;
        $WhseID = is_numeric($this->customer->getCustId()) ? SalesOrder::$ININC_WHSE : SalesOrder::$PURCHASE_WHSE;

        # Determine what Headers to Show
        if ($user->web_user)
        {
            $col_span = 3;
            $headers = "<tr>
				<th class='list'>Item #</th>
				<th class='list'>Product</th>
				<th class='list'>Quantity</th>
				<th class='list'>List</th>
				<th class='list'>Preferred</th>
				<th class='list'></th>
			</tr>";
        }
        else if ($this->limit_user)
        {
            $col_span = 5;
            $headers = "<tr>
				<th class='list'>Item #</th>
				<th class='list'>Product</th>
				<th class='list'>Quantity</th>
				<th class='list'>Available</th>
				<th class='list'>Max</th>
				<th class='list'>List</th>
				<th class='list'>Preferred</th>
				<th class='list'></th>
			</tr>";
        }
        else
        {
            $col_span = 6;
            $headers = "<tr>
				<th class='list'>Item #</th>
				<th class='list'>Product</th>
				<th class='list'>Quantity</th>
				<th class='list'>Upsell</th>
				<th class='list'>Available</th>
				<th class='list'>Max</th>
				<th class='list'>List</th>
				<th class='list'>Base</th>
				<th class='list'>Price</th>
				<th class='list'></th>
			</tr>";
        }

        $price_overide = $user->hasAccessToApplication('priceoverride');

        $item_rows = "";

        $orderType = $cart->getOrderType();

        # Get tax amount
        $tax_amount = $cart->getTaxAmount();

        # Get shipping cost
        $shipping_amount = $cart->getShippingAmount();

        # init sub totals to 0
        $list_sum = $base_sum = $sale_sum = 0;

        $row_class = 'on';


        foreach ($cart->items as $n => $item)
        {

            if ($item->getVar('model') != '')
                $model = $item->getVar('model');


            $type = $item->getVar('prod_id');


            # use item_num as an index
            $i = $item->getVar('item_num');

            $available = '&nbsp;';
            $hl = "";

            $qty = $item->getVar('quantity') * LineItem::UOMConversion($item->getVar('prod_id'), $item->getVar('uom'));
            $sale_amount = (double) $item->getVar('price');

            $item->SetPriceInfo($this->order_type, $this->bpi, $this->cpgk);

            # add amounts
            $list_sum += (double) $item->getVar('list_amount');
            $base_sum += (double) $item->getVar('base_amount');
            $sale_sum += $sale_amount;

            # Format price information
            $sale_amount = number_format($sale_amount, 2);
            $list_amount = ($item->getVar('list_amount') != 'NA') ? "\$" . number_format($item->getVar('list_amount'), 2) : 'NA';
            $base_amount = ($item->getVar('base_amount') != 'NA') ? "\$" . number_format($item->getVar('base_amount'), 2) : 'NA';

            $prod_name = $item->getVar('name');

            # Check inventory for non device items
            if (!$item->getVar('is_device'))
            {
                $avail_qty = Cart::GetAvailQty($item->getVar('code'), true, $WhseID, $Company);

                # Non Inventory will be null
                if (is_null($avail_qty))
                {
                    $avail_qty = 0;
                    $available = 'NA';
                }
                else
                {
                    $avail_qty -= Cart::GetPendingQty($item->getVar('prod_id'), $this->order_id);
                    $available = $avail_qty;
                }

                // Show Max qty for users
                if (!$user->web_user)
                {
                    # Highlight the row if ordering too much
                    if ($item->getVar('max_quantity') > 0 && $qty > $item->getVar('max_quantity'))
                    {
                        $hl = "_hl";
                        $prod_name .= "<br/>(Quantity is more than maximum allowed)";
                    }

                    if ($qty > $avail_qty && $available !== 'NA')
                    {
                        $hl = "_hl";
                        $prod_name .= "<br/>(Quantities are in limited supply)";
                    }
                }

                $quantity_input = "<input type='text'  name='quantities[$i]' value='{$item->getVar('quantity')}' onkeydown=\"PriceChange(event, '{$this->element_id('change', $i)}');\" size='2' maxlength='5' />";
                $uom_options = Forms::getUOMOptions($item->getVar('code'), $item->getVar('uom'));
                $uom_input = "<select name='uoms[$i]' onchange=\"PriceChange(event, '{$this->element_id('change', $i)}');\" >
					$uom_options
				</select>";
            }
            else
            {
                $quantity_input = "<input type='checkbox' name='quantities[$i]' value='{$item->getVar('quantity')}' onclick=\"PriceChange(event, '{$this->element_id('change', $i)}');\" checked/>";
                $uom_input = "<input type='hidden' name='uoms[$i]' value='EA'>EA";
            }

            $pic = $this->ProductImg($item->getVar('prod_id'), $item->getVar('code'), 'Catalog', null, 30);

            # Only show upsell Checkbox to CSR
            $upsell_cb = $this->UpsellCheckbox($item->getVar('upsell'), $i);

            $rm_btn = $this->GetButton('remove', "$i");

            # Some rows are not shown base on user type
            if ($user->web_user)
            {
                $upsell = "";
                $available = "";
                $max_qty = "";
                $list_amount = "<td class='prod'>{$list_amount}</td>";
                $base_amount = "";
            }
            else if ($this->limit_user)
            {
                $upsell = "";
                $available = "<td class='prod'>{$available}</td>";
                $max_qty = "<td class='prod'>{$item->getVar('max_quantity')}</td>";
                $list_amount = "<td class='prod'>{$list_amount}</td>";
                $base_amount = "";
            }
            else
            {
                $upsell = "<td class='prod' style=\"text-align:center\">$upsell_cb</td>";
                $available = "<td class='prod'>{$available}</td>";
                $max_qty = "<td class='prod'>{$item->getVar('max_quantity')}</td>";
                $list_amount = "<td class='prod'>{$list_amount}</td>";
                $base_amount = "<td class='prod'>{$base_amount}</td>";
            }

            $p_class = "flat";
            $price_read_only = "readonly";
            $p_size = strlen($sale_amount);
            if ($p_size < 5)
                $p_size = 5;
            if ($price_overide)
            {
                $p_class = "";
                $price_read_only = "";
            }

            $item_rows .= "
		<tr class=\"{$row_class}{$hl}\">
			<td class='prod' style=\"text-align:center\">
				{$pic}<br>
				#{$item->getVar('code')}
			</td>
			<td class='prod' style=\"text-align:left\">
				{$prod_name}
			</td>
			<td class='prod' style=\"text-align:left\">
				<input type=\"hidden\" name=\"prod_ids[$i]\" value=\"{$item->getVar('prod_id')}\" />
				<input type=\"hidden\" name=\"assets[$i]\" value=\"{$item->getVar('asset_id')}\" />
				<input type=\"hidden\" name=\"swap_assets[$i]\" value=\"{$item->getVar('swap_asset_id')}\" />
				<input type=\"hidden\" name=\"whses[$i]\" value=\"{$item->getVar('whse_id')}\" />
				<input type=\"hidden\" name=\"upsell[$i]\" value=\"{$item->getVar('upsell')}\" />
				$quantity_input&nbsp;$uom_input
			</td>
			{$upsell}
			{$available}
			{$max_qty}
			{$list_amount}
			{$base_amount}
			<td class='prod'>
				<input type=\"hidden\" id='{$this->element_id('setprice', $i)}' name=\"setprice[$i]\" value=\"0\" />
				<input type='text' class='$p_class' style='text-align:right;' id='{$this->element_id('prices', $i)}' name='prices[$i]' size='$p_size' value='\${$sale_amount}' $price_read_only />
			</td>
			<td class='prod'>{$rm_btn}</td>
		</tr>";

            $row_class = ($row_class == 'on') ? 'off' : 'on';
            $i++;
        }

        # Format amounts
        #
        $list_total = number_format($list_sum + $tax_amount + $shipping_amount, 2);
        $base_total = number_format($base_sum + $tax_amount + $shipping_amount, 2);
        $sale_total = number_format($sale_sum + $tax_amount + $shipping_amount, 2);
        $tax_amount = number_format($tax_amount, 2);
        $shipping_amount = number_format($shipping_amount, 2);
        $list_sum = number_format($list_sum, 2);
        $base_sum = number_format($base_sum, 2);
        $sale_sum = number_format($sale_sum, 2);

        if ($show_totals)
        {
            if ($this->limit_user)
            {
                $base_sub = "";
                $base_shipping = "";
                $base_tax = "";
                $base_total = "";
            }
            else
            {
                $base_sub = "<td class='view'>\${$base_sum}</td>";
                $base_shipping = "<td class='view'>\${$shipping_amount}</td>";
                $base_tax = "<td class='view'>\${$tax_amount}</td>";
                $base_total = "<td class='view'>\${$base_total}</td>";
            }
            $item_rows .= "
			<tr style='text-align:right;'>
				<td class='view' colspan='$col_span' style='text-align:right;font-weight:normal;'>
					Sub Total:
				</td>
				<td class='view'>\${$list_sum}</td>
				$base_sub
				<td class='view'>\${$sale_sum}</td>
				<td class='view'>&nbsp;</th>
			</tr>
			<tr style='text-align:right;'>
				<td class='view' colspan='$col_span' style='text-align:right;font-weight:normal;'>
					Estimated Shipping:
				</td>
				<td class='view'>\${$shipping_amount}</td>
				$base_shipping
				<td class='view'>\${$shipping_amount}</td>
				<td class='view'>
					<a href='{$_SERVER['PHP_SELF']}?section=Cart&act=update_shipping' style='font-size:x-small;' alt='Recalculate Shipping Cost' title='Recalculate Shipping Cost'>refresh</a>
				</td>
			</tr>
			<tr style='text-align:right;'>
				<td class='view' colspan='$col_span' style='text-align:right;font-weight:normal;'>
					Estimated Tax:
				</td>
				<td class='view'>\${$tax_amount}</td>
				$base_tax
				<td class='view'>\${$tax_amount}</td>
				<td class='view'>
					<a href='{$_SERVER['PHP_SELF']}?section=Cart&act=update_tax' style='font-size:x-small;' alt='Recalculate Estimated Tax' title='Recalculate Estimated Tax'>refresh</a>
				</td>
			</tr>
			<tr style='text-align:right;font-weight:bold;'>
				<td class='view' colspan='$col_span'>
					Estimated Total:
				</td>
				<td class='view'>\${$list_total}</td>
				$base_total
				<td class='view' style='border: 1px solid grey;'>\${$sale_total}</td>
				<td class='view'>&nbsp;</td>
			</tr>";
        }

        return "
		<table width='100%' align='center' cellpadding='5' cellspacing='0' class='list'>
			{$headers}
			{$item_rows}
		</table>";
    }

    /**
     * Set right section contents to the list of items
     * in the cart.
     */
    protected function ShowCartSummary()
    {
        global $user;

        $cart = new Cart($user->getId(), $this->user_type);

        $tax_amount = (double) $cart->getTaxAmount();
        $shipping_amount = (double) $cart->getShippingAmount();
        $sub_total = 0;

        # Set table trows and sub_total
        #
        if ($this->cart_count)
        {
            $item_rows = "";
            $this->ShowItemRows($cart, $item_rows, $sub_total);

            # Format the output
            $total = "\$" . number_format($sub_total + $tax_amount + $shipping_amount, 2);
            $sub_total = "\$" . number_format($sub_total, 2);
            $tax_amount = "\$" . number_format($tax_amount, 2);
            $shipping_amount = "\$" . number_format($shipping_amount, 2);

            $item_rows .= "
			<tr>
				<td class='view' colspan='2' style='text-align:right;line-height:20px;'>
					Sub Total: {$sub_total}<br/>
					Estimated Shipping: {$shipping_amount}<br/>
					<u>Estimated Tax: {$tax_amount}</u><br/>
					<b>Estimated Total:</b> <span style='border: 1px solid grey;background-color:#D3DCE3;'>{$total}</span>
				</td>
			</tr>
			<tr>
				<td class='buttons' colspan='2'>
					<input class='submit' type='submit' name='submit_btn' value='Change Quantity' onclick=\"this.form.section.value='Cart';\"/>
				</td>
			</tr>";
        }
        else
        {
            $item_rows = "<tr>
				<td class='view' colspan=2 nowrap>
					&nbsp;&nbsp;&nbsp;&nbsp;
					<i>Your cart is empty.</i>
					&nbsp;&nbsp;&nbsp;&nbsp;
				</td>
			</tr>";
        }

        $header = "Shopping Cart";
        if ($this->cart_count)
            $header .= "<span style='float:right'><a href='{$_SERVER['PHP_SELF']}?section=Checkout' style='font-size:small;color:#FBFBFB;'>checkout</a></span>";

        return <<<END
		<table cellpadding="5" class="view">
			<tr>
				<th class="subheader" colspan="2">$header</th>
			</tr>
			{$item_rows}
		</table>
END;
    }

    /**
     * Display html for Product Catalog
     *
     * @param array $form
     */
    protected function ShowCatalog($form)
    {
        global $user;

        if ($this->facility_id || $this->limit_user == false)
        {
            $page_num = isset ($_REQUEST['page']) ? $_REQUEST['page'] : 1;
            $action = isset ($_REQUEST['act']) ? strtolower($_REQUEST['act']) : '';
            $sort = isset ($_REQUEST['sort']) ? strtolower($_REQUEST['sort']) : '';
            $dir = isset ($_REQUEST['dir']) ? strtolower($_REQUEST['dir']) : '';

            $this->loadType();

            if ($action == 'product')
                $contents = $this->ShowProduct($_REQUEST);
            else if ($this->cat_id || isset ($form['search']))
                $contents = $this->getCategorySupplies();
            else
            {
                $contents = "
				<div class='ad_wrapper' style='margin-top: 15px'>
					<div class='banner' style='margin:0;'>Featured</div>
					{$this->GetUpsaleProducts(0, 9, 180)}
				</div>";
            }

            # Prepend Categories table to the left side of the display
            #
            $this->left = $this->ShowCategoryList();
            $this->left .= $this->ShowHelpTable();

            $this->right = $this->ShowCartSummary();

            $this->contents = "
			<input type='hidden' name='order_type' value='{$this->order_type}' />
			{$contents}";
        }
        else
        {
            # For Unknkown Web users show Welcome text
            # For CS Rep show the facility select input
            if ($user->web_user)
                $this->ShowWelcome($form);
            else
                $this->ShowFacilitySelect($form);
        }
    }

    /**
     * Display html for Catalog editing
     *
     * @param array $form
     */
    private function ShowCatalogCategory($form)
    {
        $catalog_id = isset ($form['catalog']) ? $form['catalog'] : $this->catalog;

        $categories = "";
        $customer_name = "";
        $display_order = "";
        $chk_viz_internal = "";
        $chk_viz_public = "";

        $sth = $this->dbh->query("SELECT
			c.id,
			c.catalog_name,
			(
				SELECT count(*) FROM product_catalog_category pcc WHERE pcc.catalog_id = c.id
			) as category_count
		FROM product_catalog c
		ORDER BY c.catalog_name");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $h = ($row['id'] == $catalog_id) ? "_h" : "";

            $categories .= "
			<tr>
				<td class='view$h' style='white-space:nowrap;'>
					<a href=\"{$_SERVER['PHP_SELF']}?section=Admin&tab=Catalog&catalog={$row['id']}\">
						{$row['catalog_name']}
					</a>
				</td>
				<td class='view$h' align='center'>
					{$row['category_count']}
				</td>
				<td class='view$h' style='white-space:nowrap;'>
					<a href=\"{$_SERVER['PHP_SELF']}?section=Admin&tab=Catalog&act=edit&catalog={$row['id']}\">
						rename
					</a>
				</td>
			</tr>";
        }

        return <<<END
		<table>
			<input type='hidden' name='tab' value='Catalog'/>
			<input type='hidden' name='catalog' value="{$catalog_id}"/>
			<tr valign='top'>
				<td style='padding-left: 20px;'>
					<table width='200' class='view' cellspacing='0' cellpadding='2' style='margin:0;padding: 2px;'>
						<tr>
							<th class='subheader' colspan=3>Catalog</th>
						</tr>
						<tr>
							<th class='list' style='font-size:small;text-align:left;'>Name</th>
							<th class='list' style='font-size:small;text-align:left;'>#&nbsp;Categories</th>
							<th class='list'>&nbsp;</th>
						</td>
						$categories
						<!--
						<tr>
							<td class='buttons' colspan=3>
								<input class='submit' type='button' name='new' value='Add Custom Catalog' onclick="
									document.wsorder.cat_id.value='';
									document.wsorder.act.value='edit_cat';
									document.wsorder.submit();"/>
							</td>
						</tr>
						-->
					</table>
				</td>
				<td id='edit_cat' align='center' style='padding-left: 50px;'>
					{$this->getCatalogCategories($catalog_id)}
				</td>
			</td>
		</table>
END;
    }

    /**
     * Display html for Catalog editing
     *
     * @param array $form
     */
    private function ShowCatalogEdit($form)
    {
        $catalog_id = isset ($form['catalog']) ? $form['catalog'] : $this->catalog;

        $categories = "";
        $customer_name = "";
        $display_order = "";
        $chk_viz_internal = "";
        $chk_viz_public = "";

        $sth = $this->dbh->query("SELECT
			c.id,
			c.catalog_name,
			(
				SELECT count(*) FROM product_catalog_category pcc WHERE pcc.catalog_id = c.id
			) as category_count
		FROM product_catalog c
		ORDER BY c.catalog_name");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $h = ($row['id'] == $catalog_id) ? "_h" : "";

            $categories .= "
			<tr>
				<td class='view$h' style='white-space:nowrap;'>
					<a href=\"{$_SERVER['PHP_SELF']}?section=Admin&tab=Catalog&catalog={$row['id']}\">
						{$row['catalog_name']}
					</a>
				</td>
				<td class='view$h' align='center'>
					{$row['category_count']}
				</td>
				<td class='view$h' style='white-space:nowrap;'>
					<a href=\"{$_SERVER['PHP_SELF']}?section=Admin&tab=Catalog&act=rename&catalog={$row['id']}\">
						rename
					</a>
				</td>
			</tr>";
        }

        $act = (isset ($form['act'])) ? $form['act'] : '';
        if ($act == 'rename')
            $edit_table = $this->GetCatalogTable($catalog_id);
        else
            $edit_table = $this->GetCatalogCategories($catalog_id);

        return <<<END
		<table>
			<input type='hidden' name='tab' value='Catalog'/>
			<input type='hidden' name='catalog' value="{$catalog_id}"/>
			<tr valign='top'>
				<td style='padding-left: 20px;'>
					<table width='200' class='view' cellspacing='0' cellpadding='2' style='margin:0;padding: 2px;'>
						<tr>
							<th class='subheader' colspan=3>Catalog</th>
						</tr>
						<tr>
							<th class='list' style='font-size:small;text-align:left;'>Name</th>
							<th class='list' style='font-size:small;text-align:left;'>#&nbsp;Categories</th>
							<th class='list'>&nbsp;</th>
						</td>
						$categories
						<!--
						<tr>
							<td class='buttons' colspan=3>
								<input class='submit' type='button' name='new' value='Add Custom Catalog' onclick="
									document.wsorder.cat_id.value='';
									document.wsorder.act.value='edit_cat';
									document.wsorder.submit();"/>
							</td>
						</tr>
						-->
					</table>
				</td>
				<td id='edit_cat' align='center' style='padding-left: 50px;'>
					{$edit_table}
				</td>
			</td>
		</table>
END;
    }

    /**
     * Display html for Device Category editing
     *
     * @param array $form
     */
    private function ShowCategoryEdit($form)
    {
        $categories = "";
        $category_name = "";
        $display_order = "";
        $chk_viz_internal = "";
        $chk_viz_public = "";

        $sth = $this->dbh->query("SELECT
			id, category_name, viz_internal, viz_public, display_order,
			(	SELECT count(*)
				FROM product_category_join j
				INNER JOIN products p ON j.prod_id = p.id AND p.active = true
				WHERE j.cat_id = c.id
			) as prod_count
		FROM product_category c
		ORDER BY c.display_order");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $h = ($row['id'] == $this->cat_id) ? "_h" : "";

            if ($row['id'] == $this->cat_id)
            {
                $category_name = $row['category_name'];
                $display_order = $row['display_order'];
                $chk_viz_internal = ($row['viz_internal']) ? "checked" : "";
                $chk_viz_public = ($row['viz_public']) ? "checked" : "";
            }
            $categories .= "<tr>
			<td class='view$h' style='white-space:nowrap;'>
				<a href=\"{$_SERVER['PHP_SELF']}?section=Admin&tab=Categories&cat_id={$row['id']}\">
					{$row['category_name']}
				</a>
			</td>
			<td class='view$h' align='center'>
				{$row['prod_count']}
			</td>
			<td class='view$h'>
				<span style='float:right'>
					<a href=\"{$_SERVER['PHP_SELF']}?section=Admin&tab=Categories&cat_id={$row['id']}&act=edit_cat\">
						edit
					</a>
				</span>
			</td>
			</tr>";
        }

        $act = (isset ($form['act'])) ? $form['act'] : '';
        if ($act == 'edit_cat')
        {
            $del_button = ($this->cat_id)
                ? "<input class='submit' type='submit' name='remove' value='Delete' onclick=\"
					if (confirm('Are you sure you wish to delete this category'))
					{
						this.form.act.value='delete';
						return true;
					}
					return false;\"/>"
                : "";

            $edit_table = "<table class='view' cellpadding='2' style='margin:0;'>
						<tr>
							<th class='subheader' colspan='2'>Category</th>
						</tr>
						<tr>
							<th class='form'>Category Name</th>
							<td class='form'>
								<input type='text' name='category_name' value='$category_name' size='20' maxlength='128'/>
							</td>
						</tr>
						<tr>
							<th class='form'>Visible to Field Staff</th>
							<td class='form'>
								<input type='checkbox' name='viz_internal' value='1' $chk_viz_internal/>
							</td>
						</tr>
						<tr>
							<th class='form'>Visible to the Public</th>
							<td class='form'>
								<input type='checkbox' name='viz_public' value='1' $chk_viz_public/>
							</td>
						</tr>
						<tr>
							<th class='form'>Display Order</th>
							<td class='form'>
								<input type='text' name='display_order' value='$display_order' size='5' maxlength='10'/>
							</td>
						</tr>
						<tr>
							<td class='buttons' colspan='2'>
								<input class='submit' type='submit' name='save' value='Submit' onclick=\"this.form.act.value='save';\"/>
								{$del_button}
							</td>
						</tr>
					</table>";
        }
        else
        {
            $edit_table = $this->getCategoryEditSupplies();
        }

        return <<<END
		<table>
			<input type='hidden' name='tab' value='Categories'/>
			<input type='hidden' name='cat_id' value="{$this->cat_id}"/>
			<tr valign='top'>
				<td style='padding-left: 20px;'>
					<table width='200' class='view' cellspacing='0' cellpadding='2' style='margin:0;padding: 2px;'>
						<tr>
							<th class='subheader' colspan='3'>Categories</th>
						</tr>
						<tr>
							<th class='list' style='font-size:small;text-align:left;'>Name</th>
							<th class='list' style='font-size:small;text-align:left;'>#&nbsp;Products</th>
							<th class='list'>&nbsp;</th>
						</td>
						$categories
						<tr>
							<td class='buttons' colspan='4'>
								<input class='submit' type='button' name='new_cat' value='New' onclick="
									document.wsorder.cat_id.value='';
									document.wsorder.act.value='edit_cat';
									document.wsorder.submit();"/>
							</td>
						</tr>
					</table>
				</td>
				<td id='edit_cat' align='center' style='padding-left: 50px;'>
					{$edit_table}
				</td>
			</td>
		</table>
END;
    }

    /**
     * Build html table listing available categories
     *
     * @return string
     */
    protected function ShowCategoryList()
    {
        global $user;

        if ($this->facility_id || $this->limit_user == false)
        {
            $vizable = "true";
            if ($user->web_user)
                $vizable = "c.viz_public = true";
            if ($this->field_user)
                $vizable = "c.viz_internal = true";

            $categories = "";
            $sth = $this->dbh->query("SELECT
				c.id, c.category_name as name
			FROM product_category c
			INNER JOIN product_catalog_category j
				ON c.id = j.category_id
				AND j.catalog_id = {$this->catalog}
			WHERE {$vizable} ORDER BY c.display_order");
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $h = ($row['id'] == $this->cat_id) ? "_h" : "";

                $categories .= "<tr>
				<td class='view$h'>
					<a href=\"{$_SERVER['PHP_SELF']}?section=Catalog&cat_id={$row['id']}\">
						{$row['name']}
					</a>
				</td>
				</tr>";
            }
        }

        return "
		<table width='200' class='view' cellpadding='5' cellspacing='0' style='margin: 15px 0;'>
			<tr>
				<th class='subheader'>Categories:</th>
			</tr>
			{$categories}
		</table>";
    }

    /**
     * Show Checkout html
     *
     * @param $form array
     */
    protected function ShowCheckout(&$form)
    {
        global $preferences, $user, $date_format, $this_app_name;

        $action = isset ($form['act']) ? strtolower($form['act']) : '';

        if ($action == 'process')
        {
            if (isset ($this->order))
                $this->contents .= $this->ShowOrder($this->order);
        }
        else
        {
            # Check customer status
            #
            if ($this->facility_id > 0 && $this->order_type != Order::$SUPPLY_ORDER)
            {
                $sth = $this->dbh->query("SELECT active FROM v_customer_entity WHERE id = {$this->facility_id}");
                list($active) = $sth->fetch(PDO::FETCH_NUM);

                # Diplay problems if any with this order
                if (!$active)
                    $this->msg .= "<p class='error_msg'>This account is Not Active.</p>";
            }


            $init_form = (isset ($form['load_tstamp'])) ? $form : null;

            # Build the order
            # Get form html
            $order = $this->InitOrder($init_form);

            # Save new form
            if (isset ($_COOKIE['session_id']) && isset ($form['load_tstamp']))
                $preferences->set($this_app_name, '_checkout', serialize($form), $_COOKIE['session_id']);

            $this->contents = $this->showCheckoutForm($order);
        }

        # No need to show cart information
        #
        $this->right = '';
        $this->left = $this->ShowHelpTable();
    }

    /**
     * Prints the checkout form.
     *
     * @param object $order
     *
     * @return string
     */
    public function ShowCheckoutForm($order)
    {
        global $user, $preferences, $date_format;

        $disabled = ($this->disabled) ? "disabled" : "";
        $po_output = "";
        if (!$this->facility_id)
        {
            $this->msg .= "<p class='error_msg>Please Login to place an order</p>";
        }
        else
        {
            if ($this->customer->getEntityType() & 7 > 0)
            {
                $sth = $this->dbh->query("SELECT po_required FROM facilities_details WHERE facility_id = {$this->facility_id}");
                list($po_required) = $sth->fetch(PDO::FETCH_NUM);
                if ($po_required)
                    $po_output = "<tr class='warning'>
			 		<td class='form' colspan='4' align='center'>PO Number Required</td>
				</tr>";
            }
        }

        $calendar_format = str_replace(array('Y', 'd', 'm', 'M'), array('%Y', '%d', '%m', '%b'), $date_format);
        $order_date = ($order->getVar('order_date') > 0) ? date('D j M Y', $order->getVar('order_date')) : '';

        $ship_to = $order->getVar('ship_to');
        $urgency = abs($order->getVar('urgency'));

        $ship_to_facility = ($ship_to == Order::$SHIP_TO_FACILITY) ? 'checked' : '';
        $ship_to_home = ($ship_to == Order::$SHIP_TO_CPM) ? 'checked' : '';
        $ship_to_other = ($ship_to == Order::$SHIP_TO_OTHER) ? 'checked' : '';

        $urgency_order = ($urgency == 1) ? 'checked' : '';
        $urgency_install = ($urgency == 2) ? 'checked' : '';
        $inst_date = $order->getVar('inst_date') ? date($date_format, $order->getVar('inst_date')) : '';

        # Set shipping method control
        $ship_method = ($order->getVar('ship_method') == "") ? "Ground" : $order->getVar('ship_method');

        if ($this->limit_user)
            $remove = array('Freight');
        else
            $remove = null;

        $ship_method_control = "
		<select name=\"ship_method\" onchange='UpdateTotal();'>";
        $ship_method_control .= Order::createShippingMethodList($ship_method, $remove);
        $ship_method_control .= "
		</select><span id='tnt'> <img src='images/spinner.gif' height=20 /> Loading</span>";

        # Create options list
        $service_level_options = Forms::createServiceLevelList($order->getVar('service_level'));

        if ($this->order_type == Order::$SUPPLY_ORDER)
        {
            $third_party_row = "<input type='hidden' name='third_party' value='{$order->getVar('third_party')}' />";
        }
        else
        {
            $chk_third_party = ($order->getVar('third_party')) ? "checked" : "";
            $third_party_row = "<tr>
				<th class='form'>Third Party</th>
				<td class='form' colspan='3'>
					<input type='checkbox' name='third_party' value='1' {$chk_third_party}/>
				</td>
			</tr>";
        }

        # Set Order by row
        $ob_label = "Customer Name";
        $ordered_by = "";
        if ($this->order_type == Order::$CUSTOMER_ORDER)
        {
            $ordered_by = "
			<tr>
                        <th class=\"form\" style='text-align:left;'>Customer Name<span class='required'>*</span>:</th>
			    <td class=\"form\" colspan='3' align='left'>
                                <input type=\"text\" name=\"ordered_by\" value=\"{$order->getVar('ordered_by')}\" size=\"15\" maxlength=\"64\">
			    </td>
			</tr>";
        }

        # Setup address inputs
        $address_inputs = $this->GetAddressInputs($order);

        # Load Shopping Cart details
        $item_html = "";
        $sub_total = 0;

        # Set the item list from Cart
        $cart = new Cart($user->getId(), $this->user_type);
        $this->ShowItemRows($cart, $item_html, $sub_total);

        # Get tax and shipping
        $tax_amount = ($sub_total > 0) ? $this->UpdateTax($cart) : 0;
        $shipping_cost = (double) $cart->getShippingAmount();

        # Format the values
        $total = "\$" . number_format($sub_total + $tax_amount + $shipping_cost, 2);
        $shipping = "\$" . number_format($shipping_cost, 2);
        $tax = "\$" . number_format($tax_amount, 2);
        $sub_total = "\$" . number_format($sub_total, 2);

        $tnt = "";
        if (!$this->limit_user)
        {
            $init_ac = "";

            $service_inputs = "
			<tr>
				<th class='form' nowrap>Service</th>
				<td class='form' colspan='3' align='left'>
					<select name='service_level'>
						$service_level_options
					</select>
				</td>
			</tr>";

            $bill_to_inputs = "
			<tr>
				<th class='form' style='white-space:nowrap;'>Bill to<span class='required'>*</span>:</th>
				<td class='form' colspan='3'>" . htmlentities($order->getVar('facility_name')) . "</td>
			</tr>";

            $ship_to_inputs = "<input type='hidden' name='ship_to' value='1'>";

            $ship_to_inputs .= $this->loadAddresses($order->getVar('mas_address_key'),
                $order->getVar('id'),
                $order->getVar('facility_name'));
        }
        else if ($this->field_user)
        {
            $init_ac = "InitAutoComplete(false);";

            $service_inputs = "<input type='hidden' name='service_level' value='{$order->getVar('service_level')}'/>";

            $bill_to_inputs = "
			<tr>
				<th class='form' style='white-space:nowrap;'>Facility:</th>
				<td class='form' colspan='3'>
				<div id='fac_container' style='position:relative;'>
					<input type='text' id='fac_name' name='fac_name' value='{$order->getVar('facility_name')}' size='35' style='width:20em; position:static; overflow: visible;' />
					<img class='form_bttn' src='images/cancel.png' onClick='clearFacilitySelect(document.wsorder)' alt='Clear Facility' title='Clear Facility'>
					<div id='acc_facility' style='font-size:small; position:static;'></div>
				</div>
				</td>
			</td>";

            $ship_to_inputs = "
			<tr>
				<th class='form' nowrap>Ship to<span class='required'>*</span>:</th>
				<td class='form' colspan='3'>
					<input type='radio' id='fac_addr_btn' name='ship_to' value='1' onClick='enableShippingFields(this.form);SetAddress_(this.form);' {$ship_to_facility}> Facility
					<br/>
					<input type='radio' name='ship_to' value='2' onClick=\"enableShippingFields(this.form);setHome(this.form)\" {$ship_to_home}> CPM Home
					<br/>
					<input type='radio' name='ship_to' value='3' onClick=\"enableShippingFields(this.form);setOther(this.form)\" {$ship_to_other}> Other
				</td>
			</tr>";
        }
        else
        {
            $init_ac = "";

            $service_inputs = "<input type='hidden' name='service_level' value='{$order->getVar('service_level')}'/>";

            $bill_to_inputs = "";

            $ship_to_inputs = "<input type='hidden' name='ship_to' value='1' />";

            $third_party_row = "<input type='hidden' name='third_party' value='{$order->getVar('third_party')}' />";

            $ob_label = "Ordered By";
            $ordered_by = "
			<tr>
		    	<th class=\"form\" style='text-align:left;'>Ordered By<span class='required'>*</span>:</th>
				<td class=\"form\" colspan='3' align='left'>
					<input type=\"text\" name=\"ordered_by\" value=\"{$order->getVar('ordered_by')}\" size=\"15\" maxlength=\"64\">
				</td>
			</tr>";
        }

        # Show ACode input for swap orders
        #
        $code_input = "<input type='hidden' name='code' value='{$order->getVar('code')}'/>";
        $this->loadType();

        if (!$user->web_user)
        {
            if ($this->type_ary['out_asset'] && $this->type_ary['in_return'])
            {
                $code_input = "
			<tr>
				<th class='form' nowrap>Code:</th>
				<td class='form' colspan='3'>
					<select name='code'>
						<option value=''>-- Select Code --</option>";
                $code_input .= ComplaintForm::createCodeList($order->getVar('code'));
                $code_input .= "
					</select>
				</td>
			</tr>";
            }
        }

        # Do not allow changes to order type in these cases
        if ($this->limit_user || $this->order_id)
        {
            $type_select = "<input type='hidden' name='type_id' value='{$this->order_type}'/>
			{$this->type_ary['description']}";
        }
        else
        {
            # Make sure this current order type is allowed
            if (!in_array($this->order_type, $this->allowed_types))
                $this->order_type = 0;

            # Allow customer orders for In Inc customer
            if ($this->order_type == 0 && is_numeric($this->customer->getCustId()))
            {
                $this->order_type = Order::$CUSTOMER_ORDER;
                $this->allowed_types[] = Order::$CUSTOMER_ORDER;
            }

            # Prevent errors
            if (empty ($this->allowed_types))
                $this->allowed_types[] = Order::$CUSTOMER_ORDER;

            # Reload
            $this->loadType();

            # Dont change order type if this is an existing order
            # Show allowed order types for new orders
            $sth = $this->dbh->query("SELECT
				type_id, description
			FROM order_type
			WHERE type_id IN (" . implode(',', $this->allowed_types) . ")
			ORDER BY display_order");
            if ($sth)
            {
                $type_options = "";
                while (list($type_id, $desc) = $sth->fetch(PDO::FETCH_NUM))
                {
                    $sel = ($this->order_type == $type_id) ? "selected" : "";
                    $type_options .= "<option value='{$type_id}' {$sel}>{$desc}</option>\n";
                }
            }

            $type_select = "<select name='type_id' onchange='this.form.submit();'>
				$type_options
			</select>";
        }

        # Calculate the next shipping day. We need this to calculate the arrival day.
        #
        $limit = strtotime('today 00:00:00');
        $current_tstamp = time();
        $ltime = localtime();

        $next_shipping_day = ($ltime[2] >= 13) ? strtotime('tomorrow 00:00:00') : strtotime('today 00:00:00');
        $cal = new Calendar($user);
        while ($cal->isWeekend($next_shipping_day) || $cal->isHoliday($next_shipping_day))
        {
            $next_shipping_day = strtotime('+ 1 day', $next_shipping_day);
        }

        $arrival_day = date($date_format, $limit);

        # Now that we know the next possible shipping day, we can calculate the
        # earliest arrival day. We need this to validate the shipping form.
        #
        $limit = $next_shipping_day;
        if ($this->limit_user)
        {
            $day_count = 0;
            while ($day_count < 3)
            {
                if (!$cal->isWeekend($limit) && !$cal->isHoliday($limit))
                    $day_count++;

                $limit = strtotime('+ 1 day', $limit);
            }
        }
        $arrival_day = date($date_format, $limit);

        # City may have special chars
        $city = addslashes($user->getCity());

        $has_issue = (int) $order->getVar('issue_id');

        $country_states_json = self::GetCountryStateJSON();

        return <<<END
<script type="text/javascript">
var user_name = "{$user->getName()}";
var user_address = "{$user->getAddress()}";
var user_address2 = "{$user->getAddress2()}";
var user_city = "{$city}";
var user_zip = "{$user->getZip()}";
var user_state = "{$user->getState()}";
var user_country = "US";
var limit = {$limit};
var arrival_day = "{$arrival_day}";
var fac_name = "{$order->getVar('facility_name')}";
var fac_attention = "ATTN: Rehab Dept.";
var fac_address = "{$this->customer->getAddress()}";
var fac_address2 = "{$this->customer->getAddress2()}";
var fac_city = "{$this->customer->getCity()}";
var fac_zip = "{$this->customer->getZip()}";
var fac_state = "{$this->customer->getState()}";
var fac_country = "{$this->customer->getCountry()->getAbbr()}";
var fac_phone = "{$this->customer->getPhone()}";
var ob_label = "$ob_label";
var country_states = {$country_states_json};

/**
 * Opens the date selection calendar.
 *
 * @param {String} inputElemId id of the text field
 */
function ShowCalendar(inputElemId)
{
	// Find the date input and its calandar object
	var date_inp = document.getElementById(inputElemId);
	var cal = date_inp.cal;
	var ifFormat = "%Y-%m-%d";

	if (typeof CALENDAR_FORMAT != "undefined")
		ifFormat = CALENDAR_FORMAT;
	else if (typeof CAL_FORMAT != "undefined")
		ifFormat = CAL_FORMAT;

	// Create a new cal object when first clicked
	if (!cal)
	{
		var onClose = function(cal_obj) { cal_obj.hide(); };

		var onSelect = function(cal_obj, new_date)
		{
			date_inp.value = new_date;

			if (cal_obj.dateClicked)
				cal_obj.callCloseHandler();
		};

		cal = new Calendar(0, date_inp.value, onSelect, onClose);
		cal.weekNumbers = false;
		cal.setRange(1900, 2999);
		cal.setDateFormat(ifFormat);
		cal.create();
		date_inp.cal = cal;
	}

	// Hide other dialogs
	//if (typeof equip_dialog != 'undefined') equip_dialog.hide();
	//if (typeof supply_dialog != 'undefined') supply_dialog.hide();

	// Show the Calandar widget
	cal.showAtElement(date_inp, "Br");
}
</script>
<div id='resp_msg' class="error"></div>
	<input type="hidden" name="load_tstamp" value="{$current_tstamp}"/>
	<input type="hidden" name="tax_amount" value="{$tax_amount}"/>
	<input type="hidden" name="shipping_cost" value="{$shipping_cost}"/>
	<input type="hidden" name="urgency" value="1"/>
	<table cellspacing='20' cellpadding="0">
		<tr valign='top'>
			<td>
				<table width='100%' cellpadding="5" cellspacing="2" class="form" style='margin:0;'>
					<tr>
						<th class="subheader" colspan="4">Order Details</th>
					</tr>
					<tr>
						<th class="form">Order Date:</th>
						<td class="form">{$order_date}</td>
						<th class="form">Type:</th>
						<td class="form">
							{$type_select}
						</td>
					</tr>
					<tr>
						<th class="form">Delivery Date:</th>
						<td class="form" colspan='3'>
							<input type="text" id="inst_date" name="inst_date" value="{$inst_date}" size="10" maxlength="32" />
							<img class="btn btn-default btn-xs" id="inst_date_trg" src="images/calendar-mini.png" alt="Calendar" title="Calendar" onclick="ShowCalendar('inst_date');" />
						</td>
					</tr>
					<tr>
						<th class="form">PO #:</th>
						<td class="form"><input type="text" name="po_number" value="{$order->getVar('po_number')}" size="10" maxlength="32" /></td>
						<th class="form">SO #:</th>
						<td class="form">NA</td>
					</tr>
					{$po_output}
					{$ordered_by}
					{$bill_to_inputs}
					{$code_input}
					<tr>
						<th class='subheader' colspan='4'>Comments:</th>
					</tr>
					<tr>
						<td colspan='4'>
							<textarea name="comments" rows="4" cols="60">{$order->getVar('comments')}</textarea>
						</td>
					</tr>
				</table>
				<br/>
				<table width='100%' class="form" cellpadding="5" cellspacing="2" style="margin:0;">
					<tr>
						<th class='subheader' colspan='4'>Shipping Information</th>
					</td>
					{$third_party_row}
					<tr>
						<th class="form" nowrap>Ship Via</th>
						<td class="form" colspan='3' align="left" id='ship_method_cell'>
							$ship_method_control
						</td>
					</tr>
					$service_inputs
					<tr>
						<td class="form_help" colspan="4">
							** Orders placed before 2:00PM (PST) will generally ship Ground Service same day
						</td>
					</tr>
					<tr>
						<th class='subheader' colspan='4'>Shipping Address</th>
					</tr>
					$ship_to_inputs
					$address_inputs
				</table>
			</td>
			<td>
				<table cellpadding="5" class="view" style='margin:0;'>
					<tr><th class="subheader" colspan="2">Items</th></tr>
					{$item_html}
					<td class='view' colspan='2' style='text-align:right;line-height:25px;'>
						Sub Total: <span id='sub_total_elem'>{$sub_total}</span><br/>
						Estimated Shipping: <span id='shipping_elem'>{$shipping}</span><br/>
						<u>Estimated Tax: <span id='tax_elem'>{$tax}</span></u><br/>
						<b>Estimated Total: <span id='total_elem' style='border: 1px solid grey;background-color:#D3DCE3;'>{$total}</span></b>
					</td>
					<tr>
						<td class='buttons' colspan='2'>
							<input class='submit' type='submit' name='submit_btn' value='Change Quantity' onclick="this.form.section.value='Cart';"/>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td class="form_help" colspan="2">
				<span class='required'>*</span> Denotes a <span class="required">required</span> field
			</td>
		</tr>
		<tr>
			<td colspan="2" style='text-align:center;'>
				<input class="submit" type="submit" name="act_btn" value="Submit Order" $disabled
					onClick="if (validateForm(this.form)) { this.disabled=true; this.form.act.value='process'; this.form.submit(); } return false;"/>
			</td>
		</tr>
	</table>
<script type="text/javascript">
// Perform page initialization
//
has_issue = $has_issue;
function InitPage()
{
	$init_ac

	GetTNT();
}
YAHOO.util.Event.onDOMReady(InitPage);
</script>
END;
    }

    /**
     *
     * @param array $form
     */
    private function ShowClearCart($form)
    {
        global $user;

        $this->dbh->query("DELETE FROM cart WHERE user_id = {$user->getId()} AND user_type = '{$this->user_type}'");
        $this->dbh->query("DELETE FROM cart_item WHERE user_id = {$user->getId()} AND user_type = '{$this->user_type}'");

        $this->ShowCart($form);
    }

    /**
     * Display html for Home Page Summary
     * Assumes facility_id and customer are set
     *
     * param array $form
     *
     * return string (html)
     */
    private function ShowCustomerInfo()
    {
        global $user;

        # Set customer fields
        $customer_name = $this->customer->getName();
        $cust_id = $this->customer->getCustId();

        # Add checkout link if cart is populated
        $postpone = $check_out = "";
        if ($this->cart_count)
        {
            $check_out = "<span class='order_lnk'>
			<a href='{$_SERVER['PHP_SELF']}?section=Checkout' alt='Proceed to Checkout' title='Proceed to Checkout'>checkout</a></span>";

            # Add Postpone/Hold link
            if (!$this->limit_user)
                $postpone = "<span class='order_lnk'>
				<a href='{$_SERVER['PHP_SELF']}?section=Home&act=hold' alt='Hold cart until later' title='Hold cart until later'>postpone</a></span>";
        }

        # Show Order ID if it has been set
        $order_info = "";
        if ($this->order_id)
            $order_info = "<br/>Editing Order: <span class='norm_txt' style='font-size: 10pt;'>{$this->order_id}</span>";

        # If the user is not a cpm or web user and the cart is emtpy
        # Allow user to change to a different facility
        $edit_customer = "<span class='order_lnk'>
		<a href='{$_SERVER['PHP_SELF']}?section=Home&act=change&facility_id={$this->facility_id}'
		alt='Change Site' title='Change Site'>change site</a></span>";

        # No HOLD option for CPMs and web_users
        if ($this->limit_user)
            $edit_customer = "";

        # Show additional Facility/Dealer info for Customer Service
        $cust_info = "";
        $corp_office = "";

        # Show additional Facility Derogatory status
        $set_cancelled_note = '';
        if ($this->customer->isCancelled())
            $set_cancelled_note = "<span class='label label-danger'>CANCELLED</span>";
        else if (!$this->customer->isActive())
            $set_cancelled_note = "<span class='label label-danger'>INACTIVE</span>";

        if (!$this->limit_user &&
            ($this->customer->getEntityType() == CustomerEntity::$ENTITY_FACILITY ||
                $this->customer->getEntityType() == CustomerEntity::$ENTITY_DEALER)
        )
        {
            $corp_office = " ( {$this->customer->getCorporateParent()} )";

            $cust_info = "<br/>Payment Terms: <span class='norm_txt'>{$this->customer->GetPaymentTerms(true)}</span>";

            if ($this->customer->GetPORequired())
                $cust_info .= "PO required: <span class='norm_txt'>Yes</span>";

            # Include login button for allowed users
            if ($user->hasAccessToApplication('reset_xcart_pwd'))
            {
                $edit_customer = "<span class='order_lnk'> <a href='{$_SERVER['PHP_SELF']}?act=set_fid&switch=1&facility_id={$this->facility_id}'
				alt='Login as Customer' title='Login as Customer'>
				<img src='images/login.png' height=12 /></a></span> $edit_customer";
            }
        }

        # Limit facilities which are marked to use DSSI
        #
        $sth = $this->dbh->query("SELECT t.dssi_code
		FROM corporate_parent_translation t
		INNER JOIN facilities f ON t.corporate_parent = f.corporate_parent
		INNER JOIN facility_code_translation c ON f.accounting_id = c.cust_id
		WHERE f.id = {$this->facility_id}");
        list($dssi_required) = $sth->fetch(PDO::FETCH_NUM);
        if ($dssi_required)
        {
            $this->msg .= "<p id='error_msg' class='error_msg'>As of April 1st, 2009, this facility must order supplies from DSSI.</p>";
            $this->disabled = true;
        }

        return "
		<div style='text-align:right; padding: 0 5px; margin: 0 50px;'>
			$edit_customer
			$check_out
			$postpone
		</div>
		<div align=left class='banner'>
			Welcome: <span class='norm_txt'>{$customer_name}{$corp_office}</span>
			Customer ID #: <span class='norm_txt'>{$cust_id}</span> {$set_cancelled_note}
			<font size=-1>
			$cust_info
			$order_info
			</font>
			{$this->ShowEmployeeSelect()}
		</div>";
    }

    /**
     * Display html for Device Supply editing
     *
     * @param array $form
     */
    private function ShowDeviceSupplies($form)
    {
        global $user;

        $dev_id = (int) $this->dev_id;

        $devices = "";
        $sth = $this->dbh->query('SELECT
			m.id, m.model, m.description,
			(	SELECT count(*)
				FROM equipment_supply s
				WHERE s.model_id = m.id
			) as prod_count
		FROM equipment_models m
		WHERE m.active=true
		AND m.base_assets IS NULL
		AND m.model IN (SELECT code FROM service_item_to_product)
		ORDER BY m.display_order');
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $h = ($row['id'] == $dev_id) ? "_h" : "";

            if ($row['id'] == $dev_id)
            {
                $model = $row['model'];
                $description = $row['description'];
            }

            $devices .= "<tr>
				<td class='view$h' style='white-space:nowrap;'>
					<a href=\"{$_SERVER['PHP_SELF']}?section=Admin&tab=Devices&dev_id={$row['id']}\">
						{$row['model']}
					</a>
				</td>
				<td class='view$h' style='white-space:nowrap;'>
					{$row['description']}
				</td>
				<td class='view$h'>
					{$row['prod_count']}
				</td>
			</tr>";
        }
        $search = (isset ($_REQUEST['search'])) ? addslashes(trim($_REQUEST['search'])) : '';
        $ht_search = htmlentities($search, ENT_QUOTES);
        $qu_search = addslashes($search);

        $prod_count = 0;
        $contents = "";
        $supplies = "";
        $save_button = "";
        if ($search)
        {
            if ($dev_id)
                $save_button = "<tr>
							<td style='text-align:center;padding:5px;'>
								<input class='submit' type='submit' name='update_btn' value='Update' onclick=\"this.form.act.value='update';\"/>
							</td>
						</tr>";

            $available = "Search Results For \"{$ht_search}\"";

            if (preg_match('/^#/', $search))
            {
                $qu_search = strtoupper(substr($qu_search, 1));
                $filter = "AND upper(p.code) = {$this->dbh->quote($qu_search)}";
            }
            else
            {
                $filter = "AND (
				similarity(p.name, " . $this->dbh->quote($qu_search) . ") + similarity(p.description, " . $this->dbh->quote($qu_search) . ") > 0.4
				OR position(lower(" . $this->dbh->quote($qu_search) . ") in lower(p.name)) != 0
				OR position(lower(" . $this->dbh->quote($qu_search) . ") in lower(p.description)) != 0
				OR p.name ~* '( |^)($qu_search)( |\$)'
				OR p.description ~* '( |^)($qu_search)( |\$)'
		        OR p.code = " . $this->dbh->quote($qu_search) . " )";
            }

            # Define Query specifics
            $sql = "SELECT
				p.id AS id,
				p.name AS name,
				p.code AS code,
				p.description AS description,
				lower(p.code) AS pic,
				p.track_inventory,
				CASE WHEN es.prod_id IS NOT NULL THEN 1 ELSE 0 END as in_dev
			FROM (
				products p
				LEFT JOIN product_detail d on p.id = d.prod_id
			) p
			LEFT JOIN equipment_supply es ON p.id = es.prod_id AND es.model_id = $dev_id
			WHERE p.active = true
			$filter
			ORDER BY in_dev DESC, p.name";
        }
        else if ($dev_id)
        {
            $save_button = "<tr>
							<td style='text-align:center;padding:5px;'>
								<input class='submit' type='submit' name='update_btn' value='Update' onclick=\"this.form.act.value='update';\"/>
							</td>
						</tr>";

            $available = "Available Supplies";

            # Define Query specifics
            $sql = "SELECT
				p.id AS id,
				p.name AS name,
				p.code AS code,
				p.description AS description,
				lower(p.code) AS pic,
				p.track_inventory,
				CASE WHEN es.prod_id IS NOT NULL THEN 1 ELSE 0 END as in_dev
			FROM (
				products p
				LEFT JOIN product_detail d on p.id = d.prod_id
			) p
			LEFT JOIN equipment_supply es ON p.id = es.prod_id AND es.model_id = $dev_id
			WHERE p.active = true
			ORDER BY in_dev DESC, p.name";
        }
        else
        {
            $available = "Available Supplies";
            $sql = "SELECT 0 WHERE false";
        }

        $page = (isset ($_REQUEST['page'])) ? (int) $_REQUEST['page'] : 1;
        if ($page < 1)
            $page = 1;
        $per_page = $this->per_page;
        $offset = ($page - 1) * $per_page;

        $sth = $this->dbh->query($sql);
        $total = $sth->rowCount();
        $row_class = 'on';
        $prod_count = 0;
        $count = 0;
        $cur_page = 1;
        $rolling_count = 1;
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            # Is member of this category
            #
            $chk = ($row['in_dev']) ? "checked" : "";

            # Track the actual number of supplies for this device
            if ($chk)
                $prod_count++;

            # Display details for the requested page only.
            # Pass others as a hidden input.
            #
            if ($cur_page == $page)
            {
                $pic = $this->ProductImg($row['id'], $row['pic'], 'Admin&tab=Devices', null, 30);
                $link = $this->ProductLink($row['id'], "<b><u>{$row['name']}</u></b>", 'Admin&tab=Devices');

                $inv = ($row['track_inventory']) ? "Yes" : "No";

                $dis = ($dev_id) ? "" : "disabled";

                $supplies .= "
					<tr onmouseover='this.className=\"focus\";' onmouseout='this.className=\"\";'>
						<td class='prod' style=\"text-align:center\">
							$pic
							<br>
							#{$row['code']}
						</td>
						<td class='prod' style='text-align:left'>
							$link
							<b>{$row['description']}</b><br/>
							Track Inventory: {$inv}<br/>
						</td>
						<td class='prod' style=\"text-align:center\">
							<input type='hidden' name='prod_ids[{$row['id']}]' value='{$row['id']}'/>
							<input type='checkbox' name='chk_prod_ids[{$row['id']}]' value='{$row['id']}' $chk $dis />
						</td>
					</tr>";

                $row_class = ($row_class == 'on') ? 'off' : 'on';
                $count++;
            }

            if ($rolling_count == $per_page)
            {
                # Reset the rolling count and increment the current page
                $rolling_count = 1;
                $cur_page++;
            }
            else
            {
                # Increment the rolling count of the page
                $rolling_count++;
            }
        }

        $args = null;
        if ($search)
            $args['search'] = $ht_search;
        $page_row = $this->GetPageBar($total, $count, $page, $per_page, $args);

        $contents = "
			<table width='600' cellpadding='5' cellspacing='0'>
				<tr>
					<td>
						<div style='font-size:18pt;font-weight:bold;'>{$available}</div>
					</td>
				</tr>
				<tr>
					<td>
						<div style='background-color:#cccccc;padding:5px;'>
							Showing $count of $total Products ($prod_count Selected)
						</div>
					</td>
				</tr>
				{$save_button}
				<tr>
					<td>
						<table cellpadding='5' cellspacing='0' class='list' style='margin:0;'>
							{$page_row}
							<tr>
								<th class='list'>Product</th>
								<th class='list'>Description</th>
								<th class='list'>Included</th>
							</tr>
							{$supplies}
							{$page_row}
						</table>
					</td>
				</tr>
				{$save_button}
			</table>";


        return <<<END
		<input type='hidden' name='tab' value='Devices'/>
		<input type='hidden' name='dev_id' value='{$dev_id}'/>
		<table>
			<tr valign='top'>
				<td style='padding-left: 20px;'>
					<table class='view' width='200' cellpadding='2' cellspacing='0' style='margin:0; padding: 2px;'>
						<tr>
							<th class='subheader' colspan='3'>Devices:</th>
						</tr>
						<tr>
							<th class='list' style='font-size:small;text-align:left;'>Model</th>
							<th class='list' style='font-size:small;text-align:left;'>Name</th>
							<th class='list' style='font-size:small;text-align:left;'>#&nbsp;Supplies</th>
						</tr>
						{$devices}
					</table>
				</td>
				<td align='center' style='padding-left: 50px;'>
					{$contents}
				</td>
			</tr>
		</table>
END;
    }

    /**
     * Display html for Employe Facility Selection
     */
    public function ShowEmployeeSelect()
    {
        ## Show employee records
        $employee_select = "";
        if (is_array($this->site_options))
        {
            $employee_select = "<br/>Account Options:
			<div class='ad_wrapper' style='font-size: 9pt; font-weight: normal;'>";
            $options = is_array($this->site_options) ? $this->site_options : array($this->site_options);
            foreach ($options as $id)
            {
                $fac = new Facility($id);
                $cust_id = $fac->getAccountingId();
                $CO = $this->CompanyName($cust_id);
                $employee_select .= "<span class='order_lnk'>
					<a href='{$_SERVER['PHP_SELF']}?act=set_fid&facility_id=$id' alt='Change to this Account' title='Change to this Account'>$CO: $cust_id</a>
				</span>&nbsp;";
            }

            $employee_select .= "</div></div>";
        }

        return $employee_select;
    }

    /**
     * Display html for Facility Select
     */
    private function ShowFacilitySelect($form)
    {
        $facility_id = ($this->facility_id) ? $this->facility_id : null;
        $ce = ($this->facility_id) ? new CustomerEntity($this->facility_id) : null;
        $facility_name = ($ce) ? htmlentities($ce->getName() . " (" . $ce->getCustID() . ")", ENT_QUOTES) : "";

        $po_output = ($ce && $ce->GetPORequired()) ? "PO Number Required" : "";

        $show_vendors = ($this->type_ary['is_purchase']) ? "&amp;sv=1" : "";

        $this->contents = <<<END
		<input type="hidden" name="order_type" value="{$this->order_type}"/>
		<input type="hidden" name="act" value='set_fid'/>
		<table class="form" border="0" cellspacing="2" cellpadding="4" >
			<tr>
				<th class="subheader" colspan="2">Facility</th>
			</tr>
			<tr>
				<th class="form">Set Facility</th>
				<td class="form">
					<div id='fac_container' style='position:relative;'>
						<input type='text' id='fac_name' name='fac_name' value='{$facility_name}' size='35' style='width:25em; position:static; overflow: visible;' />
						<img class="form_bttn" src="images/cancel.png" onClick="clearFacilitySelect(document.wsorder);" alt="Clear Facility" title="Clear Facility">
						<div id='acc_facility' style='font-size:x-small; position:static;'></div>
					</div>
				</td>
			</tr>
			<tr class='warning'>
			 	<td class='form' name="po_warning" id='po_warning' colspan="2" align='center'>{$po_output}</td>
			</tr>
			<tr>
				<td class="buttons" colspan="2">
					<input class="submit" type="submit" name="submit_btn" value="Submit" />
				</td>
			</tr>
		</table>
		<script type='text/javascript'> InitAutoComplete(1); </script>
END;
    }

    /**
     * Display stattic Help text
     *
     * @param array
     *
     */
    private function ShowHelp($form)
    {
        $act = (isset ($form['act'])) ? $form['act'] : '';

        if ($act)
        {
            # Set contents
            #
            $contents = "";
            if ($act == 'contact')
            {
                include ('ws_contact_us.php');
                $this->contents = $contents;
            }
            else if ($act == 'terms')
            {
                include ('ws_terms_conditions.php');
                $this->contents = $contents;
            }
            else if ($act == 'privacy')
            {
                include ('ws_privacy.php');
                $this->contents = $contents;
            }
            else if ($act == 'msds')
            {
                include ('ws_msds.php');
                $this->contents = $contents;
            }
            else
            {
                $this->ShowCatalog($form);
            }
        }
        else
        {
            $this->ShowCatalog($form);
        }
    }

    /**
     * Return help html table
     */
    private function ShowHelpTable()
    {
        return <<<END
		<table cellspacing='0' cellpadding='4' class='view' style='font-size:10pt;padding:2px;margin:15px 0;white-spcace:nowrap;'>
			<tr>
				<th class='subheader'>Help</th>
			</tr>
			<tr>
				<td class='indent'>
					<a href="https://info.acplus.com/contact-us" target="_blank">Contact Us</a>
				</td>
			</tr>
			<tr>
				<td class='indent'>
					<a href='{$_SERVER['PHP_SELF']}?section=Help&act=terms'>Terms & Conditions</a>
				</td>
			</tr>
			<tr>
				<td class='indent'>
					<a href='{$_SERVER['PHP_SELF']}?section=Help&act=privacy'>Privacy Statement</a>
				</td>
			</tr>
			<tr>
				<td class='indent'>
					<a href="https://acplus.com/msds" target="_blank">MSDS Sheets</a>
				</td>
			</tr>
		</table>
END;
    }

    /**
     * Prints a list of previous orders.
     *
     * @param array form
     *
     * @return string
     */
    protected function ShowHistory($form)
    {
        global $user;

        if ($this->facility_id)
        {
            $act = (isset ($form['act'])) ? strtolower($form['act']) : '';
            $order_id = (isset ($form['order_id'])) ? strtolower($form['order_id']) : 0;

            if ($order_id)
            {
                $this->left = "&nbsp;";
                $this->right = "&nbsp;";
                $order = new Order($order_id);

                if ($act == 'view')
                    $this->contents = $this->ShowOrder($order);
                else
                    $this->contents = $this->PrintOrder($order);
            }
            else
            {
                $this->left = $this->ShowLastOrder();
                $this->left .= $this->ShowHelpTable();
                $this->contents = $this->ShowOrderHistory($form);
                $this->right = "&nbsp;";
            }
        }
        else
        {
            if ($user->web_user)
                $this->ShowWelcome($form);
            else
                $this->ShowFacilitySelect($form);
        }
    }

    /**
     * Display html for Home Page
     *
     * param array $form
     *
     * return string (html)
     */
    private function ShowHome($form)
    {
        global $user;

        $act = (isset ($form['act'])) ? $form['act'] : '';

        # Request to change facility
        if ($act == 'change')
        {
            # Must not be CPM or web user and the cart must be empty
            if ($user->web_user)
            {
                $this->msg .= "<p class='error_msg'>This action is prohibited!</p>";
            }
            else
            {
                $this->ShowFacilitySelect($form);
                return;
            }

        }

        # When Customer is known show the account information
        # For CS Rep show the facility select input
        if ($this->facility_id)
        {
            $this->right = $this->ShowCartSummary();
            $this->ShowAccountInfo($form);
        }
        else
        {
            # For Unknkown Web users show Welcome text
            # For CS Rep show the facility select input
            if ($user->web_user)
                $this->ShowWelcome($form);
            else
            {
                $this->ShowFacilitySelect($form);
            }
        }
    }

    /**
     * Display Product Image interface
     *
     * @param array
     *
     * @return string
     */
    private function ShowImageEdit($args)
    {
        $prod_id = (isset ($args['prod_id'])) ? $args['prod_id'] : 0;

        $images = $this->GetProductImages($args);

        # Load some details
        $sth = $this->dbh->query("SELECT code, name, description
		FROM products WHERE id = $prod_id");
        list($prod_code, $prod_name, $prod_description) = $sth->fetch(PDO::FETCH_NUM);

        $media_ext = array();
        $d = dir(Config::$MEDIA_FILE_PATH_FULL . "/product_image/") or die ($php_errormsg);
        while (false !== ($f = $d->read()))
        {
            if (preg_match('/^[a-zA-Z_\d]+.(png)|(jpg)$/', $f))
            {
                $file_parts = pathinfo($f);
                $media_ext[$file_parts['filename']] = $file_parts['extension'];
            }
        }
        $d->close();
        //print_r($media_ext);
        # Set the path of the image file
        #
        $img_dir = Config::$MEDIA_FILE_PATH . "/product_image/";

        $image_bar = "";
        $image_ary = "";
        if (is_array($images))
        {
            $i = 0;
            foreach ($images as $img)
            {
                $src = "{$img_dir}default.png";

                $file_parts = pathinfo($img['name']);
                if (!isset ($file_parts['extension']))
                {
                    if (array_key_exists($img['name'], $media_ext))
                    {

                        $ext = $media_ext[$img['name']];
                        $src = "{$img_dir}{$img['name']}.{$ext}";
                    }
                }
                else
                {
                    $src = "{$img_dir}{$img['name']}";
                }

                $image_bar .= " <img src='{$src}' height='30' width='30'
					alt='{$img['description']}' title='{$img['description']}'
					onClick='LoadImgForm(this, {$img['id']});'/>";
                $image_ary .= "prod_img_ary[$i] = { id: {$img['id']}, name: '{$img['name']}', description: '{$img['description']}'};\n";
                $i++;
            }
            $help = "(Click on image to update the file)";
            $clear = "<input class='submit' type='button' id='img_clear' name='clear' value='Clear' disabled onClick=\"LoadImgForm(this, 0);\"/>";
        }
        else
        {
            $image_bar = "";
            $clear = "";
            $help = "(No picture details found. Please upload/re-upload file.)";
        }

        $pic = $this->ProductImg($prod_id, $prod_code, null, null, null);
        $link = $this->ProductLink($prod_id, 'Back to Product', 'Admin&tab=Categories');
        $image_table = "
		<script type='text/javascript'>

		// Set the enctype for the form;
		document.wsorder.enctype = \"multipart/form-data\";

		// Build product image array
		var prod_img_ary = new Array();
		$image_ary

		</script>
		<input type='hidden' id='img_id' name='img_id' value=''/>
		<input type='hidden' id='img_name' name='img_name' value=''/>
		<input type='hidden' name='prod_id' value='$prod_id'/>
		<input type='hidden' name='tab' value='CategoriesImage'/>
		<table align='center' width='750' cellpadding='15' cellspacing='0' style='background-color:#FBFBFB; border:1px solid #DBDBDB;font-size:small;'>
			<tr height='350'>
				<td width='50%' class='prod' align='center'>
					$pic
					<br/>
					$link
				</td>
				<td width='50%' class='prod' align='left'>
					<b><u>{$prod_name}</u></b>
					<br/>
					Product Code: #{$prod_code}
					<br/>
					<b>{$prod_description}</b>
				</td>
			</tr>
			<tr>
				<td class='prod' colspan=2>
					<table width='100%' cellpadding='10' cellspacing='0' style='font-size:small;'>
						<tr>
							<th align='right'>Saved Images:</th>
							<td align='left'>
								<div id='ibar' class='ibar'>
									$image_bar
								</div>
								<div class='disclaimer'>
									$help
								</div>
							</td>
						</tr>
						<tr>
							<th align='right'>File:</th>
							<td align='left'>
								<input type='file' name='img_file' size='30'/>
							</td>
						</tr>
						<tr>
							<th align='right'>Description:</th>
							<td align='left'>
								<input type='text' id='img_description' name='img_description' size='30' maxlength='64'/>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr id='img_save'>
				<td align='center' colspan=2>
				 	{$clear}
					<input class='submit' type='submit' name='submit_btn' value='Save' onClick=\"this.form.act.value='save';\"/>
					<input class='submit' type='submit' id='img_del' name='submit_btn' value='Delete' disabled
						onClick=\"
						if (confirm('Are you sure you wish to delete this image file?'))
							this.form.act.value='delete';
						else
							return false;
						return true;\"/>
				</td>
			</tr>
		</table>";

        return $image_table;
    }

    /**
     * Produce common item list for the array of items
     * and set sub total
     *
     * @param object
     * @param string
     * @param float
     */
    protected function ShowItemRows($obj, &$item_html, &$sub_total)
    {
        global $user;

        $order_type = $this->order_type;
        $bpi = $this->bpi;
        $cpgk = $this->cpgk;
        if (get_class($obj) == 'Cart')
        {
            $cart = $obj;
            $item_ary = $obj->items;
        }
        else
        {
            $cart = null;
            $item_ary = $obj->GetItems();

            $order_type = $obj->getVar('type_id');
            $bpi = $obj->getVar('base_price_index');
            $cpgk = $obj->getVar('cust_price_group_key');
        }

        # Set up Item rows
        $row_class = 'on';
        foreach ($item_ary as $n => $item)
        {
            $pic = $this->ProductImg($item->getVar('prod_id'), $item->getVar('code'), 'Catalog', null, 30);
            $prod_text = "<b><u>{$item->getVar('name')}</u></b>";
            $link = $this->ProductLink($item->getVar('prod_id'), $prod_text, 'Catalog');
            $description = ($item->getVar('description')) ? "<b>{$item->getVar('description')}</b><br/>" : "";
            $shipped = ($item->getVar('shipped')) ? " <br/>Shipped: {$item->getVar('shipped')}" : "";

            if (is_null($item->getVar('price')) || $item->getVar('price') === "")
            {
                $item->SetPriceInfo($order_type, $bpi, $cpgk);
            }

            # Format for display
            $price = $item->getVar('price');
            if (is_numeric($price))
            {
                # Add amount to sub total
                $sub_total += $price;

                $price = "\$" . number_format($item->getVar('price'), 2);
            }

            $item_html .= "
			<tr class='$row_class'>
				<td class='prod' style='text-align:center'>
					$pic
					<br/>
					#{$item->getVar('code')}
				</td>
				<td class='prod'>
					$link
					<br/>
					{$description}
					Quantity: {$item->getVar('quantity')} {$item->getVar('uom')}{$shipped}<br/>
					Price: {$price}
				</td>
			</tr>";

            $row_class = ($row_class == 'on') ? 'off' : 'on';
        }

        return $item_html;
    }

    /**
     * Build html table of the contents of the previous customer order
     *
     * @return string
     */
    private function ShowLastOrder()
    {
        global $user;

        # Show packing list tootltip for users with orderfil permissions
        $orderfil = $user->hasAccessToApplication('orderfil');
        $order_id = 0;

        $last_order = array('order_id' => '--',
            'order_date' => '--',
            'tracking_num' => '',
            'ret_tracking_num' => '',
            'status' => 'NA',
            'type' => 'NA',
            'type_id' => '',
            'invoice_num' => 'NA',
            'item_rows' => "<tr><td colspan='2'>No items</td></tr>");

        # Show last order
        if ($this->facility_id)
        {
            # Limited types only for webuser
            $limit_order_types = "";
            if ($user->web_user)
                $limit_order_types = "AND o.type_id IN (" . implode(",", self::$LIMITED_TYPES) . ")";

            $sth = $this->dbh->query("SELECT
				o.id as order_id, to_timestamp(o.order_date)::Date as order_date,
				o.tracking_num, o.ret_tracking_num,
				os.name as status, o.type_id, ot.description as type,
				i.invoice_num
			FROM orders o
			INNER JOIN order_status os on o.status_id = os.id
			INNER JOIN order_type ot on o.type_id = ot.type_id
			LEFT JOIN invoice_current i ON o.id = i.order_id
			WHERE o.facility_id = {$this->facility_id} $limit_order_types
			ORDER BY o.order_date DESC
			LIMIT 1");

            if ($sth->rowCount())
            {
                $last_order = $sth->fetch(PDO::FETCH_ASSOC);
                $last_order['item_rows'] = "<tr><td colspan='2'>No items</td></tr>";

                $sth = $this->dbh->prepare("SELECT
					oi.name, oi.quantity, oi.uom
				FROM order_item oi
				INNER JOIN products p ON oi.prod_id = p.id
				WHERE oi.order_id = ?
				ORDER by oi.item_num");
                $sth->bindValue(1, $last_order['order_id'], PDO::PARAM_INT);
                $sth->execute();
                if ($sth->rowCount())
                {
                    $last_order['item_rows'] = "";
                    $rc = "on";
                    while (list($product, $qty, $uom) = $sth->fetch(PDO::FETCH_NUM))
                    {
                        $last_order['item_rows'] .= "
						<tr class='$rc'>
							<td class='prod' style='font-size:x-small;text-align:left;'>$product</td>
							<td class='prod' style='font-size:x-small;'>$qty $uom</td>
						</tr>";

                        $rc = ($rc == 'on') ? 'off' : 'on';
                    }
                }
                # Save original value
                $order_id = $last_order['order_id'];

                # Show packing list tootltip for users with orderfil permissions
                $preview = "";
                if ($orderfil)
                    $preview = "onmouseover=\"GetPackList(event,{$last_order['order_id']});\" onmouseout=\"HidePackList({$last_order['order_id']});\"";

                $last_order['order_id'] = "<a
					href='{$_SERVER['PHP_SELF']}?section=History&act=view&order_id={$last_order['order_id']}'
					alt='View Order Detail' title='View Order Detail'
					{$preview} >
					{$last_order['order_id']}
				</a>";
            }
        }

        # Format tracking number for linking to carrier tracking page
        $tracking = Order::FormatTrackingNo($last_order['tracking_num']);
        $tracking .= Order::FormatTrackingNo($last_order['ret_tracking_num']);

        # Add Duplicate Button for Supply, Customer, Web Orders
        # When the user has permissions
        $dup_bttn = "";
        if ($last_order)
        {
            $allowed = in_array($last_order['type_id'], $this->allowed_types);
            $dup_order = in_array($last_order['type'], array('Supply Order', 'Customer Order', 'Web Order'));
            if ($dup_order && ($user->web_user || $allowed) && $this->disabled == false)
                $dup_bttn .= "<img class=\"form_bttn\" src=\"images/orderfil.png\" height='24' onClick=\"window.location='{$_SERVER['PHP_SELF']}?act=duplicate&amp;order_id={$order_id}'\" alt=\"Duplicate this order\" title=\"Duplicate this order\">";
        }


        return <<<END
		<div class='banner' style='margin: 0;'>Last Order</div>

		<table cellspacing='0' cellpadding="4" class="view" style='width:100%; margin-top:2px;'>

			<tr>
				<td class='indent'>Order #:</td>
				<td style='padding:2px 5px;color:#666666;'>
					{$last_order['order_id']}
					{$dup_bttn}
				</td>
			</tr>
			<tr>
				<td class='indent'>Order Date:</td>
				<td style='padding:2px 5px;color:#666666;'>{$last_order['order_date']}</td>
			</tr>
			<tr>
				<td class='indent'>Type:</td>
				<td style='padding:2px 5px;color:#666666;'>{$last_order['type']}</td>
			</tr>
			<tr>
				<td class='indent'>Status:</td>
				<td style='padding:2px 5px;color:#666666;'>{$last_order['status']}</td>
			</tr>
			<tr>
				<td class='indent'>Tracking Info:</td>
				<td style='padding:2px 5px;color:#666666;'>{$tracking}</td>
			</tr>
			<tr>
				<td class='indent'>Invoice #:</td>
				<td style='padding:2px 5px;color:#666666;'>{$last_order['invoice_num']}</td>
			</tr>
			<tr>
				<td colspan='2' style='padding:5px;'>
					<table width='100%' align='left' cellspacing='0' cellpadding="2" class="list" style='border:0;margin:0;'>
						<tr>
							<th class='list' colspan='2'>Items</th>
						</tr>
						{$last_order['item_rows']}
					</table>
				</td>
			</tr>
		</table>
END;
    }

    /**
     * Display html for New Orders
     *
     * @param array $form
     */
    private function ShowNew($form)
    {
        global $user;

        if ($this->facility_id)
        {
            $this->left = $this->ShowHelpTable();
            $this->right = "<div style='width:100px;'>&nbsp;</div>";
            $this->contents = $this->ShowProductHistory($form);
        }
        else
        {
            if ($user->web_user)
                $this->showWelcome($form);
            else
                $this->ShowFacilitySelect($form);
        }
    }

    /**
     * Build menu bar
     *
     * @return string
     */
    private function ShowOptions()
    {
        global $user, $this_app_admin_name;

        $options = "";
        if ($this->facility_id || $this->limit_user == false)
        {
            $Home = ($this->section == 'Home' || $this->section == 'Help') ? "tab_active" : "";
            $Catalog = ($this->section == 'Catalog') ? "tab_active" : "";
            $New = ($this->section == 'New') ? "tab_active" : "";
            $History = ($this->section == 'History') ? "tab_active" : "";
            $Cart = ($this->section == 'Cart' || $this->section == 'Checkout') ? "tab_active" : "";
            $Admin = ($this->section == 'Admin') ? "tab_active" : "";
            $s_section = ($Admin) ? "Admin" : "Catalog";

            $search = (isset ($_REQUEST['search'])) ? trim($_REQUEST['search']) : '';
            $ht_search = htmlentities($search, ENT_QUOTES);
            $qu_search = addslashes($search);

            $cp = 5;
            $admin_tab = "";
            if ($user->hasAccessToApplication($this_app_admin_name))
            {
                $cp = 6;
                $admin_tab = "<li class='{$Admin}'>
					<a href='{$_SERVER['PHP_SELF']}?section=Admin' alt='Access Admin Features' title='Access Admin Features'>
						<em>Admin</em>
					</a>
				</li>";
            }

            $cc = ($this->cart_count) ? " ({$this->cart_count} items)" : "";

            $options = <<<END
			{$this->ShowBannerAd(self::$WIDE_BANNER)}
		<div class='search'>
			Search
			<input type='text' name='search' value='{$ht_search}' size='25' onkeydown="return ForceSearch(event,'$s_section');"/>
			<img class="form_bttn" src="images/search-mini.png"
			onClick="
			document.wsorder.act.value='search';
			document.wsorder.section.value='{$s_section}';
			document.wsorder.submit();"
			alt='Search Product Catalog' title='Search Product Catalog'/>
		</div>
		<div class='tabing text-center'>
		<ul>
			<li class='{$Catalog}'>
				<a class='left' href='{$_SERVER['PHP_SELF']}?section=Catalog' alt='View Product Catalog' title='View Product Catalog'>
					<em>Catalog</em>
				</a>
			</li>
			{$admin_tab}
			<li	class='{$Home}'>
				<a href='{$_SERVER['PHP_SELF']}?section=Home' alt='View Account Summary' title='View Account Summary'>
					<em>My Account</em>
				</a>
			</li>
			<li class='{$New}'>
				<a href='{$_SERVER['PHP_SELF']}?section=New' alt='Place an Order' title='Place an Order'>
					<em>Previously Ordered</em>
				</a>
			</li>
			<li class='{$History}'>
				<a href='{$_SERVER['PHP_SELF']}?section=History' alt='View Past Orders' title='View Past Orders'>
					<em>Order History</em>
				</a>
			</li>
			<li class='{$Cart}'>
				<a class='right' href='{$_SERVER['PHP_SELF']}?section=Cart' alt='View/Edit Shopping Cart' title='View/Edit Shopping Cart'>
					<em>Shopping Cart$cc</em>
				</a>
			</li>
		</ul>
		</div>
END;
        }

        return $options;
    }

    /**
     * Display html for Product Order history
     *
     * @param array $form
     */
    private function ShowProductHistory($form)
    {
        global $user;

        $sort = (isset ($form['sort']) && $form['sort']) ? strtolower($form['sort']) : 'p.name';
        $dir = (isset ($form['dir']) && $form['dir']) ? strtolower($form['dir']) : 'DESC';
        $page = (isset ($_REQUEST['page'])) ? (int) $_REQUEST['page'] : 1;
        if ($page < 1)
            $page = 1;
        $per_page = $this->per_page;
        $offset = ($page - 1) * $per_page;

        # Limited types only for webuser
        $limit_order_types = "";
        if ($user->web_user)
            $limit_order_types = "AND o.type_id IN (" . implode(",", self::$LIMITED_TYPES) . ")";

        $sort_ary = array(
            'p.name' => 'Product Name',
            'pr.preferredprice_desc' => 'Price - High to Low',
            'pr.preferredprice_asc' => 'Price - Low to High'
        );

        # Validate sort
        if (!in_array($sort, array_keys($sort_ary)))
            $sort = 'p.name';

        if ($sort)
            $form['sort'] = $sort;

        # Sort and Dir may be passed in one variable
        if (preg_match("/^[\w\.]+_(desc|asc)\$/i", $sort, $match))
        {
            $sort = str_replace("_{$match[1]}", "", $sort);
            $dir = strtoupper($match[1]);
        }

        $add_btn = "";
        if ($this->disabled == false)
        {
            $add_btn = "<div style='text-align:center;padding:5px;'>
				<input class='submit' type='submit' name='act' value='Add to Cart' />
			</div>";
        }

        # Empty Line Item for pricing
        $line_item = new LineItem();

        $sth = $this->dbh->prepare("SELECT DISTINCT
			p.id AS prod_id,
			p.name AS name,
			p.code AS code,
			p.description AS description,
			p.unit AS unit,
			p.price_uom,
			lower(p.code) AS pic,
			(	SELECT array_to_string(array_accum(u.uom),',') as options
				FROM
				(
					SELECT uom
					FROM product_uom
					WHERE product_uom.code = p.code
					AND active = true
					ORDER BY conversion_factor
				) u
			) as uoms,
			p.max_quantity,
			pr.listprice,
			pr.preferredprice,
			pr.sheet2price,
			pr.sheet3price,
			pr.sheet4price,
			COUNT(*) OVER() as total
		FROM products p
		INNER JOIN product_pricing pr ON p.id = pr.id
		WHERE p.active = true
		AND p.id IN (
			SELECT oi.prod_id
			FROM order_item oi
			INNER JOIN orders o ON oi.order_id = o.id
			WHERE o.facility_id = ? $limit_order_types
		)
		ORDER BY {$sort} {$dir}
		LIMIT $per_page OFFSET $offset");
        $sth->bindValue(1, $this->facility_id, PDO::PARAM_INT);
        $sth->execute();
        $count = $sth->rowCount();
        $total = $count;
        $args = null;
        if ($sth && $count > 0)
        {
            $product_rows = "";
            $i = 1;
            $row_class = 'on';
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $total = $row['total'];
                $pic = $this->ProductImg($row['prod_id'], $row['pic'], 'Catalog', null, 30);
                $link = $this->ProductLink($row['prod_id'], "<b><u>{$row['name']}</u></b>", 'Catalog');

                # Create UOM options select and determine default uom
                self::ParseUOM($row);

                # Set item price
                $line_item->copyFromArray($row);
                $line_item->SetPriceInfo($this->order_type, $this->bpi, $this->cpgk);

                # Format prices for money
                $list_price = (is_numeric($line_item->getVar('list_amount'))) ? money_format('%.2n', $line_item->getVar('list_amount')) : "NA";
                $preferred_price = money_format('%.2n', $line_item->getVar('amount'));

                $ups_input = $this->UpsellCheckbox(false, $i);

                $product_rows .= "
				<tr onmouseover='this.className=\"focus\";' onmouseout='this.className=\"\";'>
					<td class='prod' style=\"text-align:center\">
						$pic
						<br>
						#{$row['code']}
					</td>
					<td class='prod' style='text-align:left'>
						$link
						<br/>
						<b>{$row['description']}</b><br/>
						List Price: {$list_price}<br/>
						<span style='color:#006699;'>Preferred Price: {$preferred_price}</span>
					</td>
					<td class='prod' style=\"text-align:center\">
						<input type='hidden' name='prod_ids[$i]' value='{$row['prod_id']}' />
						<input type='hidden' id='upsell_$i' name='upsell[$i]' value='0' />
						<input type='text' name='quantities[$i]' value='0' size='2' maxlength='5' onFocus=\"this.value = '';\" onKeyPress=\"return EntertoTab(event, this);\"/>
					</td>
					<td class='prod' style=\"text-align:center\">
						<select name='uoms[$i]'>
							{$row['uom_options']}
						</select>
					</td>
					<td class='prod' style=\"text-align:center\">
						$ups_input
					</td>
				</tr>";

                $i++;
                $row_class = ($row_class == 'on') ? 'off' : 'on';
            }

            $ups_th = ($this->limit_user) ? "&nbsp" : "Upsell";
            $page_row = $this->GetPageBar($total, $count, $page, $per_page, $args);
            $sort_div = $this->GetSortBar($total, $count, 'products', $sort_ary, $form['sort']);
            $product_table = "
			<div class='ttl'>Products Ordered</div>
			$sort_div
			$add_btn
			<table width='100%' cellpadding='5' cellspacing='0' class='list'>
				{$page_row}
				<tr>
					<th class='list'>Product</th>
					<th class='list'>Description</th>
					<th class='list'>Quantity</th>
					<th class='list'>UOM</th>
					<th class='list'>$ups_th</th>
				</tr>
				$product_rows
				$page_row
			</table>
			$add_btn";
        }
        else
        {
            $product_table = "
			<table class='list' cellpadding='5' cellspacing='2' style='margin:0;'>
				<tr>
					<td bgcolor='#f7f7ff'>No Product History found</td>
				</tr>
				<tr>
					<td class='buttons'></td>
				</tr>
			</table>";
        }

        return $product_table;
    }

    /**
     * Display html for Product Detail
     *
     * @param array $form
     */
    public function ShowProduct($form)
    {
        global $user, $this_app_admin_name;

        $prod_id = (isset ($form['prod_id'])) ? (int) $form['prod_id'] : 0;
        $act = (isset ($form['act'])) ? $form['act'] : '';

        # Can add item to cart if customer is known
        $add_button = "";
        if ($this->facility_id)
        {
            # Dont show for TT
            if ($act != 'TT')
                $add_button = $this->GetButton('add');
        }

        # Limit to vizable items for CPM and Web users
        $limit_user = "";
        if ($user->web_user)
            $limit_user .= " AND pc.viz_public = true";
        if ($this->field_user)
            $limit_user .= " AND pc.viz_internal = true";

        # Empty Line Item for pricing
        $line_item = new LineItem();

        $Company = is_numeric($this->customer->getCustId()) ? SalesOrder::$ININC_CO_ID : SalesOrder::$CO_ID;
        $WhseID = is_numeric($this->customer->getCustId()) ? SalesOrder::$ININC_WHSE : SalesOrder::$PURCHASE_WHSE;

        $sth = $this->dbh->prepare("SELECT
			p.id AS prod_id,
			p.name AS name,
			p.code AS code,
			p.description AS description,
			p.unit AS unit,
			lower(p.code) AS pic,
			p.price_uom,
			p.max_quantity,
			p.prod_price_group_key,
			p.active,
			d.long_description,
			d.specifications,
			d.purpose,
			d.onhold,
			d.special,
			pu.weight,
			pr.listprice,
			pr.preferredprice,
			pr.sheet2price,
			pr.sheet3price,
			pr.sheet4price,
			p.active,
			o.order_date,
			o.order_id,
			(	SELECT array_to_string(array_accum(u.uom),', ') as options
				FROM
				(
					SELECT uom || '(' || conversion_factor || ')' as uom
					FROM product_uom
					WHERE product_uom.code = p.code
					AND active = true
					ORDER BY conversion_factor
				) u
			) as uoms,
			(
				SELECT count(*)
				FROM product_category_join j
				INNER JOIN product_catalog_category c
					ON j.cat_id = c.category_id
				INNER JOIN product_category pc ON j.cat_id = pc.id
				WHERE j.prod_id = p.id
				AND c.catalog_id = {$this->catalog}
				{$limit_user}
			) as in_catalog
		FROM products p
		LEFT JOIN product_detail d on p.id = d.prod_id
		LEFT JOIN product_pricing pr ON p.id = pr.id
		LEFT JOIN product_uom pu ON p.code = pu.code AND p.price_uom = pu.uom
		LEFT JOIN (
			SELECT oi.prod_id, MAX(mo.order_date) as order_date, MAX(o.id) as order_id
			FROM order_item oi
			INNER JOIN orders mo ON oi.order_id = mo.id
			INNER JOIN orders o ON mo.id = o.id
			WHERE mo.status_id = " . Order::$SHIPPED . "
			AND mo.facility_id = ?
			GROUP BY oi.prod_id
		) o ON p.id = o.prod_id
		WHERE p.id = ?");
        $sth->bindValue(1, (int) $this->facility_id, PDO::PARAM_INT);
        $sth->bindValue(2, $prod_id, PDO::PARAM_INT);
        $sth->execute();
        if ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $pic = $this->ProductImg($row['prod_id'], $row['pic'], null, null, 200);

            $avail_qty = Cart::GetAvailQty($row['code'], true, $WhseID, $Company);
            # Non Inventory will be null
            if (!is_null($avail_qty))
                $avail_qty -= Cart::GetPendingQty($row['prod_id'], $this->order_id);

            $available = ($avail_qty > 0) ? "In-Stock" : "Out of Stock";

            # Determin the default uom for this item
            self::ParseUOM($row);
            $uom_input = "<select name='uoms[0]'/>{$row['uom_options']}</select>";
            $qty_input = "<input type='text' name='quantities[0]' value='1' size=2 maxlength=5 />";

            # Show with pricing
            $row['upsell'] = 1;
            $line_item->copyFromArray($row);
            $line_item->SetPriceInfo($this->order_type, $this->bpi, $this->cpgk);

            # Remove Add to Cart button
            # When the product is not active
            if (!$row['active'])
            {
                $add_button = "Not Available";
                $available = "&lt;Discontinued&gt;";
                $uom_input = "";
                $qty_input = "";
            }

            # When in the catalog
            if (!$row['in_catalog'])
            {
                $add_button = "Not Available";
                $uom_input = "";
                $qty_input = "";
            }

            # When process is disabled
            if ($this->disabled)
            {
                $add_button = "Not Available";
                $uom_input = "";
                $qty_input = "";
            }

            $hold_banner = "";
            $capa_id = "";
            $eco_number = "";
            $hold_comments = "";
            if ($row['onhold'])
            {
                $hold_banner = "<tr><td colspan='3' class='alert alert-warning text-center'><b>This product is On Hold</b></td><tr>";

                // Look up capa and eco from history
                $sth = $this->dbh->prepare("SELECT
					capa_id,
					eco_number,
					comments
				FROM product_hold_history
				WHERE prod_id = ?
				AND active
				ORDER BY tstamp DESC
				LIMIT 1");
                $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
                $sth->execute();
                if ($hold = $sth->fetch(PDO::FETCH_OBJ))
                {
                    $capa_id = $hold->capa_id;
                    $eco_number = $hold->eco_number;
                    $hold_comments = $hold->comments;
                }
            }

            # Show possible discount
            $discount_txt = "";
            $price_groups = Cart::GetPriceGroup($this->cpgk, $row['prod_price_group_key']);
            if ($price_groups)
            {
                foreach ($price_groups as $pg)
                {
                    if ($pg['price_method'] == 1)
                    {
                        $discount_price = money_format('%.2n', $line_item->getVar('base_amount') * $pg['percent_adj']);
                        $pg['range_start']++;
                        $discount_txt .= "<span style='color:#009966;'>Discount Price: {$discount_price}</span> (when you order {$pg['range_start']} {$row['price_uom']}).<br/>\n";
                    }
                    else if ($pg['price_method'] == 2)
                    {
                        $discount_price = money_format('%.2n', $line_item->getVar('base_amount') - $pg['amount_adj']);
                        $pg['range_start']++;
                        $discount_txt .= "<span style='color:#009966;'>Discount Price: {$discount_price}</span> (when you order {$pg['range_start']} {$row['price_uom']}).<br/>\n";
                    }
                }
            }

            $kit_row = "";
            if ($act != 'TT' && strstr(strtolower($row['name']), 'kit'))
            {
                $kit_row = $this->ShowProductKit(array('prod_code' => $row['code']));
            }
            $last_order_row = "";
            if ($row['order_date'])
            {
                $last_order_row = "
				<tr>
					<td class='prod' colspan='3'>
						Last ordered on <a href='{$_SERVER['PHP_SELF']}?section=History&act=view&order_id={$row['order_id']}'>" .
                    date('Y-m-d', $row['order_date']) .
                    "</a>
					</td>
				</tr>";
            }
            $long_description_row = "";
            if ($row['long_description'])
            {
                $long_description_row = "
				<tr>
					<td class='prod' colspan='3'>" .
                    htmlentities($row['long_description'], ENT_QUOTES) .
                    "</td>
				</tr>";
            }
            $specifications_row = "";
            if ($row['specifications'])
            {
                $specifications_row = "
				<tr>
					<td colspan='3'>
						<b><u>Specifications:</u></b><br/>" .
                    htmlentities($row['specifications'], ENT_QUOTES) .
                    "</td>
				</tr>";
            }
            $purpose_row = "";
            if ($row['purpose'])
            {
                $purpose_row = "
				<tr>
					<td colspan='3'>
						<b><u>Purpose:</u></b><br/>" .
                    htmlentities($row['purpose'], ENT_QUOTES) .
                    "</td>
				</tr>";
            }

            # Format prices for money
            if (is_numeric($line_item->getVar('list_amount')))
                $list_price = money_format('%.2n', (double) $line_item->getVar('list_amount'));
            else
                $list_price = 'NA';

            if (is_numeric($line_item->getVar('sale_amount')))
                $your_price = money_format('%.2n', (double) $line_item->getVar('sale_amount'));
            else if (is_numeric($line_item->getVar('base_amount')))
                $your_price = money_format('%.2n', (double) $line_item->getVar('base_amount'));
            else
                $your_price = 'NA';

            # Set Dimension
            $weight = ($row['weight']) ? "{$row['weight']} lbs / {$row['price_uom']}" : "unk";

            $edit = "";
            $upsell_row = "";
            $image_bar = "";
            # Show list of items also ordered
            # Dont show this for tooltip display
            if ($act != 'TT')
            {
                $upsell_list = $this->GetUpsaleProducts($prod_id);
                if ($upsell_list)
                    $upsell_row = "
					<tr>
						<td class='prod' colspan='3'>
							<b><u>Commonly Ordered with:</u></b><br/>
							$upsell_list
						</td>
					</tr>";

                $image_bar = "
				<div id='ibar' class='ibar'>
					<script type='text/javascript'> LoadImageBar($prod_id, true); </script>
				</div>";

                if ($user->hasAccessToApplication($this_app_admin_name))
                    $edit = "<br/>" . $this->ProductLink($prod_id, 'Admin Edit', 'Admin');
            }

            $available = ($user->web_user) ? "" : "Availability: {$available}<br/>";

            $product_table = "
			<table align='center' cellpadding='15' cellspacing='0' style='background-color:#FBFBFB; border:1px solid #DBDBDB;font-size:small;'>
				$hold_banner
				<tr>
					<td class='prod' align='center'>
						{$pic}
						{$image_bar}
						{$edit}
					</td>
					<td class='prod' align='left'>
						<b><u>{$row['name']}</u></b>
						<br/>
						Product Code: #{$row['code']}
						<br/>
						List Price: {$list_price}<br/>
						<span style='color:#006699;'>Your Price: {$your_price}</span><br/>
						{$discount_txt}
						Weight: $weight<br/>
						Order in: {$row['uoms']}<br/>
						{$available}
						<b>{$row['description']}</b><br/>
					</td>
					<td class='prod' align='center'>
						<input type='hidden' name='prod_ids[0]' value='{$row['prod_id']}' />
						<input type='hidden' id='upsell' name='upsell[0]' value='0' />
						$qty_input&nbsp;$uom_input
						<br/>
						{$add_button}
					</td>
				</tr>
				{$long_description_row}
				{$kit_row}
				{$last_order_row}
				{$upsell_row}
				{$purpose_row}
				{$specifications_row}
			</table>";

            # Only show this for corporate users
            if ($row['onhold'] && $this->limit_user == false)
            {
                $product_table .= "
			<div class='modal fade' id='hold_dlg' tabindex='-1' role='dialog' aria-labelledby='hold_dlg_lbl'>
				<div class='modal-dialog' role='document'>
					<div class='modal-content'>
						<div class='modal-header'>
							<button type='button' class='close' data-dismiss='modal' aria-label='Close'>
								<span aria-hidden='true'>&times;</span>
							</button>
							<h5 class='modal-title'>Product Hold</h5>
						</div>
						<div class='modal-body'>
							<div class='form-group'>
								<label>Capa Number: $capa_id</label>
							</div>
							<div class='form-group'>
								<label>ECO Number: $eco_number</label>
							</div>
							<div class='form-group'>
								<label>Comment:</label>
								<textarea class='form-control' readonly rows='5'>$hold_comments</textarea>
							</div>
						</div>
						<div class='modal-footer'>
							<input class='btn btn-default' type='submit' id='add_confirm' name='act' value='Add to Cart'>
							<button class='btn btn-default' type='button' data-dismiss='modal'>Close</button>
						</div>
					</div>
				</div>
			</div>
<script type='text/javascript'>
$(document).ready(function(e)
{
	$('#add_to_cart').click(function() {
		$('#hold_dlg').modal();
		return false;
	});
});
</script>";
            }
        }
        else
        {
            $product_table = "
			<table class='list' cellpadding='5' cellspacing='2' style='margin-left:0;'>
				<tr>
					<td bgcolor='#f7f7ff'>Invalid Product ID : $prod_id</td>
				</tr>
			</table>";
        }

        return $product_table;
    }

    /**
     * Display html for Product Kit Detail
     *
     * @param array $form
     */
    public function ShowProductKit($form)
    {
        global $conf, $user, $this_app_admin_name;

        # Non inventoried parts return null
        $avail_qty = null;
        $mdbh = null;
        $kit = "";
        $item_code = (isset ($form['prod_code'])) ? $form['prod_code'] : 0;

        /**
         * For now using Configuration File var
                try
                {
                    $do_lookup = $conf->get('perform_inventory_lookup');
                }
                catch (Exception $exc)
                {
                    # Just making sure this does not stop normal execution
                    $do_lookup = 0;
                }
        */
        $do_lookup = Config::$perform_inventory_lookup;

        if ($do_lookup)
        {
            $mdbh = DataStor::GetHandle(false);
            if ($mdbh)
            {
                $sth = $mdbh->prepare("SELECT
					ki.ItemID,
					id.ShortDesc,
					cl.CompItemQty
				FROM timKitCompList AS cl
				INNER JOIN timItem AS i ON cl.KitItemKey = i.ItemKey
				INNER JOIN timItem AS ki ON cl.CompItemKey = ki.ItemKey
				INNER JOIN timItemDescription id ON cl.CompItemKey = id.ItemKey
				WHERE i.ItemID = ? AND i.CompanyID = ?
				ORDER BY cl.SeqNo");
                $sth->bindValue(1, $item_code, PDO::PARAM_STR);
                $sth->bindValue(2, Config::$Company, PDO::PARAM_STR);
                $sth->execute();
                $count = $sth->rowCount();
                if ($count)
                {

                    $kit = "<tr>
						<td class='prod' colspan='3'>
							<b><u>Contains:</u></b><br/>
							<ul>";
                    while (list($id, $name, $qty) = $sth->fetch(PDO::FETCH_NUM))
                    {
                        $qty = (int) $qty;
                        $name = htmlentities($name, ENT_QUOTES);

                        $kit .= "<li>($qty) $id: $name</li>";
                    }

                    $kit .= "</ul></td></tr>";
                }
            }
        }

        return $kit;
    }

    /**
     * Display html for Product Detail for editing
     *
     * @param array $form
     */
    private function ShowProductEdit($form)
    {
        $prod_id = (isset ($form['prod_id'])) ? $form['prod_id'] : 0;

        $tab = (isset ($form['tab'])) ? $form['tab'] : 'Categories';
        $tab .= 'Product';

        $add_button = "";

        $categories = "";
        $sth = $this->dbh->query("SELECT
			c.id, c.category_name, j.prod_id
		FROM product_category c
		LEFT JOIN product_category_join j ON c.id = j.cat_id AND j.prod_id = {$prod_id}
		ORDER BY c.display_order");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $category_name = $row['category_name'];
            $chk_category = ($row['prod_id']) ? "checked" : "";

            $categories .= "<tr>
				<td class='view' style='white-space:nowrap;'>
					{$row['category_name']}
				</td>
				<td class='view'>
					<input type='checkbox' name='cat_ids[{$row['id']}]' value='{$row['id']}' $chk_category/>
				</td>
			</tr>";
        }

        $devices = "";
        $sth = $this->dbh->query("SELECT
			m.id, m.model, m.description, es.prod_id
		FROM equipment_models m
		LEFT JOIN equipment_supply es on m.id = es.model_id AND es.prod_id = {$prod_id}
		WHERE m.active = true
		AND m.base_assets IS NULL
		AND m.model IN (SELECT code FROM service_item_to_product)
		ORDER BY m.display_order");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $chk_dev = ($row['prod_id']) ? "checked" : "";

            $devices .= "<tr>
				<td class='view' style='white-space:nowrap;'>
					{$row['model']}
				</td>
				<td class='view' style='white-space:nowrap;'>
					{$row['description']}
				</td>
				<td class='view'>
					<input type='checkbox' name='dev_ids[{$row['id']}]' value='{$row['id']}' $chk_dev/>
				</td>
			</tr>";
        }

        $weights = "";
        $sth = $this->dbh->prepare("SELECT
			u.uom, u.weight
		FROM product_uom u
		INNER JOIN products p on u.code = p.code
		WHERE p.id = ?
		ORDER BY u.conversion_factor");
        $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $w = ($row['weight']) ? "{$row['weight']} lbs" : "Unk";
            $weights .= "<div>Weight: $w / {$row['uom']}</div>";
        }

        $sth = $this->dbh->prepare("SELECT
			p.id AS id,
			p.name AS name,
			p.code AS code,
			p.description AS description,
			p.unit AS unit,
			lower(p.code) AS pic,
			p.active,
			CASE WHEN d.track_inventory THEN 'checked' ELSE '' END as track_inventory,
			d.long_description,
			d.specifications,
			d.purpose,
			d.lot_required,
			d.onhold,
			d.featured,
			d.special,
			d.email_subject,
			d.email_body
		FROM products p
		LEFT JOIN product_detail d on p.id = d.prod_id
		WHERE p.id = ?");
        $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetch(PDO::FETCH_ASSOC);

        $pic = $this->ProductImg($row['id'], $row['pic'], null, null, 200);
        $link = $this->ProductLink($row['id'], 'Catalog View', 'Catalog');

        $active = ($row['active']) ? "Yes" : "No";
        $lot_required = ($row['lot_required']) ? "checked" : "";
        $featured = ($row['featured']) ? "checked" : "";
        $special = ($row['special']) ? "checked" : "";

        $special_row = "";
        if ($row['special'])
        {
            $special_row = "
			<div class='panel-heading'>Special Handling Email:</div>
			<div class='panel-body'>
				<div class='form-group'>
					<label for='email_subject'>Subject:</label>
					<textarea class='form-control' name='email_subject' rows='1'>{$row['email_subject']}</textarea>
				</div>
				<div class='form-group'>
					<label for='email_body'>Body:</label>
					<textarea class='form-control' id='email_body' name='email_body' rows='5'>{$row['email_body']}</textarea>
				</div>
				<div class='form-group'>
					<label>Replaceable Codes</label>
					<span class='badge'>:order_id</span>
					<span class='badge'>:order_notes</span>
					<span class='badge'>:cust_id</span>
				</div>
			</div>";
        }
        // Set hidden field value
        $onhold = ($row['onhold']) ? "1" : "0";
        // Set the btn action
        if ($row['onhold'])
        {
            $onhold_btn = "<button type='button' class='btn btn-xs btn-default' data-toggle='modal' data-target='#hold_dlg'/>Release Hold</button>";
            $capa_id = "";
            $eco_number = "";
            $hold_submit = "release";
            $hold_banner = "<div class='alert alert-warning text-center'><b>This product is On Hold</b></div>";

            // Look up capa and eco from history
            $sth = $this->dbh->prepare("SELECT
				capa_id,
				eco_number
			FROM product_hold_history
			WHERE prod_id = ?
			AND active
			ORDER BY tstamp DESC
			LIMIT 1");
            $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
            $sth->execute();
            if ($hold = $sth->fetch(PDO::FETCH_OBJ))
            {
                $capa_id = $hold->capa_id;
                $eco_number = $hold->eco_number;
            }
        }
        else
        {
            $onhold_btn = "<button type='button' class='btn btn-xs btn-default' data-toggle='modal' data-target='#hold_dlg'/>Set Hold</button>";
            $capa_id = "";
            $eco_number = "";
            $hold_submit = "hold";
            $hold_banner = "";
        }

        // Look all hold history
        $hold_tr = "";
        $sth = $this->dbh->prepare("SELECT
			u.firstname,
			u.lastname,
			h.tstamp,
			h.action,
			h.active,
			h.capa_id,
			h.eco_number,
			h.comments
		FROM product_hold_history h
		INNER JOIN users u on h.user_id = u.id
		WHERE h.prod_id = ?
		ORDER BY tstamp DESC");
        $sth->bindValue(1, $prod_id, PDO::PARAM_INT);
        $sth->execute();
        while ($hold = $sth->fetch(PDO::FETCH_OBJ))
        {
            $active = ($hold->active) ? "Active" : "";
            $hold_tr .= "<tr>
				<td>{$hold->firstname} {$hold->lastname}</td>
				<td>{$hold->action}</td>
				<td>{$hold->tstamp}</td>
				<td>{$active}</td>
				<td>{$hold->capa_id}</td>
				<td>{$hold->eco_number}</td>
				<td>{$hold->comments}</td>
			</tr>";
            $capa_id = $hold->capa_id;
            $eco_number = $hold->eco_number;
        }

        // Add Hold history section if there is data to show.
        $hold_history = "";
        if ($hold_tr)
        {
            $hold_history = "
			<div class='panel-heading'>Hold History:</div>
			<div class='panel-body'>
				<table class='table table-compact table-striped'>
					<thead>
						<tr>
							<th>User</th>
							<th>Action</th>
							<th>Date/Time</th>
							<th>Active</th>
							<th>Capa</th>
							<th>ECO</th>
							<th>Comments</th>
						</tr>
					</thead>
					<tbody>
						$hold_tr
					</tbody>
				</table>
			</div>";
        }

        $product_table = "
		<input type='hidden' name='prod_id' value='{$row['id']}' />
		<input type='hidden' name='tab' value='{$tab}'/>
		<input type='hidden' name='onhold' value='{$onhold}' />
		<div class='col-md-4'>
			<div class='panel panel-primary'>
				<div class='panel-heading'>Member of Category</div>
				<div class='panel-body no-padding'>
					<table class='table face table-compact'>
						$categories
					</table>
				</div>
			</div>
			<div class='panel panel-primary'>
				<div class='panel-heading'>Supply for Device</div>
				<div class='panel-body no-padding'>
					<table class='table face table-compact'>
						$devices
					</table>
				</div>
			</div>
		</div>
		<div class='col-md-8 prod'>
			<div class='panel panel-primary'>
				<div class='panel-body'>
					$hold_banner
					<div class='text-center' style='display: inline-block; vertical-align:top; margin-right: 20px;'>
						$pic
						<div id='ibar' class='ibar'>
							<script type='text/javascript'> LoadImageBar({$row['id']}); </script>
						</div>
						<div>
							$link
						</div>
						<div>
							<a href=\"{$_SERVER['PHP_SELF']}?section=Admin&act=image&tab=Categories&prod_id={$row['id']}\">Manage Images</a>
						</div>
					</div>
					<div style='display: inline-block;  vertical-align:middle; margin-right: 20px;'>
						<div><b><u>{$row['name']}</u></b></div>
						<div>Product Code: #{$row['code']}</div>
						<div>Active: <u>$active</u></div>
						{$weights}
						<div><b>{$row['description']}</b></div>
					</div>
					<div style='display: inline-block;  vertical-align:top;'>
						<table class='table face table-condensed'>
							<tr>
								<th class='subheader' colspan='2'>Admin Options</th>
							</tr>
							<tr>
								<th>Featured Item:</th>
								<td>
									<input type='checkbox' name='featured' value='1' $featured />
								</td>
							</tr>
							<tr>
								<th>Track Inventory:</th>
								<td>
									<input type='checkbox' name='track_inventory' value='1' {$row['track_inventory']} />
								</td>
							</tr>
							<tr>
								<th>Requires Lot Identification:</th>
								<td>
									<input type='checkbox' name='lot_required' value='1' $lot_required />
								</td>
							</tr>
							<tr class='bg-warning'>
								<th>Hold Shipments:</th>
								<td>
									{$onhold_btn}
								</td>
							</tr>
							<tr>
								<th>Requires Special Handling:</th>
								<td>
									<input type='checkbox' name='special' value='1' $special />
								</td>
							</tr>
						</table>
					</div>
				</div>
				<div class='modal fade' id='hold_dlg' tabindex='-1' role='dialog' aria-labelledby='hold_dlg_lbl'>
					<div class='modal-dialog' role='document'>
						<div class='modal-content'>
							<div class='modal-header'>
								<button type='button' class='close' data-dismiss='modal' aria-label='Close'>
									<span aria-hidden='true'>&times;</span>
						        </button>
								<h5 class='modal-title'>Product Hold</h5>
							</div>
							<div class='modal-body'>
								<div class='form-group'>
									<label for='capa_id'>Capa Number:</label>
									<input class='form-control' type='text' id='capa_id' name='capa_id' value='$capa_id'/>
								</div>
								<div class='form-group'>
									<label for='eco_number'>ECO Number:</label>
									<input class='form-control' type='text' id='eco_number' name='eco_number' value='$eco_number'/>
								</div>
								<div class='form-group'>
									<label for='comments'>Comment:</label>
									<textarea class='form-control' id='hold_comments' name='comments' rows='5'></textarea>
								</div>
							</div>
							<div class='modal-footer'>
								<button type='submit' class='btn btn-default' id='hold_submit' name='hold_submit' value='$hold_submit'>Save</input>
								<button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
							</div>
						</div>
					</div>
				</div>
				<div class='panel-heading'>Long Description:</div>
				<div class='panel-body'>
						<textarea class='form-control' name='long_description' rows='5'>{$row['long_description']}</textarea>
				</div>
				<div class='panel-heading'>Specifications:</div>
				<div class='panel-body'>
						<textarea class='form-control' name='specifications' rows='5'>{$row['specifications']}</textarea>
				</div>
				<div class='panel-heading'>Purpose:</div>
				<div class='panel-body'>
						<textarea class='form-control' name='purpose' rows='5'>{$row['purpose']}</textarea>
				</div>
				{$special_row}
				{$hold_history}
				<div class='panel-footer'>
					<div class='buttons'>
						<input class='submit' type='submit' name='submit_btn' value='Save' onClick=\"this.form.act.value='save';\"/>
					</div>
				</div>
			</div>
		</div>

		<script type='text/javascript'>
			$(document).ready(function() {
				$('#hold_submit').click(function () {
					if ($('#hold_comments').val())
					{
						$(\"#wsorder\").find(\"input[name='act']\").val('save');
						return true;
					}
					else
					{
						alert('A Comment is required.');
						$('#hold_comments').attr('placeholder', 'Please enter a comment');
						$('#hold_comments').parent().addClass('has-error');
						$('#hold_comments').focus();
						return false;
					}
				});

				tinymce.init({
					selector: '#email_body',
					autoresize_max_height: 675,
					menubar: false,
					plugins: 'table, autoresize, code, lists',
					toolbar: 'bold italic underline fontsizeselect forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | code',
					browser_spellcheck: true,
					forced_root_block : false,
					force_br_newlines : true,
					force_p_newlines : false,
					indent: false,
					statusbar: false,
					content_style: 'body {width: 650px; font-size: 12px; text-align: justify;}',
					setup: function (editor) {
						editor.on('change', function () {
							tinymce.triggerSave();
						});
					}
				});
			});
		</script>";

        return $product_table;
    }

    /**
     * Prints the contents of an order.
     */
    public function showOrder($order = null)
    {
        global $user, $sh, $hippa_access;

        $order_date = ($order->getVar('order_date') > 0) ? date('D j M Y', $order->getVar('order_date')) : '';
        $ship_date = ($order->getVar('ship_date') > 0) ? date('D j M Y', $order->getVar('ship_date')) : '';
        $inst_date = ($order->getVar('inst_date') > 0) ? date('D j M Y', $order->getVar('inst_date')) : '';

        $show_patient_info = ($order->getVar('cust_entity_type') == CustomerEntity::$ENTITY_PATIENT)
            ? $this->show_patient : true;

        $sname = ($show_patient_info) ? $order->getVar('sname') : "Customer";

        $duplicate = "";
        $dup_type = in_array($order->getVar('type_text'), array('Supply Order', 'Customer Order', 'Web Order'));
        $allowed = in_array($order->getVar('type_id'), $this->allowed_types);
        if ($dup_type && ($allowed || $user->web_user) && $order->getVar('ship_date') > 0)
        {
            $duplicate = "&nbsp;<img class=\"form_bttn\" src=\"images/orderfil.png\" height='24' onClick=\"window.location='{$_SERVER['PHP_SELF']}?act=duplicate&amp;order_id={$order->getVar('id')}'\" alt=\"Duplicate Order\" title=\"Duplicate Order\">";
        }

        $ship_to = 'Other';
        if ($order->getVar('ship_to') == Order::$SHIP_TO_FACILITY)
            $ship_to = ($show_patient_info) ? $order->getVar('facility_name') : "Customer";
        elseif ($order->getVar('ship_to') == Order::$SHIP_TO_CPM)
            $ship_to = 'Home';

        $so_number = ($order->getVar('mas_sales_order') > 0) ? $order->getVar('mas_sales_order') : '';

        $comments_row = "";
        if ($order->getVar('comments'))
        {
            $comments_row = "
			<tr>
				<th colspan=\"4\" class=\"subheader\">Order Notes</th>
			</tr>
			<tr>
				<td class='view' colspan=\"4\">{$order->getVar('comments')}</td>
			</tr>";
        }

        $tracking = Order::FormatTrackingNo($order->getVar('tracking_num'));
        $tracking .= Order::FormatTrackingNo($order->getVar('ret_tracking_num'));

        $tracking_row = ($tracking) ?
            "<tr>
				<td class='view'>Tracking #</td>
				<td class='view'>{$tracking}</td>
			</tr>" : "";

        // Get sum of shipping and tax for all the orders in the group
        // Normally only the first order will have these charges
        if (count($order->order_group) > 1)
        {
            $sth = $this->dbh->query("SELECT sum(shipping_cost), sum(tax_amount)
			FROM orders WHERE id IN (" . implode(",", $order->order_group) . ")");
            list($shipping_cost, $tax_amount) = $sth->fetch(PDO::FETCH_NUM);

            $order->setVar('shipping_cost', $shipping_cost);
            $order->setVar('tax_amount', $tax_amount);
        }

        # Build Item list
        $item_rows = "";
        $sub_total = 0;
        $tax = (double) $order->getVar('tax_amount');
        $shipping = (double) $order->getVar('shipping_cost');
        $this->ShowItemRows($order, $item_rows, $sub_total);
        $total = "\$" . number_format($sub_total + $tax + $shipping, 2);
        $shipping = "\$" . number_format($shipping, 2);
        $tax = "\$" . number_format($tax, 2);
        $sub_total = "\$" . number_format($sub_total, 2);

        $parent_row = "";
        if ($order->getVar('parent_order'))
            $parent_row = "
			<tr>
				<th class='view'>Reference Order:</th>
				<td  class='view' colspan='3'>{$order->getVar('parent_order')}</td>
			</tr>";

        $bo_row = "";
        if ($order->getVar('back_order'))
        {
            $bo_row = "
			<tr>
				<th class='view'>Back Order #</th>
				<td class='view' colspan='3'>{$order->getVar('back_order')}</td>
			</tr>";
        }

        # Show these rows for Customer Orders
        #
        $email = $ordered_by = $fax = "";
        if ($order->getVar('type_id') == Order::$CUSTOMER_ORDER)
        {
            if ($order->getVar('email'))
                $email = "
			<tr>
		    	<td class='view'>Outgoing Email</td>
				<td class='view'>
					{$order->getVar('email')}
				</td>
			</tr>";

            $ob_label = ($user->web_user) ? "Ordered By" : "Customer Name";

            if ($order->getVar('ordered_by'))
                $ordered_by = "
			<tr>
		    	<th class='view'>$ob_label</th>
				<td class='view' colspan='3'>
					{$order->getVar('ordered_by')}
				</td>
			</tr>";

            if ($order->getVar('fax'))
                $fax = "
			<tr>
		    	<td class='view'>Fax</td>
				<td class='view'>
					{$order->getVar('fax')}
				</td>
			</tr>";
        }


        # Show notice for view only
        if ($order->getVar('facility_id') != $this->facility_id)
        {
            $this->msg .= "<p class='warning'>View Only Facility/Customer that is selected does not match this order!</p>";
        }

        $order_id = implode(',', $order->order_group);

        return <<<END
		<table cellpadding='0' cellspacing='20' style='margin-left:20px;'>
			<tr valign='top'>
				<td>
					<table width='100%' cellpadding="5" cellspacing="2" class="view" style='margin:0;'>
						<tr>
							<th class="subheader" colspan="4">
								<a href='{$_SERVER['PHP_SELF']}?section=History&act=print&order_id={$order->getVar('id')}'
									style='float:right; font-size: 9pt; font-weight:normal;' target='_blank'>Print</a>
								Order Details
							</th>
						</tr>
						<tr>
							<th class='view' style='text-align:left;'>
								Order&nbsp;#:
							</th>
							<td class='view'>{$order_id}{$duplicate}</td>
							<th class='view'>Order Date:</th>
							<td class='view'>{$order_date}</td>
						</tr>
						<tr>
							<th class='view'>Type:</th>
							<td class='view'>{$order->getVar('type_text')}</td>
							<th class='view'>Status:</th>
							<td class='view'>{$order->getVar('status_name')}</td>
						</tr>
						{$bo_row}
						{$parent_row}
						<tr>
							<th class='view'>PO #:</th>
							<td class='view'>{$order->getVar('po_number')}</td>
							<th class='view'>SO #:</th>
							<td class='view'>{$so_number}</td>
						</tr>
						<tr>
							<th class='view'>Cust ID:</th>
							<td class='view' colspan='3'>{$order->getVar('cust_id')}</td>
						</tr>
						{$ordered_by}
						{$comments_row}
					</table>
					<br/>
					<table width='100%' cellpadding="5" cellspacing="2" class="view"  style='margin:0;'>
						<tr>
							<th class="subheader" colspan='2'>Shipping Information:</th>
						</tr>
						<tr>
							<td class='view'>Ship To:</td>
							<td class='view'>{$ship_to}</td>
						</tr>
						<tr>
							<td class='view'>Shipping Method:</td>
							<td class='view'>{$order->getVar('ship_method')}</td>
						</tr>
						<tr>
							<td class='view'>Service:</td>
							<td class='view'>{$order->getVar('service_level_text')}</td>
						</tr>
						{$tracking_row}
						<tr>
							<td class='view'>Shipped Date:</td>
							<td class='view'>{$ship_date}</td>
						</tr>
						<tr>
							<td class='view'>Delivery Date:</td>
							<td class='view'>{$inst_date}</td>
						</tr>
						<tr>
							<td class='view'>Phone #:</td>
							<td class='view'>{$order->getVar('phone')}</td>
						</tr>
						{$fax}
						{$email}
						<tr>
							<th class="subheader" colspan='2'>Address</th>
						</tr>
						<tr>
							<td class='view' colspan='2'>
								{$sname}<br>
								{$order->getVar('address')}
								{$order->getVar('address2')}<br>
								{$order->getVar('city')}, {$order->getVar('state')} {$order->getVar('zip')}<br>
							</td>
						</tr>
					</table>
				</td>
				<td>
					<table cellpadding="5" class="view" style='margin:0;'>
						<tr><th class="subheader" colspan="2">Items Ordered</th></tr>
						{$item_rows}
						<td class='view' colspan='2' style='text-align:right;line-height:25px;'>
							Sub Total: {$sub_total}<br/>
							Estimated Shipping: {$shipping}<br/>
							<u>Estimated Tax: {$tax}</u><br/>
							<b>Estimated Total: <span style='border: 1px solid grey;background-color:#D3DCE3;'>{$total}</span></b>
						</td>
					</table>
				</td>
			</tr>
	</table>
END;
    }

    /**
     * Output order history
     *
     * @param array $form
     *
     */
    private function ShowOrderHistory($form)
    {
        global $user, $sh, $hippa_access, $preferences, $date_format;

        $sort_order = (isset ($form['sort'])) ? $form['sort'] : 'order_date_desc';
        $order_term = preg_replace(array('/_desc$/', '/_asc$/'), array(' DESC ', ' ASC '), $sort_order);
        $page = (isset ($_REQUEST['page'])) ? (int) $_REQUEST['page'] : 1;
        if ($page < 1)
            $page = 1;
        $per_page = $this->per_page;
        $offset = ($page - 1) * $per_page;

        $sort_ary = array(
            'order_id_asc' => 'Order Number - Low to High',
            'order_id_desc' => 'Order Number - High to Low',
            'order_date_desc' => 'Order Date - Recent to Oldest',
            'order_date_asc' => 'Order Date - Oldest to Recent',
            'ship_date_desc' => 'Ship Date - Recent to Oldest',
            'ship_date_asc' => 'Ship Date - Oldest to Recent',
        );

        $order_rows = "<tr><td colspan='10'>No orders found</td></tr>";

        # Limited types only for webuser
        $limit_order_types = "";
        if ($user->web_user)
            $limit_order_types = "AND o.type_id IN (" . implode(",", self::$LIMITED_TYPES) . ")";

        $sth = $this->dbh->prepare("SELECT count(DISTINCT o.id)
		FROM (orders o
		INNER JOIN order_status os ON o.status_id = os.id
		INNER JOIN order_type ot ON o.type_id = ot.type_id)
		LEFT OUTER JOIN v_customer_entity ce ON o.facility_id = ce.id
		WHERE o.facility_id = ?	$limit_order_types");
        $sth->bindValue(1, $this->facility_id, PDO::PARAM_INT);
        $sth->execute();
        $total = $sth->fetchColumn();

        $sth = $this->dbh->prepare("SELECT
			o.id AS order_id,
			o.order_date AS order_date,
			o.ship_to AS ship_to,
			o.ship_date AS ship_date,
			o.tracking_num AS tracking_num,
			o.status_id AS status_id,
			o.parent_order,
			os.name AS status,
			o.type_id,
			ot.description as type,
			sl.description as service_level,
			ce.name as facility_name,
			ce.entity_type,
			ce.cust_id AS accounting_id
		FROM (orders o
		INNER JOIN order_status os ON o.status_id = os.id
		INNER JOIN order_type ot ON o.type_id = ot.type_id
		INNER JOIN order_service_level sl ON o.service_level = sl.id)
		LEFT OUTER JOIN v_customer_entity ce ON o.facility_id = ce.id
		WHERE o.facility_id = ? $limit_order_types
		ORDER BY $order_term
		LIMIT $per_page OFFSET $offset");
        $sth->bindValue(1, $this->facility_id, PDO::PARAM_INT);
        $sth->execute();
        $count = $sth->rowCount();

        # Build Pagination Bar
        $args = null;
        if ($sort_order)
            $args['sort'] = $sort_order;
        $page_row = $this->GetPageBar($total, $count, $page, $per_page, $args);

        # Build table header Row
        $hdr_ary = array('Order #', 'Order Date', 'Ship To', 'B/O', 'Type', 'Status', 'Ship Date', 'Tracking', '');
        if ($user->web_user)
        {
            unset($hdr_ary[2]);
            unset($hdr_ary[3]);
        }
        $table_hr = "<tr><th class='list'>" . implode("</th><th class='list'>", $hdr_ary) . "</th></tr>";
        $table_rows = "";

        $sort_div = $this->GetSortBar($total, $count, 'Orders', $sort_ary, $sort_order);

        if ($count)
        {
            $orderfil = $user->hasAccessToApplication('orderfil');
            $show_name = ($this->customer->getEntityType() == CustomerEntity::$ENTITY_PATIENT) ? $this->show_patient : true;

            $rc = 'on';
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                # Format Output
                $order_id = $row['order_id'];
                $order_date = date($date_format, $row['order_date']);
                $type = str_replace(' Order', '', $row['type']);
                $ship_date = ($row['ship_date']) ? date($date_format, $row['ship_date']) : '';
                $tracking = Order::FormatTrackingNo($row['tracking_num']);
                $bo = ($row['parent_order']) ? "Yes" : ""; # Back Order indicator

                if ($row['ship_to'] == 1)
                    $to = ($show_name) ? $row['facility_name'] : $row['accounting_id'];
                else if ($row['ship_to'] == 2)
                    $to = 'Home';
                else
                    $to = '';

                # Additional Information
                if ($user->web_user)
                {
                    # Changing label
                    if ($row['status'] == 'Queued')
                        $row['status'] = "Pending";

                    # Hide these fields
                    $ship_to = "";
                    $back_order = "";
                }
                else
                {
                    $ship_to = "<td style='text-align:left;'>{$to}</td>";
                    $back_order = "<td style='text-align:left;'>{$bo}</td>";
                }

                # Buttons
                $buttons = '';
                $preview = "";
                if ($orderfil)
                    $preview = "onmouseover=\"GetPackList(event,{$order_id});\"
					onmouseout=\"HidePackList($order_id);\"";

                $allowed = in_array($row['type_id'], $this->allowed_types);
                $dup_type = in_array($type, array('Supply', 'Customer', 'Web'));

                if ($row['status_id'] != 99)
                {
                    $buttons .= "<img class=\"form_bttn\" src=\"images/search-mini.png\"
					onClick=\"window.location='{$_SERVER['PHP_SELF']}?section=History&amp;act=view&amp;order_id={$order_id}'\"
					{$preview} alt='View Order' title='View Order' >&nbsp;";
                }
                else
                {
                    if (!$user->web_user)
                    {
                        $buttons .= "<img class=\"form_bttn\"
						src=\"images/edit-mini.png\"
						onClick=\"if (confirm('This will empty your current shopping cart. Are you sure you want to continue?'))
							window.location='{$_SERVER['PHP_SELF']}?act=edit&amp;order_id={$order_id}'\"
						$preview alt=\"Edit Order\" title=\"Edit Order\">&nbsp;";
                    }
                }

                if ($row['status_id'] == 1 && $this->disabled == false)
                {
                    if (!$user->web_user && !$this->order_id)
                    {
                        $buttons .= "<img class=\"form_bttn\"
						src=\"images/edit-mini.png\"
						onClick=\"if (confirm('This will empty your current shopping cart. Are you sure you want to continue?'))
							window.location='{$_SERVER['PHP_SELF']}?act=edit&amp;order_id={$order_id}'\"
						alt=\"Edit Order\" title=\"Edit Order\">&nbsp;";
                    }
                }

                if ($row['status_id'] == 1 || $row['status_id'] == 99)
                {
                    $buttons .= "<img class=\"form_bttn\"
					src=\"images/cancel.png\"
					onClick=\"if (confirm('Are you sure you want to cancel this order?'))
						window.location='{$_SERVER['PHP_SELF']}?act=cancel&amp;order_id={$order_id}'\"
					alt=\"Cancel\" title=\"Cancel Order\">";
                }

                if ($dup_type && ($allowed || $user->web_user) && $this->disabled == false)
                {
                    $buttons .= "<img class=\"form_bttn\"
					src=\"images/orderfil.png\" height='24'
					onClick=\"window.location='{$_SERVER['PHP_SELF']}?act=duplicate&amp;order_id={$order_id}'\"
					alt=\"Duplicate this order\" title=\"Duplicate this order\">";
                }

                # New Row
                $table_rows .= "
				<tr class='$rc'>
					<td>{$row['order_id']}</td>
					<td>{$order_date}</td>
					{$ship_to}
					{$back_order}
					<td style='text-align:left;'>{$type}</td>
					<td>{$row['status']}</td>
					<td>{$ship_date}</td>
					<td>{$tracking}</td>
					<td style='text-align:left;'>{$buttons}</td>
				</tr>";

                $rc = ($rc == 'on') ? 'off' : 'on';
            }
        }

        return "
			<div class='ttl'>Order History</div>
			$sort_div
			<table width='100%' align='left' cellpadding='5' cellspacing='0' class='list'>
				{$page_row}
				{$table_hr}
				{$table_rows}
				{$page_row}
			</table>";
    }

    /**
     * Display html for Welcome screen
     *
     * param array $form
     *
     * return string (html)
     */
    private function ShowWelcome($form)
    {
        $act = (isset ($form['act'])) ? $form['act'] : '';

        $dialog = "";
        switch ($act)
        {
            case 'contact':
                include ('ws_contact_us.php');
                break;
            case 'terms':
                include ('ws_terms_conditions.php');
                break;
            case 'privacy':
                include ('ws_privacy.php');
                break;
            case 'msds':
                include ('ws_msds.php');
                break;
            default:

                $location = "https://" . $_SERVER['SERVER_NAME'];
                # Set port if differs from the standard
                if (Config::$HTTPS_PORT != 443)
                    $location .= ":" . Config::$HTTPS_PORT;
                $location .= Config::$WEB_PATH . "/auth.php";

                $login = <<<END
			<table class="form" cellpadding="5" cellspacing="2"  style="width: 400px; margin-bottom:100px">
				<tr>
					<th class='subheader' colspan='2'>
						Secure Login Form
					</th>
				</tr>
				<tr>
					<th class="form">Customer ID:</th>
					<td class="form">
						<input class="form" type="text" name="username" size="15" maxlength="32">
					</td>
					<!-- <td class="form" rowspan="2">
					<img src="images/login.png" alt="[Login Graphic]">
					</td> -->
				</tr>

				<tr>
					<th class="form">Password:</th>
					<td class="form">
						<input class="form" type="password" name="password" autocomplete="off" size="15" maxlength="64">
					</td>
				</tr>

				<tr>
					<td class="buttons" colspan="2">
						<input class="form" type="submit" name="Login" value="Login" />
					</td>
				</tr>
			</table>
END;
                $company_name = Config::$COMPANY_NAME;

                $contents = <<<END
		<div style='width: 800px;'>
			<h1>Welcome to {$company_name}</h1>
			<table cellpadding="0" cellspacing="0" style="border-collapse: collapse" width="100%">
				<tr>
					<td width="100%">
						You will need a Customer ID and Password issued by <b>{$company_name}</b>,
						to access this site. If you have not received your login or do not know how to access it,
						please reach out to our Customer Support team directly at
						<a href="mailto:CustomerSupport@acplus.com">CustomerSupport@acplus.com</a> or
						1-800-350-1100.
					</td>
				</tr>
			</table>
			{$login}
		</div>
END;
                break;
        }

        $this->contents = <<<END
			<div align='center' style='background-color:white;'>
				<img border="0" src="images/Banner800x162.png">
				{$contents}
			</div>
END;
    }

    /**
     * Add/Update/Delete Items from the shopping cart
     *
     * @param array $form
     */
    protected function UpdateCart($form)
    {
        global $user, $this_app_name, $preferences;

        $act = (isset ($form['act'])) ? strtolower($form['act']) : '';
        $update_shipping = (isset ($form['update_shipping'])) ? $form['update_shipping'] : 0;
        $update_tax = (isset ($form['update_tax'])) ? $form['update_tax'] : 0;
        $cart = new Cart($user->getId(), $this->user_type);
        $cart->save(array(
            'order_type' => (int) $this->order_type,
            'facility_id' => $this->facility_id)
        );

        # Make sure these are set for acurate pricing
        $this->setBPI();
        $cart->setBPI($this->bpi);
        $cart->setCPGK($this->cpgk);
        $cart->LoadCP();

        # UpdateItems handles adding new, updating quantity, and removing items
        if (isset ($form['prod_ids']))
        {
            # Check each product to Make sure its a valid item
            foreach ($form['prod_ids'] as $i => $prod_id)
            {
                $qty = isset ($form['quantities'][$i]) ? (int) $form['quantities'][$i] : 0;
                # Remove bad products
                if ($qty > 0 && !$this->InCatalog($prod_id))
                {
                    unset($form['prod_ids'][$i]);
                }
            }

            $res = $cart->UpdateItems($form, $act);
            # May check $res for "false"
            unset($form['act']);
        }

        # Remove cart record if this has no items
        if (count($cart->items) == 0)
        {
            # Update preference _order array
            $reset_args = true;

            $sth = $this->dbh->prepare("DELETE FROM cart WHERE user_id = ? AND user_type = ?");
            $sth->bindValue(1, (int) $user->getId(), PDO::PARAM_INT);
            $sth->bindValue(2, $this->user_type, PDO::PARAM_STR);
            $sth->execute();

            # When Order is EDITING it needs to be removed
            if ($this->order_id)
            {
                # Verify this order status is EDITING
                $sth_check = $this->dbh->query("SELECT status_id FROM orders WHERE id = {$this->order_id}");
                $status_id = $sth_check->fetchColumn();
                if ($status_id == Order::$EDITING)
                {
                    $form['order_id'] = $this->order_id;
                    $this->CancelOrder($form);
                    $reset_args = false;
                }
            }

            if ($reset_args)
            {
                # Current options
                $this->order_id = 0;

                $args = array(
                    'order_id' => 0,
                    'order_type' => $this->order_type,
                    'facility_id' => $this->facility_id,
                    'cat_id' => $this->cat_id,
                    'section' => $this->section);
                $preferences->set($this_app_name, '_order', serialize($args), $_COOKIE['session_id']);
            }
        }
        else # Update the tax and shipping amounts
        {
            if ($update_tax)
                $this->UpdateTax($cart);
            if ($update_shipping)
                $this->UpdateShipping($cart);
        }
    }


    /**
     * Perform a Get Shipping Cost request and update cart record
     *
     * @return float
     */
    protected function UpdateShipping($cart = null)
    {
        global $preferences, $user, $this_app_name;

        $ship_amount = 0;

        # Load new cart information
        #
        if (is_null($cart))
            $cart = new Cart($user->getId(), $this->user_type);

        if (isset ($_REQUEST['address']))
            $cart->copyFromArray($_REQUEST);

        # Create an array of items with weight and dimentions
        $sth = $this->dbh->prepare("SELECT
			d.weight
		FROM product_uom d
		WHERE d.uom = ?
		AND d.code = (SELECT code FROM products WHERE id = ?)");

        $items = array();
        foreach ($cart->items as $item)
        {
            $sth->bindValue(1, $item->getVar('uom'), PDO::PARAM_STR);
            $sth->bindValue(2, $item->getVar('prod_id'), PDO::PARAM_INT);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_ASSOC);

            # Type cast so there are no null values
            $items[] = array(
                'weight' => (float) (($row['weight'] ? $row['weight'] : 0.5) * $item->getVar('quantity')),
                'length' => '',
                'width' => '',
                'height' => ''
            );
        }

        if (count($items))
        {
            $chko_form = @unserialize($preferences->get($this_app_name, '_checkout'));

            # Get most recent ship_method default to Ground if nothing is defined
            $ship_method = "Ground";
            if (isset ($_REQUEST['ship_method']))
                $ship_method = $_REQUEST['ship_method'];
            else if (isset ($chko_form['ship_method']))
                $ship_method = $chko_form['ship_method'];

            # Get most relevant shipping address
            if (isset ($_REQUEST['zip']))
                $address = $this->FillAddress($_REQUEST);
            else if (isset ($chko_form['zip']))
                $address = $this->FillAddress($chko_form);
            else
                $address = $this->FillAddress($this->customer);

            # Add CustId to address
            $address['cust_id'] = $this->customer->getCustId();

            # Perform tax calculations
            #
            # This will use the default carrier (FEDEX)
            $ship = new ShippingService();
            if ($ship->RateRequest($this->order_id, $ship_method, $address, $items))
            {
                # Successful query
                $amount = $ship->GetAmount();
                foreach ($amount as $price_ary)
                {
                    if ($price_ary['desc'] == $ship_method)
                    {
                        $ship_amount = $price_ary['price'];
                        $sth = $this->dbh->prepare("UPDATE cart SET shipping_amount = ?
						WHERE user_id = ? AND user_type = ?");
                        $sth->bindValue(1, $ship_amount, ($ship_amount) ? PDO::PARAM_STR : PDO::PARAM_NULL);
                        $sth->bindValue(2, (int) $user->getId(), PDO::PARAM_INT);
                        $sth->bindValue(3, $this->user_type, PDO::PARAM_STR);
                        $sth->execute();
                    }
                }
            }
            else
                $this->msg .= "<div class='error_msg'>" . $ship->GetMsg() . "</div>";
        }

        return $ship_amount;
    }

    /**
     * Perform a Get Tax request and update cart record
     *
     * @return float
     */
    protected function UpdateTax($cart = null)
    {
        global $user, $this_app_name, $preferences;

        $tax_amount = 0;

        # Load new cart information
        #
        if (is_null($cart))
            $cart = new Cart($user->getId(), $this->user_type);

        if (isset ($_REQUEST['address']))
            $cart->copyFromArray($_REQUEST);

        # Create an array of items with pricing information
        $items = $cart->items;

        if (count($items) > 0)
        {
            # Perform tax calculations
            #
            $chko_form = @unserialize($preferences->get($this_app_name, '_checkout'));

            # Get most relevant shipping address
            if (isset ($_REQUEST['zip']))
                $address = $this->FillAddress($_REQUEST);
            else if (isset ($chko_form['zip']))
                $address = $this->FillAddress($chko_form);
            else
                $address = $this->FillAddress($this->customer);

            # Add CustId to address
            $address['cust_id'] = $this->customer->getCustId();


            $tax = new TaxService();

            if ($tax->TaxRequest($address, $items, true))
            {
                # Successful query
                $tax_amount = str_replace(",", "", $tax->GetTax());

                $sth = $this->dbh->prepare("UPDATE cart SET tax_amount = ?
				WHERE user_id = ? AND user_type = ?");
                $sth->bindValue(1, $tax_amount, ($tax_amount) ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $sth->bindValue(2, (int) $user->getId(), PDO::PARAM_INT);
                $sth->bindValue(3, $this->user_type, PDO::PARAM_STR);
                $sth->execute();
            }
            else
                $this->msg .= "<div class='error_msg'>" . $tax->GetMsg() . "</div>";
        }

        return $tax_amount;
    }

    /**
     * Build checkbox input for the upsell option
     *
     * @param bool
     * @param index
     * @param bool
     */
    protected function UpsellCheckBox($upsell, $index)
    {
        if ($this->limit_user)
            $upsell_cb = "&nbsp;";
        else
        {
            $chk_ups = ($upsell) ? "checked" : "";

            $upsell_cb = "<input type='checkbox' name='ups_cb' $chk_ups
				onclick=\"document.wsorder['upsell[$index]'].value = (this.checked) ? 1 : 0;\"/>";
        }

        return $upsell_cb;
    }

    /**
     * Find a good product to upsale to the user
     *
     * @return array
     */
    public function UpsaleProduct()
    {
        $product['prod_id'] = 0;

        if ($this->facility_id)
        {
            $sql = "SELECT
				p.id as prod_id,
				i.count
			FROM products p
			INNER JOIN product_pricing pr ON p.id = pr.id
			INNER JOIN product_category_join j ON p.id = j.prod_id
			INNER JOIN product_catalog_category c ON j.cat_id = c.category_id AND c.catalog_id = ?
			INNER JOIN (
				SELECT i.prod_id, count(i.prod_id) as count
				FROM order_item i
				INNER JOIN orders o ON i.order_id = o.id
				WHERE o.facility_id = ?
				GROUP BY i.prod_id
			) i on p.id = i.prod_id
			WHERE p.active = true
			ORDER BY random() -- i.count DESC
			LIMIT 1";
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, $this->catalog, PDO::PARAM_INT);
            $sth->bindValue(2, $this->facility_id, PDO::PARAM_INT);
            $sth->execute();
            $product = $sth->fetch(PDO::FETCH_ASSOC);
        }

        return $product;
    }

    /**
     * Sends the email(s).
     *
     * @param Order $order
     */
    protected function sendMail($order, $existingOrder = false, $special_code = null)
    {
        global $dbh;
        global $user;

        # Find emails addresses for Customer Support
        $cs_emails = $this->GetEmailAddressCTC();
        $notes = $order->getVar('comments');

        $additional_note = ($existingOrder) ? "Updated " : "Created new ";

        $mail_subject = $additional_note . "Order #: " . $order->getId() . " for " . $this->customer->getCustId() . " requires special handling by Service or QC.";
        $full_body = <<<END
        Dear Team,
        <br /><br />
        Please review order notes for action needed to fulfill and ship this order. If you have questions, please reach out to customer support for clarification.
        <br /><br />
        Order Notes: {$notes}
	    <br /><br />
        Thank you.
        <br />
        Customer Support Team
END;

        //special code handling
        if ($special_code)
        {
            $search = array(":order_id", ":order_notes", ":cust_id");
            $replace = array($order->getId(), $notes, $this->customer->getCustId());
            $special_email = $this->GetEmailContext($special_code);
            if (!empty ($special_email['subject']))
            {
                $mail_subject = $special_email['subject'];
                $mail_subject = $additional_note . str_replace($search, $replace, $mail_subject);
            }
            if (!empty ($special_email['body']))
            {
                $full_body = $special_email['body'];
                $full_body = str_replace($search, $replace, $full_body);
            }
        }

        ## Create a mailer
        # use SendMail transport
        # character set to UTF-8
        $mail = new PHPMailer(true);
        $mail->IsSendmail();
        $mail->IsHTML(true);
        $mail->CharSet = 'utf-8';
        $mail->Subject = $mail_subject;
        $mail->Body = $full_body;

        Email::AddTo($mail, $cs_emails);
        Email::AddFrom($mail, $user->getEmail());

        $mail->Send();
    }

    function GetCodesToCheck()
    {
        $dbh = DataStor::getHandle();
        $rtn = array();
        $sth = $dbh->query("SELECT DISTINCT(p.code) FROM products p
			INNER JOIN product_detail pd ON pd.prod_id = p.id
			WHERE pd.special = true");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            array_push($rtn, $row['code']);
        }
        return $rtn;
    }

    function GetEmailContext($prod_code)
    {
        $dbh = DataStor::getHandle();
        $rtn = array();
        $sth = $dbh->query("SELECT pd.email_subject, pd.email_body FROM product_detail pd
			INNER JOIN products p ON pd.prod_id = p.id
			WHERE p.code = '{$prod_code}' LIMIT 1");

        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $rtn['subject'] = $row['email_subject'];
            $rtn['body'] = $row['email_body'];
        }
        return $rtn;
    }

    function GetEmailAddressCTC()
    {
        $dbh = DataStor::getHandle();
        $rtn = array();
        $sth = $dbh->query("SELECT val FROM config WHERE name='dynamic.orderAckEmails'");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $emailaddresses = $row['val'];
            $Addresses = explode(',', $emailaddresses);
            foreach ($Addresses as $key)
            {
                array_push($rtn, $key);
            }
        }
        return $rtn;
    }
}
?>