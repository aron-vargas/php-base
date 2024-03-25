<?php
/**
 * @package Freedom
 */
require_once ('Invoice.php');

/**
 * This utility class provides functions for creating different types of
 * invoice batches. Originally written by Aron and expanded by Jeremy.
 *
 * @package Freedom
 * @author Aron Vargas
 */
class GenerateInvoiceCreator extends BaseClass {
    private $mdbh;						#PDO Object
    protected $db_table = '--empty--';	# string
    protected $p_key = 'empty';			# string

    protected $batch_id_ary;			# array

    static public $NET_30_TERM = "Net 30";

    public function __construct()
    {
        # not used
        #$this->mdbh = DataStor::GetHandle();

        $this->dbh = DataStor::GetHandle();
    }

    /**
     * Add an invoice record for the customer for the period
     * start_date to end_date. Handles both invoices and credit memos.
     * If the time period spans month it will be split.
     *
     * @param int $tran_type : Type of transaction
     * @param object $contract : LeaseContract instance
     * @param string $cust_id : Cust ID
     * @param int $invoice_time : Date (unix time) invoice is for
     * @param int $start_date : Date (unix time) begining of the invoice period
     * @param int $end_date : Date (unix time) end of the invoice period
     *
     * @return int $inv_count : Number of invoices added
     */
    public function AddInvoice($tran_type, $contract, $cust_id, $invoice_time, $start_date, $end_date)
    {
        $debug = false;

        # Asign field values for the invoice
        #
        $inv_count = 0;
        $contract_id = $contract->getVar('id_contract');
        $payment_term = $contract->GetPaymentTerms();
        $hex_contract_id = ($contract_id < 0) ? '-' . dechex(-1 * $contract_id) : dechex($contract_id);
        $inv_type = 'stub';
        if (!$invoice_time)
            $invoice_time = $start_date; # Invoice date set to the period its for not "Today"
        $invoice_date = date('Y-m-d', $invoice_time);
        $due_date = date('Y-m-d', strtotime("+30 Days", $invoice_time));

        # Test if the end date is in another month
        $actual_end = $end_date;
        if (date('m', $start_date) != date('m', $end_date))
        {
            # Set the end_date to the last day of the month
            $end_date = strtotime(date('Y-m-t', $start_date));
        }
        $start_dt = new DateTime(date('Y-m-d', $start_date));
        $end_dt = new DateTime(date('Y-m-d', $end_date));

        $batch_id = $this->GetInvoiceBatch($tran_type, $end_date);

        if ($contract->getVar('id_contract_type') == LeaseContract::$LOANER_TYPE)
        {
            $pro_rate = 1;
            $sales_amount = LoanerContract::GetSaleAmount($contract_id, $start_dt, $end_dt);
            $loaner_amount = $sales_amount;
        }
        else
        {
            $sales_amount = Invoice::calcProrate($start_dt, $end_dt, $contract->getVar('sale_amount'));
            $sales_amount = round($sales_amount, 4);
            $pro_rate = $sales_amount / $contract->getVar('sale_amount');
        }

        # Set the appropriate comment string
        $comment = "";
        if ($tran_type == CREDIT_TRAN_TYPE)
        {
            # Take negative value
            $sales_amount *= -1;
            $inv_type = "credit";

            $comment = "Credit $cust_id ";
            # Append date range and formated contract id and batch id
            $comment .= date('m/d', $start_date) . '-' . date('m/d', $end_date);

            $due_date = $invoice_date;

            $payment_term = self::$NET_30_TERM;
        }
        else if ($tran_type == INV_TRAN_TYPE)
        {
            $comment = "Invoice $cust_id ";
            # Append date range and formated contract id and batch id
            $comment .= date('m/d', $start_date) . '-' . date('m/d', $end_date);
        }

        # Append formated contract id and batch id
        $comment .= "|$hex_contract_id:$batch_id";

        ## debug code
        if ($debug)
        {
            echo "::AddInvoice tran_type = $tran_type\n";
            echo "::AddInvoice due_date = $due_date\n";
            echo "::AddInvoice invoice_date = $invoice_date\n";
            echo "::AddInvoice comment = $comment\n";
            echo "::AddInvoice sales_amount $sales_amount\n";
            echo "::AddInvoice pro_rate $pro_rate\n";

            if ($sales_amount == 0)
            {
                echo "::AddInvoice --SKIPPED--\n";
            }
        }

        if ($sales_amount !== 0)
        {
            $inv_count = 1;

            # Insert the invoice
            #
            $sth_ins_inv = $this->dbh->prepare("INSERT INTO invoice_generated
				(batch_id, contract_id, cust_id, due_date, invoice_date, comment,
				tran_type, sales_amt, invoice_type, pro_rate, payment_term)
			VALUES (?,?,?,?,?,?, ?,?,?,?,?)");
            $sth_ins_inv->bindValue(1, $batch_id, PDO::PARAM_INT);
            $sth_ins_inv->bindValue(2, $contract_id, PDO::PARAM_INT);
            $sth_ins_inv->bindValue(3, $cust_id, PDO::PARAM_STR);
            $sth_ins_inv->bindValue(4, $due_date, PDO::PARAM_STR);
            $sth_ins_inv->bindValue(5, $invoice_date, PDO::PARAM_STR);
            $sth_ins_inv->bindValue(6, $comment, PDO::PARAM_STR);
            $sth_ins_inv->bindValue(7, $tran_type, PDO::PARAM_INT);
            $sth_ins_inv->bindValue(8, $sales_amount, PDO::PARAM_STR);
            $sth_ins_inv->bindValue(9, $inv_type, PDO::PARAM_STR);
            $sth_ins_inv->bindValue(10, $pro_rate, PDO::PARAM_STR);
            $sth_ins_inv->bindValue(11, $payment_term, PDO::PARAM_STR);
            $sth_ins_inv->execute();

            $invoice_id = $this->dbh->lastInsertId('invoice_generated_id_seq');

            # Insert the invoice line item
            #
            if ($contract->getVar('id_contract_type') == LeaseContract::$LOANER_TYPE)
            {
                $this->InsertLoanerItems($invoice_id, $contract_id);
            }
            else
            {
                $is_purchase = $contract->getVar('id_contract_type') == LeaseContract::$PURCHASE_TYPE;
                $this->InsertContractItems($invoice_id, $contract_id, $is_purchase);
            }

            # Increment the batch invoice count
            #
            $sth_up = $this->dbh->prepare("UPDATE invoice_batch
			SET num_invoices = num_invoices + 1
			WHERE id = ?");
            $sth_up->bindValue(1, $batch_id, PDO::PARAM_INT);
            $sth_up->execute();
        }

        # Invoice is split now across multiple months add next invoice part
        if ($actual_end > $end_date)
        {
            $start_date = $end_date + 86400;
            $inv_count += $this->AddInvoice($tran_type, $contract, $cust_id, $invoice_time, $start_date, $actual_end);
        }

        return $inv_count;
    }

    /**
     * Creates contract invoice batches. The logic for this is documented
     * in the ITWiki page Invoice_Generation_Logic.
     *
     * @param string $invoice_date
     * @return array an array of {@link InvoiceBatch} objects
     * @throws PDOException Exception
     */
    public function createContractInvoiceBatches($invoice_date)
    {
        # Make sure there are no uncommitted batches before trying to
        # create new batches
        #
        $uncommitted_statuses = array(
            InvoiceBatch::$STATUS_NEW,
            InvoiceBatch::$STATUS_IN_PROGRESS,
            InvoiceBatch::$STATUS_POSTED
        );

        $sth_posting = $this->dbh->query('
			SELECT count(*) FROM invoice_batch
			WHERE posted IN (' . implode(',', $uncommitted_statuses) . ')');
        $cnt = (int) $sth_posting->fetchColumn();
        if ($cnt > 0)
        {
            throw new Exception('A batch has not yet been posted or committed. This must be done before creating a new batch.');
        }

        # Create the batch metadata that we use to setup the batch records.
        # IMPORTANT: The order in which the batches are created is important!
        # They are in this order because we want to process them in this order
        # when their invoices return
        #
        $stub_curr_batch = $stub_next_batch = $full_batch =
            $credit_curr_batch = $credit_next_batch = null;
        $batch_creation_meta = array(
            array('stub (current)', INV_TRAN_TYPE, &$stub_curr_batch),
            array('stub (next)', INV_TRAN_TYPE, &$stub_next_batch),
            array('full', INV_TRAN_TYPE, &$full_batch),
            array('credit (next)', CREDIT_TRAN_TYPE, &$credit_next_batch),
            array('credit (current)', CREDIT_TRAN_TYPE, &$credit_curr_batch)
        );

        # Start a transaction
        #
        $this->dbh->beginTransaction();

        # Create the batch records.  We will delete any unused
        # batch records later.
        #
        foreach ($batch_creation_meta as $batch_meta_tuple)
        {
            $batch_meta_tuple[2] = InvoiceBatch::createInvoiceBatch(
                $batch_meta_tuple[0], $batch_meta_tuple[1], $invoice_date);
        }

        # Run the query that gets information from the contracts to create
        # invoices. We select a lot of dates to make the comparisons easier
        # in the PHP code.
        #
        $sth_inv = $this->dbh->query("
			SELECT f.accounting_id AS cust_id,
			       f.corporate_parent,
			       c.id_contract AS contract_id,
			       c.sale_amount AS sale_amt,
			       c.id_contract_type AS contract_type,
			       c.cust_po AS cust_po,
			       c.facility_pay AS facility_pay,
			       pt.term_disp AS payment_term,
			       extract(epoch from date_trunc('month', '$invoice_date'::date) + interval '9 days') AS mth_bill,
			       extract(epoch from c.date_billing_start) AS bill_start,
			       extract(epoch from c.date_billing_start + interval '1 day') AS bill_start_p1,

			       CASE WHEN pt.term_due IS NULL
			            THEN extract(epoch from c.date_billing_start + interval '30 days')
			            ELSE extract(epoch from c.date_billing_start + pt.term_due::interval)
			       END AS bill_start_pt,

			       extract(epoch from c.date_invoice_start) AS inv_start,

			       extract(epoch from CASE WHEN c.id_contract_type = " . LeaseContract::$PURCHASE_TYPE . "
			                               THEN LEAST (c.date_cancellation, cep.comprehensive_expiration_date)
			                               ELSE c.date_cancellation
			                               END) AS cxl,

			       extract(epoch from c.date_billed_through + interval '1 day - 1 second') AS bill_through,
			       extract(epoch from date '$invoice_date') AS inv,

			       CASE WHEN pt.term_due IS NULL
			            THEN extract(epoch from date '$invoice_date' + interval '30 days')
			            ELSE extract(epoch from date '$invoice_date' + pt.term_due::interval)
			       END AS inv_pt,

			       extract(epoch from date_trunc('month', '$invoice_date'::date)) AS beg_mth,
			       extract(epoch from date_trunc('month', '$invoice_date'::date) + interval '1 month - 1 second') AS end_mth,
			       extract(epoch from date_trunc('month', '$invoice_date'::date + interval '1 month')) AS beg_next_mth,
			       extract(epoch from date_trunc('month', '$invoice_date'::date + interval '2 months') - interval '1 second') AS end_next_mth
			FROM contract c
			  INNER JOIN facilities f ON c.id_facility = f.id
			  LEFT OUTER JOIN contract_payment_term pt ON c.payment_term_id = pt.id
			  LEFT JOIN (
					SELECT
						contract_id,
						MAX(maintenance_expiration_date) AS comprehensive_expiration_date
			        FROM contract_line_item cep
			        INNER JOIN service_item_to_product sitp ON sitp.item = cep.item_code
					WHERE (cep.date_removed IS NULL OR cep.date_removed > CURRENT_DATE)
			        GROUP BY contract_id
			  ) cep ON cep.contract_id = c.id_contract
			WHERE c.id_contract_type != 12  -- exclude LOANER contracts
			  AND c.sale_amount != 0
			  AND c.date_billing_start <= '$invoice_date'::date
			  AND (CASE WHEN c.date_install = c.date_cancellation THEN false ELSE true END)
			  AND COALESCE(c.contract_version,'none') != 'INVALID'
			ORDER BY f.corporate_parent, cust_id");
        while ($row = $sth_inv->fetch(PDO::FETCH_ASSOC))
        {
            $hex_contract_id = ($row['contract_id'] < 0) ? '-' . dechex(-1 * $row['contract_id']) : dechex($row['contract_id']);

            # Current Month Condition Map
            #
            # This keeps track of the conditions related to the bill-through date
            # and the cancellation date that are true.  This helps us determine
            # what the start and end dates are for an invoice.  Also, both elements
            # need to have a true condition to generate an invoice.
            #
            $curr_mth_cdtn_map = array('bt' => 0, 'cxl' => 0);

            if ($row['contract_type'] != LeaseContract::$PURCHASE_TYPE ||
                ($row['contract_type'] == LeaseContract::$PURCHASE_TYPE && $row['sale_amt'] > 0))
            {
                if (is_null($row['bill_through']))
                {
                    $curr_mth_cdtn_map['bt'] = 1;

                    if (is_null($row['cxl']))
                    {
                        $curr_mth_cdtn_map['cxl'] = 1;
                    }
                    # We use bill_start_p1 here because the bill_start's time is 00:00
                    # and cxl's time is 23:59:59.
                    elseif ($row['cxl'] > $row['bill_start_p1'] &&
                        $row['cxl'] < $row['end_mth'])
                    {
                        $curr_mth_cdtn_map['cxl'] = 2;
                    }
                    elseif ($row['cxl'] >= $row['beg_next_mth'])
                    {
                        $curr_mth_cdtn_map['cxl'] = 3;
                    }
                }
                elseif ($row['bill_through'] < $row['end_mth'])
                {
                    $curr_mth_cdtn_map['bt'] = 2;

                    if (is_null($row['cxl']))
                    {
                        $curr_mth_cdtn_map['cxl'] = 1;
                    }
                    elseif ($row['cxl'] > $row['bill_through'] + 1 &&
                        $row['cxl'] < $row['end_mth'])
                    {
                        $curr_mth_cdtn_map['cxl'] = 2;
                    }
                    elseif ($row['cxl'] >= $row['beg_next_mth'])
                    {
                        $curr_mth_cdtn_map['cxl'] = 3;
                    }
                }
            }

            # If a bill-through condition was true and a cancellation condition
            # was true, generate an invoice.
            #
            if ($curr_mth_cdtn_map['bt'] && $curr_mth_cdtn_map['cxl'])
            {
                # Look at the condition map to determine the date range for the
                # invoice as well as the invoice date and due date.
                #
                $start_date_epoch = ($curr_mth_cdtn_map['bt'] == 1) ? $row['bill_start'] : $row['bill_through'] + 1;
                $invoice_date_epoch = ($curr_mth_cdtn_map['bt'] == 1) ? $row['bill_start'] : $row['inv'];
                $end_date_epoch = ($curr_mth_cdtn_map['cxl'] == 2) ? $row['cxl'] - 1 : $row['end_mth'];

                # Create the invoice and add it to the batch
                #
                $stub_curr_batch->addInvoice(
                    Invoice::createContractInvoice(
                        $stub_curr_batch->getId(),
                        $row['contract_id'],
                        date('Y-m-d', $invoice_date_epoch),
                        'stub',
                        INV_TRAN_TYPE,
                        date('Y-m-d', $start_date_epoch),
                        date('Y-m-d', $end_date_epoch),
                        'Pro Rated ' . date('m/d/y', $start_date_epoch) . '-' . date('m/d/y', $end_date_epoch) . "|$hex_contract_id:" . $stub_curr_batch->getId()
                    )
                );
            }

            # If the invoice date is >= the 10th of the month, look to see if we
            # can generate any invoices for the next month.
            #
            if (date('j', $row['inv']) >= 10)
            {
                # Next Month Condition Map
                #
                # This keeps track of the conditions related to the bill-through
                # date and the cancellation date that are true.  This helps us
                # determine what the start and end dates are for an invoice.  Also,
                # both elements need to have a true condition to generate an
                # invoice.
                #
                $next_mth_cdtn_map = array('bt' => 0, 'cxl' => 0);

                if ($row['contract_type'] != LeaseContract::$PURCHASE_TYPE ||
                    ($row['contract_type'] == LeaseContract::$PURCHASE_TYPE && $row['sale_amt'] > 0))
                {
                    if (is_null($row['bill_through']))
                    {
                        $next_mth_cdtn_map['bt'] = 1;

                        if (is_null($row['cxl']))
                        {
                            $next_mth_cdtn_map['cxl'] = 1;
                        }
                        elseif ($row['cxl'] > $row['end_next_mth'])
                        {
                            $next_mth_cdtn_map['cxl'] = 2;
                        }
                        elseif ($row['cxl'] > $row['beg_next_mth'] &&
                            $row['cxl'] < $row['end_next_mth'])
                        {
                            $next_mth_cdtn_map['cxl'] = 3;
                        }
                    }
                    elseif ($row['bill_through'] < $row['beg_next_mth'])
                    {
                        $next_mth_cdtn_map['bt'] = 2;

                        if (is_null($row['cxl']))
                        {
                            $next_mth_cdtn_map['cxl'] = 1;
                        }
                        elseif ($row['cxl'] > $row['end_next_mth'])
                        {
                            $next_mth_cdtn_map['cxl'] = 2;
                        }
                        elseif ($row['cxl'] > $row['beg_next_mth'] &&
                            $row['cxl'] > $row['bill_through'] + 1 &&
                            $row['cxl'] < $row['end_next_mth'])
                        {
                            $next_mth_cdtn_map['cxl'] = 3;
                        }
                    }
                    elseif ($row['bill_through'] > $row['beg_next_mth'] &&
                        $row['bill_through'] < $row['end_next_mth'])
                    {
                        $next_mth_cdtn_map['bt'] = 3;

                        if (is_null($row['cxl']))
                        {
                            $next_mth_cdtn_map['cxl'] = 1;
                        }
                        elseif ($row['cxl'] > $row['end_next_mth'])
                        {
                            $next_mth_cdtn_map['cxl'] = 2;
                        }
                        elseif ($row['cxl'] > $row['beg_next_mth'] &&
                            $row['cxl'] > $row['bill_through'] + 1 &&
                            $row['cxl'] < $row['end_next_mth'])
                        {
                            $next_mth_cdtn_map['cxl'] = 3;
                        }
                    }
                }

                # If a bill-through condition was true and a cancellation condition
                # was true, generate an invoice.
                #
                if ($next_mth_cdtn_map['bt'] && $next_mth_cdtn_map['cxl'])
                {
                    # Look at the condition map to determine the date range for the
                    # invoice.
                    $start_date_epoch = ($next_mth_cdtn_map['bt'] == 3) ? $row['bill_through'] + 1 : $row['beg_next_mth'];
                    $end_date_epoch = ($next_mth_cdtn_map['cxl'] == 3) ? $row['cxl'] - 1 : $row['end_next_mth'];

                    # Determine whether it's a full month invoice or a stub invoice.
                    #
                    $batch = &$full_batch;
                    $comment = 'Monthly Billing ';
                    $inv_type = 'full';
                    if ($next_mth_cdtn_map['bt'] == 3 || $next_mth_cdtn_map['cxl'] == 3)
                    {
                        $batch = &$stub_next_batch;
                        $comment = 'Pro Rated ';
                        $inv_type = 'stub';
                    }

                    # Create the invoice and add it to the batch
                    #
                    $batch->addInvoice(
                        Invoice::createContractInvoice(
                            $batch->getId(),
                            $row['contract_id'],
                            date('Y-m-d', $row['inv']),
                            $inv_type,
                            INV_TRAN_TYPE,
                            date('Y-m-d', $start_date_epoch),
                            date('Y-m-d', $end_date_epoch),
                            $comment . date('m/d/y', $start_date_epoch) . '-' . date('m/d/y', $end_date_epoch) . "|$hex_contract_id:" . $batch->getId()
                        )
                    );
                }
            }

            # Check to see if the billing start date is earlier than the
            # invoice start date.  This probably means the billing start
            # date was moved back after the first invoice was generated
            # and we need to create an invoice for the length of time it
            # was moved back.
            #
            if (!is_null($row['inv_start']) &&
                $row['bill_start'] < $row['inv_start'])
            {
                if ($row['contract_type'] != LeaseContract::$PURCHASE_TYPE ||
                    ($row['contract_type'] == LeaseContract::$PURCHASE_TYPE && $row['sale_amt'] > 0))
                {

                    $stub_curr_batch->addInvoice(
                        Invoice::createContractInvoice(
                            $stub_curr_batch->getId(),
                            $row['contract_id'],
                            date('Y-m-d', $row['inv']),
                            'stub',
                            INV_TRAN_TYPE,
                            date('Y-m-d', $row['bill_start']),
                            date('Y-m-d', $row['inv_start'] - 1),
                            'Pro Rated ' . date('m/d/y', $row['bill_start']) . '-' . date('m/d/y', $row['inv_start'] - 1) . "|$hex_contract_id:" . $stub_curr_batch->getId()
                        )
                    );
                }
            }


            # Look for any contracts that need to be credited.  Credits can happen
            # in two ways:
            #
            # 1) At the end of a contract (bill through > cxl)
            # 2) At the beginning of a contract (invoice start < billing start)
            #
            # We wait to create a credit for (1) until the invoice date is later
            # than or equal to the cxl date.
            #
            if (!is_null($row['cxl']) &&
                $row['cxl'] < $row['bill_through'] &&
                $row['cxl'] <= $row['inv'])
            {
                #
                # This can happen when the customer has already been billed
                # for the next month, but they decide to cancel the contract
                # before the end of the month.
                #
                # There is also the possibility that the beginning of the credit
                # period is in a different month than the end of the credit
                # period. If this is the case, the credit needs to be split into
                # two batches.
                #

                if ($row['bill_through'] > $row['beg_next_mth'])
                {
                    # Generate a credit memo for the period beginning next month
                    #
                    $credit_next_batch->addInvoice(
                        Invoice::createContractInvoice(
                            $credit_next_batch->getId(),
                            $row['contract_id'],
                            date('Y-m-d', $row['cxl']),
                            'credit',
                            CREDIT_TRAN_TYPE,
                            date('Y-m-d', $row['beg_next_mth']),
                            date('Y-m-d', $row['bill_through']),
                            'Credit ' . date('m/d/y', $row['beg_next_mth']) . '-' . date('m/d/y', $row['bill_through']) . "|$hex_contract_id:" . $credit_next_batch->getId()
                        )
                    );
                }

                # Generate a credit memo for the current period (this month and
                # prior).
                #
                $end_date = min($row['bill_through'], $row['end_mth']);
                $credit_curr_batch->addInvoice(
                    Invoice::createContractInvoice(
                        $credit_curr_batch->getId(),
                        $row['contract_id'],
                        date('Y-m-d', $row['cxl']),
                        'credit',
                        CREDIT_TRAN_TYPE,
                        date('Y-m-d', $row['cxl']),
                        date('Y-m-d', $end_date),
                        'Credit ' . date('m/d/y', $row['cxl']) . '-' . date('m/d/y', $end_date) . "|$hex_contract_id:" . $credit_curr_batch->getId()
                    )
                );
            }

            if (!is_null($row['inv_start']) &&
                $row['inv_start'] < $row['bill_start'])
            {
                #
                # This can happen when the billing start date is moved
                # forward after the first invoice has already been
                # generated.
                #

                # Generate a credit memo
                #
                $credit_curr_batch->addInvoice(
                    Invoice::createContractInvoice(
                        $credit_curr_batch->getId(),
                        $row['contract_id'],
                        date('Y-m-d', $row['inv']),
                        'credit',
                        CREDIT_TRAN_TYPE,
                        date('Y-m-d', $row['inv_start']),
                        date('Y-m-d', $row['bill_start'] - 1),
                        'Credit ' . date('m/d/y', $row['inv_start']) . '-' . date('m/d/y', $row['bill_start'] - 1) . "|$hex_contract_id:" . $credit_curr_batch->getId()
                    )
                );
            }
        } # end of main query loop

        # Delete the batches that don't have any invoices
        #
        $batches = array();
        foreach (array($stub_curr_batch, $stub_next_batch, $full_batch, $credit_curr_batch, $credit_next_batch) as $batch)
        {
            if ($batch->getNumInvoices() == 0)
            {
                $batch->delete();
            }
            else
            {
                $batch->save(); # sets num_invoices correctly
                $batches[] = $batch;
            }
        }

        # Commit the transaction
        #
        $this->dbh->commit();

        # Return the batches that contain invoices
        #
        return $batches;
    }

    /**
     * Creates loaner invoice/credit batches. This process is described in
     * the ITwiki page Loaner_Invoicing.
     *
     * Note: this function considers the invoice date to be BILLABLE
     *
     * @param string $invoice_date
     * @return array an array of {@link InvoiceBatch} objects
     * @throws PDOException Exception
     */
    public function createLoanerInvoiceBatches($invoice_date)
    {
        # Make sure there are no unposted loaner batches
        #
        $sth_chk = $this->dbh->query("
			SELECT count(*) FROM invoice_batch
			WHERE type ILIKE '%loaner%' AND posted IN (0,1,10)");
        $num_unposted = $sth_chk->fetchColumn();
        if ($num_unposted == 0)
        {
            $invoice_type = 'loaner invoice';
            $credit_type = 'loaner credit';

            $inv_batch = $crd_batch = null;

            # Create the invoice timestamp
            #
            $inv_dt = new DateTime($invoice_date);

            # Start a transaction
            #
            $this->dbh->beginTransaction();

            # Query to get the Loaner contracts
            #
            $sth_inv = $this->dbh->query("
				SELECT c.id_contract,
				       c.date_billing_start AS bill_start,
				       c.date_invoice_start AS inv_start,
				       c.date_billed_through AS bill_thru,
				       c.date_cancellation AS cxl
				FROM contract c
				  INNER JOIN facilities f ON c.id_facility = f.id
				WHERE c.id_contract_type = " . LeaseContract::$LOANER_TYPE . "
				  AND c.date_billing_start <= '$invoice_date'::date
				  AND f.accounting_id NOT LIKE '___9__%'
				  AND (CASE WHEN c.date_install = c.date_cancellation THEN false ELSE true END)
				  AND COALESCE(c.contract_version,'none') != 'INVALID'");
            while ($row = $sth_inv->fetch(PDO::FETCH_ASSOC))
            {
                $bill_start_dt = new DateTime($row['bill_start']);
                $inv_start_dt = (is_null($row['inv_start'])) ? null : new DateTime($row['inv_start']);
                $bill_thru_dt = (is_null($row['bill_thru'])) ? null : new DateTime($row['bill_thru']);
                $cxl_dt = (is_null($row['cxl'])) ? null : new DateTime($row['cxl']);

                #
                # Bill for the end of a contract
                #
                # Set the billing window dates. These are inclusive.
                #
                $bill_window_start = clone $bill_start_dt;
                if ($bill_thru_dt)
                {
                    $bill_window_start = clone $bill_thru_dt;
                    $bill_window_start->add(new DateInterval('P1D'));
                }

                $bill_window_end = clone $inv_dt;
                if ($cxl_dt && $cxl_dt < $inv_dt)
                {
                    $bill_window_end = clone $cxl_dt;
                    $bill_window_end->sub(new DateInterval('P1D'));
                }

                if ($bill_window_start <= $bill_window_end)
                {
                    # Make sure the invoice batch has been created
                    #
                    if (!$inv_batch)
                    {
                        $inv_batch = InvoiceBatch::createInvoiceBatch(
                            $invoice_type, INV_TRAN_TYPE, $invoice_date
                        );
                    }

                    # Create an invoice for $bill_window_start to $bill_window_end
                    #
                    $new_invoice = Invoice::createLoanerInvoice(
                        $inv_batch->getId(), $row['id_contract'], $invoice_date,
                        $invoice_type, INV_TRAN_TYPE,
                        $bill_window_start->format('Y-m-d'),
                        $bill_window_end->format('Y-m-d')
                    );

                    # Add the invoice to the batch (if one was created)
                    #
                    if ($new_invoice)
                        $inv_batch->addInvoice($new_invoice);
                }

                #
                # Bill for the beginning of a contract
                #
                if ($inv_start_dt && $bill_start_dt < $inv_start_dt)
                {
                    # The billing period is the billing start date through the
                    # day before the invoice start date
                    #
                    $bill_window_start = clone $bill_start_dt;
                    $bill_window_end = clone $inv_start_dt;
                    $bill_window_end->sub(new DateInterval('P1D'));

                    # Make sure the invoice batch has been created
                    #
                    if (!$inv_batch)
                    {
                        $inv_batch = InvoiceBatch::createInvoiceBatch(
                            $invoice_type, INV_TRAN_TYPE, $invoice_date
                        );
                    }

                    # Create an invoice for $bill_start_dt to $inv_start_dt
                    #
                    $new_invoice = Invoice::createLoanerInvoice(
                        $inv_batch->getId(), $row['id_contract'], $invoice_date,
                        $invoice_type, INV_TRAN_TYPE,
                        $bill_window_start->format('Y-m-d'),
                        $bill_window_end->format('Y-m-d')
                    );

                    # Add the invoice to the batch (if one was created)
                    #
                    if ($new_invoice)
                        $inv_batch->addInvoice($new_invoice);
                }

                #
                # Credit for the end of a contract
                #
                if ($bill_thru_dt && $cxl_dt && $bill_thru_dt >= $cxl_dt)
                {
                    # Make sure the credit batch has been created
                    #
                    if (!$crd_batch)
                    {
                        $crd_batch = InvoiceBatch::createInvoiceBatch(
                            $credit_type, CREDIT_TRAN_TYPE, $invoice_date
                        );
                    }

                    # Create a credit for $cxl_dt to $bill_thru_dt
                    #
                    $new_invoice = Invoice::createLoanerInvoice(
                        $crd_batch->getId(), $row['id_contract'], $invoice_date,
                        $credit_type, CREDIT_TRAN_TYPE,
                        $cxl_dt->format('Y-m-d'),
                        $bill_thru_dt->format('Y-m-d')
                    );

                    # Add the credit to the batch (if one was created)
                    #
                    if ($new_invoice)
                        $crd_batch->addInvoice($new_invoice);
                }

                #
                # Credit for the beginning of a contract
                #
                if ($inv_start_dt && $bill_start_dt > $inv_start_dt)
                {
                    # The credit period is the invoice start date through the
                    # day before the billing start date
                    #
                    $credit_window_start = clone $inv_start_dt;
                    $credit_window_end = clone $bill_start_dt;
                    $credit_window_end->sub(new DateInterval('P1D'));

                    # Make sure the credit batch has been created
                    #
                    if (!$crd_batch)
                    {
                        $crd_batch = InvoiceBatch::createInvoiceBatch(
                            $credit_type, CREDIT_TRAN_TYPE, $invoice_date
                        );
                    }

                    # Create a credit for $inv_start_dt to $bill_start_dt
                    #
                    $new_invoice = Invoice::createLoanerInvoice(
                        $crd_batch->getId(), $row['id_contract'], $invoice_date,
                        $credit_type, CREDIT_TRAN_TYPE,
                        $credit_window_start->format('Y-m-d'),
                        $credit_window_end->format('Y-m-d')
                    );

                    # Add the credit to the batch (if one was created)
                    #
                    if ($new_invoice)
                        $crd_batch->addInvoice($new_invoice);
                }
            }

            # Delete the batches that don't have any invoices
            #
            $batches = array();
            foreach (array($inv_batch, $crd_batch) as $batch)
            {
                if ($batch)
                {
                    if ($batch->getNumInvoices() == 0)
                    {
                        $batch->delete();
                    }
                    else
                    {
                        $batch->save(); # sets num_invoices correctly in the table
                        $batches[] = $batch;
                    }
                }
            }

            # Commit the transaction
            #
            $this->dbh->commit();

            return $batches;
        }
        else
        {
            if ($num_unposted == 1)
                $msg = "There is $num_unposted unposted loaner batch";
            else
                $msg = "There are $num_unposted unposted loaner batches";

            throw new Exception($msg);
        }
    }

    /**
     * Creates a batch for a shipping charge invoice.
     *
     * @param LeaseContract $contract
     * @param string $invoice_date
     * @param float $shipping_charge
     * @return InvoiceBatch
     * @throws PDOException Exception
     */
    public function createShippingInvoiceBatch($contract, $invoice_date, $shipping_charge)
    {
        global $sh;

        $invoice_type = 'full (shipping)';

        # Start a transaction
        #
        $this->dbh->beginTransaction();

        $batch = InvoiceBatch::createInvoiceBatch($invoice_type, INV_TRAN_TYPE, $invoice_date);

        $batch->addInvoice(
            Invoice::createShippingInvoice(
                $batch->getId(), $contract, $invoice_date,
                $invoice_type, $shipping_charge
            )
        );

        $batch->save();

        # Commit the transaction
        #
        $this->dbh->commit();

        return $batch;
    }

    /**
        * Commented out by JB...don't think this is used anywhere
        *
        * Process the invoice batch.
        *
        * ** This is unused **
        *
        * @param boolean
        *
        * @param string

       public function CommitBatches($post)
       {
           $error_msg = "";

           # Prepare posted update query
           #
           if ($post)
           {
               $sth_update_posted = $this->dbh->prepare("UPDATE invoice_batch SET
                   posted = 3,
                   posted_tstamp = CURRENT_TIMESTAMP
               WHERE id = ?");
           }

           # Prepare the query that deletes unused batch records
           #
           $sth_delete = $this->dbh->prepare("DELETE FROM invoice_batch WHERE id = ? AND num_invoices = 0");

           if (is_array($this->batch_id_ary))
           {
               foreach($this->batch_id_ary as $batch_id)
               {
                   # Create the invoices
                   #
                   $errrors = null;

                   # Remove the batch if it is empty
                   #
                   $sth_delete->bindValue(1, $batch_id, PDO::PARAM_INT);
                   $sth_delete->execute();

                   if (self::$POST_INVOICES)
                   {
                       $invoices = fetch_invoices($batch_id);
                       if (count($invoices))
                       {
                           $inv_count = export_invoices($batch_id, $invoices, &$errors, true);

                           # Mark batch committed
                           if ($post)
                           {
                               $sth_update_posted->bindValue(1, $batch_id, PDO::PARAM_INT);
                               $sth_update_posted->execute();
                           }

                           # Report any problems
                           #
                           if ($inv_count < 1)
                           {
                               # Report any errors
                               if ($errors)
                               {
                                   $error_msg .= "<p class='error'>Invoice posting failed due to the following error(s):<br/>\n";
                                   $error_msg .= implode("<br/>\n* ", $errors);
                                   $error_msg .= "</p>";
                               }
                               else
                               {
                                   $error_msg .= "<p class='error'>Warning: No invoices were exported.<p>";
                               }
                           }
                       }
                   }
               }
           }

           return $error_msg;
       }
       */

    /**
     * Attempt to find an open batch for the given month.
     * When there is no open batch create one.
     *
     * @return integer
     */
    private function GetInvoiceBatch($tran_type, $end_date)
    {
        # Returning batch id
        $batch_id = 0;

        if ($tran_type == CREDIT_TRAN_TYPE)
            $type = "credit (" . date('F', $end_date) . ")";
        else # if ($tran_type == INV_TRAN_TYPE)
            $type = "stub (" . date('F', $end_date) . ")";

        # Find existing unposted batch for the type and month
        #
        $sth_sel = $this->dbh->prepare('SELECT id
		FROM invoice_batch
		WHERE type = ?
		AND tran_type = ?
		AND posted = 0');
        $sth_sel->bindValue(1, $type, PDO::PARAM_STR);
        $sth_sel->bindValue(2, $tran_type, PDO::PARAM_INT);
        $sth_sel->execute();
        list($batch_id) = $sth_sel->fetch(PDO::FETCH_NUM);

        if (!$batch_id)
        {
            # Create a new batch record
            #
            $sth_ins = $this->dbh->prepare('INSERT INTO invoice_batch
			(type, invoice_date, tran_type, num_invoices)
			VALUES (?, CURRENT_DATE, ?, 0)');
            $sth_ins->bindValue(1, $type, PDO::PARAM_STR);
            $sth_ins->bindValue(2, $tran_type, PDO::PARAM_INT);
            $sth_ins->execute();

            # Set the id
            $batch_id = $this->dbh->lastInsertId('invoice_batch_id_seq');
        }

        # Dont currenty need this but tracking it anyway
        $this->batch_id_ary[] = $batch_id;

        return $batch_id;
    }

    /**
     * Add records into invoice_generated_line_items from contract items
     *
     * @param integer $invoice_id
     * @param integer $contract_id
     * @param boolean
     */
    private function InsertContractItems($invoice_id, $contract_id, $is_purchase)
    {
        $amount_fld = "ci.amount";
        ## Only set warranty amount for purchase line items
        if ($is_purchase)
            $amount_fld = "CASE ci.item_code WHEN 'SRV-WARRANTY' THEN ci.amount ELSE 0.0 END";


        $this->dbh->exec("
		INSERT INTO invoice_generated_line_items
			(invoice_id, item_id, description, qty, unit_price, uom)
		SELECT
			$invoice_id, trim(ci.item_code), p.name, COUNT(ci.*), AVG($amount_fld), 'EA'
		FROM contract_line_item ci
		INNER JOIN products p ON ci.item_code = p.code
		AND (ci.date_removed IS NULL OR ci.date_removed > CURRENT_DATE)
		WHERE ci.contract_id = $contract_id
		GROUP BY 1, 2, 3, 6");
    }

    /**
     * Add records into invoice_generated_line_items from loaner items
     *
     * @param integer $invoice_id
     * @param integer $contract_id
     */
    private function InsertLoanerItems($invoice_id, $contract_id)
    {
        $this->dbh->exec("
		INSERT INTO invoice_generated_line_items
			(invoice_id, item_id, description, qty, unit_price, uom)
		SELECT
			$invoice_id, trim(coalesce(p.code, ci.item_code)), coalesce(p.name, 'MISSING LNR CODE'), count(ci.*), la.daily_rate, 'EA'
		FROM contract_line_item ci
		INNER JOIN service_item_to_product sitp ON ci.item_code = sitp.code
		LEFT JOIN products p ON sitp.loaner_item = p.code
		LEFT OUTER JOIN loaner_agreement la
			ON ci.contract_id = la.contract_id AND la.active = true
		WHERE ci.contract_id = $contract_id
		AND (ci.date_removed IS NULL OR ci.date_removed > CURRENT_DATE)
		GROUP BY 1, 2, 3, 5, 6");
    }
}

?>