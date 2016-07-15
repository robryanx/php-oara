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
 * @category   Webepartners
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class PrivateInternetAccess extends \Oara\Network
{
    private $_client = null;
    private $_apikey = null;

    /**
     * @param $credentials
     * @throws Exception
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_client = new \Oara\Curl\Access($credentials);
        $url = "https://www.privateinternetaccess.com/affiliates/sign_in";
        $valuesLogin = array(
            new \Oara\Curl\Parameter('affiliate[email]', $user),
            new \Oara\Curl\Parameter('affiliate[password]', $password),
        );
        $urls = array();
        $urls[] = new \Oara\Curl\Request($url, $valuesLogin);
        $exportReport = $this->_client->post($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//input[@type="hidden"]');
        $hiddenValue = null;
        foreach ($results as $result) {
            $name = $result->attributes->getNamedItem("name")->nodeValue;
            if ($name == "authenticity_token") {
                $hiddenValue = $result->attributes->getNamedItem("value")->nodeValue;
            }
        }
        if ($hiddenValue == null) {
            throw new \Exception("hidden value not found");
        }
        $valuesLogin = array(
            new \Oara\Curl\Parameter('authenticity_token', $hiddenValue),
            new \Oara\Curl\Parameter('affiliate[email]', $user),
            new \Oara\Curl\Parameter('affiliate[password]', $password),
            new \Oara\Curl\Parameter('utf8', '&#x2713;'),
            new \Oara\Curl\Parameter('commit', 'Login'),
            new \Oara\Curl\Parameter('affiliate[remember_me]', '0'),
        );
        $urls = array();
        $urls[] = new \Oara\Curl\Request($url, $valuesLogin);
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
        $connection = true;
        $valuesFormExport = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://www.privateinternetaccess.com/affiliates/affiliate_dashboard', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " login ")]');
        if ($results->length > 0) {
            $connection = false;
        }

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://www.privateinternetaccess.com/affiliates/affiliate_help', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        \preg_match_all("/access_token.=(.+).&date/", $exportReport[0], $matches);
        $this->_apikey = $matches[1][1];
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
        $obj['name'] = "Private Internet Access";
        $obj['url'] = "https://www.privateinternetaccess.com/affiliates";
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
        $totalTransactions = array();
        $amountDays = $dStartDate->diff($dEndDate)->days;
        $auxDate = clone $dStartDate;
        for ($j = 0; $j <= $amountDays; $j++) {
            $urls = array();
            $urls[] = new \Oara\Curl\Request("https://www.privateinternetaccess.com/api/affiliates/stats?access_token={$this->_apikey}&date=".$auxDate->format("Y-m-d")."&period=day", array());
            $exportReport = $this->_client->get($urls);
            $data = \json_decode($exportReport[0], TRUE);
            if ($data && $data["total"] != 0) {

                $transaction = Array();
                $transaction['merchantId'] = "1";
                $transaction['date'] = $auxDate->format("Y-m-d H:i:s");
                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                $transaction['amount'] = $data["total"];
                $transaction['commission'] = $data["total"];
                $totalTransactions[] = $transaction;
            }
            $auxDate->add(new \DateInterval('P1D'));
        }
        return $totalTransactions;
    }


}