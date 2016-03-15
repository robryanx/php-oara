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
 * @category   Etrader
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Etrader extends \Oara\Network
{
    private $_credentials = null;
    private $_client = null;

    /**
     * @param $credentials
     * @throws \Exception
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($credentials);

        $valuesLogin = array(
            new \Oara\Curl\Parameter ('j_username', $this->_credentials ['user']),
            new \Oara\Curl\Parameter ('j_password', $this->_credentials ['password']),
            new \Oara\Curl\Parameter ('_spring_security_remember_me', 'true')
        );
        $loginUrl = 'http://etrader.kalahari.com/login?';


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
        // If not login properly the construct launch an exception
        $connection = false;
        $urls = array();
        $urls [] = new \Oara\Curl\Request ('https://etrader.kalahari.com/view/affiliate/home', array());

        $exportReport = $this->_client->get($urls);

        if (\preg_match("/signout/", $exportReport [0])) {
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

        $obj = array();
        $obj ['cid'] = "1";
        $obj ['name'] = "eTrader";
        $obj ['url'] = "https://etrader.kalahari.com";
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

        $page = 1;
        $continue = true;
        while ($continue) {
            $valuesFormExport = array();
            $valuesFormExport [] = new \Oara\Curl\Parameter ('dateFrom', $dStartDate->format("d/m/Y"));
            $valuesFormExport [] = new \Oara\Curl\Parameter ('dateTo', $dEndDate->format("d/m/Y"));
            $valuesFormExport [] = new \Oara\Curl\Parameter ('startIndex', $page);
            $valuesFormExport [] = new \Oara\Curl\Parameter ('numberOfPages', '1');

            $urls = array();
            $urls [] = new \Oara\Curl\Request ('https://etrader.kalahari.com/view/affiliate/transactionreport', $valuesFormExport);
            $exportReport = $this->_client->post($urls);

            $doc = new \DOMDocument();
            @$doc->loadHTML($exportReport[0]);
            $xpath = new \DOMXPath($doc);
            $results = $xpath->query('//table');
            $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($results->item(0)));

            if (\preg_match("/No results found/", $exportData[1])) {
                break;
            } else {
                $page++;
            }

            for ($j = 1; $j < \count($exportData); $j++) {

                $transactionDetail = \str_getcsv($exportData[$j], ";");
                $transaction = Array();
                $transaction ['merchantId'] = "1";

                if (\preg_match("/Order dispatched: ([0-9]+) /", $transactionDetail[2], $match)) {
                    $transaction ['custom_id'] = $match[1];
                }

                $date = \DateTime::createFromFormat("d M Y", $transactionDetail[0]);
                $transaction ['date'] = $date->format("Y-m-d 00:00:00");
                $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;

                if ($transactionDetail[3] != null) {
                    $transaction['amount'] = \Oara\Utilities::parseDouble($transactionDetail[3]);
                    $transaction['commission'] = \Oara\Utilities::parseDouble($transactionDetail[3]);
                } else if ($transactionDetail[4] != null) {
                    $transaction['amount'] = \Oara\Utilities::parseDouble($transactionDetail[4]);
                    $transaction['commission'] = \Oara\Utilities::parseDouble($transactionDetail[4]);
                }
                $totalTransactions [] = $transaction;

            }


        }
        return $totalTransactions;
    }

}
