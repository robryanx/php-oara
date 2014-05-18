<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Etrader
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Etrader extends Oara_Network {
	private $_credentials = null;
	/**
	 * Client
	 * 
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * 
	 * @param
	 *        	$credentials
	 * @return Oara_Network_Publisher_PureVPN
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;
		self::logIn ();
	}
	private function logIn() {
		$valuesLogin = array (
				new Oara_Curl_Parameter ( 'j_username', $this->_credentials ['user'] ),
				new Oara_Curl_Parameter ( 'j_password', $this->_credentials ['password'] ),
				new Oara_Curl_Parameter ( '_spring_security_remember_me', 'true' )
		);
		
		
		$loginUrl = 'http://etrader.kalahari.com/login?';
		$this->_client = new Oara_Curl_Access ( $loginUrl, $valuesLogin, $this->_credentials );
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		// If not login properly the construct launch an exception
		$connection = false;
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( 'https://etrader.kalahari.com/view/affiliate/home', array () );
		
		$exportReport = $this->_client->get ( $urls );
		
		if (preg_match("/signout/", $exportReport [0])) {
			$connection = true;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * 
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array ();
		
		$obj = array ();
		$obj ['cid'] = "1";
		$obj ['name'] = "eTrader";
		$obj ['url'] = "https://etrader.kalahari.com";
		$merchants [] = $obj;
		
		return $merchants;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array ();
		
		$page = 1;
		$continue = true;
		while  ($continue){
			$valuesFormExport = array ();
			$valuesFormExport [] = new Oara_Curl_Parameter ( 'dateFrom', $dStartDate->toString("dd/MM/yyyy") );
			$valuesFormExport [] = new Oara_Curl_Parameter ( 'dateTo', $dEndDate->toString("dd/MM/yyyy") );
			$valuesFormExport [] = new Oara_Curl_Parameter ( 'startIndex', $page );
			$valuesFormExport [] = new Oara_Curl_Parameter ( 'numberOfPages', '1' );
			
			$urls = array ();
			$urls [] = new Oara_Curl_Request ( 'https://etrader.kalahari.com/view/affiliate/transactionreport', $valuesFormExport );
			$exportReport = $this->_client->post ( $urls );
			
			$dom = new Zend_Dom_Query ( $exportReport [0] );
			$results = $dom->query ( 'table' );
			$exportData = self::htmlToCsv ( self::DOMinnerHTML ( $results->current () ) );
			
			if (preg_match("/No results found/", $exportData[1])){
				$continue = false;
				break;
			} else {
				$page++;
			}
			
			for ($j = 1; $j < count($exportData); $j++) {
	
				$transactionDetail = str_getcsv($exportData[$j], ";");
				$transaction = Array ();
				$transaction ['merchantId'] = "1";
				
				if (preg_match("/Order dispatched: ([0-9]+) /", $transactionDetail[2], $match)){
					$transaction ['custom_id'] = $match[1];
				}
				
				$date = new Zend_Date($transactionDetail[0], "dd MMM yyyy", "en_GB");
				$transaction ['date'] = $date->toString ( "yyyy-MM-dd 00:00:00" );
				$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;
				
				if ($transactionDetail[3] != null){
					preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionDetail[3], $match);
					$transaction['amount'] = (double)$match[0];
					$transaction['commission'] = (double)$match[0];
					
				} else if ($transactionDetail[4] != null){
					preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionDetail[4], $match);
					$transaction['amount'] = (double)$match[0];
					$transaction['commission'] = (double)$match[0];
				}
				$totalTransactions [] = $transaction;
				
			}
			
			
			
		}
		return $totalTransactions;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array ();
		
		return $paymentHistory;
	}
	
	/**
	 *
	 *
	 * Function that Convert from a table to Csv
	 * 
	 * @param unknown_type $html        	
	 */
	private function htmlToCsv($html) {
		$html = str_replace ( array (
				"\t",
				"\r",
				"\n" 
		), "", $html );
		$csv = "";
		$dom = new Zend_Dom_Query ( $html );
		$results = $dom->query ( 'tr' );
		$count = count ( $results ); // get number of matches: 4
		foreach ( $results as $result ) {
			$tdList = $result->childNodes;
			$tdNumber = $tdList->length;
			for($i = 0; $i < $tdNumber; $i ++) {
				$value = $tdList->item ( $i )->nodeValue;
				if ($i != $tdNumber - 1) {
					$csv .= trim ( $value ) . ";";
				} else {
					$csv .= trim ( $value );
				}
			}
			$csv .= "\n";
		}
		$exportData = str_getcsv ( $csv, "\n" );
		return $exportData;
	}
	/**
	 *
	 *
	 * Function that returns the innet HTML code
	 * 
	 * @param unknown_type $element        	
	 */
	private function DOMinnerHTML($element) {
		$innerHTML = "";
		$children = $element->childNodes;
		foreach ( $children as $child ) {
			$tmp_dom = new DOMDocument ();
			$tmp_dom->appendChild ( $tmp_dom->importNode ( $child, true ) );
			$innerHTML .= trim ( $tmp_dom->saveHTML () );
		}
		return $innerHTML;
	}
}
