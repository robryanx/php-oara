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
 * @category   SkyParkSecure
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class SkyParkSecure extends \Oara\Network
{

    private $_credentials = null;
    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;

    private $_apiKey = null;
    private $_agent = null;

    /**
     * Constructor and Login
     * @param $credentials
     * @return SkyScanner
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        self::logIn();

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

    private function logIn()
    {

        $valuesLogin = array(
            new \Oara\Curl\Parameter('username', $this->_credentials['user']),
            new \Oara\Curl\Parameter('password', $this->_credentials['password']),
            new \Oara\Curl\Parameter('remember_me', "0"),
            new \Oara\Curl\Parameter('submit', "")
        );

        $loginUrl = 'http://agents.skyparksecure.com/auth/login';
        $this->_client = new \Oara\Curl\Access($loginUrl, $valuesLogin, $this->_credentials);

    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        //If not login properly the construct launch an exception
        $connection = true;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://agents.skyparksecure.com/bookings', array());
        $exportReport = $this->_client->get($urls);
        if (!preg_match("/Logout/", $exportReport[0], $match)) {
            $connection = false;
        }
        //Getting APIKEY
        if ($connection) {
            if (!preg_match("/self.api_key\s*=\s*'(.*)?';/", $exportReport[0], $match)) {
                $connection = false;
            } else {
                $this->_apiKey = $match[1];
            }
        }

        if ($connection) {
            if (!preg_match("/self.agent\s*=\s*'(.*)?';self.date1/", $exportReport[0], $match)) {
                if (!preg_match("/self.agent\s*=\s*'(.*)?';/", $exportReport[0], $match)) {
                    $connection = false;
                } else {
                    $this->_agent = $match[1];
                }
            } else {
                $this->_agent = $match[1];
            }
        }

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
        $obj['name'] = "SkyParkSecure Car Park";
        $obj['url'] = "http://agents.skyparksecure.com";
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

        $today = new \DateTime();
        $today->setHour(0);
        $today->setMinute(0);

        $urls = array();
        $exportParams = array(
            new \Oara\Curl\Parameter('data[query][agent]', $this->_agent),
            new \Oara\Curl\Parameter('data[query][date1]', $dStartDate->toString("yyyy-MM-dd")),
            new \Oara\Curl\Parameter('data[query][date2]', $dEndDate->toString("yyyy-MM-dd")),
            new \Oara\Curl\Parameter('data[query][api_key]', $this->_apiKey)
        );
        $urls[] = new \Oara\Curl\Request('http://www.skyparksecure.com/api/v4/jsonp/getSales?', $exportParams);
        $exportReport = $this->_client->get($urls);

        $report = substr($exportReport[0], 1, strlen($exportReport[0]) - 3);
        $exportData = json_decode($report);
        foreach ($exportData->result as $booking) {

            $transaction = Array();
            $transaction['merchantId'] = 1;
            $transaction['unique_id'] = $booking->booking_ref;
            $transaction['metadata'] = $booking->product_name;
            $transaction['custom_id'] = $booking->custom_id;
            $transactionDate = new \DateTime($booking->booking_date, 'yyyy.MMM.dd HH:mm:00', 'en');
            $pickupDate = new \DateTime($booking->dateA, 'yyyy.MMM.dd HH:mm:00', 'en');
            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
            $transaction['metadata'] = $booking->product_id;
            if ($booking->booking_mode == "Booked" || $booking->booking_mode == "Amended") {
                $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                if ($today > $pickupDate) {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                }
            } else if ($booking->booking_mode == "Cancelled") {
                $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
            } else {
                throw new Exception("New status found");
            }

            $transaction['amount'] = \Oara\Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $booking->sale_price)) / 1.2;
            $transaction['commission'] = \Oara\Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $booking->commission_affiliate)) / 1.2;

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