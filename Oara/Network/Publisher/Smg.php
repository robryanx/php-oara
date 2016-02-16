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
 * @category   Smg
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Smg extends \Oara\Network
{
    /**
     * Export client.
     * @var \Oara\Curl\Access
     */
    private $_newClient = null;

    /**
     * Access to the website?
     * @var \Oara\Curl\Access
     */
    private $_newAccess = false;

    /**
     * Date Format, it's different in some accounts
     * @var string
     */
    private $_dateFormat = null;

    private $_credentials = null;

    private $_accountSid = null;
    private $_authToken = null;

    /**
     * Constructor and Login
     * @param $tradeDoubler
     * @return Td_Export
     */
    public function __construct($credentials)
    {

        $this->_credentials = $credentials;

        $user = $this->_credentials['user'];
        $password = $this->_credentials['password'];
        $loginUrl = 'https://member.impactradius.co.uk/secure/login.user';

        $valuesLogin = array(new \Oara\Curl\Parameter('j_username', $user),
            new \Oara\Curl\Parameter('j_password', $password)
        );

        $credentials = $this->_credentials;
        $this->_newClient = new \Oara\Curl\Access($loginUrl, $valuesLogin, $credentials);

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://member.impactradius.co.uk/secure/mediapartner/accountSettings/mp-wsapi-flow.ihtml?', array());
        $exportReport = $this->_newClient->get($urls);
        $dom = new Zend_Dom_Query($exportReport[0]);
        $results = $dom->query('div .uitkFields');
        $count = count($results);
        if ($count == 0) {

            $activeAPI = array(new \Oara\Curl\Parameter('_eventId', "activate"));
            $urls = array();
            $urls[] = new \Oara\Curl\Request('https://member.impactradius.co.uk/secure/mediapartner/accountSettings/mp-wsapi-flow.ihtml?', $activeAPI);
            $exportReport = $this->_newClient->post($urls);
            $urls = array();
            $urls[] = new \Oara\Curl\Request('https://member.impactradius.co.uk/secure/mediapartner/accountSettings/mp-wsapi-flow.ihtml?', array());
            $exportReport = $this->_newClient->get($urls);
            $dom = new Zend_Dom_Query($exportReport[0]);
            $results = $dom->query('div .uitkFields');
            $count = count($results); // get number of matches: 4
            if ($count == 0) {
                throw new Exception ("No API credentials");
            }
        }
        $i = 0;
        foreach ($results as $result) {
            if ($i == 0) {
                $this->_accountSid = str_replace(array("\n", "\t", " "), "", $result->nodeValue);
            } else if ($i == 1) {
                $this->_authToken = str_replace(array("\n", "\t", " "), "", $result->nodeValue);
            }
            $i++;
        }

    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;

        //Checking connection for the impact Radius website
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://member.impactradius.co.uk/secure/mediapartner/home/pview.ihtml', array());
        $exportReport = $this->_newClient->get($urls);
        $newCheck = false;
        if (preg_match('/\/logOut\.user/', $exportReport[0], $match)) {
            $newCheck = true;
        }

        $newApi = false;
        if ($newCheck && $this->_authToken != null && $this->_accountSid != null) {
            //Checking API connection from Impact Radius
            $uri = "https://" . $this->_accountSid . ":" . $this->_authToken . "@api.impactradius.com/2010-09-01/Mediapartners/" . $this->_accountSid . "/Campaigns.xml";
            $res = simplexml_load_file($uri);
            if (isset($res->Campaigns)) {
                $newApi = true;
            }

        }

        if ($newCheck && $newApi) {
            $connection = true;
        }

        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchantReportList = self::getMerchantReportList();
        $merchants = Array();
        foreach ($merchantReportList as $key => $value) {
            $obj = Array();
            $obj['cid'] = $key;
            $obj['name'] = $value;
            $merchants[] = $obj;
        }

        return $merchants;
    }

    /**
     * It returns an array with the different merchants
     * @return array
     */
    private function getMerchantReportList()
    {
        $uri = "https://" . $this->_accountSid . ":" . $this->_authToken . "@api.impactradius.com/2010-09-01/Mediapartners/" . $this->_accountSid . "/Campaigns.xml";
        $res = simplexml_load_file($uri);
        $currentPage = (int)$res->Campaigns->attributes()->page;
        $pageNumber = (int)$res->Campaigns->attributes()->numpages;
        while ($currentPage <= $pageNumber) {

            foreach ($res->Campaigns->Campaign as $campaign) {
                $campaignId = (int)$campaign->CampaignId;
                $campaignName = (string)$campaign->CampaignName;
                $merchantReportList[$campaignId] = $campaignName;
            }

            $currentPage++;
            $nextPageUri = (string)$res->Campaigns->attributes()->nextpageuri;
            if ($nextPageUri != null) {
                $res = simplexml_load_file("https://" . $this->_accountSid . ":" . $this->_authToken . "@api.impactradius.com" . $nextPageUri);
            }
        }
        return $merchantReportList;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = Array();
        $filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));

        //New Interface
        $uri = "https://" . $this->_accountSid . ":" . $this->_authToken . "@api.impactradius.com/2010-09-01/Mediapartners/" . $this->_accountSid . "/Actions?ActionDateStart=" . $dStartDate->toString('yyyy-MM-ddTHH:mm:ss') . "-00:00&ActionDateEnd=" . $dEndDate->toString('yyyy-MM-ddTHH:mm:ss') . "-00:00";
        $res = simplexml_load_file($uri);
        if ($res) {


            $currentPage = (int)$res->Actions->attributes()->page;
            $pageNumber = (int)$res->Actions->attributes()->numpages;
            while ($currentPage <= $pageNumber) {

                foreach ($res->Actions->Action as $action) {
                    $transaction = Array();
                    $transaction['merchantId'] = (int)$action->CampaignId;

                    $transactionDate = new \DateTime((string)$action->EventDate, "yyyy-MM-dd HH:mm:ss");
                    $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

                    $transaction['unique_id'] = (string)$action->Id;
                    if ((string)$action->SharedId != '') {
                        $transaction['custom_id'] = (string)$action->SharedId;
                    }
                    if ((string)$action->SubId1 != '') {
                        $transaction['custom_id'] = (string)$action->SubId1;
                    }

                    $status = (string)$action->Status;
                    $statusArray[$status] = "";
                    if ($status == 'APPROVED' || $status == 'DEFAULT') {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else
                        if ($status == 'REJECTED') {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        } else {
                            $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        }

                    $transaction['amount'] = (double)$action->Amount;
                    $transaction['commission'] = (double)$action->Payout;
                    $totalTransactions[] = $transaction;
                }

                $currentPage++;
                $nextPageUri = (string)$res->Actions->attributes()->nextpageuri;
                if ($nextPageUri != null) {
                    $res = simplexml_load_file("https://" . $this->_accountSid . ":" . $this->_authToken . "@api.impactradius.com" . $nextPageUri);
                }
            }
        }
        return $totalTransactions;

    }

    /**
     * (non-PHPdoc)
     * @see Oara/Network/Base#getPaymentHistory()
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();
        $filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));


        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://member.impactradius.co.uk/secure/nositemesh/accounting/getPayStubParamsCSV.csv', array());
        $exportReport = $this->_newClient->get($urls);
        $exportData = str_getcsv($exportReport[0], "\n");

        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $paymentExportArray = str_getcsv($exportData[$i], ",");

            $obj = array();

            $date = new \DateTime($paymentExportArray[1], "dd MMM, yyyy");

            $obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
            $obj['pid'] = $paymentExportArray[0];
            $obj['method'] = 'BACS';
            if (preg_match('/[-+]?[0-9]*,?[0-9]*\.?[0-9]+/', $paymentExportArray[6], $matches)) {
                $obj['value'] = $filter->filter($matches[0]);
            } else {
                throw new Exception("Problem reading payments");
            }
            $paymentHistory[] = $obj;
        }
        return $paymentHistory;
    }

}
