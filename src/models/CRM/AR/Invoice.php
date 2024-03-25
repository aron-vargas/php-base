<?php
/**
 * @package Freedom
 */

/**
 *
 */
require_once ('classes/CustomerEntity.php');

/**
 * Define the TranTypes
 */
define('INV_TRAN_TYPE', 501);
define('CREDIT_TRAN_TYPE', 502);

/**
 * Base class for the various invoice types
 *
 * @author Aron Vargas
 * @package Freedom
 */
class Invoice {
    /**
     * The date the invoice was issued
     *
     * @var DateTime epoch time
     */
    protected $invoice_date = null;

    /**
     * The CustomerEntity ID
     *
     * @var int
     */
    protected $customer_id = null;

    /**
     * The total cost of the line items (may or may not be the total
     * invoice amount)
     *
     * @var float
     */
    protected $total_product_cost = 0;

    /**
     * The line items for this invoice
     *
     * @var array array of InvoiceLineItems
     */
    protected $line_items = array();


    /**
     * Constructor
     *
     * This will always throw an Exception as the Invoice class cannot be
     * instantiated.
     *
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('cannot instantiate the Invoice class');
    }

    /**
     * Returns the CustomerEntity this invoice is for
     *
     * @return CustomerEntity
     * @throws PDOException
     */
    public function getCustomer()
    {
        return new CustomerEntity($this->customer_id);
    }

    /**
     * Returns the ID of the CustomerEntity that this invoice is for
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * Returns the date this invoice was issued
     *
     * @return int epoch time
     */
    public function getInvoiceDate()
    {
        return $this->invoice_date->getTimestamp();
    }

    /**
     * Returns the line items for this invoice
     *
     * @return array
     */
    public function getLineItems()
    {
        return $this->line_items;
    }

    /**
     * Returns the total cost of the line items (may or may not be
     * the total invoice amount)
     *
     * @return float
     */
    public function getTotalProductCost()
    {
        return $this->total_product_cost;
    }
}

?>