<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Dianomi
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Dianomi extends Oara_Network {
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

		$valuesLogin = array(new Oara_Curl_Parameter('username', $user),
			new Oara_Curl_Parameter('password', $password),
			new Oara_Curl_Parameter('app', 'loginbox'),
			new Oara_Curl_Parameter('page', '378'),
			new Oara_Curl_Parameter('partner', '1'),
			new Oara_Curl_Parameter('redir', '')
		);

		$loginUrl = 'https://my.dianomi.com/index.epl?';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://my.dianomi.com/Campaign-Analysis-378_1.html?', array());
		$exportReport = $this->_client->get($urls);
		if (preg_match("/app=logout&amp;page=378&amp;partner=1/", $exportReport[0], $matches)) {
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
		$obj = Array();
		$obj['cid'] = 1;
		$obj['name'] = 'Dianomi';
		$merchants[] = $obj;

		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array();

		return $totalTransactions;

	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalOverviews = Array();

		$valuesFormExport = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('periodtype', "fromtolong");
		$valuesFormExport[] = new Oara_Curl_Parameter('fromday', $dStartDate->toString("dd"));
		$valuesFormExport[] = new Oara_Curl_Parameter('frommonth', $dStartDate->toString("MM"));
		$valuesFormExport[] = new Oara_Curl_Parameter('fromyear', $dStartDate->toString("yyyy"));

		$valuesFormExport[] = new Oara_Curl_Parameter('today', $dEndDate->toString("dd"));
		$valuesFormExport[] = new Oara_Curl_Parameter('tomonth', $dEndDate->toString("MM"));
		$valuesFormExport[] = new Oara_Curl_Parameter('toyear', $dEndDate->toString("yyyy"));

		$valuesFormExport[] = new Oara_Curl_Parameter('Go', 'Go');

		$valuesFormExport[] = new Oara_Curl_Parameter('partnerId', '326');
		$valuesFormExport[] = new Oara_Curl_Parameter('action', 'partnerLeads');
		$valuesFormExport[] = new Oara_Curl_Parameter('subaction', 'RevenueOverTime');
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://my.dianomi.com/Campaign-Analysis-378_1.html?', $valuesFormExport);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('.tabular');
		if (count($results) > 0) {
			$exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));
			$num = count($exportData);
			for ($i = 1; $i < $num; $i++) {
				$overviewExportArray = str_getcsv($exportData[$i], ";");

				$overview = Array();

				$overview['merchantId'] = 1;
				$date = new Zend_Date($overviewExportArray[0], "yyyy-MM-dd");
				$overview['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$overview['click_number'] = 0;
				$overview['impression_number'] = 0;
				$overview['transaction_number'] = 0;
				$overview['transaction_confirmed_value'] = $overviewExportArray[1];
				$overview['transaction_confirmed_commission'] = $overviewExportArray[1];
				$overview['transaction_pending_value'] = 0;
				$overview['transaction_pending_commission'] = 0;
				$overview['transaction_declined_value'] = 0;
				$overview['transaction_declined_commission'] = 0;
				$overview['transaction_paid_value'] = 0;
				$overview['transaction_paid_commission'] = 0;
				$totalOverviews[] = $overview;
			}
		}
		return $totalOverviews;
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
