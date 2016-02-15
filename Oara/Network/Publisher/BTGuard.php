<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2014  Fubra Limited
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.
 You should have received a copy of the GNU Affero General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 Contact
 ------------
 Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
 **/
/**
 * Export Class
 *
 * @author     Alejandro Muñoz Odero
 * @category   Oara_Network_Publisher_BTGuard
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_BTGuard extends Oara_Network {

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
		);

		$loginUrl = 'https://affiliate.btguard.com/login';
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
		$urls[] = new \Oara\Curl\Request('https://affiliate.btguard.com/member', array());

		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('#login');

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
		$obj['name'] = "BTGuard";
		$obj['url'] = "https://affiliate.btguard.com/";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();
		$valuesFormExport = array();

		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		$dateArraySize = sizeof($dateArray);

			
		for ($j = 0; $j < $dateArraySize; $j++) {
			$valuesFormExport = array();
			$valuesFormExport[] = new Oara_Curl_Parameter('date1', $dateArray[$j]->toString("yyyy-MM-dd"));
			$valuesFormExport[] = new Oara_Curl_Parameter('date2', $dateArray[$j]->toString("yyyy-MM-dd"));
			$valuesFormExport[] = new Oara_Curl_Parameter('prerange', '0');

			$urls = array();
			$urls[] = new \Oara\Curl\Request('https://affiliate.btguard.com/reports?', $valuesFormExport);
			$exportReport = $this->_client->get($urls);
			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('.title table[cellspacing="12"]');
			if (count($results) > 0) {
				$exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));

				for($z=1; $z < count($exportData); $z++){
					$transactionLineArray = str_getcsv($exportData[$z], ";");
					$numberTransactions = (int)$transactionLineArray[2];
					if ($numberTransactions != 0){
						$commission = preg_replace('/[^0-9\.,]/', "", $transactionLineArray[3]);
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
		$urls[] = new \Oara\Curl\Request('https://publisher.ebaypartnernetwork.com/PublisherAccountPaymentHistory', array());
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('table .aruba_report_table');
		if (count($results) > 0) {
			$exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));
			for ($j = 1; $j < count($exportData); $j++) {

				$paymentExportArray = str_getcsv($exportData[$j], ";");
				$obj = array();
				$paymentDate = new \DateTime($paymentExportArray[0], "dd/MM/yy", "en");
				$obj['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");
				$obj['pid'] = $paymentDate->toString("yyyyMMdd");
				$obj['method'] = 'BACS';
				if (preg_match('/[-+]?[0-9]*,?[0-9]*\.?[0-9]+/', $paymentExportArray[2], $matches)) {
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
