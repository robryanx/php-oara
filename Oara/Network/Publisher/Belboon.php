<?php
namespace Oara\Network\Publisher;
    /**
     * The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
     * of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
     *
     * Copyright (C) 2016  Fubra Limited
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU Affero General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or any later version.
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU Affero General Public License for more details.
     * You should have received a copy of the GNU Affero General Public License
     * along with this program.  If not, see <http://www.gnu.org/licenses/>.
     *
     * Contact
     * ------------
     * Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
     **/
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Belboon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Belboon extends \Oara\Network
{
    /**
     * Soap client.
     */
    private $_client = null;
    /**
     * Platform list.
     */
    private $_platformList = null;
    /*
     * User
     */
    private $_user = null;
    /*
     * User
     */
    private $_password = null;

    /**
     * Constructor.
     * @param $affilinet
     * @return An_Api
     */
    public function __construct($credentials)
    {
        $this->_user = $credentials['user'];
        $this->_password = $credentials['apiPassword'];

    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = true;
        self::Login();
        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchantList = array();
        foreach ($this->_platformList as $platform) {
            $result = $this->_client->getPrograms($platform["id"], null, utf8_encode('PARTNERSHIP'), null, null, null, 0);
            foreach ($result->handler->programs as $merchant) {
                $obj = array();
                $obj["name"] = $merchant["programname"];
                $obj["cid"] = $merchant["programid"];
                $obj["url"] = $merchant["advertiserurl"];
                $merchantList[] = $obj;
            }


        }


        return $merchantList;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();

        $result = $this->_client->getEventList(null, null, null, null, null, $dStartDate->toString("YYYY-MM-dd"), $dEndDate->toString("YYYY-MM-dd"), null, null, null, null, 0);


        foreach ($result->handler->events as $event) {
            if (in_array($event["programid"], $merchantList)) {


                $transaction = Array();
                $transaction['unique_id'] = $event["eventid"];
                $transaction['merchantId'] = $event["programid"];
                $transaction['date'] = $event["eventdate"];

                if ($event["subid"] != null) {
                    $transaction['custom_id'] = $event["subid"];
                    if (preg_match("/subid1=/", $transaction['custom_id'])) {
                        $transaction['custom_id'] = str_replace("subid1=", "", $transaction['custom_id']);
                    }
                }

                if ($event["eventstatus"] == 'APPROVED') {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else
                    if ($event["eventstatus"] == 'PENDING') {
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else
                        if ($event["eventstatus"] == 'REJECTED') {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        }

                $transaction['amount'] = $event["netvalue"];

                $transaction['commission'] = $event["eventcommission"];
                $totalTransactions[] = $transaction;
            }
        }

        return $totalTransactions;
    }

    /**
     * Log in the API and get the data.
     */
    public function Login()
    {
        //Setting the client.

        $oSmartFeed = new Zend_Soap_Client("http://smartfeeds.belboon.com/SmartFeedServices.php?wsdl");

        $oSessionHash = $oSmartFeed->login($this->_user, $this->_password);

        $this->_client = new SoapClient('http://api.belboon.com/?wsdl', array('login' => $this->_user, 'password' => $this->_password, 'trace' => true));
        $result = $this->_client->getAccountInfo();


        if (!$oSessionHash->HasError) {

            $sSessionHash = $oSessionHash->Records['sessionHash'];

            $aResult = $oSmartFeed->getPlatforms($sSessionHash);
            $platformList = array();
            foreach ($aResult->Records as $record) {
                if ($record['status'] == "active") {
                    $platformList[] = $record;
                }
            }
            $this->_platformList = $platformList;
        }


    }

    /**
     * (non-PHPdoc)
     * @see Oara/Network/Base#getPaymentHistory()
     */

    public function getPaymentHistory()
    {
        $paymentHistory = array();

        return $paymentHistory;
    }


    /**
     * Calculate the number of iterations needed
     * @param $rowAvailable
     * @param $rowsReturned
     */
    private function calculeIterationNumber($rowAvailable, $rowsReturned)
    {
        $iterationDouble = (double)($rowAvailable / $rowsReturned);
        $iterationInt = (int)($rowAvailable / $rowsReturned);
        if ($iterationDouble > $iterationInt) {
            $iterationInt++;
        }
        return $iterationInt;
    }
}
