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
 * @category   ClickBank
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class ClickBank extends \Oara\Network
{
    /**
     * Api Key
     * @var string
     */
    private $_api = null;
    /**
     * Dev Key
     * @var string
     */
    private $_dev = null;


    /**
     * @param $credentials
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    public function login($credentials)
    {

        $user = $credentials["user"];
        $password = $credentials["password"];
        $this->_client = new \Oara\Curl\Access($credentials);

        $loginUrl = "https://" . $user . ".accounts.clickbank.com/account/login?";
        $valuesLogin = array(new \Oara\Curl\Parameter('destination', "/account/mainMenu.htm"),
            new \Oara\Curl\Parameter('nick', $user),
            new \Oara\Curl\Parameter('pass', $password),
            new \Oara\Curl\Parameter('login', "Log In"),
            new \Oara\Curl\Parameter('rememberMe', "true"),
            new \Oara\Curl\Parameter('j_username', $user),
            new \Oara\Curl\Parameter('j_password', $password)
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $this->_client->post($urls);

        $urls = array();
        $urls[] = new \Oara\Curl\Request("https://" . $user . ".accounts.clickbank.com/account/profile.htm", array());
        $result = $this->_client->get($urls);

        if (\preg_match_all("/(API-(.*)?)\s</", $result[0], $matches)) {
            $this->_api = $matches[1][0];
        }
        if (\preg_match_all("/(DEV-(.*)?)</", $result[0], $matches)) {
            $this->_dev = $matches[1][0];
        }

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
        if ($this->_api != null && $this->_dev != null) {
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
        $obj['name'] = "ClickBank";
        $obj['url'] = "www.clickbank.com";
        $merchants[] = $obj;
        return $merchants;
    }

    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     * @throws \Exception
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();
        $number = self::returnApiData("https://api.clickbank.com/rest/1.3/orders/count?startDate=" . $dStartDate->format("Y-m-d") . "&endDate=" . $dEndDate->format("Y-m-d"));

        if ($number[0] != 0) {
            $transactionXMLList = self::returnApiData("https://api.clickbank.com/rest/1.3/orders/list?startDate=" . $dStartDate->format("Y-m-d") . "&endDate=" . $dEndDate->format("Y-m-d"));
            foreach ($transactionXMLList as $transactionXML) {
                $transactionXML = \simplexml_load_string($transactionXML, null, LIBXML_NOERROR | LIBXML_NOWARNING);

                foreach ($transactionXML->orderData as $singleTransaction) {

                    $transaction = Array();
                    $transaction['merchantId'] = 1;
                    $transactionDate = \DateTime::createFromFormat("Y-m-d\TH:i:s", self::findAttribute($singleTransaction, 'date'));
                    $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");
                    if (self::findAttribute($singleTransaction, 'affi') != null) {
                        $transaction['custom_id'] = self::findAttribute($singleTransaction, 'affi');
                    }
                    $transaction['unique_id'] = self::findAttribute($singleTransaction, 'receipt');
                    $transaction['amount'] = \Oara\Utilities::parseDouble(self::findAttribute($singleTransaction, 'amount'));
                    $transaction['commission'] = \Oara\Utilities::parseDouble(self::findAttribute($singleTransaction, 'amount'));
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    $totalTransactions[] = $transaction;
                }

            }

        }

        return $totalTransactions;
    }

    /**
     * @param $xmlLocation
     * @return array
     * @throws \Exception
     */
    private function returnApiData($xmlLocation)
    {
        $dataArray = array();
        // Get the data
        $httpCode = 206;
        $page = 1;
        while ($httpCode != 200) {
            $ch = \curl_init();
            \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            \curl_setopt($ch, CURLOPT_URL, $xmlLocation);
            \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            \curl_setopt($ch, CURLOPT_HTTPHEADER, array("Page: $page", "Accept: application/xml", "Authorization: " . $this->_dev . ":" . $this->_api));

            $dataArray[] = \curl_exec($ch);
            $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode != 200 && $httpCode != 206) {
                throw new \Exception("Couldn't connect to the API");
            }
            \curl_close($ch);
            $page++;
        }

        return $dataArray;

    }

    /**
     * @param null $object
     * @param null $attribute
     * @return null|string
     */
    private function findAttribute($object = null, $attribute = null)
    {
        $return = null;
        $return = \trim($object->$attribute);
        return $return;
    }
}
