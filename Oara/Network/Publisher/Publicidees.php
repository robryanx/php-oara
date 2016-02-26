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
 * @category   Publicidees
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Publicidees extends \Oara\Network
{
    private $_client = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {

        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_client = new \Oara\Curl\Access($credentials);

        $loginUrl = 'http://es.publicideas.com/logmein.php';
        $valuesLogin = array(new \Oara\Curl\Parameter('loginAff', $user),
            new \Oara\Curl\Parameter('passAff', $password),
            new \Oara\Curl\Parameter('userType', 'aff')
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $exportReport = $this->_client->post($urls);
        $result = \json_decode($exportReport[0]);
        $loginUrl = 'http://publisher.publicideas.com/entree_affilies.php';
        $valuesLogin = array(new \Oara\Curl\Parameter('login', $result->login),
            new \Oara\Curl\Parameter('pass', $result->pass),
            new \Oara\Curl\Parameter('submit', 'Ok'),
            new \Oara\Curl\Parameter('h', $result->h)
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
        $parameter["user"]["description"] = "User Log in";
        $parameter["user"]["required"] = true;
        $credentials[] = $parameter;

        $parameter = array();
        $parameter["password"]["description"] = "Password to Log in";
        $parameter["password"]["required"] = true;
        $credentials[] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = false;

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.publicideas.com/', array());
        $exportReport = $this->_client->get($urls);
        if (\preg_match('/deconnexion\.php/', $exportReport[0], $matches)) {
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
        $obj['name'] = "Publicidees";
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

        for ($i = 0; $i < $amountDays; $i++) {

            $valuesFromExport = array();
            $valuesFromExport[] = new \Oara\Curl\Parameter('action', "myresume");
            $valuesFromExport[] = new \Oara\Curl\Parameter('monthDisplay', 0);
            $valuesFromExport[] = new \Oara\Curl\Parameter('tout', 1);
            $valuesFromExport[] = new \Oara\Curl\Parameter('dD', $auxDate->format("d/m/Y"));
            $valuesFromExport[] = new \Oara\Curl\Parameter('dF', $auxDate->format("d/m/Y"));
            $valuesFromExport[] = new \Oara\Curl\Parameter('periode', "0");
            $valuesFromExport[] = new \Oara\Curl\Parameter('expAct', "1");
            $valuesFromExport[] = new \Oara\Curl\Parameter('tabid', "0");
            $valuesFromExport[] = new \Oara\Curl\Parameter('Submit', "Voir");
            $urls = array();
            $urls[] = new \Oara\Curl\Request('http://publisher.publicideas.com/index.php?', $valuesFromExport);
            try {
                $exportReport = $this->_client->get($urls, 'content', 5);
            } catch (\Exception $e) {
                continue;
            }

            $exportData = \str_getcsv(\utf8_decode($exportReport[0]), "\n");
            $num = \count($exportData);
            $headerArray = \str_getcsv($exportData[0], ";");
            $headerMap = array();
            if (\count($headerArray) > 1) {

                for ($j = 0; $j < \count($headerArray); $j++) {
                    if ($headerArray[$j] == "" && $headerArray[$j - 1] == "Ventes") {
                        $headerMap["pendingVentes"] = $j;
                    } else if ($headerArray[$j] == "" && $headerArray[$j - 1] == "CA") {
                        $headerMap["pendingCA"] = $j;
                    } else {
                        $headerMap[$headerArray[$j]] = $j;
                    }
                }
            }

            for ($j = 1; $j < $num; $j++) {
                $transactionExportArray = \str_getcsv($exportData[$j], ";");
                if (isset($headerMap["Ventes"]) && isset($headerMap["pendingVentes"])) {
                    $confirmedTransactions = (int)$transactionExportArray[$headerMap["Ventes"]];
                    $pendingTransactions = (int)$transactionExportArray[$headerMap["pendingVentes"]];

                    for ($z = 0; $z < $confirmedTransactions; $z++) {
                        $transaction = Array();
                        $transaction['merchantId'] = 1;
                        $transaction['date'] = $auxDate->format("Y-m-d H:i:s");
                        $transaction['amount'] = \Oara\Utilities::parseDouble(\substr($transactionExportArray[$headerMap["CA"]], 0, -2) / $confirmedTransactions);
                        $transaction['commission'] = \Oara\Utilities::parseDouble(\substr($transactionExportArray[$headerMap["CA"]], 0, -2) / $confirmedTransactions);
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                        $totalTransactions[] = $transaction;
                    }

                    for ($z = 0; $z < $pendingTransactions; $z++) {
                        $transaction = Array();
                        $transaction['merchantId'] = 1;
                        $transaction['date'] = $auxDate->format("Y-m-d H:i:s");
                        $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[$headerMap["pendingCA"]] / $pendingTransactions);
                        $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[$headerMap["pendingCA"]] / $pendingTransactions);
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        $totalTransactions[] = $transaction;
                    }
                }
            }
            $auxDate->add(new \DateInterval('P1D'));
        }
        return $totalTransactions;
    }
}
