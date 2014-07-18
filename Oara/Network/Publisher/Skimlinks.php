<?php
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
				
		$user = $credentials['user'];
		$password = $credentials['password'];
		
		$this->_publicapikey =  $credentials['publicapikey'];
		$this->_privateapikey = $credentials['privateapikey'];
		
		$valuesLogin = array(
				new Oara_Curl_Parameter('username', $user),
				new Oara_Curl_Parameter('password', $password),
				new Oara_Curl_Parameter('menu', "{ return }"),
				new Oara_Curl_Parameter('btn-login', "")
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
		curl_setopt ( $rch, CURLOPT_URL, "https://hub.skimlinks.com/login" );
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
		curl_setopt ( $rch, CURLOPT_URL, "https://hub.skimlinks.com/login" );
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
		curl_setopt ( $rch, CURLOPT_URL, "https://hub.skimlinks.com/toolbox/apis/reporting" );
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
		curl_setopt ( $rch, CURLOPT_URL, 'https://hub.skimlinks.com/' );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
			
		if (preg_match("/user_hash/", $html, $matches)) {
			$connection = true;
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
			
		foreach ($jsonArray["skimlinksAccount"]["merchants"] as $i){
			$obj = Array();
			$obj['cid']  = $i["merchantID"];
			$obj['name'] = $i["merchantName"];
			$merchants[] = $obj;
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
			$transaction ['amount'] = $i["commissionValue"];
			$transaction['commission'] = $i["commissionValue"];
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
