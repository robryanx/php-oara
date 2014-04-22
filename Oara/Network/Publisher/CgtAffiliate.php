<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_CgtAffiliate
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_CgtAffiliate extends Oara_Network {


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
				new Oara_Curl_Parameter('userid', $user),
				new Oara_Curl_Parameter('password', $password),
		);
		$loginUrl = 'http://www.cgtaffiliate.com/idevaffiliate/login.php';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);



	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www.cgtaffiliate.com/idevaffiliate/account.php', array());
		$exportReport = $this->_client->get($urls);
		
		if (preg_match("/Logout/", $exportReport[0])) {
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
		$obj['name'] = "Custom Greek Threads";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();

		$transactionUrl = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www.cgtaffiliate.com/idevaffiliate/account.php?page=4&report=1', array());
		$exportReport = $this->_client->get($urls);
		$totalTransactions = array_merge($totalTransactions, self::readTransactions($exportReport[0]));
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www.cgtaffiliate.com/idevaffiliate/account.php?page=4&report=3', array());
		$exportReport = $this->_client->get($urls);
		$totalTransactions = array_merge($totalTransactions, self::readTransactions($exportReport[0]));
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www.cgtaffiliate.com/idevaffiliate/account.php?page=4&report=4', array());
		$exportReport = $this->_client->get($urls);
		$totalTransactions = array_merge($totalTransactions, self::readTransactions($exportReport[0]));

		return $totalTransactions;
	}
	
	private function readTransactions($html){
		$totalTransactions = array();
		
		$dom = new Zend_Dom_Query($html);
		$tableList = $dom->query('table[bgcolor="#003366"][align="center"][width="100%"]');
		$exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
		$num = count($exportData);
		for ($i = 3; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ";");
		
			$transaction = Array();
			$transaction['merchantId'] = 1;
			$transaction['date'] =  preg_replace("/[^0-9\-]/", "", $transactionExportArray[0])." 00:00:00";
			
			$transactionExportArray[1] = trim($transactionExportArray[1]);
		
			if (preg_match("/Paid/", $transactionExportArray[1])){
				$transaction['status'] = Oara_Utilities::STATUS_PAID;
			} else if (preg_match("/Pending/", $transactionExportArray[1])){
				$transaction['status'] = Oara_Utilities::STATUS_PENDING;
			} else if (preg_match("/Approved/", $transactionExportArray[1])){
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
			}
				
			$transaction['amount'] = preg_replace("/[^0-9\.,]/", "", $transactionExportArray[2]);
			$transaction['commission'] = preg_replace("/[^0-9\.,]/", "", $transactionExportArray[2]);
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
