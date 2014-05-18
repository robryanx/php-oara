<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_RentalCars
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_RentalCars extends Oara_Network {
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
				new Oara_Curl_Parameter ( 'login_username', $this->_credentials ['user'] ),
				new Oara_Curl_Parameter ( 'login_password', $this->_credentials ['password'] ) 
		);
		
		$loginUrl = 'https://secure.rentalcars.com/affiliates/access?commit=true';
		$this->_client = new Oara_Curl_Access ( $loginUrl, $valuesLogin, $this->_credentials );
		
		if (! self::checkConnection ()) {
			throw new Exception ( "You are not connected\n\n" );
		}
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		// If not login properly the construct launch an exception
		$connection = false;
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( 'https://secure.rentalcars.com/affiliates/?master=1', array () );
		
		$exportReport = $this->_client->get ( $urls );
		
		$dom = new Zend_Dom_Query ( $exportReport [0] );
		$results = $dom->query ( '#header_logout' );
		
		if (count ( $results ) > 0) {
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
		$obj ['name'] = "RentalCars";
		$obj ['url'] = "https://secure.rentalcars.com";
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
		
		
		$cancelledMap = array();
		$valuesFormExport = array ();
		$valuesFormExport [] = new Oara_Curl_Parameter ( 'cancelled', 'cancelled' );
		
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( 'https://secure.rentalcars.com/affiliates/booked_excel?date_start=' . $dStartDate->toString ( "yyyy-MM-dd" ) . '&date_end=' . $dEndDate->toString ( "yyyy-MM-dd" ) . '?', $valuesFormExport );
		$exportReport = $this->_client->post ( $urls );
		
		$xml = simplexml_load_string ( $exportReport [0] );
		$json = json_encode ( $xml );
		$array = json_decode ( $json, TRUE );
		
		$headerIndex = array();
		for ($i=0; $i < count($array["Worksheet"]["Table"]["Row"][2]["Cell"]);$i++){
			$headerIndex[$i] = $array["Worksheet"]["Table"]["Row"][2]["Cell"][$i]["Data"];
		}
		
		
		for($z = 3; $z < count ( $array["Worksheet"]["Table"]["Row"] ) - 2; $z ++) {
			$transactionDetails = array();
			for ($i=0; $i < count($array["Worksheet"]["Table"]["Row"][$z]["Cell"]);$i++){
				$transactionDetails[$headerIndex[$i]] = $array["Worksheet"]["Table"]["Row"][$z]["Cell"][$i]["Data"];
			}
				
			$cancelledMap[$transactionDetails["Res. Number"]] = true;
		}
		
		
		
		$valuesFormExport = array ();
		$valuesFormExport [] = new Oara_Curl_Parameter ( 'booking', 'booking' );
		
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( 'https://secure.rentalcars.com/affiliates/booked_excel?date_start=' . $dStartDate->toString ( "yyyy-MM-dd" ) . '&date_end=' . $dEndDate->toString ( "yyyy-MM-dd" ) . '?', $valuesFormExport );
		$exportReport = $this->_client->post ( $urls );
		
		$xml = simplexml_load_string ( $exportReport [0] );
		$json = json_encode ( $xml );
		$array = json_decode ( $json, TRUE );
		
		$headerIndex = array();
		for ($i=0; $i < count($array["Worksheet"]["Table"]["Row"][2]["Cell"]);$i++){
			$headerIndex[$i] = $array["Worksheet"]["Table"]["Row"][2]["Cell"][$i]["Data"];
		}
		
		
		for($z = 3; $z < count ( $array["Worksheet"]["Table"]["Row"] ) - 2; $z ++) {
			$transactionDetails = array();
			for ($i=0; $i < count($array["Worksheet"]["Table"]["Row"][$z]["Cell"]);$i++){
				$transactionDetails[$headerIndex[$i]] = $array["Worksheet"]["Table"]["Row"][$z]["Cell"][$i]["Data"];
			}
			
			$transaction = Array ();
			$transaction ['merchantId'] = "1";
			$transaction ['unique_id'] = $transactionDetails["Res. Number"];
			
			if ($transactionDetails["Payment Date"] != null){
				$date = new Zend_Date($transactionDetails["Payment Date"], "dd MMM yyyy - HH:ii", "en_GB");
			} else {
				$date = new Zend_Date($transactionDetails["Book Date"], "dd MMM yyyy - HH:ii", "en_GB");
			}
			
			
			
			if (!empty($transactionDetails["AD Campaign"])){
				$transaction ['custom_id'] = $transactionDetails["AD Campaign"];
			}
			
			
			
			$transaction ['date'] = $date->toString ( "yyyy-MM-dd HH:mm:00" );
			
			if ($transactionDetails["Payment Date"] != null){
				$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;
			} else {
				$transaction ['status'] = Oara_Utilities::STATUS_PENDING;
			}
			
			
			if (isset($cancelledMap[$transaction ['unique_id']])){
				$transaction ['status'] = Oara_Utilities::STATUS_DECLINED;
			}
			
			$transaction ['amount'] = $transactionDetails["Booking Value"];
			$transaction ['currency'] = $transactionDetails["Payment Currency"];
			$transaction ['commission'] = $transactionDetails["Total Commission"];
			$totalTransactions [] = $transaction;
			
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
