<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2014  Fubra Limited
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
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_SkyParkSecure
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_SkyParkSecure extends Oara_Network {

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
	 * @return Oara_Network_Publisher_SkyScanner
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;
		self::logIn();

	}

	private function logIn() {
		
		$valuesLogin = array(
			new Oara_Curl_Parameter('username', $this->_credentials['user']),
			new Oara_Curl_Parameter('password', $this->_credentials['password']),
			new Oara_Curl_Parameter('remember_me', "0"),
			new Oara_Curl_Parameter('submit', "")
		);

		$loginUrl = 'http://agents.skyparksecure.com/auth/login';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $this->_credentials);
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://agents.skyparksecure.com/bookings', array());
		$exportReport = $this->_client->get($urls);
		if (!preg_match("/Logout/", $exportReport[0], $match)){
			$connection = false;
		}
		//Getting APIKEY
		if ($connection){
			if (!preg_match("/self.api_key\s*=\s*'(.*)?';/", $exportReport[0], $match)){
				$connection = false;
			} else {
				$this->_apiKey = $match[1];
			}
		}
		
		if ($connection){
			if (!preg_match("/self.agent\s*=\s*'(.*)?';self.date1/", $exportReport[0], $match)){
				$connection = false;
			} else {
				$this->_agent = $match[1];
			}
		}
		
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
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
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();
		
		$today = new Zend_Date();
		$today->setHour(0);
		$today->setMinute(0);
		
		$urls = array();
		$exportParams = array(
				new Oara_Curl_Parameter('data[query][agent]', $this->_agent),
				new Oara_Curl_Parameter('data[query][date1]', $dStartDate->toString("yyyy-MM-dd")),
				new Oara_Curl_Parameter('data[query][date2]', $dEndDate->toString("yyyy-MM-dd")),
				new Oara_Curl_Parameter('data[query][api_key]', $this->_apiKey)
		);
		$urls[] = new Oara_Curl_Request('http://www.skyparksecure.com/api/v4/jsonp/getSales?', $exportParams);
		$exportReport = $this->_client->get($urls);
		
		$report = substr($exportReport[0], 1, strlen($exportReport[0])-3);
		$exportData = json_decode($report);
		foreach ($exportData->result as $booking) {

			$transaction = Array();
			$transaction['merchantId'] = 1;
			$transaction['unique_id'] = $booking->booking_ref;
			$transaction['metadata'] = $booking->product_name;
			$transaction['custom_id'] = $booking->custom_id;
			$transactionDate = new Zend_Date($booking->booking_date, 'yyyy.MMM.dd HH:mm:00', 'en');
			$pickupDate = new Zend_Date($booking->dateA, 'yyyy.MMM.dd HH:mm:00', 'en');
			$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
			$transaction['metadata'] = $booking->product_id;
			if ($booking->booking_mode == "Booked" || $booking->booking_mode == "Amended"){
				$transaction['status'] = Oara_Utilities::STATUS_PENDING;
				if ($today > $pickupDate){
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				}
			} else if ($booking->booking_mode == "Cancelled"){
				$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
			} else {
				throw new Exception("New status found");
			}
			
			$transaction['amount'] = Oara_Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $booking->sale_price))/1.2;
			$transaction['commission'] = Oara_Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "",  $booking->commission_affiliate))/1.2;

			$totalTransactions[] = $transaction;

		}

		return $totalTransactions;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();


		return $paymentHistory;
	}
}