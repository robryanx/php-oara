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
 * @category DirectTrack
 * @copyright Fubra Limited
 * @version Release: 01.00
 *
 *
 */
class DirectTrack extends \Oara\Network
{

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        ini_set('default_socket_timeout', '120');
        $this->_version = '1_0';
        $this->_domain = $credentials["domain"];
        $this->_clientId = $credentials["client"];
        $this->_accessId = $credentials["access"];
        $this->_username = $credentials["user"];
        $this->_password = $credentials["password"];

    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "Domain for the networks Ex:www.myweb.com";
        $parameter["required"] = true;
        $parameter["name"] = "Domain";
        $credentials["domain"] = $parameter;

        $parameter = array();
        $parameter["description"] = "Id for the client";
        $parameter["required"] = true;
        $parameter["name"] = "Client";
        $credentials["client"] = $parameter;

        $parameter = array();
        $parameter["description"] = "Access ID";
        $parameter["required"] = true;
        $parameter["name"] = "Access";
        $credentials["access"] = $parameter;

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
        $connection = false;
        $apiURL = "https://{$this->_domain}/apifleet/rest/{$this->_clientId}/{$this->_accessId}/campaign/active/";
        $response = self::call($apiURL);
        if (isset($response["@attributes"])) {
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
        $obj = Array();
        $obj ['cid'] = "1";
        $obj ['name'] = "DirectTrack";
        $merchants [] = $obj;
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

        $amountDays = $dStartDate->diff($dEndDate)->days;
        $auxDate = clone $dStartDate;

        for ($j = 0; $j <= $amountDays; $j++) {

            $apiURL = "https://{$this->_domain}/apifleet/rest/{$this->_clientId}/{$this->_accessId}/statCampaign/quick/{$auxDate->format("Y-m-d")}";
            $response = self::call($apiURL);

            if (isset($response["resource"]["numSales"])) {

                $transaction = Array();
                $transaction ['merchantId'] = "1";
                $transaction ['date'] = $auxDate->format("Y-m-d H:i:s");
                $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                $transaction ['amount'] = $response["resource"]["saleAmount"];
                $transaction ['commission'] = $response["resource"]["theyGet"];
                $transaction ['currency'] = $response["resource"]["currency"];

                if ($transaction ['amount'] != 0 && $transaction ['commission'] != 0) {
                    $totalTransactions [] = $transaction;
                }
            }

            $auxDate->add(new \DateInterval('P1D'));

        }
        return $totalTransactions;
    }

    private function call($apiUrl)
    {
        $headers[] = "Authorization: Basic " . \base64_encode($this->_username . ":" . $this->_password);

        // Initiate the REST call via curl
        $ch = \curl_init($apiUrl);

        // Set the HTTP method to GET
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        // Add the headers defined above
        \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // Don't return headers
        \curl_setopt($ch, CURLOPT_HEADER, false);
        // Return data after call is made
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the REST call
        $response = \curl_exec($ch);
        $data = \simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = \json_encode($data);
        $array = \json_decode($json, true);
        // Close the connection
        \curl_close($ch);
        return $array;
    }
}