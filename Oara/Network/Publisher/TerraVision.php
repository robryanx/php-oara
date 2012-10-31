<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Tv
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_TerraVision extends Oara_Network {
	/**
	 * Export client.
	 * @var Oara_Curl_Access
	 */
	private $_client = null;

	/**
	 * Constructor and Login
	 * @param $cartrawler
	 * @return Oara_Network_Publisher_Tv_Export
	 */
	public function __construct($credentials) {

		$user = $credentials['user'];
		$password = $credentials['password'];
		$loginUrl = 'http://booking.terravision.eu/chkloginp.asp?lng=EN';

		$valuesLogin = array(new Oara_Curl_Parameter('user', $user),
			new Oara_Curl_Parameter('password', $password),
			new Oara_Curl_Parameter('bookLog1', '   Login   '),
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;

		$date = new Zend_Date();
		$valuesFormExport = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('mese', $date->toString("MM"));
		$valuesFormExport[] = new Oara_Curl_Parameter('anno', $date->toString("yyyy"));
		$valuesFormExport[] = new Oara_Curl_Parameter('vai', '  Go  ');
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://booking.terravision.eu/statsales.asp?', $valuesFormExport);
		$exportReport = $this->_client->post($urls);
		if (preg_match("/login.asp?/", $exportReport[0], $matches)) {
			$connection = false;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array();
		$obj = Array();
		$obj['cid'] = 1;
		$obj['name'] = 'Terravision';
		$obj['url'] = 'https://www.terravision.eu/';
		$merchants[] = $obj;

		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array();

		$exportTransactionList = self::readTransactionTable($dStartDate, $dEndDate);
		foreach ($exportTransactionList as $exportTransaction) {
			$transaction = array();

			$transaction['merchantId'] = 1;
			$transaction['date'] = $exportTransaction->date;
			$transaction['amount'] = (double) $exportTransaction->amount;
			$transaction['commission'] = (double) $exportTransaction->commission;
			if ($transaction['commission'] == 0) {
				$transaction['status'] = Oara_Utilities::STATUS_PENDING;
			} else {
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
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
		$totalOverviews = Array();

		$valuesFormExport = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('mese', $dStartDate->toString("MM"));
		$valuesFormExport[] = new Oara_Curl_Parameter('anno', $dStartDate->toString("yyyy"));
		$valuesFormExport[] = new Oara_Curl_Parameter('vai', '  Go  ');
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://booking.terravision.eu/statsales.asp?', $valuesFormExport);
		$exportReport = $this->_client->post($urls);

		/*** load the html into the object ***/
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($exportReport[0]);
		$tableList = $doc->getElementsByTagName('table');

		$clickNumber = substr($tableList->item(6)->childNodes->item(0)->childNodes->item(2)->nodeValue, 0, -2);

		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
		foreach ($transactionArray as $merchantId => $merchantTransaction) {
			foreach ($merchantTransaction as $date => $transactionList) {

				$overview = Array();

				$overview['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date($date, "yyyy-MM-dd");
				$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				if (is_numeric($clickNumber)) {
					$overview['click_number'] = $clickNumber;
				} else {
					$overview['click_number'] = 0;
				}
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
	 * Read the html table in the report
	 * @param string $htmlReport
	 * @param Zend_Date $startDate
	 * @param Zend_Date $endDate
	 * @param int $iteration
	 * @return array:
	 */
	public function readTransactionTable($startDate, $endDate) {
		$transactions = array();

		$valuesFormExport = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('mese', $startDate->toString("MM"));
		$valuesFormExport[] = new Oara_Curl_Parameter('anno', $endDate->toString("yyyy"));
		$valuesFormExport[] = new Oara_Curl_Parameter('vai', '  Go  ');
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://booking.terravision.eu/statsales.asp?', $valuesFormExport);
		$exportReport = $this->_client->post($urls);

		/*** load the html into the object ***/
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($exportReport[0]);
		$tableList = $doc->getElementsByTagName('table');

		$clickNumber = substr($tableList->item(6)->childNodes->item(0)->childNodes->item(2)->nodeValue, 0, -2);
		$transactionNumber = $tableList->item(6)->childNodes->item(2)->childNodes->item(2)->nodeValue;
		if ($transactionNumber != 0) {
			$totalAmout = 0;
			$totalCommission = 0;

			if ($tableList->item(10)->childNodes->item(3) != null) {
				$totalAmout = str_replace(',', '.', str_replace('.', '', substr($tableList->item(10)->childNodes->item(3)->childNodes->item(2)->nodeValue, 5)));
				$totalCommission = str_replace(',', '.', str_replace('.', '', substr($tableList->item(10)->childNodes->item(6)->childNodes->item(2)->nodeValue, 4)));
			}

			$amountPerTransaction = $totalAmout / $transactionNumber;
			$commissionPerTransaction = $totalCommission / $transactionNumber;

			for ($i = 0; $i < $transactionNumber; $i++) {
				$obj = new stdClass();
				$obj->date = $endDate->toString("yyyy-MM-dd HH:mm:ss");
				$obj->amount = $amountPerTransaction;
				$obj->commission = $commissionPerTransaction;
				$transactions[] = $obj;
			}
		}
		return $transactions;
	}

}
