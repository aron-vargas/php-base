<?php
class LineItem extends BaseClass
{
	protected $db_table = 'order_item';		# string

	protected $item_num = 1;			/** @var int Line Item number */
	protected $order_id; 				/** @var int Indentifies Order */
	protected $prod_id;					/** @var int Indentifies Product */
	protected $quantity = 1;			/** @var int Quantity Ordered */
	protected $shipped;					/** @var int Quantity Shipped */
	protected $asset_id;				/** @var int Indentifies Outgoing Asset */
	protected $swap_asset_id;			/** @var int Indentifies Returning Asset */
	protected $uom = 'EA';				/** @var string Unit of Measure */
	protected $price;					/** @var int Customers price */
	protected $whse_id;					/** @var int Indentifies Wharehouse */
	protected $upsell;					/** @var int Indenticates CS Upsell */
	protected $code;					/** @var string Product Code Identifier*/
	protected $name;					/** @var string Product Name */
	protected $description;				/** @var string Description at order time */
	protected $item_lot;				/** @var string */
	protected $max_quantity;			/** @var int Limit on order quantity */
	protected $prod_price_group_key;	/** @var int Product Pricing Group */
	protected $price_uom;				/** @var string UOM used to price items */
	protected $listprice = 'NA';		/** @var float Listed price */
	protected $preferredprice = 'NA';	/** @var float Prefered price */
	protected $sheet2price = 'NA';		/** @var float Sheet 2 price */
	protected $sheet3price = 'NA';		/** @var float Sheet 3 price */
	protected $sheet4price = 'NA';		/** @var float Sheet 4 price */
	protected $conversion_factor = 1;	/** @var float factor to convert to price uom */
	protected $is_device = false;		/** @var bool Indicates a device */
	public $price_device = false;		/** @var bool Indicates a device */
	protected $model;					/** @var int asset model */
	protected $serial_number;			/** @var string asset serial number */
	protected $amount = 0;				/** @var float Sale amount */
	protected $list_amount = 0;			/** @var float List amount */
	protected $base_amount = 0;			/** @var float Base amount */
	protected $sale_amount = 0;			/** @var float Discounted amount */

	/**
	 * Creates a new instance.
	 *
	 * @param $order_id int
	 */
	public function __construct($item_num = null, $order_id=null)
	{
		$this->dbh = DataStor::getHandle();

		$this->item_num = $item_num;
		$this->order_id = $order_id;

		$this->load();
	}

