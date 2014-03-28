<?php
/**
 * Api Class
 *
 * @author Carlos Morillo Merino
 * @category Oara_Network_Publisher_VigLink
 * @copyright Fubra Limited
 * @version Release: 01.00
 *         
 *         
 */
class Oara_Network_Publisher_VigLink extends Oara_Network {
	
	/**
	 * Password
	 */
	private $_apiPassword = null;
	
	/**
	 * Constructor.
	 *
	 * @param
	 *        	$affiliateWindow
	 * @return Oara_Network_Publisher_Aw_Api
	 */
	public function __construct($credentials) {
		
		$this->_apiPassword = $credentials["apiPassword"];
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		
		$now = new Zend_Date();
		
		$apiURL = "https://www.viglink.com/service/v1/cuidRevenue?lastDate={$now->toString("yyyy/MM/dd")}&period=month&secret={$this->_apiPassword}";
		$response = self::call($apiURL);
		if (is_array($response)){
			$connection = true;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array ();
		$obj = Array ();
		$obj ['cid'] = 1;
		$obj ['name'] = "VIgLink";
		$merchants [] = $obj;
		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array ();
		
		$apiURL = "https://www.viglink.com/service/v1/cuidRevenue?lastDate={$dEndDate->toString("yyyy/MM/dd")}&period=month&secret={$this->_apiPassword}";
		$response = self::call($apiURL);
			
		foreach ($response as $date => $transactionApi){
			foreach ($transactionApi[1] as $sale){
				if ($sale != 0){
					$transaction = Array();
					
					$transaction['merchantId'] = "1";
					
					$transactionDate = new Zend_Date($date, 'yyyy/MM/dd 00:00:00', 'en');
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
	
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					
					
					$transaction['amount'] = $sale;
					$transaction['commission'] = $sale;
					
						
					
					$totalTransactions[] = $transaction;
				}
			}
			
		
		}
			
		
		
		return $totalTransactions;
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
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
	
	private function call($apiUrl){
		
		// Initiate the REST call via curl
		$ch = curl_init($apiUrl);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0");
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		// Set the HTTP method to GET
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		// Don't return headers
		curl_setopt($ch, CURLOPT_HEADER, false);
		// Return data after call is made
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		// Execute the REST call
		$response = curl_exec($ch);
		
		$array = json_decode($response, true);
		// Close the connection
		curl_close($ch);
		return $array;
	}
}