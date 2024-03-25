<?php

/***
 * @package Freedom
 * @author Aron Vargas
 */

# Asset Update class definition

class AssetSubStatus extends BaseClass
{
	protected $db_table = 'asset_sub_status';  # string
	protected $p_key = 'asset_sub_status_id';  # string

	protected $id = null;                  # int
	protected $full_name = null;           # string
	protected $variable_name = null;       # string
	protected $active = null;              # boolean

	/**
	 * Create AssetStatus instance
	 *
	 * @param integer
	 * @return object
	 */
	public function __construct( $asset_status_id = null, $asset_sub_status_name = null, $asset_sub_status_variable_name = null, $active = null )
	{
		$this->dbh = DataStor::getHandle();

		if( $asset_status_id )
			$this->id = $asset_status_id;
		else if( $asset_sub_status_name )
			$this->full_name = $asset_sub_status_name;
		else if( $asset_sub_status_variable_name )
			$this->variable_name = $asset_sub_status_variable_name;

		$this->load();
	}

	/**
	 * Load record from Database
	 */
	public function load()
	{
		$where = "";
		if( $this->id )
			$where = "WHERE asset_sub_status_id = {$this->id}";
		else if( $this->full_name  )
			$where = "WHERE full_name ILIKE '{$this->full_name}'";
		else if( $this->variable_name  )
			$where = "WHERE variable_name = '{$this->variable_name}'";

		$sql = <<<END
SELECT asset_sub_status_id,
       full_name,
       variable_name,
       active
FROM asset_sub_status
{$where}
LIMIT 1
END;

		$sth = $this->dbh->query( $sql );
		list( $this->id,
		      $this->full_name,
		      $this->variable_name,
		      $this->active ) = $sth->fetch( PDO::FETCH_NUM );
	}

	/**
	 * Create Restricted Sub-Status js array
	 */
	private function RestrictedSubStatusAry()
	{
		# Create a restricted substatus option js array;
		$i = 0;
		$options = "";
		$sql = <<<END
SELECT asset_status_id, asset_sub_status_id
FROM asset_status_sub_status_join
ORDER BY asset_status_id
END;
		$sth = $this->dbh->query( $sql );
		while( list( $asset_status_id, $asset_sub_status_id ) = $sth->fetch(PDO::FETCH_NUM))
		{
			$options .= "restricted_sub_status_options[$i] = { asset_status_id : $asset_status_id, asset_sub_status_id : $asset_sub_status_id };\n";
			++$i;
		}

		return $options;
	}

	/**
	 * Create Sub-Status js array
	 */
	private function SubStatusAry()
	{
		# Create a substatus option js array;
		$i = 0;
		$options = "";
		$sql = <<<END
SELECT asset_sub_status_id, full_name
FROM asset_sub_status
WHERE active
ORDER BY full_name
END;
		$sth = $this->dbh->query( $sql );
		while( list( $asset_sub_status_id, $full_name ) = $sth->fetch(PDO::FETCH_NUM))
		{
			$options .= "sub_status_options[$i] = { value : $asset_sub_status_id, text : '$full_name' };\n";
			++$i;
		}

		return $options;
	}

	/**
	 * Check to see if the new sub status can be chosen
	 */
	public function ValidForStatus( $for_status )
	{
		# Create a status option js array;
		$sql = <<<END
SELECT 1
FROM asset_status_sub_status_join
WHERE asset_status_id = {$for_status}
AND asset_sub_status_id = {$this->id}
END;

		$sth = $this->dbh->query( $sql );

		return $sth->fetchColumn();
	}
}
?>