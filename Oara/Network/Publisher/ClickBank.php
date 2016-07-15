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

        $this->_api = $credentials["user"]."asd";
        $this->_dev  = $credentials["password"];
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "Clerk API Keys";
        $parameter["required"] = true;
        $parameter["name"] = "User";
        $credentials["user"] = $parameter;

        $parameter = array();
        $parameter["description"] = "Developer API Keys";
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

            $today = new \DateTime();
            try{
                $number = self::returnApiData("https://api.clickbank.com/rest/1.3/orders/count?startDate=" . $today->format("Y-m-d") . "&endDate=" . $today->format("Y-m-d"));
                $connection = true;
            } catch (\Exception $e){

            }
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
                    $dateArray = explode("-",self::findAttribute($singleTransaction, 'date'));
                    $transactionDate = \DateTime::createFromFormat("Y-m-d\TH:i:s", $dateArray[0]."-".$dateArray[1]."-".$dateArray[2]);
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
