<?php
namespace Oara\Network\Advertiser;
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
  
 Copyright (C) 2016  Fubra Limited
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
 * @category   PerformanceHorizon
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class PerformanceHorizon extends \Oara\Network {
	private $_pass = null;
	private $_currency = null;
	private $_advertiserList = null;
	/**
	 * Constructor and Login
	 * 
	 * @param
	 *        	$af
	 * @return Af_Export
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
	 * @see library/Oara/Network/Interface#getMerchantList()
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
	 * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null) {
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
						$transactionDate = new \DateTime ( $conversion [4], 'yyyy-MM-dd HH:mm:ss' );
						$transaction ['date'] = $transactionDate->toString ( "yyyy-MM-dd HH:mm:ss" );
						
						if ($conversion [10] != null) {
							$transaction ['custom_id'] = $conversion [10];
						}
						
						if ($conversion [15] == 'approved') {
							$transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
						} else if ($conversion [15] == 'pending' || $conversion [15] == 'mixed') {
							$transaction ['status'] = \Oara\Utilities::STATUS_PENDING;
						} else if ($conversion [15] == 'rejected') {
							$transaction ['status'] = \Oara\Utilities::STATUS_DECLINED;
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
	 * @see Oara/Network/Base#getPaymentHistory()
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
				$date = new \DateTime ( $selfbill ["payment_date"], "yyyy-MM-dd HH:mm:ss" );
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
