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
 * @category   Oara_Network_Publisher_Daisycon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Daisycon extends Oara_Network {

	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;

	private $_credentials = null;

	private $_publisherId = array();
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;


	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;

		try {
			$user = $this->_credentials['user'];
			$password = $this->_credentials['password'];
				
				
			$url = "https://services.daisycon.com:443/publishers?page=1&per_page=100";
			// initialize curl resource
			$ch = curl_init();
			// set the http request authentication headers
			$headers = array( 'Authorization: Basic ' . base64_encode( $user . ':' . $password ) );
			// set curl options
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// execute curl
			$response = curl_exec($ch);
			$publisherList = json_decode($response, true);
			foreach ($publisherList as $publisher){
				$this->_publisherId[] = $publisher["id"];
			}
			if (count($this->_publisherId) == 0){
				throw new \Exception("No publisher found");
			}
				
		} catch (Exception $e) {
			$connection = false;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();
		$merchantList = array();
		$user = $this->_credentials['user'];
		$password = $this->_credentials['password'];


		foreach ($this->_publisherId as $publisherId){
			$page = 1;
			$pageSize = 100;
			$finish = false;
				
			while (!$finish){
				$url = "https://services.daisycon.com:443/publishers/$publisherId/programs?page=$page&per_page=$pageSize";
				// initialize curl resource
				$ch = curl_init();
				// set the http request authentication headers
				$headers = array( 'Authorization: Basic ' . base64_encode( $user . ':' . $password ) );
				// set curl options
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				// execute curl
				$response = curl_exec($ch);
				$merchantList = json_decode($response, true);

				foreach ($merchantList as $merchant){
					if ($merchant['status'] == 'active'){
						$obj = Array();
						$obj['cid'] =  $merchant['id'];
						$obj['name'] =  $merchant['name'];
						$merchants[] = $obj;
					}
				}

				if (count($merchantList) != $pageSize){
					$finish = true;
				}
				$page++;
			}
		}

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();


		$user = $this->_credentials['user'];
		$password = $this->_credentials['password'];


		foreach ($this->_publisherId as $publisherId){
			$page = 1;
			$pageSize = 100;
			$finish = false;
				
			while (!$finish){
				$url = "https://services.daisycon.com:443/publishers/$publisherId/transactions?page=$page&per_page=$pageSize&start=".urlencode($dStartDate->toString("yyyy-MM-dd HH:mm:ss"))."&end=".urlencode($dEndDate->toString("yyyy-MM-dd HH:mm:ss"))."";
				// initialize curl resource
				$ch = curl_init();
				// set the http request authentication headers
				$headers = array( 'Authorization: Basic ' . base64_encode( $user . ':' . $password ) );
				// set curl options
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				// execute curl
				$response = curl_exec($ch);
				$transactionList = json_decode($response, true);

				foreach ($transactionList as $transaction){
					$merchantId = $transaction['program_id'];
					if ($merchantList == null || in_array($merchantId, $merchantList)) {

						$transactionArray = Array();
						$transactionArray['unique_id'] = $transaction['affiliatemarketing_id'];

						$transactionArray['merchantId'] = $merchantId;
						$transactionDate = new Zend_Date($transaction['date'], 'dd-MM-yyyyTHH:mm:ss');
						$transactionArray['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
							
						$parts = current($transaction['parts']);
						
						if ($parts['subid'] != null) {
							$transactionArray['custom_id'] = $parts['subid'];
						}
						if ($parts['status'] == 'approved') {
							$transactionArray['status'] = Oara_Utilities::STATUS_CONFIRMED;
						} else
						if ($parts['status'] == 'pending' || $parts['status'] == 'potential' || $parts['status'] == 'open' ) {
							$transactionArray['status'] = Oara_Utilities::STATUS_PENDING;
						} else
						if ($parts['status'] == 'disapproved' || $parts['status'] == 'incasso') {
							$transactionArray['status'] = Oara_Utilities::STATUS_DECLINED;
						} else {
							throw new Exception("New status {$parts['status']}");
						}
						$transactionArray['amount'] = Oara_Utilities::parseDouble($parts['revenue']);
						//$transaction['currency'] = $transactionObject->currency;
						$transactionArray['commission'] = Oara_Utilities::parseDouble($parts['commission']);
						$totalTransactions[] = $transactionArray;
					}
				}

				if (count($transactionList) != $pageSize){
					$finish = true;
				}
				$page++;
			}
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
