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
 * @category   Amazon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Amazon extends \Oara\Network
{

    private $_idBox = null;
    private $_client = null;
    protected $_networkServer = null;
    protected $_sitesAllowed = array ();

    /**
     * @param $credentials
     */
    public function login($credentials)
    {

        $user = $credentials['user'];
        $password = $credentials['password'];

        $this->_client = new \Oara\Curl\Access($credentials);
        $urls = array();
        $urls[] = new \Oara\Curl\Request($this->_networkServer, array());
        $exportReport = $this->_client->get($urls);


        $objDOM = new \DOMDocument();
        @$objDOM->loadHTML($exportReport[0]);
        $objXPath = new \DOMXPath($objDOM);
        $objForm = $objXPath->query("//form[@name='sign_in']");
        $objForm = $objForm->item(0);
        $objInputs = $objXPath->query("//input[@type='hidden']", $objForm);

        $arrInputs = array(
            new \Oara\Curl\Parameter('username', $user),
            new \Oara\Curl\Parameter('password', $password)
        );
        foreach ($objInputs as $objInput) {
            $arrInputs[] = new \Oara\Curl\Parameter($objInput->getAttribute('name'), $objInput->getAttribute('value'));
        }
        $strURL = $objForm->getAttribute('action');

        $urls = array();
        $urls[] = new \Oara\Curl\Request($strURL, $arrInputs);
        $this->_client->post($urls);

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
     * @return bool
     */
    public function checkConnection()
    {
        //If not login properly the construct launch an exception
        $connection = false;
        $urls = array();
        $urls[] = new \Oara\Curl\Request($this->_networkServer . "/gp/associates/network/reports/main.html", array());
        $exportReport = $this->_client->get($urls);

        if (\preg_match("/logout%26openid.ns/", $exportReport[0])) {

            $doc = new \DOMDocument();
            @$doc->loadHTML($exportReport[0]);
            $xpath = new \DOMXPath($doc);
            $results = $xpath->query("//select[@name='idbox_tracking_id']");
            $count = $results->length;
            $idBox= array();
            if ($count == 0) {
                $idBox[] = "";
            } else {
                foreach ($results as $result) {
                    $optionList = $result->childNodes;
                    $optionNumber = $optionList->length;
                    for ($i = 0; $i < $optionNumber; $i++) {
                        $idBoxName = $optionList->item($i)->attributes->getNamedItem("value")->nodeValue;
                        if (!\in_array($idBoxName, $idBox)) {
                            $idBox[] = $idBoxName;
                        }
                    }
                }
            }

            $results = $xpath->query("//input[@name='combinedReports']");
            foreach ($results as $n) {
                if ($n->getAttribute('checked') === 'checked') {
                    $idBox = array('');
                    break;
                }
            }
            $this->_idBox = $idBox;
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
        $obj['cid'] = "1";
        $obj['name'] = "Amazon";
        $obj['url'] = "www.amazon.com";
        $merchants[] = $obj;

        return $merchants;
    }

    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     * @throws Exception
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {

        $totalTransactions = array();
        foreach ($this->_idBox as $id) {

            $try = 0;
            $done = false;
            while (!$done && $try < 5) {
                try {

                    $totalTransactions = \array_merge($totalTransactions, self::getTransactionReportRecursive($id, $dStartDate, $dEndDate));
                    $done = true;

                } catch (\Exception $e) {
                    $try++;
                }
            }
            if ($try == 5) {
                throw new \Exception("Couldn't get data for the date ");
            }
        }

        return $totalTransactions;
    }

    private function getTransactionReportRecursive($id, $startDate, $endDate)
    {
        $totalTransactions = array();

        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('tag', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('reportType', 'earningsReport');
        $valuesFromExport[] = new \Oara\Curl\Parameter('program', 'all');
        $valuesFromExport[] = new \Oara\Curl\Parameter('preSelectedPeriod', 'monthToDate');
        $valuesFromExport[] = new \Oara\Curl\Parameter('periodType', 'exact');
        $valuesFromExport[] = new \Oara\Curl\Parameter('submit.download_CSV.x', '106');
        $valuesFromExport[] = new \Oara\Curl\Parameter('submit.download_CSV.y', '11');
        $valuesFromExport[] = new \Oara\Curl\Parameter('submit.download_CSV', 'Download report (CSV)');
        $valuesFromExport[] = new \Oara\Curl\Parameter('startDay', $startDate->format("j"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('startMonth', (int)$startDate->format("n") - 1);
        $valuesFromExport[] = new \Oara\Curl\Parameter('startYear', $startDate->format("Y"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('endDay', $endDate->format("j"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('endMonth', (int)$endDate->format("n") - 1);
        $valuesFromExport[] = new \Oara\Curl\Parameter('endYear', $endDate->format("Y"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('idbox_tracking_id', $id);

        $urls = array();
        $urls[] = new \Oara\Curl\Request($this->_networkServer . "/gp/associates/network/reports/report.html?", $valuesFromExport);
        $exportReport = $this->_client->get($urls);

        if (\preg_match("/DOCTYPE/", $exportReport[0])) {
            return array();
        }
        $exportData = \str_getcsv($exportReport[0], "\n");
        $num = count($exportData);
        for ($i = 3; $i < $num; $i++) {
            $transactionExportArray = \str_getcsv(\str_replace("\"", "", $exportData[$i]), "\t");
            if (!isset($transactionExportArray[5])) {
                throw new \Exception("Request failed");
            }
            if (\count($this->_sitesAllowed) == 0 || \in_array($transactionExportArray[4], $this->_sitesAllowed)) {
                $transactionDate = \DateTime::createFromFormat("F d, Y", $transactionExportArray[5]);
                $transaction = Array();
                $transaction['merchantId'] = 1;
                $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");
                if ($transactionExportArray[4] != null) {
                    $transaction['custom_id'] = $transactionExportArray[4];
                }
                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[9]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[10]);
                $transaction['device_type'] = $transactionExportArray[11];
                $transaction['skew'] = $transactionExportArray[2];
                $transaction['title'] = $transactionExportArray[1];
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
        foreach ($this->_idBox as $id) {
            $urls = array();
            $paymentExport = array();
            $paymentExport[] = new \Oara\Curl\Parameter('idbox_tracking_id', $id);
            $urls[] = new \Oara\Curl\Request($this->_networkServer . "/gp/associates/network/your-account/payment-history.html?", $paymentExport);
            $exportReport = $this->_client->get($urls);

            $doc = new \DOMDocument();
            @$doc->loadHTML($exportReport[0]);
            $xpath = new \DOMXPath($doc);
            $results = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " paymenthistory ")]');
            $count = $results->length;
            if ($count == 1) {
                $paymentTable = $results->item(0);
                $paymentReport = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($paymentTable));
                for ($i = 2; $i < \count($paymentReport) - 1; $i++) {
                    $paymentExportArray = \str_getcsv($paymentReport[$i], ";");
                    $obj = array();
                    $paymentDate = \DateTime::createFromFormat("n/j/Y", $paymentExportArray[0]);
                    $obj['date'] = $paymentDate->format("Y-m-d H:i:s");
                    $obj['pid'] = ($paymentDate->format("Ymd") . \substr((string)\base_convert(\md5($id), 16, 10), 0, 5));
                    $obj['method'] = 'BACS';
                    if (\preg_match("/-/",$paymentExportArray[2])) {
                        $obj['value'] = \abs(\Oara\Utilities::parseDouble($paymentExportArray[2]));
                        $paymentHistory[] = $obj;
                    }

                }
            }
        }
        return $paymentHistory;
    }
}
