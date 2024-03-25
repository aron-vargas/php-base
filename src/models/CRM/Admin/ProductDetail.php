<?php

/**
 * @package Freedom
 * @author Aron Vargas
 */

class ProductDetail extends BaseClass {
    protected $db_table = "product_detail";
    protected $p_key = 'prod_id';

    public $prod_id;			# integer NOT NULL,
    public $track_inventory;	# boolean NOT NULL DEFAULT true,
    public $long_description;	# text COLLATE pg_catalog."default",
    public $specifications;		# text COLLATE pg_catalog."default",
    public $purpose;			# text COLLATE pg_catalog."default",
    public $lot_required;		# boolean NOT NULL DEFAULT false,
    public $special;			# boolean DEFAULT false,
    public $email_subject;		# text COLLATE pg_catalog."default",
    public $email_body;			# text COLLATE pg_catalog."default",

    /*
     * Creates a new object.
     *
     * @param integer $id
     */
    public function __construct($id)
    {
        $this->dbh = DataStor::getHandle();
        $this->prod_id = (int) $id;
        $this->load();
    }

    private function BindValues(&$sth)
    {
        $i = 1;
        $sth->bindValue($i++, (bool) $this->track_inventory, PDO::PARAM_BOOL);
        $sth->bindValue($i++, $this->long_description, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->specifications, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->purpose, PDO::PARAM_STR);
        $sth->bindValue($i++, (bool) $this->lot_required, PDO::PARAM_BOOL);
        $sth->bindValue($i++, (bool) $this->special, PDO::PARAM_BOOL);
        $sth->bindValue($i++, $this->email_subject, PDO::PARAM_STR);
        $sth->bindValue($i++, $this->email_body, PDO::PARAM_STR);

        if ($this->prod_id)
            $sth->bindValue($i++, $this->prod_id, PDO::PARAM_INT);

        return $i;
    }

    /**
     * Saves the contents of this object to the database.
     */
    public function save()
    {
        # Delete and Insert
        $sth = $this->dbh->prepare("DELETE FROM product_detail WHERE prod_id = ?");
        $sth->bindValue(1, $this->prod_id, PDO::PARAM_INT);
        $sth->execute();

        $sth = $this->dbh->prepare("INSERT INTO public.product_detail
		(prod_id, track_inventory, long_description, specifications, purpose, lot_required, special, email_subject, email_body)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $this->BindValues($sth);
        $sth->execute();
    }
}
?>