<?php

/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_ItunesConnect
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_ItunesConnect extends Oara_Network {
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

		$url = "https://itunesconnect.apple.com/WebObjects/iTunesConnect.woa/wo/2.1.9.3.5.2.1.1.3.1.1";

		$valuesLogin = array(
		new Oara_Curl_Parameter('theAccountName', $user),
		new Oara_Curl_Parameter('theAccountPW', $password),
		new Oara_Curl_Parameter('1.Continue.x', "56"),
		new Oara_Curl_Parameter('1.Continue.y', "10"),
		new Oara_Curl_Parameter('theAuxValue', ""),
		);

		$this->_client = new Oara_Curl_Access($url, $valuesLogin, $credentials);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {

		$connection = false;
		$valuesFormExport = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://itunesconnect.apple.com/WebObjects/iTunesConnect.woa/wo/4.0', $valuesFormExport);
		$exportReport = $this->_client->get($urls);

		if (preg_match("/Sign Out/",$exportReport[0])) {
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
		$obj['cid'] = "1";
		$obj['name'] = "Itunes Connect";
		$obj['url'] = "https://itunesconnect.apple.com";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		$dateArraySize = sizeof($dateArray);
		
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://reportingitc.apple.com/dashboard.faces', array());
		$exportReport = $this->_client->get($urls);
		echo $exportReport[0];


		/*
		 theForm:listOfArrayVal=2013/09/01,2013/08/31,2013/08/30,2013/08/29,2013/08/28,2013/08/27,2013/08/26,2013/08/25,2013/08/24,2013/08/23,2013/08/22,2013/08/21,2013/08/20,2013/08/19,2013/08/18,2013/08/17,2013/08/16,2013/08/15,2013/08/14,2013/08/13,2013/08/12,2013/08/11,2013/08/10,2013/08/09,2013/08/08,2013/08/07,2013/08/06,2013/08/05,2013/08/04,2013/08/03
		 theForm:weeklyDates=Aug 26 - Sep 01, 2013:2013/08/26;2013/09/01|Aug 19 - Aug 25, 2013:2013/08/19;2013/08/25|Aug 12 - Aug 18, 2013:2013/08/12;2013/08/18|Aug 05 - Aug 11, 2013:2013/08/05;2013/08/11|Jul 29 - Aug 04, 2013:2013/07/29;2013/08/04|Jul 22 - Jul 28, 2013:2013/07/22;2013/07/28|Jul 15 - Jul 21, 2013:2013/07/15;2013/07/21|Jul 08 - Jul 14, 2013:2013/07/08;2013/07/14|Jul 01 - Jul 07, 2013:2013/07/01;2013/07/07|Jun 24 - Jun 30, 2013:2013/06/24;2013/06/30|Jun 17 - Jun 23, 2013:2013/06/17;2013/06/23|Jun 10 - Jun 16, 2013:2013/06/10;2013/06/16|Jun 03 - Jun 09, 2013:2013/06/03;2013/06/09|May 27 - Jun 02, 2013:2013/05/27;2013/06/02|May 20 - May 26, 2013:2013/05/20;2013/05/26|May 13 - May 19, 2013:2013/05/13;2013/05/19|May 06 - May 12, 2013:2013/05/06;2013/05/12|Apr 29 - May 05, 2013:2013/04/29;2013/05/05|Apr 22 - Apr 28, 2013:2013/04/22;2013/04/28|Apr 15 - Apr 21, 2013:2013/04/15;2013/04/21|Apr 08 - Apr 14, 2013:2013/04/08;2013/04/14|Apr 01 - Apr 07, 2013:2013/04/01;2013/04/07|Mar 25 - Mar 31, 2013:2013/03/25;2013/03/31|Mar 18 - Mar 24, 2013:2013/03/18;2013/03/24|Mar 11 - Mar 17, 2013:2013/03/11;2013/03/17|Mar 04 - Mar 10, 2013:2013/03/04;2013/03/10
		 theForm:monthlyDates=Jul 2013:2013/07/31,Jun 2013:2013/06/30,May 2013:2013/05/31,Apr 2013:2013/04/30,Mar 2013:2013/03/31,Feb 2013:2013/02/28,Jan 2013:2013/01/31,Dec 2012:2012/12/31,Nov 2012:2012/11/30,Oct 2012:2012/10/31,Sep 2012:2012/09/30,Aug 2012:2012/08/31
		 theForm:yearlyDates=2012:2012/12/31,2011:2011/12/31,2010:2010/12/31,2009:2009/12/31,2008:2008/12/31


		 */

		for ($j = 0; $j < $dateArraySize; $j++) {
			$valuesFormExport = array();
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm', 'theForm');
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:dateItem', $dateArray[$j]->toString("yyyy/MM/dd"));
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:displayDate', $dateArray[$j]->toString("yyyy/MM/dd"));
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:periodType', "1");
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:listOfArrayVal', '2013/09/01,2013/08/31,2013/08/30,2013/08/29,2013/08/28,2013/08/27,2013/08/26,2013/08/25,2013/08/24,2013/08/23,2013/08/22,2013/08/21,2013/08/20,2013/08/19,2013/08/18,2013/08/17,2013/08/16,2013/08/15,2013/08/14,2013/08/13,2013/08/12,2013/08/11,2013/08/10,2013/08/09,2013/08/08,2013/08/07,2013/08/06,2013/08/05,2013/08/04,2013/08/03');
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:fstWeekRange', '2013/03/04');
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:selectedWeekFstDate', '');
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:lastWeekRange', $dateArray[$j]->toString("yyyy/MM/dd"));
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:weeklyDates', 'Aug 26 - Sep 01, 2013:2013/08/26;2013/09/01|Aug 19 - Aug 25, 2013:2013/08/19;2013/08/25|Aug 12 - Aug 18, 2013:2013/08/12;2013/08/18|Aug 05 - Aug 11, 2013:2013/08/05;2013/08/11|Jul 29 - Aug 04, 2013:2013/07/29;2013/08/04|Jul 22 - Jul 28, 2013:2013/07/22;2013/07/28|Jul 15 - Jul 21, 2013:2013/07/15;2013/07/21|Jul 08 - Jul 14, 2013:2013/07/08;2013/07/14|Jul 01 - Jul 07, 2013:2013/07/01;2013/07/07|Jun 24 - Jun 30, 2013:2013/06/24;2013/06/30|Jun 17 - Jun 23, 2013:2013/06/17;2013/06/23|Jun 10 - Jun 16, 2013:2013/06/10;2013/06/16|Jun 03 - Jun 09, 2013:2013/06/03;2013/06/09|May 27 - Jun 02, 2013:2013/05/27;2013/06/02|May 20 - May 26, 2013:2013/05/20;2013/05/26|May 13 - May 19, 2013:2013/05/13;2013/05/19|May 06 - May 12, 2013:2013/05/06;2013/05/12|Apr 29 - May 05, 2013:2013/04/29;2013/05/05|Apr 22 - Apr 28, 2013:2013/04/22;2013/04/28|Apr 15 - Apr 21, 2013:2013/04/15;2013/04/21|Apr 08 - Apr 14, 2013:2013/04/08;2013/04/14|Apr 01 - Apr 07, 2013:2013/04/01;2013/04/07|Mar 25 - Mar 31, 2013:2013/03/25;2013/03/31|Mar 18 - Mar 24, 2013:2013/03/18;2013/03/24|Mar 11 - Mar 17, 2013:2013/03/11;2013/03/17|Mar 04 - Mar 10, 2013:2013/03/04;2013/03/10');
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:monthlyDates', 'Jul 2013:2013/07/31,Jun 2013:2013/06/30,May 2013:2013/05/31,Apr 2013:2013/04/30,Mar 2013:2013/03/31,Feb 2013:2013/02/28,Jan 2013:2013/01/31,Dec 2012:2012/12/31,Nov 2012:2012/11/30,Oct 2012:2012/10/31,Sep 2012:2012/09/30,Aug 2012:2012/08/31');
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:yearlyDates', '2012:2012/12/31,2011:2011/12/31,2010:2010/12/31,2009:2009/12/31,2008:2008/12/31');
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:listVendorHideId', '');
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:defaultVendorSelected', '');
			$valuesFormExport[] = new Oara_Curl_Parameter('javax.faces.ViewState', 'j_id26416:j_id26446');
			$valuesFormExport[] = new Oara_Curl_Parameter('theForm:downloadLabel2', 'theForm:downloadLabel2');

			$urls = array();
			$urls[] = new Oara_Curl_Request('https://reportingitc.apple.com/sales.faces?', $valuesFormExport);
			$exportReport = $this->_client->post($urls);
			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('.coupon_code table');
			if (count($results) > 0) {
				$exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));

				for($z=1; $z < count($exportData)-2; $z++){
					$transactionLineArray = str_getcsv($exportData[$z], ";");
					$numberTransactions = (int)$transactionLineArray[1];
					$commission = preg_replace("/[^0-9\.,]/", "", $transactionLineArray[2]);
					$commission = ((double)$commission)/$numberTransactions;
					for($y=0; $y < $numberTransactions; $y++){
						$transaction = Array();
						$transaction['merchantId'] = "1";
						$transaction['date'] =  $dateArray[$j]->toString("yyyy-MM-dd HH:mm:ss");
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
						$transaction['amount'] = $commission;
						$transaction['commission'] = $commission;
						$totalTransactions[] = $transaction;
					}
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