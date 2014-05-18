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
		$loginUrl = 'http://book.terravision.eu/login_check?';

		$valuesLogin = array(new Oara_Curl_Parameter('_username', $user),
			new Oara_Curl_Parameter('_password', $password),
			new Oara_Curl_Parameter('_submit', 'Login')
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://book.terravision.eu/login', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('input[name="_csrf_token"]');
		$token = null;
		foreach ($results as $result) {
			$token = $result->getAttribute("value");
		}

		$valuesLogin = array(new Oara_Curl_Parameter('_username', $user),
			new Oara_Curl_Parameter('_password', $password),
			new Oara_Curl_Parameter('_submit', 'Login'),
			new Oara_Curl_Parameter('_csrf_token', $token)
		);
		$urls = array();
		$urls[] = new Oara_Curl_Request($loginUrl, $valuesLogin);
		$exportReport = $this->_client->post($urls);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://book.terravision.eu/partner/my/', array());
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
		
		$totalOverviews = Array();
		
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://book.terravision.eu/partner/my/stats', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('input[name="form[_token]"]');
		$token = null;
		foreach ($results as $result) {
			$token = $result->getAttribute("value");
		}
		
		$valuesFormExport = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('form[year]', $dStartDate->toString("yyyy"));
		$valuesFormExport[] = new Oara_Curl_Parameter('fform[_token]', $token);
		$valuesFormExport[] = new Oara_Curl_Parameter('show', 'Show');
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://book.terravision.eu/partner/my/stats?', $valuesFormExport);
		$exportReport = $this->_client->post($urls);
		
		$stringToFind = $dStartDate->toString("MM-yyyy");
		/*** load the html into the object ***/
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('.frame > table');
		$exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));
		$num = count($exportData);
		
		$transactionCounter = 0;
		$valueCounter = 0;
		$commissionCounter = 0;
		
		for ($i = 1; $i < $num - 1; $i++) {
			$transactionArray = str_getcsv($exportData[$i], ";");
			if ($transactionArray[0] == $stringToFind){
				
				$transactionCounter = $transactionArray[12];
				$valueCounter += $transactionArray[14];
				$commissionCounter += $transactionArray[16];
			}
		}
		
		
		if ($transactionCounter > 0){
			$dateList = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
			for ($i = 0; $i < count($dateList); $i++){
		
				$transaction = array();
				$transaction['merchantId'] = 1;
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					
				$transaction['date'] = $dateList[$i]->toString("yyyy-MM-dd HH:mm:ss");
		
				$transaction['amount'] = $valueCounter/count($dateList);
				$transaction['commission'] = $commissionCounter/count($dateList);
		
				$totalTransactions[] = $transaction;
			}
		
		}

		return $totalTransactions;

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
