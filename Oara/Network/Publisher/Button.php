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
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Button
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Button extends \Oara\Network
{
    /**
     * Api Key
     * @var string
     */
    private $_api = null;
    private $_accountsMap = null;
    private $_merchantMap = null;

    /**
     * @param $credentials
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    public function login($credentials)
    {
        $this->_api = $credentials["apipassword"];
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "Publisher API Key, found here: https://app.usebutton.com/settings/organization";
        $parameter["required"] = true;
        $parameter["name"] = "API Key";
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
            $result = self::returnApiData("https://api.usebutton.com/v1/affiliation/accounts");
            if (isset($result->objects)) {
                foreach ($result->objects as $account) {
                    $this->_accountsMap [$account->id] = $account;
                }
            }
            $connection = true;
        } catch (\Exception $e) {

        }
        return $connection;
    }

    /**
     * @param $location
     * @return array
     * @throws \Exception
     */
    private function returnApiData($location)
    {
        $result = null;
        $ch = \curl_init();
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_URL, $location);
        \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $key = base64_encode("{$this->_api}:");
        \curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Basic " . $key));
        $result = \curl_exec($ch);
        \curl_close($ch);
        $result = \json_decode($result);
        if (isset($result->error) && count($result->error) > 0) {
            throw new \Exception("Error in request");
        }
        return $result;

    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();
        $result = self::returnApiData("https://api.usebutton.com/v1/merchants?status=approved");
        if (isset($result->objects)) {
            foreach ($result->objects as $merchant) {
                $obj = array();
                $hexstr = unpack('H*', $merchant->id);
                $hexstr = array_shift($hexstr);
                $obj['cid'] = (int)substr(base_convert($hexstr, 16, 10), -10, 10);
                $obj['name'] = $merchant->name;

                $this->_merchantMap[$merchant->id] = $obj['cid'];
                $merchants[] = $obj;
            }
        }


        return $merchants;
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
        $totalTransactions = array();
        foreach ($this->_accountsMap as $id => $account) {
            $transactionList = self::returnApiData("https://api.usebutton.com/v1/affiliation/accounts/$id/transactions?start={$dStartDate->format("Y-m-d\TH:i:s\Z")}&end={$dEndDate->format("Y-m-d\TH:i:s\Z")}");
            $continue = true;
            while ($continue) {
                if (isset($transactionList->objects)) {
                    foreach ($transactionList->objects as $transactionObject) {

                        if (isset($this->_merchantMap[$transactionObject->commerce_organization])) {
                            $transaction = Array();
                            $transaction['unique_id'] = $transactionObject->id;
                            $transaction['merchantId'] = $this->_merchantMap[$transactionObject->commerce_organization];
                            $date = new \DateTime($transactionObject->created_date);
                            $transaction['date'] = $date->format("Y-m-d H:i:s");

                            if (isset($transactionObject->pub_ref) && $transactionObject->pub_ref != null) {
                                $transaction['custom_id'] = $transactionObject->pub_ref;
                            }
                            $transaction['status'] = $transactionObject->status;
                            if ($transaction['status'] == "pending") {
                                $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                            } else if ($transaction['status'] == "validated") {
                                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                            } else if ($transaction['status'] == "declined") {
                                $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                            } else {
                                throw  new \Exception("new status {$transaction['status']}");
                            }

                            $transaction['amount'] = $transactionObject->order_total/100;
                            $transaction['commission'] = $transactionObject->amount/100;

                            if (isset($transactionObject->currency)) {
                                $transaction['currency'] = $transactionObject->currency;
                            }
                            $totalTransactions[] = $transaction;
                        }
                    }
                }

                if (isset($transactionList->meta) && $transactionList->meta->next != null) {
                    $transactionList = self::returnApiData($transactionList->meta->next);
                } else {
                    $continue = false;
                }
            }
        }
        return $totalTransactions;
    }
}
