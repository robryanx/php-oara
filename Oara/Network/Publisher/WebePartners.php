<?php

/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Webepartners
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_WebePartners extends Oara_Network {
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * User
	 * @var unknown_type
	 */
	private $_user = null;
	/**
	 * Pass
	 * @var unknown_type
	 */
	private $_pass = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];

		$url = "http://panel.webepartners.pl/Account/Login";

		$dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials['cookiesSubDir'] . DIRECTORY_SEPARATOR;

		$cookieName = $credentials["cookieName"];
		$cookies = $dir.$cookieName.'_cookies.txt';

		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if ($credentials['cookieName'] == strstr($file, '_', true)) {
					unlink($dir.$file);
					break;
				}
			}
			closedir($handle);
		}

		$this->_client = array(
		CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:22.0) Gecko/20100101 Firefox/22.0",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FAILONERROR => true,
		CURLOPT_COOKIEJAR => $cookies,
		CURLOPT_COOKIEFILE => $cookies,
		CURLOPT_AUTOREFERER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_HEADER => false,
		);

		//Init curl
		$ch = curl_init();
		$options = $this->_client;
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_FOLLOWLOCATION] = true;
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		$err = curl_errno($ch);
		$errmsg = curl_error($ch);
		$info = curl_getinfo($ch);


		$dom = new Zend_Dom_Query($result);
		$results = $dom->query('input[type="hidden"]');
		$hiddenValue = null;
		foreach ($results as $result){
			$name = $result->attributes->getNamedItem("name")->nodeValue;
			if ($name == "__RequestVerificationToken"){
				$hiddenValue = $result->attributes->getNamedItem("value")->nodeValue;
			}
		}
		if ($hiddenValue == null){
			throw new Exception("hidden value not found");
		}

		$valuesLogin = array(
		new Oara_Curl_Parameter('__RequestVerificationToken', $hiddenValue),
		new Oara_Curl_Parameter('Login', $user),
		new Oara_Curl_Parameter('Password', $password),
		);


		// Login form fields
		$return = array();
		foreach ($valuesLogin as $parameter) {
			$return[] = $parameter->getKey().'='.urlencode($parameter->getValue());
		}
		$arg = implode('&', $return);

		//Init curl
		$ch = curl_init();
		$options = $this->_client;
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_POSTFIELDS] = $arg;
		$options[CURLOPT_POST] = true;
		curl_setopt_array($ch, $options);

		$result = curl_exec($ch);
		$err = curl_errno($ch);
		$errmsg = curl_error($ch);
		$info = curl_getinfo($ch);


		//Init curl
		$ch = curl_init();
		$options = $this->_client;
		$options[CURLOPT_URL] = "http://panel.webepartners.pl/AffiliateTools/Api";
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_POSTFIELDS] = $arg;
		$options[CURLOPT_POST] = true;
		curl_setopt_array($ch, $options);

		$result = curl_exec($ch);
		$err = curl_errno($ch);
		$errmsg = curl_error($ch);
		$info = curl_getinfo($ch);

		$this->_user = urlencode($user);

		$dom = new Zend_Dom_Query($result);
		$apiPass = null;
		$results = $dom->query('a [href*="Authorize"]');
		if (count($results) > 0){
			$item = $results->current();
			$url = $item->attributes->getNamedItem("href")->nodeValue;
			$parsedUrl = parse_url($url);
			parse_str($parsedUrl["query"], $parameters);
			$apiPass = $parameters["password"];
		}

		$this->_pass = $apiPass;


	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		$loginUrl = "http://api.webepartners.pl/wydawca/Authorize?login={$this->_user}&password={$this->_pass}";

		$context = stream_context_create(array(
		    'http' => array(
		        'header'  => "Authorization: Basic " . base64_encode("{$this->_user}:{$this->_pass}")
		)
		));
		$data = file_get_contents($loginUrl, false, $context);
		if ($data == true) {
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

		$context = stream_context_create(array(
			    'http' => array(
		        'header'  => "Authorization: Basic " . base64_encode("{$this->_user}:{$this->_pass}")
		)
		));

		$data = file_get_contents("http://api.webepartners.pl/wydawca/Programs", false, $context);
		$dataArray = json_decode($data, true);
		foreach ($dataArray as $merchantObject){
			$obj = array();
			$obj['cid'] = $merchantObject["ProgramId"];
			$obj['name'] = $merchantObject["ProgramName"];
			$merchants[] = $obj;
		}
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$context = stream_context_create(array(
			    'http' => array(
		        'header'  => "Authorization: Basic " . base64_encode("{$this->_user}:{$this->_pass}")
		)
		));

		$from = urlencode($dStartDate->toString("yyyy-MM-dd HH:mm:ss"));

		$data = file_get_contents("http://api.webepartners.pl/wydawca/Auctions?from=$from", false, $context);
		$dataArray = json_decode($data, true);
		foreach ($dataArray as $transactionObject){

			if (in_array($transactionObject["ProgramId"], $merchantList)){
				$transaction = Array();
				$transaction['merchantId'] = $transactionObject["ProgramId"];
				$transaction['date'] = $transactionObject["AuctionDate"];
				if (isset($transactionObject["AuctionId"]) && $transactionObject["AuctionId"] != '') {
					$transaction['unique_id'] = $transactionObject["AuctionId"];
				}
				if (isset($transactionObject["subID"]) && $transactionObject["subID"] != '') {
					$transaction['custom_id'] = $transactionObject["subID"];
				}

				if ($transactionObject["AuctionStatusId"] == 3 || $transactionObject["AuctionStatusId"] == 4 || $transactionObject["AuctionStatusId"] == 5) {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
				if ($transactionObject["AuctionStatusId"] == 1) {
					$transaction['status'] = Oara_Utilities::STATUS_PENDING;
				} else
				if ($transactionObject["AuctionStatusId"] == 2) {
					$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
				} else
				if ($transactionObject["AuctionStatusId"] == 6) {
					$transaction['status'] = Oara_Utilities::STATUS_PAID;
				}

				$transaction['amount'] = Oara_Utilities::parseDouble($transactionObject["OrderCost"]);

				$transaction['commission'] = Oara_Utilities::parseDouble($transactionObject["Commission"]);
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
