<?php
namespace Oara\Network\Publisher;
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
 * API Class
 *
 * @author Carlos Morillo Merino
 * @category Afiliant
 * @copyright Fubra Limited
 * @version Release: 01.00
 *
 */
class Viagogo extends \Oara\Network {

	/**
	 * Client
	 *
	 * @var unknown_type
	 */
	private $_client = null;

	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Viagogo
	 */
	public function __construct($credentials) {
		$user = $credentials ['user'];
		$password = $credentials ['password'];
		$loginUrl = 'https://www.viagogo.co.uk/secure/loginregister/login';
		
		$dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;
		
		if (! \Oara\Utilities::mkdir_recursive ( $dir, 0777 )) {
			throw new Exception ( 'Problem creating folder in Access' );
		}
		
		$cookies = $dir . $credentials["cookieName"] . '_cookies.txt';
		unlink($cookies);
		$valuesLogin = array (
		new \Oara\Curl\Parameter ( 'Login.UserName', $user ),
		new \Oara\Curl\Parameter ( 'Login.Password', $password ),
		new \Oara\Curl\Parameter ( 'ReturnUrl', $loginUrl )
		);
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
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "https://www.viagogo.co.uk/secure/loginregister/login" );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		$dom = new Zend_Dom_Query($html);
		$hidden = $dom->query('input[type="hidden"]');
		foreach ($hidden as $values) {
			$valuesLogin[] = new \Oara\Curl\Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "https://www.viagogo.co.uk/secure/loginregister/login" );
		$options [CURLOPT_POST] = true;
		$arg = array ();
		foreach ( $valuesLogin as $parameter ) {
			$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		$options [CURLOPT_POSTFIELDS] = implode ( '&', $arg );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;

		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, 'https://www.viagogo.co.uk/secure/AffiliateReports' );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );

		if (preg_match("/secure\/loginregister\/logout/", $html, $matches)) {
			$connection = true;
		}

		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array();

		$obj = Array();
		$obj['cid'] = 1;
		$obj['name'] = 'Viagogo';
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null) {
		$totalTransactions = array();

		$valuesFromExport = array(
		new \Oara\Curl\Parameter('ReportId', '2'),
		new \Oara\Curl\Parameter('Parameters[0].Name', 'ReportPeriod'),
		new \Oara\Curl\Parameter('Parameters[0].Values', 'True'),
		new \Oara\Curl\Parameter('Parameters[1].Name', 'DateFrom'),
		new \Oara\Curl\Parameter('Parameters[1].Values', $dStartDate->toString("dd/MM/yyyy")),
		new \Oara\Curl\Parameter('Parameters[2].Name', 'DateTo'),
		new \Oara\Curl\Parameter('Parameters[2].Values', $dEndDate->toString("dd/MM/yyyy")),
		new \Oara\Curl\Parameter('Parameters[3].Name', 'CurrencyCode'),
		new \Oara\Curl\Parameter('Parameters[3].Values', 'GBP'),
		new \Oara\Curl\Parameter('RenderType', 'CSV')
		);

		$rch = curl_init ();
		$options = $this->_options;

		curl_setopt ( $rch, CURLOPT_URL, "https://www.viagogo.co.uk/secure/AffiliateReports/RenderReport" );
		$options [CURLOPT_POST] = true;
		$arg = array ();
		foreach ( $valuesFromExport as $parameter ) {
			$arg [] = urlencode($parameter->getKey ()) . '=' . urlencode ( $parameter->getValue () );
		}
		$options [CURLOPT_POSTFIELDS] = implode ( '&', $arg );
		curl_setopt_array ( $rch, $options );

		$csv = curl_exec ( $rch );
		curl_close ( $rch );

		$exportData = str_getcsv($csv, "\n");
		$num = count($exportData);
		for ($i = 4; $i < $num - 1; $i++) {

			$transactionExportArray = str_getcsv($exportData[$i], ",");

			if (!isset($transactionExportArray[0])) {
				throw new Exception('Problem getting transaction\n\n');
			}
			

			$transaction = Array();
			$transaction['unique_id'] = $transactionExportArray[0];
			if ($transaction['unique_id'] != null){
				$transaction['merchantId'] = "1";
				$transactionDate = new \DateTime($transactionExportArray[1], "dd/MM/yyyy");
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				$transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
				$transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[4]);
				$transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[6]);
				$totalTransactions[] = $transaction;
				
			}
			
		}
		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Oara/Network/Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array ();

		return $paymentHistory;
	}

	/**
	 *
	 *
	 *
	 * It returns the transactions for a payment
	 *
	 * @param int $paymentId
	 */
	public function paymentTransactions($paymentId, $merchantList, $startDate) {
		$transactionList = array ();

		return $transactionList;
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
