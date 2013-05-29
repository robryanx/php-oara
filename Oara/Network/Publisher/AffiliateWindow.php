<?php
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_AffiliateWindow
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AffiliateWindow extends Oara_Network {
	/**
	 * Soap client.
	 */
	private $_apiClient = null;

	/**
	 * Converter configuration for the merchants.
	 * @var array
	 */
	private $_merchantConverterConfiguration = Array(
		'iId'			 => 'cid',
		'sName'			 => 'name',
		'sDisplayUrl'	 => 'url'
		);

		/**
		 * Converter configuration for the transactions.
		 * @var array
		 */
		private $_transactionConverterConfiguration = Array(
		'sStatus'			 => 'status',
		'fSaleAmount'		 => 'amount',
		'fCommissionAmount'	 => 'commission',
		'dTransactionDate'	 => 'date',
		'sClickref'			 => 'custom_id',
		'iMerchantId'		 => 'merchantId',
		'iId'				 => 'unique_id'
		);
		/**
		 * merchantMap.
		 * @var array
		 */
		private $_merchantMap = array();

		/**
		 * page Size.
		 */
		private $_pageSize = 100;

		/**
		 * User Id
		 */
		private $_userId = null;

		/**
		 * Constructor.
		 * @param $affiliateWindow
		 * @return Oara_Network_Publisher_Aw_Api
		 */
		public function __construct($credentials) {
			ini_set('default_socket_timeout', '120');
			$user = $credentials['user'];
			$password = $credentials['apiPassword'];
			$passwordExport = $credentials['password'];

			$this->_exportOverviewParameters = array(new Oara_Curl_Parameter('post', 'yes'), new Oara_Curl_Parameter('merchant', ''), new Oara_Curl_Parameter('limit', '25'), new Oara_Curl_Parameter('submit.x', '75'), new Oara_Curl_Parameter('submit.y', '11'), new Oara_Curl_Parameter('submit', 'submit'));

			//Login to the website
			$validator = new Zend_Validate_EmailAddress();
			if ($validator->isValid($user)) {
				//login through darwin
				$loginUrl = 'https://darwin.affiliatewindow.com/login?';

				$valuesLogin = array(new Oara_Curl_Parameter('email', $user), new Oara_Curl_Parameter('password', $passwordExport), new Oara_Curl_Parameter('formuserloginlogin', ''));
				$this->_exportClient = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

				$urls = array();
				$urls[] = new Oara_Curl_Request('https://darwin.affiliatewindow.com/user/', array());
				$exportReport = $this->_exportClient->get($urls);
				if (preg_match_all("/id=\"goDarwin(.*)\"/", $exportReport[0], $matches)) {

					foreach ($matches[1] as $user) {
						$urls = array();
						$urls[] = new Oara_Curl_Request('https://darwin.affiliatewindow.com/awin/affiliate/'.$user, array());
						$exportReport = $this->_exportClient->get($urls);
						if (preg_match("/\/user\/redirect\/v1\/type\/affiliate\/r\/(.*?)\">/", $exportReport[0], $matches)) {
							$urls = array();
							$urls[] = new Oara_Curl_Request('https://darwin.affiliatewindow.com/user/redirect/v1/type/affiliate/r/'.$matches[1], array());
							$exportReport = $this->_exportClient->get($urls);
							$dom = new Zend_Dom_Query($exportReport[0]);
							$apiPassword = $dom->query('#aw_api_password_hash');
							$apiPassword = $apiPassword->current();
							if ($apiPassword != null && $apiPassword->nodeValue == $password) {
								$this->_userId = $user;
								break;
							}

						} else {
							throw new Exception("It couldn't connect to darwin");
						}
					}
				}
			} else {
				throw new Exception("It's not an email");
			}

			$nameSpace = 'http://api.affiliatewindow.com/';

			$wsdlUrl = 'http://api.affiliatewindow.com/v4/AffiliateService?wsdl';
			//Setting the client.
			$this->_apiClient = new Oara_Import_Soap_Client($wsdlUrl, array('login' => $user, 'encoding' => 'UTF-8', 'password' => $password, 'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE, 'soap_version' => SOAP_1_1));
			//Setting the headers
			$soapHeader1 = new SoapHeader($nameSpace, 'UserAuthentication', array('iId' => $user, 'sPassword' => $password, 'sType' => 'affiliate'), true, $nameSpace);

			$soapHeader2 = new SoapHeader($nameSpace, 'getQuota', true, true, $nameSpace);

			//Adding the headers
			$this->_apiClient->addSoapInputHeader($soapHeader1, true);
			$this->_apiClient->addSoapInputHeader($soapHeader2, true);

		}
		/**
		 * Check the connection
		 */
		public function checkConnection() {
			$connection = false;
			try {

				$params = Array();
				$params['sRelationship'] = 'joined';
				$this->_apiClient->getMerchantList($params);

				$connection = true;
			} catch (Exception $e) {

			}
			return $connection;
		}
		/**
		 * (non-PHPdoc)
		 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
		 */
		public function getMerchantList() {
			$params = Array();
			$params['sRelationship'] = 'joined';
			$merchants = $this->_apiClient->getMerchantList($params)->getMerchantListReturn;
			$arrayMerchantIds = Array();
			foreach ($merchants as $merchant) {
				$arrayMerchantIds[] = $merchant->iId;
				$arrayCountry[$merchant->oPrimaryRegion->sCountryCode] = "";
			}

			$merchants = self::getMerchant($arrayMerchantIds);

			return $merchants;
		}
		/**
		 * Get the merchant for an Id
		 * @param integer $merchantId
		 * @return array
		 */
		public function getMerchant(array $merchantIds = null) {
			$merchantList = array();

			if ($merchantIds != null) {
				$iteration = 0;
				$arraySlice = array_slice($merchantIds, $this->_pageSize * $iteration, $this->_pageSize);
				while (!empty($arraySlice)) {
					$params = array();
					$params['aMerchantIds'] = $arraySlice;

					$merchantApiList = $this->_apiClient->getMerchant($params)->getMerchantReturn;
					$merchantList = array_merge($merchantList, Oara_Utilities::soapConverter($merchantApiList, $this->_merchantConverterConfiguration));
					$iteration++;
					$arraySlice = array_slice($merchantIds, $this->_pageSize * $iteration, $this->_pageSize);
				}

			}
			return $merchantList;
		}
		/**
		 * (non-PHPdoc)
		 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
		 */
		public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
			$totalTransactions = array();

			$dStartDate = clone $dStartDate;
			$dStartDate->setHour("00");
			$dStartDate->setMinute("00");
			$dStartDate->setSecond("00");
			$dEndDate = clone $dEndDate;
			$dEndDate->setHour("23");
			$dEndDate->setMinute("59");
			$dEndDate->setSecond("59");

			$params = array();
			$params['sDateType'] = 'transaction';
			if ($merchantList != null) {
				$params['aMerchantIds'] = $merchantList;
			}
			if ($dStartDate != null) {
				$params['dStartDate'] = $dStartDate->toString("yyyy-MM-ddTHH:mm:ss");
			}
			if ($dEndDate != null) {
				$params['dEndDate'] = $dEndDate->toString("yyyy-MM-ddTHH:mm:ss");
			}

			$params['iOffset'] = null;

			$params['iLimit'] = $this->_pageSize;
			$transactionList = $this->_apiClient->getTransactionList($params);
			if (sizeof($transactionList->getTransactionListReturn) > 0) {
				$iteration = self::calculeIterationNumber($transactionList->getTransactionListCountReturn->iRowsAvailable, $this->_pageSize);
				unset($transactionList);
				for ($j = 0; $j < $iteration; $j++) {
					$params['iOffset'] = $this->_pageSize * $j;
					$transactionList = $this->_apiClient->getTransactionList($params);

					foreach ($transactionList->getTransactionListReturn as $transactionObject){
						$transaction = Array();
						$transaction['unique_id'] = $transactionObject->iId;
						$transaction['merchantId'] = $transactionObject->iMerchantId;
						$date = new Zend_Date($transactionObject->dTransactionDate, "dd-MM-yyyyTHH:mm:ss");
						$transaction['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");

						if ($transactionObject->sClickref != null) {
							$transaction['custom_id'] = $transactionObject->sClickref;
						}

						$transaction['status'] = $transactionObject->sStatus;
						$transaction['amount'] = $transactionObject->mSaleAmount->dAmount;
						$transaction['commission'] = $transactionObject->mCommissionAmount->dAmount;
						$totalTransactions[] = $transaction;
					}

					unset($transactionList);
					gc_collect_cycles();
				}

			}
			return $totalTransactions;
		}
		/**
		 * (non-PHPdoc)
		 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
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
		 * (non-PHPdoc)
		 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
		 */
		public function getPaymentHistory() {
			$paymentHistory = array();

			$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));

			$urls = array();
			$urls[] = new Oara_Curl_Request("https://darwin.affiliatewindow.com/awin/affiliate/".$this->_userId."/payments/history?", array());
			$exportReport = $this->_exportClient->get($urls);

			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('table tbody tr');

			$finished = false;
			while (!$finished) {
				foreach ($results as $result) {
					$linkList = $result->getElementsByTagName('a');
					if ($linkList->length > 0) {
						$obj = array();
						$date = new Zend_Date($linkList->item(0)->nodeValue, "EEEE,MMMM dd,yyyy");
						$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
						$attrs = $linkList->item(0)->attributes;
						foreach ($attrs as $attrName => $attrNode) {
							if ($attrName = 'href') {
								$parseUrl = trim($attrNode->nodeValue);
								if (preg_match("/\/region\/gb\/paymentId\/(.+)/", $parseUrl, $matches)) {
									$obj['pid'] = $matches[1];
								}
							}
						}

						$obj['value'] = $filter->filter($linkList->item(3)->nodeValue);
						$obj['method'] = trim($linkList->item(2)->nodeValue);
						$paymentHistory[] = $obj;
					}
				}

				$results = $dom->query('#nextPage');
				if (count($results) > 0) {
					$nextPageLink = $results->current();
					$linkList = $nextPageLink->getElementsByTagName('a');
					$attrs = $linkList->item(0)->attributes;
					$nextPageUrl = null;
					foreach ($attrs as $attrName => $attrNode) {
						if ($attrName = 'href') {
							$nextPageUrl = trim($attrNode->nodeValue);
						}
					}
					$urls = array();
					$urls[] = new Oara_Curl_Request("https://darwin.affiliatewindow.com".$nextPageUrl, array());
					$exportReport = $this->_exportClient->get($urls);
					$dom = new Zend_Dom_Query($exportReport[0]);
					$results = $dom->query('table tbody tr');
				} else {
					$finished = true;
				}
			}

			return $paymentHistory;
		}
		/**
		 *
		 * It returns the transactions for a payment
		 * @param int $paymentId
		 */
		public function paymentTransactions($paymentId, $merchantList, $startDate) {
			$transactionList = array();
			$urls = array();
			$urls[] = new Oara_Curl_Request("https://darwin.affiliatewindow.com/awin/affiliate/".$this->_userId."/payments/download/paymentId/".$paymentId, array());
			$exportReport = $this->_exportClient->get($urls);
			$exportData = str_getcsv($exportReport[0], "\n");
			$num = count($exportData);
			for ($j = 1; $j < $num; $j++) {
				$transactionArray = str_getcsv($exportData[$j], ",");
				$transactionList[] = end($transactionArray);
			}
			return $transactionList;
		}
}
