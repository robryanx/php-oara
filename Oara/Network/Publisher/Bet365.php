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
 * @category   Oara_Network_Publisher_Bet365
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Bet365 extends Oara_Network {


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

		$ch = curl_init();
		//Check HTTP Authentication
		if (!curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY)) {
			//HTTP Authentication failed. Offline?
			throw new Exception("FAIL: curl_setopt(CURLOPT_HTTPAUTH, CURLAUTH_ANY)");
		}

		//Check SSL Connection
		if (!curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false)) {
			//SSL connection not possible
			throw new Exception("FAIL: curl_setopt(CURLOPT_SSL_VERIFYPEER, false)");
		}

		//Check URL validity (last check)
		if (!curl_setopt($ch, CURLOPT_URL, 'http://www.bet365affiliates.com/ui/pages/affiliates/affiliates.aspx')) {
			throw new Exception("FAIL: curl_setopt(CURLOPT_URL, http://www.bet365affiliates.com/ui/pages/affiliates/affiliates.aspx)");
		}

		//Set to 1 to prevent output of entire xml file
		if (!curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1)) {
			throw new Exception("FAIL: curl_setopt(CURLOPT_RETURNTRANSFER, 1)");
		}

		// Get the data
		$data = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($httpCode != 200) {
			throw new Exception("Couldn't connect to the the page");
		}
		//Close Curl session
		curl_close($ch);

		$valuesLogin = array(
		new Oara_Curl_Parameter('txtUserName', $user),
		new Oara_Curl_Parameter('txtPassword', $password),
		new Oara_Curl_Parameter('ctl00%24MasterHeaderPlaceHolder%24ctl00%24userNameTextbox', $user),
		new Oara_Curl_Parameter('ctl00%24MasterHeaderPlaceHolder%24ctl00%24passwordTextbox', $password),
		new Oara_Curl_Parameter('ctl00%24MasterHeaderPlaceHolder%24ctl00%24tempPasswordTextbox', 'Password'),
		new Oara_Curl_Parameter('ctl00%24MasterHeaderPlaceHolder%24ctl00%24goButton.x', '19'),
		new Oara_Curl_Parameter('ctl00%24MasterHeaderPlaceHolder%24ctl00%24goButton.y', '15')
		);
		$forbiddenList = array('txtPassword', 'txtUserName');
		$dom = new Zend_Dom_Query($data);
		$hiddenList = $dom->query('input[type="hidden"]');
		foreach ($hiddenList as $hidden) {
			if (!in_array($hidden->getAttribute("name"), $forbiddenList)) {
				$valuesLogin[] = new Oara_Curl_Parameter($hidden->getAttribute("name"), $hidden->getAttribute("value"));
			}
		}

		$loginUrl = 'https://www.bet365affiliates.com/Members/CMSitePages/SiteLogin.aspx?lng=1';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);



		$this->_exportPaymentParameters = array();

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www.bet365affiliates.com/UI/Pages/Affiliates/?', array());
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('#ctl00_MasterHeaderPlaceHolder_ctl00_LogoutLinkButton');
		if (count($results) > 0) {
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
		$obj['name'] = "Bet 365";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();

		$valuesFromExport = array();
		$valuesFromExport[] = new Oara_Curl_Parameter('FromDate', $dStartDate->toString("dd/MM/yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('ToDate', $dEndDate->toString("dd/MM/yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('ReportType', 'dailyReport');
		$valuesFromExport[] = new Oara_Curl_Parameter('Link', '-1');

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.bet365affiliates.com/Members/Members/Statistics/Print.aspx?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$tableList = $dom->query('#Results');
		if (!preg_match("/No results exist/", $exportReport[0])){
				

			$exportData = self::htmlToCsv(self::DOMinnerHTML($tableList->current()));
			$num = count($exportData);
			for ($i = 2; $i < $num - 1; $i++) {
				$transactionExportArray = str_getcsv($exportData[$i], ";");


				$transaction = Array();
				$transaction['merchantId'] = 1;
				$transactionDate = new Zend_Date($transactionExportArray[1], 'dd-MM-yyyy', 'en');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[27]);
				$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[32]);
				if ($transaction['amount'] != 0 && $transaction['commission'] != 0) {
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