	/**
	 * Calculate the discounted amount for the product
	 *
	 * @param int $prod_pricing_group_key
	 * @param int $prod_pricing_group_key
	 * @param float $base_price
	 * @param int $qty
	 */
	public function GetDiscount($cust_price_group_key, $prod_pricing_group_key, $base_price, $qty)
	{
		$discount = 0;

		if ($cust_price_group_key && $prod_pricing_group_key && $qty > 0)
		{
			$sth = $this->dbh->prepare("SELECT
				price_method, percent_adj, amount_adj
			FROM product_pricing_group
			WHERE cust_price_group_key = ?
			AND prod_price_group_key = ?
			AND date_effective < CURRENT_DATE
			AND date_expiration >= CURRENT_DATE
			AND range_start < ?
			AND range_end >= ?");
			$sth->bindValue(1, $cust_price_group_key, PDO::PARAM_INT);
			$sth->bindValue(2, $prod_pricing_group_key, PDO::PARAM_INT);
			$sth->bindValue(3, (int)$qty, PDO::PARAM_INT);
			$sth->bindValue(4, (int)$qty, PDO::PARAM_INT);
			$sth->execute();

			if (list($price_method, $percent_adj, $amount_adj) = $sth->fetch(PDO::FETCH_NUM))
			{
				# Percentage based
				if ($price_method == 1)
					$discount = $base_price * $percent_adj;
				# Amount based
				else if ($price_method == 2)
					$discount = $amount_adj;
				# Percentage based markup
				else if ($price_method == 3)
					$discount = $base_price * $percent_adj * -1;
				# Amount based markup
				else if ($price_method == 4)
					$discount = $amount_adj * -1;
				# Static rate
				else if ($price_method == 5)
					$discount = $base_price - $amount_adj;
			}

		}

		return $discount;
	}

	/**
	 * Convert Quantity from selected uom to pricing uom
	 *
	 * @return integer
	 */
	public function GetPriceQuantity()
	{
		$price_qty = $this->quantity;

		# Convert Quantity from selected uom to pricing uom
		if ($this->uom != $this->price_uom)
		{
			$price_qty = $this->quantity * LineItem::UOMConversion($this->prod_id, $this->uom);
		}

		return $price_qty;
	}

	/**
	 * Remove order item database record
	 */
	public function delete()
	{
		if ($this->item_num && $this->order_id)
		{
			$sth = $this->dbh->prepare("DELETE FROM order_item
			WHERE order_id = ? AND item_num = ?");
			$sth->bindValue(1, $this->order_id, PDO::PARAM_INT);
			$sth->bindValue(2, $this->item_num, PDO::PARAM_INT);
			$sth->execute();
		}
	}

	/**
	 * Populate order items from database record
	 */
	public function load()
	{
		if ($this->item_num && $this->order_id)
		{
			$sth = $this->dbh->prepare("SELECT
				i.order_id,				i.item_num,
				i.prod_id,				i.quantity,
				i.asset_id,				i.swap_asset_id,
				i.uom,					i.price,
				i.whse_id,				i.upsell,
				i.code,					i.name,
				i.description,			i.item_lot,
				p.max_quantity,			p.prod_price_group_key,
				p.price_uom,
				pr.listprice,			pr.preferredprice,
				pr.sheet2price,			pr.sheet3price,
				pr.sheet4price,
				coalesce(NULLIF(u.conversion_factor,0), 1) as conversion_factor,
				e.id as is_device,
				a.model_id as model,	a.serial_num as serial_number
			FROM order_item i
			INNER JOIN products p ON i.prod_id = p.id
			LEFT JOIN product_pricing pr ON p.id = pr.id
			LEFT JOIN product_uom u ON p.code = u.code AND i.uom = u.uom
			LEFT JOIN equipment_models e on p.code = e.model
			LEFT JOIN lease_asset a ON c.asset_id = a.id
			WHERE i.order_id = ?
			AND i.item_num = ?");

			$sth->bindValue(1, $this->order_id, PDO::PARAM_INT);
			$sth->bindValue(2, $this->item_num, PDO::PARAM_INT);
			$sth->execute();
			if ($row = $sth->fetch(PDO::FETCH_ASSOC))
				$this->copyFromArray($row);
		}
	}

	/**
	 * Load product detail into the item array
	 */
	public function loadProductInfo()
	{
		# Set defaults
		if ($this->prod_id && $this->uom)
		{
			# Perform DB lookup for real values
			$sth = $this->dbh->prepare("SELECT
				p.code,
				p.name,
				p.description,
				p.max_quantity,
				p.prod_price_group_key,
				p.price_uom,
				pr.listprice,
				pr.preferredprice,
				pr.sheet2price,
				pr.sheet3price,
				pr.sheet4price,
				coalesce(NULLIF(u.conversion_factor,0), 1) as conversion_factor,
				e.id as is_device
			FROM products p
			LEFT JOIN product_pricing pr ON p.id = pr.id
			LEFT JOIN product_uom u ON p.code = u.code and u.uom = ?
			LEFT JOIN equipment_models e on p.code = e.model
			WHERE p.id = ?");
			$sth->bindValue(1, $this->uom, PDO::PARAM_STR);
			$sth->bindValue(2, (int)$this->prod_id, PDO::PARAM_INT);
			$sth->execute();
			if ($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				foreach($row as $key => $val)
				{
					if ($key == 'code' && !is_null($this->code))
						continue;
					else if ($key == 'name' && !is_null($this->name))
						continue;
					if ($key == 'description' && !is_null($this->description))
						continue;

					$this->{$key} = $val;
				}
			}
		}
	}

	/**
	 * Insert a new order item db record
	 *
	 */
	public function insert()
	{
		$order_id_type = ($this->order_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
		$price_type = (is_null($this->price)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
		$whse_type = ($this->whse_id) ? PDO::PARAM_STR : PDO::PARAM_NULL;
		$item_lot_t = (empty($this->item_lot)) ? PDO::PARAM_NULL : PDO::PARAM_STR;

		if ($this->order_id && $this->item_num)
		{
			$sth = $this->dbh->prepare("INSERT INTO order_item
			(item_num,order_id,prod_id,code,name,description,
			 quantity,shipped,asset_id,swap_asset_id,
			 uom,price,whse_id,upsell,item_lot)
			VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			$sth->bindValue(1, $this->item_num, PDO::PARAM_INT);
			$sth->bindValue(2, $this->order_id, PDO::PARAM_INT);
			$sth->bindValue(3, (int)$this->prod_id, PDO::PARAM_INT);
			$sth->bindValue(4, trim($this->code), PDO::PARAM_STR);
			$sth->bindValue(5, trim($this->name), PDO::PARAM_STR);
			$sth->bindValue(6, trim($this->description), PDO::PARAM_STR);
			$sth->bindValue(7, (int)$this->quantity, PDO::PARAM_INT);
			$sth->bindValue(8, (int)$this->shipped, PDO::PARAM_INT);
			$sth->bindValue(9, (int)$this->asset_id, PDO::PARAM_INT);
			$sth->bindValue(10, (int)$this->swap_asset_id, PDO::PARAM_INT);
			$sth->bindValue(11, $this->uom, PDO::PARAM_STR);
			$sth->bindValue(12, $this->price, $price_type);
			$sth->bindValue(13, $this->whse_id, $whse_type);
			$sth->bindValue(14, (int)$this->upsell, PDO::PARAM_BOOL);
			$sth->bindValue(15, $this->item_lot, $item_lot_t);
			$sth->execute();
		}
	}


	/**
	 * Add price information to the item
	 *
	 * @param int
	 * @param int
	 * @param int
	 * @param bool
	 */
	public function SetPriceInfo($order_type, $base_price_index, $cust_price_group_key)
	{
		$this->list_amount = 'NA';
		$this->base_amount = 'NA';
		$this->sale_amount = 'NA';

		if ($this->prod_id && ($this->is_device == false || $this->price_device == true))
		{
			$price_qty = $this->GetPriceQuantity();

			$sale_price = $base_price = 'NA';

			# Base price determind by facility base_price_index. Maps to column in pricing sheet
			if (!is_null($base_price_index) && $base_price_index == 0)
				$base_price = $this->listprice;
			else if ($base_price_index == 1)
				$base_price = $this->preferredprice;
			else if ($base_price_index == 2)
				$base_price = $this->sheet2price;
			else if ($base_price_index == 3)
				$base_price = $this->sheet3price;
			else if ($base_price_index == 4)
				$base_price = $this->sheet4price;

			# Price break on qty for the customer and product group
			if ($base_price > 0 && $base_price != 'NA')
			{
				$sale_price = $base_price;

				if ($this->prod_price_group_key)
				{
					$discount = $this->getDiscount(
						$cust_price_group_key,
						$this->prod_price_group_key,
						$base_price,
						$price_qty);

					$sale_price = $base_price - $discount;
				}

				# Should have valid prices, reset amounts
				$this->list_amount = 0.00;
				$this->base_amount = 0.00;
				$this->sale_amount = 0.00;
			}

			# Set item sales amount
			#
			$this->amount = round($this->price, 4);

			# Amount using list price
			if (is_numeric($this->listprice))
			{
				$this->list_amount = $this->listprice * $price_qty;
				$this->amount = $this->list_amount;
			}

			# Amount using base price
			if (is_numeric($base_price))
			{
				$this->base_amount = $base_price * $price_qty;
				$this->amount = $this->base_amount;
			}

			# Amount using sale price
			if (is_numeric($sale_price))
			{
				$this->sale_amount = $sale_price * $price_qty;
				$this->amount = $this->sale_amount;
			}

			# Set the price property
			#
			if ($order_type == Order::$PARTS_ORDER ||
				$order_type == Order::$SWAP_ORDER ||
				$order_type == Order::$RMA_ORDER)
			{
				# Complaint Based orders
				# Price on upsell
				if ($this->upsell)
					$this->price = $this->amount;
				else
					$this->price = 0.0;
			}
			else if ($order_type == Order::$CUSTOMER_ORDER ||
				$order_type == Order::$WEB_ORDER ||
				$order_type == Order::$DME_ORDER ||
				$order_type == Order::$DSSI_ORDER)
			{

				# Chargeable Orders
				$this->price = $this->amount;
			}
			else
			{
				$this->price = 0.0;
			}
		}

		return $this->price;
	}

	/**
	 * Update upsell field
	 *
	 * @param boolean
	 */
	public function SetUpsell($upsell=false)
	{
		if ($this->order_id && $this->item_num)
		{
			$sth = $this->dbh->prepare("UPDATE order_item
			SET
				upsell = ?
			WHERE item_num = ?
			AND  order_id = ?");
			$sth->bindValue(1, (int)$this->upsell, PDO::PARAM_BOOL);
			$sth->bindValue(2, $this->item_num, PDO::PARAM_INT);
			$sth->bindValue(3, $this->order_id, PDO::PARAM_INT);
			$sth->execute();
		}
	}

	/**
	 * Update order item db record
	 *
	 */
	public function update()
	{
		if ($this->order_id && $this->item_num)
		{
			$order_id_type = ($this->order_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
			$price_type = (is_null($this->price)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
			$whse_type = ($this->whse_id) ? PDO::PARAM_STR : PDO::PARAM_NULL;
			$item_lot_t = (empty($this->item_lot)) ? PDO::PARAM_NULL : PDO::PARAM_STR;

			$sth = $this->dbh->prepare("UPDATE order_item
			SET
				code = ?,
				\"name\" = ?,
				description = ?,
				quantity = ?,
				shipped = ?,
				asset_id = ?,
				swap_asset_id = ?,
				uom = ?,
				price = ?,
				whse_id = ?,
				upsell = ?,
				item_lot = ?
			WHERE item_num = ?
			AND order_id = ?");
			$sth->bindValue(1, $this->code, PDO::PARAM_STR);
			$sth->bindValue(2, $this->name, PDO::PARAM_STR);
			$sth->bindValue(3, $this->description, PDO::PARAM_STR);
			$sth->bindValue(4, (int)$this->quantity, PDO::PARAM_INT);
			$sth->bindValue(5, (int)$this->shipped, PDO::PARAM_INT);
			$sth->bindValue(6, (int)$this->asset_id, PDO::PARAM_INT);
			$sth->bindValue(7, (int)$this->swap_asset_id, PDO::PARAM_INT);
			$sth->bindValue(8, $this->uom, PDO::PARAM_STR);
			$sth->bindValue(9, $this->price, $price_type);
			$sth->bindValue(10, $this->whse_id, $whse_type);
			$sth->bindValue(11, (int)$this->upsell, PDO::PARAM_BOOL);
			$sth->bindValue(12, $this->item_num, PDO::PARAM_INT);
			$sth->bindValue(13, $this->item_lot, $item_lot_t);
			$sth->bindValue(14, $this->order_id, PDO::PARAM_INT);
			$sth->execute();
		}
	}

	/**
	 * Get code and conversion factor for the given product and uom
	 *
	 * @param $prod_id int
	 * @param $uom string
	 *
	 * @return int
	 *
	 */
	static public function UOMConversion($prod_id, $uom)
	{
		$factor = 1;

		$dbh = DataStor::getHandle();
		if ($dbh)
		{
			$sth = $dbh->prepare("SELECT coalesce(NULLIF(u.conversion_factor,0), 1)
			FROM product_uom u
			INNER JOIN products p ON u.code = p.code
			WHERE p.id = ? AND u.uom = ?");
			$sth->bindValue(1, (int)$prod_id, PDO::PARAM_INT);
			$sth->bindValue(2, $uom, PDO::PARAM_STR);
			$sth->execute();
			if ($row = $sth->fetch(PDO::FETCH_NUM))
			{	$factor = $row[0]; }
		}

		return $factor;
	}
}
?>