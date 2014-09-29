<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2014  Carlos Morillo Merino
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
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_ClixGalore
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_ClixGalore extends Oara_Network {
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
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Website List
	 * @var unknown_type
	 */
	private $_websiteList = array();
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];

		$loginUrl = 'https://www.clixgalore.co.uk/MemberLogin.aspx';
		$valuesLogin = array(new Oara_Curl_Parameter('txt_UserName', $user),
		new Oara_Curl_Parameter('txt_Password', $password),
		new Oara_Curl_Parameter('cmd_login.x', '29'),
		new Oara_Curl_Parameter('cmd_login.y', '8')
		);
		$dom = new Zend_Dom_Query(file_get_contents("https://www.clixgalore.co.uk/Memberlogin.aspx"));
		$results = $dom->query('input[type="hidden"]');
		$hiddenValue = null;
		foreach ($results as $result){
			$name = $result->attributes->getNamedItem("name")->nodeValue;
			$hiddenValue = $result->attributes->getNamedItem("value")->nodeValue;
			$valuesLogin[] = new Oara_Curl_Parameter($name, $hiddenValue);
		}

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.clixgalore.co.uk/CreateAffiliateProgram.aspx', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);

		$this->_websiteList = array();
		$results = $dom->query('#AffProgramDropDown1_aff_program_list');
		$count = count($results);
		if ($count == 1) {
			$selectNode = $results->current();
			$websiteLines = $selectNode->childNodes;
			for ($i = 0; $i < $websiteLines->length; $i++) {
				$wid = $websiteLines->item($i)->attributes->getNamedItem("value")->nodeValue;
				if ($wid != 0) {
					$this->_websiteList[$wid] = $websiteLines->item($i)->nodeValue;
				}
			}
		} else {
			throw new Exception('Problem getting the websites');
		}

		$this->_exportMerchantParameters = array();

		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('AfID', '0'),
		new Oara_Curl_Parameter('S', ''),
		new Oara_Curl_Parameter('ST', '2'),
		new Oara_Curl_Parameter('Period', '6'),
		new Oara_Curl_Parameter('AdID', '0'),
		new Oara_Curl_Parameter('B', '2')
		);

		$this->_exportOverviewParameters = array(new Oara_Curl_Parameter('WNO', '0')
		);

		$this->_exportPaymentParameters = array(new Oara_Curl_Parameter('dd_Period', '0'),
		new Oara_Curl_Parameter('cmd_retrieve', 'Retrieve Payments')
		);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();

		foreach (array_keys($this->_websiteList) as $websiteId) {
			$urls = array();
			$urls[] = new Oara_Curl_Request('http://www.clixgalore.co.uk/AffiliateAdvancedReporting.aspx', array());
			$exportReport = $this->_client->get($urls);
			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('#dd_AffAdv_program_list_aff_adv_program_list');
			$count = count($results);
			if ($count == 1) {
				$selectNode = $results->current();
				$merchantLines = $selectNode->childNodes;
				for ($i = 0; $i < $merchantLines->length; $i++) {
					$cid = $merchantLines->item($i)->attributes->getNamedItem("value")->nodeValue;
					if ($cid != 0) {
						$obj = array();
						$obj['cid'] = $merchantLines->item($i)->attributes->getNamedItem("value")->nodeValue;
						$obj['name'] = $merchantLines->item($i)->nodeValue;
						$obj['url'] = '';
						$merchants[] = $obj;
					}
				}
			} else {
				throw new Exception('Problem getting the websites');
			}
		}

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();

		$statusArray = array(0, 1, 2);

		foreach ($statusArray as $status) {

			$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
			$valuesFromExport[] = new Oara_Curl_Parameter('SD', $dStartDate->toString("yyyy-MM-dd"));
			$valuesFromExport[] = new Oara_Curl_Parameter('ED', $dEndDate->toString("yyyy-MM-dd"));
			$valuesFromExport[] = new Oara_Curl_Parameter('Status', $status);

			$urls = array();
			$urls[] = new Oara_Curl_Request('http://www.clixgalore.co.uk/AffiliateTransactionSentReport_Excel.aspx?', $valuesFromExport);
			$exportReport = $this->_client->get($urls);
			$exportData = self::htmlToCsv($exportReport[0]);
			$num = count($exportData);
			for ($i = 1; $i < $num; $i++) {
				$transactionExportArray = str_getcsv($exportData[$i], ";");
				if (isset($merchantMap[$transactionExportArray[2]]) && in_array((int) $merchantMap[$transactionExportArray[2]], $merchantList)) {
					$transaction = Array();
					$merchantId = (int) $merchantMap[$transactionExportArray[2]];
					$transaction['merchantId'] = $merchantId;
					$transactionDate = new Zend_Date($transactionExportArray[0], 'dd MMM yyyy HH:mm', 'en');
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

					if ($transactionExportArray[6] != null) {
						$transaction['custom_id'] = $transactionExportArray[6];
					}

					if ($status == 1) {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
					if ($status == 2) {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
					if ($status == 0) {
						$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
					}

					if (preg_match("/[0-9]*,?[0-9]*\.?[0-9]+/", $transactionExportArray[4], $matches)) {
						$transaction['amount'] = Oara_Utilities::parseDouble($matches[0]);
					}
					if (preg_match("/[0-9]*,?[0-9]*\.?[0-9]+/", $transactionExportArray[5], $matches)) {
						$transaction['commission'] = Oara_Utilities::parseDouble($matches[0]);
					}

					$totalTransactions[] = $transaction;
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

		foreach (array_keys($this->_websiteList) as $websiteId) {
			$paymentExport = Oara_Utilities::cloneArray($this->_exportPaymentParameters);

			$urls = array();
			$urls[] = new Oara_Curl_Request('http://www.clixgalore.co.uk/AffiliatePaymentDetail.aspx?', array());
			$exportReport = $this->_client->post($urls);

			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('input[type="hidden"]');
			$count = count($results);
			foreach ($results as $result) {
				$hiddenName = $result->attributes->getNamedItem("name")->nodeValue;
				$hiddenValue = $result->attributes->getNamedItem("value")->nodeValue;
				$paymentExport[] = new Oara_Curl_Parameter($hiddenName, $hiddenValue);
			}

			$paymentExport[] = new Oara_Curl_Parameter('AffProgramDropDown1$aff_program_list', $websiteId);

			$urls = array();
			$urls[] = new Oara_Curl_Request('http://www.clixgalore.co.uk/AffiliatePaymentDetail.aspx', $paymentExport);
			$exportReport = $this->_client->post($urls);

			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('#dg_payments');
			$count = count($results);
			if ($count == 1) {
				$exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));
				for ($j = 1; $j < count($exportData) - 1; $j++) {

					$paymentExportArray = str_getcsv($exportData[$j], ";");
					$obj = array();
					$paymentDate = new Zend_Date($paymentExportArray[0], "MMM d yyyy", "en");
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
