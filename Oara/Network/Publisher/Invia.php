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
 * @category Afiliant
 * @copyright Fubra Limited
 * @version Release: 01.00
 *
 */
class Invia extends \Oara\Network
{

    /**
     * Client
     *
     * @var unknown_type
     */
    private $_client = null;

    /**
     * Constructor and Login
     *
     * @param $buy
     * @return Buy_Api
     */
    public function __construct($credentials)
    {

        $user = $credentials ['user'];
        $password = $credentials ['password'];

        $loginUrl = 'http://partner2.invia.cz/';

        $dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;

        if (!\Oara\Utilities::mkdir_recursive($dir, 0777)) {
            throw new Exception ('Problem creating folder in Access');
        }
        $cookies = $dir . $credentials["cookieName"] . '_cookies.txt';
        unlink($cookies);

        $valuesLogin = array(
            new \Oara\Curl\Parameter ('ac-email', $user),
            new \Oara\Curl\Parameter ('ac-password', $password),
            new \Oara\Curl\Parameter ('redir_url', 'http://partner2.invia.cz/'),
            new \Oara\Curl\Parameter ('ac-submit', '1'),
            new \Oara\Curl\Parameter ('k2form_login', '1')
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
        curl_setopt($rch, CURLOPT_URL, "http://partner2.invia.cz/");
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
        curl_setopt($rch, CURLOPT_URL, "http://partner2.invia.cz/");
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
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;

        $rch = curl_init();
        $options = $this->_options;
        curl_setopt($rch, CURLOPT_URL, 'http://partner2.invia.cz/');
        curl_setopt_array($rch, $options);
        $html = curl_exec($rch);
        curl_close($rch);

        if (preg_match("/odhlaseni/", $html, $matches)) {
            $connection = true;
        }

        return $connection;
    }

    /**
     * (non-PHPdoc)
     *
     * @see library/Oara/Network/Interface#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = Array();

        $obj = Array();
        $obj['cid'] = 1;
        $obj['name'] = 'Invia';
        $merchants[] = $obj;

        return $merchants;
    }

    /**
     * (non-PHPdoc)
     *
     * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();

        /*
         $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array('Client-Key:'.$this->_apiKey,'X-Originating-Ip:'.$this->_apiIP	) );

        $result = curl_exec ( $ch );
        $info = curl_getinfo($ch);
        curl_close($ch);
        */

        /*
                $params = "";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AffilUI_Filter\"";
                $params .= "";
                $params .= "";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AffilUI_FilterStr\"";
                $params .= "";
                $params .= "";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AffilUI_FilterTag\"";
                $params .= "";
                $params .= "";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AdvancedFilter_State\"";
                $params .= "";
                $params .= "0";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AdvancedFilter_nl_stav_id\"";
                $params .= "";
                $params .= "0";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AdvancedFilter_nl_invia_id\"";
                $params .= "";
                $params .= "1"; //2 y 3 tambien hay que hacerlos
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AdvancedFilter_departure\"";
                $params .= "";
                $params .= "0";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AdvancedFilter_b_show_invoiced\"";
                $params .= "";
                $params .= "on";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AdvancedFilter_date_from\"";
                $params .= "";
                $params .= "01.01.2014";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AdvancedFilter_date_to\"";
                $params .= "";
                $params .= "31.10.2014";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AdvancedFilter_nl_rows\"";
                $params .= "";
                $params .= "";
                $params .= "-----------------------------30593287953754";
                $params .= "Content-Disposition: form-data; name=\"AdvancedFilter_sent\"";
                $params .= "";
                $params .= "1";
                $params .= "-----------------------------30593287953754--";

                $valuesFromExport = array(
                        new \Oara\Curl\Parameter('POSTDATA', $params)
                );
        */
        $valuesFromExport = array(
            new \Oara\Curl\Parameter('AffilUI_Filter', ''),
            new \Oara\Curl\Parameter('AffilUI_FilterStr', ''),
            new \Oara\Curl\Parameter('AffilUI_FilterTag', ''),
            new \Oara\Curl\Parameter('AdvancedFilter_State', '0'),
            new \Oara\Curl\Parameter('AdvancedFilter_nl_stav_id', '0'),
            new \Oara\Curl\Parameter('AdvancedFilter_nl_invia_id', '1'),
            new \Oara\Curl\Parameter('AdvancedFilter_departure', '0'),
            new \Oara\Curl\Parameter('AdvancedFilter_b_show_invoiced', 'on'),
            new \Oara\Curl\Parameter('AdvancedFilter_date_from', '01.01.2014'),
            new \Oara\Curl\Parameter('AdvancedFilter_date_to', '31.10.2014'),
            new \Oara\Curl\Parameter('AdvancedFilter_nl_rows', ''),
            new \Oara\Curl\Parameter('AdvancedFilter_sent', '1')
        );

        $rch = curl_init();
        $options = $this->_options;

        $arg = array();
        foreach ($valuesFromExport as $parameter) {
            $arg [] = urlencode($parameter->getKey()) . '=' . urlencode($parameter->getValue());
        }
//		curl_setopt ( $rch, CURLOPT_URL, 'http://partner2.invia.cz/ikomunity/index.php?k2MAIN[action]=AFFIL_OBJ?'.implode ( '&', $arg ) );
        curl_setopt($rch, CURLOPT_URL, 'http://partner2.invia.cz/ikomunity/index.php?k2MAIN[action]=AFFIL_OBJ?' . $params);
        curl_setopt_array($rch, $options);

        $html = curl_exec($rch);
        echo $html;
        curl_close($rch);

        $dom = new Zend_Dom_Query($html);

        $tableList = $dom->query('k2table_AffilUI');
        if (count($tableList) > 0) {

            $exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));

