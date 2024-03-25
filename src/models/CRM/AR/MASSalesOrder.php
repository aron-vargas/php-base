<?php
/**
 * @package Freedom
 * @author Aron Vargas
 */

class SalesOrder {
    private $mdbh;						#PDO Object
    protected $TranNo = '0';		#string
    protected $CompanyID = '';			#string
    protected $CustID = 'DEFAULT001';				#string
    protected $TranDate = null;			#string
    protected $ReqDate = null;			#string
    protected $PromDate = null; 		#string
    protected $ShipDate = null;			#string
    protected $AddrKey = null;			#int
    protected $UserID = '';				#string
    protected $OrderID = '';			#string
    protected $CustPONo = '';			#string
    protected $SalesSourceID = null;	#string
    protected $ShipCost = null;			#string
    protected $ShipMethod = null;		#string
    protected $ShipCarrier = null;		#string
    protected $ShipMethodKey = null;	#int

    protected $EmptyBins = 0;			# Override to use a pick method of "Empty Bins"
    protected $EmptyRandomBins = 0;		# Override to use random bins first.
    protected $PickOrdQty = 0;			# Out of stock pick option (0 = Pick Qty Availalbe; 1 = Pick Qty Ordered)

    protected $co_is_valid = false;
    private $debug = false;

    protected $items = array();			#array

    static public $DEFAULT_UOM = 'EA';	#string
    static public $EXCLUDE_WHSE = array('SRV999', 'EQUIP');
    static public $NEW_EQUIP_EXT = 'EQUIP';
    static public $USED_EQUIP_EXT = 'REFURB';
    static public $PURCHASE_WHSE = 'RENO';
    static public $ININC_WHSE = 'ZINC';

    static public $CO_ID = '100';
    static public $ININC_CO_ID = 'INI';

    public function __construct($TranNo = null)
    {
        $this->mdbh = DataStor::GetHandle();
        $this->CompanyID = self::$CO_ID;
        $this->UserID = Config::$User;
        $this->TranDate = date('Y-m-d');
        $this->ReqDate = date('Y-m-d');

        if ($TranNo)
            $this->TranNo = $TranNo;
    }

