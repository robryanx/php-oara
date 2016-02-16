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
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Buy
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class BuyAt extends \Oara\Network
{
    /**
     * Export Merchants Parameters
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
     * Export Payment Parameters
     * @var array
     */
    private $_exportPaymentParameters = null;

    /**
     *
     * Payment Details Parameters
     * @var unknown_type
     */
    private $_exportPaymentDetailsParameters = null;

    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;

    /**
     * Constructor and Login
     * @param $buy
     * @return Buy_Api
     */
    public function login($credentials)
    {

        $user = $credentials['user'];
        $password = $credentials['password'];
        $passwordApi = md5($password);

        $loginUrl = 'https://users.buy.at/ma/index.php/main/login';

        $contact = null;

        $exportPass = null;

        $valuesLogin = array(new \Oara\Curl\Parameter('email', $user),
            new \Oara\Curl\Parameter('password', $password)
        );

        $this->_client = new \Oara\Curl\Access($credentials);

        $this->_exportMerchantParameters = array(new \Oara\Curl\Parameter('handle', '0'),
            new \Oara\Curl\Parameter('orderby', 'programme_name'),
            new \Oara\Curl\Parameter('dir', 'asc'),
            new \Oara\Curl\Parameter('filter_sector', '0'),
            new \Oara\Curl\Parameter('filter_status', 'y'),
            new \Oara\Curl\Parameter('filter_region', '0'),
            new \Oara\Curl\Parameter('query', ''),
            new \Oara\Curl\Parameter('has_feed', '0'),
            new \Oara\Curl\Parameter('format', 'xml'),
            new \Oara\Curl\Parameter('email', $user),
            new \Oara\Curl\Parameter('password', $passwordApi)
        );

        $this->_exportTransactionParameters = array(new \Oara\Curl\Parameter('daterange', 'CUSTOM'),
            new \Oara\Curl\Parameter('handle', '0'),
            new \Oara\Curl\Parameter('status', ''),
            new \Oara\Curl\Parameter('include_rejected', '0'),
            new \Oara\Curl\Parameter('include_consolidated', '0'),
            new \Oara\Curl\Parameter('include_profit_plus', '1'),
            new \Oara\Curl\Parameter('orderby', 'transaction_date_time'),
            new \Oara\Curl\Parameter('dir', 'asc'),
            new \Oara\Curl\Parameter('showfields%5Bprogramme_name%5D', 'programme_name'),
            new \Oara\Curl\Parameter('showfields%5Bstatus%5D', 'status'),
            new \Oara\Curl\Parameter('showfields%5Blink_id%5D', 'link_id'),
            new \Oara\Curl\Parameter('showfields%5Btransaction_date_time%5D', 'transaction_date_time'),
            new \Oara\Curl\Parameter('showfields%5Bquantity%5D', 'quantity'),
            new \Oara\Curl\Parameter('showfields%5Btransaction_value%5D', 'transaction_value'),
            new \Oara\Curl\Parameter('showfields%5Bcommission%5D', 'commission'),
            new \Oara\Curl\Parameter('showfields%5Bunique_id%5D', 'unique_id'),
            new \Oara\Curl\Parameter('customise', 'Go'),
            new \Oara\Curl\Parameter('format', 'csv'),
            new \Oara\Curl\Parameter('email', $user),
            new \Oara\Curl\Parameter('password', $passwordApi)
        );

        $this->_exportOverviewParameters = array(new \Oara\Curl\Parameter('daterange', 'CUSTOM'),
            new \Oara\Curl\Parameter('handle', '0'),
            new \Oara\Curl\Parameter('status', ''),
            new \Oara\Curl\Parameter('include_rejected', '0'),
            new \Oara\Curl\Parameter('include_consolidated', '0'),
            new \Oara\Curl\Parameter('orderby', 'date'),
            new \Oara\Curl\Parameter('dir', 'asc'),
            new \Oara\Curl\Parameter('showfields%5Bclicks%5D', 'clicks'),
            new \Oara\Curl\Parameter('showfields%5Bsales%5D', 'sales'),
            new \Oara\Curl\Parameter('showfields%5Btransaction_value%5D', 'transaction_value'),
            new \Oara\Curl\Parameter('showfields%5Bcommission%5D', 'commission'),
            new \Oara\Curl\Parameter('customise', 'Go'),
            new \Oara\Curl\Parameter('format', 'csv'),
            new \Oara\Curl\Parameter('email', $user),
            new \Oara\Curl\Parameter('password', $passwordApi)
        );
        $this->_exportPaymentParameters = array(new \Oara\Curl\Parameter('handle', '0'),
            new \Oara\Curl\Parameter('orderby', 'date'),
            new \Oara\Curl\Parameter('dir', 'asc'),
            new \Oara\Curl\Parameter('showfields%5Bpayment_method%5D', 'payment_method'),
            new \Oara\Curl\Parameter('customise', 'Go'),
            new \Oara\Curl\Parameter('format', 'csv'),
            new \Oara\Curl\Parameter('email', $user),
            new \Oara\Curl\Parameter('password', $passwordApi)
        );

        $this->_exportPaymentDetailsParameters = array(
            new \Oara\Curl\Parameter('prog_id', '0'),
            new \Oara\Curl\Parameter('handle', '0'),
            new \Oara\Curl\Parameter('orderby', 'date'),
            new \Oara\Curl\Parameter('dir', 'asc'),
            new \Oara\Curl\Parameter('showfields%5Bprogramme_name%5D', 'programme_name'),
            new \Oara\Curl\Parameter('showfields%5Blink_id%5D', 'link_id'),
            new \Oara\Curl\Parameter('showfields%5Btransaction_value%5D', 'transaction_value'),
            new \Oara\Curl\Parameter('showfields%5Bcommission%5D', 'commission'),
            new \Oara\Curl\Parameter('showfields%5Bunique_id%5D', 'unique_id'),
            new \Oara\Curl\Parameter('&showfields%5Bcommission%5D', 'commission'),
            new \Oara\Curl\Parameter('customise', 'Go'),
            new \Oara\Curl\Parameter('format', 'csv'),
            new \Oara\Curl\Parameter('email', $user),
            new \Oara\Curl\Parameter('password', $passwordApi)
        );

    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["user"]["description"] = "User Log in";
        $parameter["user"]["required"] = true;
        $credentials[] = $parameter;

        $parameter = array();
        $parameter["password"]["description"] = "Password to Log in";
        $parameter["password"]["required"] = true;
        $credentials[] = $parameter;

        return $credentials;
    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;
        $urls = array();
        $valuesFromExport = $this->_exportMerchantParameters;
        $urls[] = new \Oara\Curl\Request('http://users.buy.at/ma/index.php/affiliateProgrammes/programmes?', $valuesFromExport);
        $exportReport = $this->_client->get($urls);
        if (!preg_match("/Password/", $exportReport[0], $matches)) {
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
        $valuesFromExport = $this->_exportMerchantParameters;
        $merchants = Array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://users.buy.at/ma/index.php/affiliateProgrammes/programmes?', $valuesFromExport);

        $exportReport = $this->_client->get($urls);
        echo $exportReport[0];
        $xml = new SimpleXMLElement($exportReport[0]);
        $list = $xml->body->resultset;
        foreach ($list as $merchant) {
            $obj = array();
            $obj['cid'] = (string)$merchant->programme_id;
            $obj['name'] = (string)$merchant->programme_name;
            $obj['url'] = "";
            $merchants[] = $obj;
        }
        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();
        $valuesFromExport = \Oara\Utilities::cloneArray($this->_exportTransactionParameters);
        $valuesFromExport[] = new \Oara\Curl\Parameter('from_year', $dStartDate->get(\DateTime::YEAR));
        $valuesFromExport[] = new \Oara\Curl\Parameter('from_month', $dStartDate->get(\DateTime::MONTH));
        $valuesFromExport[] = new \Oara\Curl\Parameter('from_day', $dStartDate->get(\DateTime::DAY));
        $valuesFromExport[] = new \Oara\Curl\Parameter('to_year', $dEndDate->get(\DateTime::YEAR));
        $valuesFromExport[] = new \Oara\Curl\Parameter('to_month', $dEndDate->get(\DateTime::MONTH));
        $valuesFromExport[] = new \Oara\Curl\Parameter('to_day', $dEndDate->get(\DateTime::DAY));
        $valuesFromExport[] = new \Oara\Curl\Parameter('prog_id', '0');

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://users.buy.at/ma/index.php/affiliateReport/commissionValue?', $valuesFromExport);

        $exportReport = $this->_client->get($urls);
        $exportData = str_getcsv($exportReport[0], "\r\n");
        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i], ",");
            if (change_it_for_isset!((int)$transactionExportArray[12], $merchantList)) {
                $transaction = Array();
                $merchantId = (int)$transactionExportArray[12];
                $transaction['merchantId'] = $merchantId;
                $transactionDate = new \DateTime($transactionExportArray[5], 'dd-MM-yyyy HH:mm:ss');
                $transaction['date'] = $transactionDate->format!("yyyy-MM-dd HH:mm:ss");
                $transaction['unique_id'] = $transactionExportArray[8];

                if ($transactionExportArray[6] != null) {
                    $transaction['custom_id'] = $transactionExportArray[6];
                }

                if ($transactionExportArray[2] == 'Approved') {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else
                    if ($transactionExportArray[2] == 'Pending') {
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else
                        if ($transactionExportArray[2] == 'Held') {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        }
                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[9]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[10]);
                $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;
    }

    /**
     * Check the overview
     * @param array $exportData
     * @return boolean
     */
    private function checkOverview($exportData)
    {
        $result = false;
        $num = count($exportData);
        $j = 1;
        while ($j < $num && !$result) {
            $overviewExportArray = str_getcsv($exportData[$j], ",");
            $result = self::checkOverviewRegister($overviewExportArray);
            $j++;
        }

        return $result;
    }

    /**
     * Check If the register has interesting information
     * @param array $register
     * @param array $properties
     * @return boolean
     */
    public static function checkOverviewRegister(array $register)
    {
        $ok = false;
        $i = 1;
        while ($i < count($register) && !$ok) {
            if ($register[$i] != 0 && $register[$i] != null) {
                $ok = true;
            }
            $i++;
        }
        return $ok;
    }

    /**
     * (non-PHPdoc)
     * @see Oara/Network/Base#getPaymentHistory()
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        $urls = array();
        $valuesFromExport = $this->_exportPaymentParameters;
        $urls[] = new \Oara\Curl\Request('http://users.buy.at/ma/index.php/affiliatePayments/paymentsHistory?', $valuesFromExport);
        $exportReport = $this->_client->get($urls);
        $exportData = str_getcsv($exportReport[0], "\r\n");
        $num = count($exportData);
        for ($j = 1; $j < $num; $j++) {
            $paymentData = str_getcsv($exportData[$j], ",");
            $obj = array();
            $date = new \DateTime($paymentData[0], "dd-MM-yyyy");
            $obj['date'] = $date->format!("yyyy-MM-dd HH:mm:ss");
            $obj['method'] = $paymentData[1];
            $obj['value'] = \Oara\Utilities::parseDouble($paymentData[2]);
            $obj['pid'] = $paymentData[4];
            $paymentHistory[] = $obj;
        }
        return $paymentHistory;
    }

    /**
     *
     * It returns the transactions for a payment
     * @param int $paymentId
     */
    public function paymentTransactions($paymentId, $merchantList, $startDate)
    {
        $transactionList = array();

        $urls = array();
        $valuesFormExport = $this->_exportPaymentDetailsParameters;
        $valuesFormExport[] = new \Oara\Curl\Parameter('payment_id', $paymentId);
        $urls[] = new \Oara\Curl\Request('https://users.buy.at/ma/index.php/affiliatePayments/paymentDetails?', $valuesFormExport);
        $exportReportList = $this->_client->get($urls);
        foreach ($exportReportList as $exportReport) {
            $exportReportData = str_getcsv($exportReport, "\r\n");
            $num = count($exportReportData);
            for ($i = 2; $i < $num - 1; $i++) {
                $transactionArray = str_getcsv($exportReportData[$i], ",");
                $transactionList[] = $transactionArray[5];
            }
        }

        return $transactionList;
    }
}
