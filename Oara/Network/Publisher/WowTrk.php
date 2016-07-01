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
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Wow
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class WowTrk extends \Oara\Network
{

    private $_exportClient = null;
    private $_apiPassword = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];
        $this->_apiPassword = $credentials['apipassword'];
        $this->_client = new \Oara\Curl\Access($credentials);


        //login through wow website
        $loginUrl = 'http://p.wowtrk.com/';
        $valuesLogin = array(new \Oara\Curl\Parameter('data[User][email]', $user),
            new \Oara\Curl\Parameter('data[User][password]', $password),
            new \Oara\Curl\Parameter('_method', 'POST')
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

        $parameter = array();
        $parameter["description"] = "API Password ";
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
        $merchants = array();

        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('api_key', $this->_apiPassword);
        $valuesFromExport[] = new \Oara\Curl\Parameter('limit', 0);

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://p.wowtrk.com/offers/offers.xml?', $valuesFromExport);
        $exportReport = $this->_exportClient->get($urls);

        $exportData = self::loadXml($exportReport[0]);

        foreach ($exportData->offer as $merchant) {
            $obj = array();
            $obj['cid'] = (int)$merchant->id;
            $obj['name'] = (string)$merchant->name;
            $obj['url'] = (string)$merchant->preview_url;
            $merchants[] = $obj;
        }

        return $merchants;
    }

    /**
     * @param null $exportReport
     * @return \SimpleXMLElement
     */
    private function loadXml($exportReport = null)
    {
        $xml = \simplexml_load_string($exportReport, null, LIBXML_NOERROR | LIBXML_NOWARNING);
        return $xml;
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

        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);
        $merchantMap = \Oara\Utilities::getMerchantNameMapFromMerchantList($merchantList);

        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('api_key', $this->_apiPassword);
        $valuesFromExport[] = new \Oara\Curl\Parameter('start_date', $dStartDate->format("Y-m-d"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('end_date', $dEndDate->format("Y-m-d"));
        $valuesFromExport[] = new \Oara\Curl\Parameter('filter[Stat.offer_id]', \implode(",", $merchantIdList));

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://p.wowtrk.com/stats/lead_report.xml?', $valuesFromExport);
        $exportReport = $this->_exportClient->get($urls);

        $exportData = self::loadXml($exportReport[0]);

        foreach ($exportData->stats->stat as $transaction) {
            if (isset($merchantMap[(string)$transaction->offer])) {
                $obj = array();
                $obj['merchantId'] = $merchantMap[(string)$transaction->offer];
                $obj['date'] = (string)$transaction->date_time;
                $obj['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                $obj['customId'] = (string)$transaction->sub_id;
                $obj['amount'] = \Oara\Utilities::parseDouble((string)$transaction->payout);
                $obj['commission'] = \Oara\Utilities::parseDouble((string)$transaction->payout);
                if ($obj['amount'] != 0 || $obj['commission'] != 0) {
                    $totalTransactions[] = $obj;
                }
            }

        }
        return $totalTransactions;
    }
}
