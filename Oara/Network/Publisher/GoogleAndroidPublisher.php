<?php
require_once "GoogleApiClient/src/Google_Client.php";
require_once "GoogleApiClient/src/contrib/Google_StorageService.php";
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_As
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_GoogleAndroidPublisher extends Oara_Network {

	/**
	 * Adsense Client
	 * @var unknown_type
	 */
	private $_android = null;
	/**
	 * Constructor and Login
	 * @param $buy
	 * @return Oara_Network_Publisher_Buy_Api
	 */
	public function __construct($credentials) {
		$client = new Google_Client();
		$client->setApplicationName("AffJet");
		$client->setClientId($credentials['clientId']);
		$client->setClientSecret($credentials['clientSecret']);
		$client->setAccessToken($credentials['oauth2']);
		$client->setAccessType('offline');
		$this->_client = $client;
		$this->_android = new Google_StorageService($client);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		if ($this->_client->getAccessToken()) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array();

		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Google Android Publisher";
		$obj['url'] = "www.google.com";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();


		//$objects = $this->_android->objects->listObjects("gs://pubsite_prod_rev_06182089191095104578");


		/**
		 * Google Cloud Storage API request to retrieve a bucket from your
		 * Google Cloud Storage project.
		 */
		$bucket = $this->_android->buckets->get("pubsite_prod_rev_06182089191095104578");
		$getBucketMarkup = generateMarkup('Get Bucket', $bucket);



		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getOverviewList($aMerchantIds, $dStartDate, $dEndDate)
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
}
