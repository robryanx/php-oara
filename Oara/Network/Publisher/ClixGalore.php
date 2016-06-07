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
 * @category   ClixGalore
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class ClixGalore extends \Oara\Network
{
    private $_client = null;
    private $_websiteList = array();

    /**
     * @param $credentials
     * @throws Exception
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_client = new \Oara\Curl\Access($credentials);


        $loginUrl = 'https://www.clixgalore.co.uk/MemberLogin.aspx';
        $valuesLogin = array(new \Oara\Curl\Parameter('txt_UserName', $user),
            new \Oara\Curl\Parameter('txt_Password', $password),
            new \Oara\Curl\Parameter('cmd_login.x', '29'),
            new \Oara\Curl\Parameter('cmd_login.y', '8')
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, array());
        $exportReport = $this->_client->get($urls);
        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $hidden = $xpath->query('//input[@type="hidden"]');
        foreach ($hidden as $values) {
            $valuesLogin[] = new \Oara\Curl\Parameter($values->getAttribute("name"), $values->getAttribute("value"));
        }

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
        return $connection;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getMerchantList()
    {
        $merchants = array();

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www.clixgalore.co.uk/AffiliateAdvancedReporting.aspx', array());
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[contains(concat(" ", normalize-space(@id), " "), " dd_AffAdv_program_list_aff_adv_program_list ")]');

        $count = $results->length;
        if ($count == 1) {
            $selectNode = $results->item(0);
            $merchantLines = $selectNode->childNodes;
            for ($i = 0; $i < $merchantLines->length; $i++) {
                $cid = $merchantLines->item($i)->attributes->getNamedItem("value")->nodeValue;
                if ($cid != 0) {
                    $obj = array();
                    $obj['cid'] = $merchantLines->item($i)->attributes->getNamedItem("value")->nodeValue;
                    $obj['name'] = $merchantLines->item($i)->nodeValue;
                    $obj['url'] = '';
                    $merchants[] = $obj;
                }
            }
        } else {
            throw new \Exception('Problem getting the websites');
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
        $statusArray = array(0, 1, 2);
        foreach ($statusArray as $status) {
            $valuesFromExport = array(new \Oara\Curl\Parameter('AfID', '0'),
                new \Oara\Curl\Parameter('S', ''),
                new \Oara\Curl\Parameter('ST', '2'),
                new \Oara\Curl\Parameter('Period', '6'),
                new \Oara\Curl\Parameter('AdID', '0'),
                new \Oara\Curl\Parameter('B', '2')
            );
            $valuesFromExport[] = new \Oara\Curl\Parameter('SD', $dStartDate->format("Y-m-d"));
            $valuesFromExport[] = new \Oara\Curl\Parameter('ED', $dEndDate->format("Y-m-d"));
            $valuesFromExport[] = new \Oara\Curl\Parameter('Status', $status);

            $urls = array();
            $urls[] = new \Oara\Curl\Request('http://www.clixgalore.co.uk/AffiliateTransactionSentReport_Excel.aspx?', $valuesFromExport);
            $exportReport = $this->_client->get($urls);
            $exportData = \Oara\Utilities::htmlToCsv($exportReport[0]);
            $num = \count($exportData);
            for ($i = 1; $i < $num; $i++) {
                $transactionExportArray = \str_getcsv($exportData[$i], ";");
                if (isset($merchantMap[$transactionExportArray[2]])) {
                    $transaction = Array();
                    $merchantId = (int)$merchantMap[$transactionExportArray[2]];
                    $transaction['merchantId'] = $merchantId;
                    $transactionDate = \DateTime::createFromFormat("d M Y H:m", $transactionExportArray[0]);
                    $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");

                    if ($transactionExportArray[6] != null) {
                        $transaction['custom_id'] = $transactionExportArray[6];
                    }
                    if ($status == 1) {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else
                        if ($status == 2) {
                            $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        } else
                            if ($status == 0) {
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
