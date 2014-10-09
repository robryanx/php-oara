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
 * @category   Oara_Network_Publisher_ShareASale
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_ShareASale extends Oara_Network {
	/**
	 * API Secret
	 * @var string
	 */
	private $_apiSecret = null;
	/**
	 * API Token
	 * @var string
	 */
	private $_apiToken = null;
	/**
	 * Merchant ID
	 * @var string
	 */
	private $_affiliateId = null;
	/**
	 * Api Version
	 * @var float
	 */
	private $_apiVersion = null;
	/**
	 * Api Server
	 * @var string
	 */
	private $_apiServer = null;
	
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_ShareASale
	 */
	public function __construct($credentials) {

		$this->_affiliateId = preg_replace("/[^0-9]/","",$credentials['affiliateId']);
		$this->_apiToken = $credentials['apiToken'];
		$this->_apiSecret = $credentials['apiSecret'];

		$this->_apiVersion = 1.8;
		$this->_apiServer = "https://shareasale.com/x.cfm?";
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;
		
		$returnResult = self::makeCall("apitokencount");
		if ($returnResult) {
			//parse HTTP Body to determine result of request
			if (stripos($returnResult, "Error Code ")) { // error occurred
				$connection = false;
				echo $returnResult;
			}
		} else { // connection error
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
		
		$returnResult = self::makeCall("merchantStatus");
		$exportData = str_getcsv($returnResult, "\r\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$merchantArray = str_getcsv($exportData[$i], "|");
			if (count($merchantArray) > 1){
				$obj = Array();
				$obj['cid'] = (int)$merchantArray[0];
				$obj['name'] = $merchantArray[1];
				$merchants[] = $obj;
			}
		}
		
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($idMerchant, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$returnResult = self::makeCall("activity","&dateStart=".$dStartDate->toString("MM/dd/yyyy")."&dateEnd=".$dEndDate->toString("MM/dd/yyyy"));
		$exportData = str_getcsv($returnResult, "\r\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], "|");
			if (count($transactionExportArray) > 1 && in_array((int) $transactionExportArray[2], $merchantList)) {
				$transaction = Array();
				$merchantId = (int) $transactionExportArray[2];
				$transaction['merchantId'] = $merchantId;
				$transactionDate = new Zend_Date($transactionExportArray[3], 'MM-dd-yyyy HH:mm:ss');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				$transaction['unique_id'] = (int)$transactionExportArray[0];

				if ($transactionExportArray[1] != null) {
					$transaction['custom_id'] = $transactionExportArray[1];
				}

				if ($transactionExportArray[9] != null) {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
					if ($transactionExportArray[8] != null) {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
						if ($transactionExportArray[7] != null) {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						} else {
							$transaction['status'] = Oara_Utilities::STATUS_PENDING;
						}
				$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[4]);
				$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[5]);
				$totalTransactions[] = $transaction;
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
	/**
	 * 
	 * Make the call for this API
	 * @param string $actionVerb
	 */
	private function makeCall($actionVerb, $params = ""){
		
		$myTimeStamp = gmdate(DATE_RFC1123);
		$sig = $this->_apiToken.':'.$myTimeStamp.':'.$actionVerb.':'.$this->_apiSecret;

		$sigHash = hash("sha256", $sig);
		$myHeaders = array("x-ShareASale-Date: $myTimeStamp", "x-ShareASale-Authentication: $sigHash");
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_apiServer."affiliateId=".$this->_affiliateId."&token=".$this->_apiToken."&version=".$this->_apiVersion."&action=".$actionVerb.$params);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $myHeaders);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$returnResult = curl_exec($ch);
		curl_close($ch);
		return $returnResult;
	}
}
