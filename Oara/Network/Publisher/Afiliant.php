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
 * @category   Afiliant
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Afiliant extends \Oara\Network
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

        $loginUrl = 'https://ssl.afiliant.com/publisher/index.php?a=auth';
        $valuesLogin = array(new \Oara\Curl\Parameter('login', $user),
            new \Oara\Curl\Parameter('password', $password),
            new \Oara\Curl\Parameter('submit', "")
        );

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
        $urls[] = new \Oara\Curl\Request('http://www.afiliant.com/publisher/index.php', array());
        $exportReport = $this->_client->get($urls);
        if (!\preg_match("/index.php?a=logout/", $exportReport[0], $matches)) {
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

        $valuesFromExport = array(
            new \Oara\Curl\Parameter('c', 'stats'),
            new \Oara\Curl\Parameter('a', 'listMonth')
        );
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www.afiliant.com/publisher/index.php?', $valuesFromExport);
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[contains(concat(" ", normalize-space(@id), " "), " id_shop ")]');

        $merchantLines = $results->item(0)->childNodes;
        for ($i = 0; $i < $merchantLines->length; $i++) {
            $cid = $merchantLines->item($i)->attributes->getNamedItem("value")->nodeValue;
            if (\is_numeric($cid)) {
                $name = $merchantLines->item($i)->nodeValue;
                $obj = array();
                $obj['cid'] = $cid;
                $obj['name'] = $name;
                $merchants[] = $obj;
            }
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
        $totalTransactions = array();
        $merchantMap = \Oara\Utilities::getMerchantNameMapFromMerchantList($merchantList);

        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('c', 'stats');
        $valuesFromExport[] = new \Oara\Curl\Parameter('id_shop', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('a', 'listMonthDayOrder');
        $valuesFromExport[] = new \Oara\Curl\Parameter('month', $dEndDate->fromat("Y-m"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('export', 'csv');

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www.afiliant.com/publisher/index.php?', $valuesFromExport);

        $exportData = null;
        try {
            $exportReport = $this->_client->get($urls);
            $exportData = \str_getcsv($exportReport[0], "\r\n");
        } catch (\Exception $e) {
            echo "No data \n";
        }
        if ($exportData != null) {
            $num = \count($exportData);
            for ($i = 0; $i < $num; $i++) {
                $transactionExportArray = \str_getcsv($exportData[$i], ";");

                if (isset($merchantMap[$transactionExportArray[1]])) {
                    $transaction = Array();
                    $merchantId = (int)$merchantMap[$transactionExportArray[1]];
                    $transaction['merchantId'] = $merchantId;
                    $transaction['date'] = $transactionExportArray[0]." 00:00:00";
                    $transaction['unique_id'] = $transactionExportArray[3];

                    if (isset($transactionExportArray[8]) && $transactionExportArray[8] != null) {
                        $transaction['custom_id'] = $transactionExportArray[8];
                    }

                    if ($transactionExportArray[6] == 'zaakceptowana') {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else
                        if ($transactionExportArray[6] == 'oczekuje') {
                            $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        } else
                            if ($transactionExportArray[6] == 'odrzucona') {
                                $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                            }
                    $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[4]);
                    $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[5]);
                    $totalTransactions[] = $transaction;
                }
            }
        }

        return $totalTransactions;
    }
}
