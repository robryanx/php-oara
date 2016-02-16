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
 * @category   AffiliatesUnited
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class AffiliatesUnited extends \Oara\Network
{

    /**
     * Merchants by name
     */
    private $_merchantMap = array();
    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;

    /**
     * Constructor and Login
     * @param $credentials
     * @return Daisycon
     */
    public function __construct($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];

        $valuesLogin = array(
            new \Oara\Curl\Parameter('us', $user),
            new \Oara\Curl\Parameter('pa', $password)
        );

        $loginUrl = 'https://affiliates.affutd.com/affiliates/Login.aspx';
        $this->_client = new \Oara\Curl\Access($loginUrl, $valuesLogin, $credentials);
    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        //If not login properly the construct launch an exception
        $connection = false;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://affiliates.affutd.com/affiliates/Dashboard.aspx', array());
        $exportReport = $this->_client->get($urls);

        $dom = new Zend_Dom_Query($exportReport[0]);
        $results = $dom->query('.lnkLogOut');
        if (count($results) > 0) {
            $connection = true;
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
        $obj['cid'] = 1;
        $obj['name'] = "Affiliates United";
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
        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('ctl00$cphPage$reportFrom', $dStartDate->toString("yyyy-MM-dd"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('ctl00$cphPage$reportTo', $dEndDate->toString("yyyy-MM-dd"));

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://affiliates.affutd.com/affiliates/DataServiceWrapper/DataService.svc/Export/CSV/Affiliates_Reports_GeneralStats_DailyFigures', $valuesFromExport);
        $exportReport = $this->_client->post($urls);
        $exportData = str_getcsv($exportReport[0], "\n");
        $num = count($exportData);
        for ($i = 2; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i], ",");

            $transaction = Array();
            $transaction['merchantId'] = 1;
            $transactionDate = new \DateTime($transactionExportArray[0], 'dd-MM-yyyy', 'en');
            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

            $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;

            $transaction['amount'] = $transactionExportArray[12];
            $transaction['commission'] = $transactionExportArray[13];
            $totalTransactions[] = $transaction;
        }

        return $totalTransactions;
    }

}
