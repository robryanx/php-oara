<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_MyPcBackUP
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_MyPcBackUP extends Oara_Network {

	private $_credentials = null;
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;

	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_PureVPN
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;
		self::logIn();

	}

	private function logIn() {

		$valuesLogin = array(
		new Oara_Curl_Parameter('username', $this->_credentials['user']),
		new Oara_Curl_Parameter('password', $this->_credentials['password']),
		new Oara_Curl_Parameter('login', 'Login'),
		);

		$loginUrl = 'http://affiliates.mypcbackup.com/login';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $this->_credentials);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliates.mypcbackup.com/', array());

		$exportReport = $this->_client->get($urls);
		if (!preg_match("/logout/", $exportReport[0])) {
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
		$obj['cid'] = "1";
		$obj['name'] = "MyPcBackUp";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();

		$urls = array();
		$valuesFromExport = array();
		$valuesFromExport[] = new Oara_Curl_Parameter('hop_id', "0");
		$valuesFromExport[] = new Oara_Curl_Parameter('transaction_id', "");
		$valuesFromExport[] = new Oara_Curl_Parameter('sales', "1");
		$valuesFromExport[] = new Oara_Curl_Parameter('refunds', "1");
		$valuesFromExport[] = new Oara_Curl_Parameter('csv', "Download CSV");
		$valuesFromExport[] = new Oara_Curl_Parameter('start', $dStartDate->toString("MM/dd/yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('end', $dEndDate->toString("MM/dd/yyyy"));
			
		$urls[] = new Oara_Curl_Request('http://affiliates.mypcbackup.com/transactions?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0], "\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ",");
			$transaction = Array();
			$transaction['merchantId'] = 1;
			$transaction['uniqueId'] = $transactionExportArray[2];
			$transactionDate = new Zend_Date($transactionExportArray[0]." ".$transactionExportArray[1], 'yyyy-MM-dd HH:mm:ss', 'en');
			$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
			unset($transactionDate);

			if (preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionExportArray[5], $match)){
				$transaction['amount'] = (double)$match[0];
			}
			if (preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionExportArray[5], $match)){
				$transaction['commission'] = (double)$match[0];
			}
			if ($transactionExportArray[4] == "Sale"){
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
			} else if ($transactionExportArray[4] == "Refund"){
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				$transaction['amount'] = -$transaction['amount'];
				$transaction['commission'] = -$transaction['commission'];
			}
			if ($transactionExportArray[7] != null){
				$transaction['customId'] = $transactionExportArray[7];
			}
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

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliates.mypcbackup.com/paychecks', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$tableList = $dom->query('.transtable');
		if ($tableList->current() != null) {
			$exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
			$num = count($exportData);
			for ($i = 1; $i < $num; $i++) {
				$paymentExportArray = str_getcsv($exportData[$i], ";");
	
				$obj = array();
				$date = new Zend_Date($paymentExportArray[14], "MM/dd/yyyy");
				$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$obj['pid'] = preg_replace("/[^0-9\.,]/", "", $paymentExportArray[14]);
				$obj['method'] = $paymentExportArray[16];
				$value = preg_replace("/[^0-9\.,]/", "", $paymentExportArray[12]);
	
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
