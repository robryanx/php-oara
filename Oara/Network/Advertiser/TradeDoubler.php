<?php
namespace Oara\Network\Advertiser;
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
 * @author Carlos Morillo Merino
 * @category Tradedoubler
 * @copyright Fubra Limited
 * @version Release: 01.00
 *
 */
class TradeDoubler extends \Oara\Network
{

    /**
     * Client
     *
     * @var unknown_type
     */
    private $_client = null;

    private $_currency = null;

    /**
     * Constructor and Login
     *
     * @param $buy
     * @return Buy_Api
     */
    public function login($credentials)
    {
        $user = $credentials ['user'];
        $password = $credentials ['password'];

        $this->_currency = $credentials["currency"];

        $loginUrl = 'https://login.tradedoubler.com/pan/login';

        $valuesLogin = array(
            new \Oara\Curl\Parameter ('j_username', $user),
            new \Oara\Curl\Parameter ('j_password', $password)
        );

        $this->_client = new \Oara\Curl\Access ($credentials);


    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {

        $connection = false;
        $urls = array();
        $urls [] = new \Oara\Curl\Request ('https://login.tradedoubler.com/pan/mStartPage.action?resetMenu=true', array());
        $exportReport = $this->_client->get($urls);
        if (preg_match("/logout/", $exportReport [0], $matches)) {
            $connection = true;
        }
        return $connection;

    }

    /**
     * (non-PHPdoc)
     *
     * @see library/Oara/Network/Interface#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = Array();

        $valuesFromExport = array();
        $urls = array();
        $urls [] = new \Oara\Curl\Request ('https://login.tradedoubler.com/pan/mStartPage.action?resetMenu=true', $valuesFromExport);
        $exportReport = $this->_client->get($urls);

        $dom = new Zend_Dom_Query ($exportReport [0]);
        $results = $dom->query('#programChooserId');
        $merchantLines = $results->current()->childNodes;
        for ($i = 0; $i < $merchantLines->length; $i++) {
            $cid = $merchantLines->item($i)->attributes->getNamedItem("value")->nodeValue;
            if (is_numeric($cid)) {
                $obj = array();
                $name = $merchantLines->item($i)->nodeValue;
                $obj = array();
                $obj ['cid'] = $cid;
                $obj ['name'] = $name;
                $merchants [] = $obj;
            }
        }

        return $merchants;
    }

    /**
     * (non-PHPdoc)
     *
     * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();

        $valuesFromExport = array();
        $valuesFromExport [] = new \Oara\Curl\Parameter ('reportName', 'mMerchantSaleAndLeadBreakdownReport');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'programCountry');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'programName');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'time_of_visit');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'timeOfEvent');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'in_session');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'eventName');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'siteName');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'product_name');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'productNrOf');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'product_value');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'open_product_feeds_id');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'open_product_feeds_name');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'voucher_code');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'deviceType');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'os');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'browser');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'vendor');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'device');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'affiliateCommission');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'totalCommission');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'segmentName');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'graphicalElementId');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'pf_product');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'orderValue');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'order_number');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'pending_status');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('columns', 'epi1');
        /**/    //$valuesFromExport [] = new \Oara\Curl\Parameter ( 'startDate', '21/08/2013' );
        /**/    //$valuesFromExport [] = new \Oara\Curl\Parameter ( 'endDate', '21/08/2014' );
        $valuesFromExport [] = new \Oara\Curl\Parameter ('startDate', $dStartDate->format!("dd/MM/yyyy"));
        $valuesFromExport [] = new \Oara\Curl\Parameter ('endDate', $dEndDate->format!("dd/MM/yyyy"));
        $valuesFromExport [] = new \Oara\Curl\Parameter ('isPostBack', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('metric1.lastOperator', '/');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('interval', 'MONTHS');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('segmentId', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('favoriteDescription', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('currencyId', $this->_currency);
        $valuesFromExport [] = new \Oara\Curl\Parameter ('run_as_organization_id', '');
        /**/
        $valuesFromExport [] = new \Oara\Curl\Parameter ('eventId', '5');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('minRelativeIntervalStartTime', '0');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('metric1.summaryType', 'NONE');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('includeMobile', '1');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('metric1.operator1', '/');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('latestDayToExecute', '0');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('showAdvanced', 'true');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('metric1.midFactor', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_MMERCHANTSALESANDLEADBREAKDOWNREPORT_TITLE');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('setColumns', 'true');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('metric1.columnName1', 'organizationId');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('metric1.columnName2', 'organizationId');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('reportPrograms', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('metric1.midOperator', '/');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('viewType', '1');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('favoriteName', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('affiliateId', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('dateType', '1');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('period', 'custom_period');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('geId', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('tabMenuName', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('maxIntervalSize', '12');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('allPrograms', 'false');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('favoriteId', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('metric1.name', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('filterOnTimeHrsInterval', 'false');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('customKeyMetricCount', '0');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('metric1.factor', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('showFavorite', 'false');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('separator', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('format', 'CSV');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('programId', '');

        for ($i = 0; $i < count($merchantList); $i++) {

            $programId = array_pop($valuesFromExport);
            $valuesFromExport [] = new \Oara\Curl\Parameter ('programId', $merchantList[$i]);
            $urls = array();
            $urls [] = new \Oara\Curl\Request ('https://login.tradedoubler.com/pan/mReport3.action?', $valuesFromExport);
            try {
                $result = $this->_client->get($urls);
            } catch (Exception $e) {
                return $transactions;
            }
            $exportData = str_getcsv($result[0], "\n");

            for ($j = 2; $j < count($exportData) - 1; $j++) {
                $transactionExportArray = str_getcsv($exportData[$j], ";");

                if (is_numeric($transactionExportArray[17])) {
                    $transaction = Array();
                    $transaction['unique_id'] = $transactionExportArray[6]; //order nr
                    if ($transactionExportArray[26] != null) {
                        $transaction['custom_id'] = $transactionExportArray[26]; //epi1
                    }
                    $transaction['merchantId'] = $merchantList[$i];
                    $transactionDate = new \DateTime($transactionExportArray[4], 'dd/MM/yy HH:mm:ss CEST');
                    $transaction['date'] = $transactionDate->format!("yyyy-MM-dd HH:mm:ss");
                    $transaction['amount'] = $transactionExportArray[17];
                    $transaction['commission'] = $transactionExportArray[24];

                    if ($transactionExportArray[25] == 'Approved') {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else
                        if ($transactionExportArray[25] == 'Pending') {
                            $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        } else
                            if ($transactionExportArray[25] == 'Deleted') {
                                $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                            }

                    $totalTransactions[] = $transaction;
                }
            }
        }

        return $totalTransactions;
    }

    /**
     * (non-PHPdoc)
     *
     * @see Oara/Network/Base#getPaymentHistory()
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        return $paymentHistory;
    }

    /**
     *
     *
     *
     * It returns the transactions for a payment
     *
     * @param int $paymentId
     */
    public function paymentTransactions($paymentId, $merchantList, $startDate)
    {
        $transactionList = array();

        return $transactionList;
    }

}
