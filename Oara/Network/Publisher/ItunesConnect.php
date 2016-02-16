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
 * @category   ItunesConnect
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class ItunesConnect extends \Oara\Network
{
    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;
    private $_constructResult = null;
    private $_user = null;
    private $_password = null;

    /**
     * Constructor and Login
     * @param $credentials
     * @return itunesConnect
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];

        $url = "https://itunesconnect.apple.com/WebObjects/iTunesConnect.woa/wo/2.1.9.3.5.2.1.1.3.1.1";

        $valuesLogin = array(
            new \Oara\Curl\Parameter('theAccountName', $user),
            new \Oara\Curl\Parameter('theAccountPW', $password),
            new \Oara\Curl\Parameter('1.Continue.x', "56"),
            new \Oara\Curl\Parameter('1.Continue.y', "10"),
            new \Oara\Curl\Parameter('theAuxValue', ""),
        );

        $this->_user = $user;
        $this->_password = $password;
        $this->_apiPassword = $credentials['apiPassword'];

        $this->_httpLogin = $credentials['httpLogin'];
        //$this->_client = new \Oara\Curl\Access($url, $valuesLogin, $credentials);
        //$this->_constructResult =  $this->_client->getConstructResult();
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

        //if (preg_match("/Sign Out/", $this->_constructResult)) {
        $connection = true;
        //}
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
        $obj['cid'] = "1";
        $obj['name'] = "Itunes Connect";
        $obj['url'] = "https://itunesconnect.apple.com";
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


        $dirDestination = realpath(dirname(COOKIES_BASE_DIR)) . '/pdf';

        $now = new \DateTime();
        if ($now->format!("yyyy-MM") != $dStartDate->format!("yyyy-MM")) {


            $fileName = "S_M_{$this->_apiPassword}_" . $dStartDate->format!("yyyyMM") . ".txt.gz";
            // Raising this value may increase performance
            $buffer_size = 4096; // read 4kb at a time
            $local_file = $dirDestination . "/" . $fileName;
            $url = "http://affjet.dc.fubra.net/tools/ItunesConnect/ic.php?user=" . urlencode($this->_user) . "&password=" . urlencode($this->_password) . "&apiPassword=" . urlencode($this->_apiPassword) . "&type=M&date=" . $dStartDate->format!("yyyyMM");

            $context = \stream_context_create(array(
                'http' => array(
                    'header' => "Authorization: Basic " . base64_encode("{$this->_httpLogin}")
                )
            ));

            \file_put_contents($local_file, \file_get_contents($url, false, $context));

            $out_file_name = \str_replace('.gz', '', $local_file);

            // Open our files (in binary mode)
            $file = \gzopen($local_file, 'rb');
            if ($file != null) {


                $out_file = \fopen($out_file_name, 'wb');

                // Keep repeating until the end of the input file
                while (!\gzeof($file)) {
                    // Read buffer-size bytes
                    // Both fwrite and gzread and binary-safe
                    \fwrite($out_file, \gzread($file, $buffer_size));
                }

                // Files are done, close files
                \fclose($out_file);
                \gzclose($file);


                unlink($local_file);

                $salesReport = file_get_contents($out_file_name);
                $salesReport = explode("\n", $salesReport);
                for ($i = 1; $i < count($salesReport) - 1; $i++) {

                    $row = str_getcsv($salesReport[$i], "\t");

                    if ($row[15] != 0) {


                        $sub = false;
                        if ($row[7] < 0) {
                            $sub = true;
                            $row[7] = abs($row[7]);
                        }
                        for ($j = 0; $j < $row[7]; $j++) {

                            $obj = array();
                            $obj['merchantId'] = "1";
                            $obj['date'] = $dEndDate->format!("yyyy-MM-dd") . " 00:00:00";
                            $obj['custom_id'] = $row[4];
                            $comission = 0.3;
                            if ($row[2] == "FUBRA1PETROLPRICES1" || $row[2] == "com.fubra.petrolpricespro.subscriptionYear") {
                                $value = 2.99;
                                $obj['amount'] = \Oara\Utilities::parseDouble($value);
                                $obj['commission'] = \Oara\Utilities::parseDouble($value - ($value * $comission));
                            } else if ($row[2] == "FUBRA1WORLDAIRPORTCODES1") {

                                if ($obj['date'] < "2013-04-23 00:00:00") {
                                    $value = 0.69;
                                    $obj['amount'] = \Oara\Utilities::parseDouble($value);
                                    $obj['commission'] = \Oara\Utilities::parseDouble($value - ($value * $comission));
                                } else {
                                    $value = 1.49;
                                    $obj['amount'] = \Oara\Utilities::parseDouble($value);
                                    $obj['commission'] = \Oara\Utilities::parseDouble($value - ($value * $comission));
                                }
                            } else {
                                throw new Exception("APP not found {$row[2]}");
                            }

                            if ($sub) {
                                $obj['amount'] = -$obj['amount'];
                                $obj['commission'] = -$obj['commission'];
                            }

                            $obj['status'] = \Oara\Utilities::STATUS_CONFIRMED;

                            $totalTransactions[] = $obj;
                        }
                    }
                }
                unlink($out_file_name);
            }

        } else {

            $dateArray = \Oara\Utilities::daysOfDifference($dStartDate, $dEndDate);
            $dateArraySize = sizeof($dateArray);
            for ($z = 0; $z < $dateArraySize; $z++) {
                $transactionDate = $dateArray[$z];


                $fileName = "S_D_{$this->_apiPassword}_" . $transactionDate->format!("yyyyMMdd") . ".txt.gz";


                // Raising this value may increase performance
                $buffer_size = 4096; // read 4kb at a time
                $local_file = $dirDestination . "/" . $fileName;
                $url = "http://affjet.dc.fubra.net/tools/ItunesConnect/ic.php?user=" . urlencode($this->_user) . "&password=" . urlencode($this->_password) . "&apiPassword=" . urlencode($this->_apiPassword) . "&type=D&date=" . $transactionDate->format!("yyyyMMdd");

                $context = \stream_context_create(array(
                    'http' => array(
                        'header' => "Authorization: Basic " . base64_encode("{$this->_httpLogin}")
                    )
                ));

                \file_put_contents($local_file, \file_get_contents($url, false, $context));

                $out_file_name = \str_replace('.gz', '', $local_file);

                // Open our files (in binary mode)
                $file = \gzopen($local_file, 'rb');
                if ($file != null) {


                    $out_file = \fopen($out_file_name, 'wb');

                    // Keep repeating until the end of the input file
                    while (!\gzeof($file)) {
                        // Read buffer-size bytes
                        // Both fwrite and gzread and binary-safe
                        \fwrite($out_file, \gzread($file, $buffer_size));
                    }

                    // Files are done, close files
                    \fclose($out_file);
                    \gzclose($file);


                    unlink($local_file);

                    $salesReport = file_get_contents($out_file_name);
                    $salesReport = explode("\n", $salesReport);
                    for ($i = 1; $i < count($salesReport) - 1; $i++) {

                        $row = str_getcsv($salesReport[$i], "\t");

                        if ($row[15] != 0) {

                            $sub = false;
                            if ($row[7] < 0) {
                                $sub = true;
                                $row[7] = abs($row[7]);
                            }
                            for ($j = 0; $j < $row[7]; $j++) {

                                $obj = array();
                                $obj['merchantId'] = "1";
                                $obj['date'] = $transactionDate->format!("yyyy-MM-dd") . " 00:00:00";
                                $obj['custom_id'] = $row[4];
                                if ($row[2] == "FUBRA1PETROLPRICES1" || $row[2] == "com.fubra.petrolpricespro.subscriptionYear") {
                                    $value = 2.99;
                                    $comission = 0.3;
                                    $obj['amount'] = \Oara\Utilities::parseDouble($value);
                                    $obj['commission'] = \Oara\Utilities::parseDouble($value - ($value * $comission));
                                } else if ($row[2] == "FUBRA1WORLDAIRPORTCODES1") {

                                    $comission = 0.3;
                                    if ($obj['date'] < "2013-04-23 00:00:00") {
                                        $value = 0.69;
                                        $obj['amount'] = \Oara\Utilities::parseDouble($value);
                                        $obj['commission'] = \Oara\Utilities::parseDouble($value - ($value * $comission));
                                    } else {
                                        $value = 1.49;
                                        $obj['amount'] = \Oara\Utilities::parseDouble($value);
                                        $obj['commission'] = \Oara\Utilities::parseDouble($value - ($value * $comission));
                                    }
                                } else {
                                    throw new Exception("APP not found {$row[2]}");
                                }

                                if ($sub) {
                                    $obj['amount'] = -$obj['amount'];
                                    $obj['commission'] = -$obj['commission'];
                                }

                                $obj['status'] = \Oara\Utilities::STATUS_CONFIRMED;

                                $totalTransactions[] = $obj;
                            }
                        }
                    }
                    unlink($out_file_name);

                }


            }

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


    /**
     *
     * Function that Convert from a table to Csv
     * @param unknown_type $html
     */
    private function htmlToCsv($html)
    {
        $html = str_replace(array("\t", "\r", "\n"), "", $html);
        $csv = "";
        $dom = new Zend_Dom_Query($html);
        $results = $dom->query('tr');
        $count = count($results); // get number of matches: 4
        foreach ($results as $result) {
            $tdList = $result->childNodes;
            $tdNumber = $tdList->length;
            if ($tdNumber > 0) {
                for ($i = 0; $i < $tdNumber; $i++) {
                    $value = $tdList->item($i)->nodeValue;
                    if ($i != $tdNumber - 1) {
                        $csv .= trim($value) . ";";
                    } else {
                        $csv .= trim($value);
                    }
                }
                $csv .= "\n";
            }
        }
        $exportData = str_getcsv($csv, "\n");
        return $exportData;
    }

    /**
     *
     * Function that returns the innet HTML code
     * @param unknown_type $element
     */
    private function DOMinnerHTML($element)
    {
        $innerHTML = "";
        $children = $element->childNodes;
        foreach ($children as $child) {
            $tmp_dom = new DOMDocument();
            $tmp_dom->appendChild($tmp_dom->importNode($child, true));
            $innerHTML .= trim($tmp_dom->saveHTML());
        }
        return $innerHTML;
    }


}