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
 * @category   Oara_Network_Publisher_SportCoverDirect
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_SportCoverDirect extends Oara_Network {
	/**
	 * Export client.
	 * @var Oara_Curl_Access
	 */
	private $_client = null;

	/**
	 * Constructor and Login
	 * @param $cartrawler
	 * @return Oara_Network_Publisher_Tv_Export
	 */
	public function __construct($credentials) {

		$user = $credentials['user'];
		$password = $credentials['password'];
		
		$valuesLogin = array(
		new Oara_Curl_Parameter('Username', $user),
		new Oara_Curl_Parameter('Password', $password),
		);
		
		$dir = realpath ( dirname ( __FILE__ ) ) . '/../../data/curl/' . $credentials ['cookiesDir'] . '/' . $credentials ['cookiesSubDir'] . '/';
		
		if (! Oara_Utilities::mkdir_recursive ( $dir, 0777 )) {
			throw new Exception ( 'Problem creating folder in Access' );
		}
		$cookies = realpath(dirname(__FILE__)).'/../../data/curl/'.$credentials['cookiesDir'].'/'.$credentials['cookiesSubDir'].'/'.$credentials["cookieName"].'_cookies.txt';
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
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "https://www.sportscoverdirect.com/promoters/account/login" );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
		$dom = new Zend_Dom_Query($html);
		$hidden = $dom->query('input[type="hidden"]');

		foreach ($hidden as $values) {
			$valuesLogin[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "https://www.sportscoverdirect.com/promoters/account/login" );
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
		curl_setopt ( $rch, CURLOPT_URL, 'https://www.sportscoverdirect.com/promoters/account/update' );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
			
		if (preg_match("/You're logged in as/", $html, $matches)) {
			$connection = true;
		}
		
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array();
		$obj = Array();
		$obj['cid'] = 1;
		$obj['name'] = 'SportCoverDirect';
		$merchants[] = $obj;

		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array();
		
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, 'https://www.sportscoverdirect.com/promoters/earn' );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
		$dom = new Zend_Dom_Query($html);
		$results = $dom->query('.performance');
		if (count($results) > 0) {
			$exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));
			$num = count($exportData) - 1; //the last row is show-more show-less
			for ($i = 1; $i < $num; $i++) {
				$overviewExportArray = str_getcsv($exportData[$i], ";");
		
				$transaction = Array();
		
				$transaction['merchantId'] = 1;
				
				$date = new Zend_Date($overviewExportArray[0], "dd/MM/yyyy");
				$transaction['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$transaction ['amount'] = Oara_Utilities::parseDouble ( preg_replace ( "/[^0-9\.,]/", "", $overviewExportArray[1] ) );
				$transaction['commission'] = Oara_Utilities::parseDouble ( preg_replace ( "/[^0-9\.,]/", "", $overviewExportArray[1] ) );
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				
				$totalTransactions[] = $transaction;
			}
		}		

		return $totalTransactions;

	}

	/**
	 *
	 * Function that Convert from a table to Csv
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
	 * Function that returns the innet HTML code
	 * @param unknown_type $element
	 */
	private function DOMinnerHTML($element) {
		$innerHTML = "";
		$children = $element->childNodes;
		foreach ($children as $child) {
			$tmp_dom = new DOMDocument();
			$tmp_dom->appendChild($tmp_dom->importNode($child, true));
			$innerHTML .= trim($tmp_dom->saveHTML());
		}
		return $innerHTML;
	}

}
