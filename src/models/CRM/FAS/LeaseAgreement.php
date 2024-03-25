<?php
/**
 * Lease Agreement class definition
 *
 * @package Freedom
 * @author Aron Vargas
 */
class LeaseAgreement extends BaseClass {
    protected $db_table = 'lease_agreement';	# string
    protected $p_key = 'lease_id';				# string

    public $lease_id;						#int
    public $nfif_id;							#int
    public $facility_id;		 				#int
    public $cust_id;		 					#str
    public $lease_amount;					#float
    public $master_agreement = false;			#bool
    public $addon_amendment = false;			#bool
    public $contract_version;				#int
    public $version_text = '';				#sting
    public $start_date = 0;				#int
    public $billing_start_date = 0;				#int
    public $effective_date = 0;				#int
    public $template_file;					#string
    public $amendments;						#string
    public $multi_year = true;				#bool
    public $period = 1;						#int
    public $renewal_period = 1;				#int
    public $cancellable = true;				#bool
    public $payment_term_id;				#int
    public $length_term_id;					#int
    public $maintenance_agreement_id;		#int
    public $warranty_option_id;				#int
    public $contact_id;						#int
    public $signatory_contact;				#int
    public $signatory;						#object
    public $show_signature = false;			#boolean
    public $signature_date;					# string
    public $pricing_method;					#string
    public $interest_rate = 1.5;			# double precision DEFAULT 1.5,
    public $termination_notice_period_id;	# integer,
    public $confidentiality_period_id;		# integer,
    public $non_solicitation_period_id;		# integer,
    public $jurisdiction_state = 'NV';		# character varying(32)
    public $discount_method;				# integer
    public $discount_amount;				# double

    public $length_term;			#string
    public $payment_term;		#string
    public $facility_name;		#string
    public $short_name;			#string
    public $corporate_parent;	#string
    public $region_name;			#string
    public $address;				#string
    public $address2;			#string
    public $city;				#string
    public $state;				#string
    public $zip;					#string
    public $phone;				#string
    public $fax;					#string
    public $email;				#string
    public $visit_frequency = 4;
    public $remote_services = 0;				# integer
    public $shipping_price = '250';
    public $starter_kit_price = '350';
    public $lease_pricing;
    public $lease_service_pricing;
    public $lease_addon_pricing;
    public $lease_warranty_pricing;
    public $lease_maintenance_pricing;
    public $lease_suite_pricing;
    public $lease_suite_service_pricing;
    public $lease_suite_warranty_pricing;
    public $lease_suite_maintenance_pricing;

    static public $ZSHIPPING_PROD_ID = 569;

    # Standard Pricing Vars
    static public $DEFAULT_EQUIP_COST = 350;
    static public $PER_VISIT_COST = 50;

    # Signature
    static public $FINAL_APPROVAL_GROUP = 25;
    static public $DefaultRepID = 158;
    static public $CompanyPresidentID = 1322;
    static public $CompanyCFOID = 158;

    # Define default template files
    static public $TEMPLATE_CUSTID = 'DEAFULT002';

    static public $MASTER_CANCELLABLE = 68; # CON_0300-F97L
    static public $MASTER_NON_CANCELLABLE = 67; # CON_0300-F117L
    static public $INDIVIDUAL_CANCELLABLE = 81; # CON_0300-F96Lr8
    static public $INDIVIDUAL_NON_CANCELLABLE = 82; # CON_0300-F118Lr5

    static public $SYNCHRONY_BASE_MODELS = array('300800A');
    static public $SYNCHRONY_COMPONENT_MODELS = array('300200A-1', '44144');

    static public $option_termination_notice = 'termination_notice';
    static public $option_ip_confidentiality = 'ip_confidentiality';
    static public $option_non_solicitation = 'non_solicitation';

    static public $DISCOUNT_METHOD_NONE = 0;
    static public $DISCOUNT_METHOD_AMOUNT = 1;
    static public $DISCOUNT_METHOD_PERCENT = 2;
    static public $DISCOUNT_METHOD_NORMAL = 3;

    static public $SIXTYDAYS_SIXMONTHS_TERM_ID = 13;

    static public $ADD_REMOTE = 26;
    static public $ADD_LITE = 25;
    static public $ADD_ACCELERATED = 24;
    static public $ADD_SILVER = 27;
    static public $ADD_GOLD = 28;
    static public $ADD_PLATINUM = 29;
    static public $ADD_SILVER_SYNCH = 46;

    static public $VD_REMOTE = 13;
    static public $VD_LITE = 12;
    static public $VD_ACCELERATED = 11;
    static public $VD_SILVER = 18;
    static public $VD_GOLD = 19;
    static public $VD_PLATINUM = 20;
    static public $VD_SILVER_SYNCH = 47;

    static public $VP_REMOTE = 17;
    static public $VP_LITE = 16;
    static public $VP_ACCELERATED = 15;
    static public $VP_SILVER = 21;
    static public $VP_GOLD = 22;
    static public $VP_PLATINUM = 23;
    static public $VP_SILVER_SYNCH = 48;

    /**
     * Create Contract Document instance
     */
    public function __construct($lease_id)
    {
        $this->dbh = DataStor::getHandle();

        $this->lease_id = $lease_id;

        $this->load();
    }

