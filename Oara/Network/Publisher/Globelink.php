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
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Globelink
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Globelink extends \Oara\Network
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


        $user = $credentials['user'];
        $password = $credentials['password'];

        $loginUrl = "http://affiliate.globelink.co.uk/form/CMSFormsUsersSignin?param=";

        $urls = array();
        $urls [] = new \Oara\Curl\Request ($loginUrl, array());
        $exportReport = $this->_client->get($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//input[@type="hidden"]');
        $valuesLogin = array(
            new \Oara\Curl\Parameter('user_login', $user),
            new \Oara\Curl\Parameter('user_password', $password),
        );
        foreach ($results as $values) {
            $valuesLogin[] = new \Oara\Curl\Parameter($values->getAttribute("name"), $values->getAttribute("value"));
        }
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
        $connection = true;

        $valuesFormExport = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://affiliate.globelink.co.uk/home/', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        if (!\preg_match("/\/form\/CMSFormsUsersLogout/", $exportReport[0], $matches)) {
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
        $obj['name'] = "Globelink";
        $obj['url'] = "";
        $obj['cid'] = 1;
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

        $urls = array();
        $valuesFormExport = array();
        $urls[] = new \Oara\Curl\Request('http://affiliate.globelink.co.uk/home/', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $commmisionUrl = "";
        if (\preg_match("/\/profile\/(.*)\/sales/", $exportReport[0], $matches)) {
            $commmisionUrl = "http://affiliate.globelink.co.uk/profile/" . $matches[1] . "/sales/?";
        }

        $auxTransactionList = array();
        $page = 1;
        $exit = false;
        while (!$exit) {
            $urls = array();
            $valuesFormExport = array();
            $valuesFormExport[] = new \Oara\Curl\Parameter('page', $page);
            $valuesFormExport[] = new \Oara\Curl\Parameter('count', 20);
            $urls[] = new \Oara\Curl\Request($commmisionUrl, $valuesFormExport);
            $exportReport = $this->_client->get($urls);

            $doc = new \DOMDocument();
            @$doc->loadHTML($exportReport[0]);
            $xpath = new \DOMXPath($doc);
            $results = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " affs-list-r ")]');

            foreach ($results as $line) {
                $auxTransaction = array();
                foreach ($line->childNodes as $attribute) {
                    $value = \trim((string)$attribute->nodeValue);
                    if (\strlen($value) > 0) {
                        $auxTransaction[] = ($value != "n/a") ? $value : '';
                    }
                }
                $auxTransactionList[] = $auxTransaction;
            }

            if (\preg_match("/<li><span>&raquo;<\/span><\/li>/", $exportReport[0])) {
                $exit = true;
            }
            $page++;
        }

        foreach ($auxTransactionList as $auxTransaction) {

            if ($dStartDate->format("Y-m-d H:i:s") <= $auxTransaction[0] && $dEndDate->format("Y-m-d H:i:s") >= $auxTransaction[0]) {
                $transaction = Array();
                $transaction['merchantId'] = 1;

                $transaction['date'] = $auxTransaction[0];
                $transaction['unique_id'] = $auxTransaction[1];

                if (strstr($auxTransaction[6], 'No')) {
                    $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                } else {
                    if (strstr($auxTransaction[6], 'Yes')) {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    }
                }

                $transaction['amount'] = $auxTransaction[3];
                $transaction['commission'] = $auxTransaction[4];
                $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;
    }

}
