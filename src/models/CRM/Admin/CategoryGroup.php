<?php

class CategoryGroup
{
	public $primary_id;		# integer
	public $sel_category;	# object
	public $group;			# array

	public function __construct($category_id)
	{
		$this->primary_id = $category_id;
		$this->group = null;

		if ($category_id)
		{
			$dbh = DataStor::getHandle();
			$sth = $dbh->prepare("SELECT id, name, display_order, input, output, sub_id, active FROM categories WHERE sub_id = ?");
			$sth->bindValue(1, (int)$category_id, PDO::PARAM_INT);
			$sth->execute();
			if ($sth->rowCount() > 0)
			{
				while(list($id, $name, $display_order, $input, $output, $sub_id, $active) = $sth->fetch(PDO::FETCH_NUM))
				{
					if (is_null($this->sel_category))
						$this->sel_category = new Category($id, $name, $display_order, $input, $output, $sub_id, $active);
					$this->group[] = new Category($id, $name, $display_order, $input, $output, $sub_id, $active);
				}
			}
			else
			{
				$sth = $dbh->prepare("SELECT id, name, display_order, input, output, sub_id, active FROM categories WHERE id = ?");
				$sth->bindValue(1, (int)$category_id, PDO::PARAM_INT);
				$sth->execute();
				list($id, $name, $display_order, $input, $output, $sub_id, $active) = $sth->fetch(PDO::FETCH_NUM);
				$this->sel_category = new Category($id, $name, $display_order, $input, $output, $sub_id, $active);
				$this->group[] = new Category($id, $name, $display_order, $input, $output, $sub_id, $active);
			}
		}
	}

	/**
	 * Check if this is a visit category list
	 *
	 * @return boolean
	 */
	public function IsVisit()
	{
		return ($this->sel_category) ? $this->sel_category->isFacilityVisit() : false;
	}

	/**
	 * Convert the group array to list of IDs
	 *
	 * @return string
	 */
	public function ToList()
	{
		$list = "";

		if ($this->group)
		{
			$list = "";
			foreach($this->group as $cat)
				$list .= $cat->getId().",";

			## Remove trailing ','
			$list = substr($list, 0, -1);
		}

		return $list;
	}
}
?>