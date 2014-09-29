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
 * @category   Oara_Network_Publisher_Skimlinks
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Skimlinks extends Oara_Network {
	/**
	 * Public API Key
	 * @var string
	 */
	private $_publicapikey = null;
	/**
	 * Private API Key
	 * @var string
	 */
	private $_privateapikey = null;

	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {

		$dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;

		if (! Oara_Utilities::mkdir_recursive ( $dir, 0777 )) {
			throw new Exception ( 'Problem creating folder in Access' );
		}

		$cookies = $dir . $credentials["cookieName"] . '_cookies.txt';
		unlink($cookies);
		$this->_options = array (
				CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FAILONERROR => true,
				CURLOPT_COOKIEJAR => $cookies,
				CURLOPT_COOKIEFILE => $cookies,
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_AUTOREFERER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HEADER => false,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPHEADER => array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Accept-Language: es,en-us;q=0.7,en;q=0.3','Accept-Encoding: gzip, deflate','Connection: keep-alive', 'Cache-Control: max-age=0'),
				CURLOPT_ENCODING => "gzip",
				CURLOPT_VERBOSE => false
		);

		$this->_publicapikey =  $credentials['user'];
		$this->_privateapikey = $credentials['apiPassword'];

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;

		try{
			self::getMerchantList();
			$connection = true;
		} catch (Exception $e){

		}

		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {

		$publicapikey = $this->_publicapikey;
		$privateapikey = $this->_privateapikey;

		$date = new DateTime();
		$timestamp = $date->getTimestamp();
		$authtoken = md5( $timestamp . $privateapikey );
		$date = Zend_Date::now();

		$merchants = Array ();

		$valuesFromExport = array(
				new Oara_Curl_Parameter('version', '0.5'),
				new Oara_Curl_Parameter('timestamp', $timestamp),
				new Oara_Curl_Parameter('apikey', $publicapikey),
				new Oara_Curl_Parameter('authtoken', $authtoken),
				new Oara_Curl_Parameter('startdate', '2009-01-01'), //minimum date
				new Oara_Curl_Parameter('enddate', $date->toString("yyyy-MM-dd")),
				new Oara_Curl_Parameter('format', 'json')
		);

		$rch = curl_init ();
		$options = $this->_options;
		$arg = array ();
		foreach ( $valuesFromExport as $parameter ) {
			$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		curl_setopt ( $rch, CURLOPT_URL, 'https://api-reports.skimlinks.com/publisher/reportmerchants?'.implode ( '&', $arg ) );

		curl_setopt_array ( $rch, $options );
		$json = curl_exec ( $rch );
		curl_close ( $rch );

		$jsonArray = json_decode($json, true);

		$iteration = 0;
		while (count($jsonArray["skimlinksAccount"]["merchants"]) != 0){

			foreach ($jsonArray["skimlinksAccount"]["merchants"] as $i){
				$obj = Array();
				$obj['cid']  = $i["merchantID"];
				$obj['name'] = $i["merchantName"];
				$merchants[] = $obj;
			}

			$iteration++;

			$valuesFromExport = array(
					new Oara_Curl_Parameter('version', '0.5'),
					new Oara_Curl_Parameter('timestamp', $timestamp),
					new Oara_Curl_Parameter('apikey', $publicapikey),
					new Oara_Curl_Parameter('authtoken', $authtoken),
					new Oara_Curl_Parameter('startdate', '2009-01-01'), //minimum date
					new Oara_Curl_Parameter('enddate', $date->toString("yyyy-MM-dd")),
					new Oara_Curl_Parameter('format', 'json'),
					new Oara_Curl_Parameter('responseFrom', $iteration*100),

			);

			$rch = curl_init ();
			$options = $this->_options;
			$arg = array ();
			foreach ( $valuesFromExport as $parameter ) {
				$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
			}
			curl_setopt ( $rch, CURLOPT_URL, 'https://api-reports.skimlinks.com/publisher/reportmerchants?'.implode ( '&', $arg ) );

			curl_setopt_array ( $rch, $options );
			$json = curl_exec ( $rch );
			curl_close ( $rch );

			$jsonArray = json_decode($json, true);


		}



		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();

		$publicapikey = $this->_publicapikey;
		$privateapikey = $this->_privateapikey;

		$date = new DateTime();
		$timestamp = $date->getTimestamp();
		$authtoken = md5( $timestamp . $privateapikey );
		$date = Zend_Date::now();

		$valuesFromExport = array(
				new Oara_Curl_Parameter('version', '0.5'),
				new Oara_Curl_Parameter('timestamp', $timestamp),
				new Oara_Curl_Parameter('apikey', $publicapikey),
				new Oara_Curl_Parameter('authtoken', $authtoken),
				new Oara_Curl_Parameter('startDate', $dStartDate->toString("yyyy-MM-dd")),
				new Oara_Curl_Parameter('endDate', $dEndDate->toString("yyyy-MM-dd")),
				new Oara_Curl_Parameter('format', 'json')
		);

		$rch = curl_init ();
		$options = $this->_options;
		$arg = array ();
		foreach ( $valuesFromExport as $parameter ) {
			$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		curl_setopt ( $rch, CURLOPT_URL, 'https://api-report.skimlinks.com/publisher/reportcommissions?'.implode ( '&', $arg ) );

		curl_setopt_array ( $rch, $options );
		$json = curl_exec ( $rch );
		curl_close ( $rch );

		$jsonArray = json_decode($json, true);

		foreach ($jsonArray["skimlinksAccount"]["commissions"] as $i){
			$transaction = Array();

			$transaction['merchantId'] = $i["merchantID"];
			$transaction['unique_id'] =  $i["commissionID"];
			$transaction['date'] = $i["date"];
			$transaction['amount'] = (double)$i["orderValue"]/100;
			$transaction['commission'] = (double)$i["commissionValue"]/100;
			$transactionStatus = $i["status"];
			if ($transactionStatus == "active") {
				$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;
			} else if ($transactionStatus == "cancelled") {
				$transaction ['status'] = Oara_Utilities::STATUS_DECLINED;
			} else {
				throw new Exception ( "New status found {$transactionStatus}" );
			}

			$totalTransactions[] = $transaction;
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
