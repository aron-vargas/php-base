<?php

/**
 * @package Freedom
 * @author Aron Vargas
 */
/**
 * Provides Corporate Office class definition
 */
class CorporateOffice {
    protected $dbh = null;

    public $office_id = null;			# int
    public $office_name = '';			# string
    public $account_id = '';				# string
    public $status = 0;					# int
    public $s_status = null;				# string
    public $parent_id = 0;				# int
    public $pull_invoice_type = 1;			# int
    public $corporate_office_id = 0;		# int
    public $orientation = 0;				# bool
    public $nursing_homes = 0;			# int
    public $acp_nursing_homes = 0;		# int
    public $last_mod = null;				# date
    public $last_mod_by = null;			# int
    public $account_executive = null;	# int
    public $slp_cpc_id = null;			# int

    private $breadcrumb;				# string

    public $is_office = true;			# bool office or Facility
    public $is_patient = false;			# bool facility can be a DME patient
    public $is_clinic = false;			# bool facility can be a hanger clinic
    public $supply_bill_to_corporate = true;	# bool : Bill office for supplies
    public $monthly_bill_to_corporate = true;	# bool : Bill office for contract
    public $po_required = false;		# bool
    public $dssi_code;					# string
    public $operator_type = 0;			# integer
    public $payment_terms_id = "Not Available";		# string
    public $payment_terms_desc = "Not Available";	# string
    public $contacts = array();			# Contact array
    public $contracts = array();		# Contract array
    public $detail = array();			# Detail array
    public $equipment = array();		# Equipment array
    public $facilities = array();		# Facility array
    public $questions = array();		# question array
    public $goals = array();			# goals array
    public $issues = array();			# Issue array
    public $visits = array();			# Visit array
    public $clinrecs = array();			# Clinrecs array
    public $invoices = array();			# Invoice array
    public $leads = array();			# Lead array
    public $orders = array();			# Order array
    public $subs = array();				# Subdivision array
    public $tasks = array();			# Task array
    public $wos = array();				# Work Order array

    public static $CO = '001';
    public static $DEFAULT001_CO = '100';
    public static $INI_CO = 'INI';

    ## Static Vars

    # Section Display
    static public $def_display = array(
        'sub_disp' => 0, 'facility_disp' => 0, 'contact_disp' => 0,
        'contract_disp' => 0, 'detail_disp' => 0, 'issue_disp' => 0,
        'clinic_disp' => 0, 'goal_disp' => 0, 'question_disp' => 0,
        'lead_disp' => 0, 'order_disp' => 0, 'memo_disp' => 0, 'invoice_disp' => 0,
        'map_disp' => 0, 'equip_disp' => 0, 'calendar_disp' => 0,
        'task_disp' => 0, 'therapist_disp' => 0,
        'clin_disp' => 0, 'visit_disp' => 0, 'wo_disp' => 0);

    /**
     * Creates a new Corporate Office object.
     *
     * @param integer $office_id
     */
    public function __construct($office_id = null, $object = null)
    {
        $this->dbh = DataStor::getHandle();

        # Allow to load on office_id and account_id
        if (is_numeric($office_id))
        {
            $this->office_id = $office_id;
        }
        else
        {
            $this->account_id = $office_id;
        }

        if ($object == 'facility')
            $this->LoadFromFacility($office_id);
        else
            $this->load();

        if ($this->operator_type == Facility::$OT_HGR_CLIN)
            $this->is_clinic = true;
    }


