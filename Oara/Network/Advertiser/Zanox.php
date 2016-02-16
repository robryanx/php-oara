<?php
namespace Oara\Network\Advertiser;
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
 * @category Tradedoubler
 * @copyright Fubra Limited
 * @version Release: 01.00
 *
 */
class Zanox extends \Oara\Network
{


    /**
     * Client
     *
     * @var unknown_type
     */
    private $_client = null;

    private $_idProgram = null;


    /**
     * Constructor
     */
    public function __construct($credentials)
    {

        $user = $credentials ['user'];
        $password = $credentials ['password'];

        $loginUrl = 'https://auth.zanox.com/login?';

        $valuesLogin = array(
            new \Oara\Curl\Parameter ('loginForm.userName', $user),
            new \Oara\Curl\Parameter ('loginForm.password', $password),
            new \Oara\Curl\Parameter ('loginForm.loginViaUserAndPassword', "true")
        );

        $this->_client = new \Oara\Curl\Access ($loginUrl, $valuesLogin, $credentials);

        $exportReport = $this->_client->getConstructResult();

        if (preg_match("/programs : [{\"id\":[0-9]+/", $exportReport, $matches)) {
            preg_match("/[0-9]+/", $matches[0], $idProgram);
            $this->_idProgram = $idProgram[0];
        }

    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;
        if (preg_match("/logout/", $this->_client->getConstructResult(), $matches)) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = Array();
        $obj = Array();
        $obj['cid'] = $this->_idProgram;
        $obj['name'] = 'Zanox';
        $merchants[] = $obj;

        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {


        $totalTransactions = array();

        /**/
        $dStartDate = new \DateTime('2014-08-05', 'yyyy-MM-dd');
        $dEndDate = new \DateTime('2014-08-05', 'yyyy-MM-dd');
        /**/
        $options = $this->_client->getOptions();
        $options[CURLOPT_ENCODING] = "gzip,deflate";
        $options[CURLOPT_HTTPHEADER] = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: es,en-us;q=0.7,en;q=0.3',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
        );
        $this->_client->setOptions($options);


        $urls = array();
        $urls [] = new \Oara\Curl\Request ("https://advertiser.zanox.com/advertisertransactionconfirmation/main/app?dest=sales&program=7641", array());
        $exportReport = $this->_client->get($urls);

        $timestampStartDate = strtotime($dStartDate->toString("dd-MM-yyyy"));  //'05-08-2014'
        $timestampEndDate = strtotime($dEndDate->toString("dd-MM-yyyy"));

        $timestampStartDate = $timestampStartDate - 3600;
        $timestampStartDate = $timestampStartDate . '000';
        $timestampEndDate = $timestampEndDate - 3600;
        $timestampEndDate = $timestampEndDate . '000';

        $valuesFromExport = array();
        $valuesFromExport [] = new \Oara\Curl\Parameter ('transaction_type', 'SALE');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('approved', 'true');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('rejected', 'true');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('open', 'true');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('invalid', 'true');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('confirmed', 'true');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('date_from', $timestampStartDate); //1407193200000 ==> "1407196800 - 3600" ++ "000"
        $valuesFromExport [] = new \Oara\Curl\Parameter ('date_to', $timestampEndDate);
        $valuesFromExport [] = new \Oara\Curl\Parameter ('sale_date', 'true');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('pattern', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('pattern_field', 'ORDER_ID');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('sort_column', 'SALE_DATE');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('sort_direction', 'DESC');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('tracking_category', '');
        $valuesFromExport [] = new \Oara\Curl\Parameter ('program_id', $this->_idProgram);
        $valuesFromExport [] = new \Oara\Curl\Parameter ('locale_name', 'en_US');

        $urls = array();
        $urls [] = new \Oara\Curl\Request ('https://advertiser.zanox.com/advertisertransactionconfirmation/main/export?', $valuesFromExport);
        $result = $this->_client->get($urls);

        echo $result[0];
        $exportData = str_getcsv($result[0], ";");
        /**/
        $exportData = null;
        $folder = realpath(dirname(COOKIES_BASE_DIR)) . '/pdf/';
        $my_file = $folder . 'tracking_pps_report.csv';

        $csvfile = fopen($my_file, 'rb');
        while (!feof($csvfile)) {
            $exportData[] = fgetcsv($csvfile, 1000, ";");

        }
        /**/
        for ($j = 1; $j < count($exportData) - 5; $j++) {
            if ($exportData[$j][2] == null) {
                throw new Exception ('Order ID is null');
            } else {
                $transaction = Array();
                $transaction['custom_id'] = $exportData[$j][4];
                $transaction['unique_id'] = $exportData[$j][2];
                $transaction['merchantId'] = $this->_idProgram;
                $transactionDate = new \DateTime($exportData[$j][12], 'dd/MM/yy HH:mm CEST');
                $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
                $transaction['amount'] = $exportData[$j][7];
                $transaction['commission'] = $exportData[$j][8];

                if ($exportData[$j][0] == 'Confirmed') {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else
                    if ($exportData[$j][0] == 'Open') {
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else
                        if ($exportData[$j][0] == 'Rejected') {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        }

                $totalTransactions[] = $transaction;
            }

        }

        return $totalTransactions;

    }
}
