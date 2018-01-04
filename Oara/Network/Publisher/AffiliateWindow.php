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
    public $_sitesAllowed = array();
    public $_timeZone = null;
    public $_includeBonus = true;
    protected $_currency = null;
    protected $_password = null;
    protected $_accountId = null;

    //OLD AW
    private $_apiClient = null;
    private $_exportClient = null;
    private $_pageSize = 100;
    private $_oldAPI = false;

    /**
     * @param $credentials
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    public function login($credentials)
    {
        $this->_accountId = $credentials['accountid'];
        $this->_password = $credentials['apipassword'];
        $this->_client = new \Oara\Curl\Access($credentials);

        //OLD AW
        $nameSpace = 'http://api.affiliatewindow.com/';
        $wsdlUrl = 'http://api.affiliatewindow.com/v6/AffiliateService?wsdl';
        //Setting the client.
        $this->_apiClient = new \SoapClient($wsdlUrl, array('login' => $this->_accountId, 'encoding' => 'UTF-8', 'password' => $this->_password, 'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE, 'soap_version' => SOAP_1_1));
        $soapHeader1 = new \SoapHeader($nameSpace, 'UserAuthentication', array('iId' => $this->_accountId, 'sPassword' => $this->_password, 'sType' => 'affiliate'), true, $nameSpace);
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
        $parameter["description"] = "Account Id (number)";
        $parameter["required"] = true;
        $parameter["name"] = "Account ID";
        $credentials["accountid"] = $parameter;

        $parameter = array();
        $parameter["description"] = "Publisher API password";
        $parameter["required"] = true;
        $parameter["name"] = "API password";
        $credentials["apipassword"] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = false;
        try {
            $urls = array();
            $valuesFromExport = array();
            $valuesFromExport[] = new \Oara\Curl\Parameter('type', "publisher");
            $valuesFromExport[] = new \Oara\Curl\Parameter('accessToken', $this->_password);
            $urls[] = new \Oara\Curl\Request("https://api.awin.com/accounts/?", $valuesFromExport);
            $accountsList = $this->_client->get($urls);

            $connection = true;
        } catch (\Exception $e) {

            //OLD AW
            try {

                $params = Array();
                $params['sRelationship'] = 'joined';
                $this->_apiClient->getMerchantList($params);

                $connection = true;
                $this->_oldAPI = true;
            } catch (\Exception $e) {

            }


        }
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        if ($this->_oldAPI) {
            $merchantList = array();
            $params = array();
            $params['sRelationship'] = 'joined';
            $merchants = $this->_apiClient->getMerchantList($params)->getMerchantListReturn;
            foreach ($merchants as $merchant) {
                if (count($this->_sitesAllowed) == 0 || \in_array($merchant->oPrimaryRegion->sCountryCode, $this->_sitesAllowed)) {
                    $merchantArray = array();
                    $merchantArray["cid"] = $merchant->iId;
                    $merchantArray["name"] = $merchant->sName;
                    $merchantArray["url"] = $merchant->sDisplayUrl;
                    $merchantList[] = $merchantArray;
                }
            }
            return $merchantList;
        } else {


            $merchantList = array();
            $urls = array();
            $valuesFromExport = array();
            $valuesFromExport[] = new \Oara\Curl\Parameter('relationship', "joined");
            $valuesFromExport[] = new \Oara\Curl\Parameter('accessToken', $this->_password);
            $urls[] = new \Oara\Curl\Request("https://api.awin.com/publishers/{$this->_accountId}/programmes/?", $valuesFromExport);
            $merchantJson = $this->_client->get($urls);
            $merchants = \json_decode($merchantJson[0], true);
            foreach ($merchants as $merchant) {
                if (count($this->_sitesAllowed) == 0 || \in_array($merchant["primaryRegion"]["countryCode"], $this->_sitesAllowed)) {
                    $merchantArray = array();
                    $merchantArray["cid"] = $merchant["id"];
                    $merchantArray["name"] = $merchant["name"];
                    $merchantArray["url"] = $merchant["displayUrl"];
                    $merchantList[] = $merchantArray;
                }
            }

            return $merchantList;
        }
    }

    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        if ($this->_oldAPI) {
            $totalTransactions = array();

            $dStartDate = clone $dStartDate;
            $dStartDate->setTime(0, 0, 0);
            $dEndDate = clone $dEndDate;
            $dEndDate->setTime(23, 59, 59);

            $params = array();
            $params['sDateType'] = 'transaction';
            if ($merchantList != null) {
                $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);
                $params['aMerchantIds'] = \array_keys($merchantIdList);
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
                        if (($transactionObject->sType != 'bonus') || ($transactionObject->sType == 'bonus' && $this->_includeBonus)) {
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
                                $transactionPart = \current($transactionObject->aTransactionParts);
                                $transaction['currency'] = $transactionPart->mCommissionAmount->sCurrency;
                            }
                            $totalTransactions[] = $transaction;
                        }
                    }

                    unset($transactionList);
                    \gc_collect_cycles();
                }

            }
            return $totalTransactions;


        } else {
            $totalTransactions = array();
            $merchantIdMap = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);


            $amountDays = $dStartDate->diff($dEndDate)->days;
            $auxDate = clone $dStartDate;
            for ($j = 0; $j <= $amountDays; $j++) {

                $dStartDate = clone $auxDate;
                $dStartDate->setTime(0, 0, 0);
                $dEndDate = clone $auxDate;
                $dEndDate->setTime(23, 59, 59);

                $urls = array();
                $valuesFromExport = array();
                $valuesFromExport[] = new \Oara\Curl\Parameter('startDate', $dStartDate->format("Y-m-d\TH:i:s"));
                $valuesFromExport[] = new \Oara\Curl\Parameter('endDate', $dEndDate->format("Y-m-d\TH:i:s"));
                if ($this->_timeZone) {
                    $valuesFromExport[] = new \Oara\Curl\Parameter('timezone', $this->_timeZone);
                } else {
                    $valuesFromExport[] = new \Oara\Curl\Parameter('timezone', "UTC");
                }

                $valuesFromExport[] = new \Oara\Curl\Parameter('accessToken', $this->_password);
                $urls[] = new \Oara\Curl\Request("https://api.awin.com/publishers/{$this->_accountId}/transactions/?", $valuesFromExport);
                $transactionJson = $this->_client->get($urls);
                $transactionList = \json_decode($transactionJson[0], true);
                if (\count($transactionList) > 0) {

                    foreach ($transactionList as $transactionObject) {
                        $transaction = Array();
                        $transaction['unique_id'] = $transactionObject["id"];
                        $transaction['merchantId'] = $transactionObject["advertiserId"];
                        if (isset($merchantIdMap[(int)$transaction['merchantId']])) {
                            if ((strtolower($transactionObject["type"]) != 'bonus') || (strtolower($transactionObject["type"]) == 'bonus' && $this->_includeBonus)) {

                                $date = new \DateTime($transactionObject["transactionDate"]);
                                $transaction['date'] = $date->format("Y-m-d H:i:s");

                                if (isset($transactionObject["clickRefs"]) && isset($transactionObject["clickRefs"]["clickRef"])) {
                                    $transaction['custom_id'] = $transactionObject["clickRefs"]["clickRef"];
                                }
                                $transaction['type'] = $transactionObject["type"];
                                $transaction['status'] = $transactionObject["commissionStatus"];
                                if ($transaction['status'] == 'approved') {
                                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                                } else if ($transaction['status'] == 'bonus') {
                                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                                } else if ($transaction['status'] == 'pending') {
                                    $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                                } else if ($transaction['status'] == 'deleted' || $transaction['status'] == 'declined') {
                                    $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                                } else {
                                    throw new \Exception("New status {$transaction['status']}\n");
                                }

                                $transaction['amount'] = $transactionObject["saleAmount"]["amount"];
                                $transaction['commission'] = $transactionObject["commissionAmount"]["amount"];
                                $transaction['currency'] = $transactionObject["commissionAmount"]["currency"];
                                $totalTransactions[] = $transaction;
                            }
                        }
                    }
                    unset($transactionJson, $transactionList);
                    \gc_collect_cycles();
                }

                $auxDate->add(new \DateInterval('P1D'));
            }


            return $totalTransactions;
        }

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


}
