<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Cj
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_CommissionJunction extends Oara_Network {
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
	 * Export Transaction Parameters
	 * @var array
	 */
	private $_exportOverviewParameters = null;

	/**
	 * merchantMap.
	 * @var array
	 */
	private $_merchantMap = array();

	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;

	/**
	 * Client
	 * @var unknown_type
	 */
	private $_apiClient = null;
	/**
	 * Member id
	 * @var int
	 */
	private $_memberId = null;
	/**
	 * API Password
	 * @var string
	 */
	private $_apiPassword = null;
	/**
	 * Constructor and Login
	 * @param $cj
	 * @return Oara_Network_Publisher_Cj_Export
	 */
	public function __construct($credentials) {

		$user = $credentials['user'];
		$password = $credentials['password'];
		$this->_apiPassword = $credentials['apiPassword'];
		

		$loginUrl = 'https://members.cj.com/member/foundation/memberlogin.do?';
		$valuesLogin = array(new Oara_Curl_Parameter('uname', $user),
			new Oara_Curl_Parameter('pw', $password),
			new Oara_Curl_Parameter('submit.x', '6'),
			new Oara_Curl_Parameter('submit.y', '8')
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$this->_exportMerchantParameters = array(new Oara_Curl_Parameter('sortKey', 'active_start_date'),
			new Oara_Curl_Parameter('sortOrder', 'DESC'),
			new Oara_Curl_Parameter('contractView', 'ALL'),
			new Oara_Curl_Parameter('contractView', 'ALL'),
			new Oara_Curl_Parameter('format', '6'),
			new Oara_Curl_Parameter('contractState', 'active'),
			new Oara_Curl_Parameter('column', 'merchantid'),
			new Oara_Curl_Parameter('column', 'websitename'),
			new Oara_Curl_Parameter('column', 'merchantcategory')
		);

		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('actionname', '0'),
			new Oara_Curl_Parameter('period', 'range'),
			new Oara_Curl_Parameter('what', 'commDetail'),
			new Oara_Curl_Parameter('corrected', ''),
			new Oara_Curl_Parameter('dtType', 'event'),
			new Oara_Curl_Parameter('filterby', '-1'),
			new Oara_Curl_Parameter('actiontype', ''),
			new Oara_Curl_Parameter('status', ''),
			new Oara_Curl_Parameter('filter', ''),
			new Oara_Curl_Parameter('website', ''),
			new Oara_Curl_Parameter('preselectrange', 'today'),
			new Oara_Curl_Parameter('download', 'csv')
		);
		$this->_exportOverviewParameters = array(
			new Oara_Curl_Parameter('perfPubByWebsite', ''),
			new Oara_Curl_Parameter('periodValue', ''),
			new Oara_Curl_Parameter('what_name', 'All Web Sites'),
			new Oara_Curl_Parameter('what', 'perfPubByAdvCompany'),
			new Oara_Curl_Parameter('period', 'range'),
			new Oara_Curl_Parameter('download', 'csv')
		);

		$this->_exportPaymentParameters = array(new Oara_Curl_Parameter('startRow', '0'),
			new Oara_Curl_Parameter('sortKey', ''),
			new Oara_Curl_Parameter('sortOrder', ''),
			new Oara_Curl_Parameter('format', '6'),
			new Oara_Curl_Parameter('button', 'Go')
		);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://members.cj.com/member/publisher/home.do', array());
		$result = $this->_client->get($urls);
		if (preg_match("/a href=\"\/member\/(.*)?\/publisher/", $result[0], $matches)) {
			$this->_memberId = trim($matches[1]);
		} else {
			return false;
		}
		
		$restUrl = 'https://commission-detail.api.cj.com/v3/commissions?date-type=event';
		$client = new Zend_Http_Client($restUrl);
		$client->setHeaders('Authorization', $this->_apiPassword);
		$response = $client->request('GET');
		if ($response->getStatus() != 200) {
			return false;
		}
		
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();
		$merchantsExport = self::getMerchantExport();
		foreach ($merchantsExport as $merchantData) {
			$obj = Array();
			$obj['cid'] = $merchantData[0];
			$obj['name'] = $merchantData[1];
			$merchants[] = $obj;
		}
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($idMerchant, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		
		$totalTransactions = Array();
		//The end data for the API has to be one day more
		$iteration = self::calculeIterationNumber(count($merchantList), '20');
		for ($it = 0; $it < $iteration; $it++) {
			echo "iteration $it of $iteration \n\n";
			//echo "mechant".$cid." ".count($totalTransactions)."\n\n";
			$merchantSlice = array_slice($merchantList, $it * 20, 20);
			try {

				$transactionDateEnd = clone $dEndDate;
				$transactionDateEnd->addDay(1);
				$restUrl = 'https://commission-detail.api.cj.com/v3/commissions?cids='.implode(',', $merchantSlice).'&date-type=posting&start-date='.$dStartDate->toString("yyyy-MM-dd").'&end-date='.$transactionDateEnd->toString("yyyy-MM-dd");
				unset($transactionDateEnd);
				$totalTransactions = array_merge($totalTransactions, self::getTransactionsXml($restUrl, $merchantList));

			} catch (Exception $e) {

				$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
				$dateArraySize = sizeof($dateArray);
				for ($j = 0; $j < $dateArraySize; $j++) {
					$transactionDateEnd = clone $dateArray[$j];
					$transactionDateEnd->addDay(1);
					echo $dateArray[$j]->toString("yyyy-MM-dd")."\n\n";
					$restUrl = 'https://commission-detail.api.cj.com/v3/commissions?cids='.implode(',', $merchantSlice).'&date-type=posting&start-date='.$dateArray[$j]->toString("yyyy-MM-dd").'&end-date='.$transactionDateEnd->toString("yyyy-MM-dd");
					try {
						$totalTransactions = array_merge($totalTransactions, self::getTransactionsXml($restUrl, $merchantList));
					} catch (Exception $e) {
						$try = 0;
						$done = false;
						while (!$done && $try < 5) {
							try {
								$totalTransactions = array_merge($totalTransactions, self::transactionsByType(implode(',', $merchantSlice), $dateArray[$j], $transactionDateEnd, $merchantList));
								$done = true;
							} catch (Exception $e) {
								$try++;
								echo "try again $try\n\n";
							}
						}
						if ($try == 5) {
							throw new Exception("Couldn't get data from the Transaction");
						}

					}
					unset($transactionDateEnd);
				}
			}
		}
		return $totalTransactions;
	}

	private function getTransactionsXml($restUrl, $merchantList) {
		$totalTransactions = array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
		$client = new Zend_Http_Client($restUrl, array('timeout' => 180));
		$client->setHeaders('Authorization', $this->_apiPassword);
		$response = $client->request('GET');
		$xml = simplexml_load_string($response->getBody(), null, LIBXML_NOERROR | LIBXML_NOWARNING);
		if (isset($xml->commissions->commission)) {
			foreach ($xml->commissions->commission as $singleTransaction) {

				if (in_array((int) self::findAttribute($singleTransaction, 'cid'), $merchantList)) {
					
					$transaction = Array();
					$transaction['merchantId'] = self::findAttribute($singleTransaction, 'cid');
					$transactionDate = new Zend_Date(self::findAttribute($singleTransaction, 'event-date'), 'yyyy-MM-ddTHH:mm:ss');
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
					unset($transactionDate);

					if (self::findAttribute($singleTransaction, 'sid') != null) {
						$transaction['custom_id'] = self::findAttribute($singleTransaction, 'sid');
					}

					$transaction['unique_id'] = self::findAttribute($singleTransaction, 'commission-id');

					if (self::findAttribute($singleTransaction, 'action-status') == 'locked' || self::findAttribute($singleTransaction, 'action-status') == 'closed') {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
						if (self::findAttribute($singleTransaction, 'action-status') == 'extended' || self::findAttribute($singleTransaction, 'action-status') == 'new') {
							$transaction['status'] = Oara_Utilities::STATUS_PENDING;
						} else
							if (self::findAttribute($singleTransaction, 'action-status') == 'corrected') {
								$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
							}

					$transaction['amount'] = (double) $filter->filter(self::findAttribute($singleTransaction, 'sale-amount'));
					$transaction['commission'] = (double) $filter->filter(self::findAttribute($singleTransaction, 'commission-amount'));
					$totalTransactions[] = $transaction;
				}
			}
		}
		return $totalTransactions;
	}

	private function transactionsByType($cid, $startDate, $endDate, $merchantList) {
		$totalTransactions = array();
		$typeTransactions = array("bonus", "click", "impression", "sale", "lead", "advanced%20sale", "advanced%20lead", "performance%20incentive");
		foreach ($typeTransactions as $type) {
			//echo $type."\n\n";
			$restUrl = 'https://commission-detail.api.cj.com/v3/commissions?action-types='.$type.'&cids='.$cid.'&date-type=posting&start-date='.$startDate->toString("yyyy-MM-dd").'&end-date='.$endDate->toString("yyyy-MM-dd");
			$totalTransactions = array_merge($totalTransactions, self::getTransactionsXml($restUrl, $merchantList));
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
	 * Gets all the merchants and returns them in an array.
	 * @return array
	 */
	private function getMerchantExport() {
		$merchantReportList = Array();
		$valuesFromExport = $this->_exportMerchantParameters;

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://members.cj.com/member/'.$this->_memberId.'/publisher/accounts/listmyadvertisers.do', array());
		$exportReport = $this->_client->get($urls);
		if (!preg_match("/Sorry, No Results Found\./", $exportReport[0], $matches)) {
			$urls = array();
			$urls[] = new Oara_Curl_Request('https://members.cj.com/member/'.$this->_memberId.'/publisher/accounts/listmyadvertisers.do', $valuesFromExport);
			$exportReport = $this->_client->post($urls);
			$exportData = str_getcsv($exportReport[0], "\n");
			$merchantReportList = Array();
			$num = count($exportData);
			for ($i = 1; $i < $num; $i++) {
				$merchantExportArray = str_getcsv($exportData[$i], ",");
				$merchantReportList[] = $merchantExportArray;
			}
		}

		return $merchantReportList;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://members.cj.com/member/cj/publisher/paymentStatus', array());
		$exportReport = $this->_client->get($urls);
		if (preg_match("/\/publisher\/getpublisherpaymenthistory\.do/", $exportReport[0], $matches)) {
			$urls = array();
			$valuesFromExport = $this->_exportPaymentParameters;
			$urls[] = new Oara_Curl_Request('https://members.cj.com/member/'.$this->_memberId.'/publisher/getpublisherpaymenthistory.do?', $valuesFromExport);
			$exportReport = $this->_client->get($urls);
			$exportData = str_getcsv($exportReport[0], "\n");
			$num = count($exportData);
			for ($j = 1; $j < $num; $j++) {
				$paymentData = str_getcsv($exportData[$j], ",");
				$obj = array();
				$date = new Zend_Date($paymentData[0], "dd-MMM-yyyy HH:mm", 'en_US');
				$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$obj['value'] = Oara_Utilities::parseDouble($paymentData[1]);
				$obj['method'] = $paymentData[2];
				$obj['pid'] = $paymentData[6];
				$paymentHistory[] = $obj;
			}
		}
		return $paymentHistory;
	}

	/**
	 * Calculate the number of iterations needed
	 * @param $rowAvailable
	 * @param $rowsReturned
	 */
	private function calculeIterationNumber($rowAvailable, $rowsReturned) {
		$iterationDouble = (double) ($rowAvailable / $rowsReturned);
		$iterationInt = (int) ($rowAvailable / $rowsReturned);
		if ($iterationDouble > $iterationInt) {
			$iterationInt++;
		}
		return $iterationInt;
	}
	/**
	 * Cast the XMLSIMPLE object into string
	 * @param $object
	 * @param $attribute
	 * @return unknown_type
	 */
	private function findAttribute($object = null, $attribute = null) {
		$return = null;
		$return = trim($object->$attribute);
		return $return;
	}
}
