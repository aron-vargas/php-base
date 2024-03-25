<?php
/**
 * Loner Items
 */
class LoanerItem extends ContractLineItem
{

	/**
	 * Remove item from the database or mark removed
	 *
	 * @param string (datetime)
	 */
	public function Delete($date_removed=null)
	{
		if ($date_removed)
		{
			$sth = $this->dbh->prepare("UPDATE contract_line_item
			SET date_removed = ?
			WHERE line_num = ?");
			$sth->bindValue(1, $this->ParseDate($date_removed), PDO::PARAM_STR);
			$sth->bindValue(2, (int)$this->line_num, PDO::PARAM_INT);
			$sth->execute();
		}
		else
		{
			$sth = $this->dbh->prepare("DELETE FROM contract_line_item
			WHERE line_num = ?");
			$sth->bindValue(1, (int)$this->line_num, PDO::PARAM_INT);
			$sth->execute();
		}
	}

	/**
	 * Load item record values into the class attributes
	 *
	 * @param boolean
	 * @param boolean
	 */
	public function Load($detail=true, $exteded=true)
	{
		# $detail indicates if line item data is to be loaded
		# $exteded indicates if linked data is to be loaded
		$JOINS = "";
		$FIELDS = "";

		if ($detail)
			$FIELDS = "i.*";

		if ($exteded)
		{
			if ($detail) $FIELDS .= ",";

			$FIELDS .=	"c.id_contract_type as contract_type, c.id_facility,
				a.model_id,			a.serial_num,
				a.facility_id,		a.status,
				a.substatus,
				m.model as model_name,
				m.description as model_description,
				p.id as prod_id,		p.name as prod_name,
				ma.name as maintenance_agreement_name,
				ma.term_interval as maintenance_agreement_term,
				w.warranty_name as warranty_option_name,
				w.year_interval as warranty_option_term";

			$JOINS = "INNER JOIN contract c ON i.contract_id = c.id_contract
			INNER JOIN products p ON i.item_code = p.code
			LEFT JOIN equipment_models m ON i.item_code = m.model
			LEFT JOIN lease_asset_status a ON i.asset_id = a.id
			LEFT JOIN maintenance_agreement ma ON i.maintenance_agreement_id = ma.id
			LEFT JOIN warranty_option w ON i.warranty_option_id = w.warranty_id";
		}

		if ($this->line_num && $FIELDS)
		{
			$sth = $this->dbh->prepare("SELECT
				$FIELDS
		 	FROM contract_line_item i
			$JOINS
			WHERE i.line_num = ?");
			$sth->bindValue(1, (int)$this->line_num, PDO::PARAM_INT);
			$sth->execute();
			if ($row = $sth->fetch(PDO::FETCH_ASSOC))
			{
				foreach($row as $key => $val)
					$this->SetVar($key, $val);
			}
		}
	}
}
?>