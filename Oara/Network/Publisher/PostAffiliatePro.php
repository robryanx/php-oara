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
 * @category   PureVPN
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class PostAffiliatePro extends \Oara\Network
{
    public $_credentials = null;
    public $_session = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        include \realpath(\dirname(__FILE__)) . "/PostAffiliatePro/PapApi.class.php";
        $this->_credentials = $credentials;
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "Domain you connect to";
        $parameter["required"] = true;
        $parameter["name"] = "Domain";
        $credentials["domain"] = $parameter;

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
        // If not login properly the construct launch an exception
        $connection = true;
        $session = new \Gpf_Api_Session("http://" . $this->_credentials["domain"] . "/scripts/server.php");
        if (!@$session->login($this->_credentials ["user"], $this->_credentials ["password"], \Gpf_Api_Session::AFFILIATE)) {
            $connection = false;
        }
        $this->_session = $session;

        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();

        $obj = array();
        $obj ['cid'] = "1";
        $obj ['name'] = "Post Affiliate Pro ({$this->_credentials["domain"]})";
        $merchants [] = $obj;

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


        //----------------------------------------------
        // get recordset of list of transactions
        $request = new \Pap_Api_TransactionsGrid($this->_session);
        // set filter
        $request->addFilter('dateinserted', 'D>=', $dStartDate->format("Y-m-d"));
        $request->addFilter('dateinserted', 'D<=', $dEndDate->format("Y-m-d"));
        $request->setLimit(0, 100);
        $request->setSorting('t_orderid', false);
        $request->sendNow();
        $grid = $request->getGrid();
        $recordset = $grid->getRecordset();
        // iterate through the records
        foreach ($recordset as $rec) {
            $transaction = Array();
            $transaction ['merchantId'] = 1;
            $transaction ['unique_id'] = $rec->get('id');
            $transaction ['date'] = $rec->get('dateinserted');
            $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            $transaction ['amount'] = \Oara\Utilities::parseDouble($rec->get('totalcost'));
            $transaction ['commission'] = \Oara\Utilities::parseDouble($rec->get('commission'));
            $totalTransactions [] = $transaction;
        }
        //----------------------------------------------
        // in case there are more than 30 records total
        // we should load and display the rest of the records
        // in the cycle
        $totalRecords = $grid->getTotalCount();
        $maxRecords = $recordset->getSize();
        if ($maxRecords > 0) {
            $cycles = \ceil($totalRecords / $maxRecords);
            for ($i = 1; $i < $cycles; $i++) {
                // now get next 30 records
                $request->setLimit($i * $maxRecords, $maxRecords);
                $request->sendNow();
                $recordset = $request->getGrid()->getRecordset();
                // iterate through the records
                foreach ($recordset as $rec) {
                    $transaction = Array();
                    $transaction ['merchantId'] = 1;
                    $transaction ['unique_id'] = $rec->get('id');
                    $transaction ['date'] = $rec->get('dateinserted');
                    if ($rec->get('rstatus') == 'D') {
                        $transaction ['status'] = \Oara\Utilities::STATUS_DECLINED;
                    } else if ($rec->get('rstatus') == 'P') {
                        $transaction ['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else if ($rec->get('rstatus') == 'A') {
                        $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    }
                    $transaction ['amount'] = \Oara\Utilities::parseDouble($rec->get('totalcost'));
                    $transaction ['commission'] = \Oara\Utilities::parseDouble($rec->get('commission'));
                    $totalTransactions [] = $transaction;
                }
            }
        }
        return $totalTransactions;
    }
}
