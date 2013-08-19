<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Skimlinks
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Skimlinks extends Oara_Network {
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
			new Oara_Curl_Parameter('menu', ''),
			new Oara_Curl_Parameter('btn-login', 'Login')
		);

		$loginUrl = 'https://skimlinks.com/login';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('type', 'merchant'),
			new Oara_Curl_Parameter('export', 'csv'),
			new Oara_Curl_Parameter('domain', '0'),
			new Oara_Curl_Parameter('merchant', '-2'),
			new Oara_Curl_Parameter('product', '1')
		);

		$this->_exportOverviewParameters = array(new Oara_Curl_Parameter('type', 'merchant'),
			new Oara_Curl_Parameter('export', 'csv'),
			new Oara_Curl_Parameter('domain', '0'),
			new Oara_Curl_Parameter('merchant', '-2'),
			new Oara_Curl_Parameter('product', '1')
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
		$urls[] = new Oara_Curl_Request('https://hub.skimlinks.com/go/full', array());
		$exportReport = $this->_client->get($urls);
		
		
		
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://accounts.skimlinks.com/', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('a[href*="kilep="]');
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
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://accounts.skimlinks.com/reports&report=daily', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$merchantList = $dom->query('#merchant_select');
		$merchantList = $merchantList->current();
		if ($merchantList != null) {
			$merchantLines = $merchantList->childNodes;
			for ($i = 0; $i < $merchantLines->length; $i++) {
				if ($merchantLines->item($i)->getAttribute("value") > 0) {
					$obj = array();
					$obj['cid'] = $merchantLines->item($i)->getAttribute("value");
					$obj['name'] = $merchantLines->item($i)->nodeValue;
					$merchants[] = $obj;
				}
			}
		}
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();
		$mothUrls = array();
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		$dateArraySize = sizeof($dateArray);
		for ($i = 0; $i < $dateArraySize; $i++) {
			$valuesFormExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
			$valuesFormExport[] = new Oara_Curl_Parameter('datefrom', $dateArray[$i]->toString("yyyy-MM-dd"));
			$valuesFormExport[] = new Oara_Curl_Parameter('dateto', $dateArray[$i]->toString("yyyy-MM-dd"));
			$mothUrls[] = new Oara_Curl_Request('https://accounts.skimlinks.com/reports_export.php?', $valuesFormExport);
		}
		$exportReport = $this->_client->get($mothUrls);
		$exportReportNumber = count($exportReport);
		for ($i = 0; $i < $exportReportNumber; $i++) {
			$exportData = str_getcsv($exportReport[$i], "\n");
			$num = count($exportData);
			for ($j = 1; $j < $num - 5; $j++) {
				$transactionExportArray = str_getcsv($exportData[$j], ",");
				$revenue = 0;
				if (preg_match("/[-+]?[0-9]*,?[0-9]*\.?[0-9]+/", $transactionExportArray[6], $matches)) {
					$revenue = Oara_Utilities::parseDouble($matches[0]);
				}
				if ($revenue != 0 && isset($merchantMap[$transactionExportArray[0]]) && in_array($merchantMap[$transactionExportArray[0]], $merchantList)) {

					$transaction = Array();
					$transaction['merchantId'] = $merchantMap[$transactionExportArray[0]];
					$transactionDateString = $mothUrls[$i]->getParameter(5)->getValue();
					$transaction['date'] = $transactionDateString;

					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;

					if (preg_match("/[-+]?[0-9]*,?[0-9]*\.?[0-9]+/", $transactionExportArray[3], $matches)) {
						$transaction['amount'] = Oara_Utilities::parseDouble($matches[0]);
					}
					$transaction['commission'] = $revenue;
					$totalTransactions[] = $transaction;
				}
			}
		}

		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
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
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

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
