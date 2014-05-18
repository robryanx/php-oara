<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Af
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AffiliateFuture extends Oara_Network {
	/**
	 * Export Transaction Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;
	/**
	 * Export Overview Parameters
	 * @var array
	 */
	private $_exportOverviewParameters = null;

	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $af
	 * @return Oara_Network_Publisher_Af_Export
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];

		$loginUrl = 'http://affiliates.affiliatefuture.com/login.aspx?';

		$valuesLogin = array(
			new Oara_Curl_Parameter('username', $user),
			new Oara_Curl_Parameter('password', $password),
			new Oara_Curl_Parameter('Submit', 'Login Now')
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliates.affiliatefuture.com/login.aspx?', $valuesLogin);
		$this->_client->get($urls);

		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('username', $user),
			new Oara_Curl_Parameter('password', $password));

		$this->_exportOverviewParameters = array();

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliates.affiliatefuture.com/myaccount/invoices.aspx', array());

		$result = $this->_client->get($urls);
		if (!preg_match("/Logout/", $result[0], $matches)) {
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

		$merchantExportList = self::readMerchants();
		foreach ($merchantExportList as $merchant) {
			$obj = Array();
			$obj['cid'] = $merchant['cid'];
			$obj['name'] = $merchant['name'];
			$merchants[] = $obj;
		}
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$nowDate = new Zend_Date();

		$dStartDate = clone $dStartDate;
		$dStartDate->setLocale('en');
		$dStartDate->setHour("00");
		$dStartDate->setMinute("00");
		$dStartDate->setSecond("00");
		$dEndDate = clone $dEndDate;
		$dEndDate->setLocale('en');
		$dEndDate->setHour("23");
		$dEndDate->setMinute("59");
		$dEndDate->setSecond("59");
		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('startDate', $dStartDate->toString("dd-MMM-yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('endDate', $dEndDate->toString("dd-MMM-yyyy"));
		$transactions = Array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://ws-external.afnt.co.uk/apiv1/AFFILIATES/affiliatefuture.asmx/GetTransactionListbyDate?', $valuesFromExport);
		$urls[] = new Oara_Curl_Request('http://ws-external.afnt.co.uk/apiv1/AFFILIATES/affiliatefuture.asmx/GetCancelledTransactionListbyDate?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		for ($i = 0; $i < count($urls); $i++) {
			$xml = self::loadXml($exportReport[$i]);
			if (isset($xml->error)) {
				throw new Exception('Error connecting with the server');
			}
			if (isset($xml->TransactionList)) {
				foreach ($xml->TransactionList as $transaction) {
					$date = new Zend_Date(self::findAttribute($transaction, 'TransactionDate'), "yyyy-MM-ddTHH:mm:ss");

					if (in_array((int) self::findAttribute($transaction, 'ProgrammeID'), $merchantList) && $date->compare($dStartDate) >= 0 && $date->compare($dEndDate) <= 0) {

						$obj = Array();

						$obj['merchantId'] = self::findAttribute($transaction, 'ProgrammeID');
						$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
						if (self::findAttribute($transaction, 'TrackingReference') != null) {
							$obj['custom_id'] = self::findAttribute($transaction, 'TrackingReference');
						}
						$obj['unique_id'] = self::findAttribute($transaction, 'TransactionID');

						if ($i == 0) {
							if (Oara_Utilities::numberOfDaysBetweenTwoDates($date, $nowDate) > 5) {
								$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;
							} else {
								$obj['status'] = Oara_Utilities::STATUS_PENDING;
							}
						} else
							if ($i == 1) {
								$obj['status'] = Oara_Utilities::STATUS_DECLINED;
							}

						$obj['amount'] = self::findAttribute($transaction, 'SaleValue');
						$obj['commission'] = self::findAttribute($transaction, 'SaleCommission');
						$leadCommission = self::findAttribute($transaction, 'LeadCommission');
						if ($leadCommission != 0) {
							$obj['commission'] += $leadCommission;
						}

						$transactions[] = $obj;
					}
				}
			}
		}

		return $transactions;
	}

	/**
	 * Read the merchants in the table
	 * @return array
	 */
	public function readMerchants() {
		$merchantList = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliates.affiliatefuture.com/myprogrammes/default.aspx', array());
		$exportReport = $this->_client->get($urls);

		/*** load the html into the object ***/
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($exportReport[0]);
		$tableList = $doc->getElementsByTagName('table');

		$merchantTable = $tableList->item(16)->childNodes;
		for ($i = 1; $i < $merchantTable->length - 1; $i++) {
			$merchant = array();

			$registerLine = $merchantTable->item($i);
			$register = $registerLine->childNodes;
			$attributeName = trim($register->item(0)->nodeValue);
			$attributeUrl = $register->item(1)->childNodes->item(0)->childNodes->item(1)->getAttribute('href');
			$merchant['name'] = trim($attributeName);

			$parseUrl = parse_url($attributeUrl);
			$parameters = explode('&', $parseUrl['query']);
			$oaraCurlParameters = array();
			foreach ($parameters as $parameter) {
				$parameterValue = explode('=', $parameter);
				if ($parameterValue[0] == 'id') {
					$merchant['cid'] = $parameterValue[1];
				}
			}
			$merchantList[] = $merchant;
		}
		return $merchantList;
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
	/**
	 * Convert the string in xml object.
	 * @param $exportReport
	 * @return xml
	 */
	private function loadXml($exportReport = null) {
		$xml = simplexml_load_string($exportReport, null, LIBXML_NOERROR | LIBXML_NOWARNING);
		/**
		 if($xml == false){
		 throw new Exception('Problems in the XML');
		 }
		 */
		return $xml;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliates.affiliatefuture.com/myaccount/invoices.aspx', array());
		$exportReport = $this->_client->get($urls);

		/*** load the html into the object ***/
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($exportReport[0]);
		$tableList = $doc->getElementsByTagName('table');
		$registerTable = $tableList->item(12);
		if ($registerTable == null) {
			throw new Exception('Fail getting the payment History');
		}

		$registerLines = $registerTable->childNodes;
		for ($i = 1; $i < $registerLines->length ; $i++) {
			$registerLine = $registerLines->item($i)->childNodes;
			$obj = array();
			$date = new Zend_Date(trim($registerLine->item(1)->nodeValue), "dd/MM/yyyy");
			$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
			$obj['pid'] = trim($registerLine->item(0)->nodeValue);
			$value = trim(substr(trim($registerLine->item(4)->nodeValue), 4));
			$obj['value'] = $filter->filter($value);
			$obj['method'] = 'BACS';
			$paymentHistory[] = $obj;
		}

		return $paymentHistory;
	}

}