            $num = count($exportData);
            for ($i = 1; $i < $num - 1; $i++) {
                $transactionExportArray = explode(";,", $exportData [$i]);

                $transaction = Array();

                $transactionDate = new \DateTime ($transactionExportArray [2], 'dd.MM.yyyy');
                $transaction ['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
                $status = $transactionExportArray [4];
                if ($status == "Zaplaceno") {
                    $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else if ($status == "Neprod�no") {
                    $transaction ['status'] = \Oara\Utilities::STATUS_PENDING;
                } else if ($status == "Storno") {
                    $transaction ['status'] = \Oara\Utilities::STATUS_DECLINED;
                } else {
                    throw new Exception ("New status found {$status}");
                }


                $transaction ['amount'] = \Oara\Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $transactionExportArray [6]));
                $transaction ['commission'] = \Oara\Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $transactionExportArray [6]));


                $transaction ['merchantId'] = 1;
                $transaction ['unique_id'] = $transactionExportArray [0];

                $totalTransactions [] = $transaction;

            }
        }
        return $totalTransactions;
    }

    /**
     * (non-PHPdoc)
     *
     * @see Oara/Network/Base#getPaymentHistory()
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        return $paymentHistory;
    }

    /**
     *
     *
     *
     * It returns the transactions for a payment
     *
     * @param int $paymentId
     */
    public function paymentTransactions($paymentId, $merchantList, $startDate)
    {
        $transactionList = array();

        return $transactionList;
    }

    /**
     *
     *
     * Function that Convert from a table to Csv
     *
     * @param unknown_type $html
     */
    private function htmlToCsv($html)
    {
        $html = str_replace(array(
            "\t",
            "\r",
            "\n"
        ), "", $html);
        $csv = "";
        $dom = new Zend_Dom_Query ($html);
        $results = $dom->query('tr');
        $count = count($results); // get number of matches: 4
        foreach ($results as $result) {

            $domTd = new Zend_Dom_Query (self::DOMinnerHTML($result));
            $resultsTd = $domTd->query('td');
            $countTd = count($resultsTd);
            $i = 0;
            foreach ($resultsTd as $resultTd) {
                $value = $resultTd->nodeValue;
                if ($i != $countTd - 1) {
                    $csv .= trim($value) . ";,";
                } else {
                    $csv .= trim($value);
                }
                $i++;
            }
            $csv .= "\n";
        }
        $exportData = str_getcsv($csv, "\n");
        return $exportData;
    }

    /**
     *
     *
     * Function that returns the innet HTML code
     *
     * @param unknown_type $element
     */
    private function DOMinnerHTML($element)
    {
        $innerHTML = "";
        $children = $element->childNodes;
        foreach ($children as $child) {
            $tmp_dom = new DOMDocument ();
            $tmp_dom->appendChild($tmp_dom->importNode($child, true));
            $innerHTML .= trim($tmp_dom->saveHTML());
        }
        return $innerHTML;
    }
}
