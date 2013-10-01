<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_AffiliateGateway
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AffiliateGateway extends Oara_Network {
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
	 * Export Payment Parameters
	 * @var array
	 */
	private $_exportPaymentParameters = null;
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	
	private $_extension = null;
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
			new Oara_Curl_Parameter('password', $password)
		);
		
		$extension = null;
		if ($credentials["network"] == "uk"){
			$extension = "https://www.tagpm.com";
		} else if ($credentials["network"] == "au"){
			$extension = "https://www.tagadmin.com.au";
		}
		$this->_extension = $extension;
		
		
		$loginUrl = "{$this->_extension}/login.html";
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('period', '8'),
			new Oara_Curl_Parameter('websiteId', '-1'),
			new Oara_Curl_Parameter('merchantId', '-1'),
			new Oara_Curl_Parameter('subId', ''),
			new Oara_Curl_Parameter('approvalStatus', '-1'),
			new Oara_Curl_Parameter('records', '20'),
			new Oara_Curl_Parameter('sortField', 'purchDate'),
			new Oara_Curl_Parameter('time', '1'),
			new Oara_Curl_Parameter('p', '1'),
			new Oara_Curl_Parameter('changePage', '1'),
			new Oara_Curl_Parameter('oldColumn', 'purchDate'),
			new Oara_Curl_Parameter('order', 'down'),
			new Oara_Curl_Parameter('mId', '-1'),
			new Oara_Curl_Parameter('submittedPeriod', '8'),
			new Oara_Curl_Parameter('submittedSubId', ''),
			new Oara_Curl_Parameter('exportType', 'csv'),
			new Oara_Curl_Parameter('reportTitle', 'report'),
		);

		$this->_exportPaymentParameters = array();

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request("{$this->_extension}/affiliate_home.html", array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('.logout-a');
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

		$valuesFromExport = array();
		$valuesFromExport[] = new Oara_Curl_Parameter('p', "");
		$valuesFromExport[] = new Oara_Curl_Parameter('time', "1");
		$valuesFromExport[] = new Oara_Curl_Parameter('changePage', "");
		$valuesFromExport[] = new Oara_Curl_Parameter('oldColumn', "programmeId");
		$valuesFromExport[] = new Oara_Curl_Parameter('sortField', "programmeId");
		$valuesFromExport[] = new Oara_Curl_Parameter('order', "up");
		$valuesFromExport[] = new Oara_Curl_Parameter('records', "-1");
		$urls = array();
		$urls[] = new Oara_Curl_Request("{$this->_extension}/affiliate_program_active.html?", $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$tableList = $dom->query('#bluetablecontent > table');
		$exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
		$num = count($exportData);
		for ($i = 4; $i < $num; $i++) {
			$merchantExportArray = str_getcsv($exportData[$i], ";");

			$obj = array();
			$obj['cid'] = $merchantExportArray[0];
			$obj['name'] = $merchantExportArray[1];
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

		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('startDate', $dStartDate->toString("dd/MM/yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('endDate', $dEndDate->toString("dd/MM/yyyy"));

		$urls = array();
		$urls[] = new Oara_Curl_Request("{$this->_extension}/affiliate_statistic_transaction.html?", $valuesFromExport);
		try {

			$exportReport = $this->_client->get($urls);
			$exportData = str_getcsv($exportReport[0], "\n");
			$num = count($exportData);
			for ($i = 1; $i < $num; $i++) {
				$transactionExportArray = str_getcsv($exportData[$i], ",");
				if (isset($merchantMap[$transactionExportArray[2]])) {
					$merchantId = $merchantMap[$transactionExportArray[2]];
					if (in_array($merchantId, $merchantList)) {

						$transaction = Array();
						$transaction['merchantId'] = $merchantId;
						$transactionDate = new Zend_Date($transactionExportArray[4], 'dd/MM/yyyy HH:mm:ss', 'en');
						$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
						$transaction['unique_id'] = $transactionExportArray[0];

						if ($transactionExportArray[11] == "Approved" || $transactionExportArray[11] == "Approve") {
							$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
						} else
							if ($transactionExportArray[11] == "Pending") {
								$transaction['status'] = Oara_Utilities::STATUS_PENDING;
							} else
								if ($transactionExportArray[11] == "Declined") {
									$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
								}
						$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[7]);
						$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[8]);
						$totalTransactions[] = $transaction;
					}
				}

			}
		} catch (Exception $e) {

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

		$urls = array();
		$urls[] = new Oara_Curl_Request("{$this->_extension}/affiliate_invoice.html?", array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$tableList = $dom->query('.bluetable');
		$exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
		$num = count($exportData);
		for ($i = 4; $i < $num; $i++) {
			$paymentExportArray = str_getcsv($exportData[$i], ";");
			if (count($paymentExportArray) > 7){
				$obj = array();
				$date = new Zend_Date($paymentExportArray[1], "dd/MM/yyyy");
				$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$obj['pid'] = preg_replace("/[^0-9\.,]/", "", $paymentExportArray[0]);
				$obj['method'] = 'BACS';
				$value = preg_replace("/[^0-9\.,]/", "", $paymentExportArray[8]);
				$obj['value'] = Oara_Utilities::parseDouble($value);
				$paymentHistory[] = $obj;
			}
			
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
		$children = $element->childNodes;
		foreach ($children as $child) {
			$tmp_dom = new DOMDocument();
			$tmp_dom->appendChild($tmp_dom->importNode($child, true));
			$innerHTML .= trim($tmp_dom->saveHTML());
		}
		return $innerHTML;
	}

}
