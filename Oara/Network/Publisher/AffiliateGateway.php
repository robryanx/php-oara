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
 * @category   AffiliateGateway
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class AffiliateGateway extends \Oara\Network
{
    private $_client = null;
    protected $_extension = null;

    /**
     * @param $credentials
     * @throws \Exception
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_client = new \Oara\Curl\Access($credentials);

        $valuesLogin = array(
            new \Oara\Curl\Parameter('username', $user),
            new \Oara\Curl\Parameter('password', $password)
        );
        $loginUrl = "{$this->_extension}/login.html";

        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $this->_client->post($urls);
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
        $connection = false;
        $urls = array();
        $urls[] = new \Oara\Curl\Request("{$this->_extension}/affiliate_home.html", array());
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " logout ")]');
        if ($results->length > 0) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();
        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('p', "");
        $valuesFromExport[] = new \Oara\Curl\Parameter('time', "1");
        $valuesFromExport[] = new \Oara\Curl\Parameter('changePage', "");
        $valuesFromExport[] = new \Oara\Curl\Parameter('oldColumn', "programmeId");
        $valuesFromExport[] = new \Oara\Curl\Parameter('sortField', "programmeId");
        $valuesFromExport[] = new \Oara\Curl\Parameter('order', "up");
        $valuesFromExport[] = new \Oara\Curl\Parameter('records', "-1");
        $urls = array();
        $urls[] = new \Oara\Curl\Request("{$this->_extension}/affiliate_program_active.html?", $valuesFromExport);
        $exportReport = $this->_client->post($urls);


        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $tableList = $xpath->query('//table[@class="bluetable"]');


        $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($tableList->item(0)));
        $num = count($exportData);
        for ($i = 4; $i < $num; $i++) {
            $merchantExportArray = \str_getcsv($exportData[$i], ";");
            if ($merchantExportArray[0] != "No available programs.") {
                $obj = array();
                $obj['cid'] = $merchantExportArray[0];
                $obj['name'] = $merchantExportArray[2];
                $merchants[] = $obj;
            }

        }
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

        $merchantNameMap = \Oara\Utilities::getMerchantNameMapFromMerchantList($merchantList);
        $totalTransactions = array();



        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('period', '-1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('subPeriod', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('websiteId', '-1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('hdnMerchantProgram', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('searchType', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('merchantId', '-1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('subId', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('approvalStatus', '-1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('records', '20');
        $valuesFromExport[] = new \Oara\Curl\Parameter('sortField', 'transactionDateTime');
        $valuesFromExport[] = new \Oara\Curl\Parameter('time', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('p', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('changePage', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('oldColumn', 'transactionDateTime');
        $valuesFromExport[] = new \Oara\Curl\Parameter('order', 'down');
        $valuesFromExport[] = new \Oara\Curl\Parameter('mId', '-1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('submittedPeriod', -1);
        $valuesFromExport[] = new \Oara\Curl\Parameter('submittedSubId', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('exportType', 'csv');
        $valuesFromExport[] = new \Oara\Curl\Parameter('reportTitle', 'report');
        $valuesFromExport[] = new \Oara\Curl\Parameter('reportId', '');


        $valuesFromExport[] = new \Oara\Curl\Parameter('startDate', $dStartDate->format("d/m/Y"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('endDate', $dEndDate->format("d/m/Y"));

        $urls = array();
        $urls[] = new \Oara\Curl\Request("{$this->_extension}/affiliate_statistic_transaction.html?", $valuesFromExport);


        $exportReport = $this->_client->post($urls);
        $exportData = \str_getcsv($exportReport[0], "\n");
        $num = \count($exportData);
        for ($i = 2; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i], ",");
            if (isset($merchantNameMap[$transactionExportArray[2]])) {
                $merchantId = $merchantNameMap[$transactionExportArray[2]];

                $transaction = Array();
                $transaction['merchantId'] = $merchantId;
                $transactionDate = \DateTime::createFromFormat("d/m/Y H:i:s", $transactionExportArray[4]);
                $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");
                $transaction['unique_id'] = $transactionExportArray[0];

                if ($transactionExportArray[12] != null && \trim($transactionExportArray[12]) != null) {
                    $transaction['custom_id'] = $transactionExportArray[12];
                }

                if ($transactionExportArray[15] == "Approved" || $transactionExportArray[15] == "Approve") {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else
                    if ($transactionExportArray[15] == "Pending") {
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else
                        if ($transactionExportArray[15] == "Declined" || $transactionExportArray[15] == "Rejected") {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        } else {
                            throw new \Exception ("No Status found " . $transactionExportArray[15]);
                        }
                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[7]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[9]);
                $totalTransactions[] = $transaction;
            }

        }

        return $totalTransactions;
    }

    /**
     * @return array
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        $urls = array();
        $urls[] = new \Oara\Curl\Request("{$this->_extension}/affiliate_invoice.html?", array());
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $tableList = $xpath->query('//table[@class="bluetable"]');
        $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($tableList->item(0)));
        $num = \count($exportData);
        for ($i = 4; $i < $num; $i++) {
            $paymentExportArray = \str_getcsv($exportData[$i], ";");
            if (\count($paymentExportArray) > 7) {
                $obj = array();
                $date = \DateTime::createFromFormat("d/m/Y", $paymentExportArray[1]);
                $obj['date'] = $date->format("Y-m-d H:i:s");
                $obj['pid'] = \preg_replace('/[^0-9]/', "",$paymentExportArray[0]);
                $obj['method'] = 'BACS';
                $obj['value'] = \Oara\Utilities::parseDouble($paymentExportArray[4]);
                $paymentHistory[] = $obj;
            }

        }
        return $paymentHistory;
    }

}
