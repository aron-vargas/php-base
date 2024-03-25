<?php

/***
 * @package Freedom
 * @author Aron Vargas
 */

# Asset Update class definition

class AssetStatus extends BaseClass
{
	protected $db_table = 'asset_status';  # string
	protected $p_key = 'asset_status_id';  # string

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
	public function __construct( $asset_status_id = null, $asset_status_name = null, $asset_status_variable_name = null, $active = null )
	{
		$this->dbh = DataStor::getHandle();

		if( $asset_status_id )
			$this->id = $asset_status_id;
		else if( $asset_status_name )
			$this->full_name = $asset_status_name;
		else if( $asset_status_variable_name )
			$this->variable_name = $asset_status_variable_name;

		$this->load();
	}

	/**
	 * Load record from Database
	 */
	public function load()
	{
		$where = "";
		if( $this->id )
			$where = "WHERE asset_status_id = {$this->id}";
		else if( $this->full_name  )
			$where = "WHERE full_name ILIKE '{$this->full_name}'";
		else if( $this->variable_name  )
			$where = "WHERE variable_name = '{$this->variable_name}'";

		$sql = <<<END
SELECT asset_status_id,
       full_name,
       variable_name,
       active
FROM asset_status
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
	 * Create Restricted Status js array
	 */
	private function RestrictedStatusAry()
	{
		# Create a restricted status option js array;
		$i = 0;
		$options = "";
		$sql = <<<END
SELECT current_asset_status_id, next_asset_status_id
FROM asset_status_flow
ORDER BY current_asset_status_id
END;
		$sth = $this->dbh->query( $sql );
		while( list( $current_asset_status_id, $next_asset_status_id ) = $sth->fetch(PDO::FETCH_NUM))
		{
			$options .= "restricted_status_options[$i] = { current_asset_status_id : $current_asset_status_id, next_asset_status_id : $next_asset_status_id };\n";
			++$i;
		}

		return $options;
	}

	/**
	 * Create Status js array
	 */
	private function StatusAry()
	{
		# Create a status option js array;
		$i = 0;
		$options = "";
		$sql = <<<END
SELECT asset_status_id, full_name
FROM asset_status
WHERE active
ORDER BY full_name
END;
		$sth = $this->dbh->query( $sql );
		while( list( $asset_status_id, $full_name ) = $sth->fetch(PDO::FETCH_NUM))
		{
			$options .= "status_options[$i] = { value : $asset_status_id, text : '$full_name' };\n";
			++$i;
		}

		return $options;
	}

	/**
	 * Check to see if the new status can be chosen
	 */
	public function ValidFromStatus( $from_status )
	{
		# Create a status option js array;
		$sql = <<<END
SELECT 1
FROM asset_status_flow
WHERE current_asset_status_id = {$from_status}
AND next_asset_status_id = {$this->id}
END;
		$sth = $this->dbh->query( $sql );

		return $sth->fetchColumn();
	}
}
?>