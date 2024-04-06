<?php

/**
 * @package Freedom
 */

if (is_file('include/textfuncs.inc'))
    require_once ('include/textfuncs.inc');

/**
 * This class represents a facility which can also be a dealer or CPM
 *
 * @author Aron Vargas
 * @package Freedom
 */
class Facility extends CustomerEntity {
    public $legal_name;					 # string
    private $cpt_id = null;              # int
    private $parent_office = '';         # string
    private $old_custid = '';            # string
    private $parent_custid = '';         # string
    private $visit_frequency = null;     # int
    private $fa = '';                    # string
    private $fa_email = '';              # string
    private $don = '';                   # string
    private $don_email = '';             # string
    private $mds = '';                   # string
    private $mds_email = '';             # string
    private $mm = '';                    # string
    private $mm_email = '';              # string
    private $frd = '';                   # string
    private $frd_email = '';             # string
    private $frd_rm = '';                # string
    private $frd_rm_email = '';          # string
    private $frd_rm_phone = '';          # string
    private $other_fvs_rcpt = '';        # string
    private $med_a_beds = null;          # int
    private $med_b_beds = null;          # int
    private $other_beds = null;          # int
    private $provnum = null;             # string
    private $rehab_provider = null;      # RehabProvider object
    private $rehab_provider_other = '';  # string
    private $titles = null;              # array
    private $id_corporate_group = null;  # int
    private $credit_hold = null;         # boolean
    private $dssi_code = null;           # string
    private $contract_hold = false;      # boolean
    private $operator_type = 1;          # int
    private $fte_count = 0;
    private $associated_phones = array(); # array
    private $pm_cpm_id = null;           # int
    private $exists = false;             # boolean whether this object represents an actual record
    private $service_contract_id;		 # int
    private $install_conversion;		 # boolean
    private $cancel_conversion;			 # boolean

    public $has_dock = false;
    public $requires_liftgate = false;
    public $requires_inside_delivery = false;
    public $update_accounting = false;
    private $rehab_type = null;          # int
    private $visit_frequency_id = null;     # int
    public $contacts;
    public $leases;
    public $purchases;
    public $loaners;
    public $cancellations;
    public $transfers;
    public $contracts;

    static public $OT_SNF = 1;
    static public $OT_ALF = 2;
    static public $OT_HOSP = 3;
    static public $OT_CCRC = 4;
    static public $OT_ILF = 5;
    static public $OT_HH = 6;
    static public $OT_OP = 7;
    static public $OT_VE = 8;
    static public $OT_LTAC = 9;
    static public $OT_SPORT = 10;
    static public $OT_HGR_CLIN = 11;
    static public $OT_COLG = 16;

    /**
     * Creates a new Facility object.
     *
     * @param integer $id
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->load();
        ##$this->LoadPurchases();
    }


    /**
     * Adds a phone number to the list of phone numbers associated with
     * this facility.
     *
     * @param string $phone
     */
    public function addAssociatedPhone($phone)
    {
        if (!in_array($phone, $this->associated_phones))
            $this->associated_phones[] = $phone;
    }


    /**
     * Changes one field in the database and reloads the object.
     *
     * @param string $field
     * @param mixed $value
     */
    public function change($field, $value)
    {
        # Determine type of input
        if (is_int($value))
            $val_type = PDO::PARAM_INT;
        else if (is_bool($value))
            $val_type = PDO::PARAM_BOOL;
        else if (is_null($value))
            $val_type = PDO::PARAM_NULL;
        else if (is_string($value) || is_float($value))
            $val_type = PDO::PARAM_STR;
        else
            $val_type = FALSE;

        if ($this->id)
        {
            $column_sth = $this->dbh->query("SELECT * FROM facilities LIMIT 1");
            if ($row = $column_sth->fetch(PDO::FETCH_ASSOC))
            {
                if (in_array($field, array_keys($row)))
                {
                    $sth = $this->dbh->prepare("UPDATE facilities SET $field = ? WHERE id = ?");
                    $sth->bindValue(1, $value, $val_type);
                    $sth->bindValue(2, $this->id, PDO::PARAM_INT);
                    $sth->execute();
                }
                else
                {
                    $column_sth = $this->dbh->query("SELECT * FROM facilities_details LIMIT 1");
                    if ($row = $column_sth->fetch(PDO::FETCH_ASSOC))
                    {
                        if (in_array($field, array_keys($row)))
                        {
                            $sth = $this->dbh->prepare("UPDATE facilities_details SET $field = ? WHERE facility_id = ?");
                            $sth->bindValue(1, $value);
                            $sth->bindValue(2, $this->id, PDO::PARAM_INT);
                            $sth->execute();
                        }
                    }
                }
            }

            $this->load();
        }
        else
        {
            throw new Exception('Cannot update a non-existant record.');
        }
    }


    /**
     *
     * @param array $new
     */
    public function copyFromArray($new = array())
    {
        if (isset($new['cpt_id']))
            $this->cpt_id = $new['cpt_id'];

        if (isset($new['facility_name']))
            $this->name = trim($new['facility_name']);

        if (isset($new['corporate_parent']))
            $this->corporate_parent = trim($new['corporate_parent']);

        if (isset($new['parent_office']))
            $this->parent_office = trim($new['parent_office']);

        if (isset($new['id_corporate_group']))
            $this->id_corporate_group = $new['id_corporate_group'];

        if (isset($new['accounting_id']))
            $this->cust_id = trim($new['accounting_id']);

        if (isset($new['dssi_code']))
            $this->dssi_code = trim($new['dssi_code']);

        if (isset($new['old_custid']))
            $this->old_custid = trim($new['old_custid']);

        if (isset($new['parent_custid']))
            $this->parent_custid = trim($new['parent_custid']);

        if (isset($new['phone']))
            $this->phone = trim($new['phone']);

        if (isset($new['address']))
            $this->addr1 = trim($new['address']);

        if (isset($new['address2']))
            $this->addr2 = trim($new['address2']);

        if (isset($new['city']))
            $this->city = trim($new['city']);

        if (isset($new['state']))
            $this->state = trim($new['state']);

        if (isset($new['zip']))
            $this->zip = trim($new['zip']);

        if (isset($new['country_id']))
            $this->country_id = $new['country_id'];

        if (isset($new['region_id']))
            $this->region_id = $new['region_id'];

        if (isset($new['comments']))
            $this->comments = trim($new['comments']);

        if (isset($new['fa']))
            $this->fa = trim($new['fa']);

        if (isset($new['fa_email']))
            $this->fa_email = trim($new['fa_email']);

        if (isset($new['don']))
            $this->don = trim($new['don']);

        if (isset($new['don_email']))
            $this->don_email = trim($new['don_email']);

        if (isset($new['mds']))
            $this->mds = trim($new['mds']);

        if (isset($new['mds_email']))
            $this->mds_email = trim($new['mds_email']);

        if (isset($new['mm']))
            $this->mm = trim($new['mm']);

        if (isset($new['mm_email']))
            $this->mm_email = trim($new['mm_email']);

        if (isset($new['frd']))
            $this->frd = trim($new['frd']);

        if (isset($new['frd_email']))
            $this->frd_email = trim($new['frd_email']);

        if (isset($new['frd_rm']))
            $this->frd_rm = trim($new['frd_rm']);

        if (isset($new['frd_rm_email']))
            $this->frd_rm_email = trim($new['frd_rm_email']);

        if (isset($new['frd_rm_phone']))
            $this->frd_rm_phone = trim($new['frd_rm_phone']);

        if (isset($new['other_fvs_rcpt']))
            $this->other_fvs_rcpt = trim($new['other_fvs_rcpt']);

        if (isset($new['med_a_beds']))
            $this->med_a_beds = (trim($new['med_a_beds']) == '') ? null : intval($new['med_a_beds']);

        if (isset($new['med_b_beds']))
            $this->med_b_beds = (trim($new['med_b_beds']) == '') ? null : intval($new['med_b_beds']);

        if (isset($new['other_beds']))
            $this->other_beds = (trim($new['other_beds']) == '') ? null : intval($new['other_beds']);

        if (isset($new['provnum']))
            $this->provnum = (trim($new['provnum']) == '') ? null : trim($new['provnum']);

        if (isset($new['operator_type']))
            $this->operator_type = $new['operator_type'];

        if (isset($new['rehab_provider']))
            $this->rehab_provider = new RehabProvider($new['rehab_provider']);
        else if (isset($new['rehab_provider_name']) && $new['rehab_provider_name'] != "")
            $this->rehab_provider = new RehabProvider(null, $new['rehab_provider_name']);
        else if ($this->rehab_provider)
            ;
        else
            $this->rehab_provider = null;

        if (isset($new['cancelled']))
            $this->cancelled = (boolean) $new['cancelled'];

        if (isset($new['contract_hold']))
            $this->contract_hold = (boolean) $new['contract_hold'];

        if (isset($new['visit_frequency']))
            $this->visit_frequency = ($new['visit_frequency']) ? intval($new['visit_frequency']) : null;

        if (isset($new['visit_frequency_id']))
        {
            $this->visit_frequency_id = ($new['visit_frequency_id']) ? intval($new['visit_frequency_id']) : null;

            # Now use id to set the facility visit_frequency, so they always match
            $visit_count = $this->LookupVisitCount($this->visit_frequency_id);
            $this->visit_frequency = self::VFtoVC($visit_count);
        }

        if (isset($new['rehab_type']))
            $this->rehab_type = trim($new['rehab_type']);

        if (isset($new['fte_count']))
            $this->fte_count = (float) preg_replace('/[^-\d\.]/', '', ($new['fte_count']));

        if (isset($new['pm_cpm_id']))
            $this->pm_cpm_id = ($new['pm_cpm_id']) ? (int) $new['pm_cpm_id'] : null;

        if (isset($new['requires_liftgate']))
            $this->requires_liftgate = (boolean) $new['requires_liftgate'];
        else
            $this->requires_liftgate = false;

        if (isset($new['requires_inside_delivery']))
            $this->requires_inside_delivery = (boolean) $new['requires_inside_delivery'];
        else
            $this->requires_inside_delivery = false;

        if (isset($new['has_dock']))
            $this->has_dock = (boolean) $new['has_dock'];
        else
            $this->has_dock = false;

        if (isset($new['service_contract_id']))
            $this->service_contract_id = ($new['service_contract_id']) ? (int) $new['service_contract_id'] : null;

        if (isset($new['install_conversion']))
            $this->install_conversion = (boolean) $new['install_conversion'];

        if (isset($new['cancel_conversion']))
            $this->cancel_conversion = (boolean) $new['cancel_conversion'];
    }


    /**
     * Deletes this facility from the database.
     */
    public function delete()
    {
        if ($this->id)
        {
            $sth = $this->dbh->prepare(
                'DELETE FROM facilities_details WHERE facility_id = ?');
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();

            $sth = $this->dbh->prepare(
                'DELETE FROM facilities WHERE id = ?');
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();

            $this->exists = false;
        }
    }


    /**
     * Returns whether this object represents a record in the facilities table
     *
     * @return boolean
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * Create a unique identifier for this facility
     */
    private function GenerateCustId()
    {
        $dbh = DataStor::GetHandle();
        $sth = $dbh->query("SELECT MAX(id) FROM facilities");
        $id = 1 + (int) $sth->fetchColumn();

        $this->cust_id = "{$id}HCE";
        $this->accounting_id = "{$id}HCE";
        $this->cancelled = true;
    }

    /**
     * Returns the accounting (cust) id.
     *
     * @return string
     */
    public function getAccountingId()
    {
        return $this->getCustId();
    }


    /**
     * Returns the facility administrator's name.
     *
     * @return string
     */
    public function getAdministrator()
    {
        return $this->fa;
    }


    /**
     * Returns the facility administrator's email address.
     *
     * @return string
     */
    public function getAdministratorEmail()
    {
        return $this->fa_email;
    }


    /**
     * Returns the facility administrator's title.
     *
     * @return string
     */
    public function getAdministratorTitle()
    {
        return $this->titles['fa'];
    }


    /**
     * Returns the extra phone numbers associated with this facility
     *
     * @return array
     */
    public function getAssociatedPhones()
    {
        return $this->associated_phones;
    }


    /**
     *
     * @return boolean
     */
    public function GetCancelConversion()
    {
        return $this->cancel_conversion;
    }

    /**
     * @param boolean $incl_other_fvs_rpcts
     * @return array
     */
    public function getContactList($incl_other_fvs_rpcts = false)
    {
        $names_titles = array();

        if ($this->fa)
            $names_titles[$this->fa] = $this->titles['fa'];

        if ($this->don)
            $names_titles[$this->don] = $this->titles['don'];

        if ($this->mds)
            $names_titles[$this->mds] = $this->titles['mds'];

        if ($this->mm)
            $names_titles[$this->mm] = $this->titles['mm'];

        if ($this->frd)
            $names_titles[$this->frd] = $this->titles['frd'];

        if ($this->frd_rm)
        {
            if ($this->titles['frd rm'])
            {
                $names_titles[$this->frd_rm] = $this->titles['frd rm'];
            }
            else
            {
                $names_titles[$this->frd_rm] = $this->titles['frd_rm'];
            }
        }

        if ($incl_other_fvs_rpcts)
        {
            $other_addrs = array_map('trim', preg_split('/,/', strtr($this->other_fvs_rcpt, ';', ','), -1, PREG_SPLIT_NO_EMPTY));
            foreach ($other_addrs as $email)
            {
                $names_titles[$email] = '';
            }
        }

        return $names_titles;
    }


    /**
     * Returns the parent office.
     *
     * @return string
     */
    public function getCorporateGroup()
    {
        $corporate_group = $this->id_corporate_group;

        if ($corporate_group)
        {
            $sth = $this->dbh->prepare("SELECT group_name FROM corporate_group WHERE id_corporate_group = {$corporate_group}");
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
                $corporate_group = $row['group_name'];
        }
        return $corporate_group;
    }


    /**
     *
     * @return string
     */
    public function getCorporateGroupList()
    {
        $group_options = "<option value=\"\">None</option>\n";


        $sth = $this->dbh->query("SELECT co2.account_id
								   FROM corporate_parent_link cpl
								   INNER JOIN corporate_office co ON co.office_id = cpl.secondary_corporate_parent AND co.account_id = '{$this->getCorporateParent()}'
								   INNER JOIN corporate_office co2 ON co2.office_id = cpl.primary_corporate_parent");

        $corporate_parent = ($sth->rowCount() == 1) ? $sth->fetchColumn() : $this->getCorporateParent();

        $sql = "select id_corporate_group, group_name
				from corporate_group
				where corporate_office_id = (
						SELECT corporate_office_id
						FROM corporate_office
						WHERE account_id = '{$corporate_parent}'
					)
				order by group_name";
        $sth = $this->dbh->prepare($sql);
        $sth->execute();

        while ($row = $sth->fetch(PDO::FETCH_NUM))
        {
            list($group_id, $group_name) = $row;
            $sel = ($group_id == $this->id_corporate_group) ? "selected" : "";
            $group_options .= "<option value=\"{$group_id}\" {$sel}>{$group_name}</option>\n";
        }

        return $group_options;
    }


    /**
     *
     * @return string
     */
    public function getRehabTypeList()
    {
        $rehab_types = "<option value=''>None</option>\n";
        $sth = $this->dbh->query("SELECT type_id, type_name FROM rehab_type");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($row['type_id'] == $this->rehab_type) ? "selected" : "";
            $rehab_types .= "<option value=\"{$row['type_id']}\" {$sel}>{$row['type_name']}</option>\n";
        }

        return $rehab_types;
    }

    /**
     *
     * @return string
     */
    public function getRehabTypeName()
    {
        $type_name = "";

        if ($this->rehab_type)
        {
            $sth = $this->dbh->prepare("SELECT type_name FROM rehab_type WHERE type_id = ?");
            $sth->bindValue(1, (int) $this->rehab_type, PDO::PARAM_INT);
            $sth->execute();
            $type_name = $sth->fetchColumn();
        }

        return $type_name;
    }

    /**
     * Returns the service contract id.
     *
     * @return integer
     */
    public function GetServiceContractId()
    {
        return $this->service_contract_id;
    }

    /**
     * Returns the country id.
     *
     * @return Country Id
     */
    public function getCountryId()
    {
        return $this->country_id;
    }