    /**
     * Insert history record
     */
    public function AddHistory()
    {
        global $user;

        $dbh = DataStor::GetHandle();

        $user_id = ($user) ? (int) $user->GetId() : 1;
        $tstamp = time();
        $change_info = (isset ($_REQUEST['change_info'])) ? trim($_REQUEST['change_info']) : "";
        $change_info = $dbh->quote($change_info);
        $src = (isset ($_REQUEST['src'])) ? trim($_REQUEST['src']) : "salesmanagement";
        $src = $dbh->quote($src);

        $sth = $dbh->prepare("INSERT INTO lease_agreement_history
		(
			user_id, change_info, src, lease_id, cust_id, nfif_id,
			master_agreement, addon_amendment, contract_version, lease_amount,
			effective_date, visit_frequency, non_cancellable, period, renewal_period,
			amendments, cancellable, shipping_price, starter_kit_price, contact_id,
			signatory_contact, payment_term_id, length_term_id, maintenance_agreement_id,
			warranty_option_id, pricing_method, interest_rate, termination_notice_period_id,
			confidentiality_period_id, non_solicitation_period_id, jurisdiction_state,
			discount_method, discount_amount
		)
		SELECT
			$user_id, $change_info, $src,
			lease_id, cust_id, nfif_id,
			master_agreement, addon_amendment, contract_version, lease_amount,
			effective_date, visit_frequency, non_cancellable, period, renewal_period,
			amendments, cancellable, shipping_price, starter_kit_price, contact_id,
			signatory_contact, payment_term_id, length_term_id, maintenance_agreement_id,
			warranty_option_id, pricing_method, interest_rate, termination_notice_period_id,
			confidentiality_period_id, non_solicitation_period_id, jurisdiction_state,
			discount_method, discount_amount
		FROM lease_agreement
		WHERE lease_id = ?");
        $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Build html for the included equipment
     *
     * @param array
     * @return string
     */
    static public function BuildEquipmentList($equipment)
    {
        // Standard list
        $html = "";

        if ($equipment)
        {
            $html = "";
            foreach ($equipment as $dev)
            {
                if (is_object($dev))
                {
                    $name = (empty ($dev->registered_name)) ? $dev->description : html_entity_decode($dev->registered_name);
                }
                else
                {
                    $name = (empty ($dev['registered_name'])) ? $dev['description'] : html_entity_decode($dev['registered_name']);
                }

                $html .= "<div>{$name}</div>\n";
            }
        }

        return $html;
    }

    /**
     * Build html table rows for the included equipment
     *
     * @param array
     * @return string
     */
    static public function BuildEquipmentRows($equipment)
    {
        $html = "";
        $blanks = 7; // Require at least 7 rows

        if ($equipment)
        {
            $html = "";

            foreach ($equipment as $dev)
            {
                if (is_object($dev))
                {
                    $name = (empty ($dev->registered_name)) ? $dev->description : html_entity_decode($dev->registered_name);
                    $qty = (isset ($dev->count)) ? $dev->count : 1;
                }
                else
                {
                    $name = (empty ($dev['registered_name'])) ? $dev['description'] : html_entity_decode($dev['registered_name']);
                    $qty = (isset ($dev['count'])) ? $dev['count'] : 1;
                }

                $html .= "<tr valign=top>
					<td class='f8' width=229>$name</td>
					<td class='f8' width=46 align='center'>$qty</td>
				</tr>\n";

                $blanks--;
            }
        }

        // Filll table with blanks if equipment rows are less than 7
        for ($i = 0; $i < $blanks; $i++)
        {
            $html .= "<tr valign=top><td width=229>&nbsp;</td><td width=46>&nbsp;</td></tr>\n";
        }

        return $html;
    }

    /**
     * Load record from Database
     */
    public function load()
    {
        $lease_id = $this->lease_id;

        if ($this->lease_id >= 0)
        {
            $sth = $this->dbh->prepare("SELECT
				l.lease_id,
				l.cust_id,
				l.nfif_id,
				l.master_agreement,
				l.addon_amendment,
				l.contract_version,
				l.cancellable,
				l.lease_amount,
				l.effective_date,
				l.visit_frequency,
				l.period,
				l.renewal_period,
				l.amendments,
				l.payment_term_id,
				l.length_term_id,
				l.maintenance_agreement_id,
				l.warranty_option_id,
				l.shipping_price,
				l.starter_kit_price,
				l.signatory_contact,
				l.contact_id,
				l.pricing_method,
				l.interest_rate,
				l.termination_notice_period_id,
			 	l.confidentiality_period_id,
				l.non_solicitation_period_id,
				l.jurisdiction_state,
				l.discount_method,
				l.discount_amount,
				vf.remote_services,
				v.version_text,
				v.template_file,
				c.address1 as address,
				c.address2,
				c.city,
				c.state,
				c.zip,
				c.phone,
				c.fax,
				c.email,
				clt.term as length_term,
				cpt.term_disp as payment_term
			FROM lease_agreement l
			LEFT JOIN contract_version v ON l.contract_version = v.version_id
			LEFT JOIN contact c ON l.contact_id = c.contact_id
			LEFT JOIN contract_length_term clt ON l.length_term_id = clt.id
			LEFT JOIN contract_payment_term cpt ON l.payment_term_id = cpt.id
			LEFT JOIN contract_visit_frequency vf ON l.visit_frequency = vf.id
			WHERE l.lease_id = ?");
            $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->copyFromArray($row);
        }

        if ($this->nfif_id)
        {
            $address_fields = "
				c.address1 as address,
				c.address2,
				c.city,
				c.state,
				c.zip,
				c.phone,
				c.fax,
				c.email,";

            # If contact id set dont overide address information
            if ($this->contact_id)
                $address_fields = "";

            $sth = $this->dbh->prepare("SELECT
				n.facility_id,
				n.legal_name as facility_name,
				n.short_name,
				$address_fields
				f.corporate_parent,
				f.accounting_id,
				g.lastname as region_name
			FROM nfif n
			LEFT JOIN contact c ON n.shipping_contact = c.contact_id
			LEFT JOIN facilities f on n.facility_id = f.id
			LEFT JOIN users u ON f.cpt_id = u.id
			LEFT JOIN v_users_primary_group upg ON u.id = upg.user_id
			LEFT JOIN users g on upg.group_id = g.id
			WHERE n.nfif_id = ?");
            $sth->bindValue(1, $this->nfif_id, PDO::PARAM_INT);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->copyFromArray($row);
        }

        $this->signatory = new Contact($this->signatory_contact);

        $sth = $this->dbh->prepare("SELECT
			lease_type, price
		FROM lease_pricing
		WHERE lease_id = ?");
        $sth->bindValue(1, (int) $lease_id, PDO::PARAM_INT);
        $sth->execute();
        while (list($lease_type, $price) = $sth->fetch(PDO::FETCH_NUM))
        {
            $this->lease_pricing[$lease_type] = $price;
        }

        $this->lease_addon_pricing = $this->GetAddonPricing($lease_id);

        $this->lease_service_pricing = $this->GetServicePricing($lease_id);

        $this->lease_warranty_pricing = $this->GetWarrantyPricing($lease_id);

        $this->lease_maintenance_pricing = $this->GetMaintenancePricing($lease_id);

        //$this->lease_suite_pricing = $this->GetSuitePricing($lease_id);

        //$this->lease_suite_service_pricing = $this->GetSuiteServicePricing($lease_id);

        //$this->lease_suite_warranty_pricing = $this->GetSuiteWarrantyPricing($lease_id);

        //$this->lease_suite_maintenance_pricing = $this->GetSuiteMainenancePricing($lease_id);
    }

    /**
     * Cache options for later
     */
    public function LoadAllOptions()
    {
        $this->LoadOptions('visit_frequency_options');
        $this->LoadOptions('length_term_id_options');
        $this->LoadOptions('payment_term_id_options');
        $this->LoadOptions('period_id_options');
    }

    /**
     * @param string
     */
    public function LoadOptions($list)
    {
        if ($list == 'visit_frequency_options')
        {
            $sth = $this->dbh->prepare('SELECT
				id, description, visit_count, lower(pricing_method) as pricing_method
			FROM contract_visit_frequency
			ORDER BY id');
            $sth->execute();
            $this->visit_frequency_options = $sth->fetchAll(PDO::FETCH_OBJ);
        }
        else if ($list == 'length_term_id_options')
        {
            $sth = $this->dbh->query("SELECT id as value, term as text, active
			FROM contract_length_term
			ORDER BY display_order");
            $this->length_term_id_options = $sth->fetchAll(PDO::FETCH_OBJ);
        }
        else if ($list == 'payment_term_id_options')
        {
            $sth = $this->dbh->query("SELECT id as value, term_disp as text, active
			FROM contract_payment_term
			ORDER BY display_order");
            $this->payment_term_id_options = $sth->fetchAll(PDO::FETCH_OBJ);
        }
        else if ($list == 'period_id_options')
        {
            $sth = $this->dbh->query("SELECT
				option_name,
				period_id as value,
				period_length as text,
				active
			FROM contract_period
			ORDER BY option_name, display_order");
            $this->period_id_options = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Find a default price for the maintenance option
     * @param integer $warranty_option_id
     * @param integer $lease_id
     * @param integer $prod_id
     * @return number
     */
    static public function LookupMaintenancePrice($maintenance_agreement_id, $lease_id, $prod_id)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT price FROM lease_maintenance_pricing WHERE maintenance_agreement_id = ? AND lease_id = ? AND prod_id = ?");
        $sth->bindValue(1, (int) $maintenance_agreement_id, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $lease_id, PDO::PARAM_INT);
        $sth->bindValue(3, (int) $prod_id, PDO::PARAM_INT);
        $sth->execute();
        $price = (float) $sth->fetchColumn();

        return $price;
    }

    /**
     * Find a default price for the maintenance option
     * @param integer $suite_id
     * @param integer $warranty_option_id
     * @param integer $lease_id
     * @param integer $prod_id
     * @return number
     */
    static public function LookupMaintenanceSuitePrice($suite_id, $maintenance_agreement_id, $lease_id, $prod_id)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT coalesce(sp.price, lp.price) as price
		FROM lease_maintenance_pricing lp
		LEFT JOIN lease_suite_maintenance_pricing sp ON sp.suite_id = ?
			AND lp.maintenance_agreement_id = sp.maintenance_agreement_id
			AND lp.lease_id = sp.lease_id
			AND lp.prod_id = sp.prod_id
		WHERE lp.maintenance_agreement_id = ? AND lp.lease_id = ? AND lp.prod_id = ?");
        $sth->bindValue(1, (int) $suite_id, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $maintenance_agreement_id, PDO::PARAM_INT);
        $sth->bindValue(3, (int) $lease_id, PDO::PARAM_INT);
        $sth->bindValue(4, (int) $prod_id, PDO::PARAM_INT);
        $sth->execute();
        $price = (float) $sth->fetchColumn();

        return $price;
    }

    /**
     * Find a default price for the warranty option
     * @param integer $warranty_option_id
     * @param integer $lease_id
     * @param integer $prod_id
     * @return number
     */
    static public function LookupWarrantyPrice($warranty_option_id, $lease_id, $prod_id)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT price
		FROM lease_warranty_pricing
		WHERE warranty_option_id = ? AND lease_id = ? AND prod_id = ?");
        $sth->bindValue(1, (int) $warranty_option_id, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $lease_id, PDO::PARAM_INT);
        $sth->bindValue(3, (int) $prod_id, PDO::PARAM_INT);
        $sth->execute();
        $price = (float) $sth->fetchColumn();

        return $price;
    }

    /**
     * Find a default price for the suite warranty option
     * @param integer $suite_id
     * @param integer $warranty_option_id
     * @param integer $lease_id
     * @param integer $prod_id
     * @return number
     */
    static public function LookupWarrantySuitePrice($suite_id, $warranty_option_id, $lease_id, $prod_id)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT coalesce(sp.price, lp.price) as price
		FROM lease_warranty_pricing lp
		LEFT JOIN lease_suite_warranty_pricing sp ON sp.suite_id = ?
			AND sp.warranty_option_id = lp.warranty_option_id
			AND sp.lease_id = lp.lease_id
			AND sp.prod_id = lp.prod_id
		WHERE lp.warranty_option_id = ? AND lp.lease_id = ? AND lp.prod_id = ?");
        $sth->bindValue(1, (int) $suite_id, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $warranty_option_id, PDO::PARAM_INT);
        $sth->bindValue(3, (int) $lease_id, PDO::PARAM_INT);
        $sth->bindValue(4, (int) $prod_id, PDO::PARAM_INT);
        $sth->execute();
        $price = (float) $sth->fetchColumn();

        return $price;
    }

    /**
     * Udate DB record
     *
     */
    public function save()
    {
        # Bookmark current record / Add history
        if ($this->lease_id)
            $this->AddHistory();

        // Avoid '' as an input for a float.
        if (!$this->lease_amount)
            $this->lease_amount = "0.0";

        # Avoid saving Purchase contracts
        if ($this->IsPurchase())
            return;

        # Validate float fields, Remove non digit and decimal chars
        $this->lease_amount = (float) preg_replace('/[^\d\.]/', '', $this->lease_amount);
        $this->shipping_price = (float) preg_replace('/[^\d\.]/', '', $this->shipping_price);
        $this->starter_kit_price = (float) preg_replace('/[^\d\.]/', '', $this->starter_kit_price);

        # Set input types
        $nfif_id_t = ($this->nfif_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $payment_term_type = ($this->payment_term_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $length_term_type = ($this->length_term_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $warranty_type = ($this->warranty_option_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $maint_type = ($this->maintenance_agreement_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $signatory_type = ($this->signatory_contact) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $pricing_method_type = ($this->pricing_method) ? PDO::PARAM_STR : PDO::PARAM_NULL;
        $interest_rate_type = (empty ($this->interest_rate)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $termination_notice_period_id_type = ($this->termination_notice_period_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $confidentiality_period_id_type = ($this->confidentiality_period_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $non_solicitation_period_id_type = ($this->non_solicitation_period_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $jurisdiction_state_type = (empty ($this->jurisdiction_state)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
        $discount_method_t = (empty ($this->discount_method)) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $discount_amount_t = (empty ($this->discount_amount)) ? PDO::PARAM_NULL : PDO::PARAM_INT;

        if (!is_null($this->lease_id))
        {
            $sth = $this->dbh->prepare("UPDATE lease_agreement
			SET
				cust_id = ?,
				master_agreement = ?,
				addon_amendment = ?,
				contract_version = ?,
				lease_amount = ?,
				effective_date = ?,
				visit_frequency = ?,
				period = ?,
				renewal_period = ?,
				amendments = ?,
				shipping_price = ?,
				starter_kit_price = ?,
				payment_term_id = ?,
				length_term_id = ?,
				maintenance_agreement_id = ?,
				warranty_option_id = ?,
				cancellable = ?,
				signatory_contact = ?,
				pricing_method = ?,
				interest_rate = ?,
				termination_notice_period_id = ?,
				confidentiality_period_id = ?,
				non_solicitation_period_id = ?,
				jurisdiction_state = ?,
				discount_method = ?,
				discount_amount = ?
			WHERE lease_id = ?");
        }
        else
        {
            $sth = $this->dbh->prepare("INSERT INTO lease_agreement
			(cust_id, master_agreement, addon_amendment, contract_version, lease_amount,
			 effective_date, visit_frequency, period, renewal_period, amendments,
			 shipping_price, starter_kit_price,
			 payment_term_id, length_term_id, maintenance_agreement_id,
			 warranty_option_id, cancellable,
			 signatory_contact, pricing_method,
			 interest_rate, termination_notice_period_id,
			 confidentiality_period_id, non_solicitation_period_id, jurisdiction_state,
			 discount_method, discount_amount
			 nfif_id)
			VALUES (?,?,?,?,?, ?,?,?,?,?, ?,?, ?,?,?, ?,?, ?,?, ?,?, ?,?,?, ?,?, ?)");
        }

        $i = 1;
        $sth->bindValue($i++, $this->cust_id, PDO::PARAM_STR);
        $sth->bindValue($i++, (int) $this->master_agreement, PDO::PARAM_BOOL);
        $sth->bindValue($i++, (int) $this->addon_amendment, PDO::PARAM_BOOL);
        $sth->bindValue($i++, (int) $this->contract_version, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->lease_amount, PDO::PARAM_STR);
        $sth->bindValue($i++, (int) $this->effective_date, PDO::PARAM_INT);
        $sth->bindValue($i++, (int) $this->visit_frequency, PDO::PARAM_INT);
        $sth->bindValue($i++, (int) $this->period, PDO::PARAM_INT);
        $sth->bindValue($i++, (int) $this->renewal_period, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->amendments, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->shipping_price, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->starter_kit_price, PDO::PARAM_STR);
        $sth->bindValue($i++, (int) $this->payment_term_id, $payment_term_type);
        $sth->bindValue($i++, (int) $this->length_term_id, $length_term_type);
        $sth->bindValue($i++, (int) $this->maintenance_agreement_id, $maint_type);
        $sth->bindValue($i++, (int) $this->warranty_option_id, $warranty_type);
        $sth->bindValue($i++, (int) $this->cancellable, PDO::PARAM_BOOL);
        $sth->bindValue($i++, $this->signatory_contact, $signatory_type);
        $sth->bindValue($i++, $this->pricing_method, $pricing_method_type);
        $sth->bindValue($i++, (float) $this->interest_rate, $interest_rate_type);
        $sth->bindValue($i++, (int) $this->termination_notice_period_id, $termination_notice_period_id_type);
        $sth->bindValue($i++, (int) $this->confidentiality_period_id, $confidentiality_period_id_type);
        $sth->bindValue($i++, (int) $this->non_solicitation_period_id, $non_solicitation_period_id_type);
        $sth->bindValue($i++, $this->jurisdiction_state, $jurisdiction_state_type);
        $sth->bindValue($i++, $this->discount_method, $discount_method_t);
        $sth->bindValue($i++, $this->discount_amount, $discount_amount_t);

        if (!is_null($this->lease_id))
            $sth->bindValue($i++, $this->lease_id, PDO::PARAM_INT);
        else
            $sth->bindValue($i++, $this->nfif_id, $nfif_id_t);
        $sth->execute();

        if (is_null($this->lease_id))
        {
            $this->lease_id = $this->dbh->lastInsertId('lease_agreement_lease_id_seq');
            $this->SaveContact();
        }

        if (isset ($_REQUEST['lease_price']))
        {
            $this->SaveLeasePricing();
        }

        if (isset ($_REQUEST['lease_service_pricing']))
        {
            $this->SaveServicePricing();
        }

        if (isset ($_REQUEST['lease_addon_pricing']))
        {
            $this->SaveAddonPricing();
        }

        if (isset ($_REQUEST['lease_warranty_pricing']))
        {
            $this->SaveWarrantyPricing();
        }

        if (isset ($_REQUEST['lease_maintenance_pricing']))
        {
            $this->SaveMaintenancePricing();
        }

        if (isset ($_REQUEST['lease_suite_pricing']))
        {
            $this->SaveSuitePricing();
        }

        if (isset ($_REQUEST['lease_suite_service_pricing']))
        {
            $this->SaveSuiteServicePricing();
        }

        if (isset ($_REQUEST['lease_suite_warranty_pricing']))
        {
            $this->SaveSuiteWarrantyPricing();
        }

        if (isset ($_REQUEST['lease_suite_maintenance_pricing']))
        {
            $this->SaveSuiteMaintenancePricing();
        }
    }

    /**
     * Save contact record
     */
    public function SaveContact()
    {
        $contact = new Contact($this->contact_id);
        $contact->SetVar('address1', $this->address);
        $contact->SetVar('address2', $this->address2);
        $contact->SetVar('city', $this->city);
        $contact->SetVar('state', $this->state);
        $contact->SetVar('zip', $this->zip);
        $contact->SetVar('phone', $this->phone);
        $contact->SetVar('fax', $this->fax);
        $contact->SetVar('email', $this->email);

        $contact->Save();

        if (!$this->contact_id)
        {
            $this->change('contact_id', $contact->getVar('contact_id'));
        }
    }

    /**
     * Update lease_addon_pricing records for this lease
     */
    public function SaveAddonPricing()
    {
        $this->dbh->query("DELETE FROM lease_addon_pricing WHERE lease_id = {$this->lease_id}");

        if (is_array($this->lease_addon_pricing))
        {
            $sth = $this->dbh->prepare("INSERT INTO lease_addon_pricing
			(lease_id, prod_id, price, addon_price, volume_price, new_purchase_price, refurb_purchase_price, shipping_cost)
			VALUES (?,?,?,?,?,?,?,?)");
            foreach ($this->lease_addon_pricing as $prod_id => $pricing)
            {
                # Convert to StdClass
                if (is_array($pricing))
                    $pricing = (object) $pricing;

                # Remove anything thats not a digit or period
                $price = preg_replace('/[^-\d\.]/', '', $pricing->price);
                $addon_price = preg_replace('/[^-\d\.]/', '', $pricing->addon_price);
                $volume_price = preg_replace('/[^-\d\.]/', '', $pricing->volume_price);
                $new_purchase_price = preg_replace('/[^-\d\.]/', '', $pricing->new_purchase_price);
                $refurb_purchase_price = preg_replace('/[^-\d\.]/', '', $pricing->refurb_purchase_price);
                $shipping_cost = preg_replace('/[^-\d\.]/', '', $pricing->shipping_cost);

                $price_t = ($price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;
                $addon_price_t = ($addon_price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;
                $volume_price_t = ($volume_price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;
                $new_purchase_price_t = ($new_purchase_price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;
                $refurb_purchase_price_t = ($refurb_purchase_price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;
                $shipping_cost_t = ($shipping_cost == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;

                $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
                $sth->bindValue(2, $prod_id, PDO::PARAM_INT);
                $sth->bindValue(3, $price, $price_t);
                $sth->bindValue(4, $addon_price, $addon_price_t);
                $sth->bindValue(5, $volume_price, $volume_price_t);
                $sth->bindValue(6, $new_purchase_price, $new_purchase_price_t);
                $sth->bindValue(7, $refurb_purchase_price, $refurb_purchase_price_t);
                $sth->bindValue(8, $shipping_cost, $shipping_cost_t);
                $sth->execute();
            }
        }
    }

    /**
     * Update lease_pricing records for this lease
     *
     * @return float
     */
    public function SaveLeasePricing()
    {
        $full_price = 0;

        $this->dbh->query("DELETE FROM lease_pricing WHERE lease_id = {$this->lease_id}");

        if (is_array($this->lease_pricing))
        {
            $sth = $this->dbh->prepare("INSERT INTO lease_pricing (lease_id, lease_type, price) VALUES (?,?,?)");
            foreach ($this->lease_pricing as $lease_type => $price)
            {
                # Remove anything thats not a digit or period
                $price = preg_replace('/[^\d\.]/', '', $price);
                $price_t = ($price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;

                $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
                $sth->bindValue(2, $lease_type, PDO::PARAM_INT);
                $sth->bindValue(3, $price, $price_t);
                $sth->execute();

                if ($lease_type == 4)
                    $full_price = $price;
            }
        }

        return $full_price;
    }

    /**
     * Update lease_service_pricing records for this lease
     */
    public function SaveServicePricing()
    {
        $this->dbh->query("DELETE FROM lease_service_pricing WHERE lease_id = {$this->lease_id}");

        if (is_array($this->lease_service_pricing))
        {
            $sth = $this->dbh->prepare("INSERT INTO lease_service_pricing (lease_id, service_id, price) VALUES (?,?,?)");
            foreach ($this->lease_service_pricing as $id => $pricing)
            {
                # Convert to StdClass
                if (is_array($pricing))
                    $pricing = (object) $pricing;

                # Remove anything thats not a digit or period
                $price = preg_replace('/[^\d\.]/', '', $pricing->price);
                $price_t = ($price == "" || $price == -1) ? PDO::PARAM_NULL : PDO::PARAM_STR;

                $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
                $sth->bindValue(2, $id, PDO::PARAM_INT);
                $sth->bindValue(3, (float) $price, $price_t);
                $sth->execute();
            }
        }
    }

    /**
     * Update lease_suite_maintenance_pricing records for this lease
     */
    public function SaveSuiteMaintenancePricing()
    {
        if (is_array($this->lease_suite_maintenance_pricing))
        {
            $sth = $this->dbh->prepare("INSERT INTO lease_suite_maintenance_pricing
			(lease_id, suite_id, prod_id, maintenance_agreement_id, price)
			VALUES (?,?,?,?,?)");

            foreach ($this->lease_suite_maintenance_pricing as $suite_id => $suite)
            {
                $this->dbh->query("DELETE FROM lease_suite_maintenance_pricing WHERE lease_id = {$this->lease_id} AND suite_id = $suite_id");

                foreach ($suite as $prod_id => $prod)
                {
                    foreach ($prod as $maint_id => $pricing)
                    {
                        # Convert to StdClass
                        if (is_array($pricing))
                            $pricing = (object) $pricing;

                        # Remove anything thats not a digit or period
                        $price = preg_replace('/[^\d\.]/', '', $pricing->price);
                        $price_t = ($price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;

                        $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
                        $sth->bindValue(2, $suite_id, PDO::PARAM_INT);
                        $sth->bindValue(3, $prod_id, PDO::PARAM_INT);
                        $sth->bindValue(4, $maint_id, PDO::PARAM_INT);
                        $sth->bindValue(5, (float) $price, $price_t);
                        $sth->execute();
                    }
                }
            }
        }
    }

    /**
     * Update lease_suite_pricing records for this lease
     */
    public function SaveSuitePricing()
    {
        if (is_array($this->lease_suite_pricing))
        {
            $sth = $this->dbh->prepare("INSERT INTO lease_suite_pricing
			(lease_id, suite_id, prod_id, addon_price, volume_price, package_price, new_purchase_price, refurb_purchase_price, shipping_cost)
			VALUES (?,?,?,?,?,?,?,?,?)");

            foreach ($this->lease_suite_pricing as $suite_id => $suite)
            {
                $this->dbh->query("DELETE FROM lease_suite_pricing WHERE lease_id = {$this->lease_id} AND suite_id = $suite_id");

                foreach ($suite as $prod_id => $pricing)
                {
                    # Convert to StdClass
                    if (is_array($pricing))
                        $pricing = (object) $pricing;

                    # Remove anything thats not a digit or period
                    $addon_price = preg_replace('/[^\d\.]/', '', $pricing->addon_price);
                    $package_price = preg_replace('/[^\d\.]/', '', $pricing->package_price);
                    $volume_price = preg_replace('/[^\d\.]/', '', $pricing->volume_price);
                    $new_purchase_price = preg_replace('/[^\d\.]/', '', $pricing->new_purchase_price);
                    $refurb_purchase_price = preg_replace('/[^\d\.]/', '', $pricing->refurb_purchase_price);
                    $shipping_cost = preg_replace('/[^\d\.]/', '', $pricing->shipping_cost);

                    $addon_price_t = ($addon_price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;
                    $package_price_t = ($package_price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;
                    $volume_price_t = ($volume_price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;
                    $new_purchase_price_t = ($new_purchase_price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;
                    $refurb_purchase_price_t = ($refurb_purchase_price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;
                    $shipping_cost_t = ($shipping_cost == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;

                    $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
                    $sth->bindValue(2, $suite_id, PDO::PARAM_INT);
                    $sth->bindValue(3, $prod_id, PDO::PARAM_INT);
                    $sth->bindValue(4, (float) $addon_price, $addon_price_t);
                    $sth->bindValue(5, (float) $volume_price, $volume_price_t);
                    $sth->bindValue(6, (float) $package_price, $package_price_t);
                    $sth->bindValue(7, (float) $new_purchase_price, $new_purchase_price_t);
                    $sth->bindValue(8, (float) $refurb_purchase_price, $refurb_purchase_price_t);
                    $sth->bindValue(9, (float) $shipping_cost, $shipping_cost_t);
                    $sth->execute();
                }
            }
        }
    }

    /**
     * Update lease_suite_service_pricing records for this lease
     */
    public function SaveSuiteServicePricing()
    {
        if (is_array($this->lease_suite_service_pricing))
        {
            $sth = $this->dbh->prepare("INSERT INTO lease_suite_service_pricing
			(lease_id, suite_id, service_id, price)
			VALUES (?,?,?,?)");

            foreach ($this->lease_suite_service_pricing as $suite_id => $suite)
            {
                $this->dbh->query("DELETE FROM lease_suite_service_pricing WHERE lease_id = {$this->lease_id} AND suite_id = $suite_id");

                foreach ($suite as $service_id => $pricing)
                {
                    # Convert to StdClass
                    if (is_array($pricing))
                        $pricing = (object) $pricing;

                    # Remove anything thats not a digit or period
                    $price = preg_replace('/[^\d\.]/', '', $pricing->price);
                    $price_t = ($price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;

                    $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
                    $sth->bindValue(2, $suite_id, PDO::PARAM_INT);
                    $sth->bindValue(3, $service_id, PDO::PARAM_INT);
                    $sth->bindValue(4, (float) $price, $price_t);
                    $sth->execute();
                }
            }
        }
    }

    /**
     * Update lease_suite_warranty_pricing records for this lease
     */
    public function SaveSuiteWarrantyPricing()
    {
        if (is_array($this->lease_suite_warranty_pricing))
        {
            $sth = $this->dbh->prepare("INSERT INTO lease_suite_warranty_pricing
			(lease_id, suite_id, prod_id, warranty_option_id, price)
			VALUES (?,?,?,?,?)");

            foreach ($this->lease_suite_warranty_pricing as $suite_id => $suite)
            {
                $this->dbh->query("DELETE FROM lease_suite_warranty_pricing WHERE lease_id = {$this->lease_id} AND suite_id = $suite_id");

                foreach ($suite as $prod_id => $prod)
                {
                    foreach ($prod as $warranty_id => $pricing)
                    {
                        # Convert to StdClass
                        if (is_array($pricing))
                            $pricing = (object) $pricing;

                        # Remove anything thats not a digit or period
                        $price = preg_replace('/[^\d\.]/', '', $pricing->price);
                        $price_t = ($price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;

                        $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
                        $sth->bindValue(2, $suite_id, PDO::PARAM_INT);
                        $sth->bindValue(3, $prod_id, PDO::PARAM_INT);
                        $sth->bindValue(4, $warranty_id, PDO::PARAM_INT);
                        $sth->bindValue(5, (float) $price, $price_t);
                        $sth->execute();
                    }
                }
            }
        }
    }

    /**
     * Update lease_warranty_pricing records for this lease
     */
    public function SaveWarrantyPricing()
    {
        $this->dbh->query("DELETE FROM lease_warranty_pricing WHERE lease_id = {$this->lease_id}");

        if (is_array($this->lease_warranty_pricing))
        {
            $sth = $this->dbh->prepare("INSERT INTO lease_warranty_pricing
			(lease_id, warranty_option_id, prod_id, price)
			VALUES (?,?,?,?)");
            foreach ($this->lease_warranty_pricing as $prod_id => $prod)
            {
                foreach ($prod as $warranty_id => $pricing)
                {
                    # Convert to StdClass
                    if (is_array($pricing))
                        $pricing = (object) $pricing;

                    # Remove anything thats not a digit or period
                    $price = preg_replace('/[^\d\.]/', '', $pricing->price);
                    $price_t = ($price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;

                    $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
                    $sth->bindValue(2, $warranty_id, PDO::PARAM_INT);
                    $sth->bindValue(3, $prod_id, PDO::PARAM_INT);
                    $sth->bindValue(4, (float) $price, $price_t);
                    $sth->execute();
                }
            }
        }
    }

    /**
     * Update lease_maintenance_pricing records for this lease
     */
    public function SaveMaintenancePricing()
    {
        $this->dbh->query("DELETE FROM lease_maintenance_pricing WHERE lease_id = {$this->lease_id}");

        if (is_array($this->lease_maintenance_pricing))
        {
            $sth = $this->dbh->prepare("INSERT INTO lease_maintenance_pricing
			(lease_id, maintenance_agreement_id, prod_id, price)
			VALUES (?,?,?,?)");
            foreach ($this->lease_maintenance_pricing as $prod_id => $prod)
            {
                foreach ($prod as $maintenance_id => $pricing)
                {
                    # Convert to StdClass
                    if (is_array($pricing))
                        $pricing = (object) $pricing;

                    # Remove anything thats not a digit or period
                    $price = preg_replace('/[^\d\.]/', '', $pricing->price);
                    $price_t = ($price == "") ? PDO::PARAM_NULL : PDO::PARAM_STR;

                    $sth->bindValue(1, $this->lease_id, PDO::PARAM_INT);
                    $sth->bindValue(2, $maintenance_id, PDO::PARAM_INT);
                    $sth->bindValue(3, $prod_id, PDO::PARAM_INT);
                    $sth->bindValue(4, (float) $price, $price_t);
                    $sth->execute();
                }
            }
        }
    }

    /**
     * Create text to explain discount
     */
    public function SetDiscountText()
    {
        $discounts = NULL;

        $nfif = new NFIF();
        $nfif->setVar('nfif_id', $this->nfif_id);
        $nfif->loadContract();
        $lease_amount = $nfif->contract->GetAmount();
        $ft = $nfif->contract->GetVar('free_trial');

        if ($ft == 30)
        {
            $discount = number_format($lease_amount, 2);
            $discounts[] = "Month One Credit: $discount";
        }
        else if ($ft === 'tp')
        {
            $all_tiers = $nfif->GetPriceTiers();
            if (is_array($all_tiers))
            {
                foreach ($nfif->GetPriceTiers() as $tier)
                {
                    if ($tier->GetVar('amount') > 0)
                    {
                        $discount = number_format($tier->GetDiscount($lease_amount), 2);
                        $discounts[] = "Month {$tier->GetMonthNum()} Credit: \${$discount}";
                    }
                }
            }
        }

        if ($discounts)
            $discounts = " (" . implode(", ", $discounts) . ")<br/>";

        return $discounts;
    }

    /**
     * Changes one field in the database and reloads the object.
     *
     * @param string $field
     * @param mixed $value
     */
    public function change($field, $value)
    {
        # Bookmark current record / Add history
        if ($this->lease_id)
            $this->AddHistory();

        parent::change($field, $value);
    }

    /**
     * When changing a lease from Master to Individual
     * The cust_id field needs to be the accounting_id from
     * the facility record.
     *
     * The facility can be found from the nfif.
     *
     * @param $cust_id string
     *
     */
    static public function CleanLeaseCustID($cust_id)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("SELECT
			l.lease_id, f.accounting_id
		FROM lease_agreement l
		INNER JOIN nfif n ON l.nfif_id = n.nfif_id
		INNER JOIN facilities f ON n.facility_id = f.id
		WHERE l.cust_id = ?");
        $sth->bindValue(1, $cust_id, PDO::PARAM_STR);
        $sth->execute();
        while (list($lease_id, $cust_id) = $sth->fetch(PDO::FETCH_NUM))
        {
            $sth2 = $dbh->prepare("UPDATE lease_agreement SET
				cust_id = ?, master_agreement = false
			WHERE lease_id = ?");
            $sth2->bindValue(1, $cust_id, PDO::PARAM_STR);
            $sth2->bindValue(2, $lease_id, PDO::PARAM_STR);
            $sth2->execute();
        }
    }

    /**
     * Find the user who's signature should be shown
     */
    function CompanyRep()
    {
        $user_id = null;
        $user_list = User::PermGroupMembers(LeaseAgreement::$FINAL_APPROVAL_GROUP);

        if (!empty ($user_list))
        {
            ## Check approved changes for the final approval
            ## Find the user who last approved this nfif
            $dbh = DataStor::getHandle();

            $sth = $dbh->prepare("SELECT a.approved_by, MAX(a.approved_on)
			FROM contract_change_approval a
			INNER JOIN contract_change_log l ON a.change_id = l.change_id
			WHERE a.valid
			AND a.complete
			AND l.nfif_id = ?
			AND a.approved_by IN (" . implode(",", $user_list) . ")
			GROUP BY a.approved_by
			ORDER BY 2 DESC");
            $sth->bindValue(1, $this->nfif_id, PDO::PARAM_STR);
            $sth->execute();
            $row = $sth->fetchAll(PDO::FETCH_OBJ);
            if (isset ($row[0]))
                $user_id = $row[0]->approved_by;
        }

        ## If nothing turned up use the default
        if (empty ($user_id))
            $user_id = LeaseAgreement::$CompanyPresidentID;

        ## Set specific titles for President and CFO
        if ($user_id == self::$CompanyPresidentID)
        {
            $rep = new User($user_id);
            $rep->SetTitle("President");
            $rep->signature_img = "&nbsp;";
            $rep->signature_date = "&nbsp;";

            if ($this->show_signature)
            {
                $rep->signature_img = "<img src=\"http://" . $_SERVER['HTTP_HOST'] . Config::$WEB_PATH . "/images/S_Dixon_LeGrande_Jr-President.JPG\" style='height:1.1em; width:125px;' height=15 width=125 />";
                $rep->signature_date = $this->signature_date;
            }

        }
        else
        {
            $user_id = LeaseAgreement::$DefaultRepID;
            $rep = new User($user_id);
            $rep->SetTitle("CFO and Assistant Treasurer");
            $rep->signature_img = "&nbsp;";
            $rep->signature_date = "&nbsp;";

            if ($this->show_signature)
            {
                $rep->signature_img = "<img src=\"http://" . $_SERVER['HTTP_HOST'] . Config::$WEB_PATH . "/images/AR_signature.jpg\" style='height:1.1em; width:125px;' height=15 width=125 />";
                $rep->signature_date = $this->signature_date;
            }
        }

        return $rep;
    }

    /**
     * Copy array values to object attributes
     *
     * @param $form array
     */
    public function copyFromArray($form = array())
    {
        $original_amount = $this->lease_amount;

        BaseClass::copyFromArray($form);

        if (isset ($form['address1']))
            $this->address = $form['address1'];
        if (isset ($form['legal_name']))
            $this->facility_name = $form['legal_name'];

        if (isset ($form['ship_contact']))
        {
            $this->address = $form['ship_contact']['address1'];
            $this->address2 = $form['ship_contact']['address2'];
            $this->city = $form['ship_contact']['city'];
            $this->state = $form['ship_contact']['state'];
            $this->zip = $form['ship_contact']['zip'];
        }

        if (isset ($form['signatory_addr']))
            $this->signatory->CopyFromArray($form['signatory_addr']);


        # Three methods of passing effective date
        if (isset ($form['install_date']))
        {
            # This in most cases comes from an nfif form
            $this->start_date = $this->ParseUnixTime($form['install_date']);
            $this->effective_date = $this->ParseUnixTime($form['install_date']);
        }
        if (isset ($form['start_date']))
        {
            # This in most cases comes from an nfif form
            $this->start_date = $this->ParseUnixTime($form['start_date']);
            $this->effective_date = $this->ParseUnixTime($form['start_date']);
        }
        if (isset ($form['billing_start_date']))
        {
            # This in most cases comes from an nfif form
            $this->billing_start_date = $this->ParseUnixTime($form['billing_start_date']);
            $this->effective_date = $this->ParseUnixTime($form['billing_start_date']);
        }
        if (isset ($form['effective_date']))
        {
            # Handle integer unix time or date string
            $this->effective_date = $this->ParseUnixTime($form['effective_date']);
        }

        # Lease Amount
        # Dont allow updating of lease_amount from its original value
        if ($this->master_agreement && $original_amount)
            $this->lease_amount = $original_amount;
        else
        {
            # Parse lease_amount string
            if (isset ($form['lease_amount']))
                $this->lease_amount = str_replace(',', '', $form['lease_amount']);
        }

        if (isset ($form['lease_pricing']) && is_array($form['lease_pricing']))
        {
            $this->lease_pricing = null;

            foreach ($form['lease_pricing'] as $type => $price)
                $this->lease_pricing[$type] = $price;
        }

        # Lease pricing
        $this->CopyLeaseAddonPricing($form);
        $this->CopyLeaseWarrantyPricing($form);
        $this->CopyLeaseMaintenancePricing($form);

        # Suite Pricing
        $this->CopyLeaseSuitePricing($form);
        $this->CopyLeaseSuiteServicePricing($form);
        $this->CopyLeaseSuiteWarrantyPricing($form);
        $this->CopyLeaseSuiteMaintenancePricing($form);
    }

    /**
     * Copy the addon pricing information
     *
     * @param array
     * @param boolean
     */
    public function CopyLeaseAddonPricing($form, $update_all = true)
    {
        if (isset ($form['lease_addon_pricing']) && is_array($form['lease_addon_pricing']))
        {
            if ($update_all)
                $this->lease_addon_pricing = null;

            foreach ($form['lease_addon_pricing'] as $prod_id => $pricing)
            {
                if ($update_all)
                {
                    $obj = new StdClass;
                    $obj->prod_id = $prod_id;
                    $obj->price = $pricing['price'];
                    $obj->addon_price = $pricing['addon_price'];
                    $obj->volume_price = $pricing['volume_price'];
                    $obj->new_purchase_price = $pricing['new_purchase_price'];
                    $obj->refurb_purchase_price = $pricing['refurb_purchase_price'];
                    $obj->shipping_cost = $pricing['shipping_cost'];
                    $this->lease_addon_pricing[$prod_id] = $obj;
                }
                else
                {
                    if (isset ($pricing['price']))
                        $this->lease_addon_pricing[$prod_id]['price'] = $pricing['price'];
                    if (isset ($pricing['addon_price']))
                        $this->lease_addon_pricing[$prod_id]['addon_price'] = $pricing['addon_price'];
                    if (isset ($pricing['volume_price']))
                        $this->lease_addon_pricing[$prod_id]['volume_price'] = $pricing['volume_price'];
                    if (isset ($pricing['new_purchase_price']))
                        $this->lease_addon_pricing[$prod_id]['new_purchase_price'] = $pricing['new_purchase_price'];
                    if (isset ($pricing['refurb_purchase_price']))
                        $this->lease_addon_pricing[$prod_id]['refurb_purchase_price'] = $pricing['refurb_purchase_price'];
                    if (isset ($pricing['shipping_cost']))
                        $this->lease_addon_pricing[$prod_id]['shipping_cost'] = $pricing['shipping_cost'];
                }
            }
        }
    }

    /**
     * Copy the suite service pricing information
     *
     * @param array
     * @param boolean
     */
    public function CopyLeaseSuiteMaintenancePricing($form, $update_all = true)
    {
        if (isset ($form['lease_suite_maintenance_pricing']) && is_array($form['lease_suite_maintenance_pricing']))
        {
            if ($update_all)
                $this->lease_suite_maintenance_pricing = null;

            foreach ($form['lease_suite_maintenance_pricing'] as $suite_id => $suite)
            {
                foreach ($suite as $prod_id => $prod)
                {
                    foreach ($prod as $service_id => $pricing)
                    {
                        if ($service_id == 'a')
                            continue;

                        $obj = new StdClass;
                        $obj->suite_id = $suite_id;
                        $obj->prod_id = $prod_id;
                        $obj->maintenance_agreement_id = $service_id;
                        $obj->price = $pricing['price'];

                        $this->lease_suite_maintenance_pricing[$suite_id][$prod_id][$service_id] = $obj;
                    }
                }
            }
        }
    }

    /**
     * Copy the suite pricing information
     *
     * @param array
     * @param boolean
     */
    public function CopyLeaseSuitePricing($form, $update_all = true)
    {
        if (isset ($form['lease_suite_pricing']) && is_array($form['lease_suite_pricing']))
        {
            if ($update_all)
                $this->lease_suite_pricing = null;

            foreach ($form['lease_suite_pricing'] as $suite_id => $suite)
            {
                foreach ($suite as $prod_id => $pricing)
                {
                    if ($update_all)
                    {
                        $obj = new StdClass;
                        $obj->suite_id = $suite_id;
                        $obj->prod_id = $prod_id;
                        $obj->addon_price = $pricing['addon_price'];
                        $obj->volume_price = $pricing['volume_price'];
                        $obj->package_price = $pricing['package_price'];
                        $obj->new_purchase_price = $pricing['new_purchase_price'];
                        $obj->refurb_purchase_price = $pricing['refurb_purchase_price'];
                        $obj->shipping_cost = $pricing['shipping_cost'];

                        $this->lease_suite_pricing[$suite_id][$prod_id] = $obj;
                    }
                    else
                    {
                        if (isset ($pricing['package_price']))
                            $this->lease_suite_pricing[$suite_id][$prod_id]['package_price'] = $pricing['package_price'];
                        if (isset ($pricing['addon_price']))
                            $this->lease_suite_pricing[$suite_id][$prod_id]['addon_price'] = $pricing['addon_price'];
                        if (isset ($pricing['volume_price']))
                            $this->lease_suite_pricing[$suite_id][$prod_id]['volume_price'] = $pricing['volume_price'];
                        if (isset ($pricing['new_purchase_price']))
                            $this->lease_suite_pricing[$suite_id][$prod_id]['new_purchase_price'] = $pricing['new_purchase_price'];
                        if (isset ($pricing['refurb_purchase_price']))
                            $this->lease_suite_pricing[$suite_id][$prod_id]['refurb_purchase_price'] = $pricing['refurb_purchase_price'];
                        if (isset ($pricing['shipping_cost']))
                            $this->lease_suite_pricing[$suite_id][$prod_id]['shipping_cost'] = $pricing['shipping_cost'];
                    }
                }
            }
        }
    }

    /**
     * Copy the suite service pricing information
     *
     * @param array
     * @param boolean
     */
    public function CopyLeaseSuiteServicePricing($form, $update_all = true)
    {
        if (isset ($form['lease_suite_service_pricing']) && is_array($form['lease_suite_service_pricing']))
        {
            if ($update_all)
                $this->lease_suite_service_pricing = null;

            foreach ($form['lease_suite_service_pricing'] as $suite_id => $suite)
            {
                foreach ($suite as $prod_id => $pricing)
                {
                    $obj = new StdClass;
                    $obj->suite_id = $suite_id;
                    $obj->service_id = $prod_id;
                    $obj->price = $pricing['price'];

                    $this->lease_suite_service_pricing[$suite_id][$prod_id] = $obj;
                }
            }
        }
    }

    /**
     * Copy the suite warranty pricing information
     *
     * @param array
     * @param boolean
     */
    public function CopyLeaseSuiteWarrantyPricing($form, $update_all = true)
    {
        if (isset ($form['lease_suite_warranty_pricing']) && is_array($form['lease_suite_warranty_pricing']))
        {
            if ($update_all)
                $this->lease_suite_warranty_pricing = null;

            foreach ($form['lease_suite_warranty_pricing'] as $suite_id => $suite)
            {
                foreach ($suite as $prod_id => $prod)
                {
                    foreach ($prod as $warranty_option_id => $pricing)
                    {
                        if ($warranty_option_id == 'a')
                            continue;

                        $obj = new StdClass;
                        $obj->suite_id = $suite_id;
                        $obj->prod_id = $prod_id;
                        $obj->warranty_option_id = $warranty_option_id;
                        $obj->price = $pricing['price'];

                        $this->lease_suite_warranty_pricing[$suite_id][$prod_id][$warranty_option_id] = $obj;
                    }
                }
            }
        }
    }

    /**
     * Copy the suite pricing information
     *
     * @param array
     * @param boolean
     */
    public function CopyLeaseServicePricing($form, $update_all = true)
    {
        if (isset ($form['lease_service_pricing']) && is_array($form['lease_service_pricing']))
        {
            if ($update_all)
                $this->lease_service_pricing = null;

            foreach ($form['lease_service_pricing'] as $id => $pricing)
            {
                $obj = new StdClass;
                $obj->id = $id;
                $obj->price = ((float) $pricing['price'] < 0 || $pricing['price'] === "") ? null : (float) $pricing['price'];
                $this->lease_service_pricing[$id] = $obj;
            }
        }
    }

    /**
     * Copy the pricing information
     *
     * @param array
     * @param boolean
     */
    public function CopyLeaseMaintenancePricing($form, $update_all = true)
    {
        if (isset ($form['lease_maintenance_pricing']) && is_array($form['lease_maintenance_pricing']))
        {
            if ($update_all)
                $this->lease_maintenance_pricing = null;

            foreach ($form['lease_maintenance_pricing'] as $prod_id => $prod)
            {
                foreach ($prod as $maintenance_id => $pricing)
                {
                    if ($maintenance_id == 'a')
                        continue;

                    $obj = new StdClass;
                    $obj->prod_id = $prod_id;
                    $obj->maintenance_id = $maintenance_id;
                    $obj->price = $pricing['price'];

                    $this->lease_maintenance_pricing[$prod_id][$maintenance_id] = $obj;
                }
            }
        }
    }

    /**
     * Copy the pricing information
     *
     * @param array
     * @param boolean
     */
    public function CopyLeaseWarrantyPricing($form, $update_all = true)
    {
        if (isset ($form['lease_warranty_pricing']) && is_array($form['lease_warranty_pricing']))
        {
            if ($update_all)
                $this->lease_warranty_pricing = null;

            foreach ($form['lease_warranty_pricing'] as $prod_id => $prod)
            {
                foreach ($prod as $warranty_id => $pricing)
                {
                    if ($warranty_id == 'a')
                        continue;

                    $obj = new StdClass;
                    $obj->prod_id = $prod_id;
                    $obj->warranty_id = $warranty_id;
                    $obj->price = $pricing['price'];

                    $this->lease_warranty_pricing[$prod_id][$warranty_id] = $obj;
                }
            }
        }
    }

    /**
     * Find Lease ID from the Customer ID
     *
     * @param string
     * @return int
     */
    function Find($cust_id)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("SELECT
			lease_id
		FROM lease_agreement
		WHERE cust_id = ?");
        $sth->bindValue(1, $cust_id, PDO::PARAM_STR);
        $sth->execute();
        $lease_id = (int) $sth->fetchColumn();

        if (empty ($lease_id) && isset ($_REQUEST['force_match']))
            throw new Exception("No information found for Customer ($cust_id)");

        return $lease_id;
    }

    /**
     * Reverse of copyFromArray()
     *
     * @return array
     */
    public function toArray()
    {
        # Build clean arrays
        $lease_pricing = '';
        if ($this->lease_pricing)
        {
            foreach ($this->lease_pricing as $type => $price)
                $lease_pricing[$type] = $price;
        }

        return array(
            'lease_id' => $this->lease_id,
            'nfif_id' => $this->nfif_id,
            'cust_id' => $this->cust_id,
            'cancellable' => $this->cancellable,
            'effective_date' => $this->effective_date,
            'lease_amount' => $this->lease_amount,
            'master_agreement' => $this->master_agreement,
            'addon_amendment' => $this->addon_amendment,
            'contract_version' => $this->contract_version,
            'version_text' => $this->version_text,
            'template_file' => $this->template_file,
            'amendments' => $this->amendments,
            'facility_id' => $this->facility_id,
            'facility_name' => $this->facility_name,
            'short_name' => $this->short_name,
            'signatory' => $this->signatory->toArray(),
            'signatory_contact' => $this->signatory_contact,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'visit_frequency' => $this->visit_frequency,
            'period' => $this->period,
            'renewal_period' => $this->renewal_period,
            'payment_term_id' => $this->payment_term_id,
            'length_term_id' => $this->length_term_id,
            'termination_notice_period_id' => $this->termination_notice_period_id,
            'interest_rate' => $this->interest_rate,
            'confidentiality_period_id' => $this->confidentiality_period_id,
            'non_solicitation_period_id' => $this->non_solicitation_period_id,
            'jurisdiction_state' => $this->jurisdiction_state,
            'discount_method' => $this->discount_method,
            'discount_amount' => $this->discount_amount,
            'maintenance_agreement_id' => $this->maintenance_agreement_id,
            'warranty_option_id' => $this->warranty_option_id,
            'pricing_method' => $this->pricing_method,
            'starter_kit_price' => $this->starter_kit_price,
            'lease_pricing' => $lease_pricing,
            'lease_addon_pricing' => $this->lease_addon_pricing,
            'lease_maintenance_pricing' => $this->lease_maintenance_pricing,
            'lease_warranty_pricing' => $this->lease_warranty_pricing,
            'lease_suite_pricing' => $this->lease_suite_pricing,
            'lease_suite_service_pricing' => $this->lease_suite_service_pricing,
            'lease_suite_warranty_pricing' => $this->lease_suite_warranty_pricing,
            'lease_suite_maintenance_pricing' => $this->lease_suite_maintenance_pricing,
        );
    }

    /**
     * View only form
     */
    public function showViewForm($form)
    {
        $view_only = true;

        # Set variables used in lease display
        $lease_amount = number_format($this->lease_amount, 2);
        $facility_name = $this->facility_name;
        $short_name = $this->short_name;
        $corporate_parent = $this->corporate_parent;
        $region_name = $this->region_name;
        $address = $this->address;
        $city = $this->city;
        $state = $this->state;
        $zip = $this->zip;
        $phone = $this->phone;
        $fax = $this->fax;
        $email = $this->email;
        $signatory_name = $this->signatory->GetFullName(0, 1);
        $signatory_title = $this->signatory->GetTitle();
        $signatory_address = trim($this->signatory->GetAddress1() . ' ' . $this->signatory->GetAddress2());
        $signatory_city = $this->signatory->GetCity();
        $signatory_state = $this->signatory->GetState();
        $signatory_zip = $this->signatory->GetZip();
        $signatory_phone = $this->signatory->GetPhone();
        $signatory_fax = $this->signatory->GetFax();
        $signatory_email = $this->signatory->GetEmail();

        $IS_FINAL = $this->show_signature;

        $co_rep = $this->CompanyRep();
        $witness_name = $co_rep->GetName();
        $witness_title = $co_rep->GetTitle();
        $signature_img = $co_rep->signature_img;

        $termination = $this->GetPeriodText($this->termination_notice_period_id);
        $ip_confidentiality = $this->GetPeriodText($this->confidentiality_period_id);
        $non_solicitation = $this->GetPeriodText($this->non_solicitation_period_id);

        $discount_txt = "";

        if ($this->length_term == 10)
            $term_duration = "Ten (10) Years";
        else if ($this->length_term == 9)
            $term_duration = "Nine (9) Years";
        else if ($this->length_term == 8)
            $term_duration = "Eight (8) Years";
        else if ($this->length_term == 7)
            $term_duration = "Seven (7) Years";
        else if ($this->length_term == 6)
            $term_duration = "Six (6) Years";
        else if ($this->length_term == 5)
            $term_duration = "Five (5) Years";
        else if ($this->length_term == 4)
            $term_duration = "Four (4) Years";
        else if ($this->length_term == 3)
            $term_duration = "Three (3) Years";
        else if ($this->length_term == 2)
            $term_duration = "Two (2) Years";
        else
            $term_duration = "One (1) Year";

        if ($this->payment_term_id == 1)
            $term_due_txt = "immediate (0)";
        else if ($this->payment_term_id == 2)
            $term_due_txt = "fifteen (15)";
        else if ($this->payment_term_id == 3)
            $term_due_txt = "thirty (30)";
        else if ($this->payment_term_id == 4)
            $term_due_txt = "forty-five (45)";
        else if ($this->payment_term_id == 5)
            $term_due_txt = "sixty (60)";
        else if ($this->payment_term_id == 6)
            $term_due_txt = "ninety (90)";
        else
            $term_due_txt = "thirty (30)";

        $equipment = array();
        $facility_list = "";
        $shipping_price = ($this->shipping_price) ? number_format($this->shipping_price, 2) : '0.00';
        $starter_kit_price = ($this->starter_kit_price) ? number_format($this->starter_kit_price, 2) : '0.00';

        $effective_day = "";
        $effective_month = "";
        $effective_year = date('Y');

        if ($this->effective_date)
        {
            $effective_day = date('jS', $this->effective_date);
            $effective_month = date('F', $this->effective_date);
            $effective_year = date('Y', $this->effective_date);
        }

        $start_date = ($this->multi_year && $this->start_date) ? date('m/d/Y', $this->start_date) : "T.B.D";
        $billing_start_date = ($this->multi_year && $this->billing_start_date) ? date('m/d/Y', $this->billing_start_date) : "T.B.D.";
        $effective_date = ($this->multi_year && $this->effective_date) ? date('m/d/Y', $this->effective_date) : "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

        if ($this->visit_frequency)
            $visits_per_year = $this->visit_frequency;
        else
            $visits_per_year = "NA";

        $visit_frequency = $this->visit_frequency;
        $pricing_method = $this->pricing_method;


        $period = $this->period;
        $renewal_period = $this->renewal_period;

        if ($this->nfif_id)
        {
            $sth = $this->dbh->prepare("SELECT
				d.quantity, d.serial_num, p.code, p.name, d.price, m.description, m.registered_name, d.combo_id, d.parent_item_num
			FROM nfif_detail d
			INNER JOIN products p ON d.prod_id = p.id
			INNER JOIN equipment_models m on p.code = m.model
			WHERE d.nfif_id = ?
			ORDER BY d.item_num");
            $sth->bindValue(1, $this->nfif_id, PDO::PARAM_INT);
            $sth->execute();
            while ($device = $sth->fetch(PDO::FETCH_ASSOC))
                $equipment[] = $device;

            $discount_txt = $this->SetDiscountText();
        }

        $facility_list = "";
        if ($this->master_agreement)
        {
            $sth = $this->dbh->prepare("SELECT
				f.facility_name, f.accounting_id
			FROM facilities f
			WHERE f.corporate_parent = ?
			AND f.cancelled = false
			ORDER BY facility_name");
            $sth->bindValue(1, $this->cust_id, PDO::PARAM_INT);
            $sth->execute();
            while ($fac = $sth->fetch(PDO::FETCH_ASSOC))
                $facility_list .= "{$fac['facility_name']} ({$fac['accounting_id']})<br/>\n";
        }

        $cust_id = $this->cust_id;

        $amendments = htmlentities($this->amendments, ENT_QUOTES);

        include $this->getTemplateFile();
    }

    /**
     * Build the length term option list
     *
     * @param integer
     * @return string
     */
    public function GetLengthTermList($length_term_id)
    {
        if (!isset ($this->length_term_id_options))
            $this->LoadOptions('length_term_id_options');

        $options = "";
        foreach ($this->length_term_id_options as $option)
        {
            $sel = ($length_term_id == $option->value) ? "selected" : "";
            if ($option->active || $length_term_id == $option->value)
                $options .= "<option value='{$option->value}' $sel>{$option->text}</option>\n";
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
     * Build the maintenance aggreement option js array
     *
     * @return string (json)
     */
    public function GetMaintenanceList($selected = null)
    {
        if (is_null($selected))
            $selected = $this->maintenance_agreement_id;

        $options = "";
        $sth = $this->dbh->query("SELECT id as value, \"name\" || ' ' || term_interval as text, active
		FROM maintenance_agreement
		ORDER BY display_order");
        while ($option = $sth->fetch(PDO::FETCH_ASSOC))
        {
            if ($option['value'] == $selected || $option['active'])
            {
                $sel = ($option['value'] == $selected) ? "selected" : "";
                $options .= "<option value='{$option['value']}' $sel>{$option['text']}</option>\n";
            }
        }

        return $options;
    }

    /**
     * Build the payment term option list
     *
     * @param integer
     * @return string
     */
    public function GetPaymentTermList($term_id)
    {
        if (!isset ($this->payment_term_id_options))
            $this->LoadOptions('payment_term_id_options');

        $options = "";
        $sth = $this->dbh->query("SELECT id as value, term_disp as text, active
		FROM contract_payment_term
		ORDER BY display_order");
        foreach ($this->payment_term_id_options as $option)
        {
            if ($option->active || $term_id == $option->value)
            {
                $sel = ($term_id == $option->value) ? "selected" : "";
                $options .= "<option value='{$option->value}' $sel>{$option->text}</option>\n";
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
        if (!isset ($this->period_id_options))
            $this->LoadOptions('period_id_options');

        $options = "";
        foreach ($this->period_id_options as $option)
        {
            if ($option->option_name = $option_name)
            {
                if ($option->active || $period_id == $option->value)
                {
                    $sel = ($period_id == $option->value) ? "selected" : "";
                    $options .= "<option value='{$option->value}' $sel>{$option->text}</option>\n";
                }
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
            array('value' => 'vp', 'text' => 'Volume Pricing')
        );
        foreach ($options as $option)
        {
            $sel = ($option['value'] == $selected) ? "selected" : "";
            $list .= "<option id='pricing_method_{$option['value']}' name='pricing_method' value='{$option['value']}' $sel />{$option['text']}</option>\n";
        }

        return $list;
    }

    /**
     * Query DB for the template from the contract version
     *
     * @return string
     */
    public function getTemplateFile()
    {
        # Set the contract version
        if ($this->master_agreement)
        {
            if ($this->cancellable)
                $contract_version = LeaseAgreement::$MASTER_CANCELLABLE;
            else
                $contract_version = LeaseAgreement::$MASTER_NON_CANCELLABLE;
        }
        else
        {
            if ($this->cancellable)
                $contract_version = LeaseAgreement::$INDIVIDUAL_CANCELLABLE;
            else
                $contract_version = LeaseAgreement::$INDIVIDUAL_NON_CANCELLABLE;
        }

        # Lookup based on version
        $sth = $this->dbh->prepare("SELECT template_file FROM contract_version WHERE version_id = ?");
        $sth->execute(array($contract_version));
        $template = $sth->fetchColumn();

        return $template;
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
     * Get formated prefilled signature portion of the lease
     */
    public function getSignatureLines()
    {
        $facility_name = ($this->facility_name) ? htmlentities($this->facility_name, ENT_QUOTES) : "";

        $phone = "(&nbsp;&nbsp;&nbsp;) &nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;&nbsp;";
        if ($this->signatory->GetPhone())
        {
            $phone = preg_replace('/\D/', '', $this->signatory->GetPhone());
            $phone = "(" . substr($phone, 0, 3) . ") " . substr($phone, 3, 3) . "-" . substr($phone, 6);
        }

        $fax = "(&nbsp;&nbsp;&nbsp;) &nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;&nbsp;";
        if ($this->signatory->GetFax())
        {
            $fax = preg_replace('/\D/', '', $this->signatory->GetFax());
            $fax = "(" . substr($fax, 0, 3) . ") " . substr($fax, 3, 3) . "-" . substr($fax, 6);
        }

        $address = ($this->signatory->GetAddress1()) ? $this->signatory->GetAddress1() : "";
        if ($this->signatory->GetAddress2())
            $address .= ", " . $this->signatory->GetAddress2();

        $effective_date = ($this->effective_date) ? date('m/d/Y', $this->effective_date) : '';

        $company_name = Config::$COMPANY_NAME;

        return "
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<b>IN WITNESS WHEREOF</b>, the Parties have executed<br style='clear: right;'/>
this Lease as of the date identified below:<br style='clear: right;'/>
<br style='clear: right;'/>
LESSOR: $company_name<br style='clear: right;'/>
<br style='clear: right;'/>
By:_________________________________<br style='clear: right;'/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Signature<br style='clear: right;'/>
Name: Richard Taylor<br style='clear: right;'/>
Title: CSO<br style='clear: right;'/>
Address: 4999 Aircenter Circle Ste 103<br style='clear: right;'/>
City: Reno, State: Nevada<br style='clear: right;'/>
Phone: (775) 685-4000<br style='clear: right;'/>
Fax: (775) 685-4013<br style='clear: right;'/>
E-Mail: leasing@acplus.com<br style='clear: right;'/>
Date Signed:<br style='clear: right;'/>
<br style='clear: right;'/>
<br style='clear: right;'/>
<br style='clear: right;'/>
LESSEE: {$facility_name}<br style='clear: right;'/>
<br style='clear: right;'/>
By:_________________________________<br style='clear: right;'/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Signature<br style='clear: right;'/>
Name: {$this->signatory->GetFullName(0, 1)}<br style='clear: right;'/>
Title: {$this->signatory->GetTitle()}<br style='clear: right;'/>
Address: $address<br style='clear: right;'/>
City: {$this->signatory->GetCity()}, State: {$this->signatory->GetState()} <br style='clear: right;'/>
Phone: $phone<br style='clear: right;'/>
Fax: $fax<br style='clear: right;'/>
E-Mail: {$this->signatory->GetEmail()}
Date Signed:<br style='clear: right;'/>
";
    }

    /**
     * Find image tag for the rep
     * @param User $co_rep
     * @return string
     */
    public function GetSignatureImg($co_rep)
    {
        $img = "&nbsp;";

        if ($this->show_signature)
        {
            if ($co_rep && $co_rep->getId() == LeaseAgreement::$DefaultRepID)
                $img = "<img src=\"http://" . $_SERVER['HTTP_HOST'] . Config::$WEB_PATH . "/images/" . LeaseAgreement::$REP_SIGNATURE . "\" height='15' width='125' />";
        }

        return $img;
    }

    /**
     * Build an array of product pricing detail
     *
     * @param integer
     * @return array
     */
    static public function GetAddonPricing($lease_id)
    {
        $pricing = array();
        $dbh = DataStor::getHandle();

        $sql = "SELECT
			p.id as prod_id,
			coalesce(e.description, p.name) as prod_name,
			p.code,
			lp.price,
			lp.addon_price,
			lp.volume_price,
			lp.shipping_cost,
			lp.new_purchase_price,
			lp.refurb_purchase_price
		FROM products p
		LEFT JOIN equipment_models e on p.code = e.model
		LEFT JOIN lease_addon_pricing lp ON p.id= lp.prod_id and lp.lease_id = ?
		WHERE
			p.code IN (SELECT code FROM service_item_to_product WHERE active = true)
			OR
			p.id IN (SELECT prod_id FROM install_suite_product)
		ORDER BY e.display_order, p.name";
        $sth = $dbh->prepare($sql);
        $sth->BindValue(1, $lease_id, PDO::PARAM_INT);
        $sth->Execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $price = is_null($row['price']) ? "" : number_format($row['price'], 2);
            $addon_price = is_null($row['addon_price']) ? "" : number_format($row['addon_price'], 2);
            $volume_price = is_null($row['volume_price']) ? "" : number_format($row['volume_price'], 2);
            $new_purchase_price = is_null($row['new_purchase_price']) ? "" : number_format($row['new_purchase_price'], 2);
            $refurb_purchase_price = is_null($row['refurb_purchase_price']) ? "" : number_format($row['refurb_purchase_price'], 2);
            $shipping_cost = is_null($row['shipping_cost']) ? "" : number_format($row['shipping_cost'], 2);

            $pricing[$row['prod_id']] = array(
                'code' => $row['code'],
                'prod_name' => $row['prod_name'],
                'price' => $price,
                'addon_price' => $addon_price,
                'volume_price' => $volume_price,
                'new_purchase_price' => $new_purchase_price,
                'refurb_purchase_price' => $refurb_purchase_price,
                'shipping_cost' => $shipping_cost);
        }

        return $pricing;
    }

    /**
     * Build an array of pricing detail
     *
     * @param integer
     * @param integer
     * @param integer
     * @return array
     */
    static public function GetSuiteMaintenancePricing($lease_id, $suite_id = null, $limit = false)
    {
        $pricing = array();
        $dbh = DataStor::getHandle();

        $suite_clause = "";
        if (!empty ($suite_id))
            $suite_clause = "AND sp.suite_id = " . (int) $suite_id;

        # Limit the list to this CP/lease
        $JOIN = ($limit) ? "INNER JOIN" : "LEFT JOIN";

        $sql = "SELECT
			m.id,
			m.name,
			m.term_interval,
			m.charge_monthly,
			p.id as prod_id,
			p.model,
			p.description,
			sp.suite_id,
			coalesce(sp.price, lp.price) as price
		FROM maintenance_agreement m
		INNER JOIN (
			SELECT p.id, e.model, e.description, e.active
			FROM products p
			INNER JOIN equipment_models e on p.code = e.model
			WHERE e.type_id = 1 AND is_test_equipment = false
		) p ON true
		$JOIN lease_suite_maintenance_pricing sp ON m.id = sp.maintenance_agreement_id AND p.id = sp.prod_id AND sp.lease_id = ?
		LEFT JOIN lease_maintenance_pricing lp ON m.id = lp.maintenance_agreement_id AND p.id = lp.prod_id AND lp.lease_id = ?
		WHERE m.active AND (p.active OR sp.prod_id IS NOT NULL) $suite_clause
		ORDER BY p.description, m.display_order";
        $sth = $dbh->prepare($sql);
        $sth->BindValue(1, $lease_id, PDO::PARAM_INT);
        $sth->BindValue(2, $lease_id, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $price = is_null($row['price']) ? "" : number_format($row['price'], 2);
            $pricing[$row['suite_id']][$row['prod_id']][$row['id']] = array(
                'name' => $row['name'],
                'term_interval' => $row['term_interval'],
                'model' => $row['model'],
                'model_name' => $row['description'],
                'charge_monthly' => $row['charge_monthly'],
                'price' => $price
            );
        }

        return $pricing;
    }

    /**
     * Build an array of product pricing detail
     *
     * @param integer
     * @param integer
     * @param integer
     * @return array
     */
    static public function GetSuitePricing($lease_id, $suite_id = null, $limit = false)
    {
        $pricing = array();
        $dbh = DataStor::getHandle();

        $suite_clause = "";
        if (!empty ($suite_id))
            $suite_clause = "AND i.suite_id = " . (int) $suite_id;

        # Limit the list to this CP/lease
        $JOIN = ($limit) ? "INNER JOIN" : "LEFT JOIN";

        $sql = "SELECT
			i.suite_id,
 			i.description,
			sp.prod_id,
			p.code,
			p.name as product_name,
			lp.addon_price,
			lp.volume_price,
			lp.package_price,
			lp.volume_price,
			lp.new_purchase_price,
			lp.refurb_purchase_price,
			lp.shipping_cost
		FROM install_suite i
		INNER JOIN install_suite_product sp ON i.suite_id = sp.suite_id
		INNER JOIN products p ON sp.prod_id = p.id
		$JOIN lease_suite_pricing lp ON i.suite_id = lp.suite_id AND sp.prod_id = lp.prod_id AND lp.lease_id = ?
		WHERE i.active $suite_clause
		ORDER BY i.sort_order, p.code";
        $sth = $dbh->prepare($sql);
        $sth->BindValue(1, $lease_id, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $addon_price = is_null($row['addon_price']) ? "" : number_format($row['addon_price'], 2);
            $volume_price = is_null($row['volume_price']) ? "" : number_format($row['volume_price'], 2);
            $package_price = is_null($row['package_price']) ? "" : number_format($row['package_price'], 2);
            $volume_price = is_null($row['volume_price']) ? "" : number_format($row['volume_price'], 2);
            $new_purchase_price = is_null($row['new_purchase_price']) ? "" : number_format($row['new_purchase_price'], 2);
            $refurb_purchase_price = is_null($row['refurb_purchase_price']) ? "" : number_format($row['refurb_purchase_price'], 2);
            $shipping_cost = is_null($row['shipping_cost']) ? "" : number_format($row['shipping_cost'], 2);

            $pricing[$row['suite_id']][$row['prod_id']] = array(
                'description' => $row['description'],
                'code' => $row['code'],
                'product_name' => $row['product_name'],
                'addon_price' => $addon_price,
                'volume_price' => $volume_price,
                'package_price' => $package_price,
                'volume_price' => $volume_price,
                'new_purchase_price' => $new_purchase_price,
                'refurb_purchase_price' => $refurb_purchase_price,
                'shipping_cost' => $shipping_cost);
        }

        return $pricing;
    }

    /**
     * Build an array of pricing detail
     *
     * @param integer
     * @param integer
     * @param integer
     * @return array
     */
    static public function GetSuiteServicePricing($lease_id, $suite_id = null, $limit = false)
    {
        $pricing = array();
        $dbh = DataStor::getHandle();

        $suite_clause = "";
        if (!empty ($suite_id))
            $suite_clause = "AND sp.suite_id = " . (int) $suite_id;

        # Limit the list to this CP/lease
        $JOIN = ($limit) ? "INNER JOIN" : "LEFT JOIN";

        $sql = "SELECT
			l.id,
			l.pricing_method,
 			l.description,
			l.display_text,
			sp.suite_id,
			sp.price
		FROM contract_visit_frequency l
		$JOIN lease_suite_service_pricing sp ON l.id = sp.service_id AND sp.lease_id = ?
		WHERE l.active $suite_clause
		ORDER BY l.pricing_method, l.visit_count";
        $sth = $dbh->prepare($sql);
        $sth->BindValue(1, $lease_id, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $price = is_null($row['price']) ? "" : number_format($row['price'], 2);
            $pricing[$row['suite_id']][$row['id']] = array(
                'pricing_method' => $row['pricing_method'],
                'description' => $row['description'],
                'display_text' => $row['display_text'],
                'price' => $price
            );
        }

        return $pricing;
    }

    /**
     * Build an array of pricing detail
     *
     * @param integer
     * @param integer
     * @param integer
     * @return array
     */
    static public function GetSuiteWarrantyPricing($lease_id, $suite_id = null, $limit = false)
    {
        $pricing = array();
        $dbh = DataStor::getHandle();

        $suite_clause = "";
        if (!empty ($suite_id))
            $suite_clause = "AND sp.suite_id = " . (int) $suite_id;

        # Limit the list to this CP/lease
        $JOIN = ($limit) ? "INNER JOIN" : "LEFT JOIN";

        $sql = "SELECT
			w.warranty_id,
			w.warranty_name,
			w.year_interval,
			w.charge_monthly,
			p.id as prod_id,
			p.model,
			p.description,
			sp.suite_id,
			coalesce(sp.price, lp.price) AS price
		FROM warranty_option w
		INNER JOIN (
			SELECT p.id, e.model, e.description, e.active
			FROM products p
			INNER JOIN equipment_models e on p.code = e.model
			WHERE e.type_id = 1 AND is_test_equipment = false
		) p ON true
		$JOIN lease_suite_warranty_pricing sp ON w.warranty_id = sp.warranty_option_id AND p.id = sp.prod_id AND sp.lease_id = ?
		LEFT JOIN lease_warranty_pricing lp ON w.warranty_id = lp.warranty_option_id AND p.id = lp.prod_id AND lp.lease_id = ?
		WHERE w.active AND (p.active OR sp.prod_id IS NOT NULL) $suite_clause
		ORDER BY p.description, w.display_order";
        $sth = $dbh->prepare($sql);
        $sth->BindValue(1, $lease_id, PDO::PARAM_INT);
        $sth->BindValue(2, $lease_id, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $price = is_null($row['price']) ? "" : number_format($row['price'], 2);
            $pricing[$row['suite_id']][$row['prod_id']][$row['warranty_id']] = array(
                'warranty_name' => $row['warranty_name'],
                'year_interval' => $row['year_interval'],
                'model' => $row['model'],
                'model_name' => $row['description'],
                'price' => $price,
                'charge_monthly' => $row['charge_monthly']
            );
        }

        return $pricing;
    }

    /**
     * Build an array of product pricing detail
     *
     * @param integer
     * @return array
     */
    static public function GetServicePricing($lease_id)
    {
        $pricing = array();
        $dbh = DataStor::getHandle();

        $sql = "SELECT
			l.id,
			l.pricing_method,
 			l.description,
			l.display_text,
			sp.price
		FROM contract_visit_frequency l
		LEFT JOIN lease_service_pricing sp ON l.id = sp.service_id AND sp.lease_id = ?
		WHERE l.active
		ORDER BY l.pricing_method, l.visit_count";
        $sth = $dbh->prepare($sql);
        $sth->BindValue(1, (int) $lease_id, PDO::PARAM_INT);
        $sth->Execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $price = is_null($row['price']) ? null : number_format($row['price'], 2);
            $pricing[$row['id']] = array(
                'pricing_method' => $row['pricing_method'],
                'description' => $row['description'],
                'display_text' => $row['display_text'],
                'price' => $price
            );
        }

        return $pricing;
    }

    /**
     * Build an array of pricing detail
     *
     * @param integer
     * @return array
     */
    static public function GetMaintenancePricing($lease_id)
    {
        $pricing = array();
        $dbh = DataStor::getHandle();

        $sql = "SELECT
			a.id,
			a.name,
			a.term_interval,
			a.charge_monthly,
			p.prod_id,
			p.model,
			p.description,
			sp.price
		FROM maintenance_agreement a
		INNER JOIN (
			SELECT p.id as prod_id, e.model, e.description, e.active
			FROM products p
			INNER JOIN equipment_models e on p.code = e.model
			WHERE e.type_id = 1 AND is_test_equipment = false
		) p ON true
		LEFT JOIN lease_maintenance_pricing sp ON a.id = sp.maintenance_agreement_id AND p.prod_id = sp.prod_id AND sp.lease_id = ?
		WHERE a.active AND (p.active OR sp.prod_id IS NOT NULL)
		ORDER BY p.description, a.display_order";
        $sth = $dbh->prepare($sql);
        $sth->BindValue(1, (int) $lease_id, PDO::PARAM_INT);
        $sth->Execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $price = is_null($row['price']) ? "" : number_format($row['price'], 2);
            $pricing[$row['prod_id']][$row['id']] = array(
                'name' => $row['name'],
                'term_interval' => $row['term_interval'],
                'model' => $row['model'],
                'model_name' => $row['description'],
                'price' => $price,
                'charge_monthly' => $row['charge_monthly']
            );
        }

        return $pricing;
    }

    /**
     * Return display text for the given option
     *
     * @param integer
     * @return string
     */
    static public function GetMaintenanceText($id)
    {
        $dbh = DataStor::getHandle();

        $sql = "SELECT
			a.name || ' ' || a.term_interval,
		FROM maintenance_agreement a
		WHERE id = ?";
        $sth = $dbh->prepare($sql);
        $sth->bindValue(1, (int) $id, PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchColumn();
    }

    /**
     * Build an array of pricing detail
     *
     * @param integer
     * @return array
     */
    static public function GetWarrantyPricing($lease_id)
    {
        $pricing = array();
        $dbh = DataStor::getHandle();

        $sql = "SELECT
			l.warranty_id,
			l.warranty_name,
			l.year_interval,
			l.charge_monthly,
			p.id as prod_id,
			p.model,
			p.description,
			sp.price
		FROM warranty_option l
		INNER JOIN (
			SELECT p.id, e.model, e.description, e.active
			FROM products p
			INNER JOIN equipment_models e on p.code = e.model
			WHERE e.type_id = 1 AND is_test_equipment = false
		) p ON true
		LEFT JOIN lease_warranty_pricing sp ON l.warranty_id = sp.warranty_option_id AND p.id = sp.prod_id AND sp.lease_id = ?
		WHERE l.active AND (p.active OR sp.prod_id IS NOT NULL)
		ORDER BY p.description, l.display_order";
        $sth = $dbh->prepare($sql);
        $sth->BindValue(1, (int) $lease_id, PDO::PARAM_INT);
        $sth->Execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $price = is_null($row['price']) ? "" : number_format($row['price'], 2);
            $pricing[$row['prod_id']][$row['warranty_id']] = array(
                'warranty_name' => $row['warranty_name'],
                'year_interval' => $row['year_interval'],
                'model' => $row['model'],
                'model_name' => $row['description'],
                'price' => $price,
                'charge_monthly' => $row['charge_monthly']
            );
        }

        return $pricing;
    }

    /**
     * Return display text for the given option
     *
     * @param integer
     * @return string
     */
    static public function GetWarrantyText($warranty_id)
    {
        $dbh = DataStor::getHandle();

        $sql = "SELECT
			a.warranty_name || ' ' || a.year_interval,
		FROM warranty_option a
		WHERE warranty_id = ?";
        $sth = $dbh->prepare($sql);
        $sth->bindValue(1, (int) $warranty_id, PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchColumn();
    }

    /**
     * Get required CSS tags for the form display
     *
     * @return string
     */
    public function getCSS(&$css)
    {
        $css[] = Config::$WEB_PATH . '/styles/result_list.css';
        $css[] = Config::$WEB_PATH . '/styles/crm.css';
        $css[] = Config::$WEB_PATH . '/styles/lease.css';
    }

    /**
     * Return form buttons for saving the form
     */
    public function getSaveButtons()
    {
        $save = " <input type='button' class='submit' name='save' value='Save' onClick='document.lease.submit();'/>";

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
     *
     */
    public function getStatusText()
    {
        $status = 'No Amendments';

        if ($this->amendments)
            $status = 'With Amendments';

        return $status;
    }

    /**
     * Get visit_frequency option list
     */
    public function GetVisitFrequencyList($visit_count = NULL)
    {
        if (is_null($visit_count))
            $visit_count = $this->visit_frequency;

        if (!isset ($this->visit_frequency_options))
            $this->LoadOptions('visit_frequency_options');

        $visit_frequency = "";
        foreach (array('vd', 'add', 'vp') as $method)
        {
            $class = ($this->pricing_method == $method) ? "" : "hidden";
            if ($method == 'vd')
                $label = "Package Pricing";
            else if ($method == 'vp')
                $label = "Volume Pricing";
            else
                $label = "Addon Pricing";

            $visit_frequency .= "<optgroup class='$class' label='$label' id='vf_og_{$method}_{$this->lease_id}'>";

            foreach ($this->visit_frequency_options as $row)
            {
                if ($row->pricing_method == $method)
                {
                    $sel = ($row->id == $visit_count) ? "selected" : "";
                    $visit_frequency .= "<option value=\"{$row->id}\" {$sel}>{$row->description}</option>";
                }
            }

            $visit_frequency .= "</optgroup>";
        }

        return $visit_frequency;
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
     * Build the clinical support text
     *
     * @param integer
     * @param string
     * @return string
     */
    public function GetVisitFrequencyText($visit_count = null)
    {
        if (is_null($visit_count))
            $visit_count = $this->visit_frequency;

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT display_text
		FROM contract_visit_frequency
		WHERE id = ?");
        $sth->bindValue(1, (int) $visit_count, PDO::PARAM_INT);
        $sth->execute();
        return $sth->fetchColumn();
    }

    /**
     * Build the warranty option js array
     *
     * @param integer
     * @return string
     */
    public function GetWarrantyList($selected = null)
    {
        if (is_null($selected))
            $selected = $this->warranty_option_id;
        $options = "";
        $sth = $this->dbh->query("SELECT warranty_id as value, warranty_name || ' ' || year_interval as text, active
		FROM warranty_option
		ORDER BY display_order");
        while ($option = $sth->fetch(PDO::FETCH_ASSOC))
        {
            if ($option['active'] || $option['value'] == $selected)
            {
                $sel = ($option['value'] == $selected) ? "selected" : "";
                $options .= "<option value='{$option['value']}' $sel>{$option['text']}</option>\n";
            }
        }

        return $options;
    }

    /**
     * Determine if this lease needs approval
     *
     * @return bool
     */
    public function preApproved()
    {
        $pre_approve = false;

        if ($this->lease_id && $this->master_agreement)
        {
            # Preapprove if existing lease not generated from this nfif
            $nfif_id = $this->nfif_id;

            $sth = $this->dbh->query("SELECT nfif_id FROM lease_agreement WHERE lease_id = {$this->lease_id}");
            list($nfif_id) = $sth->fetch(PDO::FETCH_NUM);

            if ($nfif_id <> $this->nfif_id)
                $pre_approve = true;
        }

        if ($pre_approve == false && $this->nfif_id)
        {
            # Preapprove PURCHASE contract types
            $pre_approve = $this->IsPurchase();
        }

        return $pre_approve;
    }

    /**
     * Update lease_agreement to determine when a customer is on a master lease
     *
     * @param string
     * @param boolean
     * @return integer
     * @throws Exception
     */
    static public function SetMasterLease($cust_id, $master_agreement)
    {
        $dbh = DataStor::getHandle();

        if ($cust_id == 'DEFAULT001' || $cust_id == 'DEFAULT002')
        {
            if ($master_agreement)
                throw new Exception('Cannot set master lease for this Corporate Parent.');
        }

        if ($master_agreement == false)
            LeaseAgreement::CleanLeaseCustID($cust_id);

        $sth = $dbh->prepare("SELECT lease_id, nfif_id FROM lease_agreement WHERE cust_id = ?");
        $sth->bindValue(1, $cust_id, PDO::PARAM_STR);
        $sth->execute();
        list($lease_id, $nfif_id) = $sth->fetch(PDO::FETCH_NUM);

        # Make sure the nfif exists
        if (!$nfif_id)
        {
            $nfif = new NFIF();
            $nfif->status = BaseClass::$STATUS_NEW;
            $nfif->db_insert();
            $nfif_id = $nfif->getVar('nfif_id');
        }

        if ($lease_id)
        {
            $sth = $dbh->prepare("UPDATE lease_agreement SET master_agreement = ?, nfif_id = ? WHERE lease_id = ?");
            $sth->bindValue(1, $master_agreement, PDO::PARAM_BOOL);
            $sth->bindValue(2, (int) $nfif_id, PDO::PARAM_INT);
            $sth->bindValue(3, (int) $lease_id, PDO::PARAM_INT);
            $sth->execute();
        }
        else
        {
            $sth = $dbh->prepare("INSERT INTO lease_agreement (cust_id,master_agreement, nfif_id) VALUES (?,?,?)");
            $sth->bindValue(1, $cust_id, PDO::PARAM_STR);
            $sth->bindValue(2, $master_agreement, PDO::PARAM_BOOL);
            $sth->bindValue(3, (int) $nfif_id, PDO::PARAM_INT);
            $sth->execute();

            $lease_id = $dbh->lastInsertId('lease_agreement_lease_id_seq');
        }

        return $lease_id;
    }

    /**
     * Check if this is a purchase
     */
    private function IsPurchase()
    {
        $has_purchase = false;

        if ($this->nfif_id)
        {
            # Preapprove PURCHASE contract types
            $sth = $this->dbh->query("SELECT 1 FROM nfif_contract WHERE nfif_id = {$this->nfif_id} AND lease_type = 10");
            list($has_purchase) = $sth->fetch(PDO::FETCH_NUM);
        }

        return (bool) $has_purchase;
    }

    /**
     * Return array elements with unique registered names
     *
     * @param array
     * @return array
     */
    static public function OldUniqueRegisteredNames($equipment)
    {
        $ret = null;

        if (!empty ($equipment))
        {
            ## Count the number of systems
            $systems = 0;
            $syncrony_components = array();
            foreach ($equipment as $dev)
            {
                $code = (is_object($dev)) ? $dev->code : $dev['code'];
                $base_price = (is_object($dev)) ? $dev->base_price : $dev['base_price'];

                if (in_array($code, self::$SYNCHRONY_BASE_MODELS))
                {
                    $syncrony_components[$code] = $base_price;
                    $systems++;
                }
                else if (in_array($code, self::$SYNCHRONY_COMPONENT_MODELS))
                {
                    $syncrony_components[$code] = $base_price;
                }
            }

            # Add the component prices
            $synchrony_base_price = 0;
            foreach ($syncrony_components as $code => $price)
            {
                $synchrony_base_price += $price;
            }

            # Remove a components for each system
            # Each system should have a list of components so remove them 1 time per system
            # Extra components remaining will have their own entity
            while ($systems > 0)
            {
                # Fresh set of components
                $components = self::$SYNCHRONY_COMPONENT_MODELS;
                foreach ($components as $i => $code)
                {
                    foreach ($equipment as $i => $dev)
                    {
                        $dev_code = (is_object($dev)) ? $dev->code : $dev['code'];

                        # If the component is found in list
                        # remove equipment element and stop searching for that component
                        if ($dev_code == $code)
                        {
                            unset($equipment[$i]);
                            $code = null;
                        }
                    }
                }
                $systems--;
            }

            foreach ($equipment as $dev)
            {
                $found = false;
                $reg_name = (is_object($dev)) ? $dev->registered_name : $dev['registered_name'];

                if (!empty ($ret))
                {
                    foreach ($ret as $i => $unique)
                    {
                        $u_name = (is_object($unique)) ? $unique->registered_name : $unique['registered_name'];

                        if ($reg_name == $u_name)
                        {
                            if (is_object($dev))
                                $ret[$i]->count++;
                            else
                                $ret[$i]['count']++;

                            $found = true;
                        }
                    }
                }

                if ($found == false)
                {
                    if (is_object($dev))
                    {
                        $dev->count = 1;
                        # Include the component base price in the base model's base_price
                        if (in_array($dev->code, self::$SYNCHRONY_BASE_MODELS))
                            $dev->base_price = $synchrony_base_price;
                    }
                    else
                    {
                        $dev['count'] = 1;
                        # Include the component base price in the base model's base_price
                        if (in_array($dev['code'], self::$SYNCHRONY_BASE_MODELS))
                            $dev['base_price'] = $synchrony_base_price;
                    }

                    $ret[] = $dev;
                }
            }
        }

        return $ret;
    }

    static public function findComboName($combo_id)
    {
        $dbh = DataStor::GetHandle();
        $sth = $dbh->prepare('SELECT val FROM config WHERE name = ?');
        $sth->bindValue(1, 'sm_equipment_combos', PDO::PARAM_STR);
        $sth->execute();
        $rows = $sth->fetchColumn(0);
        if ($rows)
        {
            $combo_options = json_decode($rows);
        }

        return $combo_options->$combo_id->name;
    }
    /**
     * Return array elements with unique registered names
     * Handles combo items
     *
     * @param array
     * @return array
     */
    static public function UniqueRegisteredNames($equipment)
    {
        $ret = null;

        if (!empty ($equipment))
        {
            $combo_components = array();
            foreach ($equipment as $dev)
            {
                $item_num = (is_object($dev)) ? $dev->item_num : $dev['item_num'];
                $combo_id = (is_object($dev)) ? $dev->combo_id : $dev['combo_id'];
                $parent_item_num = (is_object($dev)) ? $dev->parent_item_num : $dev['parent_item_num'];

                if ($combo_id && $parent_item_num)
                {
                    $combo_components[$combo_id][$parent_item_num][] = $dev;
                    unset($equipment[$item_num]);
                }
            }

            foreach ($combo_components as $id => $comp)
            {
                //echo "<pre>";echo self::findComboName($id);echo " x " . (count($comp));echo "</pre>";
                $equip = null;
                foreach ($comp as $c)
                {
                    $base_price = 0;
                    if (!$equip)
                    {
                        $equip = $c[0];
                        if (is_object($equip))
                        {
                            $equip->registered_name = self::findComboName($id);
                            $equip->count = count($comp);
                        }
                        else
                        {
                            $equip['registered_name'] = self::findComboName($id);
                            $equip['count'] = count($comp);
                        }
                    }

                    $handled_services = array();

                    foreach ($c as $values)
                    {
                        if (is_object($values))
                        {
                            $equip->price += $values->price;
                            $base_price += $values->base_price;
                            $equip->unit_price = $values->unit_price;
                            $equip->shipping_cost += $values->shipping_cost;

                            if ($values->warranty_option_id && $values->warranty_fee > 0)
                            {
                                //$equip->services[$values->warranty_option]->amount += self::LookupWarrantyPrice($values->warranty_option_id, $values->pricing_lease_id, $values->prod_id);
                                $equip->services[$values->warranty_option]->amount += $values->warranty_fee;

                                if (!in_array($values->warranty_option, $handled_services))
                                {
                                    $equip->services[$values->warranty_option]->count++;
                                    $handled_services[] = $values->warranty_option;
                                }
                            }

                            if ($values->maintenance_agreement_id)
                            {
                                //$equip->services[$values->maintenance_agreement]->amount += self::LookupMaintenancePrice($values->maintenance_agreement_id, $values->pricing_lease_id, $values->prod_id);
                                $equip->services[$values->maintenance_agreement]->amount += $values->maintenance_fee;

                                if (!in_array($values->maintenance_agreement, $handled_services))
                                {
                                    $equip->services[$values->maintenance_agreement]->count++;
                                    $handled_services[] = $values->maintenance_agreement;
                                }
                            }
                        }
                        else
                        {
                            $equip['price'] += $values['price'];
                            $base_price += $values['base_price'];
                            $equip['unit_price'] = $values['unit_price'];
                            $equip['shipping_cost'] += $values['shipping_cost'];

                            if ($values['warranty_option_id'] && $dev->warranty_fee > 0)
                            {
                                //$equip['services'][$values['warranty_option']]['amount'] += self::LookupWarrantyPrice($values['warranty_option_id'], $values['pricing_lease_id'], $values['prod_id']);
                                $equip['services'][$values['warranty_option']]['amount'] += $values['warranty_fee'];

                                if (!in_array($values['warranty_option'], $handled_services))
                                {
                                    $equip['services'][$values['warranty_option']]['count']++;
                                    $handled_services[] = $values['warranty_option'];
                                }
                            }

                            if ($values['maintenance_agreement_id'])
                            {
                                //$equip['services'][$values['maintenance_agreement']]['amount'] += self::LookupMaintenancePrice($values['maintenance_agreement_id'], $values['pricing_lease_id'], $values['prod_id']);
                                $equip['services'][$values['maintenance_agreement']]['amount'] += $values['maintenance_fee'];

                                if (!in_array($values['maintenance_agreement'], $handled_services))
                                {
                                    $equip['services'][$values['maintenance_agreement']]['count']++;
                                    $handled_services[] = $values['maintenance_agreement'];
                                }
                            }
                        }
                    }
                }

                if (is_object($equip))
                {
                    $equip->base_price = $equip->unit_price = $base_price;
                }
                else
                {
                    $equip['base_price'] = $equip['unit_price'] = $base_price;
                }

                $equipment[] = $equip;
                unset($combo_components[$id]);
            }

            foreach ($equipment as $dev)
            {
                $found = false;
                //$reg_name = (is_object($dev)) ? $dev->registered_name : $dev['registered_name'];
                $reg_name = (is_object($dev)) ? "{$dev->registered_name}-{$dev->serial_num}" : "{$dev['registered_name']}-{$dev['serial_num']}";

                if (!empty ($ret))
                {
                    foreach ($ret as $i => $unique)
                    {
                        //$u_name = (is_object($unique)) ? $unique->registered_name : $unique['registered_name'];
                        $u_name = (is_object($unique)) ? "{$unique->registered_name}-{$unique->serial_num}" : "{$unique['registered_name']}-{$unique['serial_num']}";

                        if ($reg_name == $u_name)
                        {
                            if (is_object($dev))
                            {
                                $ret[$i]->count++;
                                $ret[$i]->price += $dev->price;
                                $ret[$i]->shipping_cost += $dev->shipping_cost;

                                if ($dev->warranty_option_id && $dev->warranty_fee > 0)
                                {
                                    $ret[$i]->services[$dev->warranty_option]->amount += $dev->warranty_fee;
                                    $ret[$i]->services[$dev->warranty_option]->count++;
                                }

                                if ($dev->maintenance_agreement_id)
                                {
                                    $ret[$i]->services[$dev->maintenance_agreement]->amount += $dev->maintenance_fee;
                                    $ret[$i]->services[$dev->maintenance_agreement]->count++;
                                }
                            }
                            else
                            {
                                $ret[$i]['count']++;
                                $ret[$i]['price'] += $dev['price'];
                                $ret[$i]['shipping_cost'] += $dev['shipping_cost'];

                                if ($dev['warranty_option_id'] && $dev['warranty_fee'] > 0)
                                {
                                    $ret[$i]['services'][$dev['warranty_option']]['amount'] += $dev['warranty_fee'];
                                    $ret[$i]['services'][$dev['maintenance_agreement']]['count']++;

                                }

                                if ($dev->maintenance_agreement_id)
                                {
                                    $ret[$i]['services'][$dev['maintenance_agreement']]['amount'] += $dev['maintenance_fee'];
                                    $ret[$i]['services'][$dev['maintenance_agreement']]['count']++;

                                }

                            }

                            $found = true;
                        }
                    }
                }

                if ($found == false)
                {
                    if (is_object($dev))
                    {
                        if (!property_exists($dev, 'count'))
                        {
                            $dev->count = 1;

                            if ($dev->warranty_option_id && $dev->warranty_fee > 0)
                            {
                                $dev->services[$dev->warranty_option]->amount = $dev->warranty_fee;
                                $dev->services[$dev->warranty_option]->count++;
                            }

                            if ($dev->maintenance_agreement_id)
                            {
                                $dev->services[$dev->maintenance_agreement]->amount = $dev->maintenance_fee;
                                $dev->services[$dev->maintenance_agreement]->count++;
                            }
                        }
                    }
                    else
                    {
                        if (!array_key_exists('count', $dev))
                        {
                            $dev['count'] = 1;

                            if ($dev['warranty_option_id'] && $dev['warranty_fee'] > 0)
                            {
                                $dev['services'][$dev['warranty_option']]['amount'] += $dev['warranty_fee'];
                                $dev['services'][$dev['maintenance_agreement']]['count']++;

                            }

                            if ($dev->maintenance_agreement_id)
                            {
                                $dev['services'][$dev['maintenance_agreement']]['amount'] += $dev['maintenance_fee'];
                                $dev['services'][$dev['maintenance_agreement']]['count']++;
                            }
                        }
                    }

                    $ret[] = $dev;
                }
            }
        }

        return $ret;
    }
}
?>