    /**
     * Copy items into staging table
     *
     * @param array
     * @return integer
     */
    public function FillStagingTable($items = null)
    {
        # Add items to staging table
        $ln = 1;

        if (is_null($this->ShipMethodKey))
            $this->ShipMethodKey = $this->GetShipMethKey();

        $sth_ins = $this->mdbh->prepare("INSERT INTO tsoSOLineStage
		(CmntOnly,ItemID,OrderID,SOLineNo,QtyOrd,QtyShipped,UOMKey,UnitPrice,ShipMethod,ShipCost,WhseKey)
		VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        foreach ($items as $item)
        {
            # Default UOM to EA
            if (!isset ($item['CmntOnly']))
                $item['CmntOnly'] = 0;
            if (!isset ($item['QtyOrd']))
                $item['QtyOrd'] = 1;
            if (!isset ($item['QtyShipped']))
                $item['QtyShipped'] = 0;
            if (!isset ($item['UOMKey']))
                $item['UOMKey'] = self::GetUOMKey(self::$DEFAULT_UOM, $this->CompanyID);
            if (!isset ($item['UnitPrice']))
                $item['UnitPrice'] = null;
            if (!isset ($item['WhseKey']))
                $item['WhseKey'] = null;

            # Set the shipping cost on the first item
            $item['ShipCost'] = null;
            if (!is_null($this->ShipCost) && $ln == 1)
                $item['ShipCost'] = $this->ShipCost;

            if (isset ($item['ItemID']))
            {
                $sth_ins->bindValue(1, (int) $item['CmntOnly'], PDO::PARAM_INT);
                $sth_ins->bindValue(2, $item['ItemID'], PDO::PARAM_STR);
                $sth_ins->bindValue(3, $this->OrderID, PDO::PARAM_STR);
                $sth_ins->bindValue(4, $item['LineNo'], PDO::PARAM_INT);
                $sth_ins->bindValue(5, $item['QtyOrd']);
                $sth_ins->bindValue(6, $item['QtyShipped']);
                $sth_ins->bindValue(7, $item['UOMKey'], (is_null($item['UOMKey'])) ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $sth_ins->bindValue(8, $item['UnitPrice'], (is_null($item['UnitPrice']) ? PDO::PARAM_STR : PDO::PARAM_NULL));
                $sth_ins->bindValue(9, (int) $this->ShipMethodKey, PDO::PARAM_INT);
                $sth_ins->bindValue(10, $item['ShipCost'], (is_null($item['ShipCost']) ? PDO::PARAM_NULL : PDO::PARAM_STR));
                $sth_ins->bindValue(11, $item['WhseKey'], (is_null($item['WhseKey']) ? PDO::PARAM_NULL : PDO::PARAM_INT));
                $sth_ins->execute();
                $ln++;

                if ($this->debug)
                {
                    echo "<pre>
INSERT INTO tsoSOLineStage
(CmntOnly,ItemID,OrderID,SOLineNo,QtyOrd,QtyShipped,UOMKey,UnitPrice,ShipMethod,ShipCost,WhseKey)
VALUES ({$item['CmntOnly']},'{$item['ItemID']}','{$this->OrderID}',{$item['LineNo']},{$item['QtyOrd']},{$item['QtyShipped']},{$item['UOMKey']},{$item['UnitPrice']},{$this->ShipMethodKey},{$item['ShipCost']},{$item['WhseKey']})
</pre>";
                }
            }
        }

        return $ln;
    }

    /**
     * Determine proper CompanyID
     *
     * @param string
     * @return string
     */
    static public function FindCO($CustID)
    {
        if (strlen($CustID) > 6 and preg_match('/^\d+$/', $CustID))
            $CompanyID = self::$ININC_CO_ID;
        else
            $CompanyID = self::$CO_ID;

        return $CompanyID;
    }

    public function save($form)
    {
        $this->copyFromArray($form);

        # Convert Empty string to null
        if (!$this->SalesSourceID)
            $this->SalesSourceID = null;
        if (!$this->CustPONo)
            $this->CustPONo = 'NA';
        if (!$this->AddrKey)
            $this->AddrKey = null;

        $this->ValidateCompany();
        $this->ShipMethodKey = $this->GetShipMethKey();

        # Round Non Null values
        if (!is_null($this->ShipCost))
            $this->ShipCost = round($this->ShipCost, 3);

        if ($this->mdbh)
        {
            # Add the items to the staging table
            $ln = $this->FillStagingTable($this->items);

            # Need at least 1 line item to create sales order
            if ($ln > 1)
            {
                if ($this->debug)
                {
                    echo "<pre>
EXEC spsoCreateSalesOrder '{$this->CompanyID}'," .
                        " '{$this->CustID}', '{$this->TranDate}'," .
                        " '{$this->ReqDate}', '{$this->PromDate}'," .
                        " '{$this->ShipDate}', {$this->AddrKey}, " .
                        "{$this->UserID}, {$this->OrderID}," .
                        " '{$this->CustPONo}', '{$this->SalesSourceID}'," .
                        " '{$this->ShipCost}'
</pre>";
                }

                # Call stored procedure
                $sth_sp = $this->mdbh->prepare("EXEC spsoCreateSalesOrder ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?");
                $sth_sp->bindValue(1, $this->CompanyID, PDO::PARAM_STR);
                $sth_sp->bindValue(2, $this->CustID, PDO::PARAM_STR);
                $sth_sp->bindValue(3, $this->TranDate, PDO::PARAM_STR);
                $sth_sp->bindValue(4, $this->ReqDate, PDO::PARAM_STR);
                $sth_sp->bindValue(5, $this->PromDate, is_null($this->PromDate) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $sth_sp->bindValue(6, $this->ShipDate, is_null($this->ShipDate) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $sth_sp->bindValue(7, $this->AddrKey, is_null($this->AddrKey) ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $sth_sp->bindValue(8, $this->UserID, PDO::PARAM_STR);
                $sth_sp->bindValue(9, $this->OrderID, PDO::PARAM_STR);
                $sth_sp->bindValue(10, $this->CustPONo, PDO::PARAM_STR);
                $sth_sp->bindValue(11, $this->SalesSourceID, is_null($this->SalesSourceID) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $sth_sp->bindValue(12, $this->ShipCost, is_null($this->ShipCost) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $sth_sp->execute();
            }
        }
    }

    public function GetTranNo()
    {
        if (!$this->TranNo)
        {
            if ($this->mdbh && $this->OrderID)
            {
                # Retrieve sales order number

                $sth = $this->mdbh->prepare("SELECT Top 1 TranNo FROM tsoSalesOrder WHERE UserFld4 = ? order by TranNo Desc");
                $sth->bindValue(1, $this->OrderID, PDO::PARAM_STR);
                $sth->execute();
                list($this->TranNo) = $sth->fetch(PDO::FETCH_NUM);
            }
        }

        return $this->TranNo;
    }

    /**
     * Remove items from the staging table
     *
     * @param $mdbh PDO object
     */
    public function CleanStagingTable()
    {
        global $this_app_name, $user;

        if ($this->mdbh && $this->OrderID)
        {
            # Add to activity log table
            $sth = $this->mdbh->prepare("INSERT INTO tsoSOActivityLog
			(TSTAMP, ItemID, OrderID, SOLineNo, QtyOrd, QtyShipped, UOMKey, UnitPrice, ShipMethod, InfoFld1, InfoFld2, InfoFld3)
			SELECT CURRENT_TIMESTAMP, ItemID, OrderID, SOLineNo, QtyOrd, QtyShipped, UOMKey, UnitPrice, ShipMethod, ?, ?, ?
			FROM tsoSOLineStage WHERE OrderID = ?");
            $sth->bindValue(1, $this_app_name, PDO::PARAM_STR);
            $sth->bindValue(2, $user->getUsername(), PDO::PARAM_STR);
            $sth->bindValue(3, $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
            $sth->bindValue(4, $this->OrderID, PDO::PARAM_STR);
            $sth->execute();

            # Remove items from the staging table
            $sth = $this->mdbh->prepare("DELETE FROM tsoSOLineStage WHERE OrderID = ?");
            $sth->bindValue(1, $this->OrderID, PDO::PARAM_STR);
            $sth->execute();
        }
    }

    /**
     * Create a pick ticket
     *
     * @param integer
     */
    public function PickListFromSO($CommitShipment = 0)
    {
        $res = 0;

        if ($this->mdbh && $this->TranNo > 0)
        {
            $tconf = new TConfig();
            $commit_so = (int) $tconf->get('commit_mas_so');
            $Commit = ($commit_so || $CommitShipment) ? 1 : 0;

            if ($this->debug)
            {
                echo "<pre>
EXEC spsoPickListFromSO '{$this->TranNo}'," .
                    " '{$this->UserID}', '{$this->EmptyBins}'," .
                    " '{$this->EmptyRandomBins}', '{$this->PickOrdQty}'," .
                    " '{$Commit}'
</pre>";
            }

            # Call stored procedure
            if ($Commit)
            {
                $sth = $this->mdbh->prepare("EXECUTE [dbo].[spsoPickListFromSO] ?, ?, ?, ?, ?, ?");
                $sth->bindValue(1, $this->TranNo, PDO::PARAM_INT);
                $sth->bindValue(2, $this->UserID, PDO::PARAM_STR);
                $sth->bindValue(3, $this->EmptyBins, PDO::PARAM_INT);
                $sth->bindValue(4, $this->EmptyRandomBins, PDO::PARAM_INT);
                $sth->bindValue(5, $this->PickOrdQty, PDO::PARAM_INT);
                $sth->bindValue(6, (int) $Commit, PDO::PARAM_INT);
            }
            else
            {
                $sth = $this->mdbh->prepare("EXECUTE [dbo].[spsoPickListFromSO] ?, ?, ?, ?, ?");
                $sth->bindValue(1, $this->TranNo, PDO::PARAM_INT);
                $sth->bindValue(2, $this->UserID, PDO::PARAM_STR);
                $sth->bindValue(3, $this->EmptyBins, PDO::PARAM_INT);
                $sth->bindValue(4, $this->EmptyRandomBins, PDO::PARAM_INT);
                $sth->bindValue(5, $this->PickOrdQty, PDO::PARAM_INT);
            }

            $res = $sth->execute();
        }

        return $res;
    }

    /**
     * @return string
     */
    public function GetCO()
    {
        return $this->CompanyID;
    }

    /**
     * Lookup device product code for purchased device
     *
     * @param string
     * @param boolean
     *
     * @return string
     */
    static public function GetEquipmentCode($prod_code, $is_used = false)
    {
        $dbh = DataStor::getHandle();

        # Item code will remain unchanged if nothing is found
        #
        $code = $prod_code;

        # Determine which extention to use (New or Used)
        if ($is_used)
            $ext = self::$USED_EQUIP_EXT;
        else
            $ext = self::$NEW_EQUIP_EXT;

        # Find corresponding code from products matching the orginal item code
        # with desired extension.
        if ($prod_code)
        {
            $sth = $dbh->prepare("SELECT code
			FROM products
			WHERE code ILIKE ?
			AND code ILIKE ?");
            ## AJV: these are not active AND active = true
            $sth->bindValue(1, $prod_code . "%", PDO::PARAM_STR);
            $sth->bindValue(2, "%" . $ext, PDO::PARAM_STR);
            $sth->execute();
            if ($sth->rowCount())
                list($code) = $sth->fetch(PDO::FETCH_NUM);
        }

        return $code;
    }

    /**
     * Determine purchaseing warehouse for the company
     *
     * @return string
     */
    public function GetPurchaseWhseID()
    {
        return ($this->CompanyID == self::$ININC_CO_ID) ? self::$ININC_WHSE : self::$PURCHASE_WHSE;

    }

    /**
     * Find Primary Key for the ID and Company
     *
     * @param $UnitMeasID string
     *
     * @return $UnitMeasKey int
     */
    static public function GetUOMKey($UnitMeasID, $CompanyID = NULL)
    {
        $UnitMeasKey = null;

        $mdbh = DataStor::GetHandle();

        if (!$CompanyID)
            $CompanyID = Config::$Company;

        if ($mdbh && $UnitMeasID)
        {
            # Find Primary Key for the ID and Company
            $sth_uom = $mdbh->prepare("SELECT UnitMeasKey
			FROM tciUnitMeasure
			WHERE UnitMeasID = ? AND CompanyID = ?");
            $sth_uom->bindValue(1, $UnitMeasID, PDO::PARAM_STR);
            $sth_uom->bindValue(2, $CompanyID, PDO::PARAM_STR);
            $sth_uom->execute();
            while ($row = $sth_uom->fetch(PDO::FETCH_ASSOC))
            {
                $UnitMeasKey = $row['UnitMeasKey'];
            }
        }

        ##echo "GetUOMKey($UnitMeasID, $CompanyID) :: UnitMeasKey $UnitMeasKey<br/>\n";

        return $UnitMeasKey;
    }

    /**
     * Find Primary Key for the ID and Company
     *
     * @param string
     * @param string
     *
     * @return integer $WhseKey
     */
    static public function GetWhseKey($WhseID, $CompanyID = NULL)
    {
        $WhseKey = null;

        $mdbh = DataStor::GetHandle();

        if (!$CompanyID)
            $CompanyID = Config::$Company;

        if ($mdbh && $WhseID)
        {
            # Find Primary Key for the ID and Company
            $sth_uom = $mdbh->prepare("SELECT WhseKey
			FROM timWareHouse
			WHERE WhseID = ? AND CompanyID = ?");
            $sth_uom->bindValue(1, $WhseID, PDO::PARAM_STR);
            $sth_uom->bindValue(2, $CompanyID, PDO::PARAM_STR);
            $sth_uom->execute();
            while ($row = $sth_uom->fetch(PDO::FETCH_ASSOC))
            {
                $WhseKey = $row['WhseKey'];
            }
        }

        ##echo "GetWhseKey($WhseID, $CompanyID) :: WhseKey $WhseKey<br/>\n";

        return $WhseKey;
    }

    /**
     * Find Coresponding shipping method key from  sip method
     *
     * @param $ship_method string
     *
     * @return $shipMethKey int
     */
    public function GetShipMethKey()
    {
        $shipMethKey = NULL;
        $sth = NULL;

        $mdbh = DataStor::getHandle();
        if (!$mdbh)
            return NULL;

        if ($this->ShipCarrier == "MDX")
        {
            $sth = $mdbh->query("SELECT ShipMethKey FROM tciShipMethod WHERE CompanyID = '{$this->CompanyID}' AND ShipMethID = 'MDX'");
        }
        else
        {
            switch ($this->ShipMethod)
            {
                case 'Ground':
                    $sth = $mdbh->query("SELECT ShipMethKey FROM tciShipMethod WHERE CompanyID = '{$this->CompanyID}' AND ShipMethID = '{$this->ShipCarrier} Ground'");
                    break;
                case '2 Day':
                    $sth = $mdbh->query("SELECT ShipMethKey FROM tciShipMethod WHERE CompanyID = '{$this->CompanyID}' AND ShipMethID = '{$this->ShipCarrier} 2 Day'");
                    break;
                case '2 Day Early AM':
                    $sth = $mdbh->query("SELECT ShipMethKey FROM tciShipMethod WHERE CompanyID = '{$this->CompanyID}' AND ShipMethID = '{$this->ShipCarrier} 2 Day AM'");
                    break;
                case '3 Day':
                    $sth = $mdbh->query("SELECT ShipMethKey FROM tciShipMethod WHERE CompanyID = '{$this->CompanyID}' AND ShipMethID = '{$this->ShipCarrier} 3 Day'");
                    break;
                case 'Next Day':
                    $sth = $mdbh->query("SELECT ShipMethKey FROM tciShipMethod WHERE CompanyID = '{$this->CompanyID}' AND ShipMethID = '{$this->ShipCarrier} N Day'");
                    break;
                case 'Next Day Early AM':
                    $sth = $mdbh->query("SELECT ShipMethKey FROM tciShipMethod WHERE CompanyID = '{$this->CompanyID}' AND ShipMethID = '{$this->ShipCarrier} N Day AM'");
                    break;
                case 'Freight':
                    $sth = $mdbh->query("SELECT ShipMethKey FROM tciShipMethod WHERE CompanyID = '{$this->CompanyID}' AND ShipMethID = '{$this->ShipCarrier} Freight'");
                    break;
                case 'Third Party':
                    $sth = $mdbh->query("SELECT ShipMethKey FROM tciShipMethod WHERE CompanyID = '{$this->CompanyID}' AND ShipMethID = 'Third Party'");
                    break;
                case 'International':
                    $sth = $mdbh->query("SELECT ShipMethKey FROM tciShipMethod WHERE CompanyID = '{$this->CompanyID}' AND ShipMethID = '{$this->ShipCarrier} INTERNATION'");
                    break;
                default:
                    break;
            }
        }

        if ($sth)
            list($shipMethKey) = $sth->fetch(PDO::FETCH_NUM);

        if ($this->debug)
            echo "GetShipMethKey() :: shipMethKey $shipMethKey<br/>\n";

        return $shipMethKey;
    }
    /**
     * Get Wharehouse options
     *
     * @param $whse_id string
     *
     * @return string
     */
    static public function GetWhseList($whse_id = null, $CompanyID = null)
    {
        $whse_options = "<option value=''></option>";

        if (!$CompanyID)
            $CompanyID = Config::$Company;

        $mdbh = DataStor::getHandle();
        if (!$mdbh)
            return NULL;

        $excl = implode("','", self::$EXCLUDE_WHSE);

        $sth = $mdbh->prepare("SELECT WhseID FROM timWarehouse WHERE CompanyID = ? AND WhseID NOT IN ('$excl')");
        $sth->bindValue(1, $CompanyID, PDO::PARAM_STR);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($row['WhseID'] == $whse_id) ? "selected" : "";
            $whse_options .= "<option value='{$row['WhseID']}' $sel>{$row['WhseID']}</option>\,";
        }

        return $whse_options;
    }

    /**
     * Get Item type
     *
     * @param $whse_id string
     *
     * @return string
     */
    static public function GetItemType($item_id, $CompanyID = null)
    {
        $mdbh = DataStor::getHandle();
        if (!$mdbh)
            return NULL;

        if (!$CompanyID)
            $CompanyID = Config::$Company;

        $sth = $mdbh->prepare("SELECT ItemType FROM timItem WHERE ItemID = ? AND companyID = ?");
        $sth->bindValue(1, $item_id, PDO::PARAM_STR);
        $sth->bindValue(2, $CompanyID, PDO::PARAM_STR);
        $sth->execute();
        list($ItemType) = $sth->fetch(PDO::FETCH_NUM);

        ## echo "GetItemType($item_id,  $CompanyID) :: ItemType $ItemType<br/>\n";

        return $ItemType;
    }

    /**
     * Set class property matching the array key
     *
     * @param array $new
     */
    public function copyFromArray($form)
    {
        $orig_co = ($this->co_is_valid == true) ? $this->CompanyID : null;

        foreach ($form as $key => $value)
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

        ## Dont override
        if ($orig_co)
            $this->CompanyID = $orig_co;
    }

    /**
     * Set Company ID and avoid validation based on Cust ID
     *
     * @param string
     */
    public function SetCompanyID($CompanyID)
    {
        $this->CompanyID = $CompanyID;
        $this->co_is_valid = true;
    }

    /**
     * Set Cust ID
     *
     * @param string
     */
    public function SetCustID($CustID)
    {
        $this->CustID = $CustID;
        $this->co_is_valid = false;
    }

    /**
     * Set Company ID based on Cust ID format
     */
    public function ValidateCompany()
    {
        if ($this->co_is_valid == false)
        {
            $this->CompanyID = self::FindCO($this->CustID);
            $this->co_is_valid = true;
        }
    }
}

?>