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
		$dom = new Zend_Dom_Query ( $exportReport[0] );
		$results = $dom->query ( '#DataGrid1' );
		
		$merchantCsv = self::htmlToCsv(self::DOMinnerHTML($results->current()));
		
		for ($i = 1; $i < count($merchantCsv)-1; $i++) {
			$merchant = array();
			$merchantLine = str_getcsv( $merchantCsv[$i],";");
			$merchant['name'] = $merchantLine[0];

			$parseUrl = parse_url($merchantLine[2]);
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
	
	/**
	 *
	 *
	 * Function that Convert from a table to Csv
	 *
	 * @param unknown_type $html
	 */
	private function htmlToCsv($html) {
		$html = str_replace ( array (
				"\t",
				"\r",
				"\n"
		), "", $html );
		$csv = "";
		$dom = new Zend_Dom_Query ( $html );
		$results = $dom->query ( 'tr' );
		$count = count ( $results ); // get number of matches: 4
		foreach ( $results as $result ) {
				
			$domTd = new Zend_Dom_Query ( self::DOMinnerHTML($result) );
			$resultsTd = $domTd->query ( 'td' );
			$countTd = count ( $resultsTd );
			$i = 0;
			foreach( $resultsTd as $resultTd) {
				$value = $resultTd->nodeValue;
				
				$domLink = new Zend_Dom_Query ( self::DOMinnerHTML($resultTd) );
				$resultsA = $domLink->query ( 'a' );
				foreach( $resultsA as $resultA) {
					$value = $resultA->getAttribute("href");
				}
				
				if ($i != $countTd - 1) {
					$csv .= trim ( $value ) . ";,";
				} else {
					$csv .= trim ( $value );
				}
				$i++;
			}
			$csv .= "\n";
		}
		$exportData = str_getcsv ( $csv, "\n" );
		return $exportData;
	}
	/**
	 *
	 *
	 * Function that returns the innet HTML code
	 *
	 * @param unknown_type $element
	 */
	private function DOMinnerHTML($element) {
		$innerHTML = "";
		$children = $element->childNodes;
		foreach ( $children as $child ) {
			$tmp_dom = new DOMDocument ();
			$tmp_dom->appendChild ( $tmp_dom->importNode ( $child, true ) );
			$innerHTML .= trim ( $tmp_dom->saveHTML () );
		}
		return $innerHTML;
	}

}
