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
 * @category   Dianomi
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Dianomi extends \Oara\Network
{
    /**
     * Export client.
     * @var \Oara\Curl\Access
     */
    private $_client = null;

    /**
     * Constructor and Login
     * @param $cartrawler
     * @return Tv_Export
     */
    public function login($credentials)
    {

        $user = $credentials['user'];
        $password = $credentials['password'];

        $valuesLogin = array(new \Oara\Curl\Parameter('username', $user),
            new \Oara\Curl\Parameter('password', $password),
            new \Oara\Curl\Parameter('app', 'loginbox'),
            new \Oara\Curl\Parameter('page', '378'),
            new \Oara\Curl\Parameter('partner', '1'),
            new \Oara\Curl\Parameter('redir', '')
        );

        $loginUrl = 'https://my.dianomi.com/index.epl?';
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
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://my.dianomi.com/Campaign-Analysis-378_1.html?', array());
        $exportReport = $this->_client->get($urls);
        if (preg_match("/app=logout&amp;page=378&amp;partner=1/", $exportReport[0], $matches)) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = Array();
        $obj = Array();
        $obj['cid'] = 1;
        $obj['name'] = 'Dianomi';
        $merchants[] = $obj;

        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = Array();

        $valuesFormExport = array();
        $valuesFormExport[] = new \Oara\Curl\Parameter('periodtype', "fromtolong");
        $valuesFormExport[] = new \Oara\Curl\Parameter('fromday', $dStartDate->format!("dd"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('frommonth', $dStartDate->format!("MM"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('fromyear', $dStartDate->format!("yyyy"));

        $valuesFormExport[] = new \Oara\Curl\Parameter('today', $dEndDate->format!("dd"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('tomonth', $dEndDate->format!("MM"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('toyear', $dEndDate->format!("yyyy"));

        $valuesFormExport[] = new \Oara\Curl\Parameter('Go', 'Go');

        $valuesFormExport[] = new \Oara\Curl\Parameter('partnerId', '326');
        $valuesFormExport[] = new \Oara\Curl\Parameter('action', 'partnerLeads');
        $valuesFormExport[] = new \Oara\Curl\Parameter('subaction', 'RevenueOverTime');
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://my.dianomi.com/Campaign-Analysis-378_1.html?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $dom = new Zend_Dom_Query($exportReport[0]);
        $results = $dom->query('.tabular');
        if (count($results) > 0) {
            $exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));
            $num = count($exportData);
            for ($i = 1; $i < $num; $i++) {
                $overviewExportArray = str_getcsv($exportData[$i], ";");

                $transaction = Array();

                $transaction['merchantId'] = 1;
                $date = new \DateTime($overviewExportArray[0], "yyyy-MM-dd");
                $transaction['date'] = $date->format!("yyyy-MM-dd HH:mm:ss");
                $transaction['amount'] = $overviewExportArray[1];
                $transaction['commission'] = $overviewExportArray[1];
                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                $totalTransactions[] = $transaction;
            }
        }


        return $totalTransactions;

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
