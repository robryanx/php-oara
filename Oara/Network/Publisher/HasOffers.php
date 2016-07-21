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
 * @author Carlos Morillo Merino
 * @category HasOffers
 * @copyright Fubra Limited
 * @version Release: 01.00
 *
 *
 */
class HasOffers extends \Oara\Network
{
    private $_domain = null;
    private $_apiPassword = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        \ini_set('default_socket_timeout', '120');

        $this->_domain = $credentials["domain"];
        $this->_apiPassword = $credentials["apipassword"];

    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "Your Network ID ";
        $parameter["required"] = true;
        $parameter["name"] = "Domain";
        $credentials["domain"] = $parameter;

        $parameter = array();
        $parameter["description"] = "API Key";
        $parameter["required"] = true;
        $parameter["name"] = "API";
        $credentials["apipassword"] = $parameter;

        return $credentials;
    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;

        $apiURL = "http://api.hasoffers.com/v3/Affiliate_Offer.json?Method=findMyOffers&api_key={$this->_apiPassword}&NetworkId={$this->_domain}";
        $response = self::call($apiURL);
        if (\count($response["response"]["errors"]) == 0) {
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
        $apiURL = "http://api.hasoffers.com/v3/Affiliate_Offer.json?Method=findMyOffers&api_key={$this->_apiPassword}&NetworkId={$this->_domain}";
        $response = self::call($apiURL);

        foreach ($response["response"]["data"] as $merchant) {

            $obj = Array();
            $obj ['cid'] = $merchant["Offer"]["id"];
            $obj ['name'] = $merchant["Offer"]["name"];
            $merchants [] = $obj;
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
        $totalTransactions = array();

        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);

        $limit = 100;
        $page = 1;
        $loop = true;
        while ($loop) {

            $apiURL = "http://api.hasoffers.com/v3/Affiliate_Report.json?limit=$limit&page=$page&Method=getConversions&api_key={$this->_apiPassword}&NetworkId={$this->_domain}&fields[]=Stat.offer_id&fields[]=Stat.datetime&fields[]=Offer.name&fields[]=Stat.conversion_status&fields[]=Stat.payout&fields[]=Stat.conversion_sale_amount&fields[]=Stat.ip&fields[]=Stat.ad_id&fields[]=Stat.affiliate_info1&sort[Stat.datetime]=desc&filters[Stat.date][conditional]=BETWEEN&filters[Stat.date][values][]={$dStartDate->format("Y-m-d")}&filters[Stat.date][values][]={$dEndDate->format("Y-m-d")}&data_start={$dStartDate->format("Y-m-d")}&data_end={$dEndDate->format("Y-m-d")}";

            $response = self::call($apiURL);
            foreach ($response["response"]["data"]["data"] as $transactionApi) {

                $transaction = Array();
                $merchantId = (int)$transactionApi["Stat"]["offer_id"];

                if ($merchantList == null || isset($merchantIdList[$merchantId])) {
                    $transaction['merchantId'] = $merchantId;
                    $transaction['date'] = $transactionApi["Stat"]["datetime"];

                    if ($transactionApi["Stat"]["ad_id"] != null) {
                        $transaction['unique_id'] = $transactionApi["Stat"]["ad_id"];
                    }

                    if ($transactionApi["Stat"]["affiliate_info1"] != null) {
                        $transaction['custom_id'] = $transactionApi["Stat"]["affiliate_info1"];
                    }

                    if ($transactionApi["Stat"]["conversion_status"] == "approved") {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else
                        if ($transactionApi["Stat"]["conversion_status"] == "pending") {
                            $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        } else
                            if ($transactionApi["Stat"]["conversion_status"] == "rejected") {
                                $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                            }
                    $transaction['amount'] = $transactionApi["Stat"]["payout"];
                    if (isset($transactionApi["Stat"]["conversion_sale_amount"])) {
                        $transaction['amount'] = $transactionApi["Stat"]["conversion_sale_amount"];
                    }
                    $transaction['commission'] = $transactionApi["Stat"]["payout"];
                    $totalTransactions[] = $transaction;
                }

            }
            if ((int)$response["response"]["data"]["pageCount"] <= $page) {
                $loop = false;
            }
            $page++;

        }
        return $totalTransactions;
    }

    /**
     * @param $apiUrl
     * @return mixed
     */
    private function call($apiUrl)
    {
        // Initiate the REST call via curl
        $ch = \curl_init($apiUrl);
        // Set the HTTP method to GET
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        // Don't return headers
        \curl_setopt($ch, CURLOPT_HEADER, false);
        // Return data after call is made
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Execute the REST call
        $response = \curl_exec($ch);
        $array = \json_decode($response, true);
        // Close the connection
        \curl_close($ch);
        return $array;
    }
}
