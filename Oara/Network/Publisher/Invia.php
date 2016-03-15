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
     * @param $credentials
     * @throws Exception
     */
    public function login($credentials)
    {

        $user = $credentials ['user'];
        $password = $credentials ['password'];
        $this->_client = new \Oara\Curl\Access($credentials);

        $loginUrl = 'http://partner2.invia.cz/';
        $valuesLogin = array(
            new \Oara\Curl\Parameter ('ac-email', $user),
            new \Oara\Curl\Parameter ('ac-password', $password),
            new \Oara\Curl\Parameter ('redir_url', 'http://partner2.invia.cz/'),
            new \Oara\Curl\Parameter ('ac-submit', '1'),
            new \Oara\Curl\Parameter ('k2form_login', '1')
        );

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
        $connection = false;

        $urls = array();
        $urls[] = new \Oara\Curl\Request("http://partner2.invia.cz/", array());
        $exportReport = $this->_client->get($urls);

        if (\preg_match("/odhlaseni/", $exportReport[0], $matches)) {
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
        $obj['name'] = 'Invia';
        $merchants[] = $obj;

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
        $totalTransactions = array();


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

        $urls = array();
        $urls[] = new \Oara\Curl\Request("http://partner2.invia.cz/ikomunity/index.php?k2MAIN[action]=AFFIL_OBJ", $valuesFromExport);
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $tableList = $xpath->query('//*[contains(concat(" ", normalize-space(@id), " "), " k2table_AffilUI ")]');
        if ($tableList->length > 0) {

            $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($tableList->item(0)));

            $num = \count($exportData);
            for ($i = 1; $i < $num - 1; $i++) {
                $transactionExportArray = \explode(";", $exportData [$i]);
                $transaction = Array();
                $transactionDate = \DateTime::createFromFormat("d.m.Y", $transactionExportArray [2]);
                $transaction ['date'] = $transactionDate->format("Y-m-d H:i:s");
                $status = $transactionExportArray [4];
                if ($status == "Zaplaceno") {
                    $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else if ($status == "Neprod�no") {
                    $transaction ['status'] = \Oara\Utilities::STATUS_PENDING;
                } else if ($status == "Storno") {
                    $transaction ['status'] = \Oara\Utilities::STATUS_DECLINED;
                } else {
                    throw new \Exception ("New status found {$status}");
                }
                $transaction ['amount'] = \Oara\Utilities::parseDouble($transactionExportArray [6]);
                $transaction ['commission'] = \Oara\Utilities::parseDouble($transactionExportArray [6]);
                $transaction ['merchantId'] = 1;
                $transaction ['unique_id'] = $transactionExportArray [0];
                $totalTransactions [] = $transaction;
            }
        }
        return $totalTransactions;
    }
}
