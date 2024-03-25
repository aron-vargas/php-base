<?php

/**
 * This class represents a leased piece of equipment.
 *
 * @author Aron Vargas
 * @package Freedom
 */
class LeaseAsset extends Asset {
    /**
     * @var integer the database id
     */
    private $id;

    /**
     * @var string the manufacturing date (?)
     */
    private $mfg_date;

    /**
     * @var string the date the piece of equipment went into service
     */
    private $svc_date;

    /**
     * @var string the last certification date
     */
    private $last_cert_date;

    /**
     * @var string the owning account (?)
     */
    private $owning_acct;

    /**
     * @var string the bill-to account (?)
     */
    private $bill_to_acct;

    /**
     * @var string the barcode for the asset
     */
    private $barcode;

    /**
     * @var string the firmware version for the asset
     */
    private $firmware_version;

    /**
     * @var string the software version for the asset
     */
    private $software_version;

    /**
     * @var string the mac_address for the asset
     */
    private $mac_address;

    /**
     * @var string the date the piece of equipment was acquisitioned
     */
    private $acq_date;

    /**
     * @var numeric acquisition price of the asset
     */
    private $acq_price;

    /**
     * @var numeric freight cost of the asset
     */
    private $freight;

    /**
     * @var string the Warranty End Date
     */
    private $warranty_date;

    /**
     * @var string the Warranty Type
     */
    private $warranty_type;

    /**
     * @var integer the Product Group
     */
    private $prod_group;

    /**
     * @var integer the Previous Asset ID of this Lease Asset
     */
    private $previousAssetID;

    /**
     * @var boolean Indicates the Asset is on loan to the facility
     */
    private $on_loan = null;

    /**
     * @var integer to hold the base_unit asset id an accessory is attached to.
     */
    public $base_unit = null;

    /**
     * @var integer to hold the accessory_unit asset ids a base_unit has attached.
     */
    private $accessory_units = null;

    /**
     * @var string to report problems to outside applications
     */
    public $error_str = null;

    public $last_transaction = null;

    public $LastCompletedIPM = null;
    public $LastCompletedIPMWprkOrder = null;
    public $LastCompletedIPMVs = null;
    public $LastCompletedIPMVsDate = null;

    /**
     * @var char ([P]urchase, [L]ease, [S]old)
     */
    public $tag;

    static public $PURCHASE_TAG = 'P';
    static public $LEASE_TAG = 'L';
    static public $SOLD_TAG = 'S';

    static public $DUPLICATE_TRANS = '23505';
    static public $MODEL_OMNISTAND = 161;

    static public $CURRENT_HEALTH_ACCT = 'CUR900';

