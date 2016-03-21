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
 * @category   Tv
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class TerraVision extends \Oara\Network
{
    /**
     * @var null
     */
    private $_client = null;

    /**
     * @param $credentials
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    public function login($credentials)
    {

        $this->_client = new \Oara\Curl\Access($credentials);

        $user = $credentials['user'];
        $password = $credentials['password'];


        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://book.terravision.eu/login', array());
        $exportReport = $this->_client->get($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//input[@name="_csrf_token"]');
        $token = null;
        foreach ($results as $result) {
            $token = $result->getAttribute("value");
        }


        $valuesLogin = array(new \Oara\Curl\Parameter('_username', $user),
            new \Oara\Curl\Parameter('_password', $password),
            new \Oara\Curl\Parameter('_submit', 'Login'),
            new \Oara\Curl\Parameter('_csrf_token', $token)
        );
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://book.terravision.eu/login_check', $valuesLogin);
        $this->_client->post($urls);

    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = false;

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://book.terravision.eu/partner/my/', array());
        $exportReport = $this->_client->get($urls);
        if (\preg_match("/logout/", $exportReport[0], $matches)) {
            $connection = true;
        }
        return $connection;
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
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = Array();
        $obj = Array();
        $obj['cid'] = 1;
        $obj['name'] = 'Terravision';
        $obj['url'] = 'https://www.terravision.eu/';
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

        $stringToFind = $dStartDate->format("F Y");

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://book.terravision.eu/partner/my/payments', array());
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//table');
        $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($results->item(0)));
        $num = \count($exportData);

        for ($i = 1; $i < $num ; $i++) {
            $transactionArray = \str_getcsv($exportData[$i], ";");
            if ($transactionArray[0] == $stringToFind) {

                $transaction = array();
                $transaction['merchantId'] = 1;
                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                $transaction['date'] = $dEndDate->format("Y-m-d H:i:s");
                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionArray [1]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionArray [1]);

                $totalTransactions[] = $transaction;
            }
        }

        return $totalTransactions;

    }

}
