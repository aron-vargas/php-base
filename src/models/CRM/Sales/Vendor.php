<?php
/**
 * @author Aron Vargas
 * @package Freedom
 */

class Vendor
{
	private $dbh;

	# Database matching attributes
	protected $id;                             #int
	protected $mas_vendor_key;                 #int
	protected $mas_vendor_id;                  #string
	protected $vendor_name;                    #string

	protected $address_name = null;            #string

	protected $address1 = null;                #string
	protected $address2 = null;                #string
	protected $address3 = null;                #string
	protected $address4 = null;                #string
	protected $address5 = null;                #string

	protected $city = null;                    #string
	protected $state = null;                   #string
	protected $zip = null;                     #string

	protected $country = null;                 #string

	protected $phone = null;                   #string
	protected $phone_ext = null;               #string

	protected $active = TRUE;                  #boolean

	public function __construct( $vendor_id = 0 )
	{
		$this->dbh = DataStor::getHandle();
		$this->id = $vendor_id;

		if( !is_null( $this->id ) )
			$this->load();
	}

	/**
	 * Populates this object from the matching record in the
	 * database.
	 */
	public function load()
	{
		$sql = <<<END
SELECT mas_vendor_key, mas_vendor_id, vendor_name,
       address_name,
       address1, address2, address3, address4, address5,
       city, state, zip,
       country,
       phone, phone_ext,
       active
FROM vendor
WHERE id = {$this->id}
END;

		$sth = $this->dbh->query( $sql );

		if( $sth->rowCount() == 1 )
			list( $this->mas_vendor_key, $this->mas_vendor_id, $this->vendor_name,
            	  $this->address_name,
	              $this->address1, $this->address2, $this->address3, $this->address4, $this->address5,
    	          $this->city, $this->state, $this->zip,
        	      $this->country,
            	  $this->phone, $this->phone_ext,
	              $this->active ) = $sth->fetch( PDO::FETCH_NUM );
	}

	/**
	 * Returns the class Property value defined by $var.
	 *
	 * @param $var string
	 *
	 * @return mixed
	 */
	public function getVar( $var = null )
	{
		$ret = null;
		if( @property_exists( $this, $var ) )
			$ret = $this->{$var};
		return $ret;
	}

	/**
	 * Sets the class Property value defined by $var.
	 *
	 * @param $key string
	 * @param $value mixed
	 *
	 * @return nothing
	 */
	public function setVar( $key = null, $value = null )
	{
		if( @property_exists( $this, $key ) )
			$this->{$key} = $value;
	}


	/**
	 * Create string of html options suitable for a select input
	 *
	 * @param $match int
	 *
	 * @return $options string
	 */
	static public function createList( $match )
	{
		$dbh = DataStor::getHandle();

		$match = is_array( $match ) ? $match : array( $match );

		$options = "";
		$sth = $dbh->query( "
SELECT id, vendor_name
FROM vendor
WHERE active
ORDER by vendor_name" );

		while( list( $id, $vendor_name ) = $sth->fetch( PDO::FETCH_NUM ) )
		{
			$sel = in_array( $id, $match ) ? "selected " : "";
			$options .= "<option value='{$id}' {$sel}>{$vendor_name}</option>";
		}

		return $options;
	}
}
?>