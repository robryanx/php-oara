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
     * @var null
     */
    private $_client = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {

        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_client = new \Oara\Curl\Access($credentials);

        $valuesLogin = array(new \Oara\Curl\Parameter('username', $user),
            new \Oara\Curl\Parameter('password', $password),
            new \Oara\Curl\Parameter('app', 'loginbox'),
            new \Oara\Curl\Parameter('page', '378'),
            new \Oara\Curl\Parameter('partner', '1'),
            new \Oara\Curl\Parameter('redir', '')
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://my.dianomi.com/index.epl?', $valuesLogin);
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
        $connection = false;

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://my.dianomi.com/Campaign-Analysis-378_1.html?', array());
        $exportReport = $this->_client->get($urls);
        if (\preg_match("/app=logout&amp;page=378&amp;partner=1/", $exportReport[0], $matches)) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * @return array
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
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = Array();

        $valuesFormExport = array();
        $valuesFormExport[] = new \Oara\Curl\Parameter('periodtype', "fromtolong");
        $valuesFormExport[] = new \Oara\Curl\Parameter('fromday', $dStartDate->format("d"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('frommonth', $dStartDate->format("m"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('fromyear', $dStartDate->format("Y"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('today', $dEndDate->format("d"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('tomonth', $dEndDate->format("m"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('toyear', $dEndDate->format("Y"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('Go', 'Go');
        $valuesFormExport[] = new \Oara\Curl\Parameter('partnerId', '326');
        $valuesFormExport[] = new \Oara\Curl\Parameter('action', 'partnerLeads');
        $valuesFormExport[] = new \Oara\Curl\Parameter('subaction', 'RevenueOverTime');
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://my.dianomi.com/Campaign-Analysis-378_1.html?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " tabular ")]');


        if ($results->length > 0) {
            $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($results->item(0)));
            $num = count($exportData);
            for ($i = 1; $i < $num; $i++) {
                $overviewExportArray = \str_getcsv($exportData[$i], ";");

                $transaction = Array();
                $transaction['merchantId'] = 1;
                $date = \DateTime::createFromFormat("Y-m-d 00:00:00", $overviewExportArray[0]);
                $transaction['date'] = $date->format("Y-m-d H:i:s");
                $transaction['amount'] = $overviewExportArray[1];
                $transaction['commission'] = $overviewExportArray[1];
                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                $totalTransactions[] = $transaction;
            }
        }

        return $totalTransactions;

    }

}
