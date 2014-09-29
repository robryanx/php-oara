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

 Contact
 ------------
 Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
 **/
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_AffiliatesUnited
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AffiliatesUnited extends Oara_Network {
	
	/**
	 * Merchants by name
	 */
	private $_merchantMap = array();
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];

		$valuesLogin = array(
		new Oara_Curl_Parameter('us', $user),
		new Oara_Curl_Parameter('pa', $password)
		);

		$loginUrl = 'https://affiliates.affutd.com/affiliates/Login.aspx';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://affiliates.affutd.com/affiliates/Dashboard.aspx', array());
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('.lnkLogOut');
		if (count($results) > 0) {
			$connection = true;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array()) {
		$merchants = array();

		
		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Affiliates United";
		$merchants[] = $obj;
		

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$valuesFromExport = array();
		$valuesFromExport[] = new Oara_Curl_Parameter('ctl00$cphPage$reportFrom', $dStartDate->toString("yyyy-MM-dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('ctl00$cphPage$reportTo', $dEndDate->toString("yyyy-MM-dd"));
	
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://affiliates.affutd.com/affiliates/DataServiceWrapper/DataService.svc/Export/CSV/Affiliates_Reports_GeneralStats_DailyFigures', $valuesFromExport);
		$exportReport = $this->_client->post($urls);
		$exportData = str_getcsv($exportReport[0], "\n");
		$num = count($exportData);
		for ($i = 2; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ",");

			$transaction = Array();
			$transaction['merchantId'] = 1;
			$transactionDate = new Zend_Date($transactionExportArray[0], 'dd-MM-yyyy', 'en');
			$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

			$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				
			$transaction['amount'] = $transactionExportArray[12];
			$transaction['commission'] = $transactionExportArray[13];
			$totalTransactions[] = $transaction;
		}

		return $totalTransactions;
	}

}
