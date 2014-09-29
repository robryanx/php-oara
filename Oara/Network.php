<?php
/**
   The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set 
   of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
   
    Copyright (C) 2014  Carlos Morillo Merino
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
	Contact
	------------
	Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
**/	
/**
 * Base Class
 * It contains the Network common structure
 * All the Network classes extend this class.
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network
 * @copyright  Fubra Limited
 */
class Oara_Network {
	/**
	 *
	 * It checks if we are succesfully connected to the network
	 */
	public function checkConnection() {
		return false;
	}
	/**
	 *
	 * Get the merchants joined for the network
	 * it could be that we don't work with a merchant any more, but we want to retrieve its data
	 */
	public function getMerchantList() {
		$result = array();
		return $result;
	}
	/**
	 *
	 * Get the transactions for the network and the merchants selected for the date given
	 * @param array $merchantList - array with the merchants we want to retrieve the data from
	 * @param Zend_Date $dStartDate - start date (included)
	 * @param Zend_Date $dEndDate - end date (included)
	 */
	public function getTransactionList($merchantList, Zend_Date $dStartDate, Zend_Date $dEndDate, $merchantMap = null) {
		$result = array();
		return $result;
	}
	/**
	 *
	 * Get the Payments already done for this network
	 */
	public function getPaymentHistory() {
		$result = array();
		return $result;
	}
	/**
	 *
	 * Get the Transactions for payment
	 * @param string $paymentId
	 */
	public function paymentTransactions($paymentId, $merchantList, $startDate) {
		$result = array();
		return $result;
	}
}
