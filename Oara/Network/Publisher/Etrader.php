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
    /**
     * Client
     *
     * @var unknown_type
     */
    private $_client = null;

    /**
     * Constructor and Login
     *
     * @param
     *            $credentials
     * @return PureVPN
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        self::logIn();
    }

    private function logIn()
    {
        $valuesLogin = array(
            new \Oara\Curl\Parameter ('j_username', $this->_credentials ['user']),
            new \Oara\Curl\Parameter ('j_password', $this->_credentials ['password']),
            new \Oara\Curl\Parameter ('_spring_security_remember_me', 'true')
        );


        $loginUrl = 'http://etrader.kalahari.com/login?';
        $this->_client = new \Oara\Curl\Access ($loginUrl, $valuesLogin, $this->_credentials);

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
     * Check the connection
     */
    public function checkConnection()
    {
        // If not login properly the construct launch an exception
        $connection = false;
        $urls = array();
        $urls [] = new \Oara\Curl\Request ('https://etrader.kalahari.com/view/affiliate/home', array());

        $exportReport = $this->_client->get($urls);

        if (preg_match("/signout/", $exportReport [0])) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * (non-PHPdoc)
     *
     * @see library/Oara/Network/Interface#getMerchantList()
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
     * (non-PHPdoc)
     *
     * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();

        $page = 1;
        $continue = true;
        while ($continue) {
            $valuesFormExport = array();
            $valuesFormExport [] = new \Oara\Curl\Parameter ('dateFrom', $dStartDate->toString("dd/MM/yyyy"));
            $valuesFormExport [] = new \Oara\Curl\Parameter ('dateTo', $dEndDate->toString("dd/MM/yyyy"));
            $valuesFormExport [] = new \Oara\Curl\Parameter ('startIndex', $page);
            $valuesFormExport [] = new \Oara\Curl\Parameter ('numberOfPages', '1');

            $urls = array();
            $urls [] = new \Oara\Curl\Request ('https://etrader.kalahari.com/view/affiliate/transactionreport', $valuesFormExport);
            $exportReport = $this->_client->post($urls);

            $dom = new Zend_Dom_Query ($exportReport [0]);
            $results = $dom->query('table');
            $exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));

            if (preg_match("/No results found/", $exportData[1])) {
                $continue = false;
                break;
            } else {
                $page++;
            }

            for ($j = 1; $j < count($exportData); $j++) {

                $transactionDetail = str_getcsv($exportData[$j], ";");
                $transaction = Array();
                $transaction ['merchantId'] = "1";

                if (preg_match("/Order dispatched: ([0-9]+) /", $transactionDetail[2], $match)) {
                    $transaction ['custom_id'] = $match[1];
                }

                $date = new \DateTime($transactionDetail[0], "dd MMM yyyy", "en_GB");
                $transaction ['date'] = $date->toString("yyyy-MM-dd 00:00:00");
                $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;

                if ($transactionDetail[3] != null) {
                    preg_match('/[-+]?[0-9]*\.?[0-9]+/', $transactionDetail[3], $match);
                    $transaction['amount'] = (double)$match[0];
                    $transaction['commission'] = (double)$match[0];

                } else if ($transactionDetail[4] != null) {
                    preg_match('/[-+]?[0-9]*\.?[0-9]+/', $transactionDetail[4], $match);
                    $transaction['amount'] = (double)$match[0];
                    $transaction['commission'] = (double)$match[0];
                }
                $totalTransactions [] = $transaction;

            }


        }
        return $totalTransactions;
    }

    /**
     * (non-PHPdoc)
     *
     * @see Oara/Network/Base#getPaymentHistory()
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        return $paymentHistory;
    }

    /**
     *
     *
     * Function that Convert from a table to Csv
     *
     * @param unknown_type $html
     */
    private function htmlToCsv($html)
    {
        $html = str_replace(array(
            "\t",
            "\r",
            "\n"
        ), "", $html);
        $csv = "";
        $dom = new Zend_Dom_Query ($html);
        $results = $dom->query('tr');
        $count = count($results); // get number of matches: 4
        foreach ($results as $result) {
            $tdList = $result->childNodes;
            $tdNumber = $tdList->length;
            for ($i = 0; $i < $tdNumber; $i++) {
                $value = $tdList->item($i)->nodeValue;
                if ($i != $tdNumber - 1) {
                    $csv .= trim($value) . ";";
                } else {
                    $csv .= trim($value);
                }
            }
            $csv .= "\n";
        }
        $exportData = str_getcsv($csv, "\n");
        return $exportData;
    }

    /**
     *
     *
     * Function that returns the innet HTML code
     *
     * @param unknown_type $element
     */
    private function DOMinnerHTML($element)
    {
        $innerHTML = "";
        $children = $element->childNodes;
        foreach ($children as $child) {
            $tmp_dom = new DOMDocument ();
            $tmp_dom->appendChild($tmp_dom->importNode($child, true));
            $innerHTML .= trim($tmp_dom->saveHTML());
        }
        return $innerHTML;
    }
}
