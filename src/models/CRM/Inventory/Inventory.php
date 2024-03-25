<?php
/**
 * @package Freedom
 * @author Aron Vargas
 */

class Inventory {
    private $mdbh;						#PDO Object
    protected $TranNo = '0';			#string
    protected $CompanyID = '';			#string
    protected $CustID = 'DEFAULT001';		#string
    protected $TranDate = null;			#string
    protected $UserID = '';				#string
    protected $OrderID = '';			#string
    protected $ItemID;

    static public $DEFAULT_UOM = 'EA';	#string

    /**
     * Create inventory instance
     */
    public function __construct($ItemID)
    {
        $this->mdbh = DataStor::GetHandle();
        $this->CompanyID = Config::$Company;
        $this->UserID = Config::$User;
        $this->TranDate = date('Y-m-d');
        $this->ReqDate = date('Y-m-d');

        if ($ItemID)
            $this->ItemID = $ItemID;
    }

    /**
     * Add a inventory transaction to move item to a different wharehouse
     *
     * Note: This is a placeholder it is unknown when and if this needs to
     * be automated.
     *
     * @param string
     * @param string
     *
     * @return string
     */
    static public function Move($orig_whse_id, $new_whse_id)
    {
        # Was here
        $whse_id = $orig_whse_id;

        # Now here
        $whse_id = $new_whse_id;

        return $whse_id;
    }
}
?>