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
require_once "GoogleApiClient/src/Google_Client.php";
require_once "GoogleApiClient/src/contrib/Google_AdSenseService.php";
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_As
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AdSense extends Oara_Network {

	/**
	 * Adsense Client
	 * @var unknown_type
	 */
	private $_adsense = null;
	/**
	 * Constructor and Login
	 * @param $buy
	 * @return Oara_Network_Publisher_Buy_Api
	 */
	public function __construct($credentials) {
		$client = new Google_Client();
		$client->setApplicationName("AffJet");
		$client->setClientId($credentials['clientId']);
		$client->setClientSecret($credentials['clientSecret']);
		$client->setAccessToken($credentials['oauth2']);
		$client->setAccessType('offline');
		$this->_client = $client;
		$this->_adsense = new Google_AdSenseService($client);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		if ($this->_client->getAccessToken()) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array();

		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Google AdSense";
		$obj['url'] = "www.google.com";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		
		$report = $this->_adsense->reports->generate($dStartDate->toString("YYYY-MM-dd"), $dEndDate->toString("YYYY-MM-dd"), array("dimension" => "DATE", "metric" => array("PAGE_VIEWS", "CLICKS", "EARNINGS"), "sort" => "DATE"));
		
		$firstDayMonth = new Zend_Date();
		$firstDayMonth->setDay(1);
		$firstDayMonth->setHour("00");
		$firstDayMonth->setMinute("00");
		$firstDayMonth->setSecond("00");
		if (isset($report["rows"])){
			foreach ($report["rows"] as $row) {
				$obj = array();
				$obj['merchantId'] = 1;
				$tDate = new Zend_Date($row[0], "yyyy-MM-dd");
				$tDate->setHour("00");
				$tDate->setMinute("00");
				$tDate->setSecond("00");
				$obj['date'] = $tDate->toString("yyyy-MM-dd HH:mm:ss");
		
		
				$obj['impression_number'] = (int) Oara_Utilities::parseDouble($row[1]);
				$obj['click_number'] = Oara_Utilities::parseDouble($row[2]);
				if ($firstDayMonth->compare($tDate) <= 0) {
					$obj['amount'] = Oara_Utilities::parseDouble($row[3]);
					$obj['commission'] = Oara_Utilities::parseDouble($row[3]);
					$obj['status'] = Oara_Utilities::STATUS_PENDING;
				} else {
					$obj['amount'] = Oara_Utilities::parseDouble($row[3]);
					$obj['commission'] = Oara_Utilities::parseDouble($row[3]);
					$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;
				}
				
				$totalTransactions[] = $obj;
			}
		
		}
		
		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		return $paymentHistory;
	}
}
