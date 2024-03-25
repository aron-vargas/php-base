<?php

/**
 * @package Freedom
 */

/**
 * This class represents a transaction for a {@link LeaseAsset}.
 *
 * @author Aron Vargas
 * @package Freedom
 */
class LeaseAssetTransaction extends AssetTransaction {
    /**
     * @var integer the {@link LeaseAsset} database id
     */
    private $lease_asset_id;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var CustomerEntity this is called a facility, but it is really any type
     * of customer, including DME patients
     */
    private $facility;

    /**
     * @var string the status of the equipment
     */
    private $status;

    /**
     * @var string the substatus of the equipment (only for some status options)
     */
    private $substatus;

    /**
     * @var User the user that created the transaction
     */
    private $user;

    /**
     * @var User the user that created the transaction
     */
    private $realtime;

    /**
     * Status codes
     */
    public static $FGI = "FGI";
    public static $OEM = "OEM";
    public static $OUT_OF_SERVICE = "Out of Service";
    public static $PACK = "Pack";
    public static $PLACED = "Placed";
    public static $QUARANTINE = "Quarantine";
    public static $RECEIVED = "Received";
    public static $TRANSIT = "In Transit";
    public static $WIP = "WIP";

    /**
     * Substatus codes
     */
    public static $CXL = "CXL";
    public static $HOLD = "Hold";
    public static $LOAN = "Loan";
    public static $SWAP = "Swap";
    public static $RTN = "RTN";
    public static $SCRAPPED = "Scrapped";
    public static $LOST = "Lost";
    public static $PERM_LOAN = "Perm Loan";
    public static $LEASE = "Lease";
    public static $RENTAL = "Rental";
    public static $PURCHASE = "Purchase";
    public static $PURCHASE_OOS = "Purchase-OOS";
    public static $CONSIGNMENT = "Consignment";
    public static $UPGRADED = "Upgraded";
    public static $WRONG_MODEL_SERIAL = "Wrong Model/Serial Number";


