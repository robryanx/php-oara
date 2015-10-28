<?php
/**
 * The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 * of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
 *
 * Copyright (C) 2014  Fubra Limited
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
 * @category   Oara_Network_Publisher_St
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_SilverTap extends Oara_Network
{

    /**
     * Export Merchant Parameters
     * @var array
     */
    private $_exportMerchantParameters = null;
    /**
     * Export Transaction Parameters
     * @var array
     */
    private $_exportTransactionParameters = null;
    /**
     * Export Overview Parameters
     * @var array
     */
    private $_exportOverviewParameters = null;

    /**
     * Merchant Map
     * @var array
     */
    private $_merchantMap = array();
    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;
    /**
     * Server Url
     * @var unknown_type
     */
    private $_serverUrl = null;

    /**
     * @param $credentials
     * @throws Exception
     */
    public function __construct($credentials)
    {

        $user = $credentials['user'];
        $password = $credentials['password'];
        $report = null;
        if ($credentials['network'] == "SilverTap") {
            $this->_serverUrl = "https://mats.silvertap.com/";
            $report = 'AMSCommission_Breakdown';

        } else
            if ($credentials['network'] == "BrandConversions") {
                $this->_serverUrl = "https://mats.brandconversions.com/";
                $report = 'BCCommission_Breakdown';
            }

        $loginUrl = $this->_serverUrl . 'Login.aspx?ReturnUrl=/';
        $valuesLogin = array(new Oara_Curl_Parameter('txtUsername', $user),
            new Oara_Curl_Parameter('txtPassword', $password),
            new Oara_Curl_Parameter('cmdSubmit', 'Login'),
            new Oara_Curl_Parameter('__EVENTTARGET', ''),
            new Oara_Curl_Parameter('__EVENTARGUMENT', '')
        );

        $this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
        $exportPassword = md5($password);
        $exportUser = self::getExportUser();

        $this->_exportTransactionParameters = array(new Oara_Curl_Parameter('user', $exportUser),
            new Oara_Curl_Parameter('pwd', $exportPassword),
            new Oara_Curl_Parameter('report', $report),
            new Oara_Curl_Parameter('groupby', 'Programme'),
            new Oara_Curl_Parameter('groupdate', 'Day'),
            new Oara_Curl_Parameter('creative', ''),
            new Oara_Curl_Parameter('CommOnly', '1'),
            new Oara_Curl_Parameter('showimpressions', 'True'),
            new Oara_Curl_Parameter('showclicks', 'True'),
            new Oara_Curl_Parameter('showreferrals', 'True'),
            new Oara_Curl_Parameter('showtransactionvalues', 'True'),
            new Oara_Curl_Parameter('sort', 'Date asc'),
            new Oara_Curl_Parameter('format', 'csv'),
        );
        $this->_exportOverviewParameters = array(new Oara_Curl_Parameter('user', $exportUser),
            new Oara_Curl_Parameter('pwd', $exportPassword),
            new Oara_Curl_Parameter('report', 'Performance'),
            new Oara_Curl_Parameter('groupby', 'Merchant'),
            new Oara_Curl_Parameter('groupdate', 'Day'),
            new Oara_Curl_Parameter('creative', ''),
            new Oara_Curl_Parameter('CommOnly', '1'),
            new Oara_Curl_Parameter('showimpressions', 'True'),
            new Oara_Curl_Parameter('showclicks', 'True'),
            new Oara_Curl_Parameter('showreferrals', 'True'),
            new Oara_Curl_Parameter('showtransactionvalues', 'True'),
            new Oara_Curl_Parameter('sort', 'Date asc'),
            new Oara_Curl_Parameter('format', 'csv')
        );


        $this->_exportMerchantParameters = array(new Oara_Curl_Parameter('user', $exportUser),
            new Oara_Curl_Parameter('pwd', $exportPassword),
            new Oara_Curl_Parameter('type', 'csv'),
        );
    }

    /**
     * Sets up the merchant list and the program list.
     */
    private function getExportUser()
    {
        $exporUser = null;

        $urls = array();
        $urls[] = new Oara_Curl_Request($this->_serverUrl . 'Reports/Default.aspx?', array(new Oara_Curl_Parameter('report', 'Performance')));
        $result = $this->_client->get($urls);

        $urls = array();
        $urls[] = new Oara_Curl_Request($this->_serverUrl . '/Reports/RemoteHelp.aspx?', array());
        $result = $this->_client->get($urls);

        /*** load the html into the object ***/
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->validateOnParse = true;
        $doc->loadHTML($result[0]);
        $textareaList = $doc->getElementsByTagName('textarea');

        $messageNode = $textareaList->item(0);
        if (!isset($messageNode->firstChild)) {
            throw new Exception('Error getting the User');
        }
        $messageStr = $messageNode->firstChild->nodeValue;

        $parseUrl = parse_url(trim($messageStr));
        $parameters = explode('&', $parseUrl['query']);
        foreach ($parameters as $parameter) {
            $parameterValue = explode('=', $parameter);
            if ($parameterValue[0] == 'user') {
                $exporUser = $parameterValue[1];
            }
        }
        return $exporUser;
    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = true;
        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = Array();

        $urls = array();
        $urls[] = new Oara_Curl_Request($this->_serverUrl . 'Feeds/Merchantfeed.aspx?', $this->_exportMerchantParameters);
        $result = $this->_client->get($urls);

        $exportData = str_getcsv($result[0], "\n");
        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionMerchantArray = str_getcsv($exportData[$i], ",");
            $obj = Array();
            $obj['cid'] = $transactionMerchantArray[4];
            $obj['name'] = "$transactionMerchantArray[1] ($transactionMerchantArray[5])";
            $merchants[] = $obj;
        }
        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null)
    {
        $totalTransactions = Array();
        $startDate = $dStartDate->toString('dd/MM/yyyy');
        $endDate = $dEndDate->toString('dd/MM/yyyy');

        $valueIndex = 9;
        $commissionIndex = 16;
        $statusIndex = 17;
        if ($this->_serverUrl == "https://mats.brandconversions.com/") {
            $valueIndex = 11;
            $commissionIndex = 17;
            $statusIndex = 18;
        }

        $valuesFormExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
        //$valuesFormExport[] = new Oara_Curl_Parameter('merchant', '0');
        $valuesFormExport[] = new Oara_Curl_Parameter('datefrom', $startDate);
        $valuesFormExport[] = new Oara_Curl_Parameter('dateto', $endDate);
        $urls = array();
        $urls[] = new Oara_Curl_Request($this->_serverUrl . 'reports/remote.aspx?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $exportData = str_getcsv($exportReport[0], "\r\n");
        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i], ",");
            if (in_array((int)$transactionExportArray[4], $merchantList)) {
                $transaction = Array();
                $transaction['unique_id'] = \preg_replace('/\D/', '', $transactionExportArray[0]);
                $transaction['merchantId'] = $transactionExportArray[4];
                $transactionDate = new Zend_Date($transactionExportArray[2], "dd/MM/YY HH:mm:ss");
                $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

                if ($transactionExportArray[7] != null) {
                    $transaction['custom_id'] = $transactionExportArray[7];
                }

                if (preg_match('/Unpaid Confirmed/', $transactionExportArray[$statusIndex]) || preg_match('/Paid Confirmed/', $transactionExportArray[$statusIndex])) {
                    $transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
                } else
                    if (preg_match('/Unpaid Unconfirmed/', $transactionExportArray[$statusIndex])) {
                        $transaction['status'] = Oara_Utilities::STATUS_PENDING;
                    } else
                        if (preg_match('/Unpaid Rejected/', $transactionExportArray[$statusIndex])) {
                            $transaction['status'] = Oara_Utilities::STATUS_DECLINED;
                        } else {
                            throw new Exception("No Status supported " . $transactionExportArray[$statusIndex]);
                        }

                $transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[$valueIndex]);
                $transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[$commissionIndex]);
                $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;

    }

    /**
     * (non-PHPdoc)
     * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
     */
    public function getPaymentHistory($currentPage = 1)
    {
        $paymentHistory = array();


        return $paymentHistory;
    }

    /**
     *  It returns the transactions for a payment
     * @see Oara_Network::paymentTransactions()
     */
    public function paymentTransactions($paymentId, $merchantList, $startDate)
    {

        $paymentTransactionList = array();

        return $paymentTransactionList;
    }
}
