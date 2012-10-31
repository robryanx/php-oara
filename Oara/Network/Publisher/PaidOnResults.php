<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Por
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_PaidOnResults extends Oara_Network {
	/**
	 * Export client.
	 * @var Oara_Curl_Access
	 */
	private $_client = null;

	/**
	 * Merchant Export Parameters
	 * @var array
	 */
	private $_exportMerchantParameters = null;

	/**
	 * Transaction Export Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;

	/**
	 * Api Password
	 */
	private $_apiPassword = null;

	/**
	 * Session Id
	 */
	private $_sessionId = null;

	/**
	 * Payment History
	 */
	private $_paymentHistory = null;

	/**
	 * Constructor and Login
	 * @param $por
	 * @return Oara_Network_Publisher_Por_Api
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];
		$this->_apiPassword = $credentials['apiPassword'];

		$loginUrl = 'https://secure.paidonresults.com/cgi-bin/affiliate-login/login.pl';

		$valuesLogin = array(new Oara_Curl_Parameter('username', $user),
			new Oara_Curl_Parameter('password', $password)
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$valuesFormExport = array(new Oara_Curl_Parameter('username', $user),
			new Oara_Curl_Parameter('password', $password)
		);

		$urls = array();
		$urls[] = new Oara_Curl_Request($loginUrl, $valuesFormExport);
		$exportReport = $this->_client->post($urls);
		if (!preg_match("/session=(.*)\"/", $exportReport[0], $matches)) {
			throw new Exception("No session found");
		}
		$this->_sessionId = $matches[1];
		if (preg_match("/URL=(.*)\"/", $exportReport[0], $matches)) {
			$urls = array();
			$urls[] = new Oara_Curl_Request($matches[1], array());
			$exportReport = $this->_client->get($urls);
		}

		$this->_exportMerchantParameters = array(new Oara_Curl_Parameter('apikey', $this->_apiPassword),
			new Oara_Curl_Parameter('Format', 'CSV'),
			new Oara_Curl_Parameter('FieldSeparator', 'comma'),
			new Oara_Curl_Parameter('AffiliateID', $user),
			new Oara_Curl_Parameter('MerchantCategories', 'ALL'),
			new Oara_Curl_Parameter('Fields', 'MerchantID,MerchantName,MerchantURL'),
			new Oara_Curl_Parameter('JoinedMerchants', 'YES'),
			new Oara_Curl_Parameter('MerchantsNotJoined', 'NO'),
		);

		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('apikey', $this->_apiPassword),
			new Oara_Curl_Parameter('Format', 'CSV'),
			new Oara_Curl_Parameter('FieldSeparator', 'comma'),
			new Oara_Curl_Parameter('AffiliateID', $user),
			new Oara_Curl_Parameter('Fields', 'MerchantID,OrderDate,NetworkOrderID,CustomTrackingID,OrderValue,AffiliateCommission,TransactionType,PaidtoAffiliate,DatePaidToAffiliate'),
			new Oara_Curl_Parameter('AffiliateID', $user),
			new Oara_Curl_Parameter('DateFormat', 'DD/MM/YYYY+HH:MN:SS'),
			new Oara_Curl_Parameter('PendingSales', 'YES'),
			new Oara_Curl_Parameter('ValidatedSales', 'YES'),
			new Oara_Curl_Parameter('VoidSales', 'YES'),
			new Oara_Curl_Parameter('GetNewSales', 'YES')
		);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array();

		$valuesFormExport = Oara_Utilities::cloneArray($this->_exportMerchantParameters);
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliate.paidonresults.com/api/merchant-directory?', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0], "\r\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$merchantExportArray = str_getcsv($exportData[$i], ",");
			$obj = Array();
			$obj['cid'] = $merchantExportArray[0];
			$obj['name'] = $merchantExportArray[1];
			$obj['url'] = $merchantExportArray[2];
			$merchants[] = $obj;
		}
		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array();

		$valuesFormExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);

		$urls = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('DateFrom', $dStartDate->toString("yyyy-MM-dd"));
		$valuesFormExport[] = new Oara_Curl_Parameter('DateTo', $dEndDate->toString("yyyy-MM-dd"));
		$urls[] = new Oara_Curl_Request('http://affiliate.paidonresults.com/api/transactions?', $valuesFormExport);
		$exportReport = $this->_client->get($urls);

		$exportData = str_getcsv($exportReport[0], "\r\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ",");
			if (in_array($transactionExportArray[0], $merchantList)) {
				$transaction = array();
				$transaction['merchantId'] = $transactionExportArray[0];

				$transactionDate = new Zend_Date($transactionExportArray[1], "dd/MM/yyyy HH:mm:ss");
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

				$transaction['unique_id'] = $transactionExportArray[2];
				if ($transactionExportArray[3] != null) {
					$transaction['custom_id'] = $transactionExportArray[3];
				}

				$transaction['amount'] = (double) $transactionExportArray[4];
				$transaction['commission'] = (double) $transactionExportArray[5];

				if ($transactionExportArray[6] == 'VALIDATED') {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
					if ($transactionExportArray[6] == 'PENDING') {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
						if ($transactionExportArray[6] == 'VOID') {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						}

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
		$totalOverviews = Array();
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
				$totalOverviews[] = $overview;
			}
		}

		return $totalOverviews;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		$paymentExport = array();
		$paymentExport[] = new Oara_Curl_Parameter('session', $this->_sessionId);

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliate.paidonresults.com/cgi-bin/invoice-status.pl?', $paymentExport);
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('#tableID1 tr');
		foreach ($results as $result) {
			$childrenList = $result->childNodes;
			$numberChildren = $childrenList->length;
			if ($numberChildren == 18) {

				$value = $childrenList->item(14)->nodeValue;
				if (preg_match('/[0-9]+(,[0-9]{3})*(\.[0-9]{2})?$/', $value, $matches)) {
					$obj = array();
					$obj['pid'] = $childrenList->item(4)->nodeValue;
					$date = new Zend_Date($childrenList->item(0)->nodeValue, "dd/MMM/yyyy");
					$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
					$obj['value'] = Oara_Utilities::parseDouble($matches[0]);
					$obj['method'] = 'BACS';
					$paymentHistory[] = $obj;

				}
			}

		}
		$this->_paymentHistory = $paymentHistory;
		return $paymentHistory;
	}

	/**
	 *  It returns the transactions for a payment
	 * @see Oara_Network::paymentTransactions()
	 */
	public function paymentTransactions($paymentId, $merchantList, $startDate) {

		$paymentTransactionList = array();

		$paymentHistory = Oara_Utilities::registerBubbleSort($this->_paymentHistory);

		$paymentStartDate = new Zend_Date($startDate, "yyyy-MM-dd HH:mm:ss");
		$paymentEndDate = null;

		$enc = false;
		$i = 0;
		$payment = null;
		while (!$enc && $i < count($paymentHistory)) {
			$payment = $paymentHistory[$i];
			if ($payment['pid'] == $paymentId) {
				$enc = true;
				$paymentEndDate = new Zend_Date($payment['date'], "yyyy-MM-dd HH:mm:ss");
			}
			$i++;
		}

		if ($enc && $paymentStartDate->compare($paymentEndDate) <= 0) {
			$totalTransactions = array();

			$dateArray = Oara_Utilities::monthsOfDifference(new Zend_Date($startDate, "yyyy-MM-dd HH:mm:ss"), $paymentEndDate);
			for ($i = 0; $i < count($dateArray); $i++) {
				$monthStartDate = clone $dateArray[$i];
				$monthEndDate = null;

				if ($i != count($dateArray) - 1) {
					$monthEndDate = clone $dateArray[$i];
					$monthEndDate->setDay(1);
					$monthEndDate->addMonth(1);
					$monthEndDate->subDay(1);
				} else {
					$monthEndDate = $paymentEndDate;
				}
				$monthEndDate->setHour(23);
				$monthEndDate->setMinute(59);
				$monthEndDate->setSecond(59);

				echo "\n importing from ".$monthStartDate->toString("dd-MM-yyyy HH:mm:ss")." to ".$monthEndDate->toString("dd-MM-yyyy HH:mm:ss")."\n";

				$urls = array();
				$valuesFormExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
				$valuesFormExport[] = new Oara_Curl_Parameter('DateFrom', $monthStartDate->toString("yyyy-MM-dd"));
				$valuesFormExport[] = new Oara_Curl_Parameter('DateTo', $monthEndDate->toString("yyyy-MM-dd"));
				$urls[] = new Oara_Curl_Request('http://affiliate.paidonresults.com/api/transactions?', $valuesFormExport);
				$exportReport = $this->_client->get($urls);

				$exportData = str_getcsv($exportReport[0], "\r\n");
				$num = count($exportData);
				for ($i = 1; $i < $num; $i++) {
					$transactionExportArray = str_getcsv($exportData[$i], ",");
					$paid = $transactionExportArray[7] == "YES" ? true : false;
					if (in_array($transactionExportArray[0], $merchantList) && $paid) {

						$transactionDate = new Zend_Date($transactionExportArray[8], "dd/MM/yyyy HH:mm:ss");
						echo $paymentEndDate->toString("yyyy-MM-dd")."  ".$transactionDate->toString("yyyy-MM-dd")."\n\n";
						if ($paymentEndDate->toString("yyyy-MM-dd") == $transactionDate->toString("yyyy-MM-dd")) {
							$paymentTransactionList[] = $transactionExportArray[2];
						}
					}
				}

			}
		}

		return $paymentTransactionList;
	}
}
