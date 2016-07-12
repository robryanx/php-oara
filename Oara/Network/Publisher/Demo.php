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
 * Data Class
 *
 * @author     Carlos Morillo Merino
 * @category   Demo
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Demo extends \Oara\Network
{

    private $_merchantList = array("Acme Corp", "Allied Biscuit", "Ankh-Sto Associates", "Extensive Enterprise", "Corp", "Globo-Chem",
        "Mr. Sparkle", "Globex Corporation", "LexCorp", "LuthorCorp", "North Central Electronics",
        "Omni Consumer Products", "Praxis Corporation", "Sombra Corporation", "Sto Plains Holdings",
        "Sto Plains Holdings", "Yuhu Limited");

    /**
     * @param $credentials
     */
    public function login($credentials)
    {

    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        return $credentials;
    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = true;
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = Array();
        $merchantsNumber = \count($this->_merchantList);

        for ($i = 0; $i < $merchantsNumber; $i++) {
            //Getting the array Id
            $obj = Array();
            $obj['cid'] = $i;
            $obj['name'] = $this->_merchantList[$i];
            $merchants[] = $obj;
        }
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
        $totalTransactions = Array();
        $transactionNumber = \rand(1, 200);
        $twoMonthsAgoDate = new \DateTime();
        $interval = new \DateInterval('P2M');
        $twoMonthsAgoDate->sub($interval);
        for ($i = 0; $i < $transactionNumber; $i++) {

            $transactionDate = self::randomDate($dStartDate->format("Y-m-d H:i:s"), $dEndDate->format("Y-m-d H:i:s"));
            $merchantIndex = \rand(0, \count($merchantList) - 1);
            $transaction = array();
            $transaction['unique_id'] = \md5(\mt_rand() . $transactionDate);
            $transaction['custom_id'] = "my_custom_id";
            $transaction['merchantId'] = $merchantList[$merchantIndex]["cid"];
            $transaction['date'] = $transactionDate;
            $transactionAmount = \rand(1, 1000);
            $transaction['amount'] = $transactionAmount;
            $transaction['commission'] = $transactionAmount / 10;
            $transactionStatusChances = \rand(1, 100);
            if ($transaction['date'] >= $twoMonthsAgoDate->format("Y-m-d H:i:s")) {
                if ($transactionStatusChances < 60) {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else
                    if ($transactionStatusChances < 70) {
                        $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                    } else {
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    }
            } else {
                if ($transactionStatusChances < 80) {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else {
                    $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                }
            }
            $totalTransactions[] = $transaction;
        }
        return $totalTransactions;

    }

    /**
     * @return array
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();
        $startDate = new \DateTime('2015-01-01');
        $endDate = new \DateTime();
        $diff = $startDate->diff($endDate);
        $monthsDifference = (int) $diff->format('%m');
        for ($i = 0; $i <= $monthsDifference; $i++) {
            $obj = array();
            $obj['date'] = $startDate->format("Y-m-d H:i:s");
            $value = \rand(1, 1300);
            $obj['value'] = $value;
            $obj['method'] = 'BACS';
            $obj['pid'] = $startDate->format('Ymd');
            $paymentHistory[] = $obj;

            $interval = new \DateInterval('P1M');
            $startDate->add($interval);
        }
        return $paymentHistory;
    }

    private function randomDate($start_date, $end_date)
    {
        // Convert to timetamps
        $min = \strtotime($start_date);
        $max = \strtotime($end_date);

        // Generate random number using above bounds
        $val = \rand($min, $max);

        // Convert back to desired date format
        return \date('Y-m-d H:i:s', $val);
    }
}
