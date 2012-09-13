<?php
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
	 * Get the overview (a summary daily of our statistics) for the network and the merchants selected for the date given
	 * @param array $transactionList - Transaction List
	 * @param array $merchantList - array with the merchants we want to retrieve the data from
	 * @param Zend_Date $dStartDate - start date (included)
	 * @param Zend_Date $dEndDate - end date (included)
	 */
	public function getOverviewList($transactionList, $merchantList, Zend_Date $dStartDate, Zend_Date $dEndDate, $merchantMap = null) {
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