    /**
     * Gets the next lease asset id.
     *
     * @return integer the next database id in the sequence.
     */
    public function getNextId()
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->query("SELECT NEXTVAL('lease_asset_id_seq')");
        return (int) $sth->fetchColumn();
    }


    /**
     * Creates a new LeaseAsset object.
     *
     * @param integer $lease_asset_id
     * @param integer $model_id
     * @param string $serial_num
     * @param string $mfg_date
     * @param string $svc_date
     * @param string $last_cert_date
     * @param string $manufacturer
     * @param string $owning_acct
     * @param string $bill_to_acct
     * @param string $barcode
     */
    public function __construct($lease_asset_id, $model_id = null,
        $serial_num = null, $mfg_date = null, $svc_date = null,
        $last_cert_date = null, $manufacturer = null, $owning_acct = null,
        $bill_to_acct = null, $barcode = null, $expiration_date = null,
        $warranty_type = null, $prod_group = null, $software_version = null,
        $firmware_version = null, $mac_address = null)
    {
        $dbh = DataStor::getHandle();

        # If a model id or serial number wasn't passed in, we can assume we
        # need to query the database to create this object.
        #
        if (is_null($model_id) || is_null($serial_num))
        {
            # If we have a valid lease asset id, query the database to create
            # the object.
            #
            if (is_numeric($lease_asset_id))
            {
                $sql = "SELECT
					la.id, la.model_id, la.serial_num, la.mfg_date, la.svc_date,
					la.last_cert_date, la.manufacturer, la.owning_acct, la.bill_to_acct,
					la.barcode, la.firmware_version, la.software_version, la.mac_address,
					GREATEST(
						cep.warranty_expiration_date,
						cep.date_shipped +
							CASE
								WHEN wo.complaint_type = 1 THEN '1 year'::interval
								ELSE wo.year_interval::interval
							END
					) as expiration_date,
					wo.warranty_name, sitp.prod_group,
					la.acq_date, la.acq_price, la.freight, aul.old_asset_id,
					CASE
						WHEN lnr.loaner_id IS NOT NULL THEN true
						ELSE false
					END as on_loan
				FROM lease_asset la
				JOIN equipment_models em ON la.model_id = em.id
				LEFT JOIN contract_line_item cep ON la.id = cep.asset_id
				LEFT JOIN warranty_option wo ON cep.warranty_option_id = wo.warranty_id
				LEFT JOIN loaner_agreement lnr ON cep.contract_id = lnr.contract_id AND lnr.active = true
				LEFT JOIN service_item_to_product sitp ON em.model = sitp.code
				LEFT JOIN asset_upgrade_link aul ON la.id = aul.new_asset_id
				WHERE la.id = {$lease_asset_id}";

                $sth = $dbh->query($sql);
                if ($sth->rowCount() > 0)
                {
                    list($this->id, $model_id, $this->serial, $this->mfg_date,
                        $this->svc_date, $this->last_cert_date,
                        $this->manufacturer, $this->owning_acct,
                        $this->bill_to_acct, $this->barcode,
                        $this->firmware_version, $this->software_version,
                        $this->mac_address, $this->warranty_date,
                        $this->warranty_type, $this->prod_group,
                        $this->acq_date, $this->acq_price, $this->freight,
                        $this->previousAssetID, $this->on_loan) = $sth->fetch(PDO::FETCH_NUM);

                    $this->model = new EquipmentModel($model_id);
                    $this->type = $this->model->getType();
                }
                else
                {
                    throw new Exception('Asset with id ' . $lease_asset_id . ' not found.');
                }
            }
            #
            # Otherwise, get a new id and assume that the client will fill in
            # the data with setter functions and then call save().
            #
            else
            {
                $this->id = $this->getNextId();
            }
        }
        #
        # Otherwise, just create the object from the parameters.
        #
        else
        {
            $this->id = (is_numeric($lease_asset_id)) ? $lease_asset_id : $this->getNextId();
            $this->model = new EquipmentModel($model_id);
            $this->serial = trim($serial_num);
            $this->mfg_date = $mfg_date;
            $this->svc_date = $svc_date;
            $this->last_cert_date = $last_cert_date;
            $this->manufacturer = $manufacturer;
            $this->owning_acct = $owning_acct;
            $this->bill_to_acct = $bill_to_acct;
            $this->barcode = $barcode;
            $this->warranty_date = $expiration_date;
            $this->warranty_type = $warranty_type;
            $this->prod_group = $prod_group;
            $this->type = $this->model->getType();
            $this->software_version = $software_version;
            $this->firmware_version = $firmware_version;
            $this->mac_address = $mac_address;
        }

        # Load extended information based on model type
        #
        if ($this->model)
        {
            if ($this->model->getType() == EquipmentModel::$BASEUNIT)
            {
                $this->loadAcessories();
            }
            else if ($this->model->getType() == EquipmentModel::$ACCESSORY)
            {
                $this->loadBaseUnit();
            }
        }

        $this->getLastCompletedIPM();
    }

    // For cr 2120
    public function getLastCompletedIPM()
    {
        $dbh = DataStor::getHandle();

        if ($this->model)
        {
            $sth = $dbh->prepare("SELECT
				p.id, p.work_order, p.vs_id, p.completed_date
			FROM wo_pm p
			LEFT JOIN visit_summaries_equipment vse ON p.id = vse.pm_id AND p.vs_id = vse.summary_id
			LEFT JOIN work_order w ON p.work_order = w.work_order
			WHERE p.pass AND p.completed_date > 0
			AND (w.model = ? OR vse.equipment_id = ?)
			AND (upper(vse.serial_num) = ? OR upper(w.serial_num) = ?)
			ORDER BY p.id DESC
			LIMIT 1");
            $sth->bindValue(1, $this->model->getId(), PDO::PARAM_INT);
            $sth->bindValue(2, $this->model->getId(), PDO::PARAM_INT);
            $sth->bindValue(3, strtoupper($this->serial), PDO::PARAM_STR);
            $sth->bindValue(4, strtoupper($this->serial), PDO::PARAM_STR);
            $sth->execute();
            if ($sth->rowCount() > 0)
            {
                list($pm_id, $work_order_id, $vs_id, $completed_date) = $sth->fetch(PDO::FETCH_NUM);
                $this->LastCompletedIPM = $pm_id;
                $this->LastCompletedIPMWprkOrder = $work_order_id;
                $this->LastCompletedIPMVs = $vs_id;
                $this->LastCompletedIPMVsDate = $completed_date;

            }
        }
    }

    /**
     * Returns a record for which an IPM can be printed
     * for old and new ACVS ipm or workorder ipm.
     *
     * @param integer $insp_date
     * @param integer $calibrator_name
     */
    public function getThatIPM($insp_date, $calibrator_name = null)
    {
        $date = new DateTime($insp_date);
        $insp_date = '' . $date->format('Y-m-d');
        $CONDITION = "WHERE insp_date::TEXT LIKE '{$insp_date}'";
        if (!empty ($calibrator_name) && $calibrator_name != "Technician")
        {
            $CONDITION .= " AND u.firstname || ' ' || u.lastname LIKE '{$calibrator_name}'";
        }

        $dbh = DataStor::getHandle();
        if ($this->model)
        {
            $sth = $dbh->prepare("SELECT
			c.insp_date,
			em.model || ' ' || em.description AS model,
			c.serial_num,
			u.firstname || ' ' || u.lastname as calibrator_name,
			c.source,
			c.source_id,
			c.asset_id,
			c.id AS pm_id,
			c.vse_id
		FROM (
			SELECT
				to_timestamp(vs.start_time)::DATE AS insp_date,
				vse.equipment_id as model_id,
				vse.serial_num,
				vs.cpt_id as calibrator_id,
				'Visit' as source,
				vs.id as source_id,
				la.id as asset_id,
				p.id,
				vse.id AS vse_id
			FROM visit_summaries vs
			INNER JOIN visit_summaries_equipment vse ON vse.summary_id = vs.id
			INNER JOIN lease_asset la ON UPPER(vse.serial_num) = UPPER(la.serial_num)
				AND vse.equipment_id = la.model_id
				AND la.id = ?
			LEFT JOIN wo_pm p ON p.vs_id = vse.summary_id AND p.id = vse.pm_id
			INNER JOIN (
				SELECT
					visit_summaries_equipment_id,
					COUNT(pmt.question_id) as asked,
					COUNT(CASE WHEN pmt.passed = true THEN 1 ELSE 0 END) AS passed
				FROM pmt_answers pmt
				GROUP BY visit_summaries_equipment_id
			) pmt ON pmt.visit_summaries_equipment_id = vse.id AND pmt.asked = pmt.passed
		UNION
			SELECT
				to_timestamp(wo.close_date)::DATE as insp_date,
				wo.model as model_id,
				wo.serial_num,
				wo.close_by as calibrator_id,
				'Workorder' as source,
				wo.work_order as source_id,
				la.id as asset_id,
				p.id,
				0 AS vse_id
			FROM work_order wo
			INNER JOIN lease_asset la ON UPPER(wo.serial_num) = UPPER(la.serial_num)
				AND wo.model = la.model_id
				AND la.id = ?
			INNER JOIN wo_pm p ON p.work_order = wo.work_order
			WHERE wo.has_inspection
		UNION
			SELECT
				to_timestamp(p.completed_date)::DATE as insp_date,
				vse.equipment_id as model_id,
				vse.serial_num,
				p.completed_by as calibrator_id,
				'Visit' as source,
				vs.id as source_id,
				la.id as asset_id,
				p.id,
				vse.id AS vse_id
			FROM wo_pm p
			INNER JOIN visit_summaries_equipment vse ON p.id = vse.pm_id AND p.vs_id = vse.summary_id
			INNER JOIN visit_summaries vs ON vse.summary_id = vs.id
			INNER JOIN lease_asset la ON UPPER(vse.serial_num) = UPPER(la.serial_num)
				AND vse.equipment_id = la.model_id
				AND la.id = ?
			WHERE p.pass AND p.completed_date > 0
		) c
		INNER JOIN users u ON u.id = c.calibrator_id
		INNER JOIN equipment_models em ON c.model_id = em.id
		{$CONDITION}");
            $sth->bindValue(1, $this->getId(), PDO::PARAM_INT);
            $sth->bindValue(2, $this->getId(), PDO::PARAM_INT);
            $sth->bindValue(3, $this->getId(), PDO::PARAM_INT);
            $sth->execute();

            $result = null;
            if ($sth->rowCount() > 0)
            {
                $result = $sth->fetch(PDO::FETCH_ASSOC);
            }

            return $result;
        }
    }

    /**
     * Add record to accessory_to_base_unit
     *
     * @param integer
     */
    public function Attach($base_unit_id)
    {
        $dbh = DataStor::getHandle();

        # Want NULL for anything but valid asset id
        if ($base_unit_id == 0 || $base_unit_id == "")
            $base_unit_id = null;

        if ($this->id && $this->model->getType() == EquipmentModel::$ACCESSORY)
        {
            $sth = $dbh->prepare("SELECT base_unit_asset_id
			FROM accessory_to_base_unit
			WHERE accessory_asset_id = ?
			ORDER BY tstamp DESC
			LIMIT 1");
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();
            $current = $sth->fetchColumn();

            if ($base_unit_id != $current)
            {
                $bu_type = is_null($base_unit_id) ? PDO::PARAM_NULL : PDO::PARAM_INT;

                $sth = $dbh->prepare("INSERT INTO accessory_to_base_unit
				(base_unit_asset_id, accessory_asset_id)
				VALUES (?, ?)");
                $sth->bindValue(1, $base_unit_id, $bu_type);
                $sth->bindValue(2, $this->id, PDO::PARAM_INT);
                $sth->execute();
            }

            $this->base_unit = $base_unit_id;
        }
    }

    /**
     * Changes one field in the database and reloads the object.
     *
     * @param string $field
     * @param mixed $value
     */
    public function Change($field, $value)
    {
        if ($this->id)
        {
            $dbh = DataStor::getHandle();

            # Determine type of input
            if (is_int($value))
                $val_type = PDO::PARAM_INT;
            else if (is_bool($value))
                $val_type = PDO::PARAM_BOOL;
            else if (is_null($value))
                $val_type = PDO::PARAM_NULL;
            else if (is_string($value) || is_float($value))
                $val_type = PDO::PARAM_STR;
            else
                $val_type = FALSE;

            $sth = $dbh->prepare("UPDATE lease_asset SET {$field} = ? WHERE id = ?");
            $sth->bindValue(1, $value, $val_type);
            $sth->bindValue(2, $this->id, PDO::PARAM_INT);
            $sth->execute();
            $this->{$field} = $value;
        }
        else
        {
            throw new Exception('Cannot update a non-existant record.');
        }
    }

    /**
     * Determine if device is customer Owned
     *
     * @return bool
     */
    public function CustomerOwned()
    {
        return self::IsCustomerOwned($this->owning_acct);
    }

    /**
     * Determine if device is customer Owned
     *
     * @return bool
     */
    static public function IsCustomerOwned($owning_acct)
    {
        // Cases for Owned:
        // Empty/NUll or bad format
        // DEFAULT001 (this is configurable)
        if (strlen($owning_acct) < 6)
            $customer_owned = false;
        else if ($owning_acct == Config::$PURCHASE_ACCT)
            $customer_owned = false;
        else if ($owning_acct == LeaseAsset::$CURRENT_HEALTH_ACCT)
            $customer_owned = false;
        else
            $customer_owned = true;

        return $customer_owned;
    }


    /**
     * Adds a transaction to this LeaseAsset.
     *
     * @param integer $facility_id
     * @param string $status
     * @param string $substatus
     * @param User $user
     */
    public function addTransaction($facility_id, $status, $substatus, $user, $comment = '', $tstamp = null, $from_workorder = false)
    {
        $dbh = DataStor::getHandle();

        $last_trans = $this->getLastTransaction();

        # If going from !FGI to FGI Check for "Editable" Work Orders
        if ($status == LeaseAssetTransaction::$FGI)
        {
            if (is_null($last_trans) || $last_trans->getStatus() != LeaseAssetTransaction::$FGI)
            {
                $sth = $dbh->prepare("SELECT editable FROM work_order WHERE model = ? and upper(serial_num) = ?");
                $sth->bindValue(1, $this->model->getId(), PDO::PARAM_INT);
                $sth->bindValue(2, strtoupper($this->serial), PDO::PARAM_STR);
                $sth->execute();
                while (list($editable) = $sth->fetch(PDO::FETCH_NUM))
                {
                    if ($editable) # Do Not add transaction
                    {
                        $this->error_str = "Cannot add FGI Transaction! Found an unverified Work Order.";
                        return;
                    }
                }
            }
        }

        # Detect an attempt to add a transaction before $last_trans
        # Will not work well with trigger function, so $tstamp need to be current_timestamp
        if ($tstamp && $last_trans)
        {
            if (strtotime($tstamp) < $last_trans->getTimestamp())
                $tstamp = null;
        }

        # If setting to OOS clean up Work Orders
        #
        if ($status == LeaseAssetTransaction::$OUT_OF_SERVICE)
        {
            $now = time();

            $sth = $dbh->prepare("UPDATE work_order SET
				editable = false,
				status = 4,
				close_date = CASE WHEN close_date > 0 THEN close_date ELSE $now END
			WHERE editable = true AND model = ? AND upper(serial_num) = ?");
            $sth->bindValue(1, $this->model->getId(), PDO::PARAM_INT);
            $sth->bindValue(2, strtoupper($this->serial), PDO::PARAM_STR);
            $sth->execute();
        }

        $fid_type = ($facility_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;

        # Indicate no comment
        $comment = trim($comment);
        if ($comment == "")
            $comment = "--Missing Comment--";

        if ($tstamp)
        {
            $sth = $dbh->prepare('
			INSERT INTO lease_asset_transaction (lease_asset_id,facility_id,status,substatus,user_id,comment,tstamp)
			VALUES (?,?,?,?,?,?,?)');
        }
        else
        {
            $sth = $dbh->prepare('
			INSERT INTO lease_asset_transaction (lease_asset_id,facility_id,status,substatus,user_id,comment)
			VALUES (?,?,?,?,?,?)');
        }

        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->bindValue(2, $facility_id, $fid_type);
        $sth->bindValue(3, $status, PDO::PARAM_STR);
        $sth->bindValue(4, $substatus, PDO::PARAM_STR);
        $sth->bindValue(5, $user->getId(), PDO::PARAM_INT);
        $sth->bindValue(6, $comment, PDO::PARAM_STR);
        if ($tstamp)
            $sth->bindValue(7, $tstamp, PDO::PARAM_STR);
        $sth->execute();

        if ($this->accessory_units)
        {
            if ($tstamp)
            {
                $sth_chk = $dbh->prepare("SELECT 1 FROM lease_asset_transaction WHERE lease_asset_id = ? AND tstamp = ?");
                $sth_chk->bindValue(2, $tstamp, PDO::PARAM_STR);
            }
            else
                $sth_chk = $dbh->prepare("SELECT 1 FROM lease_asset_transaction WHERE lease_asset_id = ? AND tstamp = now()");

            # Match accessory status and facility with that of the base
            #
            foreach ($this->accessory_units as $key => $accessory_id)
            {
                if ($accessory_id)
                {
                    $sth_chk->bindValue(1, $accessory_id, PDO::PARAM_INT);
                    $sth_chk->execute();
                    if (!$sth_chk->fetchColumn())
                    {

                        $sth->bindValue(1, $accessory_id, PDO::PARAM_INT);
                        $sth->bindValue(2, $facility_id, $fid_type);
                        $sth->bindValue(3, $status, PDO::PARAM_STR);
                        $sth->bindValue(4, $substatus, PDO::PARAM_STR);
                        $sth->bindValue(5, $user->getId(), PDO::PARAM_INT);
                        $sth->bindValue(6, $comment, PDO::PARAM_STR);
                        if ($tstamp)
                            $sth->bindValue(7, $tstamp, PDO::PARAM_STR);
                        $sth->execute();
                    }

                    # Match owning account
                    $sth_owner = $dbh->prepare('UPDATE lease_asset SET owning_acct = ? WHERE id = ?');
                    $sth_owner->bindValue(1, $this->getOwningAcct(), PDO::PARAM_STR);
                    $sth_owner->bindValue(2, $accessory_id, PDO::PARAM_INT);
                    $sth_owner->execute();
                }
            }
        }

        if ($this->model->getType() == EquipmentModel::$BASEUNIT)
        {
            # Add Writeoff records
            if ($substatus == LeaseAssetTransaction::$SCRAPPED)
                FASOwnership::Scrap($this->id, $tstamp);
            else if ($substatus == LeaseAssetTransaction::$LOST)
                FASOwnership::OOS($this->id, $tstamp);
            else if ($last_trans && $last_trans->getStatus() == LeaseAssetTransaction::$OUT_OF_SERVICE)
            {
                # Going from OOS to some other status, then return the FAS record to back in service
                if ($status != LeaseAssetTransaction::$OUT_OF_SERVICE)
                    FASOwnership::OOSRevert($this->id, $tstamp, "Return to Service.");
            }

            # Packaging starts depreciation cycle
            if ($status == LeaseAssetTransaction::$PACK)
            {
                if ($this->CustomerOwned() == false && $this->IsPE() == true)
                    FASOwnership::StartDepreciation($this->id);
            }
        }
    }

    /**
     * Add work order record for this received device
     * @return integer
     * @throws Exception
     */
    function CreateReceivedWorkOrder()
    {
        $dbh = DataStor::getHandle();

        # Create a work order for this asset.
        #
        $model = $this->getModel()->getId();
        $serial = $this->getSerial();
        $work_order = WorkOrder::Generate($model, $serial, null, $this->getType());

        /// Model: A005-509 :: OmniStand
        if ($model == self::$MODEL_OMNISTAND)
            DetachAll($this);

        if ($work_order)
        {
            $work_order->setVar('barcode', $this->getBarcode());
            $work_order->setVar('sw_version', $this->getSWVersion());

            if ($this->getType() == EquipmentModel::$ACCESSORY)
            {
                $sth_query = $dbh->query("
				SELECT oi.order_id
				FROM order_item oi
				INNER JOIN event_order eo ON eo.order_id = oi.order_id
				INNER JOIN lease_asset la ON la.id = oi.swap_asset_id
					AND la.model_id = {$model}
					AND la.serial_num = '{$serial}'");

                if ($sth_query->rowCount() > 0)
                    $work_order->setVar('problem', 'PM');
            }

            $complaints = $work_order->getVar('complaints');
            if (isset ($complaints[0]['complaint']))
                $return_dme = in_array($complaints[0]['complaint'], array(WorkOrder::$RTN_ORTHOTIC, WorkOrder::$RTN_TENS));
            else
                $return_dme = false;

            # Retain facility id for dealer repairs
            #
            if ($return_dme)
                $work_order->setVar('dealer_repair', 0);

            $work_order->save();
        }
        else
        {
            throw new Exception('The WorkOrder object was not generated.');
        }

        return $work_order;
    }

    /**
     * Returns an array of all previous transactions.
     *
     * @return array an array of LeaseAssetTransaction
     */
    public function getAllTransactions()
    {
        $dbh = DataStor::getHandle();

        $transactions = array();
        $sth = $dbh->prepare('
			SELECT lease_asset_id,
			       EXTRACT(EPOCH FROM tstamp) AS tstamp,
			       tstamp as realtime,
			       facility_id,
			       status,
			       substatus,
			       user_id,
			       comment
			FROM lease_asset_transaction
			WHERE lease_asset_id = ?
			ORDER BY tstamp DESC');
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $transactions[] = new LeaseAssetTransaction($row['lease_asset_id'],
                $row['tstamp'], $row['realtime'], new User($row['user_id']), $row['facility_id'],
                $row['status'], $row['substatus'], $row['comment']);
        }

        return $transactions;
    }


    /**
     * Returns an array of all previous transactions.
     *
     * @return array an array of base unit transactions
     */
    public function getAllBaseUnitTransactions()
    {
        $dbh = DataStor::getHandle();

        $transactions = array();
        $sth = $dbh->prepare("SELECT
			tstamp, em.model || ' :: ' || em.description as base_model, la.id as asset_id, la.serial_num, la.barcode
		FROM accessory_to_base_unit atbu
		INNER JOIN lease_asset la ON la.id = atbu.base_unit_asset_id
		INNER JOIN equipment_models em ON em.id = la.model_id
		WHERE accessory_asset_id = ?
		ORDER BY tstamp DESC");
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->execute();
        $transactions = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $transactions;
    }


    /**
     * Returns an array of all previous transactions.
     *
     * @return array an array of accessory transactions
     */
    public function getAllAccessoryTransactions()
    {
        $dbh = DataStor::getHandle();

        $transactions = array();
        $sth = $dbh->prepare("SELECT tstamp, em.model || ' :: ' || em.description as accessory_model, la.serial_num, la.barcode
							   FROM accessory_to_base_unit atbu
							   INNER JOIN lease_asset la ON la.id = atbu.accessory_asset_id
							   INNER JOIN equipment_models em ON em.id = la.model_id
							   WHERE base_unit_asset_id = ?
							   ORDER BY accessory_model, tstamp DESC");
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->execute();
        while ($row = $sth->fetch())
        {
            $transactions[$row['accessory_model']][] = $row;
        }

        return $transactions;
    }


    /**
     * @return string
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * @return string
     */
    public function getFWVersion()
    {
        return $this->firmware_version;
    }

    /**
     * @return string
     */
    public function getSWVersion()
    {
        return $this->software_version;
    }

    /**
     * @return string
     */
    public function getSWVersionList()
    {
        $ver_text = "";
        $versions = explode("\n", $this->software_version);
        foreach ($versions as $ver)
        {
            $ver_text .= "<li>$ver</li>";
        }
        return $ver_text;
    }

    /**
     * @return string
     */
    public function getMACAddress()
    {
        return $this->mac_address;
    }

    /**
     * @return string
     */
    public function getBaseUnit()
    {
        return $this->base_unit;
    }


    /**
     * @return string
     */
    public function getBillToAcct()
    {
        return $this->bill_to_acct;
    }


    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->model->getType();
    }


    /**
     * @return string
     */
    public function getLastCertDate()
    {
        return $this->last_cert_date;
    }

    /**
     * @return string
     */
    public function getWarrantyDate()
    {
        return $this->warranty_date;
    }

    /**
     * @return string
     */
    public function getWarrantyType()
    {
        return $this->warranty_type;
    }

    /**
     * @return integer
     */
    public function getProdGroup()
    {
        return $this->prod_group;
    }

    /**
     * @return array
     */
    public function getAccessories()
    {
        return $this->model->getAccessories();
    }

    /**
     * @return array
     */
    public function getAccessoryUnits()
    {
        return $this->accessory_units;
    }


    /**
     * @return integer
     */
    public function getIpmId()
    {
        return $this->LastCompletedIPM;
    }
    /**
     * @return integer
     */
    public function getIpmWprkOrderId()
    {
        return $this->LastCompletedIPMWprkOrder;
    }

    /**
     * @return integer
     */
    public function getIpmVsId()
    {
        return $this->LastCompletedIPMVs;
    }

    /**
     * @return integer
     */
    public function getLastIpmVsDate()
    {
        return $this->LastCompletedIPMVsDate;
    }

    /**
     * Returns the last transaction.
     *
     * @param boolean
     * @return LeaseAssetTransaction
     */
    public function getLastTransaction($reload = false)
    {
        if (is_null($this->last_transaction) || $reload)
        {
            $dbh = DataStor::getHandle();

            $sth = $dbh->prepare('
				SELECT lat.lease_asset_id,
				       EXTRACT(EPOCH FROM lat.tstamp) AS tstamp,
				       lat.tstamp as realtime,
				       lat.facility_id,
				       lat.status,
				       lat.substatus,
				       lat.user_id,
				       lat.comment
				FROM (SELECT lease_asset_id, MAX(tstamp) AS tstamp
				      FROM lease_asset_transaction
				      WHERE lease_asset_id = ?
				      GROUP BY lease_asset_id) max_tstamp
				  INNER JOIN lease_asset_transaction lat ON
				    (lat.lease_asset_id = max_tstamp.lease_asset_id AND
				     lat.tstamp = max_tstamp.tstamp)');
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();

            if ($sth->rowCount() > 0)
            {
                $row = $sth->fetch(PDO::FETCH_ASSOC);

                $this->last_transaction = new LeaseAssetTransaction($row['lease_asset_id'],
                    $row['tstamp'], $row['realtime'], new User($row['user_id']),
                    $row['facility_id'], $row['status'], $row['substatus'], $row['comment']);
            }
        }

        return $this->last_transaction;
    }

    /**
     * Returns asset location (Future Enhancement)
     * For now use current facility
     *
     * @param boolean
     * @return string
     */
    public function GetLocation($as_link = false)
    {
        $tran = $this->getLastTransaction();
        $fac = ($tran) ? $tran->getFacility() : null;
        $loc = ($fac) ? $fac->getCustId() : "Unk";

        if ($fac && $as_link)
        {
            $loc = "<a href='facilities.php?act=view&entry={$fac->getId()}' alt='View Facility' label='View Facility'>$loc</a>";
        }

        return $loc;
    }


    /**
     * Returns the transaction $how_many ago.
     * $how_many = 0 means the current transaction.
     *
     * @return LeaseAssetTransaction
     */
    public function getTransaction($how_many = 0)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->query("
			SELECT lat.lease_asset_id,
       			   EXTRACT(EPOCH FROM lat.tstamp) AS tstamp,
       			   lat.tstamp AS realtime,
       			   lat.facility_id,
       			   lat.status,
       			   lat.substatus,
       			   lat.user_id,
       			   lat.comment
			FROM lease_asset_transaction lat
			WHERE lat.lease_asset_id = {$this->id}
			ORDER BY lat.tstamp desc
			OFFSET {$how_many}
			LIMIT 1");

        if ($sth->rowCount() > 0)
        {
            $row = $sth->fetch(PDO::FETCH_ASSOC);

            return new LeaseAssetTransaction($row['lease_asset_id'],
                $row['tstamp'], $row['realtime'], new User($row['user_id']),
                $row['facility_id'], $row['status'], $row['substatus'], $row['comment']);
        }

        return null;
    }


    /**
     * @return string
     */
    public function getMfgDate()
    {
        return $this->mfg_date;
    }

    /**
     * @return string
     */
    public function getAcqDate()
    {
        return $this->acq_date;
    }

    /**
     * @return string
     */
    public function getAcqPrice($price_only = false)
    {
        return ($price_only) ? $this->acq_price : $this->acq_price + $this->freight;
    }

    /**
     * @return double
     */
    public function getFreight()
    {
        return $this->freight;
    }

    /**
     * Based on model determine who should own this originally
     *
     * @return string
     */
    public function GetDefaultOwner()
    {
        return ($this->model) ? $this->model->GetDefaultOwner() : NULL;
    }

    /**
     * @return string
     */
    public function getDepreciatedValue()
    {
        $dbh = DataStor::getHandle();

        /*
         * Model #
         * Model Description
         * Serial #
         * ACQ Date
         * Opening Value
         * Additions?
         * Sold?
         * Exch Rate?
         * Upgraded?
         * Swap?
         * Total Value
         * Months in SVC
         * Accumulative Depreciation
         */
        $sql = "SELECT
			em.model, em.description, la.serial_num, la.acq_date, CASE em.lifespan WHEN 0 THEN 10 ELSE em.lifespan END AS lifespan,
			CASE
			WHEN EXTRACT( 'year' FROM acq_date ) <> EXTRACT( 'year' FROM CURRENT_DATE )
			THEN COALESCE( la.acq_price, 0 ) + COALESCE( la.freight, 0 )
			ELSE 0
			END AS opening_value,
			CASE
			WHEN EXTRACT( 'year' FROM acq_date ) = EXTRACT( 'year' FROM CURRENT_DATE )
			THEN COALESCE( la.acq_price, 0 ) + COALESCE( la.freight, 0 )
			ELSE 0
			END AS additions,
			COALESCE( 0, 0 ) AS sold,
			COALESCE( 0, 0 ) AS exch_rate,
			CASE
			WHEN oos.substatus = 'Upgraded'
			THEN 1
			ELSE 0
			END AS upgraded,
			COALESCE( 0, 0 ) AS swap,
			CASE
			WHEN oos.substatus = 'Scrapped' AND oos.status_year = EXTRACT( 'year' FROM CURRENT_DATE )
			THEN 1
			ELSE 0
			END AS current_scrap,
			CASE
			WHEN oos.substatus = 'Scrapped' AND oos.status_year <> EXTRACT( 'year' FROM CURRENT_DATE )
			THEN 1
			ELSE 0
			END AS previous_scrap,
			age( CURRENT_DATE, acq_date ) AS service_interval,
			( CASE em.lifespan WHEN 0 THEN 10 ELSE em.lifespan END || ' years' )::INTERVAL AS lifespan_interval,
			EXTRACT( 'epoch' FROM age( CURRENT_DATE, acq_date ) ) AS service_epoch,
			months_between( CURRENT_DATE, acq_date )::NUMERIC( 8, 2 ) AS months_in_svc,
			CASE
			WHEN age( CURRENT_DATE, acq_date ) > ( em.lifespan || ' years' )::INTERVAL
			THEN la.acq_price + la.freight::NUMERIC( 8, 2 )
			ELSE ( ( la.acq_price + la.freight ) -
					( EXTRACT( 'epoch' FROM ( ( em.lifespan || ' years' )::INTERVAL - AGE( CURRENT_DATE, la.acq_date ) ) ) /
						EXTRACT( 'epoch' FROM ( em.lifespan || ' years' )::INTERVAL ) *
						( la.acq_price + la.freight ) ) )::NUMERIC( 8, 2 )
			END AS accumulative_depreciation
		FROM lease_asset la
		JOIN equipment_models em ON la.model_id = em.id
								AND em.type_id = 1
		LEFT JOIN ( SELECT lease_asset_id AS id, substatus, EXTRACT( 'year' FROM tstamp ) AS status_year
					FROM lease_asset_transaction
					WHERE status = 'Out of Service'
					AND substatus IN ( 'Scrapped', 'Upgraded' )
					AND tstamp::DATE <= CURRENT_DATE
				) oos ON la.id = oos.id
		WHERE acq_date IS NOT NULL
		AND acq_date <= CURRENT_DATE
		AND la.id = {$this->id}";

        //echo "<!--\n{$sql}\n-->";
        $sth = $dbh->query($sql);

        $fasxcel_data = array();

        list($model, $description, $serial_num, $acq_date, $lifespan,
            $opening_value, $additions, $sold, $exch_rate, $upgraded, $swap, $current_scrap, $previous_scrap,
            $service_interval, $lifespan_interval, $service_epoch,
            $months_in_svc, $accumulative_depreciation) = $sth->fetch(PDO::FETCH_NUM);

        $final_total = $total = $opening_value + $additions;

        // Subtract total if unit sold
        $final_total -= ($sold == 1) ? $total : 0;

        // Subtract total if unit upgraded
        $final_total -= ($upgraded == 1) ? $total : 0;

        // Add total if unit swapped after purchase
        $final_total += ($swap == 1) ? $total : 0;

        // Subtract total if unit scrapped
        $final_total -= ($current_scrap == 1 || $previous_scrap == 1) ? $total : 0;

        $lifespan = ($lifespan == 0) ? 10 : $lifespan;

        return $final_total - (((($final_total / ($lifespan * 12)) * $months_in_svc) > $final_total) ? $final_total : ($final_total / ($lifespan * 12)) * $months_in_svc);
    }

    /**
     * @return string
     */
    public function getOwningAcct()
    {
        $owning_acct = trim($this->owning_acct);

        if (empty ($owning_acct))
        {
            $owner = FASOwnership::GetActiveOwner($this->id);
            $owning_acct = $owner->owning_acct;
            $this->owning_acct = $owner->owning_acct;
        }

        return $owning_acct;
    }

    /**
     * @return integer
     */
    public function getPreviousAssetID()
    {
        return $this->previousAssetID;
    }


    /**
     * @return string
     */
    public function getSvcDate()
    {
        return $this->svc_date;
    }


    /**
     * AJV - Renamed this function. Software version is now stored in the asset record
     * This still could be useful to compare against value from
     * the lastest work order
     *
     * @return string
     */
    public function getWOSWVersion()
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("
			SELECT sw_version
			FROM work_order wo
			  INNER JOIN (
			    SELECT model, serial_num, MAX(open_date) AS open_date
			    FROM work_order
			    WHERE sw_version IS NOT NULL AND sw_version != ''
			    GROUP BY model, serial_num
			  ) AS latest_wo ON wo.model = latest_wo.model AND
			                    wo.serial_num = latest_wo.serial_num AND
			                    wo.open_date = latest_wo.open_date
			WHERE wo.model = ? AND
			      upper(wo.serial_num) = ?");
        $sth->bindValue(1, $this->model->getId(), PDO::PARAM_INT);
        $sth->bindValue(2, strtoupper($this->serial), PDO::PARAM_STR);
        $sth->execute();
        if ($sth->rowCount() > 0)
        {
            return $sth->fetchColumn();
        }

        return null;
    }

    /**
     * Find transaction history for the asset
     *
     * @param string
     * @param string
     * @param mixed
     * @param string
     * @param string
     * @param mixed
     *
     * @return boolean
     */
    public function HasTransaction($status = null, $substatus = null, $facilities = null,
        $min_time = null, $max_time = null, $ex_facilities = null)
    {
        $dbh = DataStor::getHandle();
        $found = false;

        # Filter on status
        #
        $status_clause = ($status) ? "AND t.status = " . $dbh->quote($status) : "";

        # Filter on substatus
        #
        $sub_clause = ($substatus) ? "AND t.substatus = " . $dbh->quote($substatus) : "";

        # Filter on facility
        #
        $facility_clause = "";
        if (is_array($facilities))
            $facility_clause = "AND t.facility_id IN (" . implode(",", $facilities) . ")";
        else if ($facilities)
            $facility_clause = "AND t.facility_id = $facilities";

        # Exclude facility
        #
        $ex_facility_clause = "";
        if (is_array($ex_facilities))
            $ex_facility_clause = "AND t.facility_id NOT IN (" . implode(",", $ex_facilities) . ")";
        else if ($ex_facilities)
            $ex_facility_clause = "AND t.facility_id != $ex_facilities";

        # Filter on tstamp
        #
        if ($min_time && $max_time)
            $tstamp_clause = "AND t.tstamp BETWEEN " . $dbh->quote($min_time) . " AND " . $dbh->quote($max_time);
        else if ($min_time)
            $tstamp_clause = "AND t.tstamp >= " . $dbh->quote($min_time);
        else if ($max_time)
            $tstamp_clause = "AND t.tstamp <= " . $dbh->quote($max_time);
        else
            $tstamp_clause = "";

        if ($this->id)
        {
            $sql = "SELECT count(*)
			FROM lease_asset_transaction t
			WHERE t.lease_asset_id = {$this->id}
			$status_clause	$sub_clause	$facility_clause $ex_facility_clause $tstamp_clause";
            $sth = $dbh->query($sql);
            $found = $sth->fetchColumn();
        }

        return (bool) $found;
    }

    /**
     * Returns if the Asset is currently associated with an incident.
     */
    public function HasIncident()
    {
        $dbh = DataStor::getHandle();
        $has_incident = false;

        $sql = <<<END
SELECT e.is_incident, e.issue_id
FROM complaint_form_equipment e
JOIN ( SELECT MAX( issue_id ) AS issue_id
       FROM complaint_form_equipment
       WHERE model = ?
       AND upper(serial_number) = ?
     ) m ON e.issue_id = m.issue_id
WHERE model = ?
AND upper(serial_number) = ?
END;

        # Find latest Complaint Form
        $sth = $dbh->prepare($sql);

        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->bindValue(2, strtoupper($this->serial), PDO::PARAM_STR);
        $sth->bindValue(3, $this->id, PDO::PARAM_INT);
        $sth->bindValue(4, strtoupper($this->serial), PDO::PARAM_STR);
        $sth->execute();

        while (list($is_incident) = $sth->fetch(PDO::FETCH_NUM))
            if ($is_incident)
                $has_incident = true;

        return $has_incident;
    }

    /**
     * Returns if the Asset is in an Open Order( Queued, Processed )
     */
    public function inOpenOrder()
    {
        $dbh = DataStor::getHandle();
        $open_orders = array(Order::$QUEUED, Order::$PROCESSED);

        $sql = "SELECT o.id
		FROM orders o
		INNER JOIN order_item oi ON o.id = oi.order_id
		AND ( asset_id = {$this->id} OR swap_asset_id = {$this->id} )
		AND o.status_id IN ( " . implode(", ", $open_orders) . " )";

        $sth = $dbh->query($sql);

        $order_id = $sth->fetchColumn();

        return ($order_id) ? $order_id : null;
    }

    /**
     * True for Program Equipment
     *
     * @return boolean
     */
    public function IsPE()
    {
        $is_pe = false;

        ## This should be data driven

        if ($this->model)
        {
            $is_pe = true;

            # Dont mess with inactive or test equipment models
            if ($this->model->isActive() == false || $this->model->isTestEquipment() == true)
                $is_pe = false;
        }

        return $is_pe;
    }

    /**
     * Looks for Asset Lock.
     * Optional : Check for lock specific id or name
     *
     * @param integer
     * @param string
     * @return integer
     */
    public function hasLock($id = null, $name = null)
    {
        $dbh = DataStor::getHandle();

        # Basic query
        $sql = "SELECT
			id,
			lease_asset_id,
			tstamp,
			locked_by_id,
			locked_by_name
		FROM lease_asset_lock WHERE lease_asset_id = ?";

        # Optional:
        # Match locked_by_id
        # Match locked_by_name
        #
        if ($id)
            $sql .= " AND locked_by_id = ?";
        if ($name)
            $sql .= " AND locked_by_name = ?";

        $sth = $dbh->prepare($sql);

        # Bind Values
        $i = 1;
        $sth->bindValue($i++, (int) $this->id, PDO::PARAM_INT);
        if ($id)
            $sth->bindValue($i++, (int) $id, PDO::PARAM_INT);
        if ($name)
            $sth->bindValue($i++, (int) $name, PDO::PARAM_STR);

        $sth->execute();
        $lock = $sth->fetch(PDO::FETCH_OBJ);

        return $lock;
    }

    /**
     * Load accessory_units array with all
     * acessory units attached to this base
     *
     * AJV 2012-11-01: I moved this out of the constructor.
     * $this->accessories seems to be unused but I kept it.
     * I believe the query can be optimized but I left that
     * for another day
     */
    public function LoadAcessories()
    {
        # This is a array of model ids that can be attached to this base model
        #
        $this->accessories = $this->model->getAccessories();

        if (count($this->accessories))
        {
            $dbh = DataStor::getHandle();

            foreach ($this->accessories as $acc_model_id)
            {
                # Default to null
                $this->accessory_units[$acc_model_id] = null;

                # Query for accessories attached to this base unit
                $sth = $dbh->query("SELECT
					base_unit_asset_id,
					accessory_asset_id
				FROM accessory_to_base_unit atbu
				INNER JOIN lease_asset la ON la.id = atbu.accessory_asset_id
					AND la.model_id = {$acc_model_id}
				WHERE base_unit_asset_id = (
					SELECT base_unit_asset_id
					FROM accessory_to_base_unit
					WHERE accessory_asset_id = (
						SELECT accessory_asset_id
						FROM accessory_to_base_unit atbu
						INNER JOIN lease_asset la ON la.id = atbu.accessory_asset_id
							AND la.model_id = {$acc_model_id}
						WHERE base_unit_asset_id = {$this->id}
						ORDER BY tstamp DESC
						LIMIT 1
					)
					ORDER BY tstamp DESC
					LIMIT 1
				)
				ORDER BY tstamp DESC
				LIMIT 1");

                list($base_asset_id, $acc_asset_id) = $sth->fetch();

                # Add accessory asset_id if valid
                if ($base_asset_id == $this->id && $acc_asset_id)
                    $this->accessory_units[$acc_model_id] = $acc_asset_id;
            }
        }
    }

    /**
     * Load base_unit id this acessory is attached to
     *
     * AJV 2012-11-01: I moved this out of the contructor.
     * As with the above routine, I believe the query can be optimized.
     */
    public function LoadBaseUnit()
    {
        $dbh = DataStor::getHandle();

        # Default to null
        $this->base_unit = null;

        $model_id = $this->model->GetID();

        $sth = $dbh->query("SELECT
			base_unit_asset_id,
			accessory_asset_id
		FROM accessory_to_base_unit atbu
		INNER JOIN lease_asset la ON la.id = atbu.accessory_asset_id AND la.model_id = {$model_id}

		WHERE base_unit_asset_id = (
			SELECT base_unit_asset_id
			FROM accessory_to_base_unit
			WHERE accessory_asset_id = {$this->id}
			ORDER BY tstamp DESC
			LIMIT 1
		)
		ORDER BY tstamp DESC
		LIMIT 1");

        list($base_asset_id, $acc_asset_id) = $sth->fetch();

        if ($acc_asset_id == $this->id && $base_asset_id)
            $this->base_unit = $base_asset_id;
    }

    /**
     * Returns the Asset on_loan attribute
     *
     * @return boolean
     */
    public function OnLoan()
    {
        return $this->on_loan;
    }

    /**
     * Remove asset lock
     */
    public function RMLock()
    {
        $dbh = DataStor::getHandle();

        # Basic query
        $sql = "DELETE FROM lease_asset_lock WHERE lease_asset_id = ?";
        $sth = $dbh->prepare($sql);
        $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Saves this object to the database.
     */
    public function save($asset_tracking_save = false)
    {
        # First check to see if an entry in the database already exists with
        # this model and serial number.  This prevents someone from adding a
        # new machine that would be a duplicate.
        #
        if ($id = self::exists($this->model->getId(), $this->serial, $this->id))
        {
            $exc = new Exception('');
            $exc->setVal($id);
            throw $exc;
        }
        #
        # If the model/serial doesn't already exist (with a different id), add
        # or update this object's record.
        #
        else
        {
            $dbh = DataStor::getHandle();

            $sth = $dbh->prepare('SELECT COUNT(*) FROM lease_asset WHERE id = ?');
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();
            $cnt = $sth->fetchColumn();
            if ($cnt > 0)
            {
                $sth2 = $dbh->prepare('
					UPDATE lease_asset SET
					  model_id = ?,
					  serial_num = ?,
					  mfg_date = ?,
					  svc_date = ?,
					  last_cert_date = ?,
					  manufacturer = ?,
					  owning_acct = ?,
					  bill_to_acct = ?,
					  barcode = ?,
					  firmware_version = ?,
					  software_version = ?,
					  mac_address = ?
					WHERE id = ?');
            }
            else
            {
                $sth2 = $dbh->prepare('
					INSERT INTO lease_asset (model_id,serial_num,mfg_date,
					  svc_date,last_cert_date,manufacturer,owning_acct,
					  bill_to_acct,barcode,firmware_version,
					  software_version,mac_address,id)
					VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            }

            $mfg_date = (strtotime($this->mfg_date)) ? $this->mfg_date : null;
            $svc_date = (strtotime($this->svc_date)) ? $this->svc_date : null;
            $last_cert_date = (strtotime($this->last_cert_date)) ? $this->last_cert_date : null;
            $fwv_type = (trim($this->firmware_version)) ? PDO::PARAM_STR : PDO::PARAM_NULL;
            $swv_type = (trim($this->software_version)) ? PDO::PARAM_STR : PDO::PARAM_NULL;
            $mac_type = (trim($this->mac_address)) ? PDO::PARAM_STR : PDO::PARAM_NULL;

            $sth2->bindValue(1, $this->model->getId(), PDO::PARAM_INT);
            $sth2->bindValue(2, trim($this->serial), PDO::PARAM_STR);
            $sth2->bindValue(3, $mfg_date, is_null($mfg_date) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $sth2->bindValue(4, $svc_date, is_null($svc_date) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $sth2->bindValue(5, $last_cert_date, is_null($last_cert_date) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $sth2->bindValue(6, $this->manufacturer, PDO::PARAM_STR);
            $sth2->bindValue(7, $this->owning_acct, PDO::PARAM_STR);
            $sth2->bindValue(8, $this->bill_to_acct, PDO::PARAM_STR);

            if (is_null($this->barcode) || trim($this->barcode) == '')
                $sth2->bindValue(9, null, PDO::PARAM_NULL);
            else
                $sth2->bindValue(9, strtoupper($this->barcode), PDO::PARAM_STR);

            $sth2->bindValue(10, trim($this->firmware_version), $fwv_type);
            $sth2->bindValue(11, trim($this->software_version), $swv_type);
            $sth2->bindValue(12, trim($this->mac_address), $mac_type);
            $sth2->bindValue(13, $this->id, PDO::PARAM_INT);
            $sth2->execute();

            if ($this->model->getType() == EquipmentModel::$ACCESSORY)
                $this->Attach($this->base_unit);
        }
    }

    /**
     * @param string
     */
    public function setAcqDate($date)
    {
        $this->acq_date = $date;
    }

    /**
     * @param float
     */
    public function setAcqPrice($price)
    {
        $this->acq_price = $price;
    }

    /**
     * @param string $bill_to_acct
     */
    public function setBillToAcct($bill_to_acct)
    {
        $this->bill_to_acct = $bill_to_acct;
    }


    /**
     * @param string $barcode
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;
    }

    /**
     * @param string the serial number
     */
    public function setBaseUnit($base_serial)
    {
        $dbh = DataStor::getHandle();
        $model_id = $this->model->getId();

        $this->base_unit = null;

        if ($base_serial)
        {
            $sth = $dbh->prepare("
			SELECT la.id
			FROM lease_asset la
			INNER JOIN equipment_models em ON em.id = la.model_id
			INNER JOIN equipment_models em2 ON find_in_array(em2.base_assets, em.id)
			WHERE upper(serial_num) = ?
			AND em2.id = ?");
            $sth->bindValue(1, strtoupper($base_serial), PDO::PARAM_STR);
            $sth->bindValue(2, $model_id, PDO::PARAM_INT);
            $sth->execute();
            $this->base_unit = $sth->fetchColumn();
        }

        return $this->base_unit;
    }

    /**
     * @param double
     */
    public function setFreight($freight)
    {
        $this->freight = $freight;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @param string $last_cert_date
     */
    public function setLastCertDate($last_cert_date)
    {
        $this->last_cert_date = $last_cert_date;
    }


    /**
     * @param string $mfg_date
     */
    public function setMfgDate($mfg_date)
    {
        $this->mfg_date = $mfg_date;
    }


    /**
     * @param string $owning_acct
     * @param boolean
     */
    public function setOwningAcct($owning_acct, $update_db = false)
    {
        $this->owning_acct = $owning_acct;

        if ($update_db)
        {
            $dbh = DataStor::getHandle();
            $sth_update = $dbh->prepare('
				UPDATE lease_asset SET owning_acct = ?
				WHERE id = ?');
            $sth_update->bindValue(1, $this->owning_acct, PDO::PARAM_STR);
            $sth_update->bindValue(2, $this->id, PDO::PARAM_INT);
            $sth_update->execute();
        }
    }

    /**
     * This may not be neccessary
     * Updates ownership fields
     *
     * @param string
     * @param date (string)
     * @param float
     * @param float
     */
    public function SetOwnership($owning_acct, $acq_date, $acq_price, $freight)
    {
        $dbh = DataStor::getHandle();

        $this->owning_acct = $owning_acct;
        $this->acq_date = $acq_date;
        $this->acq_price = (float) $acq_price;
        $this->freight = (float) $freight;

        $sth = $dbh->prepare("UPDATE lease_asset SET
			owning_acct = ?,
			bill_to_acct = ?,
			acq_date = ?,
			acq_price = ?,
			freight = ?
		WHERE id = ?");
        $sth->bindValue(1, $this->owning_acct, PDO::PARAM_STR);
        $sth->bindValue(2, $this->owning_acct, PDO::PARAM_STR);
        $sth->bindValue(3, $this->acq_date, PDO::PARAM_STR);
        $sth->bindValue(4, $this->acq_price, PDO::PARAM_STR);
        $sth->bindValue(5, $this->freight, PDO::PARAM_STR);
        $sth->bindValue(6, $this->id, PDO::PARAM_INT);
        $sth->execute();

    }

    /**
     * @param string $svc_date
     */
    public function setSvcDate($svc_date)
    {
        $this->svc_date = $svc_date;
    }

    /**
     * @param string $firmware_version
     */
    public function setFWVersion($firmware_version)
    {
        $this->firmware_version = $firmware_version;
    }

    /**
     * @param string $software_version
     */
    public function setSWVersion($software_version)
    {
        $this->software_version = $software_version;
    }

    /**
     * @param string $mac_address
     */
    public function setMACAddress($mac_address)
    {
        $this->mac_address = $mac_address;
    }

    /**
     * Returns the data in this object as an array suitable for filling in
     * a form.
     *
     * @return array
     */
    public function toFormArray()
    {
        $last_transaction = $this->getLastTransaction();

        $facility_id = $facility_name = '';
        $facility = $last_transaction->getFacility();
        if ($facility)
        {
            $facility_id = $facility->getId();
            $facility_name = $facility->getName();
        }

        return array(
            'model' => $this->model->getId(),
            'serial' => $this->serial,
            'barcode' => $this->barcode,
            'facility_id' => $facility_id,
            'facility_name' => $facility_name,
            'status' => $last_transaction->getStatus(),
            'substatus' => $last_transaction->getSubStatus(),
            'mfg_date' => $this->mfg_date,
            'svc_date' => $this->svc_date,
            'last_cert_date' => $this->last_cert_date,
            'manufacturer' => $this->manufacturer,
            'owning_acct' => $this->owning_acct,
            'bill_to_acct' => $this->bill_to_acct,
            'firmware_version' => $this->firmware_version,
            'software_version' => $this->software_version,
            'mac_address' => $this->mac_address,
            'user' => $last_transaction->getUser()
        );
    }


    /**
     * Finds whether a certain model/serial exists as a machine.
     *
     * @param integer $model_id
     * @param string $serial
     * @param integer $id
     * @return integer|boolean
     */
    public static function exists($model_id, $serial, $id = null)
    {
        global $user;
        $dbh = DataStor::getHandle();

        if (strtolower(gettype($serial)) != "array")
            list($processedSerials, $orderIDs) = self::processSerial($serial);
        else
            $processedSerials = $serial;

        $sql = "SELECT id FROM lease_asset WHERE model_id = {$model_id} AND upper(serial_num) IN ( '" . implode("', '", $processedSerials) . "' )";

        if (!is_null($id))
            $sql .= " AND id != $id";

        try
        {
            $sth = $dbh->query($sql);
            if ($sth->rowCount() > 0)
            {
                return (int) ($sth->fetchColumn());
            }
        }
        catch (PDOException $pdo_exc)
        {
            ErrorHandler::showError('A database error has occurred while trying to see if the asset exists.', $pdo_exc->getMessage() . "\n" . $pdo_exc->getTraceAsString(), ErrorHandler::$BOTH, $user);
            exit;
        }

        return false;
    }


    /**
     * Takes in the serial string and generates all possible outcomes for serial numbers
     *
     * @param string $str assumed to be one or many serial numbers
     */
    public static function processSerial($str)
    {
        $processedSerials = $orderIDs = array();
        $str = str_replace("\n", ",", $str);
        $strExplode = explode(",", $str);

        if ($strExplode)
        {
            foreach ($strExplode as $serial)
            {
                $serial = strtoupper(trim($serial));
                $stripedSerial = trim($serial);

                $processedSerials[] = $serial;

                if (is_numeric(trim($serial)))
                    $orderIDs[] = $serial;

                if (strlen($stripedSerial) != strlen($serial))
                {
                    $processedSerials[] = $stripedSerial;

                    if (is_numeric($stripedSerial))
                        $orderIDs[] = $stripedSerial;
                }
            }
        }

        return array($processedSerials, $orderIDs);
    }


    /**
     * Queries for a lease asset that matches any of the given parameters.
     *
     * @param integer $model_id
     * @param string $serial
     * @param integer $facility_id
     * @param string $status
     * @param integer $substatus
     * @param string $barcode
     * @return array an array of LeaseAssets
     */
    public static function search($model_id = null, $serial = null,
        $facility_id = null, $status = null, $substatus = null, $barcode = null,
        $firmware_version = null, $software_version = null, $mac_address = null)
    {
        $dbh = DataStor::getHandle();

        $sql = LeaseAsset::searchSQL($model_id, $serial,
            $facility_id, $status, $substatus, $barcode,
            $firmware_version, $software_version, $mac_address);
        $sth = $dbh->query($sql);

        $assets = array();

        while ($a = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $asset = new LeaseAsset($a['id'], $a['model_id'], $a['serial_num'], $a['mfg_date'],
                $a['svc_date'], $a['last_cert_date'], $a['manufacturer'], $a['owning_acct'],
                $a['bill_to_acct'], $a['barcode'], $a['software_version'],
                null/*expiration_date*/ , null/*warranty_name*/ , null/*prod_group*/ ,
                $a['firmware_version'], $a['mac_address'], $a['acq_date'], $a['acq_price'], $a['freight']);

            $asset->last_transaction = new LeaseAssetTransaction($a['id'],
                $a['tstamp'], $a['realtime'], new User($a['user_id']),
                $a['facility_id'], $a['status'], $a['substatus'], $a['comment']);

            $assets[] = $asset;
        }

        return $assets;
    }

    /**
     * Queries for a lease asset that matches any of the given parameters.
     *
     * @param integer $model_id
     * @param string $serial
     * @param integer $facility_id
     * @param string $status
     * @param integer $substatus
     * @param string $barcode
     * @return array an array of LeaseAssets
     */
    public static function searchSQL($model_id = null, $serial = null,
        $facility_id = null, $status = null, $substatus = null, $barcode = null,
        $firmware_version = null, $software_version = null, $mac_address = null)
    {
        $dbh = DataStor::getHandle();

        ## Need this to be an array
        if (!is_array($serial))
            $serial = array($serial);
        $serial_list = implode("','", $serial);

        $serial_condition = ($serial_list) ? "AND UPPER(las.serial_num) in ('$serial_list')" : "";
        $model_condition = ($model_id > 0) ? "AND las.model_id = $model_id" : '';
        $facility_condition = ($facility_id) ? "AND las.facility_id = " . $dbh->quote($facility_id) : "";
        $status_condition = ($status) ? "AND las.status = " . $dbh->quote($status) : "";
        $substatus_condition = ($substatus) ? "AND las.substatus = " . $dbh->quote($substatus) : "";

        $barcode_condition = '';
        if ($barcode)
        {
            // Blank the other conditions before we set the barcode condition.
            $model_condition = $serial_condition = $facility_condition =
                $status_condition = $substatus_condition = '';

            $barcode_condition = " AND las.barcode = " . $dbh->quote(trim($barcode));
        }

        $firmware_condition = trim($firmware_version) ? 'AND las.firmware_version = ' . $dbh->quote(trim($firmware_version)) : '';
        $software_condition = trim($software_version) ? 'AND las.software_version = ' . $dbh->quote(trim($software_version)) : '';
        $mac_condition = trim($mac_address) ? 'AND las.mac_address = ' . $dbh->quote(trim($mac_address)) : '';

        $sql = "SELECT
			las.id, las.model_id, las.serial_num, las.mfg_date, las.svc_date,
			las.last_cert_date, las.manufacturer, las.owning_acct, las.bill_to_acct,
			las.barcode, las.software_version, las.firmware_version, las.mac_address,
			las.acq_date, las.acq_price, las.freight,
			lat.tstamp, lat.realtime, lat.user_id, lat.facility_id,
			lat.status, lat.substatus, lat.comment,
			m.model, m.description as model_name, m.type_id,
			f.accounting_id as cust_id,
			count(*) OVER() as result_count
		FROM lease_asset_status las
		INNER JOIN equipment_models m ON las.model_id = m.id
		INNER JOIN (
			SELECT lat.lease_asset_id,
				EXTRACT(EPOCH FROM lat.tstamp) AS tstamp,
				lat.tstamp as realtime,	lat.user_id, lat.facility_id,
				lat.status, lat.substatus, lat.comment,
				rank() OVER (partition by lat.lease_asset_id ORDER BY lat.tstamp DESC) as rank
			FROM lease_asset_transaction lat
		) lat ON las.id = lat.lease_asset_id AND lat.rank = 1
		LEFT JOIN facilities f ON lat.facility_id = f.id
		WHERE TRUE
		$model_condition
		$serial_condition
		$facility_condition
		$status_condition
		$substatus_condition
		$barcode_condition
		$firmware_condition
		$software_condition
		$mac_condition";

        return $sql;
    }


    /**
     * Strips the leading zeros from a string.
     *
     * @param string $str assumed to be a serial number
     */
    public static function stripLeadingZeros($str)
    {
        return self::isInt($str) ? ltrim($str, '0') : $str;
    }

    public static function isInt($int)
    {
        // First check if it's a numeric value as either a string or number
        if (is_numeric($int) === TRUE)
        {
            // It's a number, but it has to be an integer
            if ((int) $int == $int)
                return TRUE;
            // It's a number, but not an integer, so we fail
            else
                return FALSE;
            // Not a number
        }
        else
            return FALSE;
    }


    /**
     * Queries DB for the serial number which matches the barcode
     *
     * @param $barcode string
     *
     * @return $serial string
     */
    public static function BarcodeToSerial($barcode)
    {
        $dbh = DataStor::getHandle();

        $serial = "";
        $sql = "SELECT serial_num FROM lease_asset WHERE barcode = ?";
        $all_zero = preg_match('/^0*$/', $barcode);
        $all_nine = preg_match('/^9*$/', $barcode);
        if ($all_zero == 0 && $all_nine == 0)
        {
            $sth = $dbh->prepare($sql);
            $sth->bindValue(1, strtoupper($barcode), PDO::PARAM_STR);
            $sth->execute();
            $serial = $sth->fetchColumn();
        }

        return $serial;
    }

    /**
     * Determine if calibration is up to date
     */
    public function CalibrationUptodate()
    {
        $cert_req = null;
        # Skip calibration date check for accessories and these models
        if ($this->getType() == EquipmentModel::$ACCESSORY)
            $calibration_uptodate = true;
        else
        {
            $dbh = DataStor::getHandle();
            $sql = "SELECT
				CASE
					WHEN e.cert_req = 1 AND a.last_cert_date + coalesce(e.cal_interval, '1 Year')::Interval < now() + '6 Months'::Interval THEN false
					ELSE true
				END
			FROM lease_asset_status a
			INNER JOIN equipment_models e on a.model_id = e.id
			AND a.id = ?";
            $sth = $dbh->prepare($sql);
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();
            $calibration_uptodate = (bool) $sth->fetchColumn();
        }

        return $calibration_uptodate;
    }
}

?>