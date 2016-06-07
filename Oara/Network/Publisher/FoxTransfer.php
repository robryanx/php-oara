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
 * @category   FoxTransfer
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class FoxTransfer extends \Oara\Network
{

    private $_credentials = null;
    private $_client = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_client = new \Oara\Curl\Access($credentials);

        $this->_credentials = $credentials;

        $valuesLogin = array(
            new \Oara\Curl\Parameter('action', "user_login"),
            new \Oara\Curl\Parameter('email', $this->_credentials['user']),
            new \Oara\Curl\Parameter('password', $this->_credentials['password']),
        );
        $loginUrl = 'https://foxtransfer.eu/index.php?page=login&out=1&language=1';
        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $this->_client->post($urls);

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
        //If not login properly the construct launch an exception
        $connection = true;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://foxtransfer.eu/index.php?language=1', array());
        $exportReport = $this->_client->get($urls);
        if (!\preg_match("/Log Out/", $exportReport[0], $match)) {
            $connection = false;
        }
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();

        $obj = array();
        $obj['cid'] = "1";
        $obj['name'] = "FoxTransfer";
        $obj['url'] = "http://www.foxtransfer.eu";
        $merchants[] = $obj;

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

        $urls = array();
        $url = "https://foxtransfer.eu/index.php?q=prices.en.html&page=affiliate_orders&language=1&basedir=theme2&what=record_time&what=record_time&fy={$dStartDate->format("Y")}&fm={$dStartDate->format("n")}&fd={$dStartDate->format("j")}&ty={$dEndDate->format("Y")}&tm={$dEndDate->format("n")}&td={$dEndDate->format("j")}";
        $urls[] = new \Oara\Curl\Request($url, array());
        $exportReport = $this->_client->get($urls);
        $exportReport = \str_replace("<?xml version=\"1.0\" encoding=\"UTF-8\"?>", "", $exportReport[0]);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport);
        $xpath = new \DOMXPath($doc);
        $tableList = $xpath->query('//*[contains(concat(" ", normalize-space(@id), " "), " tartalom-hatter ")]');
        $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($tableList->item(0)));
        $num = \count($exportData);
        for ($i = 11; $i < $num - 8; $i++) {

            $transactionExportArray = \str_getcsv($exportData[$i], ";");
            $transaction = Array();
            $transaction['merchantId'] = 1;
            $transaction['unique_id'] = $transactionExportArray[0];
            $transaction['date'] = "{$dStartDate->format("Y")}-{$dStartDate->format("m")}-01 00:00:00";
            if ($transactionExportArray[7] == "Confirmed") {
                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            } else if ($transactionExportArray[7] == "Cancelled") {
                $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
            } else {
                throw new \Exception("New status found {$transaction['status']}");
            }

            $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[10]);
            $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[13]);
            $totalTransactions[] = $transaction;

        }

        return $totalTransactions;
    }

}