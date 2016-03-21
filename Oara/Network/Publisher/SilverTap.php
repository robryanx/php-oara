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
 * @category   St
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class SilverTap extends \Oara\Network
{
    private $_client = null;
    private $_serverUrl = null;

    /**
     * @param $credentials
     * @throws Exception
     * @throws \Exception
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];

        $this->_serverUrl = "https://mats.silvertap.com/";
        $this->_client = new \Oara\Curl\Access ($credentials);

        $loginUrl = $this->_serverUrl . 'Login.aspx?ReturnUrl=/';
        $valuesLogin = array(new \Oara\Curl\Parameter('txtUsername', $user),
            new \Oara\Curl\Parameter('txtPassword', $password),
            new \Oara\Curl\Parameter('cmdSubmit', 'Login'),
            new \Oara\Curl\Parameter('__EVENTTARGET', ''),
            new \Oara\Curl\Parameter('__EVENTARGUMENT', '')
        );
        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $this->_client->post($urls);
        $this->_exportPassword = \md5($password);
        $this->_exportUser = self::getExportUser();
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
     * @return null
     * @throws \Exception
     */
    private function getExportUser()
    {
        $exporUser = null;

        $urls = array();
        $urls[] = new \Oara\Curl\Request($this->_serverUrl . 'Reports/Default.aspx?', array(new \Oara\Curl\Parameter('report', 'Performance')));
        $this->_client->get($urls);

        $urls = array();
        $urls[] = new \Oara\Curl\Request($this->_serverUrl . '/Reports/RemoteHelp.aspx?', array());
        $result = $this->_client->get($urls);

        /*** load the html into the object ***/
        $doc = new \DOMDocument();
        \libxml_use_internal_errors(true);
        $doc->validateOnParse = true;
        $doc->loadHTML($result[0]);
        $textareaList = $doc->getElementsByTagName('textarea');

        $messageNode = $textareaList->item(0);
        if (!isset($messageNode->firstChild)) {
            throw new \Exception('Error getting the User');
        }
        $messageStr = $messageNode->firstChild->nodeValue;

        $parseUrl = \parse_url(\trim($messageStr));
        $parameters = \explode('&', $parseUrl['query']);
        foreach ($parameters as $parameter) {
            $parameterValue = \explode('=', $parameter);
            if ($parameterValue[0] == 'user') {
                $exporUser = $parameterValue[1];
            }
        }
        return $exporUser;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = true;
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = Array();

        $exportMerchantParameters = array(new \Oara\Curl\Parameter('user', $this->_exportUser),
            new \Oara\Curl\Parameter('pwd', $this->_exportPassword),
            new \Oara\Curl\Parameter('type', 'csv'),
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request($this->_serverUrl . 'Feeds/Merchantfeed.aspx?', $exportMerchantParameters);
        $result = $this->_client->get($urls);

        $exportData = \str_getcsv($result[0], "\n");
        $num = \count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionMerchantArray = \str_getcsv($exportData[$i], ",");
            $obj = Array();
            $obj['cid'] = $transactionMerchantArray[4];
            $obj['name'] = "$transactionMerchantArray[1] ($transactionMerchantArray[5])";
            $merchants[] = $obj;
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
        $startDate = $dStartDate->format('d/m/Y');
        $endDate = $dEndDate->format('d/m/Y');

        $marchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);

        $valueIndex = 9;
        $commissionIndex = 16;
        $statusIndex = 17;


        $valuesFormExport = array(new \Oara\Curl\Parameter('user', $this->_exportUser),
            new \Oara\Curl\Parameter('pwd', $this->_exportPassword),
            new \Oara\Curl\Parameter('report', 'AMSCommission_Breakdown'),
            new \Oara\Curl\Parameter('groupby', 'Programme'),
            new \Oara\Curl\Parameter('groupdate', 'Day'),
            new \Oara\Curl\Parameter('creative', ''),
            new \Oara\Curl\Parameter('CommOnly', '1'),
            new \Oara\Curl\Parameter('showimpressions', 'True'),
            new \Oara\Curl\Parameter('showclicks', 'True'),
            new \Oara\Curl\Parameter('showreferrals', 'True'),
            new \Oara\Curl\Parameter('showtransactionvalues', 'True'),
            new \Oara\Curl\Parameter('sort', 'Date asc'),
            new \Oara\Curl\Parameter('format', 'csv'),
        );
        $valuesFormExport[] = new \Oara\Curl\Parameter('datefrom', $startDate);
        $valuesFormExport[] = new \Oara\Curl\Parameter('dateto', $endDate);
        $urls = array();
        $urls[] = new \Oara\Curl\Request($this->_serverUrl . 'reports/remote.aspx?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $exportData = \str_getcsv($exportReport[0], "\r\n");
        $num = \count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = \str_getcsv($exportData[$i], ",");
            if (isset($marchantIdList[$transactionExportArray[4]])) {
                $transaction = Array();
                $transaction['unique_id'] = \preg_replace('/\D/', '', $transactionExportArray[0]);
                $transaction['merchantId'] = $transactionExportArray[4];

                $transactionDate = \DateTime::createFromFormat("d/m/Y H:i:s", $transactionExportArray[2]);
                $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");

                if ($transactionExportArray[7] != null) {
                    $transaction['custom_id'] = $transactionExportArray[7];
                }

                if (\preg_match('/Unpaid Confirmed/', $transactionExportArray[$statusIndex]) || \preg_match('/Paid Confirmed/', $transactionExportArray[$statusIndex])) {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else
                    if (\preg_match('/Unpaid Unconfirmed/', $transactionExportArray[$statusIndex])) {
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else
                        if (\preg_match('/Unpaid Rejected/', $transactionExportArray[$statusIndex])) {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        } else {
                            throw new \Exception("No Status supported " . $transactionExportArray[$statusIndex]);
                        }

                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[$valueIndex]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[$commissionIndex]);
                $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;

    }

}
