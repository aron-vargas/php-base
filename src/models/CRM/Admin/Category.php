<?php

/**
 * @package Freedom
 * @author Aron Vargas
 */

class Category extends BaseClass {
    public $id;				# integer PRIMARY KEY
    public $name;			# varchar (128) NOT NULL,
    public $display_order;	# integer NOT NULL,
    public $input;			# NOT NULL DEFAULT true,
    public $output;			# boolean NOT NULL DEFAULT true,
    public $sub_id;			# integer NOT NULL,
    public $active;			# boolean NOT NULL DEFAULT true,
    public $tabs;           # array not part of the table
    public $required;       # array not part of the table
    public $selected;       # array not part of the table
    public $all_tabs;
    public $all_sources;
    public $source;
    public $total_array;

    /**
     * Creates a new Category object.
     *
     * @param integer $id
     * @param string $name
     */
    public function __construct($id, $name = null, $display_order = null, $input = true, $output = true, $sub_id = null, $active = true)
    {
        $this->id = $id;
        $this->name = $name;
        $this->display_order = $display_order;
        $this->input = $input;
        $this->output = $output;
        $this->sub_id = $sub_id;
        $this->active = $active;

        $this->all_sources = array('onsite' => false, 'remote' => false, 'online' => false);
        $this->all_tabs = array(array('short_name' => 'goal', 'name' => 'Customer Goals', 'required' => false, 'selected' => false),
            array('short_name' => 'brief', 'name' => 'Open Narrative', 'required' => false, 'selected' => false),
            array('short_name' => 'status', 'name' => 'Customer Status', 'required' => false, 'selected' => false),
            array('short_name' => 'clindata', 'name' => 'Clinical Data', 'required' => false, 'selected' => false),
            array('short_name' => 'training', 'name' => 'Training', 'required' => false, 'selected' => false),
            array('short_name' => 'safety', 'name' => 'Safety', 'required' => false, 'selected' => false),
            array('short_name' => 'clinrec', 'name' => 'Clin Cons.', 'required' => false, 'selected' => false),
            array('short_name' => 'equipment', 'name' => 'Equipment', 'required' => false, 'selected' => false),
            array('short_name' => 'notes', 'name' => 'Summary &amp; Plan', 'required' => false, 'selected' => false),
            array('short_name' => 'omnivr', 'name' => 'OmniVR', 'required' => false, 'selected' => false),
            array('short_name' => 'contacts', 'name' => 'Contacts', 'required' => false, 'selected' => false));
        $this->total_array = array('source' => $this->all_sources, 'tabs' => $this->all_tabs);

        if (empty ($id) || empty ($name) || empty ($display_order) || empty ($sub_id))
        {
            $this->load();
        }
    }

    /**
     * Set values in the statement
     *
     * @param object
     */
    public function BindValues(&$sth)
    {
        $i = 1;
        $sth->bindValue($i++, htmlentities($this->name, ENT_QUOTES), PDO::PARAM_STR);
        $sth->bindValue($i++, (int) $this->display_order, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->input, PDO::PARAM_BOOL);
        $sth->bindValue($i++, $this->output, PDO::PARAM_BOOL);
        $sth->bindValue($i++, $this->sub_id, PDO::PARAM_INT);
        $sth->bindValue($i++, $this->active, PDO::PARAM_BOOL);

        if ($this->id)
            $sth->bindValue($i++, $this->id, PDO::PARAM_INT);
    }

    /**
     * Insert db record
     */
    public function DBInsert()
    {
        $dbh = DataStor::getHandle();

        # Find next available
        $sth = $dbh->query("SELECT max(id) FROM categories");
        $this->id = (int) $sth->fetchColumn() + 1;

        if (empty ($this->sub_id))
            $this->sub_id = $this->id;

        # Do Insert
        $sql = "INSERT INTO categories (name, display_order, input, output, sub_id, active, id)
		VALUES (?,?,?,?,?,?,?)";
        $sth = $dbh->prepare($sql);
        $this->BindValues($sth);
        $sth->execute();

        $this->updateSourceTab();
    }

