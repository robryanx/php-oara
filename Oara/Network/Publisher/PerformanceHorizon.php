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
class Oara_Network_Publisher_PerformanceHorizon extends Oara_Network {

	private $_pass = null;

	private $_publisherList = null;
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
		$result = file_get_contents("https://{$this->_pass}@api.performancehorizon.com/user/publisher.json");
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
		$result = file_get_contents("https://{$this->_pass}@api.performancehorizon.com/user/account.json");
		$publisherList = json_decode($result, true);
		foreach ($publisherList["user_accounts"] as $publisher){
			if (isset($publisher["publisher"])){
				$publisher = $publisher["publisher"];
				$this->_publisherList[$publisher["publisher_id"]] = $publisher["account_name"];
			}
		}

		foreach ($this->_publisherList as $id => $name){
			$url = "https://{$this->_pass}@api.performancehorizon.com/user/publisher/$id/campaign/a.json";
			$result = file_get_contents($url);
			$merchantList = json_decode($result, true);
			foreach ($merchantList["campaigns"] as $merchant){
				$merchant = $merchant["campaign"];
				$obj = Array();
				$obj['cid'] = $merchant["campaign_id"];
				$obj['name'] = $merchant["title"];
				$merchants[] = $obj;
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


		foreach ($this->_publisherList as $publisherId => $publisherName){
			$page = 0;
			$import = true;
			while ($import){

				$offset = ($page*300);

				$url = "https://{$this->_pass}@api.performancehorizon.com/reporting/report_publisher/publisher/$publisherId/conversion.json?";
				$url .= "status=approved|mixed|pending|rejected";
				$url .= "&start_date=".urlencode($dStartDate->toString("yyyy-MM-dd HH:mm"));
				$url .= "&end_date=".urlencode($dEndDate->toString("yyyy-MM-dd HH:mm"));
				$url .= "&offset=".$offset;

				$result = file_get_contents($url);
				$conversionList = json_decode($result, true);

				foreach ($conversionList["conversions"] as $conversion){
					$conversion = $conversion["conversion_data"];
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
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalOverviews = Array();
		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
		foreach ($transactionArray as $merchantId => $merchantTransaction) {
			foreach ($merchantTransaction as $date => $transactionList) {

				$overview = Array();

				$overview['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date($date, "yyyy-MM-dd");
				$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				$overview['click_number'] = 0;
				$overview['impression_number'] = 0;
				$overview['transaction_number'] = 0;
				$overview['transaction_confirmed_value'] = 0;
				$overview['transaction_confirmed_commission'] = 0;
				$overview['transaction_pending_value'] = 0;
				$overview['transaction_pending_commission'] = 0;
				$overview['transaction_declined_value'] = 0;
				$overview['transaction_declined_commission'] = 0;
				$overview['transaction_paid_value'] = 0;
				$overview['transaction_paid_commission'] = 0;
				foreach ($transactionList as $transaction) {
					$overview['transaction_number']++;
					if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED) {
						$overview['transaction_confirmed_value'] += $transaction['amount'];
						$overview['transaction_confirmed_commission'] += $transaction['commission'];
					} else
					if ($transaction['status'] == Oara_Utilities::STATUS_PENDING) {
						$overview['transaction_pending_value'] += $transaction['amount'];
						$overview['transaction_pending_commission'] += $transaction['commission'];
					} else
					if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED) {
						$overview['transaction_declined_value'] += $transaction['amount'];
						$overview['transaction_declined_commission'] += $transaction['commission'];
					} else
					if ($transaction['status'] == Oara_Utilities::STATUS_PAID) {
						$overview['transaction_paid_value'] += $transaction['amount'];
						$overview['transaction_paid_commission'] += $transaction['commission'];
					}
				}
				$totalOverviews[] = $overview;
			}
		}

		return $totalOverviews;
	}


	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		foreach ($this->_publisherList as $publisherId => $publisherName){
			$page = 0;
			$import = true;
			while ($import){

				$offset = ($page*300);

				$url = "https://{$this->_pass}@api.performancehorizon.com/user/publisher/$publisherId/selfbill.json?";
				$url .= "&offset=".$offset;
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

				if (((int)$paymentList["count"]) < $offset){
					$import = false;
				}
				$page++;

			}
		}

		return $paymentHistory;
	}

}
