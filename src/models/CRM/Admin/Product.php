<?php

/**
 * @package Freedom
 * @author Aron Vargas
 */

class Product extends BaseClass {
    protected $db_table = "products";
    protected $p_key = 'id';

    public $id;						# integer NOT NULL DEFAULT nextval('products_id_seq'::regclass),
    public $code;					# character varying(32) COLLATE pg_catalog."default" NOT NULL,
    public $name;					# character varying(128) COLLATE pg_catalog."default" NOT NULL,
    public $description;			# text COLLATE pg_catalog."default",
    public $unit;					# character varying(32) COLLATE pg_catalog."default",
    public $pic;					# character varying(128) COLLATE pg_catalog."default",
    public $active;					# boolean NOT NULL DEFAULT true,
    public $cs_item = false;		# boolean DEFAULT false,
    public $svc_item = false;		# boolean DEFAULT false,
    public $max_quantity = 0;		# integer DEFAULT 0,
    public $prod_price_group_key;	# integer,
    public $price_uom;				# character varying(10) COLLATE pg_catalog."default",
    public $display_order;			# integer,

    public $product_detail;			# ProductDetail

    /*
     * Creates a new object.
     *
     * @param integer $id
     */
    public function __construct($id)
    {
        $this->dbh = DataStor::getHandle();

        $this->id = (int) $id;
        $this->load();
        $this->product_detail = new ProductDetail($id);
    }

    private function BindValues(&$sth)
    {
        $prod_price_group_key_t = is_null($this->prod_price_group_key) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $display_order_t = is_null($this->display_order) ? PDO::PARAM_INT : PDO::PARAM_NULL;

        $i = 1;
        $sth->bindValue($i++, $this->code, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->name, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->description, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->unit, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->pic, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->active, PDO::PARAM_STR);
        $sth->bindValue($i++, (bool) $this->cs_item, PDO::PARAM_BOOL);
        $sth->bindValue($i++, (bool) $this->svc_item, PDO::PARAM_BOOL);
        $sth->bindValue($i++, (int) $this->max_quantity, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->prod_price_group_key, $prod_price_group_key_t);
        $sth->bindValue($i++, $this->price_uom, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->display_order, $display_order_t);

        if ($this->id)
            $sth->bindValue($i++, $this->id, PDO::PARAM_INT);

        return $i;
    }

    /**
     * Set class property matching the array key
     *
     * @param array $new
     */
    public function copyFromArray($new = array())
    {
        parent::copyFromArray($new);

        $this->product_detail->copyFromArray($new);
        $this->product_detail->prod_id = $this->id;
    }

    /**
     * Saves the contents of this object to the database. If this object
     * has an id, the record will be UPDATE'd.  Otherwise, it will be
     * INSERT'ed
     *
     * @param array $new
     */
    public function save()
    {
        if ($this->id)
        {
            $sth = $this->dbh->prepare("UPDATE products SET
				code = ?,
				name = ?,
				description = ?,
				unit = ?,
				pic = ?,
				active = ?,
				cs_item = ?,
				svc_item = ?,
				max_quantity = ?,
				prod_price_group_key = ?,
				price_uom = ?,
				display_order = ?
			WHERE id = ?");
            $this->BindValues($sth);
            $sth->execute();
        }
        else
        {
            $sth = $this->dbh->prepare("INSERT INTO products(
				code, name, description, unit, pic, active, cs_item, svc_item, max_quantity, prod_price_group_key, price_uom, display_order)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $this->BindValues($sth);
            $sth->execute();

            # Set the primary key
            $this->id = $this->dbh->lastInsertId("products_id_seq");
            $this->product_detail->prod_id = $this->id;
        }

        # Save the product detail information
        $this->product_detail->save();
    }
}

?>