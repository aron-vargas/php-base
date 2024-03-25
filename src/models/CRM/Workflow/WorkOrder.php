<?
/**
 * @package Freedom
 */

require_once ('include/quality/qc_functions.php');


# Define the status id of a closed WO
define("WO_CLOSED", 4);
Define("WO_INCIDENT", 3);

/**
 * The WorkOrder Class Definition
 *
 * @author Aron Vargas
 * @package Freedom
 */
class WorkOrder {
    private $dbh;

    # Database matching attributes
    protected $work_order = 0;			#int
    protected $wo_asset_id = 0;			#int
    protected $model = 0;				#int
    protected $serial_num = '';			#string
    protected $bar_code = '';			#string
    protected $open_date = 0;			#int
    protected $open_by = 0;				#int
    protected $last_mod_date = 0;		#int
    protected $last_mod_by = 0;			#int
    protected $close_date = 0;			#int
    protected $close_by = 0;			#int
    protected $status = 1;				#int
    protected $location = '';			#string

    protected $incident_tag = '';		#string

    # Additional Incident attributes
    protected $wo_pm_incident_id = null;
    protected $incident_pm_id = null;
    protected $incident_pass = false;
    protected $approval_request_submitted = false;
    protected $incident_investigation_statement = '';
    protected $incident_complete = false;	# bool
    protected $mgr_requested_additional_tests = false;	# bool


    protected $svc_code_01 = '';		#string
    protected $swap_requested = false;	#bool
    protected $swap_model = 0;			#int
    protected $swap_serial = '';		#string
    protected $rtn_airbill = '';		#string
    protected $og_airbill = '';			#string
    protected $rcvd_from = '';			#string
    protected $bill_to = '';			#string
    protected $complaint_form = 0;		#int
    protected $po_number = '';			#string
    protected $problem = '';			#string
    protected $notes = '';				#string

    protected $has_inspection = 0;		#bool



    protected $facility_id = null;		#int
    protected $editable = 0;			#bool
    protected $dealer_repair = false;	#bool
    protected $mas_sales_order = 0;		#int
    protected $wo_pm_version = 0;		#int
    protected $work_time;               #int

    # Additional Lookup attributes
    protected $last_mod_by_name = '';	#string
    protected $model_number = '';		#string
    protected $model_description = '';	#string
    protected $manufacture_date = null;	#string
    protected $service_date = null;		#string
    protected $barcode;					#string
    protected $open_by_name = '';		#string
    protected $close_by_name = '';		#string
    protected $status_text = 'Open';	#string
    protected $user_id = null;			#int
    protected $vendor_id = null;		#int
    protected $quotation_notes = '';	#string
    protected $ncmr_id = null;          #int
    protected $wo_type = 1;          #int

    # Additional Array attributes
    protected $replaced_parts = array();	#array
    protected $complaints = array();		#array
    protected $pm_forms = array();			#array
    protected $rma_detail = array();		#array
    public $manual_pack_stage;

    # Public attributes
    public $ba_ary = array();		#array
    public $device_type = 1;		#int	/// 1==base unit   2== accessory
    public $message = null;

    private $session_user;  # User the user that is logged in

    static public $WHSEKEY = 24;	# Service Center Wharehouse

    static public $SERVICE_CATALOG = 4; # Catalog for service center

    static public $RTN_ORTHOTIC = '00010';
    static public $RTN_TENS = '00011';

    static public $WF_SCRAP_ACT = 'OOS Scrap Request';



    # Build a new Instance
    public function __construct($work_order = 0)
    {
        $this->wo_legal_note = "<p style='font-size: 7pt;padding:0;margin: 0; text-align:left;'>
This document is the property of " . Config::$COMPANY_NAME . " and must be accounted for.
Information here in is considered confidential. Do not reproduce it, reveal it to
an unauthorized person or send it outside of " . Config::$COMPANY_NAME . " without
written authorization from " . Config::$COMPANY_NAME . ".</p>
<div align='right'><b>RM 0400-F1</b></div>";

        $this->dbh = DataStor::getHandle();
        $this->work_order = $work_order;
        $this->load();

        $sh = new SessionHandler();
        $this->session_user = $sh->getUser($_COOKIE['session_id']);
    }

