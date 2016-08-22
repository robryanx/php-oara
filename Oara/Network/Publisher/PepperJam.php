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
 * @category   PepperJam
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class PepperJam extends \Oara\Network
{
    private $_url = "https://api.pepperjamnetwork.com/20120402/";
    private $_password = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {

        $this->_password = $credentials['apipassword'];
        $this->_client = new \Oara\Curl\Access($credentials);


    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = false;
        $urls = array();
        $urls[] = new \Oara\Curl\Request($this->_url."publisher/advertiser?apiKey={$this->_password}&status=joined&format=json", array());

        try{
            $exportReport = $this->_client->get($urls);
            $connection = true;
        } catch (\Exception $e){

        }
        return $connection;
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "API passwrod";
        $parameter["required"] = true;
        $parameter["name"] = "API Password";
        $credentials["apipassword"] = $parameter;


        return $credentials;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();

        $urls = array();
        $urls[] = new \Oara\Curl\Request($this->_url."publisher/advertiser?apiKey={$this->_password}&status=joined&format=json", array());


        $exportReport = $this->_client->get($urls);
        $merchantList = \json_decode($exportReport[0], true);

        foreach ($merchantList["data"] as $merchant) {

            $obj = Array();
            $obj['cid'] = $merchant["id"];
            $obj['name'] = $merchant["name"];
            $merchants[] = $obj;
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
        $totalTransactions = Array();

        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);
        $urls = array();
        $urls[] = new \Oara\Curl\Request($this->_url."publisher/report/transaction-details?apiKey={$this->_password}&startDate={$dStartDate->format("Y-m-d")}&endDate={$dEndDate->format("Y-m-d")}&joined&format=json", array());
        $exportReport = $this->_client->get($urls);
        $transactionList = \json_decode($exportReport[0], true);


        foreach($transactionList["data"] as $transactionExportArray) {
            if (isset($merchantIdList[(int)$transactionExportArray["program_id"]])) {
                $transaction = Array();
                $merchantId = (int)$transactionExportArray["program_id"];
                $transaction['merchantId'] = $merchantId;
                $transaction['date'] = $transactionExportArray["date"];
                $transaction['unique_id'] = $transactionExportArray["transaction_id"];
                if ($transactionExportArray["sid"] != null) {
                    $transaction['custom_id'] = $transactionExportArray["sid"];
                }
                $status = $transactionExportArray["status"];
                if ($status == 'pending' || $status == 'delayed' || $status == 'unconfirmed') {
                    $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                } elseif ($status == 'locked') {
                    $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                } elseif ($status == 'paid') {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else {
                    throw new \Exception("Status {$status} unknown");
                }
                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray["sale_amount"]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray["commission"]);
                $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;
    }

}
