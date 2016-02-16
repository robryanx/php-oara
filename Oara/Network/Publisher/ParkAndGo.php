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
    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;

    private $_apiKey = null;
    private $_agent = null;

    /**
     * Constructor and Login
     * @param $credentials
     * @return SkyScanner
     */
    public function __construct($credentials)
    {
        $this->_credentials = $credentials;
        self::logIn();

    }

    private function logIn()
    {

        $valuesLogin = array(
            new \Oara\Curl\Parameter('agentcode', $this->_credentials['user']),
            new \Oara\Curl\Parameter('pword', $this->_credentials['password']),
        );

        $loginUrl = 'https://www.parkandgo.co.uk/agents/';
        $this->_client = new \Oara\Curl\Access($loginUrl, $valuesLogin, $this->_credentials);


    }

    /**
     * Check the connection
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

        $urls[] = new \Oara\Curl\Request('https://www.parkandgo.co.uk/agents/', $valuesLogin);
        $exportReport = $this->_client->post($urls);
        if (!preg_match("/Produce Report/", $exportReport[0], $match)) {
            $connection = false;
        }

        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getMerchantList()
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
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {

        $totalTransactions = array();

        $urls = array();
        $exportParams = array(
            new \Oara\Curl\Parameter('agentcode', $this->_credentials['user']),
            new \Oara\Curl\Parameter('pword', $this->_credentials['password']),
            new \Oara\Curl\Parameter('fromdate', $dStartDate->toString("dd-MM-yyyy")),
            new \Oara\Curl\Parameter('todate', $dEndDate->toString("dd-MM-yyyy")),
            new \Oara\Curl\Parameter('rqtype', "report")
        );
        $urls[] = new \Oara\Curl\Request('https://www.parkandgo.co.uk/agents/', $exportParams);
        $exportReport = $this->_client->post($urls);

        $today = new \DateTime();
        $today->setHour(0);
        $today->setMinute(0);

        $exportData = str_getcsv($exportReport [0], "\n");
        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {

            $transactionExportArray = str_getcsv($exportData [$i], ",");

            $arrivalDate = new \DateTime ($transactionExportArray [3], 'yyyy-MM-dd 00:00:00', 'en');

            $transaction = Array();
            $transaction ['merchantId'] = 1;
            $transaction ['unique_id'] = $transactionExportArray [0];
            $transactionDate = new \DateTime ($transactionExportArray [2], 'yyyy-MM-dd 00:00:00', 'en');
            $transaction ['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
            unset ($transactionDate);
            $transaction ['status'] = \Oara\Utilities::STATUS_PENDING;
            if ($today > $arrivalDate) {
                $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            }

            $transaction ['amount'] = \Oara\Utilities::parseDouble($transactionExportArray [6]) / 1.2;
            $transaction ['commission'] = \Oara\Utilities::parseDouble($transactionExportArray [7]) / 1.2;

            $totalTransactions [] = $transaction;
        }


        return $totalTransactions;
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

}