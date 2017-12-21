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
class OptimiseMedia extends \Oara\Network
{

    private $_affiliateID = null;
    private $_apiKey = null;
    private $_privateKey = null;
    private $_versionNumber = "v1.2.1";
    /*
     * Agency Id
     * 1 = UK
     * 95 = India
     * 118 = SE Asia
     * 12 = Poland
     * 142 = Brazil
     */
    private $_agencyId = 1;

    /**
     * @param $credentials
     * @throws Exception
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    public function login($credentials)
    {
        $this->_affiliateID = $credentials['user'];
        $this->_apiKey = $credentials['password'];
        $this->_privateKey = $credentials['apipassword'];


    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "Affiliate ID -> Take a look at any of your Optimise Tracking links - there will be a parameter called &AID= - this is your AffiliateID";
        $parameter["required"] = true;
        $parameter["name"] = "Api key";
        $credentials["user"] = $parameter;

        $parameter = array();
        $parameter["description"] = "API KEY -> Within your Optimise Affiliate Login (My Details > Update Account Details > API Key)  you will be able to generate this";
        $parameter["required"] = true;
        $parameter["name"] = "Api key";
        $credentials["password"] = $parameter;

        $parameter = array();
        $parameter["description"] = "PRIVATE KEY -> Within your Optimise Affiliate Login (My Details > Update Account Details > API Key)  you will be able to generate this";
        $parameter["required"] = true;
        $parameter["name"] = "Private key";
        $credentials["apipassword"] = $parameter;

        return $credentials;
    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = true;
        $result = $this->apiCall("GetAccounts/ValidateLogin");
        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = Array();

        $params = array("ProgramStatus" => "live","AID"=>$this->_affiliateID);
        $result = $this->apiCall("GetProgrammes",$params);

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
        $urls[] = new \Oara\Curl\Request('https://affiliate.paidonresults.com/api/transactions?', $valuesFormExport);
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

    private function apiCall($function,$params = array()){

        date_default_timezone_set("UTC");
        $t = microtime(true);
        $micro = sprintf("%03d",($t - floor($t)) * 1000);
        $utc = gmdate('Y-m-d H:i:s.', $t).$micro;

        $sig_data = $utc;
        $api_key = $this->_apiKey;
        $private_key = $this->_privateKey;
        $concateData = $private_key.$sig_data;
        $sig = md5($concateData);

        $basicParams = array('Key' => $api_key, 'Sig' => $sig, 'SigData' => $sig_data,'output' => 'json');
        $callParams = \array_merge($basicParams,$params);
        $url = "https://api.omgpm.com/network/OMGNetworkApi.svc/{$this->_versionNumber}/$function?" . http_build_query($callParams);
        $headers = array("Content-Type: application/json",
            "Accept: application/json",
            "Access-Control-Request-Method: GET");
        $ch = \curl_init();
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        \curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = \curl_exec($ch);
        $status = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return \json_decode($result, true);
    }
}
