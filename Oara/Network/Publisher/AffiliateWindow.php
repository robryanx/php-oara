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
    protected $_currency = null;
    private $_userId = null;
    public $_sitesAllowed = array();
    public $_includeBonus = true;

    /**
     * @param $credentials
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    public function login($credentials)
    {
        ini_set('default_socket_timeout', '120');
        $accountid = $credentials['accountid'];
        $password = $credentials['apipassword'];

        $nameSpace = 'http://api.affiliatewindow.com/';
        $wsdlUrl = 'http://api.affiliatewindow.com/v6/AffiliateService?wsdl';
        //Setting the client.
        $this->_apiClient = new \SoapClient($wsdlUrl, array('login' => $accountid, 'encoding' => 'UTF-8', 'password' => $password, 'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE, 'soap_version' => SOAP_1_1));
        $soapHeader1 = new \SoapHeader($nameSpace, 'UserAuthentication', array('iId' => $accountid, 'sPassword' => $password, 'sType' => 'affiliate'), true, $nameSpace);
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
        $parameter["description"] = "PublisherService API password";
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
            if (count($this->_sitesAllowed) == 0 || \in_array($merchant->oPrimaryRegion->sCountryCode, $this->_sitesAllowed)) {
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
