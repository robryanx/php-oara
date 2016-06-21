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
 * @category   Skimlinks
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Skimlinks extends \Oara\Network
{
    protected $_sitesAllowed = array();
    /**
     * Public API Key
     * @var string
     */
    private $_publicapikey = null;
    /**
     * Private API Key
     * @var string
     */
    private $_privateapikey = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_client = new \Oara\Curl\Access($credentials);
        $this->_publicapikey = $credentials['user'];
        $this->_privateapikey = $credentials['apipassword'];
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
        $parameter["description"] = "API Password";
        $parameter["required"] = true;
        $parameter["name"] = "API";
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
            self::getMerchantList();
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
        $publicapikey = $this->_publicapikey;
        $privateapikey = $this->_privateapikey;

        $date = new \DateTime();
        $timestamp = $date->getTimestamp();
        $authtoken = \md5($timestamp . $privateapikey);

        $merchants = Array();

        $valuesFromExport = array(
            new \Oara\Curl\Parameter('version', '0.5'),
            new \Oara\Curl\Parameter('timestamp', $timestamp),
            new \Oara\Curl\Parameter('apikey', $publicapikey),
            new \Oara\Curl\Parameter('authtoken', $authtoken),
            new \Oara\Curl\Parameter('startdate', '2009-01-01'), //minimum date
            new \Oara\Curl\Parameter('enddate', $date->format("Y-m-d")),
            new \Oara\Curl\Parameter('format', 'json')
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request("https://api-reports.skimlinks.com/publisher/reportmerchants?", $valuesFromExport);
        $exportReport = $this->_client->get($urls);
        $jsonArray = json_decode($exportReport[0], true);

        $iteration = 0;
        while (\count($jsonArray["skimlinksAccount"]["merchants"]) != 0) {
            foreach ($jsonArray["skimlinksAccount"]["merchants"] as $i) {
                $obj = Array();
                $obj['cid'] = $i["merchantID"];
                $obj['name'] = $i["merchantName"];
                $merchants[] = $obj;
            }
            $iteration++;
            $valuesFromExport = array(
                new \Oara\Curl\Parameter('version', '0.5'),
                new \Oara\Curl\Parameter('timestamp', $timestamp),
                new \Oara\Curl\Parameter('apikey', $publicapikey),
                new \Oara\Curl\Parameter('authtoken', $authtoken),
                new \Oara\Curl\Parameter('startdate', '2009-01-01'), //minimum date
                new \Oara\Curl\Parameter('enddate', $date->format("Y-m-d")),
                new \Oara\Curl\Parameter('format', 'json'),
                new \Oara\Curl\Parameter('responseFrom', $iteration * 100),

            );

            $urls = array();
            $urls[] = new \Oara\Curl\Request("https://api-reports.skimlinks.com/publisher/reportmerchants?", $valuesFromExport);
            $exportReport = $this->_client->get($urls);
            $jsonArray = json_decode($exportReport[0], true);
        }


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

        $publicapikey = $this->_publicapikey;
        $privateapikey = $this->_privateapikey;

        $date = new \DateTime();
        $timestamp = $date->getTimestamp();
        $authtoken = md5($timestamp . $privateapikey);

        if (\count($this->_sitesAllowed) == 0) {
            $valuesFromExport = array(
                new \Oara\Curl\Parameter('version', '0.5'),
                new \Oara\Curl\Parameter('timestamp', $timestamp),
                new \Oara\Curl\Parameter('apikey', $publicapikey),
                new \Oara\Curl\Parameter('authtoken', $authtoken),
                new \Oara\Curl\Parameter('startDate', $dStartDate->format("Y-m-d")),
                new \Oara\Curl\Parameter('endDate', $dEndDate->format("Y-m-d")),
                new \Oara\Curl\Parameter('format', 'json')
            );
            $totalTransactions = $this->processTransactions($valuesFromExport);
        } else {
            foreach ($this->_sitesAllowed as $site) {

                $valuesFromExport = array(
                    new \Oara\Curl\Parameter('version', '0.5'),
                    new \Oara\Curl\Parameter('timestamp', $timestamp),
                    new \Oara\Curl\Parameter('apikey', $publicapikey),
                    new \Oara\Curl\Parameter('authtoken', $authtoken),
                    new \Oara\Curl\Parameter('startDate', $dStartDate->format("Y-m-d")),
                    new \Oara\Curl\Parameter('endDate', $dEndDate->format("Y-m-d")),
                    new \Oara\Curl\Parameter('format', 'json'),
                    new \Oara\Curl\Parameter('domainID', $site)
                );
                $totalTransactions = $this->processTransactions($valuesFromExport);
            }
        }

        return $totalTransactions;
    }

    private function processTransactions($valuesFromExport)
    {
        $totalTransactions = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request("https://api-reports.skimlinks.com/publisher/reportcommissions?", $valuesFromExport);
        $exportReport = $this->_client->get($urls);
        $jsonArray = \json_decode($exportReport[0], true);

        foreach ($jsonArray["skimlinksAccount"]["commissions"] as $i) {
            $transaction = Array();

            $transaction['merchantId'] = $i["merchantID"];
            $transaction['unique_id'] = $i["commissionID"];
            $transaction['date'] = $i["date"] . " 00:00:00";
            $transaction['amount'] = (double)$i["orderValue"] / 100;
            $transaction['commission'] = (double)$i["commissionValue"] / 100;
            $transactionStatus = $i["status"];
            if ($transactionStatus == "active") {
                $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            } else if ($transactionStatus == "cancelled") {
                $transaction ['status'] = \Oara\Utilities::STATUS_DECLINED;
            } else {
                throw new \Exception ("New status found {$transactionStatus}");
            }
            if ($i["customID"] != null) {
                $transaction['custom_id'] = $i["customID"];
            }

            $totalTransactions[] = $transaction;
        }
        return $totalTransactions;
    }

}
