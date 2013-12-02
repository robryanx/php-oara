<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_AffiliatesUnited
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AffiliatesUnited extends Oara_Network {
	
	/**
	 * Merchants by name
	 */
	private $_merchantMap = array();
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];

		$valuesLogin = array(
		new Oara_Curl_Parameter('us', $user),
		new Oara_Curl_Parameter('pa', $password)
		);

		$loginUrl = 'https://affiliates.affutd.com/affiliates/Login.aspx';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

	

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://affiliates.affutd.com/affiliates/Dashboard.aspx', array());
		$exportReport = $this->_client->get($urls);

		echo $exportReport[0];
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('.lnkLogOut');
		if (count($results) > 0) {
			$connection = true;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array()) {
		$merchants = array();

		
		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Affiliates United";
		$merchants[] = $obj;
		

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$valuesFromExport = array();
		$valuesFromExport[] = new Oara_Curl_Parameter('ctl00$cphPage$reportFrom', $dStartDate->toString("yyyy-MM-dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('ctl00$cphPage$reportTo', $dEndDate->toString("yyyy-MM-dd"));
	
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://affiliates.affutd.com/affiliates/DataServiceWrapper/DataService.svc/Export/CSV/Affiliates_Reports_GeneralStats_DailyFigures', $valuesFromExport);
		$exportReport = $this->_client->post($urls);
		$exportData = str_getcsv($exportReport[0], "\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ",");

			$transaction = Array();
			$transaction['merchantId'] = 1;
			$transactionDate = new Zend_Date($transactionExportArray[0], 'dd-MM-yyyy', 'en');
			$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

			$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				
			$transaction['amount'] = $transactionExportArray[12];
			$transaction['commission'] = $transactionExportArray[13];
			$totalTransactions[] = $transaction;
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

}
