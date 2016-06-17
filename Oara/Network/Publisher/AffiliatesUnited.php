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
 * @category   AffiliatesUnited
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class AffiliatesUnited extends \Oara\Network
{

    /**
     * Client
     * @var unknown_type
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

        $loginUrl = 'https://affiliates.affutd.com/affiliates/Account/Login?aspxerrorpath=/affiliates/login.aspx#';
        $urls = array();
        $urls [] = new \Oara\Curl\Request ($loginUrl, array());
        $exportReport = $this->_client->get($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//form[@action="/affiliates/Account/Login"]/child::input[@type="hidden"]');
        $valuesLogin = array(
            new \Oara\Curl\Parameter('UserName', $user),
            new \Oara\Curl\Parameter('Password', $password)
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
        $connection = false;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://affiliates.affutd.com/affiliatesv1/Dashboard.aspx', array());
        $exportReport = $this->_client->post($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lnkLogOut ")]');

        if ($results->length > 0) {
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
        $obj['cid'] = 1;
        $obj['name'] = "Affiliates United";
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

        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('ctl00$cphPage$reportFrom', $dStartDate->format("Y-m-d"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('ctl00$cphPage$reportTo', $dEndDate->format("Y-m-d"));

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://affiliates.affutd.com/affiliatesv1/DataServiceWrapper/DataService.svc/Export/CSV/Affiliates_Reports_GeneralStats_DailyFigures', $valuesFromExport);
        $exportReport = $this->_client->post($urls);
        $exportData = \str_getcsv($exportReport[0], "\n");
        $num = \count($exportData);
        for ($i = 2; $i < $num-1; $i++) {
            $transactionExportArray = \str_getcsv($exportData[$i], ",");

            $transaction = Array();
            $transaction['merchantId'] = 1;
            $date = \DateTime::createFromFormat("d-m-Y", trim($transactionExportArray[0]));
            $date->setTime(0, 0);
            $transaction['date'] = $date->format("Y-m-d H:i:s");
            $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[16]);
            $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[16]);
            $totalTransactions[] = $transaction;
        }

        return $totalTransactions;
    }

}
