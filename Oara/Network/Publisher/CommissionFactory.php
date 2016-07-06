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
 * @category   CommissionFactory
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class CommissionFactory extends \Oara\Network
{

    private $_apiKey = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_apiKey = $credentials ['apipassword'];
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = false;
        // If not login properly the construct launch an exception
        $result = self::request("https://api.commissionfactory.com.au/V1/Affiliate/Merchants?apiKey={$this->_apiKey}&status=Joined");
        if (count($result) > 0) {
            $connection = true;
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
        $parameter["description"] = "API password";
        $parameter["required"] = true;
        $parameter["name"] = "API";
        $credentials["apipassword"] = $parameter;

        return $credentials;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = Array();

        $merchantExportList = self::request("https://api.commissionfactory.com.au/V1/Affiliate/Merchants?apiKey={$this->_apiKey}&status=Joined");
        foreach ($merchantExportList as $merchant) {
            $obj = Array();
            $obj ['cid'] = $merchant ['Id'];
            $obj ['name'] = $merchant ['Name'];
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
        $transactions = array();
        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);
        $transactionsExportList = self::request("https://api.commissionfactory.com.au/V1/Affiliate/Transactions?apiKey={$this->_apiKey}&fromDate={$dStartDate->format("Y-m-d")}&toDate={$dEndDate->format("Y-m-d")}");

        foreach ($transactionsExportList as $transaction) {
            if (isset($merchantIdList[$transaction ["MerchantId"]])) {

                $obj = Array();
                $obj ['merchantId'] = $transaction ["MerchantId"];
                $transactionDate = \DateTime::createFromFormat("Y-m-d\TH:i:s", \substr($transaction["DateCreated"], 0, 19));
                $obj ['date'] = $transactionDate->format("Y-m-d H:i:s");
                if ($transaction ["UniqueId"] != null) {
                    $obj ['custom_id'] = $transaction ["UniqueId"];
                }
                $obj ['unique_id'] = $transaction ["Id"];
                if ($transaction ["Status"] == "Approved") {
                    $obj ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else if ($transaction ["Status"] == "Pending") {
                    $obj ['status'] = \Oara\Utilities::STATUS_PENDING;
                } else if ($transaction ["Status"] == "Void") {
                    $obj ['status'] = \Oara\Utilities::STATUS_DECLINED;
                }

                $obj ['amount'] = \Oara\Utilities::parseDouble($transaction ["SaleValue"]);
                $obj ['commission'] = \Oara\Utilities::parseDouble($transaction ["Commission"]);
                $transactions [] = $obj;
            }
        }

        return $transactions;
    }

    /**
     * @return array
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        $today = new \DateTime();
        $paymentExportList = self::request("https://api.commissionfactory.com.au/V1/Affiliate/Payments?apiKey={$this->_apiKey}&fromDate=2000-01-01&toDate={$today->format("Y-m-d")}");

        foreach ($paymentExportList as $payment) {
            $obj = array();
            $date = \DateTime::createFromFormat("Y-m-d\TH:i:s", \substr($payment["DateCreated"], 0, 19));
            $obj ['date'] = $date->format("Y-m-d H:i:s");
            $obj ['pid'] = $payment["Id"];
            $obj ['value'] = $payment["Amount"];
            $obj ['method'] = 'BACS';
            $paymentHistory [] = $obj;
        }

        return $paymentHistory;
    }

    public function request($url)
    {
        $options = array(
            CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true
        );
        $rch = \curl_init();
        \curl_setopt($rch, CURLOPT_URL, $url);
        \curl_setopt_array($rch, $options);
        $response = \curl_exec($rch);
        \curl_close($rch);
        return \json_decode($response, true);
    }
}
