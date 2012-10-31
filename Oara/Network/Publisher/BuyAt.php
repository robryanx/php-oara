<?php
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Buy
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_BuyAt extends Oara_Network {
	/**
	 * Export Merchants Parameters
	 * @var array
	 */
	private $_exportMerchantParameters = null;
	/**
	 * Export Transaction Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;
	/**
	 * Export Overview Parameters
	 * @var array
	 */
	private $_exportOverviewParameters = null;
	/**
	 * Export Payment Parameters
	 * @var array
	 */
	private $_exportPaymentParameters = null;

	/**
	 *
	 * Payment Details Parameters
	 * @var unknown_type
	 */
	private $_exportPaymentDetailsParameters = null;

	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $buy
	 * @return Oara_Network_Publisher_Buy_Api
	 */
	public function __construct($credentials) {

		$user = $credentials['user'];
		$password = $credentials['password'];
		$passwordApi = md5($password);

		$loginUrl = 'https://users.buy.at/ma/index.php/main/login';

		$contact = null;

		$exportPass = null;

		$valuesLogin = array(new Oara_Curl_Parameter('email', $user),
			new Oara_Curl_Parameter('password', $password)
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$this->_exportMerchantParameters = array(new Oara_Curl_Parameter('handle', '0'),
			new Oara_Curl_Parameter('orderby', 'programme_name'),
			new Oara_Curl_Parameter('dir', 'asc'),
			new Oara_Curl_Parameter('filter_sector', '0'),
			new Oara_Curl_Parameter('filter_status', 'y'),
			new Oara_Curl_Parameter('filter_region', '0'),
			new Oara_Curl_Parameter('query', ''),
			new Oara_Curl_Parameter('has_feed', '0'),
			new Oara_Curl_Parameter('format', 'xml'),
			new Oara_Curl_Parameter('email', $user),
			new Oara_Curl_Parameter('password', $passwordApi)
		);

		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('daterange', 'CUSTOM'),
			new Oara_Curl_Parameter('handle', '0'),
			new Oara_Curl_Parameter('status', ''),
			new Oara_Curl_Parameter('include_rejected', '0'),
			new Oara_Curl_Parameter('include_consolidated', '0'),
			new Oara_Curl_Parameter('include_profit_plus', '1'),
			new Oara_Curl_Parameter('orderby', 'transaction_date_time'),
			new Oara_Curl_Parameter('dir', 'asc'),
			new Oara_Curl_Parameter('showfields%5Bprogramme_name%5D', 'programme_name'),
			new Oara_Curl_Parameter('showfields%5Bstatus%5D', 'status'),
			new Oara_Curl_Parameter('showfields%5Blink_id%5D', 'link_id'),
			new Oara_Curl_Parameter('showfields%5Btransaction_date_time%5D', 'transaction_date_time'),
			new Oara_Curl_Parameter('showfields%5Bquantity%5D', 'quantity'),
			new Oara_Curl_Parameter('showfields%5Btransaction_value%5D', 'transaction_value'),
			new Oara_Curl_Parameter('showfields%5Bcommission%5D', 'commission'),
			new Oara_Curl_Parameter('showfields%5Bunique_id%5D', 'unique_id'),
			new Oara_Curl_Parameter('customise', 'Go'),
			new Oara_Curl_Parameter('format', 'csv'),
			new Oara_Curl_Parameter('email', $user),
			new Oara_Curl_Parameter('password', $passwordApi)
		);

		$this->_exportOverviewParameters = array(new Oara_Curl_Parameter('daterange', 'CUSTOM'),
			new Oara_Curl_Parameter('handle', '0'),
			new Oara_Curl_Parameter('status', ''),
			new Oara_Curl_Parameter('include_rejected', '0'),
			new Oara_Curl_Parameter('include_consolidated', '0'),
			new Oara_Curl_Parameter('orderby', 'date'),
			new Oara_Curl_Parameter('dir', 'asc'),
			new Oara_Curl_Parameter('showfields%5Bclicks%5D', 'clicks'),
			new Oara_Curl_Parameter('showfields%5Bsales%5D', 'sales'),
			new Oara_Curl_Parameter('showfields%5Btransaction_value%5D', 'transaction_value'),
			new Oara_Curl_Parameter('showfields%5Bcommission%5D', 'commission'),
			new Oara_Curl_Parameter('customise', 'Go'),
			new Oara_Curl_Parameter('format', 'csv'),
			new Oara_Curl_Parameter('email', $user),
			new Oara_Curl_Parameter('password', $passwordApi)
		);
		$this->_exportPaymentParameters = array(new Oara_Curl_Parameter('handle', '0'),
			new Oara_Curl_Parameter('orderby', 'date'),
			new Oara_Curl_Parameter('dir', 'asc'),
			new Oara_Curl_Parameter('showfields%5Bpayment_method%5D', 'payment_method'),
			new Oara_Curl_Parameter('customise', 'Go'),
			new Oara_Curl_Parameter('format', 'csv'),
			new Oara_Curl_Parameter('email', $user),
			new Oara_Curl_Parameter('password', $passwordApi)
		);

		$this->_exportPaymentDetailsParameters = array(
			new Oara_Curl_Parameter('prog_id', '0'),
			new Oara_Curl_Parameter('handle', '0'),
			new Oara_Curl_Parameter('orderby', 'date'),
			new Oara_Curl_Parameter('dir', 'asc'),
			new Oara_Curl_Parameter('showfields%5Bprogramme_name%5D', 'programme_name'),
			new Oara_Curl_Parameter('showfields%5Blink_id%5D', 'link_id'),
			new Oara_Curl_Parameter('showfields%5Btransaction_value%5D', 'transaction_value'),
			new Oara_Curl_Parameter('showfields%5Bcommission%5D', 'commission'),
			new Oara_Curl_Parameter('showfields%5Bunique_id%5D', 'unique_id'),
			new Oara_Curl_Parameter('&showfields%5Bcommission%5D', 'commission'),
			new Oara_Curl_Parameter('customise', 'Go'),
			new Oara_Curl_Parameter('format', 'csv'),
			new Oara_Curl_Parameter('email', $user),
			new Oara_Curl_Parameter('password', $passwordApi)
		);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		$urls = array();
		$valuesFromExport = $this->_exportMerchantParameters;
		$urls[] = new Oara_Curl_Request('http://users.buy.at/ma/index.php/affiliateProgrammes/programmes?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		if (!preg_match("/Password/", $exportReport[0], $matches)) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$valuesFromExport = $this->_exportMerchantParameters;
		$merchants = Array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://users.buy.at/ma/index.php/affiliateProgrammes/programmes?', $valuesFromExport);

		$exportReport = $this->_client->get($urls);
		$xml = new SimpleXMLElement($exportReport[0]);
		$list = $xml->body->resultset;
		foreach ($list as $merchant) {
			$obj = array();
			$obj['cid'] = (string) $merchant->programme_id;
			$obj['name'] = (string) $merchant->programme_name;
			$obj['url'] = "";
			$merchants[] = $obj;
		}
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('from_year', $dStartDate->get(Zend_Date::YEAR));
		$valuesFromExport[] = new Oara_Curl_Parameter('from_month', $dStartDate->get(Zend_Date::MONTH));
		$valuesFromExport[] = new Oara_Curl_Parameter('from_day', $dStartDate->get(Zend_Date::DAY));
		$valuesFromExport[] = new Oara_Curl_Parameter('to_year', $dEndDate->get(Zend_Date::YEAR));
		$valuesFromExport[] = new Oara_Curl_Parameter('to_month', $dEndDate->get(Zend_Date::MONTH));
		$valuesFromExport[] = new Oara_Curl_Parameter('to_day', $dEndDate->get(Zend_Date::DAY));
		$valuesFromExport[] = new Oara_Curl_Parameter('prog_id', '0');

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://users.buy.at/ma/index.php/affiliateReport/commissionValue?', $valuesFromExport);

		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0], "\r\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ",");
			if (in_array((int) $transactionExportArray[12], $merchantList)) {
				$transaction = Array();
				$merchantId = (int) $transactionExportArray[12];
				$transaction['merchantId'] = $merchantId;
				$transactionDate = new Zend_Date($transactionExportArray[5], 'dd-MM-yyyy HH:mm:ss');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				$transaction['unique_id'] = $transactionExportArray[8];

				if ($transactionExportArray[6] != null) {
					$transaction['custom_id'] = $transactionExportArray[6];
				}

				if ($transactionExportArray[2] == 'Approved') {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
					if ($transactionExportArray[2] == 'Pending') {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
						if ($transactionExportArray[2] == 'Held') {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						}
				$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[9]);
				$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[10]);
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
		$transactionArray = array();
		$mothOverviewUrls = array();

		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('from_year', $dStartDate->get(Zend_Date::YEAR));
		$valuesFromExport[] = new Oara_Curl_Parameter('from_month', $dStartDate->get(Zend_Date::MONTH));
		$valuesFromExport[] = new Oara_Curl_Parameter('from_day', $dStartDate->get(Zend_Date::DAY));
		$valuesFromExport[] = new Oara_Curl_Parameter('to_year', $dEndDate->get(Zend_Date::YEAR));
		$valuesFromExport[] = new Oara_Curl_Parameter('to_month', $dEndDate->get(Zend_Date::MONTH));
		$valuesFromExport[] = new Oara_Curl_Parameter('to_day', $dEndDate->get(Zend_Date::DAY));
		$valuesFromExport[] = new Oara_Curl_Parameter('prog_id', '0');

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://users.buy.at/ma/index.php/affiliateReport/dailySummary?', $valuesFromExport);

		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0], "\r\n");
		if (self::checkOverview($exportData)) {
			$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
			foreach ($merchantList as $idMerchant) {
				$valuesFromExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
				$valuesFromExport[] = new Oara_Curl_Parameter('from_year', $dStartDate->get(Zend_Date::YEAR));
				$valuesFromExport[] = new Oara_Curl_Parameter('from_month', $dStartDate->get(Zend_Date::MONTH));
				$valuesFromExport[] = new Oara_Curl_Parameter('from_day', $dStartDate->get(Zend_Date::DAY));
				$valuesFromExport[] = new Oara_Curl_Parameter('to_year', $dEndDate->get(Zend_Date::YEAR));
				$valuesFromExport[] = new Oara_Curl_Parameter('to_month', $dEndDate->get(Zend_Date::MONTH));
				$valuesFromExport[] = new Oara_Curl_Parameter('to_day', $dEndDate->get(Zend_Date::DAY));
				$valuesFromExport[] = new Oara_Curl_Parameter('prog_id', $idMerchant);
				$mothOverviewUrls[] = new Oara_Curl_Request('http://users.buy.at/ma/index.php/affiliateReport/dailySummary?', $valuesFromExport);
			}
		}
		if (count($mothOverviewUrls) > 0) {
			$exportReport = $this->_client->get($mothOverviewUrls);
			$exportReportNumber = count($exportReport);
			for ($i = 0; $i < $exportReportNumber; $i++) {
				$exportData = str_getcsv($exportReport[$i], "\r\n");
				$num = count($exportData);
				if (self::checkOverview($exportData)) {
					for ($j = 1; $j < $num; $j++) {
						$overviewExportArray = str_getcsv($exportData[$j], ",");
						$parameter = $mothOverviewUrls[$i]->getParameter(21);
						$parameterMerchantId = $parameter->getValue();

						$obj = array();
						$obj['merchantId'] = $parameterMerchantId;
						$overviewDate = new Zend_Date($overviewExportArray[0], "yyyy-MM-dd");
						$obj['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");

						$obj['impression_number'] = 0;
						$obj['click_number'] = $overviewExportArray[1];
						$obj['transaction_number'] = 0;

						$obj['transaction_confirmed_commission'] = 0;
						$obj['transaction_confirmed_value'] = 0;
						$obj['transaction_pending_commission'] = 0;
						$obj['transaction_pending_value'] = 0;
						$obj['transaction_declined_commission'] = 0;
						$obj['transaction_declined_value'] = 0;
						$obj['transaction_paid_value'] = 0;
						$obj['transaction_paid_commission'] = 0;
						$transactionDateArray = Oara_Utilities::getDayFromArray($obj['merchantId'], $transactionArray, $overviewDate, true);
						foreach ($transactionDateArray as $transaction) {
							$obj['transaction_number']++;
							if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED) {
								$obj['transaction_confirmed_value'] += $transaction['amount'];
								$obj['transaction_confirmed_commission'] += $transaction['commission'];
							} else
								if ($transaction['status'] == Oara_Utilities::STATUS_PENDING) {
									$obj['transaction_pending_value'] += $transaction['amount'];
									$obj['transaction_pending_commission'] += $transaction['commission'];
								} else
									if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED) {
										$obj['transaction_declined_value'] += $transaction['amount'];
										$obj['transaction_declined_commission'] += $transaction['commission'];
									} else
										if ($transaction['status'] == Oara_Utilities::STATUS_PAID) {
											$obj['transaction_paid_value'] += $transaction['amount'];
											$obj['transaction_paid_commission'] += $transaction['commission'];
										}
						}
						if (Oara_Utilities::checkRegister($obj)) {
							$overviewArray[] = $obj;
						}
					}
				}
			}
		}
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
	 * Check the overview
	 * @param array $exportData
	 * @return boolean
	 */
	private function checkOverview($exportData) {
		$result = false;
		$num = count($exportData);
		$j = 1;
		while ($j < $num && !$result) {
			$overviewExportArray = str_getcsv($exportData[$j], ",");
			$result = self::checkOverviewRegister($overviewExportArray);
			$j++;
		}

		return $result;
	}
	/**
	 * Check If the register has interesting information
	 * @param array $register
	 * @param array $properties
	 * @return boolean
	 */
	public static function checkOverviewRegister(array $register) {
		$ok = false;
		$i = 1;
		while ($i < count($register) && !$ok) {
			if ($register[$i] != 0 && $register[$i] != null) {
				$ok = true;
			}
			$i++;
		}
		return $ok;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		$urls = array();
		$valuesFromExport = $this->_exportPaymentParameters;
		$urls[] = new Oara_Curl_Request('http://users.buy.at/ma/index.php/affiliatePayments/paymentsHistory?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0], "\r\n");
		$num = count($exportData);
		for ($j = 1; $j < $num; $j++) {
			$paymentData = str_getcsv($exportData[$j], ",");
			$obj = array();
			$date = new Zend_Date($paymentData[0], "dd-MM-yyyy");
			$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
			$obj['method'] = $paymentData[1];
			$obj['value'] = Oara_Utilities::parseDouble($paymentData[2]);
			$obj['pid'] = $paymentData[4];
			$paymentHistory[] = $obj;
		}
		return $paymentHistory;
	}

	/**
	 *
	 * It returns the transactions for a payment
	 * @param int $paymentId
	 */
	public function paymentTransactions($paymentId, $merchantList, $startDate) {
		$transactionList = array();

		$urls = array();
		$valuesFormExport = $this->_exportPaymentDetailsParameters;
		$valuesFormExport[] = new Oara_Curl_Parameter('payment_id', $paymentId);
		$urls[] = new Oara_Curl_Request('https://users.buy.at/ma/index.php/affiliatePayments/paymentDetails?', $valuesFormExport);
		$exportReportList = $this->_client->get($urls);
		foreach ($exportReportList as $exportReport) {
			$exportReportData = str_getcsv($exportReport, "\r\n");
			$num = count($exportReportData);
			for ($i = 2; $i < $num - 1; $i++) {
				$transactionArray = str_getcsv($exportReportData[$i], ",");
				$transactionList[] = $transactionArray[5];
			}
		}

		return $transactionList;
	}
}
