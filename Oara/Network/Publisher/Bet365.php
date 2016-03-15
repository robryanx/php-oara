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
 * @category   Bet365
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Bet365 extends \Oara\Network
{

    private $_client = null;

    /**
     * @param $credentials
     * @throws Exception
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_client = new \Oara\Curl\Access($credentials);

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www.bet365affiliates.com/ui/pages/affiliates/affiliates.aspx', array());
        $exportReport = $this->_client->get($urls);


        $valuesLogin = array(
            new \Oara\Curl\Parameter('txtUserName', $user),
            new \Oara\Curl\Parameter('txtPassword', $password),
            new \Oara\Curl\Parameter('ctl00%24MasterHeaderPlaceHolder%24ctl00%24userNameTextbox', $user),
            new \Oara\Curl\Parameter('ctl00%24MasterHeaderPlaceHolder%24ctl00%24passwordTextbox', $password),
            new \Oara\Curl\Parameter('ctl00%24MasterHeaderPlaceHolder%24ctl00%24tempPasswordTextbox', 'Password'),
            new \Oara\Curl\Parameter('ctl00%24MasterHeaderPlaceHolder%24ctl00%24goButton.x', '19'),
            new \Oara\Curl\Parameter('ctl00%24MasterHeaderPlaceHolder%24ctl00%24goButton.y', '15')
        );
        $forbiddenList = array('txtPassword', 'txtUserName');

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $hiddenList = $xpath->query('//input[@type="hidden"]');
        foreach ($hiddenList as $hidden) {
            if (!in_array($hidden->getAttribute("name"), $forbiddenList)) {
                $valuesLogin[] = new \Oara\Curl\Parameter($hidden->getAttribute("name"), $hidden->getAttribute("value"));
            }
        }

        $loginUrl = 'https://www.bet365affiliates.com/Members/CMSitePages/SiteLogin.aspx?lng=1';
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www.bet365affiliates.com/ui/pages/affiliates/affiliates.aspx', array());
        $this->_client->post($loginUrl, $valuesLogin);
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
        $connection = false;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://www.bet365affiliates.com/UI/Pages/Affiliates/?', array());
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[contains(concat(" ", normalize-space(@id), " "), " ctl00_MasterHeaderPlaceHolder_ctl00_LogoutLinkButton ")]');
        if (count($results) > 0) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = array();

        $obj = array();
        $obj['cid'] = 1;
        $obj['name'] = "Bet 365";
        $merchants[] = $obj;

        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {

        $totalTransactions = array();

        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('FromDate', $dStartDate->format("d/m/Y"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('ToDate', $dEndDate->format("d/m/Y"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('ReportType', 'dailyReport');
        $valuesFromExport[] = new \Oara\Curl\Parameter('Link', '-1');

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://www.bet365affiliates.com/Members/Members/Statistics/Print.aspx?', $valuesFromExport);
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $tableList = $xpath->query('//*[contains(concat(" ", normalize-space(@id), " "), " Results ")]');

        if (!\preg_match("/No results exist/", $exportReport[0])) {
            $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($tableList->item(0)));
            $num = \count($exportData);
            for ($i = 2; $i < $num - 1; $i++) {
                $transactionExportArray = \str_getcsv($exportData[$i], ";");

                $transaction = Array();
                $transaction['merchantId'] = 1;
                $transactionDate = \DateTime::createFromFormat("d-m-Y", $transactionExportArray[1]);
                $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");
                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[27]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[32]);
                if ($transaction['amount'] != 0 && $transaction['commission'] != 0) {
                    $totalTransactions[] = $transaction;
                }
            }
        }

        return $totalTransactions;
    }

}
