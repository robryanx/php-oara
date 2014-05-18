<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Ebay
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Ebay extends Oara_Network {
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

	private $_credentials = null;
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
		$this->_credentials = $credentials;
		self::logIn();
		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('pt', '2'),
			new Oara_Curl_Parameter('advIdProgIdCombo', ''),
			new Oara_Curl_Parameter('tx_fmt', '1'),
			new Oara_Curl_Parameter('submit_text', 'Download')
		);
		$this->_exportOverviewParameters = array(
			new Oara_Curl_Parameter('advIdProgIdCombo', urldecode('1|')),
			new Oara_Curl_Parameter('submit_text_epc', urldecode('Download'))
		);

	}

	private function logIn() {
		$valuesLogin = array(
			new Oara_Curl_Parameter('login_username', $this->_credentials['user']),
			new Oara_Curl_Parameter('login_password', $this->_credentials['password']),
			new Oara_Curl_Parameter('submit_btn', 'GO'),
			new Oara_Curl_Parameter('hubpage', 'y')
		);

		$loginUrl = 'https://ebaypartnernetwork.com/PublisherLogin?hubpage=y&lang=en-US?';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $this->_credentials);
		if (!self::checkConnection()) {
			throw new Exception("You are not connected\n\n");
		}
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://publisher.ebaypartnernetwork.com/PublisherHome', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('#login_username');
		if (count($results) > 0) {
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
		$obj['name'] = "Ebay";
		$obj['url'] = "https://publisher.ebaypartnernetwork.com";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		self::logIn();
		$totalTransactions = array();
		
		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('start_date', $dStartDate->toString("M/d/yy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('start_date_month', $dStartDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('start_date_day', $dStartDate->toString("d"));
		$valuesFromExport[] = new Oara_Curl_Parameter('start_date_year', $dStartDate->toString("yyyy"));

		$valuesFromExport[] = new Oara_Curl_Parameter('end_date', $dEndDate->toString("M/d/yy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('end_date_month', $dEndDate->toString("M"));
		$valuesFromExport[] = new Oara_Curl_Parameter('end_date_day', $dEndDate->toString("d"));
		$valuesFromExport[] = new Oara_Curl_Parameter('end_date_year', $dEndDate->toString("yyyy"));

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://publisher.ebaypartnernetwork.com/PublisherReportsTx?', $valuesFromExport);
		$exportReport = array();
		$exportData = array();
		try{
			$exportReport = $this->_client->get($urls, 'content', 5);
			$exportData = str_getcsv($exportReport[0], "\n");
		} catch (Exception $e){
			
		}
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], "\t");
			
			if ($transactionExportArray[2] == "Winning Bid (Revenue)"){
				
			
				$transaction = Array();
				$transaction['merchantId'] = 1;
				$transactionDate = new Zend_Date($transactionExportArray[1], 'yyyy-MM-dd', 'en');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				unset($transactionDate);
				if ($transactionExportArray[12] != null) {
					$transaction['custom_id'] = $transactionExportArray[12];
				}
	
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				
				$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[15]);
				$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[3]);
				$totalTransactions[] = $transaction;
			}
		}
		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://publisher.ebaypartnernetwork.com/PublisherAccountPaymentHistory', array());
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('table .aruba_report_table');
		if (count($results) > 0) {
			$exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));
			for ($j = 1; $j < count($exportData); $j++) {

				$paymentExportArray = str_getcsv($exportData[$j], ";");
				$obj = array();
				$paymentDate = new Zend_Date($paymentExportArray[0], "dd/MM/yy", "en");
				$obj['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");
				$obj['pid'] = $paymentDate->toString("yyyyMMdd");
				$obj['method'] = 'BACS';
				if (preg_match("/[-+]?[0-9]*,?[0-9]*\.?[0-9]+/", $paymentExportArray[2], $matches)) {
					$obj['value'] = Oara_Utilities::parseDouble($matches[0]);
				} else {
					throw new Exception("Problem reading payments");
				}

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