    /**
     * Update db record
     */
    public function DBUpdate()
    {
        $dbh = DataStor::getHandle();
        $sql = "UPDATE categories SET
			name = ?, display_order= ?, input = ?, output = ?, sub_id = ?, active = ?
		WHERE id = ?";
        $sth = $dbh->prepare($sql);
        $this->BindValues($sth);
        $sth->execute();

        $this->updateSourceTab();
    }

    /**
     * Delete db record
     */
    public function DBDelete()
    {
        $dbh = DataStor::getHandle();
        $sql = "DELETE FROM categories WHERE id = ?";
        $sth = $dbh->prepare($sql);
        $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Returns the ID of this Category.
     *
     * @return integer
     */
    public function getId()
    {
        return (int) $this->id;
    }


    /**
     * Returns the name of this Category.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Returns the sub id
     *
     * @return integer
     */
    public function getSubId()
    {
        return $this->sub_id;
    }


    /**
     * Returns whether this category is active.
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active();
    }

    /**
     * Returns whether this is a facility visit event
     *
     * @return boolean|null
     */
    public function isFacilityVisit()
    {
        return ($this->sub_id == 1);
    }

    /**
     * Select or load db record
     */
    public function Load()
    {
        if ($this->id)
        {
            $dbh = DataStor::getHandle();
            $sql = "SELECT id, name, input, output, display_order, sub_id, active FROM categories WHERE id = ?";
            $sth = $dbh->prepare($sql);
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $this->CopyFromArray($row);

            $this->LoadTabs();
        }
    }

    /**
     * Perform proper db "save" query
     */
    public function Save($rm = 0)
    {
        if ($this->id)
        {
            if ($rm == 1)
                $this->DBDelete();
            else
                $this->DBUpdate();
        }
        else
        {
            $this->DBInsert();
        }
    }

    public function sourceTabExists()
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare('SELECT count(*) FROM Config WHERE name = ?');
        $sth->bindValue(1, $this->name, PDO::PARAM_STR);
        $sth->execute();
        $rows = $sth->fetch(PDO::FETCH_NUM);
        $count = $rows[0];

        if ($count == 0)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function updateSourceTab()
    {
        if (!empty ($this->source) || !empty ($this->required) || !empty ($this->selected))
        {
            $tabs = json_decode(json_encode($this->total_array['tabs']), FALSE);

            $sorted_tabs = array();
            foreach ($tabs as $tab)
            {
                $tab->selected = $tab->required = false;
                if (!empty ($this->selected))
                {
                    if (array_key_exists($tab->short_name, $this->selected))
                    {
                        $tab->selected = true;
                    }
                }

                if (!empty ($this->required))
                {
                    if (array_key_exists($tab->short_name, $this->required))
                    {
                        $tab->required = true;
                    }
                }

                $key = array_search($tab->short_name, $this->tabs);
                $sorted_tabs[$key] = $tab;
            }

            ksort($sorted_tabs);

            $arr = array();
            foreach ($this->source as $source)
            {
                $arr[$source] = true;
            }

            foreach ($this->all_sources as $source => $val)
            {
                if (!array_key_exists($source, $arr))
                {
                    $arr[$source] = false;
                }
            }

            $this->total_array['source'] = $arr;
            $this->total_array['tabs'] = $sorted_tabs;

            if ($this->sourceTabExists())
            {
                $this->updateConfig();
            }
            else
            {
                $this->insertConfig();
            }
        }
    }

    public function insertConfig()
    {
        $dbh = DataStor::getHandle();

        $dbh->beginTransaction();

        $sth = $dbh->prepare('INSERT INTO Config (name, comment, val)
                                        VALUES (?, ?, ?)');

        $sth->bindValue(1, $this->name, PDO::PARAM_STR);
        $sth->bindValue(2, 'required tabs for ' . $this->name, PDO::PARAM_STR);
        $sth->bindValue(3, json_encode($this->total_array), PDO::PARAM_STR);
        $sth->execute();

        $dbh->commit();
    }

    public function updateConfig()
    {
        $dbh = DataStor::getHandle();

        $dbh->beginTransaction();

        $sth = $dbh->prepare('UPDATE Config
                                    SET    val  = ?
                                    WHERE  name = ?');

        $sth->bindValue(1, json_encode($this->total_array), PDO::PARAM_STR);
        $sth->bindValue(2, $this->name, PDO::PARAM_STR);
        $sth->execute();

        # Commit the transaction.
        $dbh->commit();
    }

    public function LoadTabs()
    {
        if (!$this->sourceTabExists())
        {
            $this->insertConfig();
        }

        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare('SELECT val FROM Config WHERE name = ?');
        $sth->bindValue(1, $this->name, PDO::PARAM_STR);
        $sth->execute();
        $rows = $sth->fetchColumn(0);
        $arr = json_decode($rows);
        if ($arr)
        {
            if (array_key_exists('tabs', $arr))
            {
                $this->total_array['tabs'] = $arr->tabs;
            }
            else
            {
                $this->total_array['tabs'] = $this->all_tabs;
            }

            if (array_key_exists('source', $arr))
            {
                $this->total_array['source'] = $arr->source;
            }
            else
            {
                $this->total_array['source'] = $this->all_sources;
            }
        }
    }

    public function getTabsHtml()
    {
        //echo "<pre>";print_r($this->total_array['tabs']);echo "</pre>";
        $tabs = json_decode(json_encode($this->total_array['tabs']), FALSE);
        //echo "<pre>";print_r($tabs);echo "</pre>";
        $lists = "";
        if ($tabs)
        {
            foreach ($tabs as $tab)
            {
                $disabled = '';
                $checkedRequired = !empty ($tab->required) ? ' checked' : '';
                $checkedSelected = !empty ($tab->selected) ? ' checked' : '';

                if ($checkedSelected == '')
                {
                    $disabled = ' disabled';
                }
                //$options .= "<option value='".$tab->short_name."'$checked>".$tab->name."</option>";
                $lists .= "<li class='ui-state-default'>
                                  <input type='hidden' name='tabs[]' value='{$tab->short_name}'>
							  	  <span class='col-md-1'><input class='form-check-input' type='checkbox' id='selected_{$tab->short_name}' name='selected[{$tab->short_name}]'$checkedSelected onchange='changeRequired(this,\"{$tab->short_name}\")'></span>
                    			  <span class='col-md-7'>{$tab->name}</span>
                                  <span class='col-md-4' data-togle='tooltip' title='{$tab->name}'>
                                      <span style='height: 20px;float: right;'>
                                          <input type='checkbox' data-toggle='toggle' data-size='mini' id='required_{$tab->short_name}' name='required[{$tab->short_name}]' data-on='required' data-off='not required' data-onstyle='success' data-offstyle='danger'$checkedRequired$disabled>
                                      </span>
                                  </span>
							  </li>";
            }
        }
        return $lists;
    }

    public function getTabsArray()
    {
        return $this->total_array['tabs'];
    }

    public function getTrainingSource($select = false, $force_select = null)
    {
        $html = '';

        $sources = $this->total_array['source'];
        if (empty ($sources))
        {
            $this->updateConfig();
        }

        if ($select)
        {
            foreach ($sources as $source => $selected)
            {
                if ($selected)
                {
                    $html = $source;
                }
            }
        }
        else
        {
            foreach ($sources as $source => $selected)
            {
                if (!empty ($force_select))
                {
                    $chk = ($source == $force_select) ? ' selected=\'selected\'' : '';
                }
                else
                {
                    $chk = $selected ? ' selected=\'selected\'' : '';
                }

                $html .= "<option value='{$source}'$chk>{$source}</option>";
            }
        }

        return $html;
    }
}
?>