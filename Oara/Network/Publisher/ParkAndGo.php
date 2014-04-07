<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_ParkAndGo
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_ParkAndGo extends Oara_Network {

	private $_credentials = null;
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	
	private $_apiKey = null;
	private $_agent = null;
	
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
			new Oara_Curl_Parameter('agentcode', $this->_credentials['user']),
			new Oara_Curl_Parameter('pword', $this->_credentials['password']),
		);
		
		$loginUrl = 'https://www.parkandgo.co.uk/agents/';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $this->_credentials);

		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
		
		
		$valuesLogin = array(
				new Oara_Curl_Parameter('agentcode', $this->_credentials['user']),
				new Oara_Curl_Parameter('pword', $this->_credentials['password']),
		);
		
		$urls[] = new Oara_Curl_Request('https://www.parkandgo.co.uk/agents/', $valuesLogin);
		$exportReport = $this->_client->post($urls);
		if (!preg_match("/Produce Report/", $exportReport[0], $match)){
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
		$obj['name'] = "Park And Go";
		$obj['url'] = "http://www.parkandgo.co.uk";
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
		$exportParams = array(
				new Oara_Curl_Parameter('agentcode', $this->_credentials['user']),
				new Oara_Curl_Parameter('pword', $this->_credentials['password']),
				new Oara_Curl_Parameter('fromdate', $dStartDate->toString("dd-MM-yyyy")),
				new Oara_Curl_Parameter('todate', $dEndDate->toString("dd-MM-yyyy")),
				new Oara_Curl_Parameter('rqtype', "report")
		);
		$urls[] = new Oara_Curl_Request('https://www.parkandgo.co.uk/agents/', $exportParams);
		$exportReport = $this->_client->post($urls);
		
		$today = new Zend_Date();
		$today->setHour(0);
		$today->setMinute(0);
		
		$exportData = str_getcsv ( $exportReport [0], "\n" );
		$num = count ( $exportData );
		for($i = 1; $i < $num; $i ++) {
				
			$transactionExportArray = str_getcsv ( $exportData [$i], "," );
			
			$arrivalDate = new Zend_Date ( $transactionExportArray [3], 'yyyy-MM-dd 00:00:00', 'en' );
			
			$transaction = Array ();
			$transaction ['merchantId'] = 1;
			$transaction ['unique_id'] = $transactionExportArray [0];
			$transactionDate = new Zend_Date ( $transactionExportArray [2], 'yyyy-MM-dd 00:00:00', 'en' );
			$transaction ['date'] = $transactionDate->toString ( "yyyy-MM-dd HH:mm:ss" );
			unset ( $transactionDate );
			$transaction ['status'] = Oara_Utilities::STATUS_PENDING;
			if ($today > $arrivalDate){
				$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;
			}
				
			$transaction ['amount'] = Oara_Utilities::parseDouble ( $transactionExportArray [6] );
			$transaction ['commission'] = Oara_Utilities::parseDouble ( $transactionExportArray [7] );
	
			$totalTransactions [] = $transaction;
		}
		

		return $totalTransactions;
	}
	
	

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$overviewArray = Array();
		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);


		//Add transactions
		foreach ($transactionArray as $merchantId => $merchantTransaction) {
			foreach ($merchantTransaction as $date => $transactionList) {

				$overview = Array();

				$overview['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date($date, "yyyy-MM-dd");
				$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				unset($overviewDate);
				$overview['click_number'] = 0;
				$overview['impression_number'] = 0;
				$overview['transaction_number'] = 0;
				$overview['transaction_confirmed_value'] = 0;
				$overview['transaction_confirmed_commission'] = 0;
				$overview['transaction_pending_value'] = 0;
				$overview['transaction_pending_commission'] = 0;
				$overview['transaction_declined_value'] = 0;
				$overview['transaction_declined_commission'] = 0;
				$overview['transaction_paid_value'] = 0;
				$overview['transaction_paid_commission'] = 0;
				foreach ($transactionList as $transaction) {
					$overview['transaction_number']++;
					if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED) {
						$overview['transaction_confirmed_value'] += $transaction['amount'];
						$overview['transaction_confirmed_commission'] += $transaction['commission'];
					} else
					if ($transaction['status'] == Oara_Utilities::STATUS_PENDING) {
						$overview['transaction_pending_value'] += $transaction['amount'];
						$overview['transaction_pending_commission'] += $transaction['commission'];
					} else
					if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED) {
						$overview['transaction_declined_value'] += $transaction['amount'];
						$overview['transaction_declined_commission'] += $transaction['commission'];
					} else
					if ($transaction['status'] == Oara_Utilities::STATUS_PAID) {
						$overview['transaction_paid_value'] += $transaction['amount'];
						$overview['transaction_paid_commission'] += $transaction['commission'];
					}
				}
				$overviewArray[] = $overview;
			}
		}

		return $overviewArray;
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