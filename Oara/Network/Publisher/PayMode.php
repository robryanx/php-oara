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
 * @category   PayMode
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class PayMode extends \Oara\Network
{
    private $_client = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_client = new \Oara\Curl\Access($credentials);

        $valuesLogin = array(
            new \Oara\Curl\Parameter('username', $user),
            new \Oara\Curl\Parameter('password', $password),
            new \Oara\Curl\Parameter('Enter', 'Enter')
        );
        $loginUrl = 'https://secure.paymode.com/paymode/do-login.jsp?';
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
        //If not login properly the construct launch an exception
        $connection = false;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://secure.paymode.com/paymode/home.jsp?', array());
        $exportReport = $this->_client->get($urls);

        if (\preg_match('/class="logout"/', $exportReport[0], $matches)) {

            $urls = array();
            $urls[] = new \Oara\Curl\Request('https://secure.paymode.com/paymode/reports-pre_commission_history.jsp?', array());
            $exportReport = $this->_client->get($urls);
            $doc = new \DOMDocument();
            @$doc->loadHTML($exportReport[0]);
            $xpath = new \DOMXPath($doc);
            $results = $xpath->query('//input[@type="checkbox"]');
            $agentNumber = array();
            foreach ($results as $result) {
                $agentNumber[] = $result->getAttribute("id");
            }
            $this->_agentNumber = $agentNumber;
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

        $obj = array();
        $obj['cid'] = 1;
        $obj['name'] = "Sixt";
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

        $valuesFromExport = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://secure.paymode.com/paymode/reports-baiv2.jsp?', array());
        $exportReport = $this->_client->get($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//input[@type="hidden"]');
        foreach ($results as $hidden) {
            $name = $hidden->getAttribute("name");
            $value = $hidden->getAttribute("value");
            $valuesFromExport[] = new \Oara\Curl\Parameter($name, $value);
        }
        $valuesFromExport[] = new \Oara\Curl\Parameter('dataSource', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('RA:reports-baiv2.jspCHOOSE', '620541800');
        $valuesFromExport[] = new \Oara\Curl\Parameter('reportFormat', 'csv');
        $valuesFromExport[] = new \Oara\Curl\Parameter('includeCurrencyCodeColumn', 'on');
        $valuesFromExport[] = new \Oara\Curl\Parameter('remitTypeCode', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('PAYMENT_CURRENCY_TYPE', 'CREDIT');
        $valuesFromExport[] = new \Oara\Curl\Parameter('PAYMENT_CURRENCY_TYPE', 'INSTRUCTION');
        $valuesFromExport[] = new \Oara\Curl\Parameter('subSiteExtID', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('ediProvider835Version', '5010');
        $valuesFromExport[] = new \Oara\Curl\Parameter('tooManyRowsCheck', 'true');

        $urls = array();
        $amountDays = $dStartDate->diff($dEndDate)->days;
        $auxDate = clone $dStartDate;
        for ($j = 0; $j < $amountDays; $j++) {
            $valuesFromExportTemp = \Oara\Utilities::cloneArray($valuesFromExport);
            $valuesFromExportTemp[] = new \Oara\Curl\Parameter('date', $auxDate->format("m/d/Y"));
            $urls[] = new \Oara\Curl\Request('https://secure.paymode.com/paymode/reports-do_csv.jsp?closeJQS=true?', $valuesFromExportTemp);
            $auxDate->add(new \DateInterval('P1D'));
        }

        $exportReport = $this->_client->get($urls);
        $transactionCounter = 0;
        $valueCounter = 0;
        $commissionCounter = 0;
        $j = 0;
        foreach ($exportReport as $report) {
            if (!\preg_match("/logout.jsp/", $report)) {
                $exportReportData = \str_getcsv($report, "\n");
                $num = \count($exportReportData);
                for ($i = 1; $i < $num; $i++) {
                    $transactionArray = \str_getcsv($exportReportData[$i], ",");
                    if (\count($transactionArray) == 30 && $transactionArray[0] == 'D' && $transactionArray[1] == null) {
                        $transactionCounter++;
                        $valueCounter += \Oara\Utilities::parseDouble($transactionArray[24]);
                        $commissionCounter += \Oara\Utilities::parseDouble($transactionArray[28]);
                    }
                }
            }
            $j++;
        }

        if ($transactionCounter > 0) {
            $auxDate = clone $dStartDate;
            for ($i = 0; $i < $amountDays; $i++) {
                $transaction = array();
                $transaction['merchantId'] = 1;
                $transaction['status'] = \Oara\Utilities::STATUS_PAID;
                $transaction['date'] = $auxDate->format("Y-m-d H:i:s");
                $transaction['amount'] = \Oara\Utilities::parseDouble($valueCounter / $amountDays);
                $transaction['commission'] = \Oara\Utilities::parseDouble($commissionCounter / $amountDays);
                $totalTransactions[] = $transaction;
                $auxDate->add(new \DateInterval('P1D'));
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

        $startDate = new \DateTime("2012-01-01");
        $endDate = new \DateTime();

        $amountMonths = $startDate->diff($endDate)->months;
        $auxDate = clone $startDate;

        for ($j = 0; $j < $amountMonths; $j++) {
            $monthStartDate = clone $auxDate;
            $monthEndDate = null;

            $monthEndDate = clone $auxDate;
            $monthEndDate->add(new \DateInterval('P1M'));
            $monthEndDate->sub(new \DateInterval('P1D'));
            $monthEndDate->setTime(23,59,59);

            $valuesFromExport = array();
            $valuesFromExport[] = new \Oara\Curl\Parameter('Begin_Date', $monthStartDate->format("m/d/Y"));
            $valuesFromExport[] = new \Oara\Curl\Parameter('End_Date', $monthEndDate->format("m/d/Y"));
            $valuesFromExport[] = new \Oara\Curl\Parameter('cd', "c");
            $valuesFromExport[] = new \Oara\Curl\Parameter('disb', "false");
            $valuesFromExport[] = new \Oara\Curl\Parameter('coll', "true");
            $valuesFromExport[] = new \Oara\Curl\Parameter('transactionID', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('Begin_DatePN', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('Begin_DateCN', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('End_DatePN', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('End_DateCN', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('disbAcctIDRef', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('checkNumberID', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('paymentNum', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('sel_type', "OTH");
            $valuesFromExport[] = new \Oara\Curl\Parameter('payStatusCat', "ALL_STATUSES");
            $valuesFromExport[] = new \Oara\Curl\Parameter('amount', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('aggregatedCreditAmount', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('disbSiteIDManual', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('collSiteIDManual', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('agencyid', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('collbankAccount', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('remitInvoice', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('remitAccount', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('remitCustAccount', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('remitCustName', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('remitVendorNumber', "");
            $valuesFromExport[] = new \Oara\Curl\Parameter('remitVendorName', "");

            $urls = array();
            $urls[] = new \Oara\Curl\Request('https://secure.paymode.com/paymode/payment-DB-search.jsp?dataSource=1', $valuesFromExport);
            $exportReport = $this->_client->post($urls);


            if (!\preg_match("/No payments were found/", $exportReport[0])) {

                $doc = new \DOMDocument();
                @$doc->loadHTML($exportReport[0]);
                $xpath = new \DOMXPath($doc);
                $results = $xpath->query('//form[@name="transform"] table');
                if (\count($results) > 0) {
                    $tableCsv = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($results->item(0)));
                    $payment = Array();
                    $paymentArray = \str_getcsv($tableCsv[4], ";");
                    $payment['pid'] = $paymentArray[1];

                    $dateResult = $xpath->query('//form[@name="collForm"] table');
                    if (\count($dateResult) > 0) {
                        $dateCsv = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($dateResult->item(0)));
                        $dateArray = \str_getcsv($dateCsv[2], ";");
                        $paymentDate = \DateTime::createFromFormat("d-M-Y", $dateArray [1]);
                        $payment['date'] = $paymentDate->format("Y-m-d H:i:s");
                        $paymentArray = \str_getcsv($tableCsv[3], ";");
                        $payment['value'] = \Oara\Utilities::parseDouble($paymentArray[3]);
                        $payment['method'] = "BACS";
                        $paymentHistory[] = $payment;
                    }

                } else {
                    $results = $xpath->query('//table[@cellpadding="2"]');
                    foreach ($results as $table) {

                        $tableCsv = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($table));
                        $num = \count($tableCsv);
                        for ($i = 1; $i < $num; $i++) {
                            $payment = Array();
                            $paymentArray = \str_getcsv($tableCsv[$i], ";");
                            $payment['pid'] = $paymentArray[0];
                            $paymentDate = \DateTime::createFromFormat("m/d/Y", $paymentArray [3]);
                            $payment['date'] = $paymentDate->format("Y-m-d H:i:s");
                            $payment['value'] = \Oara\Utilities::parseDouble($paymentArray[9]);
                            $payment['method'] = "BACS";
                            $paymentHistory[] = $payment;
                        }
                    }
                }
            }
            $auxDate->add(new \DateInterval('P1M'));
        }
        return $paymentHistory;
    }
}
