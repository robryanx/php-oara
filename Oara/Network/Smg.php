<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Smg
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Smg extends Oara_Network {
	/**
	 * Export client.
	 * @var Oara_Curl_Access
	 */
	private $_oldClient = null;
	private $_newClient = null;

	/**
	 * Access to the website?
	 * @var Oara_Curl_Access
	 */
	private $_oldAccess = false;
	private $_newAccess = false;

	/**
	 * Merchants Export Parameters
	 * @var array
	 */
	private $_oldExportMerchantParameters = null;
	/**
	 * Transaction Export Parameters
	 * @var array
	 */
	private $_oldExportTransactionParameters = null;
	/**
	 * Overview Export Parameters
	 * @var array
	 */
	private $_oldExportOverviewParameters = null;

	/**
	 * Date Format, it's different in some accounts
	 * @var string
	 */
	private $_dateFormat = null;

	private $_credentials = null;

	private $_accountSid = null;
	private $_authToken = null;
	/**
	 * Constructor and Login
	 * @param $tradeDoubler
	 * @return Oara_Network_Td_Export
	 */
	public function __construct($credentials) {

		$this->_credentials = $credentials;

		self::oldLogin();
		self::newLogin();

		$this->_oldExportMerchantParameters = array(new Oara_Curl_Parameter('reportName', 'aAffiliateMyProgramsReport'),
			new Oara_Curl_Parameter('tabMenuName', ''),
			new Oara_Curl_Parameter('isPostBack', ''),
			new Oara_Curl_Parameter('showAdvanced', 'true'),
			new Oara_Curl_Parameter('showFavorite', 'false'),
			new Oara_Curl_Parameter('run_as_organization_id', ''),
			new Oara_Curl_Parameter('minRelativeIntervalStartTime', '0'),
			new Oara_Curl_Parameter('maxIntervalSize', '0'),
			new Oara_Curl_Parameter('interval', 'MONTHS'),
			new Oara_Curl_Parameter('reportPrograms', ''),
			new Oara_Curl_Parameter('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_AAFFILIATEMYPROGRAMSREPORT_TITLE'),
			new Oara_Curl_Parameter('setColumns', 'true'),
			new Oara_Curl_Parameter('latestDayToExecute', '0'),
			new Oara_Curl_Parameter('affiliateId', ''),
			new Oara_Curl_Parameter('includeWarningColumn', 'true'),
			new Oara_Curl_Parameter('sortBy', 'orderDefault'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('columns', 'programId'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('columns', 'affiliateId'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('columns', 'applicationDate'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('columns', 'status'),
			new Oara_Curl_Parameter('autoCheckbox', 'useMetricColumn'),
			new Oara_Curl_Parameter('customKeyMetricCount', '0'),
			new Oara_Curl_Parameter('metric1.name', ''),
			new Oara_Curl_Parameter('metric1.midFactor', ''),
			new Oara_Curl_Parameter('metric1.midOperator', '/'),
			new Oara_Curl_Parameter('metric1.columnName1', 'programId'),
			new Oara_Curl_Parameter('metric1.operator1', '/'),
			new Oara_Curl_Parameter('metric1.columnName2', 'programId'),
			new Oara_Curl_Parameter('metric1.lastOperator', '/'),
			new Oara_Curl_Parameter('metric1.factor', ''),
			new Oara_Curl_Parameter('metric1.summaryType', 'NONE'),
			new Oara_Curl_Parameter('format', 'CSV'),
			new Oara_Curl_Parameter('separator', ','),
			new Oara_Curl_Parameter('dateType', '0'),
			new Oara_Curl_Parameter('favoriteId', ''),
			new Oara_Curl_Parameter('favoriteName', ''),
			new Oara_Curl_Parameter('favoriteDescription', '')
		);

		$this->_oldExportTransactionParameters = array(new Oara_Curl_Parameter('reportName', 'aAffiliateEventBreakdownReport'),
			new Oara_Curl_Parameter('columns', 'programId'),
			new Oara_Curl_Parameter('columns', 'timeOfVisit'),
			new Oara_Curl_Parameter('columns', 'timeOfEvent'),
			new Oara_Curl_Parameter('columns', 'timeInSession'),
			new Oara_Curl_Parameter('columns', 'lastModified'),
			new Oara_Curl_Parameter('columns', 'epi1'),
			new Oara_Curl_Parameter('columns', 'eventName'),
			new Oara_Curl_Parameter('columns', 'pendingStatus'),
			new Oara_Curl_Parameter('columns', 'siteName'),
			new Oara_Curl_Parameter('columns', 'graphicalElementName'),
			new Oara_Curl_Parameter('columns', 'graphicalElementId'),
			new Oara_Curl_Parameter('columns', 'productName'),
			new Oara_Curl_Parameter('columns', 'productNrOf'),
			new Oara_Curl_Parameter('columns', 'productValue'),
			new Oara_Curl_Parameter('columns', 'affiliateCommission'),
			new Oara_Curl_Parameter('columns', 'link'),
			new Oara_Curl_Parameter('columns', 'leadNR'),
			new Oara_Curl_Parameter('columns', 'orderNR'),
			new Oara_Curl_Parameter('columns', 'pendingReason'),
			new Oara_Curl_Parameter('columns', 'orderValue'),
			new Oara_Curl_Parameter('isPostBack', ''),
			new Oara_Curl_Parameter('metric1.lastOperator', '/'),
			new Oara_Curl_Parameter('interval', ''),
			new Oara_Curl_Parameter('favoriteDescription', ''),
			new Oara_Curl_Parameter('currencyId', 'GBP'),
			new Oara_Curl_Parameter('event_id', '0'),
			new Oara_Curl_Parameter('pending_status', '1'),
			new Oara_Curl_Parameter('run_as_organization_id', ''),
			new Oara_Curl_Parameter('minRelativeIntervalStartTime', '0'),
			new Oara_Curl_Parameter('includeWarningColumn', 'true'),
			new Oara_Curl_Parameter('metric1.summaryType', 'NONE'),
			new Oara_Curl_Parameter('metric1.operator1', '/'),
			new Oara_Curl_Parameter('latestDayToExecute', '0'),
			new Oara_Curl_Parameter('showAdvanced', 'true'),
			new Oara_Curl_Parameter('breakdownOption', '1'),
			new Oara_Curl_Parameter('metric1.midFactor', ''),
			new Oara_Curl_Parameter('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_AAFFILIATEEVENTBREAKDOWNREPORT_TITLE'),
			new Oara_Curl_Parameter('setColumns', 'true'),
			new Oara_Curl_Parameter('metric1.columnName1', 'orderValue'),
			new Oara_Curl_Parameter('metric1.columnName2', 'orderValue'),
			new Oara_Curl_Parameter('reportPrograms', ''),
			new Oara_Curl_Parameter('metric1.midOperator', '/'),
			new Oara_Curl_Parameter('dateSelectionType', '1'),
			new Oara_Curl_Parameter('favoriteName', ''),
			new Oara_Curl_Parameter('affiliateId', ''),
			new Oara_Curl_Parameter('dateType', '1'),
			new Oara_Curl_Parameter('period', 'custom_period'),
			new Oara_Curl_Parameter('tabMenuName', ''),
			new Oara_Curl_Parameter('maxIntervalSize', '0'),
			new Oara_Curl_Parameter('favoriteId', ''),
			new Oara_Curl_Parameter('sortBy', 'timeOfEvent'),
			new Oara_Curl_Parameter('metric1.name', ''),
			new Oara_Curl_Parameter('customKeyMetricCount', '0'),
			new Oara_Curl_Parameter('metric1.factor', ''),
			new Oara_Curl_Parameter('showFavorite', 'false'),
			new Oara_Curl_Parameter('separator', ','),
			new Oara_Curl_Parameter('format', 'CSV')
		);

		$this->_oldExportOverviewParameters = array(new Oara_Curl_Parameter('reportName', 'aAffiliateProgramOverviewReport'),
			new Oara_Curl_Parameter('tabMenuName', ''),
			new Oara_Curl_Parameter('isPostBack', ''),
			new Oara_Curl_Parameter('showAdvanced', 'true'),
			new Oara_Curl_Parameter('showFavorite', 'false'),
			new Oara_Curl_Parameter('run_as_organization_id', ''),
			new Oara_Curl_Parameter('minRelativeIntervalStartTime', '0'),
			new Oara_Curl_Parameter('maxIntervalSize', '12'),
			new Oara_Curl_Parameter('interval', 'MONTHS'),
			new Oara_Curl_Parameter('reportPrograms', ''),
			new Oara_Curl_Parameter('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_AAFFILIATEPROGRAMOVERVIEWREPORT_TITLE'),
			new Oara_Curl_Parameter('setColumns', 'true'),
			new Oara_Curl_Parameter('latestDayToExecute', '0'),
			new Oara_Curl_Parameter('programTypeId', ''),
			new Oara_Curl_Parameter('currencyId', 'GBP'),
			new Oara_Curl_Parameter('includeWarningColumn', 'true'),
			new Oara_Curl_Parameter('programId', ''),
			new Oara_Curl_Parameter('period', 'custom_period'),
			new Oara_Curl_Parameter('columns', 'programId'),
			new Oara_Curl_Parameter('columns', 'impNrOf'),
			new Oara_Curl_Parameter('columns', 'clickNrOf'),
			new Oara_Curl_Parameter('autoCheckbox', 'columns'),
			new Oara_Curl_Parameter('autoCheckbox', 'useMetricColumn'),
			new Oara_Curl_Parameter('customKeyMetricCount', '0'),
			new Oara_Curl_Parameter('metric1.name', ''),
			new Oara_Curl_Parameter('metric1.midFactor', ''),
			new Oara_Curl_Parameter('metric1.midOperator', '/'),
			new Oara_Curl_Parameter('metric1.columnName1', 'programId'),
			new Oara_Curl_Parameter('metric1.operator1', '/'),
			new Oara_Curl_Parameter('metric1.columnName2', 'programId'),
			new Oara_Curl_Parameter('metric1.lastOperator', '/'),
			new Oara_Curl_Parameter('metric1.factor', ''),
			new Oara_Curl_Parameter('metric1.summaryType', 'NONE'),
			new Oara_Curl_Parameter('format', 'CSV'),
			new Oara_Curl_Parameter('separator', ';'),
			new Oara_Curl_Parameter('dateType', '1'),
			new Oara_Curl_Parameter('favoriteId', ''),
			new Oara_Curl_Parameter('favoriteName', ''),
			new Oara_Curl_Parameter('favoriteDescription', '')
		);

	}

	private function oldLogin() {
		$user = $this->_credentials['user'];
		$password = $this->_credentials['password'];
		$loginUrl = 'http://publisher.tradedoubler.com/pan/login';

		$valuesLogin = array(new Oara_Curl_Parameter('j_username', $user),
			new Oara_Curl_Parameter('j_password', $password)
		);

		$credentials = $this->_credentials;
		$credentials["cookieName"] .= "old";
		$this->_oldClient = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

	}

	private function newLogin() {
		$user = $this->_credentials['user'];
		$password = $this->_credentials['password'];
		$loginUrl = 'https://member.impactradius.co.uk/secure/login.user';

		$valuesLogin = array(new Oara_Curl_Parameter('j_username', $user),
			new Oara_Curl_Parameter('j_password', $password)
		);

		$credentials = $this->_credentials;
		$credentials["cookieName"] .= "new";
		$this->_newClient = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://member.impactradius.co.uk/secure/mediapartner/accountSettings/mp-wsapi-flow.ihtml?', array());
		$exportReport = $this->_newClient->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('div .uitkFields');
		$count = count($results); // get number of matches: 4
		$i = 0;
		foreach ($results as $result) {
			if ($i == 0) {
				$this->_accountSid = str_replace(array("\n", "\t", " "), "", $result->nodeValue);
			} else {
				$this->_authToken = str_replace(array("\n", "\t", " "), "", $result->nodeValue);
			}
			$i++;
		}

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;

		//Checking connection for trade doubler website
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://publisher.tradedoubler.com/pan/aReport3Selection.action?reportName=aAffiliateProgramOverviewReport', array());
		$exportReport = $this->_oldClient->get($urls);
		if (preg_match("/\(([a-zA-Z]{0,2}[\/\.][a-zA-Z]{0,2}[\/\.][a-zA-Z]{0,2})\)/", $exportReport[0], $match)) {
			$this->_dateFormat = $match[1];
			$this->_oldAccess = true;
		}

		//Checking connection for the impact Radius website
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://member.impactradius.co.uk/secure/mediapartner/home/pview.ihtml', array());
		$exportReport = $this->_newClient->get($urls);
		$newCheck = false;
		if (preg_match("/\/logOut\.user/", $exportReport[0], $match)) {
			$newCheck = true;
		}

		$newApi = false;
		if ($newCheck && $this->_authToken != null && $this->_accountSid != null) {
			//Checking API connection from Impact Radius
			$uri = "https://".$this->_accountSid.":".$this->_authToken."@api.impactradius.com/2010-09-01/Mediapartners/".$this->_accountSid."/Campaigns.xml";
			$res = simplexml_load_file($uri);
			if (isset($res->Campaigns)) {
				$newApi = true;
			}

		}

		if ($newCheck && $newApi) {
			$this->_newAccess = true;
		}

		if ($this->_oldAccess || $this->_newAccess) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 *
	 * Format Csv
	 * @param unknown_type $csv
	 */
	private function formatCsv($csv) {
		preg_match_all("/\"([^\"]+?)\",/", $csv, $matches);
		foreach ($matches[1] as $match) {
			if (preg_match("/,/", $match)) {
				$rep = preg_replace("/,/", "", $match);
				$csv = str_replace($match, $rep, $csv);
				$match = $rep;
			}
			if (preg_match("/\n/", $match)) {
				$rep = preg_replace("/\n/", "", $match);
				$csv = str_replace($match, $rep, $csv);
			}
		}
		return $csv;
	}

	/**
	 * It returns the Merchant CVS report.
	 * @return $exportReport
	 */
	private function getExportMerchantReport($content) {
		$merchantReport = self::formatCsv($content);

		$exportData = str_getcsv($merchantReport, "\r\n");
		$merchantReportList = Array();
		$num = count($exportData);
		for ($i = 3; $i < $num; $i++) {
			$merchantExportArray = str_getcsv($exportData[$i], ",");

			if ($merchantExportArray[2] != '' && $merchantExportArray[4] != '') {
				$merchantReportList[$merchantExportArray[4]] = $merchantExportArray[2];
			}
		}
		return $merchantReportList;
	}

	/**
	 * It returns an array with the different merchants
	 * @return array
	 */
	private function getMerchantReportList() {

		if ($this->_oldAccess) {

			$merchantReportList = Array();
			$valuesFormExport = $this->_oldExportMerchantParameters;
			$valuesFormExport[] = new Oara_Curl_Parameter('programAffiliateStatusId', '3');

			$urls = array();
			$urls[] = new Oara_Curl_Request('http://publisher.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
			$exportReport = $this->_oldClient->post($urls);
			$exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
			$merchantReportList = self::getExportMerchantReport($exportReport[0]);

			$valuesFormExport = $this->_oldExportMerchantParameters;
			$valuesFormExport[] = new Oara_Curl_Parameter('programAffiliateStatusId', '4');

			$urls = array();
			$urls[] = new Oara_Curl_Request('http://publisher.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
			$exportReport = $this->_oldClient->post($urls);
			$exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
			$merchantReportListAux = self::getExportMerchantReport($exportReport[0]);
			foreach ($merchantReportListAux as $key => $value) {
				$merchantReportList[$key] = $value;
			}
		}

		if ($this->_newAccess) {

			$uri = "https://".$this->_accountSid.":".$this->_authToken."@api.impactradius.com/2010-09-01/Mediapartners/".$this->_accountSid."/Campaigns.xml";
			$res = simplexml_load_file($uri);
			$currentPage = (int) $res->Campaigns->attributes()->page;
			$pageNumber = (int) $res->Campaigns->attributes()->numpages;
			while ($currentPage <= $pageNumber) {

				foreach ($res->Campaigns->Campaign as $campaign) {
					$campaignId = (int) $campaign->CampaignId;
					$campaignName = (string) $campaign->CampaignName;
					$merchantReportList[$campaignId] = $campaignName;
				}

				$currentPage++;
				$nextPageUri = (string) $res->Campaigns->attributes()->nextpageuri;
				if ($nextPageUri != null) {
					$res = simplexml_load_file("https://".$this->_accountSid.":".$this->_authToken."@api.impactradius.com".$nextPageUri);
				}
			}
		}
		return $merchantReportList;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchantReportList = self::getMerchantReportList();
		$merchants = Array();
		foreach ($merchantReportList as $key => $value) {
			$obj = Array();
			$obj['cid'] = $key;
			$obj['name'] = $value;
			$merchants[] = $obj;
		}

		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
		if ($this->_oldAccess) {
			self::oldlogin();

			$valuesFormExport = Oara_Utilities::cloneArray($this->_oldExportTransactionParameters);
			$valuesFormExport[] = new Oara_Curl_Parameter('startDate', self::formatDate($dStartDate));
			$valuesFormExport[] = new Oara_Curl_Parameter('endDate', self::formatDate($dEndDate));
			$urls = array();
			$urls[] = new Oara_Curl_Request('http://publisher.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
			$exportReport = $this->_oldClient->get($urls);
			$exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
			$exportData = str_getcsv($exportReport[0], "\r\n");
			$num = count($exportData);
			for ($i = 2; $i < $num - 1; $i++) {

				$transactionExportArray = str_getcsv($exportData[$i], ",");

				if (!isset($transactionExportArray[2])) {
					throw new Exception('Problem getting transaction\n\n');
				}

				if ($transactionExportArray[0] !== '' && in_array((int) $transactionExportArray[2], $merchantList)) {

					$transaction = Array();
					$transaction['merchantId'] = $transactionExportArray[2];
					$transactionDate = self::toDate($transactionExportArray[4]);
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
					if ($transactionExportArray[8] != '') {
						$transaction['unique_id'] = $transactionExportArray[8];
					} else
						if ($transactionExportArray[7] != '') {
							$transaction['unique_id'] = $transactionExportArray[7];
						} else {
							throw new Exception("No Identifier");
						}
					if ($transactionExportArray[9] != '') {
						$transaction['custom_id'] = $transactionExportArray[9];
					}

					if ($transactionExportArray[11] == 'A') {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
						if ($transactionExportArray[11] == 'P') {
							$transaction['status'] = Oara_Utilities::STATUS_PENDING;
						} else
							if ($transactionExportArray[11] == 'D') {
								$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
							}

					if ($transactionExportArray[7] != '') {
						$transaction['amount'] = $filter->filter($transactionExportArray[20]);
					} else {
						$transaction['amount'] = $filter->filter($transactionExportArray[19]);
					}

					$transaction['commission'] = $filter->filter($transactionExportArray[20]);
					$totalTransactions[] = $transaction;
				}
			}
		}

		if ($this->_newAccess) {
			//New Interface
			$uri = "https://".$this->_accountSid.":".$this->_authToken."@api.impactradius.com/2010-09-01/Mediapartners/".$this->_accountSid."/Actions?ActionDateStart=".$dStartDate->toString('yyyy-MM-ddTHH:mm:ss')."-00:00&ActionDateEnd=".$dEndDate->toString('yyyy-MM-ddTHH:mm:ss')."-00:00";
			$res = simplexml_load_file($uri);
			$currentPage = (int) $res->Actions->attributes()->page;
			$pageNumber = (int) $res->Actions->attributes()->numpages;
			while ($currentPage <= $pageNumber) {

				foreach ($res->Actions->Action as $action) {
					$transaction = Array();
					$transaction['merchantId'] = (int) $action->CampaignId;

					$transactionDate = new Zend_Date((string) $action->EventDate, "yyyy-MM-dd HH:mm:ss");
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

					$transaction['unique_id'] = (string) $action->Id;
					if ((string) $action->SharedId != '') {
						$transaction['custom_id'] = (string) $action->SharedId;
					}

					$status = (string) $action->CampaignId;
					if ($status == 'APPROVED') {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
						if ($status == 'REJECTED') {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						} else {
							$transaction['status'] = Oara_Utilities::STATUS_PENDING;
						}

					$transaction['amount'] = (double) $action->Amount;
					$transaction['commission'] = (double) $action->Payout;
					$totalTransactions[] = $transaction;
				}

				$currentPage++;
				$nextPageUri = (string) $res->Actions->attributes()->nextpageuri;
				if ($nextPageUri != null) {
					$res = simplexml_load_file("https://".$this->_accountSid.":".$this->_authToken."@api.impactradius.com".$nextPageUri);
				}
			}
		}
		return $totalTransactions;

	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
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

	public function checkReportError($content, $request, $try = 0) {

		if (preg_match("/\/report\/published\/aAffiliateEventBreakdownReport/", $content, $matches)) {
			//report too big, we have to download it and read it
			if (preg_match("/(\/report\/published\/(aAffiliateEventBreakdownReport(.*))\.zip)/", $content, $matches)) {

				$file = "http://publisher.tradedoubler.com".$matches[0];
				$newfile = realpath(dirname(__FILE__)).'/../data/pdf/'.$matches[2].'.zip';

				if (!copy($file, $newfile)) {
					throw new Exception('Failing copying the zip file \n\n');
				}
				$zip = new ZipArchive();
				if ($zip->open($newfile, ZIPARCHIVE::CREATE) !== TRUE) {
					throw new Exception('Cannot open zip file \n\n');
				}
				$zip->extractTo(realpath(dirname(__FILE__)).'/../data/pdf');
				$zip->close();

				$unzipFilePath = realpath(dirname(__FILE__)).'/../data/pdf/'.$matches[2];
				$fileContent = file_get_contents($unzipFilePath);
				unlink($newfile);
				unlink($unzipFilePath);
				return $fileContent;
			}

			throw new Exception('Report too big \n\n');

		} else
			if (preg_match("/ error/", $content, $matches)) {
				$urls = array();
				$urls[] = $request;
				$exportReport = $this->_oldClient->get($urls);
				$try++;
				if ($try < 5) {
					return self::checkReportError($exportReport[0], $request, $try);
				} else {
					throw new Exception('Problem checking report\n\n');
				}

			} else {
				return $content;
			}

	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
		if ($this->_oldAccess) {
			$urls = array();
			$urls[] = new Oara_Curl_Request('http://publisher.tradedoubler.com/pan/reportSelection/Payment?', array());
			$exportReport = $this->_oldClient->get($urls);
			/*** load the html into the object ***/
			$doc = new DOMDocument();
			libxml_use_internal_errors(true);
			$doc->validateOnParse = true;
			$doc->loadHTML($exportReport[0]);
			$selectList = $doc->getElementsByTagName('select');
			$paymentSelect = null;
			if ($selectList->length > 0) {
				// looking for the payments select
				$it = 0;
				while ($it < $selectList->length) {
					$selectName = $selectList->item($it)->attributes->getNamedItem('name')->nodeValue;
					if ($selectName == 'payment_id') {
						$paymentSelect = $selectList->item($it);
						break;
					}
					$it++;
				}
				if ($paymentSelect != null) {
					$paymentLines = $paymentSelect->childNodes;
					for ($i = 0; $i < $paymentLines->length; $i++) {
						$pid = $paymentLines->item($i)->attributes->getNamedItem("value")->nodeValue;
						if (is_numeric($pid)) {
							$obj = array();

							$paymentLine = $paymentLines->item($i)->nodeValue;
							$paymentLine = htmlentities($paymentLine);
							$paymentLine = str_replace("&Acirc;&nbsp;", "", $paymentLine);
							$paymentLine = html_entity_decode($paymentLine);

							$date = self::toDate(substr($paymentLine, 0, 10));

							$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
							$obj['pid'] = $pid;
							$obj['method'] = 'BACS';
							if (preg_match("/[-+]?[0-9]*,?[0-9]*\.?[0-9]+/", substr($paymentLine, 10), $matches)) {
								$obj['value'] = $filter->filter($matches[0]);
							} else {
								throw new Exception("Problem reading payments");
							}

							$paymentHistory[] = $obj;
						}
					}
				}
			}
		}
		if ($this->_newAccess) {

			$urls = array();
			$urls[] = new Oara_Curl_Request('https://member.impactradius.co.uk/secure/nositemesh/accounting/getPayStubParamsCSV.csv', array());
			$exportReport = $this->_newClient->get($urls);
			$exportData = str_getcsv($exportReport[0], "\n");

			$num = count($exportData);
			for ($i = 1; $i < $num; $i++) {
				$paymentExportArray = str_getcsv($exportData[$i], ",");

				$obj = array();

				$date = new Zend_Date($paymentExportArray[1], "dd MMM, yyyy");

				$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$obj['pid'] = $paymentExportArray[0];
				$obj['method'] = 'BACS';
				if (preg_match("/[-+]?[0-9]*,?[0-9]*\.?[0-9]+/", $paymentExportArray[6], $matches)) {
					$obj['value'] = $filter->filter($matches[0]);
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
	 * Add Dates in a certain format to the criteriaList
	 * @param array $criteriaList
	 * @param array $dateArray
	 * @throws Exception
	 */
	private function formatDate($date) {
		$dateString = "";
		if ($this->_dateFormat == 'dd/MM/yy') {
			$dateString = $date->toString('dd/MM/yyyy');
		} else
			if ($this->_dateFormat == 'M/d/yy') {
				$dateString = $date->toString('M/d/yy');
			} else
				if ($this->_dateFormat == 'd/MM/yy') {
					$dateString = $date->toString('d/MM/yy');
				} else
					if ($this->_dateFormat == 'tt.MM.uu') {
						$dateString = $date->toString('dd.MM.yy');
					} else {
						throw new Exception("\n Date Format not supported ".$this->_dateFormat."\n");
					}
		return $dateString;
	}
	/**
	 *
	 * Date String to Object
	 * @param unknown_type $dateString
	 * @throws Exception
	 */
	private function toDate($dateString) {
		$transactionDate = null;
		if ($this->_dateFormat == 'dd/MM/yy') {
			$transactionDate = new Zend_Date(trim($dateString), "dd/MM/yy HH:mm:ss");
		} else
			if ($this->_dateFormat == 'M/d/yy') {
				$transactionDate = new Zend_Date(trim($dateString), "M/d/yy HH:mm:ss");
			} else
				if ($this->_dateFormat == 'd/MM/yy') {
					$transactionDate = new Zend_Date(trim($dateString), "d/MM/yy HH:mm:ss");
				} else
					if ($this->_dateFormat == 'tt.MM.uu') {
						$transactionDate = new Zend_Date(trim($dateString), "dd.MM.yy HH:mm:ss");
					} else {
						throw new Exception("\n Date Format not supported ".$this->_dateFormat."\n");
					}
		return $transactionDate;
	}

}
