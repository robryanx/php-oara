<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_TenThousandHours
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_TenThousandHours extends Oara_Network {


	private $_credentials = null;
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_HideMyAss
	 */
	public function __construct($credentials) {
		$this->_credentials = $credentials;
		self::logIn();
	}

	private function logIn() {

		$valuesLogin = array(
			new Oara_Curl_Parameter('_method', 'POST'),
			new Oara_Curl_Parameter('data[User][email]', $this->_credentials['user']),
			new Oara_Curl_Parameter('data[User][password]', $this->_credentials['password']),
		);
		
		
		$html = file_get_contents('http://tenthousandhours.hasoffers.com/');
		
		

		$dom = new Zend_Dom_Query($html);
		$hidden = $dom->query('#loginForm input[name*="Token"][type="hidden"]');

		foreach ($hidden as $values) {
				$valuesLogin[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}

		$loginUrl = 'http://tenthousandhours.hasoffers.com/';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $this->_credentials);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://tenthousandhours.hasoffers.com/snapshot', array());
		
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('#loginForm');

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
		$obj['name'] = "Ten Thousand Offers";
		$obj['url'] = "http://tenthousandhours.hasoffers.com/";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();
		$valuesFormExport = array();
		
		
		$urls = array();
		$urls[] = new Oara_Curl_Request("http://tenthousandhours.hasoffers.com/stats/lead_report", array());
		$exportReport = array();
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$hidden = $dom->query('#ConversionReportForm input[type="hidden"]');

		$valuesFromExport = array();
		foreach ($hidden as $values) {
			$valuesFromExport[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}

		$valuesFromExport[] = new Oara_Curl_Parameter('data[Report][fields][]', 'Offer.name');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Report][fields][]', 'Stat.offer_id');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Report][fields][]', 'Stat.datetime');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Report][fields][]', 'Stat.ad_id');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Report][fields][]', 'Stat.affiliate_info1');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Report][fields][]', 'Stat.conversion_payout');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Report][fields][]', 'Stat.conversion_status');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Report][search][field]', '');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Report][search][value]', '');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[DateRange][timezone]', 'Europe/London');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[DateRange][preset_date_range]', 'other');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[DateRange][start_date]', $dStartDate->toString("yyyy-MM-dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[DateRange][end_date]', $dEndDate->toString("yyyy-MM-dd"));
		

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://tenthousandhours.hasoffers.com/stats/lead_report?', $valuesFromExport);
		$exportReport = array();
		$exportReport = $this->_client->post($urls);
		
		
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('a [href*="report:"]');
		if (count($results) > 0) {
			$item = $results->current();
			$url = $item->attributes->getNamedItem("href")->nodeValue;
			$urls = array();
			$urls[] = new Oara_Curl_Request("http://tenthousandhours.hasoffers.com/$url", array());
			$exportReport = array();
			$exportReport = $this->_client->get($urls);
		}
		
		$exportData = str_getcsv($exportReport[0], "\n");

		$num = count($exportData);
		for ($i = 1; $i < $num-1; $i++) {

				$transactionExportArray = str_getcsv($exportData[$i], ",");

				$transaction = Array();
				$transaction['merchantId'] = 1;
				$transactionDate = new Zend_Date($transactionExportArray[2], 'yyyy-MM-dd HH:mm:ss', 'en');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				$transaction['uniqueId'] = $transactionExportArray[3];
				$transaction['customId'] = $transactionExportArray[4];
				
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;

				if (preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionExportArray[5], $match)){
					$transaction['amount'] = (double)$match[0];
				}

				if (preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionExportArray[5], $match)){
					$transaction['commission'] = (double)$match[0];
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

		return $paymentHistory;
	}
}