<?php
namespace Oara\Network\Publisher;
    /**
     * The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
     * of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
     *
     * Copyright (C) 2015  Fubra Limited
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
 * @category   Groupon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Groupon extends \Oara\Network
{

    /**
     * @param $credentials
     * @throws Exception
     */
    public function login($credentials)
    {
        $this->_client = new \Oara\Curl\Access($credentials);
        $options = array(
            CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_COOKIEJAR => $this->_client->getCookiePath(),
            CURLOPT_COOKIEFILE => $this->_client->getCookiePath(),
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
        $this->_client->setOptions($options);
        $this->_credentials = $credentials;
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "API password";
        $parameter["required"] = true;
        $parameter["name"] = "API";
        $credentials["apipassword"] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = false;

        try {
            $date = new \DateTime();
            $url = "https://partner-int-api.groupon.com/reporting/v2/order.json?clientId={$this->_credentials['apipassword']}&group=order&date=[{$date->format('Y-m-d')}&date={$date->format('Y-m-d')}]";
            $valuesFormExport = array();
            $urls = array();
            $urls[] = new \Oara\Curl\Request($url, $valuesFormExport);
            $exportReport = $this->_client->get($urls);
            if ($exportReport[0] === false) {
                throw new \Exception ("API key not valid");
            }
            $connection = true;
        } catch (\Exception $e) {

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
        $obj['cid'] = "1";
        $obj['name'] = "Groupon Partner Network";
        $merchants[] = $obj;
        return $merchants;
    }

    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     * @throws \Exception
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();
        $auxDate = clone $dStartDate;
        $amountDays = $dStartDate->diff($dEndDate)->days;
        for ($j = 0; $j <= $amountDays; $j++) {

            // Getting the csv by curl can throw an exception if the csv size is 0 bytes. So, first of all, get the json. If total is 0, continue, else, get the csv.
            $valuesFormExport = array();
            $url = "https://partner-int-api.groupon.com/reporting/v2/order.json?clientId={$this->_credentials['apipassword']}&group=order&date={$auxDate->format("Y-m-d")}";
            $urls = array();
            $urls[] = new \Oara\Curl\Request($url, $valuesFormExport);
            $exportReport = $this->_client->get($urls);
            $jsonExportReport = json_decode($exportReport[0], true);

            if ($jsonExportReport['total'] != 0) {

                $valuesFormExport = array();
                $url = "https://partner-int-api.groupon.com/reporting/v2/order.csv?clientId={$this->_credentials['apipassword']}&group=order&date={$auxDate->format("Y-m-d")}";
                $urls = array();
                $urls[] = new \Oara\Curl\Request($url, $valuesFormExport);
                $exportReport = $this->_client->get($urls);
                $exportData = \str_getcsv($exportReport[0], "\n");
                $num = \count($exportData);
                for ($i = 1; $i < $num; $i++) {
                    $transactionExportArray = \str_getcsv($exportData[$i], ",");
                    $transaction = Array();
                    $transaction['merchantId'] = "1";
                    $transaction['date'] = $auxDate->format("Y-m-d H:i:s");
                    $transaction['unique_id'] = $transactionExportArray[0];
                    $transaction['currency'] = $transactionExportArray[4];

                    if ($transactionExportArray[1] != null) {
                        $transaction['custom_id'] = $transactionExportArray[1];
                    }

                    if ($transactionExportArray[5] == 'VALID' || $transactionExportArray[5] == 'REFUNDED') {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else if ($transactionExportArray[5] == 'INVALID') {
                        $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                    } else {
                        throw new \Exception("Status {$transactionExportArray[5]} unknown");
                    }

                    $transaction['amount'] = \Oara\Utilities::parseDouble((double)$transactionExportArray[8]);
                    $transaction['commission'] = \Oara\Utilities::parseDouble((double)$transactionExportArray[12]);
                    $totalTransactions[] = $transaction;
                }
            }
            $auxDate->add(new \DateInterval('P1D'));
        }

        return $totalTransactions;
    }

}
