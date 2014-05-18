<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_SkyScanner
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_SkyScanner extends Oara_Network {

	private $_credentials = null;
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	
	private $_apiKey = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_SkyScanner
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;
		self::logIn();

	}

	private function logIn() {

		$valuesLogin = array(
			new Oara_Curl_Parameter('RememberMe', "false"),
			new Oara_Curl_Parameter('ApiKey', $this->_credentials['user']),
			new Oara_Curl_Parameter('PortalKey', $this->_credentials['password']),
		);
		
		$loginUrl = 'http://business.skyscanner.net/portal/en-GB/SignIn';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $this->_credentials);
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://business.skyscanner.net/portal/en-GB/UK/Report/Show', array());
		$exportReport = $this->_client->get($urls);
		if (!preg_match("/encrypedApiKey: \"(.*)?\",/", $exportReport[0], $match)){
			$connection = false;
		} else {
			$this->_apiKey = $match[1];
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
		$obj['name'] = "SkyScanner";
		$obj['url'] = "http://www.skyscanneraffiliate.net";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();

		$urls = array();
		
		$url = 'http://business.skyscanner.net/apiservices/reporting/v1.0/reportdata/'.$dStartDate->toString("yyyy-MM-dd").'/'.$dEndDate->toString("yyyy-MM-dd").'?encryptedApiKey='.$this->_apiKey."&type=csv";
		$urls[] = new Oara_Curl_Request($url, array());

		$exportReport = array();
		$exportReport = $this->_client->get($urls);
		$dump = var_export($exportReport[0], true);
		$dump = preg_replace("/ \. /", "", $dump);
	    $dump = preg_replace("/\"\\\\0\"/", "", $dump);
	    $dump = preg_replace("/'/", "", $dump);
	    
		$exportData = str_getcsv($dump, "\n");

		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {

				$transactionExportArray = str_getcsv($exportData[$i], ",");
				$transaction = Array();
				$transaction['merchantId'] = 1;
				$transactionDate = new Zend_Date($transactionExportArray[0], 'dd/MM/yyyy HH:mm:ss', 'en');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				//unset($transactionDate);
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				$transaction['amount'] = (double)$transactionExportArray[7];
				$transaction['commission'] = (double)$transactionExportArray[7]* 0.6;

				if ($transaction['amount'] != 0){
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