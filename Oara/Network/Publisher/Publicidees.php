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
    private $_sites = array();

    /**
     * @param $credentials
     */
    public function login($credentials)
    {

        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_client = new \Oara\Curl\Access($credentials);
        $loginUrl = 'http://performance.timeonegroup.com/logmein.php';
        $valuesLogin = array(new \Oara\Curl\Parameter('loginAff', $user),
            new \Oara\Curl\Parameter('passAff', $password),
            new \Oara\Curl\Parameter('userType', 'aff'),
            new \Oara\Curl\Parameter('site', 'pi')
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
        $merchantsMap = array();

        // Get sites
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.publicideas.com/index.php', array());
        $exportReport = $this->_client->get($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//form[@action="reconnect.php"]/descendant::select[@name="site"]/child::option');
        foreach ($results as $values) {
            $siteId = $values->getAttribute("value");
            $siteName = $values->textContent;
            if (!isset($this->_sites[$siteId])) {
                $this->_sites[$siteId] = $siteName;
                $valuesLogin = array(
                    new \Oara\Curl\Parameter('site', $siteId),
                    new \Oara\Curl\Parameter('page', '/index.php?'),
                );
                $urls = array();
                $urls[] = new \Oara\Curl\Request('http://publisher.publicideas.com/reconnect.php', $valuesLogin);
                $this->_client->post($urls);

                // Get the first page of programs.
                $programsPerPage = 100;
                $valuesPost = array(
                    new \Oara\Curl\Parameter('nb_page', $programsPerPage),
                    new \Oara\Curl\Parameter('action', 'myprograms'),
                    new \Oara\Curl\Parameter('type', ''),
                    new \Oara\Curl\Parameter('keyword', ''),
                );
                $urls = array();
                $urls[] = new \Oara\Curl\Request('http://publisher.publicideas.com/index.php?action=myprograms', $valuesPost);
                $exportReport = $this->_client->post($urls);
                if (preg_match_all('/onclick="document.location.href=\'index.php\?action=moreallstats&progid=(.+)\';"/', $exportReport[0], $match)
                    && preg_match_all('/<td width="100%" class="progTitreF">(.+) &laquo;<\/td>/', $exportReport[0], $match2)
                ) {
                    for ($i = 0; $i < count($match[1]); $i++) {
                        $cid = $match[1][$i];
                        $name = $match2[1][$i];
                        if (!isset($merchantsMap[$cid])) {
                            $merchant = array();
                            $merchant['cid'] = $cid;
                            $merchant['name'] = $name;
                            $merchants[] = $merchant;
                            $merchantsMap[$cid] = true;
                        }
                    }
                }

                // Get nex pages of programs if they exist.
                if (preg_match_all('/href="\?action=myprograms&type=&keyword=&nb_page=' . $programsPerPage . '&index=(\d+)&nb_page=' . $programsPerPage . '"/', $exportReport[0], $match)) {
                    $pages = count($match[1]) / 2;
                    for ($i = 1; $i <= $pages; $i++) {
                        $index = $programsPerPage * $i;
                        $urlString = 'http://publisher.publicideas.com/index.php?action=myprograms&type=&keyword=&nb_page=' . $programsPerPage . '&index=' . $index . '&nb_page=' . $programsPerPage;
                        $urls = array();
                        $urls[] = new \Oara\Curl\Request($urlString, array());
                        $exportReport = $this->_client->get($urls);
                        if (preg_match_all('/onclick="document.location.href=\'index.php\?action=moreallstats&progid=(.+)\';"/', $exportReport[0], $match)
                            && preg_match_all('/<td width="100%" class="progTitreF">(.+) &laquo;<\/td>/', $exportReport[0], $match2)
                        ) {
                            for ($j = 0; $j < count($match[1]); $j++) {
                                $cid = $match[1][$j];
                                $name = $match2[1][$j];
                                if (!isset($merchantsMap[$cid])) {
                                    $merchant = array();
                                    $merchant['cid'] = $cid;
                                    $merchant['name'] = $name;
                                    $merchants[] = $merchant;
                                    $merchantsMap[$cid] = true;
                                }
                            }
                        }
                    }
                }
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

        foreach ($this->_sites as $siteId => $siteName) {

            // Reconnect with the actual site
            $valuesLogin = array(
                new \Oara\Curl\Parameter('site', $siteId),
                new \Oara\Curl\Parameter('page', '/index.php?'),
            );
            $urls = array();
            $urls[] = new \Oara\Curl\Request('http://publisher.publicideas.com/reconnect.php', $valuesLogin);
            $this->_client->post($urls);

            $dStartDateAux = clone $dStartDate;
            $urls = array();
            while ($dStartDateAux <= $dEndDate) {

                $valuesFromExport = array();
                $valuesFromExport[] = new \Oara\Curl\Parameter('action', "myresume");
                $valuesFromExport[] = new \Oara\Curl\Parameter('dD', $dStartDateAux->format("d/m/Y"));
                $valuesFromExport[] = new \Oara\Curl\Parameter('dF', $dStartDateAux->format("d/m/Y"));
                $valuesFromExport[] = new \Oara\Curl\Parameter('currency', "GBP");
                $valuesFromExport[] = new \Oara\Curl\Parameter('expAct', "1");
                $valuesFromExport[] = new \Oara\Curl\Parameter('tabid', "0");
                $valuesFromExport[] = new \Oara\Curl\Parameter('partid', $siteId);
                $valuesFromExport[] = new \Oara\Curl\Parameter('Submit', "Voir");
                $urls[] = new \Oara\Curl\Request('http://publisher.publicideas.com/index.php?', $valuesFromExport);
                $dStartDateAux->add(new \DateInterval('P1D'));
            }

            try {

                $exportReportList = $this->_client->get($urls, 0 , true);
                foreach ($exportReportList as $exportReport) {
                    $exportData = \str_getcsv(\utf8_decode($exportReport), "\n");
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

                        for ($j = 1; $j < $num; $j++) {
                            $transactionExportArray = \str_getcsv($exportData[$j], ";");
                            if (isset($headerMap["Ventes"]) && isset($headerMap["pendingVentes"])
                                && isset($headerMap["Programme"]) && isset($headerMap["CA"]) && isset($headerMap["pendingCA"])
                            ) {
                                $confirmedTransactions = (int)$transactionExportArray[$headerMap["Ventes"]];
                                $pendingTransactions = (int)$transactionExportArray[$headerMap["pendingVentes"]];

                                for ($z = 0; $z < $confirmedTransactions; $z++) {
                                    $transaction = Array();
                                    $merchantFound = false;
                                    foreach ($merchantList as $merchant) {
                                        if ($merchant['name'] == $transactionExportArray[$headerMap["Programme"]]) {
                                            $transaction['merchantId'] = $merchant['id'];
                                            $merchantFound = true;
                                            break;
                                        }
                                    }
                                    if (!$merchantFound) {
                                        throw new \Exception('Merchant not found');
                                    }
                                    $transaction['merchantId'] = 1;
                                    $transaction['date'] = $dStartDateAux->format("Y-m-d H:i:s");
                                    $stringAmountValue = str_replace(',', '.', $transactionExportArray[$headerMap["CA"]]);
                                    $transaction['amount'] = \Oara\Utilities::parseDouble(floatval($stringAmountValue) / $confirmedTransactions);
                                    $transaction['commission'] = \Oara\Utilities::parseDouble(floatval($stringAmountValue) / $confirmedTransactions);
                                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                                    $totalTransactions[] = $transaction;
                                }

                                for ($z = 0; $z < $pendingTransactions; $z++) {
                                    $transaction = Array();
                                    $merchantFound = false;
                                    foreach ($merchantList as $merchant) {
                                        if ($merchant['name'] == $transactionExportArray[$headerMap["Programme"]]) {
                                            $transaction['merchantId'] = $merchant['id'];
                                            $merchantFound = true;
                                            break;
                                        }
                                    }
                                    if (!$merchantFound) {
                                        throw new \Exception('Merchant not found');
                                    }
                                    $transaction['date'] = $dStartDateAux->format("Y-m-d H:i:s");
                                    $stringAmountValue = str_replace(',', '.', $transactionExportArray[$headerMap["pendingCA"]]);
                                    $transaction['amount'] = \Oara\Utilities::parseDouble(floatval($stringAmountValue) / $pendingTransactions);
                                    $transaction['commission'] = \Oara\Utilities::parseDouble(floatval($stringAmountValue) / $pendingTransactions);
                                    $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                                    $totalTransactions[] = $transaction;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {

            }


        }

    
            return $totalTransactions;
    }
}
