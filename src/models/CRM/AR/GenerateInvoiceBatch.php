<?php
/**
 * @package Freedom
 */
require_once ('Invoice.php');
/**
 * This class represents an  invoice batch which is just a container
 * for invoices and credit memos.  Batches are copied to
 * where they are processed for billing.
 *
 * @author Aron Vargas
 * @package Freedom
 */
class GenerateInvoiceBatch {
    /**
     * The database ID
     *
     * @var int
     */
    protected $id = null;

    /**
     * The type of the batch which will be something like "stub", "full",
     * "loaner", or "shipping". This is the same as the invoice type.
     *
     * @var string
     */
    protected $type = '';

    /**
     * The timestamp of when this batch was created
     *
     * @var DateTime
     */
    protected $time_created = null;

    /**
     * The invoice date
     *
     * @var DateTime
     */
    protected $invoice_date = null;

    /**
     * The timestamp of the last change to $post_status
     *
     * @var DateTime
     */
    protected $time_posted = null;

    /**
     * Status code for the batch
     *
     * 0 = Not posted
     * 1 = Post in progress
     * 2 = Posted
     * 3 = Committed
     * 10 = Error
     *
     * @var int
     */
    protected $post_status = null;

    /**
     * The TranType:
     *  501 = invoice
     *  502 = credit
     *
     * @var int
     */
    protected $tran_type = null;

    /**
     * Array of {@link Invoice} ids
     *
     * @var array of ints
     */
    protected $invoice_ids = array();

    /**
     * @var int
     */
    public static $STATUS_NEW = 0;

    /**
     * @var int
     */
    public static $STATUS_IN_PROGRESS = 1;

    /**
     * @var int
     */
    public static $STATUS_POSTED = 2;

    /**
     * @var int
     */
    public static $STATUS_COMMITTED = 3;

    /**
     * @var int
     */
    public static $STATUS_ERROR = 10;


    /**
     * Constructor
     *
     * Takes an invoice ID and loads the database record into this object
     *
     * @param int $batch_id
     * @throws PDOException, Exception
     */
    public function __construct($batch_id)
    {
        $this->id = $batch_id;
        $this->load();
    }

    /**
     * Adds an invoice to this batch. Batch must be saved after calling this
     * to save the invoice count to the database.
     *
     * @param Invoice $invoice
     * @throws Exception
     */
    public function addInvoice($invoice)
    {
        if ($invoice instanceof Invoice)
            $this->invoice_ids[] = $invoice->getId();
        else
            throw new Exception('cannot add invoice because it is not an Invoice');
    }

