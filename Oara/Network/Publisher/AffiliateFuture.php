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
 * @category   Af
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class AffiliateFuture extends \Oara\Network
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
            new \Oara\Curl\Parameter('Submit', 'Login Now')
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://affiliates.affiliatefuture.com/login.aspx?', $valuesLogin);
        $this->_client->get($urls);

        $this->_credentials = $credentials;

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
        $connection = true;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://affiliates.affiliatefuture.com/myaccount/invoices.aspx', array());
        $result = $this->_client->get($urls);
        if (!\preg_match("/Logout/", $result[0], $matches)) {
            $connection = false;
        }
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = Array();
        $merchantExportList = self::readMerchants();
        foreach ($merchantExportList as $merchant) {
            $obj = Array();
            $obj['cid'] = $merchant['cid'];
            $obj['name'] = $merchant['name'];
            $merchants[] = $obj;
        }
        return $merchants;
    }

    /**
     * @return array
     */
    public function readMerchants()
    {
        $merchantList = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://affiliates.affiliatefuture.com/myprogrammes/default.aspx', array());
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//table[@id="DataGrid1"]');

        $merchantCsv = self::htmlToCsv(self::DOMinnerHTML($results->item(0)));

        for ($i = 1; $i < \count($merchantCsv) - 1; $i++) {
            $merchant = array();
            $merchantLine = \str_getcsv($merchantCsv[$i], ";");
            $merchant['name'] = $merchantLine[0];

            $parseUrl = \parse_url($merchantLine[2]);
            $parameters = \explode('&', $parseUrl['query']);
            foreach ($parameters as $parameter) {
                $parameterValue = \explode('=', $parameter);
                if ($parameterValue[0] == 'id') {
                    $merchant['cid'] = $parameterValue[1];
                }
            }
            $merchantList[] = $merchant;
        }
        return $merchantList;
    }

    /**
     * @param $html
     * @return array
     */
    private function htmlToCsv($html)
    {
        $html = str_replace(array(
            "\t",
            "\r",
            "\n"
        ), "", $html);
        $csv = "";

        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//tr');
        foreach ($results as $result) {

            $doc = new \DOMDocument();
            @$doc->loadHTML(self::DOMinnerHTML($result));
            $xpath = new \DOMXPath($doc);
            $resultsTd = $xpath->query('//td');
            $countTd = $resultsTd->length;
            $i = 0;
            foreach ($resultsTd as $resultTd) {
                $value = $resultTd->nodeValue;

                $doc = new \DOMDocument();
                @$doc->loadHTML(self::DOMinnerHTML($resultTd));
                $xpath = new \DOMXPath($doc);
                $resultsA = $xpath->query('//a');
                foreach ($resultsA as $resultA) {
                    $value = $resultA->getAttribute("href");
                }

                if ($i != $countTd - 1) {
                    $csv .= \trim($value) . ";,";
                } else {
                    $csv .= \trim($value);
                }
                $i++;
            }
            $csv .= "\n";
        }
        $exportData = \str_getcsv($csv, "\n");
        return $exportData;
    }

    /**
     * @param $element
     * @return string
     */
    private function DOMinnerHTML($element)
    {
        $innerHTML = "";
        $children = $element->childNodes;
        foreach ($children as $child) {
            $tmp_dom = new \DOMDocument ();
            $tmp_dom->appendChild($tmp_dom->importNode($child, true));
            $innerHTML .= trim($tmp_dom->saveHTML());
        }
        return $innerHTML;
    }

    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     * @throws \Exception
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {

        $nowDate = new \DateTime();

        $dStartDate = clone $dStartDate;
        $dStartDate->setTime(0,0,0);
        $dEndDate = clone $dEndDate;
        $dEndDate->setTime(23,59,59);


        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('username', $this->_credentials["user"]);
        $valuesFromExport[] = new \Oara\Curl\Parameter('password', $this->_credentials["password"]);
        $valuesFromExport[] = new \Oara\Curl\Parameter('startDate', $dStartDate->format("d-M-Y"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('endDate', $dEndDate->format("d-M-Y"));

        $transactions = Array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://ws-external.afnt.co.uk/apiv1/AFFILIATES/affiliatefuture.asmx/GetTransactionListbyDate?', $valuesFromExport);
        $urls[] = new \Oara\Curl\Request('http://ws-external.afnt.co.uk/apiv1/AFFILIATES/affiliatefuture.asmx/GetCancelledTransactionListbyDate?', $valuesFromExport);
        $exportReport = $this->_client->get($urls);
        for ($i = 0; $i < count($urls); $i++) {
            $xml = self::loadXml($exportReport[$i]);
            if (isset($xml->error)) {
                throw new \Exception('Error connecting with the server');
            }
            if (isset($xml->TransactionList)) {
                foreach ($xml->TransactionList as $transaction) {
                    $date = new \DateTime(self::findAttribute($transaction, 'TransactionDate'));

                    if (\in_array((int)self::findAttribute($transaction, 'ProgrammeID'), $merchantList) &&
                        ($date->format("Y-m-d H:i:s") >= $dStartDate->format("Y-m-d H:i:s")) &&
                        ($date->format("Y-m-d H:i:s") <= $dEndDate->format("Y-m-d H:i:s"))) {

                        $obj = Array();
                        $obj['merchantId'] = self::findAttribute($transaction, 'ProgrammeID');
                        $obj['date'] = $date->format("Y-m-d H:i:s");
                        if (self::findAttribute($transaction, 'TrackingReference') != null) {
                            $obj['custom_id'] = self::findAttribute($transaction, 'TrackingReference');
                        }
                        $obj['unique_id'] = self::findAttribute($transaction, 'TransactionID');
                        if ($i == 0) {
                            $interval = $date->diff($nowDate);
                            if ($interval->format('%a') > 5) {
                                $obj['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                            } else {
                                $obj['status'] = \Oara\Utilities::STATUS_PENDING;
                            }
                        } else
                            if ($i == 1) {
                                $obj['status'] = \Oara\Utilities::STATUS_DECLINED;
                            }

                        $obj['amount'] = \Oara\Utilities::parseDouble(self::findAttribute($transaction, 'SaleValue'));
                        $obj['commission'] = \Oara\Utilities::parseDouble(self::findAttribute($transaction, 'SaleCommission'));
                        $leadCommission = \Oara\Utilities::parseDouble(self::findAttribute($transaction, 'LeadCommission'));
                        if ($leadCommission != 0) {
                            $obj['commission'] += $leadCommission;
                        }
                        $transactions[] = $obj;
                    }
                }
            }
        }

        return $transactions;
    }

    /**
     * @param null $exportReport
     * @return \SimpleXMLElement
     */
    private function loadXml($exportReport = null)
    {
        $xml = simplexml_load_string($exportReport, null, LIBXML_NOERROR | LIBXML_NOWARNING);
        return $xml;
    }

    /**
     * @param null $object
     * @param null $attribute
     * @return null|string
     */
    private function findAttribute($object = null, $attribute = null)
    {
        $return = null;
        $return = trim($object->$attribute);
        return $return;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://affiliates.affiliatefuture.com/myaccount/invoices.aspx', array());
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $tableList = $xpath->query('//table');
        $registerTable = $tableList->item(12);
        if ($registerTable == null) {
            throw new \Exception('Fail getting the payment History');
        }
        $registerLines = $registerTable->childNodes;
        for ($i = 1; $i < $registerLines->length; $i++) {
            $registerLine = $registerLines->item($i)->childNodes;
            $obj = array();
            $date = \DateTime::createFromFormat("d/m/Y", trim($registerLine->item(1)->nodeValue));
            $date->setTime(0, 0);
            $obj['date'] = $date->format("Y-m-d H:i:s");
            $obj['pid'] = trim($registerLine->item(0)->nodeValue);
            $value = trim(substr(trim($registerLine->item(4)->nodeValue), 4));
            $obj['value'] = \Oara\Utilities::parseDouble($value);
            $obj['method'] = 'BACS';
            $paymentHistory[] = $obj;
        }

        return $paymentHistory;
    }

}
