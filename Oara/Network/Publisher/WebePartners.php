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
class WebePartners extends \Oara\Network
{
    private $_client = null;
    private $_user = null;
    private $_pass = null;

    /**
     * @param $credentials
     * @throws Exception
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_user = $user;
        $this->_pass = $password;

        $this->_client = new \Oara\Curl\Access($credentials);

        $url = "http://panel.webepartners.pl/Account/Login";
        $urls = array();
        $urls[] = new \Oara\Curl\Request($url, array());
        $result = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($result[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//input[@type="hidden"]');
        foreach ($results as $result) {
            $name = $result->attributes->getNamedItem("name")->nodeValue;
            if ($name == "__RequestVerificationToken") {
                $hiddenValue = $result->attributes->getNamedItem("value")->nodeValue;
            }
        }
        if ($hiddenValue == null) {
            throw new \Exception("hidden value not found");
        }

        $valuesLogin = array(
            new \Oara\Curl\Parameter('__RequestVerificationToken', $hiddenValue),
            new \Oara\Curl\Parameter('Login', $user),
            new \Oara\Curl\Parameter('Password', $password),
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request($url, $valuesLogin);
        $this->_client->post($urls);



        $urls = array();
        $urls[] = new \Oara\Curl\Request("http://panel.webepartners.pl/AffiliateTools/Api", $valuesLogin);
        $result = $this->_client->post($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($result[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//a[@href*="Authorize"]');

        if ($results->length > 0) {
            $item = $results->item(0);
            $url = $item->attributes->getNamedItem("href")->nodeValue;
            $parsedUrl = \parse_url($url);
            \parse_str($parsedUrl["query"], $parameters);
            $apiPass = $parameters["password"];
            $this->_pass = $apiPass;
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
        $loginUrl = "http://api.webepartners.pl/wydawca/Authorize?login={$this->_user}&password={$this->_pass}";

        $context = \stream_context_create(array(
            'http' => array(
                'header' => "Authorization: Basic " . \base64_encode("{$this->_user}:{$this->_pass}")
            )
        ));
        $data = \file_get_contents($loginUrl, false, $context);
        if ($data == true) {
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

        $context = \stream_context_create(array(
            'http' => array(
                'header' => "Authorization: Basic " . \base64_encode("{$this->_user}:{$this->_pass}")
            )
        ));

        $data = \file_get_contents("http://api.webepartners.pl/wydawca/Programs", false, $context);
        $dataArray = \json_decode($data, true);
        foreach ($dataArray as $merchantObject) {
            $obj = array();
            $obj['cid'] = $merchantObject["ProgramId"];
            $obj['name'] = $merchantObject["ProgramName"];
            $merchants[] = $obj;
        }
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
        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);

        $context = \stream_context_create(array(
            'http' => array(
                'header' => "Authorization: Basic " . \base64_encode("{$this->_user}:{$this->_pass}")
            )
        ));

        $from = \urlencode($dStartDate->format("Y-m-d H:i:s"));

        $data = \file_get_contents("http://api.webepartners.pl/wydawca/Auctions?from=$from", false, $context);
        $dataArray = \json_decode($data, true);
        foreach ($dataArray as $transactionObject) {

            if (isset($merchantIdList[$transactionObject["programId"]])) {
                $transaction = Array();
                $transaction['merchantId'] = $transactionObject["programId"];
                $transaction['date'] = $transactionObject["auctionDate"];
                if (isset($transactionObject["auctionId"]) && $transactionObject["auctionId"] != '') {
                    $transaction['unique_id'] = $transactionObject["auctionId"];
                }
                if (isset($transactionObject["subId"]) && $transactionObject["subId"] != '') {
                    $transaction['custom_id'] = $transactionObject["subId"];
                }

                if ($transactionObject["auctionStatusId"] == 3 || $transactionObject["auctionStatusId"] == 4 || $transactionObject["auctionStatusId"] == 5) {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else
                    if ($transactionObject["auctionStatusId"] == 1) {
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else
                        if ($transactionObject["auctionStatusId"] == 2) {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        } else
                            if ($transactionObject["auctionStatusId"] == 6) {
                                $transaction['status'] = \Oara\Utilities::STATUS_PAID;
                            }

                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionObject["orderCost"]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionObject["commission"]);
                $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;
    }


}
