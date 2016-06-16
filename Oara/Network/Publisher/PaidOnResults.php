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
 * @category   Por
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class PaidOnResults extends \Oara\Network
{

    private $_client = null;
    private $_sessionId = null;

    /**
     * @param $credentials
     * @throws Exception
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    public function login($credentials)
    {
        $this->_user = $credentials['user'];
        $password = $credentials['password'];
        $this->_apiPassword = $credentials['apipassword'];
        $this->_client = new \Oara\Curl\Access ($credentials);

        $loginUrl = 'https://www.paidonresults.com/login/';
        $valuesLogin = array(
            new \Oara\Curl\Parameter('username', $this->_user),
            new \Oara\Curl\Parameter('password', $password)
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $this->_client->post($urls);

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://affiliate.paidonresults.com/cgi-bin/home.pl', array());
        $exportReport = $this->_client->post($urls);
        if (!\preg_match('/http\:\/\/affiliate\.paidonresults\.com\/cgi\-bin\/logout\.pl/', $exportReport[0], $matches)) {
            throw new \Exception("Error on login");
        }

        if (\preg_match("/URL=(.*)\"/", $exportReport[0], $matches)) {
            $urls = array();
            $urls[] = new \Oara\Curl\Request($matches[1], array());
            $this->_client->get($urls);
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
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = true;
        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = Array();

        $valuesFormExport = array(new \Oara\Curl\Parameter('apikey', $this->_apiPassword),
            new \Oara\Curl\Parameter('Format', 'CSV'),
            new \Oara\Curl\Parameter('FieldSeparator', 'comma'),
            new \Oara\Curl\Parameter('AffiliateID', $this->_user),
            new \Oara\Curl\Parameter('MerchantCategories', 'ALL'),
            new \Oara\Curl\Parameter('Fields', 'MerchantID,MerchantName,MerchantURL'),
            new \Oara\Curl\Parameter('JoinedMerchants', 'YES'),
            new \Oara\Curl\Parameter('MerchantsNotJoined', 'NO'),
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://affiliate.paidonresults.com/api/merchant-directory?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $exportData = \str_getcsv($exportReport[0], "\r\n");
        $exportData = \preg_replace("/\n/", "", $exportData);
        $num = \count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $merchantExportArray = \str_getcsv($exportData[$i], ",");
            $obj = Array();
            $obj['cid'] = $merchantExportArray[0];
            $obj['name'] = $merchantExportArray[1];
            $obj['url'] = $merchantExportArray[2];
            $merchants[] = $obj;
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
        $totalTransactions = Array();
        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);


        $urls = array();
        $valuesFormExport = array(new \Oara\Curl\Parameter('apikey', $this->_apiPassword),
            new \Oara\Curl\Parameter('Format', 'CSV'),
            new \Oara\Curl\Parameter('FieldSeparator', 'comma'),
            new \Oara\Curl\Parameter('Fields', 'MerchantID,OrderDate,NetworkOrderID,CustomTrackingID,OrderValue,AffiliateCommission,TransactionType,PaidtoAffiliate,DatePaidToAffiliate'),
            new \Oara\Curl\Parameter('AffiliateID', $this->_user),
            new \Oara\Curl\Parameter('DateFormat', 'DD/MM/YYYY+HH:MN:SS'),
            new \Oara\Curl\Parameter('PendingSales', 'YES'),
            new \Oara\Curl\Parameter('ValidatedSales', 'YES'),
            new \Oara\Curl\Parameter('VoidSales', 'YES'),
            new \Oara\Curl\Parameter('GetNewSales', 'YES')
        );
        $valuesFormExport[] = new \Oara\Curl\Parameter('DateFrom', $dStartDate->format("Y-m-d"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('DateTo', $dEndDate->format("Y-m-d"));
        $urls[] = new \Oara\Curl\Request('http://affiliate.paidonresults.com/api/transactions?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);

        $exportData = \str_getcsv($exportReport[0], "\r\n");
        $num = \count($exportData);
        for ($i = 1; $i < $num; $i++) {

            $exportData[$i] = preg_replace("/\n/", "", $exportData[$i]);
            $transactionExportArray = \str_getcsv($exportData[$i], ",");
            if (isset($merchantIdList[$transactionExportArray[0]])) {
                $transaction = array();
                $transaction['merchantId'] = $transactionExportArray[0];
                $transactionDate = \DateTime::createFromFormat("d/m/Y H:i:s", $transactionExportArray[1]);
                $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");
                $transaction['unique_id'] = $transactionExportArray[2];
                if ($transactionExportArray[3] != null) {
                    $transaction['custom_id'] = $transactionExportArray[3];
                }
                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[4]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[5]);

                if ($transactionExportArray[6] == 'VALIDATED') {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else
                    if ($transactionExportArray[6] == 'PENDING') {
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else
                        if ($transactionExportArray[6] == 'VOID') {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        }

                $totalTransactions[] = $transaction;
            }
        }

        return $totalTransactions;

    }
}
