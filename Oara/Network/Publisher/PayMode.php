<?php
namespace Oara\Network\Publisher;
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2016  Fubra Limited
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
 * @category   PayMode
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class PayMode extends \Oara\Network {
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
	 * @return Daisycon
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];
		$valuesLogin = array(
		new \Oara\Curl\Parameter('username', $user),
		new \Oara\Curl\Parameter('password', $password),
		new \Oara\Curl\Parameter('Enter', 'Enter')
		);

		$loginUrl = 'https://secure.paymode.com/paymode/do-login.jsp?';
		$this->_client = new \Oara\Curl\Access($loginUrl, $valuesLogin, $credentials);

		$this->_exportTransactionParameters = array(new \Oara\Curl\Parameter('isDetailReport', 'true'),
		new \Oara\Curl\Parameter('method', 'ALL'),
		new \Oara\Curl\Parameter('currency', 'ALL_CURRENCIES'),
		new \Oara\Curl\Parameter('amount', ''),
		new \Oara\Curl\Parameter('disburserName', ''),
		new \Oara\Curl\Parameter('remitType', 'CAR'),
		new \Oara\Curl\Parameter('CAR_customerName', ''),
		new \Oara\Curl\Parameter('CAR_confirmationNumber', ''),
		new \Oara\Curl\Parameter('CAR_franchiseNumber', ''),
		new \Oara\Curl\Parameter('CAR_remitStartDate', ''),
		new \Oara\Curl\Parameter('CAR_remitEndDate', ''),
		new \Oara\Curl\Parameter('CAR_rentalLocation', ''),
		new \Oara\Curl\Parameter('CAR_agreementNumber', ''),
		new \Oara\Curl\Parameter('CAR_commissionAmount', ''),
		new \Oara\Curl\Parameter('CAR_sortBy', 'CUSTOMER_NAME'),
		new \Oara\Curl\Parameter('submit1', 'Submit'),
		new \Oara\Curl\Parameter('AIR_customerName', ''),
		new \Oara\Curl\Parameter('AIR_confirmationNumber', ''),
		new \Oara\Curl\Parameter('AIR_agreementNumber', ''),
		new \Oara\Curl\Parameter('AIR_issueDate', ''),
		new \Oara\Curl\Parameter('AIR_sortBy', 'CUSTOMER_NAME'),

		new \Oara\Curl\Parameter('CRUISE_vesselName', ''),
		new \Oara\Curl\Parameter('CRUISE_customerName', ''),
		new \Oara\Curl\Parameter('CRUISE_confirmationNumber', ''),
		new \Oara\Curl\Parameter('CRUISE_remitStartDate', ''),
		new \Oara\Curl\Parameter('CRUISE_duration', ''),
		new \Oara\Curl\Parameter('CRUISE_commissionAmount', ''),
		new \Oara\Curl\Parameter('CRUISE_sortBy', 'FACILITY_NAME'),

		new \Oara\Curl\Parameter('HOTEL_hotelName', ''),
		new \Oara\Curl\Parameter('HOTEL_customerName', ''),
		new \Oara\Curl\Parameter('HOTEL_confirmationNumber', ''),
		new \Oara\Curl\Parameter('HOTEL_remitStartDate', ''),
		new \Oara\Curl\Parameter('HOTEL_duration', ''),
		new \Oara\Curl\Parameter('HOTEL_commissionAmount', ''),
		new \Oara\Curl\Parameter('HOTEL_sortBy', 'FACILITY_NAME')
		);

		$this->_exportPaymentParameters = array(
		new \Oara\Curl\Parameter('isDetailReport', 'false'),
		new \Oara\Curl\Parameter('method', 'ALL'),
		new \Oara\Curl\Parameter('currency', 'ALL_CURRENCIES'),
		new \Oara\Curl\Parameter('amount', ''),
		new \Oara\Curl\Parameter('disburserName', ''),
		new \Oara\Curl\Parameter('submit1', 'Submit')
		);

		$this->_exportPaymentTransactionParameters = array(
		new \Oara\Curl\Parameter('fromNonMigrated', 'false'),
		new \Oara\Curl\Parameter('returnPage', ''),
		new \Oara\Curl\Parameter('mode', ''),
		new \Oara\Curl\Parameter('siteid', ''),
		new \Oara\Curl\Parameter('ssid', '')
		);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = false;
		$urls = array();
		$urls[] = new \Oara\Curl\Request('https://secure.paymode.com/paymode/home.jsp?', array());
		$exportReport = $this->_client->get($urls);

		if (preg_match('/class="logout"/', $exportReport[0], $matches)) {

			$urls = array();
			$urls[] = new \Oara\Curl\Request('https://secure.paymode.com/paymode/reports-pre_commission_history.jsp?', array());
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
	 * @see library/Oara/Network/Interface#getMerchantList()
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
	 * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null) {

		$totalTransactions = array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));

		$valuesFromExport = array();
		$urls = array();
		$urls[] = new \Oara\Curl\Request('https://secure.paymode.com/paymode/reports-baiv2.jsp?', array());
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('input[type="hidden"]');
		foreach ($results as $hidden) {
			$name = $hidden->getAttribute("name");
			$value = $hidden->getAttribute("value");
			$valuesFromExport[] = new \Oara\Curl\Parameter($name, $value);
		}
		$valuesFromExport[] = new \Oara\Curl\Parameter('dataSource', '1');
		$valuesFromExport[] = new \Oara\Curl\Parameter('RA:reports-baiv2.jspCHOOSE', '620541800');
		$valuesFromExport[] = new \Oara\Curl\Parameter('reportFormat', 'csv');
		$valuesFromExport[] = new \Oara\Curl\Parameter('includeCurrencyCodeColumn', 'on');
		$valuesFromExport[] = new \Oara\Curl\Parameter('remitTypeCode', '');
		$valuesFromExport[] = new \Oara\Curl\Parameter('PAYMENT_CURRENCY_TYPE', 'CREDIT');
		$valuesFromExport[] = new \Oara\Curl\Parameter('PAYMENT_CURRENCY_TYPE', 'INSTRUCTION');
		$valuesFromExport[] = new \Oara\Curl\Parameter('subSiteExtID', '');
		$valuesFromExport[] = new \Oara\Curl\Parameter('ediProvider835Version', '5010');
		$valuesFromExport[] = new \Oara\Curl\Parameter('tooManyRowsCheck', 'true');

		$urls = array();
		$dateList = \Oara\Utilities::daysOfDifference($dStartDate, $dEndDate);
		foreach ($dateList as $date) {
			$valuesFromExportTemp = \Oara\Utilities::cloneArray($valuesFromExport);
			$valuesFromExportTemp[] = new \Oara\Curl\Parameter('date', $date->toString("MM/dd/yyyy"));

			$urls[] = new \Oara\Curl\Request('https://secure.paymode.com/paymode/reports-do_csv.jsp?closeJQS=true?', $valuesFromExportTemp);
		}

		$exportReport = $this->_client->get($urls);
		$transactionCounter = 0;
		$valueCounter = 0;
		$commissionCounter = 0;
		$j = 0;
		foreach ($exportReport as $report){
			$reportParameters = $urls[$j]->getParameters();
			$reportDate = $reportParameters[count($reportParameters) -1]->getValue();
			$transactionDate = new \DateTime($reportDate, 'MM/dd/yyyy', 'en');
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
				$transaction['status'] = \Oara\Utilities::STATUS_PAID;
					
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
	 * @see Oara/Network/Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {

		$paymentHistory = array();

		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
		$startDate = new \DateTime("01-01-2012", "dd-MM-yyyy");
		$endDate = new \DateTime();

		$dateList = \Oara\Utilities::monthsOfDifference($startDate, $endDate);
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
			$valuesFromExport[] = new \Oara\Curl\Parameter('Begin_Date', $monthStartDate->toString("MM/dd/yyyy"));
			$valuesFromExport[] = new \Oara\Curl\Parameter('End_Date', $monthEndDate->toString("MM/dd/yyyy"));

			$valuesFromExport[] = new \Oara\Curl\Parameter('cd', "c");
			$valuesFromExport[] = new \Oara\Curl\Parameter('disb', "false");
			$valuesFromExport[] = new \Oara\Curl\Parameter('coll', "true");
			$valuesFromExport[] = new \Oara\Curl\Parameter('transactionID', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('Begin_DatePN', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('Begin_DateCN', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('End_DatePN', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('End_DateCN', "");

			$valuesFromExport[] = new \Oara\Curl\Parameter('disbAcctIDRef', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('checkNumberID', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('paymentNum', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('sel_type', "OTH");
			$valuesFromExport[] = new \Oara\Curl\Parameter('payStatusCat', "ALL_STATUSES");
			$valuesFromExport[] = new \Oara\Curl\Parameter('amount', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('aggregatedCreditAmount', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('disbSiteIDManual', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('collSiteIDManual', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('agencyid', "");

			$valuesFromExport[] = new \Oara\Curl\Parameter('collbankAccount', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('remitInvoice', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('remitAccount', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('remitCustAccount', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('remitCustName', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('remitVendorNumber', "");
			$valuesFromExport[] = new \Oara\Curl\Parameter('remitVendorName', "");

			$urls = array();
			$urls[] = new \Oara\Curl\Request('https://secure.paymode.com/paymode/payment-DB-search.jsp?dataSource=1', $valuesFromExport);
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

						$paymentDate = new \DateTime($dateArray[1], 'dd-MMM-yyyy', 'en');
						$payment['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");

						$paymentArray = str_getcsv($tableCsv[3], ";");
						$payment['value'] = \Oara\Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $paymentArray[3]));
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
							$paymentDate = new \DateTime($paymentArray[3], 'MM/dd/yyyy', 'en');
							$payment['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");
							$payment['value'] = \Oara\Utilities::parseDouble($paymentArray[9]);
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
