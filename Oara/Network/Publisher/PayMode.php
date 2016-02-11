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
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_PayMode
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_PayMode extends Oara_Network {
	/**
	 * Export Transaction Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;
	/**
	 * Export Payment Parameters
	 * @var array
	 */
	private $_exportPaymentParameters = null;
	/**
	 *
	 * Payment Transactions Parameters
	 * @var unknown_type
	 */
	private $_exportPaymentTransactionParameters = null;

	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;

	/**
	 * AgentNumber
	 * @var unknown_type
	 */
	private $_agent = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];
		$valuesLogin = array(
		new Oara_Curl_Parameter('username', $user),
		new Oara_Curl_Parameter('password', $password),
		new Oara_Curl_Parameter('Enter', 'Enter')
		);

		$loginUrl = 'https://secure.paymode.com/paymode/do-login.jsp?';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('isDetailReport', 'true'),
		new Oara_Curl_Parameter('method', 'ALL'),
		new Oara_Curl_Parameter('currency', 'ALL_CURRENCIES'),
		new Oara_Curl_Parameter('amount', ''),
		new Oara_Curl_Parameter('disburserName', ''),
		new Oara_Curl_Parameter('remitType', 'CAR'),
		new Oara_Curl_Parameter('CAR_customerName', ''),
		new Oara_Curl_Parameter('CAR_confirmationNumber', ''),
		new Oara_Curl_Parameter('CAR_franchiseNumber', ''),
		new Oara_Curl_Parameter('CAR_remitStartDate', ''),
		new Oara_Curl_Parameter('CAR_remitEndDate', ''),
		new Oara_Curl_Parameter('CAR_rentalLocation', ''),
		new Oara_Curl_Parameter('CAR_agreementNumber', ''),
		new Oara_Curl_Parameter('CAR_commissionAmount', ''),
		new Oara_Curl_Parameter('CAR_sortBy', 'CUSTOMER_NAME'),
		new Oara_Curl_Parameter('submit1', 'Submit'),
		new Oara_Curl_Parameter('AIR_customerName', ''),
		new Oara_Curl_Parameter('AIR_confirmationNumber', ''),
		new Oara_Curl_Parameter('AIR_agreementNumber', ''),
		new Oara_Curl_Parameter('AIR_issueDate', ''),
		new Oara_Curl_Parameter('AIR_sortBy', 'CUSTOMER_NAME'),

		new Oara_Curl_Parameter('CRUISE_vesselName', ''),
		new Oara_Curl_Parameter('CRUISE_customerName', ''),
		new Oara_Curl_Parameter('CRUISE_confirmationNumber', ''),
		new Oara_Curl_Parameter('CRUISE_remitStartDate', ''),
		new Oara_Curl_Parameter('CRUISE_duration', ''),
		new Oara_Curl_Parameter('CRUISE_commissionAmount', ''),
		new Oara_Curl_Parameter('CRUISE_sortBy', 'FACILITY_NAME'),

		new Oara_Curl_Parameter('HOTEL_hotelName', ''),
		new Oara_Curl_Parameter('HOTEL_customerName', ''),
		new Oara_Curl_Parameter('HOTEL_confirmationNumber', ''),
		new Oara_Curl_Parameter('HOTEL_remitStartDate', ''),
		new Oara_Curl_Parameter('HOTEL_duration', ''),
		new Oara_Curl_Parameter('HOTEL_commissionAmount', ''),
		new Oara_Curl_Parameter('HOTEL_sortBy', 'FACILITY_NAME')
		);

		$this->_exportPaymentParameters = array(
		new Oara_Curl_Parameter('isDetailReport', 'false'),
		new Oara_Curl_Parameter('method', 'ALL'),
		new Oara_Curl_Parameter('currency', 'ALL_CURRENCIES'),
		new Oara_Curl_Parameter('amount', ''),
		new Oara_Curl_Parameter('disburserName', ''),
		new Oara_Curl_Parameter('submit1', 'Submit')
		);

		$this->_exportPaymentTransactionParameters = array(
		new Oara_Curl_Parameter('fromNonMigrated', 'false'),
		new Oara_Curl_Parameter('returnPage', ''),
		new Oara_Curl_Parameter('mode', ''),
		new Oara_Curl_Parameter('siteid', ''),
		new Oara_Curl_Parameter('ssid', '')
		);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/home.jsp?', array());
		$exportReport = $this->_client->get($urls);

		if (preg_match('/class="logout"/', $exportReport[0], $matches)) {

			$urls = array();
			$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/reports-pre_commission_history.jsp?', array());
			$exportReport = $this->_client->get($urls);
			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('input[type="checkbox"]');
			$agentNumber = array();
			foreach ($results as $result) {
				$agentNumber[] = $result->getAttribute("id");
			}
			$this->_agentNumber = $agentNumber;
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
		$obj['name'] = "Sixt";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {

		$totalTransactions = array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));

		$valuesFromExport = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/reports-baiv2.jsp?', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('input[type="hidden"]');
		foreach ($results as $hidden) {
			$name = $hidden->getAttribute("name");
			$value = $hidden->getAttribute("value");
			$valuesFromExport[] = new Oara_Curl_Parameter($name, $value);
		}
		$valuesFromExport[] = new Oara_Curl_Parameter('dataSource', '1');
		$valuesFromExport[] = new Oara_Curl_Parameter('RA:reports-baiv2.jspCHOOSE', '620541800');
		$valuesFromExport[] = new Oara_Curl_Parameter('reportFormat', 'csv');
		$valuesFromExport[] = new Oara_Curl_Parameter('includeCurrencyCodeColumn', 'on');
		$valuesFromExport[] = new Oara_Curl_Parameter('remitTypeCode', '');
		$valuesFromExport[] = new Oara_Curl_Parameter('PAYMENT_CURRENCY_TYPE', 'CREDIT');
		$valuesFromExport[] = new Oara_Curl_Parameter('PAYMENT_CURRENCY_TYPE', 'INSTRUCTION');
		$valuesFromExport[] = new Oara_Curl_Parameter('subSiteExtID', '');
		$valuesFromExport[] = new Oara_Curl_Parameter('ediProvider835Version', '5010');
		$valuesFromExport[] = new Oara_Curl_Parameter('tooManyRowsCheck', 'true');

		$urls = array();
		$dateList = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		foreach ($dateList as $date) {
			$valuesFromExportTemp = Oara_Utilities::cloneArray($valuesFromExport);
			$valuesFromExportTemp[] = new Oara_Curl_Parameter('date', $date->toString("MM/dd/yyyy"));

			$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/reports-do_csv.jsp?closeJQS=true?', $valuesFromExportTemp);
		}

		$exportReport = $this->_client->get($urls);
		$transactionCounter = 0;
		$valueCounter = 0;
		$commissionCounter = 0;
		$j = 0;
		foreach ($exportReport as $report){
			$reportParameters = $urls[$j]->getParameters();
			$reportDate = $reportParameters[count($reportParameters) -1]->getValue();
			$transactionDate = new Zend_Date($reportDate, 'MM/dd/yyyy', 'en');
			if (!preg_match("/logout.jsp/", $report)){
				$exportReportData = str_getcsv($report, "\n");
				$num = count($exportReportData);
				for ($i = 1; $i < $num; $i++) {
					$transactionArray = str_getcsv($exportReportData[$i], ",");
					if (count($transactionArray) == 30 && $transactionArray[0] == 'D' && $transactionArray[1] == null){
						$transactionCounter++;
						$valueCounter += $filter->filter($transactionArray[24]);
						$commissionCounter += $filter->filter($transactionArray[28]);
					}
				}
			}
			$j++;
		}
		
		if ($transactionCounter > 0){
			
			for ($i = 0; $i < count($dateList); $i++){
				
				$transaction = array();
				$transaction['merchantId'] = 1;
				$transaction['status'] = Oara_Utilities::STATUS_PAID;
					
				$transaction['date'] = $dateList[$i]->toString("yyyy-MM-dd HH:mm:ss");
		
				$transaction['amount'] = $valueCounter/count($dateList);
				$transaction['commission'] = $commissionCounter/count($dateList);
		
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

		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
		$startDate = new Zend_Date("01-01-2012", "dd-MM-yyyy");
		$endDate = new Zend_Date();

		$dateList = Oara_Utilities::monthsOfDifference($startDate, $endDate);
		foreach ($dateList as $date) {
			$monthStartDate = clone $date;
			$monthEndDate = null;

			$monthEndDate = clone $date;
			$monthEndDate->setDay(1);
			$monthEndDate->addMonth(1);
			$monthEndDate->subDay(1);

			$monthEndDate->setHour(23);
			$monthEndDate->setMinute(59);
			$monthEndDate->setSecond(59);

			$valuesFromExport = array();
			$valuesFromExport[] = new Oara_Curl_Parameter('Begin_Date', $monthStartDate->toString("MM/dd/yyyy"));
			$valuesFromExport[] = new Oara_Curl_Parameter('End_Date', $monthEndDate->toString("MM/dd/yyyy"));

			$valuesFromExport[] = new Oara_Curl_Parameter('cd', "c");
			$valuesFromExport[] = new Oara_Curl_Parameter('disb', "false");
			$valuesFromExport[] = new Oara_Curl_Parameter('coll', "true");
			$valuesFromExport[] = new Oara_Curl_Parameter('transactionID', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('Begin_DatePN', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('Begin_DateCN', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('End_DatePN', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('End_DateCN', "");

			$valuesFromExport[] = new Oara_Curl_Parameter('disbAcctIDRef', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('checkNumberID', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('paymentNum', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('sel_type', "OTH");
			$valuesFromExport[] = new Oara_Curl_Parameter('payStatusCat', "ALL_STATUSES");
			$valuesFromExport[] = new Oara_Curl_Parameter('amount', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('aggregatedCreditAmount', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('disbSiteIDManual', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('collSiteIDManual', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('agencyid', "");

			$valuesFromExport[] = new Oara_Curl_Parameter('collbankAccount', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('remitInvoice', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('remitAccount', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('remitCustAccount', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('remitCustName', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('remitVendorNumber', "");
			$valuesFromExport[] = new Oara_Curl_Parameter('remitVendorName', "");

			$urls = array();
			$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/payment-DB-search.jsp?dataSource=1', $valuesFromExport);
			$exportReport = $this->_client->post($urls);


			if (!preg_match("/No payments were found/",$exportReport[0])){
				$dom = new Zend_Dom_Query($exportReport[0]);
				$results = $dom->query('form[name="transform"] table');
				if (count($results) > 0){
					$tableCsv = self::htmlToCsv(self::DOMinnerHTML($results->current()));
					$payment = Array();
					$paymentArray = str_getcsv($tableCsv[4], ";");
					$payment['pid'] = $paymentArray[1];

					$dateResult = $dom->query('form[name="collForm"] table');
					if (count($dateResult) > 0){
						$dateCsv = self::htmlToCsv(self::DOMinnerHTML($dateResult->current()));
						$dateArray = str_getcsv($dateCsv[2], ";");

						$paymentDate = new Zend_Date($dateArray[1], 'dd-MMM-yyyy', 'en');
						$payment['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");

						$paymentArray = str_getcsv($tableCsv[3], ";");
						$payment['value'] = Oara_Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $paymentArray[3]));
						$payment['method'] = "BACS";
						$paymentHistory[] = $payment;
					}

				} else {
					$results = $dom->query('table[cellpadding="2"]');
					foreach ($results as $table) {

						$tableCsv = self::htmlToCsv(self::DOMinnerHTML($table));
						$num = count($tableCsv);
						for ($i = 1; $i < $num; $i++) {
							$payment = Array();
							$paymentArray = str_getcsv($tableCsv[$i], ";");
							$payment['pid'] = $paymentArray[0];
							$paymentDate = new Zend_Date($paymentArray[3], 'MM/dd/yyyy', 'en');
							$payment['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");
							$payment['value'] = Oara_Utilities::parseDouble($paymentArray[9]);
							$payment['method'] = "BACS";
							$paymentHistory[] = $payment;

						}
					}

				}



			}


		}
		return $paymentHistory;
	}

	/**
	 *
	 * Function that returns the inner HTML code
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

}
