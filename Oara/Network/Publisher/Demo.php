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

    private $_affiliateNetwork = null;

    private $_merchantList = array("Acme Corp", "Allied Biscuit", "Ankh-Sto Associates", "Extensive Enterprise", "Corp", "Globo-Chem",
        "Mr. Sparkle", "Globex Corporation", "LexCorp", "LuthorCorp", "North Central Electronics",
        "Omni Consumer Products", "Praxis Corporation", "Sombra Corporation", "Sto Plains Holdings",
        "Sto Plains Holdings", "Yuhu Limited");

    //private $_linkList = array("homepage_content", "shopping_cart_sidenav", "footer", "linkspage", "homepage_header", "para_one_content_home");
    private $_linkList = array("unknown");
    //private $_websiteList = array("Money Guide Site", "Football Guide Site", "Hotel Guide Site", "Browser Guide Site", "Paper Cup Site", "Lightbulb Shop Site");
    private $_websiteList = array("unknown");
    //private $_pageList = array("/home.html", "/sales.html", "/contact.html", "/book.html", "/index.html","/info.html");
    private $_pageList = array("unknown");

    /**
     * Constructor and Login
     * @param $cartrawler
     * @return Demo_Export
     */
    public function __construct($credentials)
    {

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
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = Array();
        $merchantsNumber = count($this->_merchantList);

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
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = Array();
        $transactionNumber = rand(1, 200);
        $twoMonthsAgoDate = new \DateTime();
        $twoMonthsAgoDate->subMonth(2);
        $dateArray = \Oara\Utilities::daysOfDifference($dStartDate, $dEndDate);
        for ($i = 0; $i < $transactionNumber; $i++) {
            $dateIndex = rand(0, count($dateArray) - 1);
            $merchantIndex = rand(0, count($merchantList) - 1);
            $transaction = array();
            $transaction['unique_id'] = md5(mt_rand() . $dateArray[$dateIndex]->toString("yyyy-MM-dd HH:mm:ss"));
            $transaction['custom_id'] = "my_custom_id";
            $transaction['merchantId'] = $merchantList[$merchantIndex];
            $transaction['date'] = $dateArray[$dateIndex]->toString("yyyy-MM-dd HH:mm:ss");
            $transactionAmount = rand(1, 1000);
            $transaction['amount'] = $transactionAmount;
            $transaction['commission'] = $transactionAmount / 10;
            //$transaction['link'] = $this->_linkList[rand(0, count($this->_linkList)-1)];
            //$transaction['website'] = $this->_websiteList[rand(0, count($this->_websiteList)-1)];
            //$transaction['page'] = $this->_pageList[rand(0, count($this->_pageList)-1)];
            $transactionStatusChances = rand(1, 100);
            if ($dateArray[$dateIndex]->compare($twoMonthsAgoDate) >= 0) {
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
     * (non-PHPdoc)
     * @see Oara/Network/Base#getPaymentHistory()
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();
        $startDate = new \DateTime('01-01-2011', 'dd-MM-yyyy');
        $endDate = new \DateTime();
        $dateArray = \Oara\Utilities::monthsOfDifference($startDate, $endDate);
        for ($i = 0; $i < count($dateArray); $i++) {
            $dateMonth = $dateArray[$i];
            $obj = array();
            $obj['date'] = $dateMonth->toString("yyyy-MM-dd HH:mm:ss");
            $value = rand(1, 1300);
            $obj['value'] = $value;
            $obj['method'] = 'BACS';
            $obj['pid'] = $dateMonth->toString('yyyyMMdd');
            $paymentHistory[] = $obj;
        }
        return $paymentHistory;
    }
}
