<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_M4n
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_M4n extends Oara_Network {
	/**
	 * Export Payment Parameters
	 * @var array
	 */
	private $_exportPaymentParameters = null;
	/**
	 * User
	 * @var unknown_type
	 */
	private $_user = null;
	/**
	 * Password
	 * @var unknown_type
	 */
	private $_password = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];

		$this->_user = $user;
		$this->_password = $password;

		$this->_exportPaymentParameters = array();

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$xmlLocation = 'https://api.m4n.nl/restful/xml/user';
		self::returnApiData($xmlLocation);
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();
		
		$xmlLocation = 'https://api.m4n.nl/restful/csv/affiliate/merchants';
		$merchantData = self::returnApiData($xmlLocation);
		$merchantData = str_getcsv($merchantData, "\n");
		$num = count($merchantData);
		for ($j = 1; $j < $num; $j++) {
			$merchantArray = str_getcsv($merchantData[$j], ";");
			$obj = array();
			$obj['cid'] = $merchantArray[2];
			$obj['name'] = $merchantArray[0];
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

		$xmlLocation = "https://api.m4n.nl/restful/csv/affiliate/leads/clickTime/from/".$dStartDate->toString("yyyyMMdd")."/to/".$dEndDate->toString("yyyyMMdd")."";
		$transactionData = self::returnApiData($xmlLocation);
		$exportData = str_getcsv($transactionData, "\n");

		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ";");
			if (in_array((int) $transactionExportArray[3], $merchantList)) {
				$transaction = Array();
				$merchantId = (int) $transactionExportArray[3];
				$transaction['merchantId'] = $merchantId;
				$transaction['unique_id'] = $transactionExportArray[0];
				$transactionDate = new Zend_Date($transactionExportArray[10], 'yyyy-MM-dd HH:mm:ss');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

				if ($transactionExportArray[2] == 'ACCEPTED') {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
					if ($transactionExportArray[2] == 'ON_HOLD' || $transactionExportArray[2] == 'TO_BE_APPROVED' || $transactionExportArray[2] == 'BLOCKED') {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
						if ($transactionExportArray[2] == 'REJECTED') {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						} else {
							throw new Exception("New status $transactionExportArray[2]");
						}
				$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[12]);
				$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[4]);
				$totalTransactions[] = $transaction;
			}
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
		foreach ($transactionArray as $merchantId => $merchantTransaction) {
			foreach ($merchantTransaction as $date => $transactionList) {

				$overview = Array();

				$overview['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date($date, "yyyy-MM-dd");
				$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
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
	/**
	 *
	 * Api connection to M4N
	 * @param unknown_type $xmlLocation
	 * @throws Exception
	 */
	private function returnApiData($xmlLocation) {
		$ch = curl_init();

		//Set username and password to use
		if (!curl_setopt($ch, CURLOPT_USERPWD, $this->_user.":".$this->_password)) {
			//Login incorrect, even before trying to connect. Typo?
			throw new Exception("FAIL: curl_setopt(CURLOPT_USERPWD)");
		}

		//Check HTTP Authentication
		if (!curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY)) {
			//HTTP Authentication failed. Offline?
			throw new Exception("FAIL: curl_setopt(CURLOPT_HTTPAUTH, CURLAUTH_ANY)");
		}

		//Check SSL Connection
		if (!curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false)) {
			//SSL connection not possible
			throw new Exception("FAIL: curl_setopt(CURLOPT_SSL_VERIFYPEER, false)");
		}

		//Check URL validity (last check)
		if (!curl_setopt($ch, CURLOPT_URL, $xmlLocation)) {
			throw new Exception("FAIL: curl_setopt(CURLOPT_URL, $xmlLocation)");
		}

		//Set to 1 to prevent output of entire xml file
		if (!curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1)) {
			throw new Exception("FAIL: curl_setopt(CURLOPT_RETURNTRANSFER, 1)");
		}

		// Get the data
		$data = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($httpCode != 200) {
			throw new Exception("Couldn't connect to the API");
		}
		//Close Curl session
		curl_close($ch);

		return $data;

	}
}
