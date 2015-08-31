<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2015  Fubra Limited
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
 * @category   Oara_Network_Publisher_Groupon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Groupon extends Oara_Network {
	/**
	 * Private API Key
	 * @var string
	 */
	private $_credentials = null;
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;
		
		$dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;

		if (! Oara_Utilities::mkdir_recursive ( $dir, 0777 )) {
			throw new Exception ( 'Problem creating folder in Access' );
		}

		$cookies = $dir . $credentials["cookieName"] . '_cookies.txt';
		unlink($cookies);
		
		$this->_options = array (
				CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FAILONERROR => true,
				CURLOPT_COOKIEJAR => $cookies,
				CURLOPT_COOKIEFILE => $cookies,
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_AUTOREFERER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HEADER => false,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPHEADER => array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Accept-Language: es,en-us;q=0.7,en;q=0.3','Accept-Encoding: gzip, deflate','Connection: keep-alive', 'Cache-Control: max-age=0'),
				CURLOPT_ENCODING => "gzip",
				CURLOPT_VERBOSE => false
		);
		
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;

		try{
			self::getMerchantList();
			$connection = true;
		} catch (Exception $e){

		}

		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {

	    $date = new \DateTime();
		$merchants = Array ();
		$url = "https://partner-int-api.groupon.com/reporting/v2/order.csv?clientId={$this->_credentials['apiPassword']}&group=order&date=[{$date->format('Y-m-d')}&date={$date->format('Y-m-d')}]";
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL,  $url);
		curl_setopt_array ( $rch, $options );
		$result = curl_exec ( $rch );
		curl_close ( $rch );
		if ($result === false){
		    throw new Exception ("API key not valid");
		}
    	$obj = Array();
    	$obj['cid']  = "1";
    	$obj['name'] = "Groupon Partner Network";
    	$merchants[] = $obj;


		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();		
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		$dateArraySize = sizeof($dateArray);
		for ($j = 0; $j < $dateArraySize; $j++) {
		    $date = $dateArray[$j];
		
    		$url = "https://partner-int-api.groupon.com/reporting/v2/order.csv?clientId={$this->_credentials['apiPassword']}&group=order&date={$date->toString("yyyy-MM-dd")}";
    		$rch = curl_init ();
    		$options = $this->_options;
    		curl_setopt ( $rch, CURLOPT_URL,  $url);
    		curl_setopt_array ( $rch, $options );
    		$result = curl_exec ( $rch );
    		curl_close ( $rch );
    		
    	    $exportData = str_getcsv($result, "\n");
    		$num = count($exportData);
    		for ($i = 1; $i < $num; $i++) {
    			$transactionExportArray = str_getcsv($exportData[$i], ",");
    			$transaction = Array();
    			$transaction['merchantId'] = "1";
    			$transaction['date'] = $date->toString("yyyy-MM-ddTHH:mm:ss");
    			$transaction['unique_id'] =  $transactionExportArray[0];
    			$transaction['currency'] = $transactionExportArray[4];
    
    			if ($transactionExportArray[1] != null) {
    				$transaction['custom_id'] = $transactionExportArray[1];
    			}
    
    			if ($transactionExportArray[5] == 'VALID' || $transactionExportArray[5] == 'REFUNDED') {
    				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
    			} else if ($transactionExportArray[5] == 'INVALID'){
    				$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
    			} else {
    				throw new Exception("Status {$transactionExportArray[5]} unknown");
    			}
    
    			if (preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionExportArray[8], $match)){
    				$transaction['amount'] = (double)$match[0];
    			}
    			if (preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionExportArray[12], $match)){
    				$transaction['commission'] = (double)$match[0];
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
