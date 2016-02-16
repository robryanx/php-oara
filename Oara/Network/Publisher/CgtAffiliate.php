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
 * @category   CgtAffiliate
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class CgtAffiliate extends \Oara\Network
{


    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;

    /**
     * Constructor and Login
     * @param $credentials
     * @return Daisycon
     */
    public function __construct($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];

        $valuesLogin = array(
            new \Oara\Curl\Parameter('userid', $user),
            new \Oara\Curl\Parameter('password', $password),
        );
        $loginUrl = 'http://www.cgtaffiliate.com/idevaffiliate/login.php';
        $this->_client = new \Oara\Curl\Access($loginUrl, $valuesLogin, $credentials);


    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        //If not login properly the construct launch an exception
        $connection = false;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www.cgtaffiliate.com/idevaffiliate/account.php', array());
        $exportReport = $this->_client->get($urls);

        if (preg_match("/Logout/", $exportReport[0])) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = array();

        $obj = array();
        $obj['cid'] = 1;
        $obj['name'] = "Custom Greek Threads";
        $merchants[] = $obj;

        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {

        $totalTransactions = array();

        $transactionUrl = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www.cgtaffiliate.com/idevaffiliate/account.php?page=4&report=1', array());
        $exportReport = $this->_client->get($urls);
        $totalTransactions = array_merge($totalTransactions, self::readTransactions($exportReport[0]));
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www.cgtaffiliate.com/idevaffiliate/account.php?page=4&report=3', array());
        $exportReport = $this->_client->get($urls);
        $totalTransactions = array_merge($totalTransactions, self::readTransactions($exportReport[0]));
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www.cgtaffiliate.com/idevaffiliate/account.php?page=4&report=4', array());
        $exportReport = $this->_client->get($urls);
        $totalTransactions = array_merge($totalTransactions, self::readTransactions($exportReport[0]));

        return $totalTransactions;
    }

    private function readTransactions($html)
    {
        $totalTransactions = array();

        $dom = new Zend_Dom_Query($html);
        $tableList = $dom->query('table[bgcolor="#003366"][align="center"][width="100%"]');
        $exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
        $num = count($exportData);
        for ($i = 3; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i], ";");

            $transaction = Array();
            $transaction['merchantId'] = 1;
            $transaction['date'] = preg_replace("/[^0-9\-]/", "", $transactionExportArray[0]) . " 00:00:00";

            $transactionExportArray[1] = trim($transactionExportArray[1]);

            if (preg_match("/Paid/", $transactionExportArray[1])) {
                $transaction['status'] = \Oara\Utilities::STATUS_PAID;
            } else if (preg_match("/Pending/", $transactionExportArray[1])) {
                $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
            } else if (preg_match("/Approved/", $transactionExportArray[1])) {
                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            }

            $transaction['amount'] = preg_replace('/[^0-9\.,]/', "", $transactionExportArray[2]);
            $transaction['commission'] = preg_replace('/[^0-9\.,]/', "", $transactionExportArray[2]);
            $totalTransactions[] = $transaction;
        }
        return $totalTransactions;
    }


    /**
     * (non-PHPdoc)
     * @see Oara/Network/Base#getPaymentHistory()
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        return $paymentHistory;
    }

    /**
     *
     * Function that Convert from a table to Csv
     * @param unknown_type $html
     */
    private function htmlToCsv($html)
    {
        $html = str_replace(array("\t", "\r", "\n"), "", $html);
        $csv = "";
        $dom = new Zend_Dom_Query($html);
        $results = $dom->query('tr');
        $count = count($results); // get number of matches: 4
        foreach ($results as $result) {
            $tdList = $result->childNodes;
            $tdNumber = $tdList->length;
            if ($tdNumber > 0) {
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
        }
        $exportData = str_getcsv($csv, "\n");
        return $exportData;
    }

    /**
     *
     * Function that returns the innet HTML code
     * @param unknown_type $element
     */
    private function DOMinnerHTML($element)
    {
        $innerHTML = "";
        $children = $element->childNodes;
        foreach ($children as $child) {
            $tmp_dom = new DOMDocument();
            $tmp_dom->appendChild($tmp_dom->importNode($child, true));
            $innerHTML .= trim($tmp_dom->saveHTML());
        }
        return $innerHTML;
    }

}