    public function AddWFLog($work_flow_id, $action, $reason)
    {
        global $user;

        if (empty ($action))
            $action = "--Empty WO Action--";
        if (empty ($reason))
            $action = "--Empty--";

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("INSERT INTO workorder_workflow_log
		(work_order_id, work_flow_id, user_id, action, reason)
		VALUES (?,?,?,?,?)");
        $sth->bindValue(1, (int) $this->work_order, PDO::PARAM_INT);
        $sth->bindValue(2, (int) $work_flow_id, PDO::PARAM_INT);
        $sth->bindValue(3, (int) $user->getId(), PDO::PARAM_INT);
        $sth->bindValue(4, $action, PDO::PARAM_STR);
        $sth->bindValue(5, $reason, PDO::PARAM_STR);
        $sth->execute();
    }

    /**
     * Generate capa select widget options
     */
    private function CapaMenu()
    {
        $dbh = DataStor::getHandle();
        $menu_items = "";

        $rc = 'odd';
        $sth = $dbh->prepare("SELECT
			c.id,
			c.created_date,
			c.affected_area,
			c.is_corrective,
			c.is_preventive,
			c.request_nature_text,
			cp.priority_desc,
			j.workorder_id as linked
		FROM capa c
		INNER JOIN capa_priority cp on c.priority = cp.id
		LEFT JOIN work_order_capa_join j ON c.id = j.capa_id AND j.workorder_id = ?
		ORDER BY c.id");
        $sth->bindValue(1, $this->work_order, PDO::PARAM_INT);
        $sth->execute();
        while ($capa = $sth->fetch(PDO::FETCH_OBJ))
        {
            $date = date('Y-m-d', $capa->created_date);
            $chk = ($capa->linked) ? "checked" : "";
            $text = "<div class='mi'>
			<input type='checkbox' $chk onclick='Attach(this);' id='capa_id_{$capa->id}' name='capa_id[]' value='{$capa->id}' />
			<a href='{$_SERVER['PHP_SELF']}?act=capa&workorder_id={$this->work_order}&id={$capa->id}' alt='View CAPA' title='View CAPA'>Capa # {$capa->id}</a>
			<label for='capa_id_{$capa->id}'>&nbsp;&nbsp;&nbsp;$date";
            if ($capa->is_corrective)
                $text .= " [Corrective]";
            if ($capa->is_preventive)
                $text .= " [Preventive]";
            $text .= "</label></div>";
            $affected_area = htmlentities(substr($capa->affected_area, 0, 40), ENT_QUOTES);
            $request_nature_text = htmlentities(substr($capa->request_nature_text, 0, 40));
            $text .= "<div class='detail'><label>Area(s) Affected:</label> {$affected_area}</div>";
            $text .= "<div class='detail'><label>Nature of Request:</label> {$request_nature_text}</div>";

            $menu_items .= "<div class='$rc'>{$text}</div>";

            $rc = ($rc == 'odd') ? 'even' : 'odd';
        }

        return $menu_items;
    }

    /**
     * Changes one field in the database and reloads the object.
     *
     * @param string $field
     * @param mixed $value
     */
    public function change($field, $value)
    {
        if ($this->work_order)
        {
            $sth = $this->dbh->prepare("UPDATE work_order SET $field = ? WHERE work_order = ?");
            $sth->bindValue(1, $value);
            $sth->bindValue(2, $this->work_order, PDO::PARAM_INT);
            $sth->execute();

            $this->{$field} = $value;
        }
        else
        {
            throw new Exception('Cannot update a non-existant record.');
        }
    }

    /**
     * Remove temp records from acc table
     */
    public function ClearACC()
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare('DELETE FROM work_order_acc WHERE work_order = ?');
        $sth->bindValue(1, $this->work_order, PDO::PARAM_INT);
        $sth->execute();
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
                if (is_array($value))
                    $this->{$key} = $value;
                else
                    $this->{$key} = trim($value);
            }
        }
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
     * @return string
     */
    public function GetWSLinks()
    {
        $dbh = DataStor::getHandle();
        $tags = "";

        $sth = $dbh->prepare("SELECT
			id
		FROM qc_worksheet
		WHERE workorder_id = ?
		AND type_id = ?");
        $sth->bindValue(1, $this->work_order, PDO::PARAM_INT);
        $sth->bindValue(2, QCWorksheet::$DOC_TYPE_MALFUNCTION_ID, PDO::PARAM_INT);
        $sth->execute();
        while (list($id) = $sth->fetch(PDO::FETCH_NUM))
        {
            $tags .= "<a style='margin-left: 2px; margin-right: 2px;' target='_self' alt='View/Edit Decision Tree' title='View/Edit Decision Tree'
				href='{$_SERVER['PHP_SELF']}?act=ws&id=$id'>
				<img height='15' style='vertical-align: middle;' src='images/edit-mini.png'>
			</a>";
        }

        return $tags;
    }

    /**
     * Sets the class Property value defined by key.
     *
     * @param $key string
     * @param $value mixed
     */
    public function setVar($key = null, $value = null)
    {
        if (@property_exists($this, $key))
        {
            $this->{$key} = $value;
        }
    }

    public function setDealer()
    {
        global $user;

        $location = $this->facility_id;
        $asset_id = LeaseAsset::exists($this->model, $this->serial_num);
        $device = ($asset_id > 0) ? new LeaseAsset($asset_id) : null;
        $device->addTransaction($location, LeaseAssetTransaction::$RECEIVED, "", $user,
            "This unit is being set to received back at the facility.");
    }

    /**
     * Finalize WO, Set device to Finished Goods In Service
     *
     * @param booolean finalize
     */
    public function setFGI($finalize = true)
    {
        $update = false;
        $asset_id = LeaseAsset::exists($this->model, $this->serial_num);
        $device = ($asset_id > 0) ? new LeaseAsset($asset_id) : null;

        # Retain facility_id for dealer repair
        $location = ($this->dealer_repair) ? $this->facility_id : Config::$DEFAULT001_ID;

        if ($device)
        {
            # Add transaction
            $device->addTransaction($location, LeaseAssetTransaction::$FGI, '', $this->session_user, "SET to FGI (Workorder {$this->work_order})");

            # Option to keep this editable
            if ($finalize)
                $this->change('editable', 0);

            # Respond successful
            $update = true;
        }

        return $update;
    }

    /**
     * Finalize WO, Set device Out of Service
     * Make sure to close WO before adding transaction
     */
    public function setOOS($action, $form)
    {
        global $user;

        $asset_id = LeaseAsset::exists($this->model, $this->serial_num);
        $old_model = new EquipmentModel($this->model);
        if (!isset ($form["model"]))
            $form["model"] = $this->model;
        $new_model = new EquipmentModel($form["model"]);
        $device = ($asset_id > 0) ? new LeaseAsset($asset_id) : null;
        $error_msg = "";

        # Retain facility_id for dealer repair
        $location = ($this->dealer_repair) ? $this->facility_id : Config::$DEFAULT001_ID;

        if (strtolower($action) == strtolower(LeaseAssetTransaction::$SCRAPPED))
        {
            // If accessory, just scrap the unit
            if ($device->getType() == EquipmentModel::$ACCESSORY)
            {
                $device->addTransaction($location, LeaseAssetTransaction::$OUT_OF_SERVICE,
                    LeaseAssetTransaction::$SCRAPPED, $this->session_user, $form["reason"]);
            }
            else
            {

                # Check for previous scrap request
                $log = $this->GetWFLog(self::$WF_SCRAP_ACT);
                if (isset ($log->id))
                    return;

                $newStatus = new AssetStatus(null, LeaseAssetTransaction::$OUT_OF_SERVICE);
                $newSubstatus = new AssetSubStatus(null, LeaseAssetTransaction::$SCRAPPED);
                $trans = ($device) ? $device->getLastTransaction() : null;
                $currentStatus = new AssetStatus(null, $trans->getStatus());
                $currentSubstatus = new AssetSubStatus(null, $trans->getSubStatus());
                $devFacility = ($trans) ? $trans->getFacility() : null;

                // If there is(are) accessories currently attached, remove them.
                $accessory_units = $device->getAccessoryUnits();
                if ($accessory_units)
                {
                    foreach ($accessory_units as $key => $accessory_id)
                    {
                        if ($accessory_id)
                        {
                            $accessory = new LeaseAsset($accessory_id);
                            $accessory->Attach(null);
                        }
                    }
                }

                $asOfDate = date("m/d/Y", strtotime("now"));

                $oosArray = array(
                    "user_id" => $user->getId(),
                    "equip_count" => 1,
                    "wf_description" => "Scrap Request - Workorder {$this->work_order}",
                    "assigned_to" => $user->getId(),
                    "new_stage_note" => "Request Approval",
                    "stage_act" => "req_approval",
                    "asset_equip" => array(array(
                        "model_id" => $this->model,
                        "lease_asset_id" => $asset_id,
                        "serial_number" => $this->serial_num,
                        "current_facility_id" => $devFacility->getId(),
                        "current_facility_cust_id" => $devFacility->getCustId(),
                        "current_asset_status_id" => $currentStatus->getVar("id"),
                        "current_asset_sub_status_id" => $currentSubstatus->getVar("id"),
                        "current_owning_acct" => $device->getOwningAcct(),
                        "new_facility_id" => $devFacility->getId(),
                        "new_facility_cust_id" => $devFacility->getCustId(),
                        "new_asset_status_id" => $newStatus->getVar("id"),
                        "new_asset_sub_status_id" => $newSubstatus->getVar("id"),
                        "new_owning_acct" => $device->getOwningAcct(),
                        "as_of_date" => $asOfDate,
                        "reason_code" => "AT012",
                        "comment" => $form["reason"]
                    ))
                );

                // Create Asset OOS Workflow record
                $wf_ary = array('process_id' => 5);
                $wf = new WorkFlow();

                # Create the work flow records
                $wf->save_wf($wf_ary);
                $wf->load();

                # Save opportunity information into the Asset Update
                $wf->save_wf($oosArray);

                $this->AddWFLog($wf->GetVar('id'), self::$WF_SCRAP_ACT, $form["reason"]);
            }
        }
        else if (strtolower($action) == strtolower(LeaseAssetTransaction::$UPGRADED))
        {
            $form["serial"] = strtoupper($form["serial"]);

            // Check to see if new Serial # is unique
            if (LeaseAsset::exists($form["model"], $form["serial"]))
                $error_msg = "Cannot create new unit because a {$new_model->getNumber()} - {$new_model->getName()} with Serial {$form['serial']} already exists.";
            else
            {
                // Set the old unit to OOS - Upgraded
                $OOS = LeaseAssetTransaction::$OUT_OF_SERVICE;
                $UPGRAGE = LeaseAssetTransaction::$UPGRADED;
                $comment = "Unit upgraded to {$new_model->getNumber()} - {$form['serial']}.";

                $device->addTransaction($location, $OOS, $UPGRAGE, $this->session_user, $comment);
                $device->setBarcode("");
                $device->save();

                # Find the Upgrade Cost
                $sth = $this->dbh->prepare("SELECT
					upgrade_cost, labor_cost, life_extension
				FROM asset_upgrade_kit
				WHERE new_model_id = ?");
                $sth->bindValue(1, $new_model->getId(), PDO::PARAM_INT);
                $sth->execute();
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                $upgrade_cost = $row['upgrade_cost'];
                $labor_cost = $row['labor_cost'];
                $life_extension = $row['life_extension'];

                # Create the new asset from the old
                $new_device = new LeaseAsset($asset_id);

                # Save new unit with updated information
                $new_asset_id = $new_device->getNextId();
                $new_device->setId($new_asset_id);
                $new_device->setModel($new_model);
                $new_device->setSerial($form["serial"]);
                $new_device->setBarcode($form['barcode']);
                $new_device->setManufacturer("DEFAULT001");
                $new_device->save();

                # Add Upgrade records
                FASOwnership::UpgradeUnit($asset_id, $new_asset_id, $upgrade_cost, $labor_cost, $life_extension);

                # Get the new id
                if ($new_asset_id)
                {
                    // Set the new unit to WIP
                    $new_device->addTransaction($location,
                        LeaseAssetTransaction::$WIP, '', $this->session_user,
                        "Unit upgraded from {$old_model->getNumber()} - {$this->serial_num}.");

                    // Create a link between the two units for historical purposes
                    $sth = $this->dbh->prepare("INSERT INTO asset_upgrade_link
					(old_asset_id, new_asset_id) VALUES (?, ?)");
                    $sth->bindValue(1, $asset_id, PDO::PARAM_INT);
                    $sth->bindValue(2, $new_asset_id, PDO::PARAM_INT);
                    $sth->execute();

                    # The replacement model/serial in the complaint form needs updated.
                    $sth = $this->dbh->prepare("UPDATE complaint_form_equipment SET
						replacement_model = ?,
						replacement_serial_number = ?
					WHERE asset_type = 'Device' -- Only Update Base Units
					AND issue_id = ?
					AND model = ?
					AND serial_number = ?");
                    $sth->bindValue(1, $new_model->getId(), PDO::PARAM_INT);
                    $sth->bindValue(2, $form['serial'], PDO::PARAM_STR);
                    $sth->bindValue(3, $this->complaint_form, PDO::PARAM_INT);
                    $sth->bindValue(4, $this->model, PDO::PARAM_INT);
                    $sth->bindValue(5, $this->serial_num, PDO::PARAM_STR);
                    $sth->execute();

                    # Any Queued or Processed order for the old assest
                    # needs to be replaced with the upgraded record
                    # (These will be Returns on an RMA)

                    # Outgoing Unit
                    $sth = $this->dbh->prepare("UPDATE order_item SET
						asset_id = ?
					WHERE asset_id = ?
					AND (
						order_id IN (SELECT order_id FROM complaint_form_equipment WHERE issue_id = ?)
						OR
						order_id IN (SELECT return_id FROM complaint_form_equipment WHERE issue_id = ?)
					)");
                    $sth->bindValue(1, $new_asset_id, PDO::PARAM_INT);
                    $sth->bindValue(2, $asset_id, PDO::PARAM_INT);
                    $sth->bindValue(3, $this->complaint_form, PDO::PARAM_INT);
                    $sth->bindValue(4, $this->complaint_form, PDO::PARAM_INT);
                    $sth->execute();

                    # Incoming Unit
                    $sth = $this->dbh->prepare("UPDATE order_item
						SET swap_asset_id = ?
					WHERE swap_asset_id = ?
					AND (
						order_id IN (SELECT order_id FROM complaint_form_equipment WHERE issue_id = ?)
						OR
						order_id IN (SELECT return_id FROM complaint_form_equipment WHERE issue_id = ?)
					)");
                    $sth->bindValue(1, $new_asset_id, PDO::PARAM_INT);
                    $sth->bindValue(2, $asset_id, PDO::PARAM_INT);
                    $sth->bindValue(3, $this->complaint_form, PDO::PARAM_INT);
                    $sth->bindValue(4, $this->complaint_form, PDO::PARAM_INT);
                    $sth->execute();
                }
                else
                    $error_msg = "New unit was not created (Unknown reason) please contact IT.";
            }
        }

        if ($error_msg == "")
        {
            $this->editable = 0;
            $this->status = WO_CLOSED;
            $this->save();

            // Create Work Order for New Unit
            if (strtolower($action) == strtolower(LeaseAssetTransaction::$UPGRADED))
            {
                $work_order = WorkOrder::Generate($form["model"], $form["serial"]);

                if ($work_order)
                {
                    $complaints = $work_order->getVar('complaints');
                    if (isset ($complaints[0]['complaint']))
                        $return_orthotic = in_array($complaints[0]['complaint'], array('00010', '00011'));
                    else
                        $return_orthotic = false;

                    # Retain facility id for dealer repairs
                    #
                    if ($return_orthotic)
                        $work_order->setVar('dealer_repair', 0);

                    $work_order->save();
                    $work_order_id = $work_order->getVar('work_order');

                }
                else
                {
                    return "An error has occurred while trying to create the new Work Order.";
                }
            }

            return "OK";
        }

        return $error_msg;
    }

    /**
     * Finalize Workorder and set device to Pack
     * Make sure WO is ok to close/finalized
     *
     * @return boolean
     */
    public function setPackaging()
    {
        $update = false;
        $asset_id = LeaseAsset::exists($this->model, $this->serial_num);
        $device = ($asset_id > 0) ? new LeaseAsset($asset_id) : null;
        $trans = ($device) ? $device->getLastTransaction() : null;
        $is_wip = ($trans) ? ($trans->getStatus() == LeaseAssetTransaction::$WIP) : true;

        # Retain facility_id for dealer repair
        $location = ($this->dealer_repair) ? $this->facility_id : Config::$DEFAULT001_ID;

        # Check current state of workorder and asset
        if (($this->dealer_repair || $this->has_inspection) && $is_wip)
            $update = true;

        if ($update)
        {
            # Finalize Workorder
            $this->change('editable', 0);

            $svc_date = date('Y-m-d');
            $dbh = DataStor::getHandle();
            /////////////////////////////////////////////////////
            /// Is THis OEM

            if ($this->wo_type == 3)
            {
                $sql = "UPDATE lease_asset set svc_date=? WHERE id=?";
                $sth = $dbh->prepare($sql);
                $sth->bindValue(1, $svc_date, PDO::PARAM_STR);
                $sth->bindValue(2, (int) $asset_id, PDO::PARAM_INT);
                $sth->execute();
            }
            /////////////////////////////////////////////////////

            # Update Asset
            if ($device)
            {
                ## First update ownership
                # SET original as active owner for all "PE: Program Equipment"
                if ($device->CustomerOwned() == false && $device->IsPE() == true)
                {
                    $active_owner = ($device->GetDefaultOwner() == FASOwnership::$INI) ? FASOwnership::$INI : FASOwnership::$DEFAULT001;
                    $owner = FASOwnership::ActivateOwner($asset_id, $active_owner);
                }

                ## Now add trans
                $device->addTransaction($location, LeaseAssetTransaction::$PACK, '', $this->session_user, "SET to Pack (Workorder {$this->work_order})", null, true);

                ## Process the Return orders
                if ($this->dealer_repair)
                {
                    # find open return for this asset
                    $match_order = " IN (
						SELECT order_id FROM order_item
						WHERE asset_id = $asset_id
					)";

                    # Attempt to find specific order
                    if ($this->complaint_form)
                    {
                        $sth = $this->dbh->prepare("SELECT return_id
						FROM complaint_form_equipment
						WHERE issue_id = ?
						AND (
							model = ? AND serial_number = ?
							OR
							replacement_model = ? AND replacement_serial_number = ?
						)");
                        $sth->bindValue(1, $this->complaint_form, PDO::PARAM_INT);
                        $sth->bindValue(2, $this->model, PDO::PARAM_INT);
                        $sth->bindValue(3, $this->serial_num, PDO::PARAM_STR);
                        $sth->bindValue(4, $this->model, PDO::PARAM_INT);
                        $sth->bindValue(5, $this->serial_num, PDO::PARAM_STR);
                        $sth->execute();
                        $order_id = $sth->fetchColumn();

                        # Specific order id
                        if ($order_id)
                            $match_order = " = $order_id";
                    }

                    $sth = $this->dbh->prepare("UPDATE orders SET
						status_id = ?,
						processed_by = ?,
						processed_date = ?
					WHERE status_id = ?
					AND type_id = ?
					AND id $match_order");
                    $sth->bindValue(1, Order::$PROCESSED, PDO::PARAM_INT);
                    $sth->bindValue(2, $this->session_user->getId(), PDO::PARAM_INT);
                    $sth->bindValue(3, time(), PDO::PARAM_INT);
                    $sth->bindValue(4, Order::$QUEUED, PDO::PARAM_INT);
                    $sth->bindValue(5, Order::$RETURN_ORDER, PDO::PARAM_INT);
                    $sth->execute();
                }
            }
        }

        return $update;
    }

    /**
     * Preform unlock procedure
     */
    public function setWIP($show_err = true)
    {
        global $user;

        $update = false;
        $asset_id = LeaseAsset::exists($this->model, $this->serial_num);
        $device = ($asset_id > 0) ? new LeaseAsset($asset_id) : null;
        $trans = ($device) ? $device->getLastTransaction() : null;
        $dev_facility = ($trans) ? $trans->getFacility() : null;

        # Retain facility_id for dealer repair
        $location = ($this->dealer_repair) ? $this->facility_id : Config::$DEFAULT001_ID;

        if ($dev_facility)
        {
            $valid_facility = ($dev_facility->getId() == $this->facility_id || $dev_facility->getId() == Config::$DEFAULT001_ID);
            $valid_status = in_array($trans->getStatus(), array(LeaseAssetTransaction::$FGI, LeaseAssetTransaction::$QUARANTINE, $trans->getStatus() == LeaseAssetTransaction::$RECEIVED));
            $not_closed = ($this->getVar('close_date') == 0 && $this->getVar('status') != WO_CLOSED);

            # Facility and status match add new
            if ($valid_facility && $valid_status && $not_closed)
            {
                $device->addTransaction($location, LeaseAssetTransaction::$WIP, "", $this->session_user, "SET to WIP (Workorder {$this->work_order})");
                $this->change('editable', 1);
                $this->change('status', 1);
                $update = true;
            }
            else
            {
                # Show specific problem
                if ($show_err)
                {
                    $error_str = "";
                    if (!$valid_facility)
                        $error_str .= "The location of the device is invalid!<br/>";
                    if (!$valid_status)
                        $error_str .= "The status of the device ({$trans->getStatus()}) is invalid!<br/>";
                    if (!$not_closed) # Double negative :(
                        $error_str .= "This work order is closed!<br/>";

                    echo "<p class='error' style='background-color:#E0C0C0'>$error_str</p>";
                }
            }
        }
        else if ($device)
        {
            # If no valid last transaction then add new
            $device->addTransaction($location, LeaseAssetTransaction::$WIP, "", $this->session_user, "SET to WIP (Workorder {$this->work_order})");
            $this->change('editable', 1);
            $this->change('status', 1);
            $update = true;
        }

        # Check for incident and adjust status
        if ($this->incident_tag)
            $this->change('status', WO_INCIDENT);

        return $update;
    }

    /**
     * Populates this object from the matching record in the
     * database.
     */
    public function load()
    {
        global $user;

        if ($this->work_order)
        {
            $sth = $this->dbh->prepare("SELECT
				w.*,
				s.status_text,
				u_o.firstname || ' ' ||	u_o.lastname AS open_by_name,
				u_l.firstname || ' ' ||	u_l.lastname AS last_mod_by_name,
				u_c.firstname || ' ' ||	u_c.lastname AS close_by_name,
				e.model AS model_number,
				e.description AS model_description,
				e.wo_pm_version,
				e.type_id AS device_type,
				e.manual_pack_stage,
				f.cust_id AS rcvd_from,
				n.id AS ncmr_id,
				wpi.incident_complete as incident_complete,
				w.work_time,
				la.id AS wo_asset_id
			FROM work_order w
			INNER JOIN work_order_status s ON w.status = s.status_id
			LEFT JOIN users u_o ON w.open_by = u_o.id
			LEFT JOIN users u_l ON w.last_mod_by = u_l.id
			LEFT JOIN users u_c ON w.close_by = u_c.id
			LEFT JOIN equipment_models e ON w.model = e.id
			LEFT JOIN v_customer_entity f ON w.facility_id = f.id
			LEFT JOIN ncmr n ON w.work_order = n.workorder_id
			LEFT JOIN wo_pm_incident wpi on w.work_order= wpi.work_order
			LEFT JOIN lease_asset la  on w.model= la.model_id AND w.serial_num = la.serial_num
			WHERE w.work_order = ?");
            $sth->bindValue(1, $this->work_order, PDO::PARAM_INT);
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                foreach ($row as $key => $value)
                {
                    $this->{$key} = $value;
                }
            }

            # If model and serial are known load dates from asset table
            if ($this->model && $this->serial_num)
            {
                $sth = $this->dbh->prepare("SELECT mfg_date, svc_date, barcode
				FROM lease_asset
				WHERE model_id = ? AND serial_num = ?");
                $sth->bindValue(1, (int) $this->model, PDO::PARAM_INT);
                $sth->bindValue(2, $this->serial_num, PDO::PARAM_STR);
                $sth->execute();
                list($this->manufacture_date, $this->service_date, $this->barcode) = $sth->fetch(PDO::FETCH_NUM);
            }
            # User or Vendor WO
            if (!$this->facility_id && preg_match('/900$/', $this->rcvd_from))
            {
                # Lookup Vendor
                //$sth = $this->dbh->prepare("SELECT id FROM users
                //WHERE type = 1 AND active is true AND upper(substr(lastname, 1, 3)) = ?");
                //$sth->bindValue(1, substr($this->rcvd_from, 0, 3), PDO::PARAM_STR);
                //$sth->execute();
                //list($this->user_id) = $sth->fetch(PDO::FETCH_NUM);

                # Lookup User
                if (!$this->vendor_id)
                {
                    $sth = $this->dbh->prepare("SELECT id FROM users
					WHERE type = 1 AND active is true AND upper(substr(lastname, 1, 3)) = ?");
                    $sth->bindValue(1, substr($this->rcvd_from, 0, 3), PDO::PARAM_STR);
                    $sth->execute();
                    list($this->user_id) = $sth->fetch(PDO::FETCH_NUM);
                }
            }


            # Added for cr 2081
            if ($this->incident_tag)
            {
                $this->incident_investigation_statement = "<font color=red>INCIDENT INVESTIGATION:</font> <b>In Process</b>";

                $sth = $this->dbh->prepare("SELECT id,incident_complete,approval_request_submitted,approved_txt,pm_id,mgr_requested_additional_tests
				FROM wo_pm_incident
				WHERE work_order = ?");
                $sth->bindValue(1, (int) $this->work_order, PDO::PARAM_INT);
                $sth->execute();
                list($this->wo_pm_incident_id, $this->incident_pass, $this->approval_request_submitted, $disposition, $this->incident_pm_id, $this->mgr_requested_additional_tests) = $sth->fetch(PDO::FETCH_NUM);


                if ($this->approval_request_submitted)
                {
                    /// Room to add another query -- who requested mgr app  and on what date
                    $this->incident_investigation_statement = "<font color=red>INCIDENT INVESTIGATION:</font> <b>Waiting Manager Approval</b>";
                }


                if ($this->mgr_requested_additional_tests && !$this->approval_request_submitted)
                {
                    $this->incident_investigation_statement = "<font color=red>INCIDENT INVESTIGATION:</font> <b>Additional Testing Requested</b>";
                }


                if ($this->incident_pass)
                {
                    /// Room to add another query -- who approved it, on what date and the results (return to service or not.)
                    $this->incident_investigation_statement = "<font color=red>INCIDENT INVESTIGATION:</font> <a href=\"workorder.php?submit_action=print_pm&pm_id={$this->incident_pm_id}&wo_pm_incident_id={$this->wo_pm_incident_id}&work_order={$this->work_order}\" target=\"_blank\"><b>Complete</b></a> RESULTS: $disposition";

                }
            }


            if ($this->close_date == 0)
                $this->editable = true;

            # Load additional array attributes
            $this->loadParts();
            $this->loadExt();
            $this->loadPM();
        }
    }
    /**
     * Load accessory array
     */
    private function loadba_ary()
    {

        $p_type = 1;
        if ($this->device_type == 1)
            $p_type = 2;

        if ($this->work_order)
        {
            $this->ba_ary = array();

            $sth = $this->dbh->prepare("SELECT
				w.asset_id,
				w.model_id,
				w.serial_number,
				w.barcode,
				w.attached_asset_id,
				w.attached_serial_number,
				w.attached_barcode,
				w.attached_verified,
				w.verified_match,
				w.scrap_attached,
				w.swap_attached,
				w.detach_attached,
				w.new_asset,
				em.model,
				em.description
			FROM work_order_acc w
			INNER JOIN equipment_models em ON w.model_id = em.id
			WHERE w.work_order = {$this->work_order} AND w.type_id=$p_type");
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->ba_ary[] = $row;
            }
        }

    }
    /**
     * Load Replacement Parts array
     */
    private function loadParts()
    {

        if ($this->work_order)
        {
            $this->replaced_parts = array();

            $sth = $this->dbh->prepare("SELECT
				w.id, w.part_id, w.serial_number, p.name, p.code, w.new_used,
				w.mas_sales_order, w.date_added, w.date_posted, w.comment
			FROM work_order_part w
			INNER JOIN products p ON w.part_id = p.id
			WHERE w.work_order = {$this->work_order}");
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->replaced_parts[] = $row;
            }
        }
    }


    /**
     * Load A and C codes as two dimentional array
     */
    private function loadExt()
    {
        $complaint_type_id = EquipmentCode::$COMPLAINT;
        $resolution_type_id = EquipmentCode::$RESOLUTION;

        $this->complaints = array();
        # Find A and C codes Table stores work_order as a zero filled string length 7
        $sql = "SELECT
			e.id, e.complaint, e.resolution,
			c.description as code_description,
			r.description as resolution_description
		FROM work_order_ext e
		LEFT JOIN equipment_code c on e.complaint = c.code AND c.type_id = {$complaint_type_id}
		LEFT JOIN equipment_code r on e.resolution = r.code AND r.type_id = {$resolution_type_id}
		WHERE work_order = ?";
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, $this->work_order, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $this->complaints[] = $row;
        }
    }

    /**
     * Load PM Form
     */
    private function loadRma()
    {
        $sql = "SELECT
			rq.rma_id,
			rq.status,
			rq.created_by,
			rq.created_on,
			rs.status_text,
	 		rq.po_number,
	 		rq.sent_epoch
		FROM rma_quotation rq join rma_status rs on rq.status=rs.status_id
		WHERE rq.work_order = ?";
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, $this->work_order, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $this->rma_detail[] = $row;
        }
    }


    /**
     * Load PM Form
     */
    private function loadPM()
    {
        $this->pm_forms = array();

        # Find
        $sql = "SELECT
			p.id,
			p.version_id,
			p.pass,
			p.created_by,
			p.created_date,
			p.completed_by,
			p.completed_date,
			v.version_str,
			v.template
		FROM wo_pm p
		INNER JOIN wo_pm_version v ON p.version_id = v.id
		WHERE p.work_order = ?";
        $sth = $this->dbh->prepare($sql);
        $sth->bindValue(1, $this->work_order, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            // convert to integer
            $row['pass'] = ($row['pass']) ? 1 : 0;
            $this->pm_forms[] = $row;
        }
    }


    /**
     * Build WHERE clause for lead search from args
     *
     * @param array
     * @param string
     *
     * @return string
     */
    static public function ParseAdvanceSearch($args)
    {
        global $dbh;

        # Integer fields should not be typecast to text
        $int_fields = array('w.work_order', 'w.complaint_form');
        $date_fields = array('w.open_date', 'w.close_date');

        $mode = (isset ($args['mode'])) ? $args['mode'] : "wo";

        # All Valid SM status
        $WHERE = "WHERE true";

        if ($args['search_fields'])
        {
            foreach ($args['search_fields'] as $idx => $field)
            {
                $is_int = in_array($field, $int_fields);
                $is_date = in_array($field, $date_fields);

                if ($is_date)
                    $field = "to_timestamp($field)";
                if ($field == 'field_wo')
                {
                    ## Special consideration since this is comparing type integer value from boolean question
                    ## trying to take things like 1,true, yes, y, t as a TRUE = (wo_type = 2)
                    ## No longer a true or false. 1=SO 2=FO 3=OEM
                    $is_int = true;
                    $field = "w.wo_type";

                    $string = strtolower(substr($args['strings'][$idx], 0, 1));
                    if ($string == 'f')
                        $args['strings'][$idx] = 2;
                    else if ($string == 'o')
                        $args['strings'][$idx] = 3;
                    else
                        $args['strings'][$idx] = 1;
                }
                if ($field == 'serial_num')
                {
                    $field = ($mode == 'wo') ? "w.serial_num" : "a.serial_num";
                }
                if ($mode == 'wo')
                {
                    if ($field == 'open_by')
                        $field = "u_o.firstname || ' ' || u_o.lastname";
                    if ($field == 'closed_by')
                        $field = "u_c.firstname || ' ' || u_c.lastname";
                    if ($field == 'serviced_by')
                        $field = "u_s.firstname || ' ' || u_s.lastname";
                }

                $op = $args['operators'][$idx];

                $string = trim(urldecode($args['strings'][$idx]));
                if (strtoupper($string) == 'YES')
                    $string = "YES";
                if (strtoupper($string) == 'NO')
                    $string = "NO";

                if ($is_date)
                {
                    $time = strtotime($string);
                    if ($time > 1)
                        $date_str = date('Y-m-d', $time);
                    else
                        $is_date = false;
                }

                switch ($op)
                {
                    case 'sw':
                        $WHERE .= "\n AND {$field}::text ilike " . $dbh->quote("$string%");
                        break;
                    case 'ew':
                        $WHERE .= "\n AND {$field}::text ilike " . $dbh->quote("%$string");
                        break;
                    case 'eq':
                        if ($is_int)
                            $WHERE .= "\n AND $field = " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field}::Date = " . $dbh->quote($date_str);
                        else
                            $WHERE .= "\n AND upper($field::text) = " . $dbh->quote(strtoupper($string));
                        break;
                    case 'nq':
                        if ($is_int)
                            $WHERE .= "\n AND $field <> " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field}::Date != {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND upper($field::text) <> " . $dbh->quote(strtoupper($string));
                        break;
                    case 'gt':
                        if ($is_int)
                            $WHERE .= "\n AND $field > " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field}::Date > {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND $field::text > " . $dbh->quote($string);
                        break;
                    case 'lt':
                        if ($is_int)
                            $WHERE .= "\n AND $field < " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field}::Date < {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND $field::text < " . $dbh->quote($string);
                        break;
                    default:
                        if ($is_date)
                            $WHERE .= "\n AND {$field}::Date = " . $dbh->quote($date_str);
                        else
                            $WHERE .= "\n AND {$field}::text ilike " . $dbh->quote("%$string%");
                        break;
                }
            }
        }
        //echo "<pre>$WHERE</pre>";
        return $WHERE;
    }

    /**
     * Build WHERE clause for lead search from args
     *
     * @param array
     *
     * @return string
     */
    static public function ParseSimpleSearch($args)
    {
        global $dbh;

        $mode = (isset ($args['mode'])) ? $args['mode'] : "wo";

        $WHERE = "";

        if ($args['search'])
        {
            if (preg_match('/^#/', $args['search']))
                $WHERE = " WHERE w.work_order = " . (int) substr($args['search'], 1);
            else
            {
                $WHERE = "WHERE (";
                $strings = explode(",", $args['search']);
                $OR = ""; # Dont add keyword OR in the first element
                foreach ($strings as $str)
                {
                    $str = trim(urldecode($str));
                    $time = strtotime($str);
                    $int = (int) $str;
                    if ($time && (strpos($str, "/") || strpos($str, "-")))
                    {
                        ## only use dates and serial
                        if ($mode == 'wo')
                        {
                            $WHERE .= "\n $OR to_timestamp(open_date)::Date = to_timestamp($time)::Date";
                            $WHERE .= "\n OR to_timestamp(close_date)::Date = to_timestamp($time)::Date";
                            $WHERE .= " OR w.serial_num ILIKE " . $dbh->quote("$str%");
                        }
                        else
                        {
                            $WHERE .= "\n $OR a.tstamp::Date = to_timestamp($time)::Date";
                            $WHERE .= " OR a.serial_num ILIKE " . $dbh->quote("$str%");
                        }

                        $OR = "OR"; # Use OR for remaining elements
                    }
                    else
                    {
                        if ($int)
                        {
                            $WHERE .= " $OR a.id = $int";
                            $WHERE .= " OR f.id = $int";
                            if ($mode == 'wo')
                            {
                                $WHERE .= " OR w.complaint_form = $int";
                                $WHERE .= " OR w.work_order = $int";
                            }

                            $OR = "OR"; # Use OR for remaining elements
                        }
                        if ($str)
                        {

                            $WHERE .= " $OR a.barcode ILIKE " . $dbh->quote($str);
                            $WHERE .= " OR f.accounting_id iLIKE " . $dbh->quote("$str%");
                            $WHERE .= " OR f.facility_name ILIKE " . $dbh->quote("%$str%");

                            if ($mode == 'asset')
                            {
                                $WHERE .= " OR a.serial_num ILIKE " . $dbh->quote("$str%");
                            }
                            else
                            {
                                $WHERE .= " OR w.serial_num ILIKE " . $dbh->quote("$str%");
                                $WHERE .= " OR s.status_text = " . $dbh->quote($str);
                                $WHERE .= " OR u_o.firstname || ' ' || u_o.lastname ILIKE " . $dbh->quote("%$str%");
                                $WHERE .= " OR u_s.firstname || ' ' || u_s.lastname ILIKE " . $dbh->quote("%$str%");
                            }
                            $OR = "OR"; # Use OR for remaining elements
                        }
                    }
                }

                $WHERE .= ")\n";
            }
        }

        return $WHERE;
    }

    /**
     * Saves the contents of this object to the database. If this object
     * has an id, the record will be UPDATE'd.  Otherwise, it will be
     * INSERT'ed
     *
     * @param $form array
     */
    public function save($form = array())
    {
        global $user;

        $timestamp = time();

        # Copy form variables to this object
        if (is_array($form))
            $this->copyFromArray($form);

        # Do some data validation for fields we want to control behind the scenes

        # Set open_by and date if not set
        if ($this->open_by < 1)
        {
            $this->open_by = $user->getId();
            $this->open_by_name = $user->getName();
            $this->open_date = $timestamp;
        }

        # Set last_mod on every signed save, except when closed (unless its empty).
        if (isset ($form['signature']))
        {
            if ($this->status != WO_CLOSED || $this->last_mod_by == 0)
            {
                $this->last_mod_by = $user->getId();
                $this->last_mod_by_name = $user->getName();
                $this->last_mod_date = $timestamp;
            }
        }

        # If changing status to closed set close_date
        if ($this->status == WO_CLOSED)
        {
            if ($this->close_date == 0)
            {
                $this->close_date = $timestamp;
                $this->close_by = $user->getId();
                $this->close_by_name = $user->getName();
            }

            if ($this->wo_type == 2)
                $this->editable = 0;

            if ($this->dealer_repair == 1)
                $this->ValidateDR();
        }
        else
        {
            $this->close_date = 0;
            $this->editable = true;
        }

        if ($this->work_order)
        {
            $sth = $this->dbh->prepare("UPDATE work_order
			SET
				model = ?,
				serial_num = ?,
				bar_code = ?,
				open_date = ?,
				open_by = ?,
				last_mod_date = ?,
				last_mod_by = ?,
				close_date = ?,
				close_by = ?,
				status = ?,
				location = ?,
				incident_tag = ?,
				svc_code_01 = ?,
				swap_requested = ?,
				swap_model = ?,
				swap_serial = ?,
				rtn_airbill = ?,
				og_airbill = ?,
				rcvd_from = ?,
				bill_to = ?,
				complaint_form = ?,
				po_number = ?,
				problem = ?,
				notes = ?,
				quotation_notes=?,
				has_inspection = ?,
				facility_id = ?,
				editable = ?,
				dealer_repair = ?,
				work_time = ?,
				wo_type = ?
			WHERE work_order = ?");
            $sth->bindValue(32, (int) $this->work_order, PDO::PARAM_INT);
        }
        else
        {
            $insertSQL = "INSERT INTO work_order
			(model, serial_num, bar_code, open_date,open_by,
			 last_mod_date, last_mod_by, close_date, close_by, status,
			 location, incident_tag,svc_code_01, swap_requested,swap_model,
			 swap_serial, rtn_airbill, og_airbill, rcvd_from, bill_to,
			 complaint_form, po_number, problem, notes,quotation_notes, has_inspection,
			 facility_id, editable, dealer_repair, work_time,wo_type)
			VALUES
			(?, ?, ?, ?, ?,
			 ?, ?, ?, ?, ?,
			 ?, ?, ?, ?, ?,
			 ?, ?, ?, ?, ?,
			 ?, ?, ?, ?,?, ?,
			 ?, ?, ?, ?, ? )";
            $sth = $this->dbh->prepare($insertSQL);
        }

        $sth->bindValue(1, (int) $this->model, PDO::PARAM_INT);
        $sth->bindValue(2, $this->serial_num, PDO::PARAM_STR);
        $sth->bindValue(3, $this->bar_code, PDO::PARAM_STR);
        $sth->bindValue(4, (int) $this->open_date, PDO::PARAM_INT);
        $sth->bindValue(5, (int) $this->open_by, PDO::PARAM_INT);
        $sth->bindValue(6, (int) $this->last_mod_date, PDO::PARAM_INT);
        $sth->bindValue(7, (int) $this->last_mod_by, PDO::PARAM_INT);
        $sth->bindValue(8, (int) $this->close_date, PDO::PARAM_INT);
        $sth->bindValue(9, (int) $this->close_by, PDO::PARAM_INT);
        $sth->bindValue(10, (int) $this->status, PDO::PARAM_INT);
        $sth->bindValue(11, $this->location, PDO::PARAM_STR);
        $sth->bindValue(12, (int) $this->incident_tag, PDO::PARAM_BOOL);
        $sth->bindValue(13, $this->svc_code_01, PDO::PARAM_STR);
        $sth->bindValue(14, (int) $this->swap_requested, PDO::PARAM_BOOL);
        $sth->bindValue(15, (int) $this->swap_model, PDO::PARAM_INT);
        $sth->bindValue(16, $this->swap_serial, PDO::PARAM_STR);
        $sth->bindValue(17, $this->rtn_airbill, PDO::PARAM_STR);
        $sth->bindValue(18, $this->og_airbill, PDO::PARAM_STR);
        $sth->bindValue(19, $this->rcvd_from, PDO::PARAM_STR);
        $sth->bindValue(20, $this->bill_to, PDO::PARAM_STR);
        $sth->bindValue(21, (int) $this->complaint_form, PDO::PARAM_INT);
        $sth->bindValue(22, $this->po_number, PDO::PARAM_STR);
        $sth->bindValue(23, $this->problem, PDO::PARAM_STR);
        $sth->bindValue(24, $this->notes, PDO::PARAM_STR);
        $sth->bindValue(25, $this->quotation_notes, PDO::PARAM_STR);
        $sth->bindValue(26, (int) $this->has_inspection, PDO::PARAM_BOOL);
        if ($this->facility_id)
            $sth->bindValue(27, (int) $this->facility_id, PDO::PARAM_INT);
        else
            $sth->bindValue(27, null, PDO::PARAM_NULL);
        $sth->bindValue(28, (int) $this->editable, PDO::PARAM_BOOL);
        $sth->bindValue(29, (int) $this->dealer_repair, PDO::PARAM_BOOL);
        if ($this->work_time)
            $sth->bindValue(30, (int) $this->work_time, PDO::PARAM_INT);
        else
            $sth->bindValue(30, null, PDO::PARAM_NULL);
        $sth->bindValue(31, (int) $this->wo_type, PDO::PARAM_INT);
        $sth->execute();

        if (!$this->work_order)
        {
            $this->work_order = $this->dbh->lastInsertId('work_order_work_order_seq');
        }

        # Save parts array and extension for complaint codes
        $this->saveParts($form);
        $this->saveExt($form);
        # Set cert date, firmware, and software versions
        $this->UpdateAsset();

        // Generate NCMR
        if ($this->status == WO_CLOSED && is_null($this->ncmr_id))
        {
            $nh = new NCMRHolding($this->work_order);
            $nh->save();
        }
    }

    /**
     * Save changes to work order replacement parts array
     *
     * @param $form array
     */
    private function saveParts($form)
    {
        # Can have existing records, new records, or a combination
        foreach ($this->replaced_parts as $i => $part_ary)
        {
            # Do updates on exiting records, remove empty records
            if (isset ($part_ary['id']) && $part_ary['id'] > 0)
            {
                if (isset ($part_ary['part_id']) && $part_ary['part_id'] > 0)
                {
                    # Update
                    $sth = $this->dbh->prepare("UPDATE work_order_part
					SET part_id = ?, serial_number = ?
					WHERE id = ?");
                    $sth->bindValue(1, (int) $part_ary['part_id'], PDO::PARAM_INT);
                    $sth->bindValue(2, $part_ary['serial_number'], PDO::PARAM_STR);
                    $sth->bindValue(3, (int) $part_ary['id'], PDO::PARAM_INT);
                }
                else
                {
                    # This was a previously saved part but user has deselected it
                    $sth = $this->dbh->prepare("DELETE FROM work_order_part WHERE id = ?");
                    $sth->bindValue(1, (int) $part_ary['id'], PDO::PARAM_INT);
                }
                $sth->execute();
            }
            # Add New Records
            else if (isset ($part_ary['part_id']) && $part_ary['part_id'] > 0)
            {
                $part_ary['date_added'] = date('Y-m-d');

                $sth = $this->dbh->prepare("INSERT
				INTO work_order_part
				(work_order, part_id, serial_number, date_added)
				VALUES
				(?,?,?,?)");
                $sth->bindValue(1, (int) $this->work_order, PDO::PARAM_INT);
                $sth->bindValue(2, (int) $part_ary['part_id'], PDO::PARAM_INT);
                $sth->bindValue(3, $part_ary['serial_number'], PDO::PARAM_STR);
                $sth->bindValue(4, $part_ary['date_added'], PDO::PARAM_STR);
                $sth->execute();
                $this->replaced_parts[$i]['id'] = $this->dbh->lastInsertId('work_order_part_id_seq');
                $this->replaced_parts[$i]['date_added'] = $part_ary['date_added'];
            }
        }
    }
    /**
     * Save changes to work order extension array
     *
     * @param $form array
     */
    private function saveExt($form)
    {
        # Can have existing records, new records, or a combination
        foreach ($this->complaints as $i => $complaint_ary)
        {
            # Do updates on exiting records, remove empty records
            if (isset ($complaint_ary['id']) && $complaint_ary['id'] > 0)
            {
                if ($complaint_ary['complaint'] || $complaint_ary['resolution'])
                {
                    $sth = $this->dbh->prepare("UPDATE work_order_ext
					SET complaint = ?, resolution = ?
					WHERE id = ?");
                    $sth->bindValue(1, $complaint_ary['complaint'], PDO::PARAM_STR);
                    $sth->bindValue(2, $complaint_ary['resolution'], PDO::PARAM_STR);
                    $sth->bindValue(3, (int) $complaint_ary['id'], PDO::PARAM_INT);
                }
                else
                {
                    $sth = $this->dbh->prepare("DELETE FROM work_order_ext WHERE id = ?");
                    $sth->bindValue(1, (int) $complaint_ary['id'], PDO::PARAM_INT);
                }
                $sth->execute();
            }
            # Add New Records
            else if (isset ($complaint_ary['complaint']) || isset ($complaint_ary['resolution']))
            {
                $sth = $this->dbh->prepare("INSERT
				INTO work_order_ext (work_order, complaint, resolution)
				VALUES (?,?,?)");
                $sth->bindValue(1, (int) $this->work_order, PDO::PARAM_INT);
                $sth->bindValue(2, $complaint_ary['complaint'], PDO::PARAM_STR);
                $sth->bindValue(3, $complaint_ary['resolution'], PDO::PARAM_STR);
                $sth->execute();
                $this->complaints[$i]['id'] = $this->dbh->lastInsertId('work_order_ext_id_seq');
            }
        }
    }

    /**
     * Return query string for document search
     *
     * @param string
     * @param array
     */
    static public function SearchSQL($search_type, $args)
    {
        global $preferences;

        $dbh = DataStor::getHandle();

        # Get requested page
        $page = isset ($args['page']) ? (int) $args['page'] : 1;
        # Maximum number of results to show on a page.
        $LIMIT = $preferences->get('general', 'results_per_page');
        # This sets the offset for the query results.
        $OFFSET = ($page - 1) * $LIMIT;

        # Maintain valid order by field
        # Switching tabs may carry order from a non existing field
        $ORDER = (isset ($args['order'])) ? $args['order'] : "";
        $DIR = (isset ($args['dir'])) ? $args['dir'] : "";
        if ($ORDER == 'serial_num')
            $ORDER = "w.serial_num";

        # SET WHERE
        $SUB_WHERE = "";
        if ($search_type == 'simple')
            $WHERE = self::ParseSimpleSearch($args);
        else
            $WHERE = self::ParseAdvanceSearch($args);

        if (!empty ($args['model']))
        {
            if ($WHERE)
                $WHERE .= " AND w.model = " . (int) $args['model'];
            else
                $WHERE .= " WHERE w.model = " . (int) $args['model'];
        }

        if (!empty ($args['status']) && is_numeric($args['status']))
        {
            if ($WHERE)
                $WHERE .= " AND w.status = " . (int) $args['status'];
            else
                $WHERE .= " WHERE w.status = " . (int) $args['status'];
        }

        $sql = "SELECT
			count(*) as total_rows
		FROM work_order w
		INNER JOIN work_order_status s ON w.status = s.status_id
		INNER JOIN lease_asset_status a ON w.model = a.model_id AND w.serial_num = a.serial_num
		INNER JOIN equipment_models e ON w.model = e.id
		INNER JOIN wo_types wot ON w.wo_type = wot.wo_type
		LEFT JOIN facilities f ON w.facility_id = f.id
		LEFT JOIN rma_quotation rma on w.work_order = rma.work_order
		LEFT JOIN users u_o ON w.open_by = u_o.id
		LEFT JOIN users u_s ON w.last_mod_by = u_s.id
		LEFT JOIN users u_c ON w.close_by = u_c.id
		{$WHERE}";
        $sth = $dbh->query($sql);
        $total_rows = $sth->fetchColumn();

        # Query to get desired records
        $sql = "
		SELECT
			w.work_order,
			w.work_order as document_id,
			w.model as \"model_id\",
			e.model,
			w.serial_num,
			f.accounting_id,
			rma.status as \"rma_status\",
			w.complaint_form,
			s.status_text as \"wo_status\",
			w.open_date,
			u_o.firstname || ' ' || u_o.lastname as \"open_by\",
			w.close_date,
			u_c.firstname || ' ' || u_c.lastname as \"closed_by\",
			u_s.firstname || ' ' || u_s.lastname as \"serviced_by\",
			w.editable,
			w.has_inspection,
			w.dealer_repair,
			w.wo_type,
			wot.name as type_desc,
			CASE w.wo_type
				WHEN 2 THEN true
				ELSE false
			END as field_wo,
			a.id as \"asset_id\",
			a.status as \"asset_status\",
			a.substatus as \"asset_substatus\",
			a.facility_id,
			CASE
				WHEN f.accounting_id LIKE '___6__' THEN TRUE
				ELSE FALSE
			END AS is_dealer,
			a.barcode,
			w.work_time,
			pm.ipm_count,
			$total_rows total_rows
		FROM work_order w
		INNER JOIN work_order_status s ON w.status = s.status_id
		INNER JOIN lease_asset_status a ON w.model = a.model_id AND w.serial_num = a.serial_num
		INNER JOIN equipment_models e ON w.model = e.id
		INNER JOIN wo_types wot ON w.wo_type = wot.wo_type
		LEFT JOIN facilities f ON w.facility_id = f.id
		LEFT JOIN (
			SELECT p.work_order, count(*) as ipm_count
			FROM wo_pm p
			GROUP BY p.work_order
		) pm ON w.work_order = pm.work_order
		LEFT JOIN rma_quotation rma on w.work_order = rma.work_order
		LEFT JOIN users u_o ON w.open_by = u_o.id
		LEFT JOIN users u_s ON w.last_mod_by = u_s.id
		LEFT JOIN users u_c ON w.close_by = u_c.id
		{$WHERE}";
        //echo "<pre>$sql</pre>";

        # Search for office records
        # Match on any office name in the company
        #
        //$sql .= $FILTER;

        // Order the results
        if ($ORDER)
            $sql .= "\nORDER BY $ORDER $DIR";

        // Page the results
        if ($LIMIT)
            $sql .= "\nLIMIT $LIMIT OFFSET $OFFSET";

        // echo "<pre>$sql</pre>";
        return $sql;
    }

    /**
     * Update Part SO field and set posted date
     *
     * @param mixed
     * @param integer
     */
    public function SetPartSO($part_id, $mas_so)
    {
        if (!is_array($part_id))
            $part_id = array($part_id);

        $part_list = implode(',', $part_id);

        if ($part_list)
        {
            $sth = $this->dbh->prepare("UPDATE work_order_part
			SET
				mas_sales_order = ?,
				date_posted = CURRENT_DATE
			WHERE id IN ($part_list)");
            $sth->bindValue(1, $mas_so, PDO::PARAM_INT);
            $sth->execute();
        }
    }

    /**
     * Deletes this work order from the database.
     */
    public function delete()
    {
        $sth = $this->dbh->prepare('DELETE FROM work_order WHERE work_order = ?');
        $sth->bindValue(1, $this->work_order, PDO::PARAM_INT);
        $sth->execute();
    }
    /**
     * UPDATE work order temp accessory table.
     * Set Disposition of Attached Accessory
     */
    public function setdispositionofattachedaccessory($field, $att_asset_id)
    {
        $err = null;
        if ($field == 'attach_attached')
        {
            $sth = $this->dbh->prepare('UPDATE work_order_acc set scrap_attached=false, swap_attached=false,detach_attached=false  WHERE attached_asset_id=? AND work_order = ?');
            $sth->bindValue(1, $att_asset_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->work_order, PDO::PARAM_INT);
            $sth->execute();
        }
        else if ($field == 'swap_attached')
        {
            $sth = $this->dbh->prepare('UPDATE work_order_acc set  ' . $field . '=true,  scrap_attached=false,detach_attached=false  WHERE attached_asset_id=? AND work_order = ?');
            $sth->bindValue(1, $att_asset_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->work_order, PDO::PARAM_INT);
            $sth->execute();
        }
        else if ($field == 'scrap_attached')
        {
            $sth = $this->dbh->prepare('UPDATE work_order_acc set  ' . $field . '=true,  swap_attached=false,detach_attached=false  WHERE attached_asset_id=? AND work_order = ?');
            $sth->bindValue(1, $att_asset_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->work_order, PDO::PARAM_INT);
            $sth->execute();
        }
        else if ($field == 'detach_attached')
        {
            $sth = $this->dbh->prepare('UPDATE work_order_acc set  ' . $field . '=true,  scrap_attached=false,swap_attached=false  WHERE attached_asset_id=? AND work_order = ?');
            $sth->bindValue(1, $att_asset_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->work_order, PDO::PARAM_INT);
            $sth->execute();
        }
        else
        {
            $sth = $this->dbh->prepare('UPDATE work_order_acc set ' . $field . '=true  WHERE attached_asset_id=? AND work_order = ?');
            $sth->bindValue(1, $att_asset_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->work_order, PDO::PARAM_INT);
            $sth->execute();
        }


        return $err;
    }
    /**
     * UPDATE work order temp accessory table.
     * RETURN ERROR
     */
    public function verifyaccbarcode($form)
    {
        $err = null;
        $p_type = 2;
        $model_id = $form['model_id'];
        $barcode = $form['scanned_barcode'];
        $attached_barcode = $form['attached_barcode'];
        $attached_asset_id = $form['attached_asset_id'];
        $stat = $form['stat'];
        $nc = 1;

        if (($form['scanned_barcode'] != $form['attached_barcode']) && $stat == 'attached')
        {
            # Step 1 - Lets find out what the barcode should be. It may be different than what we have.
            # What this is checking is: Did user open multiple windows and change the barcode for our attached_asset

            $sth = $this->dbh->query("SELECT  barcode,serial_num FROM lease_asset WHERE id = {$attached_asset_id}");
            list($barcode_we_want, $serial_we_get) = $sth->fetch(PDO::FETCH_NUM);

            # If your scanned barcode == the barcode you want,  Update work_order_acc
            if ($form['scanned_barcode'] == $barcode_we_want)
            {
                $sth = $this->dbh->prepare("
					UPDATE work_order_acc set
					serial_number = ?,
					barcode = ?,
					attached_serial_number = ?,
					attached_barcode = ?,
					attached_verified=true,
					verified_match=true
					WHERE attached_asset_id=? AND work_order = ?");
                $sth->bindValue(1, $serial_we_get, PDO::PARAM_STR);
                $sth->bindValue(2, $barcode_we_want, PDO::PARAM_STR);
                $sth->bindValue(3, $serial_we_get, PDO::PARAM_STR);
                $sth->bindValue(4, $barcode_we_want, PDO::PARAM_STR);
                $sth->bindValue(5, (int) $form['attached_asset_id'], PDO::PARAM_INT);
                $sth->bindValue(6, (int) $this->work_order, PDO::PARAM_INT);
                $sth->execute();
                $nc = '';
            }
        }

        $accessory_asset_id = '';
        $acc_serial_number = (LeaseAsset::BarcodeToSerial($barcode)) ? LeaseAsset::BarcodeToSerial($barcode) : $barcode;
        $accessory_asset_id = LeaseAsset::exists($model_id, $acc_serial_number);
        if ($accessory_asset_id > 0)
        {
            // Update the fields for serial_number and barcode
            if (isset ($form['attached_asset_id']) && ($form['attached_asset_id'] > 0) && $nc == 1)
            {
                $sth = $this->dbh->prepare("
					UPDATE work_order_acc set
					asset_id = ?,
					serial_number = ?,
					barcode = ?,
					attached_verified=true,
					verified_match=false
					WHERE attached_asset_id=? AND work_order = ?");
                $sth->bindValue(1, (int) $accessory_asset_id, PDO::PARAM_INT);
                $sth->bindValue(2, $acc_serial_number, PDO::PARAM_STR);
                $sth->bindValue(3, $form['scanned_barcode'], PDO::PARAM_STR);
                $sth->bindValue(4, (int) $form['attached_asset_id'], PDO::PARAM_INT);
                $sth->bindValue(5, (int) $this->work_order, PDO::PARAM_INT);
                $sth->execute();
            }
            else
            {
                $sth = $this->dbh->query("SELECT  count(*) FROM work_order_acc WHERE work_order = {$this->work_order} AND asset_id={$accessory_asset_id}");
                list($count) = $sth->fetch(PDO::FETCH_NUM);

                if ($count < 1)
                {
                    $sth = $this->dbh->prepare('DELETE FROM work_order_acc WHERE work_order = ? AND model_id = ? AND new_asset=true');
                    $sth->bindValue(1, $this->work_order, PDO::PARAM_INT);
                    $sth->bindValue(2, (int) $model_id, PDO::PARAM_INT);
                    $sth->execute();

                    $sth = $this->dbh->prepare("INSERT
				INTO work_order_acc
				(work_order, model_id, asset_id,serial_number, barcode,type_id,new_asset)
				VALUES
				(?,?,?,?,?,?,true)");
                    $sth->bindValue(1, (int) $this->work_order, PDO::PARAM_INT);
                    $sth->bindValue(2, (int) $model_id, PDO::PARAM_INT);
                    $sth->bindValue(3, (int) $accessory_asset_id, PDO::PARAM_INT);
                    $sth->bindValue(4, $acc_serial_number, PDO::PARAM_STR);
                    $sth->bindValue(5, $form['scanned_barcode'], PDO::PARAM_STR);
                    $sth->bindValue(6, (int) $p_type, PDO::PARAM_INT);
                    $sth->execute();
                }
            }
        }
        else
        {
            $err = "NO ASSET FOUND FOR BARCODE:\n {$form['scanned_barcode']}";
        }
        return $err;
    }

    /**
     * Display html workorder form
     *
     * @param $form array
     */
    public function ShowForm($form)
    {
        global $user, $sh;

        $rma_quote = '';

        if ($form['submit_action'] == 'Save')
            $action = $form['action'];
        else
            $action = $form['submit_action'];

        $asset_id = $asset_status = 0;
        $lease_asset_barcode = null;
        if ($this->model && $this->serial_num)
        {
            # Get device and its status here.
            $status_h = $this->dbh->query("SELECT
			a.id, a.status,a.barcode
			FROM lease_asset_status a
			WHERE a.model_id = {$this->model} AND a.serial_num = '{$this->serial_num}'");
            list($asset_id, $asset_status, $lease_asset_barcode) = $status_h->fetch(PDO::FETCH_NUM);
        }

        if (!$this->editable || $asset_status == LeaseAssetTransaction::$QUARANTINE)
        {
            $this->ShowPrintForm($form);
            return;
        }

        # Setup Form inputs
        $IncidentYN = ($this->incident_tag) ? "Yes" : "No";
        $status_options = self::createStatusList($this->status);
        $problem = htmlentities($this->problem, ENT_QUOTES);
        $notes = htmlentities($this->notes);

        $quotation_notes = htmlentities($this->quotation_notes);

        $investigation_statement = htmlentities($this->incident_investigation_statement);

        # Open By and Date
        if ($this->open_by < 1)
            $this->open_by = $user->getId();
        $this->open_date = ($this->open_date > 0) ? $this->open_date : time();
        $date = date('Y-m-d', $this->open_date);
        $time = date('g:i a', $this->open_date);

        # Last Mod By
        if ($this->last_mod_by < 1)
            $this->last_mod_by = $user->getId();

        # Close Date
        $c_time = $c_date = '';
        if ($this->close_date > 0)
        {
            $c_date = date('Y-m-d', $this->close_date);
            $c_time = date('g:i a', $this->close_date);
        }

        # Get Facility, CPM, or Vendor
        if ($this->user_id)
            $account = new User($this->user_id);
        //		else if ($this->vendor_id)
//			$account = new Vendor($this->vendor_id);
        else
            $account = new CustomerEntity($this->facility_id);

        $show_name = ($account->getEntityType() == CustomerEntity::$ENTITY_PATIENT)
            ? ($sh->isOnLAN() && $user->hasAccessToApplication('hippa_access'))
            : true;
        $account_name = ($show_name) ? $account->getName() : 'Customer';

        $customer_description = "<textarea name='problem' style='height:5.5em; width:80%;'>{$problem}</textarea>";
        # If the WO was generated from a complaint form disable the incident input, problem input,
        # and maintain orginal CF value
        if ($this->complaint_form)
        {
            $customer_description = "<p style='width:80%; border: 1px solid gray; padding:2px; background-color:white;margin:0;'>{$problem}&nbsp;</p>
			<input type='hidden' name='problem' value='{$problem}'/>";
        }

        # Barcode Input CR 2197
        $bar_code = ($this->bar_code) ? $this->bar_code : $lease_asset_barcode;
        $bar_code_input = "<input type='hidden' name='bar_code' value='{$bar_code}' />{$bar_code}";
        if ($this->wo_type == 3)
            $bar_code_input = "<input type='text' name='bar_code' value='{$bar_code}' />";

        # Get Equipment
        if ($this->model && $this->serial_num)
        {
            $equipment_list = "<input type='hidden' name='model' value='{$this->model}'/>{$this->model_number} {$this->model_description}";
            $serial_input = "<input type='hidden' name='serial_num' value='{$this->serial_num}' />{$this->serial_num}";
        }
        else
        {
            # Can not get here from normal navigation
            # Force a valid model and serial from select
            $equipment_list = "
			<select name='model' OnChange='SetSerial(this);'>
				<option value=''>--Select Device--</option>";
            $equipment_list .= Forms::createCustomerDeviceList($this->facility_id, $this->model, $this->serial_num);
            $equipment_list .= "\n			</select>";

            $serial_input = "<input type='text' name='serial_num' value='{$this->serial_num}' size='20' readonly/>";
        }

        # Set complaint rows
        $complaints = 0;
        $complaint_codes = "";
        foreach ($this->complaints as $complaint_ary)
        {
            $complaint_codes .= $this->GetComplaintRow($complaints);
            $complaints++;
        }
        if ($complaints == 0)
            $complaint_codes .= $this->GetComplaintRow($complaints);

        # Add a row for each existing part
        $parts = 0;
        $part_rows = "";

        foreach ($this->replaced_parts as $part_ary)
        {
            $part_rows .= $this->GetPartRow($parts);
            $parts++;
        }

        # Add a row for adding new parts if not closed
        if ($this->status <> WO_CLOSED)
            $part_rows .= $this->GetPartRow($parts);

        # Add section header if there are parts to show
        $part_section = ($part_rows) ? "<tr class='form_section'><td colspan='6'>Parts Replaced</td></tr>{$part_rows}" : "";


        #cAdded for cr 2081
        $open_PM_Link = "onclick=\"OpenPMWindow(this, {$this->work_order});\"";
        $incident_row = '';

        if ($this->incident_tag)
        {
            $incident_row = "<tr>
				<th class='form' colspan='6' id='incident_cell'>
					{$this->incident_investigation_statement}
				</th>
			</tr>";

        }


        # Add a row for IPM/Inspection
        $pm_count = count($this->pm_forms);
        if ($this->wo_pm_version || $pm_count)
        {
            $pm_link = "";

            if ($pm_count)
            {
                # Add link for every saved PM
                foreach ($this->pm_forms as $pm)
                {
                    $pm_link .= "<input class='submit' type='button' name='pm_{$pm['id']}' value='IPM'
					{$open_PM_Link}/><span id='pm_{$pm['id']}_pass'></span>";
                }
            }
            ///////////////////////////////////////////////////////////////////////////////////////////////////////
            # Give a link to add the first New PM
            else
            {
                $pm_link .= "<input class='submit' type='button' id='pm_new' name='pm_new' value='New IPM'
				{$open_PM_Link}/><span id='pm_0_pass'></span>";
            }
            ///////////////////////////////////////////////////////////////////////////////////////////////////////
            # If less than two PMs add button for adding a New PM - This should go away in the future
            if ($pm_count > 0 && $pm_count < 2 && (!$this->incident_tag || $this->incident_pass))
            {
                $pm_link .= "<input class='submit' type='button' id='pm_new' name='pm_new' value='New IPM'
				{$open_PM_Link}/><span id='pm_0_pass'></span>";
            }
            ///////////////////////////////////////////////////////////////////////////////////////////////////////

            $ipm_row = "<tr>
				<th class='form' colspan='6' id='ipm_cell'>
					IPM Testing / Inspection Attached:
					<input type='hidden' name='has_inspection' value='0'/>
					$pm_link
				</th>
			</tr>";
        }
        else
        {
            $chk_inspection_y = ($this->has_inspection) ? "checked" : "";
            $chk_inspection_n = ($this->has_inspection) ? "" : "checked";

            $ipm_row = "<tr>
				<th class='form' colspan='6'>
					IPM Testing / Inspection Attached:
					<input type='radio' name='has_inspection' value='1' {$chk_inspection_y} /> Yes
					<input type='radio' name='has_inspection' value='0' {$chk_inspection_n} /> No
				</th>
			</tr>";
        }



        # Show last mod by
        $last_mod_date = date('m/d/Y', $this->last_mod_date);

        if (preg_match('/1969/i', $last_mod_date))
        {

            $last_mod_date = '';
            $signature_row = '';

        }
        else
        {

            $signature_row = "<tr><th class='form' colspan='6'>Signed By: [{$this->last_mod_by_name} - {$last_mod_date}]<br/>
			<p style='font-size: 7pt;padding:0;margin: 0; text-align:left;'>
			This electronic signature identifies the signatory for this document.
			It provides authorship and indicates the signatory's approval of the information contained within.</p></th></tr>";

        }


        $javascript = $save_row = "";

        # Add save button if editable
        if ($this->editable)
        {
            $save_row = "
			<tr><td class='buttons' colspan='6'>
				<input type='button' class='submit'  onClick='noEnterSubmit(this.form);'  name='submit_button_action' value='Save'/>
			</td></tr>";

            $span = 8;
            $previous = "";
            if ($this->last_mod_by_name)
            {
                $span = 2;
                # Add previous
                $previous = "
				<th class='form' colspan='4'>
					Previous Signature: [{$this->last_mod_by_name} - {$last_mod_date}]
				</th>";
            }

            # Add signature input
            $signature_row = "
			<tr>
				<th class='form' colspan='{$span}'>Electronic Signature:
				<input type='password' name='signature' size='20' maxlength='64'/>
				</th>
				{$previous}
			</tr>
			<tr>
				<td class='form' colspan='8'>
					<p style='font-size: 7pt;padding:0;margin: 0; text-align:left;'>
					This is an electronic signature identifying the signatory for this document.
					It provides authorship and indicates the signatory's approval of the information contained within.
					</p>
				</td>
			</tr>";
        }

        if ($this->dealer_repair)
        {
            $this->loadRma();
            if (isset ($this->rma_detail) && !empty ($this->rma_detail))
            {
                $rmaStatus = $this->rma_detail[0]['status'];

            }
        }

        $dealer_repair_note = ($this->dealer_repair) ? "
		<tr class='form_section'>
			<td colspan='6' align='center' style='font-size:20pt;background-color:#ffff47;'>RMA Repair</td>
		</tr>" : "";

        $epochnum = '';
        if (isset ($this->rma_detail[0]['sent_epoch']))
            $epochnum = $this->rma_detail[0]['sent_epoch'];

        $rma_link = "<input class='submit' type='button' name='rma_{$this->work_order}' value='Repair Estimate' ondblclick='return NoAction(event);' onclick='OpenRMAWindow({$this->work_order});'/>";

        if (isset ($this->rma_detail[0]['rma_id']))
        {
            $rma_id = $this->rma_detail[0]['rma_id'];
            $rma_link .= ' ' . $this->rma_detail[0]['status_text'];
            $rma_link .= "<input type=hidden name='rma_status' value='$rmaStatus'><input type=hidden name='rma_id' value='$rma_id'>";
        }
        else
            $rma_link .= ' No Quote<input type=hidden name="rma_id" value=0>';

        $rmaQuoteRow = '';

        if ((isset ($this->rma_detail[0]['rma_id'])) && (($this->rma_detail[0]['status_text'] != 'Customer Approved') || ($this->rma_detail[0]['status_text'] != 'Customer Denied')))
        {
            $rmaQuoteRow = "
			<tr>
				<th class='form' >Manager Override#</th>
				<td colspan=3 class='form'>
					<input type='text' name='quote_number' id='quote_number' value='$epochnum' />
					<input type='hidden' name='parts_count' id='parts_count' value='$parts' />
				</td>
			</tr>";
        }

        //$rmanotes='';
        $rma_notes = "<textarea name='quotation_notes' maxlength=700 style='height:5.5em; width:80%;'>{$quotation_notes}</textarea>";

        $rma_quote = ($this->dealer_repair) ? "
		<tr class='form_section'>
			<th colspan='6' class='form' colspan='6'>RMA Quotation  $rma_link</th>
		</tr>
		<tr class='form_section'>
			<th colspan='6' class='form' colspan='6'>Quotation Notes <br> $rma_notes</th>
		</tr>" : "";

        $sth = $this->dbh->prepare("select count(part_id) from work_order_part where work_order=$this->work_order");
        $sth->execute();
        $actualTotal = $sth->fetchColumn();

        if (isset ($rma_id) && ($rma_id > 0))
        {

            $sth = $this->dbh->prepare("select sum(quantity) from rma_estimate where rma_id=" . $rma_id);
            $sth->execute();
            $estTotal = $sth->fetchColumn();
        }

        $warranty_valid = null;
        if ($this->dealer_repair)
        {
            $query = $this->dbh->query("SELECT
				la.id
			FROM equipment_models em
			INNER JOIN lease_asset la ON la.model_id = em.id
			WHERE em.model = '{$this->model_number}'
			AND la.serial_num = '{$this->serial_num}'");
            $asset_id = $query->fetchColumn();

            $asset = new LeaseAsset($asset_id);
            $show_warranty = true;
            $serial_num = $this->serial_num;
            $model = $this->model_number;

            if ($asset->getType() == EquipmentModel::$ACCESSORY)
            {
                $base_asset_id = $asset->getBaseUnit();

                if (isset ($base_asset_id))
                {
                    $base_asset = new LeaseAsset($base_asset_id);
                    $warranty_type = $base_asset->getWarrantyType();

                    if (isset ($warranty_type))
                    {
                        $serial_num = $base_asset->getSerial();
                        $model = $base_asset->getModel()->getName();
                    }
                    else
                        $show_warranty = false;
                }
                else
                    $show_warranty = false;
            }

            if ($show_warranty)
            {
                $query = $this->dbh->query("SELECT
					coalesce(cep.warranty_expiration_date, cep.date_shipped + wo.year_interval::INTERVAL) as warranty_end,
					coalesce(cep.maintenance_expiration_date, cep.date_shipped + ma.term_interval::INTERVAL) as agreement_end
				 FROM contract_line_item cep
				 LEFT JOIN warranty_option wo ON wo.warranty_id = cep.warranty_option_id
				 LEFT JOIN maintenance_agreement ma ON ma.id = cep.maintenance_agreement_id
				 INNER JOIN lease_asset la ON la.id = cep.asset_id
	   			 INNER JOIN equipment_models em ON em.id = la.model_id
	   			 WHERE la.serial_num = '{$serial_num}'
	   		 	 AND em.model = '{$model}'");
                list($warranty_end, $agreement_end) = $query->fetch(PDO::FETCH_NUM);

                if ($warranty_end)
                    $warranty_end = strtotime($warranty_end);
                else
                    $warranty_end = 0;

                if ($agreement_end)
                    $agreement_end = strtotime($agreement_end);
                else
                    $agreement_end = 0;

                $query = $this->dbh->query("SELECT
					cf.open_date
				FROM work_order wo
				INNER JOIN complaint_form cf ON cf.issue_id = wo.complaint_form
				WHERE work_order = {$this->work_order}");

                $complaint_date = $query->fetchColumn();

                if ($warranty_end == 0 && $agreement_end == 0)
                {
                    $warranty_valid = "--Information not Available--";
                    $warrantyValue = 0;
                }
                else if ($warranty_end <= $complaint_date)
                {
                    if ($complaint_date > $agreement_end)
                    {
                        $warranty_valid = "<b>EXPIRED</b>";
                        $warrantyValue = 0;
                    }
                    else
                    {
                        $warranty_valid = "<span alt='Comprehensive Maintenance Agreement' title='Comprehensive Maintenance Agreement'>Under C.M.A.</span>";
                        $warrantyValue = 1;
                    }
                }
                else
                {
                    if ($complaint_date > $warranty_end)
                    {
                        $warranty_valid = "<b>EXPIRED</b>";
                        $warrantyValue = 0;
                    }
                    else
                    {
                        $warranty_valid = "Under Warranty";
                        $warrantyValue = 1;
                    }
                }

                $warranty_valid = "
				<tr>
					<th class='form'></th>
					<td class='form'></td>
					<th class='form'></th>
					<td class='form'></td>
					<th class='form'>Warranty:</th>
					<td class='form'>
						{$warranty_valid}
						<input type=hidden value='{$warrantyValue}' name=rma_warranty >
					</td>
				</tr>";
            }
        }

        $startTimer = time();

        if ($action == 'Field WO' || $action == 'Edit Field WO')
        {
            $topdiv = "<div style='float:left'>Field Work Order</div>";
            $wodiv = "<div style='float:right; font-size: 12pt;'> Field Work Order #: {$this->work_order}&nbsp;&nbsp;&nbsp;</div>";
        }
        else
        {
            $topdiv = "<div style='float:left'>Service Center Work Order</div>";

            $wodiv = "<div style='float:right; font-size: 12pt;'> Work Order #: {$this->work_order}&nbsp;&nbsp;&nbsp;</div>";
        }

        $ponum = '';
        if (isset ($this->rma_detail[0]['po_number']))
            $ponum = $this->rma_detail[0]['po_number'];

        # Check for previous scrap request
        $log = $this->GetWFLog(self::$WF_SCRAP_ACT);
        $chk_scrap = (empty ($log->id)) ? "" : "checked readonly";
        $reason = (isset ($log->reason)) ? htmlentities($log->reason) : "";

        $attachment = new Attachment(0);
        $attachment->reference_elem = 'WorkOrder';
        $attachment->reference_key = $this->work_order;
        $iframe_url = Config::$WEB_PATH . "/templates/workorder/wo_electrode_test_file_list.php?reference_key=$this->work_order&reference_elem=WorkOrder";

        $TestFileUploadsection = "<div id='wo_attachment_edit'>";
        $attachment = new Attachment(0);
        $attachment->reference_elem = 'WorkOrder';
        $attachment->reference_key = $this->work_order;
        ob_start();
        include ('templates/workorder/wo_attachment.php');
        $TestFileUploadsection .= ob_get_contents();
        ob_end_clean();
        $TestFileUploadsection .= "</div>
		<script type='text/javascript'>$(function() { $('#wo_attachment_edit').dialog(att_conf); });</script>";

        $base_acc_row = "<tr><td colspan='4'>No Accessories Found</td></tr>";
        if ($this->device_type == 1)
        {
            $accessory_area = $this->getAccessoryRows();
            $base_acc_row = "<tr><td colspan='4' id='acc_display_area'>{$accessory_area}</td></tr>";
        }
        #END for cr 2125

        $mgr_close_btn = '';
        if ($this->editable && $this->status == WO_CLOSED && $asset_status != 'WIP')
        {
            $mgr_close_btn = "
			<form name='close_wo_form' id='close_wo_form' action='{$_SERVER['PHP_SELF']}' method='post'>
			<input type='hidden' name='work_order' value='{$this->work_order}'>
			<input type='hidden' name='submit_action' value='change'>
			<input type='hidden' name='field' value='editable'>
			<input type='hidden' name='value' value='0'>
			<table border='1' cellspacing='1' cellpadding='5'  align='center'><tr>
			<td align='center'><b>Work order <u>Editable</u> while in FGI</b><br><br><input type=submit value='Reset Edit Flag'></td>
			</tr></table>
			</form>";
        }

        $p_term = "";
        if (!isset ($_REQUEST['skip_mas']))
        {
            $p_term = $account->GetPaymentTerms(true);
        }

        # Some actions will set a message otherwise empty
        echo "{$this->message}
<style type=\"text/css\">
table.section
{
	font-size: 8pt;
	text-align:left;
}
p.note
{
	font-size: 7pt;
	padding:0;
	margin: 0 0 0 5px;
	text-align:left;
	background-color: #f7f7ff;
}

</style>
{$this->GetJS($parts, $complaints)}
{$mgr_close_btn}
	<form name='wo_form' id='wo_form' action='{$_SERVER['PHP_SELF']}' method='post' onSubmit=\"return ValidateForm(this);\">
	<input type='hidden' name='work_order' value='{$this->work_order}' />
	<input type='hidden' name='rcvd_from' value='{$this->rcvd_from}' />
	<input type='hidden' name='facility_id' value='{$this->facility_id}' />
	<input type='hidden' name='open_date' value='{$this->open_date}' />
	<input type='hidden' name='open_by' value='{$this->open_by}' />
	<input type='hidden' name='close_date' value='{$this->close_date}' />
	<input type='hidden' name='close_by' value='{$this->close_by}' />
	<input type='hidden' name='complaint_form' value='{$this->complaint_form}' />
	<input type='hidden' name='location' value='{$this->location}' />
	<input type='hidden' name='svc_code_01' value='{$this->svc_code_01}' />
	<input type='hidden' name='swap_requested' value='{$this->swap_requested}' />
	<input type='hidden' name='swap_model' value='{$this->swap_model}' />
	<input type='hidden' name='swap_serial' value='{$this->swap_serial}' />
	<input type='hidden' name='rtn_airbill' value='{$this->rtn_airbill}' />
	<input type='hidden' name='og_airbill' value='{$this->og_airbill}' />
	<input type='hidden' name='bill_to' value='{$this->bill_to}' />
	<input type='hidden' name='start_timer' value='{$startTimer}' />
	<input type='hidden' name='action' value='{$action}' />
	<input type='hidden' name='current_model' value='{$this->model}' />
	<input type='hidden' name='current_serial' value='{$this->serial_num}' />
	<input type='hidden' name='current_asset_id' value='{$asset_id}' />
	<input type='hidden' name='device_type' value='{$this->device_type}' />
	<input type='hidden' name='submit_action' value='Save' />
	<table border='0' cellspacing='2' cellpadding='4' class='form' id='wo_table'>
		<tr>
			<th class='subheader' colspan='6'>
			{$topdiv}
			{$wodiv}
			</th>
		</tr>
		{$dealer_repair_note}
		<tr class='form_section'>
			<td colspan='6'>Customer / Facility Information</td>
		</tr>
		<tr>
			<th class='form' nowrap>Account Name:</th>
			<td class='form'>{$account_name}</td>
			<th class='form' nowrap>Customer ID:</th>
			<td class='form'>{$this->rcvd_from}</td>
			<th class='form' style='font-size:small;' nowrap>Open On:</th>
			<td class='form' style='font-size:small;' nowrap>{$date}&nbsp;&nbsp;{$time}</td>
		</tr>
		<tr>
			<th class='form'>Address:</td>
			<td class='form'>{$account->getAddress()}</td>
			<th class='form'>Payment Terms:</th>
			<td class='form'>{$p_term}</td>
			<th class='form' style='font-size:small;' nowrap>Closed On:</th>
			<td class='form' style='font-size:small;'>{$c_date}&nbsp;&nbsp;{$c_time}</td>
		</tr>
		<tr>
			<th class='form'>City:</th>
			<td class='form'>{$account->getCity()}</td>
			<th class='form'>State:</th>
			<td class='form'>{$account->getState()}</td>
			<th class='form'>Zip:</th>
			<td class='form'>{$account->getZip()}</td>
		</tr>
		<tr class='form_section'>
			<td colspan='6'>Product Information</td>
		</tr>
		<tr>
			<th class='form'>Model:</th>
			<td class='form'>
				{$equipment_list}
			</td>
			<th class='form'>Serial:</th>
			<td class='form'>
				{$serial_input}
			</td>
			<th class='form'>Barcode:</th>
			<td class='form'>
				{$bar_code_input}
			</td>
		</tr>
		<tr>
			<th class='form'>Manufacture Date:</th>
			<td class='form'>
				{$this->manufacture_date}
			</td>
			<th class='form'>Service Date:</th>
			<td class='form'>
				{$this->service_date}
			</td>
			<th class='form'>Invoice / PO:</th>
			<td class='form'>
				<input type='text' name='po_number' disabled  value='{$ponum}' size='15' maxlength='32'/>
			</td>
		</tr>
		{$warranty_valid}
		<tr class='form_section'>
			<td colspan='6'>Problem Details</td>
		</tr>
		<tr>
			<th class='form'>Status:</th>
			<td class='form' colspan='5'>
				<select id='status' name='status' onchange='SetPMText();'>
					<option value=''>--Select Status--</option>
					{$status_options}
				</select>
			</td>
		</tr>
		{$complaint_codes}
		<tr>
			<th class='form' colspan='6'>Customer's Description:<br/>
				{$customer_description}
			</th>
		</tr>
		<tr>
			<th class='form'>
				Incident:
				<input type='text' readonly value='$IncidentYN' name='is_incident' size='3'>
			</th>
			<td class='form nested' colspan='3'>
				<span id='incident_list_0' class='submenu'>
					Worksheet(s):
					{$this->GetWSLinks()}
					<a style='margin-left: 2px; margin-right: 2px;' target='_self'
						href='{$_SERVER['PHP_SELF']}?act=ws&id=0&type=Malfunction&workorder_id={$this->work_order}'
						title='Complete MDR Decision Tree'>New</a>
				</span>
			</td>
			<th class='form'>CAPA:</th>
			<td class='form nested'>
				<span class='submenu'>
					<a style='margin-left: 2px; margin-right: 2px;' onclick='OpenCapaSelect(event);' alt='Link to CAPA' title='Link to CAPA'>Add to Capa</a>
				</span>
			</td>
		</tr>
		<tr>
			<th class='form'>Electrode Test Files:</th>
			<td class='form' colspan='5'>
				<a style='margin-left: 2px; margin-right: 2px;'  onclick='OpenAttachmentViewDialog(\"{$iframe_url}\");' alt='Link to files' title='Link to file'>Electrode Test File View</a> &nbsp;&nbsp;&nbsp;&nbsp;
				<a style='margin-left: 2px; margin-right: 2px;'  onclick='OpenAttachmentUploadDialog({$this->work_order});' alt='Link to files' title='Link to file'>Electrode Test File Upload</a>
			</td>
		</tr>
		<tr>
			<th class='form' colspan='6'>Maintenance and / or Repairs Performed:<br/>
				<textarea name='notes' style='height:5.5em; width:80%;'>{$notes}</textarea>
			</th>
		</tr>
		{$ipm_row}
		{$incident_row}
		{$rma_quote}{$rmaQuoteRow}
		<tr id='scap_req'>
			<th class='form' colspan='6'>
				<lable for='scrap_btn'>Recommend Scrap</label>
				<input type='checkbox' id='scrap_btn' name='scrap' value='1' onclick='ShowScrapSection();' $chk_scrap/>
			</th>
		</tr>
		<tr id='scrap_reason'>
			<th class='form' colspan='6'>
				Please state why the unit is being recommended for Scrapped.<br/>
				<textarea id='reason' name='reason' cols='80' rows='4'>{$reason}</textarea>
			</th>
		</tr>
		{$part_section}
		<tr class='form_section'>
			<td colspan='6'>Accessories</td>
		</tr>
		{$base_acc_row}
		<tr class='form_section'>
			<td colspan='6'>Signature</td>
		</tr>
		{$signature_row}
		{$save_row}
		<tr>
			<td class='buttons' colspan='6'>
				{$this->wo_legal_note}
			</td>
		</tr>
	</table>
	</form>
{$TestFileUploadsection}
<div id='capa_menu' class='conmenu' style='display:none; background-color:#FFFFFF; height:160px;'>
<form name='capa_link' method='POST' action='{$_SERVER['PHP_SELF']}'>
<input type='hidden' name='act' value='join'/>
<input type='hidden' name='workorder_id' value='{$this->work_order}'/>
	<div style='padding: 2px;' class='sec_opt'>
		CAPA Documents:
		<span class='close' onclick='CloseCapaMenu();' alt='Close' title='Close'>x</span>
	</div>
	{$this->CapaMenu()}
</form>
</div>

<div id='elec_file'  style='display:none; background-color:#FFFFFF; height:160px;'>
    <iframe id='efIframe' src='' width='700' frameBorder='0'></iframe>
</div>";

    }


    /**
     * Display html workorder form
     *
     * @param $form array
     */
    public function ShowPrintForm($form)
    {
        global $user, $sh;

        $asset_id = $asset_status = 0;
        if ($this->model && $this->serial_num)
        {
            # Get device and its status here.
            $status_h = $this->dbh->query("SELECT
			a.id, a.status
			FROM lease_asset_status a
			WHERE a.model_id = {$this->model} AND a.serial_num = '{$this->serial_num}'");
            list($asset_id, $asset_status) = $status_h->fetch(PDO::FETCH_NUM);
        }

        # Setup Form inputs
        $IncidentYN = ($this->incident_tag) ? "Yes" : "No";

        $problem = nl2br(htmlentities($this->problem));
        if ($problem == '')
            $problem = '&nbsp';
        $notes = nl2br(htmlentities($this->notes));
        if ($notes == '')
            $notes = '&nbsp';

        # Open By and Date
        if ($this->open_by < 1)
            $this->open_by = $user->getId();
        $this->open_date = ($this->open_date > 0) ? $this->open_date : time();
        $date = date('Y-m-d', $this->open_date);
        $time = date('g:i a', $this->open_date);

        # Last Mod By
        if ($this->last_mod_by < 1)
            $this->last_mod_by = $user->getId();

        # Close Date
        $c_time = $c_date = '';
        if ($this->close_date > 0)
        {
            $c_date = date('Y-m-d', $this->close_date);
            $c_time = date('g:i a', $this->close_date);
        }

        # Get Facility, CPM, or Vendor
        if ($this->user_id)
            $account = new User($this->user_id);
        //		else if ($this->vendor_id)
//			$account = new Vendor($this->vendor_id);
        else
            $account = new CustomerEntity($this->facility_id);

        $show_name = ($account->getEntityType() == CustomerEntity::$ENTITY_PATIENT)
            ? ($sh->isOnLAN() && $user->hasAccessToApplication('hippa_access'))
            : true;
        $account_name = ($show_name) ? $account->getName() : 'Customer';

        # Set complaint rows
        $complaint_codes = "";
        foreach ($this->complaints as $complaint_ary)
        {
            $complaint_codes .= "
			<tr>
				<th class='form' style='padding-top:0;'>Complaint:</th>
				<td class='form' style='padding-top:0;'>
					{$complaint_ary['complaint']}: {$complaint_ary['code_description']}
				</td>
				<th class='form' style='padding-top:0;'>Resolution:</th>
				<td class='form' colspan='3' style='padding-top:0;'>
					{$complaint_ary['resolution']}: {$complaint_ary['resolution_description']}
				</td>
			</tr>";

        }

        # Add a row for each existing part
        $part_rows = "";
        foreach ($this->replaced_parts as $part_ary)
        {
            $part_rows .= "
			<tr>
				<th class='form'>Part #:</th>
				<td class='form'>{$part_ary['code']} :: {$part_ary['name']}</td>
				<th class='form'>Serial #:</th>
				<td class='form'>{$part_ary['serial_number']}</td>
				<th class='form'>SO #:</th>
				<td class='form'>{$part_ary['mas_sales_order']}</td>
			</tr>";
        }



        # Added for cr 2081
        $incident_row = '';
        if ($this->incident_tag)
        {
            $incident_row = "<tr>
				<th class='form' colspan='6' id='incident_cell'>
					{$this->incident_investigation_statement}
				</th>
			</tr>";
        }

        # Add a row for IPM/Inspection
        $pm_count = count($this->pm_forms);
        if ($this->wo_pm_version || $pm_count)
        {
            if ($pm_count)
            {
                $pm_link = "";
                foreach ($this->pm_forms as $pm)
                {
                    $pass = "(Incomplete)";
                    if ($pm['completed_date'])
                    {
                        if ($pm['pass'])
                            $pass = " (Passed)";
                        else
                            $pass = " (Failed)";
                    }

                    $pm_link .= "<input class='submit' type='button' name='pm_{$pm['id']}' value='IPM'
					onclick=\"OpenPMWindow(this, {$this->work_order});\"/><span id='pm_{$pm['id']}_pass'> $pass</span>";
                }
            }
            else
            {
                if ($this->has_inspection)
                    $pm_link = "<span class='info'>Yes</span>";
                else
                    $pm_link = "<span class='info'>No</span>";
            }


            $ipm_row = "<tr>
				<th class='form' colspan='6'>
					IPM Testing / Inspection Attached:
					$pm_link
				</th>
			</tr>";
        }
        else
        {
            $chk_inspection_y = ($this->has_inspection) ? "checked" : "";
            $chk_inspection_n = ($this->has_inspection) ? "" : "checked";
            $h_ins_y = ($this->has_inspection) ? "true" : "false";
            $h_ins_n = ($chk_inspection_n) ? "true" : "false";

            $ipm_row = "<tr>
				<th class='form' colspan='6'>
					IPM Testing / Inspection Attached:
					<input type='radio' name='has_inspection' id='h_ins_y' value='1' onClick=\"this.checked={$h_ins_y}; document.getElementById('h_ins_n').checked={$h_ins_n};\" {$chk_inspection_y} /> Yes
					<input type='radio' name='has_inspection' id='h_ins_n' value='0' onClick=\"this.checked={$h_ins_n}; document.getElementById('h_ins_y').checked={$h_ins_y};\" {$chk_inspection_n} /> No
				</th>
			</tr>";

        }

        $rma_row = "";

        # Add signature input

        # Show last mod by
        $last_mod_date = date('m/d/Y', $this->last_mod_date);

        if (preg_match('/1969/i', $last_mod_date))
        {
            $last_mod_date = '';
            $signature_row = '';
        }
        else
        {
            $signature_row = "<tr><th class='form' colspan='6'>Signed By: [{$this->last_mod_by_name} - $last_mod_date]<br/>
			<p style='font-size: 7pt;padding:0;margin: 0; text-align:left;'>
			This electronic signature identifies the signatory for this document.
			It provides authorship and indicates the signatory's approval of the information contained within.</p></th></tr>";
        }

        # Add section header if there are parts to show
        $part_section = ($part_rows) ? "<tr class='form_section'><td colspan='6'>Parts Replaced</td></tr>{$part_rows}" : "";

        $dealer_repair_note = ($this->dealer_repair) ? "
		<tr class='form_section'>
			<td colspan='6' align='center' style='background-color:black;color:white;'>RMA Repair</td>
		</tr>" : "";

        $background_color = "white";
        if ($this->dealer_repair)
            $background_color = "yellow";
        else if ($this->incident_tag)
            $background_color = "red";
        else if (count($this->complaints) > 0)
        {
            if ($this->complaints[0]['complaint'] == 'A9902')
                $background_color = "red";
        }
        //echo "<pre>";print_r($this->complaints[0]['complaint']);echo "</pre>";

        $ponum = '';

        $warranty_valid = null;
        if ($this->dealer_repair)
        {
            $this->loadRma();

            if (isset ($this->rma_detail[0]['po_number']))
                $ponum = $this->rma_detail[0]['po_number'];

            $query = $this->dbh->query("SELECT type_id
										 FROM equipment_models
										 WHERE model = '{$this->model_number}'");
            $type_id = $query->fetchColumn();

            if ($type_id == EquipmentModel::$BASEUNIT)
            {
                $query = $this->dbh->query("SELECT
					coalesce(cep.warranty_expiration_date, cep.date_shipped + wo.year_interval::INTERVAL) warranty_end,
					coalesce(cep.maintenance_expiration_date, cep.date_shipped + ma.term_interval::INTERVAL) as agreement_end
				 FROM contract_line_item cep
				 LEFT JOIN warranty_option wo ON wo.warranty_id = cep.warranty_option_id
				 LEFT JOIN maintenance_agreement ma ON ma.id = cep.maintenance_agreement_id
				 INNER JOIN lease_asset la ON la.id = cep.asset_id
	   			 INNER JOIN equipment_models em ON em.id = la.model_id
	   			 WHERE la.serial_num = '{$this->serial_num}'
	   		 	 AND em.id = '{$this->model}'");
                list($warranty_end, $agreement_end) = $query->fetch(PDO::FETCH_NUM);
                if ($warranty_end)
                    $warranty_end = strtotime($warranty_end);
                else
                    $warranty_end = 0;
                if ($agreement_end)
                    $agreement_end = strtotime($agreement_end);
                else
                    $agreement_end = 0;

                $query = $this->dbh->query("SELECT
					cf.open_date
				FROM work_order wo
				INNER JOIN complaint_form cf ON cf.issue_id = wo.complaint_form
				WHERE work_order = {$this->work_order}");
                $complaint_date = $query->fetchColumn();

                if ($warranty_end == 0 && $agreement_end == 0)
                {
                    $warranty_valid = "--Information not Available--";
                    $warrantyValue = 0;
                }
                else if ($warranty_end <= $complaint_date)
                {
                    if ($complaint_date > $agreement_end)
                    {
                        $warranty_valid = "<b>EXPIRED</b>";
                        $warrantyValue = 0;
                    }
                    else
                    {
                        $warranty_valid = "<span alt='Comprehensive Maintenance Agreement' title='Comprehensive Maintenance Agreement'>Under C.M.A.</span>";
                        $warrantyValue = 1;
                    }
                }
                else
                {
                    if ($complaint_date > $warranty_end)
                    {
                        $warranty_valid = "<b>EXPIRED</b>";
                        $warrantyValue = 0;
                    }
                    else
                    {
                        $warranty_valid = "Under Warranty";
                        $warrantyValue = 1;
                    }
                }

                $rma_row .= "<tr>";
                if (isset ($this->rma_detail[0]['rma_id']))
                {
                    $rma_row .= "<th class='form'>
						RMA Quote:<input class='submit' type='button' name='rma_{$this->work_order}' value='Repair Estimate' ondblclick='return NoAction(event);' onclick='OpenRMAWindow({$this->work_order});'/>
					</th>
					<th class='form' >
						{$this->rma_detail[0]['status_text']}
					</th>";
                }

                if (isset ($this->rma_detail[0]['sent_epoch']))
                {
                    $rma_row .= "<th class='form' colspan='4'> Manager Override #{$this->rma_detail[0]['sent_epoch']}</th>";
                }

                $rma_row .= "</tr>";

                $warranty_valid = "
		<tr>
			<th class='form'></th>
			<td class='form'></td>
			<th class='form'></th>
			<td class='form'></td>
			<th class='form'>Warranty:</th>
			<td class='form'>
				{$warranty_valid}
			</td>
		</tr>";
            }
        }


        if ($this->wo_type == 2)
            $header = "<th style='text-align:center; font-size:10pt;' nowrap>Field Work Order</th>
			<td align='right' width='25%' style='font-size:10pt;' nowrap>Field Work Order # <b>{$this->work_order}</b></th>";
        else
            $header = "<th style='text-align:center; font-size:10pt;' nowrap>Service Center Work Order</th>
			<td align='right' width='25%' style='font-size:10pt;' nowrap>Work Order # <b>{$this->work_order}</b></th>";

        # Check for previous scrap request
        $log = $this->GetWFLog(self::$WF_SCRAP_ACT);
        $chk_scrap = (empty ($log->id)) ? "" : "checked";
        $reason = (isset ($log->reason)) ? htmlentities($log->reason) : "";

        $attachment = new Attachment(0);
        $attachment->reference_elem = 'WorkOrder';
        $attachment->reference_key = $this->work_order;
        $iframe_url = Config::$WEB_PATH . "/templates/workorder/wo_electrode_test_file_list.php?reference_key=$this->work_order&reference_elem=WorkOrder";

        $TestFileUploadsection = "<div id='wo_attachment_edit'>";
        $attachment = new Attachment(0);
        $attachment->reference_elem = 'WorkOrder';
        $attachment->reference_key = $this->work_order;
        ob_start();
        include ('templates/workorder/wo_attachment.php');
        $TestFileUploadsection .= ob_get_contents();
        ob_end_clean();
        $TestFileUploadsection .= "</div>
		<script type='text/javascript'>$(function() { $('#wo_attachment_edit').dialog(att_conf); });</script>";


        #Get the accessories that 'are' attached to this base
        $base_acc_row = '';
        $sth = $this->dbh->prepare("SELECT
		main.base_unit_asset_id,
		main.accessory_asset_id,
		main.tstamp,
		em.id AS model_id,
		em.description,
		mem.id AS base_model_id,
		em.model || ' :: ' || em.description as acc_model,
		la.serial_num AS serial_number,
		la.barcode AS barcode,
		CASE
			WHEN find_in_array( em.base_assets, mem.id) THEN 'Y'
			ELSE 'N'
		END AS base_is_in_accessory_base_assets
		FROM accessory_to_base_unit main
		INNER JOIN lease_asset la ON la.id = main.accessory_asset_id
		INNER JOIN equipment_models em ON em.id = la.model_id
		INNER JOIN lease_asset mla ON mla.id = main.base_unit_asset_id
		INNER JOIN equipment_models mem ON mem.id = mla.model_id
		INNER JOIN (
			SELECT
			accessory_asset_id,
			MAX(tstamp) as tstamp
			FROM accessory_to_base_unit
			GROUP BY accessory_asset_id
		) max on main.accessory_asset_id = max.accessory_asset_id AND main.tstamp = max.tstamp
		WHERE main.base_unit_asset_id = $asset_id");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $model_id = $row['model_id'];
            $serial_number = $row['serial_number'];
            $barcode = $row['barcode'];
            $description = $row['description'];
            $base_acc_row .= "<tr><td class='form' valign='top'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {$row['description']}</td><td class='form' valign='top'>Barcode:&nbsp;&nbsp; {$row['barcode']}</td></tr>";
        }

        $ba_hdr = "<tr><td colspan='4' class='form'>No Accessories Found</td></tr>";
        if ($base_acc_row)
        {
            $ba_hdr = "<tr><td colspan='4' id='acc_display_area'><table cellpadding=0 cellspacing=0 border=0 width='100%'>
			{$base_acc_row}
			</table></td></tr>";
        }

        $mgr_close_btn = '';
        if ($this->editable && $this->status == WO_CLOSED && $asset_status != 'WIP')
        {
            $mgr_close_btn = "
			<form name='close_wo_form' id='close_wo_form' action='{$_SERVER['PHP_SELF']}' method='post'>
			<input type='hidden' name='work_order' value='{$this->work_order}'>
			<input type='hidden' name='submit_action' value='mgr_reset_edit'>
			<input type='hidden' name='field' value='editable'>
			<input type='hidden' name='value' value='0'>
				<table border='1' cellspacing='1' cellpadding='5'  align='center'>
					<tr>
						<td align='center'><b>Work order <u>Editable</u> while in FGI</b><br><br><input type=submit value='Reset Edit Flag'></td>
					</tr>
				</table>
			</form>";
        }

        $p_term = "";
        if (!isset ($_REQUEST['skip_mas']))
        {
            $p_term = $account->GetPaymentTerms(true);
        }

        # Override main.css to produce white background
        # Certain actions will set a message otherwise empty
        echo "{$this->message}
<style type=\"text/css\">
body
{
	padding: 0;
	background-image: url('');
	background-color: white;
}

table.section
{
	font-size: 8pt;
	text-align:left;
}
p.note
{
	font-size: 6pt;
	padding:0;
	margin: 0 0 0 5px;
	text-align:left;
}
tr.form_section
{
	background-color: white;
	font-size: 9pt;
	font-weight: bold;
}
th.header
{
	background-color: white;
	font-size: 10pt;
	font-weight: bold;
	text-align: left;
}
th.form
{
	background-color: white;
	font-size: 8pt;
	text-align: left;
}
td.form
{
	background-color: white;
	font-size: 8pt;
}
td.buttons
{
	background-color: white;
	text-align: center;
	vertical-align: middle;
	border-top: 1px solid black;
}
.label_lnk
{
	position: absolute;
	top: 1px;
	right: 1px;
	font-size: 8pt;
}
</style>
{$this->GetJS(0, 0)}
{$mgr_close_btn}
	<table border='0' cellspacing='2' cellpadding='4' class='form' style='margin:0;background-color: {$background_color}'>
		<tr valign='top'>
			<th colspan='6' style='position: relative;'>
				<table width='100%'>
					<tr>
						<td align='left' width='25%'><img src='images/acpl_logo_217x60.png'></td>
						{$header}
					</tr>
				</table>
				<a class='label_lnk' href='templates/asset/wo_label.php?work_order={$this->work_order}' target='_blank' alt='Print Band Label' title='Print Band Label'>Print Band</a>
			</th>
		</tr>
		{$dealer_repair_note}
		<tr class='form_section'>
			<td colspan='6'>Customer / Facility Information</td>
		</tr>
		<tr>
			<th class='form' nowrap>Account Name:</th>
			<td class='form'>{$account_name}</td>
			<th class='form' nowrap>Customer ID:</th>
			<td class='form'>{$this->rcvd_from}</td>
			<th class='form' nowrap>Open On:</th>
			<td class='form' nowrap>{$date}&nbsp;&nbsp;{$time}</td>
		</tr>
		<tr>
			<th class='form'>Address:</td>
			<td class='form'>{$account->getAddress()}</td>
			<th class='form'>Payment Terms:</th>
			<td class='form'>{$p_term}</td>
			<th class='form' nowrap>Closed On:</th>
			<td class='form'>{$c_date}&nbsp;&nbsp;{$c_time}</td>
		</tr>
		<tr>
			<th class='form'>City:</th>
			<td class='form'>{$account->getCity()}</td>
			<th class='form'>State:</th>
			<td class='form'>{$account->getState()}</td>
			<th class='form'>Zip:</th>
			<td class='form'>{$account->getZip()}</td>
		</tr>
		<tr class='form_section'>
			<td colspan='6'>Product Information</td>
		</tr>
		<tr>
			<th class='form'>Model:</th>
			<td class='form'>
				{$this->model_number} {$this->model_description}
			</td>
			<th class='form'>Serial:</th>
			<td class='form'>
				{$this->serial_num}
			</td>
		</tr>
		<tr>
			<th class='form'>Manufacture Date:</th>
			<td class='form'>
				{$this->manufacture_date}
			</td>
			<th class='form'>Service Date:</th>
			<td class='form'>
				{$this->service_date}
			</td>
			<th class='form'>Invoice / PO:</th>
			<td class='form'>
				{$ponum}
			</td>
		</tr>
		{$warranty_valid}
		<tr class='form_section'>
			<td colspan='6'>Problem Details</td>
		</tr>
		<tr>
			<th class='form'>Status:</th>
			<td class='form' colspan='5'>
				{$this->status_text}
			</td>
		</tr>
		{$complaint_codes}
		<tr>
			<th class='form' colspan='6'>Customer's Description:<br/>
				<p style='width:80%; border: 1px solid gray; padding:2px;'>{$problem}</p>
			</th>
		</tr>
		<tr>
			<th class='form'>
				Incident:
				<input type='text' readonly value='$IncidentYN' name='is_incident' size='3'>
			</th>
			<td class='form nested' colspan='3'>
				<span id='incident_list_0' class='submenu'>
					Worksheet(s):
					{$this->GetWSLinks()}
					<a style='margin-left: 2px; margin-right: 2px;' target='_self'
						href='{$_SERVER['PHP_SELF']}?act=ws&id=0&type=Malfunction&workorder_id={$this->work_order}'
						title='Complete MDR Decision Tree'>New</a>
				</span>
			</td>
			<th class='form'>CAPA:</th>
			<td class='form nested'>
				<span class='submenu'>
					<a style='margin-left: 2px; margin-right: 2px;' onclick='OpenCapaSelect(event);' alt='Link to CAPA' title='Link to CAPA' >Add to Capa</a>
				</span>
			</td>
		</tr>
		<tr>
			<th class='form'>Electrode Test Files:</th>
			<td class='form' colspan='5'>
				<a style='margin-left: 2px; margin-right: 2px;'  onclick='OpenAttachmentViewDialog(\"{$iframe_url}\");' alt='Link to files' title='Link to file'>Electrode Test File View</a> &nbsp;&nbsp;&nbsp;&nbsp;
				<a style='margin-left: 2px; margin-right: 2px;'  onclick='OpenAttachmentUploadDialog({$this->work_order});' alt='Link to files' title='Link to file'>Electrode Test File Upload</a>
			</td>
		</tr>
		<tr>
			<th class='form' colspan='6'>Maintenance and / or Repairs Performed:<br/>
				<p style='width:80%; border: 1px solid gray; padding:2px;'>{$notes}</p>
			</th>
		</tr>
		{$ipm_row}
		{$incident_row}
		{$rma_row}
		<tr id='scap_req'>
			<th class='form' colspan='6'>
				<lable for='scrap_btn'>Recommend Scrap</label>
				<input type='checkbox' id='scrap_btn' name='scrap' value='1' onclick='ShowScrapSection();' readonly $chk_scrap/>
			</th>
		</tr>
		<tr id='scrap_reason'>
			<th class='form' colspan='6'>
				Please state why the unit is being recommended for Scrapped.<br/>
				<p style='width:80%; border: 1px solid gray; padding:2px;'>{$reason}</p>
			</th>
		</tr>
		{$part_section}
		<tr class='form_section'>
			<td colspan='6'>Accessories</td>
		</tr>
		{$ba_hdr}
		<tr class='form_section'>
			<td colspan='6'>Signature</td>
		</tr>
		{$signature_row}
		<tr>
			<td class='buttons' colspan='6'>
				" . $this->wo_legal_note . "
			</td>
		</tr>
	</table>
	</form>
{$TestFileUploadsection}
<div id='capa_menu' class='conmenu' style='display:none; background-color:#FFFFFF; height:160px;'>
<form name='capa_link' method='POST' action='{$_SERVER['PHP_SELF']}'>
<input type='hidden' name='act' value='join'/>
<input type='hidden' name='workorder_id' value='{$this->work_order}'/>
	<div style='padding: 2px;' class='sec_opt'>
		CAPA Documents:
		<span class='close' onclick='CloseCapaMenu();' alt='Close' title='Close'>x</span>
	</div>
	{$this->CapaMenu()}
</form>
</div>

<div id='elec_file'  style='display:none; background-color:#FFFFFF; height:160px;'>
    <iframe id='efIframe' src='' width='700' frameBorder='0'></iframe>
</div>";

    }

    /**
     * Get Javascript for editable version of a WorkOrder
     *
     * @param $parts int
     * @param $complaints int
     *
     * @return string
     */
    private function GetJS($parts, $complaints)
    {
        # Init these
        $d_r_alert = "";
        $incident_alert = $multi_complaint_alert = "";
        $available_qty = "";
        $full_check = 0;
        $required_pm = 0;

        if ($this->status <> WO_CLOSED)
        {
            $full_check = 1;

            $multiComplaintDateValue = Config::$MULTIPLE_COMPLAINTS_DATE_RANGE;
            $multiComplaintComment = "\nThis unit has had multiple Complaint\nbased work orders in the last {$multiComplaintDateValue}.\n";
            $d_r_alert = ($this->dealer_repair) ? "alert('This is a RMA repair.');" : "";
            $incident_alert = ($this->incident_tag) ? "alert('Incident reported for this unit');" : "";
            $multi_complaint_alert = ($this->GetMultipleComplaints()) ? "alert( '{$multiComplaintComment}' );" : "";

            $available_qty = "part_qty = new Array();\n";

            $sth = $this->dbh->prepare("SELECT p.id, p.code
			FROM products p
			INNER JOIN device_parts d ON p.id = d.prod_id AND d.model_id = ?
			ORDER BY d.display_order, p.code");
            $sth->bindValue(1, (int) $this->model, PDO::PARAM_INT);
            $sth->execute();
            while (list($id, $code) = $sth->fetch(PDO::FETCH_NUM))
            {
                $qty = self::GetAvailQty($code);
                $available_qty .= "part_qty[$id] = $qty;\n";
            }
        }

        # Get the count of completed PM forms
        $pm_ary = "";
        if ($this->pm_forms)
        {
            $i = 0;
            foreach ($this->pm_forms as $pm)
            {
                $pass = $pm['pass'] ? 1 : 0;
                $complete = $pm['completed_date'] ? 1 : 0;

                $pm_ary .= "pm_ary[$i] = { id: {$pm['id']}, complete : {$complete}, pass : {$pass} };\n";

                $i++;
            }
        }

        # dont allow empty value
        $pm_version = (int) $this->wo_pm_version;

        $has_incident = ($this->incident_tag) ? 1 : 0;
        $incident_complete = ($this->incident_complete) ? 1 : 0;

        $js = "
<script type=\"text/javascript\">
		full_check = {$full_check}
		parts = {$parts};
		complaints = {$complaints};
		pm_version = {$pm_version};
		required_pm = $required_pm;
		has_incident = {$has_incident};
		incident_complete = {$incident_complete};
		{$pm_ary}
		{$d_r_alert}
		{$incident_alert}
		{$multi_complaint_alert}
		{$available_qty}
</script>";

        if (isset ($_REQUEST['show_band']))
        {
            $js .= "
<script type=\"text/javascript\">
$(function () {
	window.open('templates/asset/wo_label.php?work_order={$this->work_order}', 'AssetBand','width=900,height=150,menubar=no,toolbar=no,scrollbars=no,resizable=yes');
});
</script>";
        }

        return $js;
    }

    /**
     * Create a html table row for the part
     *
     * @param $index int
     *
     * @return $part_row string
     */
    private function GetPartRow($index)
    {
        $id_input = isset ($this->replaced_parts[$index]['id']) ? "<input type='hidden' name='replaced_parts[{$index}][id]' value='{$this->replaced_parts[$index]['id']}'/>" : "";
        $add_more = isset ($this->replaced_parts[$index]) ? '' : "onChange='AddPartRow({$index})';";
        $serial = isset ($this->replaced_parts[$index]) ? $this->replaced_parts[$index]['serial_number'] : '';

        $part_id = isset ($this->replaced_parts[$index]) ? (int) $this->replaced_parts[$index]['part_id'] : 0;
        $mas_so = isset ($this->replaced_parts[$index]['mas_sales_order']) ? (int) $this->replaced_parts[$index]['mas_sales_order'] : 0;
        $so_input = "<input type='hidden' name='replaced_parts[{$index}][mas_sales_order]' value='{$mas_so}'/>";
        $so_msg = ($mas_so > 0) ? " SO #: $mas_so " : "";
        $rma_msg = "";

        if (isset ($this->ncmr_id))
        {
            $part_string = "";
            if ($part_id)
            {
                $sth = $this->dbh->prepare("SELECT
					code || ' :: ' || \"name\"
				FROM products
				WHERE id = ?");
                $sth->bindValue(1, $part_id, PDO::PARAM_INT);
                $sth->execute();
                $part_string = $sth->fetchColumn();
            }

            $part_input = "<input type='hidden' name='replaced_parts[{$index}][part_id]' value='{$part_id}' />$part_string";
            $serial_input = "<input type='hidden' name='replaced_parts[{$index}][serial_number]' value='{$serial}' />$serial";
        }
        else
        {
            $part_list = self::createServicePartList($part_id, $this->model, $mas_so);
            $part_input = "<select name='replaced_parts[{$index}][part_id]' {$add_more}>$part_list</select>";
            $serial_input = "<input type='text' name='replaced_parts[{$index}][serial_number]' value='{$serial}' size='15'/>";

            $this->loadRma();
            if (isset ($this->rma_detail[0]['rma_id']) && $part_id)
            {
                $rmaId = $this->rma_detail[0]['rma_id'];

                $sth = $this->dbh->prepare("SELECT
					count(part_id)
				FROM work_order_part
				WHERE part_id = $part_id
				AND work_order = $this->work_order");
                $sth->execute();
                $actual = $sth->fetchColumn();

                $sth = $this->dbh->prepare("SELECT
					sum(quantity)
				FROM rma_estimate
				WHERE part_id = $part_id
				AND rma_id = $rmaId");
                $sth->execute();
                $estCount = $sth->fetchColumn();

                if (!$estCount)
                {
                    $rma_msg = 'Not in Quotation';
                }
                else if ($actual < $estCount)
                {
                    $rma_msg = 'Less than in Quotation';
                }
                else if ($actual > $estCount)
                {
                    $rma_msg = 'More than in Quotation';
                }
            }

            $rma_msg .= "<input type='hidden' id='parts_mismatch{$index}' name='parts_mismatch{$index}' value='{$rma_msg}'/>";
        }

        $row = "
		<tr>
			$id_input
			$so_input
			<td class='form' colspan='8'>
				Part #:	$part_input
				Serial #: $serial_input
				$so_msg
				$rma_msg
			</td>
		</tr>";

        return $row;
    }

    /**
     * Create a html table row for the complaint
     *
     * @param $index int
     *
     * @return string
     */
    private function GetComplaintRow($index)
    {
        $id_input = isset ($this->complaints[$index]['id']) ? "<input type='hidden' name='complaints[{$index}][id]' value='{$this->complaints[$index]['id']}'/>" : "";
        $complaint = isset ($this->complaints[$index]) ? $this->complaints[$index]['complaint'] : '';
        $description = isset ($this->complaints[$index]['code_description']) ? $this->complaints[$index]['code_description'] : '';
        $resolution = isset ($this->complaints[$index]) ? $this->complaints[$index]['resolution'] : '';
        $r_description = isset ($this->complaints[$index]['resolution_description']) ? $this->complaints[$index]['resolution_description'] : '';

        # Closed : Show them only not editable
        if ($this->status == WO_CLOSED)
        {
            $complaint_input = "<input type='hidden' name='complaints[$index][complaint]' value='{$complaint}'/>{$complaint}: {$description}";
            $resolution_input = "<input type='hidden' name='complaints[$index][resolution]' value='{$resolution}'/>{$resolution}:  {$r_description}";

        }
        else
        {
            # If WO is generated from complaint form the complaint is not editable
            if ($this->complaint_form > 0 && $complaint)
            {
                $complaint_input = "<input type='hidden' name='complaints[$index][complaint]' value='{$complaint}'/>{$complaint}: {$description}";
            }
            else
            {
                $complaint_input = "<select name='complaints[$index][complaint]'><option value=''></option>" . ComplaintForm::createCodeList($complaint) . "</select>";
            }

            $resolution_input = "<select name='complaints[$index][resolution]'><option value=''></option>" . self::createResolutionList($resolution, $this->model) . "</select>";
        }


        if ($this->wo_type == 3)
        {
            $complaint_input = "<select name='complaints[$index][complaint]'>" . self::createOEMCodeList($complaint) . "</select>";
        }

        return "
		<tr>{$id_input}
			<th class='form'>Complaint:</th>
			<td class='form'>
				{$complaint_input}
			</td>
			<th class='form'>Resolution:</th>
			<td class='form' colspan='5'>
				{$resolution_input}
			</td>
		</tr>";

    }

    /**
     * Get how many complaints there have been
     * in a date range specified by a Config
     * variable
     *
     * @param $returnCount Boolean
     *
     * @return true or false if $returnCount is false
     *         otherwise return the number of complaints
     */
    private function GetMultipleComplaints($returnCount = false)
    {
        $serialNum = $this->dbh->quote($this->serial_num);
        $lastDataInterval = $this->dbh->quote(Config::$MULTIPLE_COMPLAINTS_DATE_RANGE);

        $sql = <<<END
SELECT COUNT( 1 )
FROM work_order
WHERE model = {$this->model}
AND serial_num = {$serialNum}
AND to_timestamp( close_date ) > CURRENT_DATE - INTERVAL {$lastDataInterval}
AND complaint_form <> 0
END;

        $sth = $this->dbh->query($sql);

        if ($returnCount)
            return $sth->fetchColumn();
        else if ($sth->fetchColumn() > 0)
            return true;
        else
            return false;
    }

    /**
     * Lookup status text based on id
     *
     * @param $status_id int
     *
     * @return $status string
     */
    static public function getStatusText($status_id)
    {
        $dbh = DataStor::getHandle();

        $status = "";
        if ($status_id > 0)
        {
            $sth = $dbh->prepare("SELECT status_text FROM work_order_status where status_id = ?");
            $sth->bindValue(1, $status_id, PDO::PARAM_INT);
            $sth->execute();
            list($status) = $sth->fetch(PDO::FETCH_NUM);
        }
        return $status;
    }

    /**
     * check SO is created for any part under that WO
     */
    static public function hasSO($worOrder)
    {
        $dbh = DataStor::getHandle();
        $worOrder = (int) $worOrder;
        $sth = $dbh->query("SELECT wop.mas_sales_order
		FROM work_order_part wop
		WHERE wop.work_order = {$worOrder}
		AND wop.mas_sales_order <> 0
		LIMIT 1");
        $masSo = $sth->fetchColumn();

        return $masSo;
    }

    /**
     * check SO is created for any part under that WO
     */
    static public function pmStatus($worOrder)
    {
        $dbh = DataStor::getHandle();
        $worOrder = (int) $worOrder;
        $sth = $dbh->prepare("SELECT work_order
		FROM wo_pm
		WHERE work_order = {$worOrder}
		AND pass = 'f'
		LIMIT 1");
        $sth->execute();
        $pmFail = $sth->fetchColumn();
        return $pmFail;
    }




    /**
     * Return has_inspection flag
     */
    public function HasInspection()
    {
        return (bool) $this->has_inspection;
    }

    /**
     * Create string of html options suitable for a select input
     *
     * @param $match int
     *
     * @return $options string
     */
    static public function createStatusList($match)
    {
        $dbh = DataStor::getHandle();

        $options = "";
        $sth = $dbh->prepare("SELECT status_id, status_text
		FROM work_order_status
		ORDER by display_order");
        $sth->execute();
        while (list($status_id, $status_text) = $sth->fetch(PDO::FETCH_NUM))
        {
            $sel = ($match == $status_id) ? "selected " : "";
            $options .= "<option value='{$status_id}' {$sel}>{$status_text}</option>";
        }

        return $options;
    }

    /**
     * Create Parts List
     *
     * @param integer
     * @param integer
     * @param integer
     *
     * @return $supplies string
     */
    static public function createServicePartList($match, $model_id, $so)
    {
        $dbh = DataStor::getHandle();

        # When SO has been created only allow single option
        if ($so == 0)
        {
            $supplies = "<option value=''>--Select Part--</option>\n";
            $product_filter = "";
        }
        else
        {
            $supplies = "";
            $product_filter = "AND p.id = " . (int) $match;
        }

        $sth = $dbh->prepare("SELECT p.id, p.code, p.name
		FROM products p
		INNER JOIN device_parts d ON p.id = d.prod_id AND d.model_id = ?
		WHERE p.id IN (
			SELECT j.prod_id
			FROM product_category_join j
			INNER JOIN product_category pc ON j.cat_id = pc.id
			INNER JOIN product_catalog_category c ON j.cat_id = c.category_id AND c.catalog_id = " . self::$SERVICE_CATALOG . "
		)
		$product_filter
		ORDER BY d.display_order, p.code");
        $sth->bindValue(1, (int) $model_id, PDO::PARAM_INT);
        $sth->execute();
        while (list($id, $code, $name) = $sth->fetch(PDO::FETCH_NUM))
        {
            $sel = ($id == $match) ? "selected" : "";
            $supplies .= "<option value='{$id}' {$sel}>{$code} :: {$name}</option>\n";
        }

        return $supplies;
    }

    /**
     * Find open work order
     *
     * @param int $model
     * @param string $serial_number
     * @param boolean $type
     *
     * @return work_order int
     */
    static public function getOpenWO($model, $serial_number, $type = null)
    {
        $dbh = DataStor::getHandle();
        $work_order = 0;

        if ($model && $serial_number)
        {
            # Look for most recent editable work order with this model and serial number

            if ($type == 'FWO')
                $type_clause = " AND wo_type = 2 AND status != 4";
            else
                $type_clause = " AND wo_type = 1";

            $sql = "SELECT max(work_order)
				FROM work_order
				WHERE editable = true AND model = ? and serial_num = ? $type_clause";
            $sth = $dbh->prepare($sql);
            $sth->bindValue(1, (int) $model, PDO::PARAM_INT);
            $sth->bindValue(2, $serial_number, PDO::PARAM_STR);
            $sth->execute();
            list($work_order) = $sth->fetch(PDO::FETCH_NUM);
        }

        return $work_order;
    }

    /**
     * Find complaint form without a work order in an array
     *
     * There is a possibility of multiple records. This function
     * will return first one found. Those with incident checked
     * will be listed first.
     *
     * @param $model int
     * @param $serial_number string
     *
     * @return $cf array
     */
    static public function getOpenCFArray($model, $serial_number, $facility_id, $issue_id = null, $wo_type = null)
    {
        $dbh = DataStor::getHandle();
        $cf = null;

        $facility_clause = ($facility_id) ? "AND c.facility_id = ?" : "";

        if ($model && $serial_number)
        {
            if ($issue_id > 0)
            {
                # Look for complaint match equipment model and serial number
                $sql = "SELECT
					e.issue_id,
					c.facility_id,
					e.is_incident,
					e.additional_note,
					e.complaint,
					e.asset_type,
					e.replacement_model,
					e.replacement_serial_number,
					e.code
				FROM complaint_form_equipment e
				INNER JOIN complaint_form c ON e.issue_id = c.issue_id
				WHERE e.model = ? AND e.serial_number = ? ";
                if ($wo_type == null)
                    $sql .= " AND e.complaint IN ('Swap','RMA','Accessory') ";// -- Only use Swap, RMA, and Accessory complaints
                else
                    $sql .= " AND e.complaint IN ('Field Service Dispatch') ";// -- Only use Field Service Dispatch complaints for Field WO

                $sql .= " AND e.issue_id NOT IN (
					SELECT complaint_form
					FROM work_order
					WHERE complaint_form > 0
					AND model = ? AND serial_num = ?
				)
				AND e.issue_id = ?
				$facility_clause
				ORDER BY e.is_incident DESC, issue_id desc";
                $sth = $dbh->prepare($sql);
                $sth->bindValue(5, $issue_id, PDO::PARAM_STR);
                if ($facility_id)
                    $sth->bindValue(6, $facility_id, PDO::PARAM_INT);
            }
            else
            {
                # Look for complaint form with this model and serial number
                $sql = "SELECT
					e.issue_id,
					c.facility_id,
					e.is_incident,
					e.additional_note,
					e.complaint,
					e.asset_type,
					e.replacement_model,
					e.replacement_serial_number,
					e.code
				FROM complaint_form_equipment e
				INNER JOIN complaint_form c ON e.issue_id = c.issue_id
				WHERE e.model = ? AND e.serial_number = ? ";
                if ($wo_type == null)
                    $sql .= " AND e.complaint IN ('Swap','RMA','Accessory') ";//  -- Only use Swap, RMA, and Accessory complaints
                else
                    $sql .= " AND e.complaint IN ('Field Service Dispatch') ";// -- Only use Field Service Dispatch complaints for Field WO

                $sql .= " AND e.issue_id NOT IN (
					SELECT complaint_form
					FROM work_order
					WHERE complaint_form > 0
					AND model = ? AND serial_num = ? )
				$facility_clause
				ORDER BY e.is_incident DESC, issue_id DESC";
                $sth = $dbh->prepare($sql);
                if ($facility_id)
                    $sth->bindValue(5, $facility_id, PDO::PARAM_INT);
            }

            $sth->bindValue(1, $model, PDO::PARAM_INT);
            $sth->bindValue(2, $serial_number, PDO::PARAM_STR);
            $sth->bindValue(3, $model, PDO::PARAM_INT);
            $sth->bindValue(4, $serial_number, PDO::PARAM_STR);
            $sth->execute();

            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $cf = $row;
                $cf['model'] = $model;
                $cf['serial_num'] = $serial_number;
                $cf['complaint_form'] = $row['issue_id'];
                $cf['facility_id'] = $row['facility_id'];
                $cf['incident_tag'] = $row['is_incident'];
                $cf['problem'] = trim(strip_tags(str_replace('&nbsp;', ' ', $row['additional_note'])));

                # swap_requested is the complaint is SWAP
                $cf['swap_requested'] = ($row['complaint'] == 'Swap') ? 1 : 0;
                $cf['swap_model'] = $row['replacement_model'];
                $cf['swap_serial'] = $row['replacement_serial_number'];

                # Dealer Repair if the complaint is RMA
                $cf['dealer_repair'] = ($row['complaint'] == 'RMA') ? 1 : 0;

                # get all the acodes
                $cf['complaints'][] = array('complaint' => $row['code'], 'resolution' => '');
            }
        }

        return $cf;
    }

    /**
     * Generate options list for resolution code
     *
     * @param $match_code int
     *
     * @return $resolution_options string
     */
    public static function createResolutionList($match_code, $model)
    {
        $dbh = DataStor::getHandle();
        $resolution_type_id = EquipmentCode::$RESOLUTION;

        $resolution_options = "";
        $sth = $dbh->prepare("SELECT r.code, r.description
		FROM equipment_code r
		JOIN equipment_code_join c ON r.code = c.code AND c.model_id = ?
		WHERE r.active
		AND r.type_id = {$resolution_type_id}
		ORDER BY c.display_order, r.display_order");
        $sth->bindValue(1, (int) $model, PDO::PARAM_INT);
        $sth->execute();
        while (list($code, $description) = $sth->fetch(PDO::FETCH_NUM))
        {
            $sel = ($code === $match_code) ? "selected" : "";

            $resolution_options .= "<option value='{$code}' {$sel}>{$code}: {$description}</option>\n";

        }
        return $resolution_options;
    }

    public static function createOEMResolutionList($match_code, $model)
    {
        $dbh = DataStor::getHandle();
        $resolution_type_id = EquipmentCode::$RESOLUTION;

        $resolution_options = "";
        $sth = $dbh->prepare("SELECT r.code, r.description
		FROM equipment_code r
		JOIN equipment_code_join c ON r.code = c.code AND c.model_id = ?
		WHERE r.active
		AND (r.code='C8001' OR r.code='C8002')
		AND r.type_id = {$resolution_type_id}
		ORDER BY c.display_order, r.display_order");
        $sth->bindValue(1, (int) $model, PDO::PARAM_INT);
        $sth->execute();
        while (list($code, $description) = $sth->fetch(PDO::FETCH_NUM))
        {
            $sel = ($code === $match_code) ? "selected" : "";

            $resolution_options .= "<option value='{$code}' {$sel}>{$code}: {$description}</option>\n";

        }
        return $resolution_options;
    }
    public static function createOEMCodeList($match_code)
    {
        $dbh = DataStor::getHandle();

        $code_type = EquipmentCode::$COMPLAINT;

        $complaint_options = "";
        $sth = $dbh->prepare("SELECT code, description
		FROM equipment_code
		WHERE active IS true
		AND (code='A8001' OR code='A8002')
		AND type_id = $code_type
		ORDER BY display_order");
        $sth->execute();
        while (list($code, $description) = $sth->fetch(PDO::FETCH_NUM))
        {
            $sel = ($code === $match_code) ? "selected" : "";
            $complaint_options .= "<option value='{$code}' {$sel}>{$code}: {$description}</option>\n";
        }
        return $complaint_options;
    }

    /**
     * Look for a log entry for this workorder
     *
     * @return object
     */
    public function GetWFLog($action)
    {
        global $user;

        if (empty ($action))
            $action = "--Empty WO Action--";
        if (empty ($reason))
            $reason = "--Empty--";

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT
			id, work_order_id, work_flow_id, tstamp, user_id, action, reason
		FROM workorder_workflow_log
		WHERE work_order_id = ?
		AND action = ?");
        $sth->bindValue(1, (int) $this->work_order, PDO::PARAM_INT);
        $sth->bindValue(2, $action, PDO::PARAM_STR);
        $sth->execute();

        return $sth->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Generate a open work order record check for open work orders and complaint forms
     *
     * Optional issue_id to pull information from
     *
     * @param int $model
     * @param string $serial_number
     * @param int $issue_id
     * @param string $type
     *
     * @return work_order object
     */
    static public function Generate($model, $serial_number, $issue_id = null, $unit_type = 1, $type = null)
    {
        $dbh = DataStor::getHandle();
        $work_order = null;

        if ($model && $serial_number)
        {
            $asset_id = LeaseAsset::exists($model, $serial_number);
            $asset = ($asset_id > 0) ? new LeaseAsset($asset_id) : null;
            $trans = ($asset) ? $asset->getLastTransaction() : null;
            $facility_id = ($trans && $trans->getFacility()) ? $trans->getFacility()->getId() : Config::$DEFAULT001_ID;
            $facility = ($trans && $trans->getFacility()) ? $trans->getFacility() : new Facility($facility_id);

            # Look for existing editable Work Order
            if ($work_order_id = WorkOrder::getOpenWO($model, $serial_number, $type))
            {
                $work_order = new WorkOrder($work_order_id);
            }
            else
            {
                $cf_array = null;

                $work_order = new WorkOrder();
                $work_order->setVar('model', $model);
                $work_order->setVar('serial_num', $serial_number);
                # Look for Complaint form without an attached work order
                $cf_array = WorkOrder::getOpenCFArray($model, $serial_number, $facility_id, $issue_id, $type);

                if ($unit_type == EquipmentModel::$ACCESSORY && $cf_array == NULL)
                {
                    # Find the baes unit ID
                    $base_id = $asset->getBaseUnit();

                    # Look for Complaint form without an attached work order
                    if (!$base_id)
                    {
                        $base_unit_sth = $dbh->query("SELECT *
						FROM accessory_to_base_unit
						WHERE accessory_asset_id = {$asset_id}
						AND base_unit_asset_id IS NOT NULL
						ORDER BY tstamp DESC
						LIMIT 1");

                        $base_id = $base_unit_sth->fetchColumn();
                    }

                    $base = ($base_id) ? new LeaseAsset($base_id) : null;

                    if ($base)
                    {
                        $base_model = $base->getModel()->getId();
                        $base_serial = $base->getSerial();
                        $cf_array = WorkOrder::getOpenCFArray($base_model, $base_serial, $facility_id, $issue_id, $type);

                        if ($cf_array)
                        {
                            $cf_array['model'] = $model;
                            $cf_array['serial_num'] = $serial_number;
                        }
                    }
                }

                # Look for Complaint form without an attached work order
                # Incident will have precedent
                if ($cf_array)
                {
                    # CF may be for base unit
                    # For that case reset model and serial (to Accessory)
                    $cf_array['model'] = $model;
                    $cf_array['serial_num'] = $serial_number;
                    $work_order->copyFromArray($cf_array);
                }
                else
                {
                    $work_order->setVar('facility_id', $facility_id);

                    if ($facility_id && ($type == null))
                    {
                        # Mark dealer_repair for Purchased devices
                        if ($asset->getOwningAcct() == $facility->getCustId() && $asset->CustomerOwned())
                            $work_order->setVar('dealer_repair', true);
                    }
                }
            }

            if ($type == null)
            {
                # Dealer Repair if the device is a transducer
                if (EquipmentModel::IsTransducer($model))
                    $work_order->setVar('dealer_repair', true);

                # Open this work order in Incident status
                if ($work_order->getVar('incident_tag'))
                    $work_order->setVar('status', WO_INCIDENT);
            }

            # Field Work Order
            if ($type == 'FWO')
                $work_order->setVar('wo_type', 2);

            # OEM Work Order
            if ($type == 'OEM')
                $work_order->setVar('wo_type', 3);
        }

        return $work_order;
    }


    /**
     * For creating Sales orders on  Work Order.
     *
     * @param $mdbh PDO Object
     */
    public function CreateMasSalesOrder()
    {
        global $user;

        $ret = "";

        # Need Company ID to do lookups
        # This is determined by customer format
        $SO = new SalesOrder();
        $SO->SetCustID('SER999');
        $SO->ValidateCompany();

        $SO_ary = array();
        $SO_ary['TranDate'] = date('Y-m-d');
        $SO_ary['ReqDate'] = date('Y-m-d', $this->open_date);
        $SO_ary['PromDate'] = date('Y-m-d', $this->close_date);
        $SO_ary['ShipDate'] = date('Y-m-d');
        $SO_ary['iAddrKey'] = NULL;
        $SO_ary['OrderID'] = "WO{$this->work_order}";
        $SO_ary['ShipMethod'] = 'Third Party';
        $SO_ary['ShipCarrier'] = 'UPS';
        $SO_ary['CustPONo'] = NULL;
        $SO_ary['ShipCost'] = NULL;
        $SO_ary['items'] = array();
        $errors = "";
        $parts = array();

        # Add items to staging table
        $ln = 1;
        foreach ($this->replaced_parts as $item)
        {
            if ($item['mas_sales_order'] == 0)
            {
                $parts[] = $item['id'];
                $so_item['LineNo'] = $ln;
                $so_item['UOMKey'] = SalesOrder::GetUOMKey('EA', $SO->GetCO());
                $so_item['CmntOnly'] = 0;
                $so_item['ItemID'] = $item['code'];
                $so_item['QtyOrd'] = 1;
                $so_item['QtyShipped'] = 1;
                $so_item['UnitPrice'] = 0.0; ## Never charge for service SOs (isset($item['price'])) ? $item['price'] : null;
                $so_item['WhseKey'] = self::$WHSEKEY;
                $SO_ary['items'][] = $so_item;
                $ln++;
            }
        }

        # Need at least 1 line item to create sales order
        if (count($SO_ary['items']) > 0)
        {
            $SO->save($SO_ary);
            $this->mas_sales_order = $SO->GetTranNo();
            $SO->PickListFromSO();
            $SO->CleanStagingTable();
        }

        # Retrieve and save
        if (!$this->mas_sales_order)
            $this->mas_sales_order = -1;

        $this->change('mas_sales_order', $this->mas_sales_order);
        $this->SetPartSO($parts, $this->mas_sales_order);

        if ($errors)
            $ret = "The following items could not be pulled from Work Order # {$this->work_order}.\n{$errors}";

        return $ret;
    }

    /**
     * Query Mas to verify item exists
     *
     * @param string
     * @return string
     */
    private function CheckPart($part)
    {
        global $dbh, $mdbh;

        $ret = "OK";

        $mas_company_id = $mdbh->quote(Config::$Company);
        $part = $mdbh->quote($part['code']);

        $sql = "SELECT
			A.ItemID, B.ShortDesc
		FROM timItem A, timItemDescription B
		WHERE A.CompanyID = {$mas_company_id}
		AND A.ItemKey = B.ItemKey
		AND A.ItemID = {$part}";
        $sth = $mdbh->query($sql);
        list($itemid, $name) = $sth->fetch(PDO::FETCH_NUM);

        if (is_null($itemid))
            $ret = "\n{$part['code']} - {$part['name']} does not exist.";

        return $ret;
    }

    /**
     * Get available inventory amount for the product converted to the given UOM
     *
     * @param $prod_code string
     *
     * @return $avail_qty int
     */
    static public function GetAvailQty($prod_code)
    {
        # TEMP: ------------------------------------------
        # TEMP: Need to always show available
        # TEMP: This is likely to change back to a lookup
        # TEMP: ------------------------------------------
        $avail_qty = 9999;

        /**
               * prefered method
               *
              require_once('Order.php');
              $avail_qty = Cart::GetAvailQty($prod_code, true, self::$WHSEKEY);
               */

        return $avail_qty;
    }

    /**
     * Update Accessories
     *
     * @param array
     * @throws Exception
     */
    public function UpdateAccessories($request, $user)
    {
        # Grab the Base Unit Asset
        $asset_id = LeaseAsset::exists($this->model, $this->serial_num);
        $base_unit = ($asset_id > 0) ? new LeaseAsset($asset_id) : null;
        $acc_attached = false; /// will force set to pack
        $err_indicator = "";

        # Get Posted fields
        if (!empty ($request['ba_ary']))
        {
            foreach ($request['ba_ary'] as $i => $b_a_ary)
            {
                if (isset ($b_a_ary['model_id']) && $b_a_ary['model_id'] > 0)
                {
                    # type of accessory - attached or new
                    $acc_type = $b_a_ary['acc_tp'];

                    # id of asset that was listed as attached.
                    $attached_asset_id = isset ($b_a_ary['attached_asset_id']) ? ($b_a_ary['attached_asset_id']) : NULL;

                    # id of asset that is, OR is going to be, attached.
                    # This will be the same as the attached asset id OR a new accessory id
                    $acc_asset_id = isset ($b_a_ary['acc_asset_id']) ? ($b_a_ary['acc_asset_id']) : NULL;

                    $attached_verified = isset ($b_a_ary['attached_verified']) ? ($b_a_ary['attached_verified']) : NULL;
                    $verified_match = isset ($b_a_ary['verified_match']) ? ($b_a_ary['verified_match']) : NULL;
                    $acc_action = isset ($b_a_ary['action_to_take']) ? ($b_a_ary['action_to_take']) : NULL;
                    $acc_action = ($acc_action != 'attach') ? $acc_action : NULL;
                    $acc_barcode = isset ($b_a_ary['barcode']) ? trim($b_a_ary['barcode']) : NULL;
                    $acc_description = isset ($b_a_ary['description']) ? trim($b_a_ary['description']) : NULL;


                    if ($acc_barcode || $acc_action)
                    {
                        # NOTE: ALL we are checking for is accessories that were attached when the unit came in.
                        # AJV: Not sure we are capturing all scenarios
                        if ($acc_type == 'attached')
                        {
                            if (!$attached_verified && !$acc_action)
                                $err_indicator .= "You have not verfied the barcode of 1 or more attacahed accessories ($acc_description)<br> ";
                            if ($attached_verified && !$verified_match && !$acc_action)
                                $err_indicator .= "You have not indicated what action to take with an accessory that SHOULD have been attached, but a different accessory came in with the unit. ($acc_description)<br>";
                        }


                        if ($err_indicator)
                            throw new Exception($err_indicator);

                        if ($acc_asset_id > 0)
                        {
                            # IF new, OR is attached, and barcode match == false. -- Remember, we don't need to attach an already attached accessory
                            if ($acc_action == 'swap')
                            {
                                /// Detach accessory
                                $accessory = new LeaseAsset($attached_asset_id);
                                $accessory->Attach(null);

                                /// set original ($b_a_ary['attached_asset_id']) to in-transit   $status = $TRANSIT $substatus = $SWAP
                                $reason = "This unit was NOT attached to the base when the base came in for service";
                                $location = Config::$DEFAULT001_ID;
                                $accessory->addTransaction($location, LeaseAssetTransaction::$TRANSIT, LeaseAssetTransaction::$SWAP, $user, $reason);
                            }
                            if ($acc_action == 'scrap')
                            {
                                /// Detach accessory
                                $accessory = new LeaseAsset($attached_asset_id);
                                $accessory->Attach(null);

                                /// set original ($b_a_ary['attached_asset_id']) to OOS
                                $reason = "This unit was NOT attached to the base when the base came in for service";
                                $location = Config::$DEFAULT001_ID;
                                $accessory->addTransaction($location, LeaseAssetTransaction::$OUT_OF_SERVICE, LeaseAssetTransaction::$SCRAPPED, $user, $reason);
                            }
                            if ($acc_action == 'detach')
                            {
                                /// Detach accessory
                                $accessory = new LeaseAsset($attached_asset_id);
                                $accessory->Attach(null);

                                $sql = "Select lat.facility_id,lat.substatus
								FROM lease_asset_transaction lat
								JOIN facilities f on lat.facility_id=f.id
								WHERE lat.lease_asset_id = {$attached_asset_id} AND lat.status='Placed' ORDER BY lat.tstamp DESC LIMIT 1";

                                $sth = $this->dbh->prepare($sql);
                                $sth->execute();
                                list($location, $substatus) = $sth->fetch(PDO::FETCH_NUM);

                                if (!$location)
                                {
                                    $location = Config::$DEFAULT001_ID;
                                    $substatus = LeaseAssetTransaction::$LEASE;
                                }

                                $reason = "Remove asset from base unit";
                                $accessory->addTransaction($location, LeaseAssetTransaction::$PLACED, $substatus, $user, $reason);
                            }
                            if ($acc_asset_id != $attached_asset_id)
                            {
                                # Attach the new or replacement accessory asset
                                $accessory = new LeaseAsset($acc_asset_id);
                                $accessory->Attach($base_unit->getId());
                            }
                            $acc_attached = true; /// will force set to pack
                        }#End no Error
                    }# Ends if barcode

                }#Ends if model
            }# Ends foreach
        }# Ends if array

        return $acc_attached;
    }

    /**
     * Update asset
     */
    public function UpdateAsset()
    {
        if ($this->has_inspection && $this->status == WO_CLOSED)
        {
            $sql = "UPDATE lease_asset SET barcode= ?,last_cert_date = ?
			WHERE model_id = ?
			AND serial_num = ?
			AND (last_cert_date is null OR last_cert_date < ?)";
            $sth = $this->dbh->prepare($sql);
            $sth->bindValue(1, $this->bar_code, PDO::PARAM_STR);
            $sth->bindValue(2, date('Y-m-d', $this->close_date), PDO::PARAM_STR);
            $sth->bindValue(3, (int) $this->model, PDO::PARAM_INT);
            $sth->bindValue(4, $this->serial_num, PDO::PARAM_STR);
            $sth->bindValue(5, date('Y-m-d', $this->close_date), PDO::PARAM_STR);
            $sth->execute();

            $pm_id = null;
            foreach ($this->pm_forms as $ipm)
            {
                if ($ipm['pass'])
                    $pm_id = $ipm['id'];
            }

            if ($pm_id)
            {
                $sv = $fv = "";
                $sql = "SELECT
					res.val,
                    map.label,
					map.pm_column
				FROM wo_pm wopm
				INNER JOIN wo_pm_map map on wopm.version_id = map.pm_id
				JOIN wo_pm_result res on res.pm_id = wopm.id
					AND res.section_num = map.section_num
					AND res.item_num = map.item_num
					AND res.sub_item = map.sub_item
                WHERE wopm.id = ?";
                $sth = $this->dbh->prepare($sql);
                $sth->bindValue(1, $pm_id, PDO::PARAM_INT);
                $sth->execute();
                while ($row = $sth->fetch(PDO::FETCH_ASSOC))
                {
                    if ($row['pm_column'] == 'FV')
                    {
                        $fv .= substr(trim($row['val']), 0, 128);
                    }
                    else if ($row['pm_column'] == 'SV')
                    {
                        if (empty ($row['label']))
                        {
                            $row['label'] = "SOFT";
                        }
                        $sv .= $row['label'] . ": " . substr(trim($row['val']), 0, 32) . "\n";
                    }
                    else
                        continue;

                    if (count($fv) > 0)
                    {
                        $sql = "UPDATE lease_asset SET
						firmware_version = ?
					WHERE model_id = ?
					AND serial_num = ?";
                        $sth_ipm = $this->dbh->prepare($sql);
                        $sth_ipm->bindValue(1, $fv, PDO::PARAM_STR);
                        $sth_ipm->bindValue(2, (int) $this->model, PDO::PARAM_INT);
                        $sth_ipm->bindValue(3, $this->serial_num, PDO::PARAM_STR);
                        $sth_ipm->execute();
                    }
                    if (count($sv) > 0)
                    {
                        $sql = "UPDATE lease_asset SET
						software_version = ?
					WHERE model_id = ?
					AND serial_num = ?";
                        $sth_ipm = $this->dbh->prepare($sql);
                        $sth_ipm->bindValue(1, rtrim($sv), PDO::PARAM_STR);
                        $sth_ipm->bindValue(2, (int) $this->model, PDO::PARAM_INT);
                        $sth_ipm->bindValue(3, $this->serial_num, PDO::PARAM_STR);
                        $sth_ipm->execute();
                    }

                }
            }
        }
    }

    /**
     * Do pre save validation
     */
    public function ValidateDR()
    {
        global $user;

        $rmaId = isset ($_REQUEST['rma_id']) ? $_REQUEST['rma_id'] : 0;
        $partsCount = isset ($_REQUEST['parts_count']) ? $_REQUEST['parts_count'] : 0;
        $rmaStatus = isset ($_REQUEST['rma_status']) ? $_REQUEST['rma_status'] : 0;
        $mismatchFlag = false;
        $timestamp = time();

        for ($i = 0; $i < $partsCount; $i++)
        {
            if (isset ($_REQUEST['parts_mismatch' . $i]) && $_REQUEST['parts_mismatch' . $i] != '')
            {
                $mismatchFlag = true;
            }
        }

        $actualSql = "SELECT count(*)
		FROM work_order_part
		WHERE work_order = ?";
        $sth = $this->dbh->prepare($actualSql);
        $sth->bindValue(1, $this->work_order, PDO::PARAM_INT);
        $sth->execute();
        $actualCount = $sth->fetchColumn();

        if ($rmaId != 0)
        {
            $estimateSql = "SELECT
				sum(quantity)
			FROM rma_estimate
			WHERE rma_id = ?";
            $sth = $this->dbh->prepare($estimateSql);
            $sth->bindValue(1, $rmaId, PDO::PARAM_INT);
            $sth->execute();
            $estimateCount = $sth->fetchColumn();

            if ($estimateCount != $actualCount && $rmaStatus == 5)
            {
                $this->close_date = 0;
                $this->status = 1;
                $this->close_by = 0;
                $this->editable = true;

                if (isset ($_REQUEST['quote_number']))
                {
                    $epochSql = "SELECT
						epoch
					FROM rma_sent
					WHERE rma_id = ?
					ORDER BY revision DESC
					LIMIT 1";
                    $sth = $this->dbh->prepare($epochSql);
                    $sth->bindValue(1, $rmaId, PDO::PARAM_INT);
                    $sth->execute();
                    $epoch = $sth->fetchColumn();

                    if ($epoch == $_REQUEST['quote_number'])
                    {
                        $this->close_date = $timestamp;
                        $this->close_by = $user->getId();
                        $this->close_by_name = $user->getName();
                        $this->status = WO_CLOSED;

                        //Update manager override number
                        $rth = $this->dbh->prepare("UPDATE rma_quotation SET
							sent_epoch = ?
						WHERE work_order = ?");
                        $rth->bindValue(1, (int) $epoch, PDO::PARAM_INT);
                        $rth->bindValue(2, (int) $this->work_order, PDO::PARAM_INT);
                        $rth->execute();

                    }
                    else
                        echo 'Parts count mismatch with quote,please contact your manager';

                }
                else
                    echo 'Parts count mismatch with quote,please contact your manager';

            }
            else if ($rmaStatus < 5)
            {
                $this->close_date = 0;
                $this->status = 1;
                $this->close_by = 0;
                $this->editable = true;

                echo 'RMA Quote is in progress.Get confirmation from customer and close WO';
            }
        }
        else
        {
            // rma mandatory if no warranty
            if (isset ($_REQUEST['rma_warranty']) && $_REQUEST['rma_warranty'] == 0)
            {
                $this->close_date = 0;
                $this->status = 1;
                $this->close_by = 0;
                $this->editable = true;

                echo 'RMA Quote is required as the Warranty is expired.';
            }
        }
    }

    public function getSerialNum()
    {
        return $this->serial_num;
    }

    public function getFacilityId()
    {
        return $this->facility_id;
    }

    public function getAccessoryRows()
    {
        #Added for cr 2125
        $tmp_att_acc_arr = array();
        $ba_inputs = '';
        $what = "Accessories";
        $whatelse = "Base Unit";
        $barcode_n = '';
        $this->ba_ary = array();
        $base_unit_array = array(75, 99, 164);
        $dylans_replace_notice = '';
        $p_type = 1;
        if ($this->device_type == 1)
            $p_type = 2;

        #Get the accessories that 'are' attached to this base
        $sth = $this->dbh->prepare("SELECT
		main.base_unit_asset_id,
		main.accessory_asset_id,
		main.tstamp,
		em.id AS model_id,
		mem.id AS base_model_id,
		em.model || ' :: ' || em.description as acc_model,
		la.serial_num AS serial_number,
		la.barcode AS barcode,
		CASE
			WHEN find_in_array( em.base_assets, mem.id) THEN 'Y'
			ELSE 'N'
		END AS base_is_in_accessory_base_assets
		FROM accessory_to_base_unit main
		INNER JOIN lease_asset la ON la.id = main.accessory_asset_id
		INNER JOIN equipment_models em ON em.id = la.model_id
		INNER JOIN lease_asset mla ON mla.id = main.base_unit_asset_id
		INNER JOIN equipment_models mem ON mem.id = mla.model_id
		INNER JOIN (
			SELECT
			accessory_asset_id,
			MAX(tstamp) as tstamp
			FROM accessory_to_base_unit
			GROUP BY accessory_asset_id
		) max on main.accessory_asset_id = max.accessory_asset_id AND main.tstamp = max.tstamp
		WHERE main.base_unit_asset_id = $this->wo_asset_id");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $tmp_att_acc_arr[] = $row['model_id'];

            $mth = $this->dbh->query("SELECT count(*) FROM work_order_acc WHERE work_order = {$this->work_order} AND model_id={$row['model_id']} ");
            list($cnt) = $mth->fetch(PDO::FETCH_NUM);
            $scrap_attached = false;
            $attached_verified = false;
            if (in_array($row['base_model_id'], $base_unit_array) && $row['base_is_in_accessory_base_assets'] == 'N')
            {
                $scrap_attached = true;
                $attached_verified = true;
            }

            # this fix is for cases where duplicate model_ids was entered 'Prior' to our recent changes
            if ($cnt > 1)
            {
                $mth = $this->dbh->prepare("DELETE FROM work_order_acc WHERE work_order =? AND model_id = ? AND asset_id <>?");
                $mth->bindValue(1, (int) $this->work_order, PDO::PARAM_INT);
                $mth->bindValue(2, (int) $row['model_id'], PDO::PARAM_INT);
                $mth->bindValue(3, (int) $row['accessory_asset_id'], PDO::PARAM_INT);
                $mth->execute();
            }
            else if ($cnt < 1)
            {
                $mth = $this->dbh->prepare("INSERT
				INTO work_order_acc
				(
				work_order,
				asset_id,
				model_id,
				serial_number,
				barcode,
				attached_asset_id,
				attached_serial_number,
				attached_barcode,
				type_id,
				scrap_attached,
				attached_verified,attached_model_id)
				VALUES
				(?,?,?,?,?,?,?,?,?,?,?,?)");
                $mth->bindValue(1, (int) $this->work_order, PDO::PARAM_INT);
                $mth->bindValue(2, (int) $row['accessory_asset_id'], PDO::PARAM_INT);
                $mth->bindValue(3, (int) $row['model_id'], PDO::PARAM_INT);
                $mth->bindValue(4, $row['serial_number'], PDO::PARAM_STR);
                $mth->bindValue(5, $row['barcode'], PDO::PARAM_STR);
                $mth->bindValue(6, (int) $row['accessory_asset_id'], PDO::PARAM_INT);
                $mth->bindValue(7, $row['serial_number'], PDO::PARAM_STR);
                $mth->bindValue(8, $row['barcode'], PDO::PARAM_STR);
                $mth->bindValue(9, (int) $p_type, PDO::PARAM_INT);
                $mth->bindValue(10, (int) $scrap_attached, PDO::PARAM_BOOL);
                $mth->bindValue(11, (int) $attached_verified, PDO::PARAM_BOOL);
                $mth->bindValue(12, (int) $row['model_id'], PDO::PARAM_INT);
                $mth->execute();
            }
            else
            {
                $mth = $this->dbh->prepare("UPDATE work_order_acc set asset_id =?, serial_number=?,barcode =?, attached_asset_id = ?, attached_serial_number =?,attached_barcode =?,attached_model_id=? WHERE work_order =? AND model_id = ? AND attached_verified = false");
                $mth->bindValue(1, (int) $row['accessory_asset_id'], PDO::PARAM_INT);
                $mth->bindValue(2, $row['serial_number'], PDO::PARAM_STR);
                $mth->bindValue(3, $row['barcode'], PDO::PARAM_STR);
                $mth->bindValue(4, (int) $row['accessory_asset_id'], PDO::PARAM_INT);
                $mth->bindValue(5, $row['serial_number'], PDO::PARAM_STR);
                $mth->bindValue(6, $row['barcode'], PDO::PARAM_STR);
                $mth->bindValue(7, (int) $row['model_id'], PDO::PARAM_INT);
                $mth->bindValue(8, (int) $this->work_order, PDO::PARAM_INT);
                $mth->bindValue(9, (int) $row['model_id'], PDO::PARAM_INT);
                $mth->execute();
            }
        }

        # this fix is for cases where accessories are removed through asset tracking and no new asset was attached after the work order was being edited
        if (!empty ($tmp_att_acc_arr))
        {
            $mth = $this->dbh->prepare("DELETE FROM work_order_acc WHERE work_order =? AND attached_asset_id !=0 AND model_id NOT IN(" . implode(',', $tmp_att_acc_arr) . ") ");
            $mth->bindValue(1, (int) $this->work_order, PDO::PARAM_INT);
            $mth->execute();
        }
        if (empty ($tmp_att_acc_arr))
        {
            $mth = $this->dbh->prepare("DELETE FROM work_order_acc WHERE work_order =? AND attached_asset_id !=0 ");
            $mth->bindValue(1, (int) $this->work_order, PDO::PARAM_INT);
            $mth->execute();
        }

        $this->loadba_ary();
        $ba_inputs = '';
        $barcode_n = '';
        $p_type = 2;
        $ba_cnt = 0;
        $action_btns = '';
        $accessory_asset_id = '';
        $accessory_verified = null;
        $used_model_array = array();
        $base_unit_array = array(75, 99, 164);
        $dylans_replace_notice = '';

        foreach ($this->ba_ary as $acc)
        {
            if (isset ($acc['model_id']))
            {
                $used_model_array[] = $acc['model_id'];
                $dylans_replace_notice = '';

                $id_in_ary = '';
                $mth = $this->dbh->query("SELECT id FROM equipment_models WHERE id={$acc['model_id']} AND find_in_array(base_assets,{$this->model})  ");
                list($id_in_ary) = $mth->fetch(PDO::FETCH_NUM);

                if (in_array($this->model, $base_unit_array) && !$id_in_ary)
                {
                    $dylans_replace_notice = "The Attached Accessory was tagged as being replaced. It will be scrapped. ";
                }

                $model_id = $acc['model_id'];
                $model = $acc['model'];
                $accessory_asset_id = $acc['asset_id'];
                $acctp = (isset ($acc['attached_asset_id']) && $acc['attached_asset_id'] > 0) ? 'attached' : 'new';
                $serial_n = $acc['serial_number'];
                $barcode_n = $acc['barcode'];
                $attached_asset_id = $acc['attached_asset_id'];
                $attached_serial_number = $acc['attached_serial_number'];
                $attached_barcode = $acc['attached_barcode'];
                $verified = $acc['attached_verified'];
                $matched = $acc['verified_match'];
                $description = trim($acc['description']);
                $scrap_btn = "";
                $swap_btn = "";

                # These ARE in the tmp table let's verify the barcode - If the barcode is different, update it.
                $mth = $this->dbh->query("SELECT serial_num,barcode FROM lease_asset WHERE id= {$accessory_asset_id} ");
                list($pulled_serial, $pulled_barcode) = $mth->fetch(PDO::FETCH_NUM);

                if ($pulled_barcode != $barcode_n)
                {
                    $mth = $this->dbh->prepare("UPDATE work_order_acc set serial_number=?,barcode =? WHERE work_order =? AND asset_id = ? ");
                    $mth->bindValue(1, $pulled_serial, PDO::PARAM_STR);
                    $mth->bindValue(2, $pulled_barcode, PDO::PARAM_STR);
                    $mth->bindValue(3, (int) $this->work_order, PDO::PARAM_INT);
                    $mth->bindValue(4, (int) $accessory_asset_id, PDO::PARAM_INT);
                    $mth->execute();
                    $barcode_n = $pulled_barcode;
                    $serial_n = $pulled_serial;
                }

                $att_acc_ck = 'checked';
                $scrap_ck = '';
                $swap_ck = '';
                $detach_ck = '';
                $scrapping = $acc['scrap_attached'];
                $swapping = $acc['swap_attached'];
                $detachinging = $acc['detach_attached'];

                if ($scrapping)
                {
                    $scrap_ck = 'checked';
                    $att_acc_ck = '';
                }
                if ($swapping)
                {
                    $swap_ck = 'checked';
                    $att_acc_ck = '';
                }
                if ($detachinging)
                {
                    $detach_ck = 'checked';
                    $att_acc_ck = '';
                }

                $verify_input = "";
                $ckd = '';

                if ($acctp == 'new')
                {
                    $ckd = 'readonly';
                }


                if (isset ($acc['attached_asset_id']) && $acc['attached_asset_id'] > 0)
                {
                    $ckd = 'readonly';


                    if ($acc['attached_verified'] == false)
                        $verify_input = "&nbsp;&nbsp;&nbsp; <input type='text' id='verifyaccbarcode_{$ba_cnt}'  name='verifyaccbarcode_{$ba_cnt}' size=30 value='' placeholder='Scan Barcode of attached accessory' onBlur=\" CheckScannedBarcode('ba_ary[$ba_cnt][barcode]',{$this->work_order},{$this->wo_asset_id},{$attached_asset_id},'{$attached_serial_number}','{$attached_barcode}',this.value,'verifyaccbarcode','{$description}','{$serial_n}',{$model_id},'{$model}','attached');\">";



                    if (!$acc['barcode'] || $acc['barcode'] == '')
                    {
                        $verify_input = "Attached asset does NOT have a barcode. Go to <a href='asset_tracking.php?lease_asset_id={$attached_asset_id}&act=Edit'> Asset Tracking and update barcode.</a>";
                    }
                }

                if ($acc['attached_verified'] == true && $acc['verified_match'] == false && $acc['swap_attached'] == false && $acc['scrap_attached'] == false)
                {
                    $verify_input = "<br>The scanned Barcode does NOT match the attached accessory of record.<br>Select what action needs to be taken on the attached accessory of record: <br>";
                    $scrap_btn = "&nbsp;&nbsp;&nbsp; <input type='button' name='scrap_{$ba_cnt}' value='scrap' 	onClick=\"ProcessActionClick({$this->work_order},{$this->wo_asset_id},{$attached_asset_id},'{$attached_serial_number}','{$attached_barcode}',this.value,'{$description}','{$serial_n}','{$barcode_n}',{$accessory_asset_id})\">";
                    $swap_btn = "&nbsp;&nbsp;&nbsp; <input type='button' name='swap_{$ba_cnt}' value='swap' 	onClick=\"ProcessActionClick({$this->work_order},{$this->wo_asset_id},{$attached_asset_id},'{$attached_serial_number}','{$attached_barcode}',this.value,'{$description}','{$serial_n}','{$barcode_n}',{$accessory_asset_id})\"><hr size=1>";
                }
                $action_btns = "$verify_input ";
                $action_to_take = ($acc['swap_attached']) ? 'swap' : '';
                $action_to_take = ($acc['scrap_attached']) ? 'scrap' : $action_to_take;

                $action_btns .= "
						&nbsp; <input type='radio' name='ba_ary[$ba_cnt][action_to_take]' value='attach' $att_acc_ck onClick=\"ProcessActionClick({$this->work_order},{$this->wo_asset_id},{$attached_asset_id},'{$attached_serial_number}','{$attached_barcode}',this.value,'{$description}','{$serial_n}','{$barcode_n}',{$accessory_asset_id})\"> ATTACH
						&nbsp; <input type='radio' name='ba_ary[$ba_cnt][action_to_take]' value='scrap' $scrap_ck onClick=\"ProcessActionClick({$this->work_order},{$this->wo_asset_id},{$attached_asset_id},'{$attached_serial_number}','{$attached_barcode}',this.value,'{$description}','{$serial_n}','{$barcode_n}',{$accessory_asset_id})\"> SCRAP
						&nbsp; <input type='radio' name='ba_ary[$ba_cnt][action_to_take]' value='swap' $swap_ck  onClick=\"ProcessActionClick({$this->work_order},{$this->wo_asset_id},{$attached_asset_id},'{$attached_serial_number}','{$attached_barcode}',this.value,'{$description}','{$serial_n}','{$barcode_n}',{$accessory_asset_id})\"> SWAP
						&nbsp; <input type='radio' name='ba_ary[$ba_cnt][action_to_take]' value='detach' $detach_ck onClick=\"ProcessActionClick({$this->work_order},{$this->wo_asset_id},{$attached_asset_id},'{$attached_serial_number}','{$attached_barcode}',this.value,'{$description}','{$serial_n}','{$barcode_n}',{$accessory_asset_id})\"> DETACH (Removes ANY $description from base)
					";


                if ($dylans_replace_notice == '' && $acc['attached_verified'] == true)
                {
                    $action_btns .= " &nbsp; <input type=button class='submit' name='booboo' value='RESET' onClick='BooBooReset($this->work_order,$accessory_asset_id,$attached_asset_id,\"{$acctp}\", \"{$serial_n}\",\"{$barcode_n}\",\"{$attached_serial_number}\",\"{$attached_barcode}\")'>";
                }

                if ($dylans_replace_notice == '' && $acctp == 'new')
                {
                    $attached_serial_number = ($attached_serial_number) ? $attached_serial_number : 'na';
                    $attached_barcode = ($attached_barcode) ? $attached_barcode : 'na';
                    $action_btns = " &nbsp; <input type=button class='submit' name='booboo' value='RESET' onClick='BooBooReset($this->work_order,$accessory_asset_id,$attached_asset_id,\"{$acctp}\", \"{$serial_n}\",\"{$barcode_n}\",\"{$attached_serial_number}\",\"{$attached_barcode}\")'>";
                }

                if ($dylans_replace_notice !== '')
                {
                    $action_btns = " &nbsp; <input type=hidden class='submit' name=ba_ary[$ba_cnt][action_to_take] value='scrap' >";
                }

                if ($acctp == 'attached' && $acc['attached_verified'] == true && $acc['verified_match'] == true)
                {
                    $action_btns = " &nbsp; <input type=hidden class='submit' name=ba_ary[$ba_cnt][action_to_take] value='attach' > &nbsp; <input type=button class='submit' name='booboo' value='RESET' onClick='BooBooReset($this->work_order,$accessory_asset_id,$attached_asset_id,\"{$acctp}\", \"{$serial_n}\",\"{$barcode_n}\",\"{$attached_serial_number}\",\"{$attached_barcode}\")'>";
                }
            }
            $description = trim($acc['description']);
            $ba_inputs .= "
			<tr>
			<input type='hidden' name='ba_ary[$ba_cnt][acc_tp]' value='{$acctp}'>
			<input type='hidden' name='ba_ary[$ba_cnt][p_type]' value='{$p_type}'>
			<input type='hidden' name='ba_ary[$ba_cnt][attached_asset_id]' value='{$attached_asset_id}'>
			<input type='hidden' name='ba_ary[$ba_cnt][attached_serial_number]' value='{$attached_serial_number}'>
			<input type='hidden' name='ba_ary[$ba_cnt][attached_barcode]' value='{$attached_barcode}'>
			<input type='hidden' name='ba_ary[$ba_cnt][model_id]' value='{$model_id}'>
			<input type='hidden' name='ba_ary[$ba_cnt][model]' value='{$model}'>
			<input type='hidden' name='ba_ary[$ba_cnt][description]' value='{$description}'>
			<input type='hidden' id='acc_asset_serial_number_{$ba_cnt}' name='ba_ary[$ba_cnt][serial_number]' value='{$serial_n}'>
			<input type='hidden' id='acc_asset_barcode_{$ba_cnt}' name='ba_ary[$ba_cnt][barcode]' value='{$barcode_n}'>
			<input type='hidden' id='acc_asset_id_{$ba_cnt}' name='ba_ary[$ba_cnt][acc_asset_id]' value='{$accessory_asset_id}'>
			<input type='hidden' name='ba_ary[$ba_cnt][attached_verified]' value='{$verified}'>
			<input type='hidden' name='ba_ary[$ba_cnt][verified_match]' value='{$matched}'>
			<td class='form' valign='top'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {$description}</td>
			<td class='form'  colspan='5'>Barcode:&nbsp;&nbsp;<input type='text' name='ba_ary[$ba_cnt][barcode]' value='{$barcode_n}' size='20' > {$dylans_replace_notice}
			<div id='acc_barcode_{$ba_cnt}' style='display:inline'>{$action_btns}</div>
			</td>
			</tr>";
            $ba_cnt++;
        }

        /// Here is the list of possible accessories
        $gb = $this->dbh->prepare("
		SELECT  em.id,em.model,em.description
		FROM equipment_models em
		WHERE find_in_array( em.base_assets, ?)");
        $gb->bindValue(1, (int) $this->model, PDO::PARAM_INT);
        $gb->execute();

        if ($gb->rowCount() > 0)
        {
            while (list($model_id, $model, $description) = $gb->fetch(PDO::FETCH_NUM))
            {
                $acctp = 'new';
                $action_to_take = '';
                $attached_asset_id = 0;
                $attached_serial_number = '';
                $attached_barcode = '';
                $hid_id = '0';
                $serial_n = $action_btns = $accessory_asset_id = $barcode_n = '';
                $verified = false;
                $matched = false;
                $ckd = "onBlur=\" CheckScannedBarcode('ba_ary[$ba_cnt][barcode]',{$this->work_order},{$this->wo_asset_id},{$attached_asset_id},'{$attached_serial_number}','{$attached_barcode}',this.value,'verifyaccbarcode','{$description}','{$serial_n}',{$model_id},'{$model}','new')\"";

                if (!in_array($model_id, $used_model_array))
                {
                    $scrap_btn = "";
                    $swap_btn = "";
                    $verify_input = "";
                    $action_btns = "";
                    $action_to_take = "";
                    $description = trim($description);
                    $ba_inputs .= "
					<tr>
					<input type='hidden' name='ba_ary[$ba_cnt][action_to_take]' value='{$action_to_take}'>
					<input type='hidden' name='ba_ary[$ba_cnt][acc_tp]' value='{$acctp}'>
					<input type='hidden' name='ba_ary[$ba_cnt][p_type]' value='{$p_type}'>
					<input type='hidden' name='ba_ary[$ba_cnt][attached_asset_id]' value='{$attached_asset_id}'>
					<input type='hidden' name='ba_ary[$ba_cnt][attached_serial_number]' value='{$attached_serial_number}'>
					<input type='hidden' name='ba_ary[$ba_cnt][attached_barcode]' value='{$attached_barcode}'>
					<input type='hidden' name='ba_ary[$ba_cnt][model_id]' value='{$model_id}'>
					<input type='hidden' name='ba_ary[$ba_cnt][model]' value='{$model}'>
					<input type='hidden' name='ba_ary[$ba_cnt][description]' value='{$description}'>
					<input type='hidden' id='acc_asset_serial_number_{$ba_cnt}' name='ba_ary[$ba_cnt][serial_number]' value='{$serial_n}'>
					<input type='hidden' id='acc_asset_barcode_{$ba_cnt}' name='ba_ary[$ba_cnt][barcode]' value='{$barcode_n}'>
					<input type='hidden' id='acc_asset_id_{$ba_cnt}' name='ba_ary[$ba_cnt][acc_asset_id]' value='{$accessory_asset_id}'>
					<input type='hidden' name='ba_ary[$ba_cnt][attached_verified]' value='{$verified}'>
					<input type='hidden' name='ba_ary[$ba_cnt][verified_match]' value='{$matched}'>
					<td class='form' valign='top'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {$description}</td>
					<td class='form'  colspan='5'>Barcode:&nbsp;&nbsp;<input type='text' name='ba_ary[$ba_cnt][barcode]' id='ba_ary[$ba_cnt][barcode]' value='{$barcode_n}' size='20' $ckd > <div id='acc_barcode_{$ba_cnt}' style='display:inline'>{$action_btns}</div></td>
					</tr>";
                    $ba_cnt++;
                } // Ends in array
            } // Ends while
        } // Ends if ($gb->rowCount() > 0)

        $ba_hdr = "Select Accessories As Required:";
        $whatGetsReturned = "<table cellpadding=0 cellspacing=0 border=0 width='100%'>
		<tr><th class='form' colspan='2'>{$ba_hdr}</th>
		</tr>{$ba_inputs}</table><input type='button' class='submit' name='reload' value='Reload Accessories' onClick =\"ReloadAcc({$this->work_order}, {$this->wo_asset_id})\">";

        return $whatGetsReturned;
    }

    /**
     * Sometimes the user will add an accessory they didn't want to
     * This will allow them to reverse what they did
    * @param element theForm

     */
    public function BooBooReset($form)
    {
        $err = '';
        $accpt = (isset ($form['accpt'])) ? $form['accpt'] : "";
        $work_order = $form['work_order'];
        $acc_asset_id = $form['acc_asset_id'];
        $acc_serial_number = $form['acc_serial_number'];
        $acc_barcode = $form['acc_barcode'];
        $attached_asset_id = $form['attached_asset_id'];
        $attached_serial_number = $form['attached_serial_number'];
        $attached_barcode = $form['attached_barcode'];

        if ($accpt)
        {
            if ($accpt == 'new')
            {
                $sth = $this->dbh->prepare('DELETE FROM work_order_acc WHERE work_order = ? AND asset_id = ?');
                $sth->bindValue(1, $work_order, PDO::PARAM_INT);
                $sth->bindValue(2, $acc_asset_id, PDO::PARAM_INT);
                $sth->execute();
            }
            else
            {
                $sth = $this->dbh->prepare("
						UPDATE work_order_acc set
						asset_id = ?,
						serial_number = ?,
						barcode = ?,
						attached_verified=false,
						verified_match=false,
						scrap_attached = false,
						swap_attached = false,
						new_asset = false
						WHERE work_order=? AND  asset_id= ? AND barcode=?");

                $sth->bindValue(1, (int) $attached_asset_id, PDO::PARAM_INT);
                $sth->bindValue(2, $attached_serial_number, PDO::PARAM_STR);
                $sth->bindValue(3, $attached_barcode, PDO::PARAM_STR);
                $sth->bindValue(4, (int) $work_order, PDO::PARAM_INT);
                $sth->bindValue(5, (int) $acc_asset_id, PDO::PARAM_INT);
                $sth->bindValue(6, $acc_barcode, PDO::PARAM_STR);
                $sth->execute();
            }
        }
        else
        {
            $err = "COULD NOT DETERMINE ASSET STATUS";
        }

        return $err;
    }
}
?>