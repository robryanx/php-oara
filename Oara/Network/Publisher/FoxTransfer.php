<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_FoxTransfer
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_FoxTransfer extends Oara_Network {

	private $_credentials = null;
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_SkyScanner
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;
		self::logIn();

	}

	private function logIn() {
		
		$valuesLogin = array(
			new Oara_Curl_Parameter('action', "user_login"),
			new Oara_Curl_Parameter('email', $this->_credentials['user']),
			new Oara_Curl_Parameter('password', $this->_credentials['password']),
		);

		$loginUrl = 'http://www.foxtransfer.eu/index.php?page=login&out=1&language=1';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $this->_credentials);
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www.foxtransfer.eu/index.php?language=1', array());
		$exportReport = $this->_client->get($urls);
		if (!preg_match("/Log Out/", $exportReport[0], $match)){
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
		$obj['name'] = "FoxTransfer";
		$obj['url'] = "http://www.foxtransfer.eu";
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
		$url = "http://www.foxtransfer.eu/index.php?q=prices.en.html&page=affiliate_orders&language=1&basedir=theme2&what=record_time&what=record_time&fy={$dStartDate->toString("yyyy")}&fm={$dStartDate->toString("M")}&fd={$dStartDate->toString("d")}&ty={$dEndDate->toString("yyyy")}&tm={$dEndDate->toString("M")}&td={$dEndDate->toString("d")}";
		$urls[] = new Oara_Curl_Request($url, array());

		$exportReport = array();
		$exportReport = $this->_client->get($urls);
		
		$exportReport = str_replace("<?xml version=\"1.0\" encoding=\"UTF-8\"?>", "", $exportReport);
		$dom = new Zend_Dom_Query($exportReport[0]);
		
		$tableList = $dom->query('#tartalom-hatter table[cellspacing="0"][cellpadding="3"][border="0"]');
		$exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
	    
		$num = count($exportData);
		for ($i = 3; $i < $num; $i++) {

			$transactionExportArray = str_getcsv($exportData[$i], ";");
			$transaction = Array();
			$transaction['merchantId'] = 1;
			$transaction['unique_id'] = $transactionExportArray[0];
			$transactionDate = new Zend_Date($transactionExportArray[5], 'yyyy.MMM.dd HH:mm:00', 'en');
			$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
			if ($transactionExportArray[7] == "Confirmed"){
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
			} else if ($transactionExportArray[7] == "Cancelled"){
				$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
			} else {
				throw new Exception("New status found");
			}
			
			$transaction['amount'] = Oara_Utilities::parseDouble(preg_replace("/[^0-9\.,]/", "", $transactionExportArray[10]));
			$transaction['commission'] = Oara_Utilities::parseDouble(preg_replace("/[^0-9\.,]/", "", $transactionExportArray[13]));

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