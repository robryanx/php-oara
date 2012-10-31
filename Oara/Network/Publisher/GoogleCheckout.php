<?php
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_ClickBank
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_GoogleCheckout extends Oara_Network {
	/**
	 * User
	 * @var string
	 */
	private $_user = null;
	/**
	 * Password
	 * @var string
	 */
	private $_password = null;

	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Effiliation
	 */
	public function __construct($credentials) {

		$user = $credentials["user"];
		$password = $credentials["password"];
		$this->_user = $user;
		$this->_password = $password;

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;
		try {
			$body = '<order-list-request xmlns="http://checkout.google.com/schema/2"
					  start-date="2000-01-01T00:00:00" end-date="2000-01-31T23:59:59">
					</order-list-request>';
			self::returnApiData("https://checkout.google.com/api/checkout/v2/reports/Merchant/".$this->_user, $body);
		} catch (Exception $e) {
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
		$obj['cid'] = 1;
		$obj['name'] = "Google Checkout";
		$obj['url'] = "checkout.google.com/sell/";
		$merchants[] = $obj;
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));

		$body = '<order-list-request xmlns="http://checkout.google.com/schema/2"
					  start-date="'.$dStartDate->toString("yyyy-MM-ddTHH:mm:ss").'" end-date="'.$dEndDate->toString("yyyy-MM-ddTHH:mm:ss").'">
					  </order-list-request>';
		$transactionCsv = self::returnApiData("https://checkout.google.com/api/checkout/v2/reports/Merchant/".$this->_user, $body);

		if (!preg_match("/No orders/", $transactionCsv)) {
			$transactionArrayList = str_getcsv($transactionCsv, "\n");

			for ($i = 1; $i < count($transactionArrayList); $i++) {
				$transactionArray = str_getcsv($transactionArrayList[$i], ",");

				$transaction = Array();
				$transaction['merchantId'] = 1;
				$transactionDate = new Zend_Date($transactionArray[2], 'dd-MMM-yyyy HH:mm:ss');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				unset($transactionDate);

				if ($transactionArrayList[1] != null) {
					$transaction['custom_id'] = $transactionArray[1];
				}

				$transaction['unique_id'] = $transactionArray[0];

				$transaction['amount'] = (double) $filter->filter($transactionArray[4]);
				$transaction['commission'] = (double) $filter->filter($transactionArray[5]);

				if ($transactionArray[6] == 'REVIEWING' || $transactionArray[6] == 'CHARGEABLE') {
					$transaction['status'] = Oara_Utilities::STATUS_PENDING;
				} else
					if ($transactionArray[6] == 'CHARGING' || $transactionArray[6] == 'CHARGED') {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
						if ($transactionArray[6] == 'PAYMENT_DECLINED' || $transactionArray[6] == 'CANCELLED' || $transactionArray[6] == 'CANCELLED_BY_GOOGLE') {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						}

				$totalTransactions[] = $transaction;
			}

		}

		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getOverviewList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$overviewArray = array();

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
	 *
	 * Api connection to Google Checkout
	 * @param unknown_type $xmlLocation
	 * @throws Exception
	 */
	private function returnApiData($xmlLocation, $body) {
		$dataArray = array();
		// Get the data
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $xmlLocation);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_USERPWD, $this->_user.":".$this->_password);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux i686; rv:7.0.1) Gecko/20100101 Firefox/7.0.1");

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
