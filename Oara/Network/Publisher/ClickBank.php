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
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_ClickBank
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_ClickBank extends Oara_Network {
	/**
	 * Api Key
	 * @var string
	 */
	private $_api = null;
	/**
	 * Dev Key
	 * @var string
	 */
	private $_dev = null;

	/**
	 * Merchant List
	 * @var array
	 */
	private $_merchantList = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Effiliation
	 */
	public function __construct($credentials) {

		$user = $credentials["user"];
		$password = $credentials["password"];
		$loginUrl = "https://".$user.".accounts.clickbank.com/account/login?";

		$valuesLogin = array(new Oara_Curl_Parameter('destination', "/account/mainMenu.htm"),
			new Oara_Curl_Parameter('nick', $user),
			new Oara_Curl_Parameter('pass', $password),
			new Oara_Curl_Parameter('login', "Log In"),
			new Oara_Curl_Parameter('rememberMe', "true"),
			new Oara_Curl_Parameter('j_username', $user),
			new Oara_Curl_Parameter('j_password', $password)
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$urls = array();
		$urls[] = new Oara_Curl_Request("https://".$user.".accounts.clickbank.com/account/profile.htm", array());
		$result = $this->_client->get($urls);
		if (preg_match_all("/(API-(.*)?)\s</", $result[0], $matches)) {
			$this->_api = $matches[1][0];
		}
		if (preg_match_all("/(DEV-(.*)?)</", $result[0], $matches)) {
			$this->_dev = $matches[1][0];
		}

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		if ($this->_api != null && $this->_dev != null) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();
		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "ClickBank";
		$obj['url'] = "www.clickbank.com";
		$merchants[] = $obj;
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
		$number = self::returnApiData("https://api.clickbank.com/rest/1.3/orders/count?startDate=".$dStartDate->toString("yyyy-MM-dd")."&endDate=".$dEndDate->toString("yyyy-MM-dd"));

		if ($number[0] != 0) {
			$transactionXMLList = self::returnApiData("https://api.clickbank.com/rest/1.3/orders/list?startDate=".$dStartDate->toString("yyyy-MM-dd")."&endDate=".$dEndDate->toString("yyyy-MM-dd"));
			foreach ($transactionXMLList as $transactionXML) {
				$transactionXML = simplexml_load_string($transactionXML, null, LIBXML_NOERROR | LIBXML_NOWARNING);

				foreach ($transactionXML->orderData as $singleTransaction) {

					$transaction = Array();
					$transaction['merchantId'] = 1;
					$transactionDate = new Zend_Date(self::findAttribute($singleTransaction, 'date'), 'yyyy-MM-ddTHH:mm:ss');
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
					unset($transactionDate);

					if (self::findAttribute($singleTransaction, 'affi') != null) {
						$transaction['custom_id'] = self::findAttribute($singleTransaction, 'affi');
					}

					$transaction['unique_id'] = self::findAttribute($singleTransaction, 'receipt');

					$transaction['amount'] = (double) $filter->filter(self::findAttribute($singleTransaction, 'amount'));
					$transaction['commission'] = (double) $filter->filter(self::findAttribute($singleTransaction, 'amount'));

					//if (self::findAttribute($singleTransaction, 'txnType') == 'RFND'){
					//	$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
					//} else {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					//}

					$totalTransactions[] = $transaction;
				}

			}

		}

		return $totalTransactions;
	}

	/**
	 *
	 * Api connection to ClickBank
	 * @param unknown_type $xmlLocation
	 * @throws Exception
	 */
	private function returnApiData($xmlLocation) {
		$dataArray = array();
		// Get the data
		$httpCode = 206;
		$page = 1;
		while ($httpCode != 200) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $xmlLocation);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Page: $page", "Accept: application/xml", "Authorization: ".$this->_dev.":".$this->_api));

			$dataArray[] = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($httpCode != 200 && $httpCode != 206) {
				throw new Exception("Couldn't connect to the API");
			}
			//Close Curl session
			curl_close($ch);
			$page++;
		}

		return $dataArray;

	}
	/**
	 * Cast the XMLSIMPLE object into string
	 * @param $object
	 * @param $attribute
	 * @return unknown_type
	 */
	private function findAttribute($object = null, $attribute = null) {
		$return = null;
		$return = trim($object->$attribute);
		return $return;
	}
}
