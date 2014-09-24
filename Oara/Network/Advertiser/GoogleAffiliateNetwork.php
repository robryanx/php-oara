<?php
/**
   The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set 
   of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
   
    Copyright (C) 2014  Carlos Morillo Merino
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	and we should add some contact information
**/	
require_once "../Publisher/GoogleApiClient/src/apiClient.php";
require_once "../Publisher/GoogleApiClient/src/contrib/apiGanService.php";
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Advertiser_GoogleAffiliateNetwork
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Advertiser_GoogleAffiliateNetwork extends Oara_Network {

	/**
	 * Affiliate Network Client
	 * @var unknown_type
	 */
	private $_gan = null;
	/**
	 * 
	 * Advertiser Id
	 * @var unknown_type
	 */
	private $_advertiserId = null;
	/**
	 * Constructor and Login
	 * @param $buy
	 * @return Oara_Network_Publisher_Buy_Api
	 */
	public function __construct($credentials) {
		$client = new apiClient();
		$client->setApplicationName("AffJet");
		$client->setClientId($credentials['clientId']);
		$client->setClientSecret($credentials['clientSecret']);
		$client->setAccessToken($credentials['oauth2']);
		$client->setAccessType('offline');
		$this->_client = $client;
		$this->_gan = new apiGanService($client);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		if ($this->_client->getAccessToken()) {
			$advertiser = $this->_gan->advertisers->get("advertisers");
			$this->_advertiserId = $advertiser->id;
			
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
		$publishers = $this->_gan->publishers->listPublishers("advertisers", $this->_advertiserId);
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
		
		$params = array();
		$params["eventDateMin"] = $dStartDate->toString("dd-MM-yyyy");
		$params["eventDateMax"] = $dEndDate->toString("dd-MM-yyyy");	
		$params["maxResults"] = 100;
		$params["pageToken"] = null;
		
		$events = $this->_gan->event->listEvents("advertisers", $this->_advertiserId, $params);
		while ($events->nextPageToken != null){
			
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
