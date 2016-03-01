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
class Simpl extends \Oara\Network
{
    /**
     * Private API Key
     * @var string
     */
    private $_credentials = null;
    private $_client = null;

    /**
     * Constructor and Login
     * @param $credentials
     * @return Daisycon
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($credentials);

    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["user"]["description"] = "User Log in";
        $parameter["user"]["required"] = true;
        $credentials[] = $parameter;

        $parameter = array();
        $parameter["password"]["description"] = "Password to Log in";
        $parameter["password"]["required"] = true;
        $credentials[] = $parameter;

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

        $merchants = Array();

        $urls = array();
        $urls[] = new \Oara\Curl\Request("https://export.net.simpl.ie/{$this->_credentials['apiPassword']}/mlist_12807.xml?", array());
        $exportReport = $this->_client->get($urls);

        $merchantArray = \json_decode(\json_encode((array)\simplexml_load_string($exportReport[0])), 1);
        foreach ($merchantArray["merchant"] as $merchant) {
            $obj = Array();
            $obj['cid'] = $merchant["mid"];
            $obj['name'] = $merchant["title"];
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

        $totalTransactions = array();

        $valuesFromExport = array(
            new \Oara\Curl\Parameter('filter[zeitraumAuswahl]', "absolute"),
            new \Oara\Curl\Parameter('filter[zeitraumvon]', $dStartDate->format("d.m.Y")),
            new \Oara\Curl\Parameter('filter[zeitraumbis]', $dEndDate->format("d.m.Y")),
            new \Oara\Curl\Parameter('filter[currencycode]', 'EUR')
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request( "https://export.net.simpl.ie/{$this->_credentials['apiPassword']}/statstransaction_12807.xml?", $valuesFromExport);
        $exportReport = $this->_client->get($urls);

        $transactionArray = \json_decode(\json_encode((array)\simplexml_load_string($exportReport[0])), 1);
        foreach ($transactionArray["transaction"] as $trans) {
            $transaction = Array();
            $transaction['merchantId'] = $trans["merchant_id"];
            $transaction['unique_id'] = $trans["conversionid"];
            $transaction['date'] = \substr($trans["trackingtime"], 0, 19);
            $transaction['amount'] = (double)$trans["revenue"];
            $transaction['commission'] = (double)$trans["commissionvalue"];
            if ($trans["subid"] != null) {
                $transaction['custom_id'] = $trans["subid"];
            }

            $transactionStatus = $trans["status"];
            if ($transactionStatus == "open") {
                $transaction ['status'] = \Oara\Utilities::STATUS_PENDING;
            } else if ($transactionStatus == "cancelled") {
                $transaction ['status'] = \Oara\Utilities::STATUS_DECLINED;
            } else if ($transactionStatus == "paid") {
                $transaction ['status'] = \Oara\Utilities::STATUS_PAID;
            } else if ($transactionStatus == "confirmed") {
                $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            } else {
                throw new \Exception ("New status found {$transactionStatus}");
            }
            $totalTransactions[] = $transaction;

        }
        return $totalTransactions;
    }

}
