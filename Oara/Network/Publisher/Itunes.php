<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2014  Fubra Limited
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.
 You should have received a copy of the GNU Affero General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 Contact
 ------------
 Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
 **/
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_PerformanceHorizon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Itunes extends Oara_Network {

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
		$result = file_get_contents("https://{$this->_pass}@itunes-api.performancehorizon.com/user/publisher.json");
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
		$result = file_get_contents("https://{$this->_pass}@itunes-api.performancehorizon.com/user/account.json");
		$publisherList = json_decode($result, true);
		foreach ($publisherList["user_accounts"] as $publisher){
			if (isset($publisher["publisher"])){
				$publisher = $publisher["publisher"];
				$this->_publisherList[$publisher["publisher_id"]] = $publisher["account_name"];
			}
		}

		foreach ($this->_publisherList as $id => $name){
			$url = "https://{$this->_pass}@itunes-api.performancehorizon.com/user/publisher/$id/campaign/a.json";
			$result = file_get_contents($url);
			$merchantList = json_decode($result, true);
			foreach ($merchantList["campaigns"] as $merchant){
				$merchant = $merchant["campaign"];
				$obj = Array();
				$obj['cid'] = str_replace("l", "", $merchant["campaign_id"]);
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
	public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null, $merchantMap = null) {
		$transactions = array();


		foreach ($this->_publisherList as $publisherId => $publisherName){
			$page = 0;
			$import = true;
			while ($import){

				$offset = ($page*300);

				$url = "https://{$this->_pass}@itunes-api.performancehorizon.com/reporting/report_publisher/publisher/$publisherId/conversion.json?";
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
						$transactionDate = new \DateTime($conversion["conversion_time"], 'yyyy-MM-dd HH:mm:ss');
						$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

						if ($conversion["publisher_reference"] != null) {
							$transaction['custom_id'] = $conversion["publisher_reference"];
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
			$url = "https://{$this->_pass}@itunes-api.performancehorizon.com/user/publisher/$publisherId/selfbill.json?";
			$result = file_get_contents($url);
			$paymentList = json_decode($result, true);

			foreach ($paymentList["selfbills"] as $selfbill){
				$selfbill = $selfbill["selfbill"];
				$obj = array();
				$date = new \DateTime($selfbill["payment_date"], "yyyy-MM-dd HH:mm:ss");
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