    /**
     * Returns the {@link User CPM} that is responsible for this facility.
     *
     * @return User|null
     */
    public function getCPT()
    {
        return new User($this->cpt_id);
    }

    /**
     * Returns the cpt_id for this facility.
     *
     * @return User|null
     */
    public function getCPTId()
    {
        return $this->cpt_id;
    }

    /**
     * Returns the name of the director of nursing.
     *
     * @return string
     */
    public function getDirectorOfNursing()
    {
        return $this->don;
    }


    /**
     * Returns the email address of the director of nursing.
     *
     * @return string
     */
    public function getDirectorOfNursingEmail()
    {
        return $this->don_email;
    }


    /**
     * Returns the title of the director of nursing.
     *
     * @return string
     */
    public function getDirectorOfNursingTitle()
    {
        return $this->titles['don'];
    }


    /**
     *
     * @return string
     */
    public function getDSSICode()
    {
        return $this->dssi_code;
    }

    public function getFTECount()
    {
        return $this->fte_count;
    }


    /**
     * @param array $default_names names
     * @return array email addresses
     */
    public function getEmailAddresses($default_names = array())
    {
        $email_addrs = array();

        # Create a mapping between names and email addresses.
        #
        $names_addrs = array();
        if ($this->fa)
            $names_addrs[$this->fa] = $this->fa_email;

        if ($this->don)
            $names_addrs[$this->don] = $this->don_email;

        if ($this->mds)
            $names_addrs[$this->mds] = $this->mds_email;

        if ($this->mm)
            $names_addrs[$this->mm] = $this->mm_email;

        if ($this->frd)
            $names_addrs[$this->frd] = $this->frd_email;

        if ($this->frd_rm)
            $names_addrs[$this->frd_rm] = $this->frd_rm_email;


        # Search the names and keep a list of email addresses.
        #
        foreach ($names_addrs as $name => $email)
        {
            if ($email)
            {
                if (array_search($name, $default_names) !== false)
                {
                    $email_addrs[] = $email;
                }
            }
        }


        # Check the other FVS recipients for addresses.
        #
        $other_addrs = array_map('trim', preg_split('/,/', strtr($this->other_fvs_rcpt, ';', ','), -1, PREG_SPLIT_NO_EMPTY));
        foreach ($other_addrs as $email)
        {
            if (array_search($email, $default_names) !== false)
            {
                $email_addrs[] = $email;
            }
        }

        return $email_addrs;
    }


    /**
     *
     * @return array an array of LeaseAsset objects
     */
    public function getEquipment()
    {
        return LeaseAsset::search(null, null, $this->id);
    }

    /**
     *
     * @return boolean
     */
    public function GetInstallConversion()
    {
        return $this->install_conversion;
    }

    /**
     * Returns the name of the marketing manager.
     *
     * @return string
     */
    public function getMarketingManager()
    {
        return $this->mm;
    }


    /**
     * Returns the email of the marketing manager.
     *
     * @return string
     */
    public function getMarketingManagerEmail()
    {
        return $this->mm_email;
    }


    /**
     * Returns the title of the marketing manager.
     *
     * @return string
     */
    public function getMarketingManagerTitle()
    {
        return $this->titles['mm'];
    }


    /**
     * Returns the name of the MDS Coordinator.
     *
     * @return string
     */
    public function getMDSCoordinator()
    {
        return $this->mds;
    }


    /**
     * Returns the email of the MDS Coordinator.
     *
     * @return string
     */
    public function getMDSCoordinatorEmail()
    {
        return $this->mds_email;
    }


    /**
     * Returns the title of the MDS Coordinator.
     *
     * @return string
     */
    public function getMDSCoordinatorTitle()
    {
        return $this->titles['mds'];
    }


    /**
     * Returns the number of Medicare A beds.
     *
     * @return integer|null
     */
    public function getMedicareABeds()
    {
        return $this->med_a_beds;
    }


    /**
     * Returns the number of Medicare B beds.
     *
     * @return integer|null
     */
    public function getMedicareBBeds()
    {
        return $this->med_b_beds;
    }

    /**
     * Returns the operator type
     *
     * @return integer|null
     */
    public function GetOperatorType()
    {
        return $this->operator_type;
    }


    /**
     * Returns the old custid.
     *
     * @return string
     */
    public function getOldCustId()
    {
        return $this->old_custid;
    }


    /**
     * Returns the number of other beds.
     *
     * @return integer|null
     */
    public function getOtherBeds()
    {
        return $this->other_beds;
    }


    /**
     * Returns the other FVS recipients.
     *
     * @return string
     */
    public function getOtherFVSRecipients()
    {
        return $this->other_fvs_rcpt;
    }


    /**
     * Returns the parent custid.
     *
     * @return string
     */
    public function getParentCustId()
    {
        return $this->parent_custid;
    }