    /**
     * Changes one field in the database and reloads the object.
     *
     * @param string $field
     * @param mixed $value
     */
    public function change($field, $value)
    {
        if ($this->office_id)
        {
            $sth = $this->dbh->prepare("UPDATE corporate_office SET $field = ? WHERE office_id = ?");
            $sth->bindValue(1, $value);
            $sth->bindValue(2, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            $this->{$field} = $value;
        }
        else
        {
            throw new Exception('Cannot update a non-existant record.');
        }
    }

    /**
     * Build calendar html
     *
     * @param string
     * @param integer
     */
    public function CalendarDraw(&$html, &$count)
    {
        $cal = new CRMCalendar();
        $cal->is_office = $this->is_office;
        $cal->corporate_office_id = $this->corporate_office_id;
        if (!$this->is_office)
            $cal->facility_id = $this->office_id;

        ## Update attributes from request
        $defaults = array('view' => 'mo', 'usgr' => '', 'selected_time' => time(), 'focused_time' => time());
        SessionHandler::Update('crm', 'cal', $defaults);

        $cal->SelectView($_SESSION['crm']['cal']['view']);
        $cal->SelectUser($_SESSION['crm']['cal']['usgr']);
        $cal->SelectDate($_SESSION['crm']['cal']['selected_time']);
        $cal->FocusDate($_SESSION['crm']['cal']['focused_time']);

        $html = $cal->DrawCalendar();
    }

    /**
     * Get list of events
     *
     * @param string
     * @param integer
     */
    public function CalendarEvents(&$html, &$count)
    {
        $events = null;

        $cal = new CRMCalendar();
        $cal->is_office = $this->is_office;
        $cal->corporate_office_id = $this->corporate_office_id;
        if (!$this->is_office)
            $cal->facility_id = $this->office_id;
        $cal->SelectView($_SESSION['crm']['cal']['view']);
        $cal->SelectUser($_SESSION['crm']['cal']['usgr']);
        $cal->SelectDate($_SESSION['crm']['cal']['selected_time']);
        $cal->Prepare();

        $match = "o.office_id";
        if ($this->is_office)
        {
            if ($this->parent_id == 0)
                $match = "o.corporate_office_id";
        }
        else
        {
            $match = "e.facility_id";
        }

        if ($this->office_id)
        {
            $owner_clasue = "";
            if ($cal->user)
                $owner_clasue = "AND e.owner = {$cal->user->GetId()}";
            if ($cal->group)
                $owner_clasue = "AND g.group_id IN (SELECT subgroup_id FROM v_all_child_groups WHERE group_id = {$cal->group->GetId()})";

            # Load issues
            $sth = $this->dbh->prepare("SELECT
				e.id,
				e.owner,
				e.facility_id,
				e.category_id,
				e.start_time,
				e.end_time,
				e.all_day,
				e.title,
				e.description,
				e.location,
				e.repeat_type,
				e.repeat_cycle,
				e.repeat_period,
				e.repeat_end_time,
				e.public,
				e.resource_id,
				e.lock_date,
				e.office_id
			FROM events e
			LEFT JOIN facilities f on e.facility_id = f.id
			LEFT JOIN corporate_office o ON f.parent_office = o.account_id
			LEFT JOIN v_users_primary_group g ON e.owner = g.user_id
			WHERE $match = ?
			AND start_time BETWEEN ? AND ?
			$owner_clasue");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->bindValue(2, $cal->GetFirst(), PDO::PARAM_INT);
            $sth->bindValue(3, $cal->GetLast(), PDO::PARAM_INT);
            $sth->execute();
            while ($event = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $event['day_id'] = date("Y_m_d", $event['start_time']);
                $event['onclick'] = $cal->EvClick($event['id']);
                $event['id'] = "cal_event_{$event['id']}";

                # Convert the start time H:m date part to minutes
                $hr = (int) date('G', $event['start_time']);
                $min = (int) date('i', $event['start_time']);
                $start_time = $hr * 60 + $min;
                $event['start_time'] = $start_time;

                # Convert the end time H:m date part to minutes
                $hr = (int) date('G', $event['end_time']);
                $min = (int) date('i', $event['end_time']);
                $end_time = $hr * 60 + $min;
                $event['end_time'] = $end_time;

                $events[] = $event;
            }
        }

        $html = $events;
    }

    /**
     * Supply html for the Calendar section
     *
     * @return string
     */
    public function CalendarSection()
    {
        global $user;
        $is_office = ($this->is_office) ? 1 : 0;
        $redaw_args = "act=db&obj=section&method=CalendarDraw&entry={$this->office_id}&is_office=$is_office";
        $fill_args = "act=db&obj=section&method=CalendarEvents&entry={$this->office_id}&is_office=$is_office";

        $defaults = array('view' => 'mo', 'usgr' => "", 'selected_time' => time());
        SessionHandler::Update('crm', 'cal', $defaults);

        $cal = new CRMCalendar();
        $cal->is_office = $this->is_office;
        $cal->corporate_office_id = $this->corporate_office_id;
        if (!$this->is_office)
            $cal->facility_id = $this->office_id;
        $cal->SelectView($_SESSION['crm']['cal']['view']);
        $cal->SelectUser($_SESSION['crm']['cal']['usgr']);
        $cal->SelectDate($_SESSION['crm']['cal']['selected_time']);

        $office_loc_array = CorporateOffice::CreateTBHCompanyLocArray($this->corporate_office_id);

        $section = "<div class='flx_cont'>
			<div id='calendar_cont' class='box box-primary'>
				<div id='calendar_hdr' class='box-header' data-toggle='collapse' data-target='#calendar_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Calendar</h4>
				</div>
				<div id='calendar_disp' class='box-body collapse'>
					{$cal->Draw()}
				</div>
			</div>
		</div>
		<script type='text/javascript'>
			$office_loc_array
			CalEvMgr.draw_args = '$redaw_args';
			CalEvMgr.gev_args = '$fill_args';
		</script>";

        return $section;
    }

    /**
     * Supply html for the Clinic edit form
     *
     * @return string
     */
    public function ClinicDetail()
    {
        $fac = new Facility($this->office_id);

        return Facility::showDetailView($fac, true);
    }

    /**
     * Supply html for the Clinic section
     *
     * @return string
     */
    public function ClinicSection()
    {
        $entry = $this->office_id;

        $section = "
		<div id='clinic_cont' class='box box-primary flx_cont'>
			<div id='clinic_hdr' class='box-header' data-toggle='collapse' data-target='#clinic_disp' style='cursor:pointer;'>
				<i class='fa fa-minus pull-right'></i>
				<h4 class='box-title'>Site Information</h4>
			</div>
			<div id='clinic_disp' class='box-body collapse in'>
				<form id='co_clinic' name='co_clinic' action='{$_SERVER['PHP_SELF']}' method='POST'>
				<input type='hidden' name='act' value='save' />
				<input type='hidden' name='object' value='Facility' />
				<input type='hidden' name='entry' value='{$this->office_id}' />
				<input type='hidden' name='office_id' value='{$this->office_id}' />
				<input type='hidden' name='parent' value='clinic_disp' />
				<input type='hidden' name='save_detail' value='1' />
				{$this->ClinicDetail(0)}
				</form>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply html for the section
     *
     * @return string
     */
    public function ClinrecSection()
    {
        $section = "<div class='flx_cont'>
			<div id='clin_cont' class='box box-primary'>
				<div id='clin_hdr' class='box-header' data-toggle='collapse' data-target='#clin_disp' style='cursor:pointer;'>
					<i class='fa fa-minus pull-right'></i>
					<h4 class='box-title'>Clinical Consultations</h4>
					<span class='badge' id='clin_sec_badge'></span>
				</div>
				<div class='box-header with-border fltr txar'>
					<a class='btn btn-default btn-sm' href='clinrec.php?fid={$this->office_id}'>Update Consultations</a>
				</div>
				<div id='clin_disp' class='box-body collapse in'>
					<div class='on'>Loading...</div>
					<script type='text/javascript'>$(function () { CRMLoader.LoadContent('clin', false); });</script>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply html table for the section
     *
     * @return string
     */
    public function ClinrecTable(&$html, &$count)
    {
        global $this_app_name, $preferences;

        $date_format = $preferences->get('general', 'dateformat');

        $defaults = array("page" => 1, "sort_by" => "p.date_entered", "dir" => "ASC");
        SessionHandler::Update('crm', 'clin', $defaults);
        $filter = $_SESSION['crm']['clin'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 1;
        $sec_conf->disp = 'clin_disp';
        $sec_conf->nav_badge = 'clin_nav_badge';
        $sec_conf->sec_badge = 'clin_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=ClinrecTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_clinrec_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        $this->LoadClinRecs($filter);

        ## Init vars
        $rc = 'dt-tr-on';
        foreach ($this->clinrecs as $rec)
        {
            $sec_conf->row_count++;
            # Need to page results useing a window function
            # for true total. This is return in total_records field.
            $count = $rec->row_count;
            $sec_conf->count = $rec->row_count;
            $tags = array();
            ## Edit
            $req_url = "clinrec.php?action=edit&fid={$rec->facility_id}&cid{$rec->patient_id}";
            $tags[] = array('text' => "edit", 'alt' => 'Edit this record', 'href' => $req_url, $target = '_blank');

            $menu = BaseClass::BuildATags($tags);

            ## Show inactive?
            ##$ia = ($rec->active) ? "" : " faded";
            $rec->date_entered = date($date_format, strtotime($rec->date_entered));

            $sec_conf->rows .= "<tr class='{$rc}' ondblclick=\"window.location='{$req_url}';\">";

            foreach ($sec_conf->cols as $col)
            {
                $sec_conf->rows .= "<td class='{$col->cls}'>{$rec->{$col->key} }</td>";
            }
            $sec_conf->rows .= "<td class='nested' style='text-align:right;'>
					<div class='submenu'>
						$menu
					</div>
				</td>
			</tr>";

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        if ($count == 0)
        {
            $cs = count($sec_conf->cols) + 1;
            $sec_conf->rows = "<tr class='dt-tr-on'><td colspan=$cs style='text-align:center;'>No Clinical Recommendation found.</td></tr>";
        }

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    /**
     * Get an editable form to modify the office
     * Include company detail for the top level office of the company
     *
     * @return string html to be displayed
     */
    public function CompanyHdr()
    {
        global $user;

        $contact_id = (isset ($this->contacts[0])) ? $this->contacts[0]->getId() : '';

        $act = ($user->inPermGroup(User::$ACCOUNT_EXECUTIVE)) ? "save" : "view";

        $header_text = ($this->parent_id == 0) ? "Corporate Office" : "Subdivision";

        ## Accounting is not editable if linked to a accounting record
        $edit = (is_numeric($this->account_id) || $this->parent_id > 0);
        $input = $this->getAccId($edit);
        $accouting_id_row = "
		<tr>
			<th>Accounting ID:</th>
			<td>{$input}</td>
		</tr>";

        # Classic |field|value| rows
        $company_table = "
	<form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
	<input type='hidden' name='act' value='$act' />
	<input type='hidden' name='object' value='CorporateOffice' />" .
            $this->getId(1) .
            $this->getParentId(1) .
            $this->getCorporateOfficeId(1) . "
	<input type='hidden' name='entry' value='" . $this->getId(0) . "' />
	<input type='hidden' name='contact_id' value='{$contact_id}' />
	<table class='e_form' border='0' cellspacing='2' cellpadding='4'>
		<tr>
			<th class='subheader' colspan='2'>{$header_text}</th>
		</tr>
		{$accouting_id_row}
		<tr>
			<th>Name:</th>
			<td>" . $this->getOfficeName(1) . "</td>
		</tr>
		<tr>
			<th>Active:</th>
			<td>" . $this->getStatus(1) . "</td>
		</tr>
		<tr>
			<th>Account Executive:</th>
			<td>" . $this->getAccountExecutiveName(1) . "</td>
		</tr>
		<tr>
			<th>Orientation:</th>
			<td>" . $this->getOrientation(1) . "</td>
		</tr>
	</table>
	</form>";

        return $company_table;
    }

    /**
     * Build Corporate Office html
     *
     * @param array
     * @param string
     * @param int
     */
    static public function CompanyInfo($co, $num, $lvl)
    {
        $address = "";
        if ($co->address1)
            $address .= htmlentities($co->address1, ENT_QUOTES);
        if ($co->address2)
            $address .= "<br/>" . htmlentities($co->address2, ENT_QUOTES);
        if ($co->city || $co->state || $co->zip)
        {
            $address .= "<br/>";
            $address .= htmlentities($co->city, ENT_QUOTES);
            $address .= ", " . htmlentities($co->state, ENT_QUOTES);
            $address .= " " . htmlentities($co->zip, ENT_QUOTES);
        }
        if ($co->phone)
            $address .= "<br/>Phone: " . trim($co->phone);

        # AE info
        $ae = trim($co->am_first . " " . $co->am_last);
        if ($ae)
            $ae = "AE: $ae";

        # Add expand link if there are children
        $expand = "";
        $family_tree = "";
        if ($co->office_num) # || $co['facilities'])
        {
            $expand = "<span id='exp_{$co->office_id}' class='arrow plus' onclick=\"ShowChildren({$co->office_id}, 'tree{$lvl}');\">&nbsp;&nbsp;</span>";
            $family_tree = "<div class='hidden' id='tree{$lvl}_{$co->office_id}'></div>";
        }

        $subs = (int) $co->office_num;
        $facs = (int) $co->facility_num;

        # Set up the class of this row (on/off)
        $rc = ($num % 2) ? 'on' : 'off';

        # Add additional classes for first and cancelled records
        if ($num == 1)
            $rc .= " first";
        if ($co->status == 0)
            $rc .= " cancelled";

        $detail_url = "{$_SERVER['PHP_SELF']}?act=getform&object=CorporateOffice&entry={$co->office_id}";
        $addres_url = "{$_SERVER['PHP_SELF']}?act=getform&object=CoAdrress&entry={$co->office_id}";

        $info[0] = array('text' => "{$co->cust_name} ({$co->cust_id})", 'alt' => 'View Subdivision Detail', 'href' => "{$_SERVER['PHP_SELF']}?act=view&object=office&office_id={$co->office_id}");
        $name = BaseClass::BuildATags($info);

        return "
		<div class='$rc'
			onmouseover=\"this.className='hl';\"
			onmouseout=\"this.className='$rc';\">
			<div id='co_{$co->office_id}' class='office'>
				$expand
				<div style='padding-right: 15px;'>
					$name
				</div>
				<div>Subdivisions: <b>$subs</b>&nbsp;&nbsp;&nbsp;&nbsp;Facilities: <b>$facs</b></div>
				<div class='address' id='address_{$co->office_id}'>
					<div>$address</div>
					<div>$ae</div>
				</div>
			</div>
			$family_tree
		</div>";
    }

    /**
     * View is broken into sections.
     * This will show company and office level properties
     *
     * @return string html to be displayed
     */
    public function CorpInfo()
    {
        global $user;

        # For potenialy long strings check length only allow 30 chars
        # Create mailto link for the email address
        $email_link = $this->GetEmail();
        $email_disp = (strlen($email_link) > 30) ? substr($email_link, 0, 30) . "..." : $email_link;
        $email_link = ($email_link) ? "<a href='mailto:{$email_link}'>{$email_disp}</a>" : "";

        # Create a link for the web page
        $web_link = $this->GetWebsite();
        if ($web_link && !stristr($web_link, 'http'))
            $web_link = "http://$web_link";
        $web_disp = (strlen($web_link) > 30) ? substr($web_link, 0, 30) . "..." : $web_link;
        $web_link = ($web_link) ? "<a href='{$web_link}' target='_blank'>{$web_disp}</a>" : "";

        $orientation = ($this->orientation) ? "Private" : "Public";

        # Attempt to show address neatly
        $address = "";
        if ($this->GetAddress1())
            $address .= $this->GetAddress1() . "<br/>";
        if ($this->GetAddress2())
            $address .= $this->GetAddress2() . "<br/>";
        if ($this->GetCity())
            $address .= $this->GetCity();
        if ($this->GetState())
            $address .= ", " . $this->GetState();
        if ($this->GetZip())
            $address .= " " . $this->GetZip();
        $address .= "<br/>Phone: " . $this->GetPhone();
        $address .= "<br/>Fax: " . $this->GetFax();
        if ($this->GetEmail())
            $address .= "<br/>Email: $email_link";
        if ($web_link)
            $address .= "<br/>Web: $web_link";
        $address .= "<br />";

        $acc_id = ($this->parent_id == 0 || preg_match('/^\D{3}\d{3}$/', $this->GetAccID(0))) ? " ({$this->GetAccID(0)})&nbsp;&nbsp;&nbsp;" : "";
        $office_name = urlencode($this->office_name);

        $corporate_parent = self::LookupOfficeName($this->corporate_office_id);
        $cp_account_id = self::LookupAccountingId($this->corporate_office_id);
        # Provide link to CP
        if ($this->corporate_office_id <> $this->office_id)
        {
            $cp = array('href' => "{$_SERVER['PHP_SELF']}?act=view&office_id={$this->corporate_office_id}", 'text' => "$corporate_parent ($cp_account_id)", 'alt' => 'View Corp Parent');
            $corporate_parent = BaseClass::BuildATags(array($cp));
        }

        $office_contact = $this->contacts[0]->GetId();

        $href = "templates/crm/contact_disp.php?newEntry=0&edit=1&object=Contact&entry=$office_contact&is_office=1&office_id={$this->office_id}&cbp=" . basename($_SERVER['PHP_SELF']) . "&rtn=" . urlencode($_SERVER['REQUEST_URI']) . "";
        $edit_addr_js = "InitDialog(contact_conf, '{$href}');";
        /// Was: $edit_addr_js = "InitDialog(contact_conf, '{$_SERVER['PHP_SELF']}?act=getform&object=Contact&entry=$office_contact&is_office=1&office_id={$this->office_id}');";


        $edit_detail_js = "InitDialog(office_conf, '{$_SERVER['PHP_SELF']}?act=getform&object=CorporateOffice&entry={$this->office_id}&is_office=1&office_id={$this->office_id}');";
        $new_task = "InitDialog(task_conf,'templates/sm/forms/task.php?entry=0&customer_id={$this->office_id}');";
        $iss_obj = ($this->is_office) ? 'office' : 'facility';
        $new_issue = "OpenIssueWindow('issue_edit.php?submit_action=edit&issue_id=0&office_id={$this->getId(0)}&object=$iss_obj&corporate_office_id={$this->corporate_office_id}&office_name={$office_name}');";

        $href = "templates/crm/contact_disp.php?newEntry=1&object=Contact&entry=0&office_id={$this->office_id}&is_office=0&cbp=" . basename($_SERVER['PHP_SELF']) . "&rtn=" . urlencode($_SERVER['REQUEST_URI']) . "";
        $new_contact = "InitDialog(contact_conf,'{$href}');";
        /// Was: $new_contact = "InitDialog(contact_conf,'{$_SERVER['PHP_SELF']}?act=getform&object=Contact&entry=0&office_id={$this->office_id}&is_office=0');";


        $new_sub = "InitDialog(office_conf,'{$_SERVER['PHP_SELF']}?act=getform&object=CorporateOffice&entry=0&office_id=0&parent_id={$this->office_id}');";

        $tags[] = array('alt' => 'Edit Main Address', 'text' => 'Edit Address', 'href' => '#main', 'click' => $edit_addr_js, 'class' => 'btn btn-default');
        if ($user->inPermGroup(User::$ACCOUNT_EXECUTIVE))
            $tags[] = array('alt' => 'Edit Corporate Office Detail', 'text' => 'Edit Customer', 'href' => '#main', 'click' => $edit_detail_js, 'class' => 'btn btn-default');
        $tags[] = array('alt' => 'Add Task', 'text' => 'New Task', 'click' => $new_task, 'class' => 'btn btn-default');
        $tags[] = array('alt' => "Add New CS Issue", 'text' => 'New CS Issue', 'class' => 'lft', 'click' => $new_issue, 'class' => 'btn btn-default');
        $tags[] = array('alt' => "Add New Contact", 'text' => 'New Contact', 'class' => 'lft', 'click' => $new_contact, 'class' => 'btn btn-default');
        $tags[] = array('alt' => "Add New Subdivision", 'text' => 'New Subdivistion', 'class' => 'lft', 'click' => $new_sub, 'class' => 'btn btn-default');

        $tags = BaseClass::BuildATags($tags);

        # Create the html
        $customer_section = "
	<form name='company_form' action='{$_SERVER['PHP_SELF']}' method='get'>
		{$this->getId(1)}
		<input type='hidden' id='object' name='object' value='office' />
		<input type='hidden' name='act' value='view' />
	</form>
	<div id='affix_cont' data-spy='affix' data-offset-top='55'>
		<div class='box box-solid box-primary' style='margin-bottom:0;'>
			<div class='box-header' data-toggle='collapse' data-target='#home_cont'>
				<h4 class='box-title'>{$this->getOfficeName()} $acc_id</h4>
				<button class='btn btn-box-tool pull-right' type='button' onclick='ToggleAffix();' data-toggle='tooltip' data-placement='left' title='Lock/Unlock information bar possition'><i id='affix_btn' class='fa fa-lock'></i></button>
				<button class='btn btn-box-tool pull-right' type='button' data-widget='collapse' data-toggle='tooltip' data-placement='left' title='Show/Hide information bar'><i class='fa fa-minus'></i></button>
			</div>
			<div id='cust_info' class='box-body'>
				<div class='inblk' style='width: 200px; vertical-align: top;'>
					<div style='float: right;'>
						<input type='button' onclick=\"MapWindow=window.open('map.php?office={$this->office_id}','Office_Map','width=665,height=560,toolbar=no,scrollbars=yes,resizable=yes')\" value='Map' />
					</div>
					<div class='address' style='font-size:9pt; font-weight:normal;'>
						$address
					</div>
				</div>
				<div class='inblk' style='margin-left: 20px;'>
					<table class='table face table-condensed'>
						<tr>
							<th>Account Manager:</th>
							<td>{$this->getAccountExecutiveName(0)}</td>
							<th>Orientation:</th>
							<td>$orientation</td>
						</tr>
						<tr>
							<th>Corp. Parent:</th>
							<td>$corporate_parent</td>
							<th>Nursing Homes:</th>
							<td>{$this->getNursingHomes(0)}</td>
						</tr>
						<tr>
							<th>Active:</th>
							<td>{$this->GetSStatus()}</td>
						</tr>
					</table>
				</div>
			</div>
			<div class='box-footer filter'>
				<b>Customer Options</b>:
				$tags
			</div>
		</div>
	</div>";

        return $customer_section;
    }

    /**
     * Get office contact list
     *
     * @return string contact rows for the office
     */
    private function ContactSection()
    {
        global $user;

        $this->LoadContacts();
        $count = count($this->contacts) - 1;

        $contact_list = $this->contacts;
        $_PRIMARY_CONTACT = false;
        $_OPP_CONTACTS = false;
        $_FAC_CONTACTS = false;

        $row_html = "";
        $contact_rows = $this->ContactTable($row_html, $count);

        $iso = ($this->is_office) ? 1 : 0;

        /// For office id
        $officehref = "templates/crm/contact_disp.php?newEntry=1&object=Contact&entry=0&office_id={$this->office_id}&is_office=0&cbp=" . basename($_SERVER['PHP_SELF']) . "&rtn=" . urlencode($_SERVER['REQUEST_URI']) . "";

        /// For facility id
        $facilityhref = "templates/crm/contact_disp.php?newEntry=1&edit=1&object=Contact&entry=0&facility_id={$this->getId(0)}&is_office=0&cbp=" . basename($_SERVER['PHP_SELF']) . "&rtn=" . urlencode($_SERVER['REQUEST_URI']) . "";

        # Contact Sub Menu
        $con_count = count($this->contacts) - 1;
        if ($iso)
            $click = "InitDialog(contact_conf,'{$officehref}');";
        else
            $click = "InitDialog(contact_conf,'{$facilityhref}');";
        $menu[0] = array('text' => 'New Contact', 'class' => 'btn btn-default btn-sm', 'click' => $click, 'alt' => "Add New Contact");
        $contact_sub = BaseClass::BuildATags($menu);

        $html = "<div class='flx_cont'>
			<div id='contact_cont' class='box box-primary'>
				<div id='contact_hdr' class='box-header'>
					<h4 class='box-title'>Contacts</h4>
					<div class='box-tools pull-right'>
						<button type='button' class='btn btn-box-tool' data-widget='collapse'>
							<i class='fa fa-minus'></i>
						</button>
					</div>
				</div>
				<div id='contact_disp' class='box-body'>
					$contact_rows
				</div>
			</div>
		</div>";

        return $html;
    }

    /**
     * Get office contact html
     *
     * @param string
     * @param int
     *
     * @return string contact rows for the office
     */
    public function ContactTable(&$html, &$count)
    {
        global $user;

        $this->LoadContacts();
        $count = count($this->contacts) - 1;

        $contact_list = $this->contacts;
        if ($this->is_office)
            $_PRIMARY_CONTACT = true;
        else
            $_PRIMARY_CONTACT = false;
        $_OPP_CONTACTS = false;
        $_FAC_CONTACTS = false;
        $_USE_TABLE = true;
        if ($this->is_office)
        {
            $con_fac_id = $this->office_id;
        }
        else
        {
            $con_fac_id = $this->getId(0);
        }

        $_REQUEST['facility_id'] = $con_fac_id;
        $_REQUEST['updatetbl'] = 2;
        $html = "<script type='text/javascript'>
$(document).ready(function() {
	InitContactDT();
});
</script>
		<input type='hidden' id='contact_facility_id' name='contact_facility_id' value='{$con_fac_id}'>
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

        if (!$this->is_office)
        {
            $html .= $this->FindSameAddress($this->contacts[0]);
        }

        return $html;
    }

    /**
     * Get html for contract lines
     *
     * @param string
     * @param integer
     */
    public function ContractLines(&$html, &$count)
    {
        $count = "";
        $html = "";
        $rows = "";

        $rc = 'dt-tr-on';
        if (isset ($_REQUEST['contract_id']))
        {
            $sth = $this->dbh->prepare("SELECT
				i.line_num,
				i.item_code,
				p.name as item_name,
				i.asset_id,
				i.amount,
				i.date_shipped,
				i.date_removed,
  				a.serial_num
			FROM contract_line_item i
			LEFT JOIN products p on i.item_code = p.code
			LEFT JOIN lease_asset a ON i.asset_id = a.id
			WHERE contract_id = ?
			ORDER BY i.line_num");
            $sth->bindValue(1, $_REQUEST['contract_id'], PDO::PARAM_INT);
            $sth->execute();
            while ($item = $sth->fetch(PDO::FETCH_OBJ))
            {
                $count++;
                $ia = ($item->date_removed) ? " faded" : "";
                $rows .= "<tr class='$rc{$ia}'>
					<td align='center'>{$item->line_num}</td>
					<td align='center'>{$item->item_code}</td>
					<td align='left'>{$item->item_name}</td>
					<td align='center'>{$item->date_shipped}</td>
					<td align='center'>{$item->serial_num}</td>
					<td align='right'>{$item->amount}</td>
				</tr>";

                $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
            }

            $html = "<table class='dt' width='100%' cellspacing='0' cellpadding='4' border='1' style='clear:none;'>
				<tbody>
					<tr>
						<th class='hdr'>Line #</th>
						<th class='hdr'>Item Code</th>
						<th class='hdr'>Name</th>
						<th class='hdr'>Shipped</th>
						<th class='hdr'>Serial num</th>
						<th class='hdr'>Unit Price</th>
					</tr>
					$rows
				</tbody>
			</table>";
        }

        $html = "<div class='nested'>$html</div>";

        return $html;
    }

    /**
     * Supply html for the Contract section
     *
     * @return string
     */
    public function ContractSection()
    {
        $section = "<div class='flx_cont'>
			<div id='contract_cont' class='box box-primary'>
				<div id='contract_hdr' class='box-header' data-toggle='collapse' data-target='#contract_disp' style='cursor:pointer;'>
					<i class='fa fa-minus pull-right'></i>
					<h4 class='box-title'>Contracts</h4>
					<span class='badge' id='con_sec_badge'></span>
				</div>
				<div id='contract_disp' class='box-body collapse in'>
					<div class='on'>Loading...</div>
					<script type='text/javascript'>$(function () { CRMLoader.LoadContent('contract', false); });</script>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply table for the Contract section
     * @param tring
     * @param int
     * @return string
     */
    public function ContractTable(&$html, &$count)
    {
        global $this_app_name, $preferences;

        $defaults = array('page' => 1, 'sort_by' => "c.date_install", 'dir' => "ASC", 'c_show' => "a", 'c_type' => 'a');
        SessionHandler::Update('crm', 'con', $defaults);
        $filter = $_SESSION['crm']['con'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 1;
        $sec_conf->disp = 'contract_disp';
        $sec_conf->nav_badge = 'contract_nav_badge';
        $sec_conf->sec_badge = 'contract_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=ContractTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_contract_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));
        $cs = count($sec_conf->cols) + 1;

        $this->LoadContracts($filter);

        $sec_conf->row_count = count($this->contracts);

        ## Init totals for Lease, Purchase and Loaner
        $t['Lease'] = array('count' => 0, 'active' => 0, 'revenue' => 0, 'amount' => 0);
        $t['Purchase'] = array('count' => 0, 'active' => 0, 'revenue' => 0, 'amount' => 0);
        $t['Loaner'] = array('count' => 0, 'active' => 0, 'revenue' => 0, 'amount' => 0);
        $t['Supply'] = array('count' => 0, 'active' => 0, 'revenue' => 0, 'amount' => 0);

        $rc = 'dt-tr-on';
        foreach ($this->contracts as $con)
        {
            $tag['text'] = $con->id_contract;
            $tag['alt'] = "View Contract Detail";
            $tag['href'] = "contract_maintenance.php?facility_id={$con->id_facility}&contract_id={$con->id_contract}";
            $tag['target'] = "_blank";
            $contract_id = $con->id_contract;
            $con->id_contract = BaseClass::BuildATags(array($tag));
            $tag['text'] = "View";
            $menu = BaseClass::BuildATags(array($tag));
            $line_url = "corporateoffice.php?act=db&obj=section&method=ContractLines&contract_id={$contract_id}";

            # Need to page results useing a window function
            # for true total. This is return in total_records field.
            $count = $con->total_records;
            $sec_conf->count = $con->total_records;

            $t[$con->con_type]['count']++;

            if ($con->date_cancellation == '' && $con->con_type == 'Lease')
            {
                $t[$con->con_type]['active']++;
                $t[$con->con_type]['revenue'] += $con->revenue;
                $t[$con->con_type]['amount'] += $con->amount;
                $ia = "";
            }
            else
            {
                $ia = " faded";
            }

            $sec_conf->rows .= "<tr class='{$rc}{$ia}' ondblclick=\"window.location='{$tag['href']}';\">";
            foreach ($sec_conf->cols as $col)
            {
                $sec_conf->rows .= "<td class='{$col->cls}'>{$con->{$col->key} }</td>";
            }

            $sec_conf->rows .= "<td class='nested' style='text-align:right;'>
					<div class='submenu'>
						$menu<span class='close_body' id='contract_btn_{$contract_id}'
							onclick=\"if (SetContractState($contract_id)) FillSection('$line_url','contract_bdy_{$contract_id}','null','null');\">&nbsp;&nbsp;&nbsp;&nbsp;</span>
					</div>
				</td>
			</tr>
			<tr class='dt-tr-on'>
				<td colspan='$cs' style='padding: 0px;' id='contract_bdy_{$contract_id}' class='short'></td>
			</tr>";

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        foreach ($t as $type => $sum)
        {
            $revenue = number_format($sum['revenue'], 2);
            $amount = number_format($sum['amount'], 2);
            $sec_conf->rows .= "<tr class='total'>
				<td colspan=$cs style='text-align:left;'>
					$type - Total: {$sum['count']},&nbsp;&nbsp;&nbsp;&nbsp;
					Active: {$sum['active']},&nbsp;&nbsp;&nbsp;&nbsp;
					Revenue: $revenue,&nbsp;&nbsp;&nbsp;&nbsp;
					Sales:$amount
				</td>
			</tr>";
        }

        if ($count == 0)
        {
            $sec_conf->rows = "<tr class='dt-tr-on'><td colspan=$cs style='text-align:center;'>No Contracts found.</td></tr>";
        }


        ## define attributes used to query for a page of data
        $show_a = ($filter['c_show'] == "a") ? "checked" : "";
        $show_b = ($filter['c_show'] == "b") ? "checked" : "";
        $show_c = ($filter['c_show'] == "c") ? "checked" : "";

        $type_l = ($filter['c_type'] == "l") ? "checked" : "";
        $type_p = ($filter['c_type'] == "p") ? "checked" : "";
        $type_n = ($filter['c_type'] == "n") ? "checked" : "";
        $type_s = ($filter['c_type'] == "s") ? "checked" : "";
        $type_a = ($filter['c_type'] == "a") ? "checked" : "";
        $base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=ContractTable&is_office=$iso&entry={$this->office_id}";

        $show_clicka = "FillSection('$base_url&page=1&c_show=a','contract_disp','contract_nav_badge','contract_sec_badge');";
        $show_clickb = "FillSection('$base_url&page=1&c_show=b','contract_disp','contract_nav_badge','contract_sec_badge');";
        $show_clickc = "FillSection('$base_url&page=1&c_show=c','contract_disp','contract_nav_badge','contract_sec_badge');";

        $type_clickl = "FillSection('$base_url&page=1&c_type=l','contract_disp','contract_nav_badge','contract_sec_badge');";
        $type_clickp = "FillSection('$base_url&page=1&c_type=p','contract_disp','contract_nav_badge','contract_sec_badge');";
        $type_clickn = "FillSection('$base_url&page=1&c_type=n','contract_disp','contract_nav_badge','contract_sec_badge');";
        $type_clicks = "FillSection('$base_url&page=1&c_type=s','contract_disp','contract_nav_badge','contract_sec_badge');";
        $type_clicka = "FillSection('$base_url&page=1&c_type=a','contract_disp','contract_nav_badge','contract_sec_badge');";

        $sec_conf->filter = "
		<div class='filter nested' align='left'>
			<span class='lbl'>Show:</span>
			<input type='radio' name='c_show' value='a' id='c_show_a' onclick=\"$show_clicka\" $show_a /><label for='c_show_a'>Active</label>
			<input type='radio' name='c_show' value='c' id='c_show_c' onclick=\"$show_clickc\" $show_c /><label for='c_show_c'>Cancelled</label>
			<input type='radio' name='c_show' value='b' id='c_show_b' onclick=\"$show_clickb\" $show_b /><label for='c_show_b'>All</label>
			<span class='lbl'>Type:</span>
			<input type='radio' name='c_type' value='l' id='c_type_l' onclick=\"$type_clickl\" $type_l /><label for='c_type_l'>Lease</label>
			<input type='radio' name='c_type' value='p' id='c_type_p' onclick=\"$type_clickp\" $type_p /><label for='c_type_p'>Purchase</label>
			<input type='radio' name='c_type' value='s' id='c_type_s' onclick=\"$type_clicks\" $type_s /><label for='c_type_n'>Supply</label>
			<input type='radio' name='c_type' value='n' id='c_type_n' onclick=\"$type_clickn\" $type_n /><label for='c_type_n'>Loaner</label>
			<input type='radio' name='c_type' value='a' id='c_type_a' onclick=\"$type_clicka\" $type_a /><label for='c_type_a'>All</label>
		</div>";

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    /**
     * Copy form inputs into class properties
     *
     * @param array $new
     */
    public function copyFromArray($new = array())
    {
        if (isset ($new['parent_id']))
            $this->parent_id = (int) $new['parent_id'];

        if (isset ($new['corporate_office_id']))
            $this->corporate_office_id = (int) $new['corporate_office_id'];

        if (isset ($new['office_name']))
            $this->office_name = trim($new['office_name']);

        if (isset ($new['account_id']))
            $this->account_id = trim($new['account_id']);

        if (isset ($new['status']))
            $this->status = (int) $new['status'];

        if (isset ($new['orientation']))
            $this->orientation = (int) $new['orientation'];

        if (isset ($new['nursing_homes']))
            $this->nursing_homes = (int) $new['nursing_homes'];

        if (isset ($new['acp_nursing_homes']))
            $this->acp_nursing_homes = (int) $new['acp_nursing_homes'];

        if (isset ($new['account_executive']))
            $this->account_executive = (int) $new['account_executive'];

        # We always have one main office contact which we need to update
        if (is_array($this->contacts))
            $this->contacts[0]->copyFromArray($new);

        # We only save changes from the default detail list
        # Either have modified old data or newly changed data

        # New detail attributes
        if (isset ($new['new_detail']))
        {
            foreach ($new['new_detail'] as $section => $section_ary)
            {
                foreach ($section_ary as $attribute => $attribute_ary)
                {
                    foreach ($attribute_ary as $field => $type_ary)
                    {
                        foreach ($type_ary as $type => $value)
                        {
                            $section = htmlentities($section, ENT_QUOTES, 'UTF-8');
                            $attribute = htmlentities($attribute, ENT_QUOTES, 'UTF-8');
                            $field = htmlentities($field, ENT_QUOTES, 'UTF-8');
                            $this->detail[$section][$attribute][$field]['type'] = $type;
                            $this->detail[$section][$attribute][$field]['value'] = $value;
                        }
                    }
                }
            }
        }
        # Previously saved detail
        if (isset ($new['detail']))
        {
            foreach ($new['detail'] as $section => $section_ary)
            {
                foreach ($section_ary as $attribute => $attribute_ary)
                {
                    foreach ($attribute_ary as $field => $id_ary)
                    {
                        foreach ($id_ary as $id => $value)
                        {
                            $section = htmlentities($section, ENT_QUOTES, 'UTF-8');
                            $attribute = htmlentities($attribute, ENT_QUOTES, 'UTF-8');
                            $field = htmlentities($field, ENT_QUOTES, 'UTF-8');
                            $this->detail[$section][$attribute][$field]['id'] = $id;
                            $this->detail[$section][$attribute][$field]['value'] = $value;
                        }
                    }
                }
            }
        }
    }


    /**
     * Copy a New Office from its parent
     *
     * @param integer $parent_id office id of the parent office
     */
    public function copyFromParent($parent_id)
    {
        $parent_office = new CorporateOffice($parent_id);
        $this->parent_id = $parent_id;
        $this->corporate_office_id = $parent_office->getCorporateOfficeId();
        $this->office_name = $parent_office->getOfficeName() . ' New Office';
        $this->status = $parent_office->getStatus();
        $this->orientation = $parent_office->getOrientation();
        $this->account_executive = $parent_office->getAccountExecutive();
        $this->breadcrumb = $parent_office->getBreadCrumb(1) . "&nbsp;&gt&nbsp;{$this->office_name}";
        $this->contacts[0] = $parent_office->getContact(0);
        if ($this->contacts[0])
            $this->contacts[0]->setVar('contact_id', null); // Copy everything but db ID
    }

    /**
     * Find all the offices within the company
     *
     * @param array $match array of office_ids to be selected by default
     * @return string html options tags suitable for a select input of all
     * offices within the company/corporate
     */
    public function CreateCompanyList($match = array())
    {
        $company_list = "";

        if ($this->corporate_office_id > 0)
        {
            $sth = $this->dbh->prepare("SELECT
				office_id, office_name, account_id
			FROM corporate_office
			WHERE corporate_office_id = {$this->corporate_office_id}
			AND status IN (0,1,2)
			ORDER by parent_id, office_name");
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $sel = in_array($row['office_id'], $match) ? "selected" : "";
                $company_list .= "<option value='{$row['office_id']}' {$sel}>{$row['office_name']} ({$row['account_id']})</option>\n";
            }
        }
        return $company_list;
    }

    /**
     * @param integer $office_id
     * @param array $default
     * @param string $indent
     */
    public static function createSubdivisionList($office_id = 0, $default = array(), $indent = "")
    {
        $dbh = DataStor::getHandle();
        $list = '';

        $default_ary = (is_array($default)) ? $default : array($default);

        if (!$office_id || $office_id == 'All')
            $office_id = 0;

        $sth = $dbh->prepare("
			SELECT office_id, office_name FROM corporate_office
			WHERE parent_id = $office_id AND status <= 1
			ORDER BY office_name");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_NUM))
        {
            list($office_id, $office_name) = $row;

            $selected = (in_array($office_id, $default_ary)) ? 'selected' : '';
            $list .= "<option value=\"{$office_id}\" {$selected}>{$indent}" . htmlentities($office_name, ENT_QUOTES) . "</option>\n";

            # Add the child with an indent
            $list .= self::createSubdivisionList($office_id, $default_ary, $indent . "&nbsp;&nbsp;");
        }

        return $list;
    }



    /**
     * Create Company and Subdivision List set to track consultant hours
     */
    static public function CreateTBHCompanyList($default_id = null)
    {
        $dbh = DataStor::getHandle();

        $default_ary = (is_array($default_id)) ? $default_id : array($default_id);

        $office_list = "";
        $sth = $dbh->query("SELECT DISTINCT
			o.corporate_office_id, o.office_name
		FROM corporate_office o
		INNER JOIN corporate_office_detail d ON o.office_id = d.office_id
		WHERE field = 'Track Consultant Hours' AND value = '1'
		ORDER BY o.office_name");
        while (list($company_id, $company_name) = $sth->fetch(PDO::FETCH_NUM))
        {
            $sel = (in_array($company_id, $default_ary)) ? "selected" : "";
            $company_name = htmlentities($company_name, ENT_QUOTES);
            $office_list .= "<option value='$company_id' $sel>$company_name</option>";
            $office_list .= self::createSubdivisionList($company_id, $default_id, "&nbsp;&nbsp;");
        }

        return $office_list;
    }

    /**
     * Create Company and Subdivision List set to track consultant hours
     */
    static public function CreateTBHCompanyLocArray()
    {
        $dbh = DataStor::getHandle();

        $office_list = "var office_loc = new Array();\n";
        $sth = $dbh->query("SELECT
			o.office_id, o.office_name, c.city, c.state
		FROM contact c
		INNER JOIN corporate_office_contact_join j on c.contact_id = j.contact_id
		INNER JOIN corporate_office o on j.office_id = o.office_id
		WHERE c.is_office = true
		AND o.corporate_office_id IN (
			SELECT office_id
			FROM corporate_office_detail
			WHERE office_id > 0 AND field = 'Track Consultant Hours' AND value = '1'
		)
		ORDER BY o.office_id");
        while (list($office_id, $office_name, $city, $state) = $sth->fetch(PDO::FETCH_NUM))
        {
            $office_name = htmlentities(trim($office_name), ENT_QUOTES);
            $city = htmlentities(trim($city), ENT_QUOTES);
            $state = htmlentities(trim($state), ENT_QUOTES);
            if ($city && $state)
                $office_list .= "office_loc[$office_id] = '$city, $state';\n";
            else if ($city)
                $office_list .= "office_loc[$office_id] = '$city';\n";
            else if ($state)
                $office_list .= "office_loc[$office_id] = '$state';\n";
            else
                $office_list .= "office_loc[$office_id] = '$office_name';\n";

        }

        return $office_list;
    }

    /**
     * Deletes this corporate office from the database.
     */
    public function delete()
    {
        global $user;

        # Do not allow deletion of top most office
        if ($this->parent_id == 0)
        {
            ErrorHandler::showError('Unable to delete top most Corporate Office records.', 'Top most corporate office', ErrorHandler::$FOOTER, $user);
            exit;
        }

        /*
         * First delete our children
         * This is a recursive call. It will traverse the tree and remove all children
         */
        $sth = $this->dbh->prepare('SELECT office_id FROM corporate_office WHERE parent_id = ?');
        $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
        $sth->execute();
        /*while($row = $sth->fetch(PDO::FETCH_ASSOC))
              {
                  $child = new CorporateOffice($row['office_id']);
                  $child->delete();
              }*/

        $facilities_sth = $this->dbh->query("SELECT id
											  FROM facilities
											  WHERE parent_office = '{$this->account_id}'
											  OR corporate_parent = '{$this->account_id}'");

        if ($sth->rowCount() == 0 && $facilities_sth->rowCount() == 0)
        {
            /*
             * Sep 27 enable true deletion
             *
             * All we want to do is change the status of the office to inactive
             * Dont acctualy remove any DB records
             */
            //			$this->change('status', 0);

            /*
             * Remove contact records this will also remove from the join table
             */
            foreach ($this->contacts as $contact)
            {
                $contact->delete();
            }

            /*
             * Remove office detail
             */
            $sth = $this->dbh->prepare("DELETE FROM corporate_office_detail WHERE office_id = ?");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();

            /*
             * Remove issue records
             * First the notes then the issues
             */
            $sth = $this->dbh->prepare("DELETE FROM note WHERE issue_id IN (SELECT issue_id FROM issue WHERE office_id = ? AND office_issue IS TRUE)");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();

            $sth = $this->dbh->prepare("DELETE FROM issue WHERE office_id = ? AND office_issue IS TRUE");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();

            /*
             * Reset the facilities parent_office to corporate_parent if assigned here
             */
            $sth = $this->dbh->prepare("UPDATE facilities SET parent_office = corporate_parent WHERE parent_office =  ?");
            $sth->bindValue(1, $this->account_id, PDO::PARAM_STR);
            $sth->execute();

            /*
             * Finally remove from corporate_office
             */
            $sth = $this->dbh->prepare("DELETE FROM corporate_office WHERE office_id = ?");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
        }
    }

    /**
     * Supply html for the Detail section
     *
     * @return string
     */
    public function DetailSection()
    {
        $entry = $this->corporate_office_id;

        $section = "<div class='flx_cont'>
			<div id='detail_cont' class='box box-primary'>
				<div id='detail_hdr' class='box-header' data-toggle='collapse' data-target='#detail_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Detail</h4>
				</div>
				<div id='detail_disp' class='box-body collapse'>
					<form id='co_detail' name='co_detail' action='{$_SERVER['PHP_SELF']}' method='POST'>
					<input type='hidden' name='act' value='save' />
					<input type='hidden' name='object' value='CorporateOffice' />
					<input type='hidden' name='entry' value='{$entry}' />
					<input type='hidden' name='office_id' value='{$entry}' />
					<input type='hidden' name='parent' value='detail_disp' />
					<input type='hidden' name='save_detail' value='1' />
					<div class='hdr'>
						<input type='button' name='save_1' value='Save' onclick=\"SubmitForm('{$_SERVER['PHP_SELF']}', 'co_detail')\" />
					</div>
					{$this->GetDetail(0)}
					<div class='hdr'>
						<input type='button' name='save_2' value='Save' onclick=\"SubmitForm('{$_SERVER['PHP_SELF']}', 'co_detail')\" />
					</div>
					</form>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply html for the Equipment section
     *
     * @return string
     */
    public function EquipmentSection()
    {
        $equipment = array();
        foreach ($this->equipment as $dev)
        {
            if ($dev->has_pmt)
            {
                $equipment[] = array('asset_id' => $dev->asset_id);
            }
        }

        $cert_btn = "";
        if (!empty ($equipment))
        {
            $equipment_js = json_encode($equipment);
            $cert_btn = "
			<div class='box-header with-border fltr txar'>
				<button class='btn btn-xs btn-default' onClick='DownloadCerts({$equipment_js});'>Download Certs</button>
			</div>";
        }

        $section = "<div class='flx_cont'>
			<div id='equip_cont' class='box box-primary'>
				<div id='equip_hdr' class='box-header' data-toggle='collapse' data-target='#equip_disp' style='cursor:pointer;'>
					<i class='fa fa-minus pull-right'></i>
					<h4 class='box-title'>Equipment</h4>
					<span class='badge' id='equip_sec_badge'></span>
				</div>
				$cert_btn
				<div id='equip_disp' class='box-body collapse in'>
					<div class='on'>Loading...</div>
					<script type='text/javascript'>$(function () { CRMLoader.LoadContent('equip', false); });</script>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply table for the Equipment section
     *
     * @return string
     */
    public function EquipmentTable(&$html, &$count)
    {
        global $this_app_name, $preferences;

        $date_format = $preferences->get('general', 'dateformat');
        $defaults = array('page' => 1, 'sort_by' => "asset_id", 'dir' => "ASC", "e_model" => "all", "e_status" => "all");
        SessionHandler::Update('crm', 'equip', $defaults);
        $filter = $_SESSION['crm']['equip'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 0;
        $sec_conf->disp = 'equip_disp';
        $sec_conf->nav_badge = 'equip_nav_badge';
        $sec_conf->sec_badge = 'equip_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=EquipmentTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_equip_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        $this->LoadEquipment($filter);
        $warn_items = $this->MismatchedContractItems();

        $sec_conf->row_count = count($this->equipment);
        $rc = 'dt-tr-on';
        foreach ($this->equipment as $dev)
        {
            $tag['text'] = $dev->asset_id;
            $tag['alt'] = "View Asset Record";
            $tag['href'] = "asset_tracking.php?act=View&lease_asset_id={$dev->asset_id}";
            $tag['target'] = "_blank";
            $dev->asset_id = BaseClass::BuildATags(array($tag));

            # Need to page results useing a window function
            # for true total. This is return in total_records field.
            $count = $dev->total_records;
            $sec_conf->count = $dev->total_records;

            ## Show non program equipment as inactive
            $ia = ($dev->cust_id == $dev->location_id) ? "" : " alert";
            if (in_array($tag['text'], $warn_items))
                $ia = " alert";

            $sec_conf->rows .= "<tr class='{$rc}{$ia}' ondblclick=\"window.location='{$tag['href']}';\">";
            foreach ($sec_conf->cols as $col)
            {
                $sec_conf->rows .= "<td class='{$col->cls}'>{$dev->{$col->key} }</td>";
            }
            $sec_conf->rows .= "</tr>";

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        if ($count == 0)
        {
            $cs = count($sec_conf->cols);
            $sec_conf->rows = "<tr class='dt-tr-on'><td colspan=$cs style='text-align:center;'>No Equipment found.</td></tr>";
        }

        ## define attributes used to query for a page of data
        $base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=EquipmentTable&entry={$this->office_id}&is_office=$iso";
        $m_options = Forms::createEquipmentList($filter['e_model'], true);
        $s_ary = LeaseAssetTransaction::getStatusOptions();
        $s_options = Forms::createOptionListFromArrayNoKey($s_ary, $filter['e_status']);

        $sec_conf->filter = "<div class='filter nested' align='left'>
			<span class='lbl'>Model:</span>
			<select name='e_model' onchange=\"FillSection('$base_url&page=1&e_model='+this.value,'equip_disp','equip_nav_badge','equip_sec_badge');\">
				<option value='all'>--All--</option>
				$m_options
			</select>
			<span class='lbl'>Status:</span>
			<select name='e_status' onchange=\"FillSection('$base_url&page=1&e_status='+this.value,'equip_disp','equip_nav_badge','equip_sec_badge');\">
				<option value='all'>--All--</option>
				$s_options
			</select>
  		</div>";

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    /**
     * Determine if the account id exists in corporate_office table
     *
     * @param string
     * @return integer
     */
    static public function Exists($account_id)
    {
        $dbh = DataStor::getHandle();

        $sth = $dbh->prepare("SELECT office_id FROM corporate_office WHERE account_id = ?");
        $sth->bindValue(1, $account_id, PDO::PARAM_STR);
        $sth->execute();
        $exits = $sth->fetchColumn();

        return $exits;
    }

    /**
     * View is broken into sections.
     * This will show company and office level properties
     *
     * @return string html to be displayed
     */
    public function FacilityInfo()
    {
        global $user;

        # For potenialy long strings check length only allow 30 chars
        # Create mailto link for the email address
        $email_link = $this->GetEmail();
        $email_disp = (strlen($email_link) > 30) ? substr($email_link, 0, 30) . "..." : $email_link;
        $email_link = ($email_link) ? "<a href='mailto:{$email_link}'>{$email_disp}</a>" : "";
        $iso = ($this->is_office) ? 1 : 0;

        # Attempt to show address neatly
        $address = "";
        if ($this->GetAddress1())
            $address .= $this->GetAddress1() . "<br/>";
        if ($this->GetAddress2())
            $address .= $this->GetAddress2() . "<br/>";
        if ($this->GetCity())
            $address .= $this->GetCity();
        if ($this->GetState())
            $address .= ", " . $this->GetState();
        if ($this->GetZip())
            $address .= " " . $this->GetZip();
        $address .= "<br/>Phone: " . $this->GetPhone();
        $address .= "<br/>Fax: " . $this->GetFax();
        if ($email_link)
            $address .= "<br/>Email: $email_link";
        $address .= "<br />";

        $acc_id = " ({$this->GetAccID(0)})&nbsp;&nbsp;&nbsp;";
        $office_name = urlencode($this->office_name);

        $corporate_parent = self::LookupOfficeName($this->corporate_office_id);
        $cp_account_id = self::LookupAccountingId($this->corporate_office_id);
        # Provide link to CP
        $cp = array('href' => "{$_SERVER['PHP_SELF']}?act=view&office_id={$this->corporate_office_id}", 'text' => "$corporate_parent ($cp_account_id)", 'alt' => 'View Corp Parent');
        $corporate_parent = BaseClass::BuildATags(array($cp));

        $edit_fac = "InitDialog(fac_conf,'{$_SERVER['PHP_SELF']}?act=getform&object=Facility&entry={$this->getId(0)}&office_id={$this->getId(0)}&is_office=0');";
        $iss_obj = ($this->is_office) ? 'office' : 'facility';
        $new_issue = "OpenIssueWindow('issue_edit.php?submit_action=edit&issue_id=0&office_id={$this->getId(0)}&object=$iss_obj&corporate_office_id={$this->corporate_office_id}&office_name={$office_name}');";

        $href = "templates/crm/contact_disp.php?newEntry=1&edit=1&object=Contact&entry=0&facility_id={$this->getId(0)}&is_office=0&cbp=" . basename($_SERVER['PHP_SELF']) . "&rtn=" . urlencode($_SERVER['REQUEST_URI']) . "";
        $new_contact = "InitDialog(contact_conf,'{$href}');";
        /// Was: $new_contact = "InitDialog(contact_conf,'{$_SERVER['PHP_SELF']}?act=getform&object=Contact&entry=0&facility_id={$this->getId(0)}&is_office=0');";

        # Question Menu
        $new_question = "GetAsync('{$_SERVER['PHP_SELF']}?act=save&object=CustomerQuestion&parent=question_disp&entry=0&customer_id={$this->office_id}&corporate_office_id={$this->corporate_office_id}&is_office=0', SetGoalContents);";

        # New Goal
        $type_id = ($this->is_office) ? CustomerGoal::$EXPECTATION_TYPE : CustomerGoal::$OPERATIONAL_TYPE;
        $new_goal = "GetAsync('{$_SERVER['PHP_SELF']}?act=save&object=CustomerGoal&parent=goal_disp&entry=0&type_id=$type_id&customer_id={$this->office_id}&corporate_office_id={$this->corporate_office_id}&is_office=0', SetGoalContents);";

        $new_cpc_rc = "window.open('short_visit.php?react=newEvent&fid={$this->office_id}&type=31&act=edit', 'CPC_FIELD_REMOTE_CONSULTATION','status=no,height=800,width=900,resizable=yes,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no')";
        $new_rcs = "window.open('short_visit.php?react=newEvent&fid={$this->office_id}&type=32&act=edit', 'REMOTE_CLINICAL_SERVICE','status=no,height=800,width=900,resizable=yes,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no')";

        $tags = array(
            array('alt' => "Edit Facility", 'text' => 'Edit Facility', 'class' => 'btn btn-default btn-sm', 'click' => $edit_fac),
            array('alt' => "Add New CS Issue", 'text' => 'New CS Issue', 'class' => 'btn btn-default btn-sm', 'click' => $new_issue),
            array('alt' => "Add New Contact", 'text' => 'New Contact', 'class' => 'btn btn-default btn-sm', 'click' => $new_contact),
            array('alt' => "Add New Question", 'text' => 'New Question', 'class' => 'btn btn-default btn-sm', 'click' => $new_question),
            array('text' => 'New Goal', 'class' => 'btn btn-default btn-sm', 'click' => $new_goal, 'alt' => "Add New Goal"),
            array('text' => 'New CPC-Field Remote Consultation', 'class' => 'btn btn-default btn-sm', 'click' => $new_cpc_rc, 'alt' => "Add New CPC-Field Remote Consultation"),
            array('text' => 'New Remote Clinical Service', 'class' => 'btn btn-default btn-sm', 'click' => $new_rcs, 'alt' => "Add New Remote Clinical Service"),
            array('alt' => "5 Start Trend", 'text' => "<i class='fa fa-line-chart'></i> Trend", 'class' => 'btn btn-default btn-sm', 'click' => "window.open('customerstanding.php?facility_id={$this->office_id}&amp;year=all&amp;chart_type=bar', target='_blank')"),
            array('alt' => "PAT", 'text' => "<i class='fa fa-file-text'></i> PAT", 'class' => 'btn btn-default btn-sm', 'target' => '_blank', 'href' => "pat.php?facility_id={$this->office_id}"),
            array('alt' => "MBP", 'text' => "<i class='fa fa-file-text-o'></i> MBP", 'class' => 'btn btn-default btn-sm', 'target' => '_blank', 'href' => "mbp.php?facility_id={$this->office_id}")
        );

        # Add links to the install checklist
        $tags = BaseClass::BuildATags($tags);
        $cklist = InstallChecklist::AddLinks($this->office_id, $this->is_office);
        if (!empty ($cklist) && is_array($cklist))
        {
            foreach ($cklist as $lnk)
            {
                $tags .= BaseClass::BuildATags(array(array('alt' => "Install Checklist", 'text' => "Checklist", 'class' => 'btn btn-default btn-sm', 'target' => '_blank', 'href' => $lnk)));
            }
        }

        $rehab_provider = $this->rehab_provider;
        $provnum = $this->provnum;
        $rehab_type = $this->rehab_type;
        $med_a_beds = $this->med_a_beds;
        $med_b_beds = $this->med_b_beds;
        $other_beds = $this->other_beds;

        $has_dock = ($this->has_dock) ? 'Has Dock  &nbsp;&nbsp;' : '';
        $liftgate_required = ($this->requires_liftgate) ? ' &nbsp;&nbsp; Lift Gate Required' : '';
        $inside_delivery_required = ($this->requires_inside_delivery) ? ' &nbsp;&nbsp; Inside Delivery Required' : '';

        $display_special_shiiping_instructions = "NONE";
        if ($has_dock || $liftgate_required || $inside_delivery_required)
            $display_special_shiiping_instructions = "{$has_dock}{$liftgate_required} &nbsp;&nbsp; {$inside_delivery_required}";

        if (in_array($this->site_type_abv, array('OT', 'HH', 'Sport', 'College', 'Individual')))
        {
            $rehab_provider = 'N/A';
        }

        $provnum = $this->provnum;
        if (in_array($this->site_type_abv, array('OT', 'HH', 'Sport', 'College', 'Individual')))
        {
            $provnum = 'N/A';
        }

        $rehab_type = $this->rehab_type;
        if (in_array($this->site_type_abv, array('HOSP', 'IRF', 'OT', 'HH', 'Sport', 'College', 'Individual')))
        {
            $rehab_type = 'N/A';
            $med_a_beds = 'N/A';
            $med_b_beds = 'N/A';
            $other_beds = 'N/A';
        }

        if ($provnum)
        {
            $prov = array("text" => $provnum, "alt" => "View Medicare Provider Information", "href" => "provider.php?provnum=$provnum", "target" => "_blank");
            $provnum = BaseClass::BuildATags(array($prov));
        }

        $cpc_name = $rmo_name = $dvp_name = $spl_name = "None";

        if ($this->account_executive)
        {
            $cpc = new User($this->account_executive);
            $cpc_name = "{$cpc->GetName()} ({$cpc->GetRegion()})";
            $rmo = $cpc->GetSupervisor();
            if ($rmo)
            {
                $rmo_name = $rmo->GetName();
                $dvp = $rmo->GetSupervisor();
                if ($dvp)
                    $dvp_name = $dvp->GetName();
            }
        }

        if ($this->slp_cpc_id)
        {
            $spl = new User($this->slp_cpc_id);
            $spl_name = $spl->getName();
        }

        # Set the text from the contract record
        $contract_id = (int) LeaseContract::GetFirstLease($this->getId(0));
        $contract = new LeaseContract($contract_id);

        # Default to addon pricing
        $pricing_method = $contract->GetVar('pricing_method');
        if (empty ($pricing_method))
            $contract->SetVar('pricing_method', 'add');

        $visit_text = $contract->GetVisitFrequencyText();

        # Create the html
        # Create the html
        $customer_section = "
	<form name='company_form' action='{$_SERVER['PHP_SELF']}' method='get'>
		{$this->getId(1)}
		<input type='hidden' id='object' name='object' value='Facility' />
		<input type='hidden' name='act' value='view' />
	</form>
	<div id='affix_cont' data-spy='affix' data-offset-top='55'>
		<div class='box box-solid box-success' style='margin-bottom:0;'>
			<div class='box-header' data-toggle='collapse' data-target='#home_cont'>
				<h4 class='box-title'>{$this->getOfficeName()} $acc_id</h4>
				<button class='btn btn-box-tool pull-right' type='button' onclick='ToggleAffix();' data-toggle='tooltip' data-placement='left' title='Lock/Unlock information bar possition'><i id='affix_btn' class='fa fa-lock'></i></button>
				<button class='btn btn-box-tool pull-right' type='button' data-widget='collapse' data-toggle='tooltip' data-placement='left' title='Show/Hide information bar'><i class='fa fa-minus'></i></button>
			</div>
			<div id='cust_info' class='box-body'>
				<div class='inblk' style='width: 200px; vertical-align: top;'>
					<div style='float: right;'>
						<input type='button' onclick=\"MapWindow=window.open('map.php?facility={$this->office_id}','Facility_Map','width=665,height=560,toolbar=no,scrollbars=yes,resizable=yes')\" value='Map' />
					</div>
					<div class='address' style='font-size:9pt; font-weight:normal;'>
						$address
					</div>
				</div>
				<div class='inblk' style='margin-left: 20px;'>
					<table class='table face table-condensed'>
						<tr>
							<th>Site Type:</th>
							<td>{$this->site_type_abv} - {$this->site_type_text}</td>
							<th>Medicare Provider No:</th>
							<td>{$provnum}</td>
							<th>CPC:</th>
							<td>{$cpc_name}</td>
						</tr>
						<tr>
							<th>Corp. Parent:</th>
							<td>$corporate_parent</td>
							<th>Rehab Provider:</th>
							<td>{$rehab_provider}</td>
							<th>RMO:</th>
							<td>{$rmo_name}</td>
						</tr>
						<tr>
							<th>Clinical Support / Visits Frequency:</th>
							<td>$visit_text ({$this->visit_frequency}/yr)</td>
							<th>Rehab Provider Type:</th>
							<td>{$rehab_type}</td>
							<th>DVP:</th>
							<td>{$dvp_name}</td>
						</tr>
						<tr>
							<th>DSSI Code:</th>
							<td>{$this->dssi_code}</td>
							<th>FTE Count:</th>
							<td>{$this->fte_count}</td>
							<th>SLP CPC:</th>
							<td>{$spl_name}</td>
						</tr>
						<tr>
							<th>Med A Beds:</th>
							<td>{$med_a_beds}</td>
							<th>Med B Beds:</th>
							<td>{$med_b_beds}</td>
							<th>Other Beds:</th>
							<td>{$other_beds}</td>
						</tr>
						<tr>
							<th>Payment Terms:</th>
							<td colspan='5'>{$this->payment_terms_desc}</td>
						</tr>
					</table>
				</div>
			</div>
			<div class='box-footer filter'>
				<b>Customer Options</b>:
				$tags
			</div>
		</div>
	</div>";

        return $customer_section;
    }

    /**
     * Supply html and count for the Facility Map section
     *
     * @param string
     * @param int
     *
     * @return string
     */
    public function FacilityMap(&$html, &$count)
    {
        global $preferences;

        $defaults = array('page' => 1, 'sort_by' => "facility_name", 'dir' => "ASC", 'f_show' => "a", "f_view" => "m");
        SessionHandler::Update('crm', 'fac', $defaults);

        ## Set up filter array
        $filter = $_SESSION['crm']['fac'];
        $filter['limit'] = 10000;
        $filter['offset'] = 0;

        $this->LoadFacilities($filter);

        $map_width = 1500;
        $map_height = 780;

        foreach ($this->facilities as $i => $fac)
        {
            # Need to page results useing a window function
            # for true total. This is return in total_records field.
            $count = $fac->total_records;

            $name = htmlentities(trim($fac->facility_name), ENT_QUOTES);
            $city = htmlentities(trim($fac->city), ENT_QUOTES);

            # Round these values
            $lat = round($fac->latitude, 4);
            $lng = round($fac->longitude, 4);

            $shapePoints = array($lat, $lng);
            $info = str_replace(' ', '&nbsp;', "$name<br/>$city, {$fac->state} {$fac->zip}<br/>Lat: $lat, Lng: $lng}");
            $key = 'fac_' . $i++;

            $map_points[] = array(
                'type' => 'fac',
                'region' => $fac->region_id,
                'key' => $key,
                'cords' => array('lat' => $lat, 'lng' => $lng),
                'shapePoints' => $shapePoints,
                'fillColor' => '#9F4700',
                'info' => $info
            );
        }

        $count = $map_points;

        ## define attributes used to query for a page of data
        $base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=FacilityTable&entry={$this->office_id}";
        $base_url_alt = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=FacilityMap&entry={$this->office_id}";

        $show_a = ($filter['f_show'] == "a") ? "checked" : "";
        $show_b = ($filter['f_show'] == "b") ? "checked" : "";
        $show_c = ($filter['f_show'] == "c") ? "checked" : "";

        $show_clicka = "FillMap('$base_url_alt&f_show=a','facility_disp','fac_nav_badge','fac_sec_badge');";
        $show_clickb = "FillMap('$base_url_alt&f_show=b','facility_disp','fac_nav_badge','fac_sec_badge');";
        $show_clickc = "FillMap('$base_url_alt&f_show=c','facility_disp','fac_nav_badge','fac_sec_badge');";


        $f_view_l = ($filter['f_view'] == "l") ? "checked" : "";
        $f_view_m = ($filter['f_view'] == "m") ? "checked" : "";

        $click_view_l = "FillSection('$base_url&page=1&f_view=l','facility_disp','fac_nav_badge','fac_sec_badge');";
        $click_view_m = "FillMap('$base_url_alt&f_view=m','facility_disp','fac_nav_badge','fac_sec_badge');";

        $html = "
	<div class='filter nested' align='left'>
		<span class='lbl'>Show:</span>
		<input type='radio' name='f_show' value='a' id='f_show_a' onclick=\"$show_clicka\" $show_a/>
		<label for='f_show_a'>Active</label>
		<input type='radio' name='f_show' value='c' id='f_show_c' onclick=\"$show_clickc\" $show_c />
		<label for='f_show_c'>Cancelled</label>
		<input type='radio' name='f_show' value='b' id='f_show_b' onclick=\"$show_clickb\" $show_b />
		<label for='f_show_b'>All</label>

		<span class='lbl'>View:</span>
		<input type='radio' name='f_view' value='l' id='f_view_l' onclick=\"$click_view_l\" />
		<label for='f_view_l'>Listing</label>
		<input type='radio' name='f_view' value='m' id='f_view_m' onclick=\"$click_view_m\" checked />
		<label for='f_show_c'>Map</label>
	</div>
	<div id='map' style='width : {$map_width}px; height: {$map_height}px;'></div>";

        return $html;
    }

    /**
     * Supply html for the Facility section
     *
     * @return string
     */
    public function FacilitySection()
    {
        $mq_url = Config::$MAPQUEST_MAP_URL;
        $mq_key = Config::$MAPQUEST_APP_KEY;

        if ($this->is_office)
        {
            $content = "<div class='on'>Loading...</div>";
        }
        else
        {
            $content = '<div align="center" class="on">This is a facility.</div>';
        }

        $section = "<script src=\"$mq_url?key=$mq_key\"></script>
		<div class='flx_cont'>
			<div id='facility_cont' class='box box-primary'>
				<div id='facility_hdr' class='box-header' data-toggle='collapse' data-target='#facility_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Facilities</h4>
					<span class='badge' id='fac_sec_badge'></span>
				</div>
				<div id='facility_disp' class='box-body collapse'>
					$content
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply html and count for the Facility section
     *
     * @param string
     * @param int
     *
     * @return string
     */
    public function FacilityTable(&$html, &$count)
    {
        global $this_app_name, $preferences;

        $defaults = array('page' => 1, 'sort_by' => "facility_name", 'dir' => "ASC", 'f_show' => "a", "f_view" => "l");
        SessionHandler::Update('crm', 'fac', $defaults);
        $filter = $_SESSION['crm']['fac'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 1;
        $sec_conf->disp = 'facility_disp';
        $sec_conf->nav_badge = 'fac_nav_badge';
        $sec_conf->sec_badge = 'fac_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=FacilityTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_fac_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        $this->LoadFacilities($filter);

        $sec_conf->row_count = count($this->facilities);
        $active_count = $sum_revenue = 0;
        if ($sec_conf->row_count > 0)
        {
            $rc = 'dt-tr-on';
            foreach ($this->facilities as $fac)
            {
                $tag['text'] = 'view';
                $tag['alt'] = "View Customer Management Detail";
                $tag['href'] = "corporateoffice.php?act=view&object=facility&office_id={$fac->id}";
                $tag['target'] = "_blank";
                $links = BaseClass::BuildATags(array($tag));

                # Need to page results useing a window function
                # for true total. This is return in total_records field.
                $count = $fac->total_records;
                $sec_conf->count = $fac->total_records;
                $total_revenue = $fac->total_revenue;

                $fac->cancelled = ($fac->cancelled) ? "Yes" : "No";
                if ($fac->cancelled == "No")
                {
                    $active_count++;
                    $sum_revenue += $fac->lease_revenue;
                }
                $fac->facility_name = htmlentities($fac->facility_name, ENT_QUOTES);
                $fac->city = htmlentities($fac->city, ENT_QUOTES);
                $fac->cpc_first = htmlentities(trim($fac->firstname), ENT_QUOTES);
                $fac->cpc_last = htmlentities(trim($fac->lastname), ENT_QUOTES);
                $fac->cpc = trim("{$fac->firstname} {$fac->lastname}");
                $fac->lease_revenue = number_format($fac->lease_revenue, 2);
                $ia = ($fac->cancelled == "Yes") ? " faded" : "";

                $sec_conf->rows .= "<tr class='{$rc}{$ia}' ondblclick=\"window.location='{$tag['href']}';\">";
                foreach ($sec_conf->cols as $col)
                {
                    $sec_conf->rows .= "<td class='{$col->cls}'>{$fac->{$col->key} }</td>";
                }

                $sec_conf->rows .= "
					<td class='nested' style='text-align:right;'>
						<div class='submenu'>
							$links
						</div>
					</td>
				</tr>";

                $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
            }


            $sum_revenue = number_format($sum_revenue, 2);
            $total_revenue = number_format($total_revenue, 2);
            $cs = count($sec_conf->cols) + 1;
            $sec_conf->rows .= "<tr class='total'>
				<td colspan=$cs>
					<span style='width: 50%; float:left; text-align:center;'>
						Facility&nbsp;-&nbsp;Count: $count,&nbsp;&nbsp;Active: $active_count
					</span>
					<span style='width: 50%; float:left; text-align:center;'>
						Revenue&nbsp;-&nbsp;Page Total: $sum_revenue,&nbsp;&nbsp;Grand Total: $total_revenue
					</span>
				</td>
			</tr>";
        }
        else
        {
            $cs = count($sec_conf->cols) + 1;
            $sec_conf->rows = "<tr class='dt-tr-on'><td colspan=$cs style='text-align:center;'>No Facilities found.</td></tr>";
        }


        ## define attributes used to query for a page of data
        $base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=FacilityTable&entry={$this->office_id}";
        $base_url_alt = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=FacilityMap&entry={$this->office_id}";

        $show_a = ($filter['f_show'] == "a") ? "checked" : "";
        $show_b = ($filter['f_show'] == "b") ? "checked" : "";
        $show_c = ($filter['f_show'] == "c") ? "checked" : "";

        $show_clicka = "FillSection('$base_url&page=1&f_show=a','facility_disp','fac_nav_badge','fac_sec_badge');";
        $show_clickb = "FillSection('$base_url&page=1&f_show=b','facility_disp','fac_nav_badge','fac_sec_badge');";
        $show_clickc = "FillSection('$base_url&page=1&f_show=c','facility_disp','fac_nav_badge','fac_sec_badge');";

        $click_view_l = "FillSection('$base_url&page=1&f_view=l','facility_disp','fac_nav_badge','fac_sec_badge');";
        $click_view_m = "FillMap('$base_url_alt&f_view=m','facility_disp');";

        $sec_conf->filter = "<div class='filter nested' align='left'>
			<span class='lbl'>Show:</span>
			<input type='radio' name='f_show' value='a' id='f_show_a' onclick=\"$show_clicka\" $show_a/>
			<label for='f_show_a'>Active</label>
			<input type='radio' name='f_show' value='c' id='f_show_c' onclick=\"$show_clickc\" $show_c />
			<label for='f_show_c'>Cancelled</label>
			<input type='radio' name='f_show' value='b' id='f_show_b' onclick=\"$show_clickb\" $show_b />
			<label for='f_show_b'>All</label>

			<span class='lbl'>View:</span>
			<input type='radio' name='f_view' value='l' id='f_view_l' onclick=\"$click_view_l\" checked />
			<label for='f_view_l'>Listing</label>
			<input type='radio' name='f_view' value='m' id='f_view_m' onclick=\"$click_view_m\" />
			<label for='f_show_c'>Map</label>
		</div>";

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    /**
     * Locate facilities at this address or at least similar
     *
     * @param object
     * @return string
     */
    public function FindSameAddress($contact)
    {
        $facs = "";
        $dbh = DataStor::getHandle();
        $sql = "SELECT
			f.id, f.accounting_id, f.facility_name, f.corporate_parent,
			f.address, f.address2, f.city, f.state, f.zip, c.name as country,
			f.phone, f.fax, f.cancelled
		FROM facilities f
		LEFT JOIN countries c on f.country_id = c.id
		WHERE f.id <> ?
		AND coalesce(f.city, '') <> ''
		AND upper(f.address) = ?
		AND upper(f.city) = ?
		ORDER BY f.accounting_id";
        $sth = $dbh->prepare($sql);
        $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
        $sth->bindValue(2, strtoupper($contact->GetAddress1(0)), PDO::PARAM_STR);
        $sth->bindValue(3, strtoupper($contact->GetCity(0)), PDO::PARAM_STR);
        $sth->execute();
        if ($sth->rowCount() > 0)
        {
            $rc = 'dt-tr-on';
            $facs = "<div class='filter' style='margin-top:40px;'>Facilities with similar address</div>
			<table class='dt' cellspacing=0 cellpadding=2 border=1>
			<tr>
				<th class='hdr'>Name</th>
				<th class='hdr'>Cust ID</th>
				<th class='hdr'>Corp Parent</th>
				<th class='hdr'>Address Line 1</th>
				<th class='hdr'>Address Line 2</th>
				<th class='hdr'>Ctiy</th>
				<th class='hdr'>State</th>
				<th class='hdr'>Zip</th>
				<th class='hdr'>Country</th>
				<th class='hdr'>Phone</th>
				<th class='hdr'>Fax</th>
			</tr>";
            while ($fac = $sth->fetch(PDO::FETCH_OBJ))
            {
                $tag['text'] = $fac->accounting_id;
                $tag['alt'] = "View Customer Management Detail";
                $tag['href'] = "corporateoffice.php?act=view&object=facility&office_id={$fac->id}";
                $tag['target'] = "_blank";
                $accounting_id = BaseClass::BuildATags(array($tag));

                $fac->cancelled = ($fac->cancelled) ? "Yes" : "No";
                $ia = ($fac->cancelled == "Yes") ? " faded" : "";

                $facs .= "<tr class='{$rc}{$ia}' ondblclick=\"window.location='{$tag['href']}';\">
					<td class='txal'>{$fac->facility_name}</td>
					<td class='txal'>{$accounting_id}</td>
					<td class='txac'>{$fac->corporate_parent}</td>
					<td class='txal'>{$fac->address}</td>
					<td class='txal'>{$fac->address2}</td>
					<td class='txal'>{$fac->city}</td>
					<td class='txac'>{$fac->state}</td>
					<td class='txac'>{$fac->zip}</td>
					<td class='txal'>{$fac->country}</td>
					<td class='txal'>{$fac->phone}</td>
					<td class='txal'>{$fac->fax}</td>
				</tr>";

                $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
            }
            $facs .= "</table>";
        }

        return $facs;
    }

    /**
     * Returns the accounting id.
     *
     * @return string
     */
    public function getAccId($edit = 0)
    {
        $acc_id = $this->account_id;
        if ($edit)
        {
            $acc_id = "<input type='text' name='account_id' size='20' value='{$acc_id}' />";
        }
        return $acc_id;
    }

    /**
     * Returns the corporate_office id. (Top most office)
     *
     * @return integer
     */
    public function getCorporateOfficeId($edit = 0)
    {
        $corporate_office_id = $this->corporate_office_id;
        if ($edit)
        {
            $corporate_office_id = "<input type='hidden' name='corporate_office_id' value='{$corporate_office_id}' />";
        }
        return $corporate_office_id;
    }


    /**
     * Returns the first address line.
     *
     * @return string
     */
    public function getAddress1($edit = 0)
    {
        return isset ($this->contacts[0]) ? $this->contacts[0]->getAddress1($edit) : '';
    }


    /**
     * Returns the seconds address line.
     *
     * @return string
     */
    public function getAddress2($edit = 0)
    {
        return isset ($this->contacts[0]) ? $this->contacts[0]->getAddress2($edit) : '';
    }


    /**
     * Returns the city.
     *
     * @return string
     */
    public function getCity($edit = 0)
    {
        return isset ($this->contacts[0]) ? $this->contacts[0]->getCity($edit) : '';
    }


    /**
     * Returns the country.
     *
     * @return Country
     */
    public function getCountry($edit = 0)
    {
        return isset ($this->contacts[0]) ? $this->contacts[0]->getCountry($edit) : '';
    }

    /**
     * Attributes are defined by a multi dementional array
     *
     * Section -> Attribute -> Field -> @Value
     *
     * Value is an array used to determine how to treat and display the attribute
     * @(value, id, type)
     *
     * Based on the size of the array and type of value show the proper input
     * Disable the inputs in view only mode
     *
     * @param boolean $disable
     * @return string html containing the company attributes
     */
    public function GetDetail($disable = true)
    {
        $d = ($disable) ? "disabled" : "";
        $chk_style = ($disable) ? "font-size:x-small;" : "font-size:small;";

        $detail = "<table class='table face'>";

        # We wont allways need detailed information. Load it when its called for
        $this->LoadDetail();

        # Each section has a header and attribute table
        foreach ($this->detail as $section => $section_ary)
        {
            # Start a new section
            # Only Draw header row if we have > 1 attributes in the section
            # By default use <th> tag for the attribute name
            $use_small = 0;
            if (count($section_ary) > 1)
            {
                $detail .= "
			<tr>
				<th class='txal' colspan='2'>{$section}</th>
			</tr>\n";

                # Used the small <td> tag for attribute name
                $use_small = 1;
            }
            # An attribute has an attribute name and field table
            foreach ($section_ary as $attribute => $att_ary)
            {
                # Set attribute name either as th or td
                if ($use_small)
                {
                    $detail .= "			<tr>\n				<td align='left' style=\"font-size:small;\">{$attribute}</td>";
                }
                else
                {
                    $detail .= "			<tr>\n				<th align='left' >{$attribute}</th>";
                }

                # Many fields to show set create a nested table to display them
                if (count($att_ary) > 1)
                {
                    $detail .= "
				<td class='txal'>
					<table cellspacing='2' cellpadding='0'>\n";
                    $cell = 1;
                    $end_table = 1;
                }
                # Only one field show it in a single cell
                else
                {
                    $cell = 0;
                    $end_table = 0;
                }

                # Field table is field name + input three columns per row
                foreach ($att_ary as $field => $val_ary)
                {
                    # Is this a new or existing detail record
                    $name = ($val_ary['id']) ? "detail[{$section}][{$attribute}][{$field}][{$val_ary['id']}]" : "new_detail[{$section}][{$attribute}][{$field}][{$val_ary['type']}]";

                    # Start table row
                    if ($cell == 1)
                    {
                        $detail .= "						<tr>\n";
                    }

                    # Dont show Name with input
                    if ($val_ary['show_lable'] == FALSE)
                    {
                        $lable = '';
                    }
                    else
                    {
                        $lable = str_replace(" ", "&nbsp;", $field);
                    }
                    # Set the table cell based on field type
                    # relpace the spaces in field name with &nbsp;
                    #
                    if ($val_ary['type'] == 'text')
                    {
                        # field means we show name with input which limits room
                        # without a namw we have more space so give the input more size
                        $fsize = ($disable) ? "x-small" : "small";
                        $size = ($lable) ? "30" : "50";

                        # Add Dummy check box for "Other" option
                        $other_check_box = "";
                        if ($lable && strtolower($lable) == 'other')
                        {
                            $other_check_box = "<input type='checkbox' " . (($val_ary['value']) ? "checked" : "") . " $d/>";
                        }
                        $td_style = "";
                        if ($disable && $val_ary['value'])
                        {
                            $td_style .= " color:red; font-weight:bold;";
                        }
                        $detail .= "
						<td class='txal' style=\"font-size:{$fsize};{$td_style}\" nowrap>{$other_check_box}{$lable}
							<input style=\"font-size:{$fsize};\" type='text' name='{$name}' value='{$val_ary['value']}' size='{$size}' />
						</td>";
                    }
                    else if ($val_ary['type'] == 'textarea')
                    {
                        $detail .= "
						<td class='txal' style=\"font-size:small;\">{$lable}
							<textarea style=\"background:white; font-size:small; color:black;\" name='{$name}' rows='3' cols='50' $d>{$val_ary['value']}</textarea>
						</td>";
                    }
                    else if ($val_ary['type'] == 'select')
                    {
                        $options = "";
                        foreach ($val_ary['options'] as $val)
                        {
                            $sel = ($val_ary['value'] == $val) ? "selected" : "";
                            $options .= "<option value='{$val}' $sel>{$val}</option>\n";
                        }

                        $detail .= "<td class='txal' style=\"font-size:small;\">{$lable}
							<select name='{$name}' $d>
								$options
							</select>
						</td>";
                    }
                    else
                    {
                        $disabled = $d;
                        if ($field == 'po_required' || $field == 'dssi_code')
                            $disabled = "disabled";

                        $td_style = $chk_style;
                        if ($disable && $val_ary['value'])
                            $td_style .= " color:red; font-weight:bold;";

                        $detail .= "
						<td class='txal' style=\"$td_style\">
							<input type='checkbox' name='{$name}' value='1' " . (($val_ary['value']) ? "checked" : "") . " $disabled />
							{$lable}
						</td>";
                    }

                    # End the table row and
                    # Set cell to 0 so that the increment will set cell to 1
                    if ($cell == 3)
                    {
                        $detail .= "						</tr>\n";
                        $cell = 0;
                    }

                    # Next cell
                    $cell++;
                }

                # If the row was not finished end it here
                if ($cell > 1)
                    $detail .= "						</tr>\n";

                # End of field table
                if ($end_table)
                    $detail .= "
					</table>
				</td>";

                # End of attribute row
                $detail .= "
			</tr>";
            }
        } # End of section
        $detail .= "</table>";

        return $detail;
    }

    /**
     * For select type detail values obtain options for the input
     *
     * @param string $field determins what set of options to create
     * @param string $match default selected value
     * @return string html options tags suitable for a select input
     */
    private function GetDetailOptions($field, $match)
    {
        $options_ary = array();
        $options = "<option value=''></option>\n";

        if ($field == "Corporate Approval Officer")
        {
            foreach ($this->contacts as $contact_id => $contact)
            {
                # Skip office contact
                if ($contact_id == 0)
                    continue;

                $options_ary[$contact_id] = $contact->getLastName() . ", " . $contact->getFirstName() . " - " . $contact->getTitle();
            }
        }

        foreach ($options_ary as $value => $display)
        {
            $sel = ($match == $value) ? "selected" : "";
            $options .= "<option value='{$value}' $sel>$display</option>\n";
        }

        return $options;
    }

    /**
     * Returns the database id.
     *
     * @return integer
     */
    public function getId($edit = 0)
    {
        $office_id = (int) $this->office_id;

        if ($edit)
        {
            $office_id = "<input type=\"hidden\" id=\"office_id\" name=\"office_id\" value=\"{$office_id}\" />";
        }

        return $office_id;
    }


    /**
     * Returns the name of this facility.
     *
     * @return string
     */
    public function getOfficeName($edit = 0, $size = 20)
    {
        $office_name = htmlentities($this->office_name, ENT_QUOTES);
        if ($edit)
        {
            $office_name = "<input type='text' name='office_name' value='{$office_name}' size='$size' maxlength='50' />";
        }
        return $office_name;
    }


    /**
     * Returns the email.
     *
     * @return string
     */
    public function getEmail($edit = 0)
    {
        return isset ($this->contacts[0]) ? $this->contacts[0]->getEmail($edit) : '';
    }


    /**
     * Returns the phone number.
     *
     * @return string
     */
    public function getPhone($edit = 0)
    {
        return isset ($this->contacts[0]) ? $this->contacts[0]->getPhone($edit) : '';
    }


    /**
     * Returns the mobile phone number.
     *
     * @return string
     */
    public function getMobile($edit = 0)
    {
        return isset ($this->contacts[0]) ? $this->contacts[0]->getMobile($edit) : '';
    }


    /**
     * Returns the fax number.
     *
     * @return string
     */
    public function getFax($edit = 0)
    {
        return isset ($this->contacts[0]) ? $this->contacts[0]->getFax($edit) : '';
    }


    /**
     * Returns the state.
     *
     * @return string
     */
    public function getState($edit = 0)
    {
        return isset ($this->contacts[0]) ? $this->contacts[0]->getState($edit) : '';
    }


    /**
     * Returns the zip code.
     *
     * @return string
     */
    public function getZip($edit = 0)
    {
        return isset ($this->contacts[0]) ? $this->contacts[0]->getZip($edit) : '';
    }


    /**
     * Returns status value
     *
     * @return integer
     */
    public function getStatus($edit = 0)
    {
        $status = $this->status;
        if ($edit)
        {
            $status = "
			<select name='status'>";
            $sth = $this->dbh->prepare("SELECT status_id, office_status FROM corporate_office_status ORDER BY display_order");
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $sel = ($this->status == $row['status_id']) ? "selected" : "";
                $status .= "
				<option value='{$row['status_id']}' {$sel}>{$row['office_status']}</option>";
            }
            $status .= "
			</select>";
        }

        return $status;
    }

    /**
     * Generate a tr for pagination
     *
     * @param int
     * @param int
     * @param int
     * @param int
     * @param string
     *
     * @return string
     */
    static public function GetPageBar($total, $count, $page, $per_page, $href, $col_tag = "")
    {
        # Build the url
        if ($total > $count)
        {
            # Initialize values as if page is somewhere in the middle of the total
            $num_pages = ceil($total / $per_page);
            $start_page = $page - 4;
            $end_page = $page + 5;

            # When start is less than 1 adjust
            if ($start_page < 1)
            {
                $end_page += ($start_page * -1) + 1;
                $start_page = 1;

                if ($end_page > $num_pages)
                    $end_page = $num_pages;
            }
            # When the page is within 5 of the last pages adjust
            else if ($end_page > $num_pages)
            {
                $start_page -= ($end_page - $num_pages);
                $end_page = $num_pages;

                if ($start_page < 1)
                    $start_page = 1;
            }

            # Get the page html
            $page_tags = self::GetPageTags($href, $page, $start_page, $end_page, $num_pages);
            $page_row = "<div class='filter'>
				<div style='text-align:center;'>
					$col_tag
					<span style='float:left;'>Showing $count of $total</span>
					$page_tags
				</div>
			</div>";
        }
        else
        {
            $page_row = "<div class='filter'>
				$col_tag
				&nbsp;
				<span style='float:left;'>Showing $count of $total</span>
			</div>";
        }

        return $page_row;
    }

    /**
     * Build html for paging anchors
     *
     * @param string
     * @param string
     * @param integer
     * @param integer
     * @param integer
     * @param integer
     */
    static public function GetPageTags($query_str, $page_num, $start_page, $end_page, $num_pages)
    {
        $nav_bar = "";

        # Create the code for the "first" and "previous" arrows.
        #
        $first_page_link = "<span class='sel'>&laquo;</span>";
        $prev_page_link = "<span class='sel'>&lt;</span>";
        if ($page_num != 1)
        {
            $prev_page = $page_num - 1;
            $f_href = str_replace('__PAGE__', 1, $query_str);
            $p_href = str_replace('__PAGE__', $prev_page, $query_str);
            $first_page_link = "<a class='pg' href=\"$f_href\">&laquo;</a>";
            $prev_page_link = "<a class='pg' href=\"$p_href\">&lt;</a>";
        }

        $nav_bar .= "<label>Pages:</label>{$first_page_link}{$prev_page_link}";

        # Create the code for the clickable page numbers.
        #
        for ($page = $start_page; $page <= $end_page; $page++)
        {
            if ($page == $page_num)
            {
                $nav_bar .= "<span class='sel'>$page</span>";
            }
            else
            {
                $href = str_replace('__PAGE__', $page, $query_str);
                $nav_bar .= "<a class='pg' href=\"$href\">$page</a>";
            }
        }

        # Create the code for the "next" and "last" arrows.
        #
        if ($page_num == $end_page)
        {
            $next_page_link = "<span class='sel'>&gt;</span>";
            $last_page_link = "<span class='sel'>&raquo;</span>";
        }
        else
        {
            $next_page = $page_num + 1;
            $n_href = str_replace('__PAGE__', $next_page, $query_str);
            $l_href = str_replace('__PAGE__', $num_pages, $query_str);
            $next_page_link = "<a class='pg' href=\"$n_href\">&gt;</a>";
            $last_page_link = "<a class='pg' href=\"$l_href\">&raquo;</a>";
        }


        $nav_bar .= "{$next_page_link}{$last_page_link}";

        return $nav_bar;
    }

    /**
     * Build a comma seperated string listing the account_ids for the subdivision
     *
     * @param integer $office_id
     * @return string comma seperated string of account_id in the region
     */
    public static function GetSubdivision($office_id)
    {
        $dbh = DataStor::getHandle();
        $region = "";

        if ($office_id > 0)
        {
            $sql = "SELECT account_id, office_id FROM corporate_office WHERE parent_id = {$office_id}  AND status <= 1";
            $sth = $dbh->prepare($sql);
            $sth->execute();
            while (list($account_id, $child_id) = $sth->fetch(PDO::FETCH_NUM))
            {
                $children = self::GetSubdivision($child_id);
                if ($children)
                    $region .= $children . ",";
                $region .= $dbh->quote($account_id) . ",";
            }
            $region = substr($region, 0, -1); // Remove trailing ,
        }

        return $region;
    }

    /**
     * Returns the account_executive
     *
     * @return string
     */
    function getAccountExecutive($edit = 0)
    {
        $ae = $this->account_executive;
        if ($edit)
        {
            $ae = "<input type='hidden' name='account_executive' value='$ae'/>";
        }
        return $ae;
    }


    /**
     * Returns the account_executive
     *
     * @return string
     */
    function getAccountExecutiveName($edit = 0)
    {
        # Want to show all users RDO and above excluding sysadmin and nothing
        $start_role = 100;
        $end_role = 600;

        $ae = "None";
        if ($edit)
        {
            $int_id = (int) $this->account_executive;
            $sth = $this->dbh->prepare("
				SELECT v.user_id, u.firstname, u.lastname
				FROM v_users_primary_group v
				INNER JOIN users u ON v.user_id = u.id
				INNER JOIN roles r ON v.role_id = r.id AND r.id BETWEEN {$start_role} AND {$end_role}
				WHERE u.active OR u.id = {$int_id}
				ORDER BY u.lastname, u.firstname");
            $sth->execute();
            $user_count = $sth->rowCount();
            if ($user_count > 0)
                $ae = "<select name='account_executive'>\n<option value='0'></option>\n";
            while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $sel = ($this->account_executive == $row['user_id']) ? "selected" : "";
                $ae .= "<option value='{$row['user_id']}' {$sel}>{$row['lastname']}, {$row['firstname']}</option>\n";
            }
            if ($user_count > 0)
                $ae .= "</select>";
        }
        else
        {
            if ($this->account_executive) # keep from generating an exception
            {
                $cpc = new User($this->account_executive);
                $ae = ($cpc) ? "{$cpc->getName()} ({$cpc->getRegion()})" : 'None';
            }
        }
        return $ae;
    }

    /**
     * Returns status text
     *
     * @return string
     */
    public function getSStatus($edit = 0)
    {
        return $this->s_status;
    }

    /**
     * Returns the parent id.
     *
     * @return integer
     */
    public function getParentId($edit = 0)
    {
        $parent_id = $this->parent_id;
        if ($edit)
        {
            $parent_id = "<input type='hidden' name='parent_id' value='{$parent_id}' />";
        }
        return $parent_id;
    }

    /**
     * Returns the public or private orientation.
     *
     * @return string
     */
    public function getOrientation($edit = 0)
    {
        $orientation = $this->orientation;
        if ($edit)
        {
            $chk_public = ($this->orientation) ? "" : "checked";
            $chk_private = ($this->orientation) ? "checked" : "";

            $orientation = "<input type='radio' name='orientation' value='0' $chk_public />Public";
            $orientation .= "&nbsp;&nbsp;";
            $orientation .= "<input type='radio' name='orientation' value='1' $chk_private />Private";
        }
        return $orientation;
    }

    /**
     * Create a navigation bar with available sections
     *
     * @return string
     */
    public function GetNavBar()
    {
        $btns['sub']['btn'] = (int) $_SESSION['crm']['disp']['sub_disp'];
        $btns['calendar']['btn'] = (int) $_SESSION['crm']['disp']['calendar_disp'];
        $btns['contact']['btn'] = (int) $_SESSION['crm']['disp']['contact_disp'];
        $btns['contract']['btn'] = (int) $_SESSION['crm']['disp']['contract_disp'];
        $btns['detail']['btn'] = (int) $_SESSION['crm']['disp']['detail_disp'];
        $btns['clinic']['btn'] = (int) $_SESSION['crm']['disp']['clinic_disp'];
        $btns['equip']['btn'] = (int) $_SESSION['crm']['disp']['equip_disp'];
        $btns['facility']['btn'] = (int) $_SESSION['crm']['disp']['facility_disp'];
        $btns['issue']['btn'] = (int) $_SESSION['crm']['disp']['issue_disp'];
        $btns['question']['btn'] = (int) $_SESSION['crm']['disp']['question_disp'];
        $btns['goal']['btn'] = (int) $_SESSION['crm']['disp']['goal_disp'];
        $btns['invoice']['btn'] = (int) $_SESSION['crm']['disp']['invoice_disp'];
        $btns['lead']['btn'] = (int) $_SESSION['crm']['disp']['lead_disp'];
        $btns['order']['btn'] = (int) $_SESSION['crm']['disp']['order_disp'];
        $btns['task']['btn'] = (int) $_SESSION['crm']['disp']['task_disp'];
        $btns['therapist']['btn'] = (int) $_SESSION['crm']['disp']['therapist_disp'];
        $btns['clin']['btn'] = (int) $_SESSION['crm']['disp']['clin_disp'];
        $btns['visit']['btn'] = (int) $_SESSION['crm']['disp']['visit_disp'];
        $btns['wo']['btn'] = (int) $_SESSION['crm']['disp']['wo_disp'];
        $btns['memo']['btn'] = (int) $_SESSION['crm']['disp']['memo_disp'];

        $btns = json_encode($btns);
        $iso = ($this->is_office) ? 1 : 0;

        # Task Sub Menu
        $click = "InitDialog(task_conf,'templates/sm/forms/task.php?entry=0&customer_id={$this->office_id}&is_office=$iso');";
        $menu[0] = array('text' => 'New Task', 'class' => 'bdrlft', 'click' => $click, 'alt' => "Add Task");
        $task_sub = BaseClass::BuildATags($menu);

        # Issue Sub Menu
        $issue_count = count($this->issues);
        $office_name = urlencode($this->office_name);
        $iss_obj = ($this->is_office) ? 'office' : 'facility';
        $click = "OpenIssueWindow('issue_edit.php?submit_action=edit&issue_id=0&office_id={$this->getId(0)}&is_office=$iso&object=$iss_obj&corporate_office_id={$this->corporate_office_id}&office_name={$office_name}');";

        $menu[0] = array('text' => 'New CS Issue', 'class' => 'bdrlft', 'click' => $click, 'alt' => "Add New Conversation Thread");
        $issue_sub = BaseClass::BuildATags($menu);

        /// For office id
        $officehref = "templates/crm/contact_disp.php?newEntry=1&object=Contact&entry=0&office_id={$this->office_id}&is_office=0&cbp=" . basename($_SERVER['PHP_SELF']) . "&rtn=" . urlencode($_SERVER['REQUEST_URI']) . "";

        /// For facility id
        $facilityhref = "templates/crm/contact_disp.php?newEntry=1&edit=1&object=Contact&entry=0&facility_id={$this->getId(0)}&is_office=0&cbp=" . basename($_SERVER['PHP_SELF']) . "&rtn=" . urlencode($_SERVER['REQUEST_URI']) . "";

        # Contact Sub Menu
        $con_count = count($this->contacts) - 1;
        if ($iso)
            $click = "InitDialog(contact_conf,'{$officehref}');";
        else
            $click = "InitDialog(contact_conf,'{$facilityhref}');";
        $menu[0] = array('text' => 'New Contact', 'class' => 'bdrlft', 'click' => $click, 'alt' => "Add New Contact");
        $contact_sub = BaseClass::BuildATags($menu);

        # Subdivision Menu
        $sub_count = count($this->subs);
        $click = "InitDialog(office_conf,'{$_SERVER['PHP_SELF']}?act=getform&object=CorporateOffice&entry=0&office_id=0&parent_id={$this->office_id}&is_office=$iso');";
        $menu[0] = array('text' => 'New Subdivistion', 'class' => 'bdrlft', 'click' => $click, 'alt' => "Add New Subdivision");
        $sub_sub = BaseClass::BuildATags($menu);

        # Question Menu
        $click = "GetAsync('{$_SERVER['PHP_SELF']}?act=save&object=CustomerQuestion&parent=question_disp&entry=0&customer_id={$this->office_id}&corporate_office_id={$this->corporate_office_id}&is_office=$iso', SetGoalContents);";
        $menu[0] = array('text' => 'New Question', 'class' => 'bdrlft', 'click' => $click, 'alt' => "Add New Question");
        $question_sub = BaseClass::BuildATags($menu);

        # Goals Menu
        $type_id = ($this->is_office) ? CustomerGoal::$EXPECTATION_TYPE : CustomerGoal::$OPERATIONAL_TYPE;
        $click = "GetAsync('{$_SERVER['PHP_SELF']}?act=save&object=CustomerGoal&parent=goal_disp&entry=0&type_id=$type_id&customer_id={$this->office_id}&corporate_office_id={$this->corporate_office_id}&is_office=$iso', SetGoalContents);";
        $menu[0] = array('text' => 'New Goal', 'class' => 'bdrlft', 'click' => $click, 'alt' => "Add New Goal");
        $goal_sub = BaseClass::BuildATags($menu);

        if (!$this->is_office)
        {
            $click1 = "window.open('short_visit.php?react=newEvent&fid=$this->office_id&type=31&act=edit', 'CPC_FIELD_REMOTE_CONSULTATION','status=no,height=800,width=900,resizable=yes,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no')";
            $click2 = "window.open('short_visit.php?react=newEvent&fid=$this->office_id&type=32&act=edit', 'REMOTE_CLINICAL_SERVICE','status=no,height=800,width=900,resizable=yes,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no')";
            $menu[0] = array('text' => 'New CPC-Field Remote Consultation', 'class' => 'bdrlft', 'click' => $click1, 'alt' => "Add New CPC-Field Remote Consultation");
            $menu[1] = array('text' => 'New Remote Clinical Service', 'class' => 'bdrlft', 'click' => $click2, 'alt' => "Add New Remote Clinical Service");
        }

        $visit_sub = BaseClass::BuildATags($menu);

        $bar_html = "<section>
		<ul id='nav_menu' class='left_nav'>
			<li class='header'>
				SECTION NAVIGATION
			</li>";
        if ($this->is_clinic)
            $bar_html .= "
			<li class='treeview'>
				<a id='clinic_btn' data-toggle='collapse' data-target='#clinic_disp'>
					Site Information
				</a>
			</li>";
        else
            $bar_html .= "
			<li class='treeview'>
				<a id='detail_btn' onclick=\"ToggleSection('detail');\">
					Detail
				</a>
			</li>";

        if ($this->is_office)
            $bar_html .= "
			<li class='treeview'>
				<a id='task_btn' data-toggle='collapse' data-target='#task_disp'>
					Tasks
					<span class='pull-right-container'>
						<i class='fa fa-angle-down pull-right'></i>
						<span class='badge pull-right' id='task_nav_badge'></span>
					</span>
				</a>
				<ul id='task_sub' class='left_menu'>
					<li class='treeview active'>$task_sub</li>
				</ul>
			</li>";

        $bar_html .= "
			<li class='treeview'>
				<a id='question_btn' data-toggle='collapse' data-target='#question_disp'>
					Customer Questions
					<span class='pull-right-container'>
						<i class='fa fa-angle-down pull-right'></i>
					</span>
				</a>
				<ul id='question_sub' class='left_menu'>
					<li class='treeview'>$question_sub</li>
				</ul>
			</li>
			<li class='treeview'>
				<a id='goal_btn' data-toggle='collapse' data-target='#goal_disp'>
					Goals
					<span class='pull-right-container'>
						<i class='fa fa-angle-down pull-right'></i>
					</span>
				</a>
				<ul id='goal_sub' class='left_menu'>
					<li class='treeview'>$goal_sub</li>
				</ul>
			</li>
			<li class='treeview'>
				<a id='issue_btn' data-toggle='collapse' data-target='#issue_disp'>
					C.S. Issues
					<span class='pull-right-container'>
						<i class='fa fa-angle-down pull-right'></i>
						<span class='badge pull-right' id='iss_nav_badge'></span>
					</span>
				</a>
				<ul id='issue_sub' class='left_menu'>
					<li class='treeview'>$issue_sub</li>
				</ul>
			</li>
			<li class='treeview'>
				<a id='calendar_btn' data-toggle='collapse' data-target='#calendar_disp'>
					Calendar
				</a>
			</li>
			<li class='treeview'>
				<a id='visit_btn' data-toggle='collapse' data-target='#visit_disp'>
					Visits <span class='badge pull-right' id='visit_nav_badge'></span>
				</a>
                <ul id='visit_sub' class='left_menu'>
					<li class='treeview'>$visit_sub</li>
				</ul>
			</li>
			<li class='treeview'>
				<a id='contact_btn' data-toggle='collapse' data-target='#contact_disp'>
					Contacts / Addresses
					<span class='pull-right-container'>
						<i class='fa fa-angle-down pull-right'></i>
						<span class='badge pull-right'></span>
					</span>
				</a>
				<ul id='contact_sub' class='left_menu'>
					<li class='treeview'>$contact_sub</li>
				</ul>
			</li>";

        if ($this->is_office)
            $bar_html .= "
			<li class='treeview'>
				<a id='sub_btn' data-toggle='collapse' data-target='#sub_disp'>
					Subdivisions
					<span class='pull-right-container'>
						<i class='fa fa-angle-down pull-right'></i>
						<span class='badge pull-right'>$sub_count</span>
					</span>
				</a>
				<ul id='sub_sub' class='left_menu'>
					<li class='treeview'>$sub_sub</li>
				</ul>
			</li>
			<li class='treeview'>
				<a id='facility_btn' data-toggle='collapse' data-target='#facility_disp'>
					Facilities
					<span class='badge pull-right' id='fac_nav_badge'></span>
				</a>
			</li>";

        if ($this->is_office)
            $bar_html .= "
			<li class='treeview'>
				<a id='lead_btn' data-toggle='collapse' data-target='#lead_disp'>
					Leads
					<span class='badge pull-right' id='lead_nav_badge'></span>
				</a>
			</li>";
        else
        {
            $bar_html .= "
			<li class='treeview'>
				<a id='clin_btn' data-toggle='collapse' data-target='#clin_disp'>
					Clinical Recs. <span class='badge pull-right' id='clin_nav_badge'></span>
				</a>
			</li>
			<li class='treeview'>
				<a id='therapist_btn' data-toggle='collapse' data-target='#therapist_disp'>
					Therapist <span class='badge pull-right' id='therapist_nav_badge'></span>
				</a>
			</li>";
        }

        $bar_html .= "
			<li class='treeview'>
				<a id='equip_btn' data-toggle='collapse' data-target='#equip_disp'>
					Equipment <span class='badge pull-right' id='equip_nav_badge'></span>
				</a>
			</li>
			<li class='treeview'>
				<a id='contract_btn' data-toggle='collapse' data-target='#contract_disp'>
					Contracts <span class='badge pull-right' id='con_nav_badge'></span>
				</a>
			</li>
			<li class='treeview'>
				<a id='order_btn' data-toggle='collapse' data-target='#order_disp'>
					Orders <span class='badge pull-right' id='order_nav_badge'></span>
				</a>
			</li>
            <li class='treeview'>
				<a id='memo_btn' data-toggle='collapse' data-target='#memo_disp'>
					AR Collection <span class='badge pull-right' id='memo_nav_badge'></span>
				</a>
			</li>
			<li class='treeview'>
				<a id='invoice_btn' data-toggle='collapse' data-target='#invoice_disp'>
					Invoices <span class='badge pull-right' id='invoice_nav_badge'></span>
				</a>
			</li>
			<li class='treeview'>
				<a id='wo_btn' data-toggle='collapse' data-target='#wo_disp'>
					Work Orders <span class='badge pull-right' id='wo_nav_badge'></span>
				</a>
			</li>
		</ul>
	</section>
<script type='text/javascript'>
var btn_state;
$(function() {
btn_state = $btns;
});
</script>";

        return $bar_html;
    }

    /**
     * Returns the total number of nursing homes
     *
     * @return string
     */
    public function getNursingHomes($edit = 0)
    {
        $nursing_homes = $this->nursing_homes;
        if ($edit)
        {
            $nursing_homes = "<input type='text' name='nursing_homes' value='{$nursing_homes}' size='6' maxlength='12' />";
        }
        return $nursing_homes;
    }

    /**
     * @param string $default
     */
    public static function getCompanyList($default = 0, $corporate_parent_link = false)
    {
        $dbh = DataStor::getHandle();
        $list = '';

        $selected_ary = (is_array($default)) ? $default : array($default);
        $cpl_sql = ($corporate_parent_link) ? "and office_id not in ( select secondary_corporate_parent from corporate_parent_link where secondary_corporate_parent <> primary_corporate_parent )" : '';

        $sth = $dbh->prepare("
			SELECT office_id, office_name FROM corporate_office
			WHERE parent_id = 0 AND status <= 1
			{$cpl_sql}
			ORDER BY office_name");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_NUM))
        {
            list($office_id, $office_name) = $row;
            $selected = (in_array($office_id, $selected_ary)) ? 'selected' : '';
            $list .= "<option value=\"$office_id\" $selected>$office_name</option>\n";
        }

        return $list;
    }

    public static function getOnlyActiveCompanyListWithAbbreviation($default = 0, $corporate_parent_link = false)
    {
        $dbh = DataStor::getHandle();
        $list = '';

        $selected_ary = (is_array($default)) ? $default : array($default);
        $cpl_sql = ($corporate_parent_link) ? "and office_id not in ( select secondary_corporate_parent from corporate_parent_link where secondary_corporate_parent <> primary_corporate_parent )" : '';

        $sth = $dbh->prepare("
			SELECT office_id,account_id,office_name FROM corporate_office
			WHERE parent_id = 0 AND status = 1
			{$cpl_sql}
			ORDER BY office_name");
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_NUM))
        {
            list($office_id, $account_id, $office_name) = $row;
            $selected = (in_array($office_id, $selected_ary)) ? 'selected' : '';
            $list .= "<option value=\"$office_id\" $selected>$account_id --- $office_name</option>\n";
        }

        return $list;
    }

    /**
     * Return a contact
     *
     * @param mixed $index to the contact array also its DB ID
     * @return Contact|null
     */
    public function getContact($index)
    {
        $ret = null;

        if (isset ($this->contacts[$index]))
        {
            $ret = $this->contacts[$index];
        }

        return $ret;
    }


    /**
     * @param boolean $link_last
     * @return string
     */
    public function getBreadCrumb($link_last = false)
    {
        $breadcrumb = $this->breadcrumb;
        if ($link_last)
        {
            # replace our plain name with a link
            $breadcrumb = str_replace($this->getOfficeName(), "<a href='{$_SERVER['PHP_SELF']}?action=view&entry={$this->getId()}'>{$this->getOfficeName()}</a>", $breadcrumb);
        }
        return $breadcrumb;
    }

    /**
     * Returns the Website.
     *
     * Website is part of the company detail
     * Return from the 'Website' section the 'Website' attribute the 'Website' fields value
     *
     * @return string
     */
    public function getWebsite($edit = 0)
    {
        return isset ($this->detail['Website']['Website']['Website']) ? $this->detail['Website']['Website']['Website']['value'] : '';
    }

    /**
     * Handle requests to show goal notes
     *
     * @param string
     * @param integer
     *
     * @return string
     */
    public function GoalNotes(&$html, &$count)
    {
        $count = "";
        $html = "";
        $goal = new CustomerGoal();

        if (isset ($_REQUEST['goal_id']))
        {
            $goal->setVar('goal_id', $_REQUEST['goal_id']);
            $goal->loadNotes();
            $html = $goal->GetNoteDisplay();
        }

        return $html;
    }
    /**
     * Supply html for the goal section
     *
     * @return string
     */
    public function GoalSection()
    {
        # Goals Menu
        $iso = ($this->is_office) ? 1 : 0;
        $type_id = ($this->is_office) ? CustomerGoal::$EXPECTATION_TYPE : CustomerGoal::$OPERATIONAL_TYPE;
        $click = "GetAsync('{$_SERVER['PHP_SELF']}?act=save&object=CustomerGoal&parent=goal_disp&entry=0&type_id=$type_id&customer_id={$this->office_id}&corporate_office_id={$this->corporate_office_id}&is_office=$iso', SetGoalContents);";
        $menu[0] = array('text' => 'New Goal', 'class' => 'btn btn-default btn-sm', 'click' => $click, 'alt' => "Add New Goal");
        $goal_sub = BaseClass::BuildATags($menu);

        $section = "<div class='flx_cont'>
			<div id='goal_cont' class='box box-primary'>
				<div id='goal_hdr' class='box-header' style='cursor:pointer;' data-toggle='collapse' data-target='#goal_disp'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Customer Centric / Goals</h4>
				</div>
				<div class='box-header with-border fltr txar'>
					$goal_sub
				</div>
				<div id='goal_disp' class='box-body collapse'>
					<div class='on'>Loading...</div>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Handle requests to show html table
     *
     * @param string
     * @param integer
     *
     * @return string
     */
    public function GoalTable(&$html, &$count)
    {
        global $preferences, $date_fomat;

        ## Set up filter array
        $defaults = array("sort_by" => "g.type_id", "dir" => "ASC", "g_type" => "all", "g_status" => "all", "g_priority" => "all", "g_progress" => "all");
        $filter = SessionHandler::Update('crm', 'goal', $defaults);
        $count = 0;
        $this->LoadGoals($filter);

        $row_count = 0;
        $html = "";
        $rc = 'dt-tr-on';
        foreach ($this->goals as $goal)
        {
            $row_count++;
            $count = $goal->row_count;

            $html .= "<div id='customergoal_{$goal->goal_id}'>";
            $html .= CustomerGoal::GetDisplay($goal);
            $html .= "</div>";
        }

        if ($row_count == 0)
        {
            $html = "<div class='dt-tr-on'>No Goals found</div>";
        }

        return $html;
    }

    /**
     * Get html for invoice lines
     *
     * @param string
     * @param integer
     */
    public function InvoiceLines(&$html, &$count)
    {
        $count = "";
        $html = "";
        $rows = "";

        $rc = 'dt-tr-on';
        if (isset ($_REQUEST['invoice_num']))
        {
            $sth = $this->dbh->prepare("SELECT
				i.invoice_line_num,
				i.item_code,
				i.item_name,
				i.item_quantity,
				i.item_amount,
				i.tax,
				i.uom
			FROM invoice_line_items i
			WHERE invoice_num = ?
			ORDER BY i.invoice_line_num");
            $sth->bindValue(1, $_REQUEST['invoice_num'], PDO::PARAM_INT);
            $sth->execute();
            while ($item = $sth->fetch(PDO::FETCH_OBJ))
            {
                $count++;
                $rows .= "<tr class='$rc'>
					<td align='center'>{$item->invoice_line_num}</td>
					<td align='center'>{$item->item_code}</td>
					<td align='left'>{$item->item_name}</td>
					<td align='center'>{$item->item_quantity}</td>
					<td align='center'>{$item->uom}</td>
					<td align='center'>{$item->item_amount}</td>
					<td align='center'>{$item->tax}</td>
				</tr>";

                $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
            }

            $html = "<table class='dt' width='100%' cellspacing='0' cellpadding='4' border='1' style='clear:none;'>
				<tbody>
					<tr>
						<th class='hdr'>Line #</th>
						<th class='hdr'>Item Code</th>
						<th class='hdr'>Name</th>
						<th class='hdr'>Quantity</th>
						<th class='hdr'>UOM</th>
						<th class='hdr'>Amount</th>
						<th class='hdr'>Tax</th>
					</tr>
					$rows
				</tbody>
			</table>";
        }

        $html = "<div class='nested'>$html</div>";

        return $html;
    }

    /**
     * Supply html for the Invoice section
     *
     * @return string
     */
    public function InvoiceSection()
    {
        $section = "<div class='flx_cont'>
			<div id='invoice_cont' class='box box-primary'>
				<div id='invoice_hdr' class='box-header' data-toggle='collapse' data-target='#invoice_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Invoices</h4>
					<span class='badge' id='invoice_sec_badge'></span>
				</div>
				<div id='invoice_disp' class='box-body collapse'>
					<div class='on'>Loading...</div>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Get html for invoice lines
     *
     * @param string
     * @param integer
     */
    public function Invoice_bm_Lines(&$html, &$count)
    {

        $fp = isset ($_REQUEST['facility_pays']) ? $_REQUEST['facility_pays'] : false;
        $facility_pays = ($fp == 'Yes') ? true : false;

        $invoice_date = isset ($_REQUEST['invoice_date']) ? $_REQUEST['invoice_date'] : null;
        $invoice_type = isset ($_REQUEST['invoice_type']) ? $_REQUEST['invoice_type'] : 'lease';
        $consolidate_month = isset ($_REQUEST['consolidate_month']) ? (bool) $_REQUEST['consolidate_month'] : false;

        $ci = new ConsolidatedInvoice($this->account_id, $invoice_date);
        $contents = $ci->generate_cm_Invoice($invoice_type, $consolidate_month, $facility_pays);
        $html = render('templates/billing_manager/wrapper.php', array('contents' => $contents));

        $html = "<div class='nested'>$html</div>";

        return $html;
    }

    /**
     * Build datatable for the list of invoices  --- Make: bm_InvoiceTable(  --- Has no page and no filters
     */
    public function Invoice_bm_Table(&$html, &$count)
    {
        global $this_app_name, $preferences, $date_fomat;

        $defaults = array("page" => 1, "sort_by" => "invoice_date", "dir" => "ASC", "in_status" => "1", "in_type" => "all");
        SessionHandler::Update('crm', 'invoice', $defaults);

        $filter = $_SESSION['crm']['invoice'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $indinvch = "checked";
        $btchinvch = "";
        $add_type_display = "";

        if ($this->pull_invoice_type == 2)
        {
            $indinvch = "";
            $btchinvch = "checked";
        }

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 1;
        $sec_conf->disp = 'invoice_disp';
        $sec_conf->nav_badge = 'invoice_nav_badge';
        $sec_conf->sec_badge = 'invoice_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=Invoice_bm_Table&entry={$this->office_id}&is_office=$iso&pull_invoice_type=$this->pull_invoice_type";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_inv_cols";

        if ($this->pull_invoice_type == 2)
            $sec_conf->col_list_name = "co_btch_cols";

        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        #		echo "<pre>";
#		print_r($sec_conf);
#		echo "</pre>";

        $cs = count($sec_conf->cols) + 1;
        $count = 0;

        if ($this->pull_invoice_type == 1)
        {
            $this->Load_bm_Invoices($filter);
        }

        if ($this->pull_invoice_type == 2)
        {
            $this->Load_bm_batch_Invoices($filter, $this->account_id);
            $cs = count($sec_conf->cols);
            $sec_conf->show_action = null;
        }

        $row_count = 0;
        $rows = "";
        $rc = 'dt-tr-on';
        $invid = 0;

        foreach ($this->invoices as $invoice)
        {
            $invid++;
            $sec_conf->row_count++;
            $count = count($this->invoices);
            $note_url = '';
            $menu = '';

            if ($this->pull_invoice_type == 1)
            {

                $fp = ($invoice->facility_pays && $invoice->facility_pays == 'Yes') ? 1 : 0;
                $note_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=Invoice_bm_Lines&entry={$this->office_id}&is_office=$iso&invoice_date={$invoice->invoice_date} &invoice_type={$invoice->invoice_type}&facility_pays={$fp}&account_id={$invoice->account_id}";

                $ia = "";
                $sec_conf->rows .= "<tr class='{$rc}{$ia}'>";

                $tags = array();
                ## PDF Link
                $href = "cm_pdf_invoice.php?act=invoice&account_id={$invoice->account_id}&invoice_date={$invoice->invoice_date}&invoice_type={$invoice->invoice_type}&facility_pays={$fp}&cm=1";
                $tags[] = array('text' => "PDF", 'alt' => 'Generate PDF', 'href' => $href, 'target' => '_blank');
                $menu = BaseClass::BuildATags($tags);
            }

            foreach ($sec_conf->cols as $col)
            {
                if ($col->hdr == 'Status')
                {
                    if ($invoice->{$col->key} == 1)
                        $invoice->{$col->key} = "Open";
                    else if ($invoice->{$col->key} == 2)
                        $invoice->{$col->key} = "Closed";
                    else if ($invoice->{$col->key} == 3)
                        $invoice->{$col->key} = "Void";
                    else
                        $invoice->{$col->key} = "Invalid";
                }

                if ($col->hdr == 'Total')
                {
                    $invoice->{$col->key} = "$ " . number_format($invoice->{$col->key}, 2, '.', ',');
                }

                if ($col->hdr == 'Send Via')
                {
                    if (($invoice->count - $invoice->electronic_count) > 0)
                        $invoice->{$col->key} = "MAIL (" . $invoice->{$col->key} . ")";

                    if ($invoice->electronic_count > 0)
                        $invoice->{$col->key} = "Electronic (" . $invoice->{$col->key} . ")";
                }

                $sec_conf->rows .= "<td class='{$col->cls}'>{$invoice->{$col->key} }</td>";
            }

            if ($this->pull_invoice_type == 1)
            {
                $sec_conf->rows .= "<td class='nested' style='text-align:right;'>
						<div class='submenu'>
							$menu
							<span class='close_body' id='invoice_btn_{$invid}' onclick=\"if (SetInvoiceState($invid)) FillSection('{$note_url}','invoice_bdy_{$invid}','null','null');\">&nbsp;&nbsp;&nbsp;&nbsp;</span>
						</div>
					</td>
				</tr>
				<tr class='dt-tr-on'>
					<td colspan='$cs' style='padding: 0px;' id='invoice_bdy_{$invid}' class='short'></td>
				</tr>";
            }

            if ($this->pull_invoice_type == 2)
            {
                $sec_conf->rows .= "</tr>
				<tr class='dt-tr-on'>
					<td colspan='$cs' style='padding: 0px;' id='invoice_bdy_{$invid}' class='short'></td>
				</tr>";
            }

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        if ($count == 0)
        {
            $sec_conf->rows = "<tr class='dt-tr-on'><td colspan='$cs' id='no_invoices' align='center'>No Invoices found</td></tr>";
        }


        ## define attributes used to query for a page of data
        $base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=Invoice_bm_Table&is_office=$iso&entry={$this->office_id}";

        if ($this->parent_id == 0)
        {
            $add_type_display = "
			<span class='lbl'>Type:</span>
			<input type=radio name='pull_invoice_type' value='1' {$indinvch} onchange=\"FillSection('$base_url&page=1&pull_invoice_type=1','invoice_disp','invoice_nav_badge','invoice_sec_badge');\"> Individual
			<input type=radio name='pull_invoice_type' value='2' {$btchinvch} onchange=\"FillSection('$base_url&page=1&pull_invoice_type=2','invoice_disp','invoice_nav_badge','invoice_sec_badge');\"> Batch";
        }

        $s_ary = array(1 => 'Open', 2 => 'Closed', 3 => 'Void');
        $s_options = Forms::CreateOptionListFromArray($s_ary, $filter['in_status'], true);

        $sec_conf->filter = "
		<div class='filter nested' align='left'>
			<span class='lbl'>Status:</span>
			<select name='in_status' onchange=\"FillSection('$base_url&page=1&in_status='+this.value,'invoice_disp','invoice_nav_badge','invoice_sec_badge');\">
				<option value='all'>--All--</option>
				$s_options
			</select>
			$add_type_display
		</div>";

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }


    public function Load_bm_Invoices($filter)
    {
        $SORT_BY = $filter['sort_by'];
        $DIR = $filter['dir'];
        $LIMIT = $filter['limit'];
        $OFFSET = $filter['offset'];
        $status_clause = "";
        if (is_numeric($filter['in_status']))
            $status_clause = " AND ic.status = " . (int) $filter['in_status'];


        if ($this->is_office)
            $match = ($this->parent_id == 0) ? "co.corporate_office_id" : "co.office_id";
        else
            $match = "f.id";

        $this->invoices = array();

        $sth = $this->dbh->query("SELECT
			ic.invoice_date,
			f.accounting_id AS account_id,
			CASE
			WHEN o.type_id = 4 THEN 'install'
			WHEN o.type_id IN (2, 3, 8, 9, 10, 12) THEN 'supply'
			WHEN ic.contract_id IS NOT NULL THEN 'lease'
			WHEN ic.contract_id IS NULL AND ic.order_id IS NULL AND ic.ship_date IS NULL THEN 'lease'
			WHEN ic.contract_id IS NULL AND ic.order_id IS NULL AND ic.ship_date IS NOT NULL THEN 'supply'
			ELSE 'misc'
			END AS invoice_type,
			min(ic.status) as status,
			count(*) AS count,
			sum(ic.balance) AS total,
			CASE
			WHEN ic.contract_id IS NOT NULL AND c.facility_pay  THEN 'Yes' ELSE 'No'
			END AS facility_pays
		FROM invoice_current ic
		JOIN facilities f ON f.id = ic.facility_id
		LEFT JOIN contract c ON ic.contract_id = c.id_contract
		LEFT JOIN orders o ON ic.order_id = o.id
		LEFT JOIN corporate_office co ON f.parent_office = co.account_id
		WHERE
		$match = {$this->office_id}
		$status_clause
		AND ic.total_amount != 0.00
		AND (ic.contract_id IS NOT NULL OR o.type_id IN (2, 3, 4, 8, 9, 10, 12) OR (ic.contract_id IS NULL AND ic.order_id IS NULL))
		GROUP BY
		ic.invoice_date,
		f.accounting_id,
		invoice_type,
		facility_pays
		ORDER BY $SORT_BY $DIR
		LIMIT $LIMIT OFFSET $OFFSET");
        $this->invoices = $sth->fetchAll(PDO::FETCH_OBJ);
    }

    public function Load_bm_batch_Invoices($filter, $account_id, $send_via_counts = false)
    {
        $SORT_BY = $filter['sort_by'];
        $DIR = $filter['dir'];
        $LIMIT = $filter['limit'];
        $OFFSET = $filter['offset'];

        $this->invoices = array();

        $sql = <<<SQL
SELECT
    ic.invoice_date AS invoice_date,
    f.corporate_parent AS account_id,
    COUNT(*) AS count,
    SUM(ic.balance) AS total,
    SUM(CASE
        WHEN c.facility_pay AND bp_faclease.send_via = 'electronic' THEN 1
        WHEN ic.contract_id IS NOT NULL AND bp_corplease.invoice_template = 'consolidated' AND bp_corplease.send_via = 'electronic' THEN 1
        WHEN ic.contract_id IS NOT NULL AND bp_corplease.invoice_template != 'consolidated' AND bp_faclease.send_via = 'electronic' THEN 1
        WHEN o.type_id = 4 AND bp_corpinstall.invoice_template = 'consolidated' AND bp_corpinstall.send_via = 'electronic' THEN 1
        WHEN o.type_id = 4 AND bp_corpinstall.invoice_template != 'consolidated' AND bp_facinstall.send_via = 'electronic' THEN 1
        WHEN o.type_id IN (2, 3, 8, 9, 10, 12) AND bp_corpsupply.invoice_template = 'consolidated' AND bp_corpsupply.send_via = 'electronic' THEN 1
        WHEN o.type_id IN (2, 3, 8, 9, 10, 12) AND bp_corpsupply.invoice_template != 'consolidated' AND bp_facsupply.send_via = 'electronic' THEN 1
        WHEN ic.contract_id IS NULL AND ic.order_id IS NULL AND ic.ship_date IS NULL AND bp_corplease.invoice_template = 'consolidated' AND bp_corplease.send_via = 'electronic' THEN 1
        WHEN ic.contract_id IS NULL AND ic.order_id IS NULL AND ic.ship_date IS NULL AND bp_corplease.invoice_template != 'consolidated' AND bp_faclease.send_via = 'electronic' THEN 1
        WHEN ic.contract_id IS NULL AND ic.order_id IS NULL AND ic.ship_date IS NOT NULL AND bp_corpsupply.invoice_template = 'consolidated' AND bp_corplease.send_via = 'electronic' THEN 1
        WHEN ic.contract_id IS NULL AND ic.order_id IS NULL AND ic.ship_date IS NOT NULL AND bp_corpsupply.invoice_template != 'consolidated' AND bp_facsupply.send_via = 'electronic' THEN 1
        ELSE 0
    END) AS electronic_count
FROM invoice_current ic
JOIN facilities f ON f.id = ic.facility_id
LEFT JOIN contract c ON ic.contract_id = c.id_contract
LEFT JOIN orders o ON ic.order_id = o.id
LEFT JOIN billing_preference bp_corplease ON
    bp_corplease.account_id = f.corporate_parent
    AND bp_corplease.invoice_type = 'lease'
LEFT JOIN billing_preference bp_corpsupply ON
    bp_corpsupply.account_id = f.corporate_parent
    AND bp_corpsupply.invoice_type = 'supply'
LEFT JOIN billing_preference bp_corpinstall ON
    bp_corpinstall.account_id = f.corporate_parent
    AND bp_corpinstall.invoice_type = 'install'
LEFT JOIN billing_preference bp_faclease ON
    bp_faclease.account_id = f.accounting_id
    AND bp_faclease.invoice_type = 'lease'
LEFT JOIN billing_preference bp_facsupply ON
    bp_facsupply.account_id = f.accounting_id
    AND bp_facsupply.invoice_type = 'supply'
LEFT JOIN billing_preference bp_facinstall ON
    bp_facinstall.account_id = f.accounting_id
    AND bp_facinstall.invoice_type = 'install'
WHERE
    ic.status = 1
    AND ic.total_amount != 0.00
    AND (ic.contract_id IS NOT NULL OR o.type_id IN (2, 3, 4, 8, 9, 10, 12) OR (ic.contract_id IS NULL AND ic.order_id IS NULL))
    AND f.corporate_parent = ?
GROUP BY
    ic.invoice_date,
    f.corporate_parent
ORDER BY $SORT_BY $DIR
LIMIT $LIMIT OFFSET $OFFSET
SQL;

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare($sql);
        $sth->bindValue(1, $account_id, PDO::PARAM_STR);
        $sth->execute();
        $invoices = $sth->fetchAll(PDO::FETCH_OBJ);




        // This can be really slow, avoid using if possible.
        if ($send_via_counts)
        {
            $dbh->beginTransaction();

            foreach ($invoices as &$invoice)
            {
                $invoice['send_via'] = array(
                    'mail' => ConsolidatedInvoice::getBatchInvoiceCount($invoice['account_id'], $invoice['invoice_date'], 'mail'),
                    'electronic' => ConsolidatedInvoice::getBatchInvoiceCount($invoice['account_id'], $invoice['invoice_date'], 'electronic')
                );
            }

            $dbh->commit();
        }

        $this->invoices = $invoices;
    }



    /**
     * Build datatable for the list of invoices
     */
    public function InvoiceTable(&$html, &$count)
    {
        global $this_app_name, $preferences, $date_fomat;

        $defaults = array("page" => 1, "sort_by" => "invoice_date", "dir" => "ASC", "in_status" => "1", "in_type" => "all");
        SessionHandler::Update('crm', 'invoice', $defaults);
        $filter = $_SESSION['crm']['invoice'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 1;
        $sec_conf->disp = 'invoice_disp';
        $sec_conf->nav_badge = 'invoice_nav_badge';
        $sec_conf->sec_badge = 'invoice_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=InvoiceTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_invoice_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));
        $cs = count($sec_conf->cols) + 1;

        $count = 0;
        $all_invoice_total = 0;
        $all_product_total = 0;
        $page_invoice_total = 0;
        $page_product_total = 0;

        $this->LoadInvoices($filter);

        $row_count = 0;
        $rows = "";
        $rc = 'dt-tr-on';
        foreach ($this->invoices as $invoice)
        {
            $sec_conf->row_count++;
            $count = $invoice->row_count;
            $sec_conf->count = $invoice->row_count;
            $all_invoice_total = $invoice->invoice_total;
            $all_product_total = $invoice->product_total;
            $page_invoice_total += $invoice->total_amount;
            $page_product_total += $invoice->total_product_cost;

            $tags = array();
            ## Contract Link
            if ($invoice->contract_id > 0)
            {
                $href = "contract_maintenance.php?facility_id={$invoice->facility_id}&contract_id={$invoice->contract_id}";
                $tags[] = array('text' => "contract", 'alt' => 'View Contract', 'href' => $href, 'target' => '_blank');
            }
            ## Order Link
            if ($invoice->order_id > 0)
            {
                $href = "orderfil.php?act=detail&order_id={$invoice->order_id}";
                $tags[] = array('text' => "order", 'alt' => 'View Order Details', 'href' => $href, 'target' => '_blank');
            }

            $menu = BaseClass::BuildATags($tags);
            $ia = ($invoice->status_text == 'Closed') ? " faded" : "";

            $note_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=InvoiceLines&entry={$this->office_id}&is_office=$iso&invoice_num={$invoice->invoice_num}";

            $sec_conf->rows .= "<tr class='{$rc}{$ia}'>";
            foreach ($sec_conf->cols as $col)
            {
                $sec_conf->rows .= "<td class='{$col->cls}'>{$invoice->{$col->key} }</td>";
            }
            $sec_conf->rows .= "<td class='nested' style='text-align:right;'>
					<div class='submenu'>
						$menu
						<span class='close_body' id='invoice_btn_{$invoice->invoice_num}' onclick=\"if (SetInvoiceState($invoice->invoice_num)) FillSection('$note_url&invoice_num={$invoice->invoice_num}','invoice_bdy_{$invoice->invoice_num}','null','null');\">&nbsp;&nbsp;&nbsp;&nbsp;</span>
					</div>
				</td>
			</tr>
			<tr class='dt-tr-on'>
				<td colspan='$cs' style='padding: 0px;' id='invoice_bdy_{$invoice->invoice_num}' class='short'></td>
			</tr>";

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        if ($count == 0)
        {
            $rows = "<tr class='dt-tr-on'><td colspan='$cs' id='no_invoices' align='center'>No Invoices found</td></tr>";
        }
        else
        {
            $page_invoice_total = number_format($page_invoice_total, 2);
            $page_product_total = number_format($page_product_total, 2);
            $all_invoice_total = number_format($all_invoice_total, 2);
            $all_product_total = number_format($all_product_total, 2);

            $sec_conf->rows .= "<tr class='total'>
				<td colspan=$cs>
					<span style='float:left; width: 50%;'>
					Page Invoice Total: {$page_invoice_total}
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					Page Product Total: {$page_product_total}
					</span>
					<span style='float:left; width: 50%;'>
					All Invoice Total: {$all_invoice_total}
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					All Product Total: {$all_product_total}
					</span>
				</td>
			</tr>";
        }

        ## define attributes used to query for a page of data
        $base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=InvoiceTable&is_office=$iso&entry={$this->office_id}";
        $s_ary = array(1 => 'Open', 2 => 'Closed', 3 => 'Void');
        $t_ary = array(501 => 'AR Invoice (501)', 502 => 'AR Credit (502)', 503 => 'AR Debit (503)');
        $s_options = Forms::CreateOptionListFromArray($s_ary, $filter['in_status'], true);
        $t_options = Forms::CreateOptionListFromArray($t_ary, $filter['in_type'], true);

        $sec_conf->filter = "
		<div class='filter nested' align='left'>
			<span class='lbl'>Status:</span>
			<select name='in_status' onchange=\"FillSection('$base_url&page=1&in_status='+this.value,'invoice_disp','invoice_nav_badge','invoice_sec_badge');\">
				<option value='all'>--All--</option>
				$s_options
			</select>
			<span class='lbl'>Type:</span>
			<select name='in_type' onchange=\"FillSection('$base_url&page=1&in_type='+this.value,'invoice_disp','invoice_nav_badge','invoice_sec_badge');\">
				<option value='all'>--All--</option>
				$t_options
			</select>
		</div>";

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    /**
     * Handle requests to show Notes
     *
     * @param string
     * @param integer
     *
     * @return string
     */
    public function IssueAttachments(&$html, &$count)
    {
        $count = "";
        $html = "";
        $issue = new CorpIssue();

        if (isset ($_REQUEST['issue_id']))
        {
            $issue->setVar('issue_id', $_REQUEST['issue_id']);
            $issue->loadAttachments();
            $html = $issue->GetAttachmentDisplay();
        }

        return $html;
    }

    /**
     * Supply html for the Issue section
     *
     * @return string
     */
    public function IssueSection()
    {
        global $user;

        # Issue Sub Menu
        $iso = ($this->is_office) ? 1 : 0;
        $issue_count = count($this->issues);
        $office_name = urlencode($this->office_name);
        $iss_obj = ($this->is_office) ? 'office' : 'facility';
        $new_issue = "OpenIssueWindow('issue_edit.php?submit_action=edit&issue_id=0&office_id={$this->getId(0)}&object=$iss_obj&corporate_office_id={$this->corporate_office_id}&office_name={$office_name}');";
        $menu[0] = array('text' => 'New CS Issue', 'class' => 'btn btn-default btn-sm', 'click' => $new_issue, 'alt' => "Add New Conversation Thread");
        $issue_sub = BaseClass::BuildATags($menu);

        $section = "<div class='flx_cont'>
			<div id='issue_cont' class='box box-primary'>
				<div id='issue_hdr' class='box-header' data-toggle='collapse' data-target='#issue_disp' style='cursor:pointer;'>
					<i class='fa fa-minus pull-right'></i>
					<h4 class='box-title'>Customer Support Issues</h4>
					<span class='badge' id='iss_sec_badge'></span>
				</div>
				<div class='box-header with-border fltr txar'>
					$issue_sub
					<a class='btn btn-primary btn-sm' target='_blank' alt='Create PM Swap' title='Create PM Swap' onclick=\"window.open('pm_swap.php?issue_id=0&complaint_type=Swap&office_id={$this->office_id}&corporate_office_id={$this->corporate_office_id}','pm_swap','toolbar=no,scrollbars=yes,resizable=yes');\">
						<i class='fa fa-refresh'></i> PM SWAP
					</a>
					<a class='btn btn-info btn-sm' target='_blank' alt='Create PM RMA' title='Create PM RMA' onclick=\"window.open('pm_swap.php?issue_id=0&complaint_type=RMA&office_id={$this->office_id}&corporate_office_id={$this->corporate_office_id}','pm_swap','toolbar=no,scrollbars=yes,resizable=yes');\">
						<i class='fa fa-refresh'></i> PM RMA
					</a>
				</div>
				<div id='issue_disp' class='box-body collapse in'>
					<div class='on'>Loading...</div>
					<script type='text/javascript'>$(function () { CRMLoader.LoadContent('issue', false); });</script>
				</div>
			</div>
		</div>";

        $section .= "<div id='issue_attachment_edit'>";
        $attachment = new Attachment(0);
        $attachment->reference_elem = 'CorpIssue';
        ob_start();
        include ('templates/crm/attachment_edit.php');
        $section .= ob_get_contents();
        ob_end_clean();
        $section .= "</div>
		<div id='note_edit'><div id='note_cont'></div></div>
		<div id='note_history'><div id='history_cont' style='max-height: 400;'></div></div>
<script type='text/javascript'>
var note_conf = {
	autoOpen: false,
	resizable: true,
	title: 'Edit Note',
    height: '400',
    width: 'auto',
    modal: false,
    buttons: [
		{
			text: 'Save',
			click: function() {

				tinymce.triggerSave();
				var frm = $('#note_cont').find('form');
				var uri = frm.attr('action');
				var req = $.ajax({
					method: 'POST',
					url: uri,
					data: frm.serialize(),
					dataType: 'json'
				})
				.done(function (result, status, ajxObj) {
					$('#'+result.id).html(result.HTML);
				})
				.fail(function () {
					alert('Save Failed');
				});

				$(this).dialog('close');
			}
		},
		{ text: 'OK', click: function() { $(this).dialog('close') } }
	]
};
$(function() {
	$('#note_edit').dialog(note_conf);
	$('#note_history').dialog({autoOpen: false, title: 'History'});
	$('#issue_attachment_edit').dialog(att_conf);
});
</script>";

        return $section;
    }

    /**
     * Handle requests to show Notes
     *
     * @param string
     * @param integer
     *
     * @return string
     */
    public function IssueNotes(&$html, &$count)
    {
        $count = "";
        $html = "";
        $issue = new CorpIssue();

        if (isset ($_REQUEST['issue_id']))
        {
            $issue->setVar('issue_id', $_REQUEST['issue_id']);
            $issue->loadAttachments();
            $issue->loadNotes();
            $html = $issue->GetAttachmentDisplay();
            $html .= $issue->GetNoteDisplay();
        }

        return $html;
    }

    /**
     * Handle requests to show Issue Table
     *
     * @param string
     * @param integer
     *
     * @return string
     */
    public function IssueTable(&$html, &$count)
    {
        global $this_app_name, $preferences, $date_fomat;

        $defaults = array("page" => 1, "sort_by" => "open_date", "dir" => "DESC", "i_category" => "all", "i_status" => "all", "i_priority" => "all");
        SessionHandler::Update('crm', 'issue', $defaults);
        $filter = $_SESSION['crm']['issue'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 1;
        $sec_conf->disp = 'issue_disp';
        $sec_conf->nav_badge = 'iss_nav_badge';
        $sec_conf->sec_badge = 'iss_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=IssueTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_issue_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        $this->LoadIssues($filter);

        $rc = 'dt-tr-on';
        foreach ($this->issues as $issue)
        {
            $sec_conf->row_count++;
            $count = $issue->row_count;
            $sec_conf->count = $issue->row_count;
            $issue->is_office = $this->is_office;
            $issue->rc = $rc;
            $sec_conf->rows .= CorpIssue::GetDisplay($issue, $sec_conf->cols);
            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        if ($count == 0)
        {
            $cs = count($sec_conf->cols) + 1;
            $sec_conf->rows = "<tr id='no_issues' class='dt-tr-on'><td colspan=$cs style='text-align:center;'>No Issues found</td></tr>";
        }

        ## define attributes used to query for a page of data
        $base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=IssueTable&is_office=$iso&entry={$this->office_id}";

        $s_options = CorpIssue::CreateStatusList($filter['i_status']);
        $c_options = CorpIssue::CreateCategoryList($filter['i_category']);
        $p_options = CorpIssue::CreatePriorityList($filter['i_priority']);

        $sec_conf->filter = "
		<div class='filter nested' align='left'>
			<span class='lbl'>Status:</span>
			<select name='i_status' onchange=\"FillSection('$base_url&page=1&i_status='+this.value,'issue_disp','iss_nav_badge','iss_sec_badge');\">
				<option value='all'>--All--</option>
				$s_options
			</select>
			<span class='lbl'>Category:</span>
			<select name='i_category' onchange=\"FillSection('$base_url&page=1&i_category='+this.value,'issue_disp','iss_nav_badge','iss_sec_badge');\">
				<option value='all'>--All--</option>
				$c_options
			</select>
			<span class='lbl'>Priority:</span>
			<select name='i_priority' onchange=\"FillSection('$base_url&page=1&i_priority='+this.value,'issue_disp','iss_nav_badge','iss_sec_badge');\">
				<option value='all'>--All--</option>
				$p_options
			</select>
		</div>";

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    /**
     * Supply html for the Sales Leads section
     *
     * @return string
     */
    public function LeadSection()
    {
        $section = "<div class='flx_cont'>
			<div id='lead_cont' class='box box-primary'>
				<div id='lead_hdr' class='box-header' data-toggle='collapse' data-target='#lead_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Sales Leads</h4>
					<span class='badge' id='lead_sec_badge'></span>
				</div>
				<div id='lead_disp' class='box-body collapse'>
					<div class='on'>Loading...</div>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Build html table for the Sales leads
     */
    public function LeadTable(&$html, &$count)
    {
        global $this_app_name, $preferences, $date_fomat;

        $defaults = array("page" => 1, "sort_by" => "c.office_name", "dir" => "ASC");
        SessionHandler::Update('crm', 'leads', $defaults);
        $filter = $_SESSION['crm']['leads'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 0;
        $sec_conf->disp = 'lead_disp';
        $sec_conf->nav_badge = 'lead_nav_badge';
        $sec_conf->sec_badge = 'lead_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=LeadTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_lead_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        $this->LoadLeads($filter);

        $sum_revenue = 0;
        $sum_opps = 0;
        $rc = 'dt-tr-on';
        foreach ($this->leads as $lead)
        {
            $sec_conf->row_count++;
            $count = $lead->total_records;
            $sec_conf->count = $lead->total_records;

            $name = htmlentities($lead->office_name, ENT_QUOTES);

            $tag['text'] = $name;
            $tag['alt'] = "View Sales Lead";
            $tag['href'] = "salesmanagement.php?act=view&object=SalesCustomer&entry={$lead->office_id}";
            $tag['target'] = "_blank";
            $lead->office_name = BaseClass::BuildATags(array($tag));

            $sum_revenue += $lead->contract_value;
            $lead->contract_value = number_format($lead->contract_value, 2);
            $sum_opps += $lead->opportunities;

            $sec_conf->rows .= "<tr class='$rc' ondblclick=\"window.location='{$tag['href']}';\">";
            foreach ($sec_conf->cols as $col)
            {
                $sec_conf->rows .= "<td class='{$col->cls}'>{$lead->{$col->key} }</td>";
            }
            $sec_conf->rows .= "</tr>";

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        $cs = count($sec_conf->cols);
        if ($count == 0)
        {
            $sec_conf->rows = "<tr class='on' id='no_leads' align='center'><td colspan=$cs>No leads found.</td></tr>";
        }
        else
        {
            $sum_revenue = number_format($sum_revenue, 2);
            $sec_conf->rows .= "<tr class='total'>
				<td style='text-align:left;' colspan=$cs>
					Contract Value: $sum_revenue
				</td>
			</tr>";
        }

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    public function LeaseInfo()
    {
        global $user;

        $addon_access = $user->hasAccessToApplication('contract_addon');
        $user_id = $user->getId();
        $price_link = "";
        $is_field_person = $user->inField() ? 1 : 0;

        $sth = $this->dbh->prepare("SELECT
			l.lease_id,
			l.cust_id,
			l.master_agreement,
			l.addon_amendment,
			l.cancellable,
			l.effective_date,
			l.visit_frequency,
			l.period,
			l.renewal_period,
			l.payment_term_id,
			l.length_term_id,
			l.signatory_contact,
			s.title as signatory_title,
			s.first_name || ' ' || s.last_name as signatory_name,
			clt.term as length_term,
			cpt.term_disp as payment_term,
			ctp.display_text as termination
		FROM lease_agreement l
		LEFT JOIN corporate_office co ON l.cust_id = co.account_id
		LEFT JOIN contact s ON l.signatory_contact = s.contact_id
		LEFT JOIN contract_length_term clt ON l.length_term_id = clt.id
		LEFT JOIN contract_payment_term cpt ON l.payment_term_id = cpt.id
		LEFT JOIN contract_period ctp on l.termination_notice_period_id = ctp.period_id
		WHERE co.office_id = ?");
        $sth->bindValue(1, (int) $this->corporate_office_id, PDO::PARAM_INT);
        $sth->execute();
        $lease = $sth->fetch(PDO::FETCH_OBJ);

        if ($lease)
        {
            $l_type = ($lease->master_agreement) ? "Master" : "Individual";
            $l_addon = ($lease->addon_amendment) ? "Yes" : "No";
            $cancellable = ($lease->cancellable) ? "Yes" : "No";
            $length_term = $lease->length_term;
            $termination = $lease->termination;
            $renewal_period = $lease->renewal_period;
            $payment_term = $lease->payment_term;

            if ($addon_access)
            {
                $l_type = "<a id='l_terms_{$lease->cust_id}' href=\"javascript: MLDialog('{$lease->lease_id}','{$lease->cust_id}',1);\">$l_type</a>";
                $price_link = "<a id='l_amount_{$lease->cust_id}' onclick=\"InitDialog(msg_conf, 'templates/sm/admin/product_pricing.php?lease_id={$lease->lease_id}&cust_id={$lease->cust_id}');\">Pricing</a>";
            }
            if ($is_field_person == 1)
            {
                $price_link = "<a id='l_amount_{$lease->cust_id}' href=\"javascript: InitPrDialog('{$lease->lease_id}','{$lease->cust_id}');\">Pricing</a>";
            }
        }
        else
        {
            $l_type = "None";
            $l_addon = "N/A";
            $cancellable = "N/A";
            $termination = "N/A";
            $length_term = "N/A";
            $renewal_period = "N/A";
            $payment_term = "N/A";
            $price_link = "";
        }

        $la = new LeaseAgreement(0);
        $lease_detail = "
		<div id='lease_dialog' style='visibility: hidden; position:absolute;'>
			<div class='hd'>Contract Terms</div>
			<div class='bd' style='background-color: white; padding:0; text-align:left;'>
			</div>
			<div class='ft' style='padding:5px;'></div>
		</div>
		<div id='pricing_dialog' style='visibility: hidden; position:absolute;'>
			<div class='hd'>Lease Pricing</div>
			<div class='bd' style='background-color: white; padding:0; text-align:left;'></div>
			<div class='ft' style='padding:5px;'></div>
		</div>
		<table class='e_form' width='100%' cellpadding='4' cellspacing='2'>
			<tr>
				<th>Lease Type:</th>
				<td>$l_type</td>
				<th>Addon Agreement:</th>
				<td>$l_addon</td>
				<th>Cancellable:</th>
				<td>$cancellable</td>
				<th>Termination:</th>
				<td>$termination</td>
				<th>Term Length:</th>
				<td>$length_term</td>
				<th>Renewal Period:</th>
				<td>$renewal_period Year(s)</td>
				<th>Payment Terms:</th>
				<td>$payment_term</td>
				<td>$price_link</td>
			</tr>
		</table>";

        return $lease_detail;
    }

    /**
     * Populates this Office object from the matching record in the
     * database.
     *
     * Can either load from office_id or account_id
     *
     * @throws Exception
     */
    public function load()
    {
        global $user, $page;

        # Dont have office_id but we have an account_id then find our office_id
        if (empty ($this->office_id) && $this->account_id)
        {
            $sth = $this->dbh->prepare('
				SELECT office_id FROM corporate_office where account_id = ?');
            $sth->bindValue(1, $this->account_id, PDO::PARAM_STR);
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->office_id = $row['office_id'];
            }
            else
            {
                throw new Exception('The Corporate Office you are trying to view does not exist. (1)');
            }
        }

        # Load the record
        if ($this->office_id)
        {
            $sth = $this->dbh->prepare('
				SELECT
					office_id,
					parent_id,
					account_id,
					status,
					s.office_status AS s_status,
					last_mod,
					last_mod_by,
					office_name,
					orientation,
					nursing_homes,
					acp_nursing_homes,
					corporate_office_id,
					account_executive
				FROM corporate_office c
				LEFT OUTER JOIN corporate_office_status s ON c.status = s.status_id
	  			WHERE c.office_id = ?');
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->office_id = $row['office_id'];
                $this->parent_id = $row['parent_id'];
                $this->account_id = trim($row['account_id']);
                $this->status = $row['status'];
                $this->s_status = trim($row['s_status']);
                $this->last_mod = $row['last_mod'];
                $this->last_mod_by = $row['last_mod_by'];
                $this->office_name = trim($row['office_name']);
                $this->orientation = trim($row['orientation']);
                $this->nursing_homes = trim($row['nursing_homes']);
                $this->acp_nursing_homes = trim($row['acp_nursing_homes']);
                $this->corporate_office_id = trim($row['corporate_office_id']);
                $this->account_executive = trim($row['account_executive']);
            }
            else
            {
                throw new Exception('The Corporate Office you are trying to view does not exist. (2)');
            }

            # Load the contact
            # First we add a empty place holder incase we dont have one saved
            $this->contacts = array();
            $this->contacts[0] = new Contact();
            $this->contacts[0]->setIsOffice(true);

            $sth = $this->dbh->prepare("SELECT
				c.*, j.office_id
			FROM contact c
			LEFT JOIN corporate_office_contact_join j ON c.contact_id = j.contact_id
			WHERE j.office_id = ?
			AND c.is_office = true
			ORDER BY c.last_name, c.first_name");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $contact = new Contact();
                $contact->CopyFromArray($row);
                $this->contacts[0] = $contact;
            }
        }
        # Empty Record create space for contact info
        else
        {
            # We will always need one office contact
            if (!isset ($this->contacts[0]))
            {
                $this->contacts[0] = new Contact();
                $this->contacts[0]->setIsOffice(true);
            }
        }

        /*
         * Load default office and company detail
         * This will define the structure for the detail display
         * The office is not required to keep any data but the array must exist
         *
         * Resides in DB table with office_id 0
         */
        $this->LoadDetail(TRUE);
        if (!isset ($_REQUEST['skip_mas']))
        {
            $this->SetPaymentTerms();
        }
    }

    /**
     * Fill clin rec array
     *
     * @param array
     */
    public function LoadClinRecs($filter)
    {
        if ($this->office_id)
        {
            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            if (!$this->is_office)
            {
                # Match the facility
                $match = "p.facility_id";
            }
            else if ($this->parent_id == 0)
            {
                # Match all
                $match = "co.corporate_office_id";
            }
            else
            {
                # Match those for this subdivision
                $match = "co.office_id";
            }

            # Reset array
            $this->clinrecs = array();

            # Load the information
            $sth = $this->dbh->prepare("SELECT
				p.id AS patient_id,
				p.facility_id AS facility_id,
				CASE p.source
					WHEN 1 THEN 'Facility Visit'
					WHEN 2 THEN 'Phone/Fax'
					WHEN 3 THEN 'Email/Text'
					WHEN 4 THEN 'PM Visit'
					WHEN 5 THEN 'Brief Visit'
					ELSE 'Unknown'
				END AS source,
				p.date_entered AS date_entered,
				p.patient_initials AS patient_initials,
				p.therapist_initials AS therapist_initials,
				CASE p.worksheet_provided
					WHEN true THEN 'Yes'
					ELSE 'No'
				END AS wksht_prov,
				p.discipline AS discipline,
				p.comment AS comment,
				p.result AS result_id,
				p.new_patient AS new_patient,
				pr.id AS program_id,
				pr.name AS program_name,
				rslt.description AS result,
				COUNT(*) OVER () as row_count
			FROM clinrec_patient p
			INNER JOIN clinrec_result rslt ON p.result = rslt.id
			INNER JOIN clinrec_patient_program pt_pr ON p.id = pt_pr.clinrec_patient_id
			INNER JOIN clinrec_program pr ON pt_pr.clinrec_program_id = pr.id
			INNER JOIN facilities f on p.facility_id = f.id
			LEFT JOIN corporate_office co ON f.parent_office = co.account_id
			WHERE $match = ?
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            $this->clinrecs = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Fill contact array
     */
    public function LoadContacts()
    {
        if ($this->office_id && count($this->contacts) <= 1)
        {
            if ($this->is_office)
            {
                # Reset array
                $office = $this->contacts[0];
                $this->contacts = array(0 => $office);

                # Load the contact
                $sth = $this->dbh->prepare("SELECT
					 c.*, j.office_id
				FROM contact c
				LEFT JOIN corporate_office_contact_join j ON c.contact_id = j.contact_id
				WHERE c.is_office = false
				AND j.office_id = ?
				ORDER BY c.last_name, c.first_name");
                $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
                $sth->execute();
                while ($row = $sth->fetch(PDO::FETCH_ASSOC))
                {
                    $row['updatetbl'] = '1';
                    $contact = new Contact();
                    $contact->CopyFromArray($row);
                    $this->contacts[$row['contact_id']] = $contact;
                }
            }
            else
            {
                # Load the contact wich are linked to the facility
                # Using Role as special key since facility details have specific information saved
                # Contact is a more complete set of attributes but was not intended to use role for any special assignment
                $sth = $this->dbh->prepare("SELECT
					c.*,
					j.facility_id,
					j.default_billing,
					j.default_shipping
				FROM contact c
				LEFT JOIN facility_contact_join j ON c.contact_id = j.contact_id
				WHERE j.facility_id = ?
				ORDER BY c.last_name, c.first_name");
                $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
                $sth->execute();
                while ($row = $sth->fetch(PDO::FETCH_ASSOC))
                {
                    if (strtolower($row['role']) == 'fa')
                        $key = 'fa';
                    else if (strtolower($row['role']) == 'don')
                        $key = 'don';
                    else if (strtolower($row['role']) == 'mm')
                        $key = 'mm';
                    else if (strtolower($row['role']) == 'frd')
                        $key = 'frd';
                    else if (strtolower($row['role']) == 'frd rm')
                        $key = 'frd rm';
                    else if (strtolower($row['role']) == 'mds')
                        $key = 'mds';
                    else
                        $key = $row['contact_id'];

                    $row['updatetbl'] = '1';
                    $contact = new Contact();
                    $contact->CopyFromArray($row);
                    if ($row['default_shipping'])
                        $row['is_office'] = true;
                    $this->contacts[$key] = $contact;
                }

                # Facility detail may have additional contact information
                # Use this limited data to populate a contact object
                # Again role is important to keep
                $sth = $this->dbh->prepare("SELECT
					fd.fa,
					fd.fa_email,
					fd.don,
					fd.don_email,
					fd.mm,
					fd.mm_email,
					fd.frd,
					fd.frd_email,
					fd.frd_rm,
					fd.frd_rm_email,
					fd.frd_rm_phone,
					fd.mds,
					fd.mds_email
				FROM facilities_details fd
				WHERE fd.facility_id = ?");
                $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
                $sth->execute();
                $row = $sth->fetch(PDO::FETCH_ASSOC);
                $row['updatetbl'] = '2';

                # Load mock fa contact
                if (!isset ($this->contacts['fa']))
                {
                    $key = 'fa';
                    if (!isset ($this->contacts[$key]))
                    {
                        $this->contacts[$key] = new Contact();
                        $this->contacts[$key]->setVar('facility_id', $this->office_id);
                    }
                    $this->contacts[$key]->LoadFacilityInfo($key, $row);
                }
                # Load mock don contact
                if (!isset ($this->contacts['don']))
                {
                    $key = 'don';
                    if (!isset ($this->contacts[$key]))
                    {
                        $this->contacts[$key] = new Contact();
                        $this->contacts[$key]->setVar('facility_id', $this->office_id);
                    }
                    $this->contacts[$key]->LoadFacilityInfo($key, $row);
                }
                # Load mock mm contact
                if (!isset ($this->contacts['mm']))
                {
                    $key = 'mm';
                    if (!isset ($this->contacts[$key]))
                    {
                        $this->contacts[$key] = new Contact();
                        $this->contacts[$key]->setVar('facility_id', $this->office_id);
                    }
                    $this->contacts[$key]->LoadFacilityInfo($key, $row);
                }
                # Load mock fdr contact
                if (!isset ($this->contacts['frd']))
                {
                    $key = 'frd';
                    if (!isset ($this->contacts[$key]))
                    {
                        $this->contacts[$key] = new Contact();
                        $this->contacts[$key]->setVar('facility_id', $this->office_id);
                    }
                    $this->contacts[$key]->LoadFacilityInfo($key, $row);
                }
                # Load mock fdr_rm contact
                if (!isset ($this->contacts['frd rm']))
                {
                    $key = 'frd rm';
                    if (!isset ($this->contacts[$key]))
                    {
                        $this->contacts[$key] = new Contact();
                        $this->contacts[$key]->setVar('facility_id', $this->office_id);
                    }
                    $this->contacts[$key]->LoadFacilityInfo($key, $row);
                }
                # Load mock mds contact
                if (!isset ($this->contacts['mds']))
                {
                    $key = 'mds';
                    if (!isset ($this->contacts[$key]))
                    {
                        $this->contacts[$key] = new Contact();
                        $this->contacts[$key]->setVar('facility_id', $this->office_id);
                    }
                    $this->contacts[$key]->LoadFacilityInfo($key, $row);
                }
            }
        }
    }

    /**
     * Fill contract array
     *
     * @param array
     */
    public function LoadContracts($filter)
    {
        if ($this->office_id)
        {
            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            $purchase = LeaseContract::$PURCHASE_TYPE;
            $loaner = LeaseContract::$LOANER_TYPE;
            $supply = LeaseContract::$SUPPLY_TYPE;

            $status_clause = "";
            if ($filter['c_show'] == "a")
                $status_clause = " AND c.date_cancellation IS NULL";
            else if ($filter['c_show'] == "c")
                $status_clause = " AND c.date_cancellation IS NOT NULL";

            $type_clause = "AND c.id_contract_type NOT IN ($purchase,$loaner)";
            if ($filter['c_type'] == "p")
                $type_clause = " AND c.id_contract_type = $purchase";
            else if ($filter['c_type'] == "n")
                $type_clause = " AND c.id_contract_type = $loaner";
            else if ($filter['c_type'] == "s")
                $type_clause = " AND c.id_contract_type = $supply";
            else if ($filter['c_type'] == "a")
                $type_clause = "";

            # Match the facility, or corporate_office or subdivision
            if (!$this->is_office)
                $match = "f.id";
            else if ($this->parent_id == 0)
                $match = "co.corporate_office_id";
            else
                $match = "co.office_id";

            ## Getting much better performance using f.id set as a filter
            if (isset ($_SESSION['crm']['set'][$match][$this->office_id]))
                $facility_list = $_SESSION['crm']['set'][$match][$this->office_id];
            else
                $facility_list = $this->SetCustomerList();

            # Reset array
            $this->contracts = array();

            # Load the contact
            $sth = $this->dbh->prepare("SELECT
				c.id_contract,
				c.id_facility,
				c.date_install,
				c.date_cancellation,
				c.monthly_revenue as \"revenue\",
				ce.onetime_revenue as \"amount\",
				CASE c.id_contract_type
					WHEN $purchase THEN 'Purchase'
					WHEN $loaner THEN 'Loaner'
					ELSE 'Lease'
				END as \"con_type\",
				c.date_lease,
				CASE c.da_received
					WHEN 1 THEN 'Yes'
					ELSE 'No'
				END as \"da_received\",
				date_da,
				CASE c.contract_received
					WHEN 1 THEN 'Yes'
					ELSE 'No'
				END as \"contract_received\",
				c.date_received,
				c.date_billed_through,
				CASE c.non_cancellable
					WHEN true THEN 'Non Cancellable'
					ELSE 'Cancellable'
				END as \"cancellable\",
				CASE c.market_basket
					WHEN true THEN 'Yes'
					ELSE 'No'
				END as \"market_basket\",
				CASE c.ip_marketing
					WHEN true THEN 'Yes'
					ELSE 'No'
				END as \"ip_marketing\",
				c.visit_frequency,
				CASE c.facility_pay
					WHEN true THEN 'Yes'
					ELSE 'No'
				END as \"facility_pay\",
				c.date_invoice_start,
				c.date_effective,
				c.date_expiration,
				c.termination,
				c.length_term_id,
				CASE c.risk_share
					WHEN true THEN 'Yes'
					ELSE 'No'
				END as \"risk_share\",
				f.accounting_id,
				lt.term as \"term_length\",
				pt.term_due,
				pt.term_disp as \"payment_term\",
				COUNT(*) OVER() as total_records
			FROM contract c
			INNER JOIN facilities f ON c.id_facility = f.id
			LEFT JOIN (
				SELECT
					ci.contract_id,
					SUM(ci.amount) as onetime_revenue
				FROM contract_line_item ci
				INNER JOIN service_item_to_product s ON ci.item_code = s.item OR ci.item_code = s.code
				GROUP BY ci.contract_id
			) ce on c.id_contract = ce.contract_id
			LEFT JOIN corporate_office co ON f.parent_office = co.account_id
			LEFT JOIN contract_length_term lt ON c.length_term_id = lt.id
			LEFT JOIN contract_payment_term pt ON c.payment_term_id = pt.id
			WHERE f.id IN ($facility_list)
			AND COALESCE(contract_version,'none') != 'INVALID'
			$status_clause
			$type_clause
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET");
            $sth->execute();
            $this->contracts = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Load office detailed information from DB
     * Overright default detail array with our saved values;
     *
     * @param boolean $get_default Load default stucture or saved values
     */
    private function LoadDetail($get_default = false)
    {
        if (!$this->is_office)
        {
            $this->detail['Procurement']['PO Required']['po_required'] = array('id' => '', 'type' => 'checkbox', 'options' => (int) $this->po_required, 'value' => (int) $this->po_required, 'show_lable' => false);
            $chk_dssi = ($this->dssi_code) ? 1 : 0;
            $this->detail['Procurement']['DSSI Only']['dssi_code'] = array('id' => '', 'type' => 'checkbox', 'options' => $chk_dssi, 'value' => $chk_dssi, 'show_lable' => false);
        }

        if ($get_default)
        {
            $sql = "SELECT section, attribute, field, field_type, value, show_lable
					FROM corporate_office_detail
					WHERE office_id = 0
					ORDER BY display_order";
            $sth = $this->dbh->prepare($sql);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_NUM))
            {
                //print_r($row);
                list($section, $attribute, $field, $field_type, $value, $show_lable) = $row;
                $options = explode(",", $value);
                $this->detail[$section][$attribute][$field] = array('id' => '', 'type' => $field_type, 'options' => $options, 'value' => $value, 'show_lable' => $show_lable);

                if ($field == 'Supply Bill to Corporate')
                    $this->supply_bill_to_corporate = $value;
                else if ($field == 'Monthly Bill to Corporate')
                    $this->monthly_bill_to_corporate = $value;
            }
        }
        else if ($this->corporate_office_id > 0)
        {
            $sql = "SELECT detail_id, section, attribute, field, field_type, value, show_lable
					FROM corporate_office_detail
					WHERE office_id = {$this->corporate_office_id}";
            $sth = $this->dbh->prepare($sql);
            $sth->execute();
            while ($row = $sth->fetch(PDO::FETCH_NUM))
            {
                list($detail_id, $section, $attribute, $field, $field_type, $value, $show_lable) = $row;

                if (!isset ($this->detail[$section][$attribute][$field]))
                    $this->detail[$section][$attribute][$field] = array('id' => $detail_id, 'type' => $field_type, 'options' => $value, 'value' => $value, 'show_lable' => $show_lable);
                else
                {
                    $this->detail[$section][$attribute][$field]['id'] = $detail_id;
                    $this->detail[$section][$attribute][$field]['value'] = $value;
                }

                if ($field == 'Supply Bill to Corporate')
                    $this->supply_bill_to_corporate = $value;
                else if ($field == 'Monthly Bill to Corporate')
                    $this->monthly_bill_to_corporate = $value;
            }
        }


    }

    /**
     * Fill contract array
     *
     * @param array
     */
    public function LoadEquipment($filter)
    {
        if ($this->office_id)
        {
            # Match the facility, or corporate_office or subdivision
            if (!$this->is_office)
                $match = "f.id";
            else if ($this->parent_id == 0)
                $match = "co.corporate_office_id";
            else
                $match = "co.office_id";

            ## Getting much better performance using f.id set as a filter
            if (isset ($_SESSION['crm']['set'][$match][$this->office_id]))
                $facility_list = $_SESSION['crm']['set'][$match][$this->office_id];
            else
                $facility_list = $this->SetCustomerList();

            $model_clause = "";
            if ($filter['e_model'] != "all")
                $model_clause = "AND a.model_id = {$filter['e_model']}";

            $status_clause = "";
            if ($filter['e_status'] != "all")
                $status_clause = "AND a.status = {$this->dbh->quote($filter['e_status'])}";

            # Reset array
            $this->equipment = array();

            $page = $filter['page'];
            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            # Load the contact
            $sql = "SELECT
				a.id as \"asset_id\",
				a.model_id,
				a.serial_num,
				a.mfg_date,
				a.svc_date,
				a.last_cert_date,
				a.manufacturer,
				a.owning_acct,
				a.bill_to_acct,
				a.barcode,
				a.tstamp,
				a.facility_id,
				a.status,
				a.substatus,
				a.user_id,
				a.firmware_version,
				a.software_version,
				a.mac_address,
				m.model,
				m.description as model_name,
                m.has_pmt,
				CASE m.type_id
					WHEN 1 THEN 'E'
					WHEN 2 THEN 'A'
					ELSE 'U'
				END as asset_type,
				l.accounting_id as \"location_id\",
				c.contract_id,
				c.warranty_expiration_date,
				c.maintenance_expiration_date,
				c.warranty_option,
				c.maintenance_option,
				coalesce(f.accounting_id, l.accounting_id) as \"cust_id\",
				COUNT(*) OVER() as total_records
			FROM lease_asset_status a
			INNER JOIN equipment_models m on a.model_id = m.id
			INNER JOIN facilities l ON a.facility_id = l.id
			LEFT JOIN (SELECT
					i.asset_id,
					i.contract_id,
					i.warranty_expiration_date,
					i.maintenance_expiration_date,
					c.id_facility,
					w.warranty_name || ' ' || w.year_interval as warranty_option,
					ma.name || ' ' || ma.term_interval as maintenance_option
				FROM contract_line_item i
				INNER JOIN contract c ON i.contract_id = c.id_contract
				LEFT JOIN warranty_option w ON i.warranty_option_id = w.warranty_id
				LEFT JOIN maintenance_agreement ma ON i.maintenance_agreement_id = ma.id
				WHERE COALESCE(contract_version,'none') != 'INVALID'
				AND c.date_cancellation IS NULL OR c.date_cancellation > CURRENT_DATE
			) c ON a.id = c.asset_id
			LEFT JOIN facilities f ON c.id_facility = f.id
			LEFT JOIN corporate_office co ON f.parent_office = co.account_id
			WHERE l.id IN ($facility_list)
			$model_clause
			$status_clause
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET";
            $sth = $this->dbh->query($sql);
            $this->equipment = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Fill facility array
     */
    public function LoadFacilities($filter)
    {
        if ($this->office_id && count($this->facilities) == 0)
        {
            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            $match = ($this->parent_id == 0) ? "corporate_office_id" : "office_id";

            $show_status = "";
            if ($filter['f_show'] == "a")
                $show_status = " AND f.cancelled IS false";
            else if ($filter['f_show'] == "c")
                $show_status = " AND f.cancelled IS true";

            # Reset array
            $this->facilities = array();

            $purchase = LeaseContract::$PURCHASE_TYPE;
            $loaner = LeaseContract::$LOANER_TYPE;

            # Load the contact
            $sth = $this->dbh->prepare("SELECT
				f.id,
				f.accounting_id as \"cust_id\",
				f.facility_name,
				f.phone,
				f.address,
				f.address2,
				f.city,
				f.state,
				f.zip,
				f.cancelled,
				f.visit_frequency,
				f.provnum,
				f.cpt_id,
				d.latitude,
				d.longitude,
				ot.short_name as operator_type,
				rt.type_name as rehab_type,
				cpm.firstname,
				cpm.lastname,
				r.group_id as \"region_id\",
				rg.lastname as \"region\",
				c.contracts,
				c.lease_revenue,
				SUM(CASE WHEN f.cancelled IS false THEN c.lease_revenue ELSE 0 END) OVER () as total_revenue,
				COUNT(*) OVER () as total_records
			FROM facilities f
			INNER JOIN facilities_details d on f.id = d.facility_id
			LEFT JOIN facility_operator_type ot ON f.operator_type = ot.id
			LEFT JOIN rehab_type rt ON f.rehab_type = rt.type_id
			INNER JOIN corporate_office cp ON f.parent_office = cp.account_id
			INNER JOIN users cpm ON f.cpt_id = cpm.id
			INNER JOIN v_users_primary_group r ON cpm.id = r.user_id
			INNER JOIN users rg ON r.group_id = rg.id
			LEFT JOIN (
				SELECT
					id_facility,
					count(*) as contracts,
					sum(monthly_revenue) as lease_revenue
				FROM contract
				WHERE id_contract_type NOT IN ($purchase, $loaner)
				AND (date_cancellation IS NULL OR date_cancellation > CURRENT_DATE)
				AND COALESCE(contract_version,'none') != 'INVALID'
				GROUP BY id_facility
			) c ON f.id = c.id_facility
			WHERE cp.$match = ? $show_status
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            $this->facilities = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Populates this Office object from a facility record in the
     * database. Enables User to view a facility AS an Office
     *
     * @param integer $facility_id
     */
    public function LoadFromFacility($facility_id)
    {
        $this->office_id = $facility_id;
        $this->is_office = false;

        $this->provnum = '';
        $this->operator_type = 0;
        $this->med_a_beds = 0;
        $this->med_b_beds = 0;
        $this->other_beds = 0;
        $this->last_mod = null;
        $this->po_required = false;
        $this->rehab_provider_other = null;
        $this->rehab_provider = null;
        $this->rehab_type = null;
        $this->visit_frequency = 0;
        $this->site_type_abv = '';
        $this->site_type_text = '';
        $this->dssi_code = '';
        $this->is_patient = false;
        $this->fte_count = '';
        $this->requires_liftgate = false;
        $this->requires_inside_delivery = false;
        $this->has_dock = false;

        if ($this->office_id)
        {
            $sth = $this->dbh->prepare("SELECT
				f.facility_name,
				f.corporate_parent,
				f.parent_office,
				po.office_id as parent_id,
				f.accounting_id,
				f.active,
				cp.office_id as corporate_office_id,
				f.cpt_id,
				f.phone,
				f.fax,
				f.address,
				f.address2,
				f.city,
				f.state,
				f.zip,
				f.country_id,
				f.visit_frequency,
				f.provnum,
				f.operator_type,
				f.fte_count,
				f.pm_cpm_id,
				f.requires_liftgate,
				f.requires_inside_delivery,
				f.has_dock,
				fd.med_a_beds,
				fd.med_b_beds,
				fd.other_beds,
				fd.last_mod,
				fd.rehab_provider_other,
				fd.po_required,
				rp.name as rehab_provider,
				rt.type_name as rehab_type,
				ot.short_name as site_type_abv,
				ot.description as site_type_text,
				c.dssi_code
			FROM facilities f
			LEFT JOIN facilities_details fd ON f.id = fd.facility_id
			LEFT JOIN corporate_office cp on f.corporate_parent = cp.account_id
			LEFT JOIN corporate_office po on f.parent_office = po.account_id
			LEFT JOIN rehab_providers rp ON fd.rehab_provider = rp.id
			LEFT JOIN rehab_type rt ON f.rehab_type = rt.type_id
			LEFT JOIN facility_operator_type ot ON f.operator_type = ot.id
			LEFT JOIN facility_code_translation c ON f.accounting_id = c.cust_id
			WHERE f.id = ?");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $this->parent_id = $row['parent_id'];
                $this->account_id = trim($row['accounting_id']);
                $this->status = $row['active'];
                $this->s_status = ($row['active']) ? "Yes" : "No";
                $this->office_name = trim($row['facility_name']);
                $this->corporate_office_id = trim($row['corporate_office_id']);
                $this->account_executive = trim($row['cpt_id']);
                $this->provnum = $row['provnum'];
                $this->operator_type = $row['operator_type'];
                $this->med_a_beds = $row['med_a_beds'];
                $this->med_b_beds = $row['med_b_beds'];
                $this->other_beds = $row['other_beds'];
                $this->last_mod = $row['last_mod'];
                $this->po_required = $row['po_required'];
                $this->rehab_provider_other = $row['rehab_provider_other'];
                $this->rehab_provider = $row['rehab_provider'];
                $this->rehab_type = $row['rehab_type'];
                $this->visit_frequency = Facility::VFtoVC($row['visit_frequency']);
                $this->site_type_abv = $row['site_type_abv'];
                $this->site_type_text = $row['site_type_text'];
                $this->dssi_code = $row['dssi_code'];
                $this->is_patient = false;
                $this->fte_count = $row['fte_count'];
                $this->slp_cpc_id = $row['pm_cpm_id'];
                $this->requires_liftgate = (boolean) $row['requires_liftgate'];
                $this->requires_inside_delivery = (boolean) $row['requires_inside_delivery'];
                $this->has_dock = (boolean) $row['has_dock'];

                # Load mock office contact
                if (!isset ($this->contacts[0]))
                {
                    $contact = new Contact();
                    $contact->setIsOffice(true);
                    $contact->setVar('phone', $row['phone']);
                    $contact->setVar('address1', $row['address']);
                    $contact->setVar('address2', $row['address2']);
                    $contact->setVar('city', $row['city']);
                    $contact->setVar('state', $row['state']);
                    $contact->setVar('zip', $row['zip']);
                    $contact->setVar('fax', $row['fax']);
                    $contact->setVar('country_id', $row['country_id']);
                    $this->contacts[0] = $contact;
                }
            }

            $this->setBreadCrumb();
        }
        else
        {
            # We will always need one office contact
            if (!isset ($this->contacts[0]))
            {
                $this->contacts[0] = new Contact();
                $this->contacts[0]->setIsOffice(true);
            }
        }

        /*
         * Load default office and company detail
         * This will define the structure for the detail display
         * The office is not required to keep any data but the array must exist
         *
         * Resides in DB table with office_id 0
         */
        $this->LoadDetail(TRUE);
        if (!isset ($_REQUEST['skip_mas']))
        {
            $this->SetPaymentTerms();
        }
    }

    /**
     * Creat goal array
     *
     * @params array
     */
    public function LoadGoals($filter, $refresh_empty = true)
    {
        $dbh = DataStor::getHandle();
        $this->goals = array();

        $SORT_BY = $filter['sort_by'];
        $DIR = $filter['dir'];

        $status_clause = "AND g.status_id <> " . CustomerGoal::$DELETED_STATUS;
        if (is_numeric($filter['g_status']))
            $status_clause = " AND g.status_id = " . (int) $filter['g_status'];
        $type_clause = "";
        if (is_numeric($filter['g_type']))
            $type_clause = " AND g.type_id = " . (int) $filter['g_type'];
        else if (is_array($filter['g_type']))
            $type_clause = " AND g.type_id IN (" . implode(',', $filter['g_type']) . ")";
        $priority_clause = "";
        if (is_numeric($filter['g_priority']))
            $priority_clause = " AND g.priority = " . (int) $filter['g_priority'];
        $progress_clause = "";
        if (is_numeric($filter['g_progress']))
            $progress_clause = " AND g.progress = " . (int) $filter['g_progress'];

        # Need to query each type separately
        $all_types = array(
            CustomerGoal::$INITIATIVE_TYPE,
            CustomerGoal::$EXPECTATION_TYPE,
            CustomerGoal::$CLINICAL_TYPE,
            CustomerGoal::$OPERATIONAL_TYPE);
        foreach ($all_types as $type_id)
        {
            if ($type_id == CustomerGoal::$EXPECTATION_TYPE)
            {
                # Thses goals are at the Corp Parent Level and not linked to facility or subdivision
                if ($this->corporate_office_id)
                    $match_customer = "AND g.corporate_office_id = {$this->corporate_office_id}";
                else
                    $match_customer = "";
                $office_clause = "AND g.is_office = 1";
            }
            else
            {
                if ($this->is_office)
                {
                    # Get most recent goal for this customer, will be linked to a facility
                    if ($this->corporate_office_id)
                        $match_customer = "AND g.goal_id = (SELECT
						goal_id
					FROM customer_goal
					WHERE type_id = $type_id
					AND corporate_office_id = {$this->corporate_office_id}
					ORDER BY last_mod_date DESC
					LIMIT 1)";
                    else
                        $match_customer = "";

                    $office_clause = "AND g.is_office = 0";
                }
                else
                {
                    # Get goal for the facility
                    $match_customer = "AND g.customer_id = {$this->office_id}";
                    $office_clause = "AND g.is_office = 0";
                }
            }

            $sth = $dbh->prepare("SELECT
				g.*,
				t.type_text,
				s.status_desc,
				cb.firstname as created_by_first,
				cb.lastname as created_by_last,
				lm.firstname as last_mod_by_first,
				lm.lastname as last_mod_by_last,
				at.firstname as assigned_to_first,
				at.lastname as assigned_to_last,
				COUNT(*) OVER () as row_count
			FROM customer_goal g
			INNER JOIN customer_goal_type t ON g.type_id = t.type_id
			INNER JOIN customer_goal_status s ON g.status_id = s.status_id
			LEFT JOIN users cb ON g.created_by = cb.id
			LEFT JOIN users lm ON g.last_mod_by = lm.id
			LEFT JOIN users at ON g.assigned_to = at.id
			WHERE g.type_id = ?
			$match_customer
			$office_clause
			$status_clause
			$type_clause
			$priority_clause
			$progress_clause
			ORDER BY $SORT_BY $DIR");
            $sth->BindValue(1, $type_id, PDO::PARAM_INT);
            $sth->execute();
            while ($rec = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $goal = new CustomerGoal();
                foreach ($rec as $key => $value)
                    $goal->{$key} = $value;

                $goal->LoadComments();

                $this->goals[] = $goal;
            }
        }

        # If no goals are found load the defauls
        if (count($this->goals) == 0 && $refresh_empty == true)
        {
            if ($this->is_office)
            {
                $types = array(CustomerGoal::$INITIATIVE_TYPE, CustomerGoal::$EXPECTATION_TYPE);
            }
            else
            {
                $types = array(CustomerGoal::$CLINICAL_TYPE, CustomerGoal::$OPERATIONAL_TYPE);
            }
            $count = count($types);

            foreach ($types as $type_id)
            {
                $goal = new CustomerGoal();
                $goal->type_id = $type_id;
                $goal->customer_id = $this->office_id;
                $goal->corporate_office_id = $this->corporate_office_id;
                $goal->is_office = $this->is_office;
                $goal->row_count = $count;
                $goal->DBInsert();
                $goal->Load();
                $this->goals[] = $goal;
            }
        }
    }

    /**
     * Creat iinvoice array
     *
     * @params array
     */
    public function LoadInvoices($filter)
    {
        if ($this->office_id)
        {
            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            $status_clause = "";
            if (is_numeric($filter['in_status']))
                $status_clause = " AND i.status = " . (int) $filter['in_status'];

            $type_clause = "";
            if (is_numeric($filter['in_type']))
                $type_clause = " AND i.tran_type = " . (int) $filter['in_type'];


            $this->invoices = array();

            if ($this->is_office)
                $match = ($this->parent_id == 0) ? "o.corporate_office_id" : "o.office_id";
            else
                $match = "i.facility_id";

            ## Getting much better performance using f.id set as a filter
            $sth = $this->dbh->prepare("SELECT
				ARRAY_TO_STRING(ARRAY_ACCUM(facility_id), ',') as facility_list,
				SUM(i.invoice_total) as invoice_total,
				SUM(i.product_total) as product_total,
				SUM(i.row_count) as row_count
			FROM (
				SELECT
					facility_id,
					SUM(total_amount) as invoice_total,
					SUM(total_product_cost) as product_total,
					COUNT(*) as row_count
				FROM invoice_current i
				WHERE true
				$status_clause
				$type_clause
				GROUP BY facility_id
			) i
			LEFT JOIN facilities f ON i.facility_id = f.id
			LEFT JOIN corporate_office o ON f.parent_office = o.account_id
			WHERE $match = ?");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            $totals = $sth->fetch(PDO::FETCH_OBJ);
            if (empty ($totals->facility_list))
                $totals->facility_list = "0";
            if (empty ($totals->invoice_total))
                $totals->invoice_total = 0;
            if (empty ($totals->product_total))
                $totals->product_total = 0;
            if (empty ($totals->row_count))
                $totals->row_count = 0;

            # Query for invoices
            $sth = $this->dbh->query("SELECT
				i.invoice_num,
				i.facility_id,
				i.invoice_date,
				i.due_date,
				i.ship_date,
				i.total_amount,
				i.total_product_cost,
				i.status,
				CASE i.status
					WHEN 1 THEN 'Open'
					WHEN 2 THEN 'Closed'
					WHEN 3 THEN 'Void'
					ELSE 'Invalid'
				END as \"status_text\",
				i.tran_type,
				CASE i.tran_type
					WHEN 501 THEN 'AR Invoice (501)'
					WHEN 502 THEN 'AR Credit (502)'
					WHEN 503 THEN 'AR Debit (503)'
					ELSE '--missing--'
				END as \"tran_type_text\",
				i.po_num,
				i.order_id,
				i.contract_id,
				f.accounting_id,
				{$totals->invoice_total} as invoice_total,
				{$totals->product_total} as product_total,
				{$totals->row_count} as row_count
			FROM invoice_current i
			LEFT JOIN facilities f ON i.facility_id = f.id
			--LEFT JOIN corporate_office o ON f.parent_office = o.account_id
			WHERE i.facility_id IN ({$totals->facility_list})
			$status_clause
			$type_clause
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET");
            ##$sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            ##$sth->execute();
            $this->invoices = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Creat issue array
     *
     * @params array
     */
    public function LoadIssues($filter = null)
    {
        if ($this->office_id)
        {
            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            $status_clause = "";
            if (is_numeric($filter['i_status']))
                $status_clause = " AND i.issue_status = " . (int) $filter['i_status'];
            $category_clause = "";
            if (is_numeric($filter['i_category']))
                $category_clause = " AND i.category = " . (int) $filter['i_category'];
            $priority_clause = "";
            if (is_numeric($filter['i_priority']))
                $priority_clause = " AND i.priority = " . (int) $filter['i_priority'];

            $this->issues = array();

            if ($this->is_office)
                $match = ($this->parent_id == 0) ? "o.corporate_office_id" : "o.office_id";
            else
                $match = "i.office_issue IS FALSE AND i.office_id";

            # Load issues
            $sth = $this->dbh->prepare("SELECT
				i.issue_id,
				i.open_by,
				i.open_date,
				i.last_mod_by,
				i.last_mod_date,
				i.closed_by,
				i.closed_date,
				i.office_id,
				i.corporate_office_id,
				i.priority,
				p.priority_text,
				i.category,
				c.category_text,
				i.issue_status,
				s.status_text,
				i.subject,
				i.office_issue,
				cf.complaint_id,
				ob.firstname as open_by_first,
				ob.lastname as open_by_last,
				lm.firstname as last_mod_first,
				lm.lastname as last_mod_last,
				cb.firstname as closed_by_first,
				cb.lastname as closed_by_last,
				COUNT(*) OVER() as row_count
			FROM issue i
			LEFT JOIN issue_priority p ON i.priority = p.priority
			LEFT JOIN issue_status s ON i.issue_status = s.status_id
			LEFT JOIN issue_category c ON i.category = c.category_id
			LEFT JOIN complaint_form cf ON i.issue_id = cf.issue_id
			LEFT JOIN corporate_office o ON i.corporate_office_id = o.office_id
			LEFT JOIN users ob ON i.open_by = ob.id
			LEFT JOIN users lm ON i.last_mod_by = lm.id
			LEFT JOIN users cb ON i.closed_by = cb.id
			WHERE $match = ?
			$status_clause
			$category_clause
			$priority_clause
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            $this->issues = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Fill contract array
     */
    public function LoadLeads($filter)
    {
        if ($this->office_id)
        {
            # Reset array
            $this->leads = array();

            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            # Load the contact
            $sth = $this->dbh->prepare("SELECT
				c.office_id,
				c.office_name,
				cd.contract_value,
				cd.opportunities,
				cd.provider_type,
				cd.services_payer,
				CASE WHEN cd.estimated_close > 0 THEN to_timestamp(cd.estimated_close)::Date ELSE NULL END as estimated_close,
				sp.phase_name,
				ot.short_name as operator_type,
				COUNT(*) OVER () as total_records
			FROM corporate_office c
			INNER JOIN sales_customer_detail cd ON c.office_id = cd.customer_id
			INNER JOIN customer_phase cp ON c.office_id = cp.customer_id AND cp.active
			INNER JOIN sales_phase sp ON cp.phase_id = sp.phase_id
			LEFT JOIN facility_operator_type ot ON cd.operator_type = ot.id
			WHERE c.status = 4 -- Active Lead
			AND cd.corporate_parent = ?
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            $this->leads = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Fill order array (a page worth)
     *
     * @param array
     */
    public function LoadOrders($filter)
    {
        if ($this->office_id)
        {
            # Match the facility, or corporate_office or subdivision
            if (!$this->is_office)
                $match = "f.id";
            else if ($this->parent_id == 0)
                $match = "co.corporate_office_id";
            else
                $match = "co.office_id";

            ## Getting much better performance using f.id set as a filter
            if (isset ($_SESSION['crm']['set'][$match][$this->office_id]))
                $facility_list = $_SESSION['crm']['set'][$match][$this->office_id];
            else
                $facility_list = $this->SetCustomerList();

            $type_clause = "";
            if ($filter['o_type'] != "all")
                $type_clause = "AND ot.type_id = {$filter['o_type']}";

            $status_clause = "";
            if ($filter['o_status'] == "ns")
                $status_clause = "AND os.id IN (1,2,99)";
            else if ($filter['o_status'] != "all")
                $status_clause = "AND os.id = {$filter['o_status']}";

            $ship_to_facility = Order::$SHIP_TO_FACILITY;
            $ship_to_cpm = Order::$SHIP_TO_CPM;
            $processed = Order::$PROCESSED;

            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            $sth = $this->dbh->query("SELECT
			   o.id AS \"order_id\",
		       o.order_date,
		       u.firstname || ' ' || u.lastname AS user,
		       CASE
		           WHEN o.ship_to = {$ship_to_facility} THEN f.accounting_id
		           WHEN o.ship_to = {$ship_to_cpm} THEN 'CPC'
		           ELSE 'Other'
		       END AS \"ship_to_name\",
		       o.ship_to AS ship_to,
		       o.status_id AS status_id,
		       os.name AS \"status_name\",
		       CASE
		           WHEN (ot.type_id = 4 OR ot.type_id = 8) AND cto.name IS NOT NULL
		           THEN ot.description || ' - ' || cto.name
		           ELSE ot.description
		       END AS \"order_type\",
		       ot.type_id as order_type_id,
		       o.inst_date AS \"inst_date\",
		       o.ship_date AS \"ship_date\",
		       o.ship_method AS ship_method,
		       CASE
		           WHEN o.ship_method = 'Next Day' THEN 1
		           WHEN o.ship_method = 'Next Day Early AM' THEN 1
		           WHEN o.ship_method = '2 Day' THEN 2
		           WHEN o.ship_method = '3 Day' THEN 3
		           ELSE 4
		       END AS ship_priority,
		       o.urgency,
		       o.tracking_num AS tracking_num,
		       o.ret_tracking_num AS ret_tracking_num,
		       o.parent_order,
		       f.id as facility_id,
		       f.accounting_id as \"cust_id\",
			   c.issue_id,
		       o.mas_sales_order,
			   coalesce(o_tran.transaction_date,  o.processed_date) as \"processed_date\",
				COUNT(*) OVER () as total_records
			FROM orders o
			INNER JOIN order_status os ON o.status_id = os.id
			INNER JOIN order_type ot ON o.type_id = ot.type_id
			LEFT JOIN users u ON o.user_id = u.id
			LEFT JOIN contract con ON con.id_contract = o.contract_id
			LEFT JOIN contract_type_options cto ON cto.id_contract_type = con.id_contract_type
			LEFT JOIN facilities f ON o.facility_id = f.id
			LEFT JOIN corporate_office co ON f.parent_office = co.account_id
			LEFT JOIN complaint_form_equipment c ON o.id = c.order_id
			LEFT JOIN (
				SELECT
					order_id, MIN(transaction_date) AS transaction_date
				FROM order_transaction
				WHERE order_status_id = {$processed}
				GROUP BY order_id
			) o_tran ON o.id = o_tran.order_id
			WHERE f.id IN ($facility_list)
			$status_clause
			$type_clause
			AND os.id NOT IN (4,6,7,8) -- ('Exception','Canceled','Deleted','Write Off')
			ORDER BY $SORT_BY $DIR, c.issue_id
			LIMIT $LIMIT OFFSET $OFFSET");
            #$sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            #$sth->execute();
            $this->orders = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Creat goal array
     *
     * @params array
     */
    public function LoadQuestions($filter, $refresh_empty = true)
    {
        $dbh = DataStor::getHandle();
        $this->goals = array();

        $SORT_BY = $filter['sort_by'];
        $DIR = $filter['dir'];

        $status_clause = "AND g.status_id <> " . CustomerGoal::$DELETED_STATUS;
        if (is_numeric($filter['g_status']))
            $status_clause = " AND g.status_id = " . (int) $filter['g_status'];
        $type_clause = "";

        $priority_clause = "";
        if (is_numeric($filter['g_priority']))
            $priority_clause = " AND g.priority = " . (int) $filter['g_priority'];

        $progress_clause = "";
        if (is_numeric($filter['g_progress']))
            $progress_clause = " AND g.progress = " . (int) $filter['g_progress'];

        if ($this->is_office)
        {
            # Thses goals are at the Corp Parent Level and not linked to facility or subdivision
            if ($this->corporate_office_id)
                $match_customer = "AND g.corporate_office_id = {$this->corporate_office_id}";
            else
                $match_customer = "";
            $office_clause = "AND g.is_office = 1";
        }
        else
        {
            # Get goal for the facility
            $match_customer = "AND g.customer_id = {$this->office_id}";
            $office_clause = "AND g.is_office = 0";
        }

        $sth = $dbh->prepare("SELECT
			g.*,
			t.type_text,
			s.status_desc,
			cb.firstname as created_by_first,
			cb.lastname as created_by_last,
			lm.firstname as last_mod_by_first,
			lm.lastname as last_mod_by_last,
			at.firstname as assigned_to_first,
			at.lastname as assigned_to_last,
			COUNT(*) OVER () as row_count
		FROM customer_goal g
		INNER JOIN customer_goal_type t ON g.type_id = t.type_id
		INNER JOIN customer_goal_status s ON g.status_id = s.status_id
		LEFT JOIN users cb ON g.created_by = cb.id
		LEFT JOIN users lm ON g.last_mod_by = lm.id
		LEFT JOIN users at ON g.assigned_to = at.id
		WHERE g.type_id = ?
		$match_customer
		$office_clause
		$status_clause
		$type_clause
		$priority_clause
		$progress_clause
		ORDER BY $SORT_BY $DIR");
        $sth->BindValue(1, CustomerGoal::$QUESTION_TYPE, PDO::PARAM_INT);
        $sth->execute();
        while ($rec = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $goal = new CustomerQuestion();
            $goal->num = count($this->questions) + 1;
            foreach ($rec as $key => $value)
                $goal->{$key} = $value;

            $goal->LoadComments();

            $this->questions[] = $goal;
        }

        # If no goals are found load the defauls
        if (count($this->questions) == 0 && $refresh_empty)
        {
            $goal = new CustomerQuestion();
            $goal->customer_id = $this->office_id;
            $goal->corporate_office_id = $this->corporate_office_id;
            $goal->is_office = $this->is_office;
            $goal->description = "0";
            $goal->DBInsert();
            $goal->Load();
            $goal->num = count($this->questions) + 1;
            $this->questions[] = $goal;

            $goal = new CustomerQuestion();
            $goal->customer_id = $this->office_id;
            $goal->corporate_office_id = $this->corporate_office_id;
            $goal->is_office = $this->is_office;
            $goal->description = "1";
            $goal->DBInsert();
            $goal->Load();
            $goal->num = count($this->questions) + 1;
            $this->questions[] = $goal;

            $goal = new CustomerQuestion();
            $goal->customer_id = $this->office_id;
            $goal->corporate_office_id = $this->corporate_office_id;
            $goal->is_office = $this->is_office;
            $goal->description = "2";
            $goal->DBInsert();
            $goal->Load();
            $goal->num = count($this->questions) + 1;
            $this->questions[] = $goal;

            $goal = new CustomerQuestion();
            $goal->customer_id = $this->office_id;
            $goal->corporate_office_id = $this->corporate_office_id;
            $goal->is_office = $this->is_office;
            $goal->description = "3";
            $goal->DBInsert();
            $goal->Load();
            $goal->num = count($this->questions) + 1;
            $this->questions[] = $goal;

            $goal = new CustomerQuestion();
            $goal->customer_id = $this->office_id;
            $goal->corporate_office_id = $this->corporate_office_id;
            $goal->is_office = $this->is_office;
            $goal->description = "4";
            $goal->DBInsert();
            $goal->Load();
            $goal->num = count($this->questions) + 1;
            $this->questions[] = $goal;

            $goal = new CustomerQuestion();
            $goal->customer_id = $this->office_id;
            $goal->corporate_office_id = $this->corporate_office_id;
            $goal->is_office = $this->is_office;
            $goal->description = "5";
            $goal->DBInsert();
            $goal->Load();
            $goal->num = count($this->questions) + 1;
            $this->questions[] = $goal;

            $goal = new CustomerQuestion();
            $goal->customer_id = $this->office_id;
            $goal->corporate_office_id = $this->corporate_office_id;
            $goal->is_office = $this->is_office;
            $goal->description = "6";
            $goal->DBInsert();
            $goal->Load();
            $goal->num = count($this->questions) + 1;
            $this->questions[] = $goal;

            $goal = new CustomerQuestion();
            $goal->customer_id = $this->office_id;
            $goal->corporate_office_id = $this->corporate_office_id;
            $goal->is_office = $this->is_office;
            $goal->description = "7";
            $goal->DBInsert();
            $goal->Load();
            $goal->num = count($this->questions) + 1;
            $this->questions[] = $goal;

            $goal = new CustomerQuestion();
            $goal->customer_id = $this->office_id;
            $goal->corporate_office_id = $this->corporate_office_id;
            $goal->is_office = $this->is_office;
            $goal->description = "8";
            $goal->DBInsert();
            $goal->Load();
            $goal->num = count($this->questions) + 1;
            $this->questions[] = $goal;
        }
    }

    /**
     * Fill subs array with cusomters subdivisions
     */
    public function LoadSubs()
    {
        if (count($this->subs) == 0)
        {
            $this->subs = self::Search(array('search' => '', 'parent_id' => $this->office_id, 'match' => 'office', 'active' => 'a', 'ctype' => 'o'));
        }
    }

    /**
     * Query for customer tasks
     *
     * @param array
     */
    public function LoadTasks($filter = null)
    {
        if ($this->office_id)
        {
            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            $important_clause = "";
            if (is_numeric($filter['t_important']))
                $important_clause = ($filter['t_important']) ? "AND t.important IS TRUE" : "AND t.important IS FALSE";

            $required_clause = "";
            if (is_numeric($filter['t_required']))
                $required_clause = ($filter['t_required']) ? "AND t.required IS TRUE" : "AND t.required IS FALSE";

            $complete_clause = "";
            if (is_numeric($filter['t_completed']))
                $complete_clause = ($filter['t_completed']) ? "AND t.closed_date > 0" : "AND t.closed_date = 0";

            $this->tasks = array();

            # Load issues
            $sth = $this->dbh->prepare("SELECT
				t.task_id,
				t.task_action,
				t.open_date,
				t.due_date,
				t.closed_date,
				t.customer_id,
				t.last_mod_by,
				t.last_mod_date,
				t.open_by,
				t.important,
				t.required,
				ob.firstname as open_by_first,
				ob.lastname as open_by_last,
				lm.firstname as last_mod_by_first,
				lm.lastname as last_mod_by_last,
				COUNT(*) OVER() as row_count
			FROM task t
			INNER JOIN corporate_office o ON t.customer_id = o.office_id
			LEFT JOIN users ob ON t.open_by = ob.id
			LEFT JOIN users lm ON t.last_mod_by = lm.id
			WHERE t.customer_id = ?
			$required_clause
			$important_clause
			$complete_clause
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            $this->tasks = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Query for therapist
     *
     * @param array
     */
    public function LoadTherapist($filter)
    {
        global $user;

        $ce_provider_id = ($user) ? $user->GetCEProviderId() : 1;
        $this->therapists = array();

        if ($this->office_id)
        {
            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            $active_clause = "";
            if (is_numeric($filter['t_active']))
                $active_clause = ($filter['t_active']) ? "AND tf.active IS TRUE" : "AND tf.active IS FALSE";

            $profession_clause = "";
            if (is_numeric($filter['profession_id']))
                $profession_clause = "AND {$filter['profession_id']} = ANY (tp.profession_ids)";

            if (!$this->is_office)
            {
                # Match the facility
                $match = "tf.facility_id";
            }
            else if ($this->parent_id == 0)
            {
                # Match all
                $match = "co.corporate_office_id";
            }
            else
            {
                # Match those for this subdivision
                $match = "co.office_id";
            }


            # Load issues
            $sth = $this->dbh->prepare("SELECT
				t.id,
		        t.firstname,
		        t.lastname,
				t.email,
				t.phone,
		        t.city,
		        t.state,
				t.add_time::Date as add_time,
				date_trunc('second', t.last_mod) as last_mod,
		        tp.profession,
		        tf.active AS active,
				COUNT(*) OVER() as row_count
			FROM therapists t
			INNER JOIN therapists_facilities tf ON t.id = tf.therapist_id
			INNER JOIN facilities f ON tf.facility_id = f.id
			INNER JOIN corporate_office co ON f.parent_office = co.account_id
			LEFT JOIN (
				SELECT
					tp.therapist_id,
					array_accum(p.id) as profession_ids,
					array_to_string(array_accum(p.name), ',') as profession
				FROM therapists_professions tp
				INNER JOIN professions p ON tp.profession_id = p.id
				GROUP BY tp.therapist_id
			) tp ON t.id = tp.therapist_id
			WHERE t.ce_provider_id = $ce_provider_id
			AND $match = ?
			$active_clause
			$profession_clause
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            $this->therapists = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Fill visits array (a page worth)
     *
     * @param array
     */
    public function LoadVisits($filter)
    {
        if ($this->office_id)
        {
            if (!$this->is_office)
            {
                # Match the facility
                $match = "v.facility_id";
            }
            else if ($this->parent_id == 0)
            {
                # Match all
                $match = "co.corporate_office_id";
            }
            else
            {
                # Match those for this subdivision
                $match = "co.office_id";
            }

            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            $sth = $this->dbh->prepare("SELECT
				e.id as event_id,
				e.lock_date,
				e.title,
				e.description,
                e.category_id,
				f.accounting_id,
				f.parent_office,
				f.corporate_parent,
				u.id AS cpc_id,
				u.firstname AS cpc_first,
				u.lastname AS cpc_last,
				cat.id AS category_id,
				cat.name AS category_name,
				v.id as visit_id,
				v.cpt_id,
				v.start_time,
				v.end_time,
				v.status_id,
				v.brief_visit_reason,
				v.eas1,
				v.eas2,
				v.eas3,
				v.eas4,
				v.eas5,
				v.eas6,
				v.facility_risk_cxl,
				v.expose_mod_util_to_client,
				v.expose_clinrec_to_client,
				v.expose_mkt_to_client,
				v.next_visit_date,
				v.version,
				v.valid,
				v.mailed,
				v.pending_review,
				v.followup_made,
				v.heartbeat_time,
				v.last_mod,
				v.formulary_compliance,
				v.temp_reference,
				v.visit_status,
				v.status3,
				v.equip_note1,
				v.equip_note2,
				v.equip_note3,
				v.confirm_calibration,
				v.pending_review_comment,
				v.brief_visit_details,
				v.facility_risk_cxl_comment,
				v.patient_outcome,
				v.train_case_devel,
				v.impressions,
				v.calibration,
				v.other_info,
				v.plan,
				fs.name AS status_name,
				pl.id AS plan_id,
				me.id AS mail_elements_id,
				me.facility_id AS me_facility_id,
				me.emailed AS me_emailed,
				me.saved AS me_saved,
				me.event_date AS me_event_date,
				me.from_phone_number AS me_from_phone,
				me.from_email_id AS me_from_email,
				me.last_mod_time AS me_last_mod,
				COUNT(*) OVER () as row_count
			FROM events e
			INNER JOIN facilities f on e.facility_id = f.id
			INNER JOIN users u ON e.owner = u.id
			INNER JOIN categories cat ON e.category_id = cat.id
			LEFT JOIN visit_summaries v ON e.id = v.event_id
			LEFT JOIN corporate_office co ON f.parent_office = co.account_id
			LEFT JOIN facility_status fs ON v.status_id = fs.id
			LEFT JOIN planning pl ON e.id = pl.event_id
			LEFT JOIN mail_elements me ON e.id = me.event_id
			WHERE $match = ?
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            $this->visits = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * Fill work order array (a page worth)
     *
     * @param array
     */
    public function LoadWorkOrders($filter)
    {
        if ($this->office_id)
        {
            # Match the facility, or corporate_office or subdivision
            if (!$this->is_office)
                $match = "f.id";
            else if ($this->parent_id == 0)
                $match = "co.corporate_office_id";
            else
                $match = "co.office_id";

            ## Getting much better performance using f.id set as a filter
            if (isset ($_SESSION['crm']['set'][$match][$this->office_id]))
                $facility_list = $_SESSION['crm']['set'][$match][$this->office_id];
            else
                $facility_list = $this->SetCustomerList();

            $model_clause = "";
            if ($filter['w_model'] != "all")
                $model_clause = "AND w.model = {$filter['w_model']}";

            $status_clause = "";
            if ($filter['w_status'] != "all")
                $status_clause = "AND ws.status_id = {$filter['w_status']}";

            $SORT_BY = $filter['sort_by'];
            $DIR = $filter['dir'];
            $LIMIT = $filter['limit'];
            $OFFSET = $filter['offset'];

            $sth = $this->dbh->query("SELECT
				w.work_order,
				w.open_date,
				w.open_by,
				w.last_mod_date,
				w.last_mod_by,
				w.close_date,
				w.close_by,
				w.model as \"model_id\",
				w.serial_num,
				w.facility_id,
				w.editable,
				ws.status_text as \"status\",
				m.model,
				m.description as \"model_name\",
				f.accounting_id AS \"cust_id\",
				ob.firstname || ' ' || ob.lastname as \"open_by_name\",
				cb.firstname || ' ' || cb.lastname as \"close_by_name\",
				mb.firstname || ' ' || mb.lastname as \"last_mod_by_name\",
				COUNT(*) OVER () as total_records
			FROM work_order w
			INNER JOIN work_order_status ws ON w.status = ws.status_id
			INNER JOIN equipment_models m on w.model = m.id
			LEFT JOIN facilities f ON w.facility_id = f.id
			LEFT JOIN corporate_office co ON f.parent_office = co.account_id
			LEFT JOIN users ob ON w.open_by = ob.id
			LEFT JOIN users cb ON w.close_by = cb.id
			LEFT JOIN users mb ON w.last_mod_by = mb.id
			WHERE f.id IN ($facility_list)
			$model_clause
			$status_clause
			ORDER BY $SORT_BY $DIR
			LIMIT $LIMIT OFFSET $OFFSET");
            $this->wos = $sth->fetchAll(PDO::FETCH_OBJ);
        }
    }

    /**
     * @param integer $office_id to find text for
     * @return string account id for the office identified by office_id
     */
    public static function LookupAccountingId($office_id = 0)
    {
        $dbh = DataStor::getHandle();

        $acc = "";
        if ($office_id > 0)
        {
            $sth = $dbh->prepare("SELECT account_id FROM corporate_office WHERE office_id = {$office_id}");
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $acc = $row['account_id'];
            }
        }

        return $acc;
    }

    /**
     * @param integer $office_id to find text for
     * @return string office name for the office identified by office_id
     */
    public static function LookupOfficeName($office_id = 0)
    {
        $dbh = DataStor::getHandle();

        $office_name = "";
        if ($office_id > 0)
        {
            $sth = $dbh->prepare("SELECT office_name FROM corporate_office WHERE office_id = {$office_id}");
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $office_name = $row['office_name'];
            }
        }

        return $office_name;
    }

    /**
     * Find array of assets without corresponding contract item
     *
     * @return array
     */
    private function MismatchedContractItems()
    {
        $mismatched = array();

        ## Match record type
        #if ($this->is_office) return $mismatched;
        if (!$this->is_office)# Match the facility
            $match = "c.id_facility = " . (int) $this->office_id;
        else if ($this->parent_id == 0) # Match all
            $match = "co.corporate_office_id = " . (int) $this->office_id;
        else # Match those for this subdivision
            $match = "co.office_id = " . (int) $this->office_id;

        $dbh = DataStor::getHandle();
        $sth = $dbh->query("SELECT
			l.asset_id, coalesce(s.code, l.item_code) as model
		FROM contract c
		INNER JOIN contract_line_item l ON c.id_contract = l.contract_id
		INNER JOIN facilities f ON c.id_facility = f.id
		INNER JOIN corporate_office co ON f.parent_office = co.account_id
		LEFT JOIN service_item_to_product s ON l.item_code = s.item
		WHERE (c.date_cancellation IS NULL OR c.date_cancellation > CURRENT_DATE)
		AND coalesce(c.contract_version,'') != 'INVALID'
		AND $match");
        $con_items = $sth->fetchAll(PDO::FETCH_NUM);

        ## Each device is a potentially mismatched
        ## Remove all direct links to assets
        foreach ($this->equipment as $dev)
        {
            ## Onnly check program equipment
            if ($dev->asset_type != 'E' || $dev->status == 'Out of Service' || $dev->status == 'OEM')
                continue;

            ## Remove all direct links to assets
            $matched = false;
            foreach ($con_items as $i => $ary)
            {
                if ($ary[0] == $dev->asset_id)
                {
                    unset($con_items[$i]);
                    $matched = true;
                    break;
                }
            }

            ## potentially mismatched
            if ($matched == false)
                $mismatched[] = $dev->asset_id;
        }

        ## Exchanges all keys with their associated values in an arra
        ## ie: elem[index] = asset_id --flipped-- elem[asset_id] = index
        $mismatched = array_flip($mismatched);

        ## Find unmatched contract item
        foreach ($con_items as $i => $ary)
        {
            ## Find the item code / model
            foreach ($this->equipment as $dev)
            {
                ## Remove the item from mismatched array
                if (isset ($mismatched[$dev->asset_id]) && $dev->model == $ary[1])
                {
                    unset($mismatched[$dev->asset_id]);
                    break;
                }
            }
        }

        ## Return this to elem[index] = asset_id
        return array_flip($mismatched);
    }

    /**
     * Supply html for the Equipment section
     *
     * @return string
     */
    public function OrderSection()
    {
        global $user;

        $btns = "<button id='view_orders_bttn' type='button' class='btn btn-default btn-sm' onClick=\"window.location='customer_orders.php?fid={$this->office_id}&act=Orders'\" disabled>View All Orders</button>";

        if ($user->hasAccessToApplication('orderplacement') && $user->hasAccessToOrderType(8))
            $btns .= " <button type=\"button\" class='btn btn-default btn-sm' value=\"Create New Order\" onClick=\"window.location='orderplacement.php?section=Home&act=set_fid&facility_id={$this->office_id}&order_type=8&cat_id=0&order_id=0'\">Create New Order</button>";

        if ($user->hasAccessToApplication('reset_xcart_pwd'))
        {
            $url = $_SERVER["HTTP_HOST"] . Config::$WEB_PATH;
            $btns .= " <button type=\"button\" class='btn btn-default btn-sm' onClick=\"resetXCartPassword('{$this->account_id}', '$url')\">Reset Webstore Password</button>";
        }

        $section = "<div class='flx_cont'>
			<div id='order_cont' class='box box-primary'>
				<div id='order_hdr' class='box-header' data-toggle='collapse' data-target='#order_disp' style='cursor:pointer;'>
					<i class='fa fa-minus pull-right'></i>
					<h4 class='box-title'>Orders</h4>
					<span class='badge' id='order_sec_badge'></span>
				</div>
				<div class='box-header with-border fltr txar'>
					$btns
				</div>
				<div id='order_disp' class='box-body collapse in'>
					<div class='on'>Loading...</div>
					<script type='text/javascript'>$(function () { CRMLoader.LoadContent('order', false); });</script>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply table for the Equipment section
     *
     * @return string
     */
    public function OrderTable(&$html, &$count)
    {
        global $this_app_name, $preferences;

        $date_format = $preferences->get('general', 'dateformat');
        $defaults = array('page' => 1, 'sort_by' => "order_id", 'dir' => "DESC", "o_type" => "all", "o_status" => "ns");
        SessionHandler::Update('crm', 'order', $defaults);
        $filter = $_SESSION['crm']['order'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 0;
        $sec_conf->disp = 'order_disp';
        $sec_conf->nav_badge = 'order_nav_badge';
        $sec_conf->sec_badge = 'order_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=OrderTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_order_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        $this->LoadOrders($filter);

        ## Init totals for Lease, Purchase and Loaner
        $sec_conf->rows = "";
        $rc = 'dt-tr-on';
        $row_count = 0;
        foreach ($this->orders as $order)
        {
            $sec_conf->row_count++;
            $tag['text'] = $order->order_id;
            $tag['alt'] = "View Order";
            $tag['href'] = "orderfil.php?action=dtl&order_id={$order->order_id}";
            $tag['target'] = "_blank";
            $link = BaseClass::BuildATags(array($tag));

            # Need to page results useing a window function
            # for true total. This is return in total_records field.
            $count = $order->total_records;
            $sec_conf->count = $order->total_records;

            ## Show inactive?
            $ia = "";

            $order->order_date = date($date_format . ' h:i a', $order->order_date);
            $order->processed_date = ($order->processed_date) ? date($date_format . ' h:i a', $order->processed_date) : "";
            $order->inst_date = ($order->inst_date) ? date($date_format, $order->inst_date) : "";
            $order->ship_date = ($order->ship_date) ? date($date_format, $order->ship_date) : "";
            $order->tracking = Order::FormatTrackingNo($order->tracking_num);
            $order->tracking .= "&nbsp;";
            $order->tracking .= Order::FormatTrackingNo($order->ret_tracking_num);

            $sec_conf->rows .= "<tr class='{$rc}{$ia}' ondblclick=\"window.location='{$tag['href']}';\">";
            foreach ($sec_conf->cols as $col)
            {
                $sec_conf->rows .= "<td class='{$col->cls}'>{$order->{$col->key} }</td>";
            }
            $sec_conf->rows .= "</tr>";

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        if ($count == 0)
        {
            $cs = count($sec_conf->cols);
            $rows = "<tr class='dt-tr-on'><td colspan=$cs style='text-align:center;'>No Orders found.</td></tr>";
        }

        ## define attributes used to query for a page of data
        $base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=OrderTable&entry=$this->office_id&is_office=$iso";
        $t_options = Forms::createOrderTypeList($filter['o_type']);
        $exclude = array(Order::$EXCEPTION, Order::$CANCELED, Order::$DELETED, Order::$WRITEOFF);
        $s_options = Forms::createOrderStatusList($filter['o_status'], $exclude);
        $chk_ns = ($filter['o_status'] == "ns") ? "selected" : "";

        $sec_conf->filter = "<div class='filter nested' align='left'>
			<span class='lbl'>Type:</span>
			<select name='o_type' onchange=\"FillSection('$base_url&page=1&o_type='+this.value,'order_disp','order_nav_badge','order_sec_badge');\">
				<option value='all'>--All--</option>
				$t_options
			</select>
			<span class='lbl'>Status:</span>
			<select name='o_status' onchange=\"FillSection('$base_url&page=1&o_status='+this.value,'order_disp','order_nav_badge','order_sec_badge');\">
				<option value='all'>--All--</option>
				$s_options
				<option value='ns' $chk_ns>--Not Shipped--</option>
			</select>
		</div>";

        $html = $this->SectionHTML($sec_conf);
        return $html;
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
        $int_fields = array('co.office_id', 'co.corporate_office_id', 'co.parent_id', 'co.nursing_homes', 'co.acp_nursing_homes', 'co.contact_id', 'po.children', 'f.facilities');

        # All Valid SM status
        $WHERE = "";

        if ($args['search_fields'])
        {
            foreach ($args['search_fields'] as $idx => $field)
            {
                $is_int = in_array($field, $int_fields);
                $is_date = ($field == 'co.last_mod'); ## Future use

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
                $WHERE = " AND (upper(co.account_id) = {$dbh->quote(strtoupper(substr($args['search'], 1)))}";
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
                        $WHERE .= " $OR co.office_id = $int";
                        $WHERE .= " OR co.account_id = $int::TEXT";
                        $WHERE .= " OR co.fax like {$dbh->quote("%$str%")}";
                        $WHERE .= " OR co.mobile like {$dbh->quote("%$str%")}";
                        $OR = "OR"; # Use OR for remaining elements
                    }
                    if ($str)
                    {
                        $WHERE .= " $OR co.office_name ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR co.account_id ILIKE " . $dbh->quote("$str%");
                        $WHERE .= " OR ae.firstname || ' ' || ae.lastname ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR co.address1 ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR co.email ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR co.provnum ILIKE " . $dbh->quote("%$str%");
                        $WHERE .= " OR cp.office_name ILIKE " . $dbh->quote("%$str%");
                        $OR = "OR"; # Use OR for remaining elements
                    }
                }
            }

            $WHERE .= ")\n";
        }

        if ($args['corporate_parent'])
            $WHERE .= " AND upper(cp.account_id) = upper({$dbh->quote($args['corporate_parent'])})";
        if ($args['city'])
            $WHERE .= " AND upper(co.city) = upper({$dbh->quote($args['city'])})";
        if ($args['state'])
            $WHERE .= " AND upper(co.state) = upper({$dbh->quote($args['state'])})";
        if ($args['zip'])
            $WHERE .= " AND co.zip = {$dbh->quote($args['zip'])}";
        if ($args['phone'])
            $WHERE .= " AND co.phone = {$dbh->quote($args['phone'])}";
        if ($args['cpc'])
        {
            $u_g = substr($args['cpc'], 0, 1);
            $id_match = (int) substr($args['cpc'], 1);

            if ($u_g == 'u')
                $WHERE .= " AND co.account_executive = {$id_match}";
            else
                $WHERE .= " AND upg.group_id = {$id_match}";
        }

        return $WHERE;
    }

    /**
     * Supply html for the question section
     *
     * @return string
     */
    public function QuestionSection()
    {
        ## Set the defualt list of questions
        $html = "";
        $count = "";

        # Question Menu
        $iso = ($this->is_office) ? 1 : 0;
        $click = "GetAsync('{$_SERVER['PHP_SELF']}?act=save&object=CustomerQuestion&parent=question_disp&entry=0&customer_id={$this->office_id}&corporate_office_id={$this->corporate_office_id}&is_office=$iso', SetGoalContents);";
        $menu[0] = array('text' => 'New Question', 'class' => 'btn btn-default btn-sm', 'click' => $click, 'alt' => "Add New Question");
        $question_sub = BaseClass::BuildATags($menu);

        $section = "<div class='flx_cont'>
			<div id='question_cont' class='box box-primary'>
				<div id='question_hdr' class='box-header' data-toggle='collapse' data-target='#question_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Customer Questions</h4>
				</div>
				<div class='box-header width-border txar'>
					$question_sub
				</div>
				<div id='question_disp' class='box-body collapse'>
					{$this->QuestionTable($html, $count)}
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply html for the individual questions
     *
     * @param string
     * @param integer
     * @return string
     */
    public function QuestionTable(&$html, &$count)
    {
        $defaults = array("sort_by" => "g.goal_id", "dir" => "ASC", "g_status" => "all", "g_priority" => "all", "g_progress" => "all");
        $filter = SessionHandler::Update('crm', 'question', $defaults);
        $count = 0;
        $this->LoadQuestions($filter, false);

        foreach ($this->questions as $q)
        {
            $html .= "
			<div id='customerquestion_{$q->goal_id}'>
				" . CustomerQuestion::GetDisplay($q) . "
			</div>";
            $count++;
        }

        if ($count == 0)
        {
            $html .= "
			<div id='customerquestion_0'>
				No Questions have been entered
			</div>";
        }

        return $html;
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
        global $user;

        $this->last_mod_by = ($user) ? $user->getId() : 0;

        if ($this->office_id)
        {
            /*
             * Quick check to make sure corporate office ids are set correctly
             */
            if ($this->parent_id == 0)
                $this->corporate_office_id = $this->office_id;

            $sth = $this->dbh->exec("UPDATE corporate_office
			SET parent_id   = {$this->parent_id},
			    account_id  = '{$this->account_id}',
			    status      = {$this->status},
				last_mod    = CURRENT_TIMESTAMP,
			    last_mod_by = {$this->last_mod_by},
			    office_name = '" . $this->validateName($this->office_name) . "',
				orientation = {$this->orientation},
				nursing_homes = {$this->nursing_homes},
				acp_nursing_homes = {$this->acp_nursing_homes},
				corporate_office_id = {$this->corporate_office_id},
				account_executive = {$this->account_executive}
			WHERE office_id = {$this->office_id}");
        }
        else
        {
            $sth = $this->dbh->exec("INSERT INTO corporate_office
				(parent_id, account_id, status, last_mod, last_mod_by,
				office_name, orientation, nursing_homes, acp_nursing_homes,
				corporate_office_id, account_executive)
		 	VALUES ( {$this->parent_id}, '{$this->account_id}', {$this->status}, CURRENT_TIMESTAMP,
				{$this->last_mod_by}, '" . $this->validateName($this->office_name) . "', {$this->orientation},
				{$this->nursing_homes}, {$this->acp_nursing_homes},
				{$this->corporate_office_id}, {$this->account_executive} )");
        }

        if (!$this->office_id)
        {
            $this->office_id = $this->dbh->lastInsertId('corporate_office_office_id_seq');

            /*
             * When inserting a new top level office the id wont be set so we need to update the corporate_office_id
             */
            if ($this->parent_id == 0)
            {
                $this->corporate_office_id = $this->office_id;
                $this->change('corporate_office_id', $this->corporate_office_id);
            }

            /*
             * This is a unique key which may come from a different system
             * If its not set we will use our primary key
             */
            if ($this->account_id == "")
            {
                $this->account_id = $this->office_id;
                $this->change('account_id', $this->account_id);
            }

        }

        # Set/Reset Breadcrumb
        $this->setBreadCrumb();

        if (isset ($this->contacts[0]))
        {
            $this->contacts[0]->setVar('office_id', $this->office_id);
            $this->contacts[0]->save();
        }

        # Save details for this company
        # But only store real values
        if (is_array($this->detail) && isset ($_REQUEST['save_detail']))
        {
            $this->dbh->exec("DELETE FROM corporate_office_detail WHERE office_id = {$this->office_id}");

            foreach ($this->detail as $section => $section_ary)
            {
                foreach ($section_ary as $attribute => $attribute_ary)
                {
                    foreach ($attribute_ary as $field => $val_ary)
                    {
                        # Existing attribute maintain its id
                        if ($val_ary['id'] > 0)
                        {
                            $sth = $this->dbh->prepare("INSERT INTO corporate_office_detail
							(detail_id, office_id, section, attribute, field, field_type, value, show_lable)
							VALUES	(?, ?, ?, ?, ?, ?, ?, ?)");
                            $sth->bindValue(1, (int) $val_ary['id'], PDO::PARAM_INT);
                            $sth->bindValue(2, (int) $this->office_id, PDO::PARAM_INT);
                            $sth->bindValue(3, $section, PDO::PARAM_STR);
                            $sth->bindValue(4, $attribute, PDO::PARAM_STR);
                            $sth->bindValue(5, $field, PDO::PARAM_STR);
                            $sth->bindValue(6, $val_ary['type'], PDO::PARAM_STR);
                            $sth->bindValue(7, htmlentities($val_ary['value'], ENT_QUOTES, 'UTF-8'), PDO::PARAM_STR);
                            $sth->bindValue(8, (int) $val_ary['show_lable'], PDO::PARAM_BOOL);
                        }
                        # New attribute
                        else
                        {
                            $sth = $this->dbh->prepare("INSERT INTO corporate_office_detail
							(office_id, section, attribute, field, field_type, value, show_lable)
							VALUES	(?, ?, ?, ?, ?, ?, ?)");
                            $sth->bindValue(1, (int) $this->office_id, PDO::PARAM_INT);
                            $sth->bindValue(2, $section, PDO::PARAM_STR);
                            $sth->bindValue(3, $attribute, PDO::PARAM_STR);
                            $sth->bindValue(4, $field, PDO::PARAM_STR);
                            $sth->bindValue(5, $val_ary['type'], PDO::PARAM_STR);
                            $sth->bindValue(6, htmlentities($val_ary['value'], ENT_QUOTES, 'UTF-8'), PDO::PARAM_STR);
                            $sth->bindValue(7, (int) $val_ary['show_lable'], PDO::PARAM_BOOL);
                        }

                        # Only add to the DB if there is a value
                        if ($val_ary['value'])
                            $sth->execute();
                    }
                }
            }
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
    public static function Search($params)
    {
        global $dbh, $preferences;

        $search_type = (isset ($params['search_type'])) ? $params['search_type'] : '';
        $search = (isset ($params['search'])) ? $params['search'] : '';
        $active = (isset ($params['active'])) ? $params['active'] : 1;
        $ctype = (isset ($params['ctype'])) ? $params['ctype'] : 'a';
        $parent_id = (isset ($params['parent_id'])) ? (int) $params['parent_id'] : null;
        $ORDER = (isset ($params['order'])) ? $params['order'] : "";
        $DIR = (isset ($params['dir'])) ? $params['dir'] : "";
        $page = 0;
        $LIMIT = 0;
        $OFFSET = 0;
        if (isset ($params['page']))
        {
            $LIMIT = $preferences->get('general', 'results_per_page');
            $OFFSET = ($params['page'] - 1) * $LIMIT;
        }

        $FILTER = "";
        if ($search_type == 'simple')
            $FILTER = self::ParseSimpleSearch($params);
        else if ($search_type == 'advanced')
            $FILTER = self::ParseAdvanceSearch($params);

        $co_status = " = 1";
        if ($active === 'a')
            $co_status = " IN (1,0)";
        else if ($active === '0')
            $co_status = " = 0";

        $co_type = "AND co.object IS NOT NULL";
        if ($ctype == 'o')
            $co_type = "AND co.object = 'office'";
        else if ($ctype == 'f')
            $co_type = "AND co.object = 'facility'";

        $parent_filter = "";
        if (!is_null($parent_id))
            $parent_filter = " AND co.parent_id = $parent_id";

        # Search for office records
        # Match on any office name in the company
        #
        $sql = "SELECT
			co.office_id,
			co.corporate_office_id,
			co.parent_id,
			co.office_name as cust_name,
			co.account_id as cust_id,
			cs.office_status as status,
			co.last_mod,
			co.provnum,
			co.orientation,
			co.nursing_homes,
			co.acp_nursing_homes,
			co.account_executive,
			co.object,
			cp.account_id as corp_parent,
			cp.office_name as corp_parent_name,
			lm.firstname as lm_first,
			lm.lastname as lm_last,
			ae.firstname as am_first,
			ae.lastname as am_last,
			upg.group_id as region_id,
			g.lastname as region,
			co.contact_id,
			co.address1,
			co.address2,
			co.city,
			co.state,
			co.zip,
			co.phone,
			co.fax,
			co.mobile,
			co.email,
			co.latitude,
			co.longitude,
			co.title as contact_title,
			co.role as contact_role,
			po.children as office_num,
			f.facilities as facility_num,
			COUNT(*) OVER () as total_rows
		FROM (
			SELECT
				co.office_id,
				co.account_id,
				co.corporate_office_id,
				co.parent_id,
				co.office_name,
				co.office_name as cust_name,
				co.account_id as cust_id,
				co.status,
				co.last_mod,
				co.last_mod_by,
				NULL as provnum,
				co.orientation,
				co.nursing_homes,
				co.acp_nursing_homes,
				co.account_executive,
				oc.contact_id,
				oc.address1,
				oc.address2,
				oc.city,
				oc.state,
				oc.zip,
				oc.phone,
				oc.fax,
				oc.mobile,
				oc.email,
				oc.latitude,
				oc.longitude,
				oc.title,
				oc.role,
				'office'::text as object
			FROM corporate_office co
			LEFT JOIN (SELECT
					j.office_id, oc.*
				FROM corporate_office_contact_join j
				INNER JOIN contact oc ON j.contact_id = oc.contact_id AND oc.is_office = true
			) oc on co.office_id = oc.office_id
			UNION
			SELECT
				f.id as office_id,
				f.accounting_id as account_id,
				cp.office_id as corporate_office_id,
				po.office_id as parent_id,
				f.facility_name office_name,
				f.facility_name cust_name,
				f.accounting_id as cust_id,
				CASE WHEN f.cancelled THEN 0 ELSE 1 END as status,
				f.last_mod,
				0 as last_mod_by,
				f.provnum,
				0 as orientation,
				1 as nursing_homes,
				1 as acp_nursing_homes,
				f.cpt_id as account_executive,
				null as contact_id,
				f.address as address1,
				f.address2,
				f.city,
				f.state,
				f.zip,
				f.phone,
				f.fax,
				'' as mobile,
				d.fa_email as email,
				d.latitude,
				d.longitude,
				'' as title,
				'' as role,
				'facility'::text as object
			FROM facilities f
			INNER JOIN facilities_details d on f.id = d.facility_id
			INNER JOIN corporate_office cp on f.corporate_parent = cp.account_id
			LEFT JOIN corporate_office po on f.parent_office = po.account_id
		) co
		INNER JOIN corporate_office_status cs ON co.status = cs.status_id
		INNER JOIN corporate_office cp ON co.corporate_office_id = cp.office_id
		LEFT JOIN users ae ON co.account_executive = ae.id
		LEFT JOIN users lm ON co.last_mod_by = lm.id
		LEFT JOIN v_users_primary_group upg on ae.id = upg.user_id
		LEFT JOIN users g on upg.group_id = g.id
		LEFT JOIN (
			SELECT
				parent_id, count(*) as children
			FROM corporate_office
			WHERE status IN (1,0)
			GROUP BY parent_id
		) po ON co.office_id = po.parent_id
		LEFT JOIN (
			SELECT
				corporate_parent, count(*) as facilities
			FROM facilities f
			WHERE corporate_parent IS NOT NULL
			GROUP BY corporate_parent
		) f ON co.account_id = f.corporate_parent
		WHERE co.status $co_status
		$co_type
		$parent_filter
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
     * Dual purpose function for displaying an Office record
     * Either as an editable form or friendly view only display
     *
     * @param bool $edit editable form or view
     * @param array $form sumitted form key => value pairs
     * @return string return appropriate html
     */
    public function showForm($edit = 0, $form = null)
    {
        global $user;

        $js = "";

        if ($edit)
        {
            # Show the editing form
            if ($this->office_id == 0)
            {
                if (isset ($form['parent_id']))
                    $this->parent_id = (int) $form['parent_id'];
                if (isset ($form['corporate_office_id']))
                    $this->corporate_office_id = (int) $form['corporate_office_id'];

                $this->copyFromParent($this->parent_id);
            }

            $office_form = $this->CompanyHdr();
        }
        else
        {
            // Viable display items
            $add_facs = "";
            $add_subs = "";
            $add_task = "";
            $add_leads = "";
            $add_clin = "";
            $add_thrp = "";

            $iso = ($this->is_office) ? 1 : 0;

            $this->LoadSubs();
            //	$this->LoadContacts();

            $edit_link = "";
            if ($user->inPermGroup(User::$IT) || $user->hasAccessTemplates(7))
            {
                $edit_link = "<a class='btn btn-sm' onClick=\"window.open('clinrec_admin.php', target='_blank')\">
                <i class='fa fa-edit'></i> Edit
            </a>";
            }

            $base_args = "act=db&obj=section&entry={$this->office_id}&is_office=$iso&user_id={$user->GetId()}";

            $office_form = "";

            if ($this->is_office)
                $office_form .= $this->CorpInfo();
            else
                $office_form .= $this->FacilityInfo();

            # Show the viewing form
            $office_form .= "
			<div class='main skin-black-light'>";

            if ($this->is_office)
                $office_form .= $this->DetailSection();
            else
                $office_form .= $this->ClinicSection();

            $office_form .= $this->ContactSection();
            $office_form .= $this->VisitSection();

            if ($this->is_office)
            {
                $office_form .= $this->TaskSection();

                $task_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=TaskTable", 'name' => 'task', 'display_id' => 'task_disp', 'nav_badge_id' => 'task_nav_badge', 'sec_badge_id' => 'task_sec_badge'));
                $add_task = "CRMLoader.Add($task_conf);";
            }
            else
            {
                $office_form .= $this->ClinRecSection();
            }

            $office_form .= $this->OrderSection();
            $office_form .= $this->IssueSection();
            $office_form .= $this->ContractSection();
            $office_form .= $this->EquipmentSection();
            $office_form .= $this->QuestionSection();
            $office_form .= $this->GoalSection();

            $office_form .= $this->CalendarSection();


            if ($this->is_office)
            {
                $office_form .= $this->SubdivisionSection();
                $subs_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=SubdivisionTable", 'name' => 'sub', 'display_id' => 'sub_disp', 'nav_badge_id' => 'sub_nav_badge', 'sec_badge_id' => 'sub_sec_badge'));
                $add_subs = "CRMLoader.Add($subs_conf);";

                $office_form .= $this->FacilitySection();
                $facs_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=FacilityTable", 'name' => 'facility', 'display_id' => 'facility_disp', 'nav_badge_id' => 'fac_nav_badge', 'sec_badge_id' => 'fac_sec_badge'));
                $add_facs = "CRMLoader.Add($facs_conf);";
            }

            if ($this->is_office)
            {
                $office_form .= $this->LeadSection();
                $lead_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=LeadTable", 'name' => 'lead', 'display_id' => 'lead_disp', 'nav_badge_id' => 'lead_nav_badge', 'sec_badge_id' => 'lead_sec_badge'));
                $add_leads = "CRMLoader.Add($lead_conf);";
            }
            else
            {

                $office_form .= $this->TherapistSection();

                $clin_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=ClinrecTable", 'name' => 'clin', 'display_id' => 'clin_disp', 'nav_badge_id' => 'clin_nav_badge', 'sec_badge_id' => 'clin_sec_badge'));
                $therapist_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=TherapistTable", 'name' => 'therapist', 'display_id' => 'therapist_disp', 'nav_badge_id' => 'therapist_nav_badge', 'sec_badge_id' => 'therapist_sec_badge'));
                $add_clin = "CRMLoader.Add($clin_conf);";
                $add_thrp = "CRMLoader.Add($therapist_conf);";
            }
            if (!isset ($_REQUEST['skip_mas']))
            {
                $office_form .= $this->MemoSection();
            }
            $office_form .= $this->InvoiceSection();
            $office_form .= $this->WorkOrderSection();

            $office_form .= "
			</div>";

            $gui_config = json_encode(array('multi_section' => true, 'update_session' => true));

            $detail_conf = json_encode(array('name' => 'detail', 'display_id' => 'detail_disp'));
            $goal_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=GoalTable", 'name' => 'goal', 'display_id' => 'goal_disp'));
            $issue_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=IssueTable", 'name' => 'issue', 'display_id' => 'issue_disp', 'nav_badge_id' => 'iss_nav_badge', 'sec_badge_id' => 'iss_sec_badge'));
            $calendar_conf = json_encode(array('name' => 'calendar', 'display_id' => 'calendar_disp'));
            $visit_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=VisitTable", 'name' => 'visit', 'display_id' => 'visit_disp', 'nav_badge_id' => 'visit_nav_badge', 'sec_badge_id' => 'visit_sec_badge'));
            $contact_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=ContactTable", 'name' => 'contact', 'display_id' => 'contact_disp', 'nav_badge_id' => 'contact_nav_badge', 'sec_badge_id' => 'contact_sec_badge'));
            $equip_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=EquipmentTable", 'name' => 'equip', 'display_id' => 'equip_disp', 'nav_badge_id' => 'equip_nav_badge', 'sec_badge_id' => 'equip_sec_badge'));
            $contract_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=ContractTable", 'name' => 'contract', 'display_id' => 'contract_disp', 'nav_badge_id' => 'con_nav_badge', 'sec_badge_id' => 'con_sec_badge'));
            $order_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=OrderTable", 'name' => 'order', 'display_id' => 'order_disp', 'nav_badge_id' => 'order_nav_badge', 'sec_badge_id' => 'order_sec_badge'));
            $invoice_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=Invoice_bm_Table", 'name' => 'invoice', 'display_id' => 'invoice_disp', 'nav_badge_id' => 'invoice_nav_badge', 'sec_badge_id' => 'invoice_sec_badge'));
            $wo_conf = json_encode(array('req' => 'corporateoffice.php', 'args' => "$base_args&method=WorkOrderTable", 'name' => 'wo', 'display_id' => 'wo_disp', 'nav_badge_id' => 'wo_nav_badge', 'sec_badge_id' => 'wo_sec_badge'));

            $js = "<script type='text/javascript'>
var session_updater = 'corporateoffice.php';
var SessionSection = 'btns';
var CRMLoader;
$(function() {
	CRMLoader = new GUIContent($gui_config);
	CRMLoader.Add($detail_conf);
	CRMLoader.Add($goal_conf);
	CRMLoader.Add($issue_conf);
	CRMLoader.Add($calendar_conf);
	CRMLoader.Add($visit_conf);
	CRMLoader.Add($contact_conf);
	CRMLoader.Add($equip_conf);
	CRMLoader.Add($contract_conf);
	CRMLoader.Add($order_conf);
	CRMLoader.Add($invoice_conf);
	CRMLoader.Add($wo_conf);
	$add_subs
	$add_facs
	$add_task
	$add_leads
	$add_clin
	$add_thrp

	$('.collapse')
	.on('shown.bs.collapse', function()
	{
		$(this).parent().find(\".fa-plus\").removeClass(\"fa-plus\").addClass(\"fa-minus\");
		var section = $(this).parent().find(\".box-header\").attr('id');
		if (section)
		{
			$('#'+section.replace(\"_hdr\",\"_btn\")).addClass('active');
			CRMLoader.LoadContent(section.replace(\"_hdr\",\"\"), false);

			$('textarea').autoResize();
		}
	})
	.on('hidden.bs.collapse', function()
	{
		$(this).parent().find(\".fa-minus\").removeClass(\"fa-minus\").addClass(\"fa-plus\");
		var section = $(this).parent().find(\".box-header\").attr('id');
		if (section)
			$('#'+section.replace(\"_hdr\",\"_btn\")).removeClass('active');
	});


	$('#affix_cont').on('affixed.bs.affix', function()
	{
		$('div.main').css('margin-top','350px');
	});
	$('#affix_cont').on('affixed-top.bs.affix', function()
	{
		$('div.main').css('margin-top','0');
	});
});

/**
 * Turn affix control on/off
 */
function ToggleAffix()
{
	if ($('#affix_btn').hasClass('fa-lock'))
	{
		$('#affix_btn').removeClass('fa-lock');
		$('#affix_btn').addClass('fa-unlock');
		$('#affix_cont').data('bs.affix').options.offset.top = 1000000000;
	}
	else
	{
		$('#affix_btn').removeClass('fa-unlock');
		$('#affix_btn').addClass('fa-lock');
		$('#affix_cont').data('bs.affix').options.offset.top = 55;
	}
}
</script>";
        }

        echo $js;
        echo $office_form;
    }

    /**
     * Get listing of the child offices
     *
     * @return string subdivion rows for the child offices
     */
    private function SubdivisionSection()
    {
        global $user, $this_app_name, $sh;

        # Subdivision Menu
        $iso = ($this->is_office) ? 1 : 0;
        $sub_count = count($this->subs);
        $click = "InitDialog(office_conf,'{$_SERVER['PHP_SELF']}?act=getform&object=CorporateOffice&entry=0&office_id=0&parent_id={$this->office_id}&is_office=$iso');";
        $menu[0] = array('text' => 'New Subdivistion', 'class' => 'btn btn-default btn-sm', 'click' => $click, 'alt' => "Add New Subdivision");
        $sub_sub = BaseClass::BuildATags($menu);

        $subdivision_section = "<div class='flx_cont'>
			<div id='sub_cont' class='box box-primary'>
				<div id='sub_hdr' class='box-header' data-toggle='collapse' data-target='#sub_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Subdivisions</h4>
					<span class='badge' id='sub_sec_badge'></span>
				</div>
				<div class='box-header with-border fltr txar'>
					$sub_sub
				</div>
				<div id='sub_disp' class='box-body collapse'>
					<div class='on'>Loading...</div>
				</div>
			</div>
		</div>";

        return $subdivision_section;
    }

    /**
     * Get listing of the child offices
     * @param string
     * @param int
     *
     * @return string subdivion rows for the child offices
     */
    public function SubdivisionTable(&$html, &$count)
    {
        global $user, $this_app_name, $sh;

        $this->LoadSubs();
        $count = count($this->subs);

        $i = 1;
        $sub_rows = "";
        if ($count > 0)
        {
            foreach ($this->subs as $sub)
            {
                $office_name = htmlentities($sub->cust_name, ENT_QUOTES);
                $name = "<a alt='View Subdivision' title='View Subdivision' href='{$_SERVER['PHP_SELF']}?act=view&object=office&office_id={$sub->office_id}'>$office_name</a>";

                $sub_rows .= self::CompanyInfo($sub, $i++, 0);
            }
        }
        else
        {
            $sub_rows .= "<div class='on' align='center'>No subdivisions found.</div>";
        }

        $html = $sub_rows;

        return $html;
    }

    /**
     * Build the breadcrumb
     *
     * Used for navigating up the company tree
     */
    private function setBreadCrumb()
    {
        $this->breadcrumb = htmlentities($this->office_name, ENT_QUOTES);
        if (preg_match('/^...[69]..$/', $this->account_id))
            $this->breadcrumb .= " ($this->account_id)";

        $parent_id = $this->parent_id;
        while ($parent_id > 0)
        {
            $sth = $this->dbh->prepare("SELECT office_id, office_name, parent_id FROM corporate_office WHERE office_id = {$parent_id}");
            $sth->execute();
            $parent_id = 0; // stop if we dont find a parent
            if ($row = $sth->fetch(PDO::FETCH_ASSOC))
            {
                $office_name = htmlentities($row['office_name'], ENT_QUOTES);
                $this->breadcrumb = "<a href='{$_SERVER['PHP_SELF']}?action=view&entry={$row['office_id']}'>{$office_name}</a>&nbsp;&gt;&nbsp;" . $this->breadcrumb;
                if ($row['parent_id'] > 0)
                {
                    $parent_id = $row['parent_id'];
                }
            }
        }
    }

    /**
     * Build list of facilities
     * Store in session since this will be used multiple times
     *
     * @return string
     */
    public function SetCustomerList()
    {
        if (isset ($_SESSION))
        {
            if (!$this->is_office)
            {
                # Match the facility
                $match = "f.id";
            }
            else if ($this->parent_id == 0)
            {
                # Match all
                $match = "co.corporate_office_id";
            }
            else
            {
                # Match those for this subdivision
                $match = "co.office_id";
            }

            ## Getting much better performance using f.id set as a filter
            $sth = $this->dbh->prepare("SELECT
				ARRAY_TO_STRING(ARRAY_ACCUM(f.id), ',') as facility_list
			FROM facilities f
			LEFT JOIN corporate_office co ON f.parent_office = co.account_id
			WHERE $match = ?");
            $sth->bindValue(1, $this->office_id, PDO::PARAM_INT);
            $sth->execute();
            $facility_list = $sth->fetchColumn();
            if (empty ($facility_list))
                $facility_list = "0";

            $_SESSION['crm']['set'][$match][$this->office_id] = $facility_list;
        }

        return $_SESSION['crm']['set'][$match][$this->office_id];
    }

    /**
     * Create HTML for common section table listing
     *
     * @param object
     * @param string
     */
    public function SectionHTML($conf)
    {
        $iso = ($this->is_office) ? 1 : 0;
        $next_dir = ($conf->dir == 'ASC') ? 'DESC' : 'ASC';
        $url = "{$conf->base_url}&page=__PAGE__";
        $href = "javascript: FillSection('$url', '{$conf->disp}','{$conf->nav_badge}','{$conf->sec_badge}');";
        $col_tag = "<span class='lnk' style='float:right;' onclick=\"ColChooser(event, '{$conf->col_list_name}', '{$conf->base_url}', '{$conf->disp}','{$conf->nav_badge}','{$conf->sec_badge}');\" alt='Set Columns' title='Set Columns'>||</span>";
        $pagination = self::GetPageBar($conf->count, $conf->row_count, $conf->page, $conf->per_page, $href, $col_tag);

        $th_tags = "";
        foreach ($conf->cols as $col)
        {
            $click = "FillSection('{$conf->base_url}&page=1&sort_by={$col->field}&dir=$next_dir', '{$conf->disp}', '{$conf->nav_badge}', '{$conf->sec_badge}');";

            $text = $col->hdr;
            if ($conf->sort == $col->field)
                $text .= "<span class='{$conf->dir}'>&nbsp;&nbsp;&nbsp;</span>";

            $th_tags .= "<th class='hdr sortable' onclick=\"$click\">$text</th>";
        }

        if ($conf->show_action)
            $th_tags .= "<th class='hdr'>Action</th>";

        $html = "
		{$conf->filter}
		$pagination
		<table class='dt' width='100%' cellspacing='0' cellpadding='4' border='1' style='clear:right;'>
			<tbody>
				<tr>$th_tags</tr>
				{$conf->rows}
			</tbody>
		</table>
		$pagination";

        return $html;
    }

    /**
     * Lookup payment terms from accounting system
     */
    public function SetPaymentTerms()
    {
        $mdbh = DataStor::GetHandle();
        $CompanyID = (is_numeric($this->account_id)) ? self::$INI_CO : self::$DEFAULT001_CO;

        if ($mdbh)
        {
            $sql = "SELECT
				PmtTermsID, Description
			FROM tarCustomer c
			INNER JOIN tarCustAddr a ON c.PrimaryAddrKey = a.AddrKey
			INNER JOIN tciPaymentTerms p ON a.PmtTermsKey = p.PmtTermsKey
			WHERE c.CustID = ? AND c.CompanyID = ?";
            $sth = $mdbh->prepare($sql);
            $sth->bindValue(1, $this->account_id, PDO::PARAM_STR);
            $sth->bindValue(2, $CompanyID, PDO::PARAM_STR);
            $sth->execute();

            list($pt_id, $pt_description) = $sth->fetch(PDO::FETCH_NUM);

            $this->payment_terms_id = $pt_id;
            $this->payment_terms_desc = $pt_description;
        }
    }

    /**
     * Supply html for the Task section
     *
     * @return string
     */
    public function TaskSection()
    {
        $section = "<div class='flx_cont'>
			<div id='task_cont' class='box box-primary'>
				<div id='task_hdr' class='box-header' data-toggle='collapse' data-target='#task_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Tasks</h4>
					<span class='badge' id='task_sec_badge'></span>
				</div>
				<div id='task_disp' class='box-body collapse'>
					<div class='on'>Loading...</div>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply table for the Task section
     *
     * @return string
     */
    public function TaskTable(&$html, &$count)
    {
        global $this_app_name, $preferences;

        $date_format = $preferences->get('general', 'dateformat');
        $defaults = array("page" => 1, "sort_by" => "open_date", "dir" => "ASC", "t_important" => "a", "t_required" => "a", "t_completed" => "a");
        $filter = SessionHandler::Update('crm', 'task', $defaults);
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 1;
        $sec_conf->disp = 'task_disp';
        $sec_conf->nav_badge = 'task_nav_badge';
        $sec_conf->sec_badge = 'task_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=TaskTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_task_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        $this->LoadTasks($filter);

        ## Init totals for Lease, Purchase and Loaner
        $sec_conf->rows = "";
        $rc = 'dt-tr-on';
        $row_count = 0;
        foreach ($this->tasks as $task)
        {
            $sec_conf->row_count++;
            # Need to page results useing a window function
            # for true total. This is return in total_records field.
            $count = $task->row_count;
            $sec_conf->count = $task->row_count;
            $tags = array();
            ## Edit
            $req_url = "templates/sm/forms/task.php?entry={$task->task_id}";
            $tags[] = array('text' => "edit", 'alt' => 'Edit this task', 'click' => "InitDialog(task_conf,'{$req_url}')");

            $menu = BaseClass::BuildATags($tags);

            ## Show inactive?
            $ia = ($task->closed_date) ? " faded" : "";

            $task->open_date = date($date_format, $task->open_date);
            $task->due_date = ($task->due_date) ? date($date_format, $task->due_date) : "";
            $task->last_mod_date = ($task->last_mod_date) ? date($date_format, $task->last_mod_date) : "";
            $task->closed_date = ($task->closed_date) ? date($date_format, $task->closed_date) : "";
            $task->important = ($task->important) ? "Yes" : "No";
            $task->required = ($task->required) ? "Yes" : "No";

            $sec_conf->rows .= "<tr class='{$rc}{$ia}' ondbclick=\"InitDialog(task_conf,'{$req_url}');\">";

            foreach ($sec_conf->cols as $col)
            {
                $sec_conf->rows .= "<td class='{$col->cls}'>{$task->{$col->key} }</td>\n";
            }

            $sec_conf->rows .= "
				<td class='nested'><div class='submenu'>$menu</div></td>
			</tr>";

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        if ($count == 0)
        {
            $cs = count($sec_conf->cols) + 1;
            $sec_conf->rows = "<tr class='dt-tr-on'><td colspan='$cs' style='text-align:center;'>No Tasks found.</td></tr>";
        }

        ## define attributes used to query for a page of data
        $base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=TaskTable&entry={$this->office_id}";

        $completed_y = ($filter['t_completed'] == 1) ? "checked" : "";
        $completed_n = ($filter['t_completed'] == 0) ? "checked" : "";
        $completed_a = ($filter['t_completed'] == 'a') ? "checked" : "";

        $important_y = ($filter['t_important'] == 1) ? "checked" : "";
        $important_n = ($filter['t_important'] == 0) ? "checked" : "";
        $important_a = ($filter['t_important'] == 'a') ? "checked" : "";

        $required_y = ($filter['t_required'] == 1) ? "checked" : "";
        $required_n = ($filter['t_required'] == 0) ? "checked" : "";
        $required_a = ($filter['t_required'] == 'a') ? "checked" : "";

        $sec_conf->filter = "<div class='filter nested' align='left'>
			<span class='lbl'>Completed:</span>
			<input type='radio' name='t_completed' id='t_completed_y' value='1' $completed_y onchange=\"FillSection('$base_url&page=1&t_completed=1','task_disp','task_nav_badge','task_sec_badge');\">
			<label for='t_completed_y'>Yes</label>
			<input type='radio' name='t_completed' id='t_completed_n' value='0' $completed_n onchange=\"FillSection('$base_url&page=1&t_completed=0','task_disp','task_nav_badge','task_sec_badge');\">
			<label for='t_completed_n'>No</label>
			<input type='radio' name='t_completed' id='t_completed_a' value='a' $completed_a onchange=\"FillSection('$base_url&page=1&t_completed=a','task_disp','task_nav_badge','task_sec_badge');\">
			<label for='t_completed_a'>All</label>

			<span class='lbl'>Important:</span>
			<input type='radio' name='t_important' id='timportant_y' value='1' $important_y onchange=\"FillSection('$base_url&page=1&t_important=1','task_disp','task_nav_badge','task_sec_badge');\">
			<label for='t_important_y'>Yes</label>
			<input type='radio' name='t_important' id='t_important_n' value='0' $important_n onchange=\"FillSection('$base_url&page=1&t_important=0','task_disp','task_nav_badge','task_sec_badge');\">
			<label for='t_important_n'>No</label>
			<input type='radio' name='t_important' id='t_important_a' value='a' $important_a onchange=\"FillSection('$base_url&page=1&t_important=a','task_disp','task_nav_badge','task_sec_badge');\">
			<label for='t_important_a'>All</label>

			<span class='lbl'>Required:</span>
			<input type='radio' name='t_required' id='t_required_y' value='1' $required_y onchange=\"FillSection('$base_url&page=1&t_required=1','task_disp','task_nav_badge','task_sec_badge');\">
			<label for='t_required_y'>Yes</label>
			<input type='radio' name='t_required' id='t_required_n' value='0' $required_n onchange=\"FillSection('$base_url&page=1&t_required=0','task_disp','task_nav_badge','task_sec_badge');\">
			<label for='t_required_n'>No</label>
			<input type='radio' name='t_required' id='t_required_a' value='a' $required_a onchange=\"FillSection('$base_url&page=1&t_required=a','task_disp','task_nav_badge','task_sec_badge');\">
			<label for='t_required_a'>All</label>
		</div>";

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    /**
     * Supply html for the section
     *
     * @return string
     */
    public function TherapistSection()
    {
        $section = "<div class='flx_cont'>
			<div id='therapist_cont' class='box box-primary'>
				<div id='therapist_hdr' class='box-header' data-toggle='collapse' data-target='#therapist_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Therapist</h4>
					<span class='badge' id='therapist_sec_badge'></span>
				</div>
				<div id='therapist_disp' class='box-body collapse'>
					<div class='on'>Loading...</div>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply html table for the section
     *
     * @return string
     */
    public function TherapistTable(&$html, &$count)
    {
        global $this_app_name, $preferences;

        $date_format = $preferences->get('general', 'dateformat');

        $defaults = array("page" => 1, "sort_by" => "lastname", "dir" => "ASC", "t_active" => "a", "profession_id" => "a");
        SessionHandler::Update('crm', 'therapist', $defaults);
        $filter = $_SESSION['crm']['therapist'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 1;
        $sec_conf->disp = 'therapist_disp';
        $sec_conf->nav_badge = 'therapist_nav_badge';
        $sec_conf->sec_badge = 'therapist_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=TherapistTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_therapist_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        $this->LoadTherapist($filter);

        ## Init vars
        $sec_conf->rows = "";
        $rc = 'dt-tr-on';
        $row_count = 0;
        foreach ($this->therapists as $therapist)
        {
            $sec_conf->row_count++;
            # Need to page results useing a window function
            # for true total. This is return in total_records field.
            $count = $therapist->row_count;
            $sec_conf->count = $therapist->row_count;

            $tags = array();
            ## Edit
            $req_url = "therapists.php?action=edit&therapist={$therapist->id}";
            $tags[] = array('text' => "edit", 'alt' => 'Edit this record', 'href' => $req_url, $target = '_blank');

            $menu = BaseClass::BuildATags($tags);

            ## Show inactive?
            $ia = ($therapist->active) ? "" : " faded";

            $add_time = date($date_format, strtotime($therapist->add_time));
            $last_mod = date($date_format, strtotime($therapist->last_mod));
            $active = ($therapist->active) ? "Yes" : "No";

            $sec_conf->rows .= "<tr class='{$rc}{$ia}' ondblclick=\"window.location='{$req_url}';\">";
            foreach ($sec_conf->cols as $col)
            {
                $sec_conf->rows .= "<td class='{$col->cls}'>{$therapist->{$col->key} }</td>\n";
            }
            $sec_conf->rows .= "<td class='nested' style='text-align:right;'>
					<div class='submenu'>
						$menu
					</div>
				</td>
			</tr>";

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        if ($count == 0)
        {
            $cs = count($sec_conf->cols) + 1;
            $sec_conf->rows = "<tr class='dt-tr-on'><td colspan=$cs style='text-align:center;'>No Therapist found.</td></tr>";
        }

        $active_y = ($filter['t_active'] == 1) ? "checked" : "";
        $active_n = ($filter['t_active'] == 0) ? "checked" : "";
        $active_a = ($filter['t_active'] == 'a') ? "checked" : "";

        $prof_options = "<option value='a'>All</option>\n";
        $sql = "SELECT id, name FROM professions ORDER BY display_order";
        $sth = $this->dbh->query($sql);
        while ($opt = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $sel = ($opt['id'] == $filter['profession_id']) ? " selected" : "";
            $prof_options .= "<option value='{$opt['id']}'$sel>{$opt['name']}</option>\n";
        }

        $base_url = $sec_conf->base_url;
        $sec_conf->filter = "<div class='filter nested' align='left'>
			<span class='lbl'>Active:</span>
			<input type='radio' name='t_active' id='t_active_y' value='1' $active_y onchange=\"FillSection('$base_url&page=1&t_active=1','therapist_disp','therapist_nav_badge','therapist_sec_badge');\">
			<label for='t_active_y'>Yes</label>
			<input type='radio' name='t_completed' id='t_active_n' value='0' $active_n onchange=\"FillSection('$base_url&page=1&t_active=0','therapist_disp','therapist_nav_badge','therapist_sec_badge');\">
			<label for='t_active_n'>No</label>
			<input type='radio' name='t_completed' id='t_active_a' value='a' $active_a onchange=\"FillSection('$base_url&page=1&t_active=a','therapist_disp','therapist_nav_badge','therapist_sec_badge');\">
			<label for='t_active_a'>All</label>

			<span class='lbl'>Profession:</span>
			<select name='profession_id' onchange=\"FillSection('$base_url&page=1&profession_id='+this.value,'therapist_disp','therapist_nav_badge','therapist_sec_badge');\">
				$prof_options
			</select>
		</div>";

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    /**
     * Validate the Name of a corporate office.
     */
    static private function validateName($name)
    {
        if ($name == null)
            return null;

        $patterns[0] = '\\';
        $patterns[1] = '\'';

        $replaced[0] = '';
        $replaced[1] = '\'\'';

        $name = str_replace($patterns, $replaced, $name);

        return $name;
    }

    /**
     * Supply html for the section
     *
     * @return string
     */
    public function VisitSection()
    {
        global $user, $preferences;

        $date_format = $preferences->get('general', 'dateformat');

        $startdate = date($date_format, strtotime("January 1st"));
        $enddate = date($date_format);

        $click1 = "window.open('short_visit.php?react=newEvent&fid={$this->office_id}&type=31&act=edit', 'REMOTE_CPC_CONSULTATION','status=no,height=800,width=900,resizable=yes,toolbar=no,menubar=no,scrollbars=no,location=no,directories=no')";
        $click2 = "window.open('short_visit.php?react=newEvent&fid={$this->office_id}&type=32&act=edit', 'REMOTE_CLINICAL_SERVICE','status=no,height=800,width=900,resizable=yes,toolbar=no,menubar=no,scrollbars=no,location=no,directories=no')";
        $click3 = "acplog.php?order=startdtime_desc&startdate=$startdate&enddate=$enddate&usgr=u{$user->getId()}&facility_id={$this->office_id}&facility_name=" . urlencode($this->office_name) . "&action=Search";

        $menu[0] = array('text' => 'New Remote CPC Consultation', 'class' => 'btn btn-default btn-sm', 'click' => $click1, 'alt' => "Add New Remote CPC Consultation");
        $menu[1] = array('text' => 'New Remote Clinical Service', 'class' => 'btn btn-default btn-sm', 'click' => $click2, 'alt' => "Add New Remote Clinical Service");
        $menu[2] = array('text' => 'Visit Log', 'class' => 'btn btn-default btn-sm', 'href' => $click3, 'alt' => "Go to Log");
        $visit_sub = BaseClass::BuildATags($menu);

        $section = "<div class='flx_cont'>
			<div id='visit_cont' class='box box-primary'>
				<div id='visit_hdr' class='box-header' data-toggle='collapse' data-target='#visit_disp' style='cursor:pointer;'>
					<i class='fa fa-minus pull-right'></i>
					<h4 class='box-title'>Visits</h4>
					<span class='badge' id='visit_sec_badge'></span>
				</div>
				<div class='box-header with-border fltr txar'>
					$visit_sub
				</div>
				<div id='visit_disp' class='box-body collapse in'>
					<div class='on'>Loading...</div>
					<script type='text/javascript'>$(function () { CRMLoader.LoadContent('visit', false); });</script>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply html table for the section
     *
     * @return string
     */
    public function VisitTable(&$html, &$count)
    {
        global $this_app_name, $preferences;

        $date_format = $preferences->get('general', 'dateformat');

        $defaults = array("page" => 1, "sort_by" => "start_time", "dir" => "DESC", "t_active" => "a", "profession_id" => "a");
        SessionHandler::Update('crm', 'visit', $defaults);
        $filter = $_SESSION['crm']['visit'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 1;
        $sec_conf->disp = 'visit_disp';
        $sec_conf->nav_badge = 'visit_nav_badge';
        $sec_conf->sec_badge = 'visit_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=VisitTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->filter = "";
        $sec_conf->col_list_name = "co_visit_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        $this->LoadVisits($filter);

        ## Init vars
        $sec_conf->rows = "";
        $rc = 'dt-tr-on';
        $row_count = 0;
        foreach ($this->visits as $visit)
        {
            $sec_conf->row_count++;
            # Need to page results useing a window function
            # for true total. This is return in total_records field.
            $count = $visit->row_count;
            $sec_conf->count = $visit->row_count;

            $tags = array();

            if ($visit->visit_id)
            {
                if ($visit->category_id == 31 || $visit->category_id == 32)
                {
                    ## View
                    $view_url = "short_visit.php?act=view&vsid={$visit->visit_id}";
                    $tags[] = array('class' => 'btn btn-default btn-xs', 'text' => "View", 'alt' => 'View this record', 'href' => $view_url, 'target' => '_blank');
                    ## Edit
                    $req_url = "short_visit.php?act=edit&vsid={$visit->visit_id}";
                    $tags[] = array('class' => 'btn btn-default btn-xs', 'text' => "<i class='fa fa-pencil'></i> edit", 'alt' => 'Edit this record', 'href' => $req_url, 'target' => '_blank');
                }
                else
                {
                    ## View
                    $view_url = "visit_summary.php?act=view&vsid={$visit->visit_id}";
                    $tags[] = array('class' => 'btn btn-default btn-xs', 'text' => "View", 'alt' => 'View this record', 'href' => $view_url, 'target' => '_blank');

                    ## Edit
                    $req_url = "visit_summary.php?act=edit&vsid={$visit->visit_id}";
                    $tags[] = array('class' => 'btn btn-default btn-xs', 'text' => "<i class='fa fa-pencil'></i> edit", 'alt' => 'Edit this record', 'href' => $req_url, 'target' => '_blank');
                }

                ## PDF
                $pdf_url = "visit_summary.php?act=view_pdf&vsid={$visit->visit_id}";
                $tags[] = array('class' => 'btn btn-default btn-xs', 'text' => "<i class='fa fa-file-pdf-o'></i> PDF", 'alt' => 'View this record', 'href' => $pdf_url, 'target' => '_blank');

            }
            else if ($visit->event_id)
            {
                ## View
                $view_url = "calendar.php?act=viewe&event={$visit->event_id}";
                $tags[] = array('class' => 'btn btn-default btn-xs', 'text' => "View", 'alt' => 'View this record', 'href' => $view_url, 'target' => '_blank');
            }

            $menu = BaseClass::BuildATags($tags);

            ## Show inactive?
            $ia = ($visit->valid) ? "" : " faded";

            $visit->start_time = date($date_format, $visit->start_time);
            $visit->end_time = date($date_format, $visit->end_time);
            $visit->lock_date = date($date_format, $visit->lock_date);
            $visit->last_mod = date($date_format, strtotime($visit->last_mod));
            $visit->valid = ($visit->valid) ? "Yes" : "No";
            $visit->mailed = ($visit->mailed) ? "Yes" : "No";

            $sec_conf->rows .= "<tr class='{$rc}{$ia}' ondblclick=\"window.open('{$view_url}','_blank');\">";
            foreach ($sec_conf->cols as $col)
            {
                $sec_conf->rows .= "<td class='{$col->cls}'>{$visit->{$col->key} }</td>\n";
            }
            $sec_conf->rows .= "<td class='txar'>$menu</td>
			</tr>";

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        if ($count == 0)
        {
            $cs = count($sec_conf->cols) + 1;
            $sec_conf->rows = "<tr class='dt-tr-on'><td colspan=$cs style='text-align:center;'>No Visits found.</td></tr>";
        }

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    /**
     * Supply html for the Workorder section
     *
     * @return string
     */
    public function WorkOrderSection()
    {
        $section = "<div class='flx_cont'>
			<div id='wo_cont' class='box box-primary'>
				<div id='wo_hdr' class='box-header' data-toggle='collapse' data-target='#wo_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>Work Orders</h4>
					<span class='badge' id='wo_sec_badge'></span>
				</div>
				<div id='wo_disp' class='box-body collapse'>
					<div class='on'>Loading...</div>
				</div>
			</div>
		</div>";

        return $section;
    }

    /**
     * Supply table for the Equipment section
     *
     * @return string
     */
    public function WorkOrderTable(&$html, &$count)
    {
        global $this_app_name, $preferences;

        $date_format = $preferences->get('general', 'dateformat');

        $defaults = array('page' => 1, 'sort_by' => "work_order", 'dir' => "ASC", "w_model" => "all", "w_status" => "1");
        SessionHandler::Update('crm', 'wo', $defaults);
        $filter = $_SESSION['crm']['wo'];
        $page = $filter['page'];
        $filter['limit'] = $preferences->get('general', 'results_per_page');
        $filter['offset'] = ($page - 1) * $filter['limit'];

        $sec_conf = new StdClass();
        $sec_conf->rows = "";
        $sec_conf->count = $count;
        $sec_conf->page = $filter['page'];
        $sec_conf->per_page = $filter['limit'];
        $sec_conf->row_count = 0;
        $sec_conf->sort = $filter['sort_by'];
        $sec_conf->dir = $filter['dir'];
        $sec_conf->show_action = 0;
        $sec_conf->disp = 'wo_disp';
        $sec_conf->nav_badge = 'wo_nav_badge';
        $sec_conf->sec_badge = 'wo_sec_badge';
        $iso = ($this->is_office) ? 1 : 0;
        $sec_conf->base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=WorkOrderTable&entry={$this->office_id}&is_office=$iso";
        $sec_conf->col_list_name = "co_wo_cols";
        $sec_conf->cols = unserialize($preferences->get_col($this_app_name, $sec_conf->col_list_name));

        $this->LoadWorkOrders($filter);

        ## Init totals for Lease, Purchase and Loaner
        $rows = "";
        $rc = 'dt-tr-on';

        foreach ($this->wos as $wo)
        {
            $sec_conf->row_count++;
            $tag['text'] = $wo->work_order;
            $tag['alt'] = "View Work Order";
            $tag['href'] = "workorder.php?submit_action=View&work_order={$wo->work_order}";
            $tag['target'] = "_blank";
            $wo->work_order = BaseClass::BuildATags(array($tag));

            # Need to page results useing a window function
            # for true total. This is return in total_records field.
            $sec_conf->count = $wo->total_records;
            $count = $wo->total_records;

            ## Show inactive?
            $ia = ($wo->editable) ? "" : " faded";

            $wo->open_date = date($date_format, $wo->open_date);
            $wo->close_date = ($wo->close_date) ? date($date_format, $wo->close_date) : "";

            $sec_conf->rows .= "<tr class='{$rc}{$ia}' ondblclick=\"window.location='{$tag['href']}';\">";
            foreach ($sec_conf->cols as $col)
            {
                $sec_conf->rows .= "<td class='{$col->cls}'>{$wo->{$col->key} }</td>\n";
            }
            $sec_conf->rows .= "</tr>";

            $rc = ($rc == 'dt-tr-on') ? 'dt-tr-off' : 'dt-tr-on';
        }

        if ($count == 0)
        {
            $cs = count($sec_conf->cols);
            $sec_conf->rows = "<tr class='dt-tr-on'><td colspan=$cs style='text-align:center;'>No Work Orders found.</td></tr>";
        }

        ## define attributes used to query for a page of data
        $base_url = "{$_SERVER['PHP_SELF']}?act=db&obj=section&method=WorkOrderTable&is_office=$iso&entry={$this->office_id}";
        $m_options = Forms::createEquipmentList($filter['w_model'], true);
        $s_options = WorkOrder::createStatusList($filter['w_status']);

        $sec_conf->filter = "<div class='filter nested' align='left'>
			<span class='lbl'>Model:</span>
			<select name='w_model' onchange=\"FillSection('$base_url&w_model='+this.value, '{$sec_conf->disp}', '{$sec_conf->nav_badge}', '{$sec_conf->sec_badge}')\">
				<option value='all'>--All--</option>
				$m_options
			</select>
			<span class='lbl'>Status:</span>
			<select name='w_status' onchange=\"FillSection('$base_url&w_status='+this.value, '{$sec_conf->disp}', '{$sec_conf->nav_badge}', '{$sec_conf->sec_badge}')\">
				<option value='all'>--All--</option>
				$s_options
			</select>
		</div>";

        $html = $this->SectionHTML($sec_conf);
        return $html;
    }

    public static function getCorporateParents($filter_letter = null)
    {
        // Possible filter options
        $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
            'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
            'W', 'X', 'Y', 'Z');
        $filter_clause = '';
        if (!is_null($filter_letter))
        {
            if (in_array($filter_letter, $letters))
            {
                $filter_clause = "AND o.account_id ILIKE '{$filter_letter}%'";
            }
            elseif ($filter_letter == 'Num')
            {
                $filter_clause = "AND (o.account_id LIKE '0%' OR o.account_id LIKE '1%' OR o.account_id LIKE '2%' OR o.account_id LIKE '3%' OR o.account_id LIKE '4%' OR o.account_id LIKE '5%' OR o.account_id LIKE '6%' OR o.account_id LIKE '7%' OR o.account_id LIKE '8%' OR o.account_id LIKE '9%')";
            }
        }

        $sql = <<<SQL
SELECT
    o.account_id,
    o.office_name
FROM corporate_office o
WHERE o.account_id IN (
    SELECT DISTINCT f.corporate_parent
    FROM facilities f
--  WHERE
--      f.accounting_id NOT LIKE '___9%'
--      ND f.accounting_id NOT LIKE '___6%'
)
{$filter_clause}
ORDER BY 1, 2
SQL;

        $dbh = DataStor::getHandle();
        $sth = $dbh->query($sql);
        $corporate_parents = $sth->fetchAll(PDO::FETCH_ASSOC);
        return $corporate_parents;
    }

    /**
     * AR Memo Section
     */
    public function MemoSection()
    {
        $memo = new Memo(0, $this->account_id);
        $memo->is_office = $this->is_office;

        $memo_html = Memo::GetDisplay($memo);

        $section = "<div class='flx_cont'>
			<div id='memo_cont' class='box box-primary'>
				<div id='memo_hdr' class='box-header' data-toggle='collapse' data-target='#memo_disp' style='cursor:pointer;'>
					<i class='fa fa-plus pull-right'></i>
					<h4 class='box-title'>AR Collection</h4>
				</div>
				<div id='memo_disp' class='box-body collapse'>
				$memo_html
				</div>
			</div>
		</div>";

        return $section;
    }
}

?>