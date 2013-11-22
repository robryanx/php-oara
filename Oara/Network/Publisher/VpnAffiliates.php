<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_PureVPN
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_VpnAffiliates extends Oara_Network {
	private $_credentials = null;
	/**
	 * Client
	 * 
	 * @var unknown_type
	 */
	private $_client = null;
	
	/**
	 * Security Code
	 * 
	 * @var unknown_type
	 */
	private $_s = null;
	
	/**
	 * Login Result
	 * 
	 * @var unknown_type
	 */
	private $_loginResult = null;
	
	/**
	 * Transaction List
	 * 
	 * @var unknown_type
	 */
	private $_transactionList = null;
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
		$html = file_get_contents ( 'https://vpnaffiliates.com/affiliates/affiliates/login.php#login' );
		if (preg_match ( "/\"S\\\\\",\\\\\"(.*?)\\\\\"/", $html, $matches )) {
			$this->_s = $matches [1];
		}
		
		$valuesLogin = array (
				new Oara_Curl_Parameter ( 'D', '{"C":"Gpf_Rpc_Server", "M":"run", "requests":[{"C":"Gpf_Auth_Service", "M":"authenticate", "fields":[["name","value"],["Id",""],["username","' . $this->_credentials ["user"] . '"],["password","' . $this->_credentials ["password"] . '"],["rememberMe","Y"],["language","en-US"]]}], "S":"' . $this->_s . '"}' ) 
		);
		
		$loginUrl = 'https://vpnaffiliates.com/affiliates/scripts/server.php?';
		$this->_client = new Oara_Curl_Access ( $loginUrl, $valuesLogin, $this->_credentials );
		$this->_loginResult = $this->_client->getConstructResult ();
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		// If not login properly the construct launch an exception
		$connection = true;
		
		if (! preg_match ( "/User authenticated./", $this->_loginResult )) {
			$connection = false;
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
		$obj ['name'] = "Vpn Affiliates";
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
		$valuesFormExport = array ();
		
		if ($this->_transactionList == null) {
			$urls = array ();
			$valuesExport = array (
					new Oara_Curl_Parameter ( 'D', '{"C":"Pap_Affiliates_Reports_TransactionsGrid", "M":"getCSVFile", "S":"' . $this->_s . '", "FormResponse":"Y", "sort_col":"dateinserted", "sort_asc":false, "offset":0, "limit":30, "columns":[["id"],["id"],["commission"],["totalcost"],["fixedcost"],["t_orderid"],["productid"],["dateinserted"],["name"],["rtype"],["tier"],["commissionTypeName"],["rstatus"],["merchantnote"],["channel"]]}' ) 
			);
			$urls [] = new Oara_Curl_Request ( 'https://vpnaffiliates.com/affiliates/scripts/server.php?', $valuesExport );
			$exportReport = array ();
			$exportReport = $this->_client->post ( $urls );
			$this->_transactionList = str_getcsv ( $exportReport [0], "\n" );
		}
		$exportData = $this->_transactionList;
		
		$num = count ( $exportData );
		for($i = 1; $i < $num; $i ++) {
			
			$transactionExportArray = str_getcsv ( $exportData [$i], "," );
			// print_r($transactionExportArray);
			
			$transaction = Array ();
			$transaction ['merchantId'] = 1;
			$transaction ['uniqueId'] = $transactionExportArray [3];
			$transactionDate = new Zend_Date ( $transactionExportArray [5], 'yyyy-MM-dd HH:mm:ss', 'en' );
			$transaction ['date'] = $transactionDate->toString ( "yyyy-MM-dd HH:mm:ss" );
			unset ( $transactionDate );
			$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;
			
			$transaction ['amount'] = Oara_Utilities::parseDouble ( $transactionExportArray [1] );
			$transaction ['commission'] = Oara_Utilities::parseDouble ( $transactionExportArray [0] );
			
			if (preg_match ( "/renegadegames\.co\.uk/", $this->_credentials ["user"] )) {
				$transaction ['amount'] = $transaction ['amount'] * 1.8;
				$transaction ['commission'] = $transaction ['commission'] * 1.8;
			}
			// print_r($transaction);
			
			if ($transaction ['date'] >= $dStartDate->toString ( "yyyy-MM-dd HH:mm:ss" ) && $transaction ['date'] <= $dEndDate->toString ( "yyyy-MM-dd HH:mm:ss" )) {
				$totalTransactions [] = $transaction;
			}
		}
		
		return $totalTransactions;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$overviewArray = Array ();
		$transactionArray = Oara_Utilities::transactionMapPerDay ( $transactionList );
		
		// Add transactions
		foreach ( $transactionArray as $merchantId => $merchantTransaction ) {
			foreach ( $merchantTransaction as $date => $transactionList ) {
				
				$overview = Array ();
				
				$overview ['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date ( $date, "yyyy-MM-dd" );
				$overview ['date'] = $overviewDate->toString ( "yyyy-MM-dd HH:mm:ss" );
				unset ( $overviewDate );
				$overview ['click_number'] = 0;
				$overview ['impression_number'] = 0;
				$overview ['transaction_number'] = 0;
				$overview ['transaction_confirmed_value'] = 0;
				$overview ['transaction_confirmed_commission'] = 0;
				$overview ['transaction_pending_value'] = 0;
				$overview ['transaction_pending_commission'] = 0;
				$overview ['transaction_declined_value'] = 0;
				$overview ['transaction_declined_commission'] = 0;
				$overview ['transaction_paid_value'] = 0;
				$overview ['transaction_paid_commission'] = 0;
				foreach ( $transactionList as $transaction ) {
					$overview ['transaction_number'] ++;
					if ($transaction ['status'] == Oara_Utilities::STATUS_CONFIRMED) {
						$overview ['transaction_confirmed_value'] += $transaction ['amount'];
						$overview ['transaction_confirmed_commission'] += $transaction ['commission'];
					} else if ($transaction ['status'] == Oara_Utilities::STATUS_PENDING) {
						$overview ['transaction_pending_value'] += $transaction ['amount'];
						$overview ['transaction_pending_commission'] += $transaction ['commission'];
					} else if ($transaction ['status'] == Oara_Utilities::STATUS_DECLINED) {
						$overview ['transaction_declined_value'] += $transaction ['amount'];
						$overview ['transaction_declined_commission'] += $transaction ['commission'];
					} else if ($transaction ['status'] == Oara_Utilities::STATUS_PAID) {
						$overview ['transaction_paid_value'] += $transaction ['amount'];
						$overview ['transaction_paid_commission'] += $transaction ['commission'];
					}
				}
				$overviewArray [] = $overview;
			}
		}
		
		return $overviewArray;
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
}
