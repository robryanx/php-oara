<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Ladbrokers
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Ladbrokers extends Oara_Network {

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
		new Oara_Curl_Parameter('username', $user),
		new Oara_Curl_Parameter('password', $password),
		new Oara_Curl_Parameter('submit1', 'GO')
		);


		$loginUrl = 'https://affiliates.score-affiliates.com/login.asp';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);


		$this->_exportPaymentParameters = array(new Oara_Curl_Parameter('action', 'do_report_payments'),
		new Oara_Curl_Parameter('daterange', '7')
		);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://affiliates.score-affiliates.com/members/welcome.asp?language=', array());
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('#username');
		if (count($results) > 0) {
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

		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Ladbrokers";
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
		$valuesFromExport[] = new Oara_Curl_Parameter('bannerid', "0");
		$valuesFromExport[] = new Oara_Curl_Parameter('detaillevel', "detailed");
		$valuesFromExport[] = new Oara_Curl_Parameter('enddate', $dEndDate->toString("yyyy/MM/dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('startdate', $dStartDate->toString("yyyy/MM/dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('jsgetreport', "Generate Report");
		$valuesFromExport[] = new Oara_Curl_Parameter('merchantid', "0");
		$valuesFromExport[] = new Oara_Curl_Parameter('merchantname', "");
		$valuesFromExport[] = new Oara_Curl_Parameter('reportname', "earnings_report");
		$valuesFromExport[] = new Oara_Curl_Parameter('reportperiod', "");
		$valuesFromExport[] = new Oara_Curl_Parameter('siteid', "0");
		$valuesFromExport[] = new Oara_Curl_Parameter('sitename', "");
		$valuesFromExport[] = new Oara_Curl_Parameter('sortby', "date");

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://affiliates.score-affiliates.com/reporting/ajax_report_template.asp', $valuesFromExport);
		$exportReport = $this->_client->post($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$pid = $dom->query('.reportpaging form input[type="hidden"]');

		$valuesFromExport = array();
		$valuesFromExport[] = new Oara_Curl_Parameter("reportid",  $pid->current()->getAttribute("value"));
		$valuesFromExport[] = new Oara_Curl_Parameter('exportformat', "csv");

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://affiliates.score-affiliates.com/reporting/excel.asp', $valuesFromExport);
		$exportReport = $this->_client->post($urls);


		$exportData = str_getcsv($exportReport[0], "\n");
		$num = count($exportData);
		for ($i = 1; $i < $num - 2; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ",");
			$transaction = Array();

			$transaction['merchantId'] = 1;
			$transactionDate = new Zend_Date($transactionExportArray[3], 'M/d/yyyy', 'en');
			$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

			$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				
			$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[21]);
			$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[64]);
			if ($transaction['amount'] != 0 && $transaction['commission'] != 0) {
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
