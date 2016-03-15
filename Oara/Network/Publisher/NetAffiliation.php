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
 * @category   NetAfiliation
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class NetAffiliation extends \Oara\Network
{
    protected $_sitesAllowed = array();
    private $_serverNumber = null;
    private $_credentials = null;
    private $_client = null;

    /**
     * @param $credentials
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    public function login($credentials)
    {

        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($credentials);

        $user = $credentials['user'];
        $password = $credentials['password'];
        $loginUrl = "https://www2.netaffiliation.com/login";

        $valuesLogin = array(new \Oara\Curl\Parameter('login[from]', 'Accueil/index'),
            new \Oara\Curl\Parameter('login[email]', $user),
            new \Oara\Curl\Parameter('login[mdp]', $password)
        );
        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $this->_client->post($urls);

        $cookieContent = $this->_client->getCookies();
        $serverNumber = null;
        if (\preg_match('/www(.)\.netaffiliation\.com/', $cookieContent, $matches)) {
            $this->_serverNumber = $matches[1];
        }

        $urls = array();
        $valuesFormExport = array();
        $urls[] = new \Oara\Curl\Request('http://www' . $this->_serverNumber . '.netaffiliation.com/affiliate/webservice', $valuesFormExport);
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " margeHaut5 ")]');
        foreach ($results as $result) {
            $this->_credentials["apiPassword"] = $result->nodeValue;
        }
        if (!isset($this->_credentials["apiPassword"])) {
            $valuesFormExport = array();
            $urls[] = new \Oara\Curl\Request('http://www' . $this->_serverNumber . '.netaffiliation.com/affiliate/webservice?d=1', $valuesFormExport);
            $this->_client->get($urls);
        }
        $urls = array();
        $valuesFormExport = array();
        $urls[] = new \Oara\Curl\Request('http://www' . $this->_serverNumber . '.netaffiliation.com/affiliate/webservice', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " margeHaut5 ")]');
        foreach ($results as $result) {
            $this->_credentials["apiPassword"] = $result->nodeValue;
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
        $connection = true;
        //Checking connection to the platform
        $valuesFormExport = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www' . $this->_serverNumber . '.netaffiliation.com/index.php/', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        if (!\preg_match("/logout/", $exportReport[0], $matches) || !isset($this->_credentials["apiPassword"])) {
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

        $valuesFormExport = array();
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www' . $this->_serverNumber . '.netaffiliation.com/index.php/affiliate/statistics', $valuesFormExport);
        $exportReport = $this->_client->post($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//optgroup[contains(concat(" ", normalize-space(@id), " "), " statistiquesGenerales_liste_programme ")]');
        foreach ($results as $result) {
            $merchantLines = $result->childNodes;
            for ($i = 0; $i < $merchantLines->length; $i++) {
                $cid = $merchantLines->item($i)->attributes->getNamedItem("value")->nodeValue;
                $cid = \str_replace("p", "", $cid);
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
     * @throws Exception
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();
        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);

        $valuesFormExport = array();
        $valuesFormExport[] = new \Oara\Curl\Parameter('authl', $this->_credentials["user"]);
        $valuesFormExport[] = new \Oara\Curl\Parameter('authv', $this->_credentials["apiPassword"]);
        $valuesFormExport[] = new \Oara\Curl\Parameter('champs', 'idprogramme,date,etat,argann,montant,taux,monnaie,idsite');
        $valuesFormExport[] = new \Oara\Curl\Parameter('debut', $dStartDate->format("Y-m-d"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('fin', $dEndDate->format("Y-m-d"));
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://stat.netaffiliation.com/requete.php?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);


        //sales
        $exportData = str_getcsv($exportReport[0], "\n");
        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i], ";");
            if (\count($this->_sitesAllowed) == 0 || \in_array($transactionExportArray[7], $this->_sitesAllowed)) {
                if (isset($merchantIdList[$transactionExportArray[0]])) {
                    $transaction = Array();
                    $transaction['merchantId'] = $transactionExportArray[0];
                    $transactionDate = \DateTime::createFromFormat("d/m/Y H:i:s", $transactionExportArray[1]);
                    $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");

                    if ($transactionExportArray[3] != null) {
                        $transaction['custom_id'] = $transactionExportArray[3];
                    }

                    if (\strstr($transactionExportArray[2], 'v')) {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else
                        if (\strstr($transactionExportArray[2], 'r')) {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        } else if (\strstr($transactionExportArray[2], 'a')) {
                            $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        } else {
                            throw new \Exception ("Status not found");
                        }
                    $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[4]);
                    $transaction['commission'] = \Oara\Utilities::parseDouble(($transactionExportArray[4] * $transactionExportArray[5]) / 100);
                    $totalTransactions[] = $transaction;
                }
            }
        }
        return $totalTransactions;
    }

}
