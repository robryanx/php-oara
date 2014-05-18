<?php
/**
 * Api Class
 *
 * @author Carlos Morillo Merino
 * @category Oara_Network_Publisher_HasOffers
 * @copyright Fubra Limited
 * @version Release: 01.00
 *         
 *         
 */
class Oara_Network_Publisher_HasOffers extends Oara_Network {
	/**
	 * Client
	 */
	private $_domain = null;
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
		ini_set ( 'default_socket_timeout', '120' );
		
		$this->_domain = $credentials["domain"];
		$this->_apiPassword = $credentials["apiPassword"];
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		
		$apiURL = "http://api.hasoffers.com/v3/Affiliate_Offer.json?Method=findMyOffers&api_key={$this->_apiPassword}&NetworkId={$this->_domain}";
		$response = self::call($apiURL);
		if (count($response["response"]["errors"]) == 0){
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
		$apiURL = "http://api.hasoffers.com/v3/Affiliate_Offer.json?Method=findMyOffers&api_key={$this->_apiPassword}&NetworkId={$this->_domain}";
		$response = self::call($apiURL);
		
		foreach ($response["response"]["data"] as $merchant){
			
			$obj = Array ();
			$obj ['cid'] = $merchant["Offer"]["id"];
			$obj ['name'] = $merchant["Offer"]["name"];
			$merchants [] = $obj;
		}
		
		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array ();
		
		//fields[]=Stat.offer_id&fields[]=Stat.datetime&fields[]=Offer.name&fields[]=Stat.conversion_status&fields[]=Stat.payout&fields[]=Stat.conversion_sale_amount&fields[]=Stat.ip&fields[]=Stat.ad_id&fields[]=Stat.affiliate_info1&sort[Stat.datetime]=desc&filters[Stat.date][conditional]=BETWEEN&filters[Stat.date][values][]=2014-03-13&filters[Stat.date][values][]=2014-03-19&data_start=2014-03-13&data_end=2014-03-19
		
		
		$limit = 100;
		$page = 1;
		$loop = true;
		while ($loop){
		
			$apiURL = "http://api.hasoffers.com/v3/Affiliate_Report.json?limit=$limit&page=$page&Method=getConversions&api_key={$this->_apiPassword}&NetworkId={$this->_domain}&fields[]=Stat.offer_id&fields[]=Stat.datetime&fields[]=Offer.name&fields[]=Stat.conversion_status&fields[]=Stat.payout&fields[]=Stat.conversion_sale_amount&fields[]=Stat.ip&fields[]=Stat.ad_id&fields[]=Stat.affiliate_info1&sort[Stat.datetime]=desc&filters[Stat.date][conditional]=BETWEEN&filters[Stat.date][values][]={$dStartDate->toString("yyyy-MM-dd")}&filters[Stat.date][values][]={$dEndDate->toString("yyyy-MM-dd")}&data_start={$dStartDate->toString("yyyy-MM-dd")}&data_end={$dEndDate->toString("yyyy-MM-dd")}";
			
			$response = self::call($apiURL);
			foreach ($response["response"]["data"]["data"] as $transactionApi){
					
				$transaction = Array();
				$merchantId = (int) $transactionApi["Stat"]["offer_id"];
				
				if (in_array($merchantId, $merchantList)){
					$transaction['merchantId'] = $merchantId;
						
					$transactionDate = new Zend_Date($transactionApi["Stat"]["datetime"], 'yyyy-MM-dd HH:mm:ss', 'en');
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
						
					if ($transactionApi["Stat"]["ad_id"] != null) {
						$transaction['custom_id'] = $transactionApi["Stat"]["ad_id"];
					}
						
					if ($transactionApi["Stat"]["conversion_status"] == "approved") {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
					if ($transactionApi["Stat"]["conversion_status"] == "pending") {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
					if ($transactionApi["Stat"]["conversion_status"] == "rejected") {
						$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
					}
					$transaction['amount'] = $transactionApi["Stat"]["payout"];
					if (isset($transactionApi["Stat"]["conversion_sale_amount"])){
						$transaction['amount'] = $transactionApi["Stat"]["conversion_sale_amount"];
					}
					$transaction['commission'] = $transactionApi["Stat"]["payout"];
					$totalTransactions[] = $transaction;
					
				}
				
			}
			if ((int)$response["response"]["data"]["pageCount"] <= $page){
				$loop = false;
			}
			$page++;
				
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
	
	private function call($apiUrl){
		
		
		// Initiate the REST call via curl
		$ch = curl_init($apiUrl);
			
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