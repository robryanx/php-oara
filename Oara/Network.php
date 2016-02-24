<?php
namespace Oara;
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
 * Base Class
 * It contains the Network common structure
 * All the Network classes extend this class.
 *
 * @author     Carlos Morillo Merino
 * @category   \Oara\Network
 * @copyright  Fubra Limited
 */
class Network
{
    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $result = array();
        return $result;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        return false;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $result = array();
        return $result;
    }

    /**
     * @param $merchantList
     * @param \DateTime $dStartDate
     * @param \DateTime $dEndDate
     * @return array
     */
    public function getTransactionList($merchantList, \DateTime $dStartDate, \DateTime $dEndDate)
    {
        $result = array();
        return $result;
    }

    /**
     * @return array
     */
    public function getPaymentHistory()
    {
        $result = array();
        return $result;
    }

    /**
     * @param $paymentId
     * @return array
     */
    public function paymentTransactions($paymentId)
    {
        $result = array();
        return $result;
    }
}
