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
 * @author     Alejandro Muñoz Odero
 * @category   Oara_Network_Publisher_PureVPN
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_PureVPN extends Oara_Network {

	private $_credentials = null;
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_s = null;
	
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_options = array();

	/**
	 * Transaction List
	 * @var unknown_type
	 */
	private $_transactionList = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_PureVPN
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;
		self::logIn();

	}
	private function logIn() {
		

		$valuesLogin = array(
		new Oara_Curl_Parameter('username', $this->_credentials['user']),
		new Oara_Curl_Parameter('password', $this->_credentials['password']),
		);
		
		$cookies = realpath(dirname(__FILE__)).'/../../data/curl/'.$this->_credentials['cookiesDir'].'/'.$this->_credentials['cookiesSubDir'].'/'.$this->_credentials["cookieName"].'_cookies.txt';
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
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_HTTPHEADER => array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Accept-Language: es,en-us;q=0.7,en;q=0.3','Accept-Encoding: gzip, deflate','Connection: keep-alive', 'Cache-Control: max-age=0'),
				CURLOPT_ENCODING => "gzip",
				CURLOPT_VERBOSE => false
		);
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "https://billing.purevpn.com/clientarea.php" );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
		sleep(10);
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "https://billing.purevpn.com/clientarea.php" );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
		$dom = new Zend_Dom_Query($html);
		$hidden = $dom->query('#frmlogin input[name="token"][type="hidden"]');

		foreach ($hidden as $values) {
			$valuesLogin[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "https://billing.purevpn.com/dologin.php?goto=clientarea.php" );
		
		$options[CURLOPT_HTTPHEADER] =  array('Referer: https://billing.purevpn.com/clientarea.php', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Accept-Language: es,en-us;q=0.7,en;q=0.3','Accept-Encoding: gzip, deflate','Connection: keep-alive', 'Cache-Control: max-age=0');
		
		$options [CURLOPT_POST] = true;
		$arg = array ();
		foreach ( $valuesLogin as $parameter ) {
			$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		$options [CURLOPT_POSTFIELDS] = implode ( '&', $arg );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
		
		$rch = curl_init ();
		$options = $this->_options;
		$options[CURLOPT_URL] =  "https://billing.purevpn.com/check_affiliate.php?check=affiliate";
		$options[CURLOPT_HEADER] = true ;
		$options[CURLOPT_NOBODY] =  false;
		$options[CURLOPT_HTTPHEADER] =  array('Referer: https://billing.purevpn.com/affiliates.php', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Accept-Language: es,en-us;q=0.7,en;q=0.3','Accept-Encoding: gzip, deflate','Connection: keep-alive', 'Cache-Control: max-age=0');
		curl_setopt_array ( $rch, $options );
		$header = curl_exec ( $rch );
		preg_match ( '/Location:(.*?)\n/', $header, $matches );
		$newurl = trim ( array_pop ( $matches ) );
		curl_close ( $rch );
		
		if (preg_match ( "/S=(.*)/", $newurl, $matches )) {
			$this->_s = $matches [1];
		}
		
		$rch = curl_init ();
		$options = $this->_options;
		$options[CURLOPT_URL] =  $newurl;
		$options[CURLOPT_HTTPHEADER] =  array('Referer: https://billing.purevpn.com/affiliates.php', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Accept-Language: es,en-us;q=0.7,en;q=0.3','Accept-Encoding: gzip, deflate','Connection: keep-alive', 'Cache-Control: max-age=0');
		curl_setopt_array ( $rch, $options );
		$content = curl_exec ( $rch );
		
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		if ($this->_s == null) {
			$connection = false;
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
		$obj['cid'] = "1";
		$obj['name'] = "PureVPN";
		$obj['url'] = "https://billing.purevpn.com";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$valuesFormExport = array();

		$chip = $this->_s;
		if ($this->_transactionList == null){
			
			
			$rch = curl_init ();
			$options = $this->_options;
			$options[CURLOPT_HTTPHEADER] =  array("Referer: https://billing.purevpn.com/affiliates/affiliates/panel.php?S=$chip", 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Accept-Language: es,en-us;q=0.7,en;q=0.3','Accept-Encoding: gzip, deflate','Connection: keep-alive', 'Cache-Control: max-age=0');
			curl_setopt ( $rch, CURLOPT_URL, "https://billing.purevpn.com/affiliates/scripts/server.php?C=Pap_Affiliates_Reports_TransactionsGrid&M=getCSVFile&S=$chip&FormRequest=Y&FormResponse=Y" );
			$options [CURLOPT_POST] = true;
			$options [CURLOPT_POSTFIELDS] = "";
			curl_setopt_array ( $rch, $options );
			$exportReport = curl_exec ( $rch );
			curl_close ( $rch );
			$this->_transactionList = str_getcsv($exportReport, "\n");
		}
		$exportData = $this->_transactionList;

		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
				
			$transactionExportArray = str_getcsv($exportData[$i], ",");
			//print_r($transactionExportArray);

			$transaction = Array();
			$transaction['merchantId'] = 1;
			$transaction['uniqueId'] = $transactionExportArray[36];
			$transactionDate = new Zend_Date($transactionExportArray[5], 'yyyy-MM-dd HH:mm:ss', 'en');
			$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
			unset($transactionDate);
			$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
			$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[1]);
			$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[0]);
			//print_r($transaction);

			if ($transaction['date'] >= $dStartDate->toString("yyyy-MM-dd HH:mm:ss") && $transaction['date'] <= $dEndDate->toString("yyyy-MM-dd HH:mm:ss")){
				$totalTransactions[] = $transaction;
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
