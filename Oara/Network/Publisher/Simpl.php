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
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Skimlinks
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Simpl extends \Oara\Network {
	/**
	 * Private API Key
	 * @var string
	 */
	private $_credentials = null;
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Daisycon
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;
		
		$dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;

		if (! \Oara\Utilities::mkdir_recursive ( $dir, 0777 )) {
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
	 * @see library/Oara/Network/Interface#getMerchantList()
	 */
	public function getMerchantList() {

		$merchants = Array ();
		$url = "https://export.net.simpl.ie/{$this->_credentials['apiPassword']}/mlist_12807.xml?";
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL,  $url);
		curl_setopt_array ( $rch, $options );
		$xmlMerchants = curl_exec ( $rch );
		curl_close ( $rch );
		
		$merchantArray = json_decode(json_encode((array) simplexml_load_string($xmlMerchants)),1);
		foreach ($merchantArray["merchant"] as $merchant){
			$obj = Array();
			$obj['cid']  = $merchant["mid"];
			$obj['name'] = $merchant["title"];
			$merchants[] = $obj;
		}


		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null) {

		$totalTransactions = array();

		$valuesFromExport = array(
				new \Oara\Curl\Parameter('filter[zeitraumAuswahl]', "absolute"),
				new \Oara\Curl\Parameter('filter[zeitraumvon]', $dStartDate->toString("dd.MM.yyyy")),
				new \Oara\Curl\Parameter('filter[zeitraumbis]', $dEndDate->toString("dd.MM.yyyy")),
				new \Oara\Curl\Parameter('filter[currencycode]', 'EUR')
		);

		$rch = curl_init ();
		$options = $this->_options;
		$arg = array ();
		foreach ( $valuesFromExport as $parameter ) {
			$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		$url = "https://export.net.simpl.ie/{$this->_credentials['apiPassword']}/statstransaction_12807.xml?".implode ( '&', $arg );
		curl_setopt ( $rch, CURLOPT_URL, $url );
		curl_setopt_array ( $rch, $options );
		$xml = curl_exec ( $rch );
		curl_close ( $rch );

		$transactionArray = json_decode(json_encode((array) simplexml_load_string($xml)),1);
		foreach ($transactionArray["transaction"] as  $trans){
			$transaction = Array();
			$transaction['merchantId'] = $trans["merchant_id"];
			$transaction['unique_id'] =  $trans["conversionid"];
			$transaction['date'] = substr($trans["trackingtime"],0,19);
			$transaction['amount'] = (double)$trans["revenue"];
			$transaction['commission'] = (double)$trans["commissionvalue"];
			if ($trans["subid"] != null) {
				$transaction['custom_id'] = $trans["subid"];
			}
			
			$transactionStatus = $trans["status"];
			if ($transactionStatus == "open") {
				$transaction ['status'] = \Oara\Utilities::STATUS_PENDING;
			} else if ($transactionStatus == "cancelled") {
				$transaction ['status'] = \Oara\Utilities::STATUS_DECLINED;
			} else if ($transactionStatus == "paid") {
				$transaction ['status'] = \Oara\Utilities::STATUS_PAID;
			} else if ($transactionStatus == "confirmed") {
				$transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
			}  else {
				throw new Exception ( "New status found {$transactionStatus}" );
			}
			$totalTransactions[] = $transaction;
			
		}
		

		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		return $paymentHistory;
	}

}