    /**
     * Deletes this batch (and all invoices contained in it) from the database
     *
     * @throws PDOException, Exception
     */
    public function delete()
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('DELETE FROM invoice_batch WHERE id = ?');
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        if (!$sth->execute())
        {
            $err_info = $sth->errorInfo();
            throw new Exception('could not delete invoice_batch record: ' . $err_info[2]);
        }
    }

    /**
     * Returns the ID of this batch
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the invoice date for the invoices in this batch (they
     * should all have the same date)
     *
     * @return int epoch time
     */
    public function getInvoiceDate()
    {
        if ($this->invoice_date)
            return $this->invoice_date->getTimestamp();

        return null;
    }

    /**
     * Allows a function that is passed in to iterate over the invoice
     * records in this batch. Useful for listing invoices in different
     * formats (each format generator can pass in its own formatting
     * function) without storing text in a (potentially huge) string.
     *
     * @param Closure $func
     */
    public function processInvoices($func)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('
			SELECT * FROM invoice_generated WHERE batch_id = ? ORDER BY cust_id');
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->execute();
        $func($sth);
    }

    /**
     * Returns an array of {@link Invoice}s
     *
     * @return array
     */
    public function getInvoices()
    {
        $invoices = array();

        foreach ($this->invoice_ids as $invoice_id)
        {
            $invoices[] = new Invoice($invoice_id);
        }

        return $invoices;
    }

    /**
     * Returns the number of invoices in this batch
     *
     * @return int
     */
    public function getNumInvoices()
    {
        return count($this->invoice_ids);
    }

    /**
     * Returns the status code for the batch
     *
     * 0 = Not posted
     * 1 = Post in progress
     * 2 = Posted
     * 3 = Committed
     * 10 = Error
     *
     * @return int
     */
    public function getPostStatus()
    {
        return $this->post_status;
    }

    /**
     * Returns the timestamp of the batch creation time
     *
     * @return int epoch time
     */
    public function getTimeCreated()
    {
        if ($this->time_created)
            return $this->time_created->getTimestamp();

        return null;
    }

    /**
     * Returns the timestamp of the last change to the post status
     *
     * @return int epoch time
     */
    public function getTimePosted()
    {
        if ($this->time_posted)
            return $this->time_posted->getTimestamp();

        return null;
    }

    /**
     * Returns the TranType for this batch
     *
     * @return int
     */
    public function getTranType()
    {
        return $this->tran_type;
    }

    /**
     * Returns the type of invoices in this batch
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Loads the database record into this object
     *
     * @throws PDOException, Exception
     */
    protected function load()
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('
			SELECT tstamp,
			       type,
			       invoice_date,
			       posted_tstamp,
			       posted,
			       tran_type
			FROM invoice_batch
			WHERE id = ?');
        $sth->bindValue(1, $this->id, PDO::PARAM_INT);
        $sth->execute();
        if ($batch_row = $sth->fetch(PDO::FETCH_ASSOC))
        {
            $this->time_created = new DateTime($batch_row['tstamp']);
            $this->type = $batch_row['type'];
            $this->invoice_date = new DateTime($batch_row['invoice_date']);
            $this->time_posted = (is_null($batch_row['posted_tstamp'])) ? null : new DateTime($batch_row['posted_tstamp']);
            $this->post_status = $batch_row['posted'];
            $this->tran_type = $batch_row['tran_type'];

            $sth_inv = $dbh->prepare('
				SELECT id FROM invoice_generated WHERE batch_id = ?');
            $sth_inv->bindValue(1, $this->id, PDO::PARAM_INT);
            $sth_inv->execute();
            while (list($inv_id) = $sth_inv->fetch(PDO::FETCH_NUM))
            {
                $this->invoice_ids[] = $inv_id;
            }
        }
        else
        {
            throw new Exception('no invoice_batch record with id ' . $this->id);
        }
    }

    /**
     * Copies this batch's invoices then calls a function to
     * run invoice creation stored procedure
     *
     * @param boolean $force_commit
     * @return int the number of invoices posted
     * @throws PDOException, Exception
     */
    public function post($force_commit = false)
    {
        if ($this->invoice_ids)
        {
            # The status of this batch must be $STATUS_NEW
            #
            if ($this->post_status == self::$STATUS_NEW)
            {
                # Before exporting, make sure all the invoices have
                # customer PO numbers if they need them.
                #
                # Running this query to verify the PO requirement is much
                # faster than doing object instantiations for Facility
                # and LeaseContract types.
                #
                # Since we need this data for later, store it in
                # $contract_info so we don't have to do object
                # instantiations for Facility and LeaseContract types.
                #
                $dbh = DataStor::getHandle();
                $sth_po = $dbh->prepare('
					SELECT fd.po_required,
					       c.cust_po,
					       c.facility_pay,
					       c.payment_term_id,
					       f.accounting_id AS cust_id,
					       pt.term_disp AS payment_term
					FROM invoice_generated inv
					  INNER JOIN facilities f ON inv.cust_id = f.accounting_id
					  INNER JOIN facilities_details fd ON f.id = fd.facility_id
					  INNER JOIN contract c ON inv.contract_id = c.id_contract
					  LEFT OUTER JOIN contract_payment_term pt ON c.payment_term_id = pt.id
					WHERE inv.id = ?');
                $contract_info = array();
                foreach ($this->invoice_ids as $invoice_id)
                {
                    $sth_po->bindValue(1, $invoice_id);
                    $sth_po->execute();
                    $row = $sth_po->fetch(PDO::FETCH_ASSOC);
                    if ($row['po_required'] && !$row['cust_po'])
                    {
                        error_log("A customer PO number is required for {$row['cust_id']}.");
                        throw new Exception("A customer PO number is required for {$row['cust_id']}.");
                    }

                    $contract_info[$invoice_id] = $row;
                }

                # No other batches can be being posted right now
                #
                $num_posted = 0;
                $sth_posting = $dbh->query('
					SELECT id FROM invoice_batch
					WHERE posted IN (' . self::$STATUS_IN_PROGRESS . ',' . self::$STATUS_ERROR . ')');
                if ($sth_posting->rowCount() == 0)
                {
                    # Set batch status to post-in-progress. We do a catch/throw
                    # here to make the error message more specific; otherwise
                    # it might be difficult to know which database it came
                    # from.
                    #
                    try
                    {
                        $this->setPostStatus(self::$STATUS_IN_PROGRESS);
                        $this->save();
                    }
                    catch (Exception $exc)
                    {
                        throw new Exception('Could not set the batch status to Post in Progress: ' . $exc->getMessage());
                    }

                    try
                    {
                        # Get the database handle
                        #
                        $mas_dbh = DataStor::getHandle();

                        # Start a transaction
                        #
                        # The MSSQL PDO driver DOES NOT NATIVELY SUPPORT TRANSACTIONS!
                        #
                        #$mas_dbh->beginTransaction();
                        $mas_dbh->exec('BEGIN TRANSACTION');

                        # Delete any old records from the temp table.
                        #
                        $mas_dbh->exec('DELETE FROM tarGenContractBilling');

                        # Prepare the statement to insert into the staging
                        # table.
                        #
                        $sth_ins = $mas_dbh->prepare('
							INSERT INTO tarGenContractBilling (
							  InvcID,ContractID,CustID,CustPONo,InvcType,InvcDate,InvcCmnt,
							  FacilityPay,PmtTermsDescription,Description,ExtAmt,ItemID,QtyShipped,
							  SalesAcctKey,TradeDiscAmt,FreightAmt,UnitCost,Processed)
							VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,0,0,0)');

                        foreach ($this->invoice_ids as $invoice_id)
                        {
                            $invoice = new Invoice($invoice_id);
                            $line_items = $invoice->getLineItems();
                            if ($line_items)
                            {
                                foreach ($line_items as $line_item)
                                {
                                    $sales_acct_key = (strtoupper($line_item->getItemCode()) == 'Z-PTAX') ? 2900 : 2910;
                                    $qty = ($invoice->getTranType() == INV_TRAN_TYPE) ? $line_item->getQuantity() : (-1 * $line_item->getQuantity());
                                    $ext_amt = $qty * $line_item->getUnitPrice() * $invoice->getProrate();

                                    $sth_ins->bindValue(1, $invoice->getId(), PDO::PARAM_INT);
                                    $sth_ins->bindValue(2, $this->translateContractId($invoice->getContractId()), PDO::PARAM_STR);
                                    $sth_ins->bindValue(3, $contract_info[$invoice_id]['cust_id'], PDO::PARAM_STR);
                                    $sth_ins->bindValue(4, $contract_info[$invoice_id]['cust_po'], PDO::PARAM_STR);
                                    $sth_ins->bindValue(5, $invoice->getInvoiceType(), PDO::PARAM_STR);
                                    $sth_ins->bindValue(6, date('Y-m-d', $invoice->getInvoiceDate()));
                                    $sth_ins->bindValue(7, $invoice->getComment(), PDO::PARAM_STR);
                                    $sth_ins->bindValue(8, $contract_info[$invoice_id]['facility_pay'], PDO::PARAM_BOOL);
                                    $sth_ins->bindValue(9, $contract_info[$invoice_id]['payment_term'], (is_null($contract_info[$invoice_id]['payment_term']) ? PDO::PARAM_NULL : PDO::PARAM_STR));
                                    $sth_ins->bindValue(10, $line_item->getItemDescription(), PDO::PARAM_STR);
                                    $sth_ins->bindValue(11, $ext_amt);
                                    $sth_ins->bindValue(12, $line_item->getItemCode(), PDO::PARAM_STR);
                                    $sth_ins->bindValue(13, $qty, PDO::PARAM_INT);
                                    $sth_ins->bindValue(14, $sales_acct_key, PDO::PARAM_INT);
                                    $sth_ins->execute();
                                }

                                $num_posted++;
                            }
                        }

                        # Look for problems on the side that will cause
                        # errors in the stored procedure that will get called
                        # later.
                        #
                        $errors = array();

                        # Make sure all the items in staging table are
                        # active.
                        #
                        $sth_chk_item = $dbh->query('
							SELECT A.CustID, A.ItemID
							FROM tarGenContractBilling A
							  INNER JOIN timItem B ON A.ItemID = B.ItemID
							WHERE B.Status <> 1');
                        while ($row = $sth_chk_item->fetch(PDO::FETCH_ASSOC))
                        {
                            $msg = 'Item ' . $row['ItemID'] . ' at facility ' . $row['CustID'];

                            # Add the error message to the $errors array
                            #
                            if (!in_array($msg, $errors))
                            {
                                # Since the errors are reported in a browser
                                # alert(), we don't want or need to keep every
                                # one.  So we keep 10 and set the 11th to a
                                # string indicating there are more errors.
                                #
                                if (count($errors) < 10)
                                    $errors[] = $msg;
                                elseif (count($errors) == 10)
                                    $errors[] = 'and more...';
                            }
                        }

                        # Make sure all the cust ids in the staging table
                        # exist.
                        #
                        $sth_chk_cust = $dbh->query('
							SELECT CustID FROM tarGenContractBilling
							WHERE CustID NOT IN (SELECT CustID FROM tarCustomer)');
                        while ($row = $sth_chk_cust->fetch(PDO::FETCH_ASSOC))
                        {
                            $msg = $row['CustID'];

                            if (!in_array($msg, $errors))
                            {
                                if (count($errors) < 10)
                                    $errors[] = $msg;
                                elseif (count($errors) == 10)
                                    $errors[] = 'and more...';
                            }
                        }

                        # Make sure all the customers are active
                        #
                        $sth_chk_cust_status = $dbh->query('
							SELECT A.CustID
							FROM tarGenContractBilling A
							  INNER JOIN tarCustomer B ON A.CustID = B.CustID
							WHERE B.Status <> 1');
                        while ($row = $sth_chk_cust_status->fetch(PDO::FETCH_ASSOC))
                        {
                            $msg = $row['CustID']

                            if (!in_array($msg, $errors))
                            {
                                if (count($errors) < 10)
                                    $errors[] = $msg;
                                elseif (count($errors) == 10)
                                    $errors[] = 'and more...';
                            }
                        }

                        # If any errors were found, throw an Exception
                        #
                        if ($errors)
                        {
                            throw new Exception('Contract invoice posting failed due to the following error(s): ' . implode('; ', $errors));
                        }

                        # Commit the transaction
                        #
                        # The MSSQL PDO driver DOES NOT NATIVELY SUPPORT TRANSACTIONS!
                        #
                        #$mas_dbh->commit();
                        $mas_dbh->exec('COMMIT TRANSACTION');
                    }
                    catch (Exception $exc)
                    {
                        # Catch this so we can rollback the transaction and
                        # log the error before re-throwing
                        #
                        $mas_dbh->exec('ROLLBACK TRANSACTION');

                        # Set the  batch status to New
                        #
                        $this->setPostStatus(self::$STATUS_NEW);
                        $this->save();

                        # Log the error message and re-throw it
                        #
                        error_log($exc->getMessage());
                        throw $exc;
                    }

                    # Start the process that calls the stored procedure
                    #
                    $this->executeProcedure($force_commit);

                    # Return the number of invoices exported
                    #
                    return $num_posted;
                }
                else
                {
                    $batch_id = (int) $sth_posting->fetchColumn();
                    throw new Exception("A batch (#$batch_id) is currently being posted. Please wait for it to complete before trying to post another batch.");
                }
            }
            else
            {
                throw new Exception('Batch ' . $this->id . ' has already been posted or is currently being posted.');
            }
        }
        else
        {
            throw new Exception('Batch ' . $this->id . ' doesn\'t have any invoices.');
        }

        # Should never get here, but just in case...
        #
        return 0;
    }

    /**
     * Resumes a post that was previously stopped
     *
     * @throws PDOException, Exception
     */
    public function resumePost()
    {
        # Set batch status to post-in-progress
        #
        $this->setPostStatus(self::$STATUS_IN_PROGRESS);
        $this->save();

        # Start the process that calls the stored procedure
        #
        $this->executeProcedure();
    }

    /**
     * Updates the database record with the contents of this object
     *
     * @throws PDOException, Exception
     */
    public function save()
    {
        $time_posted = (is_null($this->time_posted)) ? null : $this->time_posted->format('c');
        $time_posted_type = (is_null($this->time_posted)) ? PDO::PARAM_NULL : PDO::PARAM_STR;

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('
			UPDATE invoice_batch
			SET type = ?,
			    invoice_date = ?,
			    num_invoices = ?,
			    posted_tstamp = ?,
			    posted = ?,
			    tran_type = ?
			WHERE id = ?');
        $sth->bindValue(1, $this->type, PDO::PARAM_STR);
        $sth->bindValue(2, $this->invoice_date->format('Y-m-d'), PDO::PARAM_STR);
        $sth->bindValue(3, count($this->invoice_ids), PDO::PARAM_INT);
        $sth->bindValue(4, $time_posted, $time_posted_type);
        $sth->bindValue(5, $this->post_status, PDO::PARAM_INT);
        $sth->bindValue(6, $this->tran_type, PDO::PARAM_INT);
        $sth->bindValue(7, $this->id, PDO::PARAM_INT);
        if (!$sth->execute())
        {
            $err_info = $sth->errorInfo();
            throw new Exception('could not update invoice_batch record: ' . $err_info[2]);
        }
    }

    /**
     * Sets the post status of the batch and updates the time posted
     *
     * @param int $status
     */
    public function setPostStatus($status)
    {
        $this->post_status = (int) $status;
        $this->time_posted = new DateTime();
    }

    /**
     * Creates a new InvoiceBatch
     *
     * @param string $type
     * @param int $tran_type
     * @param string $invoice_date
     * @return InvoiceBatch
     * @throws PDOException, Exception
     */
    public static function createInvoiceBatch($type, $tran_type, $invoice_date)
    {
        $inv_dt = new DateTime($invoice_date);

        $dbh = DataStor::getHandle();
        $sth = $dbh->prepare('
			INSERT INTO invoice_batch (type,invoice_date,num_invoices,tran_type)
			VALUES (?,?,0,?)');
        $sth->bindValue(1, $type, PDO::PARAM_STR);
        $sth->bindValue(2, $inv_dt->format('Y-m-d'), PDO::PARAM_STR);
        $sth->bindValue(3, $tran_type, PDO::PARAM_INT);
        if ($sth->execute())
        {
            $new_id = $dbh->lastInsertId('invoice_batch_id_seq');
            $new_batch = new InvoiceBatch($new_id);
            return $new_batch;
        }
        else
        {
            $err_info = $sth->errorInfo();
            throw new Exception('could not insert new invoice_batch record: ' . $err_info[2]);
        }
    }

    /**
     * Allows a function that is passed in to iterate over the batch
     * records. Useful for listing batches in different formats
     * (each format generator can pass in its own formatting function)
     * without storing text in a (potentially huge) string.
     *
     * @param Closure $func
     */
    public static function processBatches($func)
    {
        $dbh = DataStor::getHandle();
        $sth = $dbh->query('SELECT * FROM invoice_batch ORDER BY id DESC');
        $func($sth);
    }

    /**
     * Creates a new process which executes the post_invoices.php
     * script which executes the stored procedure which creates a
     * pending invoice batch.
     *
     * @param boolean $force_commit
     * @throws PDOException, Exception
     */
    private function executeProcedure($force_commit = false)
    {
        # Run the script that posts the invoices to and detach
        # so we can continue. Force_commit tells the post command
        # to set the batch post to 3 regardless of the result.
        #
        $params = $this->id . ($force_commit ? ' 1' : ' &');
        chdir(Config::$ROOT_PATH);
        pclose(popen("php " . Config::$ROOT_PATH . "/hidden/post_invoices.php $params", 'r'));
    }

    /**
     * Translates  contract ids into contract ids. To avoid contract
     * id collisions from different systems, contract ids generated,
     * which are 10 character, 0-padded strings, are turned into negative
     * integers when copied to . This function does the reverse
     * translation.
     *
     * @param integer $_cntr_id
     * @return string the equivalent contract id
     */
    private function translateContractId($_cntr_id)
    {
        if ($_cntr_id < 0)
        {
            $_cntr_id = sprintf('%010d', -$_cntr_id);
        }

        return $_cntr_id;
    }
}

?>