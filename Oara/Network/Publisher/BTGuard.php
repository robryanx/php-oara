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
 * @author     Alejandro Muñoz Odero
 * @category   BTGuard
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class BTGuard extends \Oara\Network
{
    private $_client = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($credentials);

        $valuesLogin = array(
            new \Oara\Curl\Parameter('username', $this->_credentials['user']),
            new \Oara\Curl\Parameter('password', $this->_credentials['password']),
        );
        $loginUrl = 'https://affiliate.btguard.com/login';

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
     * Check the connection
     */
    public function checkConnection()
    {
        //If not login properly the construct launch an exception
        $connection = true;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://affiliate.btguard.com/member', array());
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $tableList = $xpath->query('//*[contains(concat(" ", normalize-space(@id), " "), " login ")]');
        if ($tableList->length > 0) {
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
        $obj['name'] = "BTGuard";
        $obj['url'] = "https://affiliate.btguard.com/";
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

            $valuesFormExport = array();
            $valuesFormExport[] = new \Oara\Curl\Parameter('date1', $auxDate->format("Y-m-d"));
            $valuesFormExport[] = new \Oara\Curl\Parameter('date2', $auxDate->format("Y-m-d"));
            $valuesFormExport[] = new \Oara\Curl\Parameter('prerange', '0');

            $urls = array();
            $urls[] = new \Oara\Curl\Request('https://affiliate.btguard.com/reports?', $valuesFormExport);
            $exportReport = $this->_client->get($urls);

            $doc = new \DOMDocument();
            @$doc->loadHTML($exportReport[0]);
            $xpath = new \DOMXPath($doc);
            $results = $xpath->query('//table[@cellspacing="12"]');
            if ($results->length > 0) {
                $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($results->item(0)));

                for ($z = 1; $z < \count($exportData); $z++) {
                    $transactionLineArray = \str_getcsv($exportData[$z], ";");
                    $numberTransactions = (int)$transactionLineArray[2];
                    if ($numberTransactions != 0) {
                        $commission = \Oara\Utilities::parseDouble($transactionLineArray[3]);
                        $commission = ((double)$commission) / $numberTransactions;
                        for ($y = 0; $y < $numberTransactions; $y++) {
                            $transaction = Array();
                            $transaction['merchantId'] = "1";
                            $transaction['date'] = $auxDate->format("Y-m-d H:i:s");
                            $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                            $transaction['amount'] = $commission;
                            $transaction['commission'] = $commission;
                            $totalTransactions[] = $transaction;
                        }
                    }
                }
            }

            $auxDate->add(new \DateInterval('P1D'));
        }

        return $totalTransactions;
    }

}
