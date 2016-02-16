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
 * @author Carlos Morillo Merino
 * @category Shuttlefare
 * @copyright Fubra Limited
 * @version Release: 01.00
 *
 */
class Shuttlefare extends \Oara\Network
{

    /**
     * @var null
     */
    private $_client = null;

    /**
     * @param $credentials
     * @throws Exception
     */
    public function login($credentials)
    {

        $user = $credentials ['user'];
        $password = $credentials ['password'];

        $loginUrl = 'http://affiliates.shuttlefare.com/users/sign_in';

        $dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;

        if (!\Oara\Utilities::mkdir_recursive($dir, 0777)) {
            throw new Exception ('Problem creating folder in Access');
        }
        $cookies = $dir . $credentials["cookieName"] . '_cookies.txt';
        unlink($cookies);

        $valuesLogin = array(
            new \Oara\Curl\Parameter ('user[email]', $user),
            new \Oara\Curl\Parameter ('user[password]', $password),
            new \Oara\Curl\Parameter ('user[remember_me]', '0'),
            new \Oara\Curl\Parameter ('commit', 'Sign in')
        );

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
        $rch = curl_init();
        $options = $this->_options;
        curl_setopt($rch, CURLOPT_URL, "http://affiliates.shuttlefare.com/users/sign_in");
        curl_setopt_array($rch, $options);
        $html = curl_exec($rch);
        curl_close($rch);

        $dom = new Zend_Dom_Query($html);
        $hidden = $dom->query('input[type="hidden"]');

        foreach ($hidden as $values) {
            $valuesLogin[] = new \Oara\Curl\Parameter($values->getAttribute("name"), $values->getAttribute("value"));
        }
        $rch = curl_init();
        $options = $this->_options;
        curl_setopt($rch, CURLOPT_URL, "http://affiliates.shuttlefare.com/users/sign_in");
        $options [CURLOPT_POST] = true;
        $arg = array();
        foreach ($valuesLogin as $parameter) {
            $arg [] = urlencode($parameter->getKey()) . '=' . urlencode($parameter->getValue());
        }
        $options [CURLOPT_POSTFIELDS] = implode('&', $arg);
        curl_setopt_array($rch, $options);
        $html = curl_exec($rch);

        curl_close($rch);

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
     * @return bool
     */
    public function checkConnection()
    {
        $connection = false;

        $rch = curl_init();
        $options = $this->_options;
        curl_setopt($rch, CURLOPT_URL, 'http://affiliates.shuttlefare.com/partners');
        curl_setopt_array($rch, $options);
        $html = curl_exec($rch);
        curl_close($rch);

        if (preg_match("/logout/", $html, $matches)) {
            $connection = true;
        }

        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = Array();

        $obj = Array();
        $obj['cid'] = 1;
        $obj['name'] = 'Shuttlefare';
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

        $valuesFromExport = array(
            new \Oara\Curl\Parameter('payment[from]', $dStartDate->format!("MM/dd/yyyy")),
            new \Oara\Curl\Parameter('payment[to]', $dEndDate->format!("MM/dd/yyyy")),
        );

        $rch = curl_init();
        $options = $this->_options;
        $arg = array();
        foreach ($valuesFromExport as $parameter) {
            $arg [] = $parameter->getKey() . '=' . urlencode($parameter->getValue());
        }
        $url = 'http://affiliates.shuttlefare.com/partners/payments/report.csv?' . implode('&', $arg);
        curl_setopt($rch, CURLOPT_URL, $url);
        curl_setopt_array($rch, $options);

        $html = curl_exec($rch);
        curl_close($rch);

        if (!preg_match("/No transaction in given date range/", $html) && $html) {
            $exportData = explode("\n", $html);
            $num = count($exportData);
            for ($i = 0; $i < $num - 1; $i++) {
                $transactionExportArray = explode(",", $exportData [$i]);
                $transaction = Array();
                $transaction ['merchantId'] = 1;
                $transaction ['unique_id'] = $transactionExportArray [0];
                $transactionDate = new \DateTime ($transactionExportArray [7], 'MM/dd/yyyy');
                $transaction ['date'] = $transactionDate->format!("yyyy-MM-dd HH:mm:ss");
                $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                $transaction ['amount'] = \Oara\Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $transactionExportArray [2]));
                $transaction ['commission'] = \Oara\Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $transactionExportArray [3]));

                $totalTransactions [] = $transaction;

            }
        }

        return $totalTransactions;
    }

    /**
     * @return array
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        return $paymentHistory;
    }

    /**
     * @param string $paymentId
     * @param $merchantList
     * @param $startDate
     * @return array
     */
    public function paymentTransactions($paymentId, $merchantList, $startDate)
    {
        $transactionList = array();

        return $transactionList;
    }
}
