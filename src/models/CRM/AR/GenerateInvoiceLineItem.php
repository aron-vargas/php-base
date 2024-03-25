<?php
/**
 * @package Freedom
 */
/**
 * This class represents a line item for an invoice generated by 
 *
 * @author Aron Vargas
 * @package Freedom
 */
class GenerateInvoiceLineItem extends InvoiceLineItem
{
	/**
	 * @var int
	 */
	protected $invoice_id = null;


	/**
	 * Constructor
	 *
	 * This can be instantiated with or without an invoice ID and item code.
	 * We allow instantiation without an invoice ID or item code so that
	 * the createInvoiceLineItemArray() method can create an array of
	 * InvoiceLineItems more efficiently.
	 *
	 * @param int $invoice_id
	 * @param string $item_code
	 */
	public function __construct($invoice_id = null, $item_code = null)
	{
		if ($invoice_id && $item_code)
		{
			$this->invoice_id = $invoice_id;
			$this->item_code = $item_code;
			$this->load();
		}
	}

	/**
	 * Returns the contents of this object as a readable string
	 *
	 * @return string
	 */
	public function __toString()
	{
		$ret = sprintf('%8d | %15.15s | %25.25s | %3d | %010.2f | %3s',
			$this->invoice_id, $this->item_code, $this->item_desc,
			$this->quantity, $this->unit_price, $this->uom);

		return $ret;
	}

	/**
	 * Returns the id of the invoice this line item belongs to
	 *
	 * @return int
	 */
	public function getInvoiceId()
	{
		return $this->invoice_id;
	}

	/**
	 * Loads the database record into this object
	 *
	 * @throws PDOException, Exception
	 */
	protected function load()
	{
		$dbh = DataStor::getHandle();
		$sth = $dbh->prepare('
			SELECT description,
			       qty,
			       unit_price,
			       uom
			FROM invoice_generated_line_items
			WHERE invoice_id = ?
			  AND item_id = ?');
		$sth->bindValue(1, $this->invoice_id, PDO::PARAM_INT);
		$sth->bindValue(2, $this->item_code, PDO::PARAM_STR);
		$sth->execute();
		if ($sth->rowCount() > 0)
		{
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$this->item_desc = $row['description'];
			$this->quantity = $row['qty'];
			$this->unit_price = $row['unit_price'];
			$this->uom = $row['uom'];
		}
		else
		{
			throw new Exception('no invoice line item with invoice_id ' . $this->invoice_id . ' and item code ' . $this->item_code);
		}
	}

	/**
	 * Updates the database record with the contents of this object
	 *
	 * @throws PDOException, Exception
	 */
	public function save()
	{
		$dbh = DataStor::getHandle();
		$sth = $dbh->prepare('
			UPDATE invoice_generated_line_items
			SET description = ?,
			    qty = ?,
			    unit_price = ?,
			    uom = ?
			WHERE invoice_id = ?
			  AND item_id = ?');
		$sth->bindValue(1, $this->item_desc, PDO::PARAM_STR);
		$sth->bindValue(2, $this->quantity, PDO::PARAM_INT);
		$sth->bindValue(3, $this->unit_price);
		$sth->bindValue(4, $this->uom, PDO::PARAM_STR);
		$sth->bindValue(5, $this->invoice_id, PDO::PARAM_INT);
		$sth->bindValue(6, $this->item_code, PDO::PARAM_STR);
		if (!$sth->execute())
		{
			$err_info = $sth->errorInfo();
			throw new Exception('could not update invoice line item record: ' . $err_info[2]);
		}
	}

	/**
	 * Creates a new InvoiceLineItem
	 *
	 * @param int $invoice_id
	 * @param string $item_code
	 * @param string $item_desc
	 * @param int $quantity
	 * @param float $unit_price
	 * @param string $uom
	 * @throws PDOException, Exception
	 */
	public static function createInvoiceLineItem(
		$invoice_id, $item_code, $item_desc, $quantity, $unit_price, $uom)
	{
		$dbh = DataStor::getHandle();

		$sth = $dbh->prepare('
			INSERT INTO invoice_generated_line_items (
			  invoice_id,item_id,description,qty,unit_price,uom)
			VALUES (?,?,?,?,?,?)');
		$sth->bindValue(1, $invoice_id, PDO::PARAM_INT);
		$sth->bindValue(2, $item_code, PDO::PARAM_STR);
		$sth->bindValue(3, $item_desc, PDO::PARAM_STR);
		$sth->bindValue(4, $quantity, PDO::PARAM_INT);
		$sth->bindValue(5, $unit_price);
		$sth->bindValue(6, $uom, PDO::PARAM_STR);
		if ($sth->execute())
		{
			$new_line_item = new InvoiceLineItem($invoice_id, $item_code);
			return $new_line_item;
		}
		else
		{
			$err_info = $sth->errorInfo();
			throw new Exception('could not insert new invoice line item: ' . $err_info[2]);
		}
	}

	/**
	 * Creates an array of InvoiceLineItems. This is more efficient
	 * than calling createInvoiceLineItem() for each line item, but
	 * the $items array must contain all of the item data.
	 *
	 * @param array $items
	 * @return array an array of InvoiceLineItems
	 */
	public static function createInvoiceLineItemArray($items)
	{
		$ret = array();

		foreach ($items as $item)
		{
			$new_li = new InvoiceLineItem();
			$new_li->invoice_id = $item['invoice_id'];
			$new_li->item_code = $item['item_code'];
			$new_li->item_desc = $item['item_desc'];
			$new_li->quantity = $item['quantity'];
			$new_li->unit_price = $item['unit_price'];
			$new_li->uom = $item['uom'];

			$ret[] = $new_li;
		}

		return $ret;
	}
}

?>