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
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   ParkAndGo
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class ParkAndGo extends \Oara\Network
{

    private $_credentials = null;
    private $_client = null;


    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access ($credentials);

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
        $parameter["description"] = "Password to Log in";
        $parameter["required"] = true;
        $parameter["name"] = "Password";
        $credentials["password"] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        //If not login properly the construct launch an exception
        $connection = true;
        $urls = array();

        $valuesLogin = array(
            new \Oara\Curl\Parameter('agentcode', $this->_credentials['user']),
            new \Oara\Curl\Parameter('pword', $this->_credentials['password']),
        );

        $loginUrl = 'https://www.parkandgo.co.uk/agents/';
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $exportReport = $this->_client->post($urls);
        if (!\preg_match("/Produce Report/", $exportReport[0], $match)) {
            $connection = false;
        }

        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();

        $obj = array();
        $obj['cid'] = "1";
        $obj['name'] = "Park And Go";
        $obj['url'] = "http://www.parkandgo.co.uk";
        $merchants[] = $obj;

        return $merchants;
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

        $urls = array();
        $exportParams = array(
            new \Oara\Curl\Parameter('agentcode', $this->_credentials['user']),
            new \Oara\Curl\Parameter('pword', $this->_credentials['password']),
            new \Oara\Curl\Parameter('fromdate', $dStartDate->format("d-m-Y")),
            new \Oara\Curl\Parameter('todate', $dEndDate->format("d-m-Y")),
            new \Oara\Curl\Parameter('rqtype', "report")
        );
        $urls[] = new \Oara\Curl\Request('https://www.parkandgo.co.uk/agents/', $exportParams);
        $exportReport = $this->_client->post($urls);

        $today = new \DateTime();
        $today->setTime(0,0);

        $exportData = \str_getcsv($exportReport [0], "\n");
        $num = \count($exportData);
        for ($i = 1; $i < $num; $i++) {

            $transactionExportArray = \str_getcsv($exportData [$i], ",");
            $arrivalDate = $transactionExportArray [3]." 00:00:00";
            $transaction = Array();
            $transaction ['merchantId'] = 1;
            $transaction ['unique_id'] = $transactionExportArray [0];
            $transaction ['date'] = $transactionExportArray [2]." 00:00:00";
            $transaction ['status'] = \Oara\Utilities::STATUS_PENDING;
            if ($today > $arrivalDate) {
                $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            }
            $transaction ['amount'] = \Oara\Utilities::parseDouble($transactionExportArray [6] / 1.2) ;
            $transaction ['commission'] = \Oara\Utilities::parseDouble($transactionExportArray [7] / 1.2) ;
            $totalTransactions [] = $transaction;
        }


        return $totalTransactions;
    }

}