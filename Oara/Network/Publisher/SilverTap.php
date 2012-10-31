<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_St
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_SilverTap extends Oara_Network {
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
	 * Merchant Map
	 * @var array
	 */
	private $_merchantMap = array();
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Server Url
	 * @var unknown_type
	 */
	private $_serverUrl = null;
	/**
	 * Constructor and Login
	 * @param $silvertap
	 * @return Oara_Network_Publisher_St_Export
	 */
	public function __construct($credentials) {

		$user = $credentials['user'];
		$password = $credentials['password'];
		$report = null;
		if ($credentials['network'] == "SilverTap") {
			$this->_serverUrl = "http://mats.silvertap.com/";
			$report = 'AMSCommission_Breakdown';

		} else
			if ($credentials['network'] == "BrandConversions") {
				$this->_serverUrl = "https://mats.brandconversions.com/";
				$report = 'BCCommission_Breakdown';
			}

		$loginUrl = $this->_serverUrl.'login.aspx';

		$valuesLogin = array(new Oara_Curl_Parameter('txtUsername', $user),
			new Oara_Curl_Parameter('txtPassword', $password),
			new Oara_Curl_Parameter('cmdSubmit', 'Login'),
			new Oara_Curl_Parameter('__EVENTTARGET', ''),
			new Oara_Curl_Parameter('__EVENTARGUMENT', '')
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$exportPassword = md5($password);
		$exportUser = self::getExportUser();

		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('user', $exportUser),
			new Oara_Curl_Parameter('pwd', $exportPassword),
			new Oara_Curl_Parameter('report', $report),
			new Oara_Curl_Parameter('groupby', 'Programme'),
			new Oara_Curl_Parameter('groupdate', 'Day'),
			new Oara_Curl_Parameter('creative', ''),
			new Oara_Curl_Parameter('CommOnly', '1'),
			new Oara_Curl_Parameter('showimpressions', 'True'),
			new Oara_Curl_Parameter('showclicks', 'True'),
			new Oara_Curl_Parameter('showreferrals', 'True'),
			new Oara_Curl_Parameter('showtransactionvalues', 'True'),
			new Oara_Curl_Parameter('sort', 'Date asc'),
			new Oara_Curl_Parameter('format', 'csv'),
		);
		$this->_exportOverviewParameters = array(new Oara_Curl_Parameter('user', $exportUser),
			new Oara_Curl_Parameter('pwd', $exportPassword),
			new Oara_Curl_Parameter('report', 'Performance'),
			new Oara_Curl_Parameter('groupby', 'Merchant'),
			new Oara_Curl_Parameter('groupdate', 'Day'),
			new Oara_Curl_Parameter('creative', ''),
			new Oara_Curl_Parameter('CommOnly', '1'),
			new Oara_Curl_Parameter('showimpressions', 'True'),
			new Oara_Curl_Parameter('showclicks', 'True'),
			new Oara_Curl_Parameter('showreferrals', 'True'),
			new Oara_Curl_Parameter('showtransactionvalues', 'True'),
			new Oara_Curl_Parameter('sort', 'Date asc'),
			new Oara_Curl_Parameter('format', 'csv')
		);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchantList = self::getMerchantProgramMap();
		$merchants = Array();
		foreach ($merchantList as $key => $value) {
			$obj = Array();
			$obj['cid'] = $key;
			$obj['name'] = $value;
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
		$startDate = $dStartDate->toString('dd/MM/yyyy');
		$endDate = $dEndDate->toString('dd/MM/yyyy');

		$valueIndex = 9;
		$commissionIndex = 15;
		$statusIndex = 16;
		if ($this->_serverUrl == "https://mats.brandconversions.com/") {
			$valueIndex = 11;
			$commissionIndex = 17;
			$statusIndex = 18;
		}

		$valuesFormExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		//$valuesFormExport[] = new Oara_Curl_Parameter('merchant', '0');
		$valuesFormExport[] = new Oara_Curl_Parameter('datefrom', $startDate);
		$valuesFormExport[] = new Oara_Curl_Parameter('dateto', $endDate);
		$urls = array();
		$urls[] = new Oara_Curl_Request($this->_serverUrl.'reports/remote.aspx?', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		$exportData = str_getcsv($exportReport[0], "\r\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ",");
			if (in_array((int) $transactionExportArray[3], $merchantList)) {
				$transaction = Array();
				$transaction['unique_id'] = $transactionExportArray[0];
				$transaction['merchantId'] = $transactionExportArray[3];
				$transactionDate = new Zend_Date($transactionExportArray[2], "dd/MM/YY HH:mm:ss");
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

				if ($transactionExportArray[7] != null) {
					$transaction['custom_id'] = $transactionExportArray[7];
				}

				if (preg_match('/Unpaid Confirmed/', $transactionExportArray[$statusIndex]) || preg_match('/Paid Confirmed/', $transactionExportArray[$statusIndex])) {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
					if (preg_match('/Unpaid Unconfirmed/', $transactionExportArray[$statusIndex])) {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
						if (preg_match('/Unpaid Rejected/', $transactionExportArray[$statusIndex])) {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						} else {
							throw new Exception("No Status supported ".$transactionExportArray[$statusIndex]);
						}

				$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[$valueIndex]);
				$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[$commissionIndex]);
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
		$startDate = $dStartDate->toString('dd/MM/yyyy');
		$endDate = $dEndDate->toString('dd/MM/yyyy');
		$valuesFormExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
		//$valuesFormExport[] = new Oara_Curl_Parameter('merchant', '0');
		$valuesFormExport[] = new Oara_Curl_Parameter('datefrom', $startDate);
		$valuesFormExport[] = new Oara_Curl_Parameter('dateto', $endDate);
		$urls = array();
		$urls[] = new Oara_Curl_Request($this->_serverUrl.'reports/remote.aspx?', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		$exportData = array();
		$exportData = str_getcsv($exportReport[0], "\r\n");

		$num = count($exportData);
		if ($num > 1) {
			for ($j = 1; $j < $num; $j++) {
				$overviewExportArray = str_getcsv($exportData[$j], ",");
				if (isset($merchantMap[$overviewExportArray[1]]) && in_array((int) $merchantMap[$overviewExportArray[1]], $merchantList)) {

					$overview = Array();
					$merchantId = $merchantMap[$overviewExportArray[1]];
					$overview['merchantId'] = $merchantId;
					$overviewDate = new Zend_Date($overviewExportArray[0], "dd/MM/yyyy");
					$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
					$overview['click_number'] = (int) $overviewExportArray[3];
					$overview['impression_number'] = (int) $overviewExportArray[4];
					$overview['transaction_number'] = 0;
					$overview['transaction_confirmed_value'] = 0;
					$overview['transaction_confirmed_commission'] = 0;
					$overview['transaction_pending_value'] = 0;
					$overview['transaction_pending_commission'] = 0;
					$overview['transaction_declined_value'] = 0;
					$overview['transaction_declined_commission'] = 0;
					$transactionDateArray = Oara_Utilities::getDayFromArray($overview['merchantId'], $transactionArray, $overviewDate, true);
					foreach ($transactionDateArray as $transaction) {
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
					if (Oara_Utilities::checkRegister($overview)) {
						$totalOverviews[] = $overview;
					}
				}
			}
		}

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
	 * Sets up the merchant list and the program list.
	 */
	private function getMerchantProgramMap() {
		$merchantList = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request($this->_serverUrl.'Reports/Default.aspx?report=Performance', array());
		$result = $this->_client->get($urls);

		/*** load the html into the object ***/
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($result[0]);
		$select = $doc->getElementById('ctl00_ContentPlaceHolder1_ddlMerchant');

		$children = $select->childNodes;
		foreach ($children as $child) {
			if ($child->nodeValue != 'All') {
				$attrs = $child->attributes;
				foreach ($attrs as $attrName => $attrNode) // attributes
					{
					if ($attrName == 'value') {
						$merchantList[$attrNode->value] = $child->nodeValue;
					}

				}
			}
		}

		return $merchantList;
	}
	/**
	 * Sets up the merchant list and the program list.
	 */
	private function getExportUser() {
		$exporUser = null;

		$urls = array();
		$urls[] = new Oara_Curl_Request($this->_serverUrl.'Reports/Default.aspx?', array(new Oara_Curl_Parameter('report', 'Performance')));
		$result = $this->_client->get($urls);

		$urls = array();
		$urls[] = new Oara_Curl_Request($this->_serverUrl.'/Reports/RemoteHelp.aspx?', array());
		$result = $this->_client->get($urls);

		/*** load the html into the object ***/
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($result[0]);
		$textareaList = $doc->getElementsByTagName('textarea');

		$messageNode = $textareaList->item(0);
		if (!isset($messageNode->firstChild)) {
			throw new Exception('Error getting the User');
		}
		$messageStr = $messageNode->firstChild->nodeValue;

		$parseUrl = parse_url(trim($messageStr));
		$parameters = explode('&', $parseUrl['query']);
		foreach ($parameters as $parameter) {
			$parameterValue = explode('=', $parameter);
			if ($parameterValue[0] == 'user') {
				$exporUser = $parameterValue[1];
			}
		}
		return $exporUser;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory($currentPage = 1) {
		$paymentHistory = array();

		$pdfToTextPath = '';
		if (PHP_OS == "WIN32" || PHP_OS == "WINNT") {
			return $paymentHistory;
		} else {
			// some other platform
			$pdfToTextPath = 'pdftotext ';
		}

		$params = array();
		if ($currentPage != 1) {

			$urls = array();
			$urls[] = new Oara_Curl_Request($this->_serverUrl.'/Invoices/Default.aspx?', $params);
			$exportReport = $this->_client->post($urls);

			$doc = new DOMDocument();
			libxml_use_internal_errors(true);
			$doc->validateOnParse = true;
			$doc->loadHTML($exportReport[0]);
			$viewState = $doc->getElementById('__VIEWSTATE')->attributes->getNamedItem("value")->nodeValue;

			$params = array(new Oara_Curl_Parameter('__EVENTTARGET', 'ctl00$ContentPlaceHolder1$gvInvoices'),
				new Oara_Curl_Parameter('__EVENTARGUMENT', 'Page$'.$currentPage),
				new Oara_Curl_Parameter('__VIEWSTATE', $viewState)
			);
		}

		$urls = array();
		$urls[] = new Oara_Curl_Request($this->_serverUrl.'/Invoices/Default.aspx?', $params);
		$exportReport = $this->_client->post($urls);

		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($exportReport[0]);
		$registerTable = $doc->getElementById('ctl00_ContentPlaceHolder1_gvInvoices');
		if ($registerTable !== null) {

			$registerLines = $registerTable->childNodes;
			$descriptorspec = array(
				0 => array('pipe', 'r'),
				1 => array('pipe', 'w'),
				2 => array('pipe', 'w')
			);

			$pagesNumber = 1;
			for ($i = 2; $i < $registerLines->length ; $i++) {

				$registerLineClass = $registerLines->item($i)->attributes->getNamedItem("class")->nodeValue;
				if ($registerLineClass == 'pager') {
					$lenght = $registerLines->item($i)->childNodes->item(0)->childNodes->item(0)->childNodes->item(0)->childNodes->length;
					$pagesNumber = $registerLines->item($i)->childNodes->item(0)->childNodes->item(0)->childNodes->item(0)->childNodes->item($lenght - 2)->nodeValue;

				} else {

					$registerLine = $registerLines->item($i)->childNodes;

					$obj = array();

					//get the pdf
					$parameters = array();
					$parameters[] = new Oara_Curl_Parameter('id', $registerLine->item(0)->nodeValue);
					$urls = array();
					$urls[] = new Oara_Curl_Request($this->_serverUrl.'Invoices/ViewInvoice.aspx?', $parameters);
					$exportReport = $this->_client->get($urls);
					$exportReportUrl = $this->_client->get($urls, 'url');
					$exportReportUrl = explode('/', $exportReportUrl[0]);
					$exportReportUrl = $exportReportUrl[count($exportReportUrl) - 1];
					$dir = realpath(dirname(__FILE__)).'/../data/pdf/';
					//writing temp pdf
					$fh = fopen($dir.$exportReportUrl, 'w') or die("can't open file");
					fwrite($fh, $exportReport[0]);
					fclose($fh);

					//parsing the pdf

					$pipes = null;
					$pdfReader = proc_open($pdfToTextPath.$dir.$exportReportUrl.' -', $descriptorspec, $pipes, null, null);
					if (is_resource($pdfReader)) {

						$pdfContent = '';
						$error = '';

						$stdin = $pipes[0];

						$stdout = $pipes[1];

						$stderr = $pipes[2];

						while (!feof($stdout)) {
							$pdfContent .= fgets($stdout);
						}

						while (!feof($stderr)) {
							$error .= fgets($stderr);
						}

						fclose($stdin);
						fclose($stdout);
						fclose($stderr);

						$exit_code = proc_close($pdfReader);
					}

					if (preg_match_all("/[0-9]*,?[0-9]*\.[0-9]+/", $pdfContent, $matches)) {
						$obj['value'] = Oara_Utilities::parseDouble($matches[0][count($matches[0]) - 1]);
					} else {
						throw new Exception('Problem getting value in payments');
					}

					unlink($dir.$exportReportUrl);

					$date = new Zend_Date($registerLine->item(3)->nodeValue, "dd/MMM/yyyy", 'en_US');
					$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
					$obj['pid'] = $registerLine->item(0)->nodeValue;
					$obj['method'] = 'BACS';

					$paymentHistory[] = $obj;
				}
			}

			if ($currentPage == 1) {
				for ($i = 2; $i <= $pagesNumber; $i++) {
					$paymentHistory = array_merge($paymentHistory, self::getPaymentHistory($i));
				}
			}
		}

		return $paymentHistory;
	}

	/**
	 *  It returns the transactions for a payment
	 * @see Oara_Network::paymentTransactions()
	 */
	public function paymentTransactions($paymentId, $merchantList, $startDate) {

		$paymentTransactionList = array();

		$params = array();

		$urls = array();
		$urls[] = new Oara_Curl_Request($this->_serverUrl.'/Invoices/Default.aspx?', $params);
		$exportReport = $this->_client->post($urls);

		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($exportReport[0]);
		$viewState = $doc->getElementById('__VIEWSTATE')->attributes->getNamedItem("value")->nodeValue;

		$params = array(new Oara_Curl_Parameter('__EVENTTARGET', 'ctl00$ContentPlaceHolder1$gvInvoices'),
			new Oara_Curl_Parameter('__EVENTARGUMENT', 'Breakdown$'.$paymentId),
			new Oara_Curl_Parameter('__VIEWSTATE', $viewState)
		);

		$urls = array();
		$urls[] = new Oara_Curl_Request($this->_serverUrl.'/Invoices/Default.aspx?', $params);
		$exportReport = $this->_client->post($urls);
		$exportData = str_getcsv($exportReport[0], "\r\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ",");
			$paymentTransactionList[] = $transactionExportArray[0];
		}

		return $paymentTransactionList;
	}
}
