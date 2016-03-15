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
 * @category   Chegg
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Chegg extends \Oara\Network
{

    /**
     * @var null
     */
    private $_client = null;

    /**
     * @param $credentials
     * @throws \Exception
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_client = new \Oara\Curl\Access($credentials);

        $valuesLogin = array(
            new \Oara\Curl\Parameter('__EVENTTARGET', ""),
            new \Oara\Curl\Parameter('__EVENTARGUMENT', ""),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24lcLogin%24txtUserName', $user),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24lcLogin%24txtPassword', $password),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24lcLogin%24btnSubmit', 'Login'),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtFirstName', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtLastName', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtEmail', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtNewPassword', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtIM', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24ddIMNetwork', '0'),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtPhone', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtFax', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtBusinessName', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtWebsiteURL', 'http://'),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24ddlBusinessType', '0'),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtBusinessDescription', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtAddress1', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtAddress2', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtCity', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24ddlState', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtOtherState', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtPostalCode', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24ddlCountry', 'US'),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtTaxID', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24ddPaymentTo', 'Company'),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtSwift', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtAccountName', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtAccountNumber', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtBankRouting', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtBankName', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtBankAddress', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtPayPal', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24txtPayQuickerEmail', ''),
            new \Oara\Curl\Parameter('ctl00%24ContentPlaceHolder1%24scSignup%24ddlReferral', 'Select'),

        );
        $html = \file_get_contents("http://cheggaffiliateprogram.com/Welcome/LogInAndSignUp.aspx?FP=C&FR=1&S=4");

        $doc = new \DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);
        $hidden = $xpath->query('//input[@type="hidden"]');
        foreach ($hidden as $values) {
            $valuesLogin[] = new \Oara\Curl\Parameter($values->getAttribute("name"), $values->getAttribute("value"));
        }

        $loginUrl = 'http://cheggaffiliateprogram.com/Welcome/LogInAndSignUp.aspx?FP=C&FR=1&S=2';
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
        $connection = false;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://cheggaffiliateprogram.com/Home.aspx?', array());
        $exportReport = $this->_client->get($urls);

        if (\preg_match('/Welcome\/Logout\.aspx/', $exportReport[0])) {
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
        $obj['name'] = "Bet 365";
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


            $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($tableList->current()));
            $num = \count($exportData);
            for ($i = 2; $i < $num - 1; $i++) {
                $transactionExportArray = \str_getcsv($exportData[$i], ";");
                $transaction = Array();
                $transaction['merchantId'] = 1;
                $transactionDate = \DateTime::createFromFormat("d-m-Y", $transactionExportArray[1]);
                $transaction['date'] = $transactionDate->format("Y-m-d 00:00:00");
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
