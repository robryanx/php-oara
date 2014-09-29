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
 * @author     Alejandro Muñoz Odero
 * @category   Oara_Network_Publisher_HideMyAss
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_HideMyAss extends Oara_Network {


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
			new Oara_Curl_Parameter('data[User][username]', $this->_credentials['user']),
			new Oara_Curl_Parameter('data[User][password]', $this->_credentials['password']),
		);
		
		$html = file_get_contents('https://affiliate.hidemyass.com/users/login');

		$dom = new Zend_Dom_Query($html);
		$hidden = $dom->query('#loginform input[name*="Token"][type="hidden"]');

		foreach ($hidden as $values) {
				$valuesLogin[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}

		$loginUrl = 'https://affiliate.hidemyass.com/users/login';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $this->_credentials);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = true;
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://affiliate.hidemyass.com/dashboard', array());
		
		$exportReport = $this->_client->post($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('#loginform');

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
		$obj['name'] = "HideMyAss";
		$obj['url'] = "https://affiliate.hidemyass.com";
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
		$urls[] = new Oara_Curl_Request('https://affiliate.hidemyass.com/reports', array());
		$exportReport = array();
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$hidden = $dom->query('#ConditionsIndexForm input[id*="Token"][type="hidden"]');

		$valuesFromExport = array();
		foreach ($hidden as $values) {
			$valuesFromExport[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}

		$valuesFromExport[] = new Oara_Curl_Parameter('_method', 'POST');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][dateselect]', '4');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][datetype]', '2');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangefrom][day]', $dStartDate->toString("dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangefrom][month]', $dStartDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangefrom][year]', $dStartDate->toString("yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangefrom][day]', $dStartDate->toString("dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangefrom][month]', $dStartDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangefrom][year]', $dStartDate->toString("yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangeto][day]', $dEndDate->toString("dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangeto][month]', $dEndDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangeto][year]', $dEndDate->toString("yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangeto][day]', $dEndDate->toString("dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangeto][month]', $dEndDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][daterangeto][year]', $dEndDate->toString("yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][themetype]', '1');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][Theme][Theme]', '');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][Query][query]', '');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][country]', '');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][order][new]', '0');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][order][rec]', '0');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][order][refund]', '0');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][order][refund]', '1');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][order][fraud]', '0');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][order][fraud]', '1');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][month1]', '0');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][month1]', '1');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][month6]', '0');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][month6]', '1');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][month12]', '0');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][month12]', '1');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][referaldate]', '0');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][visits]', '0');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][collapsed]', '0');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][collapsed]', '1');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][output]', 'raw_csv');
		$valuesFromExport[] = new Oara_Curl_Parameter('data[Conditions][chart]', 'count');

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://affiliate.hidemyass.com/reports/index_date?', $valuesFromExport);

		$exportReport = array();
		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0], "\n");

		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {

				$transactionExportArray = str_getcsv($exportData[$i], ";");
				//print_r($transactionExportArray);

				$transaction = Array();
				$transaction['merchantId'] = 1;
				$transactionDate = new Zend_Date($transactionExportArray[1], 'yyyy-MM-dd HH:mm:ss', 'en');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				//unset($transactionDate);
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;

				if (preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionExportArray[8], $match)){
					$transaction['amount'] = (double)$match[0];
				}

				if (preg_match("/[-+]?[0-9]*\.?[0-9]+/", $transactionExportArray[10], $match)){
					$transaction['commission'] = (double)$match[0];
				}

				if ($transaction['date'] >= $dStartDate->toString("yyyy-MM-dd HH:mm:ss") && $transaction['date'] <= $dEndDate->toString("yyyy-MM-dd HH:mm:ss")){
					$totalTransactions[] = $transaction;
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
}