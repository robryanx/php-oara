<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_PerformanceHorizon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Advertiser_PerformanceHorizon extends Oara_Network {
	private $_pass = null;
	private $_currency = null;
	private $_advertiserList = null;
	/**
	 * Constructor and Login
	 * 
	 * @param
	 *        	$af
	 * @return Oara_Network_Publisher_Af_Export
	 */
	public function __construct($credentials) {
		$this->_pass = $credentials ['apiPassword'];
		$this->_currency = $credentials ['currency'];
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		// If not login properly the construct launch an exception
		$connection = true;
		$result = file_get_contents ( "https://{$this->_pass}@api.performancehorizon.com/campaign.json" );
		if ($result == false) {
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
		$merchants = Array ();
		$result = file_get_contents ( "https://{$this->_pass}@api.performancehorizon.com/campaign.json" );
		$advertiserList = json_decode ( $result, true );
		foreach ( $advertiserList ["campaigns"] as $advertiser ) {
			if (isset ( $advertiser ["campaign"] )) {
				$advertiser = $advertiser ["campaign"];
				$obj = Array ();
				$obj ['cid'] = str_replace ( "l", "", $advertiser ["campaign_id"] );
				$obj ['name'] = $advertiser ["title"];
				$merchants [] = $obj;
				$this->_advertiserList [$advertiser ["campaign_id"]] = $obj ['name'];
			}
		}
		
		return $merchants;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$transactions = array ();
		
		foreach ( $this->_advertiserList as $campaignId => $campaignName ) {
			
			$url = "https://{$this->_pass}@api.performancehorizon.com/reporting/export/export/conversion.csv?";
			$url .= "statuses[]=approved&statuses[]=mixed&statuses[]=pending&statuses[]=rejected";
			$url .= "&start_date=" . urlencode ( $dStartDate->toString ( "yyyy-MM-dd HH:mm" ) );
			$url .= "&end_date=" . urlencode ( $dEndDate->toString ( "yyyy-MM-dd HH:mm" ) );
			$url .= "&campaign_id=" . urlencode ( $campaignId );
			$url .= "&convert_currency=" . $this->_currency;
			
			$result = self::makeCall ( $url );
			$resultList = explode ( "\n", $result );
			
			$lineCounter = count ( $resultList );
			if ($lineCounter > 0) {
				for($i = 1; $i < $lineCounter -1; $i ++) {
					$conversion = str_getcsv ( $resultList [$i], "," );
					$conversion [1] = str_replace ( "l", "", $conversion [1] );
					if (in_array ( $conversion [1], $merchantList )) {
						$transaction = Array ();
						$transaction ['unique_id'] = $conversion [0];
						$transaction ['merchantId'] = $conversion [1];
						$transactionDate = new Zend_Date ( $conversion [4], 'yyyy-MM-dd HH:mm:ss' );
						$transaction ['date'] = $transactionDate->toString ( "yyyy-MM-dd HH:mm:ss" );
						
						if ($conversion [10] != null) {
							$transaction ['custom_id'] = $conversion [10];
						}
						
						if ($conversion [15] == 'approved') {
							$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;
						} else if ($conversion [15] == 'pending' || $conversion [15] == 'mixed') {
							$transaction ['status'] = Oara_Utilities::STATUS_PENDING;
						} else if ($conversion [15] == 'rejected') {
							$transaction ['status'] = Oara_Utilities::STATUS_DECLINED;
						}
						
						$transaction ['amount'] = $conversion [17];
						
						$transaction ['commission'] = $conversion [18];
						$transactions [] = $transaction;
					}
				}
			}
		}
		
		return $transactions;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array ();
		
		foreach ( $this->_publisherList as $publisherId => $publisherName ) {
			$url = "https://{$this->_pass}@api.performancehorizon.com/user/publisher/$publisherId/selfbill.json?";
			$result = file_get_contents ( $url );
			$paymentList = json_decode ( $result, true );
			
			foreach ( $paymentList ["selfbills"] as $selfbill ) {
				$selfbill = $selfbill ["selfbill"];
				$obj = array ();
				$date = new Zend_Date ( $selfbill ["payment_date"], "yyyy-MM-dd HH:mm:ss" );
				$obj ['date'] = $date->toString ( "yyyy-MM-dd HH:mm:ss" );
				$obj ['pid'] = intval ( $selfbill ["publisher_self_bill_id"] );
				$obj ['value'] = $selfbill ["total_value"];
				$obj ['method'] = "BACS";
				$paymentHistory [] = $obj;
			}
		}
		
		return $paymentHistory;
	}
	
	/**
	 *
	 * Make the call for this API
	 * @param string $actionVerb
	 */
	private function makeCall($url){
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$returnResult = curl_exec($ch);
		curl_close($ch);
		return $returnResult;
	}
}
