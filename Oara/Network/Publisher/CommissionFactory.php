<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_CommissionFactory
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_CommissionFactory extends Oara_Network {
	
	/**
	 * Client
	 * 
	 * @var unknown_type
	 */
	private $_apiKey = null;
	/**
	 * Constructor and Login
	 * 
	 * @param
	 *        	$af
	 * @return Oara_Network_Publisher_Af_Export
	 */
	public function __construct($credentials) {
		$this->_apiKey = $credentials ['apiPassword'];
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		// If not login properly the construct launch an exception
		$result = self::request ( "https://api.commissionfactory.com.au/V1/Affiliate/Merchants?apiKey={$this->_apiKey}&status=Joined" );
		if (count ( $result ) > 0) {
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
		$merchants = Array ();
		
		$merchantExportList = self::request ( "https://api.commissionfactory.com.au/V1/Affiliate/Merchants?apiKey={$this->_apiKey}&status=Joined" );
		foreach ( $merchantExportList as $merchant ) {
			$obj = Array ();
			$obj ['cid'] = $merchant ['Id'];
			$obj ['name'] = $merchant ['Name'];
			$merchants [] = $obj;
		}
		return $merchants;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$transactions = array();
		$transactionsExportList = self::request ( "https://api.commissionfactory.com.au/V1/Affiliate/Transactions?apiKey={$this->_apiKey}&fromDate={$dStartDate->toString("yyyy-MM-dd")}&toDate={$dEndDate->toString("yyyy-MM-dd")}" );
		
		foreach ( $transactionsExportList as $transaction ) {
			
			if (in_array ( ( int ) $transaction ["MerchantId"], $merchantList )) {
				
				$obj = Array ();
				
				$obj ['merchantId'] = $transaction ["MerchantId"];
				
				$date = new Zend_Date ( $transaction ["DateCreated"], "yyyy-MM-ddTHH:mm:ss" );
				$obj ['date'] = $date->toString ( "yyyy-MM-dd HH:mm:ss" );
				
				if ($transaction ["UniqueId"] != null) {
					$obj ['custom_id'] = $transaction ["UniqueId"];
				}
				$obj ['unique_id'] = $transaction ["Id"];
				
				if ($transaction ["Status"] == "Approved") {
					$obj ['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else if ($transaction ["Status"] == "Pending") {
					$obj ['status'] = Oara_Utilities::STATUS_PENDING;
				} else if ($transaction ["Status"] == "Void") {
					$obj ['status'] = Oara_Utilities::STATUS_DECLINED;
				}
				
				$obj ['amount'] = $transaction ["SaleValue"];
				$obj ['commission'] = $transaction ["Commission"];
				
				$transactions [] = $obj;
			}
		}
		
		return $transactions;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalOverviews = Array ();
		$transactionArray = Oara_Utilities::transactionMapPerDay ( $transactionList );
		foreach ( $transactionArray as $merchantId => $merchantTransaction ) {
			foreach ( $merchantTransaction as $date => $transactionList ) {
				
				$overview = Array ();
				
				$overview ['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date ( $date, "yyyy-MM-dd" );
				$overview ['date'] = $overviewDate->toString ( "yyyy-MM-dd HH:mm:ss" );
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
				$totalOverviews [] = $overview;
			}
		}
		
		return $totalOverviews;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array ();
		
		$today = new Zend_Date();
		$paymentExportList = self::request ( "https://api.commissionfactory.com.au/V1/Affiliate/Payments?apiKey={$this->_apiKey}&fromDate=2000-01-01&toDate={$today->toString("yyyy-MM-dd")}" );
	
		foreach ($paymentExportList as  $payment) {
			$obj = array ();
			$date = new Zend_Date ( $payment["DateCreated"], "yyyy-MM-ddTHH:mm:ss" );
			$obj ['date'] = $date->toString ( "yyyy-MM-dd HH:mm:ss" );
			$obj ['pid'] = $payment["Id"];
			$obj ['value'] = $payment["Amount"];
			$obj ['method'] = 'BACS';
			$paymentHistory [] = $obj;
		}
		
		return $paymentHistory;
	}
	public function request($url) {
		$options = array (
				CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FAILONERROR => true,
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_AUTOREFERER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_FOLLOWLOCATION => true 
		);
		$rch = curl_init ();
		curl_setopt ( $rch, CURLOPT_URL, $url );
		curl_setopt_array ( $rch, $options );
		$response = curl_exec ( $rch );
		curl_close ( $rch );
		return json_decode ( $response, true );
	}
}
