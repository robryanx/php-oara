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
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Wg
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class WebGains extends \Oara\Network
{

    /**
     * Web client.
     */
    private $_webClient = null;

    private $_merchantId = null;

    private $_currency = null;

    /**
     * Server.
     */
    private $_server = null;
    /**
     * Export Merchant Parameters
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
     * Array with the id from the campaigns
     * @var array
     */
    private $_campaignMap = array();

    /**
     * Constructor.
     * @param $webgains
     * @return Wg_Api
     */
    public function __construct($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_currency = $credentials["currency"];

        $serverArray = array();
        $serverArray["uk"] = 'www.webgains.com';
        $serverArray["fr"] = 'www.webgains.fr';
        $serverArray["us"] = 'us.webgains.com';
        $serverArray["de"] = 'www.webgains.de';
        $serverArray["fr"] = 'www.webgains.fr';
        $serverArray["nl"] = 'www.webgains.nl';
        $serverArray["dk"] = 'www.webgains.dk';
        $serverArray["se"] = 'www.webgains.se';
        $serverArray["es"] = 'www.webgains.es';
        $serverArray["ie"] = 'www.webgains.ie';
        $serverArray["it"] = 'www.webgains.it';

        $loginUrlArray = array();
        $loginUrlArray["uk"] = 'https://www.webgains.com/loginform.html?action=login';
        $loginUrlArray["fr"] = 'https://www.webgains.fr/loginform.html?action=login';
        $loginUrlArray["us"] = 'https://us.webgains.com/loginform.html?action=login';
        $loginUrlArray["de"] = 'https://www.webgains.de/loginform.html?action=login';
        $loginUrlArray["fr"] = 'https://www.webgains.fr/loginform.html?action=login';
        $loginUrlArray["nl"] = 'https://www.webgains.nl/loginform.html?action=login';
        $loginUrlArray["dk"] = 'https://www.webgains.dk/loginform.html?action=login';
        $loginUrlArray["se"] = 'https://www.webgains.se/loginform.html?action=login';
        $loginUrlArray["es"] = 'https://www.webgains.es/loginform.html?action=login';
        $loginUrlArray["ie"] = 'https://www.webgains.ie/loginform.html?action=login';
        $loginUrlArray["it"] = 'https://www.webgains.it/loginform.html?action=login';

        $valuesLogin = array(
            new \Oara\Curl\Parameter('user_type', 'agencyuser'),
            new \Oara\Curl\Parameter('username', $user),
            new \Oara\Curl\Parameter('password', $password)
        );

        foreach ($loginUrlArray as $country => $url) {
            $this->_webClient = new \Oara\Curl\Access($url, $valuesLogin, $credentials);
            if (preg_match("/logout.html/", $this->_webClient->getConstructResult())) {
                $this->_server = $serverArray[$country];
                break;
            }
        }

        $this->_exportMerchantParameters = array('username' => $user,
            'password' => $password
        );
        $this->_exportTransactionParameters = array('username' => $user,
            'password' => $password
        );
        $this->_exportOverviewParameters = array('username' => $user,
            'password' => $password
        );

    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;
        if ($this->_server != null) {
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
        $this->_campaignMap = self::getCampaignMap();

        $merchantList = Array();
        /*
        foreach ($this->_campaignMap as $campaignKey => $campaignValue) {
            $merchants = $this->_soapClient->getProgramsWithMembershipStatus($this->_exportMerchantParameters['username'], $this->_exportMerchantParameters['password'], $campaignKey);
            foreach ($merchants as $merchant) {
                if ($merchant->programMembershipStatusName == 'Live' || $merchant->programMembershipStatusName == 'Joined') {
                    $merchantList[$merchant->programID] = $merchant;
                }

            }
        }
        $merchantList = \Oara\Utilities::soapConverter($merchantList, $this->_merchantConverterConfiguration);
        */
        return $merchantList;
    }

    /**
     * Get the campaings identifiers and returns it in an array.
     * @return array
     */
    private function getCampaignMap()
    {
        $campaingMap = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request("http://{$this->_server}/affiliates/report.html?f=0&action=sf", array());
        $exportReport = $this->_webClient->get($urls);
        $matches = array();
        if (preg_match("/<select name=\"campaignswitchid\" class=\"formelement\" style=\"width:134px\">([^\t]*)<\/select>/", $exportReport[0], $matches)) {

            if (preg_match_all("/<option value=\"(.*)\" .*>(.*)<\/option>/", $matches[1], $matches)) {
                $campaingNumber = count($matches[1]);
                $i = 0;
                while ($i < $campaingNumber) {
                    if (in_array($matches[2][$i], $this->_sitesAllowed)) {
                        $campaingMap[$matches[1][$i]] = $matches[2][$i];
                    }

                    $i++;
                }
            } else {
                throw new Exception('No campaigns found');
            }

        } else {
            throw new Exception("No campaigns found");
        }
        return $campaingMap;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = Array();

        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('action', 'earnings');
        $valuesFromExport[] = new \Oara\Curl\Parameter('mode', 'generate');
        $valuesFromExport[] = new \Oara\Curl\Parameter('columnsSelected', 'affiliate,merchant,program,commission,value,date,orderReference,clickthroughTime,productId,transactionId,status');
        $valuesFromExport[] = new \Oara\Curl\Parameter('period', 'custom');
        $valuesFromExport[] = new \Oara\Curl\Parameter('startday', $dStartDate->toString("d"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('startmonth', $dStartDate->toString("M"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('startyear', $dStartDate->toString("yyyy"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('endday', $dEndDate->toString("d"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('endmonth', $dEndDate->toString("M"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('endyear', $dEndDate->toString("yyyy"));
        foreach ($merchantList as $merchantId) {
            $valuesFromExport[] = new \Oara\Curl\Parameter('program[]', $merchantId);
        }

        $valuesFromExport[] = new \Oara\Curl\Parameter('format', 'csv');
        $valuesFromExport[] = new \Oara\Curl\Parameter('currency', 'USD');
        $valuesFromExport[] = new \Oara\Curl\Parameter('status[]', '10');
        $valuesFromExport[] = new \Oara\Curl\Parameter('status[]', '20');
        $valuesFromExport[] = new \Oara\Curl\Parameter('status[]', '25');
        $valuesFromExport[] = new \Oara\Curl\Parameter('eventType', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('invoice', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('orderReference', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('resultsperpage', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('numdecimalplaces', '2');
        $urls = array();
        $urls [] = new \Oara\Curl\Request ("http://www.webgains.com/merchants/{$this->_merchantId}/report.html?", $valuesFromExport);
        $exportReport = $this->_webClient->get($urls);

        $exportData = str_getcsv($exportReport[0], "\n");
        $num = count($exportData);
        for ($i = 1; $i < $num - 5; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i], ",");


            if (in_array($transactionExportArray[4], $merchantList) && is_numeric($transactionExportArray[8])) {

                $transaction = array();
                $transaction['merchantId'] = $transactionExportArray[4];
                $transactionDate = new \DateTime($transactionExportArray[9], "dd/MM/yy HH:mm:ss");
                $transaction["date"] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
                $transaction['unique_id'] = $transactionExportArray[13];

                $transaction['status'] = null;
                $transaction['amount'] = $transactionExportArray[8];
                $transaction['commission'] = $transactionExportArray[6];

                $transaction['custom_id'] = $transactionExportArray[10];

                if (in_array($transactionExportArray[14], array('Paid to affiliate', 'Cleared for Payment', 'Adjusted - Cleared for Payment', 'Adjusted - Awaiting Payment'))) {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else
                    if (in_array($transactionExportArray[14], array('In Recall Period', 'Recall Expires', 'Delayed until', 'Awaiting Invoice Settlement', 'Awaiting Invoice', 'Invoiced - awaiting payment'))) {
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else
                        if ($transactionExportArray[14] == 'Cancelled') {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        } else {
                            throw new Exception("Error in the transaction status {$transactionExportArray[14]}");
                        }
                $totalTransactions[] = $transaction;
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

        return $paymentHistory;
    }
}
