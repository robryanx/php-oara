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
 * @category   Ebay
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Ebay extends \Oara\Network
{
    private $_client = null;
    protected $_sitesAllowed = array();

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($credentials);

        $valuesLogin = array(
            new \Oara\Curl\Parameter('login_username', $this->_credentials['user']),
            new \Oara\Curl\Parameter('login_password', $this->_credentials['password']),
            new \Oara\Curl\Parameter('submit_btn', 'GO'),
            new \Oara\Curl\Parameter('hubpage', 'y')
        );
        $loginUrl = 'https://ebaypartnernetwork.com/PublisherLogin?hubpage=y&lang=en-US?';

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
        //If not login properly the construct launch an exception
        $connection = true;
        $yesterday = new \DateTime();
        $yesterday->sub(new \DateInterval('P2D'));

        $urls = array();
        $urls[] = new \Oara\Curl\Request("https://publisher.ebaypartnernetwork.com/PublisherReportsTx?pt=2&start_date={$yesterday->format("n/j/Y")}&end_date={$yesterday->format("n/j/Y")}&user_name={$this->_credentials['user']}&user_password={$this->_credentials['password']}&advIdProgIdCombo=&tx_fmt=2&submit_tx=Download", array());
        $exportReport = $this->_client->get($urls);

        if (\preg_match("/DOCTYPE html PUBLIC/", $exportReport[0])) {
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
        $obj['cid'] = "1";
        $obj['name'] = "Ebay";
        $obj['url'] = "https://publisher.ebaypartnernetwork.com";
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
        $urls[] = new \Oara\Curl\Request("https://publisher.ebaypartnernetwork.com/PublisherReportsTx?pt=2&start_date={$dStartDate->format("n/j/Y")}&end_date={$dEndDate->format("n/j/Y")}&user_name={$this->_credentials['user']}&user_password={$this->_credentials['password']}&advIdProgIdCombo=&tx_fmt=3&submit_tx=Download", array());
        $exportData = array();
        try {
            $exportReport = $this->_client->get($urls, 'content', 5);
            $exportData = \str_getcsv($exportReport[0], "\n");
        } catch (\Exception $e) {

        }
        $num = \count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = \str_getcsv($exportData[$i], "\t");

            if ($transactionExportArray[2] == "Winning Bid (Revenue)" && (empty($this->_sitesAllowed) || \in_array($transactionExportArray[5], $this->_sitesAllowed))) {

                $transaction = Array();
                $transaction['merchantId'] = 1;
                $transactionDate = \DateTime::createFromFormat("Y-m-d", $transactionExportArray[1]);
                $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");
                unset($transactionDate);
                if ($transactionExportArray[10] != null) {
                    $transaction['custom_id'] = $transactionExportArray[10];
                }

                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;

                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[3]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[20]);
                $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;
    }

}