    /**
     * Returns the parent office.
     *
     * @return string
     */
    public function getParentOffice()
    {
        $parent_office = $this->parent_office;

        if ($parent_office)
        {
            $sth = $this->dbh->prepare("SELECT office_name FROM corporate_office WHERE account_id = '{$parent_office}'");
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $parent_office = $row['office_name'];
            }
        }
        return $parent_office;
    }


    /**
     * Returns the parent office id.
     *
     * @return string
     */
    public function getParentOfficeId()
    {
        return $this->parent_office;
    }


    /**
     * @return string
     */
    public function getParentOfficeList()
    {
        $office_options = "";

        $sql = "SELECT account_id, office_name
				 FROM corporate_office
				 WHERE corporate_office_id =
					(
						SELECT corporate_office_id
						FROM corporate_office
						WHERE account_id = '{$this->getCorporateParent()}'
					)
				AND status = 1
				ORDER BY parent_id, office_name";
        $sth = $this->dbh->prepare($sql);
        $sth->execute();

        while ($row = $sth->fetch(PDO::FETCH_NUM))
        {
            list($account_id, $office_name) = $row;
            $sel = ($account_id == $this->parent_office) ? "selected" : "";
            $office_options .= "<option value='{$account_id}' {$sel}>{$office_name} ({$account_id})</option>\n";
        }

        return $office_options;
    }

    /**
     * Find label to use for the personnel abreviation
     *
     * @param string
     * @return string
     */
    public function GetPersonnelLabel($abv)
    {
        $label = 'Other';

        if ($abv == "fa")
        {
            if ($this->operator_type == self::$OT_ALF)
                $label = "Director";
            else if ($this->operator_type == self::$OT_OP)
                $label = "Manager";
            else if ($this->operator_type == self::$OT_SPORT)
                $label = "Head Trainer";
            else
                $label = "Administrator";
        }
        else if ($abv == "don")
        {
            if ($this->operator_type != self::$OT_SPORT && $this->operator_type != self::$OT_OP)
                $label = "Director of Nursing";
        }
        else if ($abv == "mm")
        {
            $label = "Marketing / Admissions Manager";
        }
        else if ($abv == "frd")
        {
            if ($this->operator_type == self::$OT_OP)
                $label = "Manager";
            else if ($this->operator_type == self::$OT_SPORT)
                $label = "Other";
            else
                $label = "Director of Rehab";
        }
        else if ($abv == "frd_rm")
        {
            if ($this->operator_type == self::$OT_OP)
                $label = "Clinic Manager&rsquo;s Manager";
            else if ($this->operator_type == self::$OT_SPORT)
                $label = "Other";
            else
                $label = "Director of Rehab&rsquo;s Manager";
        }

        return $label;
    }


    /**
     * Returns the CPM responsible for PM (or null)
     *
     * @return User|null
     */
    public function getPMCPM()
    {
        if ($this->pm_cpm_id)
            return new User($this->pm_cpm_id);
        else
            return null;
    }

    /**
     * Returns provider number
     *
     * @return string
     */
    public function getProvnum()
    {
        return $this->provnum;
    }


    /**
     * Returns the name of the facility rehab director.
     *
     * @return string
     */
    public function getRehabDirector()
    {
        return $this->frd;
    }


    /**
     * Returns the email of the facility rehab director.
     *
     * @return string
     */
    public function getRehabDirectorEmail()
    {
        return $this->frd_email;
    }


    /**
     * Returns the title of the facility rehab director.
     *
     * @return string
     */
    public function getRehabDirectorTitle()
    {
        return $this->titles['frd'];
    }


    /**
     * Returns the name of the facility rehab director's manager.
     *
     * @return string
     */
    public function getRehabDirectorsManager()
    {
        return $this->frd_rm;
    }


    /**
     * Returns the email of the facility rehab director's manager.
     *
     * @return string
     */
    public function getRehabDirectorsManagerEmail()
    {
        return $this->frd_rm_email;
    }


    /**
     * Returns the title of the facility rehab director's manager.
     *
     * @return string
     */
    public function getRehabDirectorsManagerTitle()
    {
        return $this->titles['frd_rm'];
    }


    /**
     * Returns the phone number of the facility rehab director's manager.
     *
     * @return string
     */
    public function getRehabDirectorsManagerPhone()
    {
        return $this->frd_rm_phone;
    }


    /**
     * Returns the rehab provider.
     *
     * @return RehabProvider
     */
    public function getRehabProvider()
    {
        return $this->rehab_provider;
    }


    /**
     * Returns the rehab provider other.
     *
     * @return string
     */
    public function getRehabProviderOther()
    {
        return $this->rehab_provider_other;
    }

    /**
     * Get visit_count
     * @return integer
     */
    public function LookupVisitCount($visit_frequency_id)
    {
        $sth = $this->dbh->prepare("SELECT visit_count FROM contract_visit_frequency WHERE id = ?");
        $sth->bindValue(1, (int) $visit_frequency_id, PDO::PARAM_INT);
        $sth->execute();

        return $sth->fetchColumn();
    }

    /**
     * Returns the visit frequency.
     *
     * @return integer|null
     */
    public function getVisitFrequency()
    {
        return $this->visit_frequency;
    }

    /**
     * Returns the visit frequency.
     *
     * @return integer|null
     */
    public function getVisitFrequencyOptions()
    {
        return $this->visit_frequency;
    }


    /**
     * Returns whether this facility is on contract hold.
     *
     * @return boolean
     */
    public function isOnContractHold()
    {
        return $this->contract_hold;
    }


    /**
     * Returns whether this facility is on credit hold.
     *
     * @return boolean
     */
    public function isOnCreditHold()
    {
        return $this->credit_hold;
    }


    /**
     * Returns lifgate requirement
     *
     * @return boolean
     */
    public function liftgate_required()
    {
        return $this->requires_liftgate;
    }

    /**
     * Returns inside delivery requirement
     *
     * @return boolean
     */
    public function inside_delivery_required()
    {
        return $this->requires_inside_delivery;
    }
    /**
     * Returns has dock
     *
     * @return boolean
     */
    public function get_has_dock()
    {
        return $this->has_dock;
    }
    /**
     * Populates this Facility object from the matching record in the
     * database.
     *
     */
    protected function load()
    {
        if ($this->id)
        {
            # Load details
            $sth = $this->dbh->prepare('SELECT
				fa.legal_name,
				fa.cpt_id,
				fa.parent_office,
				fa.comments,
				fa.med_a_beds,
				fa.med_b_beds,
				fa.other_beds,
				fa.visit_frequency,
				fa.provnum,
				fa.rehab_provider,
				fa.rehab_provider_other,
				fa.old_custid,
				fa.parent_custid,
				fa.id_corporate_group,
				fa.credit_hold,
				fa.contract_hold,
				fa.requires_liftgate,
				fa.requires_inside_delivery,
				fa.has_dock,
				fa.operator_type,
				fa.pm_cpm_id,
				fa.rehab_type,
				fa.fte_count,
				fa.service_contract_id,
				fa.install_conversion,
				fa.cancel_conversion,
				fct.dssi_code
			FROM facilities_all fa
			LEFT OUTER JOIN facility_code_translation fct ON fa.accounting_id = fct.cust_id
			WHERE fa.id = ?');
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->exists = true;

                $this->legal_name = $row['legal_name'];
                $this->cpt_id = ($row['cpt_id']) ? $row['cpt_id'] : null;
                $this->parent_office = trim($row['parent_office']);
                $this->id_corporate_group = $row['id_corporate_group'];
                $this->visit_frequency = $row['visit_frequency'];
                $this->comments = trim($row['comments']);
                $this->med_a_beds = $row['med_a_beds'];
                $this->med_b_beds = $row['med_b_beds'];
                $this->other_beds = $row['other_beds'];
                $this->provnum = trim($row['provnum']);
                $this->old_custid = trim($row['old_custid']);
                $this->parent_custid = trim($row['parent_custid']);
                $this->credit_hold = (boolean) $row['credit_hold'];
                $this->contract_hold = (boolean) $row['contract_hold'];
                $this->operator_type = $row['operator_type'];
                $this->dssi_code = $row['dssi_code'];
                $this->rehab_type = $row['rehab_type'];
                $this->fte_count = (float) $row['fte_count'];
                $this->pm_cpm_id = $row['pm_cpm_id'];
                $this->requires_liftgate = (boolean) $row['requires_liftgate'];
                $this->requires_inside_delivery = (boolean) $row['requires_inside_delivery'];
                $this->has_dock = (boolean) $row['has_dock'];
                $this->service_contract_id = $row['service_contract_id'];
                $this->install_conversion = (boolean) $row['install_conversion'];
                $this->cancel_conversion = (boolean) $row['cancel_conversion'];

                if (!is_null($row['rehab_provider']))
                    $this->rehab_provider = new RehabProvider($row['rehab_provider']);

                $this->rehab_provider_other = trim($row['rehab_provider_other']);
            }

            # Find the contact information
            $sth = $this->dbh->prepare('SELECT
				c.first_name,
				c.last_name,
				c.email,
				c.phone,
				c.role,
				c.send_acvs
			FROM facility_contact_join fc
			INNER JOIN contact c ON fc.contact_id = c.contact_id
			WHERE fc.facility_id = ?');
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                if ($row['role'] == 'fa')
                {
                    $this->fa = trim($row['first_name'] . ' ' . $row['last_name']);
                    $this->fa_email = trim($row['email']);
                }
                else if ($row['role'] == 'don')
                {
                    $this->don = trim($row['first_name'] . ' ' . $row['last_name']);
                    $this->don_email = trim($row['email']);
                }
                else if ($row['role'] == 'mds')
                {
                    $this->mds = trim($row['first_name'] . ' ' . $row['last_name']);
                    $this->mds_email = trim($row['email']);
                }
                else if ($row['role'] == 'mm')
                {
                    $this->mm = trim($row['first_name'] . ' ' . $row['last_name']);
                    $this->mm_email = trim($row['email']);
                }
                else if ($row['role'] == 'frd')
                {
                    $this->frd = trim($row['first_name'] . ' ' . $row['last_name']);
                    $this->frd_email = trim($row['email']);
                }
                else if ($row['role'] == 'frd_rm')
                {
                    $this->frd_rm = trim($row['first_name'] . ' ' . $row['last_name']);
                    $this->frd_rm_email = trim($row['email']);
                    $this->frd_rm_phone = trim($row['phone']);
                }
                else if ($row['role'] == 'other_fvs_rcpt')
                {
                    $this->other_fvs_rcpt = trim($row['first_name'] . ' ' . $row['last_name']);
                }
            }

            $this->titles = array();
            $tth = $this->dbh->prepare("SELECT column_id, title FROM facility_staff_titles");
            $tth->execute();
            while ($row = $tth->fetch(PDO::FETCH_ASSOC))
            {
                $this->titles[trim($row['column_id'])] = trim($row['title']);
            }

            # Load the associated phone numbers which are used to look up
            # facilities with a caller id number
            #
            $sth_phone = $this->dbh->prepare('SELECT phone FROM facility_phone_numbers WHERE facility_id = ?');
            $sth_phone->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth_phone->execute();
            if ($sth_phone->rowCount() == 0)
                $this->associated_phones = array();
            else
                $this->associated_phones = $sth_phone->fetchAll(PDO::FETCH_COLUMN);
        }
        else
        {
            $this->exists = false;
        }
    }

    /**
     * Query for cancellations
     *
     * @param boolean $reload
     */
    public function LoadCCF($reload = false)
    {
        if ($reload || empty($this->cancellations))
        {
            $this->cancellations = array();

            $dbh = DataStor::getHandle();
            $sql = "SELECT
				c.ccf_id,
				c.notification_date,
				c.cancellation_date,
				c.action_plan,
				c.comments,
				c.converted,
				c.reason_code || ':' || rc.reason_description as reason_description,
				c.reason_code2 || ':' || sr.sub_reason_description as sub_reason_description
			FROM ccf c
			LEFT JOIN ccf_reason_code rc on c.reason_code = rc.reason_code
			LEFT JOIN ccf_sub_reason sr on c.reason_code2 = sr.reason_code
			WHERE c.facility_id = ?
			ORDER BY c.ccf_id DESC";
            $sth = $dbh->prepare($sql);
            $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();
            $this->cancellations = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Query for cancellations
     *
     * @param boolean $reload
     */
    public function LoadContracts($reload = false)
    {
        if ($reload || empty($this->contracts))
        {
            $this->contracts = array();

            $dbh = DataStor::getHandle();
            $sth = $dbh->prepare("SELECT
				c.id_contract,
				c.date_install,
				cto.name AS contract_type,
				c.sale_amount AS sale_amount
			FROM contract c
			INNER JOIN contract_type_options cto ON c.id_contract_type = cto.id_contract_type
			WHERE c.id_facility = ?
			AND COALESCE(c.contract_version,'none') != 'INVALID'
			ORDER BY cto.display_order, cto.name, c.date_install");
            $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();
            $this->contracts = $sth->fetchAll(PDO::FETCH_OBJ);
        }

        return $this->contracts;
    }

    /**
     * Query for leases
     *
     * @param boolean $reload
     */
    public function LoadLeases($reload = false)
    {
        if ($reload || empty($this->leases))
        {
            $this->leases = array();

            $dbh = DataStor::getHandle();
            $sth = $dbh->prepare("SELECT
				n.nfif_id,
				n.legal_name,
				n.open_date,
				n.visit_frequency,
				n.status,
				c.install_date,
				c.billing_start_date,
				c.lease_amount,
				c.training_required,
				c.transfered,
				c.lease_discount,
				c.install_discount,
				c.tiered_pricing,
				c.cancellable,
				pt.term_disp as payment_term,
				lt.term as length_term
			FROM nfif n
			INNER JOIN nfif_contract c ON n.nfif_id = c.nfif_id AND c.lease_type <> 10 -- Exclude Purchase
			LEFT JOIN contract_payment_term pt ON c.payment_term_id = pt.id
			LEFT JOIN contract_length_term lt ON c.length_term_id = lt.id
			WHERE n.facility_id = ?
			ORDER BY n.nfif_id DESC");
            $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();
            $this->leases = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Query for loaners
     *
     * @param boolean $reload
     */
    public function LoadLoaners($reload = false)
    {
        if ($reload || empty($this->loaners))
        {
            $this->loaners = array();

            $dbh = DataStor::getHandle();
            $sth = $dbh->prepare("SELECT
				l.loaner_id,
				l.facility_id,
				l.contract_id,
				l.sponsor_id,
				l.daily_rate,
				l.expiration_date,
				l.active,
				l.renewal_due_date,
				f.facility_name,
				f.accounting_id,
				s.firstname || ' ' || s.lastname as sponsor,
				c.date_install,
				c.date_cancellation,
				cpc.firstname || ' ' ||	cpc.lastname as fac_cpc,
				r.lastname as region
			FROM loaner_agreement l
			INNER JOIN facilities f ON l.facility_id = f.id
			INNER JOIN users s ON l.sponsor_id = s.id
			INNER JOIN contract c on l.contract_id = c.id_contract
			LEFT JOIN users cpc ON f.cpt_id = cpc.id
			LEFT JOIN v_users_primary_group upg ON cpc.id = upg.user_id
			LEFT JOIN users r ON upg.group_id = r.id
			WHERE l.facility_id = ?
			ORDER BY l.loaner_id DESC");
            $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();

            $this->loaners = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Query for purchases
     *
     * @param boolean $reload
     */
    public function LoadPurchases($reload = false)
    {
        if ($reload || empty($this->purchases))
        {
            $this->purchases = array();

            $dbh = DataStor::getHandle();
            $sth = $dbh->prepare("SELECT
				n.nfif_id,
				n.facility_id,
				n.legal_name,
				n.open_date,
				n.visit_frequency,
				n.status,
				c.contract_id,
				c.install_date,
				c.billing_start_date,
				c.lease_amount,
				c.training_required,
				c.transfered,
				c.lease_discount,
				c.install_discount,
				c.tiered_pricing,
				c.cancellable,
				pt.term_disp as payment_term,
				lt.term as length_term,
				w.year_interval || ' ' || w.warranty_name as warranty,
				m.term_interval || ' ' || m.name as maintenance_agreement
			FROM nfif n
			INNER JOIN nfif_contract c ON n.nfif_id = c.nfif_id
			LEFT JOIN warranty_option w on c.warranty = w.warranty_id
			LEFT JOIN maintenance_agreement m on c.maintenance_agreement_id = m.id
			LEFT JOIN contract_payment_term pt ON c.payment_term_id = pt.id
			LEFT JOIN contract_length_term lt ON c.length_term_id = lt.id
			WHERE n.facility_id = ?
			AND n.facility_id > 0
			AND c.lease_type = 10 -- Purchase
			ORDER BY n.nfif_id DESC");
            $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();

            $this->purchases = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Query for Transfers
     *
     * @param boolean $reload
     */
    public function LoadTransfers($reload = false)
    {
        if ($reload || empty($this->transfers))
        {
            $this->cancellations = array();

            $dbh = DataStor::getHandle();
            $sth = $dbh->prepare("SELECT
				t.transfer_id,
				t.transfer_date,
				t.reason_code,
				t.subreason,
				tt.description as type,
				of.accounting_id as o_cust_id,
				of.facility_name as o_facility_name,
				nf.accounting_id as n_cust_id,
				t.new_facility_name as n_facility_name
			FROM lease_transfer t
			INNER JOIN lease_transfer_type tt on t.transfer_type = tt.id
			INNER JOIN facilities of ON t.orig_facility_id = of.id
			LEFT JOIN facilities nf ON t.new_facility_id = nf.id
			WHERE (t.orig_facility_id = ? OR t.new_facility_name = ?)
			ORDER BY t.transfer_id DESC");
            $sth->bindValue(1, (int) $this->id, PDO::PARAM_INT);
            $sth->bindValue(2, (int) $this->id, PDO::PARAM_INT);
            $sth->execute();
            $this->transfers = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Get the lable for this operator type
     * @return unknown
     */
    public function OTText()
    {
        if ($this->operator_type == self::$OT_HOSP)
            $label = "Hospital";
        else if ($this->operator_type == self::$OT_ALF)
            $label = "Community";
        else if ($this->operator_type == self::$OT_OP || $this->operator_type == self::$OT_HGR_CLIN)
            $label = "Clinic";
        else if ($this->operator_type == self::$OT_HH)
            $label = "Agency";
        else if ($this->operator_type == self::$OT_SPORT)
            $label = "Training Room";
        else if ($this->operator_type == self::$OT_COLG)
            $label = "Therapy Program";
        else
            $label = "Facility";

        return $label;
    }

    /**
     * Build WHERE clause for lead search from args
     *
     * @param array
     * @param string
     *
     * @return string
     */
    static public function ParseAdvanceSearch($args)
    {
        global $dbh;

        # Integer fields should not be typecast to text
        $int_fields = array('f.id', 'cp.office_id', 'po.office_id');

        # All Valid SM status
        $WHERE = "";

        if ($args['search_fields'])
        {
            foreach ($args['search_fields'] as $idx => $field)
            {
                $is_int = in_array($field, $int_fields);
                $is_date = ($field == 'f.updated_at'); ## Future use

                #if ($is_date)
                #	$field = "to_timestamp($field)";

                $op = $args['operators'][$idx];

                $string = trim(urldecode($args['strings'][$idx]));
                if (strtoupper($string) == 'YES')
                    $string = "YES";
                if (strtoupper($string) == 'NO')
                    $string = "NO";

                if ($is_date)
                    $date_str = date('Y-m-d', strtotime($string));

                switch ($op)
                {
                    case 'sw':
                        $WHERE .= "\n AND {$field}::text ilike " . $dbh->quote("$string%");
                        break;
                    case 'ew':
                        $WHERE .= "\n AND {$field}::text ilike " . $dbh->quote("%$string");
                        break;
                    case 'eq':
                        if ($is_int)
                            $WHERE .= "\n AND $field = " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field}::Date = " . $dbh->quote($date_str);
                        else
                            $WHERE .= "\n AND upper($field::text) = " . $dbh->quote(strtoupper($string));
                        break;
                    case 'nq':
                        if ($is_int)
                            $WHERE .= "\n AND $field <> " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field}::Date != {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND upper($field::text) <> " . $dbh->quote(strtoupper($string));
                        break;
                    case 'gt':
                        if ($is_int)
                            $WHERE .= "\n AND $field > " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field}::Date > {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND $field::text > " . $dbh->quote($string);
                        break;
                    case 'lt':
                        if ($is_int)
                            $WHERE .= "\n AND $field < " . (int) $string;
                        else if ($is_date)
                            $WHERE .= "\n AND {$field}::Date < {$dbh->quote($date_str)}";
                        else
                            $WHERE .= "\n AND $field::text < " . $dbh->quote($string);
                        break;
                    default:
                        if ($is_date)
                            $WHERE .= "\n AND {$field}::Date = " . $dbh->quote($date_str);
                        else
                            $WHERE .= "\n AND {$field}::text ilike " . $dbh->quote("%$string%");
                        break;
                }
            }
        }
        //echo "<pre>$WHERE</pre>";
        return $WHERE;
    }

    /**
     * Build additional WHERE clause for funnel settings
     *
     * @param array
     *
     * @return string
     */
    static public function ParseFunnelSettings($params)
    {
        $FILTER = "";

        $purchase = LeaseContract::$PURCHASE_TYPE;
        $loaner = LeaseContract::$LOANER_TYPE;

        # Filter on user / group
        if (!empty($params['region']))
        {
            # Match group
            if (strpos($params['region'], 'g') === 0)
                $FILTER .= " AND upg.group_id IN (SELECT subgroup_id FROM v_all_child_groups WHERE group_id = " . (int) substr($params['region'], 1) . ")";
            # Match user
            if (strpos($params['region'], 'u') === 0)
                $FILTER .= " AND f.cpt_id = " . (int) substr($params['region'], 1);
        }

        if (isset($params['op_type']) && !in_array('all', $params['op_type']))
        {
            // Remove special options
            $k_a = array_search('all', $params['op_type']);
            $k_n = array_search('nsnf', $params['op_type']);
            if ($k_a !== false)
                unset($params['op_type'][$k_a]);
            if ($k_n !== false)
                unset($params['op_type'][$k_n]);

            if (count($params['op_type']))
            {
                $op_type_list = implode(",", $params['op_type']);
                $op_type_list = str_replace("null", "NULL", $op_type_list);
                $FILTER .= " AND f.operator_type IN ($op_type_list)";
            }
        }

        return $FILTER;
    }

    /**
     * Build WHERE clause for lead search from args
     *
     * @param array
     *
     * @return string
     */
    static public function ParseSimpleSearch($args)
    {
        global $dbh;

        $WHERE = "";

        if ($args['search'])
        {
            if (preg_match('/^#/', $args['search']))
                $WHERE = " AND (upper(f.id) = {$dbh->quote(strtoupper(substr($args['search'], 1)))}";
            else
            {
                $WHERE = "AND (";
                $strings = explode(",", $args['search']);
                $OR = ""; # Dont add keyword OR in the first element
                foreach ($strings as $str)
                {
                    $str = trim(urldecode($str));
                    #$time = strtotime("$str");
                    $int = (int) $str;
                    if ($int)
                    {
                        $WHERE .= " $OR f.id = $int";
                        $WHERE .= " OR cp.office_id = $int";
                        $WHERE .= " OR po.office_id = $int";
                        $WHERE .= " OR f.zip = $int::TEXT";
                        $WHERE .= " OR f.phone like {$dbh->quote($str . '%')}";
                        $WHERE .= " OR f.provnum = $int::TEXT";
                        $OR = "OR"; # Use OR for remaining elements
                    }
                    if ($str)
                    {
                        $WHERE .= " $OR f.facility_name ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR f.accounting_id ILIKE " . $dbh->quote("$str%");
                        $WHERE .= " OR cpc.firstname || ' ' || cpc.lastname ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR f.address ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR f.city ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR f.state ILIKE " . $dbh->quote("$str");
                        $WHERE .= " OR f.provnum ILIKE " . $dbh->quote("$str%");
                        $WHERE .= " OR cp.office_name ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR po.office_name ILIKE " . $dbh->quote("%$str%");
                        $OR = "OR"; # Use OR for remaining elements
                    }
                }
            }

            $WHERE .= ")\n";
        }

        return $WHERE;
    }

    /**
     * Saves the contents of this object to the database. If this object
     * has an id, the record will be UPDATE'd.  Otherwise, it will be
     * INSERT'ed
     *
     * @param array $new
     * @throws PDOException
     */
    public function save($new = array())
    {
        global $user, $sh;

        if (is_null($this->rehab_provider) && isset($new['rehab_provider']))
            $this->rehab_provider = new RehabProvider($new['rehab_provider']);


        # Set converted values if they are null
        $is_converted = ($this->old_custid && $this->old_custid <> $this->cust_id) ? true : false;

        if (is_null($this->install_conversion))
            $this->install_conversion = $is_converted;
        if (is_null($this->cancel_conversion))
            $this->cancel_conversion = $is_converted;

        # Start the transaction
        #
        $in_trans = $sh->in_trans;
        if (!$in_trans)
        {
            $sh->in_trans = true;
            $this->dbh->beginTransaction();
        }

        if ($this->id)
        {
            $facilities_sth = $this->dbh->prepare('
				UPDATE facilities
				SET cpt_id = ?, facility_name = ?, legal_name = ?, corporate_parent = ?,
				    accounting_id = ?, phone = ?, address = ?, address2 = ?,
				    city = ?, state = ?, zip = ?, country_id = ?,
				    cancelled = ?, active = ?, region_id = ?, visit_frequency = ?,
				    provnum = ?, parent_office = ?, contract_hold = ?, pm_cpm_id = ?,
					operator_type = ?, rehab_type = ?, fte_count = ?,
					requires_liftgate = ?, requires_inside_delivery = ?, has_dock = ?
				WHERE id = ?');

            $facilities_sth->bindValue(27, $this->id, PDO::PARAM_INT);

            $details_sth = $this->dbh->prepare('
				UPDATE facilities_details
				SET comments = ?, fa = ?, fa_email = ?, don = ?, don_email = ?,
				    mds = ?, mds_email = ?, mm = ?, mm_email = ?,
				    frd = ?, frd_email = ?, frd_rm = ?, frd_rm_email = ?, frd_rm_phone = ?,
				    other_fvs_rcpt = ?, med_a_beds = ?, med_b_beds = ?, other_beds = ?,
				    rehab_provider = ?, old_custid = ?, parent_custid = ?,
				    id_corporate_group = ?, install_conversion = ?, cancel_conversion = ?
				WHERE facility_id = ?');

            $details_sth->bindValue(25, $this->id, PDO::PARAM_INT);
        }
        else
        {
            # New records default to active = true
            $this->active = true;
            if (empty($this->cust_id))
                $this->GenerateCustId();

            if (empty($this->parent_office))
                $this->parent_office = $this->corporate_parent;

            $facilities_sth = $this->dbh->prepare('
				INSERT INTO facilities (
				  cpt_id, facility_name, legal_name, corporate_parent,
				  accounting_id, phone, address, address2,
				  city, state, zip, country_id,
				  cancelled, active, region_id, visit_frequency,
				  provnum, parent_office, contract_hold, pm_cpm_id,
				  operator_type, rehab_type, fte_count,
				  requires_liftgate, requires_inside_delivery, has_dock, credit_hold)
				VALUES (?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?,?,?,false)');

            $details_sth = $this->dbh->prepare('
			INSERT INTO facilities_details (
				comments, fa, fa_email, don, don_email,
				mds, mds_email, mm, mm_email,
				frd, frd_email, frd_rm, frd_rm_email, frd_rm_phone,
				other_fvs_rcpt, med_a_beds, med_b_beds, other_beds,
				rehab_provider, old_custid, parent_custid, id_corporate_group,
				install_conversion, cancel_conversion)
			VALUES (?,?,?,?,?, ?,?,?,?, ?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?)');
        }

        $med_a_beds_type = (is_null($this->med_a_beds)) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $med_b_beds_type = (is_null($this->med_b_beds)) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $other_beds_type = (is_null($this->other_beds)) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $visit_frequency_type = (is_null($this->visit_frequency)) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $region_id_t = (is_null($this->region_id)) ? PDO::PARAM_NULL : PDO::PARAM_INT;
        $rehab_type_t = ($this->rehab_type) ? PDO::PARAM_INT : PDO::PARAM_NULL;
        $pm_cpm_id_t = is_null($this->pm_cpm_id) ? PDO::PARAM_NULL : PDO::PARAM_INT;

        $facilities_sth->bindValue(1, $this->cpt_id, PDO::PARAM_INT);
        $facilities_sth->bindValue(2, $this->name, PDO::PARAM_STR);
        $facilities_sth->bindValue(3, $this->legal_name, PDO::PARAM_STR);
        $facilities_sth->bindValue(4, $this->corporate_parent, PDO::PARAM_STR);
        $facilities_sth->bindValue(5, $this->cust_id, PDO::PARAM_STR);
        $facilities_sth->bindValue(6, $this->phone, PDO::PARAM_STR);
        $facilities_sth->bindValue(7, $this->addr1, PDO::PARAM_STR);
        $facilities_sth->bindValue(8, $this->addr2, PDO::PARAM_STR);
        $facilities_sth->bindValue(9, $this->city, PDO::PARAM_STR);
        $facilities_sth->bindValue(10, $this->state, PDO::PARAM_STR);
        $facilities_sth->bindValue(11, $this->zip, PDO::PARAM_STR);
        $facilities_sth->bindValue(12, $this->country_id, PDO::PARAM_INT);
        $facilities_sth->bindValue(13, $this->cancelled, PDO::PARAM_BOOL);
        $facilities_sth->bindValue(14, $this->active, PDO::PARAM_BOOL);
        $facilities_sth->bindValue(15, $this->region_id, $region_id_t);
        $facilities_sth->bindValue(16, $this->visit_frequency, $visit_frequency_type);
        $facilities_sth->bindValue(17, $this->provnum, PDO::PARAM_STR);
        $facilities_sth->bindValue(18, $this->parent_office, PDO::PARAM_STR);
        $facilities_sth->bindValue(19, $this->contract_hold, PDO::PARAM_BOOL);
        $facilities_sth->bindValue(20, $this->pm_cpm_id, $pm_cpm_id_t);
        $facilities_sth->bindValue(21, $this->operator_type, PDO::PARAM_INT);
        $facilities_sth->bindValue(22, $this->rehab_type, $rehab_type_t);
        $facilities_sth->bindValue(23, (float) $this->fte_count, PDO::PARAM_STR);
        $facilities_sth->bindValue(24, $this->requires_liftgate, PDO::PARAM_BOOL);
        $facilities_sth->bindValue(25, $this->requires_inside_delivery, PDO::PARAM_BOOL);
        $facilities_sth->bindValue(26, $this->has_dock, PDO::PARAM_BOOL);
        $facilities_sth->execute();

        $details_sth->bindValue(1, $this->comments, PDO::PARAM_STR);
        $details_sth->bindValue(2, $this->fa, PDO::PARAM_STR);
        $details_sth->bindValue(3, $this->fa_email, PDO::PARAM_STR);
        $details_sth->bindValue(4, $this->don, PDO::PARAM_STR);
        $details_sth->bindValue(5, $this->don_email, PDO::PARAM_STR);
        $details_sth->bindValue(6, $this->mds, PDO::PARAM_STR);
        $details_sth->bindValue(7, $this->mds_email, PDO::PARAM_STR);
        $details_sth->bindValue(8, $this->mm, PDO::PARAM_STR);
        $details_sth->bindValue(9, $this->mm_email, PDO::PARAM_STR);
        $details_sth->bindValue(10, $this->frd, PDO::PARAM_STR);
        $details_sth->bindValue(11, $this->frd_email, PDO::PARAM_STR);
        $details_sth->bindValue(12, $this->frd_rm, PDO::PARAM_STR);
        $details_sth->bindValue(13, $this->frd_rm_email, PDO::PARAM_STR);
        $details_sth->bindValue(14, $this->frd_rm_phone, PDO::PARAM_STR);
        $details_sth->bindValue(15, $this->other_fvs_rcpt, PDO::PARAM_STR);
        $details_sth->bindValue(16, $this->med_a_beds, $med_a_beds_type);
        $details_sth->bindValue(17, $this->med_b_beds, $med_b_beds_type);
        $details_sth->bindValue(18, $this->other_beds, $other_beds_type);

        if (!is_null($this->rehab_provider))
            $details_sth->bindValue(19, $this->rehab_provider->getId(), PDO::PARAM_INT);
        else
            $details_sth->bindValue(19, null, PDO::PARAM_NULL);

        $details_sth->bindValue(20, $this->old_custid, PDO::PARAM_STR);
        $details_sth->bindValue(21, $this->parent_custid, PDO::PARAM_STR);
        $details_sth->bindValue(22, ($this->id_corporate_group) ? $this->id_corporate_group : null, PDO::PARAM_INT);
        $details_sth->bindValue(23, $this->install_conversion, PDO::PARAM_BOOL);
        $details_sth->bindValue(24, $this->cancel_conversion, PDO::PARAM_BOOL);

        $details_sth->execute();

        if (!$this->id)
        {
            $this->id = $this->dbh->lastInsertId('facilities_id_seq');
        }

        # Delete associated phone numbers so we can insert them later
        #
        $sth_del_phones = $this->dbh->prepare('
			DELETE FROM facility_phone_numbers WHERE facility_id = ?');
        $sth_del_phones->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth_del_phones->execute();

        # Insert the associated phone numbers
        #
        $sth_ins_phones = $this->dbh->prepare('
			INSERT INTO facility_phone_numbers (facility_id,phone) VALUES (?,?)');
        if ($this->associated_phones)
        {
            foreach ($this->associated_phones as $phone)
            {
                $sth_ins_phones->bindValue(1, $this->id, PDO::PARAM_INT);
                $sth_ins_phones->bindValue(2, $phone, PDO::PARAM_STR);
                $sth_ins_phones->execute();
            }
        }


        # Delete any entry in the facility_code_translation table (we'll
        # insert later)
        #
        $sth_fct_del = $this->dbh->prepare('
			DELETE FROM facility_code_translation WHERE cust_id = ?');
        $sth_fct_del->bindValue(1, $this->cust_id, PDO::PARAM_STR);
        $sth_fct_del->execute();

        # If this facility has a valid DSSI code, insert an entry
        # into the facility_code_translation table
        #
        if (preg_match('/^[0-9A-Z]{2,15}$/i', $this->dssi_code))
        {
            $sth_fct_ins = $this->dbh->prepare('
				INSERT INTO facility_code_translation (cust_id,dssi_code) VALUES (?,?)');
            $sth_fct_ins->bindValue(1, $this->cust_id, PDO::PARAM_STR);
            $sth_fct_ins->bindValue(2, $this->dssi_code, PDO::PARAM_STR);
            $sth_fct_ins->execute();
        }

        # Set converted flags for old_custid
        $converted_flag = "CONV-{$this->cust_id}";
        # First remove old flags
        $this->dbh->exec("UPDATE facilities_details SET old_custid = NULL WHERE old_custid = '{$converted_flag}'");
        if ($this->old_custid)
        {
            # Add new flag
            $this->dbh->exec("UPDATE facilities_details SET
				old_custid = '{$converted_flag}'
			WHERE facility_id = (SELECT id FROM facilities WHERE UPPER(accounting_id) = UPPER('{$this->old_custid}'))");
        }

        # Commit the transaction
        #
        if (!$in_trans)
            $this->dbh->commit();

        $this->exists = true;

        if ($this->update_accounting)
            $this->saveCustomer();

        $this->UpdateContractVisitFrequency();

        $facility_roles = array('fa', 'don', 'mm', 'mds', 'frd', 'frd rm');
        $contact_array = $this->GetContacts();
        if (count($contact_array) > 0)
        {
            foreach ($contact_array as $key => $cont)
            {
                if (in_array($key, $facility_roles))
                {
                    $role = strtolower($cont->role);
                    if ($role == 'frd rm')
                        $role = 'frd_rm';

                    $email = $role . '_email';
                    $matches = array();
                    $full_name = $this->{$role};
                    preg_match('/^(\S+)\s(.*)/', $full_name, $matches);
                    $cont->first_name = isset($matches[1]) ? $matches[1] : $full_name;
                    $cont->last_name = isset($matches[2]) ? trim($matches[2]) : "";
                    $cont->email = $this->{$email};
                    $cont->save();
                }
            }
        }

        parent::load();
        $this->load();
    }

    /**
     * Save corresponding record
     */
    public function SaveCustomer()
    {
        # Find Customer Class and Region
        $customerClass = 0;
        $region = '';
        if ($this->cpt_id > 0)
        {
            $sth = $this->dbh->prepare("SELECT accounting_cpt FROM users WHERE id = ?");
            $sth->execute(array((int) $this->cpt_id));
            list($customerClass) = $sth->fetch(PDO::FETCH_NUM);
            if (!is_numeric($customerClass))
                $customerClass = 0;

            $sth = $this->dbh->prepare("SELECT g.lastname
			FROM v_users_primary_group upg INNER JOIN users g ON upg.group_id = g.id
			WHERE upg.user_id = ?");
            $sth->execute(array((int) $this->cpt_id));
            list($region) = $sth->fetch(PDO::FETCH_NUM);
        }

        # Build Customer Array
        $Customer_ary['CompanyID'] = Config::$Company;
        $Customer_ary['CustID'] = $this->cust_id;
        $Customer_ary['CustName'] = $this->getName();
        $Customer_ary['Phone'] = $this->phone;
        $Customer_ary['EMail'] = $this->email;

        $Customer_ary['ShipAddrName'] = $this->getName();
        $Customer_ary['ShipCustAddrID'] = 'Shipping';
        $Customer_ary['ShipAddrLine1'] = $this->getName();
        $Customer_ary['ShipAddrLine2'] = '';
        $Customer_ary['ShipAddrLine3'] = $this->addr1;
        $Customer_ary['ShipAddrLine4'] = $this->addr2;
        $Customer_ary['ShipAddrLine5'] = '';
        $Customer_ary['ShipCity'] = $this->city;
        $Customer_ary['ShipStateID'] = $this->state;
        $Customer_ary['ShipPostalCode'] = $this->zip;
        $Customer_ary['ShipPhone'] = str_replace(array(' ', '-', '(', ')'), '', $this->phone);
        $Customer_ary['ShipFax'] = str_replace(array(' ', '-', '(', ')'), '', $this->fax);

        $Customer_ary['BillAddrName'] = $this->getName();
        $Customer_ary['BillCustAddrID'] = 'Billing';
        $Customer_ary['BillAddrLine1'] = $this->getName();
        $Customer_ary['BillAddrLine2'] = '';
        $Customer_ary['BillAddrLine3'] = $this->addr1;
        $Customer_ary['BillAddrLine4'] = $this->addr2;
        $Customer_ary['BillAddrLine5'] = '';
        $Customer_ary['BillCity'] = $this->city;
        $Customer_ary['BillStateID'] = $this->state;
        $Customer_ary['BillPostalCode'] = $this->zip;
        $Customer_ary['BillPhone'] = str_replace(array(' ', '-', '(', ')'), '', $this->phone);
        $Customer_ary['BillFax'] = str_replace(array(' ', '-', '(', ')'), '', $this->fax);

        $Customer_ary['ConAddrName'] = $this->getName();
        $Customer_ary['ConCustAddrID'] = 'CntctAddr';
        $Customer_ary['ConAddrLine1'] = $this->getName();
        $Customer_ary['ConAddrLine2'] = '';
        $Customer_ary['ConAddrLine3'] = $this->addr1;
        $Customer_ary['ConAddrLine4'] = $this->addr2;
        $Customer_ary['ConAddrLine5'] = '';
        $Customer_ary['ConCity'] = $this->city;
        $Customer_ary['ConStateID'] = $this->state;
        $Customer_ary['ConPostalCode'] = $this->zip;
        $Customer_ary['ConPhone'] = str_replace(array(' ', '-', '(', ')'), '', $this->phone);
        $Customer_ary['ConFax'] = str_replace(array(' ', '-', '(', ')'), '', $this->fax);

        $Customer_ary['CPT'] = '';
        $Customer_ary['Region'] = $region;
        $Customer_ary['CustClassID'] = (int) $customerClass;
        $Customer_ary['NationalAcctID'] = $this->corporate_client;

        # Create record if the custid does not exists
        $Customer = new Customer($this->cust_id);
        $Customer->save($Customer_ary);
    }


    /**
     *
     */
    public function SendPPSEmail()
    {
        global $preferences, $user;

        $needs_email_sql = <<<END
SELECT count( 1 )
FROM corporate_email_sent
WHERE facility_id = {$this->id}
END;

        $needs_email_sth = $this->dbh->query($needs_email_sql);
        $count = $needs_email_sth->fetchColumn();

        if ($count < 1)
        {
            $sql = <<<END
SELECT fa_email || ', ' || don_email || ', ' || mm_email || ', ' || frd_email || ', ' || frd_rm_email
FROM facilities_details
WHERE facility_id = {$this->id}
END;

            //echo "<!--\n{$sql}\n-->";

            $sth = $this->dbh->query($sql);

            $recipients = $sth->fetchColumn();

            $body = "Dear Partner,

As you are all aware, CMS published the PPS Final Rule on July 29th.  Given the patient care and business implications associated with these changes, We have developed the attached documents to help you fully leverage Our clinical programs, training and medical devices within this new regulatory environment.  With these changes, and those that may occur in the future, our belief is that an emphasis on superior clinical outcomes... will continue to foster superior business outcomes!  Please let me know if you have any questions on these materials...  and as always thank you for your partnership!

Thanks!

";

            $email_signature = trim($preferences->get('general', 'email_signature'));
            $email_signature = ($email_signature != "") ? str_replace("\n", "<br/>", $email_signature) . "\n" : "";

            $body .= ($email_signature != "") ? "{$email_signature}" : $user->getName();

            $subject = "PPS Final Rule";

            echo <<<END
<script type="text/javascript">
function ValidateSendEmail()
{
	var ComplaintButton = document.email_form['complaint_button'];
	var SendButton = document.email_form['send'];
	var body = document.email_form['body'];


	if( document.email_form.email_to.value.replace( /^\s+|\s+$/g, "" ) == "" )
	{
		alert( 'To field must be set to send email!' );
		document.email_form.email_to.focus();
		return false;
	}

	if( document.email_form.subject.value.replace( /^\s+|\s+$/g, "" ) == "" )
	{
		alert( 'Subject field must be set to send email!' );
		document.email_form.subject.focus();
		return false;
	}

	// Trim string and check for empty
	if( document.email_form.body.value.replace( /^\s+|\s+$/g, "" ) == "" )
	{
		alert( 'A Body is required in order to Save.' );
		return false;
	}

	if( SendButton )
	{
		if( SendButton.value != 'Save' )
			SendButton.value = 'Sending Email...';
		SendButton.disabled = true;
	}

	document.email_form.submit();

	return true;
}

function EntertoTab( event, next_input )
{
	var key = event.keyCode || event.which;

	// ENTER => TAB
	if( key == 13 )
	{
		next_input.focus();
		return false;
	}
	return true;
}
</script>
<form name='email_form' action='{$_SERVER['PHP_SELF']}' method='post'>
	<input type='hidden' name='entry' value='{$this->id}'>
	<input type='hidden' name='attachment[0]' value='email_file/MRK 0195 PPS Final Rule Letter.pdf'>
	<input type='hidden' name='attachment[1]' value='email_file/MRK 0196 FY 2012 SNF PPS Final Rule Therapy Highlights.pdf'>
	<input type='hidden' name='email_type' value='PPS'/>
	<input type='hidden' name='act' value='send_email_file'/>
	<table class='form' cellpadding='5' cellspacing='2' style='width:800px;'>
		<tr>
			<th class='subheader' colspan='6'>Send PPS Email</th>
		</tr>
		<tr id='to'>
			<th class='form'>To</th>
			<td class='form' colspan='5' style="text-align: left">
				<input type='text' name='email_to' value='{$recipients}' size='70' onKeyPress="return EntertoTab( event, this.form.subject );"/>
			</td>
		</tr>
		<tr>
			<th class='form'>Subject</th>
			<td class='form' colspan='5' style="text-align: left">
				<input type='text' name='subject' value='{$subject}' size='70' maxlength='255' onKeyPress="return EntertoTab( event, this.form.body );"/>
			</th>
		</tr>
		<tr class='form_section'>
				<th align='left' colspan='6'>Body</th>
			</tr>
		<tr>
			<td class='form' colspan='6'>
				<textarea name='body' rows='15' cols='90'>{$body}</textarea>
			</td>
		</tr>
		<tr>
			<th class="form">
				Attachments:
			</th>
			<td class='form' colspan='5' style="text-align: left">
				MRK 0195 PPS Final Rule Letter.pdf<br/>
				MRK 0196 FY 2012 SNF PPS Final Rule Therapy Highlights.pdf
			</td>
		</tr>
		<tr>
			<td class='buttons' align='center' colspan='6'>
				<input type='submit' class='submit' name='send' value='Send' onClick="return ValidateSendEmail();"/>
			</td>
		</tr>
	</table>
</form>
END;
        }
        else
        {
            $email_sent_sql = <<<END
SELECT sent_by, recipient, tstamp
FROM corporate_email_sent
WHERE facility_id = {$this->id}
END;

            $email_sent_sth = $this->dbh->query($email_sent_sql);
            list($sent_by, $recipient, $tstamp) = $email_sent_sth->fetch(PDO::FETCH_NUM);

            $sent_by = new User($sent_by);
            $date_format = $preferences->get('general', 'dateformat');
            $date = date($date_format, strtotime($tstamp));
            $time = date("h:i:s a", strtotime($tstamp));

            echo <<<END
<p class="info">
	PPS Info has already been e-mailed to {$recipient} by {$sent_by->getName()} on {$date} at {$time}
</p>
END;
        }

        return null;
    }

    /**
     * Handle CRM search.
     * Mulitiple methods are used to find companies
     *
     * @param array $params : Search criteria
     * @param integer $parent_id : Parrent office id
     * @return array (StdObject)
     */
    public static function Search($params)
    {
        global $dbh, $preferences;

        $search_type = (isset($params['search_type'])) ? $params['search_type'] : '';
        $search = (isset($params['search'])) ? $params['search'] : '';
        $active = (isset($params['active'])) ? $params['active'] : 1;
        $ORDER = (isset($params['sort'])) ? $params['sort'] : "";
        $DIR = (isset($params['dir'])) ? $params['dir'] : "";
        $page = 0;
        $LIMIT = (isset($params['limit'])) ? $params['limit'] : "";
        $OFFSET = 0;
        if (isset($params['page']))
        {
            $LIMIT = $preferences->get('general', 'results_per_page');
            $OFFSET = ($params['page'] - 1) * $LIMIT;
        }

        $FILTER = "";
        if ($search_type == 'simple')
            $FILTER = self::ParseSimpleSearch($params);
        else if ($search_type == 'advanced')
        {
            $FILTER = self::ParseAdvanceSearch($params);
            $FILTER .= self::ParseFunnelSettings($params);
        }

        # In order to use the string in a regex, we need to escape it with
        # preg_quote() and then quote it with the DB handle before stripping
        # off the outer quotes.
        $safe_search = $dbh->quote($search);
        $reg_search = trim($dbh->quote(preg_quote($search)), "'");

        # Search for office records
        # Match on any office name in the company
        #
        $sql = "SELECT
			f.id,
			f.accounting_id,
			f.corporate_parent,
			f.parent_office,
			f.facility_name,
			f.cancelled,
			f.updated_at,
			f.provnum,
			f.cpt_id,
			f.address,
			f.address2,
			f.city,
			f.state,
			f.zip,
			f.phone,
			f.fax,
			f.visit_frequency,
			f.fte_count,
			similarity(f.accounting_id, $safe_search) + similarity(f.facility_name, $safe_search) +
			CASE WHEN position(lower($safe_search) in lower(f.facility_name)) != 0 THEN 1 ELSE 0 END +
			CASE WHEN f.accounting_id ~* '( |^)($reg_search)( |\$)' THEN 2 ELSE 0 END +
			CASE WHEN f.facility_name ~* '( |^)($reg_search)( |\$)' THEN 2 ELSE 0 END as \"search_score\",
			d.latitude,
			d.longitude,
			ot.id as operator_type_id,
			ot.short_name as operator_type_short,
			ot.description as operator_type_long,
			r.id as rehab_provider_id,
			r.name as rehab_provider_name,
			cp.office_id as corporate_office_id,
			cp.office_name as corporate_parent_name,
			po.office_id as parent_office_id,
			po.office_name as parent_office_name,
			cpc.firstname as cpc_first,
			cpc.lastname as cpc_last,
			g.lastname as region,
			n.sm_active,
			n.sm_dropped,
			n.sm_total
		FROM facilities f
		INNER JOIN facilities_details d on f.id = d.facility_id
		LEFT JOIN facility_operator_type ot ON f.operator_type = ot.id
		LEFT JOIN rehab_providers r on d.rehab_provider = r.id
		LEFT JOIN corporate_office cp on f.corporate_parent = cp.account_id
		LEFT JOIN corporate_office po on f.parent_office = po.account_id
		LEFT JOIN users cpc ON f.cpt_id = cpc.id
		LEFT JOIN v_users_primary_group upg on cpc.id = upg.user_id
		LEFT JOIN users g on upg.group_id = g.id
		LEFT JOIN (
			SELECT
				facility_id,
				SUM(CASE WHEN 1 = 1 THEN 1 ELSE 0 END) as sm_active,
				SUM(CASE WHEN 1 = 0 THEN 1 ELSE 0 END) as sm_dropped,
				count(*) as sm_total
			FROM nfif
			GROUP BY facility_id
		) n ON f.id = n.facility_id
		WHERE true
		$FILTER";

        // Order the results
        if ($ORDER)
            $sql .= "\nORDER BY $ORDER $DIR";

        // Page the results
        if ($LIMIT)
            $sql .= "\nLIMIT $LIMIT OFFSET $OFFSET";

        //echo  "<pre>$sql</pre>";
        $sth = $dbh->query($sql);
        return $sth->fetchALL(PDO::FETCH_OBJ);
    }

    /**
     * Send Email of a text only version of this issue
     *
     * List all notes in email body include link to the issue
     *
     */
    public function SendEmail()
    {
        global $user;

        if (!isset($_POST['email_type']) || trim($_POST['email_type']) == '')
        {
            ErrorHandler::showError('An error has occurred while trying to send the email.', 'Did not get an email type.', ErrorHandler::$FOOTER, $user);
            exit;
        }

        # Setup To and CC arrays
        $to_addresses = array();
        if (isset($_POST['email_to']) && trim($_POST['email_to']) != '')
            $to_addresses = array_map('trim', preg_split('/,/', strtr($_POST['email_to'], ";", ","), -1, PREG_SPLIT_NO_EMPTY));
        else
        {
            ErrorHandler::showError("An error has occurred while trying to send the {$_POST['email_type']} email.", 'Did not get a to: address.', ErrorHandler::$FOOTER, $user);
            exit;
        }

        if (!isset($_POST['subject']) || trim($_POST['subject']) == '')
        {
            ErrorHandler::showError("An error has occurred while trying to send the {$_POST['email_type']} email.", 'Did not get a subject.', ErrorHandler::$FOOTER, $user);
            exit;
        }

        if (!isset($_POST['body']) || trim($_POST['body']) == '')
        {
            ErrorHandler::showError("An error has occurred while trying to send the {$_POST['email_type']} email.", 'Did not get a body.', ErrorHandler::$FOOTER, $user);
            exit;
        }

        if (isset($_POST['attachment']) && (is_array($_POST['attachment']) || trim($_POST['attachment']) != ''))
        {
            if (!is_array($_POST['attachment']) && !is_file($_POST['attachment']))
            {
                ErrorHandler::showError("An error has occurred while trying to send the {$_POST['email_type']} email.", 'Attachment file does not exist.', ErrorHandler::$FOOTER, $user);
                exit;
            }
            else
            {
                foreach ($_POST['attachment'] as $key => $filename)
                {
                    if (!is_file($filename))
                    {
                        ErrorHandler::showError("An error has occurred while trying to send the {$_POST['email_type']} email.", 'Attachment file does not exist.: ' . $filename, ErrorHandler::$FOOTER, $user);
                        exit;
                    }
                }
            }

            $filename = $_POST['attachment'];
        }
        else
        {
            ErrorHandler::showError("An error has occurred while trying to send the {$_POST['email_type']} email.", 'Did not get an attachment to send.', ErrorHandler::$FOOTER, $user);
            exit;
        }

        $email_body = str_replace("<br />", "\n", strip_tags($_POST['body'], "<br>")) . "\n";
        # Cleanse email, remove any html tags and wordwrap default length is 75 chars
        $email_body = more_html_entity_decode(html_entity_decode($email_body));


        try
        {
            $attachments = array();
            if ($filename)
            {
                if (is_array($filename))
                {
                    foreach ($filename as $file)
                        $attachments[] = new EmailAttachment($file, basename($file));
                }
                else
                {
                    $attachments[] = new EmailAttachment($filename, basename($filename));
                }
            }

            PHPEmail::sendEmail(
                $to_addresses,
                $user,
                trim($_POST['subject']),
                $email_body,
                null,
                'ppsmail@acplus.com',
                $attachments,
                80
            );
        }
        catch (ValidationException $vexc)
        {
            ErrorHandler::showValidationError($v_exc->getMessage(), ErrorHandler::$FOOTER);
        }
        catch (Exception $exc)
        {
            ErrorHandler::showError("An error has occurred while trying to send the {$_POST['email_type']} email.", $exc->getMessage(), ErrorHandler::$FOOTER, $user);
        }


        $email_to = $this->dbh->quote($_POST['email_to']);

        $sql = <<<END
INSERT INTO corporate_email_sent ( facility_id, sent_by, recipient )
VALUES( {$this->id}, {$user->getId()}, {$email_to} )
END;

        try
        {
            $this->dbh->exec($sql);

            echo <<<END
<p class="info">
	E-mail was sent properly to {$_POST['email_to']}.
</p>
END;
        }
        catch (PDOException $pdo_exc)
        {
            ErrorHandler::showError('A database error has occurred while trying to create the E-mail Sent record.', $pdo_exc->getMessage() . "\n" . $pdo_exc->getTraceAsString(), ErrorHandler::$NONE, $user);
        }
    }

    /**
     * Handle CRM search.
     * Mulitiple methods are used to find companies
     *
     * @param array $params : Search criteria
     * @param integer $parent_id : Parrent office id
     * @return array (StdObject)
     */
    public static function Summary($params)
    {
        global $dbh, $preferences;

        $search_type = (isset($params['search_type'])) ? $params['search_type'] : '';

        $FILTER = "";
        if ($search_type == 'simple')
            $FILTER = self::ParseSimpleSearch($params);
        else if ($search_type == 'advanced')
        {
            $FILTER = self::ParseAdvanceSearch($params);
            $FILTER .= self::ParseFunnelSettings($params);
        }

        $summary = new StdClass();
        $summary->total_contract_value = 0;
        $summary->total_rows = 0;

        # Search for office records
        # Match on any office name in the company
        #
        $sql = "SELECT
			g.lastname as region,
			ot.description as opperator_type,
			COUNT(*) as count,
			SUM(c.monthly_revenue) as contract_value
		FROM facilities f
		INNER JOIN facilities_details d on f.id = d.facility_id
		LEFT JOIN facility_operator_type ot ON f.operator_type = ot.id
		LEFT JOIN rehab_providers r on d.rehab_provider = r.id
		LEFT JOIN corporate_office cp on f.corporate_parent = cp.account_id
		LEFT JOIN corporate_office po on f.parent_office = po.account_id
		LEFT JOIN users cpc ON f.cpt_id = cpc.id
		LEFT JOIN v_users_primary_group upg on cpc.id = upg.user_id
		LEFT JOIN users g on upg.group_id = g.id
		LEFT JOIN contract c ON c.id_facility = f.id AND c.date_cancellation IS NULL AND c.id_contract_type NOT IN (10,12)
		WHERE true
		$FILTER
		GROUP BY g.lastname, ot.description
		ORDER BY g.lastname, ot.description";
        //echo  "<pre>$sql</pre>";
        $sth = $dbh->query($sql);
        $summary->breakdown = $sth->fetchALL(PDO::FETCH_OBJ);

        # Add totals to the summary
        foreach ($summary->breakdown as $r)
        {
            $summary->total_contract_value += $r->contract_value;
            $summary->total_rows += $r->count;
        }

        return $summary;
    }

    /**
     * Updates the PM CPM assigned to this facility and logs the change in the
     * cpt_assignments table. This is handled separate from the normal
     * copyFromArray/save so that changes can be logged. This is used by the
     * CPM Assignment app.
     *
     * @param User|null $new_pm_cpm
     * @throws PDOException
     */
    public function updatePMCPM($new_pm_cpm)
    {
        global $user;

        $dbh = DataStor::getHandle();

        try
        {
            $dbh->beginTransaction();

            $new_pm_cpm_id = (is_null($new_pm_cpm)) ? null : $new_pm_cpm->getId();
            $new_pm_cpm_id_type = (is_null($new_pm_cpm)) ? PDO::PARAM_NULL : PDO::PARAM_INT;

            $sth_upd = $dbh->prepare('
			UPDATE facilities SET pm_cpm_id = ? WHERE id = ?');
            $sth_upd->bindValue(1, $new_pm_cpm_id, $new_pm_cpm_id_type);
            $sth_upd->bindValue(2, $this->getId(), PDO::PARAM_INT);
            $sth_upd->execute();

            $sth_log = $dbh->prepare('
			INSERT INTO cpt_assignments (pm_cpm_id,facility_id,rtype,created_by)
			VALUES (?,?,?,?)');
            $sth_log->bindValue(1, $this->pm_cpm_id, PDO::PARAM_INT);
            $sth_log->bindValue(2, $this->getId(), PDO::PARAM_INT);
            $sth_log->bindValue(3, 0, PDO::PARAM_INT);
            $sth_log->bindValue(4, $user->getId(), PDO::PARAM_INT);
            $sth_log->execute();

            $sth_log->bindValue(1, $new_pm_cpm_id, $new_pm_cpm_id_type);
            $sth_log->bindValue(2, $this->getId(), PDO::PARAM_INT);
            $sth_log->bindValue(3, 1, PDO::PARAM_INT);
            $sth_log->bindValue(4, $user->getId(), PDO::PARAM_INT);
            $sth_log->execute();

            $this->pm_cpm_id = $new_pm_cpm_id;

            $dbh->commit();
        }
        catch (Exception $ex)
        {
            echo $ex;
            exit;
        }
    }

    /**
     * Updates the CPM assigned to this facility and logs the change in the
     * cpt_assignments table. This is handled separate from the normal
     * copyFromArray/save so that changes can be logged. This is used by the
     * CPM Assignment app.
     *
     * @param User $new_cpm
     * @throws PDOException
     */
    public function updateCPM($new_cpm)
    {
        global $user;

        $dbh = DataStor::getHandle();

        $dbh->beginTransaction();

        $region_id = $new_cpm->primaryGroup();
        $region_id_t = (is_null($region_id)) ? PDO::PARAM_NULL : PDO::PARAM_INT;

        $sth_upd = $dbh->prepare('
			UPDATE facilities SET cpt_id = ?, region_id = ? WHERE id = ?');
        $sth_upd->bindValue(1, $new_cpm->getId(), PDO::PARAM_INT);
        $sth_upd->bindValue(2, $region_id, $region_id_t);
        $sth_upd->bindValue(3, $this->getId(), PDO::PARAM_INT);
        $sth_upd->execute();

        $sth_log = $dbh->prepare('
			INSERT INTO cpt_assignments (cpt_id,facility_id,rtype,created_by)
			VALUES (?,?,?,?)');
        $sth_log->bindValue(1, $this->cpt_id, PDO::PARAM_INT);
        $sth_log->bindValue(2, $this->getId(), PDO::PARAM_INT);
        $sth_log->bindValue(3, 0, PDO::PARAM_INT);
        $sth_log->bindValue(4, $user->getId(), PDO::PARAM_INT);
        $sth_log->execute();

        $sth_log->bindValue(1, $new_cpm->getId(), PDO::PARAM_INT);
        $sth_log->bindValue(2, $this->getId(), PDO::PARAM_INT);
        $sth_log->bindValue(3, 1, PDO::PARAM_INT);
        $sth_log->bindValue(4, $user->getId(), PDO::PARAM_INT);
        $sth_log->execute();

        $this->cpt_id = $new_cpm->getId();

        $dbh->commit();
    }


    /**
        * function to get a dropdown list of contacts and emails

       public function getContactList($default_contacts = array())
       {
           $dropdown = '';

           if ($this->getAdministrator() != '')
           {
               $selected = array_search($this->getAdministrator(), $default_contacts) !== false ? 'selected' : '';
               $dropdown .= "<option value=\"".htmlentities($this->getAdministrator(), ENT_QUOTES)."\" ".$selected.">".$this->getAdministrator()." - ".$this->getAdministratorTitle()."</option>";
           }
           if ($this->getRehabDirectorsManager() != '')
           {
               $selected = array_search($this->getRehabDirectorsManager(), $default_contacts) !== false ? 'selected' : '';
               $dropdown .= "<option value=\"".htmlentities($this->getRehabDirectorsManager(), ENT_QUOTES)."\" ".$selected.">".$this->getRehabDirectorsManager()." - ".$this->getRehabDirectorsManagerTitle()."</option>";
           }
           if ($this->getRehabDirector() != '')
           {
               $selected = array_search($this->getRehabDirector(), $default_contacts) !== false ? 'selected' : '';
               $dropdown .= "<option value=\"".htmlentities($this->getRehabDirector(), ENT_QUOTES)."\" ".$selected.">".$this->getRehabDirector()." - ".$this->getRehabDirectorTitle()."</option>";
           }
           if ($this->getMarketingManager() != '')
           {
               $selected = array_search($this->getMarketingManager(), $default_contacts) !== false ? 'selected' : '';
               $dropdown .= "<option value=\"".htmlentities($this->getMarketingManager(), ENT_QUOTES)."\" ".$selected.">".$this->getMarketingManager()." - ".$this->getMarketingManagerTitle()."</option>";
           }
           if ($this->getDirectorOfNursing() != '')
           {
               $selected = array_search($this->getDirectorOfNursing(), $default_contacts) !== false ? 'selected' : '';
               $dropdown .= "<option value=\"".htmlentities($this->getDirectorOfNursing(), ENT_QUOTES)."\" ".$selected.">".$this->getDirectorOfNursing()." - ".$this->getDirectorOfNursingTitle()."</option>";
           }
           if ($this->getMDSCoordinator() != '')
           {
               $selected = array_search($this->getMDSCoordinator(), $default_contacts) !== false ? 'selected' : '';
               $dropdown .= "<option value=\"".htmlentities($this->getMDSCoordinator(), ENT_QUOTES)."\" ".$selected.">".$this->getMDSCoordinator()." - ".$this->getMDSCoordinatorTitle()."</option>";
           }

           //if the default contact is not any of the above?
           #if ($dropdown != '' && (stripos($dropdown, 'selected') === false) && $default_contacts)
           #{
           #	$dropdown = "<option value=\"".htmlentities($default_contact, ENT_QUOTES)."\" selected>".$default_contact."</option>".$dropdown;
           #}

           return $dropdown;
       }
       */

    /**
     * Returns the database id for a facility given its provider number.
     *
     * @param string $provnum
     * @param string $status
     * @return int|null
     */
    public static function GetFacilityFromProvnum($provnum, $status)
    {
        $dbh = DataStor::getHandle();

        if ($status != "canceled")
            $not_canceled = "NOT";

        $sql = <<<END
SELECT id
FROM facilities
WHERE provnum = '{$provnum}'
AND {$not_canceled} cancelled
ORDER BY id
END;

        $sth = $dbh->query($sql);

        return $sth->fetchColumn();

    }

    /**
     * Returns the database accounting_id for a facility given its id.
     *
     * @param string $cust_id
     * @return int|null
     */
    public static function GetCustIdFromId($id)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare('
			SELECT accounting_id FROM facilities WHERE id = ?');
        $sth->bindValue(1, $id, PDO::PARAM_INT);
        $sth->execute();
        if ($sth->rowCount() > 0)
        {
            return $sth->fetchColumn();
        }

        return null;
    }

    /**
     * Return contact array
     *
     * @param boolean
     * @return array
     */
    public function GetContacts($reload = false)
    {
        if (empty($this->contacts) || $reload)
        {
            # Load the contact wich are linked to the facility
            # Using Role as special key since facility details have specific information saved
            # Contact is a more complete set of attributes but was not intended to use role for any special assignment
            $sth = $this->dbh->prepare("SELECT
				c.*,
				cr.description as role_description,
				j.facility_id,
				j.default_billing,
				j.default_shipping
			FROM contact c
			LEFT JOIN contact_roles cr ON UPPER(cr.identifier) = UPPER(c.role)
			LEFT JOIN facility_contact_join j ON c.contact_id = j.contact_id
			WHERE j.facility_id = ?
			ORDER BY c.last_name, c.first_name");
            $sth->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $key = $row['contact_id'];

                $contact = new Contact();
                $contact->CopyFromArray($row);
                if ($row['default_shipping'])
                    $row['is_office'] = true;
                $this->contacts[$key] = $contact;
            }
        }

        return $this->contacts;
    }

    /**
     * Returns the database id for a facility given its cust id.
     *
     * @param string $cust_id
     * @return int|null
     */
    public static function getIdFromCustId($cust_id)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare('
			SELECT id FROM facilities WHERE accounting_id = ?');
        $sth->bindValue(1, $cust_id, PDO::PARAM_STR);
        $sth->execute();
        if ($sth->rowCount() > 0)
        {
            return (int) $sth->fetchColumn();
        }

        return null;
    }


    /**
     * Returns any facility ids associated with the given phone number
     *
     * @param string $phone_number
     * @return array|null
     */
    public static function getIdsFromPhoneNumber($phone_number)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare('
			SELECT id FROM facilities WHERE phone = ?
			UNION
			SELECT facility_id FROM facility_phone_numbers WHERE phone = ?');
        $sth->bindValue(1, $phone_number, PDO::PARAM_STR);
        $sth->bindValue(2, $phone_number, PDO::PARAM_STR);
        $sth->execute();
        if ($sth->rowCount() > 0)
        {
            return $sth->fetchAll(PDO::FETCH_COLUMN);
        }

        return null;
    }


    /**
     * Print the form for the staff contacts
     *
     * @param Facility $facility
     */
    public static function showStaffContactForm($facility = null)
    {
        $facility_id = $admin_name = $admin_email = $don_name = $don_email =
            $mds_name = $mds_email = $mm_name = $mm_email = $frd_name =
            $frd_email = $frdm_name = $frdm_email = $frdm_phone =
            $other_fvs_rcpts = '';

        if ($facility)
        {
            $facility_id = $facility->getId();
            $admin_name = htmlentities($facility->getAdministrator(), ENT_QUOTES);
            $admin_email = htmlentities($facility->getAdministratorEmail(), ENT_QUOTES);
            $don_name = htmlentities($facility->getDirectorOfNursing(), ENT_QUOTES);
            $don_email = htmlentities($facility->getDirectorOfNursingEmail(), ENT_QUOTES);
            $mds_name = htmlentities($facility->getMDSCoordinator(), ENT_QUOTES);
            $mds_email = htmlentities($facility->getMDSCoordinatorEmail(), ENT_QUOTES);
            $mm_name = htmlentities($facility->getMarketingManager(), ENT_QUOTES);
            $mm_email = htmlentities($facility->getMarketingManagerEmail(), ENT_QUOTES);
            $frd_name = htmlentities($facility->getRehabDirector(), ENT_QUOTES);
            $frd_email = htmlentities($facility->getRehabDirectorEmail(), ENT_QUOTES);
            $frdm_name = htmlentities($facility->getRehabDirectorsManager(), ENT_QUOTES);
            $frdm_email = htmlentities($facility->getRehabDirectorsManagerEmail(), ENT_QUOTES);
            $frdm_phone = htmlentities($facility->getRehabDirectorsManagerPhone(), ENT_QUOTES);
            $other_fvs_rcpts = htmlentities($facility->getOtherFVSRecipients(), ENT_QUOTES);
        }

        echo <<<END
<table class="form" cellpadding="5" cellspacing="2" style="margin:0;width:100%">
	<tr>
		<th class="form"></th>
		<th class="form" style="text-align:center">Name:</th>
		<th class="form" style="text-align:center">Email:</th>
	</tr>
	<tr>
		<th class="form">Facility Administrator:</th>
		<td class="form"><input type="text" name="fa" value="{$admin_name}" size="25" maxlength="64"></td>
		<td class="form"><input type="text" name="fa_email" value="{$admin_email}" size="25" maxlength="64"></td>
	</tr>
	<tr>
		<th class="form">Director of Nursing:</th>
		<td class="form"><input type="text" name="don" value="{$don_name}" size="25" maxlength="64"></td>
		<td class="form"><input type="text" name="don_email" value="{$don_email}" size="25" maxlength="64"></td>
	</tr>
	<tr>
		<th class="form">MDS Coordinator:</th>
		<td class="form"><input type="text" name="mds" value="{$mds_name}" size="25" maxlength="64"></td>
		<td class="form"><input type="text" name="mds_email" value="{$mds_email}" size="25" maxlength="64"></td>
	</tr>
	<tr>
		<th class="form">Marketing Manager:</th>
		<td class="form"><input type="text" name="mm" value="{$mm_name}" size="25" maxlength="64"></td>
		<td class="form"><input type="text" name="mm_email" value="{$mm_email}" size="25" maxlength="64"></td>
	</tr>
	<tr>
		<th class="form">Facility Rehab Director:</th>
		<td class="form"><input type="text" name="frd" value="{$frd_name}" size="25" maxlength="64"></td>
		<td class="form"><input type="text" name="frd_email" value="{$frd_email}" size="25" maxlength="64"></td>
	</tr>
	<tr>
		<th class="form">FRD&rsquo;s Regional Manager:</th>
		<td class="form"><input type="text" name="frd_rm" value="{$frdm_name}" size="25" maxlength="64"></td>
		<td class="form"><input type="text" name="frd_rm_email" value="{$frdm_email}" size="25" maxlength="64"></td>
	</tr>
	<tr>
		<th class="form">FRD&rsquo;s RM&rsquo;s Phone:</th>
		<td class="form" colspan="2"><input type="text" name="frd_rm_phone" value="{$frdm_phone}" size="25" maxlength="64"></td>
	</tr>
	<tr>
		<th class="form">Other FVS Recipients:</th>
		<td class="form" colspan="2"><input type="text" name="other_fvs_rcpt" value="{$other_fvs_rcpts}" size="35"></td>
	</tr>
</table>
END;
    }


    /**
     * Prints a table of the staff contacts.
     *
     * WARNING: If you change this output, you may also have to change some
     * code in the FVS!  The FVS uses the staff contact form to allow the user
     * to update facility contacts from within the FVS.  Since this function
     * provides the response to a save, the FVS javascript code parses this
     * output and extracts the names of the staff.  If you need to change this
     * output, you also need to look at the personnelSaveSuccess() function
     * in visit_summary.js.
     *
     * @param Facility $facility
     */
    public static function showStaffContactView($facility)
    {

        # Some facilities have long lists of other FVS recipients which take up a
        # lot of horizontal space since they have no whitespace.  So, we insert
        # line breaks after each comma to break up the string.
        #
        /*$admin_name = (strtolower($facility->getAdministrator()) == 'n/a') ? $facility->getAdministrator() : str_replace(array(',',';','/'), ',<br>', $facility->getAdministrator());
              $admin_email = (strtolower($facility->getAdministratorEmail()) == 'n/a') ? $facility->getAdministratorEmail() : str_replace(array(',',';','/'), ',<br>', $facility->getAdministratorEmail());
              $don_name = (strtolower($facility->getDirectorOfNursing()) == 'n/a') ? $facility->getDirectorOfNursing() : str_replace(array(',',';','/'), ',<br>', $facility->getDirectorOfNursing());
              $don_email = (strtolower($facility->getDirectorOfNursingEmail()) == 'n/a') ? $facility->getDirectorOfNursingEmail() : str_replace(array(',',';','/'), ',<br>', $facility->getDirectorOfNursingEmail());
              $mds_name = (strtolower($facility->getMDSCoordinator()) == 'n/a') ? $facility->getMDSCoordinator() : str_replace(array(',',';','/'), ',<br>', $facility->getMDSCoordinator());
              $mds_email = (strtolower($facility->getMDSCoordinatorEmail()) == 'n/a') ? $facility->getMDSCoordinatorEmail() : str_replace(array(',',';','/'), ',<br>', $facility->getMDSCoordinatorEmail());
              $mm_name = (strtolower($facility->getMarketingManager()) == 'n/a') ? $facility->getMarketingManager() : str_replace(array(',',';','/'), ',<br>', $facility->getMarketingManager());
              $mm_email = (strtolower($facility->getMarketingManagerEmail()) == 'n/a') ? $facility->getMarketingManagerEmail() : str_replace(array(',',';','/'), ',<br>', $facility->getMarketingManagerEmail());
              $frd_name = (strtolower($facility->getRehabDirector()) == 'n/a') ? $facility->getRehabDirector() : str_replace(array(',',';','/'), ',<br>', $facility->getRehabDirector());
              $frd_email = (strtolower($facility->getRehabDirectorEmail()) == 'n/a') ? $facility->getRehabDirectorEmail() : str_replace(array(',',';','/'), ',<br>', $facility->getRehabDirectorEmail());
              $frdm_name = (strtolower($facility->getRehabDirectorsManager()) == 'n/a') ? $facility->getRehabDirectorsManager() : str_replace(array(',',';','/'), ',<br>', $facility->getRehabDirectorsManager());
              $frdm_email = (strtolower($facility->getRehabDirectorsManagerEmail()) == 'n/a') ? $facility->getRehabDirectorsManagerEmail() : str_replace(array(',',';','/'), ',<br>', $facility->getRehabDirectorsManagerEmail());
              $other_fvs_rcpts = (strtolower($facility->getOtherFVSRecipients()) == 'n/a') ? $facility->getOtherFVSRecipients() : str_replace(array(',',';','/'), ',<br>', $facility->getOtherFVSRecipients());

              echo <<<END
      <table class="rollup" cellpadding="3" cellspacing="1">
          <tr>
              <th style="width:13em"></th>
              <th style="text-align:center">Name:</th>
              <th style="text-align:center">Email:</th>
          </tr>
          <tr>
              <th style="width:13em">Facility Administrator:</th>
              <td>{$admin_name}</td>
              <td>{$admin_email}</td>
          </tr>
          <tr>
              <th style="width:13em">Director of Nursing:</th>
              <td>{$don_name}</td>
              <td>{$don_email}</td>
          </tr>
          <tr>
              <th style="width:13em">MDS Coordinator:</th>
              <td>{$mds_name}</td>
              <td>{$mds_email}</td>
          </tr>
          <tr>
              <th style="width:13em">Marketing Manager:</th>
              <td>{$mm_name}</td>
              <td>{$mm_email}</td>
          </tr>
          <tr>
              <th style="width:13em">Facility Rehab Director:</th>
              <td>{$frd_name}</td>
              <td>{$frd_email}</td>
          </tr>
          <tr>
              <th style="width:13em">FRD&rsquo;s Regional Manager:</th>
              <td>{$frdm_name}</td>
              <td>{$frdm_email}</td>
          </tr>
          <tr>
              <th style="width:13em">FRD&rsquo;s RM&rsquo;s Phone:</th>
              <td colspan="2">{$facility->getRehabDirectorsManagerPhone()}</td>
          </tr>
          <tr>
              <th style="width:13em">Other FVS Recipients:</th>
              <td colspan="2">{$other_fvs_rcpts}</td>
          </tr>
      </table>
      END;*/
        $_REQUEST['facility_id'] = $facility->getId();
        $_REQUEST['updatetbl'] = 2;
        $_PRIMARY_CONTACT = false;
        $_OPP_CONTACTS = false;
        $_FAC_CONTACTS = false;
        $_USE_TABLE = true;

        $html = "<script type='text/javascript'>
$(document).ready(function() {
	InitContactDT();
});
</script>
		<input type='hidden' id='contact_facility_id' name='contact_facility_id' value='{$facility->getId()}'>
		<table class='compact hover' id='dt_contact' style='width:100%'>
			<thead><tr>
				<th>ContactId</th>
				<th>Role</th>
				<th>Company Title</th>
				<th>First Name</th>
				<th>Last Name</th>
				<th>Phone</th>
				<th>Fax</th>
				<th>Mobile</th>
				<th>Email</th>
				<th>Address</th>
				<th>City</th>
				<th>State</th>
				<th>Zip</th>
				<th>Send ACVS</th>
				<th>Burst Content</th>
				<th></th>
			</tr></thead></table>";

        echo $html;
    }


    /**
     * Print the form for facility details
     *
     * @param Facility $facility
     * @param boolean $return_text
     */
    public static function showDetailForm($facility = null, $return_text = false)
    {
        global $user;

        require_once ('CorporateOffice.php');

        $this_app_admin_name = 'facilities_admin';
        $leasing_mod_app_name = 'facilities_leasing';
        $dssi_app_name = 'dssi_code';

        $cpc = $facility->getCPT();
        $cpc_name = ($cpc) ? $cpc->getName() . ' (' . $cpc->getRegion() . ')' : 'None';

        $pm_cpc = $facility->getPMCPM();
        $pm_cpc_name = ($pm_cpc) ? $pm_cpc->getName() . ' (' . $pm_cpc->getRegion() . ')' : 'None';

        $facility_id = ($facility) ? $facility->getId() : '';
        $facility_name = ($facility) ? htmlentities($facility->getName(), ENT_QUOTES) : '';
        $corporate_parent = ($facility) ? htmlentities($facility->getCorporateParent(), ENT_QUOTES) : '';
        $accounting_id = ($facility) ? htmlentities($facility->getAccountingId(), ENT_QUOTES) : '';
        $parent_custid = ($facility) ? htmlentities($facility->getParentCustId(), ENT_QUOTES) : '';
        $phone = ($facility) ? htmlentities($facility->getPhone(), ENT_QUOTES) : '';
        $address = ($facility) ? htmlentities($facility->getAddress(), ENT_QUOTES) : '';
        $address2 = ($facility) ? htmlentities($facility->getAddress2(), ENT_QUOTES) : '';
        $city = ($facility) ? htmlentities($facility->getCity(), ENT_QUOTES) : '';
        $state = ($facility) ? htmlentities($facility->getState(), ENT_QUOTES) : '';
        $zip = ($facility) ? htmlentities($facility->getZip(), ENT_QUOTES) : '';
        $country_id = ($facility && $facility->getCountry()) ? $facility->getCountry()->getId() : '';
        if (!$country_id)
            $country_id = 228;
        $country_name = ($facility && $facility->getCountry()) ? $facility->getCountry()->getName() : '';
        $country_list = Forms::createCountryList($country_id);
        $cancelled = ($facility && $facility->isCancelled()) ? '<input type="hidden" name="cancelled" value="1">Yes' : 'No';
        $active = ($facility && $facility->isActive()) ? '<input type="hidden" name="active" value="1">Yes' : 'No';
        $visit_frequency = ($facility) ? $facility->getVisitFrequency() : '';

        $comments = ($facility) ? htmlentities($facility->getComments(), ENT_QUOTES) : '';
        $med_a_beds = ($facility) ? $facility->getMedicareABeds() : '';
        $med_b_beds = ($facility) ? $facility->getMedicareBBeds() : '';
        $other_beds = ($facility) ? $facility->getOtherBeds() : '';
        $parent_office_id = ($facility) ? $facility->getParentOfficeId() : '';
        $parent_office = ($facility) ? $facility->getParentOffice() : '';
        $parent_office_options = ($facility) ? $facility->getParentOfficeList() : '';
        $provnum = ($facility) ? htmlentities($facility->getProvnum(), ENT_QUOTES) : '';
        $rehab_provider_id = ($facility && $facility->getRehabProvider()) ? $facility->getRehabProvider()->getId() : '';
        $rehab_provider = ($facility && $facility->getRehabProvider()) ? $facility->getRehabProvider()->getName() : '';
        $operator_type = ($facility) ? $facility->GetOperatorType() : 1;
        $fte_count = ($facility) ? $facility->getFTECount() : '';

        $has_dock = ($facility && $facility->get_has_dock()) ? 'checked' : '';
        $liftgate_required = ($facility && $facility->liftgate_required()) ? 'checked' : '';
        $inside_delivery_required = ($facility && $facility->inside_delivery_required()) ? 'checked' : '';

        # Some features editable only to those with proper access
        if ($user->hasAccessToApplication($this_app_admin_name))
        {
            $provider_input = '<input type="text" name="provnum" value="' . $provnum . '" size="7" maxlength="6">';
        }
        else
        {
            $provider_input = '<input type="hidden" name="provnum" value="' . $provnum . '">' . $provnum;
            $parent_office_input = '<input type="hidden" name="parent_office" value="' . $parent_office_id . '">' . $parent_office;
        }

        # Set the options from the contract record
        $contract_id = (int) $facility->GetServiceContractId();
        $contract = new LeaseContract($contract_id);
        $visit_frequency_id = $contract->getVar('visit_frequency');

        # Convert to visit count
        $visit_count = Facility::VFtoVC($visit_frequency);

        # Default to addon pricing
        $pricing_method = $contract->GetVar('pricing_method');
        if (empty($pricing_method))
            $contract->SetVar('pricing_method', 'add');

        # Only allow Leasing users to modify the Visit Frequency
        if ($user->hasAccessToApplication($leasing_mod_app_name))
        {
            $vsf_input = "<select id='visit_frequency_id' name='visit_frequency_id'>
				{$contract->GetVisitFrequencyList()}
			</select>";

            $chk_remote_service_y = ($contract->GetVar('remote_services') & LeaseContract::$REMOTE_SERVICE_CLINICAL) ? "checked" : "";
            $chk_remote_service_n = ($contract->GetVar('remote_services') & LeaseContract::$REMOTE_SERVICE_CLINICAL) ? "" : "checked";

            if ($contract_id == 0)
            {
                $remote_input = "No Lease Found";

                # When there is no lease just provide a plain visit_frequency input
                $vsf_input = "<input type='text' name='visit_frequency' value='{$visit_frequency}'>";
            }
            else
                $remote_input = "<input type='radio' id='remote_services_y' name='remote_services' value='1' {$chk_remote_service_y}/>
			<label for='remote_services_y'>Yes</label>
			<input type='radio' id='remote_services_n' name='remote_services' value='0' {$chk_remote_service_n}/>
			<label for='remote_services_n'>No</label>";
        }
        else
        {
            $vsf_input = "<input type='hidden' name='visit_frequency_id' value='{$visit_frequency_id}'>{$contract->GetVisitFrequencyText()}";

            if ($contract_id == 0)
            {
                $remote_input = "No Lease Found";

                # When there is no lease just provide a plain visit_frequency input
                $vsf_input = "<input type='text' name='visit_frequency' value='{$visit_frequency}'>";
            }
            else if ($contract->GetVar('remote_services') & LeaseContract::$REMOTE_SERVICE_CLINICAL)
                $remote_input = "Yes";
            else
                $remote_input = "No";
        }


        if ($user->inPermGroup(User::$ACCOUNT_EXECUTIVE))
            $parent_office_input = '<select name="parent_office">' . $parent_office_options . '</select>';
        else
            $parent_office_input = '<input type="hidden" name="parent_office" value="' . $parent_office_id . '">' . $parent_office;

        # Only allow Leasing users to modify the Rehab provider
        #
        if ($user->hasAccessToApplication($leasing_mod_app_name))
        {
            $rehab_provider_inpt = '
				<div id="rehab_provider_cont">
				<input type="text" id="rehab_provider" name="rehab_provider_name" value="' . $rehab_provider . '">
				<div id="ac_rehab_provider"></div>
			</div>';
        }
        else
        {
            $rehab_provider_inpt = $rehab_provider;
        }

        if ($user->hasAccessToApplication($leasing_mod_app_name))
        {
            $rehab_type_list = ($facility) ? $facility->getRehabTypeList() : '';
            $rehab_provider_type = '
<div id="rehab_type">
	<select type="text" id="rehab_type" name="rehab_type" value="">' . $rehab_type_list . '</select>
</div>';
        }
        else
        {
            $rehab_provider_type = ($facility_id) ? $facility->getRehabTypeName($facility_id) : 'NONE';
        }


        $corporate_group_list = ($facility) ? $facility->getCorporateGroupList() : '<option value="">None</option>';

        # Set the checkbox state for conversion fields
        $chk_install_conv_y = ($facility->GetInstallConversion()) ? "checked" : "";
        $chk_install_conv_n = ($facility->GetInstallConversion()) ? "" : "checked";

        $chk_cancel_conv_y = ($facility->GetCancelConversion()) ? "checked" : "";
        $chk_cancel_conv_n = ($facility->GetCancelConversion()) ? "" : "checked";

        $show_conv_inputs = "class='hidden'";
        # Create the DSSI, Corporate Group, and Contract Hold inputs
        #
        $contract_hold_val = ($facility && $facility->isOnContractHold()) ? 1 : 0;
        if ($user->hasAccessToApplication($dssi_app_name))
        {
            $dssi_code_row = <<<END
<tr>
	<th class="form">DSSI Code:</th>
	<td class="form" colspan="2">
		<input type="text" id="demo_dssi_code" name="dssi_code" value="{$facility->getDSSICode()}" size="8" maxlength="15">
	</td>
	<th class="form">Corporate Group:</th>
	<td class="form" colspan="2">
		<select name="id_corporate_group">{$corporate_group_list}</select>
	</td>
</tr>
END;
            $contract_hold_checked = ($contract_hold_val) ? 'checked' : '';

            $contract_hold = "<input type='checkbox' id='contract_hold_chkbx' name='ignored' value='ignored' {$contract_hold_checked}>
			<input type='hidden' id='contract_hold_inpt' name='contract_hold' value='{$contract_hold_val}'>";

            $inputs['old_custid'] = "<input type='text' id='old_custid' name='old_custid' value='{$facility->getOldCustId()}' size='8' maxlength='15'>";
            $show_conv_inputs = "";
        }
        else
        {
            $dssi_code_row = <<<END
<tr>
	<th class="form">Corporate Group:</th>
	<td class="form" colspan="5">
		<select name="id_corporate_group">{$corporate_group_list}</select>
	</td>
</tr>
END;
            $contract_hold_text = ($contract_hold_val) ? 'Yes' : 'No';
            $contract_hold = "<input type='hidden' name='contract_hold' value='{$contract_hold_val}'>{$contract_hold_text}";

            $inputs['old_custid'] = '';
            if ($facility->getOldCustId())
            {
                $old_id = Facility::getIdFromCustId($facility->getOldCustId());
                if ($old_id)
                {
                    $link = "facilities.php?act=view&entry=$old_id";
                    $inputs['old_custid'] = "<a href='#' onClick=\"window.open( '{$link}', 'Changed_from', 'width=700,height=750,toolbar=no,scrollbars=yes,resizable=yes')\">{$facility->getOldCustId()}</a>";
                }
                else
                {
                    $inputs['old_custid'] = "Invalid CustID: {$facility->getOldCustId()}";
                }
            }
        }

        $op_type_input = "<select name=\"operator_type\"/>" . Forms::createOperatorTypeList($operator_type) . "</select>";

        if ($facility_id)
        {
            ## No Change
            $inputs['corporate_parent'] = "<input type=\"hidden\" name=\"corporate_parent\" value=\"{$corporate_parent}\"/> {$corporate_parent}";
            $inputs['accounting_id'] = "<input type=\"hidden\" name=\"accounting_id\" value=\"{$accounting_id}\"/> {$accounting_id}";
            $inputs['parent_custid'] = "<input type=\"hidden\" name=\"parent_custid\" value=\"{$parent_custid}\"/> {$parent_custid}";

            if (preg_match('/HGR$/', $accounting_id))
            {
                $inputs['facility_name'] = "<input type=\"text\" name=\"facility_name\" value=\"{$facility_name}\" size=\"25\" maxlength=\"128\"/>";
                $inputs['corporate_parent'] = '<select name="corporate_parent">' . Forms::createCompanyList($corporate_parent) . '</select>';
                $inputs['phone'] = "<input type=\"text\" name=\"phone\" value=\"{$phone}\" size=\"15\" maxlength=\"32\"/> ";
                $inputs['address'] = "<input type=\"text\" name=\"address\" value=\"{$address}\" size=\"25\" maxlength=\"64\"/>";
                $inputs['address2'] = "<input type=\"text\" name=\"address2\" value=\"{$address2}\" size=\"25\" maxlength=\"64\"/>";
                $inputs['city'] = "<input type=\"text\" name=\"city\" value=\"{$city}\" size=\"25\" maxlength=\"64\"/>";
                $inputs['state'] = "<select name=\"state\"/>" . Forms::createStateList() . "</select>";
                $inputs['zip'] = "<input type=\"text\" name=\"zip\" value=\"{$zip}\" size='10' maxlength=\"32\"/>";
                $inputs['country_id'] = '<select name="country_id">' . Forms::createCountryList($country_id) . '</select>';
            }
            else
            {
                $inputs['facility_name'] = "<input type=\"hidden\" name=\"facility_name\" value=\"{$facility_name}\"/>	{$facility_name}";
                $inputs['phone'] = "<input type=\"hidden\" name=\"phone\" value=\"{$phone}\"/> {$phone}";
                $inputs['address'] = "<input type=\"hidden\" name=\"address\" value=\"{$address}\"/> {$address}";
                $inputs['address2'] = "<input type=\"hidden\" name=\"address2\" value=\"{$address2}\"/> {$address2}";
                $inputs['city'] = "<input type=\"hidden\" name=\"city\" value=\"{$city}\"/> {$city}";
                $inputs['state'] = "<input type=\"hidden\" name=\"state\" value=\"{$state}\"/> {$state}";
                $inputs['zip'] = "<input type=\"hidden\" name=\"zip\" value=\"{$zip}\"/> {$zip}";
                $inputs['country_id'] = "<input type=\"hidden\" name=\"country_id\" value=\"{$country_id}\"/> {$country_name}";
            }

            $office_id = CorporateOffice::Exists($corporate_parent);
            if (is_null($office_id))
            {
                $inputs['corporate_parent'] = '<select name="corporate_parent">' . Forms::createCompanyList() . '</select>';
            }

            $inputs['parent_custid'] = '';
            if ($facility->getParentCustId())
            {
                $parent_id = Facility::getIdFromCustId($facility->getParentCustId());
                if ($parent_id)
                {
                    $link = "facilities.php?act=view&entry=$parent_id";
                    $inputs['parent_custid'] = "<a href='#' onClick=\"window.open( '{$link}', 'Changed_from', 'width=700,height=750,toolbar=no,scrollbars=yes,resizable=yes')\">{$facility->getParentCustId()}</a>";
                }
                else
                {
                    $inputs['parent_custid'] = "Invalid CustID: {$facility->getParentCustId()}";
                }
            }


            $submit_action = 'Save';
        }
        else
        {
            $inputs['facility_name'] = "<input type=\"text\" name=\"facility_name\" value=\"{$facility_name}\" size=\"25\"/>{$facility_name}";
            $inputs['corporate_parent'] = '<select name="corporate_parent">' . Forms::createCompanyList() . '</select>';
            $parent_office_input = '(Will be Set to Corporate Parent)';
            $inputs['accounting_id'] = '(Will be Set by System)';
            $inputs['old_custid'] = '(Will be Set by System)';
            $inputs['parent_custid'] = '(Will be Set by System)';
            $inputs['phone'] = "<input type=\"text\" name=\"phone\" value=\"{$phone}\" size=\"15\"/> {$phone}";
            $inputs['address'] = "<input type=\"text\" name=\"address\" value=\"{$address}\" size=\"25\"/> {$address}";
            $inputs['address2'] = "<input type=\"text\" name=\"address2\" value=\"{$address2}\" size=\"25\"/> {$address2}";
            $inputs['city'] = "<input type=\"text\" name=\"city\" value=\"{$city}\" size=\"25\"/> {$city}";
            $inputs['state'] = "<select name=\"state\"/>" . Forms::createStateList() . "</select>";
            $inputs['zip'] = "<input type=\"text\" name=\"zip\" value=\"{$zip}\" size='10'/> {$zip}";
            $inputs['country_id'] = '<select name="country_id">' . Forms::createCountryList(228) . '</select>';
            $active = '<input type="hidden" name="active" value="1">Yes';
            $cancelled = '<input type="hidden" name="cancelled" value="1">Yes';
            $submit_action = 'Add';
        }


        $html = <<<END
<table class="form" cellpadding="5" cellspacing="2" style="margin:0;width:100%">
	<tr>
		<th class="form">Facility Name:</th>
		<td class="form" colspan="5">
			{$inputs['facility_name']}
		</td>
	</tr>
	<tr>
		<th class="form">CustID:</th>
		<td class="form" colspan="2">
			{$inputs['accounting_id']}
		</td>
		<th class="form">Old CustID:</th>
		<td class="form" colspan="2">
			{$inputs['old_custid']}
		</td>
	</tr>
	<tr $show_conv_inputs>
		<th class="form">Install Conversion:</th>
		<td class="form" colspan="2">
			<input type='radio' id='install_conversion_y' name='install_conversion' value='1' $chk_install_conv_y /><label for='install_conversion_y'>Yes</label>
			<input type='radio' id='install_conversion_n' name='install_conversion' value='0' $chk_install_conv_n /><label for='install_conversion_n'>No</label>
		</td>
		<th class="form">Cancele Conversion:</th>
		<td class="form" colspan="2">
			<input type='radio' id='cancel_conversion_y' name='cancel_conversion' value='1' $chk_cancel_conv_y /><label for='cancel_conversion_y'>Yes</label>
			<input type='radio' id='cancel_conversion_n' name='cancel_conversion' value='0' $chk_cancel_conv_n /><label for='cancel_conversion_n'>No</label>
		</td>
	</tr>
	<tr>
		<th class="form">Corporate Parent:</th>
		<td class="form" colspan="5">
			{$inputs['corporate_parent']}
		</td>
	</tr>
	<tr>
		<th class="form">Parent Office:</th>
		<td class="form" colspan="5">
			{$parent_office_input}
		</td>
	</tr>
	<tr>
		<th class="form">Parent CustID:</th>
		<td class="form" colspan="5">
			{$inputs['parent_custid']}
		</td>
	</tr>
	<tr>
		<th class="form">CPC:</th>
		<td class="form" colspan="5">
			{$cpc_name}
		</td>
	</tr>
    <tr>
		<th class="form">SLP CPC:</th>
		<td class="form" colspan="5">
			{$pm_cpc_name}
		</td>
	</tr>
	<tr>
		<th class="form">Operator Type:</th>
		<td class="form" colspan="5">
			{$op_type_input}
		</td>
	</tr>
	<tr>
		<th class="form">Address:</th>
		<td class="form" colspan="5">{$inputs['address']}</td>
	</tr>
	<tr>
		<th class="form">Address:</th>
		<td class="form" colspan="5">{$inputs['address2']}</td>
	</tr>
	<tr>
		<th class="form">City:</th>
		<td class="form">{$inputs['city']}</td>
		<th class="form">State:</th>
		<td class="form">{$inputs['state']}</td>
		<th class="form">Zip:</th>
		<td class="form">{$inputs['zip']}</td>
	</tr>
	<tr>
		<th class="form">Phone:</th>
		<td class="form" colspan="5">{$inputs['phone']}</td>
	</tr>
	<tr>
		<th class="form">
			<label for="rehab_provider">Rehab Provider:</label>
		</th>
		<td class="form" colspan="2">
			{$rehab_provider_inpt}
		</td>


		<th class="form">
			<label for="rehab_provider">Rehab Type:</label>
		</th>
		<td class="form" colspan="2">
			{$rehab_provider_type}
		</td>
	</tr>
	<tr>
		<th class="form">Med A Beds:</th>
		<td class="form">
			<input type="text" name="med_a_beds" value="{$med_a_beds}" size="7" maxlength="7">
		</td>
		<th class="form">Med B Beds:</th>
		<td class="form">
			<input type="text" name="med_b_beds" value="{$med_b_beds}" size="7" maxlength="7">
		</td>
		<th class="form">Other Beds:</th>
		<td class="form">
			<input type="text" name="other_beds" value="{$other_beds}" size="7" maxlength="7">
		</td>
	</tr>
	<tr>
		<th class="form">Medicare Provider No.:</th>
		<td class="form" colspan="5">{$provider_input}</td>

	</tr>
	<tr>
		<th class="form">Visit Frequency:</th>
		<td class="form" colspan='2'>{$vsf_input}</td>
		<th class="form">Remote Services:</th>
		<td class="form" colspan='2'>{$remote_input}</td>
	</tr>
	{$dssi_code_row}
	<tr>
		<th class="form">Contract Hold:</th>
		<td class="form" colspan="2">
			{$contract_hold}
		</td>
		<th class="form">FTE Count:</th>
		<td class="form" colspan="2">
			<input type="text" name="fte_count" value="{$fte_count}" size="7" maxlength="12">
		</td>
	</tr>
	<tr>
		<th class="form">Freight Special Shipping Instructions:</th>
		<td class="form" colspan="4">
			<input type='checkbox' name='has_dock' value='1' {$has_dock}> Has Dock &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="checkbox" name="requires_liftgate" value="1" {$liftgate_required}> Requires Liftgate &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="checkbox" name="requires_inside_delivery" value="1" {$inside_delivery_required}> Requires Inside Delivery
		</td>
	</tr>
	<tr>
		<th class="form" colspan="6" style="text-align:left">Comments:</th>
	</tr>
	<tr>
		<td class="form" colspan="6">
			<textarea name="comments" rows="5" cols="70">{$comments}</textarea>
		</td>
	</tr>
</table>
END;

        # Return or Echo
        if ($return_text)
            return $html;
        else
            echo $html;
    }


    /**
     *
     * @param Facility $facility
     * @param boolean $return_text
     */
    public static function showDetailView($facility, $return_text = false)
    {
        global $user;

        $dssi_app_name = 'dssi_code';

        $old_custid = '';
        if ($facility->getOldCustId())
        {
            $old_id = Facility::getIdFromCustId($facility->getOldCustId());

            $old_custid = ($old_id) ?
                '<a href="facilities.php?act=view&entry=' . $old_id . '" target="_blank">' . $facility->getOldCustId() . '</a>' :
                "Invalid CustID: {$facility->getOldCustId()}";
        }


        $parent_custid = '';
        if ($facility->getParentCustId())
        {
            $parent_id = Facility::getIdFromCustId($facility->getParentCustId());

            $parent_custid = ($parent_id) ?
                '<a href="facilities.php?act=view&entry=' . $parent_id . '" target="_blank">' . $facility->getParentCustId() . '</a>' :
                "Invalid CustID: {$facility->getParentCustId()}";
        }


        $cpm = $facility->getCPT();
        $cpm_name = ($cpm) ? $cpm->getName() . ' (' . $cpm->getRegion() . ')' : '';

        $pm_cpm = $facility->getPMCPM();
        $pm_cpm_name = ($pm_cpm) ? $pm_cpm->getName() . ' (' . $pm_cpm->getRegion() . ')' : '';

        $map_button = '';
        if ($facility->getAddress() && $facility->getZip())
        {
            $map_button = "<input type=\"button\" value=\"Map\" onClick=\"MapWindow=window.open('map.php?facility={$facility->getId()}','Facility_Map','width=665,height=560,toolbar=no,scrollbars=yes,resizable=yes')\">";
        }

        $rehab_provider = '';
        if ($facility->getRehabProvider())
        {
            if ($facility->getRehabProvider()->getId() == 61 && $facility->getRehabProviderOther())
                $rehab_provider = $facility->getRehabProviderOther();
            else
                $rehab_provider = $facility->getRehabProvider()->getName();
        }

        $rehab_provider_type = $facility->getRehabTypeName();

        $provnum = htmlentities($facility->getProvnum(), ENT_QUOTES);
        if (preg_match("/^\d.....$/", $facility->getProvnum()))
        {
            $provnum = "<a href=\"provider.php?provnum=" . urlencode($facility->getProvnum()) . "\" target=\"_blank\">" . $provnum . "</a>";
        }

        # Set the text from the contract record
        $contract_id = (int) $facility->GetServiceContractId();
        $contract = new LeaseContract($contract_id);

        # Convert frequency (days) to count/yr
        $visit_frequency = $facility->GetVisitFrequency();
        $visit_count = Facility::VFtoVC($visit_frequency);

        if ($contract_id == 0)
            $remote = "No Lease Found";
        else if ($contract->GetVar('remote_services') & LeaseContract::$REMOTE_SERVICE_CLINICAL)
            $remote = "Yes";
        else
            $remote = "No";

        # Default to addon pricing
        $pricing_method = $contract->GetVar('pricing_method');
        if (empty($pricing_method))
            $contract->SetVar('pricing_method', 'add');

        # Set the text from the contract record
        $visit_text = $contract->GetVisitFrequencyText();

        # Show the DSSI code and Contract Hold status if the user has access.
        # Otherwise, just show the Corporate Group.
        #
        if ($user->hasAccessToApplication($dssi_app_name))
        {
            $dssi_code_row = <<<END
<tr>
	<th>DSSI Code:</th>
	<td colspan="2">{$facility->getDSSICode()}</td>
	<th>Corporate Group:</th>
	<td colspan="2">{$facility->getCorporateGroup()}</td>
</tr>
END;
            $contract_hold = $facility->isOnContractHold() ? 'Yes' : 'No';
            $contract_hold_row = <<<END
<tr>
	<th>Contract Hold:</th>
	<td>
		{$contract_hold}
	</td>
	<th></th><th></th><th></th><th></th>
</tr>
END;
        }
        else
        {
            $dssi_code_row = <<<END
<tr>
	<th>Corporate Group:</th>
	<td colspan="5">{$facility->getCorporateGroup()}</td>
</tr>
END;
            $contract_hold_row = '';
        }

        $has_dock = ($facility->get_has_dock()) ? ' Has Dock &nbsp;&nbsp; ' : '';
        $liftgate_required = ($facility->liftgate_required()) ? '&nbsp;&nbsp; Lift Gate Required' : '';
        $inside_delivery_required = ($facility->inside_delivery_required()) ? ' &nbsp;&nbsp; Inside Delivery Required' : '';

        $display_special_shiiping_instructions = "NONE";
        if ($has_dock || $liftgate_required || $inside_delivery_required)
            $display_special_shiiping_instructions = "{$has_dock}{$liftgate_required} &nbsp;&nbsp; {$inside_delivery_required}";

        $comments = str_replace("\n", '<br>', $facility->getComments());
        $pay_term = null;
        if (!isset($_REQUEST['skip_']))
        {
            $pay_term = $facility->GetPaymentTerms();
        }

        $install_conversion = ($facility->GetInstallConversion()) ? "Yes" : "No";
        $cancele_conversion = ($facility->GetCancelConversion()) ? "Yes" : "No";

        $html = <<<END
<table class="rollup" cellpadding="3" cellspacing="1" style='width: 100%;'>
	<tr>
		<th>Facility Name:</th>
		<td colspan="5">
			{$facility->getName()}
		</td>
	</tr>
	<tr>
		<th>CustID:</th>
		<td colspan="2">
			{$facility->getAccountingId()}
		</td>
		<th>Old CustID:</th>
		<td colspan="2">
			{$old_custid}
		</td>
	</tr>
	<tr>
		<th>Install Conversion:</th>
		<td colspan="2">
			{$install_conversion}
		</td>
		<th>Cancelled Conversion:</th>
		<td colspan="2">
			{$cancele_conversion}
		</td>
	</tr>
	<tr>
		<th>Corporate Parent:</th>
		<td colspan="5">
			{$facility->getCorporateParent()}
		</td>
	</tr>
	<tr>
		<th>Parent Office:</th>
		<td colspan="2">
			{$facility->getParentOffice()}
		</td>
		<th>Parent CustID:</th>
		<td colspan="2">
			{$parent_custid}
		</td>
	</tr>
	<tr>
		<th>CPC:</th>
		<td colspan="2">
			{$cpm_name}
		</td>
		<th>SLP CPC:</th>
		<td colspan="2">
			{$pm_cpm_name}
		</td>
	</tr>
	<tr>
		<th>Address:</th>
		<td colspan="4">{$facility->getAddress()}</td>
		<td rowspan="2" style="text-align:center">{$map_button}</td>
	</tr>
	<tr>
		<th>Address:</th>
		<td colspan="4">{$facility->getAddress2()}</td>
	</tr>
	<tr>
		<th>City:</th>
		<td>{$facility->getCity()}</td>
		<th>State:</th>
		<td>{$facility->getState()}</td>
		<th>Zip:</th>
		<td>{$facility->getZip()}</td>
	</tr>
	<tr>
		<th>Phone:</th>
		<td colspan="5">{$facility->getPhone()}</td>
	</tr>
	<tr>
		<th>Payment Terms:</th>
		<td colspan="5">{$pay_term}</td>
	</tr>
	<tr>
		<th>Rehab Provider:</th>
		<td colspan="2">{$rehab_provider}</td>

		<th>Rehab Type:</th>
		<td colspan="2"> {$rehab_provider_type}</td>

		</tr>
		<tr>
		<th>Med A Beds:</th>
		<td colspan="2">{$facility->getMedicareABeds()}</td>
		<th>Med B Beds:</th>
		<td colspan="2">{$facility->getMedicareBBeds()}</td>
	</tr>
	<tr>
		<th>Medicare Provider No.:</th>
		<td colspan="2">{$provnum}</td>

	</tr>
	<tr>
		<th>Clinical Support / Visits Frequency:</th>
		<td>$visit_text ({$visit_count}/yr)</td>
		<th>Remote Services:</th>
		<td>$remote</td>
		<th>Other Beds:</th>
		<td colspan="2">{$facility->getOtherBeds()}</td>
	</tr>
	{$dssi_code_row}
	{$contract_hold_row}
	<tr>
		<th>Freight Special Shipping Instructions:</th>
		<td colspan="5">{$display_special_shiiping_instructions}</td>
	</tr>
	<tr>
		<th colspan="6" style="text-align:left">Comments:</th>
	</tr>
	<tr>
		<td colspan="6" style="vertical-align:top">{$comments}</td>
	</tr>
</table>
END;

        if ($return_text)
            return $html;
        else
            echo $html;
    }

    /**
     * Dialog display for customer management
     *
     * @param integer
     * @param array
     */
    public function ShowForm($edit = 0, $form = array())
    {
        echo "<form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
		<input type='hidden' name='act' value='save_obj' />
		<input type='hidden' name='entry' value='{$this->id}' />
		<input type='hidden' name='is_office' value='0' />
		<input type='hidden' name='office_id' value='{$this->id}' />
		<input type='hidden' name='object' value='Facility' />";

        self::showDetailForm($this);

        echo "\n</form>";
    }

    /**
     * Return a list of facilities using a corporate parent account id.
     */
    public static function getFacilitiesByAccountId($account_id)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare("SELECT id FROM facilities WHERE corporate_parent = ? AND accounting_id NOT LIKE '___9%' AND accounting_id NOT LIKE '___6&' ORDER BY facility_name DESC");
        $sth->bindValue(1, $account_id, PDO::PARAM_STR);
        $sth->execute();
        $facilities = $sth->fetchAll(PDO::FETCH_ASSOC);
        foreach ($facilities as $i => $facility)
            $facilities[$i] = new Facility($facility['id']);
        return $facilities;
    }

    /**
     * Update the visit Frequency for the first lease
     */
    public function UpdateContractVisitFrequency()
    {
        if ($this->service_contract_id && !is_null($this->visit_frequency_id))
        {
            $contract = new LeaseContract($this->service_contract_id);
            $contract->change('visit_frequency', $this->visit_frequency_id);
        }
    }

    /**
     * Convert visit frequency (days) to visit count (per year)
     * @param integer $vf
     */
    static public function VFtoVC($vf)
    {
        return ((int) $vf > 0) ? floor(365 / $vf) : 0;
    }
}

?>