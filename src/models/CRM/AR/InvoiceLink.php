<?php

/**
 * This class will attempt to create a foreign key referencing invoice_current
 * invoices to the invoice_generated invoice.
 *
 * @package Freedom
 * @author Aron Vargas
 */

require_once 'classes/DataStor.php';

class InvoiceLink {
    /**
     * @var object Database handler.
     */
    protected $dbh;

    /**
     * @var string Month scope in which to attempt to link invoices.
     */
    protected $month;

    /**
     * @var integer Batch ID in which to attempt to link invoices.
     */
    protected $batch_id;

    public function __construct()
    {
        $this->dbh = DataStor::getHandle();
    }

    public function setMonth($month)
    {
        $this->month = date('Y-m-01', strtotime($month));
    }

    public function getMonth()
    {
        return $this->month;
    }

    public function setBatchId($batch_id)
    {
        $this->batch_id = (int) $batch_id;
    }

    public function getBatchId()
    {
        return $this->batch_id;
    }

    public function findDefiniteMatches()
    {
        $month_clause = '';
        if ($month = $this->getMonth())
        {
            $month_clause = "AND date_trunc('month', ib.invoice_date::TIMESTAMP) = date_trunc('month', '{$month}'::TIMESTAMP)";
        }

        $batch_id_clause = '';
        if ($batch_id = $this->getBatchId())
        {
            $batch_id_clause = "AND ib.id = {$batch_id}";
        }

        $sql = <<<SQL
SELECT
    ib.id AS batch_id,
    ib.invoice_date,
    ig.id AS id,
    min(ic.invoice_num) AS invoice_num,
    count(ic.*) AS count
FROM invoice_batch ib
JOIN invoice_generated ig ON ig.batch_id = ib.id
JOIN (
    SELECT
        invoice_num,
        CASE
            WHEN contract_id IS NULL AND trancmnt LIKE 'Shipping Cost for Contract%'
            THEN split_part(trancmnt, 'Contract ', 2)::INT
            ELSE contract_id
        END AS contract_id,
        trancmnt AS comment,
        invoice_generated_id
    FROM invoice_current
    WHERE trancmnt IS NOT NULL
) ic ON
    ig.contract_id = ic.contract_id
    AND ig.comment = ic.comment
WHERE
    ic.invoice_generated_id IS NULL
    {$month_clause}
    {$batch_id_clause}
GROUP BY
    ib.id,
    ib.invoice_date,
    ig.id
ORDER BY count DESC
SQL;

        $sth = $this->dbh->query($sql);
        $matches = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach ($matches as $i => $match)
        {
            if ($match['count'] > 1)
            {
                unset($matches[$i]);
            }
            else
            {
                break;
            }
        }

        return array_merge($matches);
    }

    public function findIndefiniteMatches()
    {
        $month_clause = '';
        if ($month = $this->getMonth())
        {
            $month_clause = "AND date_trunc('month', ib.invoice_date::TIMESTAMP) = date_trunc('month', '{$month}'::TIMESTAMP)";
        }

        $batch_id_clause = '';
        if ($batch_id = $this->getBatchId())
        {
            $batch_id_clause = "AND ib.id = {$batch_id}";
        }

        $sql = <<<SQL
SELECT
    ib.id AS batch_id,
    ib.invoice_date,
    ig.contract_id,
    ig.comment,
    array_to_string(array_accum(ig.id), ',') AS ids,
    array_to_string(array_accum(ic.invoice_num), ',') AS invoice_nums,
    count(ic.*) AS count
FROM invoice_batch ib
JOIN invoice_generated ig ON ig.batch_id = ib.id
JOIN (
    SELECT
        invoice_num,
        CASE
            WHEN contract_id IS NULL AND trancmnt LIKE 'Shipping Cost for Contract%'
            THEN split_part(trancmnt, 'Contract ', 2)::INT
            ELSE contract_id
        END AS contract_id,
        trancmnt AS comment,
        invoice_generated_id
    FROM invoice_current
    WHERE trancmnt IS NOT NULL
) ic ON
    ig.contract_id = ic.contract_id
    AND ig.comment = ic.comment
WHERE
    ic.invoice_generated_id IS NULL
    {$month_clause}
    {$batch_id_clause}
GROUP BY
    ib.id,
    ib.invoice_date,
    ig.contract_id,
    ig.comment
ORDER BY count DESC
SQL;

        $sth = $this->dbh->query($sql);
        $matches = $sth->fetchAll(PDO::FETCH_ASSOC);

        $indefinite_matches = array();

        foreach ($matches as $match)
        {
            if ($match['count'] == 1)
            {
                break;
            }

            $ids = array_unique(explode(',', $match['ids']));
            sort($ids);

            $invoice_nums = array_unique(explode(',', $match['invoice_nums']));
            sort($invoice_nums);

            foreach ($invoice_nums as $i => $invoice_num)
            {
                $id = (count($ids) == 1) ? $ids[0] : $ids[$i];
                $indefinite_matches[] = array(
                    'id' => $id,
                    'invoice_num' => $invoice_num
                );
            }
        }

        return $indefinite_matches;
    }

    public function linkMatches(array $matches = array())
    {
        $this->dbh->beginTransaction();

        $sth = $this->dbh->prepare('UPDATE invoice_current SET invoice_generated_id = ? WHERE invoice_num = ?');

        foreach ($matches as $match)
        {
            $sth->bindValue(1, $match['id'], PDO::PARAM_INT);
            $sth->bindValue(2, $match['invoice_num'], PDO::PARAM_INT);

            try
            {
                $sth->execute();
            }
            catch (PDOException $e)
            {
                break;
            }
        }

        try
        {
            $this->dbh->commit();
        }
        catch (PDOException $e)
        {
            $this->dbh->rollback();
        }
    }

    public function run()
    {
        $definite_matches = $this->findDefiniteMatches();
        $indefinite_matches = $this->findIndefiniteMatches();
        $matches = array_merge($definite_matches, $indefinite_matches);

        $this->linkMatches($matches);
    }
}
