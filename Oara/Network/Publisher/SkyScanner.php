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
 * @category   SkyScanner
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class SkyScanner extends \Oara\Network
{

    private $_credentials = null;
    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;

    private $_apiKey = null;

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
            new \Oara\Curl\Parameter('RememberMe', "false"),
            new \Oara\Curl\Parameter('ApiKey', $this->_credentials['user']),
            new \Oara\Curl\Parameter('PortalKey', $this->_credentials['password']),
        );

        $loginUrl = 'http://business.skyscanner.net/portal/en-GB/SignIn';
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
        $urls[] = new \Oara\Curl\Request('http://business.skyscanner.net/portal/en-GB/UK/Report/Show', array());
        $exportReport = $this->_client->get($urls);
        if (!preg_match("/encrypedApiKey: \"(.*)?\",/", $exportReport[0], $match)) {
            $connection = false;
        } else {
            $this->_apiKey = $match[1];
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
        $obj['name'] = "SkyScanner";
        $obj['url'] = "http://www.skyscanneraffiliate.net";
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

        $url = 'http://business.skyscanner.net/apiservices/reporting/v1.0/reportdata/' . $dStartDate->toString("yyyy-MM-dd") . '/' . $dEndDate->toString("yyyy-MM-dd") . '?encryptedApiKey=' . $this->_apiKey . "&type=csv";
        $urls[] = new \Oara\Curl\Request($url, array());

        $exportReport = array();
        $exportReport = $this->_client->get($urls);
        $dump = var_export($exportReport[0], true);
        $dump = preg_replace('/ \. /', "", $dump);
        $dump = preg_replace("/\"\\\\0\"/", "", $dump);
        $dump = preg_replace("/'/", "", $dump);

        $exportData = str_getcsv($dump, "\n");

        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {

            $transactionExportArray = str_getcsv($exportData[$i], ",");
            $transaction = Array();
            $transaction['merchantId'] = 1;
            $transactionDate = new \DateTime($transactionExportArray[0], 'dd/MM/yyyy HH:mm:ss', 'en');
            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
            //unset($transactionDate);
            $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            $transaction['amount'] = (double)$transactionExportArray[9];
            $transaction['commission'] = (double)$transaction['amount'] * 0.6;

            if ($transaction['amount'] != 0) {
                $totalTransactions[] = $transaction;
            }

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