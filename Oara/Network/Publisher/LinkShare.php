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
 * @category   Ls
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class LinkShare extends \Oara\Network
{

    public $_nid = null;
    protected $_sitesAllowed = array();
    private $_client = null;
    private $_siteList = array();

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $user = $credentials ['user'];
        $password = $credentials ['password'];
        $this->_client = new \Oara\Curl\Access ($credentials);

        $loginUrl = 'https://login.linkshare.com/sso/login?service=' . \urlencode("http://cli.linksynergy.com/cli/publisher/home.php");
        $valuesLogin = array(
            new \Oara\Curl\Parameter ('HEALTHCHECK', 'HEALTHCHECK PASSED.'),
            new \Oara\Curl\Parameter ('username', $user),
            new \Oara\Curl\Parameter ('password', $password),
            new \Oara\Curl\Parameter ('login', 'Log In')
        );

        $urls = array();
        $urls [] = new \Oara\Curl\Request ($loginUrl, array());
        $exportReport = $this->_client->get($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $hidden = $xpath->query('//input[@type="hidden"]');
        foreach ($hidden as $values) {
            $valuesLogin[] = new \Oara\Curl\Parameter($values->getAttribute("name"), $values->getAttribute("value"));
        }
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $formList = $xpath->query('//form');
        foreach ($formList as $form) {
            $loginUrl = "https://login.linkshare.com" . $form->getAttribute("action");
        }
        $urls = array();
        $urls [] = new \Oara\Curl\Request ($loginUrl, $valuesLogin);
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

        $urls [] = new \Oara\Curl\Request ('http://cli.linksynergy.com/cli/publisher/home.php?', array());
        $result = $this->_client->get($urls);

        // Check if the credentials are right
        if (\preg_match('/https:\/\/cli\.linksynergy\.com\/cli\/common\/logout\.php/', $result [0], $matches)) {

            $urls = array();
            $urls [] = new \Oara\Curl\Request ('https://cli.linksynergy.com/cli/publisher/my_account/marketingChannels.php', array());
            $exportReport = $this->_client->get($urls);

            $doc = new \DOMDocument();
            @$doc->loadHTML($exportReport[0]);
            $xpath = new \DOMXPath($doc);
            $results = $xpath->query('//table');
            foreach ($results as $table) {
                $tableCsv = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($table));
            }

            $resultsSites = array();
            $num = \count($tableCsv);
            for ($i = 1; $i < $num; $i++) {
                $siteArray = \str_getcsv($tableCsv [$i], ";");
                if (isset ($siteArray [2]) && \is_numeric($siteArray [2])) {
                    $result = array();
                    $result ["id"] = $siteArray [2];
                    $result ["name"] = $siteArray [1];
                    $result ["url"] = "https://cli.linksynergy.com/cli/publisher/common/changeCurrentChannel.php?sid=" . $result ["id"];
                    $resultsSites [] = $result;
                }
            }

            $siteList = array();
            foreach ($resultsSites as $resultSite) {
                $site = new \stdClass ();
                $site->website = $resultSite ["name"];
                $site->url = $resultSite ["url"];
                $parsedUrl = \parse_url($site->url);
                $attributesArray = \explode('&', $parsedUrl ['query']);
                $attributeMap = array();
                foreach ($attributesArray as $attribute) {
                    $attributeValue = \explode('=', $attribute);
                    $attributeMap [$attributeValue [0]] = $attributeValue [1];
                }
                $site->id = $attributeMap ['sid'];
                // Login into the Site ID
                $urls = array();
                $urls [] = new \Oara\Curl\Request ($site->url, array());
                $this->_client->get($urls);

                $urls = array();
                $urls [] = new \Oara\Curl\Request ('https://cli.linksynergy.com/cli/publisher/reports/reporting.php', array());
                $result = $this->_client->get($urls);
                if (preg_match_all('/\"token_one\"\: \"(.+)\"/', $result[0], $match)) {
                    $site->token = $match[1][0];
                }

                $urls = array();
                $urls [] = new \Oara\Curl\Request ('http://cli.linksynergy.com/cli/publisher/links/webServices.php', array());
                $result = $this->_client->get($urls);
                if (preg_match_all('/<div class="token">(.+)<\/div>/', $result[0], $match)) {
                    $site->secureToken = $match[1][1];
                }

                $siteList [] = $site;

            }
            $connection = true;
            $this->_siteList = $siteList;
        }
        return $connection;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMerchantList()
    {
        $merchants = array();
        $merchantIdMap = array();
        foreach ($this->_siteList as $site) {

            $urls = array();
            $urls [] = new \Oara\Curl\Request ($site->url, array());
            $this->_client->get($urls);

            $urls = array();
            $urls [] = new \Oara\Curl\Request ('http://cli.linksynergy.com/cli/publisher/programs/carDownload.php', array());
            $result = $this->_client->get($urls);

            $exportData = \explode(",\n", $result [0]);

            $num = \count($exportData);
            for ($i = 1; $i < $num - 1; $i++) {
                $merchantArray = \str_getcsv($exportData [$i], ",", '"');
                if (!\in_array($merchantArray [2], $merchantIdMap)) {
                    $obj = Array();
                    if (!isset ($merchantArray [2])) {
                        throw new \Exception ("Error getting merchants");
                    }
                    $obj ['cid'] = ( int )$merchantArray [2];
                    $obj ['name'] = $merchantArray [0];
                    $obj ['description'] = $merchantArray [3];
                    $obj ['url'] = $merchantArray [1];
                    $merchants [] = $obj;
                    $merchantIdMap [] = $obj ['cid'];
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
     * @throws Exception
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = Array();
        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);
        $uniqueIdMap = array();

        foreach ($this->_siteList as $site) {
            if (empty($this->_sitesAllowed) || in_array($site->id, $this->_sitesAllowed)) {
                echo "getting Transactions for site " . $site->id . "\n\n";

                $url = "https://ran-reporting.rakutenmarketing.com/en/reports/individual-item-report/filters?start_date=" . $dStartDate->format("Y-m-d") . "&end_date=" . $dEndDate->format("Y-m-d") . "&include_summary=N" . "&network=" . $this->_nid . "&tz=GMT&date_type=transaction&token=" . urlencode($site->token);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 50);
                $result = curl_exec($ch);
                $info = curl_getinfo($ch);
                if ($info['http_code'] != 200) {
                    return $totalTransactions;
                }
                curl_close($ch);

                $url = "https://ran-reporting.rakutenmarketing.com/en/reports/signature-orders-report/filters?start_date=" . $dStartDate->format("Y-m-d") . "&end_date=" . $dEndDate->format("Y-m-d") . "&include_summary=N" . "&network=" . $this->_nid . "&tz=GMT&date_type=transaction&token=" . urlencode($site->token);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 50);
                $resultSignature = curl_exec($ch);
                $info = curl_getinfo($ch);
                if ($info['http_code'] != 200) {
                    return $totalTransactions;
                }
                curl_close($ch);

                $signatureMap = array();
                $exportData = str_getcsv($resultSignature, "\n");
                $num = count($exportData);
                for ($j = 1; $j < $num; $j++) {
                    $signatureData = str_getcsv($exportData [$j], ",");
                    $signatureMap[$signatureData[3]] = $signatureData[0];
                }


                $exportData = \str_getcsv($result, "\n");
                $num = \count($exportData);
                for ($j = 1; $j < $num; $j++) {
                    $transactionData = \str_getcsv($exportData [$j], ",");

                    if (isset($merchantIdList[$transactionData[3]]) && count($transactionData) == 10) {
                        $transaction = Array();
                        $transaction ['merchantId'] = ( int )$transactionData [3];
                        $transactionDate = \DateTime::createFromFormat("m/d/y H:i:s", $transactionData [1] . " " . $transactionData [2]);
                        $transaction ['date'] = $transactionDate->format("Y-m-d H:i:s");


                        if (isset($signatureMap[$transactionData [0]])) {
                            $transaction ['custom_id'] = $signatureMap[$transactionData [0]];
                        }

                        if (!isset($uniqueIdMap[$transactionData[0]])) {
                            $uniqueIdMap[$transactionData[0]] = 1;
                        } else {
                            $uniqueIdMap[$transactionData[0]]++;
                        }
                        $transaction ['unique_id'] = $transactionData[0] . '_' . $uniqueIdMap[$transactionData[0]];

                        $sales = \Oara\Utilities::parseDouble($transactionData [7]);

                        if ($sales != 0) {
                            $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                        } else if ($sales == 0) {
                            $transaction ['status'] = \Oara\Utilities::STATUS_PENDING;
                        }

                        $transaction ['amount'] = $sales;
                        $transaction ['commission'] = \Oara\Utilities::parseDouble($transactionData [9]);
                        $totalTransactions [] = $transaction;
                    }
                }
            }
        }

        return $totalTransactions;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();
        $past = new \DateTime ("2013-01-01 00:00:00");
        $now = new \DateTime ();

        foreach ($this->_siteList as $site) {

            $interval = $past->diff($now);
            $numberYears = (int)$interval->format('%y') + 1;
            $auxStartDate = clone $past;

            for ($i = 0; $i < $numberYears; $i++) {

                $auxEndData = clone $auxStartDate;
                $auxEndData = $auxEndData->add(new \DateInterval('P1Y'));

                $url = "https://reportws.linksynergy.com/downloadreport.php?bdate=" . $auxStartDate->format("Ymd") . "&edate=" . $auxEndData->format("Ymd") . "&token=" . $site->secureToken . "&nid=" . $this->_nid . "&reportid=1";
                $result = \file_get_contents($url);
                if (\preg_match("/You cannot request/", $result)) {
                    throw new \Exception ("Reached the limit");
                }
                $paymentLines = \str_getcsv($result, "\n");
                $number = \count($paymentLines);
                for ($j = 1; $j < $number; $j++) {
                    $paymentData = \str_getcsv($paymentLines [$j], ",");
                    $obj = array();
                    $date = \DateTime::createFromFormat("Y-m-d", $paymentData [1]);
                    $obj ['date'] = $date->format("Y-m-d H:i:s");
                    $obj ['value'] = \Oara\Utilities::parseDouble($paymentData [5]);
                    $obj ['method'] = "BACS";
                    $obj ['pid'] = $paymentData [0];
                    $paymentHistory [] = $obj;
                }

                $auxStartDate->add(new \DateInterval('P1Y'));
            }
        }

        return $paymentHistory;
    }
}
