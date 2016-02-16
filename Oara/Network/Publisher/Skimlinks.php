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
 * @category   Skimlinks
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Skimlinks extends \Oara\Network
{
    /**
     * Public API Key
     * @var string
     */
    private $_publicapikey = null;
    /**
     * Private API Key
     * @var string
     */
    private $_privateapikey = null;

    /**
     * Constructor and Login
     * @param $credentials
     * @return Daisycon
     */
    public function login($credentials)
    {

        $dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;

        if (!\Oara\Utilities::mkdir_recursive($dir, 0777)) {
            throw new Exception ('Problem creating folder in Access');
        }

        $cookies = $dir . $credentials["cookieName"] . '_cookies.txt';
        @unlink($cookies);
        $this->_options = array(
            CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_COOKIEJAR => $cookies,
            CURLOPT_COOKIEFILE => $cookies,
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: es,en-us;q=0.7,en;q=0.3', 'Accept-Encoding: gzip, deflate', 'Connection: keep-alive', 'Cache-Control: max-age=0'),
            CURLOPT_ENCODING => "gzip",
            CURLOPT_VERBOSE => false
        );

        $this->_publicapikey = $credentials['user'];
        $this->_privateapikey = $credentials['apiPassword'];

    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["user"]["description"] = "User Log in";
        $parameter["user"]["required"] = true;
        $credentials[] = $parameter;

        $parameter = array();
        $parameter["password"]["description"] = "Password to Log in";
        $parameter["password"]["required"] = true;
        $credentials[] = $parameter;

        return $credentials;
    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;

        try {
            self::getMerchantList();
            $connection = true;
        } catch (Exception $e) {

        }

        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getMerchantList()
     */
    public function getMerchantList()
    {

        $publicapikey = $this->_publicapikey;
        $privateapikey = $this->_privateapikey;

        $date = new DateTime();
        $timestamp = $date->getTimestamp();
        $authtoken = md5($timestamp . $privateapikey);
        $date = \DateTime::now();

        $merchants = Array();

        $valuesFromExport = array(
            new \Oara\Curl\Parameter('version', '0.5'),
            new \Oara\Curl\Parameter('timestamp', $timestamp),
            new \Oara\Curl\Parameter('apikey', $publicapikey),
            new \Oara\Curl\Parameter('authtoken', $authtoken),
            new \Oara\Curl\Parameter('startdate', '2009-01-01'), //minimum date
            new \Oara\Curl\Parameter('enddate', $date->format!("yyyy-MM-dd")),
            new \Oara\Curl\Parameter('format', 'json')
        );

        $rch = curl_init();
        $options = $this->_options;
        $arg = array();
        foreach ($valuesFromExport as $parameter) {
            $arg [] = $parameter->getKey() . '=' . urlencode($parameter->getValue());
        }
        curl_setopt($rch, CURLOPT_URL, 'https://api-reports.skimlinks.com/publisher/reportmerchants?' . implode('&', $arg));

        curl_setopt_array($rch, $options);
        $json = curl_exec($rch);
        curl_close($rch);

        $jsonArray = json_decode($json, true);

        $iteration = 0;
        while (count($jsonArray["skimlinksAccount"]["merchants"]) != 0) {

            foreach ($jsonArray["skimlinksAccount"]["merchants"] as $i) {
                $obj = Array();
                $obj['cid'] = $i["merchantID"];
                $obj['name'] = $i["merchantName"];
                $merchants[] = $obj;
            }

            $iteration++;

            $valuesFromExport = array(
                new \Oara\Curl\Parameter('version', '0.5'),
                new \Oara\Curl\Parameter('timestamp', $timestamp),
                new \Oara\Curl\Parameter('apikey', $publicapikey),
                new \Oara\Curl\Parameter('authtoken', $authtoken),
                new \Oara\Curl\Parameter('startdate', '2009-01-01'), //minimum date
                new \Oara\Curl\Parameter('enddate', $date->format!("yyyy-MM-dd")),
                new \Oara\Curl\Parameter('format', 'json'),
                new \Oara\Curl\Parameter('responseFrom', $iteration * 100),

            );

            $rch = curl_init();
            $options = $this->_options;
            $arg = array();
            foreach ($valuesFromExport as $parameter) {
                $arg [] = $parameter->getKey() . '=' . urlencode($parameter->getValue());
            }
            curl_setopt($rch, CURLOPT_URL, 'https://api-reports.skimlinks.com/publisher/reportmerchants?' . implode('&', $arg));

            curl_setopt_array($rch, $options);
            $json = curl_exec($rch);
            curl_close($rch);

            $jsonArray = json_decode($json, true);


        }


        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {

        $totalTransactions = array();

        $publicapikey = $this->_publicapikey;
        $privateapikey = $this->_privateapikey;

        $date = new DateTime();
        $timestamp = $date->getTimestamp();
        $authtoken = md5($timestamp . $privateapikey);
        $date = \DateTime::now();

        $valuesFromExport = array(
            new \Oara\Curl\Parameter('version', '0.5'),
            new \Oara\Curl\Parameter('timestamp', $timestamp),
            new \Oara\Curl\Parameter('apikey', $publicapikey),
            new \Oara\Curl\Parameter('authtoken', $authtoken),
            new \Oara\Curl\Parameter('startDate', $dStartDate->format!("yyyy-MM-dd")),
            new \Oara\Curl\Parameter('endDate', $dEndDate->format!("yyyy-MM-dd")),
            new \Oara\Curl\Parameter('format', 'json')
        );

        $rch = curl_init();
        $options = $this->_options;
        $arg = array();
        foreach ($valuesFromExport as $parameter) {
            $arg [] = $parameter->getKey() . '=' . urlencode($parameter->getValue());
        }
        curl_setopt($rch, CURLOPT_URL, 'https://api-report.skimlinks.com/publisher/reportcommissions?' . implode('&', $arg));

        curl_setopt_array($rch, $options);
        $json = curl_exec($rch);
        curl_close($rch);

        $jsonArray = json_decode($json, true);

        foreach ($jsonArray["skimlinksAccount"]["commissions"] as $i) {
            $transaction = Array();

            $transaction['merchantId'] = $i["merchantID"];
            $transaction['unique_id'] = $i["commissionID"];
            $transactionDate = new \DateTime($i["date"], 'YYYY-MM-DD', 'en');
            $transaction['date'] = $transactionDate->format!("yyyy-MM-dd HH:mm:ss");
            $transaction['amount'] = (double)$i["orderValue"] / 100;
            $transaction['commission'] = (double)$i["commissionValue"] / 100;
            $transactionStatus = $i["status"];
            if ($transactionStatus == "active") {
                $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            } else if ($transactionStatus == "cancelled") {
                $transaction ['status'] = \Oara\Utilities::STATUS_DECLINED;
            } else {
                throw new Exception ("New status found {$transactionStatus}");
            }
            if ($i["customID"] != null) {
                $transaction['custom_id'] = $i["customID"];
            }

            $totalTransactions[] = $transaction;
        }

        return $totalTransactions;
    }

    /**
     * (non-PHPdoc)
     * @see Oara/Network/Base#getPaymentHistory()
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        return $paymentHistory;
    }

}
