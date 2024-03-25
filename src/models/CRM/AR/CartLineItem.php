<?php
class CartLineItem extends LineItem
{
	protected $db_table = 'cart_item';		# string

	protected $user_id;			/** @var int Indentifies User */
	protected $user_type;		/** @var string Type of Session User */


	/**
	 * Creates a new instance.
	 *
	 * @param $order_id int
	 */
	public function __construct($user_id=null, $user_type=null, $item_num = null)
	{
		$this->dbh = DataStor::getHandle();

		$this->item_num = $item_num;
		$this->user_id = $user_id;
		$this->user_type = $user_type;

		$this->load();
	}

	/**
	 * Remove database record
	 */
	public function delete()
	{
		if ($this->item_num && $this->user_id && $this->user_type)
		{
			$sth = $this->dbh->prepare("DELETE FROM cart_item
			WHERE user_id = ? AND user_type = ? AND item_num = ?");
			$sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
			$sth->bindValue(2, $this->user_type, PDO::PARAM_STR);
			$sth->bindValue(3, $this->item_num, PDO::PARAM_INT);
			$sth->execute();
		}
	}

	/**
	 * Populate cart items from database record
	 */
	public function load()
	{
		if ($this->item_num && $this->user_id && $this->user_type)
		{
			$sth = $this->dbh->prepare("SELECT
				c.user_id,				c.user_type,
				c.item_num,				c.order_id,
				c.prod_id,				c.quantity,
				c.asset_id,				c.swap_asset_id,
				c.uom,					c.price,
				c.whse_id,				c.upsell,
				p.code,					p.name,
				p.max_quantity,			p.prod_price_group_key,
				p.price_uom,			p.description,
				pr.listprice,			pr.preferredprice,
				pr.sheet2price,			pr.sheet3price,
				pr.sheet4price,
				coalesce(NULLIF(u.conversion_factor,0), 1) as conversion_factor,
				e.id as is_device,
				a.model_id as model,	a.serial_num as serial_number
			FROM cart_item c
			INNER JOIN products p ON c.prod_id = p.id
			LEFT JOIN product_pricing pr ON p.id = pr.id
			LEFT JOIN product_uom u ON p.code = u.code AND c.uom = u.uom
			LEFT JOIN equipment_models e on p.code = e.model
			LEFT JOIN lease_asset a ON c.asset_id = a.id
			WHERE c.user_id = ?
			AND c.user_type = ?
			AND c.item_num = ?");

			$sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
			$sth->bindValue(2, $this->user_type, PDO::PARAM_STR);
			$sth->bindValue(3, $this->item_num, PDO::PARAM_INT);
			$sth->execute();
			if ($row = $sth->fetch(PDO::FETCH_ASSOC))
				$this->copyFromArray($row);
		}
	}

	/**
	 * Insert a new db record for cart items
	 *
	 */
	public function insert()
	{
		if ($this->user_id && $this->user_type && $this->item_num)
		{
			$order_id_type = ($this->order_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
			$price_type = (is_null($this->price)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
			$whse_type = ($this->whse_id) ? PDO::PARAM_STR : PDO::PARAM_NULL;

			$sth = $this->dbh->prepare("INSERT INTO cart_item
			(user_id,user_type,item_num,order_id,prod_id,quantity,
			 asset_id,swap_asset_id,uom,price,whse_id,upsell)
			VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
			$sth->bindValue(1, $this->user_id, PDO::PARAM_INT);
			$sth->bindValue(2, $this->user_type, PDO::PARAM_STR);
			$sth->bindValue(3, $this->item_num, PDO::PARAM_INT);
			$sth->bindValue(4, (int)$this->order_id, $order_id_type);
			$sth->bindValue(5, (int)$this->prod_id, PDO::PARAM_INT);
			$sth->bindValue(6, (int)$this->quantity, PDO::PARAM_INT);
			$sth->bindValue(7, (int)$this->asset_id, PDO::PARAM_INT);
			$sth->bindValue(8, (int)$this->swap_asset_id, PDO::PARAM_INT);
			$sth->bindValue(9, $this->uom, PDO::PARAM_STR);
			$sth->bindValue(10, $this->price, $price_type);
			$sth->bindValue(11, $this->whse_id, $whse_type);
			$sth->bindValue(12, (int)$this->upsell, PDO::PARAM_BOOL);
			$sth->execute();
		}
	}

	/**
	 * Generate an associative array from class
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'user_id' => $this->user_id,
			'user_type' => $this->user_type,
			'item_num' => $this->item_num,
			'order_id' => $this->order_id,
			'prod_id' => $this->prod_id,
			'quantity' => $this->quantity,
			'asset_id' => $this->asset_id,
			'swap_asset_id' => $this->swap_asset_id,
			'uom' => $this->uom,
			'price' => $this->price,
			'whse_id' => $this->whse_id,
			'upsell' => $this->upsell,
			'model' => $this->model,
			'serial_number' => $this->serial_number,
			'item_lot' => ""
		);
	}

	/**
	 * Update database record for cart item
	 */
	public function update()
	{

		if ($this->item_num && $this->user_id && $this->user_type)
		{
			$order_id_type = ($this->order_id) ? PDO::PARAM_INT : PDO::PARAM_NULL;
			$price_type = (is_null($this->price)) ? PDO::PARAM_NULL : PDO::PARAM_STR;
			$whse_type = ($this->whse_id) ? PDO::PARAM_STR : PDO::PARAM_NULL;

			$sth = $this->dbh->prepare("UPDATE cart_item SET
				order_id = ?,
				quantity = ?,
				asset_id = ?,
				swap_asset_id = ?,
				uom = ?,
				whse_id = ?,
				upsell = ?,
				price = ?
			WHERE user_id = ? AND user_type = ? AND item_num = ?");
			$sth->bindValue(1, (int)$this->order_id, $order_id_type);
			$sth->bindValue(2, $this->quantity, PDO::PARAM_INT);
			$sth->bindValue(3, (int)$this->asset_id, PDO::PARAM_INT);
			$sth->bindValue(4, (int)$this->swap_asset_id, PDO::PARAM_INT);
			$sth->bindValue(5, $this->uom, PDO::PARAM_STR);
			$sth->bindValue(6, $this->whse_id, $whse_type);
			$sth->bindValue(7, (int)$this->upsell, PDO::PARAM_BOOL);
			$sth->bindValue(8, $this->price, $price_type);
			$sth->bindValue(9, $this->user_id, PDO::PARAM_INT);
			$sth->bindValue(10, $this->user_type, PDO::PARAM_STR);
			$sth->bindValue(11, $this->item_num, PDO::PARAM_INT);
			$sth->execute();
		}
	}
}
?>