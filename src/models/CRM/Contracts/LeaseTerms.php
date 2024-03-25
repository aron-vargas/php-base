<?

/**
 * @package Freedom
 *
 *This file is for  lease terms workflow
 *
 * @author Aron Vargas
 */

class LeaseTerms extends BaseClass {

    protected $db_table = 'lease_terms';	# string
    protected $p_key = 'lease_id';		# string

    protected $pricing_id;					# int
    protected $workflow_id;					# int
    protected $cp;							# str
    protected $cpId;						# int
    protected $lease_id;					# int
    protected $prod_id;						# int
    protected $product;						# str
    protected $price;						# double
    protected $shipping_cost;				# double

    protected $description;					# str

    protected $details;						# array

    protected $errorStr;					# str


    /**
     * Create new Lease Terms instance
     *
     * @param integer
     */
    public function __construct($workflow_id)
    {
        $this->dbh = DataStor::getHandle();

        if ($workflow_id)
            $this->workflow_id = $workflow_id;

        $this->load();
    }


    /**
     * Load details from database
     *
     * @return string
     */
    public function load()
    {

        if ($this->workflow_id)
        {
            $sth = $this->dbh->prepare("SELECT
				lt.terms_id,
				lt.work_flow_id,
				lt.corporate_parent,lt.parent_id,lt.lease_id,lt.master_agreement,
				lt.lease_amount,	lt.shipping_price,
				lt.starter_kit_price,lt.length_term_id,lt.visit_frequency,lt.non_cancellable,lt.termination
			FROM lease_terms lt
			WHERE lt.work_flow_id = ?");
            $sth->bindValue(1, $this->workflow_id, PDO::PARAM_INT);
            $sth->execute();
            $result = $this->copyFromArray($sth->fetchAll(PDO::FETCH_ASSOC));

            $this->details = $result;





        }


    }


    public function loadDetails($form)
    {
        if (isset ($form['wf_id']))
            $this->workflow_id = $form['wf_id'];


        if ($this->workflow_id)
        {
            // 			$sth = $this->dbh->prepare("SELECT	 lt.terms_id ,  lt.corporate_parent ,  lt.parent_id ,  lt.lease_id ,
// 										 lt.master_agreement ,  lt.lease_amount ,  lt.shipping_price, lt.starter_kit_price ,
// 										 lt.length_term_id ,  lt.visit_frequency ,  lt.non_cancellable,  lt.termination ,  lt.comments
// 						FROM lease_terms lt
// 						WHERE lt.work_flow_id = ?");
// 						$sth->bindValue(1, $this->workflow_id, PDO::PARAM_INT);
// 						$sth->execute();




            $sth = $this->dbh->prepare("SELECT	 lt.terms_id ,  lt.corporate_parent ,  lt.parent_id ,  lt.lease_id ,
										 lt.master_agreement ,  lt.lease_amount ,  lt.shipping_price, lt.starter_kit_price ,
										 lt.length_term_id ,  lt.visit_frequency ,  lt.non_cancellable,  lt.termination ,  lt.comments
								,w.description
FROM lease_terms lt	join work_flow w on w.id=lt.work_flow_id	WHERE lt.work_flow_id = ?");
            $sth->bindValue(1, $this->workflow_id, PDO::PARAM_INT);
            $sth->execute();


            $all_records = $sth->fetchAll(PDO::FETCH_ASSOC);
            $this->copyFromArray($all_records);
            $this->details = $all_records;
            $this->description = $this->details[0]['description'];

        }


    }



    /**
     * Copy array values to object attributes
     *
     * @param $form array
     */
    public function copyFromArray($form)
    {
        BaseClass::copyFromArray($form);
    }



    /**
     * Return form buttons for saving the form
     *
     * @return string
     */
    public function getSaveButtons()
    {
        $save = " <input type='button' class='submit' name='save' value='Save' onClick='SaveForm(document.leaseterms);'/>";

        //return $save;
    }


    /**
     * Show as view only form
     *
     * @param array
     *
     * @return string
     */
    public function ViewForm($form)
    {
        echo $this->showUploadForm(1, $form);
    }

    /**
     * Show editable html form
     *
     * @param array
     *
     * @return string
     */

    public function edit_terms($form)
    {

        if (isset ($form['wf_id']))
        {


            $this->loadDetails($form);

            if (!empty ($this->details))
            {

                echo $this->showUploadForm('hide');

                echo $this->showEditForm($form);

            }
            else
            {

                echo $this->showUploadForm();
            }


        }
        else
        {

            echo $this->showUploadForm();
        }

    }

    public function uploadPrice($form)
    {
        echo $this->save();
    }

    /**
     * Get required Javascript tags for the form controls
     *
     * @param array
     * @return array
     */
    public function getJS(&$js)
    {
        $path = Config::$WEB_PATH;
        $js[] = $path . '/js/yui/yahoo-dom-event/yahoo-dom-event.js';
        $js[] = $path . '/js/yui/element/element-min.js';
        $js[] = $path . '/js/yui/dragdrop/dragdrop-min.js';
        $js[] = $path . '/js/yui/button/button-min.js';
        $js[] = $path . '/js/yui/container/container.js';
        $js[] = $path . '/js/yui/connection/connection-min.js';
        $js[] = $path . '/js/yui/datasource/datasource-min.js';
        $js[] = $path . '/js/yui/autocomplete/autocomplete-min.js';
        $js[] = $path . '/js/popcal/calendar.js';
        $js[] = $path . '/js/popcal/calendar-en.js';
        $js[] = $path . '/js/popcal/calendar-setup.js';
        $js[] = $path . '/js/util/date_time.js';
        $js[] = $path . '/js/item_search.js';
        $js[] = $path . '/js/ajax/xmlhttprequest.js';
        $js[] = $path . '/js/yui/json/json-min.js';
        $js[] = $path . '/js/lease_terms.js';
    }


    public function save($processeData)
    {
        global $user;

        $dbh = DataStor::getHandle();
        $comments = "Test comments";

        $wf = isset ($processeData[0]['WorkFlow']) ? $processeData[0]['WorkFlow'] : null;

        if ($wf != null)
        {

            $delSql = "delete from  lease_terms where work_flow_id=" . $wf;
            $dbh->exec($delSql);

        }


        foreach ($processeData as $key => $value)
        {
            $i = 0;

            $cp = isset ($value['Parent']) ? trim($value['Parent']) : null;
            $leaseId = isset ($value['LeaseId']) ? trim($value['LeaseId']) : null;
            $cpId = isset ($value['parentId']) ? trim($value['parentId']) : null;
            $leaseType = isset ($value['LeaseType']) ? trim($value['LeaseType']) : null;
            $leaseamt = isset ($value['leaseamt']) ? trim($value['leaseamt']) : null;
            $shiprate = isset ($value['Shipping']) ? trim($value['Shipping']) : null;

            $supplies = isset ($value['supplies']) ? trim($value['supplies']) : null;
            $term = isset ($value['term']) ? trim($value['term']) : null;
            $visit = isset ($value['visit']) ? trim($value['visit']) : null;
            $cancellable = isset ($value['cancellable']) ? trim($value['cancellable']) : null;
            $termination = isset ($value['termination']) ? trim($value['termination']) : null;



            $insertSql = "insert into  lease_terms
							( work_flow_id ,  corporate_parent ,  parent_id ,  lease_id ,  master_agreement ,  lease_amount ,  shipping_price ,

							  starter_kit_price ,  length_term_id ,  visit_frequency ,  non_cancellable,  termination ,  comments )

								values(	? , ?, ? , ?, ? , ?, ? , ? , ?, ?, ?, ?, ?)";

            $comments = 'test comments';


            $sth = $dbh->prepare($insertSql);

            try
            {
                $sth->bindValue(++$i, $wf, PDO::PARAM_INT);

                $sth->bindValue(++$i, $cp, PDO::PARAM_STR);

                $sth->bindValue(++$i, $cpId, PDO::PARAM_INT);

                $sth->bindValue(++$i, $leaseId, PDO::PARAM_INT);

                $sth->bindValue(++$i, $leaseType, PDO::PARAM_STR);

                $sth->bindValue(++$i, $leaseamt, PDO::PARAM_STR);


                $sth->bindValue(++$i, $shiprate, PDO::PARAM_STR);

                $sth->bindValue(++$i, $supplies, PDO::PARAM_STR);

                $sth->bindValue(++$i, $term, PDO::PARAM_INT);

                $sth->bindValue(++$i, $visit, PDO::PARAM_INT);

                $sth->bindValue(++$i, $cancellable, PDO::PARAM_STR);

                $sth->bindValue(++$i, $termination, PDO::PARAM_INT);

                $sth->bindValue(++$i, $comments, PDO::PARAM_STR);


                $sth->execute();

            }
            catch (PDOException $pdo_exc)
            {
                ErrorHandler::showError('A database error has occurred while inserting the values to lease terms table.', $pdo_exc->getMessage() . "\n" . $pdo_exc->getTraceAsString(), ErrorHandler::$BOTH, $user);
                exit;
            }
        }

        $this->workflow_id = $wf;

    }



    /**
     * Do Final step in work flow process
     * Move term details to lease_agreement
     * @param array
     */
    public function complete($data)
    {
        global $user, $wfStatus;


        $dbh = DataStor::getHandle();

        $this->status_txt = "Complete";

        // 		// Detect (Refresh/F5)
// 		if ($this->isComplete())
// 			return;



        $stage_act = (isset ($data['stage_act'])) ? strtolower($data['stage_act']) : '';

        # Create Contract when approved
        if ($stage_act == 'approve')
        {
            # Save Contract
            $contract_id = $this->moveTerms($data);

            $wfStatus = 'complete';

        }
        else
        {
            // do nothing  .  work flow will change status!
        }

    }

    public function moveTerms($form)
    {

        $wf = $form['wf_id'];

        $sql = 'select * from lease_terms where work_flow_id= ? order by corporate_parent';
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare($sql);
        $sth->bindValue(1, $wf, PDO::PARAM_INT);
        $sth->execute();
        $result = $sth->fetchAll();
        $cpflag = '';
        $leaseFlg = 0;

        foreach ($result as $key => $value)
        {

            $lease_id = isset ($value['lease_id']) ? $value['lease_id'] : null;

            $cp = isset ($value['corporate_parent']) ? $value['corporate_parent'] : null;

            $parent_id = isset ($value['parent_id']) ? $value['parent_id'] : null;

            $master = isset ($value['master_agreement']) ? $value['master_agreement'] : null;

            $price = isset ($value['lease_amount']) ? $value['lease_amount'] : null;

            $ship = isset ($value['shipping_price']) ? $value['shipping_price'] : null;

            $supply = isset ($value['starter_kit_price']) ? $value['starter_kit_price'] : null;

            $term = isset ($value['length_term_id']) ? $value['length_term_id'] : null;

            if (isset ($value['visit_frequency']) && ($value['visit_frequency'] >= 0))
                $visit = $value['visit_frequency'];
            else
                $visit = null;

            $cancel = isset ($value['non_cancellable']) ? $value['non_cancellable'] : null;

            $termination = isset ($value['termination']) ? $value['termination'] : null;


            if ($lease_id != 0)
            {
                // 				$checkLease="Select price,shipping_cost,prod_id  from lease_addon_pricing where lease_id=$lease_id";
// 				$sth= $dbh->query( $checkLease);
// 				$rows = $sth->fetch(PDO::FETCH_ASSOC);

                $updatesql = "update lease_agreement ";
                if (isset ($cp))
                    $updatesql .= "set cust_id='" . $cp . "'";

                if ($price != null)
                    $updatesql .= " , lease_amount=" . $price;

                if ($ship != null)
                    $updatesql .= ",shipping_price=" . $ship;

                if ($master != null)
                {
                    if ($master == 'M')
                        $updatesql .= "  ,master_agreement=true";
                    if ($master == 'I')
                        $updatesql .= "  ,master_agreement=false";

                }

                if ($supply != null)
                    $updatesql .= " , starter_kit_price=" . $supply;


                if ($term != null)
                    $updatesql .= ",length_term_id=" . $term;


                if ($visit >= 0)
                {
                    $updatesql .= ",visit_frequency=" . $visit;
                }


                if ($cancel != null)
                {

                    if ($cancel == 'Y')
                        $updatesql .= "  ,non_cancellable=false";								//insert opposite values as db have "non_cancellable" field !!
                    if ($cancel == 'N')
                        $updatesql .= "  ,non_cancellable=true";

                }


                if ($termination != null)
                {
                    $termination = $termination . " Days";
                    $updatesql .= ",termination='" . $termination . "'";
                }

                $updatesql .= " where lease_id=" . $lease_id;

                $sth = $dbh->query($updatesql);

            }
            else
            {

                //create  lease if there is no lease. only one lease for one CP
                $cpflag = $cp;

                $insertLease = "insert into lease_agreement(cust_id) values('" . $cp . "')";
                $sth = $dbh->query($insertLease);
                $lease_id = $dbh->lastInsertId('lease_agreement_lease_id_seq');



                if ($lease_id != null)
                {

                    $updatesql = "update lease_agreement ";
                    if (isset ($cp))
                        $updatesql .= "set cust_id='" . $cp . "'";

                    if ($price != null)
                        $updatesql .= " , lease_amount=" . $price;

                    if ($ship != null)
                        $updatesql .= ",shipping_price=" . $ship;

                    if ($master != null)
                    {
                        if ($master == 'M')
                            $updatesql .= "  ,master_agreement=true";
                        if ($master == 'I')
                            $updatesql .= "  ,master_agreement=false";

                    }

                    if ($supply != null)
                        $updatesql .= " , starter_kit_price=" . $supply;


                    if ($term != null)
                        $updatesql .= ",length_term_id=" . $term;


                    if ($visit >= 0)
                    {
                        $updatesql .= ",visit_frequency=" . $visit;
                    }


                    if ($cancel != null)
                    {

                        if ($cancel == 'Y')
                            $updatesql .= "  ,non_cancellable=false";								//insert opposite values as db have "non_cancellable" field !!
                        if ($cancel == 'N')
                            $updatesql .= "  ,non_cancellable=true";

                    }


                    if ($termination != null)
                    {
                        $termination = $termination . " Days";
                        $updatesql .= ",termination='" . $termination . "'";
                    }

                    $updatesql .= " where lease_id=" . $lease_id;

                    $sth = $dbh->query($updatesql);






                }

            }


        }

        return true;
    }


    public function showUploadForm($show = null)
    {
        global $errorStr;
        $reupload = '';

        if ($show == null)
            echo $errorStr;

        if ($show == "hide")
        {

            $reupload = '<table  cellpadding="5" cellspacing="2">
			<tr align="right">
				<td colspan="4" align="right"><a onclick="javascript:showhide();">Re Upload</td>
			</tr>
		</table>';

        }


        echo <<<END


	<form enctype="multipart/form-data"  action="{$_SERVER['PHP_SELF']}"  name="leaseterms" onSubmit="return validateUpload(this);" method="post">
				{$reupload}
		<table class='form' id="uptable" name="uptable"  cellpadding="5" cellspacing="2">
			<tr>
				<th class="subheader" colspan="4"> Lease Terms</th>
			</tr>
			<tr>
				<th class="form" colspan="4">
					Please Specify the deafult pricing list in CSV(comma separated) format. <a href="SampleTerms.csv">Sample File</a>
				</th>
			</tr>
			<tr>
				<th class="form" colspan="1">
					Filename:<span style='color:red' >*</span>
				</th>
				<th class="form" colspan="3">
					<input type="file" name="datafile" size="40">
				</th>
			</tr>
			<tr>
				<th class="form" colspan="4">
					<input type="submit" name="upload" value="Upload File">
				</th>
			</tr>

						<tr>
						<th class="form" colspan="2">
Name/Description:<span style='color:red' >*</span>				</th>
				<th class="form" colspan="2">
					<input type="text" name="description" id="description" value="{$this->description}">
				</th>
			</tr>

			<tr>
				<td style="text-align:left" colspan="4">
					<b>	Note:</b> <br><br>
						Values taken from CSV file: Corporate parent, Lease Type,Visit Frequency ,	Cancellable , Termination 	<br><br>
						Lease Amount,Install Delivery Charge,Start Up Supplies,Term Length<br><br>
			 Corporate parent   cannot be blank.Any  blank values will not be updated . <br><br>
			Use zero instead of blank for updating .
				</td>
			</tr>
		</table>
END;

        if ($show == "hide")
        {

            echo <<<END
	<script>

			showhide();
				</script>

</form>

END;


        }

        echo <<<END


</form>

						<script>

 var drow	=	document.getElementById('datarow');

  var req		=	document.getElementById('request_approval');
      if(req!=undefined || req!=null)
      {
     	  if(drow==undefined || drow==null)
       	   req.style.display = 'none';
        }

  var close	=	document.getElementById('close');
      if(close!=undefined || close!=null)
          close.style.display = 'none';

    var savebtn	=	document.getElementById('save');
      if(savebtn!=undefined || savebtn!=null)
          savebtn.style.display = 'none';


</script>

END;

    }


    public function showEditForm($form)
    {
        global $errorStr, $wfStatus, $user;

        $dbh = DataStor::getHandle();

        if ($wfStatus == 'complete')
        {
            $wfId = $form['wf_id'];
            $completed_by = $user->getId();

            $completeWf = "update work_flow set complete=true,completed_by=" . $completed_by . ",completed_date=now() where id=" . $wfId;

            $sth = $dbh->query($completeWf);
            $sth->execute();

        }

        echo $errorStr;

        if (isset ($form['wf_id']))
        {
            $this->loadDetails($form);
        }

        echo <<<END

	<form   action="{$_SERVER['PHP_SELF']}" name="leaseterms" onSubmit="return validateUpload(this);" method="post">


		<table class="list" cellpadding="5" cellspacing="2">
			<tr>
				<th class="subheader" colspan="10"> Lease Terms</th>
			</tr>
			<tr>
				<th class="form" >
					SL#
				</th>
				<th class="form" >
					Corporate Parent
				</th>
				<th class="form" >
					Lease Type
				</th>
				<th class="form" >
					Visit Frequency
					</th>
				<th class="form" >
					Cancellable
					</th>
			<th class="form" >
					Termination
					</th>
			<th class="form" >
					Lease Amount
				</th>
				<th class="form" >
					Install Delivery Charge
				</th>
				<th class="form" >
					Start Up Supplies
					</th>
				<th class="form" >
						Term Length
					</th>
			</tr>

END;
        $i = 1;
        foreach ($this->details as $key => $value)
        {
            $class = 'on';
            $haslease = 'N';
            if (($i % 2) == 0)
                $class = 'off';

            //if(isset($value['lease_id']))$haslease='Y';

            echo "<tr  id='datarow'  class=$class><td style='text-align:left'>$i</td><td style='text-align:left'>" . $value['corporate_parent'] . "</td>
					<td style='text-align:left'>" . $value['master_agreement'] . "</td>
					<td style='text-align:left'>" . $value['visit_frequency'] . "</td>
					<td style='text-align:left'>" . $value['non_cancellable'] . "</td>
					<td style='text-align:left'>" . $value['termination'] . "</td>
					<td style='text-align:left'>" . $value['lease_amount'] . "</td>
					<td style='text-align:left'>" . $value['shipping_price'] . "</td>
					<td style='text-align:left'>" . $value['starter_kit_price'] . "</td>
					<td style='text-align:left'>" . $value['length_term_id'] . "</td>
					</tr>";
            $i++;

        }


        echo <<<END

			<tr>
				<td style="text-align:left" colspan="4">

				</td>
			</tr>
		</table>
</form>
<script>

	var close	=	document.getElementById('close');
 		if(	(close!=undefined) || 	(close!=null))
        close.style.display = 'none';

         var savebtn	=	document.getElementById('save');
         if(savebtn!=undefined || savebtn!=null)
         savebtn.style.display = 'none';


	var drow	=	document.getElementById('datarow');

  	var req		=	document.getElementById('request_approval');
      if(req!=undefined || req!=null)
				{
				if(drow==undefined || drow==null)
			          req.style.display = 'none';
							else
							req.style.display = '';
				}


</script>




END;
    }

    public function requestApproval($wf_id, $stage_id)
    {
        //echo $wf_id.",".$stage_id;


    }

    public function upload_terms($filename = null)
    {

        global $dbh, $errorStr;


        $act = isset ($filename['act']) ? $filename['act'] : null;
        $stage_act = isset ($filename['stage_act']) ? $filename['stage_act'] : null;


        if ($act == 'revert')
            return true;

        if ($stage_act == 'close')
            return true;


        if ($stage_act == 'req_approval')
        {
            $wf = $filename['wf_id'];
            $stage_id = $filename['active_stage_id'];

            $this->requestApproval($wf, $stage_act);
        }
        else if (($stage_act == 'approve') || ($stage_act == 'deny'))
        {
            $this->complete($filename);
        }
        else
        {

            $wfId = $filename['wf_id'];

            $tmpName = $_FILES['datafile']['tmp_name'];

            $returnVal = LeaseTerms::parseData($tmpName);

            $description = ucwords($filename['description']);

            $descSql = "update work_flow set description='" . $description . "' where id=" . $wfId;

            $dbh->query($descSql);


            if (!empty ($returnVal))
            {

                $corporateId = $dbh->prepare("SELECT office_id, account_id FROM corporate_office  WHERE  account_id=?  ");

                $leaseId = $dbh->prepare("SELECT lease_id FROM lease_agreement  WHERE  cust_id=?  ");


                $clean_parse = true;
                $processed_rows = array();
                $tmpI = 1;


                foreach ($returnVal as $lease)
                {
                    $valid_data = true;
                    $invalidEntryStr = '';

                    $corporateParent = null;
                    $leaseAmount = null;
                    $shipping = null;
                    $supplies = null;
                    $termLength = null;
                    $visit = null;
                    $cancellable = null;
                    $termination = null;
                    $leaseType = null;


                    if (isset ($lease))
                    {
                        $fieldcount = count($lease);

                        if ($fieldcount == 9)
                            list($corporateParent, $leaseType, $visit, $cancellable, $termination, $leaseAmount, $shipping, $supplies, $termLength) = $lease;
                        else if ($fieldcount == 5)
                            list($corporateParent, $leaseType, $visit, $cancellable, $termination) = $lease;
                        else
                            $invalidEntryStr .= " Invalid number of columns.";


                        if (isset ($corporateParent) && trim($corporateParent) != null)
                        {
                            $corporateParent = strtoupper(trim($corporateParent));
                            $leaseAmount = (isset ($leaseAmount) && trim($leaseAmount) != null) ? trim($leaseAmount) : NULL;
                            $shipping = (isset ($shipping) && trim($shipping) != null) ? trim($shipping) : NULL;
                            $supplies = (isset ($supplies) && trim($supplies) != null) ? trim($supplies) : NULL;
                            $termLength = (isset ($termLength) && trim($termLength) != null) ? trim($termLength) : NULL;
                            $visit = (isset ($visit) && trim($visit) != null) ? trim($visit) : NULL;
                            $cancellable = (isset ($cancellable) && trim($cancellable) != null) ? trim($cancellable) : NULL;
                            $termination = (isset ($termination) && trim($termination) != null) ? trim($termination) : NULL;
                            $leaseType = (isset ($leaseType) && trim($leaseType) != null) ? trim($leaseType) : NULL;

                            $corporateId->bindValue(1, $corporateParent, PDO::PARAM_STR);

                            try
                            {
                                //  Corporate Parent check
                                $corporateId->execute();

                                if ($corporateId->rowCount() <= 0)
                                {
                                    $valid_data = false;
                                    $invalidEntryStr .= " Corporate Parent: {$corporateParent} ";
                                }
                                else
                                {
                                    $corporateParent = trim((string) $corporateParent);
                                    $row = $corporateId->fetch();
                                    $corporate_Id = $row['office_id'];
                                }
                            }
                            catch (PDOException $pdo_exc)
                            {
                                ErrorHandler::showError('A database error has occurred while trying to get the Corporate information.', $pdo_exc->getMessage() . "\n" . $pdo_exc->getTraceAsString(), ErrorHandler::$BOTH, $user);
                                exit;
                            }
                            catch (Exception $_exc)
                            {
                                echo '<p class="error" style="width:550px;margin-left:auto;margin-right:auto">Corporate Parent does not exist: ' . $corporateParent . '.</p>';
                                $valid_data = false;
                            }


                            $leaseId->bindValue(1, $corporateParent, PDO::PARAM_STR);

                            try
                            {
                                // Get the Lease Id if exists
                                $leaseId->execute();
                                $lease_Id = $leaseId->fetchColumn();


                                if ($lease_Id)
                                    $Lease_Id = $lease_Id;
                                else
                                    $Lease_Id = 0;

                            }
                            catch (PDOException $pdo_exc)
                            {
                                ErrorHandler::showError('A database error has occurred while trying to get the Lease Id.', $pdo_exc->getMessage() . "\n" . $pdo_exc->getTraceAsString(), ErrorHandler::$BOTH, $user);
                                exit;
                            }
                            catch (Exception $_exc)
                            {
                                echo '<p class="error" style="width:550px;margin-left:auto;margin-right:auto">Lease agreement  does not exist: ' . $corporateParent . '.</p>';
                                $valid_data = false;
                            }



                            if ($leaseType != null)
                            {

                                if (($leaseType != 'M') && ($leaseType != 'I'))
                                {

                                    $invalidEntryStr .= "Invalid Lease Type  - ";
                                    $valid_data = false;
                                }

                            }



                            if (isset ($leaseAmount) && ($leaseAmount != null))
                            {

                                if ((!is_numeric($leaseAmount)) && (!is_float($leaseAmount)))
                                {

                                    $invalidEntryStr .= "Invalid Lease Amount  - ";
                                    $valid_data = false;
                                }
                                else if ($leaseAmount < 0)
                                {
                                    $invalidEntryStr .= "Invalid Price  - ";
                                    $valid_data = false;
                                }


                            }

                            if (isset ($supplies) && ($supplies != null))
                            {

                                if ((!is_numeric($supplies)) && (!is_float($supplies)))
                                {

                                    $invalidEntryStr .= "Invalid supplies cost  - ";
                                    $valid_data = false;
                                }
                                else if ($supplies < 0)
                                {
                                    $invalidEntryStr .= "Invalid supplies cost   - ";
                                    $valid_data = false;
                                }


                            }


                            if (isset ($shipping) && ($shipping != null))
                            {

                                if ((!is_numeric($shipping)) && (!is_float($shipping)))
                                {

                                    $invalidEntryStr .= "Invalid Shipping Cost  - ";
                                    $valid_data = false;
                                }
                                else if ($shipping < 0)
                                {
                                    $invalidEntryStr .= "Invalid Shipping Cost   - ";
                                    $valid_data = false;
                                }

                            }



                            if (isset ($termLength) && ($termLength != null))
                            {

                                if ((!is_numeric($termLength)))
                                {
                                    $invalidEntryStr .= "Invalid term length  - ";
                                    $valid_data = false;
                                }
                                else if ($termLength < 0 || $termLength > 10)
                                {
                                    $invalidEntryStr .= "Invalid term length   - ";
                                    $valid_data = false;
                                }

                            }


                            if ($visit != null)
                            {

                                if ((!is_numeric($visit)))
                                {

                                    $invalidEntryStr .= "Invalid Visit  Frequency  - ";
                                    $valid_data = false;
                                }
                                else if (($visit < 0) || ($visit > 8))
                                {
                                    $invalidEntryStr .= "Invalid Visit Frequency    - ";
                                    $valid_data = false;
                                }

                            }

                            if ($cancellable != null)
                            {

                                if (($cancellable != 'Y') && ($cancellable != 'N'))
                                {

                                    $invalidEntryStr .= "Invalid Cancellable value  - ";
                                    $valid_data = false;
                                }

                            }



                            if ($termination != null)
                            {

                                if ((!is_numeric($termination)))
                                {

                                    $invalidEntryStr .= "Invalid Termination  value  - ";
                                    $valid_data = false;
                                }
                                else if (($termination != 30) && ($termination != 60))
                                {
                                    $invalidEntryStr .= "Invalid Termination  value - ";
                                    $valid_data = false;
                                }

                            }


                            //echo $valid_data;



                            if ($valid_data == true)
                            {

                                //$corporateParent,$leaseType,$leaseAmount,$shipping,$supplies,$termLength,$visit,$cancellable,$termination

                                if ($fieldcount == 9)
                                {
                                    $lease_row = array('WorkFlow' => $wfId, 'Parent' => $corporateParent,
                                        'LeaseType' => $leaseType,
                                        'LeaseId' => $Lease_Id,
                                        'parentId' => $corporate_Id,
                                        'leaseamt' => $leaseAmount, 'Shipping' => $shipping,
                                        'supplies' => $supplies, 'term' => $termLength,
                                        'visit' => $visit, 'cancellable' => $cancellable,
                                        'termination' => $termination
                                    );

                                    $processed_rows[] = $lease_row;

                                }
                                else if ($fieldcount == 5)
                                {

                                    $lease_row = array('WorkFlow' => $wfId, 'Parent' => $corporateParent,
                                        'LeaseType' => $leaseType,
                                        'LeaseId' => $Lease_Id,
                                        'visit' => $visit, 'cancellable' => $cancellable,
                                        'termination' => $termination
                                    );

                                    $processed_rows[] = $lease_row;
                                }

                            }
                        }


                    }

                    if ($invalidEntryStr != '')
                        $errorStr .= "Invalid Entry: {$tmpI} - {$invalidEntryStr}<br>";

                    ++$tmpI;
                }


                if ($clean_parse)
                {

                    //	print_r($processed_rows);
                    //	exit;

                    LeaseTerms::save($processed_rows);

                    //return $processed_rows;


                }
                else
                    return array();
            }


            return $errorStr;
        }
    }


    /**
     * Return email body
     */
    public function GetEmailDetail($wf)
    {


        $email_body = "	Task : Lease Terms Approval";
        $email_body .= "\n	Please verify the lease terms  uploaded.\n";

        return $email_body;
    }

    /**
     * Return email addresses
     */
    public function GetEmailRecipients($stage_act)
    {
        $recipients = array();
        /*if ($stage_act == 'approve')
              {
                  # Add facility cpm, rdo, dvp, and coo
                  if ($this->facility_cpm)
                  {
                      $sth = $this->dbh->prepare("SELECT u.email, rdo.email, dvp.email, rds.email, dds.email
                      FROM users u
                      INNER JOIN v_users_primary_group upg ON u.id = upg.user_id
                      INNER JOIN users g on upg.group_id = g.id
                      LEFT JOIN (
                          SELECT u.email, g.group_id, u.supervisor_id
                          FROM users u
                          INNER JOIN v_users_primary_group g ON u.id = g.user_id AND g.role_id = 600
                          WHERE u.active = true
                      ) rdo ON upg.group_id = rdo.group_id
                      LEFT JOIN users dvp ON rdo.supervisor_id = dvp.id
                      LEFT JOIN (
                          SELECT u.email, g.group_id, u.supervisor_id
                          FROM users u
                          INNER JOIN v_users_primary_group g ON u.id = g.user_id AND g.role_id = 500
                          WHERE u.active = true
                      ) rds ON upg.group_id = rds.group_id
                      LEFT JOIN users dds ON rds.supervisor_id = dds.id
                      WHERE u.id = ?");
                      $sth->execute(array((int)$this->facility_cpm));
                      if (list($cpm,$rdo,$dvp,$rds,$dds) =  $sth->fetch(PDO::FETCH_NUM))
                      {
                          if ($cpm) $recipients[] = $cpm;
                          if ($rdo) $recipients[] = $rdo;
                          if ($dvp) $recipients[] = $dvp;
                          if ($rds) $recipients[] = $rds;
                          if ($dds) $recipients[] = $dds;
                      }

                      $sth = $this->dbh->query("SELECT u.email FROM users u
                      INNER JOIN v_users_primary_group upg ON u.id = upg.user_id AND upg.role_id = 300
                      WHERE u.active = true
                      AND u.id <> 298 -- No emails to Mark Ritchards");
                      while (list($coo) = $sth->fetch(PDO::FETCH_NUM))
                          $recipients[] = $coo;
                  }
              }*/

        return $recipients;
    }


    public static function parseData($filename)
    {

        $file = fopen($filename, 'r') or die ("Can't open file");

        $fileData = array();
        $lineNum = 0;

        while (!feof($file))
        {
            $data = fgetcsv($file);

            if (isset ($data[0]))
                $fileData = array_merge($fileData, array($data));
        }

        fclose($file);

        return $fileData;
    }
}

?>