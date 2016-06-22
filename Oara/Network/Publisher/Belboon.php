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

    private $_client = null;
    private $_platformList = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['apipassword'];

        //Setting the client.

        $oSmartFeed = new \SoapClient("http://smartfeeds.belboon.com/SmartFeedServices.php?wsdl");
        $oSessionHash = $oSmartFeed->login($user, $password);

        $this->_client = new \SoapClient('http://api.belboon.com/?wsdl', array('login' => $user, 'password' => $password, 'trace' => true));
        $this->_client->getAccountInfo();


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
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "User Log in";
        $parameter["required"] = true;
        $parameter["name"] = "User";
        $credentials["user"] = $parameter;

        $parameter = array();
        $parameter["description"] = "Api Password for Belboon";
        $parameter["required"] = true;
        $parameter["name"] = "Api Password";
        $credentials["password"] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = true;
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchantList = array();
        foreach ($this->_platformList as $platform) {
            $result = $this->_client->getPrograms($platform["id"], null, \utf8_encode('PARTNERSHIP'), null, null, null, 0);
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
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();

        $merchantIdMap = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);

        $result = $this->_client->getEventList(null, null, null, null, null, $dStartDate->format("Y-m-d"), $dEndDate->format("Y-m-d"), null, null, null, null, 0);


        foreach ($result->handler->events as $event) {
            if (isset($merchantIdMap[$event["programid"]])) {

                $transaction = Array();
                $transaction['unique_id'] = $event["eventid"];
                $transaction['merchantId'] = $event["programid"];
                $transaction['date'] = $event["eventdate"];

                if ($event["subid"] != null) {
                    $transaction['custom_id'] = $event["subid"];
                    if (\preg_match("/subid1=/", $transaction['custom_id'])) {
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

                $transaction['amount'] = \Oara\Utilities::parseDouble($event["netvalue"]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($event["eventcommission"]);
                $totalTransactions[] = $transaction;
            }
        }

        return $totalTransactions;
    }

}
