<?php
/**
 * @package Freedom
 */


/**
 * Base class for the various invoice line item types
 *
 * @author Aron Vargas
 * @package Freedom
 */
class InvoiceLineItem {
    /**
     * The item code
     *
     * @var string
     */
    protected $item_code = '';

    /**
     * The item description
     *
     * @var string
     */
    protected $item_desc = '';

    /**
     * The quantity
     *
     * @var float
     */
    protected $quantity = 0;

    /**
     * The price per unit
     *
     * @var float
     */
    protected $unit_price = 0;

    /**
     * The unit of measure
     *
     * @var string
     */
    protected $uom = '';


    /**
     * Constructor
     *
     * This will always throw an Exception as the InvoiceLineItem class cannot
     * be instantiated.
     *
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('cannot instantiate the InvoiceLineItem class');
    }

    /**
     * Returns the item code
     *
     * @return string
     */
    public function getItemCode()
    {
        return $this->item_code;
    }

    /**
     * Returns the item description
     *
     * @return string
     */
    public function getItemDescription()
    {
        return $this->item_desc;
    }

    /**
     * Returns the quantity
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Returns the price per unit
     *
     * @return float
     */
    public function getUnitPrice()
    {
        return $this->unit_price;
    }

    /**
     * Returns the unit of measure
     *
     * @return string
     */
    public function getUOM()
    {
        return $this->uom;
    }
}

?>