<?php
/**
 * The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 * of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
 *
 * Copyright (C) 2014  Fubra Limited
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
 * @category   Oara_Network_Publisher_Publicidees
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Publicidees extends Oara_Network
{
    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;

    /**
     * Constructor and Login
     * @param $credentials
     * @return Oara_Network_Publisher_Effiliation
     */
    public function __construct($credentials)
    {

        $user = $credentials['user'];
        $password = $credentials['password'];
        $loginUrl = 'http://es.publicideas.com/logmein.php';
        $valuesLogin = array(new Oara_Curl_Parameter('loginAff', $user),
            new Oara_Curl_Parameter('passAff', $password),
            new Oara_Curl_Parameter('userType', 'aff')
        );
        $this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
        $result = json_decode($this->_client->getConstructResult());
        $loginUrl = 'http://affilie.publicidees.com/entree_affilies.php';
        $valuesLogin = array(new Oara_Curl_Parameter('login', $result->login),
            new Oara_Curl_Parameter('pass', $result->pass),
            new Oara_Curl_Parameter('submit', 'Ok'),
            new Oara_Curl_Parameter('h', $result->h)
        );
        $this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;

        $urls = array();
        $urls[] = new Oara_Curl_Request('http://affilie.publicidees.com/', array());
        $exportReport = $this->_client->get($urls);
        if (preg_match('/deconnexion\.php/', $exportReport[0], $matches)) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
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
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null)
    {
        $totalTransactions = array();
        $filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2, 'locale' => 'fr'));
        $dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
        $dateArraySize = sizeof($dateArray);
        $urls = array();
        for ($i = 0; $i < $dateArraySize; $i++) {
            $valuesFromExport = array();
            $valuesFromExport[] = new Oara_Curl_Parameter('action', "myresume");
            $valuesFromExport[] = new Oara_Curl_Parameter('progid', 0);
            $valuesFromExport[] = new Oara_Curl_Parameter('dD', $dateArray[$i]->toString("dd/MM/yyyy"));
            $valuesFromExport[] = new Oara_Curl_Parameter('dF', $dateArray[$i]->toString("dd/MM/yyyy"));
            $valuesFromExport[] = new Oara_Curl_Parameter('periode', "0");
            $valuesFromExport[] = new Oara_Curl_Parameter('expAct', "1");
            $valuesFromExport[] = new Oara_Curl_Parameter('tabid', "0");
            $valuesFromExport[] = new Oara_Curl_Parameter('Submit', "Voir");
            $urls[] = new Oara_Curl_Request('http://affilie.publicidees.com/index.php?', $valuesFromExport);

            $exportReport = $this->_client->get($urls);
            $numExport = count($exportReport);
            for ($i = 0; $i < $numExport; $i++) {
                $exportData = str_getcsv(utf8_decode($exportReport[$i]), "\n");
                $num = count($exportData);

                $headerArray = str_getcsv($exportData[0], ";");
                $headerMap = array();
                if (count($headerArray) > 1) {

                    for ($j = 0; $j < count($headerArray); $j++) {
                        if ($headerArray[$j] == "" && $headerArray[$j - 1] == "Ventes") {
                            $headerMap["pendingVentes"] = $j;
                        } else
                            if ($headerArray[$j] == "" && $headerArray[$j - 1] == "CA") {
                                $headerMap["pendingCA"] = $j;
                            } else {
                                $headerMap[$headerArray[$j]] = $j;
                            }
                    }
                }

                for ($j = 1; $j < $num; $j++) {

                    $transactionExportArray = str_getcsv($exportData[$j], ";");
                    if (isset($headerMap["Ventes"]) && isset($headerMap["pendingVentes"])) {
                        $confirmedTransactions = (int)$transactionExportArray[$headerMap["Ventes"]];
                        $pendingTransactions = (int)$transactionExportArray[$headerMap["pendingVentes"]];

                        for ($z = 0; $z < $confirmedTransactions; $z++) {
                            $transaction = Array();
                            $transaction['merchantId'] = 1;
                            $parameters = $urls[$i]->getParameters();
                            $transaction['date'] = $dateArray[$i]->toString("yyyy-MM-dd HH:mm:ss");
                            $transaction['amount'] = ((double)$filter->filter(substr($transactionExportArray[$headerMap["CA"]], 0, -2)) / $confirmedTransactions);
                            $transaction['commission'] = ((double)$filter->filter(substr($transactionExportArray[$headerMap["CA"]], 0, -2)) / $confirmedTransactions);
                            $transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
                            $totalTransactions[] = $transaction;
                        }

                        for ($z = 0; $z < $pendingTransactions; $z++) {
                            $transaction = Array();
                            $transaction['merchantId'] = 1;
                            $transaction['date'] = $dateArray[$i]->toString("yyyy-MM-dd HH:mm:ss");
                            $transaction['amount'] = (double)$transactionExportArray[$headerMap["pendingCA"]] / $pendingTransactions;
                            $transaction['commission'] = (double)$transactionExportArray[$headerMap["pendingCA"]] / $pendingTransactions;
                            $transaction['status'] = Oara_Utilities::STATUS_PENDING;
                            $totalTransactions[] = $transaction;
                        }

                    }
                }
            }
        }
        return $totalTransactions;
    }
}
