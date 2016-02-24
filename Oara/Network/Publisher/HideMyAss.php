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
 * @author     Alejandro Muñoz Odero
 * @category   HideMyAss
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class HideMyAss extends \Oara\Network
{
    private $_credentials = null;
    private $_client = null;

    /**
     * @param $credentials
     * @throws \Exception
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($this->_credentials);

        $valuesLogin = array(
            new \Oara\Curl\Parameter('_method', 'POST'),
            new \Oara\Curl\Parameter('data[User][username]', $this->_credentials['user']),
            new \Oara\Curl\Parameter('data[User][password]', $this->_credentials['password']),
        );

        $loginUrl = 'https://affiliate.hidemyass.com/users/login';
        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $exportReport = $this->_client->post($urls);


        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $hidden = $xpath->query('//input[@type="hidden"]');
        foreach ($hidden as $values) {
            $valuesLogin[] = new \Oara\Curl\Parameter($values->getAttribute("name"), $values->getAttribute("value"));
        }

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
        //If not login properly the construct launch an exception
        $connection = true;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://affiliate.hidemyass.com/dashboard', array());
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//*[contains(concat(" ", normalize-space(@id), " "), " loginform ")]');
        if ($results->length > 0) {
            $connection = false;
        }
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();

        $obj = array();
        $obj['cid'] = "1";
        $obj['name'] = "HideMyAss";
        $obj['url'] = "https://affiliate.hidemyass.com";
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
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://affiliate.hidemyass.com/reports', array());
        $exportReport = $this->_client->get($urls);

        $doc = new \DOMDocument();
        @$doc->loadHTML($exportReport[0]);
        $xpath = new \DOMXPath($doc);
        $hidden = $xpath->query('//input[@type="hidden"]');
        foreach ($hidden as $values) {
            $valuesFromExport[] = new \Oara\Curl\Parameter($values->getAttribute("name"), $values->getAttribute("value"));
        }

        $valuesFromExport[] = new \Oara\Curl\Parameter('_method', 'POST');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][dateselect]', '4');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][datetype]', '2');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangefrom][day]', $dStartDate->format("d"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangefrom][month]', $dStartDate->format("m"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangefrom][year]', $dStartDate->format("Y"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangefrom][day]', $dStartDate->format("d"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangefrom][month]', $dStartDate->format("m"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangefrom][year]', $dStartDate->format("Y"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangeto][day]', $dEndDate->format("d"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangeto][month]', $dEndDate->format("m"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangeto][year]', $dEndDate->format("Y"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangeto][day]', $dEndDate->format("d"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangeto][month]', $dEndDate->format("m"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][daterangeto][year]', $dEndDate->format("Y"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][themetype]', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][Theme][Theme]', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][Query][query]', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][country]', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][order][new]', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][order][rec]', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][order][refund]', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][order][refund]', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][order][fraud]', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][order][fraud]', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][month1]', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][month1]', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][month6]', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][month6]', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][month12]', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][month12]', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][referaldate]', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][visits]', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][collapsed]', '0');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][collapsed]', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][output]', 'raw_csv');
        $valuesFromExport[] = new \Oara\Curl\Parameter('data[Conditions][chart]', 'count');

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://affiliate.hidemyass.com/reports/index_date?', $valuesFromExport);
        $exportReport = $this->_client->get($urls);
        $exportData = \str_getcsv($exportReport[0], "\n");

        $num = \count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = \str_getcsv($exportData[$i], ";");
            $transaction = Array();
            $transaction['merchantId'] = 1;
            $transaction['date'] = $transactionExportArray[1];
            $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[8]);
            $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[10]);
            $totalTransactions[] = $transaction;
        }
        return $totalTransactions;
    }

}