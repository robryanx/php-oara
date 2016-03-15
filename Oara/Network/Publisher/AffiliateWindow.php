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
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   AffiliateWindow
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class AffiliateWindow extends \Oara\Network
{
    /**
     * Soap client.
     */
    private $_apiClient = null;
    private $_exportClient = null;
    private $_pageSize = 100;
    private $_currency = null;
    private $_userId = null;
    public $_sitesAllowed = array();

    /**
     * @param $credentials
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    public function login($credentials)
    {
        ini_set('default_socket_timeout', '120');
        $user = $credentials['user'];
        $password = $credentials['apiPassword'];
        $passwordExport = $credentials['password'];
        $this->_currency = $credentials['currency'];

        //Login to the website
        if (filter_var($user, \FILTER_VALIDATE_EMAIL)) {

            $this->_exportClient = new \Oara\Curl\Access($credentials);
            //Log in
            $valuesLogin = array(
                new \Oara\Curl\Parameter('email', $user),
                new \Oara\Curl\Parameter('password', $passwordExport),
                new \Oara\Curl\Parameter('Login', '')
            );
            $urls = array();
            $urls[] = new \Oara\Curl\Request('https://darwin.affiliatewindow.com/login?', $valuesLogin);
            $this->_exportClient->post($urls);


            $urls = array();
            $urls[] = new \Oara\Curl\Request('https://darwin.affiliatewindow.com/user/', array());
            $exportReport = $this->_exportClient->get($urls);
            if (\preg_match_all("/id=\"goDarwin(.*)\"/", $exportReport[0], $matches)) {

                foreach ($matches[1] as $user) {
                    $urls = array();
                    $urls[] = new \Oara\Curl\Request('https://darwin.affiliatewindow.com/awin/affiliate/' . $user, array());
                    $exportReport = $this->_exportClient->get($urls);

                    $doc = new \DOMDocument();
                    @$doc->loadHTML($exportReport[0]);
                    $xpath = new \DOMXPath($doc);
                    $linkList = $xpath->query('//a');
                    $href = null;
                    foreach ($linkList as $link) {
                        $text = \trim($link->nodeValue);
                        if ($text == "Manage API Credentials") {
                            $href = $link->attributes->getNamedItem("href")->nodeValue;
                            break;
                        }
                    }
                    if ($href != null) {
                        $urls = array();
                        $urls[] = new \Oara\Curl\Request('https://darwin.affiliatewindow.com' . $href, array());
                        $exportReport = $this->_exportClient->get($urls);

                        $doc = new \DOMDocument();
                        @$doc->loadHTML($exportReport[0]);
                        $xpath = new \DOMXPath($doc);
                        $linkList = $xpath->query("//span[@id='aw_api_password_hash']");
                        foreach ($linkList as $link) {
                            $text = \trim($link->nodeValue);
                            if ($text == $password) {
                                $this->_userId = $user;
                                break;
                            }
                        }

                    } else {
                        throw new \Exception("It couldn't connect to darwin");
                    }
                }
            }
        } else {
            throw new \Exception("It's not an email");
        }

        $nameSpace = 'http://api.affiliatewindow.com/';
        $wsdlUrl = 'http://api.affiliatewindow.com/v4/AffiliateService?wsdl';
        //Setting the client.
        $this->_apiClient = new \SoapClient($wsdlUrl, array('login' => $user, 'encoding' => 'UTF-8', 'password' => $password, 'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE, 'soap_version' => SOAP_1_1));
        $soapHeader1 = new \SoapHeader($nameSpace, 'UserAuthentication', array('iId' => $user, 'sPassword' => $password, 'sType' => 'affiliate'), true, $nameSpace);
        $soapHeader2 = new \SoapHeader($nameSpace, 'getQuota', true, true, $nameSpace);
        $this->_apiClient->__setSoapHeaders(array($soapHeader1, $soapHeader2));

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
        $parameter["description"] = "User Password";
        $parameter["required"] = true;
        $parameter["name"] = "Password";
        $credentials["password"] = $parameter;

        $parameter = array();
        $parameter["description"] = "PublisherService API password";
        $parameter["required"] = true;
        $parameter["name"] = "API password";
        $credentials["apipassword"] = $parameter;

        $parameter = array();
        $parameter["description"] = "Currency code for reporting";
        $parameter["required"] = false;
        $parameter["name"] = "Currency";
        $credentials["currency"] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = false;
        try {

            $params = Array();
            $params['sRelationship'] = 'joined';
            $this->_apiClient->getMerchantList($params);

            $connection = true;
        } catch (\Exception $e) {

        }
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchantList = array();
        $params = array();
        $params['sRelationship'] = 'joined';
        $merchants = $this->_apiClient->getMerchantList($params)->getMerchantListReturn;
        foreach ($merchants as $merchant) {
            if (count($this->_sitesAllowed) == 0  ||\in_array($merchant->oPrimaryRegion->sCountryCode, $this->_sitesAllowed)) {
                $merchantArray = array();
                $merchantArray["cid"] = $merchant->iId;
                $merchantArray["name"] = $merchant->sName;
                $merchantArray["url"] = $merchant->sDisplayUrl;
                $merchantList[] = $merchantArray;
            }
        }
        return $merchantList;
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

        $dStartDate = clone $dStartDate;
        $dStartDate->setTime(0, 0, 0);
        $dEndDate = clone $dEndDate;
        $dEndDate->setTime(23, 59, 59);

        $params = array();
        $params['sDateType'] = 'transaction';
        if ($merchantList != null) {
            $params['aMerchantIds'] = $merchantList;
        }
        if ($dStartDate != null) {
            $params['dStartDate'] = $dStartDate->format("Y-m-d\TH:i:s");
        }
        if ($dEndDate != null) {
            $params['dEndDate'] = $dEndDate->format("Y-m-d\TH:i:s");
        }

        $params['iOffset'] = null;

        $params['iLimit'] = $this->_pageSize;
        $transactionList = $this->_apiClient->getTransactionList($params);
        if (\sizeof($transactionList->getTransactionListReturn) > 0) {
            $iteration = self::getIterationNumber($transactionList->getTransactionListCountReturn->iRowsAvailable, $this->_pageSize);
            unset($transactionList);
            for ($j = 0; $j < $iteration; $j++) {
                $params['iOffset'] = $this->_pageSize * $j;
                $transactionList = $this->_apiClient->getTransactionList($params);

                foreach ($transactionList->getTransactionListReturn as $transactionObject) {
                    $transaction = Array();
                    $transaction['unique_id'] = $transactionObject->iId;
                    $transaction['merchantId'] = $transactionObject->iMerchantId;
                    $date = new \DateTime($transactionObject->dTransactionDate);
                    $transaction['date'] = $date->format("Y-m-d H:i:s");

                    if (isset($transactionObject->sClickref) && $transactionObject->sClickref != null) {
                        $transaction['custom_id'] = $transactionObject->sClickref;
                    }
                    $transaction['type'] = $transactionObject->sType;
                    $transaction['status'] = $transactionObject->sStatus;
                    $transaction['amount'] = $transactionObject->mSaleAmount->dAmount;
                    $transaction['commission'] = $transactionObject->mCommissionAmount->dAmount;

                    if (isset($transactionObject->aTransactionParts)) {
                        $transactionPart = current($transactionObject->aTransactionParts);
                        if ($transactionPart->mCommissionAmount->sCurrency != $this->_currency) {
                            $transaction['currency'] = $transactionPart->mCommissionAmount->sCurrency;
                        }
                    }
                    $totalTransactions[] = $transaction;
                }

                unset($transactionList);
                gc_collect_cycles();
            }

        }
        return $totalTransactions;
    }

    /**
     * @param $rowAvailable
     * @param $rowsReturned
     * @return int
     */
    private function getIterationNumber($rowAvailable, $rowsReturned)
    {
        $iterationDouble = (double)($rowAvailable / $rowsReturned);
        $iterationInt = (int)($rowAvailable / $rowsReturned);
        if ($iterationDouble > $iterationInt) {
            $iterationInt++;
        }
        return $iterationInt;
    }

    /**
     * @return array
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        $urls = array();
        $urls[] = new \Oara\Curl\Request("https://darwin.affiliatewindow.com/awin/affiliate/" . $this->_userId . "/payments/history?", array());
        $exportReport = $this->_exportClient->get($urls);


        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//table/tbody/tr');

        $finished = false;
        while (!$finished) {
            foreach ($results as $result) {
                $linkList = $result->getElementsByTagName('a');
                if ($linkList->length > 0) {
                    $obj = array();
                    $date = \DateTime::createFromFormat('j M Y', $linkList->item(0)->nodeValue);
                    $date->setTime(0, 0);
                    $obj['date'] = $date->format("Y-m-d H:i:s");
                    $attrs = $linkList->item(0)->attributes;
                    foreach ($attrs as $attrName => $attrNode) {
                        if ($attrName = 'href') {
                            $parseUrl = trim($attrNode->nodeValue);
                            if (preg_match("/\/paymentId\/(.+)/", $parseUrl, $matches)) {
                                $obj['pid'] = $matches[1];
                            }
                        }
                    }

                    $obj['value'] = \Oara\Utilities::parseDouble($linkList->item(3)->nodeValue);
                    $obj['method'] = trim($linkList->item(2)->nodeValue);
                    $paymentHistory[] = $obj;
                }
            }

            $results = $xpath->query("//span[@id='nextPage']");
            if ($results->length > 0) {
                foreach ($results as $nextPageLink) {
                    $linkList = $nextPageLink->getElementsByTagName('a');
                    $attrs = $linkList->item(0)->attributes;
                    $nextPageUrl = null;
                    foreach ($attrs as $attrName => $attrNode) {
                        if ($attrName = 'href') {
                            $nextPageUrl = trim($attrNode->nodeValue);
                        }
                    }
                    $urls = array();
                    $urls[] = new \Oara\Curl\Request("https://darwin.affiliatewindow.com" . $nextPageUrl, array());
                    $exportReport = $this->_exportClient->get($urls);
                    $doc = new \DOMDocument();
                    @$doc->loadHTML($exportReport[0]);
                    $xpath = new \DOMXPath($doc);
                    $results = $xpath->query('//table/tbody/tr');
                }
            } else {
                $finished = true;
            }
        }
        return $paymentHistory;
    }

    /**
     * @param $paymentId
     * @return array
     */
    public function paymentTransactions($paymentId)
    {
        $transactionList = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request("https://darwin.affiliatewindow.com/awin/affiliate/" . $this->_userId . "/payments/download/paymentId/" . $paymentId, array());
        $exportReport = $this->_exportClient->get($urls);
        $exportData = \str_getcsv($exportReport[0], "\n");
        $num = \count($exportData);
        $header = \str_getcsv($exportData[0], ",");
        $index = \array_search("Transaction ID", $header);
        for ($j = 1; $j < $num; $j++) {
            $transactionArray = \str_getcsv($exportData[$j], ",");
            $transactionList[] = $transactionArray[$index];
        }
        return $transactionList;
    }
}
