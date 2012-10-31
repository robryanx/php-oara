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
			new Oara_Curl_Parameter('action', 'do_login'),
			new Oara_Curl_Parameter('url', 'aff_man'),
			new Oara_Curl_Parameter('uid', time())
		);

		$loginUrl = 'https://www.ladbrokes.com/aff_man';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('action', 'do_report_summary'),
			new Oara_Curl_Parameter('daterange', '7'),
			new Oara_Curl_Parameter('product', '-2'),
			new Oara_Curl_Parameter('periods', 'custom'),
			new Oara_Curl_Parameter('num_impressions', 'on'),
			new Oara_Curl_Parameter('num_clicks', 'on'),
			new Oara_Curl_Parameter('net_profit', 'on'),
			new Oara_Curl_Parameter('earnings_total', 'on')
		);

		$this->_exportOverviewParameters = array(new Oara_Curl_Parameter('action', 'do_report_summary'),
			new Oara_Curl_Parameter('daterange', '7'),
			new Oara_Curl_Parameter('product', '-2'),
			new Oara_Curl_Parameter('periods', 'custom'),
			new Oara_Curl_Parameter('num_impressions', 'on'),
			new Oara_Curl_Parameter('num_clicks', 'on'),
			new Oara_Curl_Parameter('net_profit', 'on'),
			new Oara_Curl_Parameter('earnings_total', 'on')
		);

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
		$urls[] = new Oara_Curl_Request('https://www.ladbrokes.com/aff_man', array());
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('a .logout_bt');
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

		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('fromDate', $dStartDate->toString("dd/MM/yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('until', $dEndDate->toString("dd/MM/yyyy"));

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.ladbrokes.com/aff_man', $valuesFromExport);
		$exportReport = $this->_client->post($urls);
		$exportReport[0] = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $exportReport[0]);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$tableList = $dom->query('#results_table');
		$exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
		$num = count($exportData);
		for ($i = 2; $i < $num - 2; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ";");
			if (Oara_Utilities::parseDouble($transactionExportArray[4]) != 0) {

				$transaction = Array();
				$transaction['merchantId'] = 1;
				$transactionDate = new Zend_Date($transactionExportArray[0], 'dd-MM-yy', 'en');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				if (Oara_Utilities::parseDouble($transactionExportArray[5]) == 0) {
					$transaction['status'] = Oara_Utilities::STATUS_PENDING;
				} else {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				}

				$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[4]);
				$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[5]);
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

		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('fromDate', $dStartDate->toString("dd/MM/yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('until', $dEndDate->toString("dd/MM/yyyy"));

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.ladbrokes.com/aff_man', $valuesFromExport);
		$exportReport = $this->_client->post($urls);
		$exportReport[0] = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $exportReport[0]);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$tableList = $dom->query('#results_table');
		$exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
		$num = count($exportData);
		for ($i = 2; $i < $num - 2; $i++) {
			$overviewExportArray = str_getcsv($exportData[$i], ";");

			$overview = Array();
			$overview['merchantId'] = 1;
			$overviewDate = new Zend_Date($overviewExportArray[0], 'dd-MM-yy', 'en');
			$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");

			$overview['click_number'] = (int) $overviewExportArray[3];
			$overview['impression_number'] = (int) $overviewExportArray[2];
			$overview['transaction_number'] = 0;
			$overview['transaction_confirmed_value'] = 0;
			$overview['transaction_confirmed_commission'] = 0;
			$overview['transaction_pending_value'] = 0;
			$overview['transaction_pending_commission'] = 0;
			$overview['transaction_declined_value'] = 0;
			$overview['transaction_declined_commission'] = 0;
			$overview['transaction_paid_value'] = 0;
			$overview['transaction_paid_commission'] = 0;
			$transactionDateArray = Oara_Utilities::getDayFromArray($overview['merchantId'], $transactionArray, $overviewDate, true);
			foreach ($transactionDateArray as $transaction) {
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
			if (Oara_Utilities::checkRegister($overview)) {
				$overviewArray[] = $overview;
			}
		}

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
		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportPaymentParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('fromDate', "01/01/2000");
		$dEndDate = new Zend_Date();
		$valuesFromExport[] = new Oara_Curl_Parameter('until', $dEndDate->toString("dd/MM/yyyy"));
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.ladbrokes.com/aff_man', $valuesFromExport);
		$exportReport = $this->_client->post($urls);
		$exportReport[0] = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $exportReport[0]);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$tableList = $dom->query('#results_table');
		$exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
		$num = count($exportData);
		for ($i = 2; $i < $num - 2; $i++) {
			$paymentExportArray = str_getcsv($exportData[$i], ";");
			$obj = array();
			$date = new Zend_Date($paymentExportArray[1], "dd/MM/yy", 'en');
			$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
			$obj['pid'] = $i - 1;
			$obj['value'] = $paymentExportArray[9];
			$obj['method'] = 'BACS';
			$paymentHistory[] = $obj;
		}
		return $paymentHistory;
	}
	/**
	 *
	 * Function that Convert from a table to Csv
	 * @param unknown_type $html
	 */
	private function htmlToCsv($html) {
		$html = str_replace(array("\t", "\r", "\n"), "", $html);
		$csv = "";
		$dom = new Zend_Dom_Query($html);
		$results = $dom->query('tr');
		$count = count($results); // get number of matches: 4
		foreach ($results as $result) {
			$tdList = $result->childNodes;
			$tdNumber = $tdList->length;
			if ($tdNumber > 0) {
				for ($i = 0; $i < $tdNumber; $i++) {
					$value = $tdList->item($i)->nodeValue;
					if ($i != $tdNumber - 1) {
						$csv .= trim($value).";";
					} else {
						$csv .= trim($value);
					}
				}
				$csv .= "\n";
			}
		}
		$exportData = str_getcsv($csv, "\n");
		return $exportData;
	}
	/**
	 *
	 * Function that returns the innet HTML code
	 * @param unknown_type $element
	 */
	private function DOMinnerHTML($element) {
		$innerHTML = "";
		if ($element != null) {
			$children = $element->childNodes;
			foreach ($children as $child) {
				$tmp_dom = new DOMDocument();
				$tmp_dom->appendChild($tmp_dom->importNode($child, true));
				$innerHTML .= trim($tmp_dom->saveHTML());
			}
		}
		return $innerHTML;
	}

}