    /**
     * Creates a new LeaseAssetTransaction object.
     *
     * @param integer $lease_asset_id
     * @param integer $tstamp
     * @param integer $facility_id
     * @param string $status
     * @param string $substatus
     */
    public function __construct($lease_asset_id, $tstamp, $realtime, $user,
        $facility_id = null, $status = null, $substatus = null, $comment = null)
    {
        $this->lease_asset_id = $lease_asset_id;
        $this->timestamp = $tstamp;
        $this->realtime = $realtime;
        $this->user = $user;
        $this->comment = $comment;

        # If a status wasn't passed in, we can assume we need to query the
        # database to create this object.
        #
        if (is_null($status))
        {
            $dbh = DataStor::getHandle();

            $sth = $dbh->prepare("
				SELECT facility_id,
				       status,
				       substatus
				FROM lease_asset_transaction
				WHERE lease_asset_id = ? AND
				      EXTRACT(EPOCH FROM DATE_TRUNC('second', tstamp)) = ?");
            $sth->bindValue(1, $lease_asset_id, PDO::PARAM_INT);
            $sth->bindValue(2, $tstamp, PDO::PARAM_INT);
            $sth->execute();

            list($facility_id,
                $this->status,
                $this->substatus) = $sth->fetch(PDO::FETCH_NUM);

            if (!is_null($facility_id))
                $this->facility = new CustomerEntity($facility_id);
            else
                $this->facility = null;
        }
        #
        # Otherwise, just create the object from the parameters.
        #
        else
        {
            if (is_null($facility_id))
                $this->facility = null;
            else
                $this->facility = new CustomerEntity($facility_id);


            $this->status = $status;
            $this->substatus = $substatus;
        }
    }

    /**
     * Changes one field in the database and reloads the object.
     *
     * @param string $field
     * @param mixed $value
     * @throws Exception
     */
    public function change($field, $value)
    {
        if ($this->lease_asset_id && $this->tstamp)
        {
            if (is_null($this->dbh))
                $this->dbh = DataStor::getHandle();

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

            $sth = $this->dbh->prepare("UPDATE lease_asset_transaction SET {$field} = ?
			WHERE lease_asset_id = ? and EXTRACT(EPOCH FROM DATE_TRUNC('second', tstamp)) = ?");
            $sth->bindValue(1, $value, $val_type);
            $sth->bindValue(2, $this->lease_asset_id, PDO::PARAM_INT);
            $sth->bindValue(3, $this->tstamp, PDO::PARAM_INT);
            $sth->execute();
            $this->{$field} = $value;
        }
        else
        {
            throw new Exception('Cannot update a non-existant record.');
        }
    }

    /**
     * Remove db record
     *
     * @throws Exception
     */
    public function delete()
    {
        if ($this->lease_asset_id && $this->timestamp)
        {
            $dbh = DataStor::getHandle();
            $sth = $dbh->prepare("DELETE FROM lease_asset_transaction
			WHERE lease_asset_id = ?
			AND EXTRACT(EPOCH FROM DATE_TRUNC('second', tstamp)) = ?");
            $sth->bindValue(1, $this->lease_asset_id, PDO::PARAM_INT);
            $sth->bindValue(2, $this->timestamp, PDO::PARAM_INT);
            $sth->execute();
        }
        else
        {
            throw new Exception('Cannot update a non-existant record.');
        }
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @return CustomerEntity
     */
    public function getFacility()
    {
        return $this->facility;
    }


    /**
     * @return integer
     */
    public function getLeaseAssetId()
    {
        return $this->lease_asset_id;
    }


    /**
     * @return string
     */
    public function getRealtime()
    {
        return $this->realtime;
    }


    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }


    /**
     * @return string
     */
    public function getSubStatus()
    {
        return $this->substatus;
    }


    /**
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }


    /**
     * Returns the user that created this transaction.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     * Returns an array of the next possible status options given the current
     * status.
     *
     * @param string $old_status
     * @return array
     */
    public static function getRestrictedStatusOptions($old_status)
    {
        switch ($old_status)
        {
            case self::$WIP:
                return array(self::$PACK, self::$OUT_OF_SERVICE);
            case self::$OEM:
                return array(self::$FGI);
            case self::$FGI:
                return array(self::$PLACED);
            case self::$PLACED:
                return array(self::$TRANSIT);
            case self::$TRANSIT:
                return array(self::$RECEIVED, self::$QUARANTINE);
            case self::$RECEIVED:
                return array(self::$WIP);
            case self::$PACK:
                return array(self::$FGI);
            case self::$QUARANTINE:
                return array(self::$WIP);
            case self::$OUT_OF_SERVICE:
                return array();
            default:
                return array(
                    self::$WIP, self::$OEM, self::$FGI, self::$PLACED,
                    self::$TRANSIT, self::$QUARANTINE, self::$RECEIVED, self::$PACK);
        }
    }


    /**
     * Returns an array of valid substatus options for the given status.
     *
     * @param string $status
     * @return array
     */
    public static function getRestrictedSubStatusOptions($status, $customer_owned = false)
    {
        switch ($status)
        {
            case self::$TRANSIT:
                return array(self::$CXL, self::$LOAN, self::$SWAP, self::$RTN);
            case self::$OUT_OF_SERVICE:
                return array(self::$SCRAPPED, self::$LOST, self::$PERM_LOAN, self::$UPGRADED, self::$WRONG_MODEL_SERIAL, self::$PURCHASE_OOS);
            case self::$PLACED:
                if ($customer_owned)
                    return array(self::$LOAN, self::$PURCHASE, self::$PURCHASE_OOS, self::$CONSIGNMENT);
                else
                    return array(self::$LEASE, self::$RENTAL, self::$LOAN, self::$CONSIGNMENT);
            case self::$WIP:
                return array(self::$HOLD);
            case self::$OEM:
                return array(self::$TRANSIT, self::$RECEIVED);
            default:
                return array();
        }
    }


    /**
     * Returns an array of the status options.
     *
     * @param string $current_status
     * @return array
     */
    public static function getStatusOptions($current_status = '')
    {
        if ($current_status == self::$OUT_OF_SERVICE)
        {
            return array(
                self::$OUT_OF_SERVICE, self::$WIP, self::$FGI, self::$PLACED,
                self::$TRANSIT, self::$QUARANTINE, self::$OEM, self::$RECEIVED, self::$PACK
            );
        }
        elseif ($current_status == self::$OEM)
        {
            return array(
                self::$OEM, self::$WIP, self::$FGI, self::$PLACED,
                self::$TRANSIT, self::$QUARANTINE, self::$RECEIVED, self::$PACK
            );
        }
        else
        {
            return array(
                self::$OEM, self::$WIP, self::$FGI, self::$PLACED, self::$TRANSIT,
                self::$QUARANTINE, self::$RECEIVED, self::$PACK
            );
        }
    }


    /**
     * Returns an array of the substatus options.
     *
     * @return array
     */
    public static function getSubStatusOptions()
    {
        return array(
            self::$CXL, self::$LOAN, self::$SWAP, self::$RTN, self::$SCRAPPED, self::$LOST, self::$PERM_LOAN,
            self::$RECEIVED, self::$LEASE, self::$RENTAL, self::$PURCHASE, self::$PURCHASE_OOS, self::$CONSIGNMENT,
            self::$HOLD, self::$TRANSIT, self::$UPGRADED, self::$WRONG_MODEL_SERIAL
        );
    }

    /**
     * Add a comment with date and user information to the transaction
     * Used to add comments without changing other detials of the
     * asset status
     *
     * @param string
     */
    public function PrependComment($new_comment)
    {
        global $user;

        if ($this->lease_asset_id && $this->realtime)
        {
            $today = date('Y-m-d');

            $comment = "{$user->getName()} on $today: $new_comment\n{$this->comment}";

            $dbh = DataStor::getHandle();
            $sth = $dbh->prepare("UPDATE lease_asset_transaction
			SET \"comment\" = ?
			WHERE lease_asset_id = ? AND tstamp = ?");
            $sth->bindValue(1, $comment, PDO::PARAM_STR);
            $sth->bindValue(2, $this->lease_asset_id, PDO::PARAM_INT);
            $sth->bindValue(3, $this->realtime, PDO::PARAM_STR);
            $sth->execute();
        }
    }
}

?>