<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_FashionTraffic
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_FashionTraffic extends Oara_Network {
	/**
	 * Export client.
	 * @var Oara_Curl_Access
	 */
	private $_client = null;

	/**
	 * Transaction Export Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;

	/**
	 * Constructor and Login
	 * @param $traveljigsaw
	 * @return Oara_Network_Publisher_Tj_Export
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];
		$loginUrl = 'http://system.fashiontraffic.com/';

		$valuesLogin = array(new Oara_Curl_Parameter('_method', "POST"),
		new Oara_Curl_Parameter('data[User][type]', 'affiliate_user'),
		new Oara_Curl_Parameter('data[User][email]', $user),
		new Oara_Curl_Parameter('data[User][password]', $password)

		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://system.fashiontraffic.com/', array());
		$exportReport = $this->_client->get($urls);

		if (preg_match("/\/logout/", $exportReport[0], $matches)) {
			$connection = true;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array();

		$valuesFormExport = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://system.fashiontraffic.com/stats/ajax_filter_options/Offers', $valuesFormExport);

		$exportReport = $this->_client->post($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);

		$results = $dom->query('option');
		foreach ($results as $result){
			$cid = $result->attributes->getNamedItem("value")->nodeValue;
			$obj = array();
			$name = $result->nodeValue;
			$obj = array();
			$obj['cid'] = $cid;
			$obj['name'] = $name;
			$merchants[] = $obj;
		}



		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array();

		$valuesFormExport = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://system.fashiontraffic.com/stats/lead_report', $valuesFormExport);
		$exportReport = $this->_client->post($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);

		

		$valuesFormExport = array();
		$hidden = $dom->query('#ConversionReportForm input[name="data[_Token][key]"][type="hidden"]');
		foreach ($hidden as $values) {
			$valuesFormExport[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}

		$hidden = $dom->query('#ConversionReportForm input[name="data[_Token][fields]"][type="hidden"]');
		foreach ($hidden as $values) {
			$valuesFormExport[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}

		$valuesFormExport[] = new Oara_Curl_Parameter("_method", 'POST');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][page]", '');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.offer_id');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.datetime');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.ad_id');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.source');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.affiliate_info1');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.affiliate_info2');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.affiliate_info3');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.affiliate_info4');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.affiliate_info5');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.conversion_payout');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][fields][]", 'Stat.conversion_status');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][search][field]", '');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[Report][search][value]", '');

		$valuesFormExport[] = new Oara_Curl_Parameter("data[DateRange][timezone]", 'America/New_York');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[DateRange][preset_date_range]", 'other');
		$valuesFormExport[] = new Oara_Curl_Parameter("data[DateRange][start_date]", $dStartDate->toString("yyyy-MM-dd"));
		$valuesFormExport[] = new Oara_Curl_Parameter("data[DateRange][end_date]", $dEndDate->toString("yyyy-MM-dd"));

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://system.fashiontraffic.com/stats/lead_report', $valuesFormExport);
		$exportReport = $this->_client->post($urls);
		
		$csvUrl = null;
		if (preg_match("/report:(.*).csv/", $exportReport[0], $match)){
			$csvUrl = "http://system.fashiontraffic.com/stats/conversion_report/report:{$match[1]}.csv";
		}
		
		
		$valuesFormExport = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request($csvUrl, $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0], "\n");

		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ",");
			if (in_array((int) $transactionExportArray[0], $merchantList)) {
				$transaction = Array();
				$merchantId = (int) $transactionExportArray[0];
				$transaction['merchantId'] = $merchantId;
				$transaction['date'] = $transactionExportArray[1];

				if ($transactionExportArray[5] != null) {
					$transaction['custom_id'] = $transactionExportArray[5];
				}

				if ($transactionExportArray[10] == 'approved') {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else {
					throw new Exception("Status {$transactionExportArray[10]} unknown");
				}

				if (preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionExportArray[9], $match)){
					$transaction['amount'] = (double)$match[0];
				}
				if (preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionExportArray[9], $match)){
					$transaction['commission'] = (double)$match[0];
				}
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
		$totalOverviews = Array();
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
				$totalOverviews[] = $overview;
			}
		}

		return $totalOverviews;
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
