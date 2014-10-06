<?php
/**
 * API Class
 *
 * @author Carlos Morillo Merino
 * @category Oara_Network_Publisher_Afiliant
 * @copyright Fubra Limited
 * @version Release: 01.00
 *         
 */
class Oara_Network_Publisher_Shuttlefare extends Oara_Network {
	
	/**
	 * Client
	 *
	 * @var unknown_type
	 */
	private $_client = null;
	
	/**
	 * Constructor and Login
	 *
	 * @param $buy
	 * @return Oara_Network_Publisher_Buy_Api
	 */
	public function __construct($credentials) {

		$user = $credentials ['user'];
		$password = $credentials ['password'];
		
		$valuesLogin = array (
				new Oara_Curl_Parameter ( 'user[email]', $user ),
				new Oara_Curl_Parameter ( 'user[password]', $password ),
				new Oara_Curl_Parameter ( 'user[remember_me]', '0' ),
				new Oara_Curl_Parameter ( 'commit', 'Sign in' )
		);
		$loginUrl = 'http://affiliates.shuttlefare.com/users/sign_in';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, 'http://affiliates.shuttlefare.com/partners' );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
		if (preg_match("/logout/", $html, $matches)) {	
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
		$merchants = Array();
		
		$obj = Array();
		$obj['cid'] = 1;
		$obj['name'] = 'Shuttlefare';
		$merchants[] = $obj;
		
		return $merchants;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();

		$valuesFromExport = array(
				new Oara_Curl_Parameter('utf8', '%E2%9C%93'),
				new Oara_Curl_Parameter('payment[from]', $dStartDate->toString("MM/dd/yyyy")),
				new Oara_Curl_Parameter('payment[to]', $dEndDate->toString("MM/dd/yyyy")),
				new Oara_Curl_Parameter('commit', 'Generate')
		);
	
		$rch = curl_init ();
		$options = $this->_options;		
		
		$arg = array ();
		foreach ( $valuesFromExport as $parameter ) {
			$arg [] = urlencode($parameter->getKey ()) . '=' . urlencode ( $parameter->getValue () );
		}
		curl_setopt ( $rch, CURLOPT_URL, 'http://affiliates.shuttlefare.com/partners/payments/report?'.implode ( '&', $arg ) );
		curl_setopt_array ( $rch, $options );		
		
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
		$dom = new Zend_Dom_Query($html);
		
		$tableList = $dom->query ( 'table' );
		$exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
		
		$num = count ( $exportData );
		for($i = 1; $i < $num-1; $i ++) {
			$transactionExportArray = explode (";,", $exportData [$i]);
				
			$transaction = Array ();
			$transaction ['merchantId'] = 1;
			$transaction ['unique_id'] = $transactionExportArray [0];
			$transactionDate = new Zend_Date ( $transactionExportArray [5], 'MM/dd/yyyy');
			$transaction ['date'] = $transactionDate->toString ( "yyyy-MM-dd HH:mm:ss" );				
			$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;				
			$transaction ['amount'] = Oara_Utilities::parseDouble ( preg_replace ( "/[^0-9\.,]/", "", $transactionExportArray [1] ) );
			$transaction ['commission'] = Oara_Utilities::parseDouble ( preg_replace ( "/[^0-9\.,]/", "", $transactionExportArray [2] ) );
			
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
	 *
	 * It returns the transactions for a payment
	 *
	 * @param int $paymentId        	
	 */
	public function paymentTransactions($paymentId, $merchantList, $startDate) {
		$transactionList = array ();
		
		return $transactionList;
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
			
			$domTd = new Zend_Dom_Query ( self::DOMinnerHTML($result) );
			$resultsTd = $domTd->query ( 'td' );
			$countTd = count ( $resultsTd );
			$i = 0;
			foreach( $resultsTd as $resultTd) {
				$value = $resultTd->nodeValue;
				if ($i != $countTd - 1) {
					$csv .= trim ( $value ) . ";,";
				} else {
					$csv .= trim ( $value );
				}
				$i++;
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
