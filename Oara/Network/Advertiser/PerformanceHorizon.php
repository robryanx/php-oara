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

	private $_advertiserList = null;
	/**
	 * Constructor and Login
	 * @param $af
	 * @return Oara_Network_Publisher_Af_Export
	 */
	public function __construct($credentials) {
		$this->_pass = $credentials['apiPassword'];

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$result = file_get_contents("https://{$this->_pass}@api.performancehorizon.com/campaign.json");
		if ($result == false){
			$connection = false;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		
		$merchants = Array();
		$result = file_get_contents("https://{$this->_pass}@api.performancehorizon.com/campaign.json");
		$advertiserList = json_decode($result, true);
		foreach ($advertiserList["campaigns"] as $advertiser){
			if (isset($advertiser["campaign"])){
				$advertiser = $advertiser["campaign"];
				$obj = Array();
				$obj['cid'] = str_replace("l", "", $advertiser["campaign_id"]);
				$obj['name'] = $advertiser["title"];
				$merchants[] = $obj;
				$this->_advertiserList[$advertiser["campaign_id"]] = $obj['name'];
				
			}
			
		}
	

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$transactions = array();

		foreach ($this->_advertiserList as $campaignId => $campaignName){
			$page = 0;
			$import = true;
			while ($import){

				$offset = ($page*300);
				$url = "https://{$this->_pass}@api.performancehorizon.com/reporting/report_advertiser/campaign/$campaignId/conversion.json?";
				$url .= "status=approved|mixed|pending|rejected";
				$url .= "&start_date=".urlencode($dStartDate->toString("yyyy-MM-dd HH:mm"));
				$url .= "&end_date=".urlencode($dEndDate->toString("yyyy-MM-dd HH:mm"));
				$url .= "&offset=".$offset;

				$result = file_get_contents($url);
				$conversionList = json_decode($result, true);

				foreach ($conversionList["conversions"] as $conversion){
					$conversion = $conversion["conversion_data"];
					$conversion["campaign_id"] = str_replace("l", "", $conversion["campaign_id"]);
					if (in_array($conversion["campaign_id"], $merchantList)){
						$transaction = Array();
						$transaction['unique_id'] = $conversion["conversion_id"];
						$transaction['merchantId'] = $conversion["campaign_id"];
						$transactionDate = new Zend_Date($conversion["conversion_time"], 'yyyy-MM-dd HH:mm:ss');
						$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

						if ($conversion["conversion_reference"] != null) {
							$transaction['custom_id'] = $conversion["conversion_reference"];
						}

						if ($conversion["conversion_value"]["conversion_status"] == 'approved') {
							$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
						} else
						if ($conversion["conversion_value"]["conversion_status"] == 'pending' || $conversion["conversion_value"]["conversion_status"] == 'mixed') {
							$transaction['status'] = Oara_Utilities::STATUS_PENDING;
						} else
						if ($conversion["conversion_value"]["conversion_status"] == 'rejected') {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						}

						$transaction['amount'] = $conversion["conversion_value"]["value"];
						$transaction['currency'] = $conversion["currency"];

						$transaction['commission'] = $conversion["conversion_value"]["publisher_commission"];
						$transactions[] = $transaction;
					}
				}


				if (((int)$conversionList["count"]) < $offset){
					$import = false;
				}
				$page++;

			}
		}

		return $transactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		foreach ($this->_publisherList as $publisherId => $publisherName){
			$url = "https://{$this->_pass}@api.performancehorizon.com/user/publisher/$publisherId/selfbill.json?";
			$result = file_get_contents($url);
			$paymentList = json_decode($result, true);

			foreach ($paymentList["selfbills"] as $selfbill){
				$selfbill = $selfbill["selfbill"];
				$obj = array();
				$date = new Zend_Date($selfbill["payment_date"], "yyyy-MM-dd HH:mm:ss");
				$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$obj['pid'] = intval($selfbill["publisher_self_bill_id"]);
				$obj['value'] = $selfbill["total_value"];
				$obj['method'] = "BACS";
				$paymentHistory[] = $obj;
			}

		}

		return $paymentHistory;
	}

}
