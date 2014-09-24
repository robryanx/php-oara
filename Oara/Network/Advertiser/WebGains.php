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
	and we should add some contact information
**/	
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Wg
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Advertiser_WebGains extends Oara_Network {
	
	/**
	 * Web client.
	 */
	private $_webClient = null;
	
	private $_merchantId = null;
	
	private $_agencyCode = null;
	
	private $_currency = null;

	/**
	 * Server.
	 */
	private $_server = null;
	/**
	 * Export Merchant Parameters
	 * @var array
	 */
	private $_exportMerchantParameters = null;
	/**
	 * Export Transaction Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;
	/**
	 * Export Overview Parameters
	 * @var array
	 */
	private $_exportOverviewParameters = null;
	/**
	 * Converter configuration for the merchants.
	 * @var array
	 */
	private $_merchantConverterConfiguration = Array('programID'			 => 'cid',
		'programName'		 => 'name',
		'programURL'		 => 'url',
		'programDescription' => 'description'
		);
		/**
		 * Converter configuration for the transactions.
		 * @var array
		 */
		private $_transactionConverterConfiguration = Array('status'	 => 'status',
		'saleValue'	 => 'amount',
		'commission' => 'commission',
		'date'		 => 'date',
		'merchantId' => 'merchantId',
		'custom_id'	 => 'clickRef',
		);
		/**
		 * Array with the id from the campaigns
		 * @var array
		 */
		private $_campaignMap = array();
		/**
		 * Constructor.
		 * @param $webgains
		 * @return Oara_Network_Publisher_Wg_Api
		 */
		public function __construct($credentials) {
			$user = $credentials['user'];
			$password = $credentials['password'];
			$this->_currency = $credentials["currency"];

			$serverArray = array();
			$serverArray["uk"] = 'www.webgains.com';
			$serverArray["fr"] = 'www.webgains.fr';
			$serverArray["us"] = 'us.webgains.com';
			$serverArray["de"] = 'www.webgains.de';
			$serverArray["fr"] = 'www.webgains.fr';
			$serverArray["nl"] = 'www.webgains.nl';
			$serverArray["dk"] = 'www.webgains.dk';
			$serverArray["se"] = 'www.webgains.se';
			$serverArray["es"] = 'www.webgains.es';
			$serverArray["ie"] = 'www.webgains.ie';
			$serverArray["it"] = 'www.webgains.it';
			
			$loginUrlArray = array();
			$loginUrlArray["uk"] = 'https://www.webgains.com/loginform.html?action=login';
			$loginUrlArray["fr"] = 'https://www.webgains.fr/loginform.html?action=login';
			$loginUrlArray["us"] = 'https://us.webgains.com/loginform.html?action=login';
			$loginUrlArray["de"] = 'https://www.webgains.de/loginform.html?action=login';
			$loginUrlArray["fr"] = 'https://www.webgains.fr/loginform.html?action=login';
			$loginUrlArray["nl"] = 'https://www.webgains.nl/loginform.html?action=login';
			$loginUrlArray["dk"] = 'https://www.webgains.dk/loginform.html?action=login';
			$loginUrlArray["se"] = 'https://www.webgains.se/loginform.html?action=login';
			$loginUrlArray["es"] = 'https://www.webgains.es/loginform.html?action=login';
			$loginUrlArray["ie"] = 'https://www.webgains.ie/loginform.html?action=login';
			$loginUrlArray["it"] = 'https://www.webgains.it/loginform.html?action=login';

			$valuesLogin = array(
			new Oara_Curl_Parameter('user_type', 'agencyuser'),
			new Oara_Curl_Parameter('username', $user),
			new Oara_Curl_Parameter('password', $password)
			);
			
			foreach ($loginUrlArray as $country => $url){
				$this->_webClient = new Oara_Curl_Access($url, $valuesLogin, $credentials);
				if (preg_match("/logout.html/", $this->_webClient->getConstructResult())) {
					$this->_server = $serverArray[$country];
					break;
				}
			}

			$this->_exportMerchantParameters = array('username'	 => $user,
			'password'	 => $password
			);
			$this->_exportTransactionParameters = array('username'	 => $user,
			'password'	 => $password
			);
			$this->_exportOverviewParameters = array('username'	 => $user,
			'password'	 => $password
			);

		}
		/**
		 * Check the connection
		 */
		public function checkConnection() {
			$connection = false;
			if ($this->_server != null){
				$connection = true;
			}
			return $connection;
		}
		/**
		 * (non-PHPdoc)
		 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
		 */
		public function getMerchantList() {
			$merchantList = Array();
			
			$dom = new Zend_Dom_Query($this->_webClient->getConstructResult());
			$results = $dom->query('a[href*="agency/"]');
			$merchantLines = $results->current();
			$this->_agencyCode = $merchantLines->attributes->getNamedItem("href")->nodeValue;
			
			$valuesFromExport = array();
			$urls = array ();
			$urls [] = new Oara_Curl_Request ( "http://www.webgains.com/{$this->_agencyCode}/reports/index/view/type/performance/for/performance-affiliate", $valuesFromExport );
			$exportReport = $this->_webClient->get ( $urls );
			
			
			$campaingMap = array();
			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('#program');
			$merchantLines = $results->current()->childNodes;
			for ($i = 0; $i < $merchantLines->length; $i++) {
				$cid = $merchantLines->item($i)->attributes->getNamedItem("value")->nodeValue;
				if (is_numeric($cid)){
					$campaingMap[$cid] = $merchantLines->item($i)->nodeValue;
					
					$obj = Array ();
					$obj ['cid'] = $cid;
					$obj ['name'] = $merchantLines->item($i)->nodeValue;
					$merchantList [] = $obj;
					
					$this->_merchantId = $cid;
				}
			}
			
			$this->_campaignMap = $campaingMap;
			
			$valuesFromExport = array();
			$urls = array ();
			$urls [] = new Oara_Curl_Request ( "http://www.webgains.com/{$this->_agencyCode}/default/index/login?programId={$this->_merchantId}&userType=agency&userId=null", $valuesFromExport );
			$exportReport = $this->_webClient->get ( $urls );
			

			return $merchantList;
		}
		/**
		 * (non-PHPdoc)
		 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
		 */
		public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
			$totalTransactions = Array();
						
			$valuesFromExport = array();
			$valuesFromExport[] = new Oara_Curl_Parameter('action', 'earnings');
			$valuesFromExport[] = new Oara_Curl_Parameter('mode', 'generate');
			$valuesFromExport[] = new Oara_Curl_Parameter('columnsSelected', 'affiliate,merchant,program,commission,value,date,orderReference,clickthroughTime,productId,transactionId,status');
			$valuesFromExport[] = new Oara_Curl_Parameter('period', 'custom');
			$valuesFromExport[] = new Oara_Curl_Parameter('startday', $dStartDate->toString("d"));
			$valuesFromExport[] = new Oara_Curl_Parameter('startmonth', $dStartDate->toString("M"));
			$valuesFromExport[] = new Oara_Curl_Parameter('startyear', $dStartDate->toString("yyyy"));
			$valuesFromExport[] = new Oara_Curl_Parameter('endday', $dEndDate->toString("d"));
			$valuesFromExport[] = new Oara_Curl_Parameter('endmonth', $dEndDate->toString("M"));
			$valuesFromExport[] = new Oara_Curl_Parameter('endyear', $dEndDate->toString("yyyy"));
			foreach ($merchantList as $merchantId){
				$valuesFromExport[] = new Oara_Curl_Parameter('program[]', $merchantId);
			}
			
			$valuesFromExport[] = new Oara_Curl_Parameter('format', 'csv');
			$valuesFromExport[] = new Oara_Curl_Parameter('currency', 'USD');
			$valuesFromExport[] = new Oara_Curl_Parameter('status[]', '10');
			$valuesFromExport[] = new Oara_Curl_Parameter('status[]', '20');
			$valuesFromExport[] = new Oara_Curl_Parameter('status[]', '25');
			$valuesFromExport[] = new Oara_Curl_Parameter('eventType', '');
			$valuesFromExport[] = new Oara_Curl_Parameter('invoice', '');
			$valuesFromExport[] = new Oara_Curl_Parameter('orderReference', '');
			$valuesFromExport[] = new Oara_Curl_Parameter('resultsperpage', '0');
			$valuesFromExport[] = new Oara_Curl_Parameter('numdecimalplaces', '2');
			$urls = array ();
			$urls [] = new Oara_Curl_Request ( "http://www.webgains.com/merchants/{$this->_merchantId}/report.html?", $valuesFromExport );
			$exportReport = $this->_webClient->get ( $urls );
			
			$exportData = str_getcsv($exportReport[0], "\n");
			$num = count($exportData);
			for ($i = 1; $i < $num-5; $i++) {
				$transactionExportArray = str_getcsv($exportData[$i], ",");
				
				
				if (in_array($transactionExportArray[4], $merchantList) && is_numeric($transactionExportArray[8])) {
						
					$transaction = array();
					$transaction['merchantId'] = $transactionExportArray[4];
					$transactionDate = new Zend_Date($transactionExportArray[9], "dd/MM/yy HH:mm:ss");
					$transaction["date"] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
					$transaction['unique_id'] = $transactionExportArray[13];

					$transaction['status'] = null;
					$transaction['amount'] = $transactionExportArray[8];
					$transaction['commission'] = $transactionExportArray[6];
					
					$transaction['custom_id'] = $transactionExportArray[10];

					if (in_array($transactionExportArray[14], array('Paid to affiliate','Cleared for Payment','Adjusted - Cleared for Payment','Adjusted - Awaiting Payment') )) {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
					if (in_array($transactionExportArray[14], array('In Recall Period','Recall Expires','Delayed until','Awaiting Invoice Settlement','Awaiting Invoice','Invoiced - awaiting payment') )) {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
					if ($transactionExportArray[14] == 'Cancelled') {
						$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
					} else {
						throw new Exception("Error in the transaction status {$transactionExportArray[14]}");
					}
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
}